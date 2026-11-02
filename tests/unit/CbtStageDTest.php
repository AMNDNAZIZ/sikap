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
use App\Services\Cbt\Assessment\AssessmentEngineService;
use App\Services\Cbt\Assessment\AssessmentResult;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Assessment\EssayGradingService;
use App\Services\Cbt\Assessment\MasteryRateCalculator;
use App\Services\Cbt\Assessment\ObjectiveScoringService;
use App\Services\Cbt\Assessment\QuestionWeightResolver;
use App\Services\Cbt\Assessment\TpEvidenceEvaluator;

/**
 * CbtStageDTest
 *
 * ESPATA Assessment Intelligence V3 - Stage D Test Suite
 * Verifies Assessment Engine and Psychometric Evaluation:
 * 1. Multi-Domain Status Resolution across 7 domains
 * 2. Weight Resolution with ALL-TYPE-VOID Option B & ENTIRE_TEST_VOID
 * 3. Objective Scoring (0-100 scale, unanswered = 0, void = 0)
 * 4. Essay Grading & OD-PATH-B Isolation
 * 5. TP Evidence Sufficiency (Conjunctive Smax >= 10.00 AND N_unique >= 2)
 * 6. Mastery Rates: CR_murni & CR_kohort with mandatory regression benchmark
 * 7. End-to-end AssessmentEngineService orchestration & AssessmentResult DTO
 */
class CbtStageDTest extends TestCase
{
    // =========================================================================
    // 1. STATUS RESOLVER TESTS
    // =========================================================================

    public function testStatusResolverDefinesAllSevenDomains(): void
    {
        $domains = [
            AssessmentStatusResolver::DOMAIN_TEST,
            AssessmentStatusResolver::DOMAIN_SESSION,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION,
            AssessmentStatusResolver::DOMAIN_INTEGRITY,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE,
            AssessmentStatusResolver::DOMAIN_ESSAY,
            AssessmentStatusResolver::DOMAIN_TP,
        ];

        $this->assertCount(7, array_unique($domains));
        $this->assertEquals('test', AssessmentStatusResolver::DOMAIN_TEST);
        $this->assertEquals('session', AssessmentStatusResolver::DOMAIN_SESSION);
        $this->assertEquals('participation', AssessmentStatusResolver::DOMAIN_PARTICIPATION);
        $this->assertEquals('integrity', AssessmentStatusResolver::DOMAIN_INTEGRITY);
        $this->assertEquals('objective', AssessmentStatusResolver::DOMAIN_OBJECTIVE);
        $this->assertEquals('essay', AssessmentStatusResolver::DOMAIN_ESSAY);
        $this->assertEquals('tp', AssessmentStatusResolver::DOMAIN_TP);
    }

    public function testGradebookEligibilityRules(): void
    {
        $validStatuses = [
            AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
            AssessmentStatusResolver::DOMAIN_SESSION => AssessmentStatusResolver::SESSION_SUBMITTED,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => AssessmentStatusResolver::INTEGRITY_VALID,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_GRADED,
            AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_GRADED,
            AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_MASTERED,
        ];
        $this->assertTrue(AssessmentStatusResolver::isEligibleForGradebook($validStatuses));

        // Ineligible if essay incomplete
        $incompleteEssay = $validStatuses;
        $incompleteEssay[AssessmentStatusResolver::DOMAIN_ESSAY] = AssessmentStatusResolver::ESSAY_IN_PROGRESS;
        $this->assertFalse(AssessmentStatusResolver::isEligibleForGradebook($incompleteEssay));

        // Ineligible if integrity compromised
        $compromisedIntegrity = $validStatuses;
        $compromisedIntegrity[AssessmentStatusResolver::DOMAIN_INTEGRITY] = AssessmentStatusResolver::INTEGRITY_FLAGGED;
        $this->assertFalse(AssessmentStatusResolver::isEligibleForGradebook($compromisedIntegrity));

        // Ineligible if session reset
        $resetSession = $validStatuses;
        $resetSession[AssessmentStatusResolver::DOMAIN_SESSION] = AssessmentStatusResolver::SESSION_RESET;
        $this->assertFalse(AssessmentStatusResolver::isEligibleForGradebook($resetSession));
    }

    // =========================================================================
    // 2. QUESTION WEIGHT RESOLVER & VOID HANDLING
    // =========================================================================

