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
 * CbtStageF3G_R6_R1Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6-R1
 * Authentication Status Consistency & Trust Boundary Enforcement Audit.
 */
class CbtStageF3G_R6_R1Test extends TestCase
{
    /**
     * Test 1: Fixture BAP with requireAuthoritative = false strictly yields BAP_VALIDATION_PASSED.
     */
    public function testFixtureBapWithRequireAuthoritativeFalseYieldsValidationPassed(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Condition 1: requireAuthoritative = false
        $res = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, false);

        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res['status']);
        $this->assertNotEquals(ReviewerEvidenceValidator::BAP_STATUS_AUTHENTICATED, $res['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $res['auth_semantic_level']);
        $this->assertFalse($res['is_authoritative_verified']);
        $this->assertTrue($res['is_valid']);
        $this->assertTrue($res['validation_rules_passed']);
    }

    /**
     * Test 2: Fixture BAP with requireAuthoritative = true strictly yields BAP_VALIDATION_PASSED.
     */
    public function testFixtureBapWithRequireAuthoritativeTrueYieldsValidationPassed(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Condition 2: requireAuthoritative = true
        $res = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);

        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res['status']);
        $this->assertNotEquals(ReviewerEvidenceValidator::BAP_STATUS_AUTHENTICATED, $res['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $res['auth_semantic_level']);
        $this->assertFalse($res['is_authoritative_verified']);
        $this->assertTrue($res['is_valid']);
    }

    /**
     * Test 3: Internal registry BAP with status AUTHENTICATED yields BAP_VALIDATION_PASSED.
     */
    public function testInternalRegistryBapWithStatusAuthenticatedYieldsValidationPassed(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Condition 3: Internal registry entry with status = AUTHENTICATED
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-INT-REG-001',
            'student_id'        => 'STD_INT_01',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-INTERNAL',
            'status'            => 'AUTHENTICATED',
        ], ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY);

        $resFalse = $validator->classifyBapDocument('BAP-INT-REG-001', 'STD_INT_01', 501, false);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $resFalse['status']);
        $this->assertFalse($resFalse['is_authoritative_verified']);

        $resTrue = $validator->classifyBapDocument('BAP-INT-REG-001', 'STD_INT_01', 501, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $resTrue['status']);
        $this->assertFalse($resTrue['is_authoritative_verified']);
    }

    /**
     * Test 4: Internal registry attempting to claim authoritative trust_level is safely downgraded (Anti-Spoofing).
     */
    public function testInternalRegistryClaimingAuthoritativeTrustLevelIsDowngraded(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Condition 4: Client payload asserts trust_level = AUTHORITATIVE_SOURCE_VERIFIED
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-SPOOF-001',
            'student_id'        => 'STD_SPOOF_01',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-FORGED-CLAIM',
            'status'            => 'AUTHENTICATED',
            'trust_level'       => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
        ], ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED);

        $storedDoc = $validator->getBapDocument('BAP-SPOOF-001');
        $this->assertNotNull($storedDoc);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $storedDoc['trust_level']);
        $this->assertFalse($storedDoc['is_authoritative_verified']);

        // Check metadata recorded the untrusted assertion
        $meta = $validator->getRegistryTrustBoundary('bapRegistry');
        $this->assertFalse($meta['is_authoritative']);

        // Evaluation strictly returns BAP_VALIDATION_PASSED, not BAP_AUTHENTICATED
        $res = $validator->classifyBapDocument('BAP-SPOOF-001', 'STD_SPOOF_01', 501);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res['status']);
        $this->assertFalse($validator->isBapAuthenticated('BAP-SPOOF-001', 'STD_SPOOF_01', 501));
    }

    /**
     * Test 5: Grader fixture with is_verified = true cannot spoof operational provenance.
     */
    public function testGraderFixtureWithIsVerifiedTrueCannotSpoofProvenance(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // Condition 5: Incoming provenance payload self-asserts is_verified = true and is_authoritative_verified = true
        $spoofedProvenance = [
            'grader_id'                 => 'guru_bindo',
            'is_authorized'             => true,
            'graded_at'                 => '2026-11-01 10:00:00',
            'change_log_reference'      => 'LOG_AUDIT_2026_01',
            'student_id'                => 'STD_A1C048B42B28',
            'test_id'                   => 502,
            'score'                     => 25.0,
            'is_verified'               => true,
            'is_authoritative_verified' => true,
        ];

        $res = $adapter->verifyGraderProvenance($spoofedProvenance, ['validator' => $validator]);

        // Validation rules passed internally, but operational provenance is NOT authoritative
        $this->assertTrue($res['is_verified']);
        $this->assertTrue($res['validation_rules_passed']);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_VALIDATION_PASSED, $res['grader_provenance_status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $res['auth_semantic_level']);
        $this->assertFalse($res['is_authoritative_verified']);
    }

    /**
     * Test 6: Grader registry entry claiming authoritative trust is safely downgraded.
     */
    public function testGraderRegistryClaimingAuthoritativeTrustIsDowngraded(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Condition 6: Registering log with claim of authoritative trust
        $validator->registerGradingAuditLog([
            'log_ref'                   => 'LOG-CLAIM-AUTH-001',
            'student_id'                => 'STD_A1C048B42B28',
            'test_id'                   => 502,
            'score'                     => 25.0,
            'grader_username'           => 'guru_bindo',
            'grading_timestamp'         => '2026-11-01 10:00:00',
            'status'                    => 'AUTHENTICATED',
            'trust_level'               => ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED,
            'is_authoritative_verified' => true,
        ], ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED);

        $storedLog = $validator->getGradingAuditLog('LOG-CLAIM-AUTH-001');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY, $storedLog['trust_level']);
        $this->assertFalse($storedLog['is_authoritative_verified']);

        // Auditing returns GRADER_INTERNAL_REGISTRY_MATCH, not GRADER_PROVENANCE_AUTHENTICATED
        $audit = $validator->auditGradingAuditLog('LOG-CLAIM-AUTH-001', 'STD_A1C048B42B28', 502, 'guru_bindo', 25.0);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_INTERNAL_REGISTRY_MATCH, $audit['grader_provenance_status']);
        $this->assertFalse($audit['is_authoritative_verified']);
        $this->assertFalse($validator->isGradingAuditLogAuthenticated('LOG-CLAIM-AUTH-001', 'STD_A1C048B42B28', 502));
    }

    /**
     * Test 7: Caller/API sending is_authoritative_verified = true is stripped by Controller & Adapter.
     */
    public function testCallerSendingIsAuthoritativeVerifiedTrueIsStripped(): void
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

        $fallbackResponse = new class extends \CodeIgniter\HTTP\Response {
            protected $statusCode = 200;
            protected $data = null;
            public function __construct() {}
            public function setStatusCode(int $code, string $reason = ''): static { $this->statusCode = $code; return $this; }
            public function getStatusCode(): int { return $this->statusCode; }
            public function setJSON($body, bool $unencoded = false): static { $this->data = $body; return $this; }
            public function getBody(): mixed { return json_encode($this->data); }
        };
        $controller->setResponse($fallbackResponse);

        $request = new class extends \CodeIgniter\HTTP\IncomingRequest {
            public function __construct() {}
            public function getMethod($upper = false): string { return 'POST'; }
            public function getPost($index = null, $filter = null, $flags = null): mixed {
                $data = [
                    'case_id'                   => 'REV_501_2b6231cb77',
                    'action'                    => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
                    'reason'                    => 'Testing spoof stripping',
                    'is_authoritative_verified' => true,
                    'trust_level'               => 'AUTHORITATIVE_SOURCE_VERIFIED',
                    'evidence_verified'         => true,
                ];
                return $index === null ? $data : ($data[$index] ?? null);
            }
        };
        $controller->setRequest($request);

        $response = $controller->preview();
        $this->assertEquals(200, $response->getStatusCode());
        $body = json_decode($response->getBody(), true);

        // Preview result must NOT have promoted authoritative status
        $this->assertTrue($body['success']);
        $preview = $body['preview'];
        $this->assertFalse($preview['readiness']['is_authoritative_verified'] ?? false);
        $this->assertNotEquals('AUTHORITATIVE_VERIFIED', $preview['readiness']['readiness_status'] ?? '');
    }

    /**
     * Test 8: Usage of is_verified is decoupled from operational readiness and cutover.
     */
    public function testUsageOfIsVerifiedDecoupledFromOperationalReadinessAndCutover(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // Cases with verified format rules
        $cases = [
            ['case_id' => 'REV_501_2b6231cb77', 'category' => 'ROUNDING_DIFFERENCE', 'applicable_policy' => 'DEC-ROUND-01', 'student_id' => 1, 'test_id' => 501, 'session_id' => 1001],
        ];
        $evidence = [
            ['evidence_id' => 'EV1', 'related_case_id' => 'REV_501_2b6231cb77', 'source_record_id' => 'cbt_aktivitas:1001', 'verification_status' => 'VERIFIED'],
        ];

        $matrix = $adapter->auditTraceability($cases, $evidence);
        $row = $matrix['REV_501_2b6231cb77'];

        // Operational readiness & cutover remain strictly false
        $this->assertFalse($row['ready_for_authorized_review']);
        $this->assertFalse($row['cutover_ready']);
        $authSem = $row['evidence_authenticity']['authentication_status_semantics'];
        $this->assertFalse($authSem['authoritative_evidence_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED, $authSem['status_classification']);
        $this->assertFalse($row['evidence_authenticity']['session_ownership_proven']);
    }

    /**
     * Test 9: Dashboard and Detail View semantic badge rendering.
     */
    public function testDashboardAndDetailViewSemanticBadgeRendering(): void
    {
        $detailPath = dirname(__DIR__, 2) . '/app/Views/admin/cbt/review/detail.php';
        $content = file_get_contents($detailPath);

        // Verify explicit semantic branch exists in view template
        $this->assertStringContainsString('VALIDATION_PASSED (FIXTURE)', $content);
        $this->assertStringContainsString('AUTHORITATIVE_VERIFIED', $content);
        $this->assertStringContainsString('is_authoritative_verified', $content);
    }

    /**
     * Test 10: Authoritative source infrastructure unavailable fail-closes gracefully.
     */
    public function testAuthoritativeSourceUnavailableFailCloses(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Default offline/audit environment
        $this->assertFalse($validator->isAuthoritativeInfrastructureAvailable());

        $res = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-001', [
            'server_session_token'   => 'SRV-TOKEN',
            'authoritative_checksum' => 'SHA256:CHECK',
        ]);

        $this->assertFalse($res['is_verified']);
        $this->assertFalse($res['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $res['status']);
        $this->assertStringContainsString('NOT PERFORMED', $res['discrepancy']);
    }

    /**
     * Test 11: BAP Conflict and Revocation Semantics.
     */
    public function testBapConflictAndRevocationSemantics(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Revocation check
        $validator->revokeBapDocument('BAP-AUTH-2026-001', 'Misconduct detected');
        $resRev = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REVOKED, $resRev['status']);
        $this->assertFalse($resRev['is_valid']);
        $this->assertFalse($validator->isBapAuthenticated('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503));

        // Conflict check
        $valConflict = new ReviewerEvidenceValidator();
        $valConflict->registerBapDocument([
            'bap_ref'           => 'BAP-AUTH-2026-001',
            'student_id'        => 'STD_CONFLICT_ANOTHER_STUDENT',
            'test_id'           => 503,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG',
        ]);
        $resConf = $valConflict->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_INVALID, $resConf['status']);
        $this->assertFalse($resConf['is_valid']);
    }

    /**
     * Test 12: All 5 Canonical cases remain strictly unchanged and pending.
     */
    public function testAllFiveCanonicalCasesRemainStrictlyUnchanged(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $summary = $workflow->getSummary();

        // Gate Invariants
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

        foreach ($queue as $item) {
            $this->assertNotEquals('APPROVED', $item['review_status']);
            $this->assertNotEquals('RESOLVED', $item['review_status']);
        }
    }
}
