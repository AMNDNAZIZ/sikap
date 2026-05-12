<?php

namespace App\Libraries;

/**
 * Answer Normalizer
 * Simplifies complex answer format normalization logic
 * Improves code readability and maintainability
 */
class AnswerNormalizer
{
    /**
     * Normalize various answer formats into standard array structure
     *
     * Supported formats:
     * 1. Associative map: {"12": "A", "13": "B"}
     * 2. Array of objects: [{"question_id": 12, "answer": "A"}, ...]
     * 3. Mixed formats
     *
     * @param array $input Raw answer input
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @return array Normalized answer rows
     */
    public static function normalize(array $input, int $studentId, int $testId): array
    {
        $rows = [];

        // Check if input is associative (non-sequential keys)
        $isAssoc = array_keys($input) !== range(0, count($input) - 1);

        if ($isAssoc) {
            // Handle associative array
            $rows = self::normalizeAssociative($input, $studentId, $testId);
        } else {
            // Handle sequential array
            $rows = self::normalizeSequential($input, $studentId, $testId);
        }

        return $rows;
    }

    /**
     * Normalize associative array format
     *
     * @param array $input
     * @param int $studentId
     * @param int $testId
     * @return array
     */
    protected static function normalizeAssociative(array $input, int $studentId, int $testId): array
    {
        $rows = [];

        // Check if all values are scalar (simple key-value pairs)
        $allScalar = true;
        foreach ($input as $value) {
            if (is_array($value)) {
                $allScalar = false;
                break;
            }
        }

        if ($allScalar) {
            // Format: {"12": "A", "13": "B"}
            foreach ($input as $questionId => $answer) {
                if (is_numeric($questionId)) {
                    $rows[] = [
                        'student_id' => $studentId,
                        'test_id' => $testId,
                        'question_id' => (int)$questionId,
                        'answer' => (string)$answer
                    ];
                }
            }
        } else {
            // Mixed format with nested arrays
            foreach ($input as $item) {
                if (is_array($item)) {
                    $row = self::extractFromArray($item, $studentId, $testId);
                    if ($row) {
                        $rows[] = $row;
                    }
                }
            }
        }

        return $rows;
    }

    /**
     * Normalize sequential array format
     *
     * @param array $input
     * @param int $studentId
     * @param int $testId
     * @return array
     */
    protected static function normalizeSequential(array $input, int $studentId, int $testId): array
    {
        $rows = [];

        foreach ($input as $item) {
            if (is_array($item)) {
                $row = self::extractFromArray($item, $studentId, $testId);
                if ($row) {
                    $rows[] = $row;
                }
            }
        }

        return $rows;
    }

    /**
     * Extract answer data from array item
     *
     * @param array $item
     * @param int $studentId
     * @param int $testId
     * @return array|null
     */
    protected static function extractFromArray(array $item, int $studentId, int $testId): ?array
    {
        // Format 1: {"question_id": 12, "answer": "A"}
        if (isset($item['question_id'])) {
            return [
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => (int)$item['question_id'],
                'answer' => (string)($item['answer'] ?? '')
            ];
        }

        // Format 2: {"qid": 12, "answer": "A"}
        if (isset($item['qid'])) {
            return [
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => (int)$item['qid'],
                'answer' => (string)($item['answer'] ?? '')
            ];
        }

        // Format 3: Single key-value pair {"12": "A"}
        $keys = array_keys($item);
        if (count($keys) === 1 && is_numeric($keys[0])) {
            return [
                'student_id' => $studentId,
                'test_id' => $testId,
                'question_id' => (int)$keys[0],
                'answer' => (string)$item[$keys[0]]
            ];
        }

        return null;
    }

    /**
     * Validate normalized rows
     *
     * @param array $rows
     * @return bool
     */
    public static function validate(array $rows): bool
    {
        foreach ($rows as $row) {
            if (!isset($row['student_id']) || !isset($row['test_id']) || !isset($row['question_id'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get statistics about normalized data
     *
     * @param array $rows
     * @return array
     */
    public static function getStats(array $rows): array
    {
        $questionIds = array_column($rows, 'question_id');
        
        return [
            'total' => count($rows),
            'unique_questions' => count(array_unique($questionIds)),
            'has_duplicates' => count($questionIds) !== count(array_unique($questionIds))
        ];
    }
}
