<?php

namespace App\Services\Cbt\Assessment;

/**
 * QuestionWeightResolver
 * Manages item weighting, void question handling, and proportional weight redistribution.
 *
 * Implements Approved Owner Decisions:
 * - ALL-TYPE-VOID (Option B): Proportionally redistributes void question type weights
 *   to active question types, guaranteeing 100% total active weight.
 * - ENTIRE TEST VOID: If all questions are invalid/void, flags terminal status
 *   NO_VALID_QUESTIONS with zero division protection.
 */
class QuestionWeightResolver
{
    public const RESOLUTION_VERSION = 'V3.25-OPT_B';

    /**
     * Resolve question weights and handle void redistribution.
     *
     * Supports both calling conventions:
     *   resolve(array $items, array $typeWeights = [])
     *   resolve(array $typeWeights, array $questions)
     *
     * @param array $param1 Items or Type weights
     * @param array $param2 Type weights or Items
     * @return array Resolution result
     */
    public function resolve(array $param1, array $param2 = []): array
    {
        // Detect parameter ordering
        if ($this->isQuestionsArray($param1)) {
            $questions = $param1;
            $typeWeights = $param2;
        } else {
            $typeWeights = $param1;
            $questions = $param2;
        }

        if (empty($questions)) {
            return [
                'status'                         => 'ENTIRE_TEST_VOID',
                'test_status'                    => AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS,
                'is_entire_test_void'            => true,
                'is_all_type_void_redistributed' => false,
                'total_weight'                   => 0.0,
                'items'                          => [],
                'resolved_weights'               => [],
                'item_weights'                   => [],
                'audit_trail'                    => [
                    'reason' => 'Zero questions configured for test',
                ],
            ];
        }

        // 1. Group questions by type and identify active vs void items
        $grouped = [];
        $activeCountPerType = [];
        $totalValidQuestions = 0;

        foreach ($questions as $q) {
            $type = (string)($q['type'] ?? ($q['question_type'] ?? 'pg'));
            $isVoid = !empty($q['is_void']);

            if (!isset($grouped[$type])) {
                $grouped[$type] = [];
                $activeCountPerType[$type] = 0;
            }

            $grouped[$type][] = $q;
            if (!$isVoid) {
                $activeCountPerType[$type]++;
                $totalValidQuestions++;
            }
        }

        // 2. ENTIRE TEST VOID Check: All questions are void/invalid
        if ($totalValidQuestions === 0) {
            $decoratedItems = [];
            $itemWeights = [];
            foreach ($questions as $q) {
                $qid = $q['item_id'] ?? ($q['id'] ?? null);
                $qCopy = $q;
                $qCopy['weight'] = 0.0;
                $decoratedItems[] = $qCopy;
                if ($qid !== null) {
                    $itemWeights[$qid] = 0.0;
                }
            }

            return [
                'status'                         => 'ENTIRE_TEST_VOID',
                'test_status'                    => AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS,
                'is_entire_test_void'            => true,
                'is_all_type_void_redistributed' => false,
                'total_weight'                   => 0.0,
                'items'                          => $decoratedItems,
                'resolved_weights'               => [],
                'item_weights'                   => $itemWeights,
                'audit_trail'                    => [
                    'version'         => self::RESOLUTION_VERSION,
                    'total_questions' => count($questions),
                    'valid_questions' => 0,
                    'reason'          => 'All questions in the test are marked as void',
                ],
            ];
        }

        // 3. Identify active types (types with >= 1 active question)
        $activeTypes = [];
        $voidTypes = [];
        $originalActiveWeightSum = 0.0;

        foreach ($grouped as $type => $typeQuestions) {
            $origWeight = (float)($typeWeights[$type] ?? 0.0);
            if ($activeCountPerType[$type] > 0) {
                $activeTypes[$type] = $origWeight;
                $originalActiveWeightSum += $origWeight;
            } else {
                $voidTypes[$type] = $origWeight;
            }
        }

        // If active types had 0 original weight configured, distribute equally
        if ($originalActiveWeightSum <= 0.0) {
            $equalWeight = 100.0 / count($activeTypes);
            foreach ($activeTypes as $type => $w) {
                $activeTypes[$type] = $equalWeight;
            }
            $originalActiveWeightSum = 100.0;
        }

        // 4. ALL-TYPE-VOID (Option B): Proportionally redistribute weights of void types
        $redistributedTypeWeights = [];
        $scalingFactor = 100.0 / $originalActiveWeightSum;

        foreach ($activeTypes as $type => $origWeight) {
            $redistributedTypeWeights[$type] = round($origWeight * $scalingFactor, 4);
        }

        // Adjust rounding residue to guarantee exactly 100.00%
        $currentSum = array_sum($redistributedTypeWeights);
        $diff = round(100.0 - $currentSum, 4);
        if ($diff !== 0.0 && !empty($redistributedTypeWeights)) {
            $firstKey = array_key_first($redistributedTypeWeights);
            $redistributedTypeWeights[$firstKey] = round($redistributedTypeWeights[$firstKey] + $diff, 4);
        }

        // 5. Compute per-item weights and decorate items
        $itemWeights = [];
        $decoratedItems = [];
        $isRedistributed = !empty($voidTypes);

        foreach ($questions as $q) {
            $qid = $q['item_id'] ?? ($q['id'] ?? null);
            $type = (string)($q['type'] ?? ($q['question_type'] ?? 'pg'));
            $isVoid = !empty($q['is_void']);

            if ($isVoid || !isset($redistributedTypeWeights[$type])) {
                $itemWeight = 0.0;
            } else {
                $activeCount = $activeCountPerType[$type];
                $itemWeight = $activeCount > 0 
                    ? round($redistributedTypeWeights[$type] / $activeCount, 6) 
                    : 0.0;
            }

            if ($qid !== null) {
                $itemWeights[$qid] = $itemWeight;
            }

            $qCopy = $q;
            $qCopy['item_id'] = $qid;
            $qCopy['type'] = $type;
            $qCopy['weight'] = $itemWeight;
            $decoratedItems[] = $qCopy;
        }

        return [
            'status'                         => $isRedistributed ? 'REDISTRIBUTED' : 'NORMAL',
            'test_status'                    => AssessmentStatusResolver::TEST_ACTIVE,
            'is_entire_test_void'            => false,
            'is_all_type_void_redistributed' => $isRedistributed,
            'total_weight'                   => 100.0,
            'items'                          => $decoratedItems,
            'resolved_weights'               => $redistributedTypeWeights,
            'item_weights'                   => $itemWeights,
            'audit_trail'                    => [
                'version'                    => self::RESOLUTION_VERSION,
                'original_type_weights'      => $typeWeights,
                'void_types'                 => array_keys($voidTypes),
                'active_types'               => array_keys($activeTypes),
                'scaling_factor'             => $scalingFactor,
                'redistributed_type_weights' => $redistributedTypeWeights,
                'total_redistributed_weight' => array_sum($redistributedTypeWeights),
                'resolved_at'                => date('Y-m-d H:i:s'),
            ],
        ];
    }

    private function isQuestionsArray(array $arr): bool
    {
        if (empty($arr)) {
            return false;
        }
        $first = reset($arr);
        return is_array($first) && (isset($first['item_id']) || isset($first['id']) || isset($first['type']) || isset($first['question_type']));
    }
}
