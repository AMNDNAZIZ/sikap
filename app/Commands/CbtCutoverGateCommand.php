<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Cbt\Gate\CutoverGateEvaluator;
use App\Services\Cbt\Gate\CutoverEvidenceCollector;
use App\Services\Cbt\Review\ReviewerWorkflowService;

/**
 * CbtCutoverGateCommand
 *
 * ESPATA Assessment Intelligence V3 - Stage F3A
 * Evaluates the Cutover Verification Gate and displays reviewer workflow status.
 *
 * SAFETY INVARIANT:
 * Strictly read-only. Zero DML, zero mutations, zero operational writes.
 */
class CbtCutoverGateCommand extends BaseCommand
{
    protected $group       = 'CBT';
    protected $name        = 'cbt:gate';
    protected $description = 'Evaluate Cutover Verification Gate readiness and reviewer workflow queue';
    protected $usage       = 'php spark cbt:gate [options]';
    protected $options     = [
        '--show-queue' => 'Display detailed list of review queue cases',
        '--show-checks'=> 'Display detailed evidence checks and verification levels',
        '--json'       => 'Output complete gate verdict in JSON format',
    ];

    public function run(array $params)
    {
        CLI::write("==================================================================", 'cyan');
        CLI::write("  ESPATA ASSESSMENT INTELLIGENCE — STAGE F3A CUTOVER VERIFICATION GATE", 'yellow');
        CLI::write("==================================================================", 'cyan');

        $collector = new CutoverEvidenceCollector();
        $workflow = new ReviewerWorkflowService();
        $evaluator = new CutoverGateEvaluator($collector);

        // Load shadow deviations if available
        $deviationsCsv = (defined('WRITEPATH') ? WRITEPATH : 'writable/') . 'shadow_artifacts/shadow_deviations.csv';
        if (file_exists($deviationsCsv)) {
            $fp = fopen($deviationsCsv, 'r');
            $header = fgetcsv($fp);
            $rawCases = [];
            while (($row = fgetcsv($fp)) !== false) {
                if (count($row) >= 8) {
                    $rawCases[] = [
                        'pseudonymous_student_id' => $row[0],
                        'test_id'                 => $row[1],
                        'session_id'              => $row[2] !== 'NULL' ? $row[2] : null,
                        'legacy_score'            => $row[3] !== 'NULL' ? (float)$row[3] : null,
                        'v3_score'                => $row[4] !== 'NULL' ? (float)$row[4] : null,
                        'delta'                   => $row[5] !== 'NULL' ? (float)$row[5] : null,
                        'is_comparable'           => $row[6] === 'YES',
                        'categories'              => explode('|', $row[7]),
                        'notes'                   => $row[8] ?? '',
                    ];
                }
            }
            fclose($fp);
            $workflow->ingestComparisonResults($rawCases, [
                501 => 'Matematika',
                502 => 'Bahasa Indonesia',
                503 => 'Ilmu Pengetahuan Alam',
            ]);
        }

        $verdict = $evaluator->evaluateGate([], $workflow);

        if (array_key_exists('json', $params) || CLI::getOption('json')) {
            CLI::write(json_encode($verdict, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return;
        }

        $statusColor = match ($verdict['gate_status']) {
            CutoverGateEvaluator::STATUS_READY_FOR_STAGING => 'green',
            CutoverGateEvaluator::STATUS_REQUIRES_REVIEW   => 'yellow',
            default                                        => 'red',
        };

        CLI::write("GATE STATUS: " . $verdict['gate_status'], $statusColor);
        CLI::write("EVALUATED AT: " . $verdict['evaluated_at']);
        CLI::newLine();

        CLI::write("--- EXPLICIT PASS LEVELS ---", 'cyan');
        CLI::write(sprintf("  CODE_LEVEL_PASS:     %s", $verdict['code_level_pass'] ? 'YES' : 'NO'), $verdict['code_level_pass'] ? 'green' : 'red');
        CLI::write(sprintf("  ARTIFACT_LEVEL_PASS: %s", $verdict['artifact_level_pass'] ? 'YES' : 'NO'), $verdict['artifact_level_pass'] ? 'green' : 'red');
        CLI::write(sprintf("  DATABASE_LEVEL_PASS: %s (requires live MariaDB)", $verdict['database_level_pass'] ? 'YES' : 'NO'), $verdict['database_level_pass'] ? 'green' : 'yellow');
        CLI::write(sprintf("  STAGING_LEVEL_PASS:  %s", $verdict['staging_level_pass'] ? 'YES' : 'NO'), $verdict['staging_level_pass'] ? 'green' : 'yellow');
        CLI::newLine();

        CLI::write("READY FOR STAGING:    " . ($verdict['ready_for_staging'] ? 'YES' : 'NO'), $verdict['ready_for_staging'] ? 'green' : 'yellow');
        CLI::write("READY FOR PRODUCTION: " . ($verdict['ready_for_production'] ? 'YES' : 'NO (STRICTLY BLOCKED IN STAGE F3A)'), 'yellow');

        CLI::newLine();
        CLI::write("--- 4 PILLARS AUDIT VERDICT ---", 'cyan');
        foreach ($verdict['pillars'] as $name => $data) {
            $pStatus = $data['status'] ?? 'UNKNOWN';
            $pColor = $pStatus === 'PASSED' ? 'green' : ($pStatus === 'REQUIRES_ATTENTION' ? 'yellow' : 'red');
            CLI::write(sprintf("  [%-22s]: %s", strtoupper($name), $pStatus), $pColor);
            if (!empty($data['blockers'])) {
                foreach ($data['blockers'] as $b) {
                    CLI::write("    -> BLOCKER: {$b}", 'red');
                }
            }
            if (!empty($data['reviews'])) {
                foreach ($data['reviews'] as $r) {
                    CLI::write("    -> REVIEW: {$r}", 'yellow');
                }
            }
        }

        CLI::newLine();
        CLI::write("--- REVIEW QUEUE SUMMARY BY RESPONSIBLE PARTY ---", 'cyan');
        $q = $verdict['review_queue_summary'];
        $pb = $q['party_breakdown'] ?? [];
        CLI::write("  Total Cases:                    {$q['total_cases']}");
        CLI::write("  Pending / Action Required:      {$q['pending_cases']}", $q['pending_cases'] > 0 ? 'yellow' : 'green');
        CLI::write("    -> Teacher Action Required:   " . ($pb['teacher_action_required'] ?? 0) . " (PENDING_ESSAY)");
        CLI::write("    -> Owner Decision Required:  " . ($pb['owner_decision_required'] ?? 0) . " (AMBIGUOUS_RETAKE)");
        CLI::write("    -> Acknowledgement Required: " . ($pb['acknowledgement_required'] ?? 0) . " (ROUNDING/ABSENT)");
        CLI::write("    -> Reviewer Action Required: " . ($pb['reviewer_action_required'] ?? 0));
        CLI::write("  Resolved:                       {$q['resolved_cases']}");
        CLI::write("  Blocked:                        {$q['blocked_cases']}");

        CLI::newLine();
        CLI::write("--- EVIDENCE MATURITY DISCLOSURES ---", 'cyan');
        foreach ($verdict['evidence_maturity_disclosures'] as $disc) {
            CLI::write("  * {$disc}", 'yellow');
        }

        if (array_key_exists('show-checks', $params) || CLI::getOption('show-checks')) {
            CLI::newLine();
            CLI::write("--- DETAILED EVIDENCE CHECKS & VERIFICATION LEVELS ---", 'cyan');
            foreach ($verdict['checks'] as $c) {
                CLI::write(sprintf("  [%-32s] Result: %-8s | Level: %-26s",
                    $c['check_name'],
                    $c['result'],
                    $c['verification_level']
                ));
                if (!empty($c['limitations'])) {
                    foreach ($c['limitations'] as $lim) {
                        CLI::write("      limitation: {$lim}", 'yellow');
                    }
                }
            }
        }

        if (array_key_exists('show-queue', $params) || CLI::getOption('show-queue')) {
            CLI::newLine();
            CLI::write("--- REVIEW QUEUE CASES (ZERO-PII) ---", 'cyan');
            $cases = $workflow->getQueue();
            foreach ($cases as $c) {
                CLI::write(sprintf("  [%s] %s | %s | Legacy: %s | V3: %s | Party: %s | Status: %s",
                    $c['case_id'],
                    $c['pseudonymous_student_id'],
                    $c['category'],
                    $c['legacy_score'] ?? 'NULL',
                    $c['v3_score'] ?? 'NULL',
                    $c['responsible_party'] ?? 'UNKNOWN',
                    $c['review_status']
                ));
            }
        }

        CLI::newLine();
        CLI::write("==================================================================", 'cyan');
    }
}
