<?php

namespace App\Models;

use CodeIgniter\Model;

class BkServiceModel extends Model
{
    protected $table            = 'bk_services';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'rpl_id',
        'academic_year_id',
        'service_type',
        'field',
        'counselor_id',
        'class_id',
        'student_id',
        'service_date',
        'start_time',
        'end_time',
        'status',
        'topic',
        'purpose',
        'material',
        'needs_complaint',
        'assessment_result',
        'activity_summary',
        'session_notes',
        'agreement',
        'evaluation_notes',
        'follow_up',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil data layanan BK beserta detail relasi & support filter lengkap.
     */
    public function getServicesWithDetails($filters = [])
    {
        // Mendukung argumen legacy ($counselorId, $classId, $limit) jika dipanggil format lama
        if (!is_array($filters)) {
            $counselorId = func_get_arg(0) ?? null;
            $classId     = func_num_args() > 1 ? func_get_arg(1) : null;
            $limit       = func_num_args() > 2 ? func_get_arg(2) : null;
            $filters     = [
                'counselor_id' => $counselorId,
                'class_id'     => $classId,
                'limit'        => $limit,
            ];
        }

        $builder = $this->db->table('bk_services s')
            ->select('s.*, cl.name as class_name, t.name as counselor_name, ay.year as academic_year_name, st.name as primary_student_name, st.nisn as primary_student_nisn')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->join('teachers t', 't.id = s.counselor_id', 'left')
            ->join('academic_years ay', 'ay.id = s.academic_year_id', 'left')
            ->join('students st', 'st.id = s.student_id', 'left');

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $builder->groupStart()
                ->like('st.name', $search)
                ->orLike('st.nisn', $search)
                ->orLike('s.topic', $search)
                ->orLike('cl.name', $search)
                ->groupEnd();
        }

        if (!empty($filters['class_id'])) {
            $builder->where('s.class_id', $filters['class_id']);
        }

        if (!empty($filters['academic_year_id'])) {
            $builder->where('s.academic_year_id', $filters['academic_year_id']);
        }

        if (!empty($filters['counselor_id'])) {
            $builder->where('s.counselor_id', $filters['counselor_id']);
        }

        if (!empty($filters['service_type'])) {
            $builder->where('s.service_type', $filters['service_type']);
        }

        if (!empty($filters['status'])) {
            $builder->where('s.status', $filters['status']);
        }

        if (!empty($filters['date'])) {
            $builder->where('s.service_date', $filters['date']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $builder->where('s.service_date >=', $filters['start_date']);
            $builder->where('s.service_date <=', $filters['end_date']);
        }

        $builder->orderBy('s.service_date', 'DESC')->orderBy('s.id', 'DESC');

        if (!empty($filters['limit'])) {
            $builder->limit($filters['limit']);
        }

        return $builder->get()->getResultArray();
    }
}
