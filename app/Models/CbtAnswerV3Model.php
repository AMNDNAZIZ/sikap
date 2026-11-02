<?php

namespace App\Models;

use CodeIgniter\Model;
use RuntimeException;

/**
 * CbtAnswerV3Model
 * High-integrity operational answer model for CBT V3 with concurrency protection.
 */
class CbtAnswerV3Model extends Model
{
    protected $table            = 'cbt_answers_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'legacy_answer_id',
        'session_id',
        'item_version_id',
        'student_id',
        'answer_payload',
        'allocated_score',
        'is_doubtful',
        'answer_version',
        'provenance_status',
        'canonical_hash',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'session_id'        => 'required|is_natural_no_zero',
        'item_version_id'   => 'required|is_natural_no_zero',
        'student_id'        => 'required|is_natural_no_zero',
        'allocated_score'   => 'permit_empty|decimal',
        'is_doubtful'       => 'permit_empty|in_list[0,1]',
        'answer_version'    => 'permit_empty|is_natural_no_zero',
        'provenance_status' => 'required|in_list[PROBABLE_MATCH,OPERATIONAL_V3]',
        'canonical_hash'    => 'required|exact_length[64]',
    ];

    /**
     * Atomically save or update an answer with optimistic concurrency fencing.
     * - If existing answer has identical payload and is_doubtful, returns existing record WITHOUT incrementing version.
     * - If payload or doubtful status changed, increments answer_version atomically.
     * - If $expectedVersion is provided and conflicts with current DB state, rejects with concurrency error.
     *
     * @param int         $sessionId
     * @param int         $itemVersionId
     * @param int         $studentId
     * @param string|null $answerPayload
     * @param int         $isDoubtful
     * @param string      $canonicalHash
     * @param int|null    $expectedVersion
     * @return array
     * @throws RuntimeException
     */
    public function saveAnswerAtomically(
        int $sessionId,
        int $itemVersionId,
        int $studentId,
        ?string $answerPayload,
        int $isDoubtful,
        string $canonicalHash,
        ?int $expectedVersion = null,
        ?float $allocatedScore = null
    ): array {
        $existing = $this->where('session_id', $sessionId)
            ->where('item_version_id', $itemVersionId)
            ->first();

        // 1. Initial insert
        if (!$existing) {
            $insertData = [
                'session_id'        => $sessionId,
                'item_version_id'   => $itemVersionId,
                'student_id'        => $studentId,
                'answer_payload'    => $answerPayload,
                'allocated_score'   => $allocatedScore,
                'is_doubtful'       => $isDoubtful,
                'answer_version'    => 1,
                'provenance_status' => 'OPERATIONAL_V3',
                'canonical_hash'    => $canonicalHash,
            ];
            $id = $this->insert($insertData);
            return array_merge($insertData, ['id' => $id, 'version_incremented' => false]);
        }

        // 2. Identical autosave detection: Avoid spurious version bumps
        if (
            (string)$existing['answer_payload'] === (string)$answerPayload &&
            (int)$existing['is_doubtful'] === $isDoubtful &&
            $existing['allocated_score'] === $allocatedScore
        ) {
            return array_merge($existing, ['version_incremented' => false]);
        }

        // 3. Optimistic locking check
        $currentVersion = (int)$existing['answer_version'];
        if ($expectedVersion !== null && $currentVersion !== $expectedVersion) {
            throw new RuntimeException(
                "CBT V3 Concurrency Conflict: Answer has been modified (current version: {$currentVersion}, expected: {$expectedVersion})."
            );
        }

        // 4. Atomic version increment
        $newVersion = $currentVersion + 1;
        $updateData = [
            'answer_payload'  => $answerPayload,
            'allocated_score' => $allocatedScore,
            'is_doubtful'     => $isDoubtful,
            'answer_version'  => $newVersion,
            'canonical_hash'  => $canonicalHash,
        ];

        $affected = $this->where('id', $existing['id'])
            ->where('answer_version', $currentVersion)
            ->set($updateData)
            ->update();

        if (!$affected) {
            throw new RuntimeException(
                "CBT V3 Concurrency Conflict: Atomic version CAS update failed for answer ID {$existing['id']}."
            );
        }

        return array_merge($existing, $updateData, ['version_incremented' => true]);
    }
}
