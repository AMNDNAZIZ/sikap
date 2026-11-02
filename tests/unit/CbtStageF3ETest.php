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
use PDO;

/**
 * CbtStageF3ETest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3E Test Suite
 * Live Read-Only Evidence Source Verification
 *
 * Covers 12 authoritative source verification & isolation scenarios:
 * 1. Registry VERIFIED tetapi source record tidak ada (SOURCE_NOT_FOUND).
 * 2. Source record ada tetapi identitas siswa berbeda (SOURCE_MISMATCH).
 * 3. Source record berasal dari test berbeda (SOURCE_MISMATCH).
 * 4. Source record berasal dari session berbeda (SOURCE_MISMATCH).
 * 5. Timestamp registry berbeda dari timestamp sumber (SOURCE_MISMATCH / INTEGRITY_FAILURE).
 * 6. Evidence diubah setelah verifikasi (INTEGRITY_FAILURE).
 * 7. Owner approval hanya berupa teks tanpa dokumen sumber (REGISTRY_ONLY).
 * 8. Proktor tidak terdaftar atau tidak berwenang (UNAUTHORIZED_ACTOR / INSUFFICIENT_EVIDENCE).
 * 9. Penugasan guru tidak aktif / tidak cocok (UNAUTHORIZED_ACTOR).
 * 10. Nilai desimal mengalami konversi float di jalur data (PRECISION_LOSS).
 * 11. Source database tidak tersedia (SOURCE_UNAVAILABLE).
 * 12. Preview dan source verification tidak mengubah data operasional (Non-mutation).
 */
