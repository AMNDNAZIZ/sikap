<?php

namespace App\Models;

use CodeIgniter\Model;

class BkAssignmentStudentModel extends Model
{
    protected $table            = 'bk_assignment_students';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'assignment_id',
        'student_id',
        'status',
        'started_at',
        'completed_at',
        'last_saved_at',
        'created_at',
    ];
}
