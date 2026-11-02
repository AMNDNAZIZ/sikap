<?php

namespace App\Services\Cbt\Review;

use App\Services\Cbt\Gradebook\AttemptSelectorPolicy;

/**
 * ReviewerDecisionValidator
 *
 * ESPATA Assessment Intelligence V3 - Stage F3
 * Enforces strict reviewer authority boundaries and governance rules.
 *
 * MANDATORY GOVERNANCE INVARIANTS:
 * 1. PENDING_ESSAY: Cannot be approved or published by reviewer; must wait for teacher grading.
 * 2. LEGACY_ZERO_FALLBACK: Absent students cannot be turned into earned zero scores.
 * 3. AMBIGUOUS_RETAKE: Can only be selected if a sanctioned school attempt policy or owner approval is provided.
 * 4. MANUAL_GRADEBOOK_VALUE: Overrides cannot be performed without explicit product owner authorization.
 * 5. HISTORICAL INTEGRITY: Reviewer cannot mutate historical raw answer records.
 */
class ReviewerDecisionValidator
{
    public const ACTION_SELECT_ATTEMPT           = 'SELECT_ATTEMPT';
    public const ACTION_REQUEST_OWNER_DECISION   = 'REQUEST_OWNER_DECISION';
    public const ACTION_ACKNOWLEDGE_POLICY_DIFF  = 'ACKNOWLEDGE_POLICY_DIFF';
    public const ACTION_ACKNOWLEDGE_TOLERANCE    = 'ACKNOWLEDGE_POLICY_DIFF';
    public const ACTION_FLAG_TEACHER_ACTION      = 'FLAG_TEACHER_ACTION';
    public const ACTION_REQUEST_TEACHER_GRADING  = 'FLAG_TEACHER_ACTION';
    public const ACTION_CONFIRM_ABSENCE          = 'CONFIRM_ABSENCE';
    public const ACTION_RATIFY_SESSION           = 'RATIFY_SESSION';
    public const ACTION_QUARANTINE_DATA          = 'QUARANTINE_DATA';
    public const ACTION_GRANT_RETEST             = 'GRANT_RETEST';
    public const ACTION_REJECT                   = 'REJECT';

    public const DECISION_RESOLVED               = 'RESOLVED';
    public const DECISION_REQUIRES_OWNER_DECISION= 'REQUIRES_OWNER_DECISION';
    public const DECISION_PENDING_TEACHER        = 'PENDING_TEACHER';
    public const DECISION_REJECTED               = 'REJECTED';

    /**
     * Validate a reviewer action against a review case.
     *
     * @param array $case Review case data
     * @param string $action Action being attempted
     * @param string $reason Reason provided by reviewer
     * @param array $params Additional parameters (e.g. policy, session_id, owner_ref)
     * @return array Validated decision outcome ['is_valid' => bool, 'decision_status' => string, 'sanctioned_action' => string, 'notes' => string]
     * @throws InvalidReviewActionException If the action violates non-negotiable rules
     */
    public function validate(array $case, string $action, string $reason, array $params = []): array
    {
        $category = $case['category'] ?? 'UNKNOWN';
        $v3Status = $case['relevant_status'] ?? '';

        if (trim($reason) === '') {
            throw new InvalidReviewActionException('A non-empty justification reason is required for all reviewer decisions.');
        }

        // Global Actor Role Authority Enforcement
        $actorRole = strtoupper(trim((string)($params['actor_role'] ?? $params['role'] ?? 'AUTHORIZED_OPERATOR')));
        $unauthorizedRoles = ['STUDENT', 'GUEST', 'UNAUTHORIZED'];
        if (in_array($actorRole, $unauthorizedRoles, true)) {
            throw new InvalidReviewActionException(
                "GOVERNANCE BREACH: Actor with role '{$actorRole}' is not authorized to execute reviewer actions."
            );
        }

        // Helper to validate decision_id parameter if provided
        $assertDecisionIdMatch = function (?string $expectedId) use ($params, $action) {
            $providedId = $params['decision_id'] ?? null;
            if (!empty($params['require_decision_id']) && empty($providedId)) {
                throw new InvalidReviewActionException(
                    "GOVERNANCE BREACH: Mandatory decision_id is required for action '{$action}'."
                );
            }
            if ($providedId !== null && $expectedId !== null && $providedId !== $expectedId) {
                throw new InvalidReviewActionException(
                    "GOVERNANCE BREACH: Provided decision_id '{$providedId}' does not match required policy decision '{$expectedId}' for action '{$action}'."
                );
            }
        };

        // Rule 1: PENDING_ESSAY cannot be approved or published by reviewer (DEC-ESS-01)
        if (in_array($category, ['ESSAY_POLICY_DIFFERENCE', 'PENDING_ESSAY'], true) || $v3Status === 'PENDING_ESSAY') {
            if (in_array(strtoupper($action), ['APPROVE', 'PUBLISH', 'FORCE_PUBLISH', 'SET_SCORE', self::ACTION_SELECT_ATTEMPT], true)) {
                throw new InvalidReviewActionException(
                    'GOVERNANCE BREACH: Reviewer cannot approve or publish scores for PENDING_ESSAY. ' .
                    '[DEC-ESS-01] Academic integrity requires essay questions to be graded by the subject teacher before score publication.'
                );
            }

            // Allowed: Flag for teacher action or request owner decision
            if ($action === self::ACTION_FLAG_TEACHER_ACTION) {
                $assertDecisionIdMatch('DEC-ESS-01');
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_PENDING_TEACHER,
                    'sanctioned_action' => self::ACTION_FLAG_TEACHER_ACTION,
                    'decision_id'       => 'DEC-ESS-01',
                    'notes'             => 'Case flagged for teacher essay grading; score remains HOLD (NULL) under DEC-ESS-01 until grading complete.',
                ];
            }
        }

