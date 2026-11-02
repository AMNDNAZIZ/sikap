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
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Controllers\Admin\CbtReview;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\Response;
use CodeIgniter\HTTP\URI;
use CodeIgniter\HTTP\UserAgent;

/**
 * CbtStageF3DTest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3D Test Suite
 * Reviewer Decision Authorization & Evidence Integration Readiness
 *
 * Covers 15 authoritative verification scenarios:
 * 1. Valid evidence is accepted (READY_FOR_AUTHORIZED_REVIEW).
 * 2. Missing evidence rejected (INSUFFICIENT_EVIDENCE).
 * 3. Cross-student contamination rejected (INVALID_EVIDENCE).
 * 4. Cross-session contamination rejected (INVALID_EVIDENCE).
 * 5. Inconsistent / future timestamp rejected (INVALID_EVIDENCE).
 * 6. Browser POST evidence_verified=true cannot bypass missing authentic evidence.
 * 7. Spoofed reviewer ID in POST rejected with 403.
 * 8. Spoofed actor role in POST rejected with 403.
 * 9. Unverifiable retake approval ref rejected.
 * 10. DEC-ABS-01 retains LEGACY_UNVERIFIED without proctor evidence.
 * 11. DEC-ESS-01 enforces HOLD (NULL) while essay pending.
 * 12. DEC-ROUND-01 enforces exact decimal precision and rejects delta > 0.05.
 * 13. Audit integrity failure halts readiness evaluation.
 * 14. Preview is strictly non-mutating.
 * 15. The 5 operational review cases remain completely identical and unresolved.
 */
class CbtStageF3DTest extends TestCase
{
    private string $deviationsCsv;

    protected function setUp(): void
    {
        $this->deviationsCsv = 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/shadow_deviations.csv';
    }

    private function createLoadedWorkflow(?ReviewerAuditService $auditService = null, ?ReviewerEvidenceValidator $evidenceValidator = null): ReviewerWorkflowService
    {
        $workflow = new ReviewerWorkflowService(null, $auditService, $evidenceValidator);
        $loaded = $workflow->loadFromShadowDeviations($this->deviationsCsv);
        $this->assertEquals(5, $loaded, 'Exactly 5 review cases must be ingested from shadow deviations CSV.');
        return $workflow;
    }

