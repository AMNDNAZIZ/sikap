<?php

namespace App\Models;

use CodeIgniter\Model;

class BkStudentResultModel extends Model
{
    protected $table            = 'bk_student_results';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'assignment_student_id',
        'instrument_id',
        'student_id',
        'total_score',
        'total_max_score',
        'percentage',
        'overall_level',
        'calculated_at',
        'created_at',
    ];
}
