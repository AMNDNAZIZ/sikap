<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtMigrationManifestModel
 * Batch tracking and Merkle root audit manifest for non-destructive migration.
 */
class CbtMigrationManifestModel extends Model
{
    protected $table            = 'cbt_migration_manifest';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'batch_number',
        'start_legacy_id',
        'end_legacy_id',
        'row_count',
        'migrated_v3_count',
        'quarantined_count',
        'batch_merkle_root',
        'status',
        'executed_by',
        'execution_duration_ms',
        'committed_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'batch_number'      => 'required|is_natural_no_zero',
        'start_legacy_id'   => 'required|is_natural_no_zero',
        'end_legacy_id'     => 'required|is_natural_no_zero',
        'row_count'         => 'required|is_natural',
        'migrated_v3_count' => 'required|is_natural',
        'quarantined_count' => 'required|is_natural',
        'batch_merkle_root' => 'required|exact_length[64]',
        'status'            => 'required|in_list[IN_PROGRESS,VERIFIED,FAILED]',
        'executed_by'       => 'required|max_length[64]',
    ];
}

