<?php

namespace App\Services\Cbt\Assessment;

/**
 * TpEvidenceEvaluator
 *
 * ESPATA Assessment Intelligence V3 - Stage D
 * Evaluates Learning Objective (Tujuan Pembelajaran - TP) evidence sufficiency and student mastery.
 *
 * RULES:
 * 1. Conjunctive Rule: Both Smax >= 10.00 AND N_unique >= 2 must be satisfied.
 *    - Smax: Sum of maximum possible weights/points of valid, non-void OBJECTIVE items mapped to this TP.
 *    - N_unique: Count of distinct valid, non-void OBJECTIVE items mapped to this TP.
 * 2. If Smax < 10.00 OR N_unique < 2:
 *    - Status = TP_INSUFFICIENT_EVIDENCE.
 *    - Student is neither marked as Mastered nor Failed (indeterminate evidence).
 * 3. OD-PATH-B Isolation:
 *    - Essay items NEVER count towards Smax, N_unique, or TP score.
 * 4. Mastery threshold:
 *    - Default 75.00% (configurable per TP or globally).
 *    - If evidence is sufficient and student TP percentage >= threshold: TP_MASTERED.
 *    - If evidence is sufficient and student TP percentage < threshold: TP_NOT_MASTERED.
 */
class TpEvidenceEvaluator
{
    public const DEFAULT_MASTERY_THRESHOLD = 75.00;
    public const MIN_SMAX = 10.00;
    public const MIN_N_UNIQUE = 2;

    /**
     * Evaluate TP mastery for a student given objective items and their grading breakdown.
     *
     * @param array $objectiveItemBreakdown Breakdown from ObjectiveScoringService (keyed by item_id or array of records)
     * @param array $tpDefinitions Array of TP configurations:
     *                             [['tp_id' => ..., 'tp_code' => ..., 'threshold' => 75.0], ...]
     * @param bool $isAttempted Whether student took/submitted the session
     * @return array Evaluation results per TP:
     *               [
     *                 tp_id => [
     *                   'tp_id' => ...,
     *                   'tp_code' => ...,
     *                   's_max' => float,
     *                   'n_unique' => int,
     *                   'is_sufficient_evidence' => bool,
     *                   'earned_weight' => float,
     *                   'student_percentage' => float|null,
     *                   'threshold' => float,
     *                   'status' => string, // MASTERED, NOT_MASTERED, INSUFFICIENT_EVIDENCE, NOT_ATTEMPTED
     *                   'is_mastered' => bool|null,
     *                   'insufficient_reason' => string|null
     *                 ], ...
     *               ]
     */
    public function evaluate(array $objectiveItemBreakdown, array $tpDefinitions = [], bool $isAttempted = true): array
    {
        // Group objective items by tp_id
        $itemsByTp = [];
        foreach ($objectiveItemBreakdown as $item) {
            $tpId = $item['tp_id'] ?? null;
            if ($tpId === null) {
                continue;
            }
            if (!isset($itemsByTp[$tpId])) {
                $itemsByTp[$tpId] = [];
            }
            $itemsByTp[$tpId][] = $item;
        }

        // Map configured thresholds
        $thresholds = [];
        $tpCodes = [];
        foreach ($tpDefinitions as $tp) {
            $id = $tp['tp_id'] ?? null;
            if ($id !== null) {
                $thresholds[$id] = (float)($tp['threshold'] ?? self::DEFAULT_MASTERY_THRESHOLD);
                $tpCodes[$id] = $tp['tp_code'] ?? (string)$id;
            }
        }

        // Ensure all defined TPs are processed, even if they have 0 items
        $allTpIds = array_unique(array_merge(array_keys($itemsByTp), array_keys($thresholds)));

        $results = [];

        foreach ($allTpIds as $tpId) {
            $items = $itemsByTp[$tpId] ?? [];
            $threshold = $thresholds[$tpId] ?? self::DEFAULT_MASTERY_THRESHOLD;
            $tpCode = $tpCodes[$tpId] ?? (string)$tpId;

            // If student did not attempt the test
            if (!$isAttempted) {
                $results[$tpId] = [
                    'tp_id' => $tpId,
                    'tp_code' => $tpCode,
                    's_max' => 0.0,
                    'n_unique' => 0,
                    'is_sufficient_evidence' => false,
                    'earned_weight' => 0.0,
                    'student_percentage' => null,
                    'threshold' => $threshold,
                    'status' => AssessmentStatusResolver::TP_NOT_ATTEMPTED,
                    'is_mastered' => null,
                    'insufficient_reason' => 'Student did not attempt session',
                ];
                continue;
            }

            // Calculate Smax and N_unique on valid, non-void objective items
            $sMax = 0.0;
            $uniqueItemIds = [];
            $earnedWeight = 0.0;

            foreach ($items as $item) {
                // OD-PATH-B check: ensure essay items never enter TP evaluation
                $type = $item['type'] ?? 'pg';
                if ($type === 'essay' || $type === 'uraian') {
                    continue;
                }

                if (!empty($item['is_void'])) {
                    continue; // Void items do not count towards Smax or N_unique
                }

                $itemId = $item['item_id'];
                $weight = (float)($item['weight'] ?? 0.0);
                $uniqueItemIds[$itemId] = true;
                $sMax += $weight;

                $earnedWeight += (float)($item['weighted_score'] ?? 0.0);
            }

            $nUnique = count($uniqueItemIds);
            $sMax = round($sMax, 4);
            $earnedWeight = round($earnedWeight, 4);

            // Conjunctive sufficiency check: Smax >= 10.00 AND N_unique >= 2
            $isSmaxSufficient = ($sMax >= self::MIN_SMAX);
            $isNUniqueSufficient = ($nUnique >= self::MIN_N_UNIQUE);
            $isSufficient = ($isSmaxSufficient && $isNUniqueSufficient);

            if (!$isSufficient) {
                $reasons = [];
                if (!$isSmaxSufficient) {
                    $reasons[] = sprintf('Smax (%.2f) < %.2f', $sMax, self::MIN_SMAX);
                }
                if (!$isNUniqueSufficient) {
                    $reasons[] = sprintf('N_unique (%d) < %d', $nUnique, self::MIN_N_UNIQUE);
                }

                $results[$tpId] = [
                    'tp_id' => $tpId,
                    'tp_code' => $tpCode,
                    's_max' => $sMax,
                    'n_unique' => $nUnique,
                    'is_sufficient_evidence' => false,
                    'earned_weight' => $earnedWeight,
                    'student_percentage' => null,
                    'threshold' => $threshold,
                    'status' => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE,
                    'is_mastered' => null, // Indeterminate: neither true nor false
                    'insufficient_reason' => implode(' and ', $reasons),
                ];
                continue;
            }

            // Sufficient evidence: calculate mastery percentage
            $studentPercentage = 0.0;
            if ($sMax > 0.0) {
                $studentPercentage = round(($earnedWeight / $sMax) * 100.0, 2);
            }

            $isMastered = ($studentPercentage >= $threshold);
            $status = $isMastered ? AssessmentStatusResolver::TP_MASTERED : AssessmentStatusResolver::TP_NOT_MASTERED;

            $results[$tpId] = [
                'tp_id' => $tpId,
                'tp_code' => $tpCode,
                's_max' => $sMax,
                'n_unique' => $nUnique,
                'is_sufficient_evidence' => true,
                'earned_weight' => $earnedWeight,
                'student_percentage' => $studentPercentage,
                'threshold' => $threshold,
                'status' => $status,
                'is_mastered' => $isMastered,
                'insufficient_reason' => null,
            ];
        }

        return $results;
    }
}

