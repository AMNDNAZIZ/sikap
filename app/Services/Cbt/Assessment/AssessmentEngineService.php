<?php

namespace App\Services\Cbt\Assessment;

/**
 * AssessmentEngineService
 *
 * ESPATA Assessment Intelligence V3 - Stage D
 * Master orchestrator for assessment evaluation and psychometric analysis.
 *
 * Coordinates:
 * 1. QuestionWeightResolver (Weight distribution, ALL-TYPE-VOID Option B, ENTIRE_TEST_VOID)
 * 2. ObjectiveScoringService (Objective grading, scale 0-100)
 * 3. EssayGradingService (Essay aggregation, grading completeness, OD-PATH-B enforcement)
 * 4. TpEvidenceEvaluator (Conjunctive rule: Smax >= 10.00 AND N_unique >= 2)
 * 5. AssessmentStatusResolver (7-domain status resolution)
 * 6. MasteryRateCalculator (CR_murni and CR_kohort calculation)
 */
class AssessmentEngineService
{
    private QuestionWeightResolver $weightResolver;
    private ObjectiveScoringService $objectiveScoring;
    private EssayGradingService $essayGrading;
    private TpEvidenceEvaluator $tpEvaluator;
    private MasteryRateCalculator $masteryCalculator;

    public function __construct(
        ?QuestionWeightResolver $weightResolver = null,
        ?ObjectiveScoringService $objectiveScoring = null,
        ?EssayGradingService $essayGrading = null,
        ?TpEvidenceEvaluator $tpEvaluator = null,
        ?MasteryRateCalculator $masteryCalculator = null
    ) {
        $this->weightResolver = $weightResolver ?? new QuestionWeightResolver();
        $this->objectiveScoring = $objectiveScoring ?? new ObjectiveScoringService();
        $this->essayGrading = $essayGrading ?? new EssayGradingService();
        $this->tpEvaluator = $tpEvaluator ?? new TpEvidenceEvaluator();
        $this->masteryCalculator = $masteryCalculator ?? new MasteryRateCalculator();
    }

