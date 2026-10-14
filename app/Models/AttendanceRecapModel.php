<?php

namespace App\Models;

use CodeIgniter\Model;

class AttendanceRecapModel extends Model
{
    protected $table            = 'attendance_recaps';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'class_id',
        'academic_year_id',
        'semester',
        'sick',
        'permitted',
        'absent',
        'source',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
