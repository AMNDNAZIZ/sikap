<?php

namespace App\Models;

use CodeIgniter\Model;

class BkInstrumentQuestionModel extends Model
{
    protected $table            = 'bk_instrument_questions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'instrument_id',
        'domain_id',
        'scale_group',
        'question_text',
        'question_type',
        'is_required',
        'is_reverse',
        'weight',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
