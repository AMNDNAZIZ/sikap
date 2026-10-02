<?php

namespace App\Models;

use CodeIgniter\Model;

class BkResultDetailModel extends Model
{
    protected $table            = 'bk_result_details';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'result_id',
        'domain_id',
        'domain_score',
        'domain_max_score',
        'percentage',
        'level_label',
        'created_at',
    ];
}
