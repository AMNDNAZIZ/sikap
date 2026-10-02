<?php

namespace App\Models;

use CodeIgniter\Model;

class BkProgramModel extends Model
{
    protected $table            = 'bk_programs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'academic_year_id',
        'counselor_id',
        'title',
        'program_type',
        'period_month',
        'field',
        'target_class_id',
        'description',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
