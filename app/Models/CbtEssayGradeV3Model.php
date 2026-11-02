<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtEssayGradeV3Model
 *
 * Implements append-only essay grading revisions.
 * Revisions never overwrite existing records; a new version is inserted,
 * and only one active version is confirmed per answer.
 */
class CbtEssayGradeV3Model extends Model
{
    protected $table            = 'cbt_essay_grades_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'answer_id',
        'session_id',
        'grader_id',
        'version_number',
        'score_awarded',
        'feedback',
        'is_current_confirmed',
        'graded_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'answer_id'            => 'required|is_natural_no_zero',
        'session_id'           => 'required|is_natural_no_zero',
        'grader_id'            => 'required|is_natural_no_zero',
        'version_number'       => 'required|is_natural_no_zero',
        'score_awarded'        => 'required|decimal',
        'is_current_confirmed' => 'required|in_list[0,1]',
    ];

    /**
     * Atomically confirm an essay grade revision for an answer.
     * Deactivates previous confirmed versions and inserts the new revision.
     */
    public function recordGradeRevision(
        int $answerId,
        int $sessionId,
        int $graderId,
        float $scoreAwarded,
        ?string $feedback = null
    ): array {
        $db = $this->db;

        // 1. Determine next version number
        $latest = $this->where('answer_id', $answerId)
            ->orderBy('version_number', 'DESC')
            ->first();

        $nextVersion = $latest ? ((int)$latest['version_number'] + 1) : 1;

        // 2. Demote previous active version
        $this->where('answer_id', $answerId)
            ->where('is_current_confirmed', 1)
            ->set(['is_current_confirmed' => 0])
            ->update();

        // 3. Insert new active revision
        $data = [
            'answer_id'            => $answerId,
            'session_id'           => $sessionId,
            'grader_id'            => $graderId,
            'version_number'       => $nextVersion,
            'score_awarded'        => $scoreAwarded,
            'feedback'             => $feedback,
            'is_current_confirmed' => 1,
            'graded_at'            => date('Y-m-d H:i:s'),
        ];

        $insertId = $this->insert($data, true);
        $data['id'] = $insertId;

        return $data;
    }

    /**
     * Get the active confirmed grade for an answer.
     */
    public function getActiveGrade(int $answerId): ?array
    {
        return $this->where('answer_id', $answerId)
            ->where('is_current_confirmed', 1)
            ->first();
    }

    /**
     * Get all revisions for a specific answer ordered chronologically.
     */
    public function getRevisionsForAnswer(int $answerId): array
    {
        return $this->where('answer_id', $answerId)
            ->orderBy('version_number', 'ASC')
            ->findAll();
    }

    /**
     * Get all essay grading revisions for an entire student session.
     * Includes grader names where available.
     */
    public function getRevisionsForSession(int $sessionId): array
    {
        $builder = $this->db->table($this->table . ' eg')
            ->select('eg.*, u.nama_lengkap AS grader_name, u.username AS grader_username')
            ->join('users u', 'u.id = eg.grader_id', 'left')
            ->where('eg.session_id', $sessionId)
            ->orderBy('eg.answer_id', 'ASC')
            ->orderBy('eg.version_number', 'ASC');

        return $builder->get()->getResultArray();
    }
}

