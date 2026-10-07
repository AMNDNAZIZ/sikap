<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulMemberModel extends Model
{
    protected $table            = 'ekskul_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'student_id', 'academic_year_id', 'status'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get students enrolled in a specific ekskul
     */
    public function getMembers($ekskulId, $academicYearId = null)
    {
        $builder = $this->select('ekskul_members.*, students.name as student_name, students.nis, classes.name as class_name')
            ->join('students', 'students.id = ekskul_members.student_id')
            ->join('student_records', 'student_records.student_id = ekskul_members.student_id AND student_records.academic_year_id = ekskul_members.academic_year_id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('ekskul_members.ekskul_id', $ekskulId);

        if ($academicYearId) {
            $builder->where('ekskul_members.academic_year_id', $academicYearId);
        }

        return $builder->orderBy('classes.name', 'ASC')->orderBy('students.name', 'ASC')->findAll();
    }
}
