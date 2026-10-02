<?php

namespace App\Models;

use CodeIgniter\Model;

class BkServiceParticipantModel extends Model
{
    protected $table            = 'bk_service_participants';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'service_id',
        'student_id',
        'attendance_status',
        'notes',
    ];

    public function getParticipantsWithDetails($serviceId)
    {
        return $this->db->table('bk_service_participants p')
            ->select('p.*, s.name as student_name, s.nisn, s.gender, cl.name as class_name')
            ->join('students s', 's.id = p.student_id', 'left')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->where('p.service_id', $serviceId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();
    }
}
