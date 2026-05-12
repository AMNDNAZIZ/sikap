<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table = 'students';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'nisn',
        'nis',
        'name',
        'nik',
        'gender',
        'birth_place',
        'birth_date',
        'child_order',
        'religion',
        'nationality',
        'admission_date',
        'admission_class',
        'registration_type',
        'photo',
        'address',
        'residence_type',
        'transportation',
        'distance',
        'latitude',
        'longitude',
        'special_needs',
        'father_name',
        'father_nik',
        'father_birth_year',
        'father_education',
        'father_job',
        'father_income',
        'mother_name',
        'mother_nik',
        'mother_birth_year',
        'mother_education',
        'mother_job',
        'mother_income',
        'guardian_name',
        'guardian_education',
        'guardian_job',
        'guardian_income',
        'user_id',
        'class_id',
        'room',
        'plain_password'
    ];

    public function withUser()
    {
        return $this->select('students.*, users.username, users.email')
            ->join('users', 'users.id = students.user_id', 'left');
    }

    public function getStudentWithClass($id)
    {
        return $this->select('students.*, classes.name AS class_name, users.username, users.password as user_password')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('students.id', $id)
            ->orderBy('student_records.id', 'DESC')
            ->first();
    }

    public function getByClass($classId)
    {
        return $this->select('students.*, classes.name AS class_name, student_records.class_id, users.username, users.password as user_password')
            ->join('student_records', 'student_records.student_id = students.id')
            ->join('classes', 'classes.id = student_records.class_id')
            ->join('users', 'users.id = students.user_id', 'left')
            ->where('student_records.class_id', $classId)
            ->where('student_records.status', 'aktif')
            ->orderBy('students.name', 'ASC')
            ->findAll();
    }
}


