<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtSessionModel extends Model
{
    protected $table = 'cbt_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id',
        'test_id',
        'started_at',
        'question_order',
        'option_orders',
        'last_activity',
        'status',
        'score',
        'essay_score',
        'total_score',
        'finished_at',
        'reset_token',
        'cheat_locked',
        'extra_time'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $returnType = 'array';

    /**
     * Get active session for specific student & test
     */
    public function getActiveSession($studentId, $testId)
    {
        return $this->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Mark session as finished
     */
    public function markFinished($sessionId)
    {
        return $this->update($sessionId, ['status' => 'finished']);
    }

    /**
     * Reset session (dipakai admin)
     */
    public function resetSession($studentId, $testId)
    {
        $newToken = bin2hex(random_bytes(8));

        return $this->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->set([
                'status' => 'active',
                'finished_at' => null,
                'score' => null,
                'essay_score' => null,
                'total_score' => null,
                'cheat_locked' => 0,
                'reset_token' => $newToken,
                'updated_at' => date('Y-m-d H:i:s')
            ])
            ->update();
    }
}
