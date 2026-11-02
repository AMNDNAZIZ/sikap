<?php

namespace App\Services\Cbt\Reporting;

use App\Services\Cbt\Assessment\AssessmentResult;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Assessment\MasteryRateCalculator;

/**
 * AssessmentReportingService
 *
 * ESPATA Assessment Intelligence V3 - Stage E
 * Responsible for projecting Assessment Engine outputs into distinct, non-overlapping reporting perspectives:
 * 1. Test Final Score (Raw objective + essay weighted scores)
 * 2. Gradebook Report Score (Eligible score for academic records, null if ineligible/terminal void)
 * 3. Learning Objective (TP) Mastery Analytics (Smax, N_unique, threshold, sufficiency)
 * 4. Cohort Analytics (CR_murni, CR_kohort, status distributions)
 *
 * MANDATORY ARCHITECTURAL POLICY:
 * Completion Rates (CR_murni & CR_kohort) MUST NEVER be transformed into gradebook scores,
 * and gradebook scores MUST NEVER be converted into mastery rates.
 */
class AssessmentReportingService
{
    public const REPORTING_VERSION = 'V3.26-STAGE_E';
    public const DECISION_ID_ESSAY = 'DEC-ESS-01';

    private MasteryRateCalculator $masteryCalculator;

    public function __construct(?MasteryRateCalculator $masteryCalculator = null)
    {
        $this->masteryCalculator = $masteryCalculator ?? new MasteryRateCalculator();
    }

