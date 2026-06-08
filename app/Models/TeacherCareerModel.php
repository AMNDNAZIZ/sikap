<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherCareerModel extends Model
{
    protected $table = 'teacher_careers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['teacher_id', 'academic_year_id', 'sk_number', 'assignment_description'];

    public function getByTeacher($teacherId)
    {
        return $this->select('teacher_careers.*, academic_years.year as academic_year')
            ->join('academic_years', 'academic_years.id = teacher_careers.academic_year_id')
            ->where('teacher_id', $teacherId)
            ->orderBy('academic_years.start_date', 'DESC')
            ->findAll();
    }
}
