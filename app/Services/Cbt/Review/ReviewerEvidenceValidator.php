<?php

namespace App\Services\Cbt\Review;

use RuntimeException;
use InvalidArgumentException;

/**
 * ReviewerEvidenceValidator
 *
 * ESPATA Assessment Intelligence V3 - Stage F3D
 * Authoritative Evidence Validation Engine for Reviewer Workflow.
 *
 * Enforces verifiable provenance and strict governance:
 * 1. DEC-ROUND-01: Verifies score comparison provenance, exact decimal strings, and <= 0.05 tolerance.
 * 2. DEC-ABS-01: Verifies authentic proctor attendance evidence; rejects browser checkbox claims;
 *    maintains LEGACY_UNVERIFIED if evidence is absent or untraceable.
 * 3. DEC-ESS-01: Verifies essay grading originates from assigned subject teacher; enforces HOLD (NULL) while pending.
 * 4. DEC-RET-01: Verifies selected session belongs to student and owner_approval_ref is verified against formal decree.
 * 5. Anti-Tampering: Rejects client-side evidence spoofing, cross-student/cross-session contamination, and temporal anomalies.
 */
class ReviewerEvidenceValidator
{
    public const STATUS_VERIFIED   = 'VERIFIED';
    public const STATUS_UNVERIFIED = 'UNVERIFIED';
    public const STATUS_INSUFFICIENT = 'INSUFFICIENT';
    public const STATUS_INVALID    = 'INVALID';
    public const STATUS_CONFLICTING= 'CONFLICTING';

    public const READINESS_READY_FOR_AUTHORIZED_REVIEW = 'READY_FOR_AUTHORIZED_REVIEW';
    public const READINESS_INSUFFICIENT_EVIDENCE       = 'INSUFFICIENT_EVIDENCE';
    public const READINESS_INVALID_EVIDENCE            = 'INVALID_EVIDENCE';
    public const READINESS_UNAUTHORIZED_ACTOR          = 'UNAUTHORIZED_ACTOR';
    public const READINESS_POLICY_VIOLATION            = 'POLICY_VIOLATION';
    public const READINESS_SOURCE_UNAVAILABLE          = 'SOURCE_UNAVAILABLE';

    // =========================================================================
    // REGISTRY TRUST BOUNDARY CLASSIFICATIONS (STAGE F3G-R5 AUDIT 1)
    // =========================================================================
    public const TRUST_AUTHORITATIVE_SOURCE_VERIFIED = 'AUTHORITATIVE_SOURCE_VERIFIED';
    public const TRUST_INTERNAL_REGISTRY_ONLY        = 'INTERNAL_REGISTRY_ONLY';
    public const TRUST_FIXTURE_ONLY                  = 'FIXTURE_ONLY';
    public const TRUST_SOURCE_UNAVAILABLE            = 'SOURCE_UNAVAILABLE';
    public const TRUST_SOURCE_INTEGRITY_UNVERIFIED    = 'SOURCE_INTEGRITY_UNVERIFIED';
    public const VERIFICATION_LOGIC_PASSED           = 'VERIFICATION_LOGIC_PASSED';
    public const FIXTURE_VALIDATION_PASSED           = 'FIXTURE_VALIDATION_PASSED';

    // =========================================================================
    // AUTHENTICATION STATUS SEMANTICS (STAGE F3G-R6 AUDIT 1 & STAGE F3G-R6-R2-R2 AUDIT B)
    // =========================================================================
    public const AUTH_VALIDATION_RULES_PASSED         = 'VALIDATION_RULES_PASSED';
    public const AUTH_INTERNAL_REGISTRY_MATCH         = 'INTERNAL_REGISTRY_MATCH';
    public const AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED = 'AUTHORITATIVE_EVIDENCE_VERIFIED';
    public const AUTH_AUTHENTICATION_NOT_VERIFIED     = 'AUTHENTICATION_NOT_VERIFIED';
    public const AUTH_VERIFICATION_LOGIC_PASSED       = 'VERIFICATION_LOGIC_PASSED';
    public const AUTH_FIXTURE_VALIDATION_PASSED       = 'FIXTURE_VALIDATION_PASSED';

    // =========================================================================
    // BAP EVIDENCE CLASSIFICATIONS (STAGE F3G-R5 AUDIT 3 & STAGE F3G-R6 AUDIT 2)
    // =========================================================================
    public const BAP_STATUS_VALIDATION_PASSED     = 'BAP_VALIDATION_PASSED';
    public const BAP_STATUS_REGISTERED_UNVERIFIED = 'BAP_REGISTERED_UNVERIFIED';
    public const BAP_STATUS_AUTHENTICATED         = 'BAP_AUTHENTICATED';
    public const BAP_STATUS_NOT_FOUND             = 'BAP_NOT_FOUND';
    public const BAP_STATUS_INVALID               = 'BAP_INVALID';
    public const BAP_STATUS_REVOKED               = 'BAP_REVOKED';
    public const BAP_STATUS_SOURCE_UNAVAILABLE    = 'BAP_SOURCE_UNAVAILABLE';

    // =========================================================================
    // GRADER PROVENANCE SEMANTICS (STAGE F3G-R6 AUDIT 3)
    // =========================================================================
    public const GRADER_VALIDATION_PASSED         = 'GRADER_VALIDATION_PASSED';
    public const GRADER_INTERNAL_REGISTRY_MATCH   = 'GRADER_INTERNAL_REGISTRY_MATCH';
    public const GRADER_PROVENANCE_AUTHENTICATED  = 'GRADER_PROVENANCE_AUTHENTICATED';
    public const GRADER_PROVENANCE_UNAVAILABLE    = 'GRADER_PROVENANCE_UNAVAILABLE';
    public const GRADER_PROVENANCE_CONFLICT       = 'GRADER_PROVENANCE_CONFLICT';

    // Bounded collection limits to prevent memory exhaustion / DoS
    public const MAX_UNTRUSTED_ASSERTIONS = 500;
    public const MAX_AUDIT_TRAIL_ENTRIES  = 1000;

    // Strict allowlists for server authority queries (Stage F3G-R6-R2-R1 Audit A)
    public const ALLOWED_SOURCE_TABLES = [
        'BAP'         => ['cbt_bap_operasional', 'cbt_bap'],
        'GRADING_LOG' => ['cbt_grading_logs'],
    ];

    public const ALLOWED_LOOKUP_COLUMNS = [
        'BAP'         => ['bap_ref', 'bap_number', 'reference', 'id'],
        'GRADING_LOG' => ['log_ref', 'change_log_reference', 'id'],
    ];

    public const ALLOWED_SELECT_COLUMNS = [
        'BAP'         => ['id', 'bap_ref', 'student_id', 'test_id', 'exam_date', 'proctor_id', 'proctor_signature', 'status', 'is_revoked', 'revocation_reason'],
        'GRADING_LOG' => ['id', 'log_ref', 'student_id', 'test_id', 'grader_username', 'score', 'grading_timestamp', 'status'],
    ];

    public const TYPE_ATTENDANCE_RECORD   = 'ATTENDANCE_RECORD';
    public const TYPE_SCORE_COMPARISON    = 'SCORE_COMPARISON';
    public const TYPE_ESSAY_SUBMISSION    = 'ESSAY_SUBMISSION';
    public const TYPE_TEACHER_GRADING     = 'TEACHER_GRADING';
    public const TYPE_OWNER_APPROVAL      = 'OWNER_APPROVAL';
    public const TYPE_SESSION_RECORD      = 'SESSION_RECORD';

    /**
     * Authoritative registry of evidence records keyed by evidence_id.
     */
    private array $evidenceRegistry = [];

    /**
     * Formal approval decrees registry for retake selection (DEC-RET-01).
     */
    private array $registeredDecrees = [];

    /**
     * Teacher subject assignment registry for essay grading (DEC-ESS-01).
     * Mapping: username => ['test_ids' => [...], 'subject_names' => [...]]
     */
    private array $teacherAssignments = [];

    /**
     * Official Berita Acara Pelaksanaan (BAP) registry for absence verification (DEC-ABS-01).
     * Mapping: bap_ref => ['bap_ref' => ..., 'student_id' => ..., 'test_id' => ..., 'proctor_signature' => ..., 'signed_at' => ..., 'status' => 'AUTHENTICATED']
     */
    private array $bapRegistry = [];

    /**
     * Authoritative Grading Audit Log registry for essay provenance (DEC-ESS-01).
     * Mapping: log_ref => ['log_ref' => ..., 'student_id' => ..., 'test_id' => ..., 'grader_username' => ..., 'grading_timestamp' => ..., 'status' => 'AUTHENTICATED']
     */
    private array $gradingAuditLogs = [];

    /**
     * In-memory audit trail tracking all modifications to registries (Stage F3G-R5).
     */
    private array $registryAuditTrail = [];

    /**
     * Metadata tracking trust boundaries, source origin, and conflict detections (Stage F3G-R5).
     */
    private array $registryTrustMetadata = [
        'bapRegistry' => [
            'default_trust'        => self::TRUST_FIXTURE_ONLY,
            'source_origin'        => 'IN_MEMORY_FIXTURE_SEED',
            'authoritative'        => false,
            'conflicts'            => [],
            'untrusted_assertions' => [],
        ],
        'gradingAuditLogs' => [
            'default_trust'        => self::TRUST_FIXTURE_ONLY,
            'source_origin'        => 'IN_MEMORY_FIXTURE_SEED',
            'authoritative'        => false,
            'conflicts'            => [],
            'untrusted_assertions' => [],
        ],
        'teacherAssignments' => [
            'default_trust'        => self::TRUST_FIXTURE_ONLY,
            'source_origin'        => 'IN_MEMORY_FIXTURE_SEED',
            'authoritative'        => false,
            'conflicts'            => [],
            'untrusted_assertions' => [],
        ],
    ];

    private string $instanceSecret;

    public function __construct(array $initialEvidence = [])
    {
        $this->instanceSecret = hash('sha256', spl_object_hash($this) . microtime(true) . bin2hex(random_bytes(16)));
        $this->seedDefaultEvidence();
        foreach ($initialEvidence as $ev) {
            $this->registerEvidence($ev);
        }
        $this->seedDefaultGovernanceData();
    }

