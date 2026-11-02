<?php

namespace App\Services\Cbt\Gradebook;

use InvalidArgumentException;
use RuntimeException;

/**
 * AttemptSelectorPolicy
 *
 * ESPATA Assessment Intelligence V3 - Stage E
 * Validates session provenance and governs multiple attempt / retake resolution.
 *
 * OWNER MANDATE (Section 5):
 * When multiple sessions exist for a student on a test:
 * - Do NOT silently pick highest score without explicit policy.
 * - Do NOT silently pick latest attempt without explicit policy.
 * - Do NOT silently merge attempts.
 * - If policy is not configured, flag as AMBIGUOUS_ATTEMPTS and require manual selection.
 */
class AttemptSelectorPolicy
{
    public const POLICY_REQUIRE_MANUAL    = 'REQUIRE_MANUAL';
    public const POLICY_LATEST_FINISHED   = 'LATEST_FINISHED';
    public const POLICY_HIGHEST_SCORE     = 'HIGHEST_SCORE';
    public const POLICY_FIRST_FINISHED    = 'FIRST_FINISHED';
    public const POLICY_EXPLICIT_SELECTION = 'EXPLICIT_SELECTION';

    public const DECISION_ID = 'DEC-RET-01';

    public const AUTHORIZED_ROLES = [
        'OWNER',
        'PRINCIPAL',
        'HEADMASTER',
        'CURRICULUM_HEAD',
        'ADMIN',
        'AUTHORIZED_OPERATOR',
        'REVIEWER',
    ];

    public const SUPPORTED_POLICIES = [
        self::POLICY_REQUIRE_MANUAL,
        self::POLICY_LATEST_FINISHED,
        self::POLICY_HIGHEST_SCORE,
        self::POLICY_FIRST_FINISHED,
        self::POLICY_EXPLICIT_SELECTION,
    ];

