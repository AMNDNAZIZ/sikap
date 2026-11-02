<?php

namespace App\Services\Cbt\Assessment;

/**
 * ObjectiveScoringService
 *
 * ESPATA Assessment Intelligence V3 - Stage D
 * Calculates objective item scoring on scale 0-100.
 *
 * Rules:
 * 1. Unanswered questions count as 0 points.
 * 2. Void questions are excluded from scoring (weight = 0, score = 0).
 * 3. Weights applied are normalized weights from QuestionWeightResolver.
 * 4. Raw score and scaled score are computed deterministically.
 */
class ObjectiveScoringService
{
    /**
     * Grade objective items for a student.
     *
     * @param array $items Array of item definitions:
     *                     [['item_id' => ..., 'type' => 'pg'|'pg_kompleks'|..., 'tp_id' => ..., 'weight' => float, 'is_void' => bool, 'key' => ...], ...]
     * @param array $answers Array of student answers keyed by item_id or array of answer records:
     *                       [['item_id' => ..., 'student_answer' => ..., 'is_correct' => bool|null], ...]
     * @return array Grading summary:
     *               [
     *                 'status' => string,
     *                 'total_items' => int,
     *                 'active_items' => int,
     *                 'void_items' => int,
     *                 'answered_items' => int,
     *                 'correct_items' => int,
     *                 'weighted_score' => float,      // Weighted points earned towards overall test
     *                 'objective_scale_score' => float,// Normalized to 0-100 within objective scope
     *                 'max_objective_weight' => float,
     *                 'items_breakdown' => array
     *               ]
     */
    public function grade(array $items, array $answers): array
    {
        // Index answers by item_id
        $indexedAnswers = [];
        foreach ($answers as $ans) {
            $itemId = $ans['item_id'] ?? null;
            if ($itemId !== null) {
                $indexedAnswers[$itemId] = $ans;
            }
        }

        $totalItems = count($items);
        $activeItems = 0;
        $voidItems = 0;
        $answeredItems = 0;
        $correctItems = 0;

        $weightedScore = 0.0;
        $maxObjectiveWeight = 0.0;
        $itemsBreakdown = [];

        foreach ($items as $item) {
            $itemId = $item['item_id'];
            $isVoid = (bool)($item['is_void'] ?? false);
            $weight = (float)($item['weight'] ?? 0.0);
            $tpId = $item['tp_id'] ?? null;
            $type = $item['type'] ?? 'pg';

            $ansRecord = $indexedAnswers[$itemId] ?? null;
            $studentAnswer = $ansRecord['student_answer'] ?? ($ansRecord['jawaban'] ?? null);
            $hasAnswered = $studentAnswer !== null && trim((string)$studentAnswer) !== '';

            if ($isVoid) {
                $voidItems++;
                $itemsBreakdown[$itemId] = [
                    'item_id' => $itemId,
                    'tp_id' => $tpId,
                    'type' => $type,
                    'is_void' => true,
                    'weight' => 0.0,
                    'is_answered' => $hasAnswered,
                    'is_correct' => false,
                    'score_ratio' => 0.0,
                    'weighted_score' => 0.0,
                ];
                continue;
            }

            $activeItems++;
            $maxObjectiveWeight += $weight;

            $isCorrect = false;
            $scoreRatio = 0.0;

            if ($hasAnswered) {
                $answeredItems++;
                // If is_correct is explicitly provided in the answer record (e.g. from CBT evaluation)
                if (isset($ansRecord['is_correct']) && $ansRecord['is_correct'] !== null) {
                    $isCorrect = (bool)$ansRecord['is_correct'];
                    $scoreRatio = $isCorrect ? 1.0 : (float)($ansRecord['score_ratio'] ?? 0.0);
                } elseif (isset($item['key'])) {
                    // Compare against key
                    $isCorrect = $this->evaluateAnswer($studentAnswer, $item['key'], $type);
                    $scoreRatio = $isCorrect ? 1.0 : 0.0;
                }
            }

            if ($scoreRatio > 0.0) {
                if ($scoreRatio >= 1.0) {
                    $correctItems++;
                }
                $itemWeightedScore = $weight * $scoreRatio;
                $weightedScore += $itemWeightedScore;
            } else {
                $itemWeightedScore = 0.0;
            }

            $itemsBreakdown[$itemId] = [
                'item_id' => $itemId,
                'tp_id' => $tpId,
                'type' => $type,
                'is_void' => false,
                'weight' => round($weight, 4),
                'is_answered' => $hasAnswered,
                'is_correct' => $isCorrect,
                'score_ratio' => round($scoreRatio, 4),
                'weighted_score' => round($itemWeightedScore, 4),
            ];
        }

        // Objective scale score (0-100)
        $objectiveScaleScore = 0.0;
        if ($maxObjectiveWeight > 0.0) {
            $objectiveScaleScore = ($weightedScore / $maxObjectiveWeight) * 100.0;
        }

        // Status
        if ($activeItems === 0) {
            $status = AssessmentStatusResolver::OBJ_NOT_APPLICABLE;
        } else {
            $status = AssessmentStatusResolver::OBJ_GRADED;
        }

        return [
            'status' => $status,
            'total_items' => $totalItems,
            'active_items' => $activeItems,
            'void_items' => $voidItems,
            'answered_items' => $answeredItems,
            'correct_items' => $correctItems,
            'weighted_score' => round($weightedScore, 4),
            'objective_scale_score' => round(min(100.0, max(0.0, $objectiveScaleScore)), 2),
            'max_objective_weight' => round($maxObjectiveWeight, 4),
            'items_breakdown' => $itemsBreakdown,
        ];
    }

    /**
     * Basic correctness evaluation.
     */
    private function evaluateAnswer(string $studentAnswer, string $key, string $type): bool
    {
        $cleanStudent = strtoupper(trim($studentAnswer));
        $cleanKey = strtoupper(trim($key));

        if ($type === 'pg' || $type === 'single_choice' || $type === 'true_false') {
            return $cleanStudent === $cleanKey;
        }

        // Exact match fallback
        return $cleanStudent === $cleanKey;
    }
}

