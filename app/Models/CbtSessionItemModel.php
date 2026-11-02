<?php

namespace App\Models;

use CodeIgniter\Model;
use LogicException;

/**
 * CbtSessionItemModel
 * Allocates randomized questions to student test sessions.
 * Strictly immutable once assigned (including assigned_order).
 */
class CbtSessionItemModel extends Model
{
    protected $table            = 'cbt_session_items';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'session_id',
        'test_id',
        'test_item_id',
        'item_version_id',
        'assigned_order',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'session_id'      => 'required|is_natural_no_zero',
        'test_id'         => 'required|is_natural_no_zero',
        'test_item_id'    => 'required|is_natural_no_zero',
        'item_version_id' => 'required|is_natural_no_zero',
        'assigned_order'  => 'required|is_natural',
    ];

    protected $beforeUpdate = ['enforceAllocationImmutability'];

    /**
     * Session item allocations and orders cannot be reassigned once generated.
     */
    protected function enforceAllocationImmutability(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $immutableFields = ['session_id', 'test_id', 'test_item_id', 'item_version_id', 'assigned_order'];

        foreach ($immutableFields as $field) {
            if (array_key_exists($field, $data['data'])) {
                if (isset($data['id'])) {
                    $existing = $this->find($data['id']);
                    if ($existing && (string)$existing[$field] !== (string)$data['data'][$field]) {
                        throw new LogicException("CBT V3 Error: Session item field '{$field}' cannot be modified once allocated.");
                    }
                }
            }
        }

        return $data;
    }
}