        // Rule 2: LEGACY_ZERO_FALLBACK - Cannot convert absent to earned zero (DEC-ABS-01)
        if (in_array($category, ['LEGACY_ZERO_FALLBACK'], true) || $v3Status === 'ABSENT') {
            if (in_array(strtoupper($action), ['CONVERT_TO_EARNED_ZERO', 'SET_ZERO_SCORE', 'APPROVE_AS_ZERO'], true)) {
                throw new InvalidReviewActionException(
                    'GOVERNANCE BREACH: Reviewer cannot convert student absence into an earned zero score. ' .
                    '[DEC-ABS-01] V3 strictly distinguishes between absent (unattempted) and legitimate earned zero.'
                );
            }

            if ($action === self::ACTION_ACKNOWLEDGE_POLICY_DIFF) {
                $assertDecisionIdMatch('DEC-ABS-01');
                // Must ensure absence evidence is verified
                $evidenceVerified = $params['evidence_verified'] ?? ($case['evidence_verified'] ?? true);
                if ($evidenceVerified === false) {
                    throw new InvalidReviewActionException(
                        'GOVERNANCE BREACH: [DEC-ABS-01] Cannot acknowledge absence zero fallback without verified absence evidence.'
                    );
                }

                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                    'decision_id'       => 'DEC-ABS-01',
                    'notes'             => 'Confirmed absence preserved as null; legacy zero fallback acknowledged under DEC-ABS-01 without altering student absence status.',
                ];
            }
        }

        // Rule 2B: ATTENDANCE_DISCREPANCY / ABSENCE_DISCREPANCY (DEC-ABS-01 & OVERRIDE-SYNC-POLICY)
        if (in_array($category, ['ATTENDANCE_DISCREPANCY', 'ABSENCE_DISCREPANCY'], true) || $v3Status === 'ATTENDANCE_DISCREPANCY') {
            if (in_array(strtoupper($action), ['CONVERT_TO_EARNED_ZERO', 'SET_ZERO_SCORE', 'APPROVE_AS_ZERO'], true)) {
                throw new InvalidReviewActionException(
                    'GOVERNANCE BREACH: Reviewer cannot convert attendance discrepancy into an earned zero score. ' .
                    '[DEC-ABS-01] V3 strictly distinguishes between absent and legitimate earned zero.'
                );
            }

            if ($action === self::ACTION_CONFIRM_ABSENCE) {
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_CONFIRM_ABSENCE,
                    'decision_id'       => 'DEC-ABS-01',
                    'notes'             => 'Absence confirmed from digital BAP evidence; score remains NULL under DEC-ABS-01. Telemetry preserved non-destructively.',
                ];
            }

            if ($action === self::ACTION_RATIFY_SESSION) {
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_RATIFY_SESSION,
                    'decision_id'       => 'OVERRIDE-SYNC-POLICY',
                    'notes'             => 'Telemetry session ratified by authorized reviewer; proctor absence note retained in historical BAP record.',
                ];
            }

            if ($action === self::ACTION_QUARANTINE_DATA) {
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_QUARANTINE_DATA,
                    'notes'             => 'Attempt quarantined pending committee investigation under OVERRIDE-SYNC-POLICY.',
                ];
            }

            if ($action === self::ACTION_GRANT_RETEST) {
                $ownerRef = $params['owner_approval_ref'] ?? null;
                if (empty($ownerRef)) {
                    throw new InvalidReviewActionException(
                        "GOVERNANCE BREACH: Granting retest requires explicit 'owner_approval_ref'."
                    );
                }
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_GRANT_RETEST,
                    'notes'             => "Retest authorization granted under reference '{$ownerRef}'.",
                ];
            }
        }

        // Rule 3: AMBIGUOUS_RETAKE / RETAKE_ANOMALY - Requires explicit selection & owner authorization (DEC-RET-01)
        if (in_array($category, ['ATTEMPT_SELECTION_DIFFERENCE', 'AMBIGUOUS_RETAKE', 'RETAKE_ANOMALY', 'MULTIPLE_RETAKES'], true) || $v3Status === 'AMBIGUOUS_ATTEMPTS') {
            if ($action === self::ACTION_SELECT_ATTEMPT || $action === self::ACTION_RATIFY_SESSION) {
                $assertDecisionIdMatch('DEC-RET-01');
                $selectedSessionId = $params['selected_session_id'] ?? null;
                $ownerRef = $params['owner_approval_ref'] ?? null;

                // 1. Must specify selected session ID
                if (!$selectedSessionId) {
                    throw new InvalidReviewActionException(
                        'GOVERNANCE BREACH: [DEC-RET-01] Selected session ID must be provided when resolving ambiguous retakes.'
                    );
                }

                // 2. DEC-RET-01: Must have explicit owner approval reference
                if (empty($ownerRef)) {
                    throw new InvalidReviewActionException(
                        "GOVERNANCE BREACH: [DEC-RET-01] Multiple retake selection requires explicit 'owner_approval_ref'. " .
                        "Automatic selection (highest score, latest attempt, etc.) is strictly prohibited under DEC-RET-01."
                    );
                }

                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => $action,
                    'decision_id'       => 'DEC-RET-01',
                    'notes'             => "Attempt resolved explicitly to session {$selectedSessionId} under DEC-RET-01 via owner approval ref '{$ownerRef}'.",
                ];
            }

            // Escalation to owner
            if ($action === self::ACTION_REQUEST_OWNER_DECISION) {
                $assertDecisionIdMatch('DEC-RET-01');
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_REQUIRES_OWNER_DECISION,
                    'sanctioned_action' => self::ACTION_REQUEST_OWNER_DECISION,
                    'decision_id'       => 'DEC-RET-01',
                    'notes'             => 'Ambiguous retakes retained as REQUIRES_OWNER_DECISION awaiting explicit selection under DEC-RET-01.',
                ];
            }
        }

        // Rule 4: ROUNDING_DIFFERENCE (DEC-ROUND-01)
        if (in_array($category, ['ROUNDING_DIFFERENCE'], true)) {
            if ($action === self::ACTION_ACKNOWLEDGE_POLICY_DIFF) {
                $assertDecisionIdMatch('DEC-ROUND-01');

                // Fail-closed dependency check: BCMath is mandatory for exact decimal precision
                if (!function_exists('bccomp') || !function_exists('bcsub')) {
                    throw new \RuntimeException(
                        'CRITICAL DEPENDENCY ERROR: BCMath extension is required for exact decimal policy enforcement [DEC-ROUND-01].'
                    );
                }

                $legacyScore = $params['legacy_score'] ?? ($case['legacy_score'] ?? null);
                $v3Score = $params['v3_score'] ?? ($case['v3_score'] ?? null);
                $delta = $params['delta'] ?? ($case['delta'] ?? null);

                if ($delta === null) {
                    if ($legacyScore === null || $v3Score === null) {
                        throw new InvalidReviewActionException(
                            'GOVERNANCE BREACH: [DEC-ROUND-01] Cannot acknowledge rounding difference without valid legacy and V3 scores.'
                        );
                    }
                    if (!is_numeric($legacyScore) || !is_numeric($v3Score)) {
                        throw new InvalidReviewActionException(
                            'GOVERNANCE BREACH: [DEC-ROUND-01] Legacy and V3 scores must be valid numeric values.'
                        );
                    }

                    $legStr = is_string($legacyScore) ? trim($legacyScore) : sprintf('%.10f', (float)$legacyScore);
                    $v3Str  = is_string($v3Score) ? trim($v3Score) : sprintf('%.10f', (float)$v3Score);
                    $subScale = max(
                        (strpos($legStr, '.') !== false ? strlen($legStr) - strpos($legStr, '.') - 1 : 0),
                        (strpos($v3Str, '.') !== false ? strlen($v3Str) - strpos($v3Str, '.') - 1 : 0),
                        10
                    );
                    $deltaStr = bcsub($v3Str, $legStr, $subScale);
                } else {
                    if (!is_numeric($delta)) {
                        throw new InvalidReviewActionException(
                            "GOVERNANCE BREACH: [DEC-ROUND-01] Delta must be a valid numeric value, '" . (is_scalar($delta) ? (string)$delta : gettype($delta)) . "' provided."
                        );
                    }
                    $deltaStr = is_string($delta) ? trim($delta) : sprintf('%.10f', (float)$delta);
                }

                $isNegative = str_starts_with($deltaStr, '-');
                $absStr = $isNegative ? substr($deltaStr, 1) : $deltaStr;

                $decimalPos = strpos($absStr, '.');
                $inputScale = ($decimalPos !== false) ? (strlen($absStr) - $decimalPos - 1) : 0;
                $scale = max($inputScale, 10);

                // Exact comparison: abs(delta) <= 0.05 without tolerance expansion
                $isExceeded = bccomp($absStr, '0.05', $scale) === 1;

                if ($isExceeded) {
                    throw new InvalidReviewActionException(
                        "GOVERNANCE BREACH: [DEC-ROUND-01] Rounding difference delta ({$deltaStr}) exceeds permitted tolerance of 0.05. " .
                        "Acknowledgement rejected. Case requires investigation."
                    );
                }

                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_RESOLVED,
                    'sanctioned_action' => self::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                    'decision_id'       => 'DEC-ROUND-01',
                    'notes'             => "Rounding difference delta {$deltaStr} acknowledged within 0.05 tolerance under DEC-ROUND-01 without mutating scores.",
                ];
            }
        }

        // Rule 4: MANUAL_GRADEBOOK_VALUE override
        if ($category === 'MANUAL_TEACHER_SCORE' || $action === 'OVERRIDE_MANUAL_SCORE') {
            $ownerAuth = $params['owner_override_auth'] ?? false;
            if (!$ownerAuth) {
                return [
                    'is_valid'          => true,
                    'decision_status'   => self::DECISION_REQUIRES_OWNER_DECISION,
                    'sanctioned_action' => self::ACTION_REQUEST_OWNER_DECISION,
                    'notes'             => 'Manual gradebook score override policy awaits formal approval from system owner.',
                ];
            }
        }

        // Rule 5: Standard owner decision request
        if ($action === self::ACTION_REQUEST_OWNER_DECISION) {
            return [
                'is_valid'          => true,
                'decision_status'   => self::DECISION_REQUIRES_OWNER_DECISION,
                'sanctioned_action' => self::ACTION_REQUEST_OWNER_DECISION,
                'notes'             => 'Reviewer escalated case to Product Owner / Curriculum Committee.',
            ];
        }

        // Standard policy acknowledgment
        if ($action === self::ACTION_ACKNOWLEDGE_POLICY_DIFF) {
            return [
                'is_valid'          => true,
                'decision_status'   => self::DECISION_RESOLVED,
                'sanctioned_action' => self::ACTION_ACKNOWLEDGE_POLICY_DIFF,
                'notes'             => 'Policy difference acknowledged and documented.',
            ];
        }

        // Rejection
        if ($action === self::ACTION_REJECT) {
            return [
                'is_valid'          => true,
                'decision_status'   => self::DECISION_REJECTED,
                'sanctioned_action' => self::ACTION_REJECT,
                'notes'             => 'Case review rejected by reviewer: ' . $reason,
            ];
        }

        // Default: If action is unrecognized or unapproved
        return [
            'is_valid'          => false,
            'decision_status'   => self::DECISION_REQUIRES_OWNER_DECISION,
            'sanctioned_action' => self::ACTION_REQUEST_OWNER_DECISION,
            'notes'             => "Unsanctioned action '{$action}' routed to REQUIRES_OWNER_DECISION.",
        ];
    }
}
