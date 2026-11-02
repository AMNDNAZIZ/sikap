<?php

namespace App\Services\Cbt\Assessment;

use App\Models\CbtAnswerV3Model;
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtEssayGradeV3Model;
use App\Models\CbtSessionScoreV3Model;
use App\Models\CbtReviewCaseV3Model;
use App\Models\CbtReviewDecisionV3Model;
use InvalidArgumentException;
use RuntimeException;

/**
 * CbtAssessmentFinalizationService
 *
 * ESPATA Assessment Intelligence V3 - Iteration 3
 * Authoritative single pathway for calculating and finalizing student assessment scores.
 *
 * Enforces:
 * 1. Strict separation of 7 domain statuses (Session, Participation, Integrity, Essay, Test, Assessment, Publication).
 * 2. Immutable test configuration snapshots and decimal precision with BCMath.
 * 3. OD-ALL-TYPE-VOID: Proportional weight redistribution when all items of a type are void.
 * 4. DEC-ESS-01 & OD-02: Strict Essay Completion Gate (NULL score until 100% graded; essay excluded from TP analytics).
 * 5. DEC-ABS-01: Absence cannot be converted to earned zero; preserved as NULL.
 * 6. DEC-RET-01: Multi-session attempts require explicit ratified owner_approval_ref before eligibility.
 * 7. Tamper-evident hash chain logging in cbt_audit_trail_v3.
 */
class CbtAssessmentFinalizationService
{
    // Domain Status 1: Session Status
    public const SESSION_IN_PROGRESS = 'IN_PROGRESS';
    public const SESSION_FINISHED    = 'FINISHED';
    public const SESSION_ABANDONED   = 'ABANDONED';
    public const SESSION_EXPIRED     = 'EXPIRED';

    // Domain Status 2: Participation Status
    public const PARTICIPATION_PRESENT   = 'PRESENT';
    public const PARTICIPATION_ABSENT    = 'ABSENT';
    public const PARTICIPATION_DISPENSED = 'DISPENSED';
    public const PARTICIPATION_EXCUSED   = 'EXCUSED';

    // Domain Status 3: Exam Integrity Status
    public const INTEGRITY_VALID            = 'VALID';
    public const INTEGRITY_SUSPICIOUS       = 'SUSPICIOUS';
    public const INTEGRITY_CHEATING_FLAGGED = 'CHEATING_FLAGGED';
    public const INTEGRITY_VOID             = 'VOID';

    // Domain Status 4: Essay Correction Status
    public const ESSAY_NOT_APPLICABLE   = 'NOT_APPLICABLE';
    public const ESSAY_PENDING_GRADING  = 'PENDING_GRADING';
    public const ESSAY_PARTIALLY_GRADED = 'PARTIALLY_GRADED';
    public const ESSAY_FULLY_GRADED     = 'FULLY_GRADED';
    public const ESSAY_REVISED          = 'REVISED';

    // Domain Status 5: Test Validity Status
    public const TEST_VALID_TEST         = 'VALID_TEST';
    public const TEST_PARTIAL_TYPE_VOID  = 'PARTIAL_TYPE_VOID';
    public const TEST_NO_VALID_QUESTIONS = 'NO_VALID_QUESTIONS';

    // Domain Status 6: Assessment Result Status
    public const ASSESSMENT_HOLD         = 'HOLD';
    public const ASSESSMENT_PROVISIONAL  = 'PROVISIONAL';
    public const ASSESSMENT_FINALIZED    = 'FINALIZED';
    public const ASSESSMENT_QUARANTINED  = 'QUARANTINED';

    protected CbtAnswerV3Model $answerModel;
    protected CbtEssayGradeV3Model $essayGradeModel;
    protected CbtSessionScoreV3Model $sessionScoreModel;
    protected CbtAuditTrailV3Model $auditTrailModel;
    protected ?CbtReviewCaseV3Model $reviewCaseModel;
    protected ?CbtReviewDecisionV3Model $reviewDecisionModel;

