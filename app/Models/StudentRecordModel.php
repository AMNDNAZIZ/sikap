<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentRecordModel extends Model
{
    protected $table = 'student_records'; // ✅ sudah benar
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'student_id', 'class_id', 'academic_year_id', 'status', 'graduation_date', 'note'
    ];

    public function getWithRelations($yearId = null, $classId = null)
    {
        $builder = $this->select('student_records.*, students.nisn, students.nis, students.name as student_name, classes.name as class_name, academic_years.year as academic_year')
                        ->join('students', 'students.id = student_records.student_id')
                        ->join('classes', 'classes.id = student_records.class_id', 'left')
                        ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left');

        if ($yearId) {
            $builder->where('student_records.academic_year_id', $yearId);
        }

        if ($classId) {
            $builder->where('student_records.class_id', $classId);
        }

        return $builder->orderBy('students.name', 'ASC')->findAll();
    }

    public function getHistoryByStudent($studentId)
    {
        return $this->select('student_records.*, classes.name as class_name, academic_years.year as academic_year')
                    ->join('classes', 'classes.id = student_records.class_id', 'left')
                    ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
                    ->where('student_records.student_id', $studentId)
                    ->orderBy('academic_years.year', 'ASC')
                    ->findAll();
    }
}
