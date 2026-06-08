<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectWeightModel extends Model
{
    protected $table         = 'subject_weights';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'subject_id', 'year_id', 'formative_weight', 'summative_weight', 'final_exam_weight'
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