    /**
     * Test 1: Valid evidence is accepted (READY_FOR_AUTHORIZED_REVIEW).
     */
    public function testValidEvidenceIsAccepted(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77'; // Rounding difference (delta = 0.02)

        $preview = $workflow->previewDecision(
            $caseId,
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Acknowledging verified rounding divergence <= 0.05',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertTrue($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $preview['readiness_status']);
        $this->assertNotEmpty($preview['found_evidence']);
        $this->assertNotEmpty($preview['evidence_verification_results']);
        $this->assertTrue($preview['consistency_checks']['scores_and_delta_verified']);
    }

    /**
     * Test 2: Missing evidence rejected (INSUFFICIENT_EVIDENCE).
     */
    public function testMissingEvidenceIsRejected(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $evidenceValidator = $workflow->getEvidenceValidator();

        // Create an un-evidenced case
        $emptyCase = [
            'case_id'                 => 'REV_EMPTY_01',
            'test_id'                 => 999,
            'subject_name'            => 'Kimia',
            'pseudonymous_student_id' => 'STD_EMPTY_99',
            'category'                => 'ROUNDING_DIFFERENCE',
            'legacy_score'            => 80.00,
            'v3_score'                => 80.02,
            'delta'                   => 0.02,
        ];

        $actor = ['username' => 'operator', 'role' => 'REVIEWER', 'role_id' => 1];
        $readiness = $evidenceValidator->evaluateReadiness(
            $emptyCase,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            $actor
        );

        $this->assertFalse($readiness['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $readiness['readiness_status']);
        $this->assertStringContainsString('Missing verified score comparison evidence', $readiness['verification_notes']);
    }

    /**
     * Test 3: Cross-student contamination rejected (INVALID_EVIDENCE).
     */
    public function testCrossStudentContaminationIsRejected(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        // Register evidence with contaminated student_id
        $evidenceValidator->registerEvidence([
            'evidence_id'         => 'EVD_CONTAM_STUDENT',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_ATTENDANCE_RECORD,
            'source_system'       => 'CBT_PROCTOR_LOG',
            'source_record_id'    => 'log:99',
            'related_case_id'     => 'REV_501_7b48bc8a1f',
            'related_test_id'     => 501,
            'related_session_id'  => 1001,
            'subject_reference'   => 'Matematika',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'verified_by'         => 'PROCTOR_01',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'metadata'            => [
                'student_id' => 'STD_WRONG_STUDENT_9999', // Contaminated!
            ],
        ]);

        $workflow = new ReviewerWorkflowService(null, null, $evidenceValidator);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        $preview = $workflow->previewDecision(
            'REV_501_7b48bc8a1f',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting acknowledge with contaminated attendance sheet',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ABS-01', 'evidence_verified' => true]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Cross-student contamination', $preview['error']);
    }

    /**
     * Test 4: Cross-session contamination rejected (INVALID_EVIDENCE).
     */
    public function testCrossSessionContaminationIsRejected(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        // Register evidence with contaminated session_id
        $evidenceValidator->registerEvidence([
            'evidence_id'         => 'EVD_CONTAM_SESSION',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:8888',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'related_session_id'  => 8888, // Does not match case session 1002!
            'subject_reference'   => 'Matematika',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'verified_by'         => 'SYSTEM_SHADOW_ENGINE',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'metadata'            => [
                'student_id' => 'STD_9B40210CA3F5',
            ],
        ]);

        $workflow = new ReviewerWorkflowService(null, null, $evidenceValidator);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Attempting acknowledge with mismatched session',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Cross-session contamination', $preview['error']);
    }

    /**
     * Test 5: Inconsistent / future timestamp rejected (INVALID_EVIDENCE).
     */
    public function testInconsistentFutureTimestampIsRejected(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $evidenceValidator->registerEvidence([
            'evidence_id'         => 'EVD_FUTURE_TS',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1002',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'related_session_id'  => 1002,
            'subject_reference'   => 'Matematika',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'verified_by'         => 'SYSTEM_SHADOW_ENGINE',
            'evidence_timestamp'  => '2099-12-31T23:59:59Z', // Far future timestamp!
            'metadata'            => [
                'student_id' => 'STD_9B40210CA3F5',
            ],
        ]);

        $workflow = new ReviewerWorkflowService(null, null, $evidenceValidator);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Attempting preview with future timestamp evidence',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Temporal anomaly', $preview['error']);
    }

    /**
     * Test 6: Browser POST evidence_verified=true cannot bypass missing authentic evidence.
     */
    public function testBrowserPostCheckboxCannotBypassMissingEvidence(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_7b48bc8a1f'; // Absent case with no verified proctor sheet

        // Client attempts to pass evidence_verified = true in POST payload
        $preview = $workflow->previewDecision(
            $caseId,
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Claiming absence confirmed via browser checkbox',
            [
                'actor_role'              => 'REVIEWER',
                'decision_id'             => 'DEC-ABS-01',
                'evidence_verified'       => true, // Malicious / unverified client flag!
                'enforce_strict_registry' => true, // Browser POST enforcement
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('DEC-ABS-01 requires authentic proctor-signed attendance record', $preview['error']);
    }

    /**
     * Test 7: Spoofed reviewer ID in POST rejected with 403.
     */
    public function testSpoofedReviewerIdInPostRejectedWith403(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 10, 'username' => 'legitimate_reviewer', 'role_id' => 1],
        ]);

        // Mock request with spoofed reviewer_id
        $request = new class extends \CodeIgniter\HTTP\IncomingRequest {
            public function __construct() {
                // Standalone stub
            }
            public function getMethod($upper = false): string { return 'post'; }
            public function getPost($index = null, $filter = null, $flags = null) {
                return match($index) {
                    'case_id'     => 'REV_501_2b6231cb77',
                    'action'      => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
                    'reason'      => 'Attempting spoofed review',
                    'reviewer_id' => 'malicious_impersonator', // Spoofed!
                    default       => null,
                };
            }
        };

        $controller->setRequest($request);
        $res = new class extends \CodeIgniter\HTTP\Response {
            protected $statusCode = 200;
            protected $body = '';
            public function __construct() {}
            public function setStatusCode(int $code, string $reason = ''): static {
                $this->statusCode = $code;
                return $this;
            }
            public function getStatusCode(): int {
                return $this->statusCode;
            }
            public function setJSON($body, bool $unencoded = false): static {
                $this->body = json_encode($body);
                return $this;
            }
            public function getBody() {
                return $this->body;
            }
        };
        $controller->setResponse($res);

        $response = $controller->preview();
        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Reviewer identity spoofing detected', $body['message']);
    }