    /**
     * Evaluate an individual student assessment session.
     *
     * @param array $testConfig Configuration of the test:
     *                          ['test_id' => ..., 'type_weights' => ['pg' => 60, 'essay' => 40], 'tp_definitions' => [...], 'passing_grade' => 75]
     * @param array $items List of test items:
     *                     [['item_id' => ..., 'type' => 'pg'|'essay', 'weight' => ..., 'tp_id' => ..., 'key' => ..., 'is_void' => bool], ...]
     * @param array $answers List of student answers / grading records:
     *                       [['item_id' => ..., 'student_answer' => ..., 'is_correct' => bool, 'score_earned' => float, 'is_graded' => bool], ...]
     * @param array $context Session context:
     *                       ['student_id' => ..., 'session_id' => ..., 'session_status' => ..., 'participation_status' => ..., 'integrity_status' => ...]
     * @return AssessmentResult
     */
    public function evaluateStudent(
        array $testConfig,
        array $items,
        array $answers,
        array $context = []
    ): AssessmentResult {
        $studentId = $context['student_id'] ?? 'unknown';
        $testId = $testConfig['test_id'] ?? ($context['test_id'] ?? 'unknown');
        $sessionId = $context['session_id'] ?? null;

        $sessionStatus = $context['session_status'] ?? AssessmentStatusResolver::SESSION_SUBMITTED;
        $participationStatus = $context['participation_status'] ?? AssessmentStatusResolver::PARTICIPATION_PRESENT;
        $integrityStatus = $context['integrity_status'] ?? AssessmentStatusResolver::INTEGRITY_VALID;

        // Step 1: Resolve weights across all items (ALL-TYPE-VOID Option B & ENTIRE_TEST_VOID)
        $configuredTypeWeights = $testConfig['type_weights'] ?? [];
        $weightResult = $this->weightResolver->resolve($items, $configuredTypeWeights);

        // Step 2: Check for ENTIRE_TEST_VOID
        if ($weightResult['is_entire_test_void']) {
            $statuses = [
                AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS,
                AssessmentStatusResolver::DOMAIN_SESSION => $sessionStatus,
                AssessmentStatusResolver::DOMAIN_PARTICIPATION => $participationStatus,
                AssessmentStatusResolver::DOMAIN_INTEGRITY => $integrityStatus,
                AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_NOT_APPLICABLE,
                AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_NOT_APPLICABLE,
                AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE,
            ];

            return new AssessmentResult(
                $studentId,
                $testId,
                $sessionId,
                $statuses,
                null, // Explicitly null (differentiating from earned zero) when entire test is void
                null,
                0.0,
                0.0,
                $weightResult,
                [],
                [],
                false,
                ['void_reason' => 'Entire test void: no valid questions']
            );
        }

        // Check if student attempted the test
        $isAttempted = ($participationStatus === AssessmentStatusResolver::PARTICIPATION_PRESENT &&
                        $sessionStatus !== AssessmentStatusResolver::SESSION_VOID &&
                        $sessionStatus !== AssessmentStatusResolver::SESSION_RESET);

        if (!$isAttempted) {
            $statuses = [
                AssessmentStatusResolver::DOMAIN_TEST => AssessmentStatusResolver::TEST_PUBLISHED,
                AssessmentStatusResolver::DOMAIN_SESSION => $sessionStatus,
                AssessmentStatusResolver::DOMAIN_PARTICIPATION => $participationStatus,
                AssessmentStatusResolver::DOMAIN_INTEGRITY => $integrityStatus,
                AssessmentStatusResolver::DOMAIN_OBJECTIVE => AssessmentStatusResolver::OBJ_NOT_APPLICABLE,
                AssessmentStatusResolver::DOMAIN_ESSAY => AssessmentStatusResolver::ESSAY_NOT_APPLICABLE,
                AssessmentStatusResolver::DOMAIN_TP => AssessmentStatusResolver::TP_NOT_ATTEMPTED,
            ];

            return new AssessmentResult(
                $studentId,
                $testId,
                $sessionId,
                $statuses,
                null,
                null,
                0.0,
                0.0,
                $weightResult,
                [],
                [],
                true,
                ['attempt_status' => 'Not attempted or void session']
            );
        }

        // Step 3: Separate items into objective and essay using resolved weights
        $resolvedItems = $weightResult['items'];
        $objectiveItems = [];
        $essayItems = [];

        foreach ($resolvedItems as $item) {
            $type = strtolower($item['type'] ?? 'pg');
            if ($type === 'essay' || $type === 'uraian') {
                $essayItems[] = $item;
            } else {
                $objectiveItems[] = $item;
            }
        }

        // Step 4: Grade objective items
        $objGrading = $this->objectiveScoring->grade($objectiveItems, $answers);

        // Step 5: Grade essay items (OD-PATH-B)
        $essayGrading = $this->essayGrading->grade($essayItems, $answers);

        // Step 6: Total Final Score Aggregation
        $isFullyGraded = $essayGrading['is_complete'];

        if (!$isFullyGraded) {
            // Essay grading is incomplete. Total test score cannot be definitively determined yet.
            // Gradebook report score MUST be null (PENDING_ESSAY).
            $finalScore = null;
            $reportScore = null;
        } else {
            // Sum of weighted scores from objective and essay
            $finalScore = $objGrading['weighted_score'] + $essayGrading['weighted_score'];
            $finalScore = min(100.0, max(0.0, $finalScore));
            $reportScore = round($finalScore); // Standard gradebook rounded representation
        }

        // Step 7: Grade Completeness & Multi-Domain Status Resolution

        $testStatus = AssessmentStatusResolver::TEST_PUBLISHED;
        $objStatus = $objGrading['status'];
        $essayStatus = $essayGrading['status'];

        // Step 8: Evaluate TP evidence (strictly from objective items - OD-PATH-B)
        $tpDefinitions = $testConfig['tp_definitions'] ?? [];
        $tpEvaluations = $this->tpEvaluator->evaluate($objGrading['items_breakdown'], $tpDefinitions, $isAttempted);

        // Derive overall TP evaluation status
        $tpOverallStatus = $this->deriveOverallTpStatus($tpEvaluations);

        $statuses = [
            AssessmentStatusResolver::DOMAIN_TEST => $testStatus,
            AssessmentStatusResolver::DOMAIN_SESSION => $sessionStatus,
            AssessmentStatusResolver::DOMAIN_PARTICIPATION => $participationStatus,
            AssessmentStatusResolver::DOMAIN_INTEGRITY => $integrityStatus,
            AssessmentStatusResolver::DOMAIN_OBJECTIVE => $objStatus,
            AssessmentStatusResolver::DOMAIN_ESSAY => $essayStatus,
            AssessmentStatusResolver::DOMAIN_TP => $tpOverallStatus,
        ];

        // Combine items breakdown for audit trail
        $allBreakdowns = array_merge($objGrading['items_breakdown'], $essayGrading['items_breakdown']);

        return new AssessmentResult(
            $studentId,
            $testId,
            $sessionId,
            $statuses,
            $finalScore,
            $reportScore,
            $objGrading['objective_scale_score'],
            $essayGrading['essay_scale_score'],
            $weightResult,
            $allBreakdowns,
            $tpEvaluations,
            $isFullyGraded,
            [
                'objective_weighted_score' => $objGrading['weighted_score'],
                'essay_weighted_score' => $essayGrading['weighted_score'],
                'all_type_void_redistributed' => $weightResult['is_all_type_void_redistributed'],
                'od_path_b_enforced' => true,
            ]
        );
    }