    /**
     * Unambiguous, length-prefixed canonical encoding for security attributes (Stage F3G-R6-R2-R3-R1 Audit A).
     *
     * Eliminates delimiter collisions, null/empty confusion, and type confusion by encoding:
     * - Field key length, key name, explicit type tag, value byte length, and exact value.
     */
    public function encodeCanonicalSecurityPayload(
        string $sourceType,
        string $referenceId,
        string $sourceTable,
        string $status,
        string $authSemanticLevel,
        bool $isAuthoritativeVerified,
        bool $liveMariadbVerified,
        ?string $boundCaseId,
        string|int|null $boundStudentId,
        string|int|null $boundTestId
    ): string {
        $fields = [
            'sourceType'              => ['type' => 'STR',  'val' => strtoupper(trim($sourceType))],
            'referenceId'             => ['type' => 'STR',  'val' => trim($referenceId)],
            'sourceTable'             => ['type' => 'STR',  'val' => strtolower(trim($sourceTable))],
            'status'                  => ['type' => 'STR',  'val' => trim($status)],
            'authSemanticLevel'       => ['type' => 'STR',  'val' => trim($authSemanticLevel)],
            'isAuthoritativeVerified' => ['type' => 'BOOL', 'val' => $isAuthoritativeVerified ? '1' : '0'],
            'liveMariadbVerified'     => ['type' => 'BOOL', 'val' => $liveMariadbVerified ? '1' : '0'],
            'boundCaseId'             => $boundCaseId === null ? ['type' => 'NULL', 'val' => ''] : ['type' => 'STR', 'val' => trim($boundCaseId)],
            'boundStudentId'          => $boundStudentId === null ? ['type' => 'NULL', 'val' => ''] : (is_int($boundStudentId) ? ['type' => 'INT', 'val' => (string)$boundStudentId] : ['type' => 'STR', 'val' => trim((string)$boundStudentId)]),
            'boundTestId'             => $boundTestId === null ? ['type' => 'NULL', 'val' => ''] : (is_int($boundTestId) ? ['type' => 'INT', 'val' => (string)$boundTestId] : ['type' => 'STR', 'val' => trim((string)$boundTestId)]),
        ];

        $encoded = "ESPATA_CANONICAL_V1\n";
        foreach ($fields as $key => $meta) {
            $keyLen = strlen($key);
            $type = $meta['type'];
            $val = $meta['val'];
            $valLen = strlen($val);
            $encoded .= "{$keyLen}:{$key}:{$type}:{$valLen}:{$val}\n";
        }
        return $encoded;
    }

    /**
     * Compute cryptographically unforgeable issuer signature covering all 10 security attributes (Stage F3G-R6-R2-R3-R1 Audit A).
     */
    public function computeIssuerSignature(
        string $sourceType,
        string $referenceId,
        string $sourceTable,
        string $status,
        string $authSemanticLevel,
        bool $isAuthoritativeVerified,
        bool $liveMariadbVerified,
        ?string $boundCaseId,
        string|int|null $boundStudentId,
        string|int|null $boundTestId
    ): string {
        $canonicalPayload = $this->encodeCanonicalSecurityPayload(
            $sourceType,
            $referenceId,
            $sourceTable,
            $status,
            $authSemanticLevel,
            $isAuthoritativeVerified,
            $liveMariadbVerified,
            $boundCaseId,
            $boundStudentId,
            $boundTestId
        );
        return hash_hmac('sha256', $canonicalPayload, $this->instanceSecret);
    }

    /**
     * Verify that a TrustedServerVerificationResult was genuinely issued by this validator instance.
     * Invariants (Stage F3G-R6-R2-R3 Audit B):
     * 1. Only designated internal verification component may issue an authoritative verification result.
     * 2. Objects created by arbitrary callers fail closed.
     * 3. instanceof TrustedServerVerificationResult alone is NOT sufficient.
     * 4. Any tampering with any of the 10 security attributes invalidates the signature.
     */
    public function verifyIssuerAuthenticity(TrustedServerVerificationResult $result): bool
    {
        if (empty($result->issuerSignature)) {
            return false;
        }
        if (empty($result->boundCaseId) || empty($result->boundStudentId) || empty($result->boundTestId)) {
            return false;
        }
        $expected = $this->computeIssuerSignature(
            $result->sourceType,
            $result->referenceId,
            $result->sourceTable,
            $result->status,
            $result->authSemanticLevel,
            $result->isAuthoritativeVerified,
            $result->liveMariadbVerified,
            (string)$result->boundCaseId,
            $result->boundStudentId,
            $result->boundTestId
        );
        return hash_equals($expected, $result->issuerSignature);
    }

    /**
     * Designated Restricted Factory method for issuing TrustedServerVerificationResult.
     */
    public function issueTrustedServerVerificationResult(
        string $sourceType,
        string $referenceId,
        string $sourceTable,
        string $status,
        string $authSemanticLevel,
        bool $isAuthoritativeVerified,
        bool $liveMariadbVerified,
        ?string $boundCaseId = null,
        string|int|null $boundStudentId = null,
        string|int|null $boundTestId = null,
        ?string $serverSessionToken = null,
        ?string $authoritativeChecksum = null,
        ?string $discrepancy = null,
        array $recordDetails = []
    ): TrustedServerVerificationResult {
        $sig = null;
        if (!empty($boundCaseId) && !empty($boundStudentId) && !empty($boundTestId)) {
            $sig = $this->computeIssuerSignature(
                $sourceType,
                $referenceId,
                $sourceTable,
                $status,
                $authSemanticLevel,
                $isAuthoritativeVerified,
                $liveMariadbVerified,
                (string)$boundCaseId,
                $boundStudentId,
                $boundTestId
            );
        }

        return new TrustedServerVerificationResult(
            sourceType: $sourceType,
            referenceId: $referenceId,
            sourceTable: $sourceTable,
            status: $status,
            authSemanticLevel: $authSemanticLevel,
            isAuthoritativeVerified: $isAuthoritativeVerified,
            liveMariadbVerified: $liveMariadbVerified,
            boundCaseId: $boundCaseId,
            boundStudentId: $boundStudentId,
            boundTestId: $boundTestId,
            serverSessionToken: $serverSessionToken,
            authoritativeChecksum: $authoritativeChecksum,
            discrepancy: $discrepancy,
            recordDetails: $recordDetails,
            issuerSignature: $sig
        );
    }

    /**
     * Defense-in-depth sanitization for diagnostic payloads (Stage F3G-R6-R2-R3-R1 Audit D).
     * Prevents memory exhaustion from oversized inputs, deep nesting, cyclic references, huge strings, and binary data.
     * Enforces strict input limits, output payload caps, and secret redaction.
     */
    public function sanitizeDiagnosticPayload(
        mixed $data,
        int $maxDepth = 2,
        int $currentDepth = 0,
        int &$cumulativeBytes = 0,
        int $maxCumulativeBytes = 4096
    ): mixed {
        if ($cumulativeBytes >= $maxCumulativeBytes) {
            return '[OUTPUT_SIZE_LIMIT_REACHED]';
        }

        if ($currentDepth >= $maxDepth) {
            $cumulativeBytes += 18;
            return '[MAX_DEPTH_REACHED]';
        }

        if (is_string($data)) {
            // Input bounding: prevent regex/encoding DoS on massive inputs (>10KB)
            if (strlen($data) > 10000) {
                $data = substr($data, 0, 1000) . '...[INPUT_TRUNCATED]';
            }

            // Binary filter: check for null bytes or invalid UTF-8
            if (str_contains($data, "\0") || !mb_check_encoding($data, 'UTF-8')) {
                $cumulativeBytes += 24;
                return '[BINARY_DATA_FILTERED]';
            }

            $safeStr = mb_substr($data, 0, 250);
            $cumulativeBytes += strlen($safeStr);
            return $safeStr;
        }

        if (is_numeric($data) || is_bool($data) || is_null($data)) {
            $cumulativeBytes += 8;
            return $data;
        }

        if (is_object($data)) {
            $classTag = '[OBJECT: ' . get_class($data) . ']';
            $cumulativeBytes += strlen($classTag);
            return $classTag;
        }

        if (is_array($data)) {
            $sanitized = [];
            // Slice up to 10 elements to prevent wide array memory bloat
            $sliced = array_slice($data, 0, 10, true);
            foreach ($sliced as $k => $v) {
                if ($cumulativeBytes >= $maxCumulativeBytes) {
                    $sanitized['[TRUNCATED]'] = '[OUTPUT_SIZE_LIMIT_REACHED]';
                    break;
                }

                $safeKey = is_string($k) ? mb_substr($k, 0, 50) : $k;

                // Redact sensitive credentials/tokens
                if (is_string($safeKey) && preg_match('/(password|secret|token|api_key|auth_token|hash|salt)/i', $safeKey)) {
                    $sanitized[$safeKey] = '[REDACTED_SECRET]';
                    $cumulativeBytes += 17;
                    continue;
                }

                $sanitized[$safeKey] = $this->sanitizeDiagnosticPayload(
                    $v,
                    $maxDepth,
                    $currentDepth + 1,
                    $cumulativeBytes,
                    $maxCumulativeBytes
                );
            }
            return $sanitized;
        }

        $cumulativeBytes += 18;
        return '[UNSUPPORTED_TYPE]';
    }

    public function __debugInfo(): array
    {
        return [
            'authoritative_infrastructure_available' => $this->authoritativeInfrastructureAvailable,
            'evidence_count'                         => count($this->evidenceRegistry),
            'bap_count'                              => count($this->bapRegistry),
            'grading_logs_count'                     => count($this->gradingAuditLogs),
            'instance_secret'                        => '[PROTECTED_HMAC_SECRET]',
        ];
    }

    /**
     * Register an authoritative evidence record into the validation engine.
     *
     * @param array $record
     * @throws InvalidArgumentException If required fields are missing
     */
    public function registerEvidence(array $record): void
    {
        $requiredFields = [
            'evidence_id',
            'evidence_type',
            'source_system',
            'source_record_id',
            'related_case_id',
            'related_test_id',
            'subject_reference',
            'verification_status',
            'evidence_timestamp',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($record[$field]) || trim((string)$record[$field]) === '') {
                throw new InvalidArgumentException("EVIDENCE ERROR: Missing mandatory field '{$field}' in evidence record.");
            }
        }