    /**
     * Project an individual student assessment result into a structured report.
     *
     * @param AssessmentResult $result
     * @param array $academicContext ['subject_name' => ..., 'class_name' => ..., 'academic_year' => ..., 'semester' => ...]
     * @return array
     */
    public function projectStudentReport(AssessmentResult $result, array $academicContext = []): array
    {
        $testStatus = $result->getStatus(AssessmentStatusResolver::DOMAIN_TEST);
        $essayStatus = $result->getStatus(AssessmentStatusResolver::DOMAIN_ESSAY);
        $isEntireTestVoid = ($testStatus === AssessmentStatusResolver::TEST_NO_VALID_QUESTIONS);
        $isEligible = $result->isEligibleForGradebook();
        $isEssayPending = !$result->isFullyGraded();

        // 1. Nilai Tes Perspective
        $testScoreProjection = [
            'final_score'            => $result->getFinalScore(),
            'has_score'              => $result->hasScore(),
            'objective_scale_score'  => $result->getObjectiveScore(),
            'essay_scale_score'      => $result->getEssayScore(),
            'essay_grading_status'   => $essayStatus,
            'is_fully_graded'        => $result->isFullyGraded(),
            'is_entire_test_void'    => $isEntireTestVoid,
            'decision_id'            => $isEssayPending ? self::DECISION_ID_ESSAY : null,
            'calculation_version'    => self::REPORTING_VERSION,
        ];

        // 2. Nilai Rapor Perspective (strictly guarded under DEC-ESS-01)
        $ineligibleReasons = [];
        if (!$isEligible) {
            if ($isEntireTestVoid) {
                $ineligibleReasons[] = 'Test has no valid questions (NO_VALID_QUESTIONS)';
            }
            if ($isEssayPending) {
                $ineligibleReasons[] = 'Essay grading incomplete (DEC-ESS-01: HOLD until teacher grading complete)';
            }
            $sessionStatus = $result->getStatus(AssessmentStatusResolver::DOMAIN_SESSION);
            if (in_array($sessionStatus, [AssessmentStatusResolver::SESSION_RESET, AssessmentStatusResolver::SESSION_VOID], true)) {
                $ineligibleReasons[] = "Session status is {$sessionStatus}";
            }
            $integrityStatus = $result->getStatus(AssessmentStatusResolver::DOMAIN_INTEGRITY);
            if (in_array($integrityStatus, [AssessmentStatusResolver::INTEGRITY_FLAGGED, AssessmentStatusResolver::INTEGRITY_SUSPICIOUS, AssessmentStatusResolver::INTEGRITY_LOCKED_CHEAT], true)) {
                $ineligibleReasons[] = "Session integrity flagged ({$integrityStatus})";
            }
        }

        $gradebookProjection = [
            'is_eligible'       => $isEligible,
            'report_score'      => $isEligible ? $result->getReportScore() : null,
            'ineligible_reason' => empty($ineligibleReasons) ? null : implode('; ', $ineligibleReasons),
            'target_candidate'  => $isEligible,
            'decision_id'       => $isEssayPending ? self::DECISION_ID_ESSAY : null,
            'hold_policy'       => $isEssayPending ? 'DEC-ESS-01 (HOLD)' : null,
        ];

        // 3. Analitik TP Perspective (Strictly separated from gradebook score)
        $tpEvaluations = $result->getTpEvaluations();
        $tpAnalytics = [];
        foreach ($tpEvaluations as $tpId => $tpData) {
            $tpAnalytics[$tpId] = [
                'tp_id'                  => $tpId,
                'tp_code'                => $tpData['tp_code'] ?? (string)$tpId,
                's_max'                  => $tpData['s_max'] ?? 0.0,
                'n_unique'               => $tpData['n_unique'] ?? 0,
                'is_sufficient_evidence' => $tpData['is_sufficient_evidence'] ?? false,
                'earned_weight'          => $tpData['earned_weight'] ?? 0.0,
                'student_percentage'     => $tpData['student_percentage'] ?? null,
                'threshold'              => $tpData['threshold'] ?? 75.0,
                'status'                 => $tpData['status'] ?? AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE,
                'is_mastered'            => $tpData['is_mastered'] ?? null,
                'insufficient_reason'    => $tpData['insufficient_reason'] ?? null,
            ];
        }

        return [
            'student_id'         => $result->getStudentId(),
            'test_id'            => $result->getTestId(),
            'session_id'         => $result->getSessionId(),
            'academic_context'   => $academicContext,
            'statuses'           => $result->getStatuses(),
            'test_score'         => $testScoreProjection,
            'gradebook_report'   => $gradebookProjection,
            'tp_analytics'       => $tpAnalytics,
            'weight_details'     => $result->getWeightDetails(),
            'items_breakdown'    => $result->getItemsBreakdown(),
            'metadata'           => $result->getMetadata(),
            'projected_at'       => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Project cohort-wide reporting including individual reports and aggregate TP mastery metrics.
     *
     * @param AssessmentResult[] $results Array of student AssessmentResult instances
     * @param array $testConfig
     * @param array $academicContext
     * @return array
     */
    public function projectCohortReport(array $results, array $testConfig = [], array $academicContext = []): array
    {
        $studentReports = [];
        $cohortEvaluations = [];

        $totalStudents = count($results);
        $eligibleCount = 0;
        $ineligibleCount = 0;
        $scoreSum = 0.0;
        $scoredCount = 0;

        foreach ($results as $res) {
            $report = $this->projectStudentReport($res, $academicContext);
            $studentReports[] = $report;

            if ($report['gradebook_report']['is_eligible']) {
                $eligibleCount++;
                $reportScore = $report['gradebook_report']['report_score'];
                if ($reportScore !== null) {
                    $scoreSum += $reportScore;
                    $scoredCount++;
                }
            } else {
                $ineligibleCount++;
            }

            $cohortEvaluations[] = [
                'student_id'     => $res->getStudentId(),
                'tp_evaluations' => $res->getTpEvaluations(),
            ];
        }

        // 4. Analitik Kohor TP (CR_murni & CR_kohort)
        $cohortTpMetrics = $this->masteryCalculator->calculateCohort($cohortEvaluations);

        $averageReportScore = $scoredCount > 0 ? round($scoreSum / $scoredCount, 2) : null;

        return [
            'test_id'              => $testConfig['test_id'] ?? 'unknown',
            'academic_context'     => $academicContext,
            'total_students'       => $totalStudents,
            'eligible_gradebook'   => $eligibleCount,
            'ineligible_gradebook' => $ineligibleCount,
            'average_report_score' => $averageReportScore,
            'cohort_tp_metrics'    => $cohortTpMetrics,
            'student_reports'      => $studentReports,
            'reporting_version'    => self::REPORTING_VERSION,
            'generated_at'         => date('Y-m-d H:i:s'),
        ];
    }
}

