<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtAnalysisModel extends Model
{
    protected $table = 'cbt_analysis';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'test_id', 'question_id', 'total_answered',
        'total_correct', 'total_wrong', 'difficulty_index',
        'discrimination_index'
    ];
    protected $useTimestamps = true;
}
