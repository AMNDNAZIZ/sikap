<?php

namespace App\Services\Cbt;

use App\Models\CbtAnswerQuarantineModel;
use App\Models\CbtAnswerV3Model;

/**
 * CbtMigrationVerifier
 * Rigorous multi-tier verification engine for CBT V3 migration completeness.
 *
 * Implements:
 * - Count reconciliation (Source, V3, Quarantine, Unprocessed, Conflicts).
 * - Two-way anti-join assertions (MariaDB 10.4.32 compatible).
 * - Cross-table duplicate detection (V3 vs Quarantine exclusivity).
 * - Canonical hash verification.
 * - Multi-subset Merkle root computation.
 */
class CbtMigrationVerifier
{
    private $db;
    private ?CbtAnswerV3Model $v3Model;
    private ?CbtAnswerQuarantineModel $quarantineModel;

    public function __construct(
        $db = null,
        ?CbtAnswerV3Model $v3Model = null,
        ?CbtAnswerQuarantineModel $quarantineModel = null
    ) {
        $this->db              = $db;
        $this->v3Model         = $v3Model;
        $this->quarantineModel = $quarantineModel;
    }

    public function getV3Model(): CbtAnswerV3Model
    {
        if ($this->v3Model === null) {
            $this->v3Model = new CbtAnswerV3Model();
        }
        return $this->v3Model;
    }

    public function getQuarantineModel(): CbtAnswerQuarantineModel
    {
        if ($this->quarantineModel === null) {
            $this->quarantineModel = new CbtAnswerQuarantineModel();
        }
        return $this->quarantineModel;
    }

