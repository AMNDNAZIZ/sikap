<?php

namespace App\Services\Cbt\Assessment;

/**
 * MasteryRateCalculator
 *
 * ESPATA Assessment Intelligence V3 - Stage D
 * Calculates cohort mastery metrics:
 * 1. CR_murni (Pure Completion Rate):
 *    CR_murni = (Count of Mastered) / (Count of Mastered + Count of Not Mastered) * 100%
 *    - Evaluates ONLY students with definitive evidence.
 *    - Strictly excludes NOT_ATTEMPTED, INSUFFICIENT_EVIDENCE, GRADING_INCOMPLETE, EXCLUDED.
 *
 * 2. CR_kohort (Cohort Completion Rate):
 *    CR_kohort = (Count of Mastered) / (Definitive Count + NOT_ATTEMPTED Count) * 100%
 *    - Cohort-wide perspective treating unattempted as unmastered.
 *    - Strictly EXCLUDES INSUFFICIENT_EVIDENCE and other non-definitive states from denominator.
 *    - Only valid if definitive evidence exists in cohort.
 *
 * Mandatory Regression Benchmark:
 * 13 students total: 6 mastered, 4 not mastered, 3 not attempted.
 * -> CR_murni = 6 / 10 = 60.0%
 * -> CR_kohort = 6 / 13 = 46.15%
 */
class MasteryRateCalculator
{
    /**
     * Calculate cohort completion/mastery rates for a given set of student TP evaluations.
     *
     * @param array $studentTpResults Array of student evaluations for one TP:
     *                                [
     *                                  ['student_id' => ..., 'status' => 'MASTERED'|'NOT_MASTERED'|'INSUFFICIENT_EVIDENCE'|'NOT_ATTEMPTED'|...],
     *                                  ...
     *                                ]
     * @param string|int|null $tpId Optional TP ID for identification
     * @param string|null $tpCode Optional TP Code
     * @return array Metric result containing CR_murni, CR_kohort, and count breakdown:
     *               [
     *                 'tp_id' => ...,
     *                 'tp_code' => ...,
     *                 'total_students' => int,
     *                 'mastered_count' => int,
     *                 'not_mastered_count' => int,
     *                 'definitive_count' => int,
     *                 'not_attempted_count' => int,
     *                 'insufficient_evidence_count' => int,
     *                 'excluded_count' => int,
     *                 'cr_murni' => float|null,
     *                 'cr_kohort' => float|null,
     *                 'has_definitive_evidence' => bool
     *               ]
     */
    public function calculate(array $studentTpResults, $tpId = null, ?string $tpCode = null): array
    {
        $totalStudents = count($studentTpResults);
        $masteredCount = 0;
        $notMasteredCount = 0;
        $notAttemptedCount = 0;
        $insufficientCount = 0;
        $excludedCount = 0;

        foreach ($studentTpResults as $res) {
            $status = $res['status'] ?? null;

            switch ($status) {
                case AssessmentStatusResolver::TP_MASTERED:
                    $masteredCount++;
                    break;

                case AssessmentStatusResolver::TP_NOT_MASTERED:
                    $notMasteredCount++;
                    break;

                case AssessmentStatusResolver::TP_NOT_ATTEMPTED:
                    $notAttemptedCount++;
                    break;

                case AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE:
                    $insufficientCount++;
                    break;

                default:
                    // Any other status (INCOMPLETE, INTEGRITY_ERROR, EXCLUDED)
                    $excludedCount++;
                    break;
            }
        }

        $definitiveCount = $masteredCount + $notMasteredCount;
        $hasDefinitive = ($definitiveCount > 0);

        // CR_murni calculation: Mastered / Definitive
        $crMurni = null;
        if ($definitiveCount > 0) {
            $crMurni = round(($masteredCount / $definitiveCount) * 100.0, 2);
        }

        // CR_kohort calculation: Mastered / (Definitive + Not Attempted)
        $crKohort = null;
        $cohortDenominator = $definitiveCount + $notAttemptedCount;
        if ($hasDefinitive && $cohortDenominator > 0) {
            $crKohort = round(($masteredCount / $cohortDenominator) * 100.0, 2);
        }

        return [
            'tp_id' => $tpId,
            'tp_code' => $tpCode,
            'total_students' => $totalStudents,
            'mastered_count' => $masteredCount,
            'not_mastered_count' => $notMasteredCount,
            'definitive_count' => $definitiveCount,
            'not_attempted_count' => $notAttemptedCount,
            'insufficient_evidence_count' => $insufficientCount,
            'excluded_count' => $excludedCount,
            'cr_murni' => $crMurni,
            'cr_kohort' => $crKohort,
            'has_definitive_evidence' => $hasDefinitive,
        ];
    }

    /**
     * Calculate mastery rates for all TPs across a cohort of student evaluations.
     *
     * @param array $cohortEvaluations Array of student results, where each student has 'tp_evaluations':
     *                                [
     *                                  ['student_id' => ..., 'tp_evaluations' => [tp_id => ['status' => ..., 'tp_code' => ...], ...]],
     *                                  ...
     *                                ]
     * @return array Array of calculation results keyed by tp_id
     */
    public function calculateCohort(array $cohortEvaluations): array
    {
        $tpBuckets = [];
        $tpCodes = [];

        foreach ($cohortEvaluations as $studentRes) {
            $studentId = $studentRes['student_id'] ?? null;
            $tpEvals = $studentRes['tp_evaluations'] ?? [];

            foreach ($tpEvals as $tpId => $eval) {
                if (!isset($tpBuckets[$tpId])) {
                    $tpBuckets[$tpId] = [];
                }
                $tpBuckets[$tpId][] = [
                    'student_id' => $studentId,
                    'status' => $eval['status'] ?? null,
                ];
                if (isset($eval['tp_code']) && !isset($tpCodes[$tpId])) {
                    $tpCodes[$tpId] = $eval['tp_code'];
                }
            }
        }

        $cohortMetrics = [];
        foreach ($tpBuckets as $tpId => $studentResults) {
            $cohortMetrics[$tpId] = $this->calculate($studentResults, $tpId, $tpCodes[$tpId] ?? null);
        }

        return $cohortMetrics;
    }
}

