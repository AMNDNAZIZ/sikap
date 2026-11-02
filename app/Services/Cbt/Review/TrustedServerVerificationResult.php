<?php

namespace App\Services\Cbt\Review;

/**
 * TrustedServerVerificationResult
 *
 * Immutable value object representing a genuine server-side verification outcome.
 *
 * Cannot be constructed or forged from client-supplied HTTP payloads or untrusted arrays.
 * Bound to a specific source record, case context, and server session.
 *
 * Stage F3G-R6-R2-R2 Authority Authenticity, Authorization & Governance Reconciliation.
 */
final class TrustedServerVerificationResult
{
    public function __construct(
        public readonly string $sourceType,
        public readonly string $referenceId,
        public readonly string $sourceTable,
        public readonly string $status,
        public readonly string $authSemanticLevel,
        public readonly bool $isAuthoritativeVerified,
        public readonly bool $liveMariadbVerified,
        public readonly ?string $boundCaseId = null,
        public readonly string|int|null $boundStudentId = null,
        public readonly string|int|null $boundTestId = null,
        public readonly ?string $serverSessionToken = null,
        public readonly ?string $authoritativeChecksum = null,
        public readonly ?string $discrepancy = null,
        public readonly array $recordDetails = [],
        public readonly ?string $issuerSignature = null
    ) {}

    /**
     * Strictly disallow object cloning to maintain cryptographic immutability (Stage F3G-R6-R2-R3-R1 Audit B).
     */
    public function __clone(): void
    {
        throw new \LogicException('TrustedServerVerificationResult is strictly immutable and cannot be cloned.');
    }

    /**
     * Verify that this result is strictly bound to the current case, student, and test context.
     * Prevents record-switching / reusing verification results from record A to authenticate record B.
     *
     * Invariants (Stage F3G-R6-R2-R2 Audit C):
     * 1. boundCaseId, boundStudentId, boundTestId are MANDATORY.
     * 2. Any missing identity component fails closed.
     * 3. Prevents cross-case spoofing.
     */
    public function isAuthenticForCase(string $caseId, string|int $studentId, string|int $testId): bool
    {
        // Missing identity components fail closed
        if (empty($this->boundCaseId) || empty($this->boundStudentId) || empty($this->boundTestId)) {
            return false;
        }

        if (empty($caseId) || empty($studentId) || empty($testId)) {
            return false;
        }

        if ($this->boundCaseId !== $caseId) {
            return false;
        }

        if ((string)$this->boundStudentId !== (string)$studentId) {
            return false;
        }

        if ((string)$this->boundTestId !== (string)$testId) {
            return false;
        }

        // Must have passed either authoritative or verification logic
        if (!$this->isAuthoritativeVerified && !$this->isVerificationLogicPassed()) {
            return false;
        }

        return true;
    }

    /**
     * Verify that this result is genuine operational live authoritative verification.
     * Invariant (Audit B & C): live_mariadb_verified === false can NEVER establish live authoritative trust.
     */
    public function isLiveAuthoritativeForCase(string $caseId, string|int $studentId, string|int $testId): bool
    {
        return $this->isAuthenticForCase($caseId, $studentId, $testId)
            && $this->isAuthoritativeVerified
            && $this->liveMariadbVerified
            && $this->authSemanticLevel === ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED;
    }

    public function isVerificationLogicPassed(): bool
    {
        return $this->status === ReviewerEvidenceValidator::VERIFICATION_LOGIC_PASSED
            || $this->authSemanticLevel === ReviewerEvidenceValidator::AUTH_VERIFICATION_LOGIC_PASSED
            || $this->status === ReviewerEvidenceValidator::TRUST_AUTHORITATIVE_SOURCE_VERIFIED;
    }

    public function isAuthoritative(): bool
    {
        return $this->isAuthoritativeVerified
            && $this->liveMariadbVerified
            && $this->authSemanticLevel === ReviewerEvidenceValidator::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED;
    }

    public function toArray(): array
    {
        return [
            'source_type'               => $this->sourceType,
            'reference_id'              => $this->referenceId,
            'source_table'              => $this->sourceTable,
            'status'                    => $this->status,
            'auth_semantic_level'       => $this->authSemanticLevel,
            'is_authoritative_verified' => $this->isAuthoritativeVerified,
            'live_mariadb_verified'     => $this->liveMariadbVerified,
            'bound_case_id'             => $this->boundCaseId,
            'bound_student_id'          => $this->boundStudentId,
            'bound_test_id'             => $this->boundTestId,
            'discrepancy'               => $this->discrepancy,
            'record_details'            => $this->recordDetails,
            'has_issuer_signature'      => !empty($this->issuerSignature),
        ];
    }
}


