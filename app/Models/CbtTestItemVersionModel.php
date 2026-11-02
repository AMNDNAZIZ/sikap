<?php

namespace App\Models;

use CodeIgniter\Model;
use LogicException;

/**
 * CbtTestItemVersionModel
 * Snapshot version model with software-level immutability protection.
 */
class CbtTestItemVersionModel extends Model
{
    protected $table            = 'cbt_test_item_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'test_item_id',
        'test_id',
        'version_number',
        'item_type',
        'max_score',
        'content_payload_json',
        'payload_sha256',
        'is_active',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'test_item_id'         => 'required|is_natural_no_zero',
        'test_id'              => 'required|is_natural_no_zero',
        'version_number'       => 'required|is_natural_no_zero',
        'item_type'            => 'required|in_list[pg,esai,pg_kompleks,benar_salah]',
        'max_score'            => 'required|decimal',
        'content_payload_json' => 'required',
        'payload_sha256'       => 'required|exact_length[64]',
        'is_active'            => 'permit_empty|in_list[0,1]',
    ];

    protected $beforeUpdate = ['enforceSnapshotImmutability'];

    /**
     * Enforce strict immutability: Content payload, test_id, scores, and identity cannot be modified.
     * Only `is_active` may be toggled.
     */
    protected function enforceSnapshotImmutability(array $data): array
    {
        if (!isset($data['data'])) {
            return $data;
        }

        $immutableFields = [
            'content_payload_json',
            'payload_sha256',
            'max_score',
            'item_type',
            'version_number',
            'test_item_id',
            'test_id',
        ];

        foreach ($immutableFields as $field) {
            if (array_key_exists($field, $data['data'])) {
                if (isset($data['id'])) {
                    $existing = $this->find($data['id']);
                    if ($existing && (string)$existing[$field] !== (string)$data['data'][$field]) {
                        throw new LogicException("CBT V3 Error: Test item version field '{$field}' is strictly immutable.");
                    }
                }
            }
        }

        return $data;
    }
}
