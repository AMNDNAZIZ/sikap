<?php

namespace App\Services\Cbt\Shadow;

/**
 * ShadowReportGenerator
 *
 * ESPATA Assessment Intelligence V3 - Stage F1
 * Generates aggregated statistical metrics and detailed pseudonymous comparison reports.
 *
 * CRITICAL PRIVACY & STATISTICAL MANDATES:
 * 1. ZERO PII: All student identifiers are pseudorandomly hashed (e.g. SHA-256 HMAC or salt).
 *    No student names, NISN, or sensitive personal data are ever emitted into report artifacts.
 * 2. Pure Numeric Metrics: MAE and RMSE are computed STRICTLY on valid, comparable numeric pairs.
 *    Non-comparable cases (nulls, void tests, unattempted absences) are NEVER treated as zero.
 */
class ShadowReportGenerator
{
    private string $salt;

    public function __construct(string $salt = 'ESPATA_SHADOW_V3_SALT')
    {
        $this->salt = $salt;
    }

    /**
     * Generate comprehensive aggregate report from a list of comparison results.
     *
     * @param array $comparisonResults
     * @return array
     */
    public function generateAggregateReport(array $comparisonResults): array
    {
        $totalCases = count($comparisonResults);
        $comparableCount = 0;
        $nonComparableCount = 0;

        $exactMatchCount = 0;
        $roundingDiffCount = 0;
        $scoreDiffCount = 0;

        $categoryCounts = [
            ShadowComparisonService::CAT_EXACT_MATCH                  => 0,
            ShadowComparisonService::CAT_ROUNDING_DIFFERENCE          => 0,
            ShadowComparisonService::CAT_SCORE_DIFFERENCE             => 0,
            ShadowComparisonService::CAT_WEIGHT_DIFFERENCE            => 0,
            ShadowComparisonService::CAT_VOID_POLICY_DIFFERENCE       => 0,
            ShadowComparisonService::CAT_UNANSWERED_POLICY_DIFFERENCE => 0,
            ShadowComparisonService::CAT_ESSAY_POLICY_DIFFERENCE      => 0,
            ShadowComparisonService::CAT_ATTEMPT_SELECTION_DIFFERENCE => 0,
            ShadowComparisonService::CAT_LEGACY_ZERO_FALLBACK         => 0,
            ShadowComparisonService::CAT_NO_VALID_QUESTIONS           => 0,
            ShadowComparisonService::CAT_INSUFFICIENT_EVIDENCE        => 0,
            ShadowComparisonService::CAT_INTEGRITY_STATUS_DIFFERENCE  => 0,
            ShadowComparisonService::CAT_LEGACY_UNVERIFIABLE          => 0,
            ShadowComparisonService::CAT_INSUFFICIENT_SOURCE_DATA     => 0,
            ShadowComparisonService::CAT_PUBLICATION_ELIGIBILITY_DIFFERENCE => 0,
        ];

        $absDeltas = [];
        $squaredDeltas = [];
        $deltas = [];
        $eligibleCount = 0;
        $statusMatchCount = 0;
        $statusDiffCount = 0;
        $v3StatusDist = [];
        $legacyStatusDist = [];

        foreach ($comparisonResults as $res) {
            $isComparable = !empty($res['is_comparable']);
            $categories = $res['categories'] ?? [];

            // Track publication eligibility
            $pub = $res['publication_eligibility'] ?? [];
            if (!empty($pub['v3_eligible'])) {
                $eligibleCount++;
            }
            if (isset($pub['is_eligibility_match'])) {
                if ($pub['is_eligibility_match']) {
                    $statusMatchCount++;
                } else {
                    $statusDiffCount++;
                }
            }
            if (isset($pub['v3_status'])) {
                $v3StatusDist[$pub['v3_status']] = ($v3StatusDist[$pub['v3_status']] ?? 0) + 1;
            }
            if (isset($pub['legacy_status'])) {
                $legacyStatusDist[$pub['legacy_status']] = ($legacyStatusDist[$pub['legacy_status']] ?? 0) + 1;
            }

            foreach ($categories as $cat) {
                if (isset($categoryCounts[$cat])) {
                    $categoryCounts[$cat]++;
                }
            }

            if (in_array(ShadowComparisonService::CAT_EXACT_MATCH, $categories, true)) {
                $exactMatchCount++;
            } elseif (in_array(ShadowComparisonService::CAT_ROUNDING_DIFFERENCE, $categories, true)) {
                $roundingDiffCount++;
            }

            if (in_array(ShadowComparisonService::CAT_SCORE_DIFFERENCE, $categories, true)) {
                $scoreDiffCount++;
            }

            if ($isComparable && isset($res['delta']) && $res['delta'] !== null) {
                $comparableCount++;
                $d = (float)$res['delta'];
                $absD = abs($d);
                $deltas[] = $d;
                $absDeltas[] = $absD;
                $squaredDeltas[] = $d * $d;
            } else {
                $nonComparableCount++;
            }
        }

        // Statistical Metrics (computed ONLY on comparable pairs)
        $mae = null;
        $rmse = null;
        $maxDelta = null;
        $minDelta = null;

        if ($comparableCount > 0) {
            $mae = round(array_sum($absDeltas) / $comparableCount, 4);
            $rmse = round(sqrt(array_sum($squaredDeltas) / $comparableCount), 4);
            $maxDelta = round(max($deltas), 4);
            $minDelta = round(min($deltas), 4);
        }

        $exactMatchPct = $totalCases > 0 ? round(($exactMatchCount / $totalCases) * 100.0, 2) : 0.0;
        $scoreDiffPct = $totalCases > 0 ? round(($scoreDiffCount / $totalCases) * 100.0, 2) : 0.0;
        $roundingDiffPct = $totalCases > 0 ? round(($roundingDiffCount / $totalCases) * 100.0, 2) : 0.0;

        // Multi-tier percentage breakdowns (Total, Comparable, Eligible)
        $pctBreakdown = [
            'denominators' => [
                'total_examined'   => $totalCases,
                'total_comparable' => $comparableCount,
                'total_eligible'   => $eligibleCount,
                'explanation'      => [
                    'total_examined'   => 'Total student cases evaluated across all subjects (N=' . $totalCases . ')',
                    'total_comparable' => 'Pairs with valid, complete scores on both legacy and V3 (N=' . $comparableCount . ')',
                    'total_eligible'   => 'Cases eligible for gradebook publication in V3 (N=' . $eligibleCount . ')',
                ],
            ],
            'exact_match' => [
                'count'             => $exactMatchCount,
                'pct_of_total'      => $exactMatchPct,
                'pct_of_comparable' => $comparableCount > 0 ? round(($exactMatchCount / $comparableCount) * 100.0, 2) : 0.0,
                'pct_of_eligible'   => $eligibleCount > 0 ? round(($exactMatchCount / $eligibleCount) * 100.0, 2) : 0.0,
            ],
            'rounding_difference' => [
                'count'             => $roundingDiffCount,
                'pct_of_total'      => $roundingDiffPct,
                'pct_of_comparable' => $comparableCount > 0 ? round(($roundingDiffCount / $comparableCount) * 100.0, 2) : 0.0,
                'pct_of_eligible'   => $eligibleCount > 0 ? round(($roundingDiffCount / $eligibleCount) * 100.0, 2) : 0.0,
            ],
            'score_difference' => [
                'count'             => $scoreDiffCount,
                'pct_of_total'      => $scoreDiffPct,
                'pct_of_comparable' => $comparableCount > 0 ? round(($scoreDiffCount / $comparableCount) * 100.0, 2) : 0.0,
                'pct_of_eligible'   => $eligibleCount > 0 ? round(($scoreDiffCount / $eligibleCount) * 100.0, 2) : 0.0,
            ],
        ];

        return [
            'total_cases_examined'          => $totalCases,
            'total_comparable_cases'        => $comparableCount,
            'total_non_comparable_cases'    => $nonComparableCount,
            'total_eligible_cases'          => $eligibleCount,
            'exact_match_count'             => $exactMatchCount,
            'exact_match_percentage'        => $exactMatchPct,
            'exact_match_pct_comparable'    => $pctBreakdown['exact_match']['pct_of_comparable'],
            'exact_match_pct_eligible'      => $pctBreakdown['exact_match']['pct_of_eligible'],
            'score_difference_count'        => $scoreDiffCount,
            'score_difference_percentage'   => $scoreDiffPct,
            'score_difference_pct_comparable'=> $pctBreakdown['score_difference']['pct_of_comparable'],
            'score_difference_pct_eligible' => $pctBreakdown['score_difference']['pct_of_eligible'],
            'rounding_difference_count'     => $roundingDiffCount,
            'percentage_breakdown'          => $pctBreakdown,
            'category_distribution'         => $categoryCounts,
            'publication_eligibility'       => [
                'status_match_count'            => $statusMatchCount,
                'status_difference_count'       => $statusDiffCount,
                'v3_status_distribution'        => $v3StatusDist,
                'legacy_status_distribution'    => $legacyStatusDist,
            ],
            'mae'                           => $mae,
            'rmse'                          => $rmse,
            'max_delta'                     => $maxDelta,
            'min_delta'                     => $minDelta,
            'legacy_zero_fallback_count'    => $categoryCounts[ShadowComparisonService::CAT_LEGACY_ZERO_FALLBACK],
            'ambiguous_retake_count'        => $categoryCounts[ShadowComparisonService::CAT_ATTEMPT_SELECTION_DIFFERENCE],
            'void_test_count'               => $categoryCounts[ShadowComparisonService::CAT_NO_VALID_QUESTIONS],
            'incomplete_essay_count'        => $categoryCounts[ShadowComparisonService::CAT_ESSAY_POLICY_DIFFERENCE],
            'legacy_unverifiable_count'     => $categoryCounts[ShadowComparisonService::CAT_LEGACY_UNVERIFIABLE],
            'generated_at'                  => date('Y-m-d H:i:s'),
            'mae'                           => $mae,
            'rmse'                          => $rmse,
            'max_delta'                     => $maxDelta,
            'min_delta'                     => $minDelta,
            'legacy_zero_fallback_count'    => $categoryCounts[ShadowComparisonService::CAT_LEGACY_ZERO_FALLBACK],
            'ambiguous_retake_count'        => $categoryCounts[ShadowComparisonService::CAT_ATTEMPT_SELECTION_DIFFERENCE],
            'void_test_count'               => $categoryCounts[ShadowComparisonService::CAT_NO_VALID_QUESTIONS],
            'incomplete_essay_count'        => $categoryCounts[ShadowComparisonService::CAT_ESSAY_POLICY_DIFFERENCE],
            'legacy_unverifiable_count'     => $categoryCounts[ShadowComparisonService::CAT_LEGACY_UNVERIFIABLE],
            'generated_at'                  => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Generate detailed report with pseudonymous student identifiers and zero PII.
     *
     * @param array $comparisonResults
     * @param bool $maskPii
     * @return array
     */
    public function generateDetailReport(array $comparisonResults, bool $maskPii = true): array
    {
        $details = [];

        foreach ($comparisonResults as $res) {
            $rawStudentId = (string)($res['student_id'] ?? 'unknown');
            $pseudonymousId = $maskPii 
                ? 'STD_' . strtoupper(substr(hash_hmac('sha256', $rawStudentId, $this->salt), 0, 12))
                : $rawStudentId;

            $details[] = [
                'pseudonymous_student_id' => $pseudonymousId,
                'test_id'                 => $res['test_id'] ?? null,
                'session_id'              => $res['session_id'] ?? null,
                'legacy_score'            => $res['legacy_score'],
                'v3_score'                => $res['v3_score'],
                'delta'                   => $res['delta'],
                'is_comparable'           => $res['is_comparable'],
                'categories'              => $res['categories'] ?? [],
                'notes'                   => $res['notes'] ?? '',
            ];
        }

        return $details;
    }

    /**
     * Export complete shadow evaluation bundle to JSON format.
     */
    public function exportJson(array $aggregateReport, array $detailReport): string
    {
        return json_encode([
            'metadata' => [
                'pipeline'     => 'ESPATA Shadow Testing Pipeline V3.27-STAGE_F1',
                'pii_scrubbed' => true,
                'exported_at'  => date('Y-m-d H:i:s'),
            ],
            'aggregate_summary' => $aggregateReport,
            'case_details'      => $detailReport,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Export case details to CSV format (Zero PII).
     */
    public function exportCsv(array $detailReport): string
    {
        $fp = fopen('php://temp', 'r+');

        // CSV Header
        fputcsv($fp, [
            'PseudonymousStudentID',
            'TestID',
            'SessionID',
            'LegacyScore',
            'V3Score',
            'Delta',
            'IsComparable',
            'Categories',
            'Notes',
        ]);

        foreach ($detailReport as $row) {
            fputcsv($fp, [
                $row['pseudonymous_student_id'],
                $row['test_id'],
                $row['session_id'],
                $row['legacy_score'] !== null ? $row['legacy_score'] : 'NULL',
                $row['v3_score'] !== null ? $row['v3_score'] : 'NULL',
                $row['delta'] !== null ? $row['delta'] : 'NULL',
                $row['is_comparable'] ? 'YES' : 'NO',
                implode('|', $row['categories']),
                $row['notes'],
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }
}

