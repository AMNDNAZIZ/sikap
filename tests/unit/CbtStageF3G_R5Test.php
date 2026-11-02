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
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use PDO;

/**
 * CbtStageF3G_R5Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R5
 * Registry Trust Boundary, Evidence Provenance & Readiness Semantic Verification.
 */
class CbtStageF3G_R5Test extends TestCase
{
    /**
     * Test 1: Registry Trust Boundary Classifications (Audit 1).
     */
    public function testRegistryTrustBoundaryClassifications(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // 1.A: Default seeded registries are classified as FIXTURE_ONLY (not authoritative)
        $tbBap = $validator->getRegistryTrustBoundary('bapRegistry');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $tbBap['trust_level']);
        $this->assertFalse($tbBap['is_authoritative']);
        $this->assertStringContainsString('FIXTURE_ONLY', $tbBap['discrepancy']);

        $tbGrading = $validator->getRegistryTrustBoundary('gradingAuditLogs');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $tbGrading['trust_level']);
        $this->assertFalse($tbGrading['is_authoritative']);

        $tbTeacher = $validator->getRegistryTrustBoundary('teacherAssignments');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_FIXTURE_ONLY, $tbTeacher['trust_level']);
        $this->assertFalse($tbTeacher['is_authoritative']);

        // 1.B: Empty registry is classified as SOURCE_UNAVAILABLE
        $validator->clearRegistry('bapRegistry');
        $tbEmpty = $validator->getRegistryTrustBoundary('bapRegistry');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE, $tbEmpty['trust_level']);
        $this->assertEquals(0, $tbEmpty['entry_count']);

        // 1.C: Conflicting duplicate identifiers classified as SOURCE_INTEGRITY_UNVERIFIED
        $validatorConflict = new ReviewerEvidenceValidator();
        // Register duplicate BAP with conflicting student_id
        $validatorConflict->registerBapDocument([
            'bap_ref'           => 'BAP-AUTH-2026-001', // Identical ref as seeded
            'student_id'        => 'STD_CONFLICT_999',  // Conflicting student
            'test_id'           => 503,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG-CONFLICT',
        ]);
        $tbConflict = $validatorConflict->getRegistryTrustBoundary('bapRegistry');
        $this->assertEquals(ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED, $tbConflict['trust_level']);
        $this->assertFalse($tbConflict['is_authoritative']);
        $this->assertNotEmpty($tbConflict['conflicts']);
    }

    /**
     * Test 2: Grader Provenance & Granular Audit Log Verification (Audit 2).
     */
    public function testGraderProvenanceAndGranularAuditLogVerification(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // 2.A: Valid teacher and valid log passes
        $validProv = [
            'grader_id'            => 'guru_bindo',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_AUDIT_2026_01',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
            'score'                => 25.0,
        ];
        $chk1 = $adapter->verifyGraderProvenance($validProv, ['validator' => $validator]);
        $this->assertTrue($chk1['is_verified']);
        $this->assertNull($chk1['discrepancy']);

        // 2.B: Teacher registered for different subject/test (guru_matematika on 502) fails
        $wrongSubjectProv = array_merge($validProv, ['grader_id' => 'guru_matematika']);
        $chk2 = $adapter->verifyGraderProvenance($wrongSubjectProv, ['validator' => $validator]);
        $this->assertFalse($chk2['is_verified']);
        $this->assertStringContainsString('lacks verified authority', $chk2['discrepancy']);

        // 2.C: Audit log belonging to another student fails
        $wrongStudentProv = array_merge($validProv, ['student_id' => 'STD_DIFFERENT_999']);
        $chk3 = $adapter->verifyGraderProvenance($wrongStudentProv, ['validator' => $validator]);
        $this->assertFalse($chk3['is_verified']);
        $this->assertStringContainsString('student mismatch', $chk3['discrepancy']);

        // 2.D: Audit log belonging to another test fails
        // guru_matematika is assigned to 501, but LOG_AUDIT_2026_01 is for test 502
        $wrongTestProv = array_merge($validProv, [
            'grader_id'  => 'guru_matematika',
            'test_id'    => 501,
        ]);
        $chk4 = $adapter->verifyGraderProvenance($wrongTestProv, ['validator' => $validator]);
        $this->assertFalse($chk4['is_verified']);
        $this->assertStringContainsString('test mismatch', $chk4['discrepancy']);

        // 2.E: Audit log with score mismatch fails
        $wrongScoreProv = array_merge($validProv, ['score' => 40.0]); // Log records 25.0, claimed is 40.0
        $chk5 = $adapter->verifyGraderProvenance($wrongScoreProv, ['validator' => $validator]);
        $this->assertFalse($chk5['is_verified']);
        $this->assertStringContainsString('score mismatch', $chk5['discrepancy']);

        // 2.F: Audit log not found fails
        $notFoundProv = array_merge($validProv, ['change_log_reference' => 'LOG_NON_EXISTENT']);
        $chk6 = $adapter->verifyGraderProvenance($notFoundProv, ['validator' => $validator]);
        $this->assertFalse($chk6['is_verified']);
        $this->assertStringContainsString('not found in registry', $chk6['discrepancy']);
    }

    /**
     * Test 3: Grader Provenance Fail-Closed When Registry Unavailable or Conflicting (Audit 2).
     */
    public function testGraderProvenanceFailClosedOnEmptyOrConflictedRegistry(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 3.A: Registry unavailable / empty
        $emptyValidator = new ReviewerEvidenceValidator();
        $emptyValidator->clearRegistry('gradingAuditLogs');
        $prov = [
            'grader_id'            => 'guru_bindo',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_AUDIT_2026_01',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
        ];
        $chkEmpty = $adapter->verifyGraderProvenance($prov, ['validator' => $emptyValidator]);
        $this->assertFalse($chkEmpty['is_verified']);
        $this->assertStringContainsString('empty or unavailable', $chkEmpty['discrepancy']);

        // 3.B: Registry conflict detected
        $conflictValidator = new ReviewerEvidenceValidator();
        $conflictValidator->registerGradingAuditLog([
            'log_ref'         => 'LOG_AUDIT_2026_01',
            'student_id'      => 'STD_DIFFERENT',
            'test_id'         => 502,
            'grader_username' => 'guru_bindo',
            'score'           => 99.0,
            'status'          => 'AUTHENTICATED',
        ]);
        $chkConflict = $adapter->verifyGraderProvenance($prov, ['validator' => $conflictValidator]);
        $this->assertFalse($chkConflict['is_verified']);
        $this->assertStringContainsString('integrity unverified due to duplicate', $chkConflict['discrepancy']);
    }

    /**
     * Test 4: BAP Evidence Classification across All 6 Formal States (Audit 3).
     */
    public function testBapEvidenceClassificationAcrossSixFormalStates(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // State 1: BAP_VALIDATION_PASSED (Stage F3G-R6-R1: fixture strictly yields BAP_VALIDATION_PASSED)
        $res1 = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED, $res1['status']);
        $this->assertTrue($res1['is_valid']);

        // State 2: BAP_NOT_FOUND
        $res2 = $validator->classifyBapDocument('BAP_NON_EXISTENT', 'STD_7B5939E895CC', 503);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_NOT_FOUND, $res2['status']);
        $this->assertFalse($res2['is_valid']);

        // State 3: BAP_REVOKED
        $validator->revokeBapDocument('BAP-AUTH-2026-001', 'Cancelled due to proctor misconduct');
        $res3 = $validator->classifyBapDocument('BAP-AUTH-2026-001', 'STD_7B5939E895CC', 503);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REVOKED, $res3['status']);
        $this->assertFalse($res3['is_valid']);
        $this->assertStringContainsString('formally revoked', $res3['discrepancy']);

        // State 4: BAP_INVALID (Student or Test Mismatch)
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP_MISMATCH_01',
            'student_id'        => 'STD_STUDENT_A',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG_OK',
            'status'            => 'AUTHENTICATED',
        ]);
        $res4 = $validator->classifyBapDocument('BAP_MISMATCH_01', 'STD_STUDENT_B', 501);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_INVALID, $res4['status']);
        $this->assertFalse($res4['is_valid']);
        $this->assertStringContainsString('student mismatch', $res4['discrepancy']);

        // State 5: BAP_REGISTERED_UNVERIFIED
        $validator->registerBapDocument([
            'bap_ref'           => 'BAP_UNVERIFIED_01',
            'student_id'        => 'STD_STUDENT_A',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG_OK',
            'status'            => 'PENDING_COMMITTEE_SIGN',
        ]);
        $res5 = $validator->classifyBapDocument('BAP_UNVERIFIED_01', 'STD_STUDENT_A', 501);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REGISTERED_UNVERIFIED, $res5['status']);
        $this->assertFalse($res5['is_valid']);

        // State 6: BAP_SOURCE_UNAVAILABLE
        $validator->clearRegistry('bapRegistry');
        $res6 = $validator->classifyBapDocument('BAP-AUTH-2026-001');
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_SOURCE_UNAVAILABLE, $res6['status']);
        $this->assertFalse($res6['is_valid']);
    }

    /**
     * Test 5: verifyAbsenceEvidence Returns Granular BAP Classification (Audit 3).
     */
    public function testVerifyAbsenceEvidenceReturnsGranularBapClassification(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // 5.A: Revoked BAP returns BAP_REVOKED classification
        $validator->revokeBapDocument('BAP-AUTH-2026-001', 'Formal cancellation');
        $revokedBapDoc = [
            'bap_document' => [
                'bap_number'            => 'BAP-AUTH-2026-001',
                'proctor_id'            => 'Pengawas Utama',
                'is_authorized_proctor' => true,
                'exam_date'             => '2026-11-01',
                'is_bap_signed'         => true,
                'student_id'            => 'STD_7B5939E895CC',
                'test_id'               => 503,
            ],
        ];
        $res = $adapter->verifyAbsenceEvidence($revokedBapDoc, [
            'validator'  => $validator,
            'student_id' => 'STD_7B5939E895CC',
            'test_id'    => 503,
        ]);
        $this->assertFalse($res['is_verified']);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED, $res['status']);
        $this->assertEquals(ReviewerEvidenceValidator::BAP_STATUS_REVOKED, $res['bap_classification']);
        $this->assertStringContainsString('formally revoked', $res['discrepancy']);
    }

    /**
     * Test 6: Readiness Semantic Separation (Audit 4).
     */
    public function testReadinessSemanticSeparationAcrossAllDimensions(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $cases = [
            [
                'case_id'           => 'REV_501_2b6231cb77',
                'category'          => 'ROUNDING_DIFFERENCE',
                'applicable_policy' => 'DEC-ROUND-01',
                'student_id'        => 'STD_2434EBBEFF9A',
                'test_id'           => 501,
                'session_id'        => 1001,
            ],
            [
                'case_id'           => 'REV_502_3ff144b11c',
                'category'          => 'PENDING_ESSAY',
                'applicable_policy' => 'DEC-ESS-01',
                'student_id'        => 'STD_A1C048B42B28',
                'test_id'           => 502,
                'session_id'        => 1006,
            ],
        ];

        $evidence = [
            [
                'evidence_id'         => 'EVD_501_ROUND_01',
                'related_case_id'     => 'REV_501_2b6231cb77',
                'source_record_id'    => 'cbt_aktivitas:1001',
                'verification_status' => 'VERIFIED',
            ],
            [
                'evidence_id'         => 'EVD_502_ESSAY_01',
                'related_case_id'     => 'REV_502_3ff144b11c',
                'source_record_id'    => 'cbt_soal_siswa:1006',
                'verification_status' => 'VERIFIED',
            ],
        ];

        $matrix = $adapter->auditTraceability($cases, $evidence);

        // Verify granular breakdown exists and is not conflated
        foreach ($cases as $c) {
            $row = $matrix[$c['case_id']];
            $this->assertArrayHasKey('evidence_authenticity', $row);
            $auth = $row['evidence_authenticity'];
            $this->assertArrayHasKey('source_record_exists', $auth);
            $this->assertArrayHasKey('student_context_match', $auth);
            $this->assertArrayHasKey('session_ownership_proven', $auth);
            $this->assertArrayHasKey('governance_approval_ready', $auth);
            $this->assertArrayHasKey('grader_provenance_verified', $auth);
            $this->assertArrayHasKey('bap_authenticated', $auth);
            $this->assertArrayHasKey('registry_trust_boundary', $auth);

            // Session ownership proven is strictly false
            $this->assertFalse($auth['session_ownership_proven']);
            $this->assertFalse($row['ready_for_authorized_review']);
            $this->assertFalse($row['cutover_ready']);
        }
    }

    /**
     * Test 7: Failure Injection - Exception in Readiness Evaluation Fail-Closes Gracefully (Audit 5).
     */
    public function testFailureInjectionReadinessExceptionFailClosesGracefully(): void
    {
        $auditService = new ReviewerAuditService();
        $workflow = new ReviewerWorkflowService(null, $auditService);

        // Ingest a test case
        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_FAIL_01',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'delta'                   => 0.02,
                'is_comparable'           => true,
            ],
        ]);
        $queue = $workflow->getQueue();
        $caseId = $queue[0]['case_id'];

        // Inject a mock validator that throws a database/adapter exception
        $mockValidator = $this->createMock(ReviewerEvidenceValidator::class);
        $mockValidator->method('evaluateReadiness')
            ->willThrowException(new \RuntimeException("Database connection dropped during evidence verification"));

        $workflow->setEvidenceValidator($mockValidator);

        $preview = $workflow->previewDecision(
            $caseId,
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing exception injection',
            ['actor_role' => 'REVIEWER']
        );

        // Verify preview caught exception fail-closed
        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_SOURCE_UNAVAILABLE, $preview['readiness_status']);
        $this->assertStringContainsString('Database connection dropped', $preview['error']);

        // Verify queue state was NOT mutated
        $caseAfter = $workflow->getCase($caseId);
        $this->assertEquals('ACKNOWLEDGEMENT_REQUIRED', $caseAfter['review_status']);
    }

    /**
     * Test 8: Registry Mutation Audit Trail Tracks All Additions and Revocations (Audit 1).
     */
    public function testRegistryMutationAuditTrailTracksAllOperations(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $trailBefore = count($validator->getRegistryAuditTrail());

        $validator->registerBapDocument([
            'bap_ref'           => 'BAP-TEST-AUDIT-01',
            'student_id'        => 'STD_01',
            'test_id'           => 501,
            'exam_date'         => '2026-11-01',
            'proctor_signature' => 'SIG_01',
        ]);
        $validator->revokeBapDocument('BAP-TEST-AUDIT-01', 'Test revocation');

        $trailAfter = $validator->getRegistryAuditTrail();
        $this->assertCount($trailBefore + 2, $trailAfter);

        $lastRevoke = end($trailAfter);
        $this->assertEquals('REVOKE_BAP', $lastRevoke['action']);
        $this->assertEquals('BAP-TEST-AUDIT-01', $lastRevoke['ref']);
        $this->assertEquals('Test revocation', $lastRevoke['reason']);
    }

    /**
     * Test 9: Client Cannot Inject Arbitrary Grader Authorization (Audit 2).
     */
    public function testClientCannotInjectArbitraryGraderAuthorization(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // Client payload attempts to claim is_authorized = true for non-existent teacher
        $forgedProv = [
            'grader_id'            => 'malicious_actor',
            'is_authorized'        => true, // Client claim
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_AUDIT_2026_01',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
        ];

        $chk = $adapter->verifyGraderProvenance($forgedProv, ['validator' => $validator]);
        $this->assertFalse($chk['is_verified']);
        $this->assertStringContainsString('lacks verified authority', $chk['discrepancy']);
    }

    /**
     * Test 10: Cutover Gate Invariant - Strictly Blocked (0 Resolved, 5 Pending) (Audit 4).
     */
    public function testCutoverGateInvariantStrictlyBlocked(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $summary = $workflow->getSummary();

        $this->assertEquals(0, $summary['resolved_cases']);
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);

        $adapter = new HistoricalSourceAdapter();
        $matrix = $adapter->getCanonicalCaseMatrix();
        $cases = [];
        $evidence = [];
        foreach ($matrix as $id => $data) {
            $cases[] = [
                'case_id'           => $id,
                'category'          => 'ROUNDING_DIFFERENCE',
                'applicable_policy' => $data['current_policy'],
                'student_id'        => $data['student_ref'],
                'test_id'           => (int)$data['test_ref'],
            ];
            $evidence[] = [
                'evidence_id'         => $data['evidence_ref'],
                'related_case_id'     => $id,
                'source_record_id'    => 'cbt_aktivitas:1001',
                'verification_status' => 'UNVERIFIED',
            ];
        }

        $audit = $adapter->auditTraceability($cases, $evidence);
        foreach ($audit as $cId => $row) {
            $this->assertFalse($row['cutover_ready'], "Case {$cId} cutover_ready must be false");
            $this->assertEquals('NOT_READY_FAIL_CLOSED', $row['overall_readiness']);
        }
    }
}
