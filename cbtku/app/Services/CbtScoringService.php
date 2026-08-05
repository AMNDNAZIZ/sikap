<?php

namespace App\Services;

use App\Models\CbtAnswerModel;
use App\Models\CbtQuestionModel;

/**
 * CBT Scoring Service
 * 
 * Handles all business logic related to scoring and grading
 */
class CbtScoringService extends BaseService
{
    protected $answerModel;
    protected $questionModel;

    public function __construct()
    {
        parent::__construct();
        $this->answerModel = new CbtAnswerModel();
        $this->questionModel = new CbtQuestionModel();
    }

    /**
     * Get detailed score results with per-type breakdown
     * This is the NEW central method for all scoring displays (Student & Admin)
     * 
     * @param int $studentId
     * @param int $testId
     * @return array
     */
    public function getDetailedScoreResults(int $studentId, int $testId): array
    {
        try {
            $sessionModel = new \App\Models\CbtSessionModel();
            $session = $sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->first();

            if (!$session) {
                return ['error' => 'Session not found'];
            }

            $testModel = new \App\Models\CbtTestStatusModel();
            $test = $testModel->find($testId);
            
            if (!$test) {
                return ['error' => 'Test not found'];
            }

            // Get question_order from session
            $questionOrder = json_decode($session['question_order'] ?? '[]', true) ?? [];
            if (empty($questionOrder)) {
                $questions = $this->questionModel->where('bank_id', $test['bank_id'])->findAll();
            } else {
                $questions = $this->questionModel->whereIn('id', $questionOrder)->findAll();
            }

            // Get answers
            $answers = $this->answerModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->findAll();

            $answerMap = [];
            foreach ($answers as $a) {
                $answerMap[$a['question_id']] = $a['answer'] ?? '';
            }

            // Weights
            $weights = [
                'pg' => (float) ($test['bobot_pg'] ?? 0),
                'pgk' => (float) ($test['bobot_pg_kompleks'] ?? 0),
                'bs' => (float) ($test['bobot_bs'] ?? 0),
                'esai' => (float) ($test['bobot_esai'] ?? 0)
            ];

            // Initialize Stats
            $stats = [
                'pg' => ['total' => 0, 'earned' => 0, 'weight' => $weights['pg']],
                'pgk' => ['total' => 0, 'earned' => 0, 'weight' => $weights['pgk']],
                'bs' => ['total' => 0, 'earned' => 0, 'weight' => $weights['bs']],
                'esai' => ['total' => 0, 'earned' => 0, 'raw_score' => $session['essay_score'], 'weight' => $weights['esai']]
            ];

            // Breakdown
            $breakdown = [];

            foreach ($questions as $q) {
                $type = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
                $qid = $q['id'];
                $correct = $q['correct_answer'] ?? $q['correct_option'] ?? '';
                $studentAns = $answerMap[$qid] ?? '';

                $normScore = $this->compareAnswerNormalized($type, $studentAns, $correct);
                
                // Add to breakdown
                $breakdown[$qid] = [
                    'score' => $normScore,
                    'type' => $type
                ];

                if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                    $stats['pg']['total']++;
                    $stats['pg']['earned'] += $normScore;
                } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                    $stats['pgk']['total']++;
                    $stats['pgk']['earned'] += $normScore;
                } elseif ($type === 'benar_salah' || $type === 'bs') {
                    $stats['bs']['total']++;
                    $stats['bs']['earned'] += $normScore;
                } elseif (in_array($type, ['esai', 'essay'])) {
                    $stats['esai']['total']++;
                }
            }

            // Scaled scores (0-100) per type
            $stats['pg']['score'] = ($stats['pg']['total'] > 0) ? ($stats['pg']['earned'] / $stats['pg']['total']) * 100 : 0;
            $stats['pgk']['score'] = ($stats['pgk']['total'] > 0) ? ($stats['pgk']['earned'] / $stats['pgk']['total']) * 100 : 0;
            $stats['bs']['score'] = ($stats['bs']['total'] > 0) ? ($stats['bs']['earned'] / $stats['bs']['total']) * 100 : 0;
            // For Essay, 'score' is the raw manual input (0-100)
            $stats['esai']['score'] = (float) ($session['essay_score'] ?? 0);

