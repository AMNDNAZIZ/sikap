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

/**
 * CbtStageF3G_R6Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R6
 * Evidence Authentication Semantics, Registry Lifecycle & Cross-Request Integrity Audit.
 */
class CbtStageF3G_R6Test extends TestCase
{
    /**
     * Test 1: Explicit 4-Level Authentication Status Semantics (Audit 1).
     */
    public function testExplicitFourLevelAuthenticationStatusSemantics(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // Level 1: VALIDATION_RULES_PASSED - Fixture passes validation rules, but is NOT authoritative
        $resFixture = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $resFixture['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $resFixture['auth_semantic_level']);
        $this->assertFalse($resFixture['is_authoritative_verified']);
        $this->assertTrue($resFixture['is_valid']);

        // Level 2: INTERNAL_REGISTRY_MATCH - Present in internal registry but unverified status
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-INTERNAL-MATCH-ONLY',
            'student_id'        => 'STD_MATCH_01',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-MATCH',
            'status'            => 'REGISTERED_DRAFT', // Not authenticated
        ], ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY);
        $resMatch = $validator->classifyBapDocument('BAP-INTERNAL-MATCH-ONLY', 'STD_MATCH_01', 501, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REGISTERED_UNVERIFIED, $resMatch['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_INTERNAL_REGISTRY_MATCH, $resMatch['auth_semantic_level']);
        $this->assertFalse($resMatch['is_authoritative_verified']);

        // Level 3: AUTHORITATIVE_EVIDENCE_VERIFIED - Only true when server-controlled verification verifies authentic source
        $validator->setAuthoritativeInfrastructureAvailable(true);
        $serverAuthResult = $validator->verifyAuthoritativeSourceViaServer('BAP', 'BAP-AUTHORITATIVE-001', [
            'server_session_token'   => 'SRV-TOKEN-2026-X',
            'authoritative_checksum' => 'SHA256:AUTHENTIC_DB_PROVENANCE',
        ]);
        $this->assertTrue($serverAuthResult['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED, $serverAuthResult['auth_semantic_level']);
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED, $serverAuthResult['status']);
        $validator->setAuthoritativeInfrastructureAvailable(false); // Reset to offline default

        // Level 4: AUTHENTICATION_NOT_VERIFIED - Unknown / untraceable records
        $resNotFound = $validator->classifyBapDocument('BAP-NON-EXISTENT-999', 'STD_X', 501, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_NOT_FOUND, $resNotFound['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED, $resNotFound['auth_semantic_level']);
        $this->assertFalse($resNotFound['is_authoritative_verified']);
    }

    /**
     * Test 2: BAP Fixture BAP-AUTH-2026-001 Hardened Semantics (Audit 2).
     *
     * In R5, BAP-AUTH-2026-001 produced BAP_AUTHENTICATED despite being FIXTURE_ONLY.
     * In R6, operational verification strictly classifies it as BAP_VALIDATION_PASSED.
     */
    public function testBapFixtureHardenedOperationalSemantics(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // 2.A: Operational classification of fixture BAP yields BAP_VALIDATION_PASSED
        $resOperational = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $resOperational['status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $resOperational['auth_semantic_level']);
        $this->assertFalse($resOperational['is_authoritative_verified']);

        // 2.B: isBapAuthenticated strictly returns false for fixture-only documents
        $this->assertFalse($validator->isBapAuthenticated('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503));

        // 2.C: Caller registration with TRUST_AUTHORITATIVE_SOURCE_VERIFIED is safely downgraded
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-REAL-DB-001',
            'student_id'        => 'STD_7B5939E895CC',
            'test_id'           => 503,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-VERIFIED',
            'status'            => 'AUTHENTICATED',
        ], ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED);
        // Anti-spoofing enforcement: Caller cannot elevate trust; operational check remains false
        $this->assertFalse($validator->isBapAuthenticated('BAP-REAL-DB-001', 'STD_7B5939E895CC', 503));
    }

    /**
     * Test 3: Grader Provenance Authentication Semantics (Audit 3).
     */
    public function testGraderProvenanceAuthenticationSemantics(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // 3.A: Seeded fixture grading log yields GRADER_VALIDATION_PASSED (not operational authoritative)
        $auditFixture = $validator->auditGradingAuditLog('LOG_AUDIT_2026_01', 'STD_A1C048B42B28', 502, 'guru_bindo', 25.0);
        $this->assertTrue($auditFixture['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_VALIDATION_PASSED, $auditFixture['grader_provenance_status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $auditFixture['auth_semantic_level']);
        $this->assertFalse($auditFixture['is_authoritative_verified']);

        // 3.B: Caller attempting to register authoritative log is downgraded to INTERNAL_REGISTRY_MATCH
        $validator->registerGradingAuditLog([
            'log_ref'           => 'LOG_AUTH_DB_999',
            'student_id'        => 'STD_A1C048B42B28',
            'test_id'           => 502,
            'score'             => 30.0,
            'grader_username'   => 'guru_bindo',
            'grading_timestamp' => '2026-11-01 11:00:00',
            'status'            => 'AUTHENTICATED',
        ], ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED);

        $auditAuthoritative = $validator->auditGradingAuditLog('LOG_AUTH_DB_999', 'STD_A1C048B42B28', 502, 'guru_bindo', 30.0);
        $this->assertTrue($auditAuthoritative['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_INTERNAL_REGISTRY_MATCH, $auditAuthoritative['grader_provenance_status']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $auditAuthoritative['auth_semantic_level']);
        $this->assertFalse($auditAuthoritative['is_authoritative_verified']);

        // 3.C: Missing log yields GRADER_PROVENANCE_UNAVAILABLE
        $auditMissing = $validator->auditGradingAuditLog('LOG_MISSING_000', 'STD_A1C048B42B28', 502);
        $this->assertFalse($auditMissing['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_PROVENANCE_UNAVAILABLE, $auditMissing['grader_provenance_status']);

        // 3.D: Conflict in registry yields GRADER_PROVENANCE_CONFLICT
        $conflictVal = new ReviewerEvidenceValidator();
        $conflictVal->registerGradingAuditLog([
            'log_ref'         => 'LOG_AUDIT_2026_01',
            'student_id'      => 'STD_CONFLICT_STUDENT',
            'test_id'         => 502,
            'grader_username' => 'guru_bindo',
            'score'           => 10.0,
            'status'          => 'AUTHENTICATED',
        ]);
        $auditConflict = $conflictVal->auditGradingAuditLog('LOG_AUDIT_2026_01', 'STD_A1C048B42B28', 502);
        $this->assertFalse($auditConflict['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::GRADER_PROVENANCE_CONFLICT, $auditConflict['grader_provenance_status']);
    }

    /**
     * Test 4: Registry Lifecycle Scenario 1 & 2 - BAP Registration & Revocation Ephemerality (Audit 4).
     */
    public function testRegistryLifecycleBapRegistrationAndRevocationEphemerality(): void
    {
        // Scenario 1: Register BAP on Instance 1, then create fresh Instance 2
        $instance1 = new ReviewerEvidenceValidator();
        $instance1->registerBapDocument([
            'bap_ref'           => 'BAP-EPHEMERAL-REQ1',
            'student_id'        => 'STD_REQ1',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-REQ1',
            'status'            => 'AUTHENTICATED',
        ]);
        $this->assertNotNull($instance1->getBapDocument('BAP-EPHEMERAL-REQ1'));

        // Instance 2 (representing next request) does NOT retain the registration
        $instance2 = new ReviewerEvidenceValidator();
        $this->assertNull($instance2->getBapDocument('BAP-EPHEMERAL-REQ1'));
        $resFresh = $instance2->classifyBapDocument('BAP-EPHEMERAL-REQ1', 'STD_REQ1', 501, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_NOT_FOUND, $resFresh['status']);

        // Scenario 2: Revoke BAP on Instance 1, Instance 2 still sees unrevoked baseline
        $instance1->revokeBapDocument('BAP-AUTH-2026-001', 'Revocation test request 1');
        $bap1 = $instance1->getBapDocument('BAP-AUTH-2026-001');
        $this->assertTrue($bap1['is_revoked']);

        $instanceFresh = new ReviewerEvidenceValidator();
        $bapFresh = $instanceFresh->getBapDocument('BAP-AUTH-2026-001');
        $this->assertFalse($bapFresh['is_revoked'] ?? false);
    }

    /**
     * Test 5: Registry Lifecycle Scenario 3 & 4 - Grading Log Ephemerality & Cross-Request Evaluation (Audit 4).
     */
    public function testRegistryLifecycleGradingLogAndCrossRequestEvaluation(): void
    {
        // Scenario 3: Register grading log on Instance A, Instance B does NOT have it
        $instanceA = new ReviewerEvidenceValidator();
        $instanceA->registerGradingAuditLog([
            'log_ref'           => 'LOG-EPHEMERAL-REQA',
            'student_id'        => 'STD_A',
            'test_id'           => 501,
            'score'             => 45.0,
            'grader_username'   => 'guru_matematika',
            'grading_timestamp' => '2026-11-01 10:00:00',
            'status'            => 'AUTHENTICATED',
        ]);
        $this->assertNotNull($instanceA->getGradingAuditLog('LOG-EPHEMERAL-REQA'));

        $instanceB = new ReviewerEvidenceValidator();
        $this->assertNull($instanceB->getGradingAuditLog('LOG-EPHEMERAL-REQA'));

        // Scenario 4: Workflow evaluation across different request simulations
        $wfA = new ReviewerWorkflowService();
        $wfA->setEvidenceValidator($instanceA);

        $wfB = new ReviewerWorkflowService();
        $wfB->setEvidenceValidator($instanceB);

        // Verification on A finds the log, verification on B fails to find it
        $adapter = new HistoricalSourceAdapter();
        $prov = [
            'grader_id'            => 'guru_matematika',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG-EPHEMERAL-REQA',
            'student_id'           => 'STD_A',
            'test_id'              => 501,
            'score'                => 45.0,
        ];
        $chkA = $adapter->verifyGraderProvenance($prov, ['validator' => $instanceA]);
        $this->assertTrue($chkA['is_verified']);

        $chkB = $adapter->verifyGraderProvenance($prov, ['validator' => $instanceB]);
        $this->assertFalse($chkB['is_verified']);
        $this->assertStringContainsString('not found in registry', $chkB['discrepancy']);
    }

    /**
     * Test 6: Registry Lifecycle Scenario 5 & 6 - Concurrent Request Isolation & Empty Registry (Audit 4).
     */
    public function testRegistryLifecycleIsolationAndEmptyRegistry(): void
    {
        // Scenario 5: Two concurrent requests reading different states
        $req1Validator = new ReviewerEvidenceValidator();
        $req2Validator = new ReviewerEvidenceValidator();

        $req1Validator->registerTeacherAssignment('guru_external', [501], ['Matematika']);
        $this->assertTrue($req1Validator->isTeacherAssignedToTest('guru_external', 501));
        $this->assertFalse($req2Validator->isTeacherAssignedToTest('guru_external', 501));

        // Scenario 6: Request 1 clears registry, Request 2 starts with default seeded data
        $req1Validator->clearRegistry('bapRegistry');
        $tb1 = $req1Validator->getRegistryTrustBoundary('bapRegistry');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $tb1['trust_level']);

        $freshReq = new ReviewerEvidenceValidator();
        $tbFresh = $freshReq->getRegistryTrustBoundary('bapRegistry');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $tbFresh['trust_level']);
        $this->assertGreaterThan(0, $tbFresh['entry_count']);
    }

    /**
     * Test 7: Registry Lifecycle Scenario 7 & 8 - Exception Fail-Closed & Audit Trail Non-Persistence (Audit 4).
     */
    public function testRegistryLifecycleExceptionFailClosedAndAuditTrail(): void
    {
        // Scenario 7: Exception during registry read fail-closes gracefully
        $mockVal = new class extends ReviewerEvidenceValidator {
            public function classifyBapDocument(string $bapRef, ?string $studentId = null, int|string|null $testId = null, bool $requireAuthoritative = false): array {
                throw new \RuntimeException("Database connection timed out during registry read");
            }
        };

        $adapter = new HistoricalSourceAdapter();
        $bapDoc = [
            'bap_document' => [
                'proctor_id'           => 'PRC_01',
                'is_authorized_proctor'=> true,
                'is_bap_signed'        => true,
                'bap_number'           => 'BAP-AUTH-2026-001',
                'exam_date'            => '2026-11-01',
            ],
        ];

        // Ensure exception thrown is captured or caught fail-closed
        $caught = false;
        try {
            $adapter->verifyAbsenceEvidence($bapDoc, ['validator' => $mockVal]);
        } catch (\RuntimeException $e) {
            $caught = true;
            $this->assertStringContainsString('Database connection timed out', $e->getMessage());
        }
        $this->assertTrue($caught);

        // Scenario 8: Audit trail records in-memory mutations but does not persist
        $val1 = new ReviewerEvidenceValidator();
        $val1->registerBapDocument([
            'bap_ref'           => 'BAP-TRAIL-01',
            'student_id'        => 'STD_T1',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG',
        ]);
        $trail1 = $val1->getRegistryAuditTrail();
        $this->assertNotEmpty($trail1);

        $val2 = new ReviewerEvidenceValidator();
        $trail2 = $val2->getRegistryAuditTrail();
        $actions = array_column($trail2, 'action');
        $this->assertNotContains('BAP-TRAIL-01', array_column($trail2, 'ref'));
    }

    /**
     * Test 8: Failure Injection Across All Non-Authoritative Scenarios (Section H).
     */
    public function testFailureInjectionAcrossNonAuthoritativeScenarios(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // Failure Case 1: Fixture with AUTHENTICATED status fails operational requirement
        $res1 = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);
        $this->assertNotEquals(ReviewerEvidenceValidator::BAP_STATUS_AUTHENTICATED, $res1['status']);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res1['status']);

        // Failure Case 2: Complete internal registry data without authoritative boundary
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-COMPLETE-INTERNAL',
            'student_id'        => 'STD_C1',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-INTERNAL',
            'status'            => 'AUTHENTICATED',
        ], ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY);
        $res2 = $validator->classifyBapDocument('BAP-COMPLETE-INTERNAL', 'STD_C1', 501, true);
        $this->assertFalse($res2['is_authoritative_verified']);
        $this->assertEquals(ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED, $res2['auth_semantic_level']);

        // Failure Case 3: Empty registry yields SOURCE_UNAVAILABLE
        $emptyVal = new ReviewerEvidenceValidator();
        $emptyVal->clearRegistry('bapRegistry');
        $res3 = $emptyVal->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_SOURCE_UNAVAILABLE, $res3['status']);
        $this->assertFalse($res3['is_valid']);

        // Failure Case 4: Registry conflict fail-closes
        $conflictVal = new ReviewerEvidenceValidator();
        $conflictVal->registerBapDocument([
            'bap_ref'           => 'BAP-AUTH-2026-001',
            'student_id'        => 'STD_CONFLICT_ANOTHER',
            'test_id'           => 503,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-CONFLICT',
        ]);
        $res4 = $conflictVal->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503, true);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_INVALID, $res4['status']);
        $this->assertFalse($res4['is_valid']);
    }

    /**
     * Test 9: Readiness & API / Dashboard Consistency (Audit 5).
     */
    public function testReadinessAndApiDashboardConsistency(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $cases = [
            ['case_id' => 'REV_501_2b6231cb77', 'category' => 'ROUNDING_DIFFERENCE', 'applicable_policy' => 'DEC-ROUND-01', 'student_id' => 1, 'test_id' => 501],
            ['case_id' => 'REV_501_7b48bc8a1f', 'category' => 'ABSENT_SCORE_DIFFERENCE', 'applicable_policy' => 'DEC-ABS-01', 'student_id' => 2, 'test_id' => 501],
            ['case_id' => 'REV_502_3ff144b11c', 'category' => 'PENDING_ESSAY', 'applicable_policy' => 'DEC-ESS-01', 'student_id' => 3, 'test_id' => 502],
            ['case_id' => 'REV_503_4266a4ed7f', 'category' => 'RETAKE_ATTEMPT_MISMATCH', 'applicable_policy' => 'DEC-RET-01', 'student_id' => 4, 'test_id' => 503],
            ['case_id' => 'REV_503_fc68cd64f2', 'category' => 'ABSENT_SCORE_DIFFERENCE', 'applicable_policy' => 'DEC-ABS-01', 'student_id' => 5, 'test_id' => 503],
        ];

        $matrix = $adapter->auditTraceability($cases, []);

        foreach ($cases as $c) {
            $row = $matrix[$c['case_id']];
            // Rule 1: ready_for_authorized_review is strictly FALSE
            $this->assertFalse($row['ready_for_authorized_review']);

            // Rule 2: cutover_ready is strictly FALSE
            $this->assertFalse($row['cutover_ready']);

            // Rule 3: authoritative_evidence_verified is strictly FALSE
            $authSemantics = $row['evidence_authenticity']['authentication_status_semantics'];
            $this->assertFalse($authSemantics['authoritative_evidence_verified']);
            $this->assertEquals(ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED, $authSemantics['status_classification']);
        }
    }

    /**
     * Test 10: Invariant Cutover Gate - 0 Resolved, 5 Pending, Gate REQUIRES_REVIEW (Section L).
     */
    public function testInvariantCutoverGate(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $summary = $workflow->getSummary();

        // 5 Canonical cases remain completely unresolved and pending
        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        $queue = $workflow->getQueue();
        $this->assertCount(5, $queue);
        foreach ($queue as $item) {
            $this->assertNotEquals('APPROVED', $item['review_status']);
            $this->assertNotEquals('RESOLVED', $item['review_status']);
        }
    }
}

