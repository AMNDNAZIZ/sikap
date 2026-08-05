<?php

namespace App\Services;

use App\Models\CbtAnswerModel;

/**
 * CBT Answer Service
 * 
 * Handles all business logic related to student answers
 */
class CbtAnswerService extends BaseService
{
    protected $answerModel;

    public function __construct()
    {
        parent::__construct();
        $this->answerModel = new CbtAnswerModel();
    }

    /**
     * Save single answer
     * 
     * Saves or updates a student's answer to a specific question.
     * Uses upsert logic (insert if new, update if exists).
     * 
     * Process:
     * 1. Normalizes answer (converts arrays to comma-separated strings)
     * 2. Checks if answer already exists
     * 3. Updates existing answer OR inserts new answer
     * 4. Records timestamps (created_at, updated_at)
     * 
     * Answer Normalization:
     * - Arrays: Converted to comma-separated string (e.g., ['A','B'] → "A,B")
     * - Strings: Used as-is
     * 
     * @param int $studentId Student ID submitting the answer
     * @param int $testId Test ID being answered
     * @param int $questionId Question ID being answered
     * @param mixed $answer Answer value (string or array)
     * @param bool $isDoubtful Mark as doubtful/ragu-ragu (default: false)
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtAnswerService();
     * 
     * // Save PG answer
     * $result = $service->saveAnswer(1, 10, 5, 'A');
     * 
     * // Save PGK answer (multiple options)
     * $result = $service->saveAnswer(1, 10, 6, ['A', 'B', 'C']);
     * 
     * // Save with doubtful flag
     * $result = $service->saveAnswer(1, 10, 7, 'B', true);
     */
    public function saveAnswer(int $studentId, int $testId, int $questionId, $answer, bool $isDoubtful = false): array
    {
        try {
            // Normalize answer
            $normalizedAnswer = $this->normalizeAnswer($answer);
            
            // Check if answer exists
            $existing = $this->answerModel->where([
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => $questionId
            ])->first();

            $now = date('Y-m-d H:i:s');

            if ($existing) {
                // Update existing answer
                $updated = $this->answerModel->update($existing['id'], [
                    'answer' => $normalizedAnswer,
                    'is_doubtful' => $isDoubtful ? 1 : 0,
                    'updated_at' => $now
                ]);

                if (!$updated) {
                    return $this->errorResponse('Gagal update jawaban');
                }

                $this->logDebug('CbtAnswerService::saveAnswer', "Updated answer for Q{$questionId}");
            } else {
                // Insert new answer
                $inserted = $this->answerModel->insert([
                    'student_id' => $studentId,
                    'test_id' => $testId,
                    'question_id' => $questionId,
                    'answer' => $normalizedAnswer,
                    'is_doubtful' => $isDoubtful ? 1 : 0,
                    'created_at' => $now,
                    'updated_at' => $now
                ]);

                if (!$inserted) {
                    return $this->errorResponse('Gagal simpan jawaban');
                }

                $this->logDebug('CbtAnswerService::saveAnswer', "Inserted new answer for Q{$questionId}");
            }

            return $this->successResponse(null, 'Jawaban berhasil disimpan');
        } catch (\Throwable $e) {
            $this->logError('CbtAnswerService::saveAnswer', 'Failed to save answer', $e);
            return $this->errorResponse('Terjadi kesalahan saat menyimpan jawaban');
        }
    }

