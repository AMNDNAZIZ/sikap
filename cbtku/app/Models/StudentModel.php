<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['nis','username','name','gender','class_id','religion', 'user_id', 'plain_password', 'room'];

    public function withUser()
    {
        return $this->select('students.*, users.username')
                    ->join('users','users.id = students.user_id','left');
    }

    public function getStudentWithClass($id)
    {
        return $this->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.id', $id)
            ->first();
    }

    public function getByClass($classId)
    {
        return $this->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.class_id', $classId)
            ->orderBy('students.name', 'ASC')
            ->findAll();
    }

}


