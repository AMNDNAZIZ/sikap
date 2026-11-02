<?php

namespace App\Services\Cbt\Review;

use PDO;
use Exception;
use InvalidArgumentException;
use RuntimeException;

/**
 * HistoricalSourceAdapter
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R1 Hardened
 * Non-destructive, read-only adapter bridging historical shadow references
 * (e.g. cbt_aktivitas, cbt_soal_siswa) to operational database tables
 * (cbt_sessions, cbt_answers, cbt_questions, cbt_test_status).
 *
 * GOVERNANCE RULES:
 * 1. Strict Read-Only: Only parameterized internal SELECT queries are executed; zero DDL, zero DML.
 * 2. Non-Destructive: Zero modifications to database state, schema, or permissions.
 * 3. Transparent Provenance: If operational records do not exist or differ,
 *    truthfully reports status without forced synthetic data reconstruction.
 * 4. Multi-Dimensional Readiness Architecture:
 *    - Decision Readiness (Policy & decree compliance)
 *    - Record Identity Readiness (PK/FK relational integrity)
 *    - Session Ownership Readiness (Proven non-ambiguous session-to-answer attribution)
 *    - Evidence Readiness (Physical authoritative live source verification)
 * 5. Academic Semantic Integrity:
 *    - Session ID != Answer ID (Disambiguated in Case REV_502).
 *    - cbt_answers lacks session_id; (student_id, test_id) yields SESSION_CONTEXT_MATCH,
 *      which is NEVER automatically promoted to SESSION_OWNERSHIP_VERIFIED.
 *    - In retakes with multiple sessions, ownership is SESSION_OWNERSHIP_AMBIGUOUS.
 *    - Strict Essay Semantics: NULL != 0.00; default 0.00 is DEFAULT_ZERO_UNVERIFIED;
 *      pending essay enforces HOLD on final_score and report_score while isolating objective_score.
 */
class HistoricalSourceAdapter
{
    // =========================================================================
    // 1. BACKWARD COMPATIBLE ADAPTER STATUSES (STAGE F3F)
    // =========================================================================
    public const STATUS_MAPPED_VERIFIED         = 'MAPPED_VERIFIED';
    public const STATUS_MAPPED_VALUE_MISMATCH   = 'MAPPED_VALUE_MISMATCH';
    public const STATUS_MAPPED_RECORD_NOT_FOUND = 'MAPPED_RECORD_NOT_FOUND';
    public const STATUS_UNMAPPED_SOURCE         = 'UNMAPPED_SOURCE';
    public const STATUS_UNMAPPED_AMBIGUOUS      = 'UNMAPPED_AMBIGUOUS';
    public const STATUS_UNVERIFIED_NO_DB        = 'UNVERIFIED_NO_DB';

    // =========================================================================
    // 2. FORMAL RECORD IDENTITY MAPPING STATUSES (STAGE F3G & F3G-R4 CONTRACT)
    // =========================================================================
    public const RECORD_STATUS_VERIFIED_RECORD_MATCH   = 'VERIFIED_RECORD_MATCH';
    public const RECORD_STATUS_RECORD_NOT_FOUND        = 'RECORD_NOT_FOUND';
    public const RECORD_STATUS_RECORD_VALUE_MISMATCH   = 'RECORD_VALUE_MISMATCH';
    public const RECORD_STATUS_RECORD_CONTEXT_MISMATCH = 'RECORD_CONTEXT_MISMATCH';
    public const RECORD_STATUS_RECORD_AMBIGUOUS        = 'RECORD_AMBIGUOUS';
    public const RECORD_STATUS_SOURCE_UNAVAILABLE      = 'SOURCE_UNAVAILABLE';
    public const RECORD_STATUS_MAPPING_UNPROVEN        = 'MAPPING_UNPROVEN';

    // Stage F3G-R4 Standardized Status Aliases
    public const RECORD_IDENTITY_VERIFIED              = 'RECORD_IDENTITY_VERIFIED';
    public const RECORD_CONTEXT_MATCH                  = 'RECORD_CONTEXT_MATCH';

    // =========================================================================
    // 2B. GRANULAR FAILURE & READINESS REASON CODES (STAGE F3G-R4 CONTRACT)
    // =========================================================================
    public const REASON_CASE_IDENTITY_CONFLICT         = 'CASE_IDENTITY_CONFLICT';
    public const REASON_SOURCE_RECORD_NOT_FOUND        = 'SOURCE_RECORD_NOT_FOUND';
    public const REASON_SOURCE_CONTEXT_ONLY            = 'SOURCE_CONTEXT_ONLY';
    public const REASON_SESSION_OWNERSHIP_UNPROVEN     = 'SESSION_OWNERSHIP_UNPROVEN';
    public const REASON_SESSION_OWNERSHIP_AMBIGUOUS    = 'SESSION_OWNERSHIP_AMBIGUOUS';
    public const REASON_GRADER_PROVENANCE_UNAVAILABLE  = 'GRADER_PROVENANCE_UNAVAILABLE';
    public const REASON_BAP_NOT_AUTHENTICATED          = 'BAP_NOT_AUTHENTICATED';
    public const REASON_POLICY_APPROVAL_MISSING        = 'POLICY_APPROVAL_MISSING';

    // =========================================================================
    // 3. SESSION OWNERSHIP STATUSES (STAGE F3G-R1 CONTRACT)
    // =========================================================================
    public const SESSION_CONTEXT_MATCH            = 'SESSION_CONTEXT_MATCH';
    public const SESSION_OWNERSHIP_VERIFIED        = 'SESSION_OWNERSHIP_VERIFIED';
    public const SESSION_OWNERSHIP_UNPROVEN        = 'SESSION_OWNERSHIP_UNPROVEN';
    public const SESSION_OWNERSHIP_AMBIGUOUS       = 'SESSION_OWNERSHIP_AMBIGUOUS';

    // =========================================================================
    // 4. ESSAY DATA SEMANTIC STATES (SECTION 5)
    // =========================================================================
    public const ESSAY_STATE_NULL                  = 'NULL';
    public const ESSAY_STATE_ZERO                  = '0.00';
    public const ESSAY_STATE_PENDING               = 'PENDING';
    public const ESSAY_STATE_SCORED                = 'SCORED';
    public const ESSAY_STATE_LEGACY_UNVERIFIED     = 'LEGACY_UNVERIFIED';
    public const ESSAY_STATE_NO_QUESTIONS          = 'NO_ESSAY_QUESTIONS';
    public const ESSAY_STATE_UNANSWERED            = 'UNANSWERED_ESSAY';
    public const ESSAY_STATE_PARTIALLY_SCORED      = 'PARTIALLY_SCORED';
    public const ESSAY_STATE_SCORED_POSITIVE       = 'SCORED_POSITIVE';
    public const ESSAY_STATE_SCORED_ALL_ZERO       = 'SCORED_ALL_ZERO';
    public const ESSAY_STATE_DEFAULT_ZERO          = 'DEFAULT_ZERO_UNVERIFIED';
    public const ESSAY_STATE_SCORE_PRESENT_UNVERIFIED = 'SCORE_PRESENT_UNVERIFIED';

    // =========================================================================
    // 5. ABSENCE EVIDENCE STATUSES (STAGE F3G-R3 CONTRACT)
    // =========================================================================
    public const ABSENCE_STATUS_EVIDENCE_MISSING            = 'ABSENCE_EVIDENCE_MISSING';
    public const ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED = 'ABSENCE_EVIDENCE_PRESENT_UNVERIFIED';
    public const ABSENCE_STATUS_VERIFIED                    = 'ABSENCE_VERIFIED';

    /**
     * Historical concept to operational table mapping dictionary (Concept Layer).
     */
    public const TABLE_MAPPING = [
        'cbt_aktivitas'  => 'cbt_sessions',
        'cbt_soal_siswa' => 'cbt_answers',
    ];

    /**
     * Strict Whitelist of Allowed Operational Database Tables.
     */
    public const ALLOWED_OPERATIONAL_TABLES = [
        'cbt_sessions',
        'cbt_answers',
        'cbt_questions',
        'cbt_question_banks',
        'cbt_test_status',
        'students',
        'teachers',
        'subjects',
        'classes',
    ];

    /**
     * Column mappings between historical concepts and operational physical schema.
     */
    public const COLUMN_MAPPING = [
        'cbt_aktivitas' => [
            'id'           => 'id',           // Session Primary Key
            'student_id'   => 'student_id',   // Student FK
            'test_id'      => 'test_id',      // Test Status FK
            'score'        => 'score',        // objective score
            'legacy_score' => 'score',        // objective score
            'essay_score'  => 'essay_score',  // essay score
            'total_score'  => 'total_score',  // total calculated score
            'status'       => 'status',       // 'active' / 'finished'
            'started_at'   => 'started_at',
            'finished_at'  => 'finished_at',
            'created_at'   => 'created_at',
            'updated_at'   => 'updated_at',
        ],
        'cbt_soal_siswa' => [
            'id'               => 'id',               // Answer Primary Key (NOT Session ID)
            'student_id'       => 'student_id',       // Student FK
            'test_id'          => 'test_id',          // Test Status FK
            'question_id'      => 'question_id',      // Question FK
            'answer'           => 'answer',
            'score'            => 'score',            // float or NULL for pending essay
            'updated_at_micro' => 'updated_at_micro',
            'version'          => 'version',
            'is_doubtful'      => 'is_doubtful',
            'updated_at'       => 'updated_at',
        ],
    ];

