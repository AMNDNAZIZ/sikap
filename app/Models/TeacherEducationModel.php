<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherEducationModel extends Model
{
    protected $table = 'teacher_educations';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['teacher_id', 'level', 'major', 'institution', 'graduation_year'];

    public function getByTeacher($teacherId)
    {
        return $this->where('teacher_id', $teacherId)->orderBy('graduation_year', 'DESC')->findAll();
    }
}
