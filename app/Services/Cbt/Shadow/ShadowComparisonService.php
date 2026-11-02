<?php

namespace App\Services\Cbt\Shadow;

use App\Services\Cbt\Assessment\AssessmentStatusResolver;

/**
 * ShadowComparisonService
 *
 * ESPATA Assessment Intelligence V3 - Stage F1
 * Compares legacy CBT outcomes against V3 Assessment Engine evaluations.
 *
 * Multi-Cause Classification Engine:
 * - EXACT_MATCH
 * - ROUNDING_DIFFERENCE
 * - SCORE_DIFFERENCE
 * - WEIGHT_DIFFERENCE
 * - VOID_POLICY_DIFFERENCE
 * - UNANSWERED_POLICY_DIFFERENCE
 * - ESSAY_POLICY_DIFFERENCE
 * - ATTEMPT_SELECTION_DIFFERENCE
 * - LEGACY_ZERO_FALLBACK
 * - NO_VALID_QUESTIONS
 * - INSUFFICIENT_EVIDENCE
 * - INTEGRITY_STATUS_DIFFERENCE
 * - LEGACY_UNVERIFIABLE
 * - INSUFFICIENT_SOURCE_DATA
 */
class ShadowComparisonService
{
    public const CAT_EXACT_MATCH                  = 'EXACT_MATCH';
    public const CAT_ROUNDING_DIFFERENCE          = 'ROUNDING_DIFFERENCE';
    public const CAT_SCORE_DIFFERENCE             = 'SCORE_DIFFERENCE';
    public const CAT_WEIGHT_DIFFERENCE            = 'WEIGHT_DIFFERENCE';
    public const CAT_VOID_POLICY_DIFFERENCE       = 'VOID_POLICY_DIFFERENCE';
    public const CAT_UNANSWERED_POLICY_DIFFERENCE = 'UNANSWERED_POLICY_DIFFERENCE';
    public const CAT_ESSAY_POLICY_DIFFERENCE      = 'ESSAY_POLICY_DIFFERENCE';
    public const CAT_ATTEMPT_SELECTION_DIFFERENCE = 'ATTEMPT_SELECTION_DIFFERENCE';
    public const CAT_LEGACY_ZERO_FALLBACK         = 'LEGACY_ZERO_FALLBACK';
    public const CAT_NO_VALID_QUESTIONS           = 'NO_VALID_QUESTIONS';
    public const CAT_INSUFFICIENT_EVIDENCE        = 'INSUFFICIENT_EVIDENCE';
    public const CAT_INTEGRITY_STATUS_DIFFERENCE  = 'INTEGRITY_STATUS_DIFFERENCE';
    public const CAT_LEGACY_UNVERIFIABLE          = 'LEGACY_UNVERIFIABLE';
    public const CAT_INSUFFICIENT_SOURCE_DATA     = 'INSUFFICIENT_SOURCE_DATA';
    public const CAT_PUBLICATION_ELIGIBILITY_DIFFERENCE = 'PUBLICATION_ELIGIBILITY_DIFFERENCE';

    public const ROUNDING_TOLERANCE = 0.05;