    public function __construct(
        ?CbtAnswerV3Model $answerModel = null,
        ?CbtEssayGradeV3Model $essayGradeModel = null,
        ?CbtSessionScoreV3Model $sessionScoreModel = null,
        ?CbtAuditTrailV3Model $auditTrailModel = null,
        ?CbtReviewCaseV3Model $reviewCaseModel = null,
        ?CbtReviewDecisionV3Model $reviewDecisionModel = null
    ) {
        $this->answerModel         = $answerModel ?? new CbtAnswerV3Model();
        $this->essayGradeModel     = $essayGradeModel ?? new CbtEssayGradeV3Model();
        $this->sessionScoreModel   = $sessionScoreModel ?? new CbtSessionScoreV3Model();
        $this->auditTrailModel     = $auditTrailModel ?? new CbtAuditTrailV3Model();
        $this->reviewCaseModel     = $reviewCaseModel;
        $this->reviewDecisionModel = $reviewDecisionModel;
    }

    /**
     * Calculate normalized question type weights implementing OD-ALL-TYPE-VOID.
     * Redistributes voided types' weights proportionally to remaining active types.
     *
     * @param array $baseWeights Base weights per question type (e.g. ['pg' => 40, 'esai' => 30, 'bs' => 30])
     * @param array $activeQuestionCounts Number of valid/active questions per type (e.g. ['pg' => 20, 'esai' => 5, 'bs' => 0])
     * @return array ['test_validity' => string, 'normalized_weights' => array, 'redistributed' => bool]
     */
    public function calculateNormalizedWeights(array $baseWeights, array $activeQuestionCounts): array
    {
        $activeSum = '0.0000';
        $filteredBase = [];

        foreach ($baseWeights as $type => $weight) {
            $typeLower = strtolower($type);
            $count = (int)($activeQuestionCounts[$typeLower] ?? 0);

            if ($count > 0 && (float)$weight > 0) {
                $filteredBase[$typeLower] = (string)$weight;
                $activeSum = bcadd($activeSum, (string)$weight, 4);
            }
        }

        // Check if all questions are void
        if (bccomp($activeSum, '0.0000', 4) === 0) {
            return [
                'test_validity'      => self::TEST_NO_VALID_QUESTIONS,
                'normalized_weights' => [],
                'redistributed'      => false,
                'active_sum'         => '0.0000',
            ];
        }

        $redistributed = (count($filteredBase) < count($baseWeights));
        $normalized = [];
        $runningSum = '0.0000';
        $keys = array_keys($filteredBase);
        $lastKey = end($keys);

        // Normalize proportionally to 100.0%
        foreach ($filteredBase as $type => $weight) {
            if ($type === $lastKey) {
                // Ensure exact 100.0000 total without floating point drift
                $normalized[$type] = bcsub('100.0000', $runningSum, 4);
            } else {
                $prop = bcdiv(bcmul($weight, '100.0000', 6), $activeSum, 4);
                $normalized[$type] = $prop;
                $runningSum = bcadd($runningSum, $prop, 4);
            }
        }

        return [
            'test_validity'      => $redistributed ? self::TEST_PARTIAL_TYPE_VOID : self::TEST_VALID_TEST,
            'normalized_weights' => $normalized,
            'redistributed'      => $redistributed,
            'active_sum'         => $activeSum,
        ];
    }