    public function testWeightResolverNormalDistribution(): void
    {
        $resolver = new QuestionWeightResolver();

        $items = [
            ['item_id' => 1, 'type' => 'pg', 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'is_void' => false],
            ['item_id' => 3, 'type' => 'pg', 'is_void' => false],
            ['item_id' => 4, 'type' => 'essay', 'is_void' => false],
        ];

        $typeWeights = ['pg' => 60.0, 'essay' => 40.0];
        $result = $resolver->resolve($items, $typeWeights);

        $this->assertFalse($result['is_entire_test_void']);
        $this->assertFalse($result['is_all_type_void_redistributed']);
        $this->assertEquals(100.0, $result['total_weight']);

        // 3 PG items sharing 60% = 20% each
        $this->assertEquals(20.0, $result['items'][0]['weight']);
        $this->assertEquals(20.0, $result['items'][1]['weight']);
        $this->assertEquals(20.0, $result['items'][2]['weight']);
        // 1 Essay item having 40%
        $this->assertEquals(40.0, $result['items'][3]['weight']);
    }

    public function testWeightResolverAllTypeVoidOptionB(): void
    {
        $resolver = new QuestionWeightResolver();

        // All essay items void: PG should redistribute to 100%
        $items = [
            ['item_id' => 1, 'type' => 'pg', 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'is_void' => false],
            ['item_id' => 3, 'type' => 'essay', 'is_void' => true],
            ['item_id' => 4, 'type' => 'essay', 'is_void' => true],
        ];

        $typeWeights = ['pg' => 70.0, 'essay' => 30.0];
        $result = $resolver->resolve($items, $typeWeights);

        $this->assertFalse($result['is_entire_test_void']);
        $this->assertTrue($result['is_all_type_void_redistributed']);
        $this->assertEquals(100.0, $result['total_weight']);

        // Essay items have 0 weight
        $this->assertEquals(0.0, $result['items'][2]['weight']);
        $this->assertEquals(0.0, $result['items'][3]['weight']);

        // 2 PG items share 100% = 50% each
        $this->assertEquals(50.0, $result['items'][0]['weight']);
        $this->assertEquals(50.0, $result['items'][1]['weight']);
    }

    public function testWeightResolverEntireTestVoid(): void
    {
        $resolver = new QuestionWeightResolver();

        $items = [
            ['item_id' => 1, 'type' => 'pg', 'is_void' => true],
            ['item_id' => 2, 'type' => 'essay', 'is_void' => true],
        ];

        $result = $resolver->resolve($items, ['pg' => 50, 'essay' => 50]);

        $this->assertTrue($result['is_entire_test_void']);
        $this->assertEquals(0.0, $result['total_weight']);
        $this->assertEquals(0.0, $result['items'][0]['weight']);
        $this->assertEquals(0.0, $result['items'][1]['weight']);
    }

    // =========================================================================
    // 3. OBJECTIVE SCORING SERVICE
    // =========================================================================

    public function testObjectiveScoringCalculations(): void
    {
        $scorer = new ObjectiveScoringService();

        $items = [
            ['item_id' => 101, 'type' => 'pg', 'weight' => 25.0, 'is_void' => false, 'tp_id' => 1, 'key' => 'A'],
            ['item_id' => 102, 'type' => 'pg', 'weight' => 25.0, 'is_void' => false, 'tp_id' => 1, 'key' => 'B'],
            ['item_id' => 103, 'type' => 'pg', 'weight' => 25.0, 'is_void' => false, 'tp_id' => 2, 'key' => 'C'],
            ['item_id' => 104, 'type' => 'pg', 'weight' => 25.0, 'is_void' => true,  'tp_id' => 2, 'key' => 'D'],
        ];

        $answers = [
            ['item_id' => 101, 'student_answer' => 'A'], // Correct (+25)
            ['item_id' => 102, 'student_answer' => 'X'], // Wrong
            ['item_id' => 103, 'student_answer' => ''],  // Unanswered (+0)
            ['item_id' => 104, 'student_answer' => 'D'], // Void (+0)
        ];

        $result = $scorer->grade($items, $answers);

        $this->assertEquals(4, $result['total_items']);
        $this->assertEquals(3, $result['active_items']);
        $this->assertEquals(1, $result['void_items']);
        $this->assertEquals(2, $result['answered_items']);
        $this->assertEquals(1, $result['correct_items']);
        $this->assertEquals(25.0, $result['weighted_score']);
        // Scale score: (25.0 / 75.0) * 100 = 33.33%
        $this->assertEquals(33.33, $result['objective_scale_score']);
        $this->assertEquals(AssessmentStatusResolver::OBJ_GRADED, $result['status']);
    }