class CbtStageF3ETest extends TestCase
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
     * Helper to create an in-memory SQLite PDO database simulating CBT source schema.
     */
    private function createMockSourceDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("
            CREATE TABLE cbt_sessions (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                test_id INTEGER,
                score TEXT,
                essay_score TEXT,
                total_score TEXT,
                started_at INTEGER,
                finished_at INTEGER,
                created_at TEXT
            );
        ");

        $pdo->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, started_at, finished_at, created_at)
            VALUES (1002, 2, 501, '49.98', NULL, '49.98', 1770905999, 1770907000, '2026-11-01 06:00:00');
        ");

        return $pdo;
    }

    /**
     * Test 1: Registry VERIFIED tetapi source record tidak ada (SOURCE_NOT_FOUND).
     */
    public function testRegistryVerifiedButSourceRecordNotFound(): void
    {
        $mockDb = $this->createMockSourceDb();
        $verifier = new ReviewerSourceVerifier($mockDb);

        // Evidence points to non-existent table cbt_aktivitas or non-existent record
        $evidence = [
            'evidence_id'         => 'EVD_TEST_01',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_sessions:9999', // Non-existent ID in cbt_sessions
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
        ];

        $case = [
            'case_id'    => 'REV_501_2b6231cb77',
            'student_id' => 2,
            'test_id'    => 501,
        ];

        $result = $verifier->verifyEvidenceRecord($evidence, $case);

        $this->assertEquals(ReviewerSourceVerifier::STATUS_SOURCE_NOT_FOUND, $result['source_status']);
        $this->assertFalse($result['is_source_verified']);
        $this->assertStringContainsString("Record ID '9999' does not exist", $result['discrepancy']);
    }

    /**
     * Test 2: Source record ada tetapi identitas siswa berbeda (SOURCE_MISMATCH).
     */
    public function testSourceRecordExistsButStudentIdMismatched(): void
    {
        $mockDb = $this->createMockSourceDb();
        $verifier = new ReviewerSourceVerifier($mockDb);

        $evidence = [
            'evidence_id'         => 'EVD_TEST_02',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_OPERATIONAL',
            'source_record_id'    => 'cbt_sessions:1002', // In DB, student_id = 2
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
        ];

        // Case claims student_id = 99 (mismatch with DB student_id 2)
        $case = [
            'case_id'    => 'REV_501_2b6231cb77',
            'student_id' => 99,
            'test_id'    => 501,
        ];

        $result = $verifier->verifyEvidenceRecord($evidence, $case);

        $this->assertEquals(ReviewerSourceVerifier::STATUS_SOURCE_MISMATCH, $result['source_status']);
        $this->assertFalse($result['is_source_verified']);
        $this->assertStringContainsString('Student mismatch', $result['discrepancy']);
    }

    /**
     * Test 3: Source record berasal dari test berbeda (SOURCE_MISMATCH).
     */
    public function testSourceRecordBelongsToDifferentTest(): void
    {
        $mockDb = $this->createMockSourceDb();
        $verifier = new ReviewerSourceVerifier($mockDb);

        $evidence = [
            'evidence_id'         => 'EVD_TEST_03',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_OPERATIONAL',
            'source_record_id'    => 'cbt_sessions:1002', // In DB, test_id = 501
            'related_case_id'     => 'REV_502_3ff144b11c',
            'related_test_id'     => 502,
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
        ];

        // Case claims test_id = 502 (mismatch with DB test_id 501)
        $case = [
            'case_id'    => 'REV_502_3ff144b11c',
            'student_id' => 2,
            'test_id'    => 502,
        ];

        $result = $verifier->verifyEvidenceRecord($evidence, $case);

        $this->assertEquals(ReviewerSourceVerifier::STATUS_SOURCE_MISMATCH, $result['source_status']);
        $this->assertFalse($result['is_source_verified']);
        $this->assertStringContainsString('Test mismatch', $result['discrepancy']);
    }

    /**
     * Test 4: Source record berasal dari session berbeda (SOURCE_MISMATCH).
     */
    public function testSourceRecordBelongsToDifferentSession(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $evidenceValidator->registerEvidence([
            'evidence_id'         => 'EVD_MISMATCH_SESS',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1001',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'related_session_id'  => 1001, // Case is session 1002!
            'subject_reference'   => 'Matematika',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'metadata'            => ['student_id' => 'STD_9B40210CA3F5'],
        ]);

        $workflow = new ReviewerWorkflowService(null, null, $evidenceValidator);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Attempting preview with session mismatch',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Cross-session contamination', $preview['error']);
    }

    /**
     * Test 5: Timestamp registry berbeda dari timestamp sumber (anomali masa depan).
     */
    public function testTimestampDiscrepancyBetweenRegistryAndSource(): void
    {
        $evidenceValidator = new ReviewerEvidenceValidator();
        $evidenceValidator->registerEvidence([
            'evidence_id'         => 'EVD_FUTURE_ANOMALY',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1002',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'related_session_id'  => 1002,
            'subject_reference'   => 'Matematika',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2099-01-01T00:00:00Z', // Inconsistent future timestamp
            'metadata'            => ['student_id' => 'STD_9B40210CA3F5'],
        ]);

        $workflow = new ReviewerWorkflowService(null, null, $evidenceValidator);
        $workflow->loadFromShadowDeviations($this->deviationsCsv);

        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing future timestamp anomaly',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Temporal anomaly', $preview['error']);
    }

    /**
     * Test 6: Evidence diubah setelah verifikasi (INTEGRITY_FAILURE).
     */
    public function testEvidenceTamperedAfterVerification(): void
    {
        $workflow = $this->createLoadedWorkflow();

        // Client supplies delta = 0.01 while source scores are 49.98 and 50.00 (delta = 0.02)
        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Tampered client delta test',
            [
                'actor_role'   => 'REVIEWER',
                'decision_id'  => 'DEC-ROUND-01',
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.01', // Tampered delta contradicting source!
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('contradicts recomputed source delta', $preview['error']);
    }

    /**
     * Test 7: Owner approval hanya berupa teks tanpa dokumen sumber (REGISTRY_ONLY / INVALID_EVIDENCE).
     */
    public function testOwnerApprovalTextOnlyWithoutSourceDocument(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_503_4266a4ed7f'; // AMBIGUOUS_RETAKE

        // Submitting an arbitrary text string not found in formal decree registry
        $preview = $workflow->previewDecision(
            $caseId,
            'principal_user',
            ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
            'Selecting session with unbacked text decree',
            [
                'actor_role'          => 'PRINCIPAL',
                'decision_id'         => 'DEC-RET-01',
                'selected_session_id' => '1008',
                'owner_approval_ref'  => 'SURAT-LISAN-KEPSEK-01', // Free-text without decree backing
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INVALID_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('Reference cannot be verified against formal Product Owner registry', $preview['error']);
    }

    /**
     * Test 8: Proktor tidak terdaftar atau tidak berwenang (INSUFFICIENT_EVIDENCE).
     */
    public function testProctorUnregisteredOrUnauthorized(): void
    {
        $workflow = $this->createLoadedWorkflow();

        // Absent case without signed proctor attendance sheet
        $preview = $workflow->previewDecision(
            'REV_501_7b48bc8a1f',
            'operator_reviewer',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Testing absence confirmation without valid proctor evidence',
            [
                'actor_role'              => 'REVIEWER',
                'decision_id'             => 'DEC-ABS-01',
                'evidence_verified'       => true,
                'enforce_strict_registry' => true,
            ]
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_INSUFFICIENT_EVIDENCE, $preview['readiness_status']);
        $this->assertStringContainsString('requires authentic proctor-signed attendance record', $preview['error']);
    }

    /**
     * Test 9: Penugasan guru tidak aktif / tidak cocok (UNAUTHORIZED_ACTOR).
     */
    public function testTeacherAssignmentInactiveOrExpired(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $caseId = 'REV_502_3ff144b11c'; // Bahasa Indonesia Test ID 502

        // guru_matematika is assigned to 501, not 502
        $preview = $workflow->previewDecision(
            $caseId,
            'guru_matematika',
            ReviewerDecisionValidator::ACTION_FLAG_TEACHER_ACTION,
            'Teacher out-of-subject evaluation attempt',
            ['actor_role' => 'TEACHER', 'decision_id' => 'DEC-ESS-01']
        );

        $this->assertFalse($preview['is_valid']);
        $this->assertEquals(ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR, $preview['readiness_status']);
        $this->assertStringContainsString('Subject teacher authority mismatch', $preview['error']);
    }

    /**
     * Test 10: Nilai desimal mengalami konversi float di jalur data (PRECISION_LOSS).
     */
    public function testDecimalPrecisionEndToEndNoFloatLoss(): void
    {
        $verifier = new ReviewerSourceVerifier();
        $audit = $verifier->auditDecimalPrecisionPipeline();

        $this->assertTrue($audit['is_all_precision_checks_passed'], 'All exact decimal checks must pass.');
        $this->assertTrue($audit['checks']['delta_0_05_accepted']);
        $this->assertTrue($audit['checks']['micro_deviation_0_0500000000001_rejected']);
        $this->assertTrue($audit['checks']['negative_0_05_accepted_via_abs']);
        $this->assertTrue($audit['checks']['negative_micro_deviation_rejected']);
        $this->assertTrue($audit['checks']['recompute_from_source_scores']);
        $this->assertTrue($audit['checks']['client_delta_mismatch_detected']);
        $this->assertTrue($audit['checks']['scientific_notation_requires_normalization']);
        $this->assertTrue($audit['checks']['non_numeric_format_rejected']);
    }

    /**
     * Test 11: Source database tidak tersedia (SOURCE_UNAVAILABLE / REGISTRY_ONLY).
     */
    public function testSourceDatabaseUnavailable(): void
    {
        // Null PDO simulates offline / unmounted source database
        $verifier = new ReviewerSourceVerifier(null);
        $envAudit = $verifier->auditEnvironment();

        $this->assertFalse($envAudit['is_connected']);
        $this->assertFalse($envAudit['is_read_only_guaranteed']);
        $this->assertStringContainsString('BLOCKED', $envAudit['status']);

        // Verifying an evidence record when DB is null returns REGISTRY_ONLY
        $result = $verifier->verifyEvidenceRecord([
            'evidence_id'         => 'EVD_501_SCORE_01',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1002',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
        ], ['case_id' => 'REV_501_2b6231cb77']);

        $this->assertEquals(ReviewerSourceVerifier::STATUS_REGISTRY_ONLY, $result['source_status']);
        $this->assertFalse($result['is_source_verified']);
    }

    /**
     * Test 12: Preview dan source verification tidak mengubah data operasional (Non-mutation).
     */
    public function testPreviewAndSourceVerificationAreStrictlyNonMutating(): void
    {
        $workflow = $this->createLoadedWorkflow();
        $casesBefore = $workflow->getQueue();

        $verifier = new ReviewerSourceVerifier();
        $precisionAudit = $verifier->auditDecimalPrecisionPipeline();
        $this->assertTrue($precisionAudit['is_all_precision_checks_passed']);

        // Run preview on case
        $preview = $workflow->previewDecision(
            'REV_501_2b6231cb77',
            'reviewer_01',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Non-mutation verification test',
            ['actor_role' => 'REVIEWER', 'decision_id' => 'DEC-ROUND-01']
        );
        $this->assertTrue($preview['is_valid']);

        $casesAfter = $workflow->getQueue();

        $this->assertEquals($casesBefore, $casesAfter, 'Queue must remain 100% identical after preview and verification.');
        $summary = $workflow->getSummary();
        $this->assertEquals(0, $summary['resolved_cases'], 'Zero cases may be resolved.');
        $this->assertEquals(5, $summary['pending_cases']);
        $this->assertFalse($summary['is_all_cleared_for_staging']);
    }
}