    /**
     * Save multiple answers in bulk
     * 
     * Efficiently saves multiple answers in a single operation.
     * Optimized for performance when submitting entire test.
     * 
     * Process:
     * 1. Validates input (must have at least one answer)
     * 2. Normalizes all answers
     * 3. Attempts bulk upsert (database-specific)
     * 4. Falls back to optimized individual saves if bulk fails
     * 
     * Performance:
     * - Bulk mode: Single database query (fastest)
     * - Fallback mode: Batched queries with single lookup (optimized)
     * - Avoids N+1 query problem
     * 
     * @param int $studentId Student ID submitting answers
     * @param int $testId Test ID being answered
     * @param array $answers Associative array [question_id => answer]
     *                       Example: [1 => 'A', 2 => 'B', 3 => ['A','B']]
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     *               - 'data' (array): Contains 'count' (int) of saved answers
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtAnswerService();
     * $answers = [
     *     1 => 'A',           // PG question
     *     2 => ['A', 'B'],    // PGK question
     *     3 => 'B,S,B,S',     // BS question
     *     4 => 'Essay text'   // Essay question
     * ];
     * 
     * $result = $service->saveBulkAnswers(1, 10, $answers);
     * if ($result['success']) {
     *     echo "Saved " . $result['data']['count'] . " answers";
     * }
     */
    public function saveBulkAnswers(int $studentId, int $testId, array $answers): array
    {
        try {
            if (empty($answers)) {
                return $this->errorResponse('Tidak ada jawaban untuk disimpan');
            }

            // Build rows for bulk insert/update
            $rows = [];
            foreach ($answers as $questionId => $answer) {
                $rows[] = [
                    'student_id' => $studentId,
                    'test_id' => $testId,
                    'question_id' => (int) $questionId,
                    'answer' => $this->normalizeAnswer($answer),
                    'is_doubtful' => 0
                ];
            }

            // Try bulk upsert
            $success = $this->answerModel->saveAnswersBulk($rows);

            if (!$success) {
                // Fallback to individual saves
                $success = $this->fallbackUpsertAnswers($rows);
            }

            if ($success) {
                $count = count($rows);
                $this->logDebug('CbtAnswerService::saveBulkAnswers', "Saved {$count} answers");
                return $this->successResponse(['count' => $count], 'Jawaban berhasil disimpan');
            }

            return $this->errorResponse('Gagal menyimpan jawaban');
        } catch (\Throwable $e) {
            $this->logError('CbtAnswerService::saveBulkAnswers', 'Failed to save bulk answers', $e);
            return $this->errorResponse('Terjadi kesalahan saat menyimpan jawaban');
        }
    }

    /**
     * Get student answers for a test
     * 
     * Retrieves all answers submitted by a student for a specific test.
     * Returns answers indexed by question_id for easy lookup.
     * 
     * Use Cases:
     * - Display student's answers in review page
     * - Calculate scores
     * - Check answer completion status
     * - Generate answer sheets
     * 
     * @param int $studentId Student ID whose answers to retrieve
     * @param int $testId Test ID to get answers for
     * @return array Associative array [question_id => answer]
     *               Example: [1 => 'A', 2 => 'B,C', 3 => 'Essay text']
     *               Returns empty array if no answers found
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtAnswerService();
     * $answers = $service->getStudentAnswers(1, 10);
     * 
     * // Check specific answer
     * if (isset($answers[5])) {
     *     echo "Answer to Q5: " . $answers[5];
     * }
     * 
     * // Count answered questions
     * echo "Answered: " . count($answers) . " questions";
     */
    public function getStudentAnswers(int $studentId, int $testId): array
    {
        try {
            $answers = $this->answerModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->findAll();

            // Build map: question_id => answer
            $answerMap = [];
            foreach ($answers as $answer) {
                $answerMap[$answer['question_id']] = $answer['answer'];
            }

            return $answerMap;
        } catch (\Throwable $e) {
            $this->logError('CbtAnswerService::getStudentAnswers', 'Failed to get answers', $e);
            return [];
        }
    }

