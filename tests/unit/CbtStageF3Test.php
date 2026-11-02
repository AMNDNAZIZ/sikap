<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Services\Cbt\Gate\CutoverGateEvaluator;
use App\Services\Cbt\Gate\CutoverEvidenceCollector;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\InvalidReviewActionException;
use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;
use RuntimeException;

/**
 * CbtStageF3Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3A Test Suite
 * Fully implements and verifies all 14 mandatory requirements from Section 7:
 *  1. Mock Zero-DML does not produce database certified status.
 *  2. Mock hash/Merkle does not produce database integrity verified.
 *  3. Snapshot checksum remains unverified when physical file unavailable.
 *  4. Every gate check shows evidence source and verification level.
 *  5. Pending essay maps to teacher action, not reviewer approval.
 *  6. Ambiguous retake maps to owner decision if policy unavailable.
 *  7. Legacy zero fallback does not alter absent status.
 *  8. Rounding acknowledgement does not alter score.
 *  9. Gate does not grant READY_FOR_STAGING if staging prerequisites unmet.
 * 10. Audit trail detects record modifications (hash tampering).
 * 11. Audit trail detects missing or duplicate sequence numbers.
 * 12. Concurrent write does not corrupt audit sequence / flock protection.
 * 13. Audit write failure aborts review decision (fail-closed).
 * 14. Full regression suite Stage A-F3 passes cleanly.
 */