    /**
     * Compare a single student assessment case between Legacy and V3.
     *
     * @param array $legacyResult Output from LegacyScoreReconstructor
     * @param array $v3Result Output from V3ShadowEvaluator
     * @param array $context Additional context metadata
     * @return array Comparison outcome with multi-cause categories and delta analysis
     */
    public function compareCase(array $legacyResult, array $v3Result, array $context = []): array
    {
        $studentId = $v3Result['student_id'] ?? ($legacyResult['student_id'] ?? 'unknown');
        $testId = $v3Result['test_id'] ?? ($context['test_id'] ?? 'unknown');
        $sessionId = $v3Result['session_id'] ?? ($legacyResult['session_id'] ?? null);

        $categories = [];
        $notes = [];

        // 1. Check for Insufficient Source Data or Unverifiable
        if (empty($legacyResult) || empty($v3Result)) {
            $categories[] = self::CAT_INSUFFICIENT_SOURCE_DATA;
            return $this->buildComparisonResult(
                $studentId, $testId, $sessionId, null, null, null, false, $categories, ['Missing legacy or V3 evaluation data']
            );
        }

        if (empty($legacyResult['is_verifiable'])) {
            $categories[] = self::CAT_LEGACY_UNVERIFIABLE;
            $notes[] = 'Legacy score could not be definitively verified from source records';
        }

        // 2. Check for Multiple Attempt Selection Differences
        if (($v3Result['attempt_status'] ?? '') === 'AMBIGUOUS_ATTEMPTS') {
            $categories[] = self::CAT_ATTEMPT_SELECTION_DIFFERENCE;
            $notes[] = 'V3 requires manual selection for ambiguous retakes, while legacy may have picked arbitrary session';
        }

        // 3. Check for Entire Test Void (NO_VALID_QUESTIONS)
        if (!empty($v3Result['is_entire_test_void'])) {
            $categories[] = self::CAT_NO_VALID_QUESTIONS;
            $notes[] = 'V3 flagged test as NO_VALID_QUESTIONS (final score null). Gradebook sync blocked.';
        }

        // 4. Check for Legacy Zero Fallback (Absent student)
        $isLegacyZeroFallback = !empty($legacyResult['is_zero_fallback']);
        if ($isLegacyZeroFallback) {
            $categories[] = self::CAT_LEGACY_ZERO_FALLBACK;
            $notes[] = 'Legacy treated student absence as score 0.0, while V3 preserved null/unattempted status';
        }

        // 5. Check for Void Policy Differences
        $rawResult = $v3Result['raw_result'] ?? null;
        if ($rawResult && !empty($rawResult->getWeightDetails()['is_all_type_void_redistributed'])) {
            $categories[] = self::CAT_VOID_POLICY_DIFFERENCE;
            $notes[] = 'V3 applied ALL-TYPE-VOID Option B proportional redistribution';
        }

        // 6. Check for Essay Policy Differences
        $isEssayIncomplete = (isset($v3Result['is_fully_graded']) && !$v3Result['is_fully_graded']);
        if ($isEssayIncomplete) {
            $categories[] = self::CAT_ESSAY_POLICY_DIFFERENCE;
            $notes[] = 'V3 withheld gradebook score in PENDING_ESSAY status while legacy published partial score; excluded from numerical score comparison';
        }

        // 7. Check for Integrity Status Differences
        $integrityStatus = $v3Result['statuses'][AssessmentStatusResolver::DOMAIN_INTEGRITY] ?? AssessmentStatusResolver::INTEGRITY_VALID;
        if ($integrityStatus !== AssessmentStatusResolver::INTEGRITY_VALID && $integrityStatus !== AssessmentStatusResolver::INTEGRITY_VERIFIED_CLEAN) {
            $categories[] = self::CAT_INTEGRITY_STATUS_DIFFERENCE;
            $notes[] = "V3 flagged session integrity as {$integrityStatus}";
        }

        // 8. Check for TP Insufficient Evidence
        $tpEvals = $v3Result['tp_evaluations'] ?? [];
        $hasInsufficientTp = false;
        foreach ($tpEvals as $tp) {
            if (($tp['status'] ?? '') === AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE) {
                $hasInsufficientTp = true;
                break;
            }
        }
        if ($hasInsufficientTp) {
            $categories[] = self::CAT_INSUFFICIENT_EVIDENCE;
            $notes[] = 'One or more TPs flagged as INSUFFICIENT_EVIDENCE (Smax < 10 or N_unique < 2)';
        }

        // 9. Numeric Score Comparison (Strictly on comparable, fully graded, eligible scores)
        $legacyScore = $legacyResult['converted_score'] ?? ($legacyResult['raw_score'] ?? null);
        $v3Score = $v3Result['report_score'] ?? ($v3Result['final_score'] ?? null);

        if ($isEssayIncomplete) {
            // Strictly null for gradebook comparison if essay grading is incomplete
            $v3Score = null;
        }

        $isComparable = ($legacyScore !== null && $v3Score !== null && !$isLegacyZeroFallback && empty($v3Result['is_entire_test_void']) && !$isEssayIncomplete);
        $delta = null;
        $absDelta = null;

        if ($isComparable) {
            $delta = round($v3Score - $legacyScore, 4);
            $absDelta = abs($delta);

            if ($absDelta <= 0.0001) {
                $categories[] = self::CAT_EXACT_MATCH;
            } elseif ($absDelta <= self::ROUNDING_TOLERANCE) {
                $categories[] = self::CAT_ROUNDING_DIFFERENCE;
                $notes[] = "Minor rounding difference ({$delta}) within tolerance of " . self::ROUNDING_TOLERANCE;
            } else {
                $categories[] = self::CAT_SCORE_DIFFERENCE;
                $notes[] = "Significant score difference: V3 {$v3Score} vs Legacy {$legacyScore} (delta: {$delta})";
            }
        } else {
            if ($legacyScore === null || $v3Score === null) {
                $notes[] = 'Non-comparable: one or both scores are null (void test, incomplete grading, or absent)';
            }
        }

        // 10. Publication Eligibility Comparison
        $legacyPublished = ($legacyScore !== null && !$isLegacyZeroFallback);
        $legacyStatus = 'PUBLISHED';
        if ($isLegacyZeroFallback) {
            $legacyStatus = 'ABSENT_ZERO_FALLBACK';
        } elseif (empty($legacyResult['is_verifiable'])) {
            $legacyStatus = 'UNVERIFIABLE';
        } elseif ($legacyScore === null) {
            $legacyStatus = 'NOT_PUBLISHED';
        }

        $v3Eligible = !empty($v3Result['is_eligible']);
        $v3Status = 'ELIGIBLE';
        if (!empty($v3Result['is_entire_test_void'])) {
            $v3Status = 'NO_VALID_QUESTIONS';
        } elseif (($v3Result['attempt_status'] ?? '') === 'AMBIGUOUS_ATTEMPTS') {
            $v3Status = 'AMBIGUOUS_ATTEMPTS';
        } elseif ($isLegacyZeroFallback || ($v3Result['statuses'][AssessmentStatusResolver::DOMAIN_PARTICIPATION] ?? '') === AssessmentStatusResolver::PARTICIPATION_ABSENT) {
            $v3Status = 'ABSENT';
        } elseif ($isEssayIncomplete) {
            $v3Status = 'PENDING_ESSAY';
        } elseif (!$v3Eligible) {
            $v3Status = $v3Result['statuses'][AssessmentStatusResolver::DOMAIN_SESSION] ?? 'INELIGIBLE';
        }

        $eligibilityMatch = ($legacyPublished === $v3Eligible);
        if (!$eligibilityMatch) {
            $categories[] = self::CAT_PUBLICATION_ELIGIBILITY_DIFFERENCE;
        }

        $publicationEligibility = [
            'legacy_published'     => $legacyPublished,
            'legacy_status'        => $legacyStatus,
            'v3_eligible'          => $v3Eligible,
            'v3_status'            => $v3Status,
            'is_eligibility_match' => $eligibilityMatch,
            'difference_reason'    => !$eligibilityMatch ? "Legacy {$legacyStatus} vs V3 {$v3Status}" : null,
        ];

        // 11. Objective Score Comparison
        $legacyObjScore = $legacyResult['objective_score'] ?? null;
        $v3ObjScore = $v3Result['objective_score'] ?? null;
        $objComparable = ($legacyObjScore !== null && $v3ObjScore !== null);
        $objDelta = $objComparable ? round($v3ObjScore - $legacyObjScore, 4) : null;
        $objectiveComparison = [
            'legacy_objective_score' => $legacyObjScore !== null ? round($legacyObjScore, 2) : null,
            'v3_objective_score'     => $v3ObjScore !== null ? round($v3ObjScore, 2) : null,
            'delta'                  => $objDelta,
            'is_comparable'          => $objComparable,
        ];

        // If categories is empty, default to SCORE_DIFFERENCE or UNVERIFIABLE
        if (empty($categories)) {
            $categories[] = self::CAT_SCORE_DIFFERENCE;
        }

        return $this->buildComparisonResult(
            $studentId,
            $testId,
            $sessionId,
            $legacyScore,
            $v3Score,
            $delta,
            $isComparable,
            array_unique($categories),
            $notes,
            $publicationEligibility,
            $objectiveComparison
        );
    }