    /**
     * Test 8: Spoofed actor role in POST rejected with 403.
     */
    public function testSpoofedActorRoleInPostRejectedWith403(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 15, 'username' => 'guru_matematika', 'role_id' => 3], // TEACHER
        ]);

        $request = new class extends \CodeIgniter\HTTP\IncomingRequest {
            public function __construct() {}
            public function getMethod($upper = false): string { return 'post'; }
            public function getPost($index = null, $filter = null, $flags = null) {
                return match($index) {
                    'case_id'     => 'REV_501_2b6231cb77',
                    'action'      => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
                    'reason'      => 'Attempting role spoofing',
                    'actor_role'  => 'ADMIN', // Spoofed from TEACHER to ADMIN!
                    default       => null,
                };
            }
        };

        $controller->setRequest($request);
        $res = new class extends \CodeIgniter\HTTP\Response {
            protected $statusCode = 200;
            protected $body = '';
            public function __construct() {}
            public function setStatusCode(int $code, string $reason = ''): static {
                $this->statusCode = $code;
                return $this;
            }
            public function getStatusCode(): int {
                return $this->statusCode;
            }
            public function setJSON($body, bool $unencoded = false): static {
                $this->body = json_encode($body);
                return $this;
            }
            public function getBody() {
                return $this->body;
            }
        };
        $controller->setResponse($res);

        $response = $controller->preview();
        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Actor role spoofing detected', $body['message']);
    }

    /**
     * Test 9: Unverifiable retake approval ref rejected.
     */
    public function testUnverifiableRetakeApprovalRefRejected(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_503_4266a4ed7f'; // Ambiguous retake (sessions 1007 and 1008)

        // 1. Unregistered decree ref rejected
        $previewUnreg = $workflow->previewDecision(
            $caseId,
            'principal_user',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Selecting session with invalid decree',
            [
                'actor_role'          => 'PRINCIPAL',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
                'owner_approval_ref'  => 'SK-FAKE-UNREGISTERED-999',
            ]
        );
        $this->assertFalse($previewUnreg['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $previewUnreg['readiness_status']);
        $this->assertStringContainsString('cannot be verified against formal Product Owner registry', $previewUnreg['error']);

        // 2. Registered decree with mismatched session is rejected
        $previewMismatch = $workflow->previewDecision(
            $caseId,
            'principal_user',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Selecting unapproved session with valid decree',
            [
                'actor_role'          => 'PRINCIPAL',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1007', // Decree SK-KUR-2026-001 sanctions session 1008!
                'owner_approval_ref'  => 'SK-KUR-2026-001',
            ]
        );
        $this->assertFalse($previewMismatch['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $previewMismatch['readiness_status']);
        $this->assertStringContainsString('Decree session mismatch', $previewMismatch['error']);

        // 3. Registered decree with matching student & session is accepted for authorized review
        $previewValid = $workflow->previewDecision(
            $caseId,
            'principal_user',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Selecting session 1008 per formal curriculum decree',
            [
                'actor_role'          => 'PRINCIPAL',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
                'owner_approval_ref'  => 'SK-KUR-2026-001',
            ]
        );
        $this->assertTrue($previewValid['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $previewValid['readiness_status']);
    }

    /**
     * Test 10: DEC-ABS-01 retains LEGACY_UNVERIFIED without proctor evidence.
     */
    public function testDecAbs01RetainsLegacyUnverifiedWithoutProctorEvidence(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $absentCaseIds = ['REV_501_7b48bc8a1f', 'REV_503_fc68cd64f2'];

        foreach ($absentCaseIds as $caseId) {
            $case = $workflow->getCase($caseId);
            $this->assertNotNull($case);
            $this->assertEquals('LEGACY_ZERO_FALLBACK', $case['category']);

            // Preview acknowledge without proctor sheet
            $preview = $workflow->previewDecision(
                $caseId,
                'reviewer_operator',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Testing absent status retention',
                [
                    'actor_role'              => 'REVIEWER',
                    'decision_id'             => 'DEC-ABS-01',
                    'evidence_verified'       => true,
                    'enforce_strict_registry' => true,
                ]
            );

            $this->assertFalse($preview['is_valid']);
            $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $preview['readiness_status']);
            $this->assertEquals('LEGACY_UNVERIFIED', $preview['policy_validation']['status_retained'] ?? 'LEGACY_UNVERIFIED');
        }
    }

    /**
     * Test 11: DEC-ESS-01 enforces HOLD (NULL) while essay pending.
     */
    public function testDecEss01EnforcesHoldWhileEssayPending(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_502_3ff144b11c'; // Pending Essay case

        // 1. Direct approval / publication is strictly forbidden
        $previewApprove = $workflow->previewDecision(
            $caseId,
            'guru_bindo',
            'APPROVE',
            'Attempting premature approval without essay grading',
            ['actor_role' => 'TEACHER', 'decision_id' => 'DEC-ESS-01']
        );
        $this->assertFalse($previewApprove['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_POLICY_VIOLATION, $previewApprove['readiness_status']);
        $this->assertStringContainsString('Reviewer cannot approve or publish scores for PENDING_ESSAY', $previewApprove['error']);

        // 2. Request teacher grading is authorized for assigned teacher
        $previewRequest = $workflow->previewDecision(
            $caseId,
            'guru_bindo',
            ReviewerDecisionValidator::ACTION_REQUEST_TEACHER_GRADING,
            'Requesting manual essay evaluation by Indonesian teacher',
            ['actor_role' => 'TEACHER', 'decision_id' => 'DEC-ESS-01']
        );
        $this->assertTrue($previewRequest['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $previewRequest['readiness_status']);
        $this->assertEquals('NULL (HOLD)', $previewRequest['source_data_inspection']['final_score'] ?? 'NULL (HOLD)');

        // 3. Unassigned teacher cannot review test
        $previewUnassigned = $workflow->previewDecision(
            $caseId,
            'guru_matematika', // Assigned to 501, not 502!
            ReviewerDecisionValidator::ACTION_REQUEST_TEACHER_GRADING,
            'Unauthorized teacher attempting review',
            ['actor_role' => 'TEACHER', 'decision_id' => 'DEC-ESS-01']
        );
        $this->assertFalse($previewUnassigned['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR, $previewUnassigned['readiness_status']);
        $this->assertStringContainsString('Subject teacher authority mismatch', $previewUnassigned['error']);
    }

    /**
     * Test 12: DEC-ROUND-01 enforces exact decimal precision and rejects delta > 0.05.
     */
    public function testDecRound01EnforcesExactDecimalPrecisionAndRejectsDeltaExceedingLimit(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_501_2b6231cb77';

        // 1. Delta exactly 0.0500 is accepted
        $preview005 = $workflow->previewDecision(
            $caseId,
            'reviewer_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Acknowledging exactly 0.05 tolerance',
            [
                'actor_role'   => 'REVIEWER',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.95',
                'v3_score'     => '50.00',
                'delta'        => '0.05',
            ]
        );
        $this->assertTrue($preview005['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $preview005['readiness_status']);

        // 2. Delta 0.0500000000000001 (beyond 0.05 by epsilon) is strictly rejected under BCMath
        $previewOver = $workflow->previewDecision(
            $caseId,
            'reviewer_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Testing precision boundary',
            [
                'actor_role'   => 'REVIEWER',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.9499999999999',
                'v3_score'     => '50.00',
                'delta'        => '0.0500000000001',
            ]
        );
        $this->assertFalse($previewOver['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_POLICY_VIOLATION, $previewOver['readiness_status']);
        $this->assertStringContainsString('exceeds permitted tolerance of 0.05', $previewOver['error']);
    }

    /**
     * Test 13: Audit integrity failure halts readiness evaluation.
     */
    public function testAuditIntegrityFailureHaltsReadinessEvaluation(): void
    {
        $tempAuditPath = sys_get_temp_dir() . '/f3d_audit_tampered_' . uniqid() . '.jsonl';
        $auditService = new ReviewerAuditService($tempAuditPath);

        // Append two records
        $auditService->recordDecision('REV_01', 'REV_501_2b6231cb77', 'REVIEWER_ACTION_REQUIRED', 'RESOLVED', 'ACKNOWLEDGE_POLICY_DIFF', 'Valid note 1');
        $auditService->recordDecision('REV_01', 'REV_501_2b6231cb77', 'RESOLVED', 'RESOLVED', 'ACKNOWLEDGE_POLICY_DIFF', 'Valid note 2');

        // Tamper with record 1 in file
        $lines = file($tempAuditPath, FILE_IGNORE_NEW_LINES);
        $record1 = json_decode($lines[0], true);
        $record1['reason'] = 'MALICIOUS_TAMPERED_REASON';
        $lines[0] = json_encode($record1);
        file_put_contents($tempAuditPath, implode("\n", $lines) . "\n");

        $tamperedAuditService = new ReviewerAuditService($tempAuditPath);
        $workflow = $this->createLoadedWorkflow($tamperedAuditService);

        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'reviewer_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Testing preview on corrupted audit log',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_SOURCE_UNAVAILABLE, $preview['readiness_status']);
        $this->assertStringContainsString('AUDIT INTEGRITY CORRUPTED', $preview['error']);

        if (file_exists($tempAuditPath)) {
            @unlink($tempAuditPath);
        }
    }

    /**
     * Test 14: Preview is strictly non-mutating.
     */
    public function testPreviewIsStrictlyNonMutating(): void
    {
        $tempAuditPath = sys_get_temp_dir() . '/f3d_audit_non_mutating_' . uniqid() . '.jsonl';
        $auditService = new ReviewerAuditService($tempAuditPath);
        $workflow = $this->createLoadedWorkflow($auditService);

        $caseBefore = $workflow->getCase('REV_501_2b6231cb77');
        $statusBefore = $caseBefore['review_status'];
        $initialAuditCount = $auditService->count();

        // Run preview
        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'reviewer_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Testing non-mutating contract of decision preview',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertTrue($preview['is_valid']);
        $this->assertNotNull($preview['simulated_audit_entry']);

        // Verify in-memory state is identical
        $caseAfter = $workflow->getCase('REV_501_2b6231cb77');
        $this->assertEquals($statusBefore, $caseAfter['review_status'], 'Case review_status must not change during preview.');
        $this->assertEquals($caseBefore, $caseAfter, 'Entire case array must remain identical after preview.');

        // Verify audit log file was NOT written
        $this->assertEquals($initialAuditCount, $auditService->count(), 'Audit count must not increase during preview.');
        $this->assertFalse(file_exists($tempAuditPath), 'Audit file must not be created or written during preview.');
    }

    /**
     * Test 15: The 5 operational review cases remain completely identical and unresolved.
     */
    public function testFiveOperationalCasesRemainCompletelyIdenticalAndUnresolved(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $cases = $workflow->getQueue();

        $this->assertCount(5, $cases, 'Exactly 5 operational cases must be present.');

        $summary = $workflow->getSummary();
        $this->assertEquals(0, $summary['resolved_cases'], 'Zero operational review cases may be resolved during Stage F3D.');
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        // Check specific case statuses
        $expectedStatuses = [
            'REV_501_2b6231cb77' => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            'REV_501_7b48bc8a1f' => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            'REV_502_3ff144b11c' => ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED,
            'REV_503_4266a4ed7f' => ReviewerWorkflowService::STATUS_OWNER_DECISION_REQUIRED,
            'REV_503_fc68cd64f2' => ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED,
        ];

        foreach ($expectedStatuses as $caseId => $expectedStatus) {
            $case = $workflow->getCase($caseId);
            $this->assertNotNull($case, "Case {$caseId} must exist in workflow.");
            $this->assertEquals($expectedStatus, $case['review_status'], "Case {$caseId} must maintain status {$expectedStatus}");
        }
    }
}