    /**
     * Validate session candidate provenance.
     *
     * @param array $session
     * @param int|string $expectedStudentId
     * @param int|string $expectedTestId
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateSessionProvenance(array $session, $expectedStudentId, $expectedTestId): bool
    {
        $sessionStudentId = $session['student_id'] ?? null;
        if ((string)$sessionStudentId !== (string)$expectedStudentId) {
            throw new InvalidArgumentException(
                "Session {$session['id']} belongs to student '{$sessionStudentId}', expected student '{$expectedStudentId}'"
            );
        }

        $sessionTestId = $session['test_id'] ?? null;
        if ((string)$sessionTestId !== (string)$expectedTestId) {
            throw new InvalidArgumentException(
                "Session {$session['id']} belongs to test '{$sessionTestId}', expected test '{$expectedTestId}'"
            );
        }

        $status = strtolower(trim((string)($session['status'] ?? '')));
        $validStatuses = ['finished', 'submitted'];
        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException(
                "Session {$session['id']} status '{$status}' is not eligible for grading (expected finished/submitted)"
            );
        }

        return true;
    }

    /**
     * Select the definitive session among candidate sessions for a student.
     *
     * @param array $sessions List of session records for the student & test
     * @param int|string $studentId
     * @param int|string $testId
     * @param string $policy Selection policy (default POLICY_REQUIRE_MANUAL)
     * @param int|string|null $explicitSessionId Explicitly chosen session ID (if provided by teacher/admin)
     * @return array [
     *                 'selected_session' => array|null,
     *                 'status'           => 'SELECTED'|'AMBIGUOUS_ATTEMPTS'|'NO_VALID_SESSIONS',
     *                 'policy_used'      => string,
     *                 'candidate_count'  => int,
     *                 'audit_note'       => string
     *               ]
     */
    public function selectAttempt(
        array $sessions,
        $studentId,
        $testId,
        string $policy = self::POLICY_REQUIRE_MANUAL,
        $explicitSessionId = null,
        array $authContext = []
    ): array {
        // Filter and validate eligible sessions
        $eligibleSessions = [];
        foreach ($sessions as $s) {
            try {
                $this->validateSessionProvenance($s, $studentId, $testId);
                $eligibleSessions[] = $s;
            } catch (InvalidArgumentException $e) {
                // Ineligible session, skip
                continue;
            }
        }

        $candidateCount = count($eligibleSessions);

        if ($candidateCount === 0) {
            return [
                'selected_session' => null,
                'status'           => 'NO_VALID_SESSIONS',
                'policy_used'      => $policy,
                'candidate_count'  => 0,
                'audit_note'       => 'No eligible finished/submitted sessions found for this student and test',
            ];
        }

        // If explicitSessionId was provided, verify and use it
        if ($explicitSessionId !== null) {
            if (array_key_exists('owner_approval_ref', $authContext) && empty($authContext['owner_approval_ref'])) {
                throw new InvalidArgumentException(
                    "GOVERNANCE BREACH [DEC-RET-01]: Multiple retake selection requires explicit 'owner_approval_ref'."
                );
            }
            if (isset($authContext['actor_role'])) {
                $role = strtoupper(trim((string)$authContext['actor_role']));
                if (in_array($role, ['STUDENT', 'GUEST', 'UNAUTHORIZED'], true) || (!empty($role) && !in_array($role, self::AUTHORIZED_ROLES, true))) {
                    throw new InvalidArgumentException(
                        "GOVERNANCE BREACH [DEC-RET-01]: Actor with role '{$role}' is not authorized to select retake session."
                    );
                }
            }

            foreach ($eligibleSessions as $s) {
                if ((string)$s['id'] === (string)$explicitSessionId) {
                    $ownerRef = $authContext['owner_approval_ref'] ?? null;
                    return [
                        'selected_session'   => $s,
                        'status'             => 'SELECTED',
                        'policy_used'        => self::POLICY_EXPLICIT_SELECTION,
                        'decision_id'        => self::DECISION_ID,
                        'owner_approval_ref' => $ownerRef,
                        'actor_id'           => $authContext['actor_id'] ?? null,
                        'candidate_count'    => $candidateCount,
                        'audit_note'         => "Session {$explicitSessionId} selected explicitly under DEC-RET-01" . ($ownerRef ? " (Ref: {$ownerRef})" : ""),
                    ];
                }
            }
            throw new InvalidArgumentException(
                "Explicitly chosen session ID '{$explicitSessionId}' is not among eligible sessions for student '{$studentId}'"
            );
        }

        // Exactly one attempt: clean selection
        if ($candidateCount === 1) {
            $session = reset($eligibleSessions);
            return [
                'selected_session' => $session,
                'status'           => 'SELECTED',
                'policy_used'      => 'SINGLE_ATTEMPT',
                'candidate_count'  => 1,
                'audit_note'       => "Single valid session (ID: {$session['id']}) auto-selected",
            ];
        }

        // Multiple attempts handling
        if ($policy === self::POLICY_REQUIRE_MANUAL) {
            return [
                'selected_session' => null,
                'status'           => 'AMBIGUOUS_ATTEMPTS',
                'policy_used'      => self::POLICY_REQUIRE_MANUAL,
                'decision_id'      => self::DECISION_ID,
                'candidate_count'  => $candidateCount,
                'audit_note'       => "Student has {$candidateCount} valid sessions. Explicit manual selection required under DEC-RET-01.",
            ];
        }

        if ($policy === self::POLICY_LATEST_FINISHED) {
            // Sort by finished_at DESC, id DESC
            usort($eligibleSessions, function ($a, $b) {
                $timeA = $a['finished_at'] ?? ($a['updated_at'] ?? '0');
                $timeB = $b['finished_at'] ?? ($b['updated_at'] ?? '0');
                if ($timeA === $timeB) {
                    return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
                }
                return strcmp($timeB, $timeA);
            });
            $selected = $eligibleSessions[0];
            return [
                'selected_session' => $selected,
                'status'           => 'SELECTED',
                'policy_used'      => self::POLICY_LATEST_FINISHED,
                'candidate_count'  => $candidateCount,
                'audit_note'       => "Resolved by LATEST_FINISHED policy (Selected session ID: {$selected['id']})",
            ];
        }

        if ($policy === self::POLICY_HIGHEST_SCORE) {
            usort($eligibleSessions, function ($a, $b) {
                $scoreA = (float)($a['total_score'] ?? ($a['score'] ?? 0.0));
                $scoreB = (float)($b['total_score'] ?? ($b['score'] ?? 0.0));
                if ($scoreA === $scoreB) {
                    return ($b['id'] ?? 0) <=> ($a['id'] ?? 0);
                }
                return $scoreB <=> $scoreA;
            });
            $selected = $eligibleSessions[0];
            return [
                'selected_session' => $selected,
                'status'           => 'SELECTED',
                'policy_used'      => self::POLICY_HIGHEST_SCORE,
                'candidate_count'  => $candidateCount,
                'audit_note'       => "Resolved by HIGHEST_SCORE policy (Selected session ID: {$selected['id']})",
            ];
        }

        if ($policy === self::POLICY_FIRST_FINISHED) {
            usort($eligibleSessions, function ($a, $b) {
                $timeA = $a['finished_at'] ?? ($a['created_at'] ?? '0');
                $timeB = $b['finished_at'] ?? ($b['created_at'] ?? '0');
                if ($timeA === $timeB) {
                    return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
                }
                return strcmp($timeA, $timeB);
            });
            $selected = $eligibleSessions[0];
            return [
                'selected_session' => $selected,
                'status'           => 'SELECTED',
                'policy_used'      => self::POLICY_FIRST_FINISHED,
                'candidate_count'  => $candidateCount,
                'audit_note'       => "Resolved by FIRST_FINISHED policy (Selected session ID: {$selected['id']})",
            ];
        }

        throw new InvalidArgumentException("Unsupported attempt selector policy: '{$policy}'");
    }

