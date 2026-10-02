<?php

namespace App\Models;

use CodeIgniter\Model;

class BkAssignmentModel extends Model
{
    protected $table            = 'bk_assignments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'instrument_id',
        'title',
        'target_type',
        'target_id',
        'academic_year_id',
        'start_date',
        'end_date',
        'status',
        'assigned_by',
        'notes',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