            // Final Calculation Logic
            $finalScore = $this->calculateFinalScore($stats, $session['essay_score'], $weights);

            return [
                'stats' => $stats,
                'breakdown' => $breakdown, // Added breakdown
                'weights' => $weights,
                'final_score' => $finalScore,
                'is_graded' => ($session['essay_score'] !== null || $stats['esai']['total'] == 0)
            ];
        } catch (\Throwable $e) {
            $this->logError('CbtScoringService::getDetailedScoreResults', 'Failed', $e);
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Standardized comparison helper
     */
    public function compareAnswerNormalized($type, $studentAns, $correctAns): float
    {
        $type = strtolower(str_replace(' ', '_', $type ?? 'pg'));
        $studentAns = trim((string)($studentAns ?? ''));
        $correctAns = trim((string)($correctAns ?? ''));

        if ($studentAns === '' || $correctAns === '') return 0.0;

        // PG
        if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
            return (strtoupper($studentAns) === strtoupper($correctAns)) ? 1.0 : 0.0;
        }

        // PGK
        if ($type === 'pgk' || $type === 'pg_kompleks') {
            $cArr = array_filter(array_map('trim', explode(',', strtoupper($correctAns))));
            $sArr = array_filter(array_map('trim', explode(',', strtoupper($studentAns))));
            if (empty($cArr) || empty($sArr)) return 0.0;

            $cSel = count(array_intersect($sArr, $cArr));
            $iSel = count(array_diff($sArr, $cArr));
            $tCorr = count($cArr);
            $raw = $cSel - (0.5 * $iSel);
            return ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0.0;
        }

        // BS
        if ($type === 'bs' || $type === 'benar_salah') {
            $cArr = array_map('trim', explode(',', strtoupper($correctAns)));
            $sArr = array_map('trim', explode(',', strtoupper($studentAns)));
            $matches = 0;
            $tItems = count($cArr);
            for ($i = 0; $i < $tItems; $i++) {
                $sVal = $sArr[$i] ?? '';
                $cVal = $cArr[$i] ?? '';
                if ($sVal !== '' && $sVal === $cVal) $matches++;
            }
            return ($tItems > 0) ? ($matches / $tItems) : 0.0;
        }

        return 0.0;
    }

    /**
     * Logic for dynamic essay scaling
     */
    protected function calculateFinalScore(array $stats, $essayScore, array $weights): float
    {
        $objectiveContribution = 
            ($stats['pg']['score'] * $weights['pg'] / 100) +
            ($stats['pgk']['score'] * $weights['pgk'] / 100) +
            ($stats['bs']['score'] * $weights['bs'] / 100);

        // Scenario 1: No Essay questions exist at all
        if ($stats['esai']['total'] == 0) {
            // Scale objective parts to 100%
            $totalObjectiveWeight = $weights['pg'] + $weights['pgk'] + $weights['bs'];
            if ($totalObjectiveWeight > 0) {
                return round(($objectiveContribution / ($totalObjectiveWeight / 100)), 2);
            }
            return 0.0;
        }

        // Scenario 2: Essay exists but NOT GRADED yet (NULL)
        if ($essayScore === null) {
            // Scale objective parts to 100% as requested by user
            $totalObjectiveWeight = $weights['pg'] + $weights['pgk'] + $weights['bs'];
            if ($totalObjectiveWeight > 0) {
                return round(($objectiveContribution / ($totalObjectiveWeight / 100)), 2);
            }
            return round($objectiveContribution, 2); 
        }

        // Scenario 3: Essay exists and GRADED
        $essayContribution = ($stats['esai']['score'] * $weights['esai'] / 100);
        return round($objectiveContribution + $essayContribution, 2);
    }

    /**
     * Auto-score Pilihan Ganda (PG) questions
     * 
     * Automatically calculates scores for objective question types:
     * - PG (Pilihan Ganda / Multiple Choice)
     * - PGK (Pilihan Ganda Kompleks / Complex Multiple Choice)
     * - BS (Benar-Salah / True-False)
     * 
     * Scoring Logic:
     * - PG: Exact match with correct answer (case-insensitive)
     * - PGK: All selected options must match (order-independent)
     * - BS: All statements must match correct answers
     * 
     * @param int $studentId Student ID who took the test
     * @param int $testId Test ID being scored
     * @return float Total score for PG questions (sum of earned points)
     * 
     * @throws \Throwable If database query fails or test not found
     * 
     * @example
     * $service = new CbtScoringService();
     * $score = $service->autoScorePG(1, 10);
     * // Returns: 85.0 (total points earned from PG, PGK, BS questions)
     */
    public function autoScorePG(int $studentId, int $testId): float
    {
        $res = $this->getDetailedScoreResults($studentId, $testId);
        if (isset($res['error'])) return 0.0;
        
        return (float)($res['stats']['pg']['earned'] + $res['stats']['pgk']['earned'] + $res['stats']['bs']['earned']);
    }

    /**
     * Calculate total score including essay
     * 
     * Combines auto-scored objective questions (PG, PGK, BS) with manually graded essay scores.
     * 
     * Score Components:
     * - auto_score: Points from objective questions (calculated automatically)
     * - essay_score: Points from essay questions (graded manually by teacher)
     * - total_score: Sum of auto_score + essay_score
     * 
     * @param int $studentId Student ID who took the test
     * @param int $testId Test ID being scored
     * @return array Score breakdown with keys:
     *               - 'auto_score' (float): Score from objective questions
     *               - 'essay_score' (float): Score from essay questions
     *               - 'total_score' (float): Combined total score
     * 
     * @throws \Throwable If database query fails or session not found
     * 
     * @example
     * $service = new CbtScoringService();
     * $scores = $service->calculateTotalScore(1, 10);
     * // Returns: ['auto_score' => 75.0, 'essay_score' => 20.0, 'total_score' => 95.0]
     */
    public function calculateTotalScore(int $studentId, int $testId): array
    {
        $res = $this->getDetailedScoreResults($studentId, $testId);
        if (isset($res['error'])) return ['auto_score' => 0, 'essay_score' => 0, 'total_score' => 0];

        $auto = (float)($res['stats']['pg']['earned'] + $res['stats']['pgk']['earned'] + $res['stats']['bs']['earned']);
        
        return [
            'auto_score' => $auto,
            'essay_score' => $res['stats']['esai']['score'],
            'total_score' => $res['final_score']
        ];
    }

    /**
     * Calculate score with bobot (weight-based scoring)
     * This is the CORRECT logic that matches what users see in selesai() and hasil() pages
     * 
     * Formula: (PG% * bobot_pg/100) + (PGK% * bobot_pgk/100) + (BS% * bobot_bs/100) + (Esai * bobot_esai/100)
     * 
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @return float Final score with bobot applied
     */
    public function calculateScoreWithBobot(int $studentId, int $testId): float
    {
        $res = $this->getDetailedScoreResults($studentId, $testId);
        return isset($res['error']) ? 0.0 : (float)$res['final_score'];
    }

    /**
     * Validate all questions are answered
     * 
     * Checks if student has answered all questions completely:
     * - PG/PGK/Essay: Must have non-empty answer
     * - Benar-Salah: All statements must be answered (no partial answers allowed)
     * 
     * Validation Rules:
     * - Counts total questions vs answered questions
     * - Identifies incomplete Benar-Salah questions (missing statements)
     * - Identifies completely unanswered questions
     * 
     * @param int $studentId Student ID who took the test
     * @param int $testId Test ID being validated
     * @param int $bankId Question bank ID containing the questions
     * @return array Validation result with keys:
     *               - 'valid' (bool): True if all questions answered completely
     *               - 'incomplete_bs' (array): List of incomplete Benar-Salah question IDs
     *               - 'unanswered' (array): List of completely unanswered question IDs
     *               - 'total_questions' (int): Total number of questions
     *               - 'answered_count' (int): Number of questions with answers
     *               - 'error' (string): Error message if validation fails
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtScoringService();
     * $result = $service->validateAllAnswered(1, 10, 5);
     * if (!$result['valid']) {
     *     echo "Missing answers: " . count($result['unanswered']);
     *     echo "Incomplete BS: " . count($result['incomplete_bs']);
     * }
     */
    public function validateAllAnswered(int $studentId, int $testId, int $bankId): array
    {
        try {
            // Get all questions
            $questions = $this->questionModel->where('bank_id', $bankId)->findAll();
            
            // Get student answers
            $answers = $this->answerModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->findAll();

            // Build answer map
            $answerMap = [];
            foreach ($answers as $a) {
                $answerMap[$a['question_id']] = $a['answer'];
            }

            // Check for incomplete answers
            $incompleteBS = [];
            $unanswered = [];

            foreach ($questions as $q) {
                $qid = $q['id'];
                $type = strtolower($q['question_type'] ?? 'pg');
                $studentAnswer = $answerMap[$qid] ?? '';

                if (in_array($type, ['benar_salah', 'bs'])) {
                    // Check if all rows are answered
                    $rows = ['A', 'B', 'C', 'D', 'E'];
                    $studentArr = explode(',', $studentAnswer);
                    
                    $answeredOptions = 0;
                    foreach ($studentArr as $ans) {
                        if (trim($ans) !== '') {
                            $answeredOptions++;
                        }
                    }

                    // Count how many options exist
                    $existingOptions = 0;
                    foreach ($rows as $row) {
                        $col = 'option_' . strtolower($row);
                        if (!empty($q[$col])) {
                            $existingOptions++;
                        }
                    }

                    if ($answeredOptions < $existingOptions) {
                        $incompleteBS[] = $qid;
                    }
                } else {
                    // Check if answered
                    if (empty(trim($studentAnswer))) {
                        $unanswered[] = $qid;
                    }
                }
            }

            $isValid = empty($incompleteBS) && empty($unanswered);

            return [
                'valid' => $isValid,
                'incomplete_bs' => $incompleteBS,
                'unanswered' => $unanswered,
                'total_questions' => count($questions),
                'answered_count' => count($answerMap)
            ];
        } catch (\Throwable $e) {
            $this->logError('CbtScoringService::validateAllAnswered', 'Validation failed', $e);
            return [
                'valid' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sort letters in answer (for PG Kompleks)
     * 
     * Normalizes PG Kompleks answers by sorting letters alphabetically.
     * This allows order-independent comparison (e.g., "BCA" equals "ABC").
     * 
     * Process:
     * 1. Remove commas from answer string
     * 2. Split into individual characters
     * 3. Sort alphabetically
     * 4. Join back into string
     * 
     * @param string $answer Answer string (e.g., "B,C,A" or "BCA")
     * @return string Sorted answer (e.g., "ABC")
     * 
     * @example
     * $sorted = $this->sortLetters("D,B,A,C");
     * // Returns: "ABCD"
     */
    /**
     * Sort letters in answer (for PG Kompleks)
     * 
     * Normalizes PG Kompleks answers by sorting letters alphabetically.
     * This allows order-independent comparison (e.g., "BCA" equals "ABC").
     */
    protected function sortLetters(string $answer): string
    {
        $letters = str_split(str_replace(',', '', $answer));
        sort($letters);
        return implode('', $letters);
    }

    /**
     * Compare Benar-Salah answers
     */
    protected function compareBenarSalah(string $studentAnswer, string $correctAnswer): bool
    {
        $studentArr = explode(',', $studentAnswer);
        $correctArr = explode(',', $correctAnswer);

        if (count($studentArr) !== count($correctArr)) {
            return false;
        }

        for ($i = 0; $i < count($correctArr); $i++) {
            $correct = strtoupper(trim($correctArr[$i] ?? ''));
            $student = strtoupper(trim($studentArr[$i] ?? ''));

            if ($correct !== $student) {
                return false;
            }
        }

        return true;
    }
}
