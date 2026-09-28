<?php

namespace App\Models;

use CodeIgniter\Model;

class BkCaseLogModel extends Model
{
    protected $table            = 'bk_case_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'case_id',
        'old_status',
        'new_status',
        'changed_by',
        'notes',
        'created_at',
    ];

    public function logStatusChange($caseId, $oldStatus, $newStatus, $userId, $notes = null)
    {
        return $this->insert([
            'case_id'    => $caseId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $userId,
            'notes'      => $notes,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function getLogsByCase($caseId)
    {
        return $this->db->table('bk_case_logs l')
            ->select('l.*, u.username, t.name as teacher_name')
            ->join('users u', 'u.id = l.changed_by', 'left')
            ->join('teachers t', 't.user_id = u.id', 'left')
            ->where('l.case_id', $caseId)
            ->orderBy('l.created_at', 'ASC')
            ->get()->getResultArray();
    }
}