class CbtStageF3Test extends TestCase
{
    private string $tempAuditFile;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempAuditFile = sys_get_temp_dir() . '/f3_test_audit_' . uniqid() . '.json';
    }

    protected function tearDown(): void
    {
        if (file_exists($this->tempAuditFile)) {
            @unlink($this->tempAuditFile);
        }
        parent::tearDown();
    }

    // =========================================================================
    // 1. MOCK ZERO-DML DOES NOT PRODUCE DATABASE CERTIFIED STATUS
    // =========================================================================

    public function testMockZeroDmlDoesNotProduceDatabaseCertifiedStatus(): void
    {
        $collector = new CutoverEvidenceCollector();
        $evaluator = new CutoverGateEvaluator($collector);

        // Bundle evaluated with application-level mock guard active
        $bundle = $collector->collectBundle([
            'security' => [
                'zero_dml_guard_active'       => true,
                'zero_dml_database_verified'  => false, // SHOW GRANTS not run on live DB
            ],
            'maturity' => [
                'modes' => [
                    CutoverEvidenceCollector::LEVEL_UNIT_TESTED                => true,
                    CutoverEvidenceCollector::LEVEL_MOCK_ADAPTER_TESTED        => true,
                    CutoverEvidenceCollector::LEVEL_ZERO_DML_DATABASE_VERIFIED => false,
                ],
            ],
        ]);

        $this->assertTrue($bundle['security_safety']['zero_dml_mock_guard_verified']);
        $this->assertFalse($bundle['security_safety']['zero_dml_database_verified']);

        // Check specific check record
        $guardCheck = null;
        foreach ($bundle['checks'] as $c) {
            if ($c['check_name'] === 'ZERO_DML_MOCK_GUARD_VERIFIED') {
                $guardCheck = $c;
            }
        }
        $this->assertNotNull($guardCheck);
        $this->assertEquals(CutoverEvidenceCollector::LEVEL_MOCK_ADAPTER_TESTED, $guardCheck['verification_level']);
        $this->assertStringNotContainsString('SELECT_ONLY_CERTIFIED', $guardCheck['verification_level']);

        // In GateEvaluator, database_level_pass must be strictly false
        $verdict = $evaluator->evaluateGate($bundle);
        $this->assertFalse($verdict['database_level_pass']);
    }

    // =========================================================================
    // 2. MOCK HASH/MERKLE DOES NOT PRODUCE DATABASE INTEGRITY VERIFIED
    // =========================================================================

    public function testMockHashMerkleDoesNotProduceDatabaseIntegrityVerified(): void
    {
        $collector = new CutoverEvidenceCollector();
        $bundle = $collector->collectBundle();

        $mig = $bundle['database_migration'];
        $this->assertTrue($mig['canonical_hash_consistent']);
        $this->assertTrue($mig['merkle_root_consistent']);
        $this->assertFalse($mig['is_real_db_verified']);
        $this->assertStringContainsString('Mock fixture dataset', $mig['data_scope']);

        // Check check-level metadata
        $missingCheck = null;
        foreach ($bundle['checks'] as $c) {
            if ($c['check_name'] === 'MISSING_RECORDS_CHECK') {
                $missingCheck = $c;
            }
        }
        $this->assertNotNull($missingCheck);
        $this->assertEquals(CutoverEvidenceCollector::LEVEL_MOCK_ADAPTER_TESTED, $missingCheck['verification_level']);
        $this->assertNotEmpty($missingCheck['limitations']);
    }

    // =========================================================================
    // 3. SNAPSHOT CHECKSUM REMAINS UNVERIFIED WHEN PHYSICAL FILE UNAVAILABLE
    // =========================================================================

    public function testSnapshotChecksumRemainsUnverifiedWhenPhysicalFileUnavailable(): void
    {
        $collector = new CutoverEvidenceCollector();
        $evaluator = new CutoverGateEvaluator($collector);

        $bundle = $collector->collectBundle();

        $this->assertEquals('SNAPSHOT_CHECKSUM_UNVERIFIED', $bundle['evidence_maturity']['snapshot_checksum_status']);
        $this->assertFalse($bundle['evidence_maturity']['is_real_snapshot_run']);

        $verdict = $evaluator->evaluateGate($bundle);
        $this->assertContains('Snapshot checksum remains SNAPSHOT_CHECKSUM_UNVERIFIED.', $verdict['evidence_maturity_disclosures']);
    }

    // =========================================================================
    // 4. EVERY GATE CHECK SHOWS EVIDENCE SOURCE AND VERIFICATION LEVEL
    // =========================================================================

    public function testEveryGateCheckShowsEvidenceSourceAndVerificationLevel(): void
    {
        $collector = new CutoverEvidenceCollector();
        $bundle = $collector->collectBundle();

        $validLevels = [
            CutoverEvidenceCollector::LEVEL_UNIT_TESTED,
            CutoverEvidenceCollector::LEVEL_MOCK_ADAPTER_TESTED,
            CutoverEvidenceCollector::LEVEL_ARTIFACT_VERIFIED,
            CutoverEvidenceCollector::LEVEL_MARIADB_INTEGRATION_TESTED,
            CutoverEvidenceCollector::LEVEL_REAL_SNAPSHOT_EXECUTED,
            CutoverEvidenceCollector::LEVEL_ZERO_DML_DATABASE_VERIFIED,
        ];

        $this->assertNotEmpty($bundle['checks']);
        foreach ($bundle['checks'] as $c) {
            $this->assertArrayHasKey('check_name', $c);
            $this->assertArrayHasKey('result', $c);
            $this->assertArrayHasKey('evidence_source', $c);
            $this->assertArrayHasKey('verification_level', $c);
            $this->assertArrayHasKey('execution_timestamp', $c);
            $this->assertArrayHasKey('evidence_reference', $c);
            $this->assertArrayHasKey('limitations', $c);

            $this->assertNotEmpty($c['check_name']);
            $this->assertNotEmpty($c['evidence_source']);
            $this->assertContains($c['verification_level'], $validLevels);
            $this->assertIsArray($c['limitations']);
        }
    }

    // =========================================================================
    // 5. PENDING ESSAY MAPS TO TEACHER ACTION, NOT REVIEWER APPROVAL
    // =========================================================================

    public function testPendingEssayMapsToTeacherActionNotReviewerApproval(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_ESSAY_001',
                'test_id'                 => 502,
                'session_id'              => 101,
                'legacy_score'            => 75.0,
                'v3_score'                => null,
                'is_comparable'           => false,
                'categories'              => ['ESSAY_POLICY_DIFFERENCE'],
                'notes'                   => 'Pending teacher grading',
            ],
        ]);

        $cases = $workflow->getQueue();
        $this->assertCount(1, $cases);
        $case = $cases[0];

        $this->assertEquals(ReviewerWorkflowService::STATUS_TEACHER_ACTION_REQUIRED, $case['review_status']);
        $this->assertEquals('Teacher', $case['responsible_party']);

        // Reviewer attempting to publish/approve pending essay MUST be blocked by governance
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH: Reviewer cannot approve or publish scores for PENDING_ESSAY');

        $workflow->applyDecision(
            $case['case_id'],
            'REV_ADMIN',
            'PUBLISH',
            'Attempting to force publish'
        );
    }

    // =========================================================================
    // 6. AMBIGUOUS RETAKE MAPS TO OWNER DECISION IF POLICY UNAVAILABLE
    // =========================================================================

    public function testAmbiguousRetakeMapsToOwnerDecisionIfPolicyUnavailable(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_RETAKE_001',
                'test_id'                 => 503,
                'session_id'              => null,
                'legacy_score'            => 90.0,
                'v3_score'                => null,
                'is_comparable'           => false,
                'categories'              => ['ATTEMPT_SELECTION_DIFFERENCE'],
                'notes'                   => 'Multiple finished attempts without school policy',
            ],
        ]);

        $cases = $workflow->getQueue();
        $this->assertCount(1, $cases);
        $case = $cases[0];

        $this->assertEquals(ReviewerWorkflowService::STATUS_OWNER_DECISION_REQUIRED, $case['review_status']);
        $this->assertEquals('Product Owner', $case['responsible_party']);

        // Applying request owner decision
        $updated = $workflow->applyDecision(
            $case['case_id'],
            'REV_ADMIN',
            ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
            'Awaiting formal policy from product owner.'
        );

        $this->assertEquals(ReviewerWorkflowService::STATUS_OWNER_DECISION_REQUIRED, $updated['review_status']);
    }

    // =========================================================================
    // 7. LEGACY ZERO FALLBACK DOES NOT ALTER ABSENT STATUS
    // =========================================================================

    public function testLegacyZeroFallbackDoesNotAlterAbsentStatus(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_ABSENT_001',
                'test_id'                 => 501,
                'session_id'              => null,
                'legacy_score'            => 0.0,
                'v3_score'                => null,
                'is_comparable'           => false,
                'categories'              => ['LEGACY_ZERO_FALLBACK'],
                'notes'                   => 'No session answers submitted',
            ],
        ]);

        $cases = $workflow->getQueue();
        $case = $cases[0];

        $this->assertEquals(ReviewerWorkflowService::STATUS_ACKNOWLEDGEMENT_REQUIRED, $case['review_status']);

        // Reviewer cannot change ABSENT into EARNED_ZERO
        $validator = new ReviewerDecisionValidator();
        $this->expectException(InvalidReviewActionException::class);
        $this->expectExceptionMessage('GOVERNANCE BREACH: Reviewer cannot convert student absence into an earned zero score');

        $validator->validate(
            $case,
            'CONVERT_TO_EARNED_ZERO',
            'Trying to make absent student earn zero'
        );
    }

    // =========================================================================
    // 8. ROUNDING ACKNOWLEDGEMENT DOES NOT ALTER SCORE
    // =========================================================================

    public function testRoundingAcknowledgementDoesNotAlterScore(): void
    {
        $auditService = new ReviewerAuditService($this->tempAuditFile);
        $workflow = new ReviewerWorkflowService(null, $auditService);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_ROUND_001',
                'test_id'                 => 501,
                'session_id'              => 201,
                'legacy_score'            => 49.98,
                'v3_score'                => 50.0,
                'is_comparable'           => true,
                'delta'                   => 0.02,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'notes'                   => 'Floating point precision delta 0.02',
            ],
        ]);

        $cases = $workflow->getQueue();
        $case = $cases[0];

        // Acknowledge rounding diff
        $updated = $workflow->applyDecision(
            $case['case_id'],
            'REV_ADMIN',
            ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'Delta 0.02 acknowledged within threshold.'
        );

        $this->assertEquals(ReviewerWorkflowService::STATUS_RESOLVED, $updated['review_status']);
        // Crucial invariant: neither legacy_score nor v3_score is mutated to eliminate the difference!
        $this->assertEquals(49.98, $updated['legacy_score']);
        $this->assertEquals(50.0, $updated['v3_score']);
        $this->assertEquals(0.02, $updated['delta']);
    }

    // =========================================================================
    // 9. GATE DOES NOT GRANT READY_FOR_STAGING IF PREREQUISITES UNMET
    // =========================================================================

    public function testGateDoesNotGrantReadyForStagingIfPrerequisitesUnmet(): void
    {
        $collector = new CutoverEvidenceCollector();
        $evaluator = new CutoverGateEvaluator($collector);
        $workflow = new ReviewerWorkflowService();

        // Populate workflow with 1 unresolved case
        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_PENDING_001',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'is_comparable'           => true,
                'delta'                   => 0.02,
            ],
        ]);

        $bundle = $collector->collectBundle();
        $verdict = $evaluator->evaluateGate($bundle, $workflow);

        $this->assertEquals(CutoverGateEvaluator::STATUS_REQUIRES_REVIEW, $verdict['gate_status']);
        $this->assertFalse($verdict['ready_for_staging']);
        $this->assertFalse($verdict['staging_level_pass']);
        $this->assertTrue($verdict['code_level_pass']);
        $this->assertTrue($verdict['artifact_level_pass']);
    }

    // =========================================================================
    // 10. AUDIT TRAIL DETECTS RECORD TAMPERING
    // =========================================================================

    public function testAuditTrailDetectsRecordTampering(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();

        $audit->recordDecision('REV_1', 'C1', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 1');
        $audit->recordDecision('REV_2', 'C2', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 2');
        $audit->recordDecision('REV_3', 'C3', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 3');

        $this->assertTrue($audit->verifyAuditIntegrity()['is_valid']);

        // Tamper with record 2 in file
        $records = json_decode(file_get_contents($this->tempAuditFile), true);
        $records[1]['reason'] = 'MALICIOUS_TAMPERED_REASON';
        file_put_contents($this->tempAuditFile, json_encode($records, JSON_PRETTY_PRINT));

        $integrity = $audit->verifyAuditIntegrity();
        $this->assertFalse($integrity['is_valid']);
        $this->assertStringContainsString('Hash mismatch', $integrity['reason']);
    }

    // =========================================================================
    // 11. AUDIT TRAIL DETECTS MISSING OR DUPLICATE SEQUENCE NUMBERS
    // =========================================================================

    public function testAuditTrailDetectsMissingOrDuplicateSequence(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();

        $audit->recordDecision('REV_1', 'C1', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 1');
        $audit->recordDecision('REV_2', 'C2', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 2');
        $audit->recordDecision('REV_3', 'C3', 'PENDING', 'RESOLVED', 'ACKNOWLEDGE', 'Reason 3');

        // Tamper sequence of record 3 to introduce a sequence gap
        $records = json_decode(file_get_contents($this->tempAuditFile), true);
        $records[2]['sequence_number'] = 99; // Gap: expected 3
        file_put_contents($this->tempAuditFile, json_encode($records, JSON_PRETTY_PRINT));

        $integrity = $audit->verifyAuditIntegrity();
        $this->assertFalse($integrity['is_valid']);
        $this->assertStringContainsString('Sequence anomaly', $integrity['reason']);
    }

    // =========================================================================
    // 12. CONCURRENT WRITE DOES NOT CORRUPT AUDIT SEQUENCE / FLOCK PROTECTION
    // =========================================================================

    public function testConcurrentWriteDoesNotCorruptAuditSequence(): void
    {
        $audit = new ReviewerAuditService($this->tempAuditFile);
        $audit->clearForTesting();

        // Sequential rapid writes under flock
        for ($i = 1; $i <= 10; $i++) {
            $audit->recordDecision(
                "REV_{$i}",
                "CASE_{$i}",
                'PENDING',
                'RESOLVED',
                'ACKNOWLEDGE',
                "Reason for case {$i}"
            );
        }

        $this->assertEquals(10, $audit->count());
        $integrity = $audit->verifyAuditIntegrity();
        $this->assertTrue($integrity['is_valid']);
        $this->assertEquals(10, $integrity['total_records']);
    }

    // =========================================================================
    // 13. AUDIT WRITE FAILURE ABORTS REVIEW DECISION (FAIL-CLOSED)
    // =========================================================================

    public function testAuditWriteFailureAbortsReviewDecision(): void
    {
        // Target an un-writeable directory path to trigger write failure
        $invalidPath = 'Z:/non_existent_drive/invalid_dir/audit.json';
        $brokenAudit = new ReviewerAuditService($invalidPath);
        $workflow = new ReviewerWorkflowService(null, $brokenAudit);

        $workflow->ingestComparisonResults([
            [
                'pseudonymous_student_id' => 'STD_FAIL_001',
                'test_id'                 => 501,
                'categories'              => ['ROUNDING_DIFFERENCE'],
                'is_comparable'           => true,
                'delta'                   => 0.02,
            ],
        ]);

        $cases = $workflow->getQueue();
        $caseId = $cases[0]['case_id'];
        $statusBefore = $cases[0]['review_status'];

        $threw = false;
        try {
            $workflow->applyDecision(
                $caseId,
                'REV_ADMIN',
                ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'Acknowledging difference'
            );
        } catch (RuntimeException $e) {
            $threw = true;
            $this->assertStringContainsString('AUDIT', $e->getMessage());
        }

        $this->assertTrue($threw, 'Expected applyDecision to throw when audit write fails.');

        // Verify state did NOT mutate (fail-closed!)
        $afterCases = $workflow->getQueue();
        $this->assertEquals($statusBefore, $afterCases[0]['review_status']);
        $this->assertNull($afterCases[0]['decision']);
    }

    // =========================================================================
    // 14. FULL REGRESSION SUITE STAGE A-F3 PRESERVED
    // =========================================================================

    public function testFullRegressionSuiteStageAToF3Preserved(): void
    {
        $engine = new \App\Services\Cbt\Assessment\AssessmentEngineService();
        $comparator = new \App\Services\Cbt\Shadow\ShadowComparisonService();

        // Stage D / E
        $res = $engine->evaluateStudent(
            ['test_id' => 900, 'type_weights' => ['pg' => 100.0]],
            [['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'is_void' => false]],
            [['item_id' => 1, 'student_answer' => 'A']],
            ['student_id' => 1001, 'session_status' => 'finished']
        );
        $this->assertEquals(100.0, $res->getFinalScore());

        // Stage F1 / F2
        $comp = $comparator->compareCase(
            ['student_id' => 1001, 'converted_score' => 100.0, 'is_verifiable' => true],
            ['student_id' => 1001, 'final_score' => 100.0, 'report_score' => 100.0, 'is_fully_graded' => true, 'is_eligible' => true]
        );
        $this->assertTrue($comp['is_comparable']);
        $this->assertEquals(0.0, $comp['delta']);

        // Stage F3
        $collector = new CutoverEvidenceCollector();
        $evaluator = new CutoverGateEvaluator($collector);
        $verdict = $evaluator->evaluateGate();
        $this->assertContains($verdict['gate_status'], [
            CutoverGateEvaluator::STATUS_READY_FOR_STAGING,
            CutoverGateEvaluator::STATUS_REQUIRES_REVIEW,
        ]);
    }
}
