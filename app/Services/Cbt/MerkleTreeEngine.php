<?php

namespace App\Services\Cbt;

use InvalidArgumentException;

/**
 * MerkleTreeEngine
 * Cryptographically sound 256-bit Merkle Tree implementation for CBT migration verification.
 *
 * Implements:
 * - Full 256-bit SHA-256 (zero 64-bit truncation).
 * - RFC 6962 domain separation: \x00 for leaves, \x01 for internal nodes.
 * - Deterministic sorting of input records by legacy ID ASC.
 * - Explicit promotion policy for odd leaf counts.
 * - Fixed canonical hash for empty batches.
 */
class MerkleTreeEngine
{
    public const LEAF_PREFIX     = "\x00";
    public const INTERNAL_PREFIX = "\x01";
    public const EMPTY_BATCH_TAG = "ESPATA-EMPTY-BATCH-V1\0";

    /**
     * Compute the 64-character hex Merkle Root for an array of legacy answer records.
     * Records are automatically sorted by 'id' ASC before leaf computation.
     *
     * @param array $records Array of raw legacy answer records
     * @return string 64-character hex SHA-256
     */
    public static function computeRootFromRecords(array $records): string
    {
        if (empty($records)) {
            return hash('sha256', self::EMPTY_BATCH_TAG, false);
        }

        // 1. Sort records deterministically by ID ASC
        usort($records, function ($a, $b) {
            $idA = (int)($a['id'] ?? $a['legacy_answer_id'] ?? 0);
            $idB = (int)($b['id'] ?? $b['legacy_answer_id'] ?? 0);
            return $idA <=> $idB;
        });

        // 2. Compute canonical leaf hashes using domain separation \x00
        $leafHashes = [];
        foreach ($records as $record) {
            $serialized = EspataCanonicalSerializer::serialize($record);
            // Leaf hash: SHA-256(\x00 || serialized_bytes)
            $leafHashes[] = hash('sha256', self::LEAF_PREFIX . $serialized, true);
        }

        return bin2hex(self::computeRootFromBinaryLeaves($leafHashes));
    }

    /**
     * Compute 64-character hex Merkle Root directly from an array of pre-computed leaf hashes.
     *
     * @param array $leafHashes Array of 64-char hex strings or 32-byte binary hashes
     * @return string 64-character hex SHA-256
     */
    public static function computeRootFromLeafHashes(array $leafHashes): string
    {
        if (empty($leafHashes)) {
            return hash('sha256', self::EMPTY_BATCH_TAG, false);
        }

        $binaryLeaves = [];
        foreach ($leafHashes as $h) {
            if (strlen($h) === 64 && ctype_xdigit($h)) {
                $binaryLeaves[] = hex2bin($h);
            } elseif (strlen($h) === 32) {
                $binaryLeaves[] = $h;
            } else {
                throw new InvalidArgumentException("Invalid hash format: must be 64 hex characters or 32 binary bytes.");
            }
        }

        return bin2hex(self::computeRootFromBinaryLeaves($binaryLeaves));
    }

    /**
     * Internal recursive / iterative Merkle tree reduction.
     *
     * @param array $nodes Array of 32-byte binary hashes
     * @return string 32-byte binary root
     */
    private static function computeRootFromBinaryLeaves(array $nodes): string
    {
        if (count($nodes) === 1) {
            return $nodes[0];
        }

        while (count($nodes) > 1) {
            $nextLevel = [];
            $count = count($nodes);

            for ($i = 0; $i < $count; $i += 2) {
                if ($i + 1 < $count) {
                    // Internal node hash: SHA-256(\x01 || left || right)
                    $combined = self::INTERNAL_PREFIX . $nodes[$i] . $nodes[$i + 1];
                    $nextLevel[] = hash('sha256', $combined, true);
                } else {
                    // Odd leaf policy: Promote the solitary node to the next level unchanged
                    $nextLevel[] = $nodes[$i];
                }
            }

            $nodes = $nextLevel;
        }

        return $nodes[0];
    }
}

