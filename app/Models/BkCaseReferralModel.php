<?php

namespace App\Models;

use CodeIgniter\Model;

class BkCaseReferralModel extends Model
{
    protected $table            = 'bk_case_referrals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'case_id',
        'student_id',
        'referral_target',
        'target_name',
        'referral_date',
        'reason',
        'recommendation_received',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getReferralsByCase($caseId)
    {
        return $this->db->table('bk_case_referrals r')
            ->select('r.*, s.name as student_name, s.nisn')
            ->join('students s', 's.id = r.student_id', 'left')
            ->where('r.case_id', $caseId)
            ->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();
    }
}
