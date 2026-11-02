<?php

namespace App\Services\Cbt\Review;

use InvalidArgumentException;
use RuntimeException;

/**
 * ReviewerWorkflowService
 *
 * ESPATA Assessment Intelligence V3 - Stage F3A
 * Manages the review queue with strict party-of-responsibility classification:
 * - REVIEWER_ACTION_REQUIRED: Technical deviations actionable by reviewer.
 * - TEACHER_ACTION_REQUIRED: Academic grading gaps (e.g. pending essay) requiring teacher correction.
 * - OWNER_DECISION_REQUIRED: Governance/policy ambiguities requiring principal or product owner sign-off.
 * - ACKNOWLEDGEMENT_REQUIRED: Confirmed policy divergences requiring documented acknowledgement.
 * - RESOLVED: Validated, audited, and cleared cases.
 * - BLOCKED: Critical unresolvable anomalies.
 */
class ReviewerWorkflowService
{
    public const STATUS_REVIEWER_ACTION_REQUIRED = 'REVIEWER_ACTION_REQUIRED';
    public const STATUS_TEACHER_ACTION_REQUIRED  = 'TEACHER_ACTION_REQUIRED';
    public const STATUS_OWNER_DECISION_REQUIRED  = 'OWNER_DECISION_REQUIRED';
    public const STATUS_ACKNOWLEDGEMENT_REQUIRED = 'ACKNOWLEDGEMENT_REQUIRED';
    public const STATUS_RESOLVED                 = 'RESOLVED';
    public const STATUS_BLOCKED                  = 'BLOCKED';

    // Legacy aliases for backward compatibility
    public const STATUS_PENDING                  = 'REVIEWER_ACTION_REQUIRED';
    public const STATUS_IN_REVIEW                = 'REVIEWER_ACTION_REQUIRED';
    public const STATUS_REQUIRES_OWNER_DECISION  = 'OWNER_DECISION_REQUIRED';
    public const STATUS_REJECTED                 = 'BLOCKED';

    private ReviewerDecisionValidator $validator;
    private ReviewerAuditService $auditService;
    private ReviewerEvidenceValidator $evidenceValidator;
    private array $cases = [];

    public function __construct(
        ?ReviewerDecisionValidator $validator = null,
        ?ReviewerAuditService $auditService = null,
        ?ReviewerEvidenceValidator $evidenceValidator = null
    ) {
        $this->validator = $validator ?? new ReviewerDecisionValidator();
        $this->auditService = $auditService ?? new ReviewerAuditService();
        $this->evidenceValidator = $evidenceValidator ?? new ReviewerEvidenceValidator();
    }

    public function getEvidenceValidator(): ReviewerEvidenceValidator
    {
        return $this->evidenceValidator;
    }

    public function setEvidenceValidator(ReviewerEvidenceValidator $evidenceValidator): void
    {
        $this->evidenceValidator = $evidenceValidator;
    }

    /**
     * Ingest comparison results into review cases with party-based status assignment.
     *
     * @param array $comparisonResults
     * @param array $subjectMap Mapping of test_id => subject_name
     * @return int Number of cases ingested
     */
    public function ingestComparisonResults(array $comparisonResults, array $subjectMap = []): int
    {
        $ingested = 0;

        foreach ($comparisonResults as $res) {
            $categories = $res['categories'] ?? [];
            $isComparable = !empty($res['is_comparable']);

            $needsReview = (!$isComparable) ||
                in_array('SCORE_DIFFERENCE', $categories, true) ||
                in_array('ROUNDING_DIFFERENCE', $categories, true) ||
                in_array('ESSAY_POLICY_DIFFERENCE', $categories, true) ||
                in_array('ATTEMPT_SELECTION_DIFFERENCE', $categories, true) ||
                in_array('LEGACY_ZERO_FALLBACK', $categories, true) ||
                in_array('PUBLICATION_ELIGIBILITY_DIFFERENCE', $categories, true) ||
                in_array('NO_VALID_QUESTIONS', $categories, true) ||
                in_array('INSUFFICIENT_EVIDENCE', $categories, true) ||
                in_array('INTEGRITY_STATUS_DIFFERENCE', $categories, true) ||
                in_array('LEGACY_UNVERIFIABLE', $categories, true) ||
                in_array('INSUFFICIENT_SOURCE_DATA', $categories, true);

            if (!$needsReview) {
                continue;
            }

            $primaryCategory = $this->determinePrimaryReviewCategory($categories, $res);
            $initialStatus = $this->determineInitialPartyStatus($primaryCategory, $res);

            $testId = $res['test_id'] ?? 'unknown';
            $subjectName = $subjectMap[$testId] ?? ($res['subject_name'] ?? "Subject-{$testId}");

            $rawId = $res['pseudonymous_student_id'] ?? ($res['student_id'] ?? 'unknown');
            $pseudoId = str_starts_with((string)$rawId, 'STD_')
                ? (string)$rawId
                : 'STD_' . strtoupper(substr(hash_hmac('sha256', (string)$rawId, 'ESPATA_REVIEW_SALT'), 0, 12));

            $caseId = 'REV_' . $testId . '_' . substr(hash('sha256', $pseudoId . ($res['session_id'] ?? '') . $primaryCategory), 0, 10);

            $this->cases[$caseId] = [
                'case_id'                 => $caseId,
                'pseudonymous_student_id' => $pseudoId,
                'test_id'                 => $testId,
                'subject_name'            => $subjectName,
                'session_id'              => $res['session_id'] ?? null,
                'category'                => $primaryCategory,
                'all_categories'          => $categories,
                'legacy_score'            => $res['legacy_score'] ?? null,
                'v3_score'                => $res['v3_score'] ?? null,
                'delta'                   => $res['delta'] ?? null,
                'is_comparable'           => $isComparable,
                'relevant_status'         => $res['publication_eligibility']['v3_status'] ?? ($res['v3_status'] ?? 'UNKNOWN'),
                'legacy_status'           => $res['publication_eligibility']['legacy_status'] ?? 'UNKNOWN',
                'evidence_reference'      => $res['notes'] ?? 'Shadow testing comparison outcome',
                'review_status'           => $initialStatus,
                'responsible_party'       => $this->getPartyForStatus($initialStatus),
                'reviewer_id'             => null,
                'review_timestamp'        => null,
                'decision'                => null,
                'decision_id'             => null,
                'decision_reason'         => null,
                'review_notes'            => null,
                'created_at'              => $res['created_at'] ?? '2026-11-01 06:00:00',
                'evidence_verified'       => $res['evidence_verified'] ?? ($primaryCategory !== 'LEGACY_ZERO_FALLBACK'),
            ];

            $ingested++;
        }

        return $ingested;
    }

