<?php

namespace App\Models;

use CodeIgniter\Model;

class BkInstrumentScaleModel extends Model
{
    protected $table            = 'bk_instrument_scales';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'instrument_id',
        'scale_group',
        'scale_value',
        'scale_label',
        'sort_order',
        'created_at',
    ];
}
