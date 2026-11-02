<?php

namespace App\Services\Cbt\Shadow;

use App\Services\Cbt\Assessment\AssessmentEngineService;
use App\Services\Cbt\Assessment\AssessmentResult;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Reporting\AssessmentReportingService;
use App\Services\Cbt\Gradebook\GradebookMappingRegistry;
use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;

/**
 * V3ShadowEvaluator
 *
 * ESPATA Assessment Intelligence V3 - Stage F1
 * Executes Assessment Engine V3 and Stage E reporting/gradebook mapping in read-only shadow mode.
 *
 * SAFETY INVARIANT:
 * Performs ZERO writes, ZERO DML, and ZERO gradebook mutations.
 */
class V3ShadowEvaluator
{
    private AssessmentEngineService $engine;
    private AssessmentReportingService $reporting;
    private GradebookMappingRegistry $mappingRegistry;
    private AttemptSelectorPolicy $attemptPolicy;

    public function __construct(
        ?AssessmentEngineService $engine = null,
        ?AssessmentReportingService $reporting = null,
        ?GradebookMappingRegistry $mappingRegistry = null,
        ?AttemptSelectorPolicy $attemptPolicy = null
    ) {
        $this->engine = $engine ?? new AssessmentEngineService();
        $this->reporting = $reporting ?? new AssessmentReportingService();
        $this->mappingRegistry = $mappingRegistry ?? new GradebookMappingRegistry();
        $this->attemptPolicy = $attemptPolicy ?? new AttemptSelectorPolicy();
    }

    /**
     * Evaluate a student test session in shadow mode.
     *
     * @param array $testConfig
     * @param array $items
     * @param array $answers
     * @param array $context
     * @param array $options
     * @return array
     */
    public function evaluateCase(
        array $testConfig,
        array $items,
        array $answers,
        array $context = [],
        array $options = []
    ): array {
        $studentId = $context['student_id'] ?? 'unknown';
        $testId = $testConfig['test_id'] ?? ($context['test_id'] ?? 'unknown');

        // Check for multiple candidate sessions if provided
        $candidateSessions = $options['candidate_sessions'] ?? null;
        $attemptAudit = null;
        $attemptStatus = 'SINGLE_ATTEMPT';

        if (!empty($candidateSessions) && is_array($candidateSessions)) {
            $policy = $options['attempt_policy'] ?? AttemptSelectorPolicy::POLICY_REQUIRE_MANUAL;
            $explicitSessionId = $options['explicit_session_id'] ?? null;

            $attemptResolution = $this->attemptPolicy->selectAttempt(
                $candidateSessions,
                $studentId,
                $testId,
                $policy,
                $explicitSessionId
            );

            $attemptStatus = $attemptResolution['status'];
            $attemptAudit = $attemptResolution;

            if ($attemptResolution['status'] !== 'SELECTED') {
                // Return shadow response indicating ambiguous attempt selection
                return [
                    'student_id'          => $studentId,
                    'test_id'             => $testId,
                    'session_id'          => null,
                    'final_score'         => null,
                    'report_score'        => null,
                    'is_eligible'         => false,
                    'is_entire_test_void' => false,
                    'statuses'            => [
                        AssessmentStatusResolver::DOMAIN_TEST    => AssessmentStatusResolver::TEST_ACTIVE,
                        AssessmentStatusResolver::DOMAIN_SESSION => 'AMBIGUOUS_SESSIONS',
                    ],
                    'attempt_status'      => $attemptStatus,
                    'attempt_audit'       => $attemptAudit,
                    'reporting'           => null,
                    'gradebook_simulation'=> null,
                ];
            }

            // Bind selected session
            $context['session_id'] = $attemptResolution['selected_session']['id'];
        }

        // Run authentic V3 Assessment Engine
        $assessmentResult = $this->engine->evaluateStudent($testConfig, $items, $answers, $context);

        // Run authentic V3 Reporting Projection
        $academicContext = $options['academic_context'] ?? [];
        $reportingOutput = $this->reporting->projectStudentReport($assessmentResult, $academicContext);

        // Simulate Gradebook Mapping (Zero DML)
        $componentType = $options['component_type'] ?? 'pts';
        $simulatedTargetTable = null;
        $matchCriteria = [];
        try {
            $simulatedTargetTable = $this->mappingRegistry->resolveTargetTable($componentType);
            $matchCriteria = $this->mappingRegistry->getMatchCriteria($simulatedTargetTable);
        } catch (\Throwable $e) {
            $simulatedTargetTable = 'UNAUTHORIZED_OR_UNKNOWN';
        }

        $testStatus = $assessmentResult->getStatus(AssessmentStatusResolver::DOMAIN_TEST);
        $isEntireTestVoid = ($testStatus === AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS);

        return [
            'student_id'          => $assessmentResult->getStudentId(),
            'test_id'             => $assessmentResult->getTestId(),
            'session_id'          => $assessmentResult->getSessionId(),
            'final_score'         => $assessmentResult->getFinalScore(),
            'report_score'        => $assessmentResult->getReportScore(),
            'has_score'           => $assessmentResult->hasScore(),
            'is_fully_graded'     => $assessmentResult->isFullyGraded(),
            'is_eligible'         => $assessmentResult->isEligibleForGradebook(),
            'is_entire_test_void' => $isEntireTestVoid,
            'statuses'            => $assessmentResult->getStatuses(),
            'tp_evaluations'      => $assessmentResult->getTpEvaluations(),
            'attempt_status'      => $attemptStatus,
            'attempt_audit'       => $attemptAudit,
            'reporting'           => $reportingOutput,
            'gradebook_simulation'=> [
                'target_table'   => $simulatedTargetTable,
                'match_criteria' => $matchCriteria,
                'simulated_score'=> $assessmentResult->isEligibleForGradebook() ? $assessmentResult->getReportScore() : null,
                'would_write'    => false, // Always false in shadow mode
            ],
            'raw_result'          => $assessmentResult,
        ];
    }
}

