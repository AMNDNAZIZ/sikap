<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Services\Cbt\CbtMigrationOrchestrator;
use App\Services\Cbt\CbtMigrationVerifier;

/**
 * CbtMigrateV3Command
 * CLI command to orchestrate the non-destructive migration to Assessment Intelligence V3.
 */
class CbtMigrateV3Command extends BaseCommand
{
    protected $group       = 'CBT';
    protected $name        = 'cbt:migrate-v3';
    protected $description = 'Non-destructive migration engine from legacy cbt_answers to Assessment Intelligence V3';
    protected $usage       = 'php spark cbt:migrate-v3 [options]';
    protected $options     = [
        '--dry-run'     => 'Run in read-only simulation mode (default)',
        '--execute'     => 'Explicitly execute migration to target tables (staging/test only)',
        '--batch-size'  => 'Number of rows per batch (default: 500, max: 1000)',
        '--limit'       => 'Optional maximum total rows to process',
        '--start-id'    => 'Starting legacy ID cursor (default: 0)',
        '--verify-only' => 'Skip copying and run full verification suite on current data',
    ];

    public function run(array $params)
    {
        CLI::write("==================================================================", 'cyan');
        CLI::write("  ESPATA ASSESSMENT INTELLIGENCE — V3 MIGRATION ENGINE (STAGE C)", 'yellow');
        CLI::write("==================================================================", 'cyan');

        $isExecute    = array_key_exists('execute', $params) || in_array('--execute', $params, true);
        $isVerifyOnly = array_key_exists('verify-only', $params) || in_array('--verify-only', $params, true);
        $batchSize    = (int)($params['batch-size'] ?? CLI::getOption('batch-size') ?? 500);
        $limit        = isset($params['limit']) ? (int)$params['limit'] : (CLI::getOption('limit') ? (int)CLI::getOption('limit') : null);
        $startId      = (int)($params['start-id'] ?? CLI::getOption('start-id') ?? 0);

        $db = null;
        try {
            $db = \Config\Database::connect();
        } catch (\Throwable $e) {
            CLI::write("[WARNING] Database connection failed: " . $e->getMessage(), 'red');
        }

        // Handle --verify-only
        if ($isVerifyOnly) {
            CLI::write("Running in VERIFY-ONLY mode...", 'yellow');
            $verifier = new CbtMigrationVerifier($db);
            $results  = $verifier->verify();
            $this->printVerificationReport($results);
            return;
        }

        $modeText = $isExecute ? "EXECUTE (STAGING / TEST)" : "DRY-RUN (READ-ONLY SIMULATION)";
        $modeColor = $isExecute ? 'red' : 'green';

        CLI::write("Mode: {$modeText}", $modeColor);
        CLI::write("Batch Size: {$batchSize} | Start ID: {$startId}" . ($limit ? " | Limit: {$limit}" : ""), 'white');
        CLI::newLine();

        $orchestrator = new CbtMigrationOrchestrator($db);

        try {
            $report = $orchestrator->run([
                'execute'     => $isExecute,
                'batch_size'  => $batchSize,
                'limit'       => $limit,
                'start_id'    => $startId,
                'operator'    => get_current_user() ?: 'CLI_OPERATOR',
                'environment' => defined('ENVIRONMENT') ? ENVIRONMENT : 'development',
            ]);

            $this->printFullReport($report);

        } catch (\Throwable $e) {
            CLI::newLine();
            CLI::error("MIGRATION ABORTED: " . $e->getMessage());
        }
    }

    private function printFullReport(array $report): void
    {
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" MIGRATION EXECUTION SUMMARY", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write("Status:            " . $report['pipeline_status'], 'green');
        CLI::write("Mode:              " . $report['mode'], 'white');
        CLI::write("Environment:       " . $report['environment'], 'white');
        CLI::write("Duration:          " . $report['execution_duration_ms'] . " ms", 'white');
        CLI::write("Total Scanned:     " . $report['total_source_scanned'] . " rows", 'white');
        CLI::write("Total Batches:     " . $report['total_batches_run'], 'white');
        CLI::write("Last Processed ID: " . $report['last_processed_id'], 'white');

        $v = $report['verification'];
        CLI::newLine();
        CLI::write("--- VERIFICATION METRICS ---", 'yellow');
        CLI::write("Verdict:           " . $v['verdict'], $v['verdict'] === 'PASS' ? 'green' : 'red');
        CLI::write("Source Count:      " . $v['counts']['source_count'], 'white');
        CLI::write("Target V3 Count:   " . $v['counts']['target_v3_count'], 'white');
        CLI::write("Quarantine Count:  " . $v['counts']['quarantine_count'], 'white');
        CLI::write("Unprocessed:       " . $v['counts']['unprocessed_count'], 'white');
        CLI::write("Conflicts:         " . $v['counts']['conflict_count'], $v['counts']['conflict_count'] > 0 ? 'red' : 'green');

        CLI::newLine();
        CLI::write("--- TWO-WAY ANTI-JOIN AUDIT ---", 'yellow');
        CLI::write("Missing in Dest:   " . count($v['anti_join']['missing_in_destination']), count($v['anti_join']['missing_in_destination']) > 0 ? 'red' : 'green');
        CLI::write("Orphan in V3:      " . count($v['anti_join']['orphan_in_v3']), count($v['anti_join']['orphan_in_v3']) > 0 ? 'red' : 'green');
        CLI::write("Orphan in Quar:    " . count($v['anti_join']['orphan_in_quarantine']), count($v['anti_join']['orphan_in_quarantine']) > 0 ? 'red' : 'green');
        CLI::write("Cross Duplicates:  " . count($v['anti_join']['cross_table_duplicates']), count($v['anti_join']['cross_table_duplicates']) > 0 ? 'red' : 'green');

        CLI::newLine();
        CLI::write("--- MERKLE SUBSET ROOTS ---", 'yellow');
        CLI::write("V3 Merkle Root:    " . $v['merkle_reconciliation']['v3_subset_merkle_root'], 'cyan');
        CLI::write("Quar Merkle Root:  " . $v['merkle_reconciliation']['quar_subset_merkle_root'], 'cyan');

        $r = $report['reconciliation'];
        CLI::newLine();
        CLI::write("--- DRIFT & RECONCILIATION ---", 'yellow');
        CLI::write("New Rows Drift:    " . $r['new_rows_count'], $r['new_rows_count'] > 0 ? 'yellow' : 'green');
        CLI::write("Modified Drift:    " . $r['modified_rows_count'], $r['modified_rows_count'] > 0 ? 'yellow' : 'green');
        CLI::write("Advice:            " . $r['reconciliation_advice'], 'white');

        CLI::write("==================================================================", 'cyan');
    }

    private function printVerificationReport(array $results): void
    {
        CLI::write("Verification Verdict: " . $results['verdict'], $results['verdict'] === 'PASS' ? 'green' : 'red');
        CLI::write("Source Count:        " . $results['counts']['source_count'], 'white');
        CLI::write("Target V3 Count:     " . $results['counts']['target_v3_count'], 'white');
        CLI::write("Quarantine Count:    " . $results['counts']['quarantine_count'], 'white');
        CLI::write("Unprocessed Count:   " . $results['counts']['unprocessed_count'], 'white');
    }
}

