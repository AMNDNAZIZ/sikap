<?php

namespace App\Services;

use App\Models\CbtQuestionModel;

/**
 * CBT Question Service
 * 
 * Handles all business logic related to questions:
 * - Fetching questions
 * - Shuffling questions and options
 * - Generating question order
 * - Parsing raw questions
 */
class CbtQuestionService extends BaseService
{
    protected $questionModel;

    public function __construct()
    {
        parent::__construct();
        $this->questionModel = new CbtQuestionModel();
    }

    /**
     * Get all questions for a test bank
     * 
     * Retrieves all questions from a specific question bank.
     * Results are cached for 10 minutes to improve performance.
     * 
     * @param int $bankId Question bank ID
     * @param bool $useCache Whether to use cache (default: true)
     * @return array Array of question records
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtQuestionService();
     * $questions = $service->getQuestionsForBank(5);
     * echo "Found " . count($questions) . " questions";
     */
    public function getQuestionsForBank(int $bankId, bool $useCache = true): array
    {
        try {
            if (!$useCache) {
                return $this->questionModel
                    ->where('bank_id', $bankId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
            }

            $cacheKey = 'cbt_all_questions_bank_' . $bankId;
            return cache_remember($cacheKey, 600, function() use ($bankId) {
                return $this->questionModel
                    ->where('bank_id', $bankId)
                    ->orderBy('id', 'ASC')
                    ->findAll();
            });
        } catch (\Throwable $e) {
            $this->logError('CbtQuestionService::getQuestionsForBank', 'Failed to get questions', $e);
            return [];
        }
    }

    /**
     * Get questions in session order
     * 
     * Retrieves questions based on the order stored in session.
     * Maintains the exact order as shown to student during exam.
     * 
     * @param array $questionOrder Array of question IDs in display order
     * @return array Array of question records sorted by question_order
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtQuestionService();
     * $questionOrder = [5, 2, 8, 1, 3];
     * $questions = $service->getQuestionsInOrder($questionOrder);
     * // Returns questions in order: 5, 2, 8, 1, 3
     */
    public function getQuestionsInOrder(array $questionOrder): array
    {
        try {
            if (empty($questionOrder)) {
                return [];
            }

            // Fetch questions
            $questions = $this->questionModel->whereIn('id', $questionOrder)->findAll();
            
            // Sort by question_order
            $questionMap = [];
            foreach ($questions as $q) {
                $questionMap[$q['id']] = $q;
            }
            
            $sortedQuestions = [];
            foreach ($questionOrder as $qid) {
                if (isset($questionMap[$qid])) {
                    $sortedQuestions[] = $questionMap[$qid];
                }
            }
            
            return $sortedQuestions;
        } catch (\Throwable $e) {
            $this->logError('CbtQuestionService::getQuestionsInOrder', 'Failed to get ordered questions', $e);
            return [];
        }
    }

    /**
     * Generate question order for a new session
     * 
     * Creates the question order for a student's exam session.
     * Handles:
     * - Grouping by question type (PG, PGK, BS, Essay)
     * - Shuffling within each type (if enabled)
     * - Slicing to show only specified count per type
     * - Merging all types in order
     * 
     * Process:
     * 1. Group questions by type
     * 2. Shuffle each type (if shuffle_question = 'ya')
     * 3. Slice each type to show_*_count
     * 4. Merge: PG → PGK → BS → Essay
     * 5. Return array of question IDs
     * 
     * @param int $bankId Question bank ID
     * @param array $testConfig Test configuration with keys:
     *                          - 'shuffle_question' (string): 'ya' or 'tidak'
     *                          - 'show_pg_count' (int): Number of PG questions to show
     *                          - 'show_pg_kompleks_count' (int): Number of PGK questions
     *                          - 'show_bs_count' (int): Number of BS questions
     *                          - 'show_esai_count' (int): Number of Essay questions
     * @return array Array of question IDs in display order
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtQuestionService();
     * $config = [
     *     'shuffle_question' => 'ya',
     *     'show_pg_count' => 20,
     *     'show_pg_kompleks_count' => 5,
     *     'show_bs_count' => 10,
     *     'show_esai_count' => 2
     * ];
     * $order = $service->generateQuestionOrder(5, $config);
     * // Returns: [12, 5, 8, ...] (37 question IDs total)
     */
    public function generateQuestionOrder(int $bankId, array $testConfig): array
    {
        try {
            // Get all questions
            $allQuestions = $this->getQuestionsForBank($bankId);
            
            // Group by type
            $grouped = $this->groupQuestionsByType($allQuestions);
            
            // Shuffle if enabled
            $shouldShuffle = ($testConfig['shuffle_question'] ?? 'tidak') === 'ya';
            if ($shouldShuffle) {
                shuffle($grouped['pg']);
                shuffle($grouped['pgk']);
                shuffle($grouped['bs']);
                shuffle($grouped['essay']);
            }
            
            // Slice based on test settings
            $showPg = (int) ($testConfig['show_pg_count'] ?? 0);
            $showPgk = (int) ($testConfig['show_pg_kompleks_count'] ?? 0);
            $showBs = (int) ($testConfig['show_bs_count'] ?? 0);
            $showEsai = (int) ($testConfig['show_esai_count'] ?? 0);
            
            $pgQuestions = ($showPg > 0) ? array_slice($grouped['pg'], 0, $showPg) : $grouped['pg'];
            $pgkQuestions = ($showPgk > 0) ? array_slice($grouped['pgk'], 0, $showPgk) : $grouped['pgk'];
            $bsQuestions = ($showBs > 0) ? array_slice($grouped['bs'], 0, $showBs) : $grouped['bs'];
            $essayQuestions = ($showEsai > 0) ? array_slice($grouped['essay'], 0, $showEsai) : $grouped['essay'];
            
            // Merge all types
            $mergedQuestions = array_merge($pgQuestions, $pgkQuestions, $bsQuestions, $essayQuestions);
            
            // Extract IDs
            $questionOrder = array_column($mergedQuestions, 'id');
            
            $this->logDebug('CbtQuestionService::generateQuestionOrder', "Generated order with " . count($questionOrder) . " questions");
            
            return $questionOrder;
        } catch (\Throwable $e) {
            $this->logError('CbtQuestionService::generateQuestionOrder', 'Failed to generate order', $e);
            return [];
        }
    }

    /**
     * Generate option orders for questions
     * 
     * Creates shuffled option orders for each question.
     * Only shuffles PG and PGK questions (if shuffle_option enabled).
     * BS and Essay questions are not shuffled.
     * 
     * Process:
     * 1. For each question, extract available options (A, B, C, D, E)
     * 2. If shuffle_option = 'ya' AND type is PG/PGK: shuffle options
     * 3. Store shuffled order for each question
     * 
     * @param array $questions Array of question records
     * @param bool $shouldShuffle Whether to shuffle options
     * @return array Associative array [question_id => ['A', 'B', 'C', ...]]
     * 
     * @throws \Throwable If processing fails
     * 
     * @example
     * $service = new CbtQuestionService();
     * $questions = [...]; // Array of questions
     * $optionOrders = $service->generateOptionOrders($questions, true);
     * // Returns: [1 => ['C', 'A', 'D', 'B'], 2 => ['B', 'D', 'A', 'C'], ...]
     */
    public function generateOptionOrders(array $questions, bool $shouldShuffle): array
    {
        try {
            $optionOrders = [];
            
            foreach ($questions as $q) {
                // Extract available options
                $opts = [];
                foreach (['A', 'B', 'C', 'D', 'E'] as $opt) {
                    $col = 'option_' . strtolower($opt);
                    if (!empty($q[$col])) {
                        $opts[$opt] = $q[$col];
                    }
                }
                
                $keys = array_keys($opts);
                $type = $this->normalizeQuestionType($q['question_type'] ?? 'pg');
                
                // Only shuffle PG and PGK types
                if ($shouldShuffle && in_array($type, ['pg', 'pg_kompleks'])) {
                    shuffle($keys);
                }
                
                $optionOrders[$q['id']] = $keys;
            }
            
            return $optionOrders;
        } catch (\Throwable $e) {
            $this->logError('CbtQuestionService::generateOptionOrders', 'Failed to generate option orders', $e);
            return [];
        }
    }

    /**
     * Parse raw question text
     * 
     * Delegates to model's parseRawQuestion method.
     * Extracts question text, type, and options from raw format.
     * 
     * @param string $rawText Raw question text
     * @return array Parsed question data with keys:
     *               - 'question' (string): Question text
     *               - 'type' (string): Question type
     *               - 'options' (array): Answer options
     * 
     * @throws \Throwable If parsing fails
     * 
     * @example
     * $service = new CbtQuestionService();
     * $parsed = $service->parseRawQuestion("[PG] What is 2+2? A. 3 B. 4 C. 5");
     * // Returns: ['question' => 'What is 2+2?', 'type' => 'pg', 'options' => [...]]
     */
    public function parseRawQuestion(string $rawText): array
    {
        try {
            return $this->questionModel->parseRawQuestion($rawText);
        } catch (\Throwable $e) {
            $this->logError('CbtQuestionService::parseRawQuestion', 'Failed to parse raw question', $e);
            return [
                'question' => $rawText,
                'type' => 'pg',
                'options' => []
            ];
        }
    }

    /**
     * Group questions by type
     * 
     * Separates questions into PG, PGK, BS, and Essay groups.
     * 
     * Type Mapping:
     * - PG: 'pg', 'pilihan_ganda', 'multiple_choice'
     * - PGK: 'pg_kompleks', 'pgk'
     * - BS: 'benar_salah', 'bs'
     * - Essay: Everything else
     * 
     * @param array $questions Array of question records
     * @return array Grouped questions with keys:
     *               - 'pg' (array): PG questions
     *               - 'pgk' (array): PGK questions
     *               - 'bs' (array): BS questions
     *               - 'essay' (array): Essay questions
     * 
     * @example
     * $service = new CbtQuestionService();
     * $grouped = $service->groupQuestionsByType($allQuestions);
     * echo "PG: " . count($grouped['pg']);
     * echo "PGK: " . count($grouped['pgk']);
     */
    public function groupQuestionsByType(array $questions): array
    {
        $grouped = [
            'pg' => [],
            'pgk' => [],
            'bs' => [],
            'essay' => []
        ];
        
        foreach ($questions as $q) {
            $type = $this->normalizeQuestionType($q['question_type'] ?? 'pg');
            
            if ($type === 'pg') {
                $grouped['pg'][] = $q;
            } elseif ($type === 'pg_kompleks') {
                $grouped['pgk'][] = $q;
            } elseif ($type === 'benar_salah') {
                $grouped['bs'][] = $q;
            } else {
                $grouped['essay'][] = $q;
            }
        }
        
        return $grouped;
    }

    /**
     * Normalize question type
     * 
     * Converts various question type formats to standardized format.
     * 
     * Normalization Rules:
     * - 'pg', 'pilihan_ganda', 'multiple_choice' → 'pg'
     * - 'pg_kompleks', 'pgk' → 'pg_kompleks'
     * - 'benar_salah', 'bs', 'true_false' → 'benar_salah'
     * - Everything else → 'essay'
     * 
     * @param string $type Raw question type
     * @return string Normalized type ('pg', 'pg_kompleks', 'benar_salah', 'essay')
     * 
     * @example
     * $normalized = $this->normalizeQuestionType('Pilihan Ganda');
     * // Returns: 'pg'
     */
    protected function normalizeQuestionType(string $type): string
    {
        $type = strtolower(str_replace(' ', '_', $type));
        
        if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
            return 'pg';
        } elseif (in_array($type, ['pg_kompleks', 'pgk'])) {
            return 'pg_kompleks';
        } elseif (in_array($type, ['benar_salah', 'bs', 'true_false'])) {
            return 'benar_salah';
        }
        
        return 'essay';
    }
}