    /**
     * Finalize assessment for a student session.
     * Enforces DEC-ESS-01, DEC-ABS-01, DEC-RET-01, and OD-ALL-TYPE-VOID.
     */
    public function finalizeSession(
        int $sessionId,
        array $sessionContext,
        ?array $answers = null,
        ?array $testSnapshot = null
    ): array {
        $studentId           = (int)($sessionContext['student_id'] ?? 0);
        $testId              = (int)($sessionContext['test_id'] ?? 0);
        $participationStatus = strtoupper(trim((string)($sessionContext['participation_status'] ?? self::PARTICIPATION_PRESENT)));
        $integrityStatus     = strtoupper(trim((string)($sessionContext['integrity_status'] ?? self::INTEGRITY_VALID)));
        $sessionStatus       = strtoupper(trim((string)($sessionContext['session_status'] ?? self::SESSION_FINISHED)));
        $isRetakeCandidate   = !empty($sessionContext['is_retake_candidate']);
        $ownerApprovalRef    = $sessionContext['owner_approval_ref'] ?? null;

        $blockers = [];

        // 1. Enforce DEC-ABS-01 (Student Absence, Excused, Dispensed)
        if (in_array($participationStatus, [self::PARTICIPATION_ABSENT, self::PARTICIPATION_EXCUSED, self::PARTICIPATION_DISPENSED], true)) {
            $blockers[] = "STUDENT_NON_ATTENDANCE: [DEC-ABS-01] Non-attending students ({$participationStatus}) must not receive earned scores; final score remains NULL.";
            return $this->buildHeldResponse(
                $sessionId,
                $studentId,
                $testId,
                $participationStatus,
                $integrityStatus,
                self::ASSESSMENT_HOLD,
                self::ESSAY_NOT_APPLICABLE,
                self::TEST_VALID_TEST,
                $blockers,
                "Siswa tidak hadir ({$participationStatus}) di bawah DEC-ABS-01."
            );
        }

        // 2. Enforce Integrity Status
        if (in_array($integrityStatus, [self::INTEGRITY_CHEATING_FLAGGED, self::INTEGRITY_VOID], true)) {
            $blockers[] = "INTEGRITY_VIOLATION: Exam session flagged with integrity violation '{$integrityStatus}'. Final score withheld.";
            return $this->buildHeldResponse(
                $sessionId,
                $studentId,
                $testId,
                $participationStatus,
                $integrityStatus,
                self::ASSESSMENT_QUARANTINED,
                self::ESSAY_NOT_APPLICABLE,
                self::TEST_VALID_TEST,
                $blockers,
                "Ujian dikarantina akibat pelanggaran integritas '{$integrityStatus}'."
            );
        }

        // 3. Enforce DEC-RET-01 (Retake Session Selection)
        if ($isRetakeCandidate && empty($ownerApprovalRef)) {
            $blockers[] = "AMBIGUOUS_RETAKE: [DEC-RET-01] Multiple attempts exist without explicit ratified 'owner_approval_ref'.";
            return $this->buildHeldResponse(
                $sessionId,
                $studentId,
                $testId,
                $participationStatus,
                $integrityStatus,
                self::ASSESSMENT_QUARANTINED,
                self::ESSAY_NOT_APPLICABLE,
                self::TEST_VALID_TEST,
                $blockers,
                'Kasus retake menunggu keputusan formal pemilik sistem di bawah DEC-RET-01.'
            );
        }

        // 4. Load test snapshot configuration & calculate type weights (OD-ALL-TYPE-VOID)
        $snapshot = $testSnapshot ?? ($sessionContext['test_snapshot'] ?? [
            'base_weights' => ['pg' => 50.0, 'esai' => 50.0],
            'items'        => [],
        ]);

        $baseWeights = $snapshot['base_weights'] ?? ['pg' => 100.0];
        $itemsConfig = $snapshot['items'] ?? [];

        // Count active items by type
        $activeItemCounts = [];
        $itemTypesMap = [];
        $itemMaxScores = [];

        foreach ($itemsConfig as $item) {
            $itemVerId = (int)($item['item_version_id'] ?? $item['id']);
            $type = strtolower(trim((string)($item['question_type'] ?? 'pg')));
            $isVoid = !empty($item['is_void']);
            $maxScore = (float)($item['item_weight'] ?? $item['max_score'] ?? 1.0);

            if (!$isVoid) {
                $activeItemCounts[$type] = ($activeItemCounts[$type] ?? 0) + 1;
                $itemTypesMap[$itemVerId] = $type;
                $itemMaxScores[$itemVerId] = $maxScore;
            }
        }

        // Weight normalization & OD-ALL-TYPE-VOID check
        $weightAnalysis = $this->calculateNormalizedWeights($baseWeights, $activeItemCounts);

        if ($weightAnalysis['test_validity'] === self::TEST_NO_VALID_QUESTIONS) {
            $blockers[] = 'NO_VALID_QUESTIONS: [OD-ALL-TYPE-VOID] All questions in exam are voided. Assessment cannot produce a final score.';
            return $this->buildHeldResponse(
                $sessionId,
                $studentId,
                $testId,
                $participationStatus,
                $integrityStatus,
                self::ASSESSMENT_HOLD,
                self::ESSAY_NOT_APPLICABLE,
                self::TEST_NO_VALID_QUESTIONS,
                $blockers,
                'Seluruh soal dibatalkan (NO_VALID_QUESTIONS).'
            );
        }

        $normalizedWeights = $weightAnalysis['normalized_weights'];

        // 5. Gather student answers & compute points per type
        $answersList = $answers ?? $this->answerModel->where('session_id', $sessionId)->findAll();

        $rawScoresByType   = [];
        $maxScoresByType   = [];
        $pendingEssayCount = 0;
        $totalEssayCount   = 0;
        $gradedEssayCount  = 0;

        foreach ($answersList as $ans) {
            $itemVerId = (int)$ans['item_version_id'];
            if (!isset($itemTypesMap[$itemVerId])) {
                // Item is void or not active in snapshot
                continue;
            }

            $type = $itemTypesMap[$itemVerId];
            $itemMax = $itemMaxScores[$itemVerId] ?? 1.0;
            $allocated = $ans['allocated_score'] !== null ? (float)$ans['allocated_score'] : null;

            $maxScoresByType[$type] = ($maxScoresByType[$type] ?? 0.0) + $itemMax;

            if ($type === 'esai' || $type === 'essay') {
                $totalEssayCount++;
                if ($allocated === null) {
                    $pendingEssayCount++;
                } else {
                    $gradedEssayCount++;
                    $rawScoresByType['esai'] = ($rawScoresByType['esai'] ?? 0.0) + $allocated;
                }
            } else {
                $rawScoresByType[$type] = ($rawScoresByType[$type] ?? 0.0) + ($allocated ?? 0.0);
            }
        }

        // Determine Essay Correction Status
        $essayStatus = self::ESSAY_NOT_APPLICABLE;
        if ($totalEssayCount > 0) {
            if ($pendingEssayCount === $totalEssayCount) {
                $essayStatus = self::ESSAY_PENDING_GRADING;
            } elseif ($pendingEssayCount > 0) {
                $essayStatus = self::ESSAY_PARTIALLY_GRADED;
            } else {
                $essayStatus = self::ESSAY_FULLY_GRADED;
            }
        }

        // 6. Enforce DEC-ESS-01 (Essay Completion Gate)
        $scorePg = round($rawScoresByType['pg'] ?? 0.0, 3);
        $scorePgKompleks = round($rawScoresByType['pg_kompleks'] ?? 0.0, 3);
        $scoreBs = round($rawScoresByType['benar_salah'] ?? ($rawScoresByType['bs'] ?? 0.0), 3);
        $scoreEsai = ($totalEssayCount > 0 && $pendingEssayCount === 0) ? round($rawScoresByType['esai'] ?? 0.0, 3) : null;

        if ($pendingEssayCount > 0) {
            $blockers[] = sprintf(
                'PENDING_ESSAY_GRADING: [DEC-ESS-01] %d essay questions remain ungraded. Final score is withheld (NULL).',
                $pendingEssayCount
            );

            // Record provisional intermediate state in database
            $scoreRecord = $this->sessionScoreModel->recalculateSessionScore(
                $sessionId,
                $scorePg,
                null,
                $scorePgKompleks,
                $scoreBs,
                $pendingEssayCount
            );

            // Log audit
            $this->auditTrailModel->appendLog(
                'ASSESSMENT_HELD',
                'cbt_session_scores_v3',
                (string)$scoreRecord['id'],
                $sessionId,
                'SYSTEM',
                [
                    'session_id'          => $sessionId,
                    'reason'              => 'DEC-ESS-01: Essay grading incomplete',
                    'pending_essay_count' => $pendingEssayCount,
                    'blockers'            => $blockers,
                ]
            );

            return [
                'success'                 => true,
                'is_eligible'             => false,
                'session_id'              => $sessionId,
                'student_id'              => $studentId,
                'test_id'                 => $testId,
                'session_status'          => $sessionStatus,
                'participation_status'    => $participationStatus,
                'integrity_status'        => $integrityStatus,
                'essay_status'            => $essayStatus,
                'test_validity_status'    => $weightAnalysis['test_validity'],
                'assessment_status'       => self::ASSESSMENT_HOLD,
                'pending_essay_count'     => $pendingEssayCount,
                'score_components'        => [
                    'pg'          => $scorePg,
                    'pg_kompleks' => $scorePgKompleks,
                    'bs'          => $scoreBs,
                    'esai'        => null,
                ],
                'final_score'             => null,
                'report_score'            => null,
                'blockers'                => $blockers,
                'governance_notes'        => 'Nilai akhir ditahan (NULL) sesuai DEC-ESS-01 sampai guru menyelesaikan koreksi esai.',
            ];
        }

        // 7. Compute Final Score deterministically using BCMath
        // Formula per type: (rawScore / maxScore) * normalizedWeight
        $finalSum = '0.0000';
        $componentPercentages = [];

        foreach ($normalizedWeights as $type => $normWeight) {
            $raw = $rawScoresByType[$type] ?? 0.0;
            $max = $maxScoresByType[$type] ?? 1.0;

            if ($max <= 0) {
                $max = 1.0;
            }

            // Ratio: raw / max
            $ratio = bcdiv((string)$raw, (string)$max, 6);
            // Contribution: ratio * normWeight
            $contrib = bcmul($ratio, (string)$normWeight, 4);

            $finalSum = bcadd($finalSum, $contrib, 4);
            $componentPercentages[$type] = round((float)$contrib, 3);
        }

        $finalScore = round((float)$finalSum, 3);
        // Bounded to 0.0 - 100.0
        if ($finalScore < 0.0) {
            $finalScore = 0.0;
        }
        if ($finalScore > 100.0) {
            $finalScore = 100.0;
        }

        $reportScore = $finalScore;
        $isEligible = empty($blockers);

        // 8. Update database persistence
        $scoreRecord = $this->sessionScoreModel->recalculateSessionScore(
            $sessionId,
            $scorePg,
            $scoreEsai,
            $scorePgKompleks,
            $scoreBs,
            0
        );

        // 9. Tamper-evident Audit Trail Logging
        $auditPayload = [
            'session_id'              => $sessionId,
            'student_id'              => $studentId,
            'test_id'                 => $testId,
            'final_score'             => $finalScore,
            'report_score'            => $reportScore,
            'component_percentages'   => $componentPercentages,
            'normalized_weights'      => $normalizedWeights,
            'redistributed_weights'   => $weightAnalysis['redistributed'],
            'is_eligible'             => $isEligible,
            'essay_status'            => $essayStatus,
            'test_validity_status'    => $weightAnalysis['test_validity'],
        ];

        $this->auditTrailModel->appendLog(
            'ASSESSMENT_FINALIZED',
            'cbt_session_scores_v3',
            (string)$scoreRecord['id'],
            $sessionId,
            'SYSTEM',
            $auditPayload
        );

        return [
            'success'                 => true,
            'is_eligible'             => $isEligible,
            'session_id'              => $sessionId,
            'student_id'              => $studentId,
            'test_id'                 => $testId,
            'session_status'          => $sessionStatus,
            'participation_status'    => $participationStatus,
            'integrity_status'        => $integrityStatus,
            'essay_status'            => $essayStatus,
            'test_validity_status'    => $weightAnalysis['test_validity'],
            'assessment_status'       => self::ASSESSMENT_FINALIZED,
            'pending_essay_count'     => 0,
            'score_components'        => [
                'pg'          => $scorePg,
                'pg_kompleks' => $scorePgKompleks,
                'bs'          => $scoreBs,
                'esai'        => $scoreEsai,
            ],
            'normalized_weights'      => $normalizedWeights,
            'final_score'             => $finalScore,
            'report_score'            => $reportScore,
            'blockers'                => [],
            'governance_notes'        => 'Hasil asesmen berhasil difinalisasi dan layak dipublikasikan ke gradebook.',
        ];
    }

    /**
     * Helper to construct a HELD response for ineligible sessions.
     */
    private function buildHeldResponse(
        int $sessionId,
        int $studentId,
        int $testId,
        string $participationStatus,
        string $integrityStatus,
        string $assessmentStatus,
        string $essayStatus,
        string $testValidityStatus,
        array $blockers,
        string $governanceNotes
    ): array {
        return [
            'success'                 => true,
            'is_eligible'             => false,
            'session_id'              => $sessionId,
            'student_id'              => $studentId,
            'test_id'                 => $testId,
            'session_status'          => self::SESSION_FINISHED,
            'participation_status'    => $participationStatus,
            'integrity_status'        => $integrityStatus,
            'essay_status'            => $essayStatus,
            'test_validity_status'    => $testValidityStatus,
            'assessment_status'       => $assessmentStatus,
            'pending_essay_count'     => 0,
            'score_components'        => [
                'pg'          => null,
                'pg_kompleks' => null,
                'bs'          => null,
                'esai'        => null,
            ],
            'final_score'             => null,
            'report_score'            => null,
            'blockers'                => $blockers,
            'governance_notes'        => $governanceNotes,
        ];
    }
}

