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
 * CbtStageF3G_R6_R2_R2Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R2-R2
 * Authority Authenticity, Authorization & Governance Reconciliation Audit.
 */
class CbtStageF3G_R6_R2_R2Test extends TestCase
{
    /**
     * Test 1: Arbitrary caller cannot construct an accepted authoritative verification result.
     */
    public function testArbitraryCallerCannotConstructAcceptedAuthoritativeResult(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $case = [
            'case_id'                  => 'REV_501_2b6231cb77',
            'category'                 => 'ROUNDING_DIFFERENCE',
            'applicable_policy'        => 'DEC-ROUND-01',
            'test_id'                  => 501,
            'pseudonymous_student_id'  => 'STD_01',
            'legacy_score'             => 79.98,
            'v3_score'                 => 80.00,
        ];
        $actor = [
            'username'  => 'reviewer_user',
            'role'      => 'REVIEWER',
            'role_id'   => 1,
            'authority' => 'REVIEWER',
        ];

        // An arbitrary caller constructs an object directly with no HMAC issuer signature
        $forgedResult = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-FORGED-01',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: true,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_01',
            boundTestId: '501'
        );

        $this->assertFalse($validator->verifyIssuerAuthenticity($forgedResult));

        $params = [
            'trusted_server_verification' => $forgedResult,
        ];