    /**
     * Compare a batch of cases.
     *
     * @param array $legacyCases
     * @param array $v3Cases
     * @return array List of comparison results
     */
    public function compareBatch(array $legacyCases, array $v3Cases): array
    {
        $indexedLegacy = [];
        foreach ($legacyCases as $l) {
            $sid = $l['student_id'] ?? null;
            if ($sid !== null) {
                $indexedLegacy[$sid] = $l;
            }
        }

        $results = [];
        foreach ($v3Cases as $v) {
            $sid = $v['student_id'] ?? null;
            $legacy = $indexedLegacy[$sid] ?? null;

            if ($legacy !== null) {
                $results[] = $this->compareCase($legacy, $v);
            } else {
                // Legacy record missing for student
                $results[] = $this->compareCase(
                    ['student_id' => $sid, 'is_verifiable' => false, 'converted_score' => null, 'is_zero_fallback' => true],
                    $v
                );
            }
        }

        return $results;
    }

    private function buildComparisonResult(
        $studentId,
        $testId,
        $sessionId,
        ?float $legacyScore,
        ?float $v3Score,
        ?float $delta,
        bool $isComparable,
        array $categories,
        array $notes,
        array $publicationEligibility = [],
        array $objectiveComparison = []
    ): array {
        return [
            'student_id'              => $studentId,
            'test_id'                 => $testId,
            'session_id'              => $sessionId,
            'legacy_score'            => $legacyScore !== null ? round($legacyScore, 2) : null,
            'v3_score'                => $v3Score !== null ? round($v3Score, 2) : null,
            'delta'                   => $delta !== null ? round($delta, 4) : null,
            'abs_delta'               => $delta !== null ? round(abs($delta), 4) : null,
            'is_comparable'           => $isComparable,
            'categories'              => $categories,
            'notes'                   => implode('; ', $notes),
            'publication_eligibility' => $publicationEligibility,
            'objective_comparison'    => $objectiveComparison,
        ];
    }
}

