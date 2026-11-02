<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;
use App\Services\Cbt\Assessment\AssessmentEngineService;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Reporting\AssessmentReportingService;
use App\Services\Cbt\Shadow\LegacyScoreReconstructor;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Services\Cbt\Gate\CutoverGateEvaluator;
use App\Services\Cbt\Gate\CutoverEvidenceCollector;
use App\Services\Cbt\Gradebook\GradebookSyncService;
use InvalidArgumentException;
use RuntimeException;

/**
 * CbtStageF3BTest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3B Test Suite
 * Fully implements and verifies Product Owner Decision Record (PODR - 1 Nov 2026):
 * 1. DEC-RET-01: Explicit Retake Selection & Prohibition of Automatic Heuristics
 * 2. DEC-ESS-01: Essay Grading Incomplete HOLD on Final & Report Scores
 * 3. DEC-ROUND-01: Rounding Difference Tolerance strictly <= 0.05
 * 4. DEC-ABS-01: Absence Zero Fallback Preservation & Prohibition of Earned Zero Conversion
 * 5. Review Workflow & Audit Trail: Decision ID tracking, Fail-Closed on Corrupted Audit
 * 6. Non-automated resolution of 5 shadow review queue cases
 */
class CbtStageF3BTest extends TestCase
{
    private string $tempAuditFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempAuditFile = sys_get_temp_dir() . '/f3b_test_audit_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempAuditFile)) {
            @unlink($this->tempAuditFile);
        }
        parent::tearDown();
    }

    // =========================================================================
    // 1. DEC-RET-01: EXPLICIT RETAKE SELECTION
    // =========================================================================

    public function testDecRet01SingleAttemptFollowsExistingContract(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 85.0],
        ];

        $res = $policy->selectAttempt($sessions, 10, 50);
        $this->assertEquals('SELECTED', $res['status']);
        $this->assertEquals('SINGLE_ATTEMPT', $res['policy_used']);
        $this->assertEquals(101, $res['selected_session']['id']);
        $this->assertEquals(85.0, $res['selected_session']['score']);
    }

    public function testDecRet01MultipleAttemptsWithoutExplicitSelectionRemainsAmbiguous(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0],
        ];

        $res = $policy->selectAttempt($sessions, 10, 50, AttemptSelectorPolicy::POLICY_REQUIRE_MANUAL);
        $this->assertEquals('AMBIGUOUS_ATTEMPTS', $res['status']);
        $this->assertNull($res['selected_session']);
        $this->assertEquals(2, $res['candidate_count']);
        $this->assertEquals('DEC-RET-01', $res['decision_id']);
    }

    public function testDecRet01ExplicitSelectionAcceptedWithValidAuthorization(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0],
        ];

        $res = $policy->selectExplicitAttempt(
            $sessions,
            10,
            50,
            102,
            'HEADMASTER_01',
            'PRINCIPAL',
            'Official retake session validated by principal',
            'SK-KUR-2026-088'
        );

        $this->assertEquals('SELECTED', $res['status']);
        $this->assertEquals(102, $res['selected_session']['id']);
        $this->assertEquals(90.0, $res['selected_session']['score']);
        $this->assertEquals(AttemptSelectorPolicy::POLICY_EXPLICIT_SELECTION, $res['policy_used']);
        $this->assertEquals('DEC-RET-01', $res['decision_id']);
        $this->assertEquals('SK-KUR-2026-088', $res['owner_approval_ref']);
    }

    public function testDecRet01SelectionWithoutOwnerApprovalRefIsRejected(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DEC-RET-01');

        $policy->selectExplicitAttempt(
            $sessions,
            10,
            50,
            102,
            'OPERATOR_01',
            'ADMIN',
            'No approval ref given',
            '' // Empty owner_approval_ref!
        );
    }

    public function testDecRet01SelectionByUnauthorizedActorIsRejected(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0],
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('not authorized');

        $policy->selectExplicitAttempt(
            $sessions,
            10,
            50,
            102,
            'STUDENT_10',
            'STUDENT', // Unauthorized role!
            'Self-selection',
            'APPROVAL_001'
        );
    }

    public function testDecRet01ExplicitSelectionDoesNotMutateCandidateScores(): void
    {
        $policy = new AttemptSelectorPolicy();
        $sessions = [
            ['id' => 101, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 10, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0],
        ];

        $res = $policy->selectExplicitAttempt($sessions, 10, 50, 102, 'ACTOR_1', 'OWNER', 'Authorized', 'REF-99');
        $this->assertEquals(90.0, $res['selected_session']['score']);
        // Verify candidate session array was not mutated
        $this->assertEquals(70.0, $sessions[0]['score']);
        $this->assertEquals(90.0, $sessions[1]['score']);
    }

    public function testDecRet01ReviewerValidatorRejectsRetakeSelectionWithoutOwnerRef(): void
    {
        $validator = new ReviewerDecisionValidator();
        $case = [
            'case_id'         => 'REV_RETAKE_01',
            'category'        => 'AMBIGUOUS_RETAKE',
            'relevant_status' => 'AMBIGUOUS_ATTEMPTS',
        ];

        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH: [DEC-RET-01]');

        // Reviewer tries to select session without owner approval ref
        $validator->validate(
            $case,
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Attempting selection without ref',
            ['selected_session_id' => 102, 'owner_approval_ref' => '']
        );
    }

    // =========================================================================
    // 2. DEC-ESS-01: ESSAY PENDING (HOLD POLICY)
    // =========================================================================

    public function testDecEss01PendingEssayProducesNullFinalAndReportScore(): void
    {
        $engine = new AssessmentEngineService();
        $reporting = new AssessmentReportingService();

        $testConfig = [
            'test_id'      => 701,
            'type_weights' => ['pg' => 60.0, 'essay' => 40.0],
        ];
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'weight' => 60.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'essay', 'max_points' => 100.0, 'weight' => 40.0, 'is_void' => false],
        ];
        $answers = [
            ['item_id' => 1, 'student_answer' => 'A'], // Objective correct (100% * 60 = 60.0)
            ['item_id' => 2, 'score_earned' => null, 'is_graded' => false], // Essay pending!
        ];

        $result = $engine->evaluateStudent($testConfig, $items, $answers, ['student_id' => 101, 'session_status' => 'finished']);

        // Assert AssessmentResult invariants under DEC-ESS-01
        $this->assertEquals(100.0, $result->getObjectiveScore());
        $this->assertNull($result->getFinalScore());
        $this->assertNull($result->getReportScore());
        $this->assertFalse($result->isEligibleForGradebook());
        $this->assertFalse($result->isFullyGraded());

        // Assert Reporting Projection invariants
        $report = $reporting->projectStudentReport($result);
        $this->assertNull($report['test_score']['final_score']);
        $this->assertEquals(100.0, $report['test_score']['objective_scale_score']);
        $this->assertFalse($report['gradebook_report']['is_eligible']);
        $this->assertNull($report['gradebook_report']['report_score']);
        $this->assertEquals('DEC-ESS-01', $report['test_score']['decision_id']);
        $this->assertEquals('DEC-ESS-01 (HOLD)', $report['gradebook_report']['hold_policy']);
    }

    public function testDecEss01ReviewerCannotForcePublishPendingEssay(): void
    {
        $validator = new ReviewerDecisionValidator();
        $case = [
            'case_id'         => 'REV_ESSAY_01',
            'category'        => 'PENDING_ESSAY',
            'relevant_status' => 'PENDING_ESSAY',
        ];

        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH: Reviewer cannot approve or publish scores for PENDING_ESSAY.');

        $validator->validate(
            $case,
            'PUBLISH',
            'Attempting bypass'
        );
    }

    public function testDecEss01RecalculationAfterTeacherGradingCompletes(): void
    {
        $engine = new AssessmentEngineService();

        $testConfig = [
            'test_id'      => 701,
            'type_weights' => ['pg' => 60.0, 'essay' => 40.0],
        ];
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'weight' => 60.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'essay', 'max_points' => 100.0, 'weight' => 40.0, 'is_void' => false],
        ];

        // 1. Initial State: Essay ungraded
        $initialAnswers = [
            ['item_id' => 1, 'student_answer' => 'A'],
            ['item_id' => 2, 'score_earned' => null, 'is_graded' => false],
        ];
        $initialResult = $engine->evaluateStudent($testConfig, $items, $initialAnswers, ['student_id' => 101, 'session_status' => 'finished']);
        $this->assertNull($initialResult->getFinalScore());

        // 2. Teacher grades essay (75 points out of 100)
        $completedAnswers = [
            ['item_id' => 1, 'student_answer' => 'A'],
            ['item_id' => 2, 'score_earned' => 75.0, 'is_graded' => true],
        ];
        $finalResult = $engine->evaluateStudent($testConfig, $items, $completedAnswers, ['student_id' => 101, 'session_status' => 'finished']);

        // 60 (obj) + (75% * 40 = 30) = 90.0
        $this->assertEquals(90.0, $finalResult->getFinalScore());
        $this->assertEquals(90.0, $finalResult->getReportScore());
        $this->assertTrue($finalResult->isEligibleForGradebook());
        $this->assertTrue($finalResult->isFullyGraded());
        // Verify objective score was not duplicated
        $this->assertEquals(100.0, $finalResult->getObjectiveScore());
    }

    // =========================================================================
    // 3. DEC-ROUND-01: ROUNDING DIFFERENCE TOLERANCE (STRICTLY <= 0.05)
    // =========================================================================

    public function testDecRound01ExactThresholds(): void
    {
        $validator = new ReviewerDecisionValidator();

        // 1. Delta 0.02 (49.98 vs 50.00) -> Allowed
        $case1 = ['case_id' => 'C1', 'category' => 'ROUNDING_DIFFERENCE', 'legacy_score' => 49.98, 'v3_score' => 50.00, 'delta' => 0.02];
        $res1 = $validator->validate($case1, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Delta 0.02');
        $this->assertEquals(ReviewerDecisionValidator::DECISION_RESOLVED, $res1['decision_status']);
        $this->assertEquals('DEC-ROUND-01', $res1['decision_id']);

        // 2. Delta 0.05 (50.00 vs 50.05) -> Allowed
        $case2 = ['case_id' => 'C2', 'category' => 'ROUNDING_DIFFERENCE', 'legacy_score' => 50.00, 'v3_score' => 50.05, 'delta' => 0.05];
        $res2 = $validator->validate($case2, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Delta 0.05');
        $this->assertEquals(ReviewerDecisionValidator::DECISION_RESOLVED, $res2['decision_status']);

        // 3. Delta 0.05 inverted (50.05 vs 50.00) -> Allowed
        $case3 = ['case_id' => 'C3', 'category' => 'ROUNDING_DIFFERENCE', 'legacy_score' => 50.05, 'v3_score' => 50.00, 'delta' => 0.05];
        $res3 = $validator->validate($case3, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Delta 0.05');
        $this->assertEquals(ReviewerDecisionValidator::DECISION_RESOLVED, $res3['decision_status']);

        // 4. Delta 0.051 (50.00 vs 50.051) -> Rejected!
        $case4 = ['case_id' => 'C4', 'category' => 'ROUNDING_DIFFERENCE', 'legacy_score' => 50.00, 'v3_score' => 50.051, 'delta' => 0.051];
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('exceeds permitted tolerance of 0.05');
        $validator->validate($case4, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Delta 0.051');
    }

    public function testDecRound01Delta010IsRejected(): void
    {
        $validator = new ReviewerDecisionValidator();
        $case = ['case_id' => 'C5', 'category' => 'ROUNDING_DIFFERENCE', 'legacy_score' => 49.90, 'v3_score' => 50.00, 'delta' => 0.10];

        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('exceeds permitted tolerance of 0.05');
        $validator->validate($case, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Delta 0.10');
    }

    public function testDecRound01AcknowledgementPreservesSourceScoresWithoutMutation(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $workflow = new ReviewerWorkflowService(null, $audit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_RND',
                'test_id'                 => 501,
                'session_id'              => 101,
                'legacy_score'            => 49.98,
                'v3_score'                => 50.00,
                'delta'                   => 0.02,
                'is_comparable'           => true,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'notes'                   => 'Delta 0.02',
            ],
        ]);

        $cases = $workflow->getQueue();
        $c = $cases[0];

        $updated = $workflow->applyDecision(
            $c['case_id'],
            'REV_ADMIN',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Acknowledged under DEC-ROUND-01'
        );

        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $updated['review_status']);
        $this->assertEquals('DEC-ROUND-01', $updated['decision_id']);
        // Scores MUST NOT be mutated
        $this->assertEquals(49.98, $updated['legacy_score']);
        $this->assertEquals(50.00, $updated['v3_score']);
        $this->assertEquals(0.02, $updated['delta']);
    }

    // =========================================================================
    // 4. DEC-ABS-01: ABSENCE VS EARNED ZERO
    // =========================================================================

    public function testDecAbs01ZeroWithConfirmedAbsentEvidenceYieldsAbsent(): void
    {
        $reconstructor = new LegacyScoreReconstructor();

        // 1. Session is null (student did not register session) + confirmed absence
        $resNull = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 10,
            'attendance_evidence' => 'CONFIRMED_ABSENT',
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_ABSENT, $resNull['semantic_status']);
        $this->assertTrue($resNull['is_zero_fallback']);
        $this->assertFalse($resNull['is_participating']);
        $this->assertEquals(0.0, $resNull['converted_score']);
        $this->assertEquals('DEC-ABS-01', $resNull['decision_id']);

        // 2. Session exists with score 0.0 + status 'absent'
        $sessionAbsent = ['id' => 99, 'student_id' => 10, 'score' => 0.0, 'total_score' => 0.0, 'status' => 'absent'];
        $resAbsent = $reconstructor->reconstructFromSession($sessionAbsent);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_ABSENT, $resAbsent['semantic_status']);
        $this->assertTrue($resAbsent['is_zero_fallback']);
        $this->assertFalse($resAbsent['is_participating']);
    }

    public function testDecAbs01ZeroWithConfirmedPresentEvidenceYieldsEarnedZero(): void
    {
        $reconstructor = new LegacyScoreReconstructor();

        // Student attended exam (answers_count > 0, started_at recorded) and legitimately scored 0.0
        $sessionPresent = [
            'id'             => 101,
            'student_id'     => 12,
            'score'          => 0.0,
            'total_score'    => 0.0,
            'status'         => 'finished',
            'answers_count'  => 20,
            'started_at'     => '2026-11-01 08:00:00',
        ];

        $res = $reconstructor->reconstructFromSession($sessionPresent, [
            'attendance_evidence' => 'CONFIRMED_PRESENT',
        ]);

        $this->assertEquals(LegacyScoreReconstructor::STATUS_EARNED_ZERO, $res['semantic_status']);
        $this->assertFalse($res['is_zero_fallback']);
        $this->assertTrue($res['is_participating']);
        $this->assertEquals(0.0, $res['raw_score']);
        $this->assertEquals(0.0, $res['converted_score']);
    }

    public function testDecAbs01ZeroWithInsufficientEvidenceYieldsLegacyUnverified(): void
    {
        $reconstructor = new LegacyScoreReconstructor();

        // Score is 0.0 but attendance evidence is insufficient/unknown
        $sessionUnknown = [
            'id'          => 102,
            'student_id'  => 13,
            'score'       => 0.0,
            'total_score' => 0.0,
            'status'      => 'finished',
        ];

        $res = $reconstructor->reconstructFromSession($sessionUnknown, [
            'attendance_evidence' => 'INSUFFICIENT_EVIDENCE',
        ]);

        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $res['semantic_status']);
        $this->assertFalse($res['is_verifiable']);
        $this->assertEquals(LegacyScoreReconstructor::SOURCE_UNVERIFIABLE, $res['source_type']);
        $this->assertEquals(0.0, $res['raw_score']); // Raw score preserved
    }

    public function testDecRound01StrictDecimalBoundaryMatrix(): void
    {
        $validator = new ReviewerDecisionValidator();

        // Exact matrix mandated by PODR & V3.29B-R2 specifications
        $matrix = [
            ['delta' => 0.050000,    'legacy' => 50.00, 'v3' => 50.05,       'expected' => 'ACK'],
            ['delta' => '0.050000',  'legacy' => 50.00, 'v3' => 50.05,       'expected' => 'ACK'],
            ['delta' => 0.0500000,   'legacy' => 50.00, 'v3' => 50.05,       'expected' => 'ACK'],
            ['delta' => '0.0500000', 'legacy' => 50.00, 'v3' => 50.05,       'expected' => 'ACK'],
            ['delta' => 0.0500001,   'legacy' => 50.00, 'v3' => 50.0500001,  'expected' => 'REJECT'],
            ['delta' => '0.0500001', 'legacy' => 50.00, 'v3' => 50.0500001,  'expected' => 'REJECT'],
            ['delta' => 0.0500004,   'legacy' => 50.00, 'v3' => 50.0500004,  'expected' => 'REJECT'],
            ['delta' => '0.0500004', 'legacy' => 50.00, 'v3' => 50.0500004,  'expected' => 'REJECT'],
            ['delta' => 0.050001,    'legacy' => 50.00, 'v3' => 50.050001,   'expected' => 'REJECT'],
            ['delta' => '0.050001',  'legacy' => 50.00, 'v3' => 50.050001,   'expected' => 'REJECT'],
            ['delta' => 0.05001,     'legacy' => 50.00, 'v3' => 50.05001,    'expected' => 'REJECT'],
            ['delta' => '0.05001',   'legacy' => 50.00, 'v3' => 50.05001,    'expected' => 'REJECT'],
            ['delta' => 0.05004,     'legacy' => 50.00, 'v3' => 50.05004,    'expected' => 'REJECT'],
            ['delta' => '0.05004',   'legacy' => 50.00, 'v3' => 50.05004,    'expected' => 'REJECT'],
            ['delta' => 0.0501,      'legacy' => 50.00, 'v3' => 50.0501,     'expected' => 'REJECT'],
            ['delta' => '0.0501',    'legacy' => 50.00, 'v3' => 50.0501,     'expected' => 'REJECT'],
            // Negative deltas (must verify absolute value)
            ['delta' => -0.050000,   'legacy' => 50.05, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => '-0.050000', 'legacy' => 50.05, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => -0.0500001,  'legacy' => 50.0500001, 'v3' => 50.00,  'expected' => 'REJECT'],
            ['delta' => '-0.0500001','legacy' => 50.0500001, 'v3' => 50.00,  'expected' => 'REJECT'],
            ['delta' => -0.05004,    'legacy' => 50.05004, 'v3' => 50.00,    'expected' => 'REJECT'],
            ['delta' => '-0.05004',  'legacy' => 50.05004, 'v3' => 50.00,    'expected' => 'REJECT'],
            // Zero delta
            ['delta' => 0,           'legacy' => 50.00, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => 0.0,         'legacy' => 50.00, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => '0.0000',    'legacy' => 50.00, 'v3' => 50.00,       'expected' => 'ACK'],
            // Within tolerance
            ['delta' => 0.0100,      'legacy' => 50.00, 'v3' => 50.01,       'expected' => 'ACK'],
            ['delta' => 0.0499,      'legacy' => 50.00, 'v3' => 50.0499,     'expected' => 'ACK'],
            ['delta' => 0.0499999,   'legacy' => 50.00, 'v3' => 50.0499999,  'expected' => 'ACK'],
            ['delta' => 0.0500,      'legacy' => 50.00, 'v3' => 50.05,       'expected' => 'ACK'],
            ['delta' => 0.1000,      'legacy' => 50.00, 'v3' => 50.10,       'expected' => 'REJECT'],
            ['delta' => -0.0100,     'legacy' => 50.01, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => -0.0500,     'legacy' => 50.05, 'v3' => 50.00,       'expected' => 'ACK'],
            ['delta' => -0.0510,     'legacy' => 50.051, 'v3' => 50.00,      'expected' => 'REJECT'],
            ['delta' => -0.1000,     'legacy' => 50.10, 'v3' => 50.00,       'expected' => 'REJECT'],
        ];

        foreach ($matrix as $idx => $testCase) {
            $case = [
                'case_id'      => 'CASE_ROUND_' . $idx,
                'category'     => 'ROUNDING_DIFFERENCE',
                'legacy_score' => $testCase['legacy'],
                'v3_score'     => $testCase['v3'],
                'delta'        => $testCase['delta'],
            ];

            if ($testCase['expected'] === 'ACK') {
                $outcome = $validator->validate(
                    $case,
                    ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                    'Testing valid rounding delta within tolerance'
                );
                $this->assertEquals(ReviewerDecisionValidator::DECISION_RESOLVED, $outcome['decision_status'], "Delta '{$testCase['delta']}' must be acknowledged (ACK).");
                $this->assertEquals('DEC-ROUND-01', $outcome['decision_id']);
            } else {
                $rejected = false;
                try {
                    $validator->validate(
                        $case,
                        ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                        'Testing exceeding delta'
                    );
                } catch (InvalidReviewActionException $e) {
                    $rejected = true;
                    $this->assertStringContainsString('GOVERNANCE BREACH: [DEC-ROUND-01]', $e->getMessage());
                }
                $this->assertTrue($rejected, "Delta '{$testCase['delta']}' must be strictly rejected (REJECT).");
            }
        }
    }

    public function testDecRound01NullDeltaScoreSubtractionPrecision(): void
    {
        $validator = new ReviewerDecisionValidator();

        // 1. Equal or within 0.05 (delta is null, calculated via bcsub)
        $caseAck = [
            'case_id'      => 'CASE_SUB_ACK',
            'category'     => 'ROUNDING_DIFFERENCE',
            'legacy_score' => '50.00',
            'v3_score'     => '50.0500000',
            'delta'        => null,
        ];
        $resAck = $validator->validate($caseAck, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'bcsub 0.0500000');
        $this->assertEquals(ReviewerDecisionValidator::DECISION_RESOLVED, $resAck['decision_status']);

        // 2. Exceeds 0.05 by 0.0000001 (50.00 vs 50.0500001)
        $caseRej = [
            'case_id'      => 'CASE_SUB_REJ',
            'category'     => 'ROUNDING_DIFFERENCE',
            'legacy_score' => '50.00',
            'v3_score'     => '50.0500001',
            'delta'        => null,
        ];
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('exceeds permitted tolerance of 0.05');
        $validator->validate($caseRej, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'bcsub 0.0500001');
    }

    public function testDecRound01InvalidDeltaFormatThrows(): void
    {
        $validator = new ReviewerDecisionValidator();
        $case = [
            'case_id'      => 'CASE_INV',
            'category'     => 'ROUNDING_DIFFERENCE',
            'legacy_score' => 50.00,
            'v3_score'     => 50.02,
            'delta'        => 'not_a_valid_number',
        ];

        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('Delta must be a valid numeric value');
        $validator->validate($case, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, 'Invalid delta');
    }

    public function testDecAbs01AttendanceEvidenceMatrix(): void
    {
        $reconstructor = new LegacyScoreReconstructor();

        // 1. Confirmed absent with valid structured evidence
        $resValidAbsent = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 101,
            'attendance_evidence' => [
                'status'     => 'CONFIRMED_ABSENT',
                'student_id' => 101,
                'session_id' => null,
            ],
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_ABSENT, $resValidAbsent['semantic_status']);
        $this->assertTrue($resValidAbsent['is_zero_fallback']);
        $this->assertEquals(0.0, $resValidAbsent['converted_score']);

        // 2. Confirmed present with valid structured evidence
        $resValidPresent = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 102,
            'attendance_evidence' => [
                'status'     => 'CONFIRMED_PRESENT',
                'student_id' => 102,
                'session_id' => null,
            ],
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_EARNED_ZERO, $resValidPresent['semantic_status']);
        $this->assertFalse($resValidPresent['is_zero_fallback']);
        $this->assertEquals(0.0, $resValidPresent['raw_score']);

        // 3. Evidence empty (null / empty string / empty array) -> MUST be LEGACY_UNVERIFIED
        $resEmptyEvidence = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 103,
            'attendance_evidence' => null,
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $resEmptyEvidence['semantic_status']);
        $this->assertFalse($resEmptyEvidence['is_verifiable']);

        // 4. Evidence incomplete (missing status) -> MUST be LEGACY_UNVERIFIED
        $resIncomplete = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 104,
            'attendance_evidence' => ['student_id' => 104],
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $resIncomplete['semantic_status']);

        // 5. Evidence referencing different student -> MUST be LEGACY_UNVERIFIED (Cross-student contamination defense)
        $resDiffStudent = $reconstructor->reconstructFromSession(null, [
            'student_id'          => 105,
            'attendance_evidence' => [
                'status'     => 'CONFIRMED_ABSENT',
                'student_id' => 999, // Mismatched student ID
            ],
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $resDiffStudent['semantic_status']);

        // 6. Evidence referencing different test session -> MUST be LEGACY_UNVERIFIED (Cross-session contamination defense)
        $sessionExisting = ['id' => 201, 'student_id' => 106, 'score' => null, 'total_score' => null];
        $resDiffSession = $reconstructor->reconstructFromSession($sessionExisting, [
            'attendance_evidence' => [
                'status'     => 'CONFIRMED_ABSENT',
                'student_id' => 106,
                'session_id' => 888, // Mismatched session ID
            ],
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $resDiffSession['semantic_status']);

        // 7. Legacy zero without evidence must NOT become ABSENT -> MUST be LEGACY_UNVERIFIED
        $sessionZeroNoAnswers = [
            'id'            => 202,
            'student_id'    => 107,
            'score'         => 0.0,
            'total_score'   => 0.0,
            'answers_count' => 0,
            'started_at'    => null,
        ];
        $resZeroNoAnswers = $reconstructor->reconstructFromSession($sessionZeroNoAnswers, [
            'attendance_evidence' => null,
        ]);
        $this->assertEquals(LegacyScoreReconstructor::STATUS_LEGACY_UNVERIFIED, $resZeroNoAnswers['semantic_status']);
        $this->assertNotEquals(LegacyScoreReconstructor::STATUS_ABSENT, $resZeroNoAnswers['semantic_status']);
        // Legacy raw score 0.0 is strictly preserved
        $this->assertEquals(0.0, $resZeroNoAnswers['raw_score']);
        $this->assertEquals(0.0, $resZeroNoAnswers['converted_score']);
    }

    public function testDecAbs01ReviewerCannotConvertAbsentToEarnedZero(): void
    {
        $validator = new ReviewerDecisionValidator();
        $case = [
            'case_id'         => 'REV_ABS_01',
            'category'        => 'LEGACY_ZERO_FALLBACK',
            'relevant_status' => 'ABSENT',
        ];

        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH: Reviewer cannot convert student absence into an earned zero score.');

        $validator->validate($case, 'CONVERT_TO_EARNED_ZERO', 'Trying to convert');
    }

    // =========================================================================
    // 5. REVIEW WORKFLOW, AUDIT TRAIL, AND FAIL-CLOSED CORRUPTION DEFENSE
    // =========================================================================

    public function testAtomicAuditSuccessAppliesSanctionedChanges(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();
        $workflow = new ReviewerWorkflowService(null, $audit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_ATOMIC_OK',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'delta'                   => 0.02,
                'legacy_score'            => 49.98,
                'v3_score'                => 50.00,
                'is_comparable'           => true,
            ],
        ]);

        $casesBefore = $workflow->getQueue();
        $caseId = $casesBefore[0]['case_id'];

        $updated = $workflow->applyDecision(
            $caseId,
            'REV_ADMIN',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Valid acknowledgement of 0.02 delta',
            ['decision_id' => 'DEC-ROUND-01']
        );

        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $updated['review_status']);
        $this->assertEquals('None (Resolved)', $updated['responsible_party']);
        $this->assertEquals('REV_ADMIN', $updated['reviewer_id']);
        $this->assertNotEmpty($updated['resolved_at']);
        $this->assertEquals('DEC-ROUND-01', $updated['decision_id']);
        $this->assertEquals('Valid acknowledgement of 0.02 delta', $updated['decision_reason']);

        // Verify audit log has 1 clean chained record
        $this->assertEquals(1, $audit->count());
        $auditIntegrity = $audit->verifyAuditIntegrity();
        $this->assertTrue($auditIntegrity['is_valid']);
    }

    public function testAtomicAuditFileCannotBeOpenedRollsBackEntireState(): void
    {
        // Unopenable path (invalid Windows device/directory structure)
        $badAudit = new ReviewerAuditService('Z:/non_existent_drive/unopenable_audit.json');
        $workflow = new ReviewerWorkflowService(null, $badAudit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_UNOPENABLE',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'delta'                   => 0.02,
                'legacy_score'            => 49.98,
                'v3_score'                => 50.00,
                'is_comparable'           => true,
            ],
        ]);

        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];
        $caseId = $caseBefore['case_id'];

        $writeFailed = false;
        try {
            $workflow->applyDecision(
                $caseId,
                'REV_OPERATOR',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Acknowledgement that must fail persistence'
            );
        } catch (\Throwable $e) {
            $writeFailed = true;
        }

        $this->assertTrue($writeFailed, 'Persistence failure must throw exception.');

        $casesAfter = $workflow->getQueue();
        $caseAfter = $casesAfter[0];

        // 100% full snapshot equality across all keys
        $this->assertSame($caseBefore, $caseAfter);
    }

    public function testAtomicAuditCorruptedHashChainBlocksActionWithoutStateChange(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();

        // 1. Write clean record
        $audit->recordDecision('REV_1', 'C1', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE_POLICY_DIFF', 'Ack 1');

        // 2. Corrupt audit file externally on disk
        $records = json_decode(file_get_contents($this->tempAuditFile), true);
        $records[0]['record_hash'] = 'ffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffffff';
        file_put_contents($this->tempAuditFile, json_encode($records, JSON_PRETTY_PRINT));

        // 3. Workflow attempting to apply decision must FAIL-CLOSED
        $workflow = new ReviewerWorkflowService(null, $audit);
        $workflow->ingestComparisonResults([
            ['pseudonymous_student_id' => 'STD_CHAIN_CORRUPT', 'test_id' => 501, 'categories' => ['ROUNDING_DIFFERENCE'], 'delta' => 0.02, 'is_comparable' => true],
        ]);
        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];

        $corruptBlocked = false;
        try {
            $workflow->applyDecision(
                $caseBefore['case_id'],
                'REV_ADMIN',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Should be blocked by corrupted audit trail'
            );
        } catch (\RuntimeException $e) {
            $corruptBlocked = true;
            $this->assertStringContainsString('AUDIT INTEGRITY CORRUPTED', $e->getMessage());
        }

        $this->assertTrue($corruptBlocked);

        $casesAfter = $workflow->getQueue();
        $this->assertSame($caseBefore, $casesAfter[0], 'Case state must remain strictly unmodified when audit chain is broken.');
    }

    public function testAtomicAuditSequenceInvalidBlocksActionWithoutStateChange(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();

        // 1. Write clean record
        $audit->recordDecision('REV_1', 'C1', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE_POLICY_DIFF', 'Ack 1');

        // 2. Corrupt sequence number on disk
        $records = json_decode(file_get_contents($this->tempAuditFile), true);
        $records[0]['sequence_number'] = 99; // invalid sequence
        file_put_contents($this->tempAuditFile, json_encode($records, JSON_PRETTY_PRINT));

        $workflow = new ReviewerWorkflowService(null, $audit);
        $workflow->ingestComparisonResults([
            ['pseudonymous_student_id' => 'STD_SEQ_INVALID', 'test_id' => 501, 'categories' => ['ROUNDING_DIFFERENCE'], 'delta' => 0.02, 'is_comparable' => true],
        ]);
        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];

        $seqBlocked = false;
        try {
            $workflow->applyDecision(
                $caseBefore['case_id'],
                'REV_ADMIN',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Should be blocked by sequence anomaly'
            );
        } catch (\RuntimeException $e) {
            $seqBlocked = true;
        }

        $this->assertTrue($seqBlocked);
        $casesAfter = $workflow->getQueue();
        $this->assertSame($caseBefore, $casesAfter[0]);
    }

    public function testAtomicAuditDecisionIdMismatchBlocksActionWithoutStateChange(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();
        $workflow = new ReviewerWorkflowService(null, $audit);

        $workflow->ingestComparisonResults([
            ['pseudonymous_student_id' => 'STD_DEC_MISMATCH', 'test_id' => 501, 'categories' => ['ROUNDING_DIFFERENCE'], 'delta' => 0.02, 'is_comparable' => true],
        ]);
        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];

        $mismatchBlocked = false;
        try {
            $workflow->applyDecision(
                $caseBefore['case_id'],
                'REV_ADMIN',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Attempting wrong decision id',
                ['decision_id' => 'DEC-ABS-01'] // Mismatch! Expects DEC-ROUND-01
            );
        } catch (InvalidReviewActionException $e) {
            $mismatchBlocked = true;
            $this->assertStringContainsString('does not match required policy decision', $e->getMessage());
        }

        $this->assertTrue($mismatchBlocked);
        $casesAfter = $workflow->getQueue();
        $this->assertSame($caseBefore, $casesAfter[0], 'State must not change on decision id mismatch.');
    }

    public function testAtomicAuditUnauthorizedActorRoleBlocksActionWithoutStateChange(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();
        $workflow = new ReviewerWorkflowService(null, $audit);

        $workflow->ingestComparisonResults([
            ['pseudonymous_student_id' => 'STD_UNAUTH', 'test_id' => 501, 'categories' => ['ROUNDING_DIFFERENCE'], 'delta' => 0.02, 'is_comparable' => true],
        ]);
        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];

        $unauthBlocked = false;
        try {
            $workflow->applyDecision(
                $caseBefore['case_id'],
                'STUDENT_USER',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Unauthorized action',
                ['actor_role' => 'STUDENT']
            );
        } catch (InvalidReviewActionException $e) {
            $unauthBlocked = true;
            $this->assertStringContainsString("Actor with role 'STUDENT' is not authorized", $e->getMessage());
        }

        $this->assertTrue($unauthBlocked);
        $casesAfter = $workflow->getQueue();
        $this->assertSame($caseBefore, $casesAfter[0], 'State must not change when actor is unauthorized.');
    }

    public function testAtomicAuditPersistenceFailureAfterValidationSucceedsRollsBack(): void
    {
        // Unwritable path simulates persistence failure AFTER validator validation succeeds
        $badAudit = new ReviewerAuditService('Z:/unwritable/dir/fail.json');
        $workflow = new ReviewerWorkflowService(null, $badAudit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_POST_VALIDATION_FAIL',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'delta'                   => 0.02,
                'legacy_score'            => 49.98,
                'v3_score'                => 50.00,
                'is_comparable'           => true,
            ],
        ]);

        $casesBefore = $workflow->getQueue();
        $caseBefore = $casesBefore[0];
        $caseId = $caseBefore['case_id'];

        $persistenceFailed = false;
        try {
            $workflow->applyDecision(
                $caseId,
                'REV_VALID_ADMIN',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Legitimate justification that passes validator but fails storage write'
            );
        } catch (\Throwable $e) {
            $persistenceFailed = true;
        }

        $this->assertTrue($persistenceFailed);

        $casesAfter = $workflow->getQueue();
        $caseAfter = $casesAfter[0];

        // Zero mutation: all attributes remain exactly as before
        $this->assertSame($caseBefore, $caseAfter);
    }

    public function testAtomicAuditZeroPartialStateMutationFullSnapshotComparison(): void
    {
        $badAudit = new ReviewerAuditService('Z:/read_only/audit.json');
        $workflow = new ReviewerWorkflowService(null, $badAudit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_FULL_SNAPSHOT',
                'test_id'                 => 503,
                'session_id'              => 101,
                'categories'              => ['AMBIGUOUS_RETAKE'],
                'legacy_score'            => 90.0,
                'v3_score'                => null,
                'is_comparable'           => false,
            ],
        ]);

        $caseBefore = $workflow->getQueue()[0];

        try {
            $workflow->applyDecision(
                $caseBefore['case_id'],
                'REV_OPERATOR',
                ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
                'Valid selection with ref',
                [
                    'selected_session_id' => 101,
                    'owner_approval_ref'  => 'SK-OWNER-99',
                    'decision_id'         => 'DEC-RET-01',
                ]
            );
        } catch (\Throwable $e) {
            // Expected audit write failure
        }

        $caseAfter = $workflow->getQueue()[0];

        // Check each and every specific field mentioned in specification
        $this->assertEquals($caseBefore['review_status'], $caseAfter['review_status']);
        $this->assertEquals($caseBefore['responsible_party'], $caseAfter['responsible_party']);
        $this->assertEquals($caseBefore['decision_id'] ?? null, $caseAfter['decision_id'] ?? null);
        $this->assertEquals($caseBefore['reviewer_id'] ?? null, $caseAfter['reviewer_id'] ?? null);
        $this->assertEquals($caseBefore['decision_reason'] ?? null, $caseAfter['decision_reason'] ?? null);
        $this->assertEquals($caseBefore['resolved_at'] ?? null, $caseAfter['resolved_at'] ?? null);
        $this->assertEquals($caseBefore['selected_session_id'] ?? null, $caseAfter['selected_session_id'] ?? null);
        $this->assertEquals($caseBefore['owner_approval_ref'] ?? null, $caseAfter['owner_approval_ref'] ?? null);

        // Entire array identical
        $this->assertSame($caseBefore, $caseAfter);
    }

    // =========================================================================
    // 6. 5 SHADOW REVIEW QUEUE CASES ARE NOT AUTO-RESOLVED & RECONCILED WITH PODR
    // =========================================================================

    public function testAuthoritativeCaseIdReconciliationWithPodr(): void
    {
        $workflow = new ReviewerWorkflowService();
        $deviationsCsv = 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/shadow_deviations.csv';

        $this->assertFileExists($deviationsCsv);

        $fp = fopen($deviationsCsv, 'r');
        fgetcsv($fp);
        $rawCases = [];
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 8) {
                $rawCases[] = [
                    'pseudonymous_student_id' => $row[0],
                    'test_id'                 => (int)$row[1],
                    'session_id'              => $row[2] !== 'NULL' && $row[2] !== '' ? $row[2] : null,
                    'legacy_score'            => $row[3] !== 'NULL' ? (float)$row[3] : null,
                    'v3_score'                => $row[4] !== 'NULL' ? (float)$row[4] : null,
                    'delta'                   => $row[5] !== 'NULL' ? (float)$row[5] : null,
                    'is_comparable'           => $row[6] === 'YES',
                    'categories'              => explode('|', $row[7]),
                    'notes'                   => $row[8] ?? '',
                ];
            }
        }
        fclose($fp);

        $workflow->ingestComparisonResults($rawCases, [
            501 => 'Matematika',
            502 => 'Bahasa Indonesia',
            503 => 'Ilmu Pengetahuan Alam',
        ]);

        $cases = $workflow->getQueue();
        $this->assertCount(5, $cases);

        // Authoritative mapping from Product Owner Decision Record (PODR 1 Nov 2026)
        $expectedPodrMap = [
            'REV_501_2b6231cb77' => [
                'test_id'     => 501,
                'category'    => 'ROUNDING_DIFFERENCE',
                'decision_id' => 'DEC-ROUND-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
            'REV_501_7b48bc8a1f' => [
                'test_id'     => 501,
                'category'    => 'LEGACY_ZERO_FALLBACK',
                'decision_id' => 'DEC-ABS-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
            'REV_502_3ff144b11c' => [
                'test_id'     => 502,
                'category'    => 'PENDING_ESSAY',
                'decision_id' => 'DEC-ESS-01',
                'status'      => ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED,
            ],
            'REV_503_4266a4ed7f' => [
                'test_id'     => 503,
                'category'    => 'AMBIGUOUS_RETAKE',
                'decision_id' => 'DEC-RET-01',
                'status'      => ReviewerWorkflowService::STATUS_OWNER_DECISION_REQUIRED,
            ],
            'REV_503_fc68cd64f2' => [
                'test_id'     => 503,
                'category'    => 'LEGACY_ZERO_FALLBACK',
                'decision_id' => 'DEC-ABS-01',
                'status'      => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            ],
        ];

        foreach ($cases as $case) {
            $caseId = $case['case_id'];
            $this->assertArrayHasKey($caseId, $expectedPodrMap, "Case ID {$caseId} must exist in authoritative PODR map.");
            $expected = $expectedPodrMap[$caseId];

            $this->assertEquals($expected['test_id'], (int)$case['test_id'], "Test ID mismatch for {$caseId}");
            $this->assertEquals($expected['category'], $case['category'], "Category mismatch for {$caseId}");
            $this->assertEquals($expected['status'], $case['review_status'], "Initial status mismatch for {$caseId}");
        }
    }

    public function testFiveShadowReviewCasesAreNotAutoResolved(): void
    {
        $workflow = new ReviewerWorkflowService();
        $deviationsCsv = 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/shadow_deviations.csv';

        if (file_exists($deviationsCsv)) {
            $fp = fopen($deviationsCsv, 'r');
            fgetcsv($fp);
            $rawCases = [];
            while (($row = fgetcsv($fp)) !== false) {
                if (count($row) >= 8) {
                    $rawCases[] = [
                        'pseudonymous_student_id' => $row[0],
                        'test_id'                 => $row[1],
                        'session_id'              => $row[2] !== 'NULL' ? $row[2] : null,
                        'legacy_score'            => $row[3] !== 'NULL' ? (float)$row[3] : null,
                        'v3_score'                => $row[4] !== 'NULL' ? (float)$row[4] : null,
                        'delta'                   => $row[5] !== 'NULL' ? (float)$row[5] : null,
                        'is_comparable'           => $row[6] === 'YES',
                        'categories'              => explode('|', $row[7]),
                        'notes'                   => $row[8] ?? '',
                    ];
                }
            }
            fclose($fp);
            $workflow->ingestComparisonResults($rawCases, [
                501 => 'Matematika',
                502 => 'Bahasa Indonesia',
                503 => 'Ilmu Pengetahuan Alam',
            ]);
        }

        $summary = $workflow->getSummary();
        $this->assertEquals(5, $summary['total_cases']);
        $this->assertEquals(5, $summary['pending_cases'], 'Policy enactment must NOT auto-resolve shadow review cases!');
        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(1, $summary['party_breakdown']['teacher_action_required']);
        $this->assertEquals(1, $summary['party_breakdown']['owner_decision_required']);
        $this->assertEquals(3, $summary['party_breakdown']['acknowledgement_required']);

        // Gate Evaluator status must remain REQUIRES_REVIEW and ready_for_staging FALSE
        $evaluator = new CutoverGateEvaluator();
        $verdict = $evaluator->evaluateGate([], $workflow);
        $this->assertEquals(CutoverGateEvaluator::STATUS_REQUIRES_REVIEW, $verdict['gate_status']);
        $this->assertFalse($verdict['ready_for_staging']);
        $this->assertFalse($verdict['ready_for_production']);
    }
}

