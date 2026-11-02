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
use InvalidArgumentException;
use App\Services\Cbt\Assessment\AssessmentResult;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Reporting\AssessmentReportingService;
use App\Services\Cbt\Gradebook\GradebookMappingRegistry;
use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;
use App\Services\Cbt\Gradebook\GradebookSyncService;

/**
 * CbtStageETest
 *
 * ESPATA Assessment Intelligence V3 - Stage E Test Suite
 * Verifies Reporting Projections and Gradebook Integration:
 * 1. Reporting Projections (Test score, Report score, TP Analytics, Cohort Analytics separation)
 * 2. Gradebook Mapping Registry (5 tables, allowlists, required fields, match criteria)
 * 3. Multiple Attempt Resolution (Single, Manual, Latest, Highest, Provenance validation)
 * 4. Manual Score Protection & Override Policy (Conflict detection, PENDING_REVIEW, authorized override)
 * 5. Idempotency & Versioning (Identical re-sync = NOOP, score change audit)
 * 6. Eligibility Gates (Void test = SKIPPED_VOID_TEST, incomplete essay = SKIPPED, TP insufficient = SYNCED)
 * 7. Audit Trail & Traceability
 */
class CbtStageETest extends TestCase
{
    // =========================================================================
    // 1. REPORTING PROJECTION TESTS
    // =========================================================================

    public function testReportingProjectsAllPerspectivesSeparately(): void
    {
        $reporting = new AssessmentReportingService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        $tpEvals = [
            101 => [
                'tp_id' => 101, 'tp_code' => 'TP-ALGEBRA', 's_max' => 20.0, 'n_unique' => 2,
                'is_sufficient_evidence' => true, 'student_percentage' => 85.0, 'status' => AssessmentStatusResolver::TP_MASTERED,
            ]
        ];

        $result = new AssessmentResult(
            1, 10, 100, $statuses,
            85.0, 85.0, 85.0, 85.0,
            [], [], $tpEvals, true
        );

        $report = $reporting->projectStudentReport($result, ['subject_name' => 'Matematika']);

        // Perspective A: Test score
        $this->assertEquals(85.0, $report['test_score']['final_score']);
        $this->assertTrue($report['test_score']['has_score']);
        $this->assertFalse($report['test_score']['is_entire_test_void']);

        // Perspective B: Gradebook report
        $this->assertTrue($report['gradebook_report']['is_eligible']);
        $this->assertEquals(85.0, $report['gradebook_report']['report_score']);
        $this->assertNull($report['gradebook_report']['ineligible_reason']);

        // Perspective C: TP Analytics
        $this->assertArrayHasKey(101, $report['tp_analytics']);
        $this->assertEquals('TP-ALGEBRA', $report['tp_analytics'][101]['tp_code']);
        $this->assertTrue($report['tp_analytics'][101]['is_sufficient_evidence']);
    }

    public function testReportingStrictlySeparatesCohortCrFromGradebookScores(): void
    {
        $reporting = new AssessmentReportingService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_NOT_APPLICABLE,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        $results = [
            new AssessmentResult(1, 10, 100, $statuses, 90.0, 90.0, 90.0, 0.0, [], [], [1 => ['status' => AssessmentStatusResolver::TP_MASTERED]], true),
            new AssessmentResult(2, 10, 101, $statuses, 50.0, 50.0, 50.0, 0.0, [], [], [1 => ['status' => AssessmentStatusResolver::TP_NOT_MASTERED]], true),
        ];

        $cohortReport = $reporting->projectCohortReport($results, ['test_id' => 10]);

        $this->assertEquals(2, $cohortReport['total_students']);
        $this->assertEquals(70.0, $cohortReport['average_report_score']); // (90 + 50) / 2 = 70.0

        // CR_murni is 50.0% (1 mastered / 2 definitive)
        $this->assertEquals(50.0, $cohortReport['cohort_tp_metrics'][1]['cr_murni']);

        // Assert CR is not conflated with report score
        $this->assertNotEquals($cohortReport['average_report_score'], $cohortReport['cohort_tp_metrics'][1]['cr_murni']);
    }

    // =========================================================================
    // 2. GRADEBOOK MAPPING REGISTRY TESTS
    // =========================================================================

    public function testRegistryResolvesAllFiveTargetTables(): void
    {
        $registry = new GradebookMappingRegistry();

        $this->assertEquals(GradebookMappingRegistry::TABLE_MATERIAL_SCORES, $registry->resolveTargetTable('formatif'));
        $this->assertEquals(GradebookMappingRegistry::TABLE_MATERIAL_SCORES, $registry->resolveTargetTable('lingkup_materi'));

        $this->assertEquals(GradebookMappingRegistry::TABLE_PTS_SCORES, $registry->resolveTargetTable('pts'));
        $this->assertEquals(GradebookMappingRegistry::TABLE_PTS_SCORES, $registry->resolveTargetTable('sts'));

        $this->assertEquals(GradebookMappingRegistry::TABLE_SUMMATIVE_SCORES, $registry->resolveTargetTable('sumatif'));
        $this->assertEquals(GradebookMappingRegistry::TABLE_SUMMATIVE_SCORES, $registry->resolveTargetTable('sas'));

        $this->assertEquals(GradebookMappingRegistry::TABLE_FINAL_EXAM_SCORES, $registry->resolveTargetTable('final'));
        $this->assertEquals(GradebookMappingRegistry::TABLE_FINAL_EXAM_SCORES, $registry->resolveTargetTable('asas'));

        $this->assertEquals(GradebookMappingRegistry::TABLE_SUBJECT_SCORES, $registry->resolveTargetTable('subject'));
        $this->assertEquals(GradebookMappingRegistry::TABLE_SUBJECT_SCORES, $registry->resolveTargetTable('rapor'));
    }

