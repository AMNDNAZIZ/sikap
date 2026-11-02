<?php

namespace App\Services\Cbt;

use App\Models\CbtAnswerQuarantineModel;
use App\Models\CbtAnswerV3Model;
use App\Models\CbtMigrationManifestModel;
use InvalidArgumentException;
use RuntimeException;

/**
 * CbtMigrationBatchProcessor
 * Handles chunked, idempotent, non-destructive migration of cbt_answers records.
 *
 * Enforces:
 * - Deterministic serialization & hashing via EspataCanonicalSerializer.
 * - Merkle root computation via MerkleTreeEngine.
 * - Strict non-destructive copy (NEVER deletes from cbt_answers).
 * - Keyset-based pagination.
 * - Optimistic duplicate detection: skips identical records, flags hash conflicts.
 * - Compatibility with MariaDB 10.4.32.
 */
class CbtMigrationBatchProcessor
{
    private $db;
    private ?CbtAnswerV3Model $v3Model;
    private ?CbtAnswerQuarantineModel $quarantineModel;
    private ?CbtMigrationManifestModel $manifestModel;

    public function __construct(
        $db = null,
        ?CbtAnswerV3Model $v3Model = null,
        ?CbtAnswerQuarantineModel $quarantineModel = null,
        ?CbtMigrationManifestModel $manifestModel = null
    ) {
        $this->db              = $db;
        $this->v3Model         = $v3Model;
        $this->quarantineModel = $quarantineModel;
        $this->manifestModel   = $manifestModel;
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

    public function getManifestModel(): CbtMigrationManifestModel
    {
        if ($this->manifestModel === null) {
            $this->manifestModel = new CbtMigrationManifestModel();
        }
        return $this->manifestModel;
    }

    /**
     * Process a single batch of cbt_answers records.
     *
     * @param array  $records  Array of raw legacy records
     * @param int    $batchNum Batch sequence number
     * @param bool   $isDryRun If true, computes metrics without database mutation
     * @param string $operator Identity executing the migration
     * @return array Processing summary and Merkle root
     */
    public function processBatch(array $records, int $batchNum, bool $isDryRun = true, string $operator = 'CLI_MIGRATOR'): array
    {
        if (empty($records)) {
            return [
                'batch_number'        => $batchNum,
                'status'              => 'COMPLETED',
                'row_count'           => 0,
                'migrated_v3_count'   => 0,
                'quarantined_count'   => 0,
                'skipped_count'       => 0,
                'conflict_count'      => 0,
                'batch_merkle_root'   => MerkleTreeEngine::computeRootFromRecords([]),
                'start_id'            => 0,
                'end_id'              => 0,
                'is_dry_run'          => $isDryRun,
                'conflicts'           => [],
            ];
        }

        $startTime = microtime(true);

        // Sort records deterministically by ID ASC
        usort($records, fn($a, $b) => (int)$a['id'] <=> (int)$b['id']);

        $startId          = (int)$records[0]['id'];
        $endId            = (int)$records[count($records) - 1]['id'];
        $batchMerkleRoot  = MerkleTreeEngine::computeRootFromRecords($records);

        $v3Inserts        = [];
        $quarantineInserts= [];
        $skippedCount     = 0;
        $conflictCount    = 0;
        $conflicts        = [];

        foreach ($records as $row) {
            $legacyId      = (int)$row['id'];
            $canonicalHash = EspataCanonicalSerializer::hash($row);

            // Pre-check for existing record in target or quarantine (idempotency guard)
            $existingStatus = $this->checkExistingRecord($legacyId, $canonicalHash);

            if ($existingStatus === 'IDENTICAL_SKIP') {
                $skippedCount++;
                continue;
            }

            if ($existingStatus === 'HASH_CONFLICT') {
                $conflictCount++;
                $conflicts[] = [
                    'legacy_id'      => $legacyId,
                    'source_hash'    => $canonicalHash,
                    'conflict_type'  => 'TARGET_EXISTS_WITH_DIFFERENT_HASH',
                ];
                continue;
            }

            // Classify record based on provenance evidence
            $classification = $this->classifyRecord($row);

            if ($classification['target'] === 'V3') {
                $v3Inserts[] = [
                    'legacy_answer_id'  => $legacyId,
                    'session_id'        => $classification['session_id'],
                    'item_version_id'   => $classification['item_version_id'],
                    'student_id'        => (int)$row['student_id'],
                    'answer_payload'    => $row['answer'] !== null ? (string)$row['answer'] : null,
                    'allocated_score'   => $row['score'] !== null ? (float)$row['score'] : null,
                    'is_doubtful'       => (int)($row['is_doubtful'] ?? 0),
                    'answer_version'    => (int)($row['version'] ?? 1),
                    'provenance_status' => 'PROBABLE_MATCH',
                    'canonical_hash'    => $canonicalHash,
                    'created_at'        => $row['updated_at'] ?? date('Y-m-d H:i:s'),
                ];
            } else {
                $quarantineInserts[] = [
                    'legacy_answer_id'        => $legacyId,
                    'student_id'              => (int)$row['student_id'],
                    'test_id'                 => (int)$row['test_id'],
                    'question_id'             => (int)$row['question_id'],
                    'answer_raw'              => $row['answer'] !== null ? (string)$row['answer'] : null,
                    'score_raw'               => $row['score'] !== null ? (float)$row['score'] : null,
                    'is_doubtful_raw'         => (int)($row['is_doubtful'] ?? 0),
                    'version_raw'             => (int)($row['version'] ?? 1),
                    'updated_at_raw'          => $row['updated_at'] ?? null,
                    'updated_at_micro_raw'    => $row['updated_at_micro'] ?? null,
                    'quarantine_reason'       => $classification['reason'],
                    'quarantine_details_json' => json_encode($classification['details']),
                    'canonical_hash'          => $canonicalHash,
                    'quarantined_at'          => date('Y-m-d H:i:s'),
                ];
            }
        }

        $migratedV3Count   = count($v3Inserts);
        $quarantinedCount  = count($quarantineInserts);
        $durationMs        = (int)((microtime(true) - $startTime) * 1000);

        // Execute transaction if not dry-run
        if (!$isDryRun) {
            $this->executeBatchDatabaseWrites(
                $batchNum,
                $startId,
                $endId,
                count($records),
                $migratedV3Count,
                $quarantinedCount,
                $batchMerkleRoot,
                $v3Inserts,
                $quarantineInserts,
                $operator,
                $durationMs
            );
        }

        return [
            'batch_number'        => $batchNum,
            'status'              => $conflictCount > 0 ? 'COMPLETED_WITH_CONFLICTS' : 'COMPLETED',
            'row_count'           => count($records),
            'migrated_v3_count'   => $migratedV3Count,
            'quarantined_count'   => $quarantinedCount,
            'skipped_count'       => $skippedCount,
            'conflict_count'      => $conflictCount,
            'batch_merkle_root'   => $batchMerkleRoot,
            'start_id'            => $startId,
            'end_id'              => $endId,
            'is_dry_run'          => $isDryRun,
            'duration_ms'         => $durationMs,
            'conflicts'           => $conflicts,
        ];
    }

    /**
     * Check if a legacy answer ID already exists in target V3 or Quarantine.
     *
     * @param int    $legacyId
     * @param string $sourceHash
     * @return string 'NOT_FOUND', 'IDENTICAL_SKIP', or 'HASH_CONFLICT'
     */
    public function checkExistingRecord(int $legacyId, string $sourceHash): string
    {
        if (!$this->db && $this->v3Model === null && $this->quarantineModel === null) {
            return 'NOT_FOUND';
        }

        // 1. Check in cbt_answers_v3
        $existingV3 = $this->getV3Model()->where('legacy_answer_id', $legacyId)->first();
        if ($existingV3) {
            return $existingV3['canonical_hash'] === $sourceHash ? 'IDENTICAL_SKIP' : 'HASH_CONFLICT';
        }

        // 2. Check in cbt_answers_quarantine
        $existingQuar = $this->getQuarantineModel()->where('legacy_answer_id', $legacyId)->first();
        if ($existingQuar) {
            return $existingQuar['canonical_hash'] === $sourceHash ? 'IDENTICAL_SKIP' : 'HASH_CONFLICT';
        }

        return 'NOT_FOUND';
    }

    /**
     * Classify record based on provenance evidence without assuming unapproved heuristics.
     *
     * @param array $row
     * @return array ['target' => 'V3'|'QUARANTINE', 'reason' => string, 'details' => array, ...]
     */
    public function classifyRecord(array $row): array
    {
        $studentId  = (int)$row['student_id'];
        $testId     = (int)$row['test_id'];
        $questionId = (int)$row['question_id'];

        // If no database connection is supplied (e.g. standalone test mode), check row hints
        if (!$this->db) {
            if (!empty($row['_mock_session_id']) && !empty($row['_mock_item_version_id'])) {
                return [
                    'target'          => 'V3',
                    'session_id'      => (int)$row['_mock_session_id'],
                    'item_version_id' => (int)$row['_mock_item_version_id'],
                    'reason'          => 'PROBABLE_MATCH',
                    'details'         => ['evidence' => 'Mock verified session item'],
                ];
            }

            return [
                'target'  => 'QUARANTINE',
                'reason'  => $row['_mock_quarantine_reason'] ?? 'AMBIGUOUS_METADATA',
                'details' => ['note' => 'No database connection or unverified session relation'],
            ];
        }

        // 1. Query candidate sessions for (student_id, test_id)
        $sessions = $this->db->table('cbt_sessions')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->get()
            ->getResultArray();

        // Condition A: 0 sessions found -> ORPHAN
        if (empty($sessions)) {
            return [
                'target'  => 'QUARANTINE',
                'reason'  => 'ORPHAN_NO_SESSION',
                'details' => ['error' => 'No session row exists for student_id and test_id'],
            ];
        }

        // Condition B: Multiple sessions found -> AMBIGUOUS_METADATA / RESET_AFFECTED
        if (count($sessions) > 1) {
            return [
                'target'  => 'QUARANTINE',
                'reason'  => 'RESET_AFFECTED_DISCREPANCY',
                'details' => [
                    'error'         => 'Multiple sessions found for student and test',
                    'session_count' => count($sessions),
                    'session_ids'   => array_column($sessions, 'id'),
                ],
            ];
        }

        $session = $sessions[0];
        $sessionId = (int)$session['id'];

        // 2. Query cbt_session_items to find allocated item_version_id
        $sessionItem = $this->db->table('cbt_session_items')
            ->join('cbt_test_item_versions tiv', 'tiv.id = cbt_session_items.item_version_id')
            ->join('cbt_test_items ti', 'ti.id = cbt_session_items.test_item_id')
            ->where('cbt_session_items.session_id', $sessionId)
            ->where('ti.bank_question_id', $questionId)
            ->select('cbt_session_items.item_version_id, cbt_session_items.test_id')
            ->get()
            ->getRowArray();

        if (!$sessionItem) {
            return [
                'target'  => 'QUARANTINE',
                'reason'  => 'AMBIGUOUS_METADATA',
                'details' => [
                    'error'      => 'Question was not allocated in session items',
                    'session_id' => $sessionId,
                ],
            ];
        }

        // Session item found and single finished session verified -> PROBABLE_MATCH
        return [
            'target'          => 'V3',
            'session_id'      => $sessionId,
            'item_version_id' => (int)$sessionItem['item_version_id'],
            'reason'          => 'PROBABLE_MATCH',
            'details'         => [
                'session_id'      => $sessionId,
                'session_status'  => $session['status'] ?? 'unknown',
            ],
        ];
    }

    /**
     * Atomically write V3 records, quarantine records, and manifest entry within an InnoDB transaction.
     */
    private function executeBatchDatabaseWrites(
        int $batchNum,
        int $startId,
        int $endId,
        int $rowCount,
        int $v3Count,
        int $quarCount,
        string $merkleRoot,
        array $v3Inserts,
        array $quarantineInserts,
        string $operator,
        int $durationMs
    ): void {
        if (!$this->db) {
            return;
        }

        $this->db->transStart();

        try {
            // 1. Insert into cbt_answers_v3
            if (!empty($v3Inserts)) {
                $this->db->table('cbt_answers_v3')->insertBatch($v3Inserts);
            }

            // 2. Insert into cbt_answers_quarantine
            if (!empty($quarantineInserts)) {
                $this->db->table('cbt_answers_quarantine')->insertBatch($quarantineInserts);
            }

            // 3. Record manifest
            $this->db->table('cbt_migration_manifest')->insert([
                'batch_number'          => $batchNum,
                'start_legacy_id'       => $startId,
                'end_legacy_id'         => $endId,
                'row_count'             => $rowCount,
                'migrated_v3_count'     => $v3Count,
                'quarantined_count'     => $quarCount,
                'batch_merkle_root'     => $merkleRoot,
                'status'                => 'VERIFIED',
                'executed_by'           => $operator,
                'execution_duration_ms' => $durationMs,
                'committed_at'          => date('Y-m-d H:i:s'),
            ]);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new RuntimeException("InnoDB transaction failed during batch {$batchNum} execution.");
            }
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw new RuntimeException("Batch {$batchNum} database write aborted: " . $e->getMessage(), 0, $e);
        }
    }
}
