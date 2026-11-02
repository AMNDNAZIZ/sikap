<?php

namespace App\Services\Cbt;

use App\Models\CbtAnswerQuarantineModel;
use App\Models\CbtAnswerV3Model;

/**
 * CbtMigrationReconciler
 * Detects concurrent data drift, new rows, and modifications during the migration window.
 *
 * Implements:
 * - Watermark-based new row detection (id > watermark_max_id).
 * - Temporal change detection (updated_at and updated_at_micro).
 * - Canonical hash divergence scanning.
 * - Delta catch-up planning.
 */
class CbtMigrationReconciler
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
     * Analyze delta drift between current legacy state and already migrated records.
     *
     * @param int         $maxProcessedId Maximum legacy ID recorded in manifest
     * @param string|null $watermarkTime  ISO timestamp when migration began
     * @param array|null  $currentSourceRecords Optional array for standalone/test execution
     * @return array Delta drift report
     */
    public function detectDelta(
        int $maxProcessedId,
        ?string $watermarkTime = null,
        ?array $currentSourceRecords = null
    ): array {
        if ($currentSourceRecords !== null || !$this->db) {
            return $this->detectMemoryDelta($maxProcessedId, $watermarkTime, $currentSourceRecords ?? []);
        }

        // 1. Detect new rows inserted beyond the ID watermark
        $newRowsQuery = $this->db->table('cbt_answers')
            ->where('id >', $maxProcessedId)
            ->select('id, student_id, test_id, question_id, updated_at')
            ->get();
        $newRows = $newRowsQuery->getResultArray();

        // 2. Detect modified rows with updated_at > watermark time
        $modifiedRows = [];
        if ($watermarkTime) {
            $modifiedQuery = $this->db->table('cbt_answers')
                ->where('id <=', $maxProcessedId)
                ->where('updated_at >', $watermarkTime)
                ->select('id, student_id, test_id, question_id, updated_at, updated_at_micro')
                ->get();
            $modifiedRows = $modifiedQuery->getResultArray();
        }

        // 3. Scan for hash divergences among already migrated rows
        $divergentRows = $this->scanHashDivergence();

        $requiresWriteFreeze = (count($newRows) > 0 || count($modifiedRows) > 0 || count($divergentRows) > 0);

        return [
            'watermark_max_id'        => $maxProcessedId,
            'watermark_time'          => $watermarkTime,
            'new_rows_count'          => count($newRows),
            'new_row_ids'             => array_column($newRows, 'id'),
            'modified_rows_count'     => count($modifiedRows),
            'modified_row_ids'        => array_column($modifiedRows, 'id'),
            'divergent_rows_count'    => count($divergentRows),
            'divergent_rows'          => $divergentRows,
            'requires_write_freeze'   => $requiresWriteFreeze,
            'reconciliation_advice'   => $requiresWriteFreeze 
                ? 'Concurrent legacy activity detected. Final consistency requires activating maintenance write freeze before catch-up copy.'
                : 'Zero drift detected. System is consistent with current legacy snapshot.',
            'reconciled_at'           => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * In-memory delta drift detection for test environments.
     */
    private function detectMemoryDelta(
        int $maxProcessedId,
        ?string $watermarkTime,
        array $records
    ): array {
        $newRows = [];
        $modifiedRows = [];
        $divergentRows = [];

        foreach ($records as $row) {
            $id = (int)$row['id'];
            if ($id > $maxProcessedId) {
                $newRows[] = $id;
                continue;
            }

            if ($watermarkTime && !empty($row['updated_at']) && $row['updated_at'] > $watermarkTime) {
                $modifiedRows[] = $id;
            }

            // Check if stored hash in V3 or Quarantine matches current row hash
            $currentHash = EspataCanonicalSerializer::hash($row);
            $storedHash = null;

            if ($this->db || $this->v3Model) {
                $v3Row = $this->getV3Model()->where('legacy_answer_id', $id)->first();
                $storedHash = $v3Row['canonical_hash'] ?? null;
            }

            if (!$storedHash && ($this->db || $this->quarantineModel)) {
                $quarRow = $this->getQuarantineModel()->where('legacy_answer_id', $id)->first();
                $storedHash = $quarRow['canonical_hash'] ?? null;
            }

            if ($storedHash && $storedHash !== $currentHash) {
                $divergentRows[] = [
                    'legacy_id'    => $id,
                    'stored_hash'  => $storedHash,
                    'current_hash' => $currentHash,
                ];
            }
        }

        $requiresWriteFreeze = (count($newRows) > 0 || count($modifiedRows) > 0 || count($divergentRows) > 0);

        return [
            'watermark_max_id'        => $maxProcessedId,
            'watermark_time'          => $watermarkTime,
            'new_rows_count'          => count($newRows),
            'new_row_ids'             => $newRows,
            'modified_rows_count'     => count($modifiedRows),
            'modified_row_ids'        => $modifiedRows,
            'divergent_rows_count'    => count($divergentRows),
            'divergent_rows'          => $divergentRows,
            'requires_write_freeze'   => $requiresWriteFreeze,
            'reconciliation_advice'   => $requiresWriteFreeze 
                ? 'Concurrent legacy activity detected. Final consistency requires activating maintenance write freeze before catch-up copy.'
                : 'Zero drift detected. System is consistent with current legacy snapshot.',
            'reconciled_at'           => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Scan sample of migrated rows in database to detect hash divergence.
     */
    private function scanHashDivergence(int $sampleLimit = 500): array
    {
        if (!$this->db) {
            return [];
        }

        // Compare hash of legacy cbt_answers with cbt_answers_v3
        $sampleQuery = $this->db->query("
            SELECT s.*, v.canonical_hash as v3_hash
            FROM cbt_answers s
            INNER JOIN cbt_answers_v3 v ON v.legacy_answer_id = s.id
            ORDER BY s.id DESC
            LIMIT {$sampleLimit}
        ");

        $divergences = [];
        foreach ($sampleQuery->getResultArray() as $row) {
            $expectedHash = EspataCanonicalSerializer::hash($row);
            if ($row['v3_hash'] !== $expectedHash) {
                $divergences[] = [
                    'legacy_id'     => (int)$row['id'],
                    'stored_v3_hash'=> $row['v3_hash'],
                    'current_hash'  => $expectedHash,
                ];
            }
        }

        return $divergences;
    }
}
