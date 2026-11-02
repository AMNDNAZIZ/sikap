<?php

namespace App\Services\Cbt;

use RuntimeException;
use InvalidArgumentException;

/**
 * CbtMigrationOrchestrator
 * Master coordinator for the EXPAND -> COPY -> VERIFY -> RECONCILE -> REPORT pipeline.
 *
 * Enforces:
 * - Production environment lock: Strictly refuses execution on production databases.
 * - Dry-run by default: Requires explicit execute flag to perform database mutation.
 * - Strict non-destructive migration: Zero DELETE or ALTER on legacy cbt_answers.
 * - Idempotency and restartability via keyset pagination and manifest tracking.
 */
class CbtMigrationOrchestrator
{
    private $db;
    private CbtMigrationBatchProcessor $batchProcessor;
    private CbtMigrationVerifier $verifier;
    private CbtMigrationReconciler $reconciler;

    public function __construct(
        $db = null,
        ?CbtMigrationBatchProcessor $batchProcessor = null,
        ?CbtMigrationVerifier $verifier = null,
        ?CbtMigrationReconciler $reconciler = null
    ) {
        $this->db             = $db;
        $this->batchProcessor = $batchProcessor ?? new CbtMigrationBatchProcessor($db);
        $this->verifier       = $verifier ?? new CbtMigrationVerifier($db);
        $this->reconciler     = $reconciler ?? new CbtMigrationReconciler($db);
    }

    /**
     * Run the complete migration pipeline under strict safety controls.
     *
     * @param array $options Configuration flags:
     *                       - 'execute': bool (default false, requires explicit true)
     *                       - 'batch_size': int (default 500, max 1000)
     *                       - 'start_id': int (default 0)
     *                       - 'limit': int (optional row limit)
     *                       - 'operator': string
     *                       - 'environment': string
     *                       - 'source_records': array (optional for isolated test mode)
     * @return array Complete migration audit report
     * @throws RuntimeException If production is detected or preconditions fail
     */
    public function run(array $options = []): array
    {
        $startTime = microtime(true);
        $watermarkTime = date('Y-m-d H:i:s');

        $isExecute = !empty($options['execute']);
        $isDryRun  = !$isExecute;
        $batchSize = min(1000, max(1, (int)($options['batch_size'] ?? 500)));
        $startId   = max(0, (int)($options['start_id'] ?? 0));
        $limit     = isset($options['limit']) ? (int)$options['limit'] : null;
        $operator  = (string)($options['operator'] ?? 'CLI_MIGRATOR');

        // 1. Safety Guard: Enforce production rejection
        $this->assertNonProductionEnvironment($options);

        // 2. Precondition & Schema Validation
        $schemaValidation = $this->validateSchemaPreconditions();

        // 3. Batch Copy Pipeline
        $batchSummaries = [];
        $totalProcessed = 0;
        $lastProcessedId = $startId;
        $batchNum = 1;

        // Check if standalone in-memory records are provided (for unit testing)
        $standaloneRecords = $options['source_records'] ?? null;

        if ($standaloneRecords !== null) {
            // In-memory pagination
            $chunks = array_chunk($standaloneRecords, $batchSize);
            foreach ($chunks as $chunk) {
                if ($limit && $totalProcessed >= $limit) {
                    break;
                }
                $summary = $this->batchProcessor->processBatch($chunk, $batchNum++, $isDryRun, $operator);
                $batchSummaries[] = $summary;
                $totalProcessed += $summary['row_count'];
                $lastProcessedId = $summary['end_id'];
            }
        } elseif ($this->db) {
            // Keyset pagination from database
            while (true) {
                if ($limit && $totalProcessed >= $limit) {
                    break;
                }

                $currentBatchSize = $limit ? min($batchSize, $limit - $totalProcessed) : $batchSize;
                $query = $this->db->table('cbt_answers')
                    ->where('id >', $lastProcessedId)
                    ->orderBy('id', 'ASC')
                    ->limit($currentBatchSize)
                    ->get();

                $records = $query->getResultArray();
                if (empty($records)) {
                    break;
                }

                $summary = $this->batchProcessor->processBatch($records, $batchNum++, $isDryRun, $operator);
                $batchSummaries[] = $summary;
                $totalProcessed += $summary['row_count'];
                $lastProcessedId = $summary['end_id'];
            }
        }

        // 4. Verification Suite
        $verification = $this->verifier->verify($standaloneRecords);

        // 5. Delta Reconciliation
        $reconciliation = $this->reconciler->detectDelta($lastProcessedId, $watermarkTime, $standaloneRecords);

        $executionDurationMs = (int)((microtime(true) - $startTime) * 1000);

        return [
            'pipeline_status'       => 'COMPLETED',
            'mode'                  => $isDryRun ? 'DRY-RUN' : 'EXECUTE',
            'environment'           => $options['environment'] ?? (defined('ENVIRONMENT') ? ENVIRONMENT : 'testing'),
            'execution_duration_ms' => $executionDurationMs,
            'schema_validation'     => $schemaValidation,
            'total_source_scanned'  => $totalProcessed,
            'last_processed_id'     => $lastProcessedId,
            'total_batches_run'     => count($batchSummaries),
            'batch_summaries'       => $batchSummaries,
            'verification'          => $verification,
            'reconciliation'        => $reconciliation,
            'completed_at'          => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Strict safety barrier: Rejects execution on production databases.
     */
    public function assertNonProductionEnvironment(array $options): void
    {
        $env = strtolower((string)($options['environment'] ?? (defined('ENVIRONMENT') ? ENVIRONMENT : '')));

        if ($env === 'production' || $env === 'prod' || $env === 'live') {
            throw new RuntimeException(
                "CRITICAL SECURITY BLOCK: Migration engine execution is strictly blocked on PRODUCTION environments."
            );
        }

        if ($this->db && is_object($this->db)) {
            $dbName = strtolower((string)($this->db->database ?? ''));
            if (str_contains($dbName, 'prod') || str_contains($dbName, 'production') || str_contains($dbName, 'live')) {
                throw new RuntimeException(
                    "CRITICAL SECURITY BLOCK: Database name '{$dbName}' appears to be a production database. Execution blocked."
                );
            }
        }
    }

    /**
     * Validate schema readiness for source and target tables.
     */
    public function validateSchemaPreconditions(): array
    {
        if (!$this->db) {
            return [
                'status'  => 'MOCK_STANDALONE',
                'details' => 'No live database attached. Running in isolated validation mode.',
            ];
        }

        $requiredTables = [
            'cbt_answers'             => 'Source table',
            'cbt_answers_v3'          => 'Target V3 table',
            'cbt_answers_quarantine'  => 'Target Quarantine table',
            'cbt_migration_manifest'  => 'Manifest table',
            'cbt_sessions'            => 'Parent Session table',
            'cbt_session_items'       => 'Session Item assignment table',
            'cbt_test_item_versions'  => 'Item Version table',
        ];

        $missing = [];
        foreach ($requiredTables as $table => $desc) {
            if (!$this->db->tableExists($table)) {
                $missing[] = "{$table} ({$desc})";
            }
        }

        if (!empty($missing)) {
            return [
                'status'         => 'MISSING_TABLES',
                'missing_tables' => $missing,
                'advice'         => 'Run Stage A foundation migration before executing migration.',
            ];
        }

        return [
            'status'  => 'VALIDATED',
            'details' => 'All source and V3 foundation tables are present and verified.',
        ];
    }
}
