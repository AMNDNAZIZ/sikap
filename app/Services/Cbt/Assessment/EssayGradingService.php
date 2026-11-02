<?php

namespace App\Services\Cbt\Assessment;

/**
 * EssayGradingService
 *
 * ESPATA Assessment Intelligence V3 - Stage D
 * Aggregates essay scoring and tracks manual grading completion.
 *
 * CRITICAL POLICY (OD-PATH-B):
 * Essay scores enter the test's overall final score and the gradebook report score.
 * However, essay items MUST NEVER be attributed to Learning Objectives (TP),
 * MUST NEVER contribute to Smax or N_unique, and MUST NEVER affect TP mastery rates.
 */
class EssayGradingService
{
    /**
     * Grade essay items for a student.
     *
     * @param array $items Array of essay items:
     *                     [['item_id' => ..., 'weight' => float, 'is_void' => bool, 'max_points' => float], ...]
     * @param array $grades Array of essay grades keyed by item_id or list of grade records:
     *                      [['item_id' => ..., 'score_earned' => float, 'is_graded' => bool], ...]
     * @return array Grading summary:
     *               [
     *                 'status' => string,
     *                 'is_complete' => bool,
     *                 'total_items' => int,
     *                 'active_items' => int,
     *                 'void_items' => int,
     *                 'graded_items' => int,
     *                 'ungraded_items' => int,
     *                 'weighted_score' => float,      // Weighted points earned towards overall test
     *                 'essay_scale_score' => float,   // Normalized to 0-100 within essay scope
     *                 'max_essay_weight' => float,
     *                 'items_breakdown' => array,
     *                 'od_path_b_enforced' => bool    // Always true to confirm TP isolation
     *               ]
     */
    public function grade(array $items, array $grades): array
    {
        // Index grades by item_id
        $indexedGrades = [];
        foreach ($grades as $g) {
            $itemId = $g['item_id'] ?? null;
            if ($itemId !== null) {
                $indexedGrades[$itemId] = $g;
            }
        }

        $totalItems = count($items);
        $activeItems = 0;
        $voidItems = 0;
        $gradedItems = 0;
        $ungradedItems = 0;

        $weightedScore = 0.0;
        $maxEssayWeight = 0.0;
        $itemsBreakdown = [];

        foreach ($items as $item) {
            $itemId = $item['item_id'];
            $isVoid = (bool)($item['is_void'] ?? false);
            $weight = (float)($item['weight'] ?? 0.0);
            $maxPoints = (float)($item['max_points'] ?? 100.0);
            if ($maxPoints <= 0.0) {
                $maxPoints = 100.0;
            }

            if ($isVoid) {
                $voidItems++;
                $itemsBreakdown[$itemId] = [
                    'item_id' => $itemId,
                    'is_void' => true,
                    'weight' => 0.0,
                    'is_graded' => false,
                    'score_earned' => 0.0,
                    'max_points' => $maxPoints,
                    'score_ratio' => 0.0,
                    'weighted_score' => 0.0,
                ];
                continue;
            }

            $activeItems++;
            $maxEssayWeight += $weight;

            $gradeRecord = $indexedGrades[$itemId] ?? null;
            $isGraded = false;
            $scoreEarned = 0.0;
            $scoreRatio = 0.0;

            if ($gradeRecord !== null && isset($gradeRecord['is_graded']) && $gradeRecord['is_graded']) {
                $isGraded = true;
                $scoreEarned = (float)($gradeRecord['score_earned'] ?? 0.0);
                $scoreRatio = min(1.0, max(0.0, $scoreEarned / $maxPoints));
                $gradedItems++;
            } elseif ($gradeRecord !== null && isset($gradeRecord['score_earned']) && $gradeRecord['score_earned'] !== null) {
                // If score_earned is explicitly provided without explicit boolean
                $isGraded = true;
                $scoreEarned = (float)$gradeRecord['score_earned'];
                $scoreRatio = min(1.0, max(0.0, $scoreEarned / $maxPoints));
                $gradedItems++;
            } else {
                $ungradedItems++;
            }

            $itemWeightedScore = $isGraded ? ($weight * $scoreRatio) : 0.0;
            $weightedScore += $itemWeightedScore;

            $itemsBreakdown[$itemId] = [
                'item_id' => $itemId,
                'is_void' => false,
                'weight' => round($weight, 4),
                'is_graded' => $isGraded,
                'score_earned' => round($scoreEarned, 2),
                'max_points' => round($maxPoints, 2),
                'score_ratio' => round($scoreRatio, 4),
                'weighted_score' => round($itemWeightedScore, 4),
            ];
        }

        // Determine Status
        if ($activeItems === 0) {
            $status = AssessmentStatusResolver::ESSAY_NOT_APPLICABLE;
            $isComplete = true;
        } elseif ($gradedItems === 0) {
            $status = AssessmentStatusResolver::ESSAY_UNGRADED;
            $isComplete = false;
        } elseif ($gradedItems === $activeItems) {
            $status = AssessmentStatusResolver::ESSAY_GRADED;
            $isComplete = true;
        } else {
            $status = AssessmentStatusResolver::ESSAY_IN_PROGRESS;
            $isComplete = false;
        }

        $essayScaleScore = 0.0;
        if ($maxEssayWeight > 0.0) {
            $essayScaleScore = ($weightedScore / $maxEssayWeight) * 100.0;
        }

        return [
            'status' => $status,
            'is_complete' => $isComplete,
            'total_items' => $totalItems,
            'active_items' => $activeItems,
            'void_items' => $voidItems,
            'graded_items' => $gradedItems,
            'ungraded_items' => $ungradedItems,
            'weighted_score' => round($weightedScore, 4),
            'essay_scale_score' => round(min(100.0, max(0.0, $essayScaleScore)), 2),
            'max_essay_weight' => round($maxEssayWeight, 4),
            'items_breakdown' => $itemsBreakdown,
            'od_path_b_enforced' => true,
        ];
    }
}

