<?php

namespace App\Models;

use CodeIgniter\Model;

class BkAssessmentModel extends Model
{
    protected $table            = 'bk_assessments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'title',
        'type',
        'target_grade',
        'academic_year_id',
        'description',
        'is_active',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