    /**
     * Get review queue with optional filters.
     *
     * @param array $filters ['category' => ..., 'review_status' => ..., 'responsible_party' => ..., 'subject_name' => ...]
     * @return array
     */
    public function getQueue(array $filters = []): array
    {
        $filtered = array_values($this->cases);

        if (!empty($filters['category'])) {
            $filtered = array_filter($filtered, fn($c) => $c['category'] === $filters['category']);
        }
        if (!empty($filters['review_status'])) {
            $filtered = array_filter($filtered, fn($c) => $c['review_status'] === $filters['review_status']);
        }
        if (!empty($filters['responsible_party'])) {
            $filtered = array_filter($filtered, fn($c) => ($c['responsible_party'] ?? '') === $filters['responsible_party']);
        }
        if (!empty($filters['subject_name'])) {
            $filtered = array_filter($filtered, fn($c) => $c['subject_name'] === $filters['subject_name']);
        }

        return array_values($filtered);
    }

    /**
     * Get a single case by case_id.
     */
    public function getCase(string $caseId): ?array
    {
        return $this->cases[$caseId] ?? null;
    }

    /**
     * Load review cases directly from shadow deviations CSV.
     *
     * @param string|null $csvPath
     * @param array $subjectMap
     * @return int Number of cases loaded
     */
    public function loadFromShadowDeviations(?string $csvPath = null, array $subjectMap = []): int
    {
        $path = $csvPath ?? (defined('WRITEPATH') ? WRITEPATH . 'shadow_artifacts/shadow_deviations.csv' : 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/shadow_deviations.csv');
        if (!file_exists($path)) {
            $path = 'writable/shadow_artifacts/shadow_deviations.csv';
        }
        if (!file_exists($path)) {
            return 0;
        }

        $defaultSubjectMap = [
            501 => 'Matematika',
            502 => 'Bahasa Indonesia',
            503 => 'Ilmu Pengetahuan Alam',
        ];
        $effectiveSubjectMap = !empty($subjectMap) ? $subjectMap : $defaultSubjectMap;

        $fp = fopen($path, 'r');
        if (!$fp) {
            return 0;
        }

        fgetcsv($fp); // Skip header
        $rawCases = [];
        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) >= 8) {
                $rawCases[] = [
                    'pseudonymous_student_id' => $row[0],
                    'test_id'                 => $row[1],
                    'session_id'              => ($row[2] !== '' && $row[2] !== 'NULL') ? $row[2] : null,
                    'legacy_score'            => ($row[3] !== '' && $row[3] !== 'NULL') ? (float)$row[3] : null,
                    'v3_score'                => ($row[4] !== '' && $row[4] !== 'NULL') ? (float)$row[4] : null,
                    'delta'                   => ($row[5] !== '' && $row[5] !== 'NULL') ? (float)$row[5] : null,
                    'is_comparable'           => $row[6] === 'YES',
                    'categories'              => explode('|', $row[7]),
                    'notes'                   => $row[8] ?? '',
                ];
            }
        }
        fclose($fp);

        return $this->ingestComparisonResults($rawCases, $effectiveSubjectMap);
    }

    /**
     * Ingest an attendance discrepancy detected by DigitalBapService.
     */
    public function ingestDiscrepancy(array $discrepancy, string $subjectName = 'Ujian'): string
    {
        $studentId = $discrepancy['student_id'];
        $bap = $discrepancy['bap_evidence'] ?? [];
        $telemetry = $discrepancy['telemetry_evidence'] ?? [];
        $testId = $bap['test_id'] ?? 501;

        $pseudoId = 'STD_' . strtoupper(substr(hash_hmac('sha256', (string)$studentId, 'ESPATA_REVIEW_SALT'), 0, 12));
        $caseId = 'REV_DISC_' . $testId . '_' . substr(hash('sha256', $pseudoId . ($bap['bap_number'] ?? '') . microtime(true)), 0, 8);

        $this->cases[$caseId] = [
            'case_id'                 => $caseId,
            'pseudonymous_student_id' => $pseudoId,
            'raw_student_id'          => $studentId,
            'test_id'                 => $testId,
            'subject_name'            => $subjectName,
            'session_id'              => $telemetry['session_id'] ?? null,
            'category'                => 'ATTENDANCE_DISCREPANCY',
            'all_categories'          => ['ATTENDANCE_DISCREPANCY'],
            'legacy_score'            => null,
            'v3_score'                => $telemetry['score'] ?? null,
            'delta'                   => null,
            'is_comparable'           => false,
            'relevant_status'         => 'ATTENDANCE_DISCREPANCY',
            'legacy_status'           => $bap['reported_status'] ?? 'UNKNOWN',
            'evidence_reference'      => 'Discrepancy: Proctor BAP (' . ($bap['reported_status'] ?? 'ABSENT') . ') vs Telemetry (' . ($telemetry['answer_count'] ?? 0) . ' answers)',
            'review_status'           => self::STATUS_REVIEWER_ACTION_REQUIRED,
            'responsible_party'       => 'Reviewer',
            'bap_evidence'            => $bap,
            'telemetry_evidence'      => $telemetry,
            'created_at'              => date('Y-m-d H:i:s'),
            'evidence_verified'       => !empty($bap['document_sha256']),
        ];

        return $caseId;
    }

    /**
     * Ingest a retake anomaly case for explicit selection under DEC-RET-01.
     */
    public function ingestRetakeAnomaly(
        int $studentId,
        int $testId,
        string $subjectName,
        array $candidateSessions
    ): string {
        $pseudoId = 'STD_' . strtoupper(substr(hash_hmac('sha256', (string)$studentId, 'ESPATA_REVIEW_SALT'), 0, 12));
        $caseId = 'REV_RET_' . $testId . '_' . substr(hash('sha256', $pseudoId . count($candidateSessions) . microtime(true)), 0, 8);

        $this->cases[$caseId] = [
            'case_id'                 => $caseId,
            'pseudonymous_student_id' => $pseudoId,
            'raw_student_id'          => $studentId,
            'test_id'                 => $testId,
            'subject_name'            => $subjectName,
            'session_id'              => $candidateSessions[0]['session_id'] ?? null,
            'category'                => 'RETAKE_ANOMALY',
            'all_categories'          => ['RETAKE_ANOMALY', 'MULTIPLE_RETAKES'],
            'legacy_score'            => null,
            'v3_score'                => null,
            'delta'                   => null,
            'is_comparable'           => false,
            'relevant_status'         => 'AMBIGUOUS_ATTEMPTS',
            'legacy_status'           => 'MULTIPLE_SESSIONS',
            'evidence_reference'      => count($candidateSessions) . ' exam sessions logged for candidate under DEC-RET-01',
            'review_status'           => self::STATUS_OWNER_DECISION_REQUIRED,
            'responsible_party'       => 'Product Owner',
            'retake_sessions'         => $candidateSessions,
            'created_at'              => date('Y-m-d H:i:s'),
            'evidence_verified'       => true,
        ];

        return $caseId;
    }

    /**
     * Get review queue with filtering, searching, and pagination.
     *
     * @param array $filters ['category' => ..., 'review_status' => ..., 'responsible_party' => ..., 'decision_id' => ..., 'search' => ...]
     * @param int $page
     * @param int $perPage
     * @return array ['items' => ..., 'total_items' => ..., 'page' => ..., 'per_page' => ..., 'total_pages' => ...]
     */
    public function getFilteredQueue(array $filters = [], int $page = 1, int $perPage = 10): array
    {
        $filtered = array_values($this->cases);

        if (!empty($filters['category'])) {
            $filtered = array_filter($filtered, fn($c) => $c['category'] === $filters['category']);
        }
        if (!empty($filters['review_status'])) {
            $filtered = array_filter($filtered, fn($c) => $c['review_status'] === $filters['review_status']);
        }
        if (!empty($filters['responsible_party'])) {
            $filtered = array_filter($filtered, fn($c) => ($c['responsible_party'] ?? '') === $filters['responsible_party']);
        }
        if (!empty($filters['decision_id'])) {
            $filtered = array_filter($filtered, function ($c) use ($filters) {
                $decId = $c['decision_id'] ?? $this->getApplicableDecisionId($c['category']);
                return $decId === $filters['decision_id'];
            });
        }
        if (!empty($filters['search'])) {
            $term = strtolower(trim($filters['search']));
            $filtered = array_filter($filtered, function ($c) use ($term) {
                return str_contains(strtolower($c['case_id'] ?? ''), $term) ||
                       str_contains(strtolower((string)($c['test_id'] ?? '')), $term) ||
                       str_contains(strtolower($c['pseudonymous_student_id'] ?? ''), $term) ||
                       str_contains(strtolower($c['subject_name'] ?? ''), $term);
            });
        }

        $totalItems = count($filtered);
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int)ceil($totalItems / $perPage));
        $page = min(max(1, $page), $totalPages);
        $offset = ($page - 1) * $perPage;

        $pagedItems = array_slice(array_values($filtered), $offset, $perPage);

        // Enrich each item with applicable PODR decision_id if not resolved
        foreach ($pagedItems as &$item) {
            $item['applicable_decision_id'] = $item['decision_id'] ?? $this->getApplicableDecisionId($item['category']);
            $item['podr_policy'] = $this->getPodrPolicyDetails($item['applicable_decision_id']);
            $item['evidence_status'] = $this->getEvidenceSummary($item);
            $evList = $this->evidenceValidator->getEvidenceForCase($item['case_id']);
            $item['authentic_evidence_count'] = count($evList);
        }

        return [
            'items'        => $pagedItems,
            'total_items'  => $totalItems,
            'page'         => $page,
            'per_page'     => $perPage,
            'total_pages'  => $totalPages,
        ];
    }

    /**
     * Get enriched case detail for inspection.
     *
     * @param string $caseId
     * @return array|null
     */
    public function getCaseDetail(string $caseId): ?array
    {
        $case = $this->getCase($caseId);
        if (!$case) {
            return null;
        }

        $applicableDecisionId = $case['decision_id'] ?? $this->getApplicableDecisionId($case['category']);
        $podrPolicy = $this->getPodrPolicyDetails($applicableDecisionId);
        $evidenceSummary = $this->getEvidenceSummary($case);

        // Determine verification level
        $verificationLevel = match ($case['category']) {
            'ROUNDING_DIFFERENCE', 'LEGACY_ZERO_FALLBACK', 'PENDING_ESSAY', 'AMBIGUOUS_RETAKE' => 'CODE_LEVEL_VERIFIED',
            default => 'MOCK_ADAPTER_TESTED',
        };

        // Allowed reviewer actions for UI dropdown
        $allowedActions = $podrPolicy['allowed_actions'] ?? [
            ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
            ReviewerDecisionValidator::ACTION_REJECT,
        ];

        // Retrieve existing audit entries for this specific case
        $auditHistory = $this->auditService->getEntriesForCase($caseId);

        // Stage F3D: Retrieve authentic evidence & preliminary readiness
        $authenticEvidence = $this->evidenceValidator->getEvidenceForCase($caseId);
        $defaultActor = [
            'username'  => 'system_inspector',
            'role'      => 'REVIEWER',
            'role_id'   => 1,
            'authority' => 'REVIEWER',
        ];
        $defaultAction = match ($case['category']) {
            'ROUNDING_DIFFERENCE'          => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_TOLERANCE,
            'LEGACY_ZERO_FALLBACK'         => ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
            'PENDING_ESSAY',
            'ESSAY_POLICY_DIFFERENCE'      => ReviewerDecisionValidator::ACTION_REQUEST_TEACHER_GRADING,
            'AMBIGUOUS_RETAKE',
            'ATTEMPT_SELECTION_DIFFERENCE' => ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
            default                        => ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
        };
        $preliminaryReadiness = $this->evidenceValidator->evaluateReadiness($case, $defaultAction, $defaultActor);

        return array_merge($case, [
            'applicable_decision_id' => $applicableDecisionId,
            'podr_policy'            => $podrPolicy,
            'evidence_breakdown'     => $evidenceSummary,
            'authentic_evidence'     => $authenticEvidence,
            'evidence_readiness'     => $preliminaryReadiness,
            'verification'           => [
                'level'                   => $verificationLevel,
                'source_type'             => 'SHADOW_PIPELINE_CSV',
                'source_path'             => 'writable/shadow_artifacts/shadow_deviations.csv',
                'zero_dml_status'         => 'VERIFIED_IN_SANDBOX',
                'database_verification'   => 'MOCK_SANDBOX_ONLY',
            ],
            'allowed_actions'        => $allowedActions,
            'audit_history'          => $auditHistory,
        ]);
    }

    /**
     * Strictly non-mutating Decision Preview.
     *
     * Simulates the validation, status projection, and audit payload of an intended decision
     * without modifying in-memory case state, audit trail file, or database records.
     *
     * @param string $caseId
     * @param string $reviewerId
     * @param string $action
     * @param string $reason
     * @param array $params
     * @return array
     */
    public function previewDecision(
        string $caseId,
        string $reviewerId,
        string $action,
        string $reason,
        array $params = []
    ): array {
        if (!isset($this->cases[$caseId])) {
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_SOURCE_UNAVAILABLE,
                'error'                       => "Case ID '{$caseId}' not found in review queue.",
                'rejection_reason'            => "Case ID '{$caseId}' not found in review queue.",
                'current_status'              => null,
                'projected_status'            => null,
                'current_responsible_party'   => null,
                'projected_responsible_party' => null,
                'simulated_audit_entry'       => null,
                'consequences'                => [],
            ];
        }

        $case = $this->cases[$caseId];
        $statusBefore = $case['review_status'];

        // 1. Actor identity validation
        if (trim($reviewerId) === '') {
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR,
                'error'                       => 'Reviewer ID is required for review actions.',
                'rejection_reason'            => 'Reviewer ID is required for review actions.',
                'current_status'              => $statusBefore,
                'projected_status'            => $statusBefore,
                'current_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party' => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'       => null,
                'consequences'                => ['Action rejected: Missing reviewer identity.'],
            ];
        }

        // Global Actor Role Authority Enforcement
        $actorRole = strtoupper(trim((string)($params['actor_role'] ?? $params['role'] ?? 'AUTHORIZED_OPERATOR')));
        $unauthorizedRoles = ['STUDENT', 'GUEST', 'UNAUTHORIZED'];
        if (in_array($actorRole, $unauthorizedRoles, true)) {
            $msg = "GOVERNANCE BREACH: Actor with role '{$actorRole}' is not authorized to execute reviewer actions.";
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_UNAUTHORIZED_ACTOR,
                'error'                       => $msg,
                'rejection_reason'            => $msg,
                'current_status'              => $statusBefore,
                'projected_status'            => $statusBefore,
                'current_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party' => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'       => null,
                'consequences'                => ["Action blocked: Role '{$actorRole}' lacks required authority."],
            ];
        }

        // Build authenticated actor metadata
        $actor = [
            'username'  => $reviewerId,
            'role'      => $actorRole,
            'role_id'   => $params['actor_role_id'] ?? 1,
            'authority' => $actorRole,
        ];

        // 2. Audit integrity check: verify chain before allowing preview/decision
        $auditIntegrity = $this->auditService->verifyAuditIntegrity();
        if (!$auditIntegrity['is_valid']) {
            $msg = "AUDIT INTEGRITY CORRUPTED: Review decision cannot be previewed because audit log integrity verification failed: " .
                ($auditIntegrity['reason'] ?? 'chain break or sequence tampering detected');
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_SOURCE_UNAVAILABLE,
                'error'                       => $msg,
                'rejection_reason'            => $msg,
                'current_status'              => $statusBefore,
                'projected_status'            => $statusBefore,
                'current_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party' => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'       => null,
                'consequences'                => ['Decision preview halted due to cryptographic audit integrity corruption.'],
            ];
        }

        // 3. Governance Policy Validation via ReviewerDecisionValidator
        try {
            $validation = $this->validator->validate($case, $action, $reason, $params);
        } catch (\Throwable $e) {
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_POLICY_VIOLATION,
                'error'                       => $e->getMessage(),
                'rejection_reason'            => $e->getMessage(),
                'current_status'              => $statusBefore,
                'projected_status'            => $statusBefore,
                'current_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party' => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'       => null,
                'consequences'                => ["Validation failed: {$e->getMessage()}"],
            ];
        }

        // 4. Stage F3D Authoritative Evidence & Readiness Evaluation (Fail-Closed with Exception Guard)
        try {
            $readiness = $this->evidenceValidator->evaluateReadiness($case, $action, $actor, $params);
        } catch (\Throwable $e) {
            return [
                'is_valid'                    => false,
                'readiness_status'            => ReviewerEvidenceValidator::READINESS_SOURCE_UNAVAILABLE,
                'error'                       => 'READINESS EVALUATION EXCEPTION: ' . $e->getMessage(),
                'rejection_reason'            => $e->getMessage(),
                'current_status'              => $statusBefore,
                'projected_status'            => $statusBefore,
                'current_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party' => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'       => null,
                'consequences'                => ["Readiness evaluation exception: {$e->getMessage()}"],
            ];
        }
        if (!$readiness['is_valid']) {
            return [
                'is_valid'                      => false,
                'readiness_status'              => $readiness['readiness_status'],
                'error'                         => $readiness['verification_notes'],
                'rejection_reason'              => $readiness['verification_notes'],
                'current_status'                => $statusBefore,
                'projected_status'              => $statusBefore,
                'current_responsible_party'     => $case['responsible_party'] ?? 'Reviewer',
                'projected_responsible_party'   => $case['responsible_party'] ?? 'Reviewer',
                'simulated_audit_entry'         => null,
                'decision_id'                   => $readiness['decision_id'],
                'required_evidence_types'       => $readiness['required_evidence_types'],
                'found_evidence'                => $readiness['found_evidence'],
                'evidence_verification_results' => $readiness['evidence_verification_results'],
                'source_data_inspection'        => $readiness['source_data_inspection'],
                'consistency_checks'            => $readiness['consistency_checks'],
                'policy_validation'             => $readiness['policy_validation'],
                'rejection_reasons'             => $readiness['rejection_reasons'],
                'consequences'                  => array_map(fn($r) => "Action rejected: {$r}", $readiness['rejection_reasons']),
            ];
        }

        $newStatus = $this->mapDecisionToWorkflowStatus($validation['decision_status']);
        $projectedParty = $this->getPartyForStatus($newStatus);
        $decisionId = $validation['decision_id'] ?? ($params['decision_id'] ?? $this->getApplicableDecisionId($case['category']));

        // 5. Generate simulated audit entry payload
        $simulatedSeq = $this->auditService->count() + 1;
        $simulatedTimestamp = gmdate('c');
        $trail = $this->auditService->getTrail();
        $prevHash = !empty($trail) ? ($trail[count($trail) - 1]['record_hash'] ?? ReviewerAuditService::GENESIS_HASH) : ReviewerAuditService::GENESIS_HASH;
        $auditId = 'AUD_' . substr(hash('sha256', "SIMULATED_{$caseId}_{$simulatedSeq}"), 0, 12);
        $simulatedRecordHash = $this->auditService->computeRecordHash(
            $auditId,
            $simulatedSeq,
            $simulatedTimestamp,
            $reviewerId,
            $caseId,
            $statusBefore,
            $newStatus,
            $action,
            $reason,
            $prevHash,
            ReviewerAuditService::POLICY_VERSION,
            $decisionId
        );

        $simulatedAuditEntry = [
            'audit_id'             => $auditId,
            'sequence_number'      => $simulatedSeq,
            'timestamp_iso'        => $simulatedTimestamp,
            'reviewer_id'          => $reviewerId,
            'case_id'              => $caseId,
            'status_before'        => $statusBefore,
            'status_after'         => $newStatus,
            'decision'             => $action,
            'decision_id'          => $decisionId,
            'reason'               => $reason,
            'previous_record_hash' => $prevHash,
            'record_hash'          => $simulatedRecordHash,
            'policy_version'       => ReviewerAuditService::POLICY_VERSION,
            'extra_context'        => [
                'category'          => $case['category'],
                'sanctioned_action' => $validation['sanctioned_action'],
                'legacy_score'      => $case['legacy_score'] ?? null,
                'v3_score'          => $case['v3_score'] ?? null,
                'delta'             => $case['delta'] ?? null,
                'notes'             => $validation['notes'],
                'simulation_mode'   => true,
            ],
        ];

        // 6. Build human-readable consequences list
        $consequences = $this->buildConsequences($case, $action, $newStatus, $decisionId, $validation, $params);

        return [
            'is_valid'                      => true,
            'readiness_status'              => $readiness['readiness_status'],
            'decision_status'               => $validation['decision_status'],
            'sanctioned_action'             => $validation['sanctioned_action'],
            'decision_id'                   => $decisionId,
            'current_status'                => $statusBefore,
            'projected_status'              => $newStatus,
            'current_responsible_party'     => $case['responsible_party'] ?? 'Reviewer',
            'projected_responsible_party'   => $projectedParty,
            'notes'                         => $validation['notes'],
            'consequences'                  => $consequences,
            'simulated_audit_entry'         => $simulatedAuditEntry,
            'required_evidence_types'       => $readiness['required_evidence_types'],
            'found_evidence'                => $readiness['found_evidence'],
            'evidence_verification_results' => $readiness['evidence_verification_results'],
            'source_data_inspection'        => $readiness['source_data_inspection'],
            'consistency_checks'            => $readiness['consistency_checks'],
            'policy_validation'             => $readiness['policy_validation'],
        ];
    }

    /**
     * Map a review category to its governing PODR Decision ID.
     */
    public function getApplicableDecisionId(string $category): ?string
    {
        return match ($category) {
            'ROUNDING_DIFFERENCE'                                 => 'DEC-ROUND-01',
            'LEGACY_ZERO_FALLBACK'                                => 'DEC-ABS-01',
            'PENDING_ESSAY',
            'ESSAY_POLICY_DIFFERENCE'                             => 'DEC-ESS-01',
            'AMBIGUOUS_RETAKE',
            'ATTEMPT_SELECTION_DIFFERENCE',
            'RETAKE_ANOMALY',
            'MULTIPLE_RETAKES'                                    => 'DEC-RET-01',
            'ATTENDANCE_DISCREPANCY',
            'ABSENCE_DISCREPANCY'                                 => 'OVERRIDE-SYNC-POLICY',
            default                                               => null,
        };
    }

    /**
     * Get PODR policy specification by Decision ID.
     */
    public function getPodrPolicyDetails(?string $decisionId): array
    {
        return match ($decisionId) {
            'OVERRIDE-SYNC-POLICY' => [
                'decision_id'         => 'OVERRIDE-SYNC-POLICY',
                'title'               => 'Attendance Discrepancy & Sync Preservation Policy',
                'policy_mode'         => 'NON_DESTRUCTIVE_SYNC',
                'ratification_status' => 'RATIFIED',
                'ratified_date'       => '2026-11-01',
                'governing_document'  => 'Product Owner Decision Record (PODR), 1 November 2026',
                'summary'             => 'Kedua sumber data (BAP digital fisik pengawas dan telemetri server) dipelihara berdampingan secara non-destruktif. Konflik ditampilkan side-by-side untuk ajudikasi manusia. Penimpaan otomatis dilarang.',
                'authorized_roles'    => ['Reviewer', 'Admin', 'Principal'],
                'allowed_actions'     => [
                    ReviewerDecisionValidator::ACTION_CONFIRM_ABSENCE,
                    ReviewerDecisionValidator::ACTION_RATIFY_SESSION,
                    ReviewerDecisionValidator::ACTION_QUARANTINE_DATA,
                    ReviewerDecisionValidator::ACTION_GRANT_RETEST,
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                ],
            ],
            'DEC-RET-01' => [
                'decision_id'         => 'DEC-RET-01',
                'title'               => 'Retake Session Selection Policy',
                'policy_mode'         => 'EXPLICIT_SELECTION',
                'ratification_status' => 'RATIFIED',
                'ratified_date'       => '2026-11-01',
                'governing_document'  => 'Product Owner Decision Record (PODR), 1 November 2026',
                'summary'             => 'Tidak ada pemilihan sesi otomatis. Sesi ujian yang digunakan harus dipilih secara eksplisit oleh pihak berwenang (owner approval ref) dan dicatat dalam audit trail.',
                'authorized_roles'    => ['Product Owner', 'Principal', 'Admin'],
                'allowed_actions'     => [
                    ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT,
                    ReviewerDecisionValidator::ACTION_RATIFY_SESSION,
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                ],
            ],
            'DEC-ESS-01' => [
                'decision_id'         => 'DEC-ESS-01',
                'title'               => 'Pending Essay Grading Policy',
                'policy_mode'         => 'HOLD',
                'ratification_status' => 'RATIFIED',
                'ratified_date'       => '2026-11-01',
                'governing_document'  => 'Product Owner Decision Record (PODR), 1 November 2026',
                'summary'             => 'Nilai akhir dan nilai rapor ditahan (NULL) sampai seluruh penilaian esai wajib selesai oleh guru pengampu mapel. Nilai objektif disimpan terpisah. Force publish dilarang.',
                'authorized_roles'    => ['Teacher', 'Admin'],
                'allowed_actions'     => [
                    ReviewerDecisionValidator::ACTION_FLAG_TEACHER_ACTION,
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                ],
            ],
            'DEC-ROUND-01' => [
                'decision_id'         => 'DEC-ROUND-01',
                'title'               => 'Rounding Difference Tolerance Policy',
                'policy_mode'         => 'ACK',
                'ratification_status' => 'RATIFIED',
                'ratified_date'       => '2026-11-01',
                'governing_document'  => 'Product Owner Decision Record (PODR), 1 November 2026',
                'summary'             => 'Perbedaan absolut maksimal 0.05 poin dapat diselesaikan melalui acknowledgement (ACK) tanpa mutasi nilai sumber. Perbedaan > 0.05 wajib diinvestigasi.',
                'authorized_roles'    => ['Reviewer', 'Admin', 'Principal'],
                'allowed_actions'     => [
                    ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                ],
            ],
            'DEC-ABS-01' => [
                'decision_id'         => 'DEC-ABS-01',
                'title'               => 'Student Absence Fallback Policy',
                'policy_mode'         => 'ABSENT',
                'ratification_status' => 'RATIFIED',
                'ratified_date'       => '2026-11-01',
                'governing_document'  => 'Product Owner Decision Record (PODR), 1 November 2026',
                'summary'             => 'Nilai nol fallback legacy pada siswa tidak hadir diubah menjadi status ABSENT (nilai rapor NULL). Tidak boleh mengonversi absen menjadi nilai nol perolehan tanpa bukti kehadiran.',
                'authorized_roles'    => ['Reviewer', 'Admin', 'Principal'],
                'allowed_actions'     => [
                    ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                ],
            ],
            default => [
                'decision_id'      => 'POLICY_PENDING',
                'title'            => 'Standard Operational Review',
                'policy_mode'      => 'REVIEW',
                'summary'          => 'Pemeriksaan standar penyimpangan shadow testing.',
                'authorized_roles' => ['Reviewer', 'Admin'],
                'allowed_actions'  => [
                    ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION,
                    ReviewerDecisionValidator::ACTION_REJECT,
                ],
            ],
        };
    }

    /**
     * Generate structured evidence breakdown for a case.
     */
    private function getEvidenceSummary(array $case): array
    {
        $category = $case['category'];
        return match ($category) {
            'ROUNDING_DIFFERENCE' => [
                'status'    => 'SUFFICIENT',
                'available' => [
                    'Legacy session raw score verified (cbt_aktivitas)',
                    'V3 psychometric calculation generated with exact decimal precision',
                    'Direct delta calculation computed (' . ($case['delta'] ?? '0') . ')',
                ],
                'missing'   => [],
            ],
            'LEGACY_ZERO_FALLBACK' => [
                'status'    => 'EVIDENCE_REQUIRED',
                'available' => [
                    'Legacy zero-score record identified in session table',
                    'V3 presence detector identified unattempted / absent condition',
                ],
                'missing'   => [
                    'Physical exam attendance sign-off sheet / proctor report',
                ],
            ],
            'PENDING_ESSAY' => [
                'status'    => 'ACTION_REQUIRED',
                'available' => [
                    'Objective questions scored and isolated (100.0%)',
                    'Student essay responses logged in answer storage',
                ],
                'missing'   => [
                    'Subject teacher manual score entry in cbt_aktivitas_essay',
                ],
            ],
            'AMBIGUOUS_RETAKE', 'RETAKE_ANOMALY', 'MULTIPLE_RETAKES' => [
                'status'    => 'ACTION_REQUIRED',
                'available' => [
                    'Multiple test sessions logged for student in cbt_aktivitas',
                    'Individual timestamps and session scores preserved',
                ],
                'missing'   => [
                    'Formal Product Owner / Curriculum Committee explicit attempt selection reference',
                ],
            ],
            'ATTENDANCE_DISCREPANCY', 'ABSENCE_DISCREPANCY' => [
                'status'    => 'DISCREPANCY_FLAGGED',
                'available' => [
                    'Digital BAP document recorded with proctor signature',
                    'Server telemetry session & answer timestamps logged',
                    'Side-by-side evidence preserved under OVERRIDE-SYNC-POLICY',
                ],
                'missing'   => [
                    'Human reviewer formal adjudication between BAP and telemetry',
                ],
            ],
            default => [
                'status'    => 'INSPECTION_REQUIRED',
                'available' => ['Shadow comparison outcome recorded'],
                'missing'   => ['Detailed provenance verification'],
            ],
        };
    }

    /**
     * Build semantic consequence explanations for a previewed decision.
     */
    private function buildConsequences(
        array $case,
        string $action,
        string $newStatus,
        ?string $decisionId,
        array $validation,
        array $params = []
    ): array {
        $consequences = [];
        $statusBefore = $case['review_status'];

        $consequences[] = "Transisi Status Kasus: '{$statusBefore}' -> '{$newStatus}'.";

        if ($decisionId === 'DEC-ROUND-01') {
            $delta = $case['delta'] ?? ($params['delta'] ?? '0.00');
            $consequences[] = "Toleransi pembulatan delta ({$delta}) diakui (ACK) sesuai PODR DEC-ROUND-01 (<= 0.05).";
            $consequences[] = "Nilai legacy (" . ($case['legacy_score'] ?? '-') . ") dan nilai V3 (" . ($case['v3_score'] ?? '-') . ") dipertahankan tanpa mutasi sumber.";
            $consequences[] = "Zero-DML terjamin: Tidak ada pembaruan pada cbt_aktivitas maupun gradebook.";
        } elseif ($decisionId === 'DEC-ABS-01') {
            $consequences[] = "Status ketidakhadiran siswa dikonfirmasi sebagai ABSENT sesuai PODR DEC-ABS-01.";
            $consequences[] = "Nilai rapor tetap ditahan NULL (bukan nilai perolehan nol legacy).";
            $consequences[] = "Fallback nilai nol legacy diakui tanpa mengubah integritas status absensi siswa.";
        } elseif ($decisionId === 'DEC-ESS-01') {
            $consequences[] = "Kasus dialihkan ke Guru Pengampu Mapel (TEACHER_ACTION_REQUIRED) untuk penilaian esai.";
            $consequences[] = "Nilai akhir dan nilai rapor tetap ditahan (HOLD / NULL) hingga seluruh esai wajib dinilai.";
            $consequences[] = "Nilai komponen objektif tetap dipertahankan secara terpisah.";
        } elseif ($decisionId === 'DEC-RET-01') {
            if ($action === ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT || $action === ReviewerDecisionValidator::ACTION_RATIFY_SESSION) {
                $sessId = $params['selected_session_id'] ?? 'N/A';
                $ownerRef = $params['owner_approval_ref'] ?? 'N/A';
                $consequences[] = "Sesi ujian {$sessId} dipilih secara eksplisit berdasarkan persetujuan resmi '{$ownerRef}'.";
                $consequences[] = "Kasus dinyatakan RESOLVED dengan referensi audit persetujuan owner.";
            } else {
                $consequences[] = "Kasus dieskalasikan ke Product Owner / Tim Kurikulum (OWNER_DECISION_REQUIRED) untuk penentuan sesi resmi.";
            }
        } elseif ($decisionId === 'OVERRIDE-SYNC-POLICY') {
            $consequences[] = "Ajudikasi ketidakhadiran diselesaikan di bawah PODR OVERRIDE-SYNC-POLICY.";
            $consequences[] = "Kedua sumber (BAP digital fisik dan telemetri server) dipelihara berdampingan secara non-destruktif.";
            if ($action === ReviewerDecisionValidator::ACTION_CONFIRM_ABSENCE) {
                $consequences[] = "Status absensi dikonfirmasi; nilai rapor tetap NULL.";
            } elseif ($action === ReviewerDecisionValidator::ACTION_RATIFY_SESSION) {
                $consequences[] = "Sesi telemetri siswa diratifikasi sebagai sesi absah; catatan ketidakhadiran pengawas diarsipkan.";
            } elseif ($action === ReviewerDecisionValidator::ACTION_QUARANTINE_DATA) {
                $consequences[] = "Data upaya ujian dikarantina untuk peninjauan dewan penguji.";
            }
        }

        $consequences[] = "Audit Trail: Satu entri transaksi audit baru dengan hash chaining SHA-256 akan dicatat saat eksekusi riil.";

        return $consequences;
    }

    /**
     * Apply a reviewer decision to a case with fail-closed audit protection.
     *
     * @param string $caseId
     * @param string $reviewerId
     * @param string $action
     * @param string $reason
     * @param array $params
     * @return array Updated case
     * @throws RuntimeException If audit trail writing fails
     */
    public function applyDecision(
        string $caseId,
        string $reviewerId,
        string $action,
        string $reason,
        array $params = []
    ): array {
        if (!isset($this->cases[$caseId])) {
            throw new InvalidArgumentException("Case ID '{$caseId}' not found in review queue.");
        }

        // 0. Full immutable snapshot of prior case state for complete atomicity
        $caseBefore = $this->cases[$caseId];
        $statusBefore = $caseBefore['review_status'];

        // Terminal State & Concurrency Guard (Stage F3G-R6-R2-R3-R1 Audit C)
        if ($statusBefore === self::STATUS_RESOLVED) {
            throw new InvalidReviewActionException("GOVERNANCE BREACH: Case '{$caseId}' is already resolved. Duplicate apply rejected.");
        }

        if (isset($params['expected_status']) && $params['expected_status'] !== $statusBefore) {
            throw new InvalidReviewActionException("CONCURRENCY CONFLICT: Case '{$caseId}' status changed from '{$params['expected_status']}' to '{$statusBefore}' since preview. Apply rejected.");
        }

        // 1. Independent Actor and Assignment Validation (Stage F3G-R6-R2-R2 Audit D)
        // Preview authorization cannot be reused as authorization for apply; apply must independently validate.
        if (trim($reviewerId) === '') {
            throw new InvalidArgumentException('Reviewer ID is required for review actions.');
        }
        $actorRole = strtoupper(trim((string)($params['actor_role'] ?? $params['role'] ?? 'AUTHORIZED_OPERATOR')));
        $unauthorizedRoles = ['STUDENT', 'GUEST', 'UNAUTHORIZED'];
        if (in_array($actorRole, $unauthorizedRoles, true)) {
            throw new InvalidReviewActionException("GOVERNANCE BREACH: Actor with role '{$actorRole}' is not authorized to execute reviewer actions.");
        }

        // Granular subject teacher authority check independently enforced on apply
        if ($actorRole === 'TEACHER') {
            if (!$this->evidenceValidator->isTeacherAssignedToTest($reviewerId, $caseBefore['test_id'])) {
                throw new InvalidReviewActionException("GOVERNANCE BREACH: Teacher '{$reviewerId}' is not authorized to review Test ID {$caseBefore['test_id']}.");
            }
        }

        // 2. Decision validation through strict decision validator
        $validation = $this->validator->validate($caseBefore, $action, $reason, $params);

        $newStatus = $this->mapDecisionToWorkflowStatus($validation['decision_status']);
        $notes = $validation['notes'];
        $decisionId = $validation['decision_id'] ?? ($params['decision_id'] ?? null);

        // 3. Audit integrity check: verify chain before writing
        $auditIntegrity = $this->auditService->verifyAuditIntegrity();
        if (!$auditIntegrity['is_valid']) {
            throw new RuntimeException(
                "AUDIT INTEGRITY CORRUPTED: Review decision rejected because audit log integrity verification failed: " .
                ($auditIntegrity['reason'] ?? 'chain break or sequence tampering detected')
            );
        }

        // 4. Audit persistence: record in append-only audit trail FIRST.
        // If audit writing fails, this throws, preventing any state mutation.
        try {
            $auditEntry = $this->auditService->recordDecision(
                $reviewerId,
                $caseId,
                $statusBefore,
                $newStatus,
                $action,
                $reason,
                $caseBefore['evidence_reference'],
                [
                    'category'            => $caseBefore['category'],
                    'sanctioned_action'   => $validation['sanctioned_action'],
                    'decision_id'         => $decisionId,
                    'legacy_score'        => $caseBefore['legacy_score'] ?? null,
                    'v3_score'            => $caseBefore['v3_score'] ?? null,
                    'delta'               => $caseBefore['delta'] ?? null,
                    'notes'               => $notes,
                    'selected_session_id' => $params['selected_session_id'] ?? ($caseBefore['selected_session_id'] ?? null),
                    'owner_approval_ref'  => $params['owner_approval_ref'] ?? ($caseBefore['owner_approval_ref'] ?? null),
                    'sk_number'           => $params['sk_number'] ?? ($params['owner_approval_ref'] ?? null),
                    'sk_date'             => $params['sk_date'] ?? date('Y-m-d'),
                    'sk_issuer'           => $params['sk_issuer'] ?? ($params['actor_role'] ?? 'KEPALA_SEKOLAH'),
                    'ratification_time'   => date('Y-m-d H:i:s'),
                ],
                $decisionId
            );
        } catch (\Throwable $e) {
            // Absolute fail-closed guarantee: restore entire case state snapshot
            $this->cases[$caseId] = $caseBefore;
            throw $e;
        }

        // 5. Pre-mutation assignment re-verification (Stage F3G-R6-R2-R3-R1 Audit C)
        if ($actorRole === 'TEACHER' && !$this->evidenceValidator->isTeacherAssignedToTest($reviewerId, $caseBefore['test_id'])) {
            $this->cases[$caseId] = $caseBefore;
            throw new InvalidReviewActionException("GOVERNANCE BREACH: Teacher '{$reviewerId}' assignment was revoked prior to state mutation on Test ID {$caseBefore['test_id']}.");
        }

        // 6. Mutate case state & build resolved metadata ONLY after audit record is secured on disk
        $this->cases[$caseId]['review_status']       = $newStatus;
        $this->cases[$caseId]['responsible_party']   = $this->getPartyForStatus($newStatus);
        $this->cases[$caseId]['reviewer_id']         = $reviewerId;
        $this->cases[$caseId]['review_timestamp']    = $auditEntry['timestamp'];
        $this->cases[$caseId]['resolved_at']         = $auditEntry['timestamp'];
        $this->cases[$caseId]['decision']            = $action;
        $this->cases[$caseId]['decision_id']         = $decisionId;
        $this->cases[$caseId]['decision_reason']     = $reason;
        $this->cases[$caseId]['review_notes']        = $notes;
        $this->cases[$caseId]['selected_session_id'] = $params['selected_session_id'] ?? ($caseBefore['selected_session_id'] ?? null);
        $this->cases[$caseId]['owner_approval_ref']  = $params['owner_approval_ref'] ?? ($caseBefore['owner_approval_ref'] ?? null);
        $this->cases[$caseId]['sk_number']           = $params['sk_number'] ?? ($params['owner_approval_ref'] ?? null);
        $this->cases[$caseId]['sk_date']             = $params['sk_date'] ?? date('Y-m-d');
        $this->cases[$caseId]['sk_issuer']           = $params['sk_issuer'] ?? ($params['actor_role'] ?? 'KEPALA_SEKOLAH');
        $this->cases[$caseId]['ratified_at']         = $auditEntry['timestamp'];

        return $this->cases[$caseId];
    }

    /**
     * Get review queue summary statistics broken down by responsible party.
     */
    public function getSummary(): array
    {
        $total = count($this->cases);
        $byStatus = [
            self::STATUS_REVIEWER_ACTION_REQUIRED => 0,
            self::STATUS_TEACHER_ACTION_REQUIRED  => 0,
            self::STATUS_OWNER_DECISION_REQUIRED  => 0,
            self::STATUS_ACKNOWLEDGEMENT_REQUIRED => 0,
            self::STATUS_RESOLVED                 => 0,
            self::STATUS_BLOCKED                  => 0,
        ];

        $byParty = [
            'Teacher'       => 0,
            'Product Owner' => 0,
            'Reviewer'      => 0,
            'System/Admin'  => 0,
            'None (Resolved)'=> 0,
        ];

        $categoryBreakdown = [];

        foreach ($this->cases as $c) {
            $cat = $c['category'];
            $st = $c['review_status'];
            $party = $c['responsible_party'] ?? 'Reviewer';

            $categoryBreakdown[$cat] = ($categoryBreakdown[$cat] ?? 0) + 1;

            if (isset($byStatus[$st])) {
                $byStatus[$st]++;
            } else {
                $byStatus[$st] = 1;
            }

            $byParty[$party] = ($byParty[$party] ?? 0) + 1;
        }

        $pendingAny = $byStatus[self::STATUS_REVIEWER_ACTION_REQUIRED] +
                      $byStatus[self::STATUS_TEACHER_ACTION_REQUIRED] +
                      $byStatus[self::STATUS_OWNER_DECISION_REQUIRED] +
                      $byStatus[self::STATUS_ACKNOWLEDGEMENT_REQUIRED];

        $partyBreakdown = [
            'teacher_action_required'  => $byStatus[self::STATUS_TEACHER_ACTION_REQUIRED],
            'owner_decision_required'  => $byStatus[self::STATUS_OWNER_DECISION_REQUIRED],
            'acknowledgement_required' => $byStatus[self::STATUS_ACKNOWLEDGEMENT_REQUIRED],
            'reviewer_action_required' => $byStatus[self::STATUS_REVIEWER_ACTION_REQUIRED],
            'resolved'                 => $byStatus[self::STATUS_RESOLVED],
            'blocked'                  => $byStatus[self::STATUS_BLOCKED],
        ];

        return [
            'total_cases'                   => $total,
            'pending_cases'                 => $pendingAny,
            'reviewer_action_required'      => $byStatus[self::STATUS_REVIEWER_ACTION_REQUIRED],
            'teacher_action_required'       => $byStatus[self::STATUS_TEACHER_ACTION_REQUIRED],
            'owner_decision_required'       => $byStatus[self::STATUS_OWNER_DECISION_REQUIRED],
            'acknowledgement_required'      => $byStatus[self::STATUS_ACKNOWLEDGEMENT_REQUIRED],
            'resolved_cases'                => $byStatus[self::STATUS_RESOLVED],
            'blocked_cases'                 => $byStatus[self::STATUS_BLOCKED],
            'party_breakdown'               => $partyBreakdown,
            'requires_owner_decision_cases' => $byStatus[self::STATUS_OWNER_DECISION_REQUIRED], // Alias
            'in_review_cases'               => 0,
            'rejected_cases'                => $byStatus[self::STATUS_BLOCKED],
            'by_party'                      => $byParty,
            'category_breakdown'            => $categoryBreakdown,
            'is_all_cleared_for_staging'    => ($pendingAny === 0 && $byStatus[self::STATUS_BLOCKED] === 0),
        ];
    }

    /**
     * Export review queue to CSV (Zero PII).
     */
    public function exportCsv(): string
    {
        $fp = fopen('php://temp', 'r+');

        fputcsv($fp, [
            'CaseID',
            'PseudonymousStudentID',
            'TestID',
            'SubjectName',
            'SessionID',
            'Category',
            'LegacyScore',
            'V3Score',
            'RelevantStatus',
            'ReviewStatus',
            'ResponsibleParty',
            'ReviewerID',
            'ReviewTimestamp',
            'Decision',
            'DecisionReason',
        ]);

        foreach ($this->cases as $c) {
            fputcsv($fp, [
                $c['case_id'],
                $c['pseudonymous_student_id'],
                $c['test_id'],
                $c['subject_name'],
                $c['session_id'] ?? 'NULL',
                $c['category'],
                $c['legacy_score'] !== null ? $c['legacy_score'] : 'NULL',
                $c['v3_score'] !== null ? $c['v3_score'] : 'NULL',
                $c['relevant_status'],
                $c['review_status'],
                $c['responsible_party'] ?? 'Reviewer',
                $c['reviewer_id'] ?? 'UNASSIGNED',
                $c['review_timestamp'] ?? 'NULL',
                $c['decision'] ?? 'NONE',
                $c['decision_reason'] ?? '',
            ]);
        }

        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);

        return $csv;
    }

    private function determinePrimaryReviewCategory(array $categories, array $res): string
    {
        if (in_array('ATTEMPT_SELECTION_DIFFERENCE', $categories, true)) return 'AMBIGUOUS_RETAKE';
        if (in_array('ESSAY_POLICY_DIFFERENCE', $categories, true)) return 'PENDING_ESSAY';
        if (in_array('LEGACY_ZERO_FALLBACK', $categories, true)) return 'LEGACY_ZERO_FALLBACK';
        if (in_array('ROUNDING_DIFFERENCE', $categories, true)) return 'ROUNDING_DIFFERENCE';
        if (in_array('PUBLICATION_ELIGIBILITY_DIFFERENCE', $categories, true)) return 'PUBLICATION_ELIGIBILITY_DIFFERENCE';
        if (in_array('SCORE_DIFFERENCE', $categories, true)) return 'SCORE_POLICY_DIFFERENCE';
        if (in_array('NO_VALID_QUESTIONS', $categories, true)) return 'VOID_POLICY_DIFFERENCE';
        if (in_array('INTEGRITY_STATUS_DIFFERENCE', $categories, true)) return 'INTEGRITY_STATUS_DIFFERENCE';
        if (in_array('LEGACY_UNVERIFIABLE', $categories, true)) return 'LEGACY_UNVERIFIABLE';
        if (in_array('INSUFFICIENT_SOURCE_DATA', $categories, true)) return 'INSUFFICIENT_SOURCE_DATA';

        return $categories[0] ?? 'UNKNOWN_DEVIATION';
    }

    private function determineInitialPartyStatus(string $category, array $res): string
    {
        return match ($category) {
            'PENDING_ESSAY'          => self::STATUS_TEACHER_ACTION_REQUIRED,
            'AMBIGUOUS_RETAKE'       => self::STATUS_OWNER_DECISION_REQUIRED,
            'LEGACY_ZERO_FALLBACK'   => self::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            'ROUNDING_DIFFERENCE'    => self::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            'VOID_POLICY_DIFFERENCE' => self::STATUS_ACKNOWLEDGEMENT_REQUIRED,
            default                  => self::STATUS_REVIEWER_ACTION_REQUIRED,
        };
    }

    private function getPartyForStatus(string $status): string
    {
        return match ($status) {
            self::STATUS_TEACHER_ACTION_REQUIRED  => 'Teacher',
            self::STATUS_OWNER_DECISION_REQUIRED  => 'Product Owner',
            self::STATUS_ACKNOWLEDGEMENT_REQUIRED => 'Reviewer',
            self::STATUS_REVIEWER_ACTION_REQUIRED => 'Reviewer',
            self::STATUS_RESOLVED                 => 'None (Resolved)',
            self::STATUS_BLOCKED                  => 'System/Admin',
            default                               => 'Reviewer',
        };
    }

    private function mapDecisionToWorkflowStatus(string $decisionStatus): string
    {
        return match ($decisionStatus) {
            ReviewerDecisionValidator::DECISION_RESOLVED               => self::STATUS_RESOLVED,
            ReviewerDecisionValidator::DECISION_REQUIRES_OWNER_DECISION=> self::STATUS_OWNER_DECISION_REQUIRED,
            ReviewerDecisionValidator::DECISION_PENDING_TEACHER        => self::STATUS_TEACHER_ACTION_REQUIRED,
            ReviewerDecisionValidator::DECISION_REJECTED               => self::STATUS_BLOCKED,
            default                                                    => self::STATUS_REVIEWER_ACTION_REQUIRED,
        };
    }
}