    /**
     * Mark answer as doubtful
     * 
     * Toggles the "ragu-ragu" (doubtful) flag on a specific answer.
     * Allows students to mark questions they want to review later.
     * 
     * Use Cases:
     * - Student marks question for later review
     * - UI shows doubtful questions with special indicator
     * - Navigation shows which questions are marked
     * 
     * Note: Answer must already exist (cannot mark non-existent answer)
     * 
     * @param int $studentId Student ID who owns the answer
     * @param int $testId Test ID containing the answer
     * @param int $questionId Question ID to mark
     * @param bool $isDoubtful True to mark as doubtful, false to unmark
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtAnswerService();
     * 
     * // Mark as doubtful
     * $result = $service->markAsDoubtful(1, 10, 5, true);
     * 
     * // Unmark (clear doubtful flag)
     * $result = $service->markAsDoubtful(1, 10, 5, false);
     */
    public function markAsDoubtful(int $studentId, int $testId, int $questionId, bool $isDoubtful): array
    {
        try {
            $answer = $this->answerModel->where([
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => $questionId
            ])->first();

            if (!$answer) {
                return $this->errorResponse('Jawaban tidak ditemukan');
            }

            $updated = $this->answerModel->update($answer['id'], [
                'is_doubtful' => $isDoubtful ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if (!$updated) {
                return $this->errorResponse('Gagal update status ragu-ragu');
            }

            return $this->successResponse(null, 'Status ragu-ragu berhasil diupdate');
        } catch (\Throwable $e) {
            $this->logError('CbtAnswerService::markAsDoubtful', 'Failed to mark as doubtful', $e);
            return $this->errorResponse('Terjadi kesalahan');
        }
    }

    /**
     * Normalize answer value
     * 
     * Converts various answer formats into a consistent string format.
     * 
     * Normalization Rules:
     * - Arrays: Joined with commas (e.g., ['A','B'] → "A,B")
     * - Strings: Used as-is
     * - Other types: Cast to string
     * 
     * This ensures consistent storage format regardless of input type.
     * 
     * @param mixed $answer Raw answer (array, string, or other)
     * @return string Normalized answer string
     * 
     * @example
     * $normalized = $this->normalizeAnswer(['A', 'B', 'C']);
     * // Returns: "A,B,C"
     * 
     * $normalized = $this->normalizeAnswer('A');
     * // Returns: "A"
     */
    protected function normalizeAnswer($answer): string
    {
        if (is_array($answer)) {
            return implode(',', $answer);
        }
        
        return (string) $answer;
    }

    /**
     * Fallback upsert when bulk operation fails
     * 
     * Optimized fallback for saving answers when bulk insert fails.
     * Uses batched queries to avoid N+1 problem.
     * 
     * Optimization Strategy:
     * 1. Single query to fetch all existing answers (batch lookup)
     * 2. Build in-memory map of existing answers
     * 3. Loop through rows: update existing, insert new
     * 
     * Performance:
     * - Old approach: N+1 queries (1 lookup per answer)
     * - New approach: 1 lookup + N updates/inserts
     * - Significant improvement for large answer sets
     * 
     * @param array $rows Answer rows to save, each with keys:
     *                    - 'student_id' (int)
     *                    - 'test_id' (int)
     *                    - 'question_id' (int)
     *                    - 'answer' (string)
     *                    - 'is_doubtful' (int)
     * @return bool True if all rows saved successfully, false otherwise
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * // Internal use only - called by saveBulkAnswers()
     * $rows = [
     *     ['student_id' => 1, 'test_id' => 10, 'question_id' => 1, 'answer' => 'A'],
     *     ['student_id' => 1, 'test_id' => 10, 'question_id' => 2, 'answer' => 'B']
     * ];
     * $success = $this->fallbackUpsertAnswers($rows);
     */
    protected function fallbackUpsertAnswers(array $rows): bool
    {
        try {
            if (empty($rows)) {
                return true;
            }
            
            // Optimize: Batch query to check existing answers instead of N+1 queries
            $studentIds = array_unique(array_column($rows, 'student_id'));
            $testIds = array_unique(array_column($rows, 'test_id'));
            $questionIds = array_unique(array_column($rows, 'question_id'));
            
            // Get all existing answers in single query
            $existingAnswers = $this->answerModel
                ->whereIn('student_id', $studentIds)
                ->whereIn('test_id', $testIds)
                ->whereIn('question_id', $questionIds)
                ->findAll();
            
            // Build map of existing answers for quick lookup
            $existingMap = [];
            foreach ($existingAnswers as $existing) {
                $key = $existing['student_id'] . '_' . $existing['test_id'] . '_' . $existing['question_id'];
                $existingMap[$key] = $existing;
            }
            
            // Process each row
            $now = date('Y-m-d H:i:s');
            foreach ($rows as $r) {
                $key = $r['student_id'] . '_' . $r['test_id'] . '_' . $r['question_id'];
                
                if (isset($existingMap[$key])) {
                    // Update existing
                    $this->answerModel->update($existingMap[$key]['id'], [
                        'answer' => $r['answer'],
                        'updated_at' => $now
                    ]);
                } else {
                    // Insert new
                    $insert = $r;
                    if (!isset($insert['created_at'])) {
                        $insert['created_at'] = $now;
                    }
                    $this->answerModel->insert($insert);
                }
            }
            
            return true;
        } catch (\Throwable $e) {
            $this->logError('CbtAnswerService::fallbackUpsertAnswers', 'Fallback upsert failed', $e);
            return false;
        }
    }
}