    private ?PDO $pdo;

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo;
    }

    public function setPdo(?PDO $pdo): self
    {
        $this->pdo = $pdo;
        return $this;
    }

    public function getPdo(): ?PDO
    {
        return $this->pdo;
    }

    // =========================================================================
    // LAYER 1: CONCEPT MAPPING METHODS
    // =========================================================================

    public function getTableMapping(): array
    {
        return self::TABLE_MAPPING;
    }

    public function getConceptMapping(string $historicalConcept): ?string
    {
        return self::TABLE_MAPPING[$historicalConcept] ?? null;
    }

    public function getColumnMapping(string $historicalTable): array
    {
        return self::COLUMN_MAPPING[$historicalTable] ?? [];
    }

    public function explainConcept(string $historicalConcept): array
    {
        return match ($historicalConcept) {
            'cbt_aktivitas' => [
                'concept_name'           => 'Exam Activity / Session',
                'operational_table'      => 'cbt_sessions',
                'primary_key'            => 'id',
                'foreign_keys'           => ['student_id' => 'students.id', 'test_id' => 'cbt_test_status.id'],
                'semantic_meaning'       => 'Represents a single exam sitting/attempt by a student on a specific test.',
                'child_relationships'   => ['cbt_answers' => 'Joined via composite foreign key (student_id, test_id)'],
                'critical_notes'         => 'cbt_aktivitas is the UI controller route name (Admin\CbtAktivitas), not a physical database table.',
            ],
            'cbt_soal_siswa' => [
                'concept_name'           => 'Student Question Answer / Submission',
                'operational_table'      => 'cbt_answers',
                'primary_key'            => 'id',
                'foreign_keys'           => ['student_id' => 'students.id', 'test_id' => 'cbt_test_status.id', 'question_id' => 'cbt_questions.id'],
                'semantic_meaning'       => 'Represents a student response to a specific question item within a test.',
                'child_relationships'   => ['cbt_questions' => 'Joined via question_id'],
                'critical_notes'         => 'cbt_soal_siswa was a synthetic shadow identifier. cbt_answers does NOT have a session_id column; it joins to sessions via (student_id, test_id). Answer ID must NEVER be conflated with Session ID.',
            ],
            default => [
                'concept_name'           => 'Unknown Historical Reference',
                'operational_table'      => null,
                'primary_key'            => null,
                'foreign_keys'           => [],
                'semantic_meaning'       => 'Unrecognized entity; has no valid operational table representation.',
                'critical_notes'         => 'Unmapped source.',
            ],
        };
    }

    // =========================================================================
    // LAYER 2: IDENTIFIER SEMANTICS & DISAMBIGUATION
    // =========================================================================

    public function mapRecordIdentifier(string $sourceRecordId, array $context = []): array
    {
        $parts = explode(':', $sourceRecordId, 2);
        $historicalTable = trim($parts[0] ?? '');
        $rawId = trim($parts[1] ?? '');

        if ($rawId === '' || strtolower($rawId) === 'null') {
            $recordId = null;
            $isMulti = false;
            $ids = [];
        } elseif (str_contains($rawId, '_') || str_contains($rawId, ',')) {
            $recordId = $rawId;
            $isMulti = true;
            $ids = array_values(array_filter(preg_split('/[_,]/', $rawId), fn($v) => trim($v) !== ''));
        } else {
            $recordId = $rawId;
            $isMulti = false;
            $ids = [$rawId];
        }

        if (in_array($historicalTable, ['cbt_sessions', 'cbt_answers'], true)) {
            return [
                'is_mapped'          => true,
                'historical_table'   => $historicalTable,
                'operational_table'  => $historicalTable,
                'record_id'          => $recordId,
                'is_multi'           => $isMulti,
                'ids'                => $ids,
                'mapping_confidence' => 'EXACT',
                'semantic_type'      => ($historicalTable === 'cbt_sessions') ? 'SESSION_PRIMARY_KEY' : 'ANSWER_PRIMARY_KEY',
                'semantic_notes'     => "Direct operational table reference to {$historicalTable}.",
            ];
        }

        if (isset(self::TABLE_MAPPING[$historicalTable])) {
            $opTable = self::TABLE_MAPPING[$historicalTable];

            $semanticType = 'UNKNOWN';
            $semanticNotes = '';

            if ($historicalTable === 'cbt_aktivitas') {
                $semanticType = 'SESSION_PRIMARY_KEY';
                $semanticNotes = "Maps to cbt_sessions.id = {$recordId}";
            } elseif ($historicalTable === 'cbt_soal_siswa') {
                $relatedSessionId = isset($context['session_id']) ? (string)$context['session_id'] : null;
                if ($relatedSessionId !== null && (string)$recordId === $relatedSessionId) {
                    $semanticType = 'SESSION_REFERENCE_NOT_ANSWER_ID';
                    $semanticNotes = "WARNING: Identifier '{$recordId}' represents the parent SESSION_ID, NOT cbt_answers.id. Querying cbt_answers by id = {$recordId} is semantically invalid; must resolve answers via session (student_id, test_id).";
                } else {
                    $semanticType = 'AMBIGUOUS_ANSWER_OR_SESSION';
                    $semanticNotes = "Identifier '{$recordId}' on cbt_soal_siswa requires context verification to distinguish between answer primary key and parent session ID.";
                }
            }

            return [
                'is_mapped'          => true,
                'historical_table'   => $historicalTable,
                'operational_table'  => $opTable,
                'record_id'          => $recordId,
                'is_multi'           => $isMulti,
                'ids'                => $ids,
                'mapping_confidence' => 'HIGH',
                'semantic_type'      => $semanticType,
                'semantic_notes'     => $semanticNotes,
            ];
        }

        return [
            'is_mapped'          => false,
            'historical_table'   => $historicalTable,
            'operational_table'  => null,
            'record_id'          => $recordId,
            'is_multi'           => $isMulti,
            'ids'                => $ids,
            'mapping_confidence' => 'UNMAPPED',
            'semantic_type'      => 'UNMAPPED',
            'semantic_notes'     => "No operational table mapping exists for '{$historicalTable}'.",
        ];
    }

    public function tableExists(string $tableName, ?PDO $pdo = null): bool
    {
        $activePdo = $pdo ?? $this->pdo;
        if ($activePdo === null) {
            return false;
        }

        if (!in_array($tableName, self::ALLOWED_OPERATIONAL_TABLES, true)) {
            return false;
        }

        try {
            $activePdo->query("SELECT 1 FROM `{$tableName}` LIMIT 0");
            return true;
        } catch (Exception) {
            return false;
        }
    }

    // =========================================================================
    // LAYER 3: SECURITY & SQL READ-ONLY ASSERTIONS (SECTION 6)
    // =========================================================================

    public function assertReadOnlyQuery(string $sql, ?string $targetTable = null): void
    {
        $raw = trim($sql);

        if ($targetTable !== null && !in_array($targetTable, self::ALLOWED_OPERATIONAL_TABLES, true)) {
            throw new InvalidArgumentException("HistoricalSourceAdapter Security Error: Table '{$targetTable}' is not in the allowed operational tables whitelist.");
        }

        if (preg_match('/;\s*\S+/', $raw)) {
            throw new InvalidArgumentException("HistoricalSourceAdapter Security Error: Multiple SQL statements detected via semicolon separator.");
        }

        $cleanSql = preg_replace('/\/\*.*?\*\//s', ' ', $raw);
        $cleanSql = preg_replace('/(--|#)[^\r\n]*/', ' ', $cleanSql);
        $cleanSql = trim($cleanSql);

        if (!preg_match('/^(SELECT|SHOW|DESCRIBE|EXPLAIN|WITH)\b/i', $cleanSql)) {
            throw new InvalidArgumentException("HistoricalSourceAdapter Security Error: Query must begin with SELECT, SHOW, DESCRIBE, EXPLAIN, or WITH.");
        }

        $strippedSql = preg_replace("/'([^'\\\\]|\\\\.)*'/", "''", $cleanSql);
        $strippedSql = preg_replace('/"([^"\\\\]|\\\\.)*"/', '""', $strippedSql);

        // Verify CTE and FROM/JOIN tables do not escape allowlist
        if (preg_match_all('/\b(FROM|JOIN)\s+[`"\']?([a-zA-Z0-9_]+)[`"\']?/i', $strippedSql, $matches)) {
            foreach ($matches[2] as $tbl) {
                $tblLower = strtolower($tbl);
                if (!in_array($tblLower, self::ALLOWED_OPERATIONAL_TABLES, true) && !in_array($tblLower, ['dual'], true)) {
                    throw new InvalidArgumentException("HistoricalSourceAdapter Security Error: Referenced table '{$tblLower}' is not in the allowed operational tables whitelist.");
                }
            }
        }

        $forbiddenKeywords = [
            'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE',
            'REPLACE', 'TRUNCATE', 'GRANT', 'REVOKE', 'LOCK', 'UNLOCK',
            'CALL', 'EXECUTE', 'INTO\s+OUTFILE', 'INTO\s+DUMPFILE', 'LOAD\s+DATA'
        ];

        foreach ($forbiddenKeywords as $pattern) {
            if (preg_match('/\b' . $pattern . '\b/i', $strippedSql)) {
                throw new InvalidArgumentException("HistoricalSourceAdapter Security Error: Mutating SQL keyword '{$pattern}' detected.");
            }
        }
    }

    // =========================================================================
    // LAYER 4: SESSION OWNERSHIP & ESSAY SEMANTICS EVALUATION
    // =========================================================================

    /**
     * Audit Session Ownership between exam sessions and student answers.
     *
     * GOVERNANCE RULE (Section 3):
     * SESSION_CONTEXT_MATCH must NEVER be automatically promoted to SESSION_OWNERSHIP_VERIFIED.
     * If relational ownership cannot be proven without ambiguity, fail-closed!
     *
     * @param int|null $sessionId Specific session ID being audited
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @param PDO|null $pdo Database handle
     * @return array [status, session_count, sessions, discrepancy, is_verified]
     */
    public function evaluateSessionOwnership(?int $sessionId, int $studentId, int $testId, ?PDO $pdo = null): array
    {
        $activePdo = $pdo ?? $this->pdo;
        if ($activePdo === null) {
            return [
                'ownership_status'  => self::SESSION_OWNERSHIP_UNPROVEN,
                'session_count'     => 0,
                'sessions'          => [],
                'is_verified'       => false,
                'discrepancy'       => 'No database connection available to evaluate session ownership.',
            ];
        }

        if (!$this->tableExists('cbt_sessions', $activePdo)) {
            return [
                'ownership_status'  => self::SESSION_OWNERSHIP_UNPROVEN,
                'session_count'     => 0,
                'sessions'          => [],
                'is_verified'       => false,
                'discrepancy'       => 'cbt_sessions table does not exist in target database.',
            ];
        }

        $sql = "SELECT * FROM `cbt_sessions` WHERE student_id = ? AND test_id = ?";
        $this->assertReadOnlyQuery($sql, 'cbt_sessions');
        $stmt = $activePdo->prepare($sql);
        $stmt->execute([$studentId, $testId]);
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $sessionCount = count($sessions);

        // 1. Zero sessions found
        if ($sessionCount === 0) {
            return [
                'ownership_status'  => self::SESSION_OWNERSHIP_UNPROVEN,
                'session_count'     => 0,
                'sessions'          => [],
                'is_verified'       => false,
                'discrepancy'       => "No session record found for student {$studentId} on test {$testId}.",
            ];
        }

        // 2. Multiple sessions found (Retake scenario, e.g. Case 503)
        if ($sessionCount > 1) {
            return [
                'ownership_status'  => self::SESSION_OWNERSHIP_AMBIGUOUS,
                'session_count'     => $sessionCount,
                'sessions'          => $sessions,
                'is_verified'       => false,
                'discrepancy'       => "Multiple ({$sessionCount}) sessions logged for student {$studentId} on test {$testId}. Because cbt_answers lacks a session_id column, answers cannot be definitively bound to a single session. Session ownership is relationally AMBIGUOUS.",
            ];
        }

        // 3. Exactly 1 session found (Single session context match)
        // STRICT RULE: SESSION_CONTEXT_MATCH must NOT be automatically promoted to SESSION_OWNERSHIP_VERIFIED!
        return [
            'ownership_status'  => self::SESSION_CONTEXT_MATCH,
            'session_count'     => 1,
            'sessions'          => $sessions,
            'is_verified'       => false, // Unverified without physical session foreign key on answers
            'discrepancy'       => "Student ID {$studentId} and Test ID {$testId} match session context (Session ID: {$sessions[0]['id']}). However, because cbt_answers does not store a physical session_id foreign key, ownership remains unproven by direct schema relation.",
        ];
    }

    /**
     * Authenticate Teacher Essay Grading Provenance (Priority A).
     *
     * In accordance with DEC-ESS-01 and Stage F3G-R3 governance:
     * Neither `score IS NOT NULL`, `updated_at`, nor a caller boolean constitutes authentic grader proof.
     * Grader provenance MUST explicitly bind:
     * 1. Grader identity (`grader_id`)
     * 2. Grader authority / teaching assignment (`is_authorized` === true)
     * 3. Target student and test binding
     * 4. Grading timestamp (`graded_at`)
     * 5. Authentic change log reference (`change_log_reference`)
     *
     * Fail-closed: returns ['is_verified' => false, ...] if any condition fails.
     */
    public function verifyGraderProvenance(mixed $provenance, array $context = []): array
    {
        if (!is_array($provenance) || empty($provenance)) {
            return [
                'is_verified' => false,
                'discrepancy' => 'Grader provenance missing or not structured. Fail-closed: teacher authenticity unproven.',
            ];
        }

        $graderId = trim((string)($provenance['grader_id'] ?? ''));
        if ($graderId === '') {
            return [
                'is_verified' => false,
                'discrepancy' => 'Grader identity (grader_id) is missing.',
            ];
        }

        if (empty($provenance['is_authorized']) || $provenance['is_authorized'] !== true) {
            return [
                'is_verified' => false,
                'discrepancy' => "Grader '{$graderId}' lacks verified authority / teaching assignment for this subject.",
            ];
        }

        if (empty($provenance['graded_at'])) {
            return [
                'is_verified' => false,
                'discrepancy' => 'Grading timestamp (graded_at) is missing from provenance record.',
            ];
        }

        if (empty($provenance['change_log_reference'])) {
            return [
                'is_verified' => false,
                'discrepancy' => 'Authentic score change log reference or score audit entry is missing.',
            ];
        }

        if (isset($context['student_id']) && isset($provenance['student_id']) && (string)$provenance['student_id'] !== (string)$context['student_id']) {
            return [
                'is_verified' => false,
                'discrepancy' => "Provenance student mismatch: targets student {$provenance['student_id']}, expected {$context['student_id']}.",
            ];
        }

        if (isset($context['test_id']) && isset($provenance['test_id']) && (string)$provenance['test_id'] !== (string)$context['test_id']) {
            return [
                'is_verified' => false,
                'discrepancy' => "Provenance test mismatch: targets test {$provenance['test_id']}, expected {$context['test_id']}.",
            ];
        }

        // Priority C & Stage F3G-R5 Audit 2: Authenticate teacher assignment and grading log against authoritative registry
        $logAudit = null;
        if (isset($context['validator']) && $context['validator'] instanceof ReviewerEvidenceValidator) {
            $valTestId = $provenance['test_id'] ?? $context['test_id'] ?? null;
            $valStudentId = $provenance['student_id'] ?? $context['student_id'] ?? null;
            $expectedScore = isset($context['expected_score']) ? (float)$context['expected_score'] : (isset($provenance['score']) ? (float)$provenance['score'] : null);

            if ($valTestId !== null && !$context['validator']->isTeacherAssignedToTest($graderId, $valTestId)) {
                return [
                    'is_verified' => false,
                    'discrepancy' => "Grader '{$graderId}' lacks verified authority / teaching assignment for Test ID {$valTestId} in authoritative registry.",
                ];
            }

            $logRef = (string)($provenance['change_log_reference'] ?? '');
            $logAudit = $context['validator']->auditGradingAuditLog($logRef, (string)$valStudentId, (string)$valTestId, $graderId, $expectedScore);
            if (!$logAudit['is_valid']) {
                return [
                    'is_verified' => false,
                    'discrepancy' => $logAudit['discrepancy'],
                ];
            }
        }

        return [
            'is_verified'               => true,
            'validation_rules_passed'   => true,
            'grader_id'                 => $graderId,
            'graded_at'                 => $provenance['graded_at'],
            'change_log_reference'      => $provenance['change_log_reference'],
            'grader_provenance_status'  => $logAudit['grader_provenance_status'] ?? ReviewerEvidenceValidator::GRADER_VALIDATION_PASSED,
            'auth_semantic_level'       => $logAudit['auth_semantic_level'] ?? ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED,
            'is_authoritative_verified' => $logAudit['is_authoritative_verified'] ?? false,
            'discrepancy'               => null,
        ];
    }

    /**
     * Authenticate Proctor-Signed Absence Evidence (Priority C).
     *
     * In accordance with DEC-ABS-01:
     * Legacy score 0, empty answers, missing session != proof of absence.
     * Proof must bind: student_id, test_id, exam_date, session_id (or null/absent indicator),
     * proctor ID / authority, and signed BAP document.
     *
     * @param mixed $evidence Evidence array or metadata containing BAP document
     * @param array $context Context parameters (student_id, test_id)
     * @return array [status, is_verified, bap_reference, proctor_id, discrepancy, bap_details]
     */
    public function verifyAbsenceEvidence(mixed $evidence, array $context = []): array
    {
        if (empty($evidence) || !is_array($evidence)) {
            return [
                'status'      => self::ABSENCE_STATUS_EVIDENCE_MISSING,
                'is_verified' => false,
                'discrepancy' => 'Absence evidence record is missing. DEC-ABS-01: Absence cannot be inferred without proctor-signed documentation.',
            ];
        }

        $bapDoc = $evidence['bap_document'] ?? $evidence['metadata']['bap_document'] ?? $evidence['metadata']['bap'] ?? $context['bap_document'] ?? null;
        if (empty($bapDoc) || !is_array($bapDoc)) {
            return [
                'status'      => self::ABSENCE_STATUS_EVIDENCE_MISSING,
                'is_verified' => false,
                'discrepancy' => 'No proctor-signed BAP (Berita Acara Pelaksanaan) document provided for absence verification.',
            ];
        }

        $proctorId = trim((string)($bapDoc['proctor_id'] ?? ''));
        $proctorAuthority = !empty($bapDoc['is_authorized_proctor']) || !empty($bapDoc['is_authorized']) || !empty($bapDoc['proctor_authority']);
        $isSigned = (!empty($bapDoc['is_bap_signed']) && $bapDoc['is_bap_signed'] === true)
                 || (!empty($bapDoc['bap_signed']) && $bapDoc['bap_signed'] === true);
        $bapRef = trim((string)($bapDoc['bap_number'] ?? $bapDoc['bap_reference'] ?? $bapDoc['reference'] ?? ''));
        $examDate = trim((string)($bapDoc['exam_date'] ?? ''));

        if ($proctorId === '' || !$proctorAuthority || !$isSigned || $bapRef === '' || $examDate === '') {
            return [
                'status'      => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                'is_verified' => false,
                'discrepancy' => 'BAP document present but incomplete or unverified (requires authorized proctor ID, proctor authority, physical/digital signature, valid exam date, and official BAP reference).',
                'bap_details' => $bapDoc,
            ];
        }

        // Check context bindings (student_id, test_id)
        $expectedStudentId = $context['student_id'] ?? $evidence['metadata']['student_id'] ?? null;
        if ($expectedStudentId !== null && isset($bapDoc['student_id'])) {
            if ((string)$bapDoc['student_id'] !== (string)$expectedStudentId) {
                return [
                    'status'      => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                    'is_verified' => false,
                    'discrepancy' => "BAP student ID mismatch: document targets student {$bapDoc['student_id']}, expected {$expectedStudentId}.",
                    'bap_details' => $bapDoc,
                ];
            }
        }

        $expectedTestId = $context['test_id'] ?? $evidence['related_test_id'] ?? null;
        if ($expectedTestId !== null && isset($bapDoc['test_id'])) {
            if ((string)$bapDoc['test_id'] !== (string)$expectedTestId) {
                return [
                    'status'      => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                    'is_verified' => false,
                    'discrepancy' => "BAP test ID mismatch: document targets test {$bapDoc['test_id']}, expected {$expectedTestId}.",
                    'bap_details' => $bapDoc,
                ];
            }
        }

        // Check exam date not in the future
        $ts = strtotime($examDate);
        if ($ts === false || $ts > time() + 86400) {
            return [
                'status'      => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                'is_verified' => false,
                'discrepancy' => "BAP exam date '{$examDate}' is chronologically invalid.",
                'bap_details' => $bapDoc,
            ];
        }

        // Priority D & Stage F3G-R5 Audit 3 & Stage F3G-R6 Audit 2: Authenticate against authoritative BAP registry if validator is provided
        $bapClass = null;
        if (isset($context['validator']) && $context['validator'] instanceof ReviewerEvidenceValidator) {
            $valStudentId = (string)($expectedStudentId ?? $bapDoc['student_id'] ?? '');
            $valTestId = (string)($expectedTestId ?? $bapDoc['test_id'] ?? '');
            $bapClass = $context['validator']->classifyBapDocument($bapRef, $valStudentId, $valTestId);

            if (!$bapClass['is_valid']) {
                return [
                    'status'                    => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                    'bap_classification'        => $bapClass['status'],
                    'auth_semantic_level'       => $bapClass['auth_semantic_level'] ?? ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED,
                    'is_authoritative_verified' => false,
                    'is_verified'               => false,
                    'discrepancy'               => $bapClass['discrepancy'],
                    'bap_details'               => $bapDoc,
                ];
            }
        }

        $isAuth = $bapClass['is_authoritative_verified'] ?? false;
        $authLevel = $bapClass['auth_semantic_level'] ?? ReviewerEvidenceValidator::AUTH_VALIDATION_RULES_PASSED;
        $bapStatus = $bapClass['status'] ?? ($isAuth ? ReviewerEvidenceValidator::BAP_STATUS_AUTHENTICATED : ReviewerEvidenceValidator::BAP_STATUS_VALIDATION_PASSED);

        return [
            'status'                    => self::ABSENCE_STATUS_VERIFIED,
            'bap_classification'        => $bapStatus,
            'auth_semantic_level'       => $authLevel,
            'is_authoritative_verified' => $isAuth,
            'validation_rules_passed'   => true,
            'is_verified'               => true,
            'bap_reference'             => $bapRef,
            'proctor_id'                => $proctorId,
            'exam_date'                 => $examDate,
            'discrepancy'               => null,
            'bap_details'               => $bapDoc,
        ];
    }

    /**
     * Audit Essay Data Semantics across 9 distinct states (Priority A & B).
     *
     * GOVERNANCE ENFORCEMENT:
     * Parameter $provenance defaults to NULL (fail-closed). Permissive defaults are strictly eliminated.
     * SCORED_POSITIVE and SCORED_ALL_ZERO require verified grader provenance.
     *
     * @param float|null $sessionEssayScore Stored aggregate essay_score from cbt_sessions
     * @param array $essayAnswerRows Rows from cbt_answers corresponding to essay questions
     * @param int $mandatoryEssayCount Count of mandatory essay questions defined in test
     * @param bool $isLegacy Flag indicating unverified legacy data origin
     * @param mixed $provenance Structured grader provenance array
     * @param array $context Context parameters (student_id, test_id, config, void flags)
     * @return array
     */
    public function evaluateEssayGradingState(
        ?float $sessionEssayScore,
        array $essayAnswerRows,
        int $mandatoryEssayCount,
        bool $isLegacy = false,
        mixed $provenance = null,
        array $context = []
    ): array {
        // State 8: Data historis esai dengan status tidak dapat diverifikasi (LEGACY_UNVERIFIED)
        if ($isLegacy) {
            return [
                'state'                => self::ESSAY_STATE_LEGACY_UNVERIFIED,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'discrepancy'          => 'Historical essay data cannot be verified from operational item-level answer records.',
                'reason'               => 'DEC-ESS-01: Legacy unverified essay score cannot be authenticated without item records; final score withheld.',
            ];
        }

        // State 1: Tes tidak memiliki soal esai (Priority B.A)
        // Must verify actual questions, not solely bobot_esai = 0
        if ($mandatoryEssayCount === 0) {
            if (!empty($context['has_essay_questions_in_bank']) && empty($context['is_all_type_void']) && empty($context['is_entire_test_void'])) {
                return [
                    'state'                => self::ESSAY_STATE_PENDING,
                    'is_hold_enforced'     => true,
                    'final_score_withheld' => true,
                    'is_eligible'          => false,
                    'final_score'          => null,
                    'report_score'         => null,
                    'discrepancy'          => 'Test bank contains essay questions despite zero essay weight configuration; requires verification before determining test has no essay items.',
                    'reason'               => 'Cannot conclude NO_ESSAY_QUESTIONS solely from zero weight when essay items exist in test bank.',
                ];
            }

            return [
                'state'                => self::ESSAY_STATE_NO_QUESTIONS,
                'is_hold_enforced'     => false,
                'final_score_withheld' => false,
                'is_eligible'          => true,
                'discrepancy'          => null,
                'reason'               => !empty($context['is_all_type_void']) 
                    ? 'OD-ALL-TYPE-VOID: All essay questions voided; final score determined solely by objective items.'
                    : (!empty($context['is_entire_test_void'])
                        ? 'OD-ENTIRE-TEST-VOID: Entire test voided; zero weight applied.'
                        : 'Test configuration and question bank confirm zero essay questions; final score is determined solely by objective items.'),
            ];
        }

        // State 8 (Auto): Session reports essay score but no item-level essay answers exist
        if (empty($essayAnswerRows) && $sessionEssayScore !== null && $sessionEssayScore > 0.0) {
            return [
                'state'                => self::ESSAY_STATE_LEGACY_UNVERIFIED,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'discrepancy'          => "Session reports essay score {$sessionEssayScore} but no item-level essay answers exist in database.",
                'reason'               => 'DEC-ESS-01: Legacy unverified essay score cannot be authenticated without item records; final score withheld.',
            ];
        }

        // State 7 (Case with no answer rows but session essay_score is 0.00 default):
        if (empty($essayAnswerRows) && $sessionEssayScore === 0.0) {
            return [
                'state'                => self::ESSAY_STATE_DEFAULT_ZERO,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'discrepancy'          => 'Session essay_score is 0.00 (database default) without item-level teacher grading evidence in cbt_answers.',
                'reason'               => 'Cannot verify essay scoring from default 0.00 without question-level audit trail.',
            ];
        }

        // State 2: Tes memiliki soal esai, tetapi tidak ditemukan record jawaban
        if (empty($essayAnswerRows)) {
            return [
                'state'                => self::ESSAY_STATE_UNANSWERED,
                'unanswered_subreason' => 'RECORD_ABSENT_POSSIBLE_DATA_LOSS',
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'discrepancy'          => 'No essay answer rows found in cbt_answers for mandatory essay questions. Record absence may indicate student omission, session reset, or historical data loss.',
                'reason'               => 'DEC-ESS-01: Essay answers missing from database; final score withheld pending proctor verification.',
            ];
        }

        $totalAnswers = count($essayAnswerRows);
        $pendingCount = 0;
        $scoredPositiveCount = 0;
        $scoredZeroCount = 0;
        $blankSubmissionCount = 0;

        foreach ($essayAnswerRows as $ans) {
            $score = $ans['score'] ?? null;
            $text = trim((string)($ans['answer'] ?? ''));
            if ($text === '') {
                $blankSubmissionCount++;
            }

            if ($score === null || $score === '') {
                $pendingCount++;
            } elseif ((float)$score === 0.0) {
                $scoredZeroCount++;
            } elseif ((float)$score > 0.0) {
                $scoredPositiveCount++;
            }
        }

        // Priority B.B: Differentiate blank text vs verified blank submission
        $isAllBlankText = ($blankSubmissionCount === $totalAnswers);
        $participationVerified = !empty($context['attendance_verified']) && !empty($context['session_ownership_verified']);
        $unansweredSubreason = $isAllBlankText 
            ? ($participationVerified ? 'PROVEN_BLANK_SUBMISSION' : 'BLANK_ANSWER_RECORD_PRESENT_UNVERIFIED')
            : 'STUDENT_SUBMITTED_ANSWERS';

        // State 7: Nilai agregat esai 0.00 karena default database, bukan koreksi guru
        if ($sessionEssayScore === 0.0 && ($scoredPositiveCount + $scoredZeroCount) === 0) {
            return [
                'state'                => self::ESSAY_STATE_DEFAULT_ZERO,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'discrepancy'          => 'Session essay_score is 0.00 (database default) without item-level teacher grading evidence in cbt_answers.',
                'reason'               => 'Cannot verify essay scoring from default 0.00 without question-level audit trail.',
            ];
        }

        // State 3 & 4: Jawaban esai ada dan belum dinilai (Full Pending vs Partially Scored)
        if ($pendingCount > 0) {
            $state = ($pendingCount === $totalAnswers) ? self::ESSAY_STATE_PENDING : self::ESSAY_STATE_PARTIALLY_SCORED;
            return [
                'state'                => $state,
                'unanswered_subreason' => $unansweredSubreason,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'pending_count'        => $pendingCount,
                'scored_count'         => $scoredPositiveCount + $scoredZeroCount,
                'discrepancy'          => "Essay grading incomplete: {$pendingCount} of {$totalAnswers} essay questions pending evaluation.",
                'reason'               => 'DEC-ESS-01: Final score and report score withheld (NULL) while essay evaluation is pending.',
            ];
        }

        // Authenticate Grader Provenance (Priority A)
        $provenanceCheck = $this->verifyGraderProvenance($provenance, $context);
        $isProvenanceVerified = $provenanceCheck['is_verified'];

        // State: SCORE_PRESENT_UNVERIFIED (Priority A.5)
        // Nilai butir tersedia di cbt_answers, tetapi asal-usul penilaian (identitas guru, wewenang, log) belum terbukti
        if (!$isProvenanceVerified && ($scoredPositiveCount + $scoredZeroCount) === $totalAnswers) {
            return [
                'state'                => self::ESSAY_STATE_SCORE_PRESENT_UNVERIFIED,
                'is_hold_enforced'     => true,
                'final_score_withheld' => true,
                'is_eligible'          => false,
                'final_score'          => null,
                'report_score'         => null,
                'scored_count'         => $scoredPositiveCount + $scoredZeroCount,
                'discrepancy'          => 'Item scores are present in cbt_answers, but grading provenance cannot be authenticated: ' . $provenanceCheck['discrepancy'],
                'reason'               => 'DEC-ESS-01: Score present without authenticated grading provenance cannot be published; final score and report score withheld (NULL).',
            ];
        }

        // State 6: Semua jawaban esai telah dinilai dengan nilai 0.00 dan terverifikasi
        if ($scoredZeroCount === $totalAnswers) {
            return [
                'state'                => self::ESSAY_STATE_SCORED_ALL_ZERO,
                'is_hold_enforced'     => false,
                'final_score_withheld' => false,
                'is_eligible'          => true,
                'final_score'          => 0.0,
                'report_score'         => 0.0,
                'scored_type'          => 'SCORED_ALL_ZERO',
                'grader_id'            => $provenanceCheck['grader_id'] ?? null,
                'discrepancy'          => null,
                'reason'               => 'All essay questions graded by teacher with legitimate score 0.00 and verified provenance. Score 0.00 is a valid academic grade.',
            ];
        }

        // State 5: Semua jawaban esai telah dinilai dengan nilai positif dan terverifikasi
        return [
            'state'                => self::ESSAY_STATE_SCORED_POSITIVE,
            'is_hold_enforced'     => false,
            'final_score_withheld' => false,
            'is_eligible'          => true,
            'scored_type'          => 'SCORED_POSITIVE',
            'grader_id'            => $provenanceCheck['grader_id'] ?? null,
            'discrepancy'          => null,
            'reason'               => 'All essay questions evaluated and verified from item-level records with authenticated provenance.',
        ];
    }

    // =========================================================================
    // LAYER 5: RESOLUTION METHODS WITH SESSION OWNERSHIP DISPATCH
    // =========================================================================

    public function resolveEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $type = $evidence['evidence_type'] ?? '';
        $activePdo = $pdo ?? $this->pdo;

        return match ($type) {
            ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON => $this->resolveScoreEvidence($evidence, $activePdo, $context),
            ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION => $this->resolveEssayEvidence($evidence, $activePdo, $context),
            ReviewerEvidenceValidator::TYPE_SESSION_RECORD   => $this->dispatchSessionRecord($evidence, $activePdo, $context),
            default                                          => $this->resolveGenericEvidence($evidence, $activePdo, $context),
        };
    }

    private function dispatchSessionRecord(array $evidence, ?PDO $pdo, array $context): array
    {
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        if ($mapping['is_multi'] || str_contains($sourceRecordId, '_') || ($context['category'] ?? '') === 'RETAKE_SESSION_SELECTION') {
            return $this->resolveRetakeEvidence($evidence, $pdo, $context);
        }

        if ($mapping['record_id'] === null || ($context['category'] ?? '') === 'ABSENT_FALLBACK' || str_contains($sourceRecordId, 'null')) {
            return $this->resolveAttendanceEvidence($evidence, $pdo, $context);
        }

        return $this->resolveSessionEvidence($evidence, $pdo, $context);
    }

    public function resolveScoreEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        if (!$mapping['is_mapped']) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNMAPPED_SOURCE,
                'record_mapping_status'    => self::RECORD_STATUS_MAPPING_UNPROVEN,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => null,
                'target_id'                => $mapping['record_id'],
                'mapping_confidence'       => 'UNMAPPED',
                'is_verified'              => false,
                'discrepancy'              => "Source table '{$mapping['historical_table']}' has no operational mapping.",
                'operational_data'         => null,
            ];
        }

        $opTable = $mapping['operational_table'];
        $opId = $mapping['record_id'];

        if ($activePdo === null) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNVERIFIED_NO_DB,
                'record_mapping_status'    => self::RECORD_STATUS_SOURCE_UNAVAILABLE,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => 'No operational database connection available for live resolution.',
                'operational_data'         => null,
            ];
        }

        if (!$this->tableExists($opTable, $activePdo)) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Mapped operational table '{$opTable}' does not exist in target database.",
                'operational_data'         => null,
            ];
        }

        $sql = "SELECT * FROM `{$opTable}` WHERE id = ?";
        $this->assertReadOnlyQuery($sql, $opTable);

        $stmt = $activePdo->prepare($sql);
        $stmt->execute([$opId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Record ID '{$opId}' not found in operational table '{$opTable}' (historical '{$mapping['historical_table']}').",
                'operational_data'         => null,
            ];
        }

        // Context check: Student ID and Test ID matching
        $expectedStudentId = $context['student_id'] ?? $evidence['metadata']['student_id'] ?? null;
        if ($expectedStudentId !== null && isset($row['student_id'])) {
            if ((string)$row['student_id'] !== (string)$expectedStudentId) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Cross-student contamination prevented: Operational session '{$opId}' belongs to student '{$row['student_id']}', expected '{$expectedStudentId}'.",
                    'operational_data'         => $row,
                ];
            }
        }

        $expectedTestId = $context['test_id'] ?? $evidence['related_test_id'] ?? null;
        if ($expectedTestId !== null && isset($row['test_id'])) {
            if ((string)$row['test_id'] !== (string)$expectedTestId) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Cross-test contamination prevented: Operational session '{$opId}' belongs to test '{$row['test_id']}', expected '{$expectedTestId}'.",
                    'operational_data'         => $row,
                ];
            }
        }

        // Evaluate Session Ownership for this session
        $ownershipAudit = $this->evaluateSessionOwnership((int)$opId, (int)$row['student_id'], (int)$row['test_id'], $activePdo);

        // Check score match
        $claimedLegacyScore = $evidence['metadata']['legacy_score'] ?? null;
        if ($claimedLegacyScore !== null && isset($row['score'])) {
            $dbScoreNorm = number_format((float)$row['score'], 2, '.', '');
            $claimedScoreNorm = number_format((float)$claimedLegacyScore, 2, '.', '');

            if ($dbScoreNorm !== $claimedScoreNorm) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_VALUE_MISMATCH,
                    'session_ownership_status' => $ownershipAudit['ownership_status'],
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Operational score '{$dbScoreNorm}' does not match claimed legacy score '{$claimedScoreNorm}'.",
                    'operational_data'         => $row,
                ];
            }
        }

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
            'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
            'session_ownership_status' => $ownershipAudit['ownership_status'],
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => $opTable,
            'target_id'                => $opId,
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => true,
            'discrepancy'              => null,
            'operational_data'         => $row,
        ];
    }

    public function resolveEssayEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        $opId = $mapping['record_id'];

        if ($activePdo === null) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNVERIFIED_NO_DB,
                'record_mapping_status'    => self::RECORD_STATUS_SOURCE_UNAVAILABLE,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => 'cbt_answers',
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => 'No database connection available to verify essay submissions.',
                'operational_data'         => null,
            ];
        }

        // STEP 1: Disambiguate Session ID vs Answer ID
        // In CBT schema, cbt_answers does NOT have session_id.
        $sessionRow = null;
        if ($this->tableExists('cbt_sessions', $activePdo)) {
            $sSql = "SELECT * FROM `cbt_sessions` WHERE id = ?";
            $this->assertReadOnlyQuery($sSql, 'cbt_sessions');
            $sStmt = $activePdo->prepare($sSql);
            $sStmt->execute([$opId]);
            $sessionRow = $sStmt->fetch(PDO::FETCH_ASSOC);
        }

        if ($sessionRow) {
            // Validate context (student_id, test_id)
            $expectedStudent = $context['student_id'] ?? $evidence['metadata']['student_id'] ?? null;
            if ($expectedStudent !== null && (string)$sessionRow['student_id'] !== (string)$expectedStudent) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => 'cbt_sessions',
                    'target_id'                => $opId,
                    'mapping_confidence'       => 'HIGH',
                    'is_verified'              => false,
                    'discrepancy'              => "Session context mismatch: cbt_sessions ID '{$opId}' belongs to student '{$sessionRow['student_id']}', expected '{$expectedStudent}'.",
                    'operational_data'         => $sessionRow,
                ];
            }

            // Inspect child essay answers via (student_id, test_id)
            $essayDetails = $this->inspectEssayAnswers($activePdo, (int)$sessionRow['student_id'], (int)$sessionRow['test_id']);

            // Evaluate Session Ownership
            $ownershipAudit = $this->evaluateSessionOwnership((int)$opId, (int)$sessionRow['student_id'], (int)$sessionRow['test_id'], $activePdo);

            // Evaluate Mandatory Essay Count
            $mandatoryEssayCount = $this->getMandatoryEssayCount($activePdo, (int)$sessionRow['test_id']);
            $gradingAudit = $this->evaluateEssayGradingState(
                $sessionRow['essay_score'] !== null ? (float)$sessionRow['essay_score'] : null,
                $essayDetails['rows'] ?? [],
                $mandatoryEssayCount,
                false,
                $evidence['provenance'] ?? $context['provenance'] ?? null,
                array_merge($context, [
                    'student_id' => $sessionRow['student_id'],
                    'test_id'    => $sessionRow['test_id'],
                    'session_id' => $opId,
                ])
            );

            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
                'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
                'session_ownership_status' => $ownershipAudit['ownership_status'],
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => 'cbt_answers',
                'resolved_tables'          => ['cbt_sessions', 'cbt_answers'],
                'target_id'                => $opId,
                'mapping_confidence'       => 'HIGH',
                'is_verified'              => true,
                'discrepancy'              => null,
                'operational_data'         => array_merge($sessionRow, [
                    'score'                    => $essayDetails['rows'][0]['score'] ?? null,
                    'objective_score'          => $sessionRow['score'],
                    'semantic_resolution'      => 'RESOLVED_VIA_PARENT_SESSION',
                    'essay_answers'            => $essayDetails,
                    'essay_state'              => in_array($gradingAudit['state'], [self::ESSAY_STATE_SCORED_ALL_ZERO, self::ESSAY_STATE_SCORED_POSITIVE, self::ESSAY_STATE_SCORED], true) ? self::ESSAY_STATE_SCORED : $gradingAudit['state'],
                    'essay_grading_state'      => $gradingAudit['state'],
                    'is_hold_enforced'              => $gradingAudit['is_hold_enforced'],
                    'final_score_withheld'          => $gradingAudit['final_score_withheld'],
                    'session_ownership_status'      => $ownershipAudit['ownership_status'],
                    'numeric_coincidence_prevented' => true,
                ]),
            ];
        }

        // STEP 2: If NOT found as session, check if it's a direct primary key in cbt_answers
        if ($this->tableExists('cbt_answers', $activePdo)) {
            $aSql = "SELECT * FROM `cbt_answers` WHERE id = ?";
            $this->assertReadOnlyQuery($aSql, 'cbt_answers');
            $aStmt = $activePdo->prepare($aSql);
            $aStmt->execute([$opId]);
            $answerRow = $aStmt->fetch(PDO::FETCH_ASSOC);

            if ($answerRow) {
                $expectedStudent = $context['student_id'] ?? $evidence['metadata']['student_id'] ?? null;
                $expectedTest = $context['test_id'] ?? $evidence['related_test_id'] ?? null;

                if (($expectedStudent !== null && (string)$answerRow['student_id'] !== (string)$expectedStudent) ||
                    ($expectedTest !== null && (string)$answerRow['test_id'] !== (string)$expectedTest)) {
                    return [
                        'evidence_id'              => $evidence['evidence_id'] ?? '',
                        'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                        'record_mapping_status'    => self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                        'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                        'historical_table'         => $mapping['historical_table'],
                        'operational_table'        => 'cbt_answers',
                        'target_id'                => $opId,
                        'mapping_confidence'       => 'LOW',
                        'is_verified'              => false,
                        'discrepancy'              => "Semantic Conflation Prevented: cbt_answers.id = '{$opId}' exists but belongs to student '{$answerRow['student_id']}' and test '{$answerRow['test_id']}', which contradicts case context. ID {$opId} was intended as a Session ID, not this Answer ID.",
                        'operational_data'         => $answerRow,
                    ];
                }

                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
                    'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
                    'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => 'cbt_answers',
                    'target_id'                => $opId,
                    'mapping_confidence'       => 'EXACT',
                    'is_verified'              => true,
                    'discrepancy'              => null,
                    'operational_data'         => $answerRow,
                ];
            }
        }

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
            'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
            'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => 'cbt_sessions / cbt_answers',
            'target_id'                => $opId,
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => false,
            'discrepancy'              => "Record ID '{$opId}' not found as parent session in cbt_sessions nor as answer in cbt_answers.",
            'operational_data'         => null,
        ];
    }

    private function getMandatoryEssayCount(PDO $pdo, int $testId): int
    {
        if (!$this->tableExists('cbt_questions', $pdo) || !$this->tableExists('cbt_test_status', $pdo)) {
            return 1; // Default assumption if questions table not attached
        }

        $sql = "SELECT COUNT(*) FROM `cbt_questions` q
                INNER JOIN `cbt_test_status` t ON t.bank_id = q.bank_id
                WHERE t.id = ? AND q.question_type IN ('esai', 'essay')";
        $this->assertReadOnlyQuery($sql);
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$testId]);
        return (int)$stmt->fetchColumn();
    }

    private function inspectEssayAnswers(PDO $pdo, int $studentId, int $testId): array
    {
        $hasAnswers = $this->tableExists('cbt_answers', $pdo);
        $hasQuestions = $this->tableExists('cbt_questions', $pdo);

        if (!$hasAnswers) {
            return ['essay_state' => self::ESSAY_STATE_PENDING, 'essay_count' => 0, 'pending_count' => 0, 'rows' => []];
        }

        if ($hasQuestions) {
            $sql = "SELECT a.id, a.student_id, a.test_id, a.question_id, a.answer, a.score, q.question_type 
                    FROM `cbt_answers` a 
                    INNER JOIN `cbt_questions` q ON q.id = a.question_id 
                    WHERE a.student_id = ? AND a.test_id = ? AND q.question_type IN ('esai', 'essay')";
            $this->assertReadOnlyQuery($sql);
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$studentId, $testId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $sql = "SELECT * FROM `cbt_answers` WHERE student_id = ? AND test_id = ?";
            $this->assertReadOnlyQuery($sql, 'cbt_answers');
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$studentId, $testId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $totalEssay = count($rows);
        $pendingCount = 0;
        $scoredPositiveCount = 0;
        $scoredZeroCount = 0;

        foreach ($rows as $r) {
            if ($r['score'] === null || $r['score'] === '') {
                $pendingCount++;
            } elseif ((float)$r['score'] === 0.0) {
                $scoredZeroCount++;
            } else {
                $scoredPositiveCount++;
            }
        }

        if ($totalEssay === 0 || $pendingCount > 0) {
            $state = self::ESSAY_STATE_PENDING;
        } elseif ($scoredZeroCount === $totalEssay) {
            $state = self::ESSAY_STATE_SCORED_ALL_ZERO;
        } else {
            $state = self::ESSAY_STATE_SCORED_POSITIVE;
        }

        return [
            'essay_state'   => $state,
            'essay_count'   => $totalEssay,
            'pending_count' => $pendingCount,
            'scored_count'  => $scoredPositiveCount + $scoredZeroCount,
            'zero_count'    => $scoredZeroCount,
            'rows'          => $rows,
        ];
    }

    public function resolveAttendanceEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        $opTable = $mapping['operational_table'] ?? 'cbt_sessions';
        $opId = $mapping['record_id'];

        $absenceAudit = $this->verifyAbsenceEvidence($evidence, $context);

        if ($activePdo === null) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNVERIFIED_NO_DB,
                'record_mapping_status'    => self::RECORD_STATUS_SOURCE_UNAVAILABLE,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'absence_status'           => $absenceAudit['status'],
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => 'No database connection available to verify student attendance.',
                'operational_data'         => null,
            ];
        }

        if (!$this->tableExists($opTable, $activePdo)) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'absence_status'           => $absenceAudit['status'],
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Operational table '{$opTable}' does not exist.",
                'operational_data'         => null,
            ];
        }

        if ($opId !== null) {
            $sql = "SELECT * FROM `{$opTable}` WHERE id = ?";
            $this->assertReadOnlyQuery($sql, $opTable);
            $stmt = $activePdo->prepare($sql);
            $stmt->execute([$opId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$row) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'absence_status'           => $absenceAudit['status'],
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Session ID '{$opId}' not found in operational database.",
                    'operational_data'         => null,
                ];
            }

            if (($row['status'] ?? '') === 'finished' && (float)($row['score'] ?? 0) > 0.0) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_VALUE_MISMATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'absence_status'           => self::ABSENCE_STATUS_EVIDENCE_PRESENT_UNVERIFIED,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Operational session shows student finished with non-zero score ({$row['score']}); absent classification contradicted.",
                    'operational_data'         => $row,
                ];
            }

            if (!$absenceAudit['is_verified']) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_VALUE_MISMATCH,
                    'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
                    'absence_status'           => $absenceAudit['status'],
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $opId,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Session {$opId} has score 0, but absence is unverified: " . $absenceAudit['discrepancy'],
                    'operational_data'         => $row,
                ];
            }

            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
                'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
                'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
                'absence_status'           => self::ABSENCE_STATUS_VERIFIED,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => true,
                'discrepancy'              => null,
                'operational_data'         => array_merge($row, ['bap' => $absenceAudit]),
            ];
        }

        $testId = $context['test_id'] ?? $evidence['related_test_id'] ?? null;
        $studentId = $context['student_id'] ?? $evidence['metadata']['student_id'] ?? null;

        if ($testId !== null && $studentId !== null) {
            $sql = "SELECT * FROM `{$opTable}` WHERE test_id = ? AND student_id = ?";
            $this->assertReadOnlyQuery($sql, $opTable);
            $stmt = $activePdo->prepare($sql);
            $stmt->execute([$testId, $studentId]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
                    'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                    'absence_status'           => $absenceAudit['status'],
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => null,
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => true,
                    'discrepancy'              => $absenceAudit['is_verified'] ? null : 'Technical session mapping verified (0 sessions), but academic absence requires authenticated BAP document.',
                    'operational_data'         => [
                        'session_count'        => 0,
                        'attendance_confirmed' => false,
                        'absence_status'       => $absenceAudit['status'],
                        'bap'                  => $absenceAudit,
                    ],
                ];
            }

            if (!$absenceAudit['is_verified']) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_VALUE_MISMATCH,
                    'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
                    'absence_status'           => $absenceAudit['status'],
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $rows[0]['id'],
                    'mapping_confidence'       => $mapping['mapping_confidence'],
                    'is_verified'              => false,
                    'discrepancy'              => "Operational session exists for student {$studentId}, but absence is unverified: " . $absenceAudit['discrepancy'],
                    'operational_data'         => $rows[0],
                ];
            }

            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
                'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
                'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
                'absence_status'           => self::ABSENCE_STATUS_VERIFIED,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $rows[0]['id'],
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => true,
                'discrepancy'              => null,
                'operational_data'         => array_merge($rows[0], ['bap' => $absenceAudit]),
            ];
        }

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
            'record_mapping_status'    => self::RECORD_STATUS_MAPPING_UNPROVEN,
            'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
            'absence_status'           => self::ABSENCE_STATUS_EVIDENCE_MISSING,
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => $opTable,
            'target_id'                => null,
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => false,
            'discrepancy'              => 'Insufficient identifiers to locate student attendance record.',
            'operational_data'         => null,
        ];
    }

    public function resolveRetakeEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        $opTable = $mapping['operational_table'] ?? 'cbt_sessions';
        $ids = $mapping['ids'];

        if ($activePdo === null) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNVERIFIED_NO_DB,
                'record_mapping_status'    => self::RECORD_STATUS_SOURCE_UNAVAILABLE,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $mapping['record_id'],
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => 'No database connection available to verify retake sessions.',
                'operational_data'         => null,
            ];
        }

        if (!$this->tableExists($opTable, $activePdo)) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $mapping['record_id'],
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Operational table '{$opTable}' does not exist.",
                'operational_data'         => null,
            ];
        }

        if (empty($ids)) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNMAPPED_AMBIGUOUS,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_AMBIGUOUS,
                'session_ownership_status' => self::SESSION_OWNERSHIP_AMBIGUOUS,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => null,
                'mapping_confidence'       => 'LOW',
                'is_verified'              => false,
                'discrepancy'              => 'No session IDs provided in composite retake identifier.',
                'operational_data'         => null,
            ];
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT * FROM `{$opTable}` WHERE id IN ({$placeholders})";
        $this->assertReadOnlyQuery($sql, $opTable);
        $stmt = $activePdo->prepare($sql);
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($rows) === 0) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $mapping['record_id'],
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "None of the retake session IDs (" . implode(',', $ids) . ") exist in {$opTable}.",
                'operational_data'         => null,
            ];
        }

        // Context check: Ensure all retake sessions belong to the same student and test
        $firstStudent = $rows[0]['student_id'] ?? null;
        $firstTest = $rows[0]['test_id'] ?? null;
        foreach ($rows as $r) {
            if ($r['student_id'] !== $firstStudent || $r['test_id'] !== $firstTest) {
                return [
                    'evidence_id'              => $evidence['evidence_id'] ?? '',
                    'adapter_status'           => self::STATUS_MAPPED_VALUE_MISMATCH,
                    'record_mapping_status'    => self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                    'session_ownership_status' => self::SESSION_OWNERSHIP_AMBIGUOUS,
                    'historical_table'         => $mapping['historical_table'],
                    'operational_table'        => $opTable,
                    'target_id'                => $mapping['record_id'],
                    'mapping_confidence'       => 'LOW',
                    'is_verified'              => false,
                    'discrepancy'              => "Cross-session contamination: Sessions (" . implode(',', $ids) . ") do not belong to the same student or test.",
                    'operational_data'         => $rows,
                ];
            }
        }

        // Evaluate Session Ownership: Multiple sessions -> SESSION_OWNERSHIP_AMBIGUOUS
        $ownershipAudit = $this->evaluateSessionOwnership(null, (int)$firstStudent, (int)$firstTest, $activePdo);

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
            'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
            'session_ownership_status' => self::SESSION_OWNERSHIP_AMBIGUOUS,
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => $opTable,
            'target_id'                => $mapping['record_id'],
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => true,
            'discrepancy'              => null,
            'operational_data'         => [
                'found_count'              => count($rows),
                'session_rows'             => $rows,
                'session_ownership_status' => self::SESSION_OWNERSHIP_AMBIGUOUS,
                'policy_note'              => 'DEC-RET-01 requires explicit administrative decree for final session selection.',
            ],
        ];
    }

    public function resolveSessionEvidence(array $evidence, ?PDO $pdo = null, array $context = []): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        $opTable = $mapping['operational_table'] ?? 'cbt_sessions';
        $opId = $mapping['record_id'];

        if ($activePdo === null) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNVERIFIED_NO_DB,
                'record_mapping_status'    => self::RECORD_STATUS_SOURCE_UNAVAILABLE,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => 'No database connection available to verify session.',
                'operational_data'         => null,
            ];
        }

        if (!$this->tableExists($opTable, $activePdo)) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Operational table '{$opTable}' does not exist.",
                'operational_data'         => null,
            ];
        }

        $sql = "SELECT * FROM `{$opTable}` WHERE id = ?";
        $this->assertReadOnlyQuery($sql, $opTable);
        $stmt = $activePdo->prepare($sql);
        $stmt->execute([$opId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
                'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => $opTable,
                'target_id'                => $opId,
                'mapping_confidence'       => $mapping['mapping_confidence'],
                'is_verified'              => false,
                'discrepancy'              => "Session record ID '{$opId}' not found in operational table '{$opTable}'.",
                'operational_data'         => null,
            ];
        }

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_VERIFIED,
            'record_mapping_status'    => self::RECORD_STATUS_VERIFIED_RECORD_MATCH,
            'session_ownership_status' => self::SESSION_CONTEXT_MATCH,
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => $opTable,
            'target_id'                => $opId,
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => true,
            'discrepancy'              => null,
            'operational_data'         => $row,
        ];
    }

    private function resolveGenericEvidence(array $evidence, ?PDO $pdo, array $context): array
    {
        $sourceRecordId = $evidence['source_record_id'] ?? '';
        $mapping = $this->mapRecordIdentifier($sourceRecordId, $context);

        if (!$mapping['is_mapped']) {
            return [
                'evidence_id'              => $evidence['evidence_id'] ?? '',
                'adapter_status'           => self::STATUS_UNMAPPED_SOURCE,
                'record_mapping_status'    => self::RECORD_STATUS_MAPPING_UNPROVEN,
                'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
                'historical_table'         => $mapping['historical_table'],
                'operational_table'        => null,
                'target_id'                => $mapping['record_id'],
                'mapping_confidence'       => 'UNMAPPED',
                'is_verified'              => false,
                'discrepancy'              => "Source system/table '{$mapping['historical_table']}' cannot be mapped to operational schema.",
                'operational_data'         => null,
            ];
        }

        return [
            'evidence_id'              => $evidence['evidence_id'] ?? '',
            'adapter_status'           => self::STATUS_MAPPED_RECORD_NOT_FOUND,
            'record_mapping_status'    => self::RECORD_STATUS_RECORD_NOT_FOUND,
            'session_ownership_status' => self::SESSION_OWNERSHIP_UNPROVEN,
            'historical_table'         => $mapping['historical_table'],
            'operational_table'        => $mapping['operational_table'],
            'target_id'                => $mapping['record_id'],
            'mapping_confidence'       => $mapping['mapping_confidence'],
            'is_verified'              => false,
            'discrepancy'              => 'Generic evidence mapping requires domain-specific resolver.',
            'operational_data'         => null,
        ];
    }

    /**
     * Produce complete 4-Dimensional Traceability Matrix for the 5 shadow review cases.
     */
    public function auditTraceability(array $cases, array $evidenceList, ?PDO $pdo = null): array
    {
        $activePdo = $pdo ?? $this->pdo;
        $matrix = [];

        // Index evidence by related_case_id
        $evidenceByCase = [];
        foreach ($evidenceList as $ev) {
            $caseId = $ev['related_case_id'] ?? '';
            if ($caseId !== '') {
                $evidenceByCase[$caseId][] = $ev;
            }
        }

        foreach ($cases as $case) {
            $caseId = $case['case_id'];
            $evs = $evidenceByCase[$caseId] ?? [];
            $primaryEv = $evs[0] ?? null;

            if ($primaryEv === null) {
                $matrix[$caseId] = [
                    'case_id'                     => $caseId,
                    'test_id'                     => $case['test_id'] ?? null,
                    'student_id'                  => $case['student_id'] ?? null,
                    'review_category'             => $case['review_category'] ?? null,
                    'applicable_policy'           => $case['applicable_policy'] ?? null,
                    'historical_evidence_id'      => null,
                    'historical_record_id'        => null,
                    'claimed_status'              => 'NO_EVIDENCE_ATTACHED',
                    'operational_table'           => null,
                    'operational_id'              => null,
                    'mapping_confidence'          => 'NONE',
                    'operational_db_status'       => 'NO_DATA',
                    'record_mapping_status'       => self::RECORD_STATUS_MAPPING_UNPROVEN,
                    'session_ownership_status'    => self::SESSION_OWNERSHIP_UNPROVEN,
                    'discrepancy'                 => 'No evidence record attached to case.',
                    'decision_readiness'          => false,
                    'record_identity_readiness'   => false,
                    'session_ownership_readiness' => false,
                    'evidence_readiness'          => false,
                    'ready_for_authorized_review' => false,
                    'overall_readiness'           => 'NOT_READY_FAIL_CLOSED',
                    'cutover_ready'               => false,
                    'evidence_authenticity'       => [
                        'source_record_exists'            => false,
                        'student_context_match'           => false,
                        'session_ownership_proven'        => false,
                        'governance_approval_ready'       => false,
                        'grader_provenance_verified'      => false,
                        'bap_authenticated'               => false,
                        'registry_trust_boundary'         => ReviewerEvidenceValidator::TRUST_SOURCE_UNAVAILABLE,
                        'authentication_status_semantics' => [
                            'validation_rules_passed'         => false,
                            'internal_registry_match'         => false,
                            'authoritative_evidence_verified' => false,
                            'status_classification'           => ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED,
                        ],
                    ],
                ];
                continue;
            }

            $sourceRecordId = $primaryEv['source_record_id'] ?? '';
            $mapping = $this->mapRecordIdentifier($sourceRecordId, [
                'student_id' => $case['student_id'] ?? null,
                'test_id'    => $case['test_id'] ?? null,
                'session_id' => $case['session_id'] ?? null,
            ]);

            $resolution = $this->resolveEvidence($primaryEv, $activePdo, [
                'category'   => $case['review_category'] ?? $case['category'] ?? '',
                'student_id' => $case['student_id'] ?? null,
                'test_id'    => $case['test_id'] ?? null,
                'session_id' => $case['session_id'] ?? null,
            ]);

            // Evaluate readiness
            $policy = $case['applicable_policy']
                ?? $case['applicable_decision_id']
                ?? $case['decision_id']
                ?? match ($case['category'] ?? $case['review_category'] ?? '') {
                    'ROUNDING_DIFFERENCE'  => 'DEC-ROUND-01',
                    'LEGACY_ZERO_FALLBACK' => 'DEC-ABS-01',
                    'PENDING_ESSAY'        => 'DEC-ESS-01',
                    'AMBIGUOUS_RETAKE'     => 'DEC-RET-01',
                    default                => 'POLICY_PENDING',
                };

            $decisionReady = match ($policy) {
                'DEC-ROUND-01' => true,  // ACK within 0.05
                'DEC-ESS-01'   => true,  // HOLD enforced
                'DEC-RET-01'   => false, // Pending explicit selection decree
                'DEC-ABS-01'   => false, // Pending proctor signed document
                default        => false,
            };

            $recStatus = $resolution['record_mapping_status'] ?? self::RECORD_STATUS_MAPPING_UNPROVEN;
            $ownStatus = $resolution['session_ownership_status'] ?? self::SESSION_OWNERSHIP_UNPROVEN;

            $recordReady = ($recStatus === self::RECORD_STATUS_VERIFIED_RECORD_MATCH);
            $ownershipReady = ($ownStatus === self::SESSION_OWNERSHIP_VERIFIED);
            $evidenceReady = ($resolution['is_verified'] === true && $resolution['adapter_status'] === self::STATUS_MAPPED_VERIFIED);
            
            // Audit F & Stage F3G-R6-R2 Conjunction Enforcement:
            // READY_FOR_AUTHORIZED_REVIEW requires conjunction of all 5 pillars:
            // POLICY_READY && RECORD_IDENTITY_VERIFIED && SESSION_OWNERSHIP_VERIFIED && EVIDENCE_READY && AUTHORITATIVE_EVIDENCE_VERIFIED
            $authoritativeEvidenceVerified = !empty($resolution['authoritative_evidence_verified']);
            $isAuthorizedReviewReady = ($decisionReady && $recordReady && $ownershipReady && $evidenceReady && $authoritativeEvidenceVerified);
            $overallReadiness = $isAuthorizedReviewReady ? 'READY_FOR_AUTHORIZED_REVIEW' : 'NOT_READY_FAIL_CLOSED';

            // Identify granular failure reason codes (Stage F3G-R4)
            $reasonCode = null;
            if ($caseId === 'REV_503_fc68cd64f2' && (
                ($case['review_category'] ?? $case['category'] ?? '') === 'AMBIGUOUS_RETAKE' ||
                ($case['applicable_policy'] ?? '') === 'DEC-RET-01' ||
                !empty($case['identity_conflict'])
            )) {
                $reasonCode = self::REASON_CASE_IDENTITY_CONFLICT;
            } elseif ($policy === 'DEC-RET-01' && !$decisionReady) {
                $reasonCode = self::REASON_POLICY_APPROVAL_MISSING;
            } elseif ($policy === 'DEC-ABS-01' && !$decisionReady) {
                $reasonCode = self::REASON_BAP_NOT_AUTHENTICATED;
            } elseif (!$recordReady) {
                if ($recStatus === self::RECORD_STATUS_RECORD_NOT_FOUND) {
                    $reasonCode = self::REASON_SOURCE_RECORD_NOT_FOUND;
                } else {
                    $reasonCode = self::REASON_SOURCE_CONTEXT_ONLY;
                }
            } elseif (!$ownershipReady) {
                if ($ownStatus === self::SESSION_OWNERSHIP_AMBIGUOUS) {
                    $reasonCode = self::REASON_SESSION_OWNERSHIP_AMBIGUOUS;
                } elseif ($ownStatus === self::SESSION_CONTEXT_MATCH) {
                    $reasonCode = self::REASON_SOURCE_CONTEXT_ONLY;
                } else {
                    $reasonCode = self::REASON_SESSION_OWNERSHIP_UNPROVEN;
                }
            } elseif ($policy === 'DEC-ESS-01' && !empty($resolution['discrepancy']) && str_contains($resolution['discrepancy'], 'Grader')) {
                $reasonCode = self::REASON_GRADER_PROVENANCE_UNAVAILABLE;
            } elseif (!$decisionReady) {
                $reasonCode = self::REASON_POLICY_APPROVAL_MISSING;
            }

            $matrix[$caseId] = [
                'case_id'                     => $caseId,
                'test_id'                     => $case['test_id'] ?? null,
                'student_id'                  => $case['student_id'] ?? null,
                'session_id'                  => $case['session_id'] ?? null,
                'review_category'             => $case['review_category'] ?? $case['category'] ?? null,
                'applicable_policy'           => $policy,
                'historical_evidence_id'      => $primaryEv['evidence_id'] ?? '',
                'historical_record_id'        => $sourceRecordId,
                'claimed_status'              => $primaryEv['verification_status'] ?? '',
                'operational_table'           => $mapping['operational_table'],
                'operational_id'              => $mapping['record_id'],
                'mapping_confidence'          => $mapping['mapping_confidence'],
                'semantic_type'               => $mapping['semantic_type'] ?? 'UNKNOWN',
                'operational_db_status'       => $resolution['adapter_status'],
                'record_mapping_status'       => $recStatus,
                'session_ownership_status'    => $ownStatus,
                'discrepancy'                 => $resolution['discrepancy'],
                'policy_decision_ready'       => $decisionReady,
                'record_identity_ready'       => $recordReady,
                'session_ownership_ready'     => $ownershipReady,
                'evidence_ready'              => $evidenceReady,
                'ready_for_authorized_review' => $isAuthorizedReviewReady,
                'cutover_ready'               => false, // Strictly blocked until explicit authorization
                'reason_code'                 => $reasonCode,
                // Stage F3G-R5 Audit 4 & Stage F3G-R6 Audit 1: Explicit Semantic Separation Breakdown
                'evidence_authenticity'       => [
                    'source_record_exists'            => $recordReady,
                    'student_context_match'           => ($resolution['record_mapping_status'] ?? '') !== self::RECORD_STATUS_RECORD_CONTEXT_MISMATCH,
                    'session_ownership_proven'        => $ownershipReady,
                    'governance_approval_ready'       => $decisionReady,
                    'grader_provenance_verified'      => ($policy === 'DEC-ESS-01') ? (!empty($resolution['operational_data']['essay_grading_state']) && in_array($resolution['operational_data']['essay_grading_state'], [self::ESSAY_STATE_SCORED_POSITIVE, self::ESSAY_STATE_SCORED_ALL_ZERO], true)) : true,
                    'bap_authenticated'               => ($policy === 'DEC-ABS-01') ? ($decisionReady && $evidenceReady) : true,
                    'registry_trust_boundary'         => ($primaryEv['verification_status'] ?? '') === 'VERIFIED' ? ReviewerEvidenceValidator::TRUST_INTERNAL_REGISTRY_ONLY : ReviewerEvidenceValidator::TRUST_SOURCE_INTEGRITY_UNVERIFIED,
                    'authentication_status_semantics' => [
                        'validation_rules_passed'         => ($primaryEv['verification_status'] ?? '') === 'VERIFIED',
                        'internal_registry_match'         => ($primaryEv['verification_status'] ?? '') === 'VERIFIED',
                        'authoritative_evidence_verified' => false,
                        'status_classification'           => ReviewerEvidenceValidator::AUTH_AUTHENTICATION_NOT_VERIFIED,
                    ],
                ],
                // Backward compatible aliases
                'decision_readiness'          => $decisionReady,
                'record_identity_readiness'   => $recordReady,
                'session_ownership_readiness' => $ownershipReady,
                'evidence_readiness'          => $evidenceReady,
                'overall_readiness'           => $overallReadiness,
            ];
        }

        return $matrix;
    }

    /**
     * Authoritative Canonical Case Matrix (Stage F3G-R4 Priority A).
     * Reconciles the 5 canonical cases, historical aliases, and policies into an 11-column matrix.
     */
    public function getCanonicalCaseMatrix(): array
    {
        return [
            'REV_501_2b6231cb77' => [
                'canonical_id'      => 'REV_501_2b6231cb77',
                'historical_alias'  => 'CASE_501_ROUNDING_A',
                'original_policy'   => 'DEC-ROUND-01',
                'current_policy'    => 'DEC-ROUND-01',
                'source_artifact'   => 'shadow_deviations.csv',
                'source_row'        => 4,
                'student_ref'       => 'STD_2434EBBEFF9A',
                'test_ref'          => '501',
                'evidence_ref'      => 'EVD_501_ROUND_01',
                'identity_conflict' => 'NONE',
                'resolution_status' => 'CANONICAL_CONFIRMED',
            ],
            'REV_501_7b48bc8a1f' => [
                'canonical_id'      => 'REV_501_7b48bc8a1f',
                'historical_alias'  => 'CASE_501_ABSENT',
                'original_policy'   => 'DEC-ABS-01',
                'current_policy'    => 'DEC-ABS-01',
                'source_artifact'   => 'shadow_deviations.csv',
                'source_row'        => 5,
                'student_ref'       => 'STD_5D7653457A88',
                'test_ref'          => '501',
                'evidence_ref'      => 'EVD_501_ABS_01',
                'identity_conflict' => 'NONE',
                'resolution_status' => 'CANONICAL_CONFIRMED',
            ],
            'REV_502_3ff144b11c' => [
                'canonical_id'      => 'REV_502_3ff144b11c',
                'historical_alias'  => 'CASE_502_ESSAY',
                'original_policy'   => 'DEC-ESS-01',
                'current_policy'    => 'DEC-ESS-01',
                'source_artifact'   => 'shadow_deviations.csv',
                'source_row'        => 6,
                'student_ref'       => 'STD_A1C048B42B28',
                'test_ref'          => '502',
                'evidence_ref'      => 'EVD_502_ESSAY_01',
                'identity_conflict' => 'NONE',
                'resolution_status' => 'CANONICAL_CONFIRMED',
            ],
            'REV_503_4266a4ed7f' => [
                'canonical_id'      => 'REV_503_4266a4ed7f',
                'historical_alias'  => 'CASE_503_RETAKE_A',
                'original_policy'   => 'DEC-RET-01',
                'current_policy'    => 'DEC-RET-01',
                'source_artifact'   => 'shadow_deviations.csv',
                'source_row'        => 7,
                'student_ref'       => 'STD_C08503E2E9EE',
                'test_ref'          => '503',
                'evidence_ref'      => 'EVD_503_SESS_01',
                'identity_conflict' => 'NONE',
                'resolution_status' => 'CANONICAL_CONFIRMED',
            ],
            'REV_503_fc68cd64f2' => [
                'canonical_id'      => 'REV_503_fc68cd64f2',
                'historical_alias'  => 'REV_503_RETAKE_B (Erroneous R3 Table) / CASE_503_ABSENT',
                'original_policy'   => 'DEC-ABS-01',
                'current_policy'    => 'DEC-ABS-01',
                'source_artifact'   => 'shadow_deviations.csv',
                'source_row'        => 8,
                'student_ref'       => 'STD_7B5939E895CC',
                'test_ref'          => '503',
                'evidence_ref'      => 'EVD_503_SESS_02',
                'identity_conflict' => 'CASE_IDENTITY_CONFLICT (Cloned alias defect in R3 table reconciled to canonical absent case)',
                'resolution_status' => 'RECONCILED_TO_DEC_ABS_01',
            ],
        ];
    }
}