    /**
     * Explicit selection governed strictly by DEC-RET-01.
     *
     * @param array $sessions
     * @param int|string $studentId
     * @param int|string $testId
     * @param int|string $explicitSessionId
     * @param string $actorId
     * @param string $actorRole
     * @param string $reason
     * @param string $ownerApprovalRef
     * @return array
     * @throws InvalidArgumentException
     */
    public function selectExplicitAttempt(
        array $sessions,
        $studentId,
        $testId,
        $explicitSessionId,
        string $actorId,
        string $actorRole,
        string $reason,
        string $ownerApprovalRef
    ): array {
        if (trim($ownerApprovalRef) === '') {
            throw new InvalidArgumentException(
                "GOVERNANCE BREACH [DEC-RET-01]: Multiple retake selection requires non-empty 'owner_approval_ref'."
            );
        }
        if (trim($actorId) === '') {
            throw new InvalidArgumentException(
                "GOVERNANCE BREACH [DEC-RET-01]: Actor ID is required for explicit retake selection."
            );
        }
        $role = strtoupper(trim($actorRole));
        if (in_array($role, ['STUDENT', 'GUEST', 'UNAUTHORIZED'], true) || (!empty($role) && !in_array($role, self::AUTHORIZED_ROLES, true))) {
            throw new InvalidArgumentException(
                "GOVERNANCE BREACH [DEC-RET-01]: Actor '{$actorId}' with role '{$role}' is not authorized to select retake session."
            );
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException(
                "GOVERNANCE BREACH [DEC-RET-01]: Reason is mandatory for explicit retake selection."
            );
        }

        return $this->selectAttempt($sessions, $studentId, $testId, self::POLICY_EXPLICIT_SELECTION, $explicitSessionId, [
            'owner_approval_ref' => $ownerApprovalRef,
            'actor_id'           => $actorId,
            'actor_role'         => $role,
            'reason'             => $reason,
        ]);
    }
}

