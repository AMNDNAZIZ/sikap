<?php

namespace App\Models;

use CodeIgniter\Model;

class BkInstrumentModel extends Model
{
    protected $table            = 'bk_instruments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'title',
        'description',
        'assessment_type',
        'academic_year_id',
        'semester',
        'target_level',
        'purpose',
        'default_scale_type',
        'status',
        'is_template',
        'source_instrument_id',
        'total_questions',
        'created_by',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
