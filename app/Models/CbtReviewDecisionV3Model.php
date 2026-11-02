<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtReviewDecisionV3Model
 *
 * ESPATA Assessment Intelligence V3 - Ratified Review Decisions.
 * Records formal resolutions (RATIFY_SESSION, CONFIRM_ABSENCE, QUARANTINE_DATA, GRANT_RETEST).
 */
class CbtReviewDecisionV3Model extends Model
{
    protected $table            = 'cbt_review_decisions_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'case_id',
        'reviewer_id',
        'decision',
        'selected_session_id',
        'student_id',
        'test_id',
        'owner_consent_verified',
        'justification',
        'effective_score',
        'decided_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    public const DECISION_RATIFY_SESSION  = 'RATIFY_SESSION';
    public const DECISION_CONFIRM_ABSENCE = 'CONFIRM_ABSENCE';
    public const DECISION_QUARANTINE_DATA = 'QUARANTINE_DATA';
    public const DECISION_GRANT_RETEST    = 'GRANT_RETEST';

    /**
     * Get decision for a specific case.
     */
    public function getByCaseId(int $caseId): ?array
    {
        return $this->where('case_id', $caseId)->first();
    }
}

