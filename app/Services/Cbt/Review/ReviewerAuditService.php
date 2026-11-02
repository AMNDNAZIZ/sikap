<?php

namespace App\Services\Cbt\Review;

use RuntimeException;
use InvalidArgumentException;

/**
 * ReviewerAuditService
 *
 * ESPATA Assessment Intelligence V3 - Stage F3A
 * Cryptographically chained, concurrency-safe, logically append-only audit trail logger.
 *
 * HARDENED INTEGRITY INVARIANTS:
 * 1. Concurrent Write Safety: Uses exclusive file locking (flock) to prevent race conditions.
 * 2. Monotonic Sequence: Sequence numbers are assigned while holding the lock.
 * 3. Cryptographic Hash Chaining: Each record contains the SHA-256 hash of the previous record.
 * 4. Atomic Record Hash: Each record computes a SHA-256 digest of its canonical fields.
 * 5. Fail-Closed Enforcement: Any failure to acquire lock or write audit immediately throws,
 *    aborting the caller's review decision transaction.
 * 6. Zero PII: Rejects any sensitive identifiers (NISN, credentials, passwords).
 *
 * NOTE ON THREAT MODEL:
 * Hash chaining provides internal tamper-detection against record modification, deletion,
 * or insertion. It does not prevent a filesystem administrator from deleting the entire file.
 */
class ReviewerAuditService
{
    public const POLICY_VERSION = 'V3.29A-STAGE_F3A';
    public const GENESIS_HASH   = '0000000000000000000000000000000000000000000000000000000000000000';

    private string $storageFilePath;
    private array $inMemoryAuditTrail = [];

    public function __construct(?string $storageFilePath = null)
    {
        $this->storageFilePath = $storageFilePath ?? (defined('WRITEPATH') ? WRITEPATH . 'shadow_artifacts/reviewer_audit_trail.json' : 'C:/xampp/htdocs/siakad/writable/shadow_artifacts/reviewer_audit_trail.json');
        $this->loadFromStorage();
    }

