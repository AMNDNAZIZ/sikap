<?php

namespace App\Models;

use CodeIgniter\Model;

class BkIndividualPlanModel extends Model
{
    protected $table            = 'bk_individual_plans';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'student_id',
        'academic_year_id',
        'strengths',
        'growth_areas',
        'interests',
        'learning_style',
        'career_target',
        'academic_target',
        'habit_target',
        'progress_percent',
        'counselor_notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getPlanWithStudent($studentId)
    {
        return $this->db->table('bk_individual_plans ip')
            ->select('ip.*, s.name as student_name, s.nisn, s.gender, cl.name as class_name')
            ->join('students s', 's.id = ip.student_id', 'left')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->where('ip.student_id', $studentId)
            ->get()->getRowArray();
    }
}
