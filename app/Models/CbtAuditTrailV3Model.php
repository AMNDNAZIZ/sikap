<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtAuditTrailV3Model
 *
 * Tamper-evident append-only cryptographic audit trail.
 * Implements HMAC-SHA256 hash chaining and sequence verification.
 */
class CbtAuditTrailV3Model extends Model
{
    protected $table            = 'cbt_audit_trail_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'seq_no',
        'event_type',
        'entity_table',
        'entity_id',
        'actor_id',
        'actor_role',
        'payload_canonical',
        'key_id',
        'prev_hash',
        'record_hash',
        'created_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

    /**
     * Append a new tamper-evident audit record to the hash chain.
     */
    public function appendLog(
        string $eventType,
        string $entityTable,
        string $entityId,
        int $actorId,
        string $actorRole,
        array $payload,
        string $keyId = 'KEY-HMAC-V1',
        string $secretKey = 'ESPATA_DEFAULT_TEST_KEY_2026'
    ): array {
        // 1. Get the latest record for hash chaining
        $latest = $this->orderBy('seq_no', 'DESC')->first();
        $prevHash = $latest ? $latest['record_hash'] : self::GENESIS_HASH;
        $nextSeq = $latest ? ((int)$latest['seq_no'] + 1) : 1;

        // 2. Canonicalize payload
        ksort($payload);
        $payloadCanonical = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        // 3. Compute HMAC-SHA256 chained hash
        $hashMaterial = "{$nextSeq}|{$eventType}|{$entityTable}|{$entityId}|{$actorId}|{$actorRole}|{$payloadCanonical}|{$prevHash}";
        $recordHash = hash_hmac('sha256', $hashMaterial, $secretKey);

        $record = [
            'seq_no'            => $nextSeq,
            'event_type'        => $eventType,
            'entity_table'      => $entityTable,
            'entity_id'         => $entityId,
            'actor_id'          => $actorId,
            'actor_role'        => $actorRole,
            'payload_canonical' => $payloadCanonical,
            'key_id'            => $keyId,
            'prev_hash'         => $prevHash,
            'record_hash'       => $recordHash,
            'created_at'        => date('Y-m-d H:i:s'),
        ];

        $insertId = $this->insert($record, true);
        $record['id'] = $insertId;

        return $record;
    }

    /**
     * Verify the integrity of the audit hash chain.
     * Returns ['valid' => bool, 'total_records' => int, 'broken_seq' => ?int]
     */
    public function verifyChain(string $secretKey = 'ESPATA_DEFAULT_TEST_KEY_2026'): array
    {
        $records = $this->orderBy('seq_no', 'ASC')->findAll();
        $expectedPrev = self::GENESIS_HASH;
        $expectedSeq = 1;

        foreach ($records as $row) {
            $seqNo = (int)$row['seq_no'];

            // Verify sequence continuity
            if ($seqNo !== $expectedSeq) {
                return [
                    'valid'         => false,
                    'error'         => "Sequence gap detected. Expected {$expectedSeq}, found {$seqNo}",
                    'broken_seq'    => $seqNo,
                    'total_records' => count($records),
                ];
            }

            // Verify previous hash linkage
            if ($row['prev_hash'] !== $expectedPrev) {
                return [
                    'valid'         => false,
                    'error'         => "Hash chain linkage mismatch at seq {$seqNo}",
                    'broken_seq'    => $seqNo,
                    'total_records' => count($records),
                ];
            }

            // Recalculate hash
            $hashMaterial = "{$seqNo}|{$row['event_type']}|{$row['entity_table']}|{$row['entity_id']}|{$row['actor_id']}|{$row['actor_role']}|{$row['payload_canonical']}|{$row['prev_hash']}";
            $recalculated = hash_hmac('sha256', $hashMaterial, $secretKey);

            if ($recalculated !== $row['record_hash']) {
                return [
                    'valid'         => false,
                    'error'         => "HMAC signature mismatch at seq {$seqNo}",
                    'broken_seq'    => $seqNo,
                    'total_records' => count($records),
                ];
            }

            $expectedPrev = $row['record_hash'];
            $expectedSeq++;
        }

        return [
            'valid'         => true,
            'error'         => null,
            'broken_seq'    => null,
            'total_records' => count($records),
        ];
    }
}

