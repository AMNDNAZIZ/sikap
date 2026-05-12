<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectScoreModel extends Model
{
    protected $table      = 'subject_scores';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'subject_id', 'year_id', 'semester',
        'formatif_score', 'sumatif_score', 'final_exam_score',
        'report_score'
    ];
    protected $useTimestamps = true;
}