    // =========================================================================
    // 4. ESSAY GRADING SERVICE & OD-PATH-B ISOLATION
    // =========================================================================

    public function testEssayGradingCompleteAndIncomplete(): void
    {
        $essayService = new EssayGradingService();

        $items = [
            ['item_id' => 201, 'weight' => 20.0, 'is_void' => false, 'max_points' => 100.0],
            ['item_id' => 202, 'weight' => 20.0, 'is_void' => false, 'max_points' => 50.0],
        ];

        // Case 1: Partial / Incomplete grading
        $partialGrades = [
            ['item_id' => 201, 'score_earned' => 80.0, 'is_graded' => true],
            // 202 is ungraded
        ];

        $partialResult = $essayService->grade($items, $partialGrades);
        $this->assertFalse($partialResult['is_complete']);
        $this->assertEquals(AssessmentStatusResolver::ESSAY_IN_PROGRESS, $partialResult['status']);
        $this->assertTrue($partialResult['od_path_b_enforced']);

        // Case 2: Complete grading
        $completeGrades = [
            ['item_id' => 201, 'score_earned' => 100.0, 'is_graded' => true], // 20 * 1.0 = 20
            ['item_id' => 202, 'score_earned' => 25.0,  'is_graded' => true], // 20 * (25/50) = 10
        ];

        $completeResult = $essayService->grade($items, $completeGrades);
        $this->assertTrue($completeResult['is_complete']);
        $this->assertEquals(AssessmentStatusResolver::ESSAY_GRADED, $completeResult['status']);
        $this->assertEquals(30.0, $completeResult['weighted_score']);
        $this->assertEquals(75.0, $completeResult['essay_scale_score']); // 30 / 40 * 100 = 75
    }

    // =========================================================================
    // 5. TP EVIDENCE SUFFICIENCY (CONJUNCTIVE Smax >= 10.00 & N_unique >= 2)
    // =========================================================================

