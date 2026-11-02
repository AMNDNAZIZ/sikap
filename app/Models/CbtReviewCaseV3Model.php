<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtReviewCaseV3Model
 *
 * ESPATA Assessment Intelligence V3 - Governance Review Cases.
 * Manages anomaly tickets for multi-session retakes, attendance discrepancies, and score anomalies.
 */
class CbtReviewCaseV3Model extends Model
{
    protected $table            = 'cbt_review_cases_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'case_code',
        'scope',
        'test_id',
        'student_id',
        'session_id',
        'anomaly_type',
        'severity',
        'status',
        'evidence_summary',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    public const ANOMALY_MULTIPLE_RETAKES       = 'MULTIPLE_RETAKES';
    public const ANOMALY_ABSENCE_DISCREPANCY    = 'ABSENCE_DISCREPANCY';
    public const ANOMALY_SCORE_CONTRADICTION    = 'SCORE_CONTRADICTION';
    public const ANOMALY_UNVERIFIED_LINEAGE     = 'UNVERIFIED_LINEAGE';

    public const STATUS_PENDING   = 'PENDING';
    public const STATUS_IN_REVIEW = 'IN_REVIEW';
    public const STATUS_RESOLVED  = 'RESOLVED';
    public const STATUS_REJECTED  = 'REJECTED';

    /**
     * Find case by unique case_code.
     */
    public function findByCode(string $caseCode): ?array
    {
        return $this->where('case_code', $caseCode)->first();
    }
}

