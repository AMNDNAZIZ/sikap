<?php

namespace App\Models;

use CodeIgniter\Model;

class BkRplModel extends Model
{
    protected $table            = 'bk_rpl';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'program_id',
        'title',
        'service_type',
        'field',
        'target_class_id',
        'duration_minutes',
        'purpose',
        'media_tools',
        'methods',
        'evaluation_steps',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
