<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtGradebookPublicationV3Model
 *
 * ESPATA Assessment Intelligence V3 - Gradebook Publication Lineage.
 * Tracks published CBT scores into summative_scores, final_exam_scores, and pts_scores.
 */
class CbtGradebookPublicationV3Model extends Model
{
    protected $table            = 'cbt_gradebook_publications_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'session_id',
        'test_id',
        'student_id',
        'target_table',
        'target_record_id',
        'published_score',
        'previous_score',
        'status',
        'published_by',
        'published_at',
        'updated_at',
        'audit_hash',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'published_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    public const STATUS_PUBLISHED = 'PUBLISHED';
    public const STATUS_REVISED   = 'REVISED';
    public const STATUS_RETRACTED = 'RETRACTED';

    /**
     * Find publication by session ID and target table.
     */
    public function findBySessionAndTarget(int $sessionId, string $targetTable): ?array
    {
        return $this->where('session_id', $sessionId)
            ->where('target_table', $targetTable)
            ->first();
    }

    /**
     * Get all publications for a specific test.
     */
    public function getByTestId(int $testId): array
    {
        return $this->where('test_id', $testId)->findAll();
    }
}

