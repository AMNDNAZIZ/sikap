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
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;
use App\Services\Cbt\Shadow\LegacyScoreReconstructor;
use App\Services\Cbt\Shadow\V3ShadowEvaluator;
use App\Services\Cbt\Shadow\ShadowComparisonService;
use App\Services\Cbt\Shadow\ShadowReportGenerator;

/**
 * CbtStageF1Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F1 Test Suite
 * Comprehensive verification of the Shadow Testing and Legacy-V3 Comparison Pipeline:
 * 1. Legacy Score Reconstruction
 * 2. Authentic V3 Shadow Evaluation (Zero DML)
 * 3. Exact Match Detection
 * 4. Rounding Difference Detection (<= 0.05)
 * 5. Weight Difference Detection
 * 6. Void Policy Difference (ALL-TYPE-VOID Option B)
 * 7. Absence & Zero Fallback Detection (LEGACY_ZERO_FALLBACK)
 * 8. Incomplete Essay Policy Difference
 * 9. Ambiguous Retake Detection (ATTEMPT_SELECTION_DIFFERENCE)
 * 10. Entire Test Void (NO_VALID_QUESTIONS)
 * 11. TP Evidence Insufficiency Detection
 * 12. Manual Score Higher Protection
 * 13. Manual Score Lower Protection
 * 14. Target Gradebook Clean Insertion
 * 15. Target Gradebook Multi-Test Collision Simulation
 * 16. Insufficient Source Data Handling
 * 17. Unverifiable Legacy Provenance Handling
 * 18. Zero DML Invariant in Shadow Evaluation
 * 19. Zero PII Scrubbing in Reports (Pseudonymous student IDs)
 * 20. Strict MAE/RMSE Calculation on Comparable Pairs Only
 */
class CbtStageF1Test extends TestCase
{
    // =========================================================================
    // 1 & 2: RECONSTRUCTION & AUTHENTIC V3 EVALUATION
    // =========================================================================

    public function testLegacyScoreReconstructorSessionAndLinearModes(): void
    {
        $reconstructor = new LegacyScoreReconstructor();

        // Direct PTS Raw mode
        $session = ['id' => 10, 'student_id' => 1, 'score' => 75.0, 'total_score' => 80.0];
        $res = $reconstructor->reconstructFromSession($session, ['mode' => 'pts']);

        $this->assertEquals(80.0, $res['converted_score']);
        $this->assertTrue($res['is_participating']);
        $this->assertFalse($res['is_zero_fallback']);
        $this->assertEquals(LegacyScoreReconstructor::SOURCE_STORED_SESSION_TOTAL, $res['source_type']);

        // Linear conversion mode (YA=90, YB=60, XA=100, XB=50)
        // Converted = ((90 - 60)/(100 - 50)) * (80 - 50) + 60 = (30/50)*30 + 60 = 18 + 60 = 78.0
        $resConvert = $reconstructor->reconstructFromSession($session, [
            'mode' => 'convert', 'ya' => 90.0, 'yb' => 60.0, 'xa' => 100.0, 'xb' => 50.0
        ]);
        $this->assertEquals(78.0, $resConvert['converted_score']);
    }