    /**
     * Execute full verification suite on the current database state or provided dataset.
     *
     * @param array|null $sourceRecords Optional array of source records for standalone/test execution
     * @return array Verification results and verdict
     */
    public function verify(?array $sourceRecords = null): array
    {
        $antiJoinResults = $this->runAntiJoinChecks($sourceRecords);
        $countResults    = $this->runCountReconciliation($sourceRecords, $antiJoinResults);
        $hashResults     = $this->runHashReconciliation($sourceRecords);
        $merkleResults   = $this->runMerkleReconciliation($sourceRecords);

        $hasFailures = (
            $countResults['unprocessed_count'] > 0 ||
            count($antiJoinResults['missing_in_destination']) > 0 ||
            count($antiJoinResults['orphan_in_v3']) > 0 ||
            count($antiJoinResults['orphan_in_quarantine']) > 0 ||
            count($antiJoinResults['cross_table_duplicates']) > 0 ||
            $hashResults['mismatch_count'] > 0
        );

        $verdict = $hasFailures ? 'FAIL' : 'PASS';

        return [
            'verdict'                 => $verdict,
            'counts'                  => $countResults,
            'anti_join'               => $antiJoinResults,
            'hash_reconciliation'     => $hashResults,
            'merkle_reconciliation'   => $merkleResults,
            'verified_at'             => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Run Two-Way Anti-Join assertions to prove data completeness and mutual exclusivity.
     */
    public function runAntiJoinChecks(?array $sourceRecords = null): array
    {
        // Standalone / Test mode using in-memory arrays
        if ($sourceRecords !== null || !$this->db) {
            return $this->runMemoryAntiJoin($sourceRecords ?? []);
        }

        // 1. Missing in destination (Source IDs not in V3 and not in Quarantine)
        $missingQuery = $this->db->query("
            SELECT s.id 
            FROM cbt_answers s
            LEFT JOIN cbt_answers_v3 v ON v.legacy_answer_id = s.id
            LEFT JOIN cbt_answers_quarantine q ON q.legacy_answer_id = s.id
            WHERE v.id IS NULL AND q.id IS NULL
            LIMIT 100
        ");
        $missingInDestination = array_column($missingQuery->getResultArray(), 'id');

        // 2. Orphan in V3 (V3 rows that do not exist in cbt_answers)
        $orphanV3Query = $this->db->query("
            SELECT v.legacy_answer_id 
            FROM cbt_answers_v3 v
            LEFT JOIN cbt_answers s ON s.id = v.legacy_answer_id
            WHERE v.legacy_answer_id IS NOT NULL AND s.id IS NULL
            LIMIT 100
        ");
        $orphanInV3 = array_column($orphanV3Query->getResultArray(), 'legacy_answer_id');

        // 3. Orphan in Quarantine (Quarantine rows that do not exist in cbt_answers)
        $orphanQuarQuery = $this->db->query("
            SELECT q.legacy_answer_id 
            FROM cbt_answers_quarantine q
            LEFT JOIN cbt_answers s ON s.id = q.legacy_answer_id
            WHERE s.id IS NULL
            LIMIT 100
        ");
        $orphanInQuarantine = array_column($orphanQuarQuery->getResultArray(), 'legacy_answer_id');

        // 4. Cross-table duplicates (IDs appearing simultaneously in V3 and Quarantine)
        $dupesQuery = $this->db->query("
            SELECT v.legacy_answer_id 
            FROM cbt_answers_v3 v
            INNER JOIN cbt_answers_quarantine q ON q.legacy_answer_id = v.legacy_answer_id
            LIMIT 100
        ");
        $crossTableDuplicates = array_column($dupesQuery->getResultArray(), 'legacy_answer_id');

        return [
            'missing_in_destination' => $missingInDestination,
            'orphan_in_v3'           => $orphanInV3,
            'orphan_in_quarantine'   => $orphanInQuarantine,
            'cross_table_duplicates' => $crossTableDuplicates,
        ];
    }

    /**
     * In-memory anti-join evaluation for unit tests and isolated validation.
     */
    private function runMemoryAntiJoin(array $sourceRecords): array
    {
        $sourceIds = array_column($sourceRecords, 'id');
        $v3Rows = ($this->db || $this->v3Model) ? $this->getV3Model()->findAll() : [];
        $quarRows = ($this->db || $this->quarantineModel) ? $this->getQuarantineModel()->findAll() : [];

        $v3LegacyIds = array_filter(array_column($v3Rows, 'legacy_answer_id'));
        $quarLegacyIds = array_column($quarRows, 'legacy_answer_id');

        $destinationMap = array_flip(array_merge($v3LegacyIds, $quarLegacyIds));
        $missingInDestination = [];

        foreach ($sourceIds as $id) {
            if (!isset($destinationMap[$id])) {
                $missingInDestination[] = $id;
            }
        }

        $sourceMap = array_flip($sourceIds);
        $orphanInV3 = [];
        foreach ($v3LegacyIds as $id) {
            if (!isset($sourceMap[$id])) {
                $orphanInV3[] = $id;
            }
        }

        $orphanInQuar = [];
        foreach ($quarLegacyIds as $id) {
            if (!isset($sourceMap[$id])) {
                $orphanInQuar[] = $id;
            }
        }

        $crossDupes = array_values(array_intersect($v3LegacyIds, $quarLegacyIds));

        return [
            'missing_in_destination' => $missingInDestination,
            'orphan_in_v3'           => $orphanInV3,
            'orphan_in_quarantine'   => $orphanInQuar,
            'cross_table_duplicates' => $crossDupes,
        ];
    }

    /**
     * Compute count reconciliation metrics.
     */
    private function runCountReconciliation(?array $sourceRecords, array $antiJoinResults): array
    {
        $sourceCount = $sourceRecords !== null 
            ? count($sourceRecords) 
            : ($this->db ? $this->db->table('cbt_answers')->countAllResults() : 0);

        $v3Count   = ($this->db || $this->v3Model) ? $this->getV3Model()->countAllResults() : 0;
        $quarCount = ($this->db || $this->quarantineModel) ? $this->getQuarantineModel()->countAllResults() : 0;
        $processedCount = $v3Count + $quarCount;
        $unprocessedCount = max(0, $sourceCount - $processedCount);

        return [
            'source_count'       => $sourceCount,
            'target_v3_count'    => $v3Count,
            'quarantine_count'   => $quarCount,
            'processed_count'    => $processedCount,
            'unprocessed_count'  => $unprocessedCount,
            'conflict_count'     => count($antiJoinResults['cross_table_duplicates']),
        ];
    }

    /**
     * Verify canonical hash equality between source records and destination stored hashes.
     */
    private function runHashReconciliation(?array $sourceRecords): array
    {
        if ($sourceRecords === null && !$this->db) {
            return ['status' => 'SKIPPED', 'checked_count' => 0, 'mismatch_count' => 0];
        }

        $records = $sourceRecords ?? ($this->db ? $this->db->table('cbt_answers')->limit(500)->get()->getResultArray() : []);
        $mismatches = [];
        $checkedCount = 0;

        foreach ($records as $row) {
            $checkedCount++;
            $legacyId = (int)$row['id'];
            $expectedHash = EspataCanonicalSerializer::hash($row);

            $targetHash = null;
            if ($this->db || $this->v3Model) {
                $v3Row = $this->getV3Model()->where('legacy_answer_id', $legacyId)->first();
                if ($v3Row) {
                    $targetHash = $v3Row['canonical_hash'];
                }
            }

            if ($targetHash === null && ($this->db || $this->quarantineModel)) {
                $quarRow = $this->getQuarantineModel()->where('legacy_answer_id', $legacyId)->first();
                if ($quarRow) {
                    $targetHash = $quarRow['canonical_hash'];
                }
            }

            if ($targetHash !== null && $targetHash !== $expectedHash) {
                $mismatches[] = [
                    'legacy_id'     => $legacyId,
                    'expected_hash' => $expectedHash,
                    'stored_hash'   => $targetHash,
                ];
            }
        }

        return [
            'status'         => empty($mismatches) ? 'VERIFIED' : 'MISMATCH_DETECTED',
            'checked_count'  => $checkedCount,
            'mismatch_count' => count($mismatches),
            'mismatches'     => $mismatches,
        ];
    }

    /**
     * Compute separate Merkle roots for Source, V3, and Quarantine.
     */
    private function runMerkleReconciliation(?array $sourceRecords): array
    {
        $v3Rows = ($this->db || $this->v3Model) ? $this->getV3Model()->findAll() : [];
        $quarRows = ($this->db || $this->quarantineModel) ? $this->getQuarantineModel()->findAll() : [];

        $v3Hashes = array_column($v3Rows, 'canonical_hash');
        $quarHashes = array_column($quarRows, 'canonical_hash');

        $sourceRoot = $sourceRecords !== null 
            ? MerkleTreeEngine::computeRootFromRecords($sourceRecords) 
            : 'UNAVAILABLE_WITHOUT_FULL_SOURCE_SCAN';

        return [
            'source_merkle_root'     => $sourceRoot,
            'v3_subset_merkle_root'  => MerkleTreeEngine::computeRootFromLeafHashes($v3Hashes),
            'quar_subset_merkle_root'=> MerkleTreeEngine::computeRootFromLeafHashes($quarHashes),
        ];
    }
}
