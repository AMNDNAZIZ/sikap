<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtTestItemModel
 * Container model for items belonging to a CBT test.
 */
class CbtTestItemModel extends Model
{
    protected $table            = 'cbt_test_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'test_id',
        'bank_question_id',
        'item_order',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'test_id'          => 'required|is_natural_no_zero',
        'bank_question_id' => 'required|is_natural_no_zero',
        'item_order'       => 'permit_empty|is_natural',
    ];
}

