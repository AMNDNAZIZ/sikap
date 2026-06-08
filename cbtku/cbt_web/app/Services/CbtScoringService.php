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
        try {
            // Get test info
            $testModel = new \App\Models\CbtTestStatusModel();
            $test = $testModel->find($testId);
            
            if (!$test) {
                $this->logError('CbtScoringService::autoScorePG', 'Test not found');
                return 0.0;
            }

            $bankId = $test['bank_id'];

            // Get all questions for this bank
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

            // Calculate scores
            $earnedPg = 0;
            $earnedPgk = 0;
            $earnedBs = 0;

            foreach ($questions as $q) {
                $type = strtolower($q['question_type'] ?? 'pg');
                $qid = $q['id'];
                $studentAnswer = $answerMap[$qid] ?? '';

                if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                    // PG scoring
                    $correctAnswer = strtoupper(trim($q['correct_answer'] ?? ''));
                    $studentAnswerUpper = strtoupper(trim($studentAnswer));
                    
                    if ($studentAnswerUpper === $correctAnswer) {
                        $earnedPg += (float) ($q['score'] ?? 1);
                    }
                } elseif (in_array($type, ['pg_kompleks', 'pgk'])) {
                    // PG Kompleks scoring
                    $correctAnswer = strtoupper(trim($q['correct_answer'] ?? ''));
                    $studentAnswerUpper = strtoupper(trim($studentAnswer));
                    
                    // Normalize: sort letters
                    $correctSorted = $this->sortLetters($correctAnswer);
                    $studentSorted = $this->sortLetters($studentAnswerUpper);
                    
                    if ($correctSorted === $studentSorted) {
                        $earnedPgk += (float) ($q['score'] ?? 1);
                    }
                } elseif (in_array($type, ['benar_salah', 'bs', 'true_false'])) {
                    // Benar-Salah scoring
                    $correctAnswer = $q['correct_answer'] ?? '';
                    
                    if ($this->compareBenarSalah($studentAnswer, $correctAnswer)) {
                        $earnedBs += (float) ($q['score'] ?? 1);
                    }
                }
            }

            $totalScore = $earnedPg + $earnedPgk + $earnedBs;
            
            $this->logDebug('CbtScoringService::autoScorePG', "Scored: PG={$earnedPg}, PGK={$earnedPgk}, BS={$earnedBs}, Total={$totalScore}");
            
            return $totalScore;
        } catch (\Throwable $e) {
            $this->logError('CbtScoringService::autoScorePG', 'Failed to auto-score', $e);
            return 0.0;
        }
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
        try {
            // Get auto-scored PG
            $autoScore = $this->autoScorePG($studentId, $testId);

            // Get essay score (manually graded)
            $sessionModel = new \App\Models\CbtStudentSessionModel();
            $session = $sessionModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->first();

            $essayScore = (float) ($session['essay_score'] ?? 0);
            $totalScore = $autoScore + $essayScore;

            return [
                'auto_score' => $autoScore,
                'essay_score' => $essayScore,
                'total_score' => $totalScore
            ];
        } catch (\Throwable $e) {
            $this->logError('CbtScoringService::calculateTotalScore', 'Failed to calculate total', $e);
            return [
                'auto_score' => 0,
                'essay_score' => 0,
                'total_score' => 0
            ];
        }
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
        try {
            $sessionModel = new \App\Models\CbtSessionModel();
            $session = $sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->first();

            if (!$session) {
                $this->logError('CbtScoringService::calculateScoreWithBobot', 'Session not found');
                return 0.0;
            }

            $testModel = new \App\Models\CbtTestStatusModel();
            $test = $testModel->find($testId);
            
            if (!$test) {
                $this->logError('CbtScoringService::calculateScoreWithBobot', 'Test not found');
                return 0.0;
            }

            // Get question_order from session (only questions shown to student)
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

            // Initialize stats
            $stats = [
                'pg' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_pg'] ?? 0)],
                'pgk' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_pg_kompleks'] ?? 0)],
                'bs' => ['total' => 0, 'earned' => 0, 'weight' => (float) ($test['bobot_bs'] ?? 0)],
                'esai' => ['total' => 0, 'earned' => (float) ($session['essay_score'] ?? 0), 'weight' => (float) ($test['bobot_esai'] ?? 0)]
            ];

            // Count by type
            foreach ($questions as $q) {
                $type = strtolower(str_replace(' ', '_', $q['question_type'] ?? 'pg'));
                $qid = $q['id'];
                $correctStr = $q['correct_answer'] ?? $q['correct_option'] ?? '';
                $studentAns = $answerMap[$qid] ?? '';

                if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                    $stats['pg']['total']++;
                    if ($correctStr !== '' && $studentAns !== '' && strtoupper(trim($studentAns)) === strtoupper(trim($correctStr))) {
                        $stats['pg']['earned']++;
                    }
                } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                    $stats['pgk']['total']++;
                    if (!empty($correctStr) && !empty($studentAns)) {
                        $cArr = explode(',', $correctStr);
                        $sArr = explode(',', $studentAns);
                        $cSel = count(array_intersect($sArr, $cArr));
                        $iSel = count(array_diff($sArr, $cArr));
                        $tCorr = count($cArr);
                        $raw = $cSel - (0.5 * $iSel);
                        $qScore = ($tCorr > 0) ? (max(0, $raw) / $tCorr) : 0;
                        $stats['pgk']['earned'] += $qScore;
                    }
                } elseif ($type === 'benar_salah' || $type === 'bs') {
                    $stats['bs']['total']++;
                    if (!empty($correctStr) && !empty($studentAns)) {
                        $cArr = explode(',', $correctStr);
                        $sArr = explode(',', $studentAns);
                        $matches = 0;
                        $totalItems = count($cArr);
                        for ($i = 0; $i < $totalItems; $i++) {
                            $stdAns = isset($sArr[$i]) ? trim($sArr[$i]) : '';
                            $keyAns = isset($cArr[$i]) ? trim($cArr[$i]) : '';
                            
                            if ($stdAns !== '' && strtoupper($stdAns) === strtoupper($keyAns)) {
                                $matches++;
                            }
                        }
                        $qScore = ($totalItems > 0) ? ($matches / $totalItems) : 0;
                        $stats['bs']['earned'] += $qScore;
                    }
                } else {
                    $stats['esai']['total']++;
                }
            }

            // Calculate final score with bobot
            $stats['pg']['score'] = ($stats['pg']['total'] > 0) ? ($stats['pg']['earned'] / $stats['pg']['total']) * 100 : 0;
            $stats['pgk']['score'] = ($stats['pgk']['total'] > 0) ? ($stats['pgk']['earned'] / $stats['pgk']['total']) * 100 : 0;
            $stats['bs']['score'] = ($stats['bs']['total'] > 0) ? ($stats['bs']['earned'] / $stats['bs']['total']) * 100 : 0;
            $stats['esai']['score'] = ($stats['esai']['total'] > 0) ? ($stats['esai']['earned']) : 0;

            $stats['pg']['contribution'] = $stats['pg']['score'] * ($stats['pg']['weight'] / 100);
            $stats['pgk']['contribution'] = $stats['pgk']['score'] * ($stats['pgk']['weight'] / 100);
            $stats['bs']['contribution'] = $stats['bs']['score'] * ($stats['bs']['weight'] / 100);
            $stats['esai']['contribution'] = $stats['esai']['score'] * ($stats['esai']['weight'] / 100);

            $finalScore = $stats['pg']['contribution'] + $stats['pgk']['contribution'] + $stats['bs']['contribution'] + $stats['esai']['contribution'];

            $this->logDebug('CbtScoringService::calculateScoreWithBobot', "Final score: {$finalScore}");

            return round($finalScore, 2);
        } catch (\Throwable $e) {
            $this->logError('CbtScoringService::calculateScoreWithBobot', 'Failed to calculate score', $e);
            return 0.0;
        }
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
    protected function sortLetters(string $answer): string
    {
        $letters = str_split(str_replace(',', '', $answer));
        sort($letters);
        return implode('', $letters);
    }

    /**
     * Compare Benar-Salah answers
     * 
     * Compares student's Benar-Salah answers with correct answers.
     * All statements must match for the question to be considered correct.
     * 
     * Comparison Logic:
     * - Splits both answers by comma
     * - Compares each statement position-by-position
     * - Case-insensitive comparison
     * - All positions must match exactly
     * 
     * @param string $studentAnswer Student's answer (e.g., "B,S,B,B")
     * @param string $correctAnswer Correct answer (e.g., "B,S,B,B")
     * @return bool True if all statements match, false otherwise
     * 
     * @example
     * $match = $this->compareBenarSalah("B,S,B", "B,S,B");
     * // Returns: true
     * 
     * $match = $this->compareBenarSalah("B,S,B", "B,B,S");
     * // Returns: false (order matters)
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