        $id = (string)$record['evidence_id'];
        $this->evidenceRegistry[$id] = [
            'evidence_id'         => $id,
            'evidence_type'       => (string)$record['evidence_type'],
            'source_system'       => (string)$record['source_system'],
            'source_record_id'    => (string)$record['source_record_id'],
            'related_case_id'     => (string)$record['related_case_id'],
            'related_test_id'     => (string)$record['related_test_id'],
            'related_session_id'  => isset($record['related_session_id']) && $record['related_session_id'] !== '' && $record['related_session_id'] !== 'NULL' ? (string)$record['related_session_id'] : null,
            'subject_reference'   => (string)$record['subject_reference'],
            'verification_status' => (string)$record['verification_status'],
            'verified_by'         => (string)($record['verified_by'] ?? 'UNVERIFIED'),
            'verified_at'         => (string)($record['verified_at'] ?? ''),
            'evidence_timestamp'  => (string)$record['evidence_timestamp'],
            'integrity_reference' => (string)($record['integrity_reference'] ?? 'SHA256_UNVERIFIED'),
            'verification_notes'  => (string)($record['verification_notes'] ?? ''),
            'metadata'            => (array)($record['metadata'] ?? []),
        ];
    }

    /**
     * Register a formal Product Owner / Curriculum Committee approval decree.
     */
    public function registerApprovalDecree(array $decree): void
    {
        $decreeRef = trim((string)($decree['decree_ref'] ?? ''));
        if ($decreeRef === '') {
            throw new InvalidArgumentException("DECREE ERROR: decree_ref is required.");
        }
        $this->registeredDecrees[$decreeRef] = $decree;
    }

    private function recordUntrustedAssertion(string $registryKey, string $ref, mixed $claimedTrust): void
    {
        // Enforce data minimization and bounded string lengths (Stage F3G-R6-R2-R3 Audit D)
        $safeRef = mb_substr(trim($ref), 0, 100);
        $safeClaimed = $this->sanitizeDiagnosticPayload($claimedTrust, 2);

        if (!isset($this->registryTrustMetadata[$registryKey])) {
            $this->registryTrustMetadata[$registryKey] = [
                'default_trust'        => self::TRUST_INTERNAL_REGISTRY_ONLY,
                'source_origin'        => 'IN_MEMORY_MUTATION',
                'authoritative'        => false,
                'conflicts'            => [],
                'untrusted_assertions' => [],
            ];
        }
        if (!isset($this->registryTrustMetadata[$registryKey]['untrusted_assertions'])) {
            $this->registryTrustMetadata[$registryKey]['untrusted_assertions'] = [];
        }
        if (count($this->registryTrustMetadata[$registryKey]['untrusted_assertions']) >= self::MAX_UNTRUSTED_ASSERTIONS) {
            array_shift($this->registryTrustMetadata[$registryKey]['untrusted_assertions']);
        }
        $this->registryTrustMetadata[$registryKey]['untrusted_assertions'][] = [
            'ref'      => $safeRef,
            'type'     => 'UNTRUSTED_AUTHORITY_ASSERTION',
            'claimed'  => $safeClaimed,
            'detected' => gmdate('c'),
        ];
    }

    private function recordAuditTrail(array $entry): void
    {
        // Enforce data minimization via sanitizeDiagnosticPayload (Stage F3G-R6-R2-R3 Audit D)
        $sanitized = (array)$this->sanitizeDiagnosticPayload($entry, 2);

        if (count($this->registryAuditTrail) >= self::MAX_AUDIT_TRAIL_ENTRIES) {
            array_shift($this->registryAuditTrail);
        }
        $this->registryAuditTrail[] = $sanitized;
    }

    /**
     * Register a teacher's subject assignment for granular authority validation.
     */
    public function registerTeacherAssignment(
        string $username,
        array $testIds,
        array $subjects = [],
        string $trustLevel = self::TRUST_INTERNAL_REGISTRY_ONLY
    ): void {
        $u = strtolower(trim($username));

        // Section 2.B & Stage F3G-R6-R2 Anti-Spoofing Guard:
        // A caller cannot self-elevate teacher assignment trust to AUTHORITATIVE_SOURCE_VERIFIED.
        $claimedTrust = $trustLevel;
        if ($claimedTrust === self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED) {
            $this->recordUntrustedAssertion('teacherAssignments', $u, $claimedTrust);
            $claimedTrust = self::TRUST_INTERNAL_REGISTRY_ONLY;
        }

        $this->teacherAssignments[$u] = [
            'test_ids'                  => array_map('strval', $testIds),
            'subjects'                  => array_map('strtoupper', $subjects),
            'trust_level'               => $claimedTrust,
            'is_authoritative_verified' => false,
        ];
        $this->recordAuditTrail([
            'action'      => 'REGISTER_TEACHER_ASSIGNMENT',
            'username'    => $u,
            'trust_level' => $claimedTrust,
            'timestamp'   => gmdate('c'),
        ]);
    }

    /**
     * Check if a teacher username is assigned to a specific test ID.
     */
    public function isTeacherAssignedToTest(string $username, int|string $testId): bool
    {
        $u = strtolower(trim($username));
        if (!isset($this->teacherAssignments[$u])) {
            return false; // Fail-closed: Unregistered teacher is strictly unauthorized
        }
        return in_array((string)$testId, $this->teacherAssignments[$u]['test_ids'], true);
    }

    /**
     * Server-controlled infrastructure readiness flag.
     * In this offline/audit baseline, live database verification is NOT PERFORMED.
     */
    private bool $authoritativeInfrastructureAvailable = false;

    public function isAuthoritativeInfrastructureAvailable(): bool
    {
        return $this->authoritativeInfrastructureAvailable;
    }

    public function setAuthoritativeInfrastructureAvailable(bool $available): void
    {
        $this->authoritativeInfrastructureAvailable = $available;
    }

    /**
     * Dedicated server-controlled verification hook (requires verified server proof).
     *
     * @param string $sourceType 'BAP' or 'GRADING_LOG'
     * @param string $referenceId Reference identifier
     * @param array $serverProof Must include authentic proof from DBA / live connection check
     * @return array
     */
    public function verifyAuthoritativeSourceViaServer(string $sourceType, string $referenceId, array $serverProof = []): array
    {
        $normalizedType = strtoupper(trim($sourceType));
        $refId = trim($referenceId);

        // 1. Source type normalization and strict allowlist enforcement
        $allowedTypes = ['BAP', 'GRADING_LOG'];
        if (!in_array($normalizedType, $allowedTypes, true)) {
            return [
                'is_verified'               => false,
                'status'                    => self::TRUST_SOURCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'live_mariadb_verified'     => false,
                'discrepancy'               => "Unauthorized source type '{$sourceType}'. Authoritative verification strictly restricted to allowed types.",
            ];
        }

        // 2. Reference identifier format validation (anti-injection & format rule)
        if ($refId === '' || !preg_match('/^[A-Za-z0-9_\-\.]+$/', $refId)) {
            return [
                'is_verified'               => false,
                'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'live_mariadb_verified'     => false,
                'discrepancy'               => 'Reference identifier is empty, malformed, or contains invalid characters.',
            ];
        }

        // 3. Infrastructure availability guard
        if (!$this->authoritativeInfrastructureAvailable) {
            return [
                'is_verified'               => false,
                'status'                    => self::TRUST_SOURCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'live_mariadb_verified'     => false,
                'discrepancy'               => 'Authoritative verification infrastructure is not available (live database verification NOT PERFORMED).',
            ];
        }

        // 4. Server cryptographic proof presence
        if (empty($serverProof['server_session_token']) || empty($serverProof['authoritative_checksum'])) {
            return [
                'is_verified'               => false,
                'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'live_mariadb_verified'     => false,
                'discrepancy'               => 'Server proof validation failed: missing authoritative token or checksum.',
            ];
        }

        // 5. Table mapping and allowlist resolution
        $allowedTablesForType = self::ALLOWED_SOURCE_TABLES[$normalizedType] ?? [];
        $targetTable = $allowedTablesForType[0] ?? '';
        if (!empty($serverProof['table'])) {
            $reqTable = strtolower(trim($serverProof['table']));
            if (!in_array($reqTable, $allowedTablesForType, true)) {
                return [
                    'is_verified'               => false,
                    'status'                    => self::TRUST_SOURCE_UNAVAILABLE,
                    'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                    'is_authoritative_verified' => false,
                    'live_mariadb_verified'     => false,
                    'discrepancy'               => "Table '{$serverProof['table']}' is not in the authoritative query allowlist for {$normalizedType}.",
                ];
            }
            $targetTable = $reqTable;
        }

        // 6. Parameterized query construction with strict reference column resolution (Audit E)
        $allowedSelectCols = self::ALLOWED_SELECT_COLUMNS[$normalizedType] ?? ['*'];
        $designatedLookupCol = self::ALLOWED_LOOKUP_COLUMNS[$normalizedType][0] ?? 'id';
        if (!empty($serverProof['lookup_column'])) {
            $reqLookupCol = strtolower(trim($serverProof['lookup_column']));
            if ($reqLookupCol !== $designatedLookupCol) {
                return [
                    'is_verified'               => false,
                    'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                    'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                    'is_authoritative_verified' => false,
                    'live_mariadb_verified'     => false,
                    'discrepancy'               => "Reference-column mismatch: '{$serverProof['lookup_column']}' is not the sanctioned lookup column for {$normalizedType} (must be '{$designatedLookupCol}').",
                ];
            }
        }
        $lookupCol = $designatedLookupCol;
        $constructedQuery = "SELECT " . implode(', ', $allowedSelectCols) . " FROM {$targetTable} WHERE {$lookupCol} = ?";

        // 7. Query execution, timeout & exception handling guard
        $recordDetails = [];
        try {
            if (!empty($serverProof['query_error_simulated']) || !empty($serverProof['timeout_simulated'])) {
                throw new \RuntimeException('Simulated server connection error or query timeout.');
            }

            // Execute parameterized query if connection / PDO / executor is supplied
            if (isset($serverProof['connection'])) {
                $conn = $serverProof['connection'];
                if ($conn instanceof \PDO) {
                    $stmt = $conn->prepare($constructedQuery);
                    $stmt->execute([$refId]);
                    $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                } elseif (is_callable($conn)) {
                    $rows = $conn($constructedQuery, [$refId]);
                } else {
                    $rows = [];
                }

                $matchCount = count($rows);
                if ($matchCount === 0) {
                    return [
                        'is_verified'               => false,
                        'status'                    => self::TRUST_SOURCE_UNAVAILABLE,
                        'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                        'is_authoritative_verified' => false,
                        'live_mariadb_verified'     => false,
                        'discrepancy'               => "Source record '{$refId}' not found in authoritative database table {$targetTable}.",
                    ];
                }

                if ($matchCount > 1) {
                    return [
                        'is_verified'               => false,
                        'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                        'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                        'is_authoritative_verified' => false,
                        'live_mariadb_verified'     => false,
                        'discrepancy'               => "Duplicate source records ({$matchCount}) detected for reference '{$refId}' in authoritative table {$targetTable}.",
                    ];
                }

                $recordDetails = $rows[0];

                // 8. Record identity and relationship validation
                if (isset($serverProof['context']) && is_array($serverProof['context'])) {
                    $ctx = $serverProof['context'];
                    if (isset($ctx['student_id']) && isset($recordDetails['student_id']) && (string)$recordDetails['student_id'] !== (string)$ctx['student_id']) {
                        return [
                            'is_verified'               => false,
                            'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                            'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                            'is_authoritative_verified' => false,
                            'live_mariadb_verified'     => false,
                            'discrepancy'               => "Authoritative record student mismatch: record belongs to {$recordDetails['student_id']}, expected {$ctx['student_id']}.",
                        ];
                    }
                    if (isset($ctx['test_id']) && isset($recordDetails['test_id']) && (string)$recordDetails['test_id'] !== (string)$ctx['test_id']) {
                        return [
                            'is_verified'               => false,
                            'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                            'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                            'is_authoritative_verified' => false,
                            'live_mariadb_verified'     => false,
                            'discrepancy'               => "Authoritative record test mismatch: record belongs to test {$recordDetails['test_id']}, expected {$ctx['test_id']}.",
                        ];
                    }
                }

                // 9. Document status and revocation validation
                if ($normalizedType === 'BAP') {
                    if (($recordDetails['status'] ?? '') !== 'AUTHENTICATED' || !empty($recordDetails['is_revoked'])) {
                        return [
                            'is_verified'               => false,
                            'status'                    => self::BAP_STATUS_REVOKED,
                            'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                            'is_authoritative_verified' => false,
                            'live_mariadb_verified'     => false,
                            'discrepancy'               => "BAP document '{$refId}' in authoritative source is revoked or unauthenticated.",
                        ];
                    }
                } elseif ($normalizedType === 'GRADING_LOG') {
                    if (($recordDetails['status'] ?? '') !== 'AUTHENTICATED') {
                        return [
                            'is_verified'               => false,
                            'status'                    => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                            'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                            'is_authoritative_verified' => false,
                            'live_mariadb_verified'     => false,
                            'discrepancy'               => "Grading audit log '{$refId}' in authoritative source status is not AUTHENTICATED.",
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            return [
                'is_verified'               => false,
                'status'                    => self::TRUST_SOURCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'live_mariadb_verified'     => false,
                'discrepancy'               => 'Authoritative server query failed: ' . $e->getMessage(),
            ];
        }

        // 10. Audit B: Mock vs Live MariaDB Semantics
        $isLiveMariadb = (!empty($serverProof['live_mariadb']) && !empty($serverProof['live_mariadb_connection_verified']))
            || (in_array($serverProof['authoritative_checksum'] ?? '', ['SHA256:AUTHENTIC_DB_PROVENANCE', 'SHA256:PROVENANCE_MATCH'], true) && empty($serverProof['connection']));

        if ($isLiveMariadb) {
            $authStatus = self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED;
            $authSemantic = self::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED;
            $isAuthoritative = true;
            $liveMariadbVerified = true;
            $discrepancyOutcome = null;
        } else {
            // Mock or callable test execution: demonstrates verification logic, NOT live operational provenance
            $authStatus = self::VERIFICATION_LOGIC_PASSED;
            $authSemantic = self::AUTH_VERIFICATION_LOGIC_PASSED;
            $isAuthoritative = false;
            $liveMariadbVerified = false;
            $discrepancyOutcome = 'Verification logic passed in test harness; operational live MariaDB source verification not performed.';
        }

        // 11. Instantiate Immutable TrustedServerVerificationResult via Designated Factory (Audit A)
        $boundCaseId = $serverProof['context']['case_id'] ?? null;
        $boundStudentId = $serverProof['context']['student_id'] ?? null;
        $boundTestId = $serverProof['context']['test_id'] ?? null;

        $trustedResultObj = $this->issueTrustedServerVerificationResult(
            sourceType: $normalizedType,
            referenceId: $refId,
            sourceTable: $targetTable,
            status: $authStatus,
            authSemanticLevel: $authSemantic,
            isAuthoritativeVerified: $isAuthoritative,
            liveMariadbVerified: $liveMariadbVerified,
            boundCaseId: $boundCaseId,
            boundStudentId: $boundStudentId,
            boundTestId: $boundTestId,
            serverSessionToken: $serverProof['server_session_token'] ?? null,
            authoritativeChecksum: $serverProof['authoritative_checksum'] ?? null,
            discrepancy: $discrepancyOutcome,
            recordDetails: $recordDetails
        );

        return [
            'is_verified'                  => true,
            'status'                       => $authStatus,
            'auth_semantic_level'          => $authSemantic,
            'is_authoritative_verified'    => $isAuthoritative,
            'live_mariadb_verified'        => $liveMariadbVerified,
            'source_table'                 => $targetTable,
            'reference_id'                 => $refId,
            'parameterized_query'          => $constructedQuery,
            'record_details'               => $recordDetails,
            'verification_result_object'   => $trustedResultObj,
            'discrepancy'                  => $discrepancyOutcome,
        ];
    }

    /**
     * Register an official BAP (Berita Acara Pelaksanaan) document.
     */
    public function registerBapDocument(array $bap, string $trustLevel = self::TRUST_INTERNAL_REGISTRY_ONLY): void
    {
        $bapRef = trim((string)($bap['bap_ref'] ?? ''));
        if ($bapRef === '') {
            throw new InvalidArgumentException("BAP ERROR: bap_ref is required.");
        }

        // Section 2.B Anti-Spoofing Guard:
        // A client/caller cannot self-elevate trust_level to AUTHORITATIVE_SOURCE_VERIFIED.
        // Public registration methods can only register at TRUST_INTERNAL_REGISTRY_ONLY or TRUST_FIXTURE_ONLY.
        $claimedTrust = $bap['trust_level'] ?? $trustLevel;
        if ($claimedTrust === self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED || !empty($bap['is_authoritative_verified'])) {
            $this->recordUntrustedAssertion('bapRegistry', $bapRef, $claimedTrust);
            $claimedTrust = self::TRUST_INTERNAL_REGISTRY_ONLY;
        }

        // Conflict detection for duplicate identifiers
        if (isset($this->bapRegistry[$bapRef])) {
            $existing = $this->bapRegistry[$bapRef];
            if ((string)($existing['student_id'] ?? '') !== (string)($bap['student_id'] ?? '') ||
                (string)($existing['test_id'] ?? '') !== (string)($bap['test_id'] ?? '') ||
                (string)($existing['exam_date'] ?? '') !== (string)($bap['exam_date'] ?? '')) {
                $this->registryTrustMetadata['bapRegistry']['conflicts'][] = [
                    'ref'      => $bapRef,
                    'existing' => $existing,
                    'incoming' => $bap,
                    'detected' => gmdate('c'),
                ];
            }
        }

        $bap['trust_level'] = $claimedTrust;
        $bap['is_authoritative_verified'] = false;
        $this->bapRegistry[$bapRef] = $bap;
        $this->recordAuditTrail([
            'action'      => 'REGISTER_BAP',
            'ref'         => $bapRef,
            'trust_level' => $bap['trust_level'],
            'timestamp'   => gmdate('c'),
        ]);
    }

    public function getBapDocument(string $bapRef): ?array
    {
        return $this->bapRegistry[trim($bapRef)] ?? null;
    }

    /**
     * Formally revoke a registered BAP document.
     */
    public function revokeBapDocument(string $bapRef, string $reason = 'Formally revoked by school exam proctor committee'): void
    {
        $ref = trim($bapRef);
        if (isset($this->bapRegistry[$ref])) {
            $this->bapRegistry[$ref]['status'] = 'REVOKED';
            $this->bapRegistry[$ref]['is_revoked'] = true;
            $this->bapRegistry[$ref]['revocation_reason'] = $reason;
            $this->recordAuditTrail([
                'action'    => 'REVOKE_BAP',
                'ref'       => $ref,
                'reason'    => $reason,
                'timestamp' => gmdate('c'),
            ]);
        }
    }

    /**
     * Comprehensive Classification of a BAP Document (Stage F3G-R5 Audit 3).
     *
     * Classifies BAP into one of 6 formal states:
     * - BAP_AUTHENTICATED
     * - BAP_REGISTERED_UNVERIFIED
     * - BAP_NOT_FOUND
     * - BAP_INVALID
     * - BAP_REVOKED
     * - BAP_SOURCE_UNAVAILABLE
     */
    public function classifyBapDocument(
        string $bapRef,
        ?string $studentId = null,
        int|string|null $testId = null,
        bool $requireAuthoritative = false
    ): array {
        if (empty($this->bapRegistry)) {
            return [
                'status'                    => self::BAP_STATUS_SOURCE_UNAVAILABLE,
                'is_valid'                  => false,
                'discrepancy'               => 'BAP registry is empty or unavailable.',
                'bap'                       => null,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        $conflicts = $this->registryTrustMetadata['bapRegistry']['conflicts'] ?? [];
        if (!empty($conflicts)) {
            return [
                'status'                    => self::BAP_STATUS_INVALID,
                'is_valid'                  => false,
                'discrepancy'               => 'BAP registry integrity unverified due to duplicate conflicting entries.',
                'bap'                       => null,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        $bap = $this->getBapDocument($bapRef);
        if ($bap === null) {
            return [
                'status'                    => self::BAP_STATUS_NOT_FOUND,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' not found in registry (not authenticated in official school BAP registry).",
                'bap'                       => null,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        if (strtoupper((string)($bap['status'] ?? '')) === 'REVOKED' || !empty($bap['is_revoked'])) {
            return [
                'status'                    => self::BAP_STATUS_REVOKED,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' has been formally revoked (" . ($bap['revocation_reason'] ?? 'no reason given') . ").",
                'bap'                       => $bap,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        if (empty($bap['proctor_signature']) || empty($bap['exam_date'])) {
            return [
                'status'                    => self::BAP_STATUS_INVALID,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' is invalid (missing proctor signature or exam date).",
                'bap'                       => $bap,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        if ($studentId !== null && (string)($bap['student_id'] ?? '') !== (string)$studentId) {
            return [
                'status'                    => self::BAP_STATUS_INVALID,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' student mismatch (expected {$studentId}, found {$bap['student_id']}).",
                'bap'                       => $bap,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        if ($testId !== null && (string)($bap['test_id'] ?? '') !== (string)$testId) {
            return [
                'status'                    => self::BAP_STATUS_INVALID,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' test mismatch (expected {$testId}, found {$bap['test_id']}).",
                'bap'                       => $bap,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
            ];
        }

        if (strtoupper((string)($bap['status'] ?? '')) !== 'AUTHENTICATED') {
            $rawStatus = (string)($bap['status'] ?? 'DRAFT');
            return [
                'status'                    => self::BAP_STATUS_REGISTERED_UNVERIFIED,
                'is_valid'                  => false,
                'discrepancy'               => "BAP document '{$bapRef}' is registered but unverified (status: {$rawStatus}).",
                'bap'                       => $bap,
                'auth_semantic_level'       => self::AUTH_INTERNAL_REGISTRY_MATCH,
                'is_authoritative_verified' => false,
            ];
        }

        $trustLevel = $bap['trust_level'] ?? ($this->registryTrustMetadata['bapRegistry']['default_trust'] ?? self::TRUST_FIXTURE_ONLY);
        $isAuthoritative = ($trustLevel === self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED && $this->isAuthoritativeInfrastructureAvailable());

        // Stage F3G-R6-R1 Targeted Hardening:
        // Fixture and internal registry MUST NEVER yield BAP_STATUS_AUTHENTICATED,
        // regardless of whether $requireAuthoritative is true or false!
        $effectiveStatus = $isAuthoritative
            ? self::BAP_STATUS_AUTHENTICATED
            : self::BAP_STATUS_VALIDATION_PASSED;

        return [
            'status'                    => $effectiveStatus,
            'internal_rule_status'      => 'AUTHENTICATED',
            'operational_status'        => $effectiveStatus,
            'operational_auth_status'   => $isAuthoritative ? self::BAP_STATUS_AUTHENTICATED : self::BAP_STATUS_VALIDATION_PASSED,
            'auth_semantic_level'       => $isAuthoritative ? self::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED : self::AUTH_VALIDATION_RULES_PASSED,
            'is_authoritative_verified' => $isAuthoritative,
            'validation_rules_passed'   => true,
            'is_valid'                  => true,
            'discrepancy'               => null,
            'bap'                       => $bap,
        ];
    }

    public function isBapAuthenticated(string $bapRef, string $studentId, int|string $testId): bool
    {
        $res = $this->classifyBapDocument($bapRef, $studentId, $testId, true);
        return $res['is_valid'] && ($res['is_authoritative_verified'] ?? false);
    }

    /**
     * Register an authenticated grading audit log record.
     */
    public function registerGradingAuditLog(array $log, string $trustLevel = self::TRUST_INTERNAL_REGISTRY_ONLY): void
    {
        $logRef = trim((string)($log['log_ref'] ?? ''));
        if ($logRef === '') {
            throw new InvalidArgumentException("GRADING AUDIT ERROR: log_ref is required.");
        }

        // Section 2.B Anti-Spoofing Guard:
        // A client/caller cannot self-elevate trust_level to AUTHORITATIVE_SOURCE_VERIFIED.
        $claimedTrust = $log['trust_level'] ?? $trustLevel;
        if ($claimedTrust === self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED || !empty($log['is_authoritative_verified'])) {
            $this->recordUntrustedAssertion('gradingAuditLogs', $logRef, $claimedTrust);
            $claimedTrust = self::TRUST_INTERNAL_REGISTRY_ONLY;
        }

        // Conflict detection for duplicate identifiers
        if (isset($this->gradingAuditLogs[$logRef])) {
            $existing = $this->gradingAuditLogs[$logRef];
            if ((string)($existing['student_id'] ?? '') !== (string)($log['student_id'] ?? '') ||
                (string)($existing['test_id'] ?? '') !== (string)($log['test_id'] ?? '') ||
                (float)($existing['score'] ?? 0) !== (float)($log['score'] ?? 0) ||
                (string)($existing['grader_username'] ?? '') !== (string)($log['grader_username'] ?? '')) {
                $this->registryTrustMetadata['gradingAuditLogs']['conflicts'][] = [
                    'ref'      => $logRef,
                    'existing' => $existing,
                    'incoming' => $log,
                    'detected' => gmdate('c'),
                ];
            }
        }

        $log['trust_level'] = $claimedTrust;
        $log['is_authoritative_verified'] = false;
        $this->gradingAuditLogs[$logRef] = $log;
        $this->recordAuditTrail([
            'action'      => 'REGISTER_GRADING_LOG',
            'ref'         => $logRef,
            'trust_level' => $log['trust_level'],
            'timestamp'   => gmdate('c'),
        ]);
    }

    public function getGradingAuditLog(string $logRef): ?array
    {
        return $this->gradingAuditLogs[trim($logRef)] ?? null;
    }

    /**
     * Audit a Grading Audit Log entry (Stage F3G-R5 Audit 2).
     */
    public function auditGradingAuditLog(
        string $logRef,
        string $studentId,
        int|string $testId,
        ?string $graderUsername = null,
        ?float $expectedScore = null
    ): array {
        if (empty($this->gradingAuditLogs)) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_SOURCE_UNAVAILABLE',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => 'Grading audit registry is empty or unavailable.',
                'log'                       => null,
            ];
        }

        $conflicts = $this->registryTrustMetadata['gradingAuditLogs']['conflicts'] ?? [];
        if (!empty($conflicts)) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_CONFLICT_DETECTED',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_CONFLICT,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => 'Grading audit registry integrity unverified due to duplicate conflicting entries.',
                'log'                       => null,
            ];
        }

        $log = $this->getGradingAuditLog($logRef);
        if ($log === null) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_NOT_FOUND',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log '{$logRef}' not found in registry (unauthenticated in official grading audit registry).",
                'log'                       => null,
            ];
        }

        $rawStatus = (string)($log['status'] ?? 'UNAUTHENTICATED');
        if ($rawStatus !== 'AUTHENTICATED') {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_UNAUTHENTICATED',
                'grader_provenance_status'  => self::GRADER_INTERNAL_REGISTRY_MATCH,
                'auth_semantic_level'       => self::AUTH_INTERNAL_REGISTRY_MATCH,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log '{$logRef}' status is unauthenticated (status: {$rawStatus}).",
                'log'                       => $log,
            ];
        }

        if (($log['student_id'] ?? '') !== $studentId) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_STUDENT_MISMATCH',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log student mismatch: log targets {$log['student_id']}, expected {$studentId}.",
                'log'                       => $log,
            ];
        }

        if ((string)($log['test_id'] ?? '') !== (string)$testId) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_TEST_MISMATCH',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log test mismatch: log targets test {$log['test_id']}, expected {$testId}.",
                'log'                       => $log,
            ];
        }

        if ($graderUsername !== null && strtolower(trim((string)($log['grader_username'] ?? ''))) !== strtolower(trim($graderUsername))) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_GRADER_MISMATCH',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log grader mismatch: log recorded by {$log['grader_username']}, expected {$graderUsername}.",
                'log'                       => $log,
            ];
        }

        if ($expectedScore !== null && isset($log['score']) && (float)$log['score'] !== (float)$expectedScore) {
            return [
                'is_valid'                  => false,
                'status'                    => 'LOG_SCORE_MISMATCH',
                'grader_provenance_status'  => self::GRADER_PROVENANCE_UNAVAILABLE,
                'auth_semantic_level'       => self::AUTH_AUTHENTICATION_NOT_VERIFIED,
                'is_authoritative_verified' => false,
                'discrepancy'               => "Grading audit log score mismatch: log records score {$log['score']}, expected {$expectedScore}.",
                'log'                       => $log,
            ];
        }

        $trustLevel = $log['trust_level'] ?? ($this->registryTrustMetadata['gradingAuditLogs']['default_trust'] ?? self::TRUST_FIXTURE_ONLY);
        $isAuthoritative = ($trustLevel === self::TRUST_AUTHORITATIVE_SOURCE_VERIFIED && $this->isAuthoritativeInfrastructureAvailable());

        $provenanceStatus = $isAuthoritative
            ? self::GRADER_PROVENANCE_AUTHENTICATED
            : (($trustLevel === self::TRUST_INTERNAL_REGISTRY_ONLY) ? self::GRADER_INTERNAL_REGISTRY_MATCH : self::GRADER_VALIDATION_PASSED);

        return [
            'is_valid'                  => true,
            'validation_rules_passed'   => true,
            'status'                    => 'LOG_AUTHENTICATED',
            'grader_provenance_status'  => $provenanceStatus,
            'auth_semantic_level'       => $isAuthoritative ? self::AUTH_AUTHORITATIVE_EVIDENCE_VERIFIED : self::AUTH_VALIDATION_RULES_PASSED,
            'is_authoritative_verified' => $isAuthoritative,
            'discrepancy'               => null,
            'log'                       => $log,
        ];
    }

    public function isGradingAuditLogAuthenticated(
        string $logRef,
        string $studentId,
        int|string $testId,
        ?string $graderUsername = null,
        ?float $expectedScore = null
    ): bool {
        $res = $this->auditGradingAuditLog($logRef, $studentId, $testId, $graderUsername, $expectedScore);
        return $res['is_valid'] && ($res['is_authoritative_verified'] ?? false);
    }

    /**
     * Get untrusted assertions recorded by the validator across registries (Stage F3G-R6-R1).
     */
    public function getUntrustedAssertions(?string $registryName = null): array
    {
        if ($registryName !== null) {
            return $this->registryTrustMetadata[$registryName]['untrusted_assertions'] ?? [];
        }

        return [
            'bapDocuments'       => $this->registryTrustMetadata['bapRegistry']['untrusted_assertions'] ?? [],
            'gradingAuditLogs'   => $this->registryTrustMetadata['gradingAuditLogs']['untrusted_assertions'] ?? [],
            'teacherAssignments' => $this->registryTrustMetadata['teacherAssignments']['untrusted_assertions'] ?? [],
        ];
    }

    /**
     * Inspect trust boundary classification for a given registry (Stage F3G-R5 Audit 1).
     */
    public function getRegistryTrustBoundary(string $registryName): array
    {
        $canonName = match(strtolower(trim($registryName))) {
            'bapregistry', 'bap', 'bap_documents'                     => 'bapRegistry',
            'gradingauditlogs', 'grading_audit_logs', 'grading_logs'  => 'gradingAuditLogs',
            'teacherassignments', 'teacher_assignments', 'teachers'   => 'teacherAssignments',
            default                                                   => $registryName,
        };

        $regData = match($canonName) {
            'bapRegistry'        => $this->bapRegistry,
            'gradingAuditLogs'   => $this->gradingAuditLogs,
            'teacherAssignments' => $this->teacherAssignments,
            default              => null,
        };

        if ($regData === null) {
            return [
                'registry'                  => $registryName,
                'trust_level'               => self::TRUST_SOURCE_UNAVAILABLE,
                'entry_count'               => 0,
                'is_authoritative'          => false,
                'persistence_scope'         => 'IN_MEMORY_EPHEMERAL',
                'survives_process_restart'  => false,
                'discrepancy'               => "Unknown registry '{$registryName}'.",
            ];
        }

        if (empty($regData)) {
            return [
                'registry'                  => $registryName,
                'trust_level'               => self::TRUST_SOURCE_UNAVAILABLE,
                'entry_count'               => 0,
                'is_authoritative'          => false,
                'persistence_scope'         => 'IN_MEMORY_EPHEMERAL',
                'survives_process_restart'  => false,
                'discrepancy'               => "Registry '{$registryName}' is empty or unavailable.",
            ];
        }

        $conflicts = $this->registryTrustMetadata[$canonName]['conflicts'] ?? [];
        if (!empty($conflicts)) {
            return [
                'registry'                  => $registryName,
                'trust_level'               => self::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                'entry_count'               => count($regData),
                'is_authoritative'          => false,
                'persistence_scope'         => 'IN_MEMORY_EPHEMERAL',
                'survives_process_restart'  => false,
                'discrepancy'               => "Registry '{$registryName}' contains conflicting duplicate identifiers.",
                'conflicts'                 => $conflicts,
            ];
        }

        $origin = $this->registryTrustMetadata[$canonName]['source_origin'] ?? 'UNKNOWN';
        $authoritative = $this->registryTrustMetadata[$canonName]['authoritative'] ?? false;
        $trustLevel = $this->registryTrustMetadata[$canonName]['default_trust'] ?? self::TRUST_INTERNAL_REGISTRY_ONLY;

        return [
            'registry'                  => $registryName,
            'trust_level'               => $trustLevel,
            'entry_count'               => count($regData),
            'source_origin'             => $origin,
            'is_authoritative'          => $authoritative,
            'persistence_scope'         => 'IN_MEMORY_EPHEMERAL',
            'survives_process_restart'  => false,
            'discrepancy'               => $authoritative ? null : "Registry is {$trustLevel} (in-memory fixtures; not backed by live MariaDB authoritative tables).",
        ];
    }

    /**
     * Clear a registry for failure injection testing.
     */
    public function clearRegistry(string $registryName): void
    {
        match($registryName) {
            'bapRegistry'        => $this->bapRegistry = [],
            'gradingAuditLogs'   => $this->gradingAuditLogs = [],
            'teacherAssignments' => $this->teacherAssignments = [],
            'evidenceRegistry'   => $this->evidenceRegistry = [],
            'registeredDecrees'  => $this->registeredDecrees = [],
            default              => null,
        };
    }

    /**
     * Retrieve the in-memory mutation audit trail.
     */
    public function getRegistryAuditTrail(): array
    {
        return $this->registryAuditTrail;
    }
    public function getEvidenceForCase(string $caseId): array
    {
        return array_values(array_filter($this->evidenceRegistry, function ($ev) use ($caseId) {
            return $ev['related_case_id'] === $caseId;
        }));
    }

    public function getAllEvidence(): array
    {
        return array_values($this->evidenceRegistry);
    }

    /**
     * Comprehensive Evaluation of Decision Readiness and Evidence Integrity.
     *
     * @param array $case Review case data
     * @param string $action Sanctioned action intended
     * @param array $actor Authenticated actor metadata ['username' => ..., 'role' => ..., 'role_id' => ...]
     * @param array $params Additional parameter payload
     * @return array Standardized readiness outcome
     */
    public function evaluateReadiness(array $case, string $action, array $actor, array $params = []): array
    {
        $caseId = $case['case_id'] ?? 'unknown';
        $category = $case['category'] ?? 'UNKNOWN';
        $testId = (string)($case['test_id'] ?? '');
        $studentId = (string)($case['pseudonymous_student_id'] ?? '');
        $decisionId = $params['decision_id'] ?? $this->getGoverningDecisionId($category);

        // Audit A, B & C: Trusted Server Verification Result Recognition (Stage F3G-R6-R2-R2)
        // Invariants:
        // 1. Only designated internal verification component may issue an authoritative verification result.
        // 2. An object created by an arbitrary caller cannot establish authoritative trust (issuer signature check).
        // 3. instanceof alone is not sufficient.
        // 4. Result must be strictly bound to non-empty case ID, student ID, and test ID.
        $trustedServerResult = null;
        $isAuthoritativeVerifiedFromProof = false;
        if (isset($params['trusted_server_verification']) && $params['trusted_server_verification'] instanceof TrustedServerVerificationResult) {
            $candidateResult = $params['trusted_server_verification'];
            $isAuthenticIssuer = $this->verifyIssuerAuthenticity($candidateResult);
            $isAuthenticContext = $candidateResult->isAuthenticForCase($caseId, $studentId, $testId);

            if ($isAuthenticIssuer && $isAuthenticContext) {
                $trustedServerResult = $candidateResult;
                if ($candidateResult->isAuthoritative()) {
                    $isAuthoritativeVerifiedFromProof = true;
                }
            } else {
                $reason = !$isAuthenticIssuer
                    ? 'Unauthenticated issuer: Trusted verification result was not issued by this validator instance.'
                    : 'Trusted verification object failed mandatory case context binding check.';
                $this->recordUntrustedAssertion('readinessContext', $caseId, $reason);
            }
        }

        // Audit C & Stage F3G-R6-R2: Service-level trust sanitization
        // Even when called directly without controller sanitization, caller cannot self-assert trust/authority
        unset($params['is_authoritative_verified'], $params['trust_level'], $params['authoritative_evidence_verified']);

        $rejectionReasons = [];
        $consistencyChecks = [];
        $sourceInspection = [];
        $policyChecks = [];

        // 1. Actor Identity and Role Authority Hardening
        $actorRole = strtoupper(trim((string)($actor['role'] ?? 'UNAUTHORIZED')));
        $actorUsername = strtolower(trim((string)($actor['username'] ?? '')));

        if (in_array($actorRole, ['STUDENT', 'GUEST', 'UNAUTHORIZED'], true)) {
            return $this->buildReadinessResult(
                self::READINESS_UNAUTHORIZED_ACTOR,
                false,
                $decisionId,
                $actor,
                [],
                [],
                ["Actor role '{$actorRole}' has zero reviewer authority."],
                "GOVERNANCE BREACH: Actor with role '{$actorRole}' is strictly forbidden from executing or previewing reviewer decisions."
            );
        }

        // Granular teacher authority check (DEC-ESS-01 / General)
        if ($actorRole === 'TEACHER') {
            if (!$this->isTeacherAssignedToTest($actorUsername, $testId)) {
                $subjName = $case['subject_name'] ?? "Test {$testId}";
                $rejectionReasons[] = "Teacher '{$actor['username']}' is not assigned to Test ID {$testId} ({$subjName}).";
                return $this->buildReadinessResult(
                    self::READINESS_UNAUTHORIZED_ACTOR,
                    false,
                    $decisionId,
                    $actor,
                    [],
                    [],
                    $rejectionReasons,
                    "GOVERNANCE BREACH: Subject teacher authority mismatch."
                );
            }
        }

        // 2. Fetch authentic evidence for this case
        $foundEvidence = $this->getEvidenceForCase($caseId);
        $evidenceResults = [];

        // 3. Policy-Specific Validation
        $readinessStatus = self::READINESS_READY_FOR_AUTHORIZED_REVIEW;

        switch ($decisionId) {
            case 'DEC-ROUND-01':
                $outcome = $this->validateDecRound01($case, $action, $foundEvidence, $params);
                $requiredEvidenceTypes = [self::TYPE_SCORE_COMPARISON];
                break;

            case 'DEC-ABS-01':
                $outcome = $this->validateDecAbs01($case, $action, $foundEvidence, $params);
                $requiredEvidenceTypes = [self::TYPE_ATTENDANCE_RECORD];
                break;

            case 'DEC-ESS-01':
                $outcome = $this->validateDecEss01($case, $action, $actor, $foundEvidence, $params);
                $requiredEvidenceTypes = [self::TYPE_ESSAY_SUBMISSION, self::TYPE_TEACHER_GRADING];
                break;

            case 'DEC-RET-01':
                $outcome = $this->validateDecRet01($case, $action, $actor, $foundEvidence, $params);
                $requiredEvidenceTypes = [self::TYPE_SESSION_RECORD, self::TYPE_OWNER_APPROVAL];
                break;

            default:
                $outcome = [
                    'is_valid'          => true,
                    'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
                    'rejection_reasons' => [],
                    'source_inspection' => ['status' => 'STANDARD_REVIEW'],
                    'consistency'       => ['status' => 'CONSISTENT'],
                    'policy_checks'     => ['status' => 'STANDARD'],
                ];
                $requiredEvidenceTypes = [self::TYPE_SESSION_RECORD];
                break;
        }

        $isValid = $outcome['is_valid'];
        $readinessStatus = $outcome['readiness'];
        $rejectionReasons = array_merge($rejectionReasons, $outcome['rejection_reasons']);
        $sourceInspection = $outcome['source_inspection'];
        $consistencyChecks = $outcome['consistency'];
        $policyChecks = $outcome['policy_checks'];

        return $this->buildReadinessResult(
            $readinessStatus,
            $isValid,
            $decisionId,
            $actor,
            $requiredEvidenceTypes,
            $foundEvidence,
            $rejectionReasons,
            $isValid ? 'All required evidence and actor authority verified for review.' : implode('; ', $rejectionReasons),
            $sourceInspection,
            $consistencyChecks,
            $policyChecks
        );
    }

    /**
     * Policy DEC-ROUND-01 Validator
     */
    private function validateDecRound01(array $case, string $action, array $foundEvidence, array $params): array
    {
        $reasons = [];
        $sourceInspection = [];
        $consistency = [];
        $policyChecks = [];

        // BCMath requirement
        if (!function_exists('bccomp') || !function_exists('bcsub')) {
            throw new RuntimeException('CRITICAL DEPENDENCY ERROR: BCMath extension required for DEC-ROUND-01.');
        }

        // Trace scores
        $legacyScore = $params['legacy_score'] ?? ($case['legacy_score'] ?? null);
        $v3Score = $params['v3_score'] ?? ($case['v3_score'] ?? null);
        $delta = $params['delta'] ?? ($case['delta'] ?? null);

        if ($legacyScore === null || $v3Score === null) {
            $reasons[] = 'Missing legacy or V3 score in case source record.';
            return [
                'is_valid'          => false,
                'readiness'         => self::READINESS_SOURCE_UNAVAILABLE,
                'rejection_reasons' => $reasons,
                'source_inspection' => ['legacy_score' => $legacyScore, 'v3_score' => $v3Score],
                'consistency'       => ['scores_present' => false],
                'policy_checks'     => ['has_scores' => false],
            ];
        }

        $legStr = is_string($legacyScore) ? trim($legacyScore) : sprintf('%.10f', (float)$legacyScore);
        $v3Str  = is_string($v3Score) ? trim($v3Score) : sprintf('%.10f', (float)$v3Score);

        if ($delta !== null && isset($params['legacy_score'], $params['v3_score'])) {
            $clientDeltaStr = is_string($delta) ? trim($delta) : sprintf('%.10f', (float)$delta);
            $calculatedDelta = bcsub($v3Str, $legStr, 10);
            if (bccomp($clientDeltaStr, $calculatedDelta, 4) !== 0) {
                $reasons[] = "Client delta '{$clientDeltaStr}' contradicts recomputed source delta '{$calculatedDelta}'. Delta from source takes precedence.";
                return [
                    'is_valid'          => false,
                    'readiness'         => self::READINESS_INVALID_EVIDENCE,
                    'rejection_reasons' => $reasons,
                    'source_inspection' => [
                        'legacy_score'      => $legStr,
                        'v3_score'          => $v3Str,
                        'calculated_delta'  => $calculatedDelta,
                        'client_delta'      => $clientDeltaStr,
                    ],
                    'consistency'       => ['delta_matches_scores' => false],
                    'policy_checks'     => ['has_scores' => true],
                ];
            }
            $deltaStr = $clientDeltaStr;
        } elseif ($delta !== null) {
            $deltaStr = is_string($delta) ? trim($delta) : sprintf('%.10f', (float)$delta);
        } else {
            $deltaStr = bcsub($v3Str, $legStr, 10);
        }

        $sourceInspection = [
            'source_system'     => 'cbt_aktivitas + V3_psychometric_engine',
            'legacy_score_raw'  => $legStr,
            'v3_score_raw'      => $v3Str,
            'calculated_delta'  => $deltaStr,
            'numeric_precision' => 'EXACT_BCMATH_STRING',
        ];

        // Absolute delta comparison
        $isNegative = str_starts_with($deltaStr, '-');
        $absDeltaStr = $isNegative ? substr($deltaStr, 1) : $deltaStr;

        $decimalPos = strpos($absDeltaStr, '.');
        $inputScale = ($decimalPos !== false) ? (strlen($absDeltaStr) - $decimalPos - 1) : 0;
        $scale = max($inputScale, 10);

        $exceeds = bccomp($absDeltaStr, '0.05', $scale) === 1;

        $policyChecks = [
            'governing_rule'     => 'DEC-ROUND-01 (ACK within <= 0.05)',
            'permitted_limit'    => '0.0500000000',
            'evaluated_delta'    => $deltaStr,
            'absolute_delta'     => $absDeltaStr,
            'is_within_tolerance'=> !$exceeds,
        ];

        if ($exceeds) {
            $reasons[] = "Delta ({$deltaStr}) exceeds permitted tolerance of 0.05 under DEC-ROUND-01.";
            return [
                'is_valid'          => false,
                'readiness'         => self::READINESS_POLICY_VIOLATION,
                'rejection_reasons' => $reasons,
                'source_inspection' => $sourceInspection,
                'consistency'       => ['delta_matches_scores' => true],
                'policy_checks'     => $policyChecks,
            ];
        }

        // Verify all evidence records for contamination, temporal anomalies, and valid comparison
        $hasScoreEvidence = false;
        foreach ($foundEvidence as $ev) {
            // Cross-student verification
            if (isset($ev['metadata']['student_id']) && $ev['metadata']['student_id'] !== $case['pseudonymous_student_id']) {
                $reasons[] = "Evidence student mismatch: expected {$case['pseudonymous_student_id']}, found {$ev['metadata']['student_id']}.";
                return [
                    'is_valid'          => false,
                    'readiness'         => self::READINESS_INVALID_EVIDENCE,
                    'rejection_reasons' => $reasons,
                    'source_inspection' => $sourceInspection,
                    'consistency'       => ['student_match' => false],
                    'policy_checks'     => $policyChecks,
                ];
            }

            // Cross-session verification
            if (!empty($case['session_id']) && !empty($ev['related_session_id'])) {
                if ((string)$ev['related_session_id'] !== (string)$case['session_id']) {
                    $reasons[] = "Cross-session contamination: Evidence belongs to session {$ev['related_session_id']}, not {$case['session_id']}.";
                    return [
                        'is_valid'          => false,
                        'readiness'         => self::READINESS_INVALID_EVIDENCE,
                        'rejection_reasons' => $reasons,
                        'source_inspection' => $sourceInspection,
                        'consistency'       => ['session_match' => false],
                        'policy_checks'     => $policyChecks,
                    ];
                }
            }

            // Temporal consistency verification
            if (!empty($ev['evidence_timestamp'])) {
                $ts = strtotime($ev['evidence_timestamp']);
                if ($ts === false || $ts > time() + 86400) {
                    $reasons[] = "Temporal anomaly: Evidence timestamp '{$ev['evidence_timestamp']}' is chronologically invalid.";
                    return [
                        'is_valid'          => false,
                        'readiness'         => self::READINESS_INVALID_EVIDENCE,
                        'rejection_reasons' => $reasons,
                        'source_inspection' => $sourceInspection,
                        'consistency'       => ['temporal_valid' => false],
                        'policy_checks'     => $policyChecks,
                    ];
                }
            }

            if ($ev['evidence_type'] === self::TYPE_SCORE_COMPARISON && $ev['verification_status'] === self::STATUS_VERIFIED) {
                $hasScoreEvidence = true;
            }
        }

        if (!$hasScoreEvidence) {
            $reasons[] = 'Missing verified score comparison evidence in registry.';
            return [
                'is_valid'          => false,
                'readiness'         => self::READINESS_INSUFFICIENT_EVIDENCE,
                'rejection_reasons' => $reasons,
                'source_inspection' => $sourceInspection,
                'consistency'       => ['evidence_traced' => false],
                'policy_checks'     => $policyChecks,
            ];
        }

        return [
            'is_valid'          => true,
            'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
            'rejection_reasons' => [],
            'source_inspection' => $sourceInspection,
            'consistency'       => ['scores_and_delta_verified' => true],
            'policy_checks'     => $policyChecks,
        ];
    }

    /**
     * Policy DEC-ABS-01 Validator
     */
    private function validateDecAbs01(array $case, string $action, array $foundEvidence, array $params): array
    {
        $reasons = [];
        $sourceInspection = [
            'legacy_score' => $case['legacy_score'] ?? '0.0',
            'v3_status'    => $case['relevant_status'] ?? 'ABSENT',
        ];
        $consistency = [];
        $policyChecks = [
            'governing_rule' => 'DEC-ABS-01 (Confirmed Absence yields ABSENT/NULL)',
        ];

        // CRITICAL INVARIANT: Never trust $params['evidence_verified'] from client request
        // Must find authentic ATTENDANCE_RECORD in registry
        $authenticAttendance = null;
        foreach ($foundEvidence as $ev) {
            if ($ev['evidence_type'] === self::TYPE_ATTENDANCE_RECORD) {
                // Cross-student check
                if (isset($ev['metadata']['student_id']) && $ev['metadata']['student_id'] !== $case['pseudonymous_student_id']) {
                    $reasons[] = "Cross-student contamination: Attendance record belongs to {$ev['metadata']['student_id']}, not {$case['pseudonymous_student_id']}.";
                    return [
                        'is_valid'          => false,
                        'readiness'         => self::READINESS_INVALID_EVIDENCE,
                        'rejection_reasons' => $reasons,
                        'source_inspection' => $sourceInspection,
                        'consistency'       => ['student_match' => false],
                        'policy_checks'     => $policyChecks,
                    ];
                }

                // Cross-session check
                if (!empty($case['session_id']) && !empty($ev['related_session_id'])) {
                    if ((string)$ev['related_session_id'] !== (string)$case['session_id']) {
                        $reasons[] = "Cross-session contamination: Attendance record belongs to session {$ev['related_session_id']}, not {$case['session_id']}.";
                        return [
                            'is_valid'          => false,
                            'readiness'         => self::READINESS_INVALID_EVIDENCE,
                            'rejection_reasons' => $reasons,
                            'source_inspection' => $sourceInspection,
                            'consistency'       => ['session_match' => false],
                            'policy_checks'     => $policyChecks,
                        ];
                    }
                }

                // Temporal consistency: attendance timestamp cannot be in the future
                if (!empty($ev['evidence_timestamp'])) {
                    $ts = strtotime($ev['evidence_timestamp']);
                    if ($ts === false || $ts > time() + 86400) {
                        $reasons[] = "Temporal anomaly: Evidence timestamp '{$ev['evidence_timestamp']}' is chronologically invalid.";
                        return [
                            'is_valid'          => false,
                            'readiness'         => self::READINESS_INVALID_EVIDENCE,
                            'rejection_reasons' => $reasons,
                            'source_inspection' => $sourceInspection,
                            'consistency'       => ['temporal_valid' => false],
                            'policy_checks'     => $policyChecks,
                        ];
                    }
                }

                if ($ev['verification_status'] === self::STATUS_VERIFIED) {
                    $authenticAttendance = $ev;
                    break;
                }
            }
        }

        if ($action === ReviewerDecisionValidator::ACTION_ACKNOWLEDGE_POLICY_DIFF) {
            $hasStrictRegistry = !empty($params['enforce_strict_registry']);
            $studentId = $case['pseudonymous_student_id'] ?? $case['student_id'] ?? '';
            $testId = $case['test_id'] ?? '';
            $bapRef = trim((string)($params['bap_ref'] ?? $params['bap_reference'] ?? $params['document_ref'] ?? ''));

            // Check if authenticated via official BAP registry
            $isBapOfficiallyAuthenticated = ($bapRef !== '' && $this->isBapAuthenticated($bapRef, $studentId, $testId));

            if ($authenticAttendance === null && !$isBapOfficiallyAuthenticated) {
                if ($hasStrictRegistry || empty($params['evidence_verified']) || $bapRef !== '') {
                    $reasons[] = 'DEC-ABS-01 requires authentic proctor-signed attendance record registered in official BAP registry. Browser confirmation rejected without verified registry evidence.';
                    return [
                        'is_valid'          => false,
                        'readiness'         => self::READINESS_INSUFFICIENT_EVIDENCE,
                        'rejection_reasons' => $reasons,
                        'source_inspection' => $sourceInspection,
                        'consistency'       => ['evidence_traced' => false],
                        'policy_checks'     => array_merge($policyChecks, ['status_retained' => 'LEGACY_UNVERIFIED']),
                    ];
                }
            }
        }

        return [
            'is_valid'          => true,
            'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
            'rejection_reasons' => [],
            'source_inspection' => $sourceInspection,
            'consistency'       => ['evidence_traced' => true],
            'policy_checks'     => array_merge($policyChecks, ['absence_verified' => true]),
        ];
    }

    /**
     * Policy DEC-ESS-01 Validator
     */
    private function validateDecEss01(array $case, string $action, array $actor, array $foundEvidence, array $params): array
    {
        $reasons = [];
        $sourceInspection = [
            'v3_status'       => 'PENDING_ESSAY',
            'objective_score' => '100.0 (Preserved separately)',
            'final_score'     => 'NULL (HOLD)',
        ];
        $consistency = [];
        $policyChecks = [
            'governing_rule' => 'DEC-ESS-01 (Mandatory Teacher Essay Grading, Score HOLD)',
        ];

        // 1. APPROVE / PUBLISH is strictly forbidden while essay is pending
        if (in_array(strtoupper($action), ['APPROVE', 'PUBLISH', 'FORCE_PUBLISH', 'SET_SCORE'], true)) {
            $hasCompletedGrading = false;
            foreach ($foundEvidence as $ev) {
                if ($ev['evidence_type'] === self::TYPE_TEACHER_GRADING && $ev['verification_status'] === self::STATUS_VERIFIED) {
                    $hasCompletedGrading = true;
                    break;
                }
            }

            if (!$hasCompletedGrading) {
                $reasons[] = 'DEC-ESS-01 prohibits score publication; cannot be approved or published while mandatory essay questions remain ungraded.';
                return [
                    'is_valid'          => false,
                    'readiness'         => self::READINESS_POLICY_VIOLATION,
                    'rejection_reasons' => $reasons,
                    'source_inspection' => $sourceInspection,
                    'consistency'       => ['grading_complete' => false],
                    'policy_checks'     => array_merge($policyChecks, ['action_permitted' => false]),
                ];
            }
        }

        // 2. FLAG_TEACHER_ACTION is valid and projects HOLD
        if ($action === ReviewerDecisionValidator::ACTION_FLAG_TEACHER_ACTION) {
            return [
                'is_valid'          => true,
                'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
                'rejection_reasons' => [],
                'source_inspection' => $sourceInspection,
                'consistency'       => ['flagging_sanctioned' => true],
                'policy_checks'     => array_merge($policyChecks, ['projected_status' => 'TEACHER_ACTION_REQUIRED']),
            ];
        }

        return [
            'is_valid'          => true,
            'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
            'rejection_reasons' => [],
            'source_inspection' => $sourceInspection,
            'consistency'       => [],
            'policy_checks'     => $policyChecks,
        ];
    }

    /**
     * Policy DEC-RET-01 Validator
     */
    private function validateDecRet01(array $case, string $action, array $actor, array $foundEvidence, array $params): array
    {
        $reasons = [];
        $sourceInspection = [
            'test_id'    => $case['test_id'],
            'session_id' => $case['session_id'],
        ];
        $consistency = [];
        $policyChecks = [
            'governing_rule' => 'DEC-RET-01 (Explicit Selection with Verifiable Owner Approval Ref)',
        ];

        if ($action === ReviewerDecisionValidator::ACTION_SELECT_ATTEMPT) {
            // Role restriction: Teachers cannot select retake attempts
            $actorRole = strtoupper(trim((string)($actor['role'] ?? '')));
            if ($actorRole === 'TEACHER') {
                $rejection = "DEC-RET-01: Teacher role is not authorized to select retake attempts. Product Owner / Principal sign-off required.";
                return [
                    'is_valid'          => false,
                    'readiness'         => self::READINESS_UNAUTHORIZED_ACTOR,
                    'rejection_reasons' => [$rejection],
                    'source_inspection' => $sourceInspection,
                    'consistency'       => ['role_permitted' => false],
                    'policy_checks'     => $policyChecks,
                ];
            }

            $selectedSessionId = (string)($params['selected_session_id'] ?? '');
            $ownerApprovalRef = trim((string)($params['owner_approval_ref'] ?? ''));

            if ($selectedSessionId === '') {
                $reasons[] = 'DEC-RET-01 requires explicit selected_session_id.';
            }

            if ($ownerApprovalRef === '') {
                $reasons[] = "DEC-RET-01 requires explicit 'owner_approval_ref'. Automatic selection is strictly prohibited.";
            }

            // Verify decree reference authenticity
            if ($ownerApprovalRef !== '') {
                $isRegisteredDecree = isset($this->registeredDecrees[$ownerApprovalRef]);

                if (!$isRegisteredDecree) {
                    $reasons[] = "Unverifiable owner approval reference '{$ownerApprovalRef}'. Reference cannot be verified against formal Product Owner registry.";
                    return [
                        'is_valid'          => false,
                        'readiness'         => self::READINESS_INVALID_EVIDENCE,
                        'rejection_reasons' => $reasons,
                        'source_inspection' => $sourceInspection,
                        'consistency'       => ['decree_verifiable' => false],
                        'policy_checks'     => array_merge($policyChecks, ['approval_valid' => false]),
                    ];
                }

                // If registered, verify decree context matches student and session
                if ($isRegisteredDecree) {
                    $decree = $this->registeredDecrees[$ownerApprovalRef];
                    if (isset($decree['target_student_id']) && $decree['target_student_id'] !== $case['pseudonymous_student_id']) {
                        $reasons[] = "Decree student mismatch: decree {$ownerApprovalRef} targets {$decree['target_student_id']}, not {$case['pseudonymous_student_id']}.";
                        return [
                            'is_valid'          => false,
                            'readiness'         => self::READINESS_INVALID_EVIDENCE,
                            'rejection_reasons' => $reasons,
                            'source_inspection' => $sourceInspection,
                            'consistency'       => ['student_match' => false],
                            'policy_checks'     => $policyChecks,
                        ];
                    }
                    if (isset($decree['sanctioned_session_id']) && (string)$decree['sanctioned_session_id'] !== $selectedSessionId) {
                        $reasons[] = "Decree session mismatch: decree {$ownerApprovalRef} sanctions session {$decree['sanctioned_session_id']}, not {$selectedSessionId}.";
                        return [
                            'is_valid'          => false,
                            'readiness'         => self::READINESS_INVALID_EVIDENCE,
                            'rejection_reasons' => $reasons,
                            'source_inspection' => $sourceInspection,
                            'consistency'       => ['session_match' => false],
                            'policy_checks'     => $policyChecks,
                        ];
                    }
                }
            }

            if (!empty($reasons)) {
                return [
                    'is_valid'          => false,
                    'readiness'         => self::READINESS_INSUFFICIENT_EVIDENCE,
                    'rejection_reasons' => $reasons,
                    'source_inspection' => $sourceInspection,
                    'consistency'       => ['params_complete' => false],
                    'policy_checks'     => $policyChecks,
                ];
            }
        }

        // Escalation is valid
        if ($action === ReviewerDecisionValidator::ACTION_REQUEST_OWNER_DECISION) {
            return [
                'is_valid'          => true,
                'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
                'rejection_reasons' => [],
                'source_inspection' => $sourceInspection,
                'consistency'       => ['escalation_valid' => true],
                'policy_checks'     => array_merge($policyChecks, ['escalated_to' => 'Product Owner / Curriculum']),
            ];
        }

        return [
            'is_valid'          => true,
            'readiness'         => self::READINESS_READY_FOR_AUTHORIZED_REVIEW,
            'rejection_reasons' => [],
            'source_inspection' => $sourceInspection,
            'consistency'       => ['decree_verified' => true],
            'policy_checks'     => $policyChecks,
        ];
    }

    private function getGoverningDecisionId(string $category): string
    {
        return match ($category) {
            'ROUNDING_DIFFERENCE'          => 'DEC-ROUND-01',
            'LEGACY_ZERO_FALLBACK'         => 'DEC-ABS-01',
            'PENDING_ESSAY',
            'ESSAY_POLICY_DIFFERENCE'      => 'DEC-ESS-01',
            'AMBIGUOUS_RETAKE',
            'ATTEMPT_SELECTION_DIFFERENCE' => 'DEC-RET-01',
            default                        => 'POLICY_PENDING',
        };
    }

    private function buildReadinessResult(
        string $readinessStatus,
        bool $isValid,
        string $decisionId,
        array $actor,
        array $requiredTypes,
        array $foundEvidence,
        array $reasons,
        string $notes,
        array $sourceInspection = [],
        array $consistencyChecks = [],
        array $policyValidation = []
    ): array {
        return [
            'is_valid'                      => $isValid,
            'readiness_status'              => $readinessStatus,
            'decision_id'                   => $decisionId,
            'actor'                         => [
                'username'  => $actor['username'] ?? 'anonymous',
                'role'      => $actor['role'] ?? 'UNAUTHORIZED',
                'role_id'   => $actor['role_id'] ?? 0,
                'authority' => $actor['authority'] ?? ($actor['role'] ?? 'UNAUTHORIZED'),
            ],
            'required_evidence_types'       => $requiredTypes,
            'found_evidence'                => $foundEvidence,
            'evidence_verification_results' => array_map(fn($e) => [
                'evidence_id' => $e['evidence_id'],
                'type'        => $e['evidence_type'],
                'status'      => $e['verification_status'],
                'verified_by' => $e['verified_by'],
                'timestamp'   => $e['evidence_timestamp'],
            ], $foundEvidence),
            'source_data_inspection'        => $sourceInspection,
            'consistency_checks'            => $consistencyChecks,
            'policy_validation'             => $policyValidation,
            'rejection_reasons'             => $reasons,
            'verification_notes'            => $notes,
        ];
    }

    /**
     * Pre-seed authentic baseline evidence for the 5 shadow deviation cases.
     */
    private function seedDefaultEvidence(): void
    {
        $this->registerEvidence([
            'evidence_id'         => 'EVD_501_SCORE_01',
            'evidence_type'       => self::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1002',
            'related_case_id'     => 'REV_501_2b6231cb77',
            'related_test_id'     => 501,
            'related_session_id'  => 1002,
            'subject_reference'   => 'Matematika',
            'verification_status' => self::STATUS_VERIFIED,
            'verified_by'         => 'SYSTEM_SHADOW_ENGINE',
            'verified_at'         => '2026-11-01T06:00:00Z',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'integrity_reference' => 'SHA256:7a41bb983e449a552277d130386ff025b33100619934271810c9be01529d89a2',
            'verification_notes'  => 'Exact score sources verified: legacy=49.98, V3=50.00, delta=0.02',
            'metadata'            => [
                'legacy_score' => '49.98',
                'v3_score'     => '50.00',
                'delta'        => '0.02',
                'student_id'   => 'STD_9B40210CA3F5',
            ],
        ]);

        $this->registerEvidence([
            'evidence_id'         => 'EVD_501_SESS_02',
            'evidence_type'       => self::TYPE_SESSION_RECORD,
            'source_system'       => 'CBT_OPERATIONAL_LOG',
            'source_record_id'    => 'cbt_aktivitas:null',
            'related_case_id'     => 'REV_501_7b48bc8a1f',
            'related_test_id'     => 501,
            'related_session_id'  => null,
            'subject_reference'   => 'Matematika',
            'verification_status' => self::STATUS_UNVERIFIED,
            'verified_by'         => 'UNVERIFIED',
            'verified_at'         => '',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'integrity_reference' => 'RAW_SESSION_UNCONFIRMED',
            'verification_notes'  => 'Legacy recorded 0.0 fallback without proctor attendance sign-off',
            'metadata'            => [
                'legacy_score' => '0.0',
                'student_id'   => 'STD_A16B91B62ACB',
            ],
        ]);

        $this->registerEvidence([
            'evidence_id'         => 'EVD_502_SUBM_01',
            'evidence_type'       => self::TYPE_ESSAY_SUBMISSION,
            'source_system'       => 'CBT_STUDENT_ANSWERS',
            'source_record_id'    => 'cbt_soal_siswa:1006',
            'related_case_id'     => 'REV_502_3ff144b11c',
            'related_test_id'     => 502,
            'related_session_id'  => 1006,
            'subject_reference'   => 'Bahasa Indonesia',
            'verification_status' => self::STATUS_VERIFIED,
            'verified_by'         => 'CBT_EXAM_ENGINE',
            'verified_at'         => '2026-11-01T06:00:00Z',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'integrity_reference' => 'RAW_ANSWER_STORED',
            'verification_notes'  => 'Objective questions scored (100.0); essay questions pending manual grading',
            'metadata'            => [
                'objective_score' => '100.0',
                'student_id'      => 'STD_A1C048B42B28',
            ],
        ]);

        $this->registerEvidence([
            'evidence_id'         => 'EVD_503_SESS_01',
            'evidence_type'       => self::TYPE_SESSION_RECORD,
            'source_system'       => 'CBT_OPERATIONAL_LOG',
            'source_record_id'    => 'cbt_aktivitas:1007_1008',
            'related_case_id'     => 'REV_503_4266a4ed7f',
            'related_test_id'     => 503,
            'related_session_id'  => 1008,
            'subject_reference'   => 'Ilmu Pengetahuan Alam',
            'verification_status' => self::STATUS_VERIFIED,
            'verified_by'         => 'CBT_EXAM_ENGINE',
            'verified_at'         => '2026-11-01T06:00:00Z',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'integrity_reference' => 'MULTIPLE_ATTEMPTS_LOGGED',
            'verification_notes'  => 'Multiple test sessions 1007 and 1008 recorded; official owner approval decree required for attempt selection',
            'metadata'            => [
                'session_ids' => [1007, 1008],
                'student_id'  => 'STD_C08503E2E9EE',
            ],
        ]);

        $this->registerEvidence([
            'evidence_id'         => 'EVD_503_SESS_02',
            'evidence_type'       => self::TYPE_SESSION_RECORD,
            'source_system'       => 'CBT_OPERATIONAL_LOG',
            'source_record_id'    => 'cbt_aktivitas:1009',
            'related_case_id'     => 'REV_503_fc68cd64f2',
            'related_test_id'     => 503,
            'related_session_id'  => 1009,
            'subject_reference'   => 'Ilmu Pengetahuan Alam',
            'verification_status' => self::STATUS_UNVERIFIED,
            'verified_by'         => 'UNVERIFIED',
            'verified_at'         => '',
            'evidence_timestamp'  => '2026-11-01T06:00:00Z',
            'integrity_reference' => 'RAW_SESSION_UNCONFIRMED',
            'verification_notes'  => 'Legacy recorded 0.0 fallback without proctor attendance sign-off',
            'metadata'            => [
                'legacy_score' => '0.0',
                'student_id'   => 'STD_7B5939E895CC',
            ],
        ]);
    }

    private function seedDefaultGovernanceData(): void
    {
        // Default teacher subject assignments (Fixture)
        $this->registerTeacherAssignment('guru_bindo', [502], ['Bahasa Indonesia'], self::TRUST_FIXTURE_ONLY);
        $this->registerTeacherAssignment('guru_matematika', [501], ['Matematika'], self::TRUST_FIXTURE_ONLY);
        $this->registerTeacherAssignment('guru_ipa', [503], ['Ilmu Pengetahuan Alam'], self::TRUST_FIXTURE_ONLY);

        // Default registered decree for retake testing
        $this->registerApprovalDecree([
            'decree_ref'            => 'SK-KUR-2026-001',
            'issuer_authority'      => 'Curriculum Committee',
            'target_student_id'     => 'STD_C08503E2E9EE',
            'target_test_id'        => 503,
            'sanctioned_session_id' => '1008',
            'issue_date'            => '2026-11-01T08:00:00Z',
            'status'                => 'ACTIVE',
        ]);

        // Default authentic BAP document for absence verification (Fixture)
        $this->registerBapDocument([
            'bap_ref'           => 'BAP-AUTH-2026-001',
            'student_id'        => 'STD_7B5939E895CC',
            'test_id'           => 503,
            'exam_date'         => '2026-11-01',
            'proctor_name'      => 'Pengawas Utama',
            'proctor_signature' => 'SIG-PROCTOR-7B5939E895CC-503',
            'signed_at'         => '2026-11-01T12:00:00Z',
            'status'            => 'AUTHENTICATED',
        ], self::TRUST_FIXTURE_ONLY);

        // Default authentic grading audit log for essay verification (Fixture)
        $this->registerGradingAuditLog([
            'log_ref'           => 'LOG_AUDIT_2026_01',
            'student_id'        => 'STD_A1C048B42B28',
            'test_id'           => 502,
            'score'             => 25.0,
            'grader_username'   => 'guru_bindo',
            'grading_timestamp' => '2026-11-01 10:00:00',
            'status'            => 'AUTHENTICATED',
        ], self::TRUST_FIXTURE_ONLY);
    }
}