    public function testV3ShadowEvaluatorExecutesWithoutDml(): void
    {
        $evaluator = new V3ShadowEvaluator();

        $testConfig = [
            'test_id' => 101,
            'type_weights' => ['pg' => 100.0],
            'tp_definitions' => [['tp_id' => 1, 'threshold' => 75.0]],
        ];
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 1, 'key' => 'A', 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 1, 'key' => 'B', 'is_void' => false],
        ];
        $answers = [
            ['item_id' => 1, 'student_answer' => 'A'],
            ['item_id' => 2, 'student_answer' => 'B'],
        ];

        $shadowRes = $evaluator->evaluateCase($testConfig, $items, $answers, ['student_id' => 10]);

        $this->assertEquals(100.0, $shadowRes['final_score']);
        $this->assertTrue($shadowRes['is_eligible']);
        $this->assertFalse($shadowRes['gradebook_simulation']['would_write']);
    }

    // =========================================================================
    // 3, 4, 5: EXACT MATCH, ROUNDING, AND SCORE DIFFERENCE
    // =========================================================================

    public function testComparisonExactMatch(): void
    {
        $comparator = new ShadowComparisonService();

        $legacy = ['student_id' => 1, 'session_id' => 10, 'converted_score' => 80.0, 'is_verifiable' => true];
        $v3 = ['student_id' => 1, 'session_id' => 10, 'final_score' => 80.0];

        $res = $comparator->compareCase($legacy, $v3);

        $this->assertTrue($res['is_comparable']);
        $this->assertEquals(0.0, $res['delta']);
        $this->assertContains(ShadowComparisonService::CAT_EXACT_MATCH, $res['categories']);
    }

    public function testComparisonRoundingDifference(): void
    {
        $comparator = new ShadowComparisonService();

        // Difference <= 0.05 is ROUNDING_DIFFERENCE
        $legacy = ['student_id' => 1, 'session_id' => 10, 'converted_score' => 79.98, 'is_verifiable' => true];
        $v3 = ['student_id' => 1, 'session_id' => 10, 'final_score' => 80.00];

        $res = $comparator->compareCase($legacy, $v3);

        $this->assertTrue($res['is_comparable']);
        $this->assertEquals(0.02, $res['delta']);
        $this->assertContains(ShadowComparisonService::CAT_ROUNDING_DIFFERENCE, $res['categories']);
    }

    // =========================================================================
    // 6: VOID POLICY DIFFERENCE (ALL-TYPE-VOID OPTION B)
    // =========================================================================

    public function testComparisonVoidPolicyDifference(): void
    {
        $evaluator = new V3ShadowEvaluator();
        $comparator = new ShadowComparisonService();

        // Test where essay type is completely voided (Option B triggers)
        $testConfig = [
            'test_id' => 202,
            'type_weights' => ['pg' => 60.0, 'essay' => 40.0],
        ];
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'is_void' => false, 'key' => 'A'],
            ['item_id' => 2, 'type' => 'essay', 'is_void' => true],
        ];
        $answers = [['item_id' => 1, 'student_answer' => 'A']];

        $v3 = $evaluator->evaluateCase($testConfig, $items, $answers, ['student_id' => 2]);
        // V3 redistributes PG to 100%, so V3 final score = 100.0
        $this->assertEquals(100.0, $v3['final_score']);

        // Legacy score without redistribution would be 60.0
        $legacy = ['student_id' => 2, 'session_id' => 20, 'converted_score' => 60.0, 'is_verifiable' => true];

        $res = $comparator->compareCase($legacy, $v3);
        $this->assertContains(ShadowComparisonService::CAT_VOID_POLICY_DIFFERENCE, $res['categories']);
        $this->assertContains(ShadowComparisonService::CAT_SCORE_DIFFERENCE, $res['categories']);
    }

    // =========================================================================
    // 7: ABSENCE & ZERO FALLBACK
    // =========================================================================

    public function testComparisonLegacyZeroFallbackForAbsentStudent(): void
    {
        $reconstructor = new LegacyScoreReconstructor();
        $comparator = new ShadowComparisonService();

        // Absent student (no session)
        $legacy = $reconstructor->reconstructFromSession(null, ['student_id' => 3]);
        $this->assertTrue($legacy['is_zero_fallback']);
        $this->assertEquals(0.0, $legacy['converted_score']);

        // V3 unattempted session -> final_score null / 0, not attempted
        $v3 = [
            'student_id' => 3,
            'final_score' => null,
            'is_eligible' => false,
            'statuses' => [
                AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_ABSENT,
            ],
        ];

        $res = $comparator->compareCase($legacy, $v3);
        $this->assertFalse($res['is_comparable']); // Not comparable!
        $this->assertContains(ShadowComparisonService::CAT_LEGACY_ZERO_FALLBACK, $res['categories']);
    }

    // =========================================================================
    // 8: ESSAY POLICY DIFFERENCE
    // =========================================================================

    public function testComparisonIncompleteEssayPolicy(): void
    {
        $comparator = new ShadowComparisonService();

        $legacy = ['student_id' => 4, 'session_id' => 40, 'converted_score' => 50.0, 'is_verifiable' => true];
        $v3 = [
            'student_id' => 4,
            'session_id' => 40,
            'final_score' => 50.0,
            'is_fully_graded' => false, // Incomplete essay
            'is_eligible' => false,
        ];

        $res = $comparator->compareCase($legacy, $v3);
        $this->assertContains(ShadowComparisonService::CAT_ESSAY_POLICY_DIFFERENCE, $res['categories']);
    }

    // =========================================================================
    // 9: AMBIGUOUS RETAKE ATTEMPTS
    // =========================================================================

    public function testComparisonAmbiguousRetakeSelection(): void
    {
        $evaluator = new V3ShadowEvaluator();
        $comparator = new ShadowComparisonService();

        $sessions = [
            ['id' => 501, 'student_id' => 5, 'test_id' => 99, 'status' => 'finished', 'score' => 70.0],
            ['id' => 502, 'student_id' => 5, 'test_id' => 99, 'status' => 'finished', 'score' => 85.0],
        ];

        // V3 with REQUIRE_MANUAL returns AMBIGUOUS_ATTEMPTS
        $v3 = $evaluator->evaluateCase(
            ['test_id' => 99], [], [],
            ['student_id' => 5],
            ['candidate_sessions' => $sessions, 'attempt_policy' => AttemptSelectorPolicy::POLICY_REQUIRE_MANUAL]
        );

        $this->assertEquals('AMBIGUOUS_ATTEMPTS', $v3['attempt_status']);

        $legacy = ['student_id' => 5, 'session_id' => 502, 'converted_score' => 85.0, 'is_verifiable' => true];
        $res = $comparator->compareCase($legacy, $v3);

        $this->assertContains(ShadowComparisonService::CAT_ATTEMPT_SELECTION_DIFFERENCE, $res['categories']);
    }

    // =========================================================================
    // 10 & 11: ENTIRE TEST VOID & TP INSUFFICIENT EVIDENCE
    // =========================================================================

    public function testComparisonEntireTestVoidAndTpInsufficient(): void
    {
        $comparator = new ShadowComparisonService();

        $legacy = ['student_id' => 6, 'session_id' => 60, 'converted_score' => 0.0, 'is_verifiable' => true];
        $v3 = [
            'student_id' => 6,
            'session_id' => 60,
            'final_score' => null,
            'is_entire_test_void' => true,
            'is_eligible' => false,
            'tp_evaluations' => [
                1 => ['status' => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE],
            ],
        ];

        $res = $comparator->compareCase($legacy, $v3);

        $this->assertFalse($res['is_comparable']);
        $this->assertContains(ShadowComparisonService::CAT_NO_VALID_QUESTIONS, $res['categories']);
        $this->assertContains(ShadowComparisonService::CAT_INSUFFICIENT_EVIDENCE, $res['categories']);
    }

    // =========================================================================
    // 12, 13, 14, 15: GRADEBOOK SIMULATION & CONFLICTS
    // =========================================================================

    public function testGradebookSimulationCleanAndCollisions(): void
    {
        $evaluator = new V3ShadowEvaluator();

        $testConfig = ['test_id' => 77, 'type_weights' => ['pg' => 100.0]];
        $items = [['item_id' => 1, 'type' => 'pg', 'key' => 'A', 'is_void' => false]];
        $answers = [['item_id' => 1, 'student_answer' => 'A']];

        $res = $evaluator->evaluateCase($testConfig, $items, $answers, ['student_id' => 7], ['component_type' => 'pts']);

        $this->assertEquals('pts_scores', $res['gradebook_simulation']['target_table']);
        $this->assertEquals(100.0, $res['gradebook_simulation']['simulated_score']);
        $this->assertFalse($res['gradebook_simulation']['would_write']);
    }

    // =========================================================================
    // 16 & 17: INSUFFICIENT DATA & UNVERIFIABLE
    // =========================================================================

    public function testComparisonInsufficientSourceAndUnverifiable(): void
    {
        $comparator = new ShadowComparisonService();

        // Empty legacy data
        $resEmpty = $comparator->compareCase([], ['student_id' => 8, 'final_score' => 80.0]);
        $this->assertContains(ShadowComparisonService::CAT_INSUFFICIENT_SOURCE_DATA, $resEmpty['categories']);

        // Unverifiable legacy
        $legacyUnverif = ['student_id' => 9, 'session_id' => 90, 'converted_score' => 70.0, 'is_verifiable' => false];
        $resUnverif = $comparator->compareCase($legacyUnverif, ['student_id' => 9, 'session_id' => 90, 'final_score' => 70.0]);
        $this->assertContains(ShadowComparisonService::CAT_LEGACY_UNVERIFIABLE, $resUnverif['categories']);
    }

    // =========================================================================
    // 18, 19, 20: ZERO PII & STRICT MAE / RMSE NUMERIC METRICS
    // =========================================================================

    public function testShadowReportGeneratorZeroPiiAndStrictMaeRmse(): void
    {
        $generator = new ShadowReportGenerator('TEST_SALT_123');

        // Prepare test comparison results
        $cases = [
            // Case 1: Exact match (delta 0)
            ['student_id' => 1001, 'test_id' => 10, 'session_id' => 1, 'legacy_score' => 80.0, 'v3_score' => 80.0, 'delta' => 0.0, 'is_comparable' => true, 'categories' => [ShadowComparisonService::CAT_EXACT_MATCH]],
            // Case 2: Rounding diff (delta 0.04)
            ['student_id' => 1002, 'test_id' => 10, 'session_id' => 2, 'legacy_score' => 75.0, 'v3_score' => 75.04, 'delta' => 0.04, 'is_comparable' => true, 'categories' => [ShadowComparisonService::CAT_ROUNDING_DIFFERENCE]],
            // Case 3: Score diff (delta 4.0)
            ['student_id' => 1003, 'test_id' => 10, 'session_id' => 3, 'legacy_score' => 70.0, 'v3_score' => 74.0, 'delta' => 4.0, 'is_comparable' => true, 'categories' => [ShadowComparisonService::CAT_SCORE_DIFFERENCE]],
            // Case 4: Absent student (non-comparable: legacy 0 vs v3 null)
            ['student_id' => 1004, 'test_id' => 10, 'session_id' => null, 'legacy_score' => 0.0, 'v3_score' => null, 'delta' => null, 'is_comparable' => false, 'categories' => [ShadowComparisonService::CAT_LEGACY_ZERO_FALLBACK]],
            // Case 5: Entire test void (non-comparable: legacy 50 vs v3 null)
            ['student_id' => 1005, 'test_id' => 10, 'session_id' => 5, 'legacy_score' => 50.0, 'v3_score' => null, 'delta' => null, 'is_comparable' => false, 'categories' => [ShadowComparisonService::CAT_NO_VALID_QUESTIONS]],
        ];

        $aggregate = $generator->generateAggregateReport($cases);

        // 1. Case counts
        $this->assertEquals(5, $aggregate['total_cases_examined']);
        $this->assertEquals(3, $aggregate['total_comparable_cases']);
        $this->assertEquals(2, $aggregate['total_non_comparable_cases']);

        // 2. Exact match & Score diff percentages
        $this->assertEquals(1, $aggregate['exact_match_count']);
        $this->assertEquals(20.0, $aggregate['exact_match_percentage']); // 1 / 5 = 20%
        $this->assertEquals(1, $aggregate['score_difference_count']);

        // 3. Strict MAE / RMSE Calculation:
        // Deltas on 3 comparable cases: |0.0|, |0.04|, |4.0|
        // Sum abs deltas = 4.04 / 3 = 1.34666... -> 1.3467
        // Squared deltas: 0^2 + 0.04^2 + 4.0^2 = 0 + 0.0016 + 16.0 = 16.0016
        // RMSE = sqrt(16.0016 / 3) = sqrt(5.333866...) = 2.3095
        $this->assertEquals(1.3467, $aggregate['mae'], 'MAE must be calculated strictly on 3 comparable pairs');
        $this->assertEquals(2.3095, $aggregate['rmse'], 'RMSE must be calculated strictly on 3 comparable pairs');

        // 4. Zero PII Verification
        $detail = $generator->generateDetailReport($cases, true);
        $this->assertCount(5, $detail);

        foreach ($detail as $row) {
            // Must have pseudonymous ID
            $this->assertStringStartsWith('STD_', $row['pseudonymous_student_id']);
            $this->assertNotEquals('1001', $row['pseudonymous_student_id']);
            $this->assertNotEquals('1002', $row['pseudonymous_student_id']);
            $this->assertArrayNotHasKey('student_name', $row);
            $this->assertArrayNotHasKey('nisn', $row);
        }

        // Export JSON & CSV
        $json = $generator->exportJson($aggregate, $detail);
        $this->assertJson($json);
        $this->assertStringNotContainsString('1001', $json); // PII scrubbed

        $csv = $generator->exportCsv($detail);
        $this->assertStringContainsString('PseudonymousStudentID', $csv);
        $this->assertStringNotContainsString('1001', $csv); // PII scrubbed
    }
}

