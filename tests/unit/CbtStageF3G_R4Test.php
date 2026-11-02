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
 * CbtStageF3G_R4Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R4
 * Canonical Case Registry Integrity, Evidence Authenticity & Source Relationship Reconciliation.
 */
class CbtStageF3G_R4Test extends TestCase
{
    private function createInMemoryPdo(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("
            CREATE TABLE cbt_sessions (
                id INTEGER PRIMARY KEY,
                student_id INTEGER NOT NULL,
                test_id INTEGER NOT NULL,
                score REAL,
                essay_score REAL,
                total_score REAL,
                status TEXT,
                started_at TEXT,
                finished_at TEXT,
                created_at TEXT,
                updated_at TEXT
            );

            CREATE TABLE cbt_answers (
                id INTEGER PRIMARY KEY,
                student_id INTEGER NOT NULL,
                test_id INTEGER NOT NULL,
                question_id INTEGER NOT NULL,
                answer TEXT,
                score REAL,
                updated_at_micro TEXT,
                version INTEGER DEFAULT 1,
                is_doubtful INTEGER DEFAULT 0,
                updated_at TEXT
            );

            CREATE TABLE cbt_questions (
                id INTEGER PRIMARY KEY,
                test_id INTEGER NOT NULL,
                question_type INTEGER NOT NULL,
                content TEXT
            );
        ");

        return $pdo;
    }

    /**
     * Test 1: Canonical Case Registry Matrix & Conflict Resolution.
     */
    public function testCanonicalCaseRegistryMatrixAndConflictResolution(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $matrix = $adapter->getCanonicalCaseMatrix();

        $this->assertCount(5, $matrix);

        // Verify the 5 canonical IDs
        $expectedIds = [
            'REV_501_2b6231cb77',
            'REV_501_7b48bc8a1f',
            'REV_502_3ff144b11c',
            'REV_503_4266a4ed7f',
            'REV_503_fc68cd64f2',
        ];
        $this->assertEquals($expectedIds, array_keys($matrix));

        // Verify REV_503_fc68cd64f2 resolution
        $case5 = $matrix['REV_503_fc68cd64f2'];
        $this->assertEquals('REV_503_fc68cd64f2', $case5['canonical_id']);
        $this->assertEquals('DEC-ABS-01', $case5['current_policy']);
        $this->assertEquals('shadow_deviations.csv', $case5['source_artifact']);
        $this->assertEquals(8, $case5['source_row']);
        $this->assertEquals('STD_7B5939E895CC', $case5['student_ref']);
        $this->assertEquals('503', $case5['test_ref']);
        $this->assertStringContainsString('CASE_IDENTITY_CONFLICT', $case5['identity_conflict']);
        $this->assertEquals('RECONCILED_TO_DEC_ABS_01', $case5['resolution_status']);

        // Verify auditTraceability flags CASE_IDENTITY_CONFLICT if conflicting category is passed
        $casesWithConflict = [
            [
                'case_id'           => 'REV_503_fc68cd64f2',
                'category'          => 'AMBIGUOUS_RETAKE', // Conflicting alias from erroneous R3 table
                'applicable_policy' => 'DEC-RET-01',
                'student_id'        => 'STD_7B5939E895CC',
                'test_id'           => 503,
                'session_id'        => 1009,
            ],
        ];
        $evList = [
            [
                'evidence_id'         => 'EVD_503_SESS_02',
                'related_case_id'     => 'REV_503_fc68cd64f2',
                'source_record_id'    => 'cbt_aktivitas:1009',
                'verification_status' => 'UNVERIFIED',
            ],
        ];
        $audit = $adapter->auditTraceability($casesWithConflict, $evList);
        $this->assertEquals(HistoricalSourceAdapter::REASON_CASE_IDENTITY_CONFLICT, $audit['REV_503_fc68cd64f2']['reason_code']);
    }

    /**
     * Test 2: Numeric Primary-Key Coincidence Disambiguation (Session ID 1006 vs Answer ID 1006).
     */
    public function testNumericPrimaryKeyCoincidenceDisambiguation(): void
    {
        $pdo = $this->createInMemoryPdo();
        $adapter = new HistoricalSourceAdapter($pdo);

        // Session 1006 belongs to Student 101, Test 502
        $pdo->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, status) VALUES (1006, 101, 502, 100.0, NULL, 'finished')");

        // Answer 1006 belongs to DIFFERENT Student 999, Test 888 (Numeric PK Coincidence!)
        $pdo->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, score) VALUES (1006, 999, 888, 1, 50.0)");

