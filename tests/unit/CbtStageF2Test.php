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
use RuntimeException;
use App\Services\Cbt\Shadow\ZeroDmlGuard;
use App\Services\Cbt\Shadow\ZeroDmlViolationException;
use App\Services\Cbt\Shadow\IsolatedSnapshotRunner;

/**
 * CbtStageF2Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F2 Test Suite
 * Verifies Isolated Real-Data Shadow Execution Pipeline:
 * 1. Zero-DML Guard & Query Inspection Firewall
 * 2. SELECT-Only Privilege Verification & Production Rejection
 * 3. Multi-Subject Execution & Historical Coverage Auditing
 * 4. Numeric Error Statistics (MAE, RMSE, Median, 90th Percentile)
 * 5. Generation of 6 Mandatory Stage F2 Output Artifacts
 * 6. Zero PII Scrubbing Across All Artifacts
 * 7. Memory-Safe Batch Chunking
 */
class CbtStageF2Test extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ZeroDmlGuard::resetAudit();
    }

    // =========================================================================
    // 1. ZERO-DML FIREWALL & STATEMENT AUDITING
    // =========================================================================

    public function testZeroDmlGuardPermitsReadOnlyStatements(): void
    {
        $this->assertTrue(ZeroDmlGuard::assertReadOnly("SELECT * FROM cbt_sessions WHERE id = 1"));
        $this->assertTrue(ZeroDmlGuard::assertReadOnly("SHOW TABLES LIKE 'cbt_%'"));
        $this->assertTrue(ZeroDmlGuard::assertReadOnly("DESCRIBE cbt_answers"));
        $this->assertTrue(ZeroDmlGuard::assertReadOnly("EXPLAIN SELECT s.id FROM students s"));
    }

    public function testZeroDmlGuardBlocksMutativeDmlAndDdl(): void
    {
        $blockedDml = [
            "INSERT INTO cbt_sessions (student_id) VALUES (1)",
            "UPDATE cbt_sessions SET score = 100 WHERE id = 1",
            "DELETE FROM cbt_answers WHERE id = 1",
            "REPLACE INTO cbt_answers (id) VALUES (1)",
            "DROP TABLE cbt_sessions",
            "ALTER TABLE cbt_sessions ADD COLUMN hack VARCHAR(10)",
            "TRUNCATE TABLE cbt_answers",
            "GRANT ALL ON *.* TO 'attacker'@'%'",
        ];

        foreach ($blockedDml as $sql) {
            try {
                ZeroDmlGuard::assertReadOnly($sql);
                $this->fail("Expected ZeroDmlViolationException was not thrown for statement: {$sql}");
            } catch (ZeroDmlViolationException $e) {
                $this->assertStringContainsString("ZERO-DML VIOLATION BLOCKED", $e->getMessage());
            }
        }
    }

    // =========================================================================
    // 2. PRIVILEGE VERIFICATION & PRODUCTION REJECTION
    // =========================================================================

    public function testPrivilegeVerificationEnforcesSelectOnly(): void
    {
        // Safe read-only grant
        $safeGrants = ["GRANT SELECT ON `cbt_snapshot`.* TO 'cbt_reader'@'localhost'"];
        $safeCheck = ZeroDmlGuard::verifySelectOnlyPrivilege($safeGrants);
        $this->assertTrue($safeCheck['is_select_only']);
        $this->assertEmpty($safeCheck['violations']);

        // Dangerous mutative grants
        $unsafeGrants = [
            "GRANT ALL PRIVILEGES ON *.* TO 'root'@'localhost'",
            "GRANT SELECT, INSERT, UPDATE ON `siakad`.* TO 'app_user'@'%'",
        ];
        $unsafeCheck = ZeroDmlGuard::verifySelectOnlyPrivilege($unsafeGrants);
        $this->assertFalse($unsafeCheck['is_select_only']);
        $this->assertNotEmpty($unsafeCheck['violations']);
    }

    public function testEnvironmentAuditBlocksProduction(): void
    {
        $runner = new IsolatedSnapshotRunner();

        $prodEnv = [
            'database_name' => 'cbt_production',
            'is_production' => true,
            'grants'        => ["GRANT SELECT ON `cbt_production`.* TO 'cbt_reader'@'localhost'"],
        ];

        $audit = $runner->auditEnvironment($prodEnv);
        $this->assertFalse($audit['is_ready']);
        $this->assertTrue($audit['is_production_blocked']);
    }

    // =========================================================================
    // 3. STATISTICAL ERROR METRICS (MAE, RMSE, MEDIAN, 90th PERCENTILE)
    // =========================================================================

    public function testStatisticalMetricsCalculation(): void
    {
        $runner = new IsolatedSnapshotRunner();

        // 5 comparable cases with deltas: |0.0|, |1.0|, |2.0|, |3.0|, |4.0|
        $cases = [
            ['is_comparable' => true, 'delta' => 0.0],
            ['is_comparable' => true, 'delta' => 1.0],
            ['is_comparable' => true, 'delta' => -2.0], // abs 2.0
            ['is_comparable' => true, 'delta' => 3.0],
            ['is_comparable' => true, 'delta' => -4.0], // abs 4.0
            // Non-comparable cases (must be ignored from denominators)
            ['is_comparable' => false, 'delta' => null],
            ['is_comparable' => false, 'delta' => 0.0],
        ];

        $stats = $runner->calculateNumericMetrics($cases);

        // Sum = 0 + 1 + 2 + 3 + 4 = 10 / 5 = 2.0
        $this->assertEquals(5, $stats['comparable_count']);
        $this->assertEquals(2.0, $stats['mae']);

        // Squared sum = 0 + 1 + 4 + 9 + 16 = 30 / 5 = 6.0 -> sqrt(6) = 2.4495
        $this->assertEquals(2.4495, $stats['rmse']);

        // Median of [0, 1, 2, 3, 4] is 2.0
        $this->assertEquals(2.0, $stats['median_absolute_error']);

        // 90th percentile of 5 items: ceil(0.9 * 5) - 1 = 4 -> index 4 is 4.0
        $this->assertEquals(4.0, $stats['percentile_90_absolute_error']);
        $this->assertEquals(4.0, $stats['max_absolute_error']);
    }

    // =========================================================================
    // 4. MULTI-SUBJECT SHADOW RUN & 6 ARTIFACTS VERIFICATION
    // =========================================================================

    public function testMultiSubjectExecutionGeneratesAllSixArtifacts(): void
    {
        $tempArtifactDir = sys_get_temp_dir() . '/espata_stage_f2_test_' . uniqid();
        $runner = new IsolatedSnapshotRunner(null, null, null, null, $tempArtifactDir, 'TEST_SALT');

        // Multi-subject representative dataset
        $dataset = [
            'subjects' => [
                // Subject 1: Matematika (PG + Void Question)
                [
                    'subject_id'   => 1,
                    'subject_name' => 'Matematika',
                    'test_config'  => [
                        'test_id'      => 101,
                        'type_weights' => ['pg' => 100.0],
                        'component_type' => 'pts',
                    ],
                    'items' => [
                        ['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'is_void' => false],
                        ['item_id' => 2, 'type' => 'pg', 'key' => 'B', 'is_void' => true], // Void question
                    ],
                    'student_cases' => [
                        // Case A: Finished session
                        [
                            'student_id' => 101,
                            'session'    => ['id' => 1, 'status' => 'finished', 'score' => 80.0, 'total_score' => 80.0],
                            'answers'    => [['item_id' => 1, 'student_answer' => 'A']],
                        ],
                        // Case B: Absent student
                        [
                            'student_id' => 102,
                            'session'    => null,
                            'answers'    => [],
                        ],
                    ],
                ],
                // Subject 2: Bahasa Indonesia (Essay questions)
                [
                    'subject_id'   => 2,
                    'subject_name' => 'Bahasa Indonesia',
                    'test_config'  => [
                        'test_id'      => 202,
                        'type_weights' => ['essay' => 100.0],
                        'component_type' => 'pts',
                    ],
                    'items' => [
                        ['item_id' => 3, 'type' => 'essay', 'max_points' => 100.0, 'is_void' => false],
                    ],
                    'student_cases' => [
                        // Case C: Essay graded
                        [
                            'student_id' => 103,
                            'session'    => ['id' => 3, 'status' => 'finished', 'score' => 85.0, 'total_score' => 85.0],
                            'answers'    => [['item_id' => 3, 'score_earned' => 85.0, 'is_graded' => true]],
                        ],
                    ],
                ],
            ],
        ];

        $envMetadata = [
            'database_name'   => 'cbt_snapshot_db',
            'db_user'         => 'cbt_readonly',
            'server_version'  => '10.4.32-MariaDB',
            'is_production'   => false,
            'grants'          => ["GRANT SELECT ON `cbt_snapshot_db`.* TO 'cbt_readonly'@'localhost'"],
        ];

        $runResult = $runner->executeShadowRun($dataset, $envMetadata, 2);

        $this->assertEquals('COMPLETED_SUCCESSFULLY', $runResult['status']);
        $this->assertTrue($runResult['zero_dml_verified']);
        $this->assertEquals(3, $runResult['total_cases_evaluated']);

        // Verify all 6 artifacts were created on disk
        $artifacts = $runResult['artifacts_generated'];
        $this->assertCount(6, $artifacts);

        $this->assertFileExists($artifacts['shadow_summary.json']);
        $this->assertFileExists($artifacts['shadow_summary.csv']);
        $this->assertFileExists($artifacts['shadow_deviations.csv']);
        $this->assertFileExists($artifacts['shadow_execution_metadata.json']);
        $this->assertFileExists($artifacts['shadow_data_coverage.json']);
        $this->assertFileExists($artifacts['shadow_zero_dml_evidence.txt']);

        // Verify Zero PII in exported deviations CSV
        $csvContent = file_get_contents($artifacts['shadow_deviations.csv']);
        $this->assertStringContainsString('PseudonymousStudentID', $csvContent);
        $this->assertStringContainsString('STD_', $csvContent);
        // Student raw IDs (101, 102, 103) should not appear as bare student IDs
        $this->assertStringNotContainsString('"101"', $csvContent);

        // Verify Zero-DML evidence contents
        $evidenceContent = file_get_contents($artifacts['shadow_zero_dml_evidence.txt']);
        $this->assertStringContainsString('PRIVILEGE AUDIT: SELECT-ONLY (VERIFIED VIA GRANT AUDIT)', $evidenceContent);
        $this->assertStringContainsString('MUTATIVE STATEMENTS DETECTED: 0', $evidenceContent);
        $this->assertStringContainsString('100% CLEAN READ-ONLY CERTIFIED', $evidenceContent);

        // Clean up temporary files
        foreach ($artifacts as $file) {
            @unlink($file);
        }
        @rmdir($tempArtifactDir);
    }

    // =========================================================================
    // 5. STAGE F2A EVIDENCE VALIDATION & STATISTICAL CORRECTION TESTS
    // =========================================================================

    public function testEssayPendingProducesGradebookScoreNullAndPreservesObjective(): void
    {
        $engine = new \App\Services\Cbt\Assessment\AssessmentEngineService();
        $reporting = new \App\Services\Cbt\Reporting\AssessmentReportingService();

        $testConfig = [
            'test_id'        => 502,
            'type_weights'   => ['pg' => 60.0, 'essay' => 40.0],
            'component_type' => 'pts',
        ];

        $items = [
            ['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'weight' => 60.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'essay', 'max_points' => 100.0, 'weight' => 40.0, 'is_void' => false],
        ];

        // Student answered PG correctly, but essay is ungraded
        $answers = [
            ['item_id' => 1, 'student_answer' => 'A'],
            ['item_id' => 2, 'score_earned' => null, 'is_graded' => false],
        ];

        $context = [
            'student_id'           => 201,
            'session_id'           => 101,
            'session_status'       => \App\Services\Cbt\Assessment\AssessmentStatusResolver::SESSION_FINISHED,
            'participation_status' => \App\Services\Cbt\Assessment\AssessmentStatusResolver::PARTICIPATION_PRESENT,
            'integrity_status'     => \App\Services\Cbt\Assessment\AssessmentStatusResolver::INTEGRITY_VALID,
        ];

        $result = $engine->evaluateStudent($testConfig, $items, $answers, $context);

        // 1. Objective score is valid numerics (100% on PG)
        $this->assertEquals(100.0, $result->getObjectiveScore());

        // 2. Essay is incomplete -> finalScore and reportScore MUST be null
        $this->assertNull($result->getFinalScore());
        $this->assertNull($result->getReportScore());
        $this->assertFalse($result->isFullyGraded());
        $this->assertFalse($result->isEligibleForGradebook());

        // 3. Reporting projection has null report_score
        $report = $reporting->projectStudentReport($result);
        $this->assertNull($report['gradebook_report']['report_score']);
        $this->assertFalse($report['gradebook_report']['is_eligible']);
        $this->assertStringContainsString('Essay grading incomplete', $report['gradebook_report']['ineligible_reason']);
    }

    public function testObjectiveZeroDoesNotGetConfusedWithPendingOrAbsent(): void
    {
        $engine = new \App\Services\Cbt\Assessment\AssessmentEngineService();

        $testConfig = [
            'test_id'      => 501,
            'type_weights' => ['pg' => 100.0],
        ];
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'is_void' => false],
        ];

        // Case A: Student attempted and scored 0.0 (wrong answer)
        $attemptedWrong = $engine->evaluateStudent(
            $testConfig, $items,
            [['item_id' => 1, 'student_answer' => 'X']],
            [
                'student_id'           => 1,
                'session_status'       => \App\Services\Cbt\Assessment\AssessmentStatusResolver::SESSION_FINISHED,
                'participation_status' => \App\Services\Cbt\Assessment\AssessmentStatusResolver::PARTICIPATION_PRESENT,
            ]
        );
        $this->assertEquals(0.0, $attemptedWrong->getObjectiveScore());
        $this->assertEquals(0.0, $attemptedWrong->getFinalScore());
        $this->assertEquals(0.0, $attemptedWrong->getReportScore());
        $this->assertTrue($attemptedWrong->isFullyGraded());
        $this->assertTrue($attemptedWrong->isEligibleForGradebook());

        // Case B: Student absent (unattempted)
        $absentStudent = $engine->evaluateStudent(
            $testConfig, $items, [],
            [
                'student_id'           => 2,
                'session_status'       => \App\Services\Cbt\Assessment\AssessmentStatusResolver::SESSION_NOT_STARTED,
                'participation_status' => \App\Services\Cbt\Assessment\AssessmentStatusResolver::PARTICIPATION_ABSENT,
            ]
        );
        $this->assertNull($absentStudent->getFinalScore());
        $this->assertNull($absentStudent->getReportScore());
        $this->assertFalse($absentStudent->isEligibleForGradebook());
    }

    public function testPendingEssayExcludedFromNumericalComparisonAndMaeRmse(): void
    {
        $comparator = new \App\Services\Cbt\Shadow\ShadowComparisonService();

        $legacyResult = [
            'student_id'      => 301,
            'session_id'      => 501,
            'converted_score' => 75.0,
            'is_verifiable'   => true,
        ];

        $v3Result = [
            'student_id'          => 301,
            'test_id'             => 601,
            'session_id'          => 501,
            'final_score'         => null,
            'report_score'        => null,
            'objective_score'     => 75.0,
            'is_fully_graded'     => false, // Pending essay
            'is_eligible'         => false,
            'statuses'            => [\App\Services\Cbt\Assessment\AssessmentStatusResolver::DOMAIN_ESSAY => 'IN_PROGRESS'],
            'is_entire_test_void' => false,
        ];

        $comparison = $comparator->compareCase($legacyResult, $v3Result);

        // Must be non-comparable
        $this->assertFalse($comparison['is_comparable']);
        $this->assertNull($comparison['v3_score']);
        $this->assertNull($comparison['delta']);

        // Must contain ESSAY_POLICY_DIFFERENCE and PUBLICATION_ELIGIBILITY_DIFFERENCE
        $this->assertContains(\App\Services\Cbt\Shadow\ShadowComparisonService::CAT_ESSAY_POLICY_DIFFERENCE, $comparison['categories']);
        $this->assertContains(\App\Services\Cbt\Shadow\ShadowComparisonService::CAT_PUBLICATION_ELIGIBILITY_DIFFERENCE, $comparison['categories']);

        // MUST NOT be classified as SCORE_DIFFERENCE
        $this->assertNotContains(\App\Services\Cbt\Shadow\ShadowComparisonService::CAT_SCORE_DIFFERENCE, $comparison['categories']);

        // When aggregated, MAE and RMSE must remain null if only pending essay is tested
        $reportGen = new \App\Services\Cbt\Shadow\ShadowReportGenerator();
        $agg = $reportGen->generateAggregateReport([$comparison]);
        $this->assertEquals(0, $agg['total_comparable_cases']);
        $this->assertNull($agg['mae']);
        $this->assertNull($agg['rmse']);
    }

    public function testPublicationEligibilityComparisonAndDenominators(): void
    {
        $comparator = new \App\Services\Cbt\Shadow\ShadowComparisonService();
        $reportGen = new \App\Services\Cbt\Shadow\ShadowReportGenerator();

        // 3 Cases:
        // Case 1: Exact match, eligible on both sides (score 100)
        $c1 = $comparator->compareCase(
            ['student_id' => 1, 'converted_score' => 100.0, 'is_verifiable' => true],
            ['student_id' => 1, 'final_score' => 100.0, 'report_score' => 100.0, 'is_fully_graded' => true, 'is_eligible' => true]
        );

        // Case 2: Rounding difference, eligible on both sides (legacy 49.98 vs V3 50.00)
        $c2 = $comparator->compareCase(
            ['student_id' => 2, 'converted_score' => 49.98, 'is_verifiable' => true],
            ['student_id' => 2, 'final_score' => 50.0, 'report_score' => 50.0, 'is_fully_graded' => true, 'is_eligible' => true]
        );

        // Case 3: Legacy published 75.0, V3 pending essay
        $c3 = $comparator->compareCase(
            ['student_id' => 3, 'converted_score' => 75.0, 'is_verifiable' => true],
            ['student_id' => 3, 'final_score' => null, 'report_score' => null, 'is_fully_graded' => false, 'is_eligible' => false]
        );

        $agg = $reportGen->generateAggregateReport([$c1, $c2, $c3]);

        // Denominators
        $this->assertEquals(3, $agg['total_cases_examined']);
        $this->assertEquals(2, $agg['total_comparable_cases']);
        $this->assertEquals(2, $agg['total_eligible_cases']);

        // Exact match breakdown
        $this->assertEquals(1, $agg['exact_match_count']);
        $this->assertEquals(33.33, $agg['percentage_breakdown']['exact_match']['pct_of_total']);      // 1 / 3
        $this->assertEquals(50.0, $agg['percentage_breakdown']['exact_match']['pct_of_comparable']); // 1 / 2
        $this->assertEquals(50.0, $agg['percentage_breakdown']['exact_match']['pct_of_eligible']);   // 1 / 2

        // Publication eligibility
        $this->assertEquals(2, $agg['publication_eligibility']['status_match_count']);
        $this->assertEquals(1, $agg['publication_eligibility']['status_difference_count']);
    }

    public function testSnapshotChecksumAndExecutionModesIntegrity(): void
    {
        $runner = new IsolatedSnapshotRunner();

        // 1. Without physical snapshot file -> marked SNAPSHOT_CHECKSUM_UNVERIFIED
        $auditNoFile = $runner->auditEnvironment([
            'database_name' => 'cbt_test',
            'grants'        => ["GRANT SELECT ON `cbt_test`.* TO 'reader'@'localhost'"],
        ]);
        $this->assertEquals('SNAPSHOT_CHECKSUM_UNVERIFIED', $auditNoFile['snapshot_checksum']);
        $this->assertEquals('SNAPSHOT_CHECKSUM_UNVERIFIED', $auditNoFile['snapshot_details']['status']);
        $this->assertFalse($auditNoFile['execution_modes']['MARIADB_INTEGRATION_TESTED']);
        $this->assertFalse($auditNoFile['execution_modes']['REAL_SNAPSHOT_EXECUTED']);
        $this->assertFalse($auditNoFile['execution_modes']['ZERO_DML_DATABASE_VERIFIED']);
        $this->assertTrue($auditNoFile['execution_modes']['ZERO_DML_MOCK_GUARD_VERIFIED']);

        // 2. With real physical file -> computes real SHA-256
        $tempFile = tempnam(sys_get_temp_dir(), 'snap_');
        file_put_contents($tempFile, 'CREATE TABLE sample_test (id INT);');
        $expectedHash = hash_file('sha256', $tempFile);

        $auditWithFile = $runner->auditEnvironment([
            'database_name'      => 'cbt_test',
            'snapshot_file_path' => $tempFile,
            'grants'             => ["GRANT SELECT ON `cbt_test`.* TO 'reader'@'localhost'"],
        ]);

        $this->assertEquals($expectedHash, $auditWithFile['snapshot_checksum']);
        $this->assertEquals('VERIFIED_FROM_PHYSICAL_FILE', $auditWithFile['snapshot_details']['status']);
        $this->assertEquals(filesize($tempFile), $auditWithFile['snapshot_details']['file_size_bytes']);
        $this->assertStringContainsString("PHP hash_file('sha256'", $auditWithFile['snapshot_details']['calculation_method']);

        unlink($tempFile);
    }
}

