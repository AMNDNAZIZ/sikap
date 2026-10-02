<?php

namespace App\Models;

use CodeIgniter\Model;

class BkCareerModel extends Model
{
    protected $table            = 'bk_career_profiles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;

    protected $allowedFields = [
        'student_id', 'post_graduate_plan', 'university_target', 'major_interest',
        'hobby', 'talent', 'favorite_subject', 'dream_job',
        'family_income', 'family_support', 'motivation',
        'filled_at', 'counselor_note'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getAllWithStudent()
    {
        // Ambil tahun ajaran aktif
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : 0;

        return $this->select('bk_career_profiles.*, students.name as student_name, students.nis, classes.name as class_name')
                    ->join('students', 'students.id = bk_career_profiles.student_id')
                    ->join('student_records', 'student_records.student_id = students.id')
                    ->join('classes', 'classes.id = student_records.class_id', 'left')
                    ->where('student_records.status', 'aktif')
                    ->where('student_records.academic_year_id', $activeYearId)
                    ->orderBy('students.name', 'ASC')
                    ->findAll();
    }

    public function getByStudent($student_id)
    {
        return $this->where('student_id', $student_id)->first();
    }

    // Statistik untuk laporan
    public function getStatPostGraduatePlan()
    {
        return $this->select('post_graduate_plan, COUNT(id) as total')
                    ->groupBy('post_graduate_plan')
                    ->findAll();
    }
}
