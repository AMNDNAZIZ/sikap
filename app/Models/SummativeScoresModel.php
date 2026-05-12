<?php namespace App\Models;

use CodeIgniter\Model;

class SummativeScoresModel extends Model
{
    protected $table = 'summative_scores';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'subject_id', 'year_id', 'semester',
        'type', 'score', 'created_at', 'updated_at'
    ];
}
