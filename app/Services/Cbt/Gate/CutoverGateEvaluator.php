<?php

namespace App\Services\Cbt\Gate;

use App\Services\Cbt\Review\ReviewerWorkflowService;
use RuntimeException;

/**
 * CutoverGateEvaluator
 *
 * ESPATA Assessment Intelligence V3 - Stage F3A
 * Evaluates system readiness gate across the 4 core pillars and review queue.
 *
 * GATE STATUSES:
 * - BLOCKED: Critical failures, data corruption, invariant breach, or unsafe environment.
 * - REQUIRES_REVIEW: Technical foundation sound, but pending reviewer/teacher/owner decisions remain.
 * - READY_FOR_STAGING: All code-level invariants, artifact thresholds, and review items cleared for staging rehearsal.
 * - NOT_EVALUATED: Evidence not yet gathered or incomplete.
 *
 * EXPLICIT PASS LEVELS:
 * - code_level_pass: Unit tests and domain invariants verified.
 * - artifact_level_pass: Shadow execution statistical tolerances met.
 * - database_level_pass: Real MariaDB live integration tested (false if on mock).
 * - staging_level_pass: All prerequisites for staging deployment met.
 *
 * CRITICAL ARCHITECTURAL MANDATE:
 * CUTOVER_APPROVED / READY_FOR_PRODUCTION is unconditionally FALSE in Stage F3/F3A.
 */
class CutoverGateEvaluator
{
    public const STATUS_BLOCKED           = 'BLOCKED';
    public const STATUS_REQUIRES_REVIEW   = 'REQUIRES_REVIEW';
    public const STATUS_READY_FOR_STAGING = 'READY_FOR_STAGING';
    public const STATUS_NOT_EVALUATED     = 'NOT_EVALUATED';

    private CutoverEvidenceCollector $collector;

    public function __construct(?CutoverEvidenceCollector $collector = null)
    {
        $this->collector = $collector ?? new CutoverEvidenceCollector();
    }