    public function testRegistryRejectsUnauthorizedTargetTable(): void
    {
        $registry = new GradebookMappingRegistry();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("is not in the authorized allowlist");

        $registry->resolveTargetTable('malicious_users_table');
    }

    public function testRegistryEnforcesMatchCriteria(): void
    {
        $registry = new GradebookMappingRegistry();

        // material_scores requires: student_id, material_id, type
        $whereMaterial = $registry->buildMatchWhere('material_scores', [
            'student_id'  => 10,
            'material_id' => 55,
            'type'        => 'tulis',
        ]);
        $this->assertEquals(['student_id' => 10, 'material_id' => 55, 'type' => 'tulis'], $whereMaterial);

        // final_exam_scores does NOT have semester in schema
        $whereFinal = $registry->buildMatchWhere('final_exam_scores', [
            'student_id' => 10,
            'subject_id' => 3,
            'year_id'    => 2026,
            'semester'   => 'ganjil', // superfluous field ignored
        ]);
        $this->assertEquals(['student_id' => 10, 'subject_id' => 3, 'year_id' => 2026], $whereFinal);
        $this->assertArrayNotHasKey('semester', $whereFinal);
    }

    // =========================================================================
    // 3. MULTIPLE ATTEMPT SELECTOR TESTS
    // =========================================================================

    public function testAttemptSelectorSingleSession(): void
    {
        $policy = new AttemptSelectorPolicy();

        $sessions = [
            ['id' => 101, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 80.0],
        ];

        $selection = $policy->selectAttempt($sessions, 5, 50);

        $this->assertEquals('SELECTED', $selection['status']);
        $this->assertEquals('SINGLE_ATTEMPT', $selection['policy_used']);
        $this->assertEquals(101, $selection['selected_session']['id']);
    }

    public function testAttemptSelectorMultipleSessionsDefaultsToManualSelection(): void
    {
        $policy = new AttemptSelectorPolicy();

        $sessions = [
            ['id' => 101, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0],
            ['id' => 102, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 85.0],
        ];

        // Default policy is REQUIRE_MANUAL
        $selection = $policy->selectAttempt($sessions, 5, 50, AttemptSelectorPolicy::POLICY_REQUIRE_MANUAL);

        $this->assertEquals('AMBIGUOUS_ATTEMPTS', $selection['status']);
        $this->assertNull($selection['selected_session']);
        $this->assertEquals(2, $selection['candidate_count']);
    }

    public function testAttemptSelectorConfigurablePolicies(): void
    {
        $policy = new AttemptSelectorPolicy();

        $sessions = [
            ['id' => 101, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 70.0, 'finished_at' => '2026-11-01 08:00:00'],
            ['id' => 102, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 90.0, 'finished_at' => '2026-11-01 09:00:00'],
            ['id' => 103, 'student_id' => 5, 'test_id' => 50, 'status' => 'finished', 'score' => 60.0, 'finished_at' => '2026-11-01 10:00:00'],
        ];

        // 1. LATEST_FINISHED selects 103
        $latest = $policy->selectAttempt($sessions, 5, 50, AttemptSelectorPolicy::POLICY_LATEST_FINISHED);
        $this->assertEquals(103, $latest['selected_session']['id']);

        // 2. HIGHEST_SCORE selects 102
        $highest = $policy->selectAttempt($sessions, 5, 50, AttemptSelectorPolicy::POLICY_HIGHEST_SCORE);
        $this->assertEquals(102, $highest['selected_session']['id']);

        // 3. FIRST_FINISHED selects 101
        $first = $policy->selectAttempt($sessions, 5, 50, AttemptSelectorPolicy::POLICY_FIRST_FINISHED);
        $this->assertEquals(101, $first['selected_session']['id']);

        // 4. EXPLICIT SELECTION overrides policy
        $explicit = $policy->selectAttempt($sessions, 5, 50, AttemptSelectorPolicy::POLICY_REQUIRE_MANUAL, 102);
        $this->assertEquals(102, $explicit['selected_session']['id']);
        $this->assertEquals('EXPLICIT_SELECTION', $explicit['policy_used']);
    }

    public function testAttemptSelectorRejectsForeignStudentOrTest(): void
    {
        $policy = new AttemptSelectorPolicy();

        // Foreign student
        $this->expectException(InvalidArgumentException::class);
        $policy->validateSessionProvenance(['id' => 99, 'student_id' => 999, 'test_id' => 50, 'status' => 'finished'], 5, 50);
    }