    public function testTpEvidenceSufficiencyBoundaries(): void
    {
        $evaluator = new TpEvidenceEvaluator();

        // Sub-test A: Smax = 10.00, N_unique = 2 -> Sufficient!
        $breakdownA = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 10, 'weight' => 5.0, 'weighted_score' => 5.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 10, 'weight' => 5.0, 'weighted_score' => 5.0, 'is_void' => false],
        ];
        $resA = $evaluator->evaluate($breakdownA, [['tp_id' => 10, 'threshold' => 75.0]]);
        $this->assertTrue($resA[10]['is_sufficient_evidence']);
        $this->assertEquals(AssessmentStatusResolver::TP_MASTERED, $resA[10]['status']);
        $this->assertTrue($resA[10]['is_mastered']);

        // Sub-test B: Smax = 9.99, N_unique = 2 -> Insufficient (Smax < 10.00)!
        $breakdownB = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 10, 'weight' => 4.99, 'weighted_score' => 4.99, 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 10, 'weight' => 5.00, 'weighted_score' => 5.00, 'is_void' => false],
        ];
        $resB = $evaluator->evaluate($breakdownB, [['tp_id' => 10, 'threshold' => 75.0]]);
        $this->assertFalse($resB[10]['is_sufficient_evidence']);
        $this->assertEquals(AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE, $resB[10]['status']);
        $this->assertNull($resB[10]['is_mastered']); // Indeterminate

        // Sub-test C: Smax = 20.00, N_unique = 1 -> Insufficient (N_unique < 2)!
        $breakdownC = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 10, 'weight' => 20.0, 'weighted_score' => 20.0, 'is_void' => false],
        ];
        $resC = $evaluator->evaluate($breakdownC, [['tp_id' => 10, 'threshold' => 75.0]]);
        $this->assertFalse($resC[10]['is_sufficient_evidence']);
        $this->assertEquals(AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE, $resC[10]['status']);
        $this->assertNull($resC[10]['is_mastered']);

        // Sub-test D: OD-PATH-B exclusion: essay item with TP mapped must NOT count towards Smax or N_unique!
        $breakdownD = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 10, 'weight' => 8.0, 'weighted_score' => 8.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'essay', 'tp_id' => 10, 'weight' => 12.0, 'weighted_score' => 12.0, 'is_void' => false],
        ];
        $resD = $evaluator->evaluate($breakdownD, [['tp_id' => 10, 'threshold' => 75.0]]);
        // Only PG item (weight 8.0, count 1) counts: Smax = 8.0, N_unique = 1 -> Insufficient!
        $this->assertFalse($resD[10]['is_sufficient_evidence']);
        $this->assertEquals(8.0, $resD[10]['s_max']);
        $this->assertEquals(1, $resD[10]['n_unique']);
    }

    public function testTpMasteryThresholdEvaluation(): void
    {
        $evaluator = new TpEvidenceEvaluator();

        // 2 items, Smax = 20.0, student gets 14.0 -> 70%
        $breakdown = [
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 1, 'weight' => 10.0, 'weighted_score' => 10.0, 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 1, 'weight' => 10.0, 'weighted_score' => 4.0,  'is_void' => false],
        ];

        // At 75% threshold -> NOT_MASTERED
        $res75 = $evaluator->evaluate($breakdown, [['tp_id' => 1, 'threshold' => 75.0]]);
        $this->assertEquals(70.0, $res75[1]['student_percentage']);
        $this->assertEquals(AssessmentStatusResolver::TP_NOT_MASTERED, $res75[1]['status']);
        $this->assertFalse($res75[1]['is_mastered']);

        // At 65% threshold -> MASTERED
        $res65 = $evaluator->evaluate($breakdown, [['tp_id' => 1, 'threshold' => 65.0]]);
        $this->assertEquals(70.0, $res65[1]['student_percentage']);
        $this->assertEquals(AssessmentStatusResolver::TP_MASTERED, $res65[1]['status']);
        $this->assertTrue($res65[1]['is_mastered']);
    }

    // =========================================================================
    // 6. MASTERY RATE CALCULATOR & MANDATORY REGRESSION BENCHMARK
    // =========================================================================

    /**
     * Mandatory Regression Benchmark:
     * 13 students total in cohort:
     * - 6 MASTERED
     * - 4 NOT_MASTERED
     * - 3 NOT_ATTEMPTED
     * -> CR_murni = 6 / 10 = 60.0%
     * -> CR_kohort = 6 / 13 = 46.15%
     */
    public function testMasteryRateMandatoryRegressionBenchmark(): void
    {
        $calculator = new MasteryRateCalculator();

        $cohort = [];

        // 6 Mastered
        for ($i = 1; $i <= 6; $i++) {
            $cohort[] = ['student_id' => "M_$i", 'status' => AssessmentStatusResolver::TP_MASTERED];
        }

        // 4 Not Mastered
        for ($i = 1; $i <= 4; $i++) {
            $cohort[] = ['student_id' => "NM_$i", 'status' => AssessmentStatusResolver::TP_NOT_MASTERED];
        }

        // 3 Not Attempted
        for ($i = 1; $i <= 3; $i++) {
            $cohort[] = ['student_id' => "NA_$i", 'status' => AssessmentStatusResolver::TP_NOT_ATTEMPTED];
        }

        $this->assertCount(13, $cohort);

        $result = $calculator->calculate($cohort, 'TP-01', 'TP-ALGEBRA');

        $this->assertEquals(13, $result['total_students']);
        $this->assertEquals(6, $result['mastered_count']);
        $this->assertEquals(4, $result['not_mastered_count']);
        $this->assertEquals(10, $result['definitive_count']);
        $this->assertEquals(3, $result['not_attempted_count']);
        $this->assertTrue($result['has_definitive_evidence']);

        // Assert exact benchmark values
        $this->assertSame(60.0, $result['cr_murni'], 'CR_murni must equal exactly 60.0%');
        $this->assertSame(46.15, $result['cr_kohort'], 'CR_kohort must equal exactly 46.15%');
    }

    public function testMasteryRateExcludesInsufficientEvidenceFromDenominators(): void
    {
        $calculator = new MasteryRateCalculator();

        // 15 students: 6 M, 4 NM, 3 NA, plus 2 INSUFFICIENT_EVIDENCE
        $cohort = [];
        for ($i = 1; $i <= 6; $i++) {
            $cohort[] = ['student_id' => "M_$i", 'status' => AssessmentStatusResolver::TP_MASTERED];
        }
        for ($i = 1; $i <= 4; $i++) {
            $cohort[] = ['student_id' => "NM_$i", 'status' => AssessmentStatusResolver::TP_NOT_MASTERED];
        }
        for ($i = 1; $i <= 3; $i++) {
            $cohort[] = ['student_id' => "NA_$i", 'status' => AssessmentStatusResolver::TP_NOT_ATTEMPTED];
        }
        for ($i = 1; $i <= 2; $i++) {
            $cohort[] = ['student_id' => "IE_$i", 'status' => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE];
        }

        $result = $calculator->calculate($cohort, 'TP-01');

        $this->assertEquals(15, $result['total_students']);
        $this->assertEquals(2, $result['insufficient_evidence_count']);
        // Crucial: Denominators must ignore INSUFFICIENT_EVIDENCE
        $this->assertSame(60.0, $result['cr_murni']);
        $this->assertSame(46.15, $result['cr_kohort']);
    }

    // =========================================================================
    // 7. END-TO-END ASSESSMENT ENGINE SERVICE
    // =========================================================================

    public function testAssessmentEngineEndToEndEvaluation(): void
    {
        $engine = new AssessmentEngineService();

        $testConfig = [
            'test_id' => 500,
            'passing_grade' => 75.0,
            'type_weights' => ['pg' => 60.0, 'essay' => 40.0],
            'tp_definitions' => [
                ['tp_id' => 1, 'tp_code' => 'TP-1', 'threshold' => 75.0],
                ['tp_id' => 2, 'tp_code' => 'TP-2', 'threshold' => 75.0],
            ],
        ];

        $items = [
            // Objective items (60% total -> 20% each)
            ['item_id' => 1, 'type' => 'pg', 'tp_id' => 1, 'key' => 'A', 'is_void' => false],
            ['item_id' => 2, 'type' => 'pg', 'tp_id' => 1, 'key' => 'B', 'is_void' => false],
            ['item_id' => 3, 'type' => 'pg', 'tp_id' => 2, 'key' => 'C', 'is_void' => false],
            // Essay item (40% total)
            ['item_id' => 4, 'type' => 'essay', 'max_points' => 100.0, 'is_void' => false],
        ];

        $answers = [
            ['item_id' => 1, 'student_answer' => 'A'], // Correct (20)
            ['item_id' => 2, 'student_answer' => 'B'], // Correct (20)
            ['item_id' => 3, 'student_answer' => 'X'], // Wrong (0)
            ['item_id' => 4, 'score_earned' => 80.0, 'is_graded' => true], // Essay 80% of 40 = 32
        ];

        $context = [
            'student_id' => 1001,
            'session_id' => 2001,
            'session_status' => AssessmentStatusResolver::SESSION_SUBMITTED,
            'participation_status' => AssessmentStatusResolver::PARTICIPATION_PRESENT,
            'integrity_status' => AssessmentStatusResolver::INTEGRITY_VALID,
        ];

        $result = $engine->evaluateStudent($testConfig, $items, $answers, $context);

        $this->assertInstanceOf(AssessmentResult::class, $result);
        $this->assertEquals(1001, $result->getStudentId());
        $this->assertEquals(500, $result->getTestId());

        // Final score: 20 + 20 + 32 = 72.0
        $this->assertEquals(72.0, $result->getFinalScore());
        $this->assertTrue($result->isFullyGraded());
        $this->assertTrue($result->isEligibleForGradebook());

        // TP Evaluations:
        // TP 1 has 2 items (weight 20 + 20 = 40 >= 10, N=2 >= 2) -> sufficient!
        // Student got 40/40 = 100% >= 75% -> MASTERED
        $tpEvals = $result->getTpEvaluations();
        $this->assertEquals(AssessmentStatusResolver::TP_MASTERED, $tpEvals[1]['status']);

        // TP 2 has 1 item (N=1 < 2) -> INSUFFICIENT_EVIDENCE
        $this->assertEquals(AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE, $tpEvals[2]['status']);

        // Serialization
        $arrayData = $result->toArray();
        $this->assertIsArray($arrayData);
        $this->assertArrayHasKey('final_score', $arrayData);
        $this->assertJson($result->toJson());
    }

    public function testAssessmentEngineEntireTestVoidHandling(): void
    {
        $engine = new AssessmentEngineService();

        $testConfig = [
            'test_id' => 600,
            'type_weights' => ['pg' => 100.0],
        ];

        $items = [
            ['item_id' => 1, 'type' => 'pg', 'is_void' => true],
            ['item_id' => 2, 'type' => 'pg', 'is_void' => true],
        ];

        $result = $engine->evaluateStudent($testConfig, $items, [], ['student_id' => 10]);

        $this->assertNull($result->getFinalScore());
        $this->assertFalse($result->hasScore());
        $this->assertEquals(AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS, $result->getStatus(AssessmentStatusResolver::DOMAIN_TEST));
        $this->assertFalse($result->isEligibleForGradebook());
    }
}