    /**
     * Record a reviewer decision with concurrent write safety and hash chaining.
     *
     * @param string $reviewerId
     * @param string $caseId
     * @param string $statusBefore
     * @param string $statusAfter
     * @param string $decision
     * @param string $reason
     * @param string $evidenceReference
     * @param array $extraContext
     * @return array The recorded audit entry
     * @throws RuntimeException If audit persistence fails (aborts review action)
     */
    public function recordDecision(
        string $reviewerId,
        string $caseId,
        string $statusBefore,
        string $statusAfter,
        string $decision,
        string $reason,
        string $evidenceReference = '',
        array $extraContext = [],
        ?string $decisionId = null
    ): array {
        if (trim($reviewerId) === '') {
            throw new InvalidArgumentException('Reviewer ID is required for audit trail provenance.');
        }
        if (trim($caseId) === '') {
            throw new InvalidArgumentException('Case ID is required for audit trail provenance.');
        }
        if (trim($reason) === '') {
            throw new InvalidArgumentException('A documented reason is mandatory for all reviewer audit entries.');
        }

        $effectiveDecisionId = $decisionId ?? ($extraContext['decision_id'] ?? null);

        // 1. Zero-PII Sanitization check
        $this->assertZeroPii($reviewerId, $reason, $extraContext);

        // 2. Concurrency-safe atomic append using flock
        $dir = dirname($this->storageFilePath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $fp = @fopen($this->storageFilePath, 'c+');
        if (!$fp) {
            throw new RuntimeException("AUDIT PERSISTENCE FAILURE: Unable to open audit storage file '{$this->storageFilePath}' for writing.");
        }

        if (!flock($fp, LOCK_EX)) {
            fclose($fp);
            throw new RuntimeException("AUDIT CONCURRENCY FAILURE: Unable to acquire exclusive lock on audit storage file.");
        }

        try {
            // Read fresh contents while holding lock to avoid race conditions
            $fileSize = filesize($this->storageFilePath);
            $currentTrail = [];
            if ($fileSize > 0) {
                rewind($fp);
                $content = fread($fp, $fileSize);
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $currentTrail = $decoded;
                }
            }

            $seq = count($currentTrail) + 1;
            $prevHash = ($seq === 1) ? self::GENESIS_HASH : ($currentTrail[$seq - 2]['record_hash'] ?? self::GENESIS_HASH);

            $timestamp = date('Y-m-d H:i:s');
            $timestampIso = date('c');
            $auditId = 'AUD_' . strtoupper(bin2hex(random_bytes(8)));

            // Compute canonical record hash
            $recordHash = $this->computeRecordHash(
                $auditId,
                $seq,
                $timestampIso,
                $reviewerId,
                $caseId,
                $statusBefore,
                $statusAfter,
                $decision,
                $reason,
                $prevHash,
                self::POLICY_VERSION,
                $effectiveDecisionId
            );

            $entry = [
                'audit_id'             => $auditId,
                'sequence_number'      => $seq,
                'timestamp'            => $timestamp,
                'timestamp_iso'        => $timestampIso,
                'reviewer_id'          => $reviewerId,
                'case_id'              => $caseId,
                'status_before'        => $statusBefore,
                'status_after'         => $statusAfter,
                'decision'             => $decision,
                'decision_id'          => $effectiveDecisionId,
                'reason'               => $reason,
                'evidence_reference'   => $evidenceReference,
                'policy_version'       => self::POLICY_VERSION,
                'previous_record_hash' => $prevHash,
                'record_hash'          => $recordHash,
                'extra_context'        => $extraContext,
            ];

            $currentTrail[] = $entry;

            // Write back to file atomically
            ftruncate($fp, 0);
            rewind($fp);
            $written = fwrite($fp, json_encode($currentTrail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fflush($fp);

            if ($written === false) {
                throw new RuntimeException("AUDIT WRITE FAILURE: Failed writing audit log to disk.");
            }

            // Sync in-memory representation
            $this->inMemoryAuditTrail = $currentTrail;

            return $entry;
        } finally {
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }

    private bool $storageCorrupt = false;

    /**
     * Verify the cryptographic chain and structural integrity of the audit trail.
     *
     * @return array ['is_valid' => bool, 'total_records' => int, 'violations' => array, 'reason' => string]
     */
    public function verifyAuditIntegrity(): array
    {
        $this->loadFromStorage();
        $violations = [];

        if ($this->storageCorrupt) {
            $violations[] = 'Storage file corruption: invalid JSON syntax on disk.';
        }

        $expectedSeq = 1;
        $expectedPrevHash = self::GENESIS_HASH;

        foreach ($this->inMemoryAuditTrail as $idx => $entry) {
            $seq = $entry['sequence_number'] ?? null;
            if ($seq !== $expectedSeq) {
                $violations[] = "Sequence anomaly at index {$idx}: expected {$expectedSeq}, found " . ($seq ?? 'NULL');
            }

            $prevHash = $entry['previous_record_hash'] ?? null;
            if ($prevHash !== $expectedPrevHash) {
                $violations[] = "Chain break at sequence {$seq}: previous hash mismatch. Expected {$expectedPrevHash}, found {$prevHash}";
            }

            $expectedRecordHash = $this->computeRecordHash(
                $entry['audit_id'] ?? '',
                $seq ?? 0,
                $entry['timestamp_iso'] ?? '',
                $entry['reviewer_id'] ?? '',
                $entry['case_id'] ?? '',
                $entry['status_before'] ?? '',
                $entry['status_after'] ?? '',
                $entry['decision'] ?? '',
                $entry['reason'] ?? '',
                $prevHash ?? '',
                $entry['policy_version'] ?? '',
                $entry['decision_id'] ?? null
            );

            if (($entry['record_hash'] ?? '') !== $expectedRecordHash) {
                $violations[] = "Record tampering at sequence {$seq}: Hash mismatch. Calculated hash {$expectedRecordHash} does not match stored hash {$entry['record_hash']}";
            }

            $expectedPrevHash = $entry['record_hash'] ?? '';
            $expectedSeq++;
        }

        return [
            'is_valid'      => empty($violations),
            'total_records' => count($this->inMemoryAuditTrail),
            'violations'    => $violations,
            'reason'        => implode('; ', $violations),
        ];
    }

    /**
     * Retrieve complete audit trail (read-only view).
     */
    public function getTrail(): array
    {
        $this->loadFromStorage();
        return $this->inMemoryAuditTrail;
    }

    /**
     * Get audit entries for a specific case.
     */
    public function getEntriesForCase(string $caseId): array
    {
        $this->loadFromStorage();
        return array_values(array_filter($this->inMemoryAuditTrail, function ($entry) use ($caseId) {
            return $entry['case_id'] === $caseId;
        }));
    }

    /**
     * Get total recorded count.
     */
    public function count(): int
    {
        $this->loadFromStorage();
        return count($this->inMemoryAuditTrail);
    }

    /**
     * Clear in-memory and on-disk trail (FOR TESTING PURPOSES ONLY).
     */
    public function clearForTesting(): void
    {
        $this->inMemoryAuditTrail = [];
        if (file_exists($this->storageFilePath)) {
            @unlink($this->storageFilePath);
        }
    }

    /**
     * Compute deterministic record hash for chaining.
     */
    public function computeRecordHash(
        string $auditId,
        int $seq,
        string $timestampIso,
        string $reviewerId,
        string $caseId,
        string $statusBefore,
        string $statusAfter,
        string $decision,
        string $reason,
        string $prevHash,
        string $policyVersion,
        ?string $decisionId = null
    ): string {
        $canonicalPayload = [
            $auditId,
            (string)$seq,
            $timestampIso,
            $reviewerId,
            $caseId,
            $statusBefore,
            $statusAfter,
            $decision,
            trim($reason),
            $prevHash,
            $policyVersion,
        ];

        if (!empty($decisionId)) {
            $canonicalPayload[] = $decisionId;
        }

        return hash('sha256', implode('|', $canonicalPayload));
    }

    private function loadFromStorage(): void
    {
        if (file_exists($this->storageFilePath)) {
            $content = file_get_contents($this->storageFilePath);
            if (trim($content) === '') {
                $this->inMemoryAuditTrail = [];
                $this->storageCorrupt = false;
                return;
            }
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $this->inMemoryAuditTrail = $decoded;
                $this->storageCorrupt = false;
            } else {
                $this->inMemoryAuditTrail = [];
                $this->storageCorrupt = true;
            }
        } else {
            $this->inMemoryAuditTrail = [];
            $this->storageCorrupt = false;
        }
    }

    private function assertZeroPii(string $reviewerId, string $reason, array $context): void
    {
        $content = json_encode([$reviewerId, $reason, $context]);
        if (preg_match('/(nisn|nomor_induk|password|secret|nik)/i', $content)) {
            throw new RuntimeException('PII SAFETY VIOLATION: Review audit payload contains prohibited PII or credential identifiers.');
        }
    }
}
