<?php namespace App\Models;

use CodeIgniter\Model;

class FinalExamScoresModel extends Model
{
    protected $table = 'final_exam_scores';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'subject_id', 'year_id',
        'score', 'created_at', 'updated_at'
    ];
}