        // Legitimate answer for student 101, test 502
        $pdo->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, score) VALUES (2001, 101, 502, 10, NULL)");
        $pdo->exec("INSERT INTO cbt_questions (id, test_id, question_type) VALUES (10, 502, 2)");

        // Resolve essay evidence for Student 101 with historical reference cbt_soal_siswa:1006
        $evidence = [
            'evidence_id'         => 'EVD_502_ESSAY_01',
            'source_record_id'    => 'cbt_soal_siswa:1006',
            'related_case_id'     => 'REV_502_3ff144b11c',
            'related_test_id'     => 502,
            'metadata'            => ['student_id' => 101],
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $pdo, ['student_id' => 101, 'test_id' => 502]);

        $this->assertTrue($res['is_verified']);
        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VERIFIED, $res['adapter_status']);
        $this->assertTrue($res['operational_data']['numeric_coincidence_prevented']);
        $this->assertEquals('RESOLVED_VIA_PARENT_SESSION', $res['operational_data']['semantic_resolution']);
        // Ensure Answer 1006 belonging to Student 999 was NOT erroneously bound
        $this->assertNotEquals(50.0, $res['operational_data']['score']);
    }

    /**
     * Test 3: Session Context Match is Never Auto-Promoted to Verified Ownership.
     */
    public function testSessionContextMatchNeverAutoPromotedToVerified(): void
    {
        $pdo = $this->createInMemoryPdo();
        $adapter = new HistoricalSourceAdapter($pdo);

        $pdo->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score) VALUES (1001, 201, 501, 80.0)");
        $pdo->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, score) VALUES (3001, 201, 501, 1, 80.0)");

        $audit = $adapter->evaluateSessionOwnership(1001, 201, 501, $pdo);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_CONTEXT_MATCH, $audit['ownership_status']);
        $this->assertFalse($audit['is_verified']);
        $this->assertStringContainsString('ownership remains unproven', $audit['discrepancy']);
    }

    /**
     * Test 4: Unregistered or Unassigned Teacher is Strictly Unauthorized (Fail-Closed).
     */
    public function testUnregisteredOrUnassignedTeacherFailClosed(): void
    {
        $validator = new ReviewerEvidenceValidator();

        // 4.A: Unregistered teacher username returns false
        $this->assertFalse($validator->isTeacherAssignedToTest('unknown_teacher', 501));

        // 4.B: Registered teacher for 502 (guru_bindo) attempting 501 returns false
        $this->assertFalse($validator->isTeacherAssignedToTest('guru_bindo', 501));

        // 4.C: Registered teacher for 502 (guru_bindo) on 502 returns true
        $this->assertTrue($validator->isTeacherAssignedToTest('guru_bindo', 502));

        // 4.D: evaluateReadiness rejects unassigned teacher with READINESS_UNAUTHORIZED_ACTOR
        $case = [
            'case_id'                  => 'REV_502_3ff144b11c',
            'test_id'                  => 502,
            'category'                 => 'PENDING_ESSAY',
            'pseudonymous_student_id'  => 'STD_A1C048B42B28',
            'subject_name'             => 'Bahasa Indonesia',
        ];
        $actorUnassigned = [
            'username' => 'guru_matematika', // Assigned to 501, not 502
            'role'     => 'TEACHER',
        ];

        $res = $validator->evaluateReadiness($case, ReviewerDecisionValidator::ACTION_FLAG_TEACHER_ACTION, $actorUnassigned);
        $this->assertFalse($res['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR, $res['readiness_status']);
        $this->assertStringContainsString('is not assigned to Test ID 502', $res['rejection_reasons'][0]);
    }

    /**
     * Test 5: Grader Provenance Authoritative Validation Against Registry.
     */
    public function testGraderProvenanceAuthoritativeValidationAgainstRegistry(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // 5.A: Grader with unauthenticated change log reference fails closed
        $provUnauthLog = [
            'grader_id'            => 'guru_bindo',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'FAKE_LOG_999',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
        ];
        $chk1 = $adapter->verifyGraderProvenance($provUnauthLog, ['validator' => $validator]);
        $this->assertFalse($chk1['is_verified']);
        $this->assertStringContainsString('unauthenticated in official grading audit registry', $chk1['discrepancy']);

        // 5.B: Grader with teacher not assigned to test fails closed
        $provUnauthTeacher = [
            'grader_id'            => 'guru_matematika', // Not assigned to 502
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_AUDIT_2026_01',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
        ];
        $chk2 = $adapter->verifyGraderProvenance($provUnauthTeacher, ['validator' => $validator]);
        $this->assertFalse($chk2['is_verified']);
        $this->assertStringContainsString('lacks verified authority', $chk2['discrepancy']);

        // 5.C: Fully authenticated grader provenance passes
        $provAuth = [
            'grader_id'            => 'guru_bindo',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_AUDIT_2026_01',
            'student_id'           => 'STD_A1C048B42B28',
            'test_id'              => 502,
        ];
        $chk3 = $adapter->verifyGraderProvenance($provAuth, ['validator' => $validator]);
        $this->assertTrue($chk3['is_verified']);
        $this->assertNull($chk3['discrepancy']);
    }

    /**
     * Test 6: Absence Evidence Authoritative Validation Against BAP Registry.
     */
    public function testAbsenceEvidenceAuthoritativeValidationAgainstBapRegistry(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $adapter = new HistoricalSourceAdapter();

        // 6.A: Caller payload claiming is_bap_signed = true with unauthenticated bap_number fails closed
        $fakeBap = [
            'bap_document' => [
                'proctor_id'           => 'PRC_01',
                'is_authorized_proctor'=> true,
                'is_bap_signed'        => true,
                'bap_number'           => 'BAP_FORGED_999',
                'exam_date'            => '2026-11-01',
                'student_id'           => 'STD_7B5939E895CC',
                'test_id'              => 503,
            ],
        ];
        $chk1 = $adapter->verifyAbsenceEvidence($fakeBap, ['validator' => $validator]);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED, $chk1['status']);
        $this->assertFalse($chk1['is_verified']);
        $this->assertStringContainsString('not authenticated in official school BAP registry', $chk1['discrepancy']);

        // 6.B: Official authenticated BAP passes
        $authBap = [
            'bap_document' => [
                'proctor_id'           => 'PRC_01',
                'is_authorized_proctor'=> true,
                'is_bap_signed'        => true,
                'bap_number'           => 'BAP-AUTH-2026-001',
                'exam_date'            => '2026-11-01',
                'student_id'           => 'STD_7B5939E895CC',
                'test_id'              => 503,
            ],
        ];
        $chk2 = $adapter->verifyAbsenceEvidence($authBap, ['validator' => $validator]);
        $this->assertEquals(HistoricalSourceAdapter::ABSENCE_STATUS_VERIFIED, $chk2['status']);
        $this->assertTrue($chk2['is_verified']);
        $this->assertNull($chk2['discrepancy']);
    }

    /**
     * Test 7: Standardized 6 Dimensions & Granular Reason Codes in Audit Matrix.
     */
    public function testStandardizedSixDimensionsAndReasonCodesInAuditMatrix(): void
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
                'case_id'           => 'REV_503_4266a4ed7f',
                'category'          => 'AMBIGUOUS_RETAKE',
                'applicable_policy' => 'DEC-RET-01',
                'student_id'        => 'STD_C08503E2E9EE',
                'test_id'           => 503,
                'session_id'        => 1008,
            ],
            [
                'case_id'           => 'REV_503_fc68cd64f2',
                'category'          => 'LEGACY_ZERO_FALLBACK',
                'applicable_policy' => 'DEC-ABS-01',
                'student_id'        => 'STD_7B5939E895CC',
                'test_id'           => 503,
                'session_id'        => 1009,
            ],
        ];

        $evList = [
            [
                'evidence_id'         => 'EVD_501_ROUND_01',
                'related_case_id'     => 'REV_501_2b6231cb77',
                'source_record_id'    => 'cbt_aktivitas:1001',
                'verification_status' => 'VERIFIED',
            ],
            [
                'evidence_id'         => 'EVD_503_SESS_01',
                'related_case_id'     => 'REV_503_4266a4ed7f',
                'source_record_id'    => 'cbt_aktivitas:1007_1008',
                'verification_status' => 'VERIFIED',
            ],
            [
                'evidence_id'         => 'EVD_503_SESS_02',
                'related_case_id'     => 'REV_503_fc68cd64f2',
                'source_record_id'    => 'cbt_aktivitas:1009',
                'verification_status' => 'UNVERIFIED',
            ],
        ];

        $audit = $adapter->auditTraceability($cases, $evList);

        // Verify the 6 dimension keys exist
        $sixDimensions = [
            'policy_decision_ready',
            'record_identity_ready',
            'session_ownership_ready',
            'evidence_ready',
            'ready_for_authorized_review',
            'cutover_ready',
        ];
        foreach ($sixDimensions as $dim) {
            $this->assertArrayHasKey($dim, $audit['REV_501_2b6231cb77']);
            $this->assertArrayHasKey($dim, $audit['REV_503_4266a4ed7f']);
            $this->assertArrayHasKey($dim, $audit['REV_503_fc68cd64f2']);
        }

        // Verify reason codes
        $this->assertEquals(HistoricalSourceAdapter::REASON_POLICY_APPROVAL_MISSING, $audit['REV_503_4266a4ed7f']['reason_code']);
        $this->assertEquals(HistoricalSourceAdapter::REASON_BAP_NOT_AUTHENTICATED, $audit['REV_503_fc68cd64f2']['reason_code']);
    }

    /**
     * Test 8: Client Cannot Spoof BAP Verification in DEC-ABS-01.
     */
    public function testClientCannotSpoofBapVerificationWithoutRegistry(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $case = [
            'case_id'                  => 'REV_501_7b48bc8a1f',
            'test_id'                  => 501,
            'category'                 => 'LEGACY_ZERO_FALLBACK',
            'pseudonymous_student_id'  => 'STD_5D7653457A88',
        ];
        $actor = [
            'username' => 'operator_reviewer',
            'role'     => 'REVIEWER',
        ];

        // Caller attempts to pass bap_ref that does NOT exist in official registry
        $params = [
            'decision_id'             => 'DEC-ABS-01',
            'bap_ref'                 => 'BAP-FORGED-001',
            'evidence_verified'       => true,
            'enforce_strict_registry' => true,
        ];

        $res = $validator->evaluateReadiness($case, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, $actor, $params);
        $this->assertFalse($res['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $res['readiness_status']);
        $this->assertStringContainsString('official BAP registry', $res['rejection_reasons'][0]);
    }

    /**
     * Test 9: Unauthorized Roles Strictly Blocked from Reviewer Workflow.
     */
    public function testUnauthorizedRolesStrictlyBlocked(): void
    {
        $validator = new ReviewerEvidenceValidator();
        $case = [
            'case_id'                  => 'REV_501_2b6231cb77',
            'test_id'                  => 501,
            'category'                 => 'ROUNDING_DIFFERENCE',
            'pseudonymous_student_id'  => 'STD_2434EBBEFF9A',
        ];

        foreach (['STUDENT', 'GUEST', 'UNAUTHORIZED'] as $badRole) {
            $actor = ['username' => 'malicious_user', 'role' => $badRole];
            $res = $validator->evaluateReadiness($case, ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF, $actor);
            $this->assertFalse($res['is_valid']);
            $this->assertEquals(ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR, $res['readiness_status']);
        }
    }

    /**
     * Test 10: Cutover Strictly Blocked for All Cases (Gate Remains REQUIRES_REVIEW).
     */
    public function testCutoverStrictlyBlockedForAllCases(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $matrix = $adapter->getCanonicalCaseMatrix();

        $cases = [];
        $evidence = [];
        foreach ($matrix as $id => $data) {
            $cases[] = [
                'case_id'           => $id,
                'category'          => $data['original_policy'] === 'DEC-ROUND-01' ? 'ROUNDING_DIFFERENCE' : ($data['original_policy'] === 'DEC-ESS-01' ? 'PENDING_ESSAY' : ($data['original_policy'] === 'DEC-RET-01' ? 'AMBIGUOUS_RETAKE' : 'LEGACY_ZERO_FALLBACK')),
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

        foreach ($audit as $caseId => $res) {
            $this->assertFalse($res['cutover_ready'], "Case {$caseId} must have cutover_ready = false");
            $this->assertEquals('NOT_READY_FAIL_CLOSED', $res['overall_readiness']);
        }
    }
}
