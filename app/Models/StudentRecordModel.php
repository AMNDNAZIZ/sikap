<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentRecordModel extends Model
{
    protected $table = 'student_records'; // ✅ sudah benar
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'student_id', 'class_id', 'academic_year_id', 'status','note'
    ];
}
