<?php

namespace App\Models;

use CodeIgniter\Model;

class BkCaseActionModel extends Model
{
    protected $table            = 'bk_case_actions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'case_id',
        'action_date',
        'action_type',
        'description',
        'result_notes',
        'next_step',
        'performed_by',
        'created_at',
    ];

    public function getActionsByCase($caseId)
    {
        return $this->db->table('bk_case_actions a')
            ->select('a.*, t.name as performer_name, u.username as performer_username')
            ->join('teachers t', 't.id = a.performed_by', 'left')
            ->join('users u', 'u.id = a.performed_by', 'left')
            ->where('a.case_id', $caseId)
            ->orderBy('a.action_date', 'ASC')
            ->orderBy('a.id', 'ASC')
            ->get()->getResultArray();
    }
}
