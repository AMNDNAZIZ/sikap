<?php

namespace App\Models;

use CodeIgniter\Model;

class BkInstrumentInterpretationModel extends Model
{
    protected $table            = 'bk_instrument_interpretations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'instrument_id',
        'domain_id',
        'min_percent',
        'max_percent',
        'level_label',
        'level_color',
        'description',
        'recommendation',
        'created_at',
    ];
}