    /**
     * Evaluate a full cohort of students, returning both individual AssessmentResults
     * and aggregated TP mastery metrics (CR_murni and CR_kohort).
     *
     * @param array $studentsData List of student entries:
     *                            [['context' => [...], 'answers' => [...]], ...]
     * @param array $testConfig Test configuration
     * @param array $items Items list
     * @return array ['student_results' => AssessmentResult[], 'cohort_tp_metrics' => array]
     */
    public function evaluateCohort(array $studentsData, array $testConfig, array $items): array
    {
        $studentResults = [];
        $cohortEvaluations = [];

        foreach ($studentsData as $studentEntry) {
            $context = $studentEntry['context'] ?? [];
            $answers = $studentEntry['answers'] ?? [];

            $result = $this->evaluateStudent($testConfig, $items, $answers, $context);
            $studentResults[] = $result;

            $cohortEvaluations[] = [
                'student_id' => $result->getStudentId(),
                'tp_evaluations' => $result->getTpEvaluations(),
            ];
        }

        // Calculate cohort TP completion rates (CR_murni & CR_kohort)
        $cohortTpMetrics = $this->masteryCalculator->calculateCohort($cohortEvaluations);

        return [
            'student_results' => $studentResults,
            'cohort_tp_metrics' => $cohortTpMetrics,
        ];
    }

    /**
     * Helper to derive overall TP domain status.
     */
    private function deriveOverallTpStatus(array $tpEvaluations): string
    {
        if (empty($tpEvaluations)) {
            return AssessmentStatusResolver::TP_NOT_APPLICABLE;
        }

        $allMastered = true;
        $anyDefinitive = false;
        $anyInsufficient = false;

        foreach ($tpEvaluations as $eval) {
            $status = $eval['status'] ?? null;
            if ($status === AssessmentStatusResolver::TP_MASTERED) {
                $anyDefinitive = true;
            } elseif ($status === AssessmentStatusResolver::TP_NOT_MASTERED) {
                $anyDefinitive = true;
                $allMastered = false;
            } elseif ($status === AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE) {
                $anyInsufficient = true;
                $allMastered = false;
            } elseif ($status === AssessmentStatusResolver::TP_NOT_ATTEMPTED) {
                return AssessmentStatusResolver::TP_NOT_ATTEMPTED;
            }
        }

        if ($allMastered && $anyDefinitive) {
            return AssessmentStatusResolver::TP_MASTERED;
        }

        if (!$anyDefinitive && $anyInsufficient) {
            return AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE;
        }

        return AssessmentStatusResolver::TP_NOT_MASTERED;
    }
}