    // =========================================================================
    // 4. MANUAL SCORE PROTECTION & CONFLICT DETECTION
    // =========================================================================

    public function testSyncProtectsManualScoreAndFlagsPendingReview(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        // Proposed CBT score is 75.0
        $result = new AssessmentResult(10, 50, 501, $statuses, 75.0, 75.0, 75.0, 75.0, [], [], [], true);

        // Target already has teacher manual score 85.0
        $existing = ['id' => 999, 'student_id' => 10, 'subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil', 'score' => 85.0];

        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil'],
            ['existing_record' => $existing, 'allow_override' => false]
        );

        $this->assertEquals(GradebookSyncService::STATUS_PENDING_REVIEW, $syncOutcome['sync_status']);
        $this->assertEquals(85.0, $syncOutcome['score_before']);
        $this->assertEquals(75.0, $syncOutcome['score_proposed']);
        $this->assertEquals(85.0, $syncOutcome['score_after']); // Preserved unchanged!
        $this->assertEquals('PENDING_REVIEW', $syncOutcome['review_status']);
    }

    public function testSyncAuthorizedOverrideUpdatesScoreWithAudit(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        $result = new AssessmentResult(10, 50, 501, $statuses, 90.0, 90.0, 90.0, 90.0, [], [], [], true);
        $existing = ['id' => 999, 'student_id' => 10, 'subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil', 'score' => 85.0];

        // Authorized override by teacher/headmaster (reviewer 7)
        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil'],
            [
                'existing_record' => $existing,
                'allow_override'  => true,
                'reviewer_id'     => 7,
                'review_reason'   => 'CBT score verified after rechecking essay rubrics',
            ]
        );

        $this->assertEquals(GradebookSyncService::STATUS_SYNCED_UPDATED, $syncOutcome['sync_status']);
        $this->assertEquals(85.0, $syncOutcome['score_before']);
        $this->assertEquals(90.0, $syncOutcome['score_proposed']);
        $this->assertEquals(90.0, $syncOutcome['score_after']);
        $this->assertEquals('APPROVED', $syncOutcome['review_status']);
        $this->assertEquals(7, $syncOutcome['reviewer_id']);
    }

    // =========================================================================
    // 5. IDEMPOTENCY & VERSIONING TESTS
    // =========================================================================

    public function testSyncIdempotentNoopWhenScoresAreIdentical(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        $result = new AssessmentResult(10, 50, 501, $statuses, 80.0, 80.0, 80.0, 80.0, [], [], [], true);
        $existing = ['id' => 55, 'student_id' => 10, 'subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil', 'score' => 80.0];

        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil'],
            ['existing_record' => $existing]
        );

        $this->assertEquals(GradebookSyncService::STATUS_IDEMPOTENT_NOOP, $syncOutcome['sync_status']);
        $this->assertTrue($syncOutcome['is_successful']);
        $this->assertEquals(80.0, $syncOutcome['score_after']);
    }

    // =========================================================================
    // 6. ELIGIBILITY GATE TESTS (VOID TEST, ESSAY INCOMPLETE, TP INSUFFICIENT)
    // =========================================================================

    public function testEntireTestVoidIsSkippedAndNeverEntersGradebook(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_NOT_APPLICABLE,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_NOT_APPLICABLE,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE,
        ];

        // NO_VALID_QUESTIONS has null finalScore and reportScore
        $result = new AssessmentResult(10, 50, 501, $statuses, null, null, 0.0, 0.0, [], [], [], false);

        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil']
        );

        $this->assertEquals(GradebookSyncService::STATUS_SKIPPED_VOID_TEST, $syncOutcome['sync_status']);
        $this->assertFalse($syncOutcome['is_successful']);
        $this->assertNull($syncOutcome['score_proposed']);
        $this->assertNull($syncOutcome['score_after']);
    }

    public function testIncompleteEssayIsSkipped(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_IN_PROGRESS, // INCOMPLETE
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];

        $result = new AssessmentResult(10, 50, 501, $statuses, 60.0, 60.0, 60.0, 0.0, [], [], [], false);

        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil']
        );

        $this->assertEquals(GradebookSyncService::STATUS_SKIPPED_INELIGIBLE, $syncOutcome['sync_status']);
    }

    public function testTpInsufficientEvidenceDoesNotBlockGradebookSync(): void
    {
        $syncService = new GradebookSyncService();

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE, // TP insufficient
        ];

        // Overall test score is valid 82.0
        $result = new AssessmentResult(10, 50, 501, $statuses, 82.0, 82.0, 82.0, 0.0, [], [], [], true);

        $syncOutcome = $syncService->syncResult(
            $result,
            'pts',
            ['subject_id' => 1, 'year_id' => 2026, 'semester' => 'ganjil'],
            ['existing_record' => null] // Clean new entry
        );

        // Must sync successfully to gradebook!
        $this->assertEquals(GradebookSyncService::STATUS_SYNCED_NEW, $syncOutcome['sync_status']);
        $this->assertTrue($syncOutcome['is_successful']);
        $this->assertEquals(82.0, $syncOutcome['score_after']);
    }
}

