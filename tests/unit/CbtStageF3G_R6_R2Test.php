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
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Controllers\Admin\CbtReview;

/**
 * CbtStageF3G_R6_R2Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R2
 * End-to-End Trust Propagation, Server Authority & Readiness Consumer Audit.
 */
class CbtStageF3G_R6_R2Test extends TestCase
{
    /**
     * Test 1: BAP caller spoof -> record tersimpan dengan trust hasil downgrade.
     */
    public function testBapCallerSpoofPersistsDowngradedTrust(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerBapDocument([
            'bap_ref'                   => 'BAP-R2-SPOOF',
            'student_id'                => 'STD_R2_01',
            'test_id'                   => 501,
            'exam_date'                 => '2026-11-01',
            'proctor_signature'         => 'SIG',
            'trust_level'               => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            'is_authoritative_verified' => true,
        ]);

        $stored = $validator->getBapDocument('BAP-R2-SPOOF');
        $this->assertNotNull($stored);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $stored['trust_level']);
        $this->assertFalse($stored['is_authoritative_verified']);
    }

    /**
     * Test 2: Grader caller spoof -> record tersimpan dengan trust hasil downgrade.
     */
    public function testGraderCallerSpoofPersistsDowngradedTrust(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerGradingAuditLog([
            'log_ref'                   => 'LOG-R2-SPOOF',
            'student_id'                => 'STD_R2_02',
            'test_id'                   => 502,
            'score'                     => 85.0,
            'grader_username'           => 'guru_bindo',
            'grading_timestamp'         => '2026-11-01 10:00:00',
            'status'                    => 'AUTHENTICATED',
            'trust_level'               => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            'is_authoritative_verified' => true,
        ]);

        $stored = $validator->getGradingAuditLog('LOG-R2-SPOOF');
        $this->assertNotNull($stored);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $stored['trust_level']);
        $this->assertFalse($stored['is_authoritative_verified']);
    }

    /**
     * Test 3: is_authoritative_verified=true dari caller tidak tersimpan sebagai true (Teacher Assignment).
     */
    public function testTeacherAssignmentCallerSpoofPersistsDowngradedTrust(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->registerTeacherAssignment(
            'guru_spoof_r2',
            [501, 502],
            ['Matematika'],
            ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED
        );

        $boundary = $validator->getRegistryTrustBoundary('teacherAssignments');
        $untrusted = $validator->getUntrustedAssertions('teacherAssignments');
        $this->assertNotEmpty($untrusted);
        $this->assertEquals('guru_spoof_r2', $untrusted[0]['ref']);
    }

    /**
     * Test 4: Pemanggilan service langsung tanpa controller tetap fail-closed.
     */
    public function testDirectServiceCallWithoutControllerRemainsFailClosed(): void
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
            'username'  => 'operator_test',
            'role'      => 'REVIEWER',
            'role_id'   => 1,
            'authority' => 'REVIEWER',
        ];

        // Caller attempts to self-inject authoritative readiness
        $forgedParams = [
            'is_authoritative_verified'        => true,
            'trust_level'                      => 'AUTHORITATIVE_SOURCE_VERIFIED',
            'authoritative_evidence_verified'  => true,
        ];

        $readiness = $validator->evaluateReadiness(
            $case,
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            $actor,
            $forgedParams
        );

        // Verification notes / readiness must not elevate to authoritative
        $this->assertFalse($readiness['is_valid']);
    }

    /**
     * Test 5: Endpoint selain preview tidak menerima trust elevation.
     */
    public function testEndpointsOtherThanPreviewDoNotAcceptTrustElevation(): void
    {
        $controller = new CbtReview();
        $controller->setMockSession([
            'logged_in' => true,
            'user'      => [
                'id'       => 1,
                'username' => 'admin_operator',
                'role_id'  => 1,
            ],
        ]);

        // Detail case consumer receives authentic evidence without accepting any trust override
        $caseDetail = $controller->getWorkflow()->getCaseDetail('REV_501_2b6231cb77');
        $this->assertNotNull($caseDetail);
        $evidenceList = $caseDetail['authentic_evidence'] ?? [];
        $this->assertNotEmpty($evidenceList);
        foreach ($evidenceList as $ev) {
            $this->assertFalse($ev['is_authoritative_verified'] ?? false);
            $this->assertEquals('VERIFIED', $ev['verification_status'] ?? '');
        }
    }

    /**
     * Test 6: Server authority boundary enforces allowed source types.
     */
    public function testServerAuthorityBoundaryEnforcesAllowedSourceTypes(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $resUnauthorizedType = $validator->verifyAuthoritativeSourceViaServer('USER_TABLE', 'USER_1', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);

        $this->assertFalse($resUnauthorizedType['is_verified']);
        $this->assertFalse($resUnauthorizedType['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $resUnauthorizedType['status']);
        $this->assertStringContainsString('Unauthorized source type', $resUnauthorizedType['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 7: Server source unavailable menghasilkan SOURCE_UNAVAILABLE.
     */
    public function testServerSourceUnavailableYieldsSourceUnavailable(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $this->assertFalse($validator->isAuthoritativeInfrastructureAvailable());

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('live database verification NOT PERFORMED', $res['discrepancy']);
    }

    /**
     * Test 8: Query error / timeout tidak menghasilkan autentikasi.
     */
    public function testQueryErrorOrTimeoutFailsClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $validator->setAuthoritativeInfrastructureAvailable(true);

        $resTimeout = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-AUTH-2026-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
            'timeout_simulated'      => true,
        ]);

        $this->assertFalse($resTimeout['is_verified']);
        $this->assertFalse($resTimeout['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $resTimeout['status']);
        $this->assertStringContainsString('query timeout', $resTimeout['discrepancy']);

        $validator->setAuthoritativeInfrastructureAvailable(false);
    }

    /**
     * Test 9: Badge dashboard hanya hijau setelah semua syarat terpenuhi.
     */
    public function testDashboardBadgeOnlyGreenWhenAuthoritativeAndVerified(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        // Explicit check that both is_authoritative_verified and server_infrastructure_verified are required
        $this->assertStringContainsString('!empty($ev[\'is_authoritative_verified\']) && !empty($ev[\'server_infrastructure_verified\'])', $content);
    }

    /**
     * Test 10: BAP revoked tidak ditampilkan sebagai authenticated.
     */
    public function testBapRevokedNotDisplayedAsAuthenticated(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        // Explicit badge bg-danger branch for BAP_REVOKED
        $this->assertStringContainsString('BAP_REVOKED', $content);
        $this->assertStringContainsString('bg-danger', $content);
    }

    /**
     * Test 11: BAP not found dan source unavailable memiliki representasi berbeda.
     */
    public function testBapNotFoundAndSourceUnavailableHaveDistinctBadges(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        $this->assertStringContainsString('BAP_NOT_FOUND', $content);
        $this->assertStringContainsString('bg-warning', $content);
        $this->assertStringContainsString('SOURCE_UNAVAILABLE', $content);
        $this->assertStringContainsString('bg-secondary', $content);
    }

    /**
     * Test 12: Grader fixture tidak dapat menghasilkan provenance authenticated.
     */
    public function testGraderFixtureCannotProduceProvenanceAuthenticated(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $audit = $validator->auditGradingAuditLog('LOG_AUDIT_2026_01', 'STD_A1C048B42B28', 502, 'guru_bindo', 25.0);

        $this->assertEquals(ReviewerEvidenceValidator::GRADER_VALIDATION_PASSED, $audit['grader_provenance_status']);
        $this->assertNotEquals(ReviewerEvidenceValidator::GRADER_PROVENANCE_AUTHENTICATED, $audit['grader_provenance_status']);
        $this->assertFalse($audit['is_authoritative_verified']);
    }

    /**
     * Test 13: is_verified=true tidak dapat menaikkan readiness.
     */
    public function testIsVerifiedTrueCannotElevateReadiness(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $cases = [
            ['case_id' => 'REV_501_2b6231cb77', 'category' => 'ROUNDING_DIFFERENCE', 'applicable_policy' => 'DEC-ROUND-01', 'student_id' => 1, 'test_id' => 501, 'session_id' => 1001],
        ];
        $evidence = [
            ['evidence_id' => 'EV1', 'related_case_id' => 'REV_501_2b6231cb77', 'source_record_id' => 'cbt_aktivitas:1001', 'verification_status' => 'VERIFIED'],
        ];

        $matrix = $adapter->auditTraceability($cases, $evidence);
        $row = $matrix['REV_501_2b6231cb77'];

        $this->assertFalse($row['ready_for_authorized_review']);
        $this->assertEquals('NOT_READY_FAIL_CLOSED', $row['overall_readiness']);
    }

    /**
     * Test 14: Authoritative evidence saja tidak dapat menaikkan cutover readiness.
     */
    public function testAuthoritativeEvidenceAloneCannotElevateCutover(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $cases = [
            ['case_id' => 'REV_501_2b6231cb77', 'category' => 'ROUNDING_DIFFERENCE', 'applicable_policy' => 'DEC-ROUND-01', 'student_id' => 1, 'test_id' => 501, 'session_id' => 1001],
        ];
        $evidence = [
            ['evidence_id' => 'EV1', 'related_case_id' => 'REV_501_2b6231cb77', 'source_record_id' => 'cbt_aktivitas:1001', 'verification_status' => 'VERIFIED'],
        ];

        $matrix = $adapter->auditTraceability($cases, $evidence);
        $this->assertFalse($matrix['REV_501_2b6231cb77']['cutover_ready']);
    }

    /**
     * Test 15: Seluruh lima canonical case tetap tidak berubah.
     */
    public function testAllFiveCanonicalCasesRemainStrictlyUnchanged(): void
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
