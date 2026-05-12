<?php

namespace App\Models;

use CodeIgniter\Model;

class TeachingAssignmentModel extends Model
{
    protected $table = 'teaching_assignments';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'teacher_id', 'class_id', 'subject_id', 'academic_year_id', 'role'
    ];
    protected $useTimestamps = true;

    public function getAllWithRelations()
    {
        return $this->select('teaching_assignments.*, teachers.name AS teacher, classes.name AS class, subjects.name AS subject, academic_years.year AS academic_year')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->join('academic_years', 'academic_years.id = teaching_assignments.academic_year_id')
            ->findAll();
    }

    public function getAllWithRelationsPaginated($perPage = 20)
    {
        return $this->select('teaching_assignments.*, teachers.name AS teacher, classes.name AS class, subjects.name AS subject, academic_years.year AS academic_year')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->join('academic_years', 'academic_years.id = teaching_assignments.academic_year_id')
            ->orderBy('teachers.name', 'ASC')
            ->paginate($perPage);
    }

    public function searchAssignments($keyword = null, $yearId = null, $perPage = 10)
    {
        $builder = $this->select('teaching_assignments.*, teachers.name AS teacher, classes.name AS class, subjects.name AS subject, academic_years.year AS academic_year')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->join('academic_years', 'academic_years.id = teaching_assignments.academic_year_id')
            ->orderBy('teachers.name', 'ASC');

        if ($keyword) {
            $builder->groupStart()
                ->like('teachers.name', $keyword)
                ->orLike('classes.name', $keyword)
                ->orLike('subjects.name', $keyword)
                ->groupEnd();
        }

        if ($yearId) {
            $builder->where('academic_years.id', $yearId);
        }

        return $builder->paginate($perPage);
    }

    public function getAssignmentsByTeacher($teacherId, $yearId = null)
    {
        $builder = $this->select('teaching_assignments.*, 
                                  teachers.name AS teacher, 
                                  classes.name AS class, 
                                  subjects.name AS subject, 
                                  academic_years.year AS academic_year')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->join('academic_years', 'academic_years.id = teaching_assignments.academic_year_id')
            ->where('teachers.id', $teacherId);

        // filter tahun ajaran aktif
        if ($yearId) {
            $builder->where('teaching_assignments.academic_year_id', $yearId);
        }

        return $builder->findAll();
    }


}

