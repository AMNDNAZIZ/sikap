<?php

namespace App\Models;

use CodeIgniter\Model;

class BkStudentAnswerModel extends Model
{
    protected $table            = 'bk_student_answers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'assignment_student_id',
        'question_id',
        'option_id',
        'text_answer',
        'selected_options',
        'raw_score',
        'final_score',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