        $readiness = $validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            $actor,
            $params
        );

        // Forged object must be rejected and logged as untrusted assertion
        $untrustedAssertions = $validator->getUntrustedAssertions('readinessContext');
        $this->assertNotEmpty($untrustedAssertions);
        $this->assertStringContainsString('Unauthenticated issuer', $untrustedAssertions[count($untrustedAssertions) - 1]['claimed']);
    }

    /**
     * Test 2: Missing boundCaseId fails closed.
     */
    public function testMissingBoundCaseIdFailsClosed(): void
    {
        $result = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: true,
            boundCaseId: null, // Missing case ID
            boundStudentId: 'STD_01',
            boundTestId: '501'
        );

        $this->assertFalse($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_01', '501'));
    }

    /**
     * Test 3: Missing boundStudentId fails closed.
     */
    public function testMissingBoundStudentIdFailsClosed(): void
    {
        $result = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: true,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: null, // Missing student ID
            boundTestId: '501'
        );

        $this->assertFalse($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_01', '501'));
    }

    /**
     * Test 4: Missing boundTestId fails closed.
     */
    public function testMissingBoundTestIdFailsClosed(): void
    {
        $result = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-001',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: true,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_01',
            boundTestId: null // Missing test ID
        );

        $this->assertFalse($result->isAuthenticForCase('REV_501_2b6231cb77', 'STD_01', '501'));
    }

    /**
     * Test 5: Record A result cannot authenticate Record B.
     */
    public function testRecordAResultCannotAuthenticateRecordB(): void
    {
        $resultA = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-CASE-A',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: true,
            boundCaseId: 'REV_CASE_A',
            boundStudentId: 'STD_A',
            boundTestId: '501'
        );

        $this->assertTrue($resultA->isAuthenticForCase('REV_CASE_A', 'STD_A', '501'));
        $this->assertFalse($resultA->isAuthenticForCase('REV_CASE_B', 'STD_B', '502'));
    }

    /**
     * Test 6: Mock result cannot claim operational authoritative verification.
     */
    public function testMockResultCannotClaimOperationalAuthoritativeVerification(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $mockExecutor = function (string $query, array $params) {
            return [
                ['id' => 1, 'bap_ref' => 'BAP-001', 'status' => 'AUTHENTICATED', 'is_revoked' => 0],
            ];
        };

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'connection'             => $mockExecutor,
        ]);

        $this->assertTrue($res['is_verified']);
        // Mock execution yields VERIFICATION_LOGIC_PASSED, not AUTHORITATIVE_SOURCE_VERIFIED
        $this->assertEquals(ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED, $res['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED, $res['auth_semantic_level']);
        $this->assertFalse($res['is_authoritative_verified']);
        $this->assertFalse($res['live_mariadb_verified']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 7: live_mariadb_verified = false cannot produce an authoritative badge.
     */
    public function testLiveMariadbVerifiedFalseCannotProduceAuthoritativeBadge(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        $this->assertStringContainsString('AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED', $content);
        $this->assertStringContainsString('!empty($ev[\'is_authoritative_verified\']) && !empty($ev[\'server_infrastructure_verified\'])', $content);
        $this->assertStringContainsString('VERIFICATION_LOGIC_PASSED', $content);
    }

    /**
     * Test 8: Fixture result cannot produce an authoritative API status.
     */
    public function testFixtureResultCannotProduceAuthoritativeApiStatus(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $res = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, false);

        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $res['auth_semantic_level']);
        $this->assertFalse($res['is_authoritative_verified']);
    }

    /**
     * Test 9: Request-supplied actor_role cannot elevate permissions.
     */
    public function testRequestSuppliedActorRoleCannotElevatePermissions(): void
    {
        $controller = new CbtReview();
        // Session user is a regular teacher (role_id = 3)
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => [
                'id'       => 10,
                'username' => 'guru_regular',
                'role_id'  => 3,
            ],
        ]);

        // Request maliciously asserts actor_role = ADMIN
        $request = new class extends \CodeIgniter\HTTP\IncomingRequest {
            public function __construct() {}
            public function getMethod($upper = false): string { return 'POST'; }
            public function isAJAX(): bool { return true; }
            public function getPost($index = null, $filter = null, $flags = null) {
                return match($index) {
                    'case_id'    => 'REV_501_2b6231cb77',
                    'action'     => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
                    'reason'     => 'Attempting privilege escalation',
                    'actor_role' => 'ADMIN', // Spoofed role
                    default      => null,
                };
            }
        };

        $controller->setRequest($request);
        $response = $controller->preview();

        $this->assertEquals(403, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);
        $this->assertFalse($body['success']);
        $this->assertStringContainsString('Actor role spoofing detected', $body['message']);
    }

    /**
     * Test 10: Preview authorization cannot bypass apply authorization.
     */
    public function testPreviewAuthorizationCannotBypassApplyAuthorization(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();

        // An unassigned teacher attempts to execute applyDecision on Test 501
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('Teacher \'guru_unassigned\' is not authorized to review Test ID 501');

        $workflow->applyDecision(
            'REV_501_2b6231cb77',
            'guru_unassigned',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Unauthorized teacher attempting to apply decision',
            ['actor_role' => 'TEACHER']
        );
    }

    /**
     * Test 11: Teacher internal assignment cannot establish authoritative assignment.
     */
    public function testTeacherInternalAssignmentCannotEstablishAuthoritativeAssignment(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerTeacherAssignment('guru_ipa_local', [503], ['Ilmu Pengetahuan Alam']);

        $boundary = $validator->getRegistryTrustBoundary('teacherAssignments');
        $this->assertFalse($boundary['is_authoritative']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $boundary['trust_level']);
    }

    /**
     * Test 12: Query table/column identifiers cannot be caller-controlled.
     */
    public function testQueryTableColumnIdentifiersCannotBeCallerControlled(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'table'                  => 'users_unauthorized',
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('not in the authoritative query allowlist', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 13: Reference-column mismatch fails closed.
     */
    public function testReferenceColumnMismatchFailsClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'lookup_column'          => 'bap_number', // Mismatch against sanctioned bap_ref
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED, $res['status']);
        $this->assertStringContainsString('Reference-column mismatch', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 14: Excessive diagnostic payload is rejected or safely bounded.
     */
    public function testExcessiveDiagnosticPayloadIsRejectedOrSafelyBounded(): void
    {
        $validator = new ReviewerEvidenceValidator();
        // Register an untrusted assertion with an excessively long string
        $longString = str_repeat('A', 5000);
        $validator->registerTeacherAssignment($longString, [501], ['Math'], ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED);

        $untrusted = $validator->getUntrustedAssertions('teacherAssignments');
        $this->assertNotEmpty($untrusted);
        $lastEntry = $untrusted[count($untrusted) - 1];

        // Ref should be bounded to 100 chars, claimed trust bounded to 250 chars
        $this->assertLessThanOrEqual(100, strlen($lastEntry['ref']));
        $this->assertLessThanOrEqual(250, strlen($lastEntry['claimed']));
    }

    /**
     * Test 15: All five canonical cases retain their identities and pending status.
     */
    public function testAllFiveCanonicalCasesRetainIdentitiesAndPendingStatus(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $queue = $workflow->getQueue();

        $expectedCaseIds = [
            'REV_501_2b6231cb77',
            'REV_501_7b48bc8a1f',
            'REV_502_3ff144b11c',
            'REV_503_4266a4ed7f',
            'REV_503_fc68cd64f2',
        ];

        $this->assertCount(5, $queue);
        $actualCaseIds = array_column($queue, 'case_id');
        sort($actualCaseIds);
        sort($expectedCaseIds);
        $this->assertEquals($expectedCaseIds, $actualCaseIds);

        $summary = $workflow->getSummary();
        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);
    }

    /**
     * Test 16: Ratified decisions are not incorrectly classified as awaiting policy approval.
     */
    public function testRatifiedDecisionsNotClassifiedAsAwaitingPolicyApproval(): void
    {
        $workflow = new ReviewerWorkflowService();
        $ratifiedPolicies = ['DEC-RET-01', 'DEC-ESS-01', 'DEC-ROUND-01', 'DEC-ABS-01'];

        foreach ($ratifiedPolicies as $policyId) {
            $details = $workflow->getPodrPolicyDetails($policyId);
            $this->assertEquals('RATIFIED', $details['ratification_status']);
            $this->assertEquals('2026-11-01', $details['ratified_date']);
            $this->assertStringContainsString('Product Owner Decision Record (PODR)', $details['governing_document']);
        }
    }
}
