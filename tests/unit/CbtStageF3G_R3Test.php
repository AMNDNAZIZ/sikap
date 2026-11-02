<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
    if (str_starts_with($class, 'Config\\')) {
        $file = dirname(__DIR__, 2) . '/app/Config/' . str_replace('\\', '/', substr($class, 7)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerEvidenceValidator;
use App\Services\Cbt\Review\ReviewerSourceVerifier;
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use PDO;

/**
 * CbtStageF3G_R3Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R3
 * Fail-Closed Semantic Enforcement & Readiness Consistency Verification.
 */
class CbtStageF3G_R3Test extends TestCase
{
    /**
     * Test 1: Caller without provenance fail-closes to SCORE_PRESENT_UNVERIFIED and HOLD.
     */
    public function testCallerWithoutProvenanceYieldsScorePresentUnverifiedAndHold(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [
            ['id' => 1, 'score' => '85.00'],
        ];

        // Call evaluateEssayGradingState without passing $provenance (defaults to null)
        $res = $adapter->evaluateEssayGradingState(85.0, $answers, 1);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORE_PRESENT_UNVERIFIED, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);
        $this->assertTrue($res['final_score_withheld']);
        $this->assertFalse($res['is_eligible']);
        $this->assertNull($res['final_score']);
        $this->assertNull($res['report_score']);
        $this->assertStringContainsString('provenance', $res['discrepancy']);
    }

    /**
     * Test 2: Incomplete or invalid grader provenance fail-closes.
     */
    public function testIncompleteOrInvalidProvenanceFailsClosed(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 2.A: Missing grader_id
        $provNoId = ['is_authorized' => true, 'graded_at' => '2026-11-01 10:00:00', 'change_log_reference' => 'LOG_01'];
        $chk1 = $adapter->verifyGraderProvenance($provNoId);
        $this->assertFalse($chk1['is_verified']);
        $this->assertStringContainsString('grader_id', $chk1['discrepancy']);

        // 2.B: Unauthorized grader
        $provUnauth = ['grader_id' => 'TCH_99', 'is_authorized' => false, 'graded_at' => '2026-11-01 10:00:00', 'change_log_reference' => 'LOG_01'];
        $chk2 = $adapter->verifyGraderProvenance($provUnauth);
        $this->assertFalse($chk2['is_verified']);
        $this->assertStringContainsString('authority', $chk2['discrepancy']);

        // 2.C: Missing change log reference
        $provNoLog = ['grader_id' => 'TCH_01', 'is_authorized' => true, 'graded_at' => '2026-11-01 10:00:00'];
        $chk3 = $adapter->verifyGraderProvenance($provNoLog);
        $this->assertFalse($chk3['is_verified']);
        $this->assertStringContainsString('change log reference', $chk3['discrepancy']);

        // 2.D: Context student mismatch
        $provMismatch = [
            'grader_id'            => 'TCH_01',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_01',
            'student_id'           => 999,
        ];
        $chk4 = $adapter->verifyGraderProvenance($provMismatch, ['student_id' => 501]);
        $this->assertFalse($chk4['is_verified']);
        $this->assertStringContainsString('Provenance student mismatch', $chk4['discrepancy']);
    }

    /**
     * Test 3: Authenticated provenance unlocks SCORED_POSITIVE and SCORED_ALL_ZERO.
     */
    public function testAuthenticatedGraderProvenanceUnlocksPositiveAndZeroScores(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $validProvenance = [
            'grader_id'            => 'TCH_01',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_ESSAY_PROVEN',
        ];

        // 3.A: Positive score
        $resPos = $adapter->evaluateEssayGradingState(
            75.0,
            [['id' => 1, 'score' => '75.00']],
            1,
            false,
            $validProvenance
        );
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORED_POSITIVE, $resPos['state']);
        $this->assertFalse($resPos['is_hold_enforced']);
        $this->assertFalse($resPos['final_score_withheld']);
        $this->assertTrue($resPos['is_eligible']);
        $this->assertEquals('TCH_01', $resPos['grader_id']);

        // 3.B: Legitimate zero score awarded by teacher
        $resZero = $adapter->evaluateEssayGradingState(
            0.0,
            [['id' => 1, 'score' => '0.00']],
            1,
            false,
            $validProvenance
        );
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORED_ALL_ZERO, $resZero['state']);
        $this->assertFalse($resZero['is_hold_enforced']);
        $this->assertFalse($resZero['final_score_withheld']);
        $this->assertTrue($resZero['is_eligible']);
        $this->assertEquals(0.0, $resZero['final_score']);
    }

    /**
     * Test 4: Blank answer row without participation verified yields BLANK_ANSWER_RECORD_PRESENT_UNVERIFIED.
     */
    public function testBlankAnswerRowWithoutParticipationVerifiedYieldsUnverified(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $blankAnswers = [
            ['id' => 1, 'answer' => '', 'score' => null],
        ];

        // Unverified participation context
        $resUnverified = $adapter->evaluateEssayGradingState(null, $blankAnswers, 1, false, null, []);
        $this->assertEquals('BLANK_ANSWER_RECORD_PRESENT_UNVERIFIED', $resUnverified['unanswered_subreason']);

        // Verified participation context
        $resVerified = $adapter->evaluateEssayGradingState(null, $blankAnswers, 1, false, null, [
            'attendance_verified'        => true,
            'session_ownership_verified' => true,
        ]);
        $this->assertEquals('PROVEN_BLANK_SUBMISSION', $resVerified['unanswered_subreason']);
    }

    /**
     * Test 5: Absence evidence missing when BAP is not provided.
     */
    public function testAbsenceEvidenceMissingWhenBapNotProvided(): void
    {
        $adapter = new HistoricalSourceAdapter();

        $res1 = $adapter->verifyAbsenceEvidence(null);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_EVIDENCE_MISSING, $res1['status']);
        $this->assertFalse($res1['is_verified']);

        $res2 = $adapter->verifyAbsenceEvidence(['evidence_id' => 'E1', 'metadata' => []]);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_EVIDENCE_MISSING, $res2['status']);
        $this->assertFalse($res2['is_verified']);
    }

    /**
     * Test 6: Absence evidence present unverified when BAP is incomplete or unsigned.
     */
    public function testAbsenceEvidencePresentUnverifiedWhenBapIncompleteOrUnsigned(): void
    {
        $adapter = new HistoricalSourceAdapter();

        $unsignedBap = [
            'evidence_id'  => 'E1',
            'bap_document' => [
                'bap_number'            => 'BAP-2026-001',
                'proctor_id'            => 'PRC_01',
                'is_authorized_proctor' => true,
                'exam_date'             => '2026-11-01',
                'is_bap_signed'         => false, // Unsigned!
            ],
        ];

        $res = $adapter->verifyAbsenceEvidence($unsignedBap);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED, $res['status']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString('incomplete or unverified', $res['discrepancy']);
    }

    /**
     * Test 7: Authenticated BAP unlocks ABSENCE_VERIFIED.
     */
    public function testAuthenticatedBapUnlocksAbsenceVerified(): void
    {
        $adapter = new HistoricalSourceAdapter();

        $validBap = [
            'evidence_id'  => 'EVD_ABS_501',
            'bap_document' => [
                'bap_number'            => 'BAP-2026-501-OFFICIAL',
                'proctor_id'            => 'PROCTOR_ALPHA',
                'is_authorized_proctor' => true,
                'exam_date'             => '2026-11-01',
                'is_bap_signed'         => true,
                'student_id'            => 501,
                'test_id'               => 101,
            ],
        ];

        $res = $adapter->verifyAbsenceEvidence($validBap, ['student_id' => 501, 'test_id' => 101]);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_VERIFIED, $res['status']);
        $this->assertTrue($res['is_verified']);
        $this->assertEquals('BAP-2026-501-OFFICIAL', $res['bap_reference']);
    }

    /**
     * Test 8: bobot_esai = 0 with essay questions in bank does NOT yield NO_ESSAY_QUESTIONS.
     */
    public function testZeroEssayWeightWithBankQuestionsDoesNotYieldNoEssayQuestions(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 8.A: Config says 0 mandatory essay count, but question bank contains essay questions
        $res = $adapter->evaluateEssayGradingState(null, [], 0, false, null, [
            'has_essay_questions_in_bank' => true,
        ]);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_PENDING, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);

        // 8.B: Formally voided under OD-ALL-TYPE-VOID yields NO_ESSAY_QUESTIONS
        $resVoid = $adapter->evaluateEssayGradingState(null, [], 0, false, null, [
            'has_essay_questions_in_bank' => true,
            'is_all_type_void'            => true,
        ]);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_NO_QUESTIONS, $resVoid['state']);
        $this->assertFalse($resVoid['is_hold_enforced']);
        $this->assertStringContainsString('OD-ALL-TYPE-VOID', $resVoid['reason']);
    }

    /**
     * Test 9: Multi-dimensional readiness matrix enforces fail-closed.
     */
    public function testMultiDimensionalReadinessMatrixEnforcesFailClosed(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $cases = [
            ['case_id' => 'REV_501_2b6231cb77', 'category' => 'ROUNDING_DIFFERENCE', 'student_id' => 1, 'test_id' => 501],
            ['case_id' => 'REV_501_7b48bc8a1f', 'category' => 'LEGACY_ZERO_FALLBACK', 'student_id' => 2, 'test_id' => 501],
        ];
        $evidence = [
            ['evidence_id' => 'EV1', 'related_case_id' => 'REV_501_2b6231cb77', 'evidence_type' => 'SCORE_COMPARISON', 'source_record_id' => 'cbt_aktivitas:101', 'verification_status' => 'UNVERIFIED'],
            ['evidence_id' => 'EV2', 'related_case_id' => 'REV_501_7b48bc8a1f', 'evidence_type' => 'SESSION_RECORD',   'source_record_id' => 'cbt_aktivitas:null', 'verification_status' => 'UNVERIFIED'],
        ];

        // Without DB connection, evidence_readiness and session_ownership are false
        $matrix = $adapter->auditTraceability($cases, $evidence, null);

        foreach ($cases as $c) {
            $row = $matrix[$c['case_id']];
            $this->assertFalse($row['ready_for_authorized_review'], "Case {$c['case_id']} cannot be ready for review without verified dimensions.");
            $this->assertEquals('NOT_READY_FAIL_CLOSED', $row['overall_readiness']);
            $this->assertFalse($row['cutover_ready']);
        }
    }

    /**
     * Test 10: Client parameters cannot bypass DEC-ABS-01 without authentic BAP registry.
     */
    public function testClientParametersCannotBypassDecAbs01(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $case = [
            'case_id'                 => 'REV_501_7b48bc8a1f',
            'pseudonymous_student_id' => 'STU_501_P',
            'test_id'                 => 501,
            'legacy_score'            => '0.0',
            'relevant_status'         => 'ABSENT',
        ];
        $actor = [
            'username'  => 'reviewer_01',
            'role'      => 'REVIEWER',
            'authority' => 'REVIEWER',
        ];

        // Caller attempts to bypass with client flag ['evidence_verified' => true, 'enforce_strict_registry' => true]
        $res = $validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            $actor,
            [
                'decision_id'             => 'DEC-ABS-01',
                'evidence_verified'       => true,
                'enforce_strict_registry' => true,
            ]
        );

        $this->assertFalse($res['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $res['readiness_status']);
        $this->assertStringContainsString('DEC-ABS-01 requires authentic proctor-signed attendance record', implode(' ', $res['rejection_reasons']));
    }
}

