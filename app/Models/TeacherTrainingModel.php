<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherTrainingModel extends Model
{
    protected $table = 'teacher_trainings';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['teacher_id', 'name', 'year', 'organizer', 'certificate_number'];

    public function getByTeacher($teacherId)
    {
        return $this->where('teacher_id', $teacherId)->orderBy('year', 'DESC')->findAll();
    }
}