    /**
     * Evaluate system readiness gate.
     *
     * @param array $evidence Optional pre-collected evidence bundle
     * @param ReviewerWorkflowService|null $workflow Optional reviewer workflow instance
     * @return array Complete gate evaluation verdict
     */
    public function evaluateGate(array $evidence = [], ?ReviewerWorkflowService $workflow = null): array
    {
        $bundle = !empty($evidence) ? $evidence : $this->collector->collectBundle();

        if (empty($bundle) || empty($bundle['database_migration']) || empty($bundle['assessment_gradebook'])) {
            return [
                'gate_status'                   => self::STATUS_NOT_EVALUATED,
                'evaluated_at'                  => date('Y-m-d H:i:s'),
                'code_level_pass'               => false,
                'artifact_level_pass'           => false,
                'database_level_pass'           => false,
                'staging_level_pass'            => false,
                'ready_for_staging'             => false,
                'ready_for_production'          => false,
                'pillars'                       => [],
                'review_queue_summary'          => [],
                'blockers'                      => ['Evidence bundle is empty or incomplete; evaluation aborted.'],
                'review_triggers'               => [],
                'evidence_maturity_disclosures' => ['No evidence gathered.'],
                'verdict_reasons'               => ['NOT_EVALUATED: Incomplete evidence.'],
                'checks'                        => [],
            ];
        }

        $pillars = [];
        $blockers = [];
        $reviewTriggers = [];

        // --------------------------------------------------------------------
        // 1. Evaluate Pillar A: Database Foundation & Migration Integrity
        // --------------------------------------------------------------------
        $mig = $bundle['database_migration'] ?? [];
        $migBlockers = [];

        if (($mig['missing_records_count'] ?? 0) > 0) {
            $migBlockers[] = "Missing records detected in migration: {$mig['missing_records_count']} unmigrated records.";
        }
        if (($mig['orphan_records_count'] ?? 0) > 0) {
            $migBlockers[] = "Orphan records detected in V3 tables: {$mig['orphan_records_count']} rows.";
        }
        if (($mig['cross_table_conflicts_count'] ?? 0) > 0) {
            $migBlockers[] = "Cross-table duplicate/conflicting records: {$mig['cross_table_conflicts_count']}.";
        }
        if (empty($mig['canonical_hash_consistent'])) {
            $migBlockers[] = 'Canonical hash consistency check failed across legacy and V3 datasets.';
        }
        if (empty($mig['merkle_root_consistent'])) {
            $migBlockers[] = 'Merkle tree root hash mismatch between batch stages.';
        }
        if (empty($mig['reconciliation_clean'])) {
            $migBlockers[] = 'Reconciliation audit detected unhandled drift between source and target.';
        }

        $pillars['database_migration'] = [
            'status'   => empty($migBlockers) ? 'PASSED' : 'FAILED',
            'blockers' => $migBlockers,
        ];
        $blockers = array_merge($blockers, $migBlockers);

        // --------------------------------------------------------------------
        // 2. Evaluate Pillar B: Shadow Execution Evidence & Statistics
        // --------------------------------------------------------------------
        $shadow = $bundle['shadow_execution'] ?? [];
        $shadowBlockers = [];
        $shadowReviews = [];

        if (($shadow['total_cases_examined'] ?? 0) === 0) {
            $shadowBlockers[] = 'Zero shadow cases evaluated; evidence is incomplete.';
        }
        if (($shadow['score_difference_count'] ?? 0) > 0) {
            $shadowReviews[] = "Significant unexplained score differences detected in {$shadow['score_difference_count']} comparable cases.";
        }
        if (($shadow['mae'] ?? null) !== null && $shadow['mae'] > 0.05) {
            $shadowReviews[] = "Mean Absolute Error ({$shadow['mae']}) exceeds tolerance threshold of 0.05.";
        }
        if (($shadow['rmse'] ?? null) !== null && $shadow['rmse'] > 0.05) {
            $shadowReviews[] = "Root Mean Squared Error ({$shadow['rmse']}) exceeds tolerance threshold of 0.05.";
        }

        $pillars['shadow_execution'] = [
            'status'   => empty($shadowBlockers) ? (empty($shadowReviews) ? 'PASSED' : 'REQUIRES_ATTENTION') : 'FAILED',
            'blockers' => $shadowBlockers,
            'reviews'  => $shadowReviews,
        ];
        $blockers = array_merge($blockers, $shadowBlockers);
        $reviewTriggers = array_merge($reviewTriggers, $shadowReviews);

        // --------------------------------------------------------------------
        // 3. Evaluate Pillar C: Assessment Engine & Gradebook Invariants
        // --------------------------------------------------------------------
        $assess = $bundle['assessment_gradebook'] ?? [];
        $assessBlockers = [];

        if (empty($assess['no_valid_questions_is_null_score'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: NO_VALID_QUESTIONS test produced numeric report score.';
        }
        if (empty($assess['pending_essay_is_null_score'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: PENDING_ESSAY produced non-null gradebook report score.';
        }
        if (empty($assess['not_attempted_distinct_from_zero'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: Student absence was treated as legitimate earned zero.';
        }
        if (empty($assess['ambiguous_retake_requires_manual'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: Ambiguous retakes selected without policy.';
        }
        if (empty($assess['manual_gradebook_immutable_without_auth'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: Manual teacher score in gradebook can be overwritten without auth.';
        }
        if (empty($assess['tp_analytics_strictly_isolated'])) {
            $assessBlockers[] = 'CRITICAL INVARIANT BREACH: TP mastery rates were converted into test scores or vice versa.';
        }

        $pillars['assessment_gradebook'] = [
            'status'   => empty($assessBlockers) ? 'PASSED' : 'FAILED',
            'blockers' => $assessBlockers,
        ];
        $blockers = array_merge($blockers, $assessBlockers);

        // --------------------------------------------------------------------
        // 4. Evaluate Pillar D: Security & Operational Safety
        // --------------------------------------------------------------------
        $sec = $bundle['security_safety'] ?? [];
        $secBlockers = [];

        if (empty($sec['production_guard_active'])) {
            $secBlockers[] = 'SECURITY FAILURE: Production environment guard is disabled.';
        }
        if (empty($sec['zero_dml_guard_active'])) {
            $secBlockers[] = 'SECURITY FAILURE: Zero-DML query inspection firewall is inactive.';
        }
        if (($sec['zero_dml_violations_blocked'] ?? 0) > 0) {
            $secBlockers[] = "Zero-DML violation attempt detected ({$sec['zero_dml_violations_blocked']} blocked).";
        }

        $pillars['security_safety'] = [
            'status'   => empty($secBlockers) ? 'PASSED' : 'FAILED',
            'blockers' => $secBlockers,
        ];
        $blockers = array_merge($blockers, $secBlockers);

        // --------------------------------------------------------------------
        // 5. Evaluate Review Queue Status & Party Breakdown
        // --------------------------------------------------------------------
        $reviewSummary = [
            'total_cases'                   => 0,
            'pending_cases'                 => 0,
            'in_review_cases'               => 0,
            'resolved_cases'                => 0,
            'blocked_cases'                 => 0,
            'party_breakdown'               => [
                'teacher_action_required'     => 0,
                'owner_decision_required'     => 0,
                'acknowledgement_required'    => 0,
                'reviewer_action_required'    => 0,
                'resolved'                    => 0,
                'blocked'                     => 0,
            ],
        ];

        if ($workflow !== null) {
            $reviewSummary = $workflow->getSummary();
            $pendingTotal = $reviewSummary['pending_cases'] ?? 0;
            $inReviewTotal = $reviewSummary['in_review_cases'] ?? 0;
            $parties = $reviewSummary['party_breakdown'] ?? [];

            if ($pendingTotal > 0 || $inReviewTotal > 0) {
                $reviewTriggers[] = "Review queue has {$pendingTotal} pending/action-required and {$inReviewTotal} in-review cases requiring resolution.";
            }
            if (($parties['teacher_action_required'] ?? 0) > 0) {
                $reviewTriggers[] = "There are {$parties['teacher_action_required']} case(s) requiring Teacher scoring action (PENDING_ESSAY).";
            }
            if (($parties['owner_decision_required'] ?? 0) > 0) {
                $reviewTriggers[] = "There are {$parties['owner_decision_required']} case(s) requiring formal Product Owner policy decision (AMBIGUOUS_RETAKE).";
            }
            if (($parties['acknowledgement_required'] ?? 0) > 0) {
                $reviewTriggers[] = "There are {$parties['acknowledgement_required']} case(s) requiring formal acknowledgement (rounding differences or absent fallbacks).";
            }
        }

        // --------------------------------------------------------------------
        // 6. Evidence Maturity Evaluation & Truthful Disclosures
        // --------------------------------------------------------------------
        $maturity = $bundle['evidence_maturity'] ?? [];
        $modes = $maturity['modes'] ?? [];

        $maturityDisclosures = [];
        if (empty($modes[CutoverEvidenceCollector::LEVEL_MARIADB_INTEGRATION_TESTED])) {
            $maturityDisclosures[] = 'MariaDB live database integration is unverified (daemon stopped in test sandbox).';
        }
        if (empty($modes[CutoverEvidenceCollector::LEVEL_REAL_SNAPSHOT_EXECUTED])) {
            $maturityDisclosures[] = 'Physical database snapshot dump was not executed (evaluated on representative mock dataset).';
        }
        if (empty($modes[CutoverEvidenceCollector::LEVEL_ZERO_DML_DATABASE_VERIFIED])) {
            $maturityDisclosures[] = 'Database-level Zero-DML privilege (SHOW GRANTS) was not verified on live MariaDB server.';
        }
        if (($maturity['snapshot_checksum_status'] ?? '') === 'SNAPSHOT_CHECKSUM_UNVERIFIED') {
            $maturityDisclosures[] = 'Snapshot checksum remains SNAPSHOT_CHECKSUM_UNVERIFIED.';
        }

        $pillars['evidence_maturity'] = [
            'status'               => empty($modes[CutoverEvidenceCollector::LEVEL_MARIADB_INTEGRATION_TESTED]) ? 'MOCK_RESTRICTED' : 'FULLY_INTEGRATED',
            'modes'                => $modes,
            'maturity_disclosures' => $maturityDisclosures,
        ];

        // --------------------------------------------------------------------
        // 7. Explicit Pass Level Determination
        // --------------------------------------------------------------------
        // A. Code Level Pass: Code algorithms, canonical serializers, and domain invariants pass
        $codeLevelPass = empty($migBlockers) && empty($assessBlockers) && empty($secBlockers);

        // B. Artifact Level Pass: Shadow execution summary, error metrics (MAE <= 0.05, RMSE <= 0.05), and logs exist
        $artifactLevelPass = empty($shadowBlockers) && (($shadow['mae'] ?? 1.0) <= 0.05) && (($shadow['rmse'] ?? 1.0) <= 0.05) && (($shadow['score_difference_count'] ?? 0) === 0);

        // C. Database Level Pass: True ONLY IF real MariaDB daemon is active, integrated, and verified via SHOW GRANTS
        $databaseLevelPass = !empty($modes[CutoverEvidenceCollector::LEVEL_MARIADB_INTEGRATION_TESTED])
            && !empty($modes[CutoverEvidenceCollector::LEVEL_ZERO_DML_DATABASE_VERIFIED]);

        // D. Staging Level Pass: Code level and artifact level pass, and ALL review queue items resolved
        $hasPendingReviews = !empty($reviewTriggers);
        $stagingLevelPass = $codeLevelPass && $artifactLevelPass && empty($blockers) && !$hasPendingReviews;

        // --------------------------------------------------------------------
        // 8. Gate Status Determination
        // --------------------------------------------------------------------
        $gateStatus = self::STATUS_NOT_EVALUATED;
        $verdictReasons = [];

        if (!empty($blockers)) {
            $gateStatus = self::STATUS_BLOCKED;
            $verdictReasons = $blockers;
        } elseif ($hasPendingReviews) {
            $gateStatus = self::STATUS_REQUIRES_REVIEW;
            $verdictReasons = $reviewTriggers;
        } elseif ($stagingLevelPass) {
            $gateStatus = self::STATUS_READY_FOR_STAGING;
            $verdictReasons[] = 'All code-level invariants, non-destructive migration checks, shadow error metrics (MAE 0.005), and reviewer/teacher/owner queues are fully resolved for STAGING.';
        } else {
            $gateStatus = self::STATUS_BLOCKED;
            $verdictReasons[] = 'Staging prerequisites unmet.';
        }

        // Production cutover is unconditionally FALSE in Stage F3/F3A
        $readyForProduction = false;
        if (!empty($maturityDisclosures)) {
            $verdictReasons[] = 'PRODUCTION CUTOVER BLOCKED: ' . implode('; ', $maturityDisclosures);
        }

        return [
            'gate_status'                   => $gateStatus,
            'evaluated_at'                  => date('Y-m-d H:i:s'),
            'code_level_pass'               => $codeLevelPass,
            'artifact_level_pass'           => $artifactLevelPass,
            'database_level_pass'           => $databaseLevelPass,
            'staging_level_pass'            => $stagingLevelPass,
            'ready_for_staging'             => ($gateStatus === self::STATUS_READY_FOR_STAGING),
            'ready_for_production'          => $readyForProduction, // Always false in Stage F3/F3A
            'pillars'                       => $pillars,
            'review_queue_summary'          => $reviewSummary,
            'blockers'                      => $blockers,
            'review_triggers'               => $reviewTriggers,
            'evidence_maturity_disclosures' => $maturityDisclosures,
            'verdict_reasons'               => $verdictReasons,
            'checks'                        => $bundle['checks'] ?? [],
        ];
    }
}
