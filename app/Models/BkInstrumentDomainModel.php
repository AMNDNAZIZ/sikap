<?php

namespace App\Models;

use CodeIgniter\Model;

class BkInstrumentDomainModel extends Model
{
    protected $table            = 'bk_instrument_domains';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'instrument_id',
        'domain_name',
        'sub_domain',
        'description',
        'sort_order',
        'is_active',
        'created_at',
    ];
}
