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
use App\Services\Cbt\Review\ReviewerEvidenceValidator;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\TrustedServerVerificationResult;
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Controllers\Admin\CbtReview;

/**
 * CbtStageF3G_R6_R2_R3Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R2-R3
 * TRUST CONSUMER ENFORCEMENT, AUTHORIZATION RACE CONDITIONS & CANONICAL EVIDENCE RECONCILIATION AUDIT.
 */
class CbtStageF3G_R6_R2_R3Test extends TestCase
{
    private ReviewerEvidenceValidator $validator;
    private ReviewerDecisionValidator $decisionValidator;
    private ReviewerWorkflowService $workflow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ReviewerEvidenceValidator();
        $this->decisionValidator = new ReviewerDecisionValidator($this->validator);
        $this->workflow = new ReviewerWorkflowService(
            $this->decisionValidator,
            null,
            $this->validator
        );
        $this->workflow->loadFromShadowDeviations();
    }

    // -------------------------------------------------------------------------
    // GROUP 1: TRUST CONSUMER ENFORCEMENT & SEMANTIC DISCRIMINATION (AUDIT A)
    // -------------------------------------------------------------------------

    public function testAuthenticForCaseDoesNotGrantAuthoritativeTrust(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-001',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        // Verification logic & binding are valid
        $this->assertTrue($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '501'));
        $this->assertTrue($result->isVerificationLogicPassed());

        // BUT authoritative and live flags MUST BE FALSE
        $this->assertFalse($result->isLiveAuthoritativeForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '501'));
        $this->assertFalse($result->isAuthoritativeVerified);
        $this->assertFalse($result->liveMariadbVerified);
    }

    public function testVerificationLogicPassedCannotFulfillAuthoritativeRequirement(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'GRADING_LOG',
            referenceId: 'LOG-TEST-002',
            sourceTable: 'cbt_grading_logs',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_502_3ff144b11c',
            boundStudentId: 'STD_A1C048B42B28',
            boundTestId: '502'
        );

        $this->assertTrue($result->isVerificationLogicPassed());
        $this->assertSame(ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED, $result->authSemanticLevel);
        $this->assertNotSame(ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED, $result->authSemanticLevel);
    }

    public function testFixtureValidationPassedCannotFulfillLiveDatabaseVerification(): void
    {
        $summary = $this->workflow->getSummary();
        $this->assertSame(5, $summary['pending_cases']);
        $this->assertSame(0, $summary['resolved_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        // Authoritative verification infrastructure remains strictly unavailable
        $this->assertFalse($this->validator->isAuthoritativeInfrastructureAvailable());

        $case = $this->workflow->getCase('REV_501_2b6231cb77');
        $readiness = $this->validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            ['role' => 'REVIEWER', 'username' => 'auditor']
        );

        $this->assertSame(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $readiness['readiness_status']);
        $this->assertFalse($this->validator->isAuthoritativeInfrastructureAvailable());
    }

    // -------------------------------------------------------------------------
    // GROUP 2: ISSUER SIGNATURE & CROSS-INSTANCE SECURITY (AUDIT B)
    // -------------------------------------------------------------------------

    public function testTamperedSourceTableInvalidatesIssuerSignature(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-003',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->assertTrue($this->validator->verifyIssuerAuthenticity($result));

        // Tamper with sourceTable
        $tampered = new TrustedServerVerificationResult(
            sourceType: $result->sourceType,
            referenceId: $result->referenceId,
            sourceTable: 'unauthorized_table',
            status: $result->status,
            authSemanticLevel: $result->authSemanticLevel,
            isAuthoritativeVerified: $result->isAuthoritativeVerified,
            liveMariadbVerified: $result->liveMariadbVerified,
            boundCaseId: $result->boundCaseId,
            boundStudentId: $result->boundStudentId,
            boundTestId: $result->boundTestId,
            issuerSignature: $result->issuerSignature
        );

        $this->assertFalse($this->validator->verifyIssuerAuthenticity($tampered));
    }

    public function testTamperedStatusInvalidatesIssuerSignature(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-004',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::BAP_STATUS_REGISTERED_UNVERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        // Tamper status to BAP_STATUS_AUTHENTICATED
        $tampered = new TrustedServerVerificationResult(
            sourceType: $result->sourceType,
            referenceId: $result->referenceId,
            sourceTable: $result->sourceTable,
            status: ReviewerEvidenceValidator::BAP_STATUS_AUTHENTICATED,
            authSemanticLevel: $result->authSemanticLevel,
            isAuthoritativeVerified: $result->isAuthoritativeVerified,
            liveMariadbVerified: $result->liveMariadbVerified,
            boundCaseId: $result->boundCaseId,
            boundStudentId: $result->boundStudentId,
            boundTestId: $result->boundTestId,
            issuerSignature: $result->issuerSignature
        );

        $this->assertFalse($this->validator->verifyIssuerAuthenticity($tampered));
    }

    public function testTamperedAuthoritativeFlagInvalidatesIssuerSignature(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-005',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        // Tamper isAuthoritativeVerified to true
        $tampered = new TrustedServerVerificationResult(
            sourceType: $result->sourceType,
            referenceId: $result->referenceId,
            sourceTable: $result->sourceTable,
            status: $result->status,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: $result->liveMariadbVerified,
            boundCaseId: $result->boundCaseId,
            boundStudentId: $result->boundStudentId,
            boundTestId: $result->boundTestId,
            issuerSignature: $result->issuerSignature
        );

        $this->assertFalse($this->validator->verifyIssuerAuthenticity($tampered));
    }

    public function testTamperedLiveMariadbFlagInvalidatesIssuerSignature(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-TEST-006',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        // Tamper liveMariadbVerified to true
        $tampered = new TrustedServerVerificationResult(
            sourceType: $result->sourceType,
            referenceId: $result->referenceId,
            sourceTable: $result->sourceTable,
            status: $result->status,
            authSemanticLevel: $result->authSemanticLevel,
            isAuthoritativeVerified: $result->isAuthoritativeVerified,
            liveMariadbVerified: true,
            boundCaseId: $result->boundCaseId,
            boundStudentId: $result->boundStudentId,
            boundTestId: $result->boundTestId,
            issuerSignature: $result->issuerSignature
        );

        $this->assertFalse($this->validator->verifyIssuerAuthenticity($tampered));
    }

    public function testCrossInstanceReplayRejected(): void
    {
        $instanceA = new ReviewerEvidenceValidator();
        $instanceB = new ReviewerEvidenceValidator();

        $resultFromA = $instanceA->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-CROSS-001',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        // Instance A validates its own result
        $this->assertTrue($instanceA->verifyIssuerAuthenticity($resultFromA));

        // Instance B MUST reject the result issued by Instance A
        $this->assertFalse($instanceB->verifyIssuerAuthenticity($resultFromA));
    }

    public function testInstanceSecretIsProtectedFromExposure(): void
    {
        $debug = $this->validator->__debugInfo();

        $this->assertArrayHasKey('instance_secret', $debug);
        $this->assertSame('[PROTECTED_HMAC_SECRET]', $debug['instance_secret']);

        // Reflection verification
        $reflection = new \ReflectionClass($this->validator);
        $this->assertFalse($reflection->hasMethod('getInstanceSecret'));
        $this->assertFalse($reflection->hasMethod('getSecret'));
    }

    // -------------------------------------------------------------------------
    // GROUP 3: THREE-DIMENSIONAL BINDING ENFORCEMENT
    // -------------------------------------------------------------------------

    public function testReplayAcrossDifferentCaseRejected(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-BIND-001',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->assertTrue($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '501'));
        $this->assertFalse($result->isAuthenticForCase('REV_503_fc68cd64f2', 'STD_9B40210CA3F5', '501'));
    }

    public function testReplayAcrossDifferentStudentRejected(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-BIND-002',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->assertTrue($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '501'));
        $this->assertFalse($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_DIFFERENT', '501'));
    }

    public function testReplayAcrossDifferentTestRejected(): void
    {
        $result = $this->validator->issueTrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-BIND-003',
            sourceTable: 'cbt_bap',
            status: ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED,
            isAuthoritativeVerified: false,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_9B40210CA3F5',
            boundTestId: '501'
        );

        $this->assertTrue($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '501'));
        $this->assertFalse($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_9B40210CA3F5', '999'));
    }

    // -------------------------------------------------------------------------
    // GROUP 4: CONTROLLER APPLY ENDPOINT & AUTHORIZATION RACES (AUDIT C)
    // -------------------------------------------------------------------------

    private function createMockPostRequest(array $postParams, string $method = 'POST'): object
    {
        return new class($postParams, $method) extends \CodeIgniter\HTTP\IncomingRequest {
            public function __construct(private array $params, private string $httpMethod) {}
            public function getMethod($upper = false): string { return strtoupper($this->httpMethod); }
            public function isAJAX(): bool { return true; }
            public function getPost($index = null, $filter = null, $flags = null) {
                if ($index === null) return $this->params;
                return $this->params[$index] ?? null;
            }
        };
    }

    public function testApplyRequiresPostMethod(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 1, 'username' => 'admin_user', 'role_id' => 1],
        ]);
        $controller->setRequest($this->createMockPostRequest([], 'GET'));

        $response = $controller->apply();
        $this->assertSame(405, $response->getStatusCode());
    }

    public function testApplyBlocksReviewerIdSpoofing(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 1, 'username' => 'legit_admin', 'role_id' => 1],
        ]);
        $controller->setRequest($this->createMockPostRequest([
            'case_id'     => 'REV_501_2b6231cb77',
            'action'      => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'reason'      => 'Valid legacy audit reason',
            'reviewer_id' => 'impostor_reviewer',
        ]));

        $response = $controller->apply();
        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Reviewer identity spoofing detected', $body['message']);
    }

    public function testApplyBlocksRoleSpoofing(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 10, 'username' => 'teacher_user', 'role_id' => 3],
        ]);
        $controller->setRequest($this->createMockPostRequest([
            'case_id'     => 'REV_501_2b6231cb77',
            'action'      => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'reason'      => 'Valid legacy audit reason',
            'reviewer_id' => 'teacher_user',
            'actor_role'  => 'ADMIN', // Claiming ADMIN while session is TEACHER
        ]));

        $response = $controller->apply();
        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Actor role spoofing detected', $body['message']);
    }

    public function testUnassignedTeacherApplyRejectedWithGovernanceBreach(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 15, 'username' => 'teacher_unassigned', 'role_id' => 3],
        ]);
        $controller->setRequest($this->createMockPostRequest([
            'case_id'      => 'REV_501_2b6231cb77',
            'action'       => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'reason'       => 'Valid reason',
            'reviewer_id'  => 'teacher_unassigned',
            'actor_role'   => 'TEACHER',
            'decision_id'  => 'DEC-ROUND-01',
            'legacy_score' => '49.98',
            'v3_score'     => '50.00',
            'delta'        => '0.02',
        ]));

        $response = $controller->apply();
        $this->assertSame(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('GOVERNANCE BREACH: Teacher', $body['message']);
    }

    public function testAuthorizedTeacherApplySucceeds(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 20, 'username' => 'teacher_math', 'role_id' => 3],
        ]);

        // Register teacher assignment in controller's evidence validator
        $controller->getWorkflow()->getEvidenceValidator()->registerTeacherAssignment(
            'teacher_math',
            [501],
            ['MATEMATIKA']
        );

        $controller->setRequest($this->createMockPostRequest([
            'case_id'      => 'REV_501_2b6231cb77',
            'action'       => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'reason'       => 'Minor rounding difference (0.02) within tolerance of 0.05',
            'reviewer_id'  => 'teacher_math',
            'actor_role'   => 'TEACHER',
            'decision_id'  => 'DEC-ROUND-01',
            'legacy_score' => '49.98',
            'v3_score'     => '50.00',
            'delta'        => '0.02',
        ]));

        $response = $controller->apply();
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('RESOLVED', $body['result']['review_status']);
    }

    public function testAdminCanApplyWithoutTeacherAssignment(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => ['id' => 1, 'username' => 'superadmin', 'role_id' => 1],
        ]);

        $controller->setRequest($this->createMockPostRequest([
            'case_id'      => 'REV_501_2b6231cb77',
            'action'       => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'reason'       => 'Minor rounding difference (0.02) within tolerance of 0.05',
            'reviewer_id'  => 'superadmin',
            'actor_role'   => 'ADMIN',
            'decision_id'  => 'DEC-ROUND-01',
            'legacy_score' => '49.98',
            'v3_score'     => '50.00',
            'delta'        => '0.02',
        ]));

        $response = $controller->apply();
        $this->assertSame(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertTrue($body['success']);
        $this->assertSame('RESOLVED', $body['result']['review_status']);
    }

    // -------------------------------------------------------------------------
    // GROUP 5: MEMORY BOUNDING & PAYLOAD SANITIZATION (AUDIT D)
    // -------------------------------------------------------------------------

    public function testSanitizeDiagnosticPayloadLimitsDepth(): void
    {
        $deep = [
            'level1' => [
                'level2' => [
                    'level3' => 'too_deep_value',
                ],
            ],
        ];

        $sanitized = $this->validator->sanitizeDiagnosticPayload($deep, 2);
        $this->assertIsArray($sanitized);
        $this->assertIsArray($sanitized['level1']);
        $this->assertSame('[MAX_DEPTH_REACHED]', $sanitized['level1']['level2']);
    }

    public function testSanitizeDiagnosticPayloadLimitsStringLength(): void
    {
        $longString = str_repeat('A', 500);
        $sanitized = $this->validator->sanitizeDiagnosticPayload($longString);

        $this->assertSame(250, mb_strlen($sanitized));
        $this->assertSame(str_repeat('A', 250), $sanitized);
    }

    public function testSanitizeDiagnosticPayloadFiltersBinaryData(): void
    {
        $binary = "\xFF\xFE\x00\x00InvalidUTF8";
        $sanitized = $this->validator->sanitizeDiagnosticPayload($binary);

        $this->assertSame('[BINARY_DATA_FILTERED]', $sanitized);
    }

    public function testSanitizeDiagnosticPayloadSafelyHandlesCyclicReferences(): void
    {
        $cyclic = ['name' => 'root'];
        $cyclic['self'] = &$cyclic;

        $sanitized = $this->validator->sanitizeDiagnosticPayload($cyclic, 2);

        $this->assertIsArray($sanitized);
        $this->assertSame('root', $sanitized['name']);
        $this->assertIsArray($sanitized['self']);
        $this->assertSame('[MAX_DEPTH_REACHED]', $sanitized['self']['self']);
    }

    public function testSanitizeDiagnosticPayloadLimitsArrayElements(): void
    {
        $wideArray = [];
        for ($i = 0; $i < 30; $i++) {
            $wideArray["key_{$i}"] = "value_{$i}";
        }

        $sanitized = $this->validator->sanitizeDiagnosticPayload($wideArray);
        $this->assertCount(10, $sanitized);
    }

    // -------------------------------------------------------------------------
    // GROUP 6: CANONICAL CASE & PODR RECONCILIATION (AUDIT E)
    // -------------------------------------------------------------------------

    public function testRev503IsAbsenceAndRejectsRetakeAction(): void
    {
        $case = $this->workflow->getCase('REV_503_fc68cd64f2');
        $this->assertNotNull($case);
        $this->assertSame('LEGACY_ZERO_FALLBACK', $case['category']);

        // Attempting retake with DEC-RET-01 fails validation because this case is student absence (DEC-ABS-01)
        $preview = $this->workflow->previewDecision(
            'REV_503_fc68cd64f2',
            'admin',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting to treat absence case as retake',
            ['decision_id' => 'DEC-RET-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertStringContainsString('does not match required policy decision', $preview['error']);
    }

    public function testDecRound01DeltaIsStrictly002AndClientMismatchFails(): void
    {
        // Canonical shadow deviation line 3 has legacy 49.98, v3 50.00, delta 0.02
        $previewCorrect = $this->workflow->previewDecision(
            'REV_501_2b6231cb77',
            'admin',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Minor rounding difference (0.02) within tolerance of 0.05',
            [
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.02',
            ]
        );
        $this->assertTrue($previewCorrect['is_valid']);

        // Mismatched delta fails validation
        $previewMismatched = $this->workflow->previewDecision(
            'REV_501_2b6231cb77',
            'admin',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Mismatched delta',
            [
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.05', // Delta claimed does not match |49.98 - 50.00| = 0.02
            ]
        );
        $this->assertFalse($previewMismatched['is_valid']);
    }

    // -------------------------------------------------------------------------
    // GROUP 7: FINAL GATE INVARIANTS & INTEGRITY (AUDIT G)
    // -------------------------------------------------------------------------

    public function testFinalSystemGateInvariantsHeld(): void
    {
        $freshWf = new ReviewerWorkflowService();
        $freshWf->loadFromShadowDeviations();

        $summary = $freshWf->getSummary();
        $this->assertSame(5, $summary['pending_cases']);
        $this->assertSame(0, $summary['resolved_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        $case = $freshWf->getCase('REV_501_2b6231cb77');
        $readiness = $freshWf->getEvidenceValidator()->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            ['role' => 'REVIEWER', 'username' => 'auditor']
        );
        $this->assertSame(ReviewerEvidenceValidator::READINESS_READY_FOR_AUTHORIZED_REVIEW, $readiness['readiness_status']);
        $this->assertFalse($freshWf->getEvidenceValidator()->isAuthoritativeInfrastructureAvailable());
    }
}
