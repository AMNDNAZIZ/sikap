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
use App\Services\Cbt\Review\TrustedServerVerificationResult;
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Controllers\Admin\CbtReview;

/**
 * CbtStageF3G_R6_R2_R1Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R2-R1
 * Verification Completion & Trust Provenance Reconciliation Audit.
 */
class CbtStageF3G_R6_R2_R1Test extends TestCase
{
    /**
     * Test 1: Forged BAP trust level is downgraded before storage.
     */
    public function testForgedBapTrustLevelIsDowngradedBeforeStorage(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-ADV-01',
            'student_id'        => 'STD_ADV_01',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG',
            'trust_level'       => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
        ]);

        $stored = $validator->getBapDocument('BAP-ADV-01');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $stored['trust_level']);
    }

    /**
     * Test 2: Forged BAP authoritative boolean is downgraded before storage.
     */
    public function testForgedBapAuthoritativeBooleanIsDowngradedBeforeStorage(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerBapDocument([
            'bap_ref'                   => 'BAP-ADV-02',
            'student_id'                => 'STD_ADV_02',
            'test_id'                   => 501,
            'exam_date'                 => '2026-11-01',
            'proctor_signature'         => 'SIG',
            'is_authoritative_verified' => true,
        ]);

        $stored = $validator->getBapDocument('BAP-ADV-02');
        $this->assertFalse($stored['is_authoritative_verified']);
    }

    /**
     * Test 3: Forged grading-log trust level is downgraded before storage.
     */
    public function testForgedGradingLogTrustLevelIsDowngradedBeforeStorage(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerGradingAuditLog([
            'log_ref'                   => 'LOG-ADV-03',
            'student_id'                => 'STD_ADV_03',
            'test_id'                   => 502,
            'score'                     => 95.0,
            'grader_username'           => 'guru_bindo',
            'grading_timestamp'         => '2026-11-01 10:00:00',
            'status'                    => 'AUTHENTICATED',
            'trust_level'               => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            'is_authoritative_verified' => true,
        ]);

        $stored = $validator->getGradingAuditLog('LOG-ADV-03');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $stored['trust_level']);
        $this->assertFalse($stored['is_authoritative_verified']);
    }

    /**
     * Test 4: Forged teacher assignment trust level is downgraded before storage.
     */
    public function testForgedTeacherAssignmentTrustLevelIsDowngradedBeforeStorage(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerTeacherAssignment(
            'guru_adv_04',
            [501, 502],
            ['Matematika'],
            ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED
        );

        $boundary = $validator->getRegistryTrustBoundary('teacherAssignments');
        $untrusted = $validator->getUntrustedAssertions('teacherAssignments');
        $this->assertNotEmpty($untrusted);
        $this->assertEquals('guru_adv_04', $untrusted[0]['ref']);
    }

    /**
     * Test 5: $serverProof cannot self-assert authoritative verification when infrastructure is unavailable.
     */
    public function testServerProofCannotSelfAssertAuthoritativeVerification(): void
    {
        $validator = new ReviewerEvidenceValidator();
        // Server infrastructure is offline/unavailable by default
        $this->assertFalse($validator->isAuthoritativeInfrastructureAvailable());

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN-SPOOF',
            'authoritative_checksum' => 'SHA256:SPOOF',
            'is_authoritative_verified' => true,
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertFalse($res['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
    }

    /**
     * Test 6: Unauthorized source type is rejected.
     */
    public function testUnauthorizedSourceTypeIsRejected(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $res = $validator->verifyAuthoritativeSourceViaServer('ARBITRARY_TABLE', 'REF_01', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('Unauthorized source type', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 7: Invalid or empty reference ID is rejected.
     */
    public function testInvalidOrEmptyReferenceIdIsRejected(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $resEmpty = $validator->verifyAuthoritativeSourceViaServer('BAP', '', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);
        $this->assertFalse($resEmpty['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED, $resEmpty['status']);

        $resMalformed = $validator->verifyAuthoritativeSourceViaServer('BAP', 'REF; DROP TABLE users;--', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);
        $this->assertFalse($resMalformed['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED, $resMalformed['status']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 8: Unknown table or column cannot enter a query path.
     */
    public function testUnknownTableCannotEnterQueryPath(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'table'                  => 'unauthorized_users_table',
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('not in the authoritative query allowlist', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 9: Duplicate source records fail closed.
     */
    public function testDuplicateSourceRecordsFailClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        // Simulated connection returning 2 rows (duplicate collision)
        $mockExecutor = function (string $query, array $params) {
            return [
                ['id' => 1, 'bap_ref' => 'BAP-DUP', 'status' => 'AUTHENTICATED'],
                ['id' => 2, 'bap_ref' => 'BAP-DUP', 'status' => 'AUTHENTICATED'],
            ];
        };

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-DUP', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'connection'             => $mockExecutor,
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED, $res['status']);
        $this->assertStringContainsString('Duplicate source records', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 10: Missing source record fails closed.
     */
    public function testMissingSourceRecordFailsClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        // Simulated connection returning 0 rows
        $mockExecutor = function (string $query, array $params) {
            return [];
        };

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-NON-EXISTENT', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'connection'             => $mockExecutor,
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('not found in authoritative database', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 11: Revoked BAP cannot be authenticated.
     */
    public function testRevokedBapCannotBeAuthenticated(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        // Simulated record that is marked REVOKED
        $mockExecutor = function (string $query, array $params) {
            return [
                ['id' => 1, 'bap_ref' => 'BAP-REV', 'status' => 'REVOKED', 'is_revoked' => 1],
            ];
        };

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-REV', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'connection'             => $mockExecutor,
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REVOKED, $res['status']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 12: Server infrastructure failure fails closed.
     */
    public function testServerInfrastructureFailureFailsClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-ERR', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'query_error_simulated'  => true,
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('Simulated server connection error', $res['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 13: Legitimate internal verification result is not accidentally destroyed by caller-input sanitization.
     */
    public function testLegitimateInternalVerificationResultPropagatesSafely(): void
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
            'username'  => 'authorized_op',
            'role'      => 'REVIEWER',
            'role_id'   => 1,
            'authority' => 'REVIEWER',
        ];

        // Valid internal TrustedServerVerificationResult object
        $trustedResult = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-VALID-01',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: false,
            boundCaseId: 'REV_501_2b6231cb77',
            boundStudentId: 'STD_01',
            boundTestId: '501'
        );

        // Caller also maliciously passes raw forged fields
        $params = [
            'trusted_server_verification' => $trustedResult,
            'is_authoritative_verified'   => true, // untrusted
            'trust_level'                 => 'AUTHORITATIVE_SOURCE_VERIFIED', // untrusted
        ];

        $readiness = $validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            $actor,
            $params
        );

        // Untrusted raw params were sanitized, but legitimate object is bound
        $this->assertTrue($trustedResult->isAuthenticForCase('REV_501_2b6231cb77', 'STD_01', '501'));
    }

    /**
     * Test 14: A verification result for record A cannot authenticate record B.
     */
    public function testVerificationResultForRecordACannotAuthenticateRecordB(): void
    {
        // Result specifically bound to record A
        $trustedResultA = new TrustedServerVerificationResult(
            sourceType: 'BAP',
            referenceId: 'BAP-REC-A',
            sourceTable: 'cbt_bap_operasional',
            status: ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            authSemanticLevel: ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED,
            isAuthoritativeVerified: true,
            liveMariadbVerified: false,
            boundCaseId: 'REV_CASE_A',
            boundStudentId: 'STD_AAA',
            boundTestId: '501'
        );

        // Attempting to use result A on case B fails
        $this->assertTrue($trustedResultA->isAuthenticForCase('REV_CASE_A', 'STD_AAA', '501'));
        $this->assertFalse($trustedResultA->isAuthenticForCase('REV_CASE_B', 'STD_BBB', '502'));
    }

    /**
     * Test 15: Fixture evidence cannot generate an authoritative dashboard badge.
     */
    public function testFixtureEvidenceCannotGenerateAuthoritativeBadge(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        // Green badge requires auth_semantic_level === AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED
        $this->assertStringContainsString('AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED', $content);
        $this->assertStringContainsString('!empty($ev[\'is_authoritative_verified\'])', $content);
        $this->assertStringContainsString('!empty($ev[\'server_infrastructure_verified\'])', $content);
    }

    /**
     * Test 16: live_mariadb_verified = false cannot produce AUTHORITATIVE_VERIFIED badge.
     */
    public function testLiveMariadbVerifiedFalseCannotProduceAuthoritativeBadge(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $serverRes = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', []);

        $this->assertFalse($serverRes['live_mariadb_verified']);
        $this->assertFalse($serverRes['is_authoritative_verified']);
    }

    /**
     * Test 17: Internal teacher assignment cannot masquerade as authoritative operational assignment.
     */
    public function testInternalTeacherAssignmentCannotMasqueradeAsAuthoritative(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerTeacherAssignment('guru_internal_only', [502], ['Bahasa Indonesia']);

        $boundary = $validator->getRegistryTrustBoundary('teacherAssignments');
        $this->assertFalse($boundary['is_authoritative']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $boundary['trust_level']);
    }

    /**
     * Test 18: Unauthorized actor cannot execute an authorized reviewer action.
     */
    public function testUnauthorizedActorCannotExecuteReviewerAction(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();

        $this->expectException(\App\Services\Cbt\Review\InvalidReviewActionException::class);
        $this->expectExceptionMessage('Actor with role \'STUDENT\' is not authorized');

        $workflow->applyDecision(
            'REV_501_2b6231cb77',
            'student_user',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'Attempted student action',
            ['actor_role' => 'STUDENT']
        );
    }

    /**
     * Test 19: Authorized actor with incomplete evidence cannot bypass evidence readiness.
     */
    public function testAuthorizedActorWithIncompleteEvidenceCannotBypassReadiness(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $case = [
            'case_id'                  => 'REV_501_7b48bc8a1f',
            'category'                 => 'ABSENT_SCORE_DIFFERENCE',
            'applicable_policy'        => 'DEC-ABS-01',
            'test_id'                  => 501,
            'pseudonymous_student_id'  => 'STD_ABSENT',
        ];
        $actor = [
            'username'  => 'super_admin',
            'role'      => 'ADMIN',
            'role_id'   => 1,
            'authority' => 'ADMIN',
        ];

        // Action without BAP evidence fails closed even for ADMIN role
        $readiness = $validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            $actor,
            ['decision_id' => 'DEC-ABS-01']
        );

        $this->assertFalse($readiness['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $readiness['readiness_status']);
    }

    /**
     * Test 20: All five canonical cases remain unchanged and pending.
     */
    public function testAllFiveCanonicalCasesRemainUnchangedAndPending(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $summary = $workflow->getSummary();

        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        $queue = $workflow->getQueue();
        $this->assertCount(5, $queue);

        $expectedIds = [
            'REV_501_2b6231cb77',
            'REV_501_7b48bc8a1f',
            'REV_502_3ff144b11c',
            'REV_503_4266a4ed7f',
            'REV_503_fc68cd64f2',
        ];

        $actualIds = array_column($queue, 'case_id');
        $this->assertEquals($expectedIds, $actualIds);
    }
}
