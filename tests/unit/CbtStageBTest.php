<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Cbt\EspataCanonicalSerializer;
use App\Services\Cbt\MerkleTreeEngine;
use App\Models\CbtAnswerQuarantineModel;

/**
 * CbtStageBTest
 * Comprehensive test suite for Canonical Serializer, Merkle Tree, and Lossless Quarantine.
 */
class CbtStageBTest extends CIUnitTestCase
{
    public function testCanonicalSerializerFullRow()
    {
        $row = [
            'id'               => 101,
            'student_id'       => 5001,
            'test_id'          => 202,
            'question_id'      => 303,
            'answer'           => 'A',
            'updated_at_micro' => '1708654200.123456',
            'version'          => 1,
            'is_doubtful'      => 0,
            'score'            => 1.0,
            'updated_at'       => '2026-02-23 08:30:00',
        ];

        $hash1 = EspataCanonicalSerializer::hash($row);
        $hash2 = EspataCanonicalSerializer::hash($row);

        $this->assertSame(64, strlen($hash1));
        $this->assertSame($hash1, $hash2, 'Serializer must be 100% deterministic');

        // Round-trip deserialization verification
        $stream = EspataCanonicalSerializer::serialize($row);
        $deserialized = EspataCanonicalSerializer::deserialize($stream);

        $this->assertSame($row['id'], $deserialized['id']);
        $this->assertSame($row['student_id'], $deserialized['student_id']);
        $this->assertSame($row['test_id'], $deserialized['test_id']);
        $this->assertSame($row['question_id'], $deserialized['question_id']);
        $this->assertSame($row['answer'], $deserialized['answer']);
        $this->assertSame($row['updated_at_micro'], $deserialized['updated_at_micro']);
        $this->assertSame($row['version'], $deserialized['version']);
        $this->assertSame($row['is_doubtful'], $deserialized['is_doubtful']);
        $this->assertSame($row['score'], $deserialized['score']);
        $this->assertSame($row['updated_at'], $deserialized['updated_at']);
    }

    public function testNullVersusEmptyStringDistinction()
    {
        $rowNull = [
            'id'          => 1,
            'student_id'  => 2,
            'test_id'     => 3,
            'question_id' => 4,
            'answer'      => null,
            'score'       => null,
        ];

        $rowEmpty = [
            'id'          => 1,
            'student_id'  => 2,
            'test_id'     => 3,
            'question_id' => 4,
            'answer'      => '',
            'score'       => 0.0,
        ];

        $hashNull = EspataCanonicalSerializer::hash($rowNull);
        $hashEmpty = EspataCanonicalSerializer::hash($rowEmpty);

        $this->assertNotSame($hashNull, $hashEmpty, 'SQL NULL must never collide with empty string or numeric zero');
    }

    public function testZeroDateHandling()
    {
        $rowZeroDate = [
            'id'          => 1,
            'student_id'  => 2,
            'test_id'     => 3,
            'question_id' => 4,
            'answer'      => 'B',
            'updated_at'  => '0000-00-00 00:00:00',
        ];

        $rowNullDate = [
            'id'          => 1,
            'student_id'  => 2,
            'test_id'     => 3,
            'question_id' => 4,
            'answer'      => 'B',
            'updated_at'  => null,
        ];

        $hashZero = EspataCanonicalSerializer::hash($rowZeroDate);
        $hashNull = EspataCanonicalSerializer::hash($rowNullDate);

        $this->assertNotSame($hashZero, $hashNull, 'Zero date must produce a distinct hash from SQL NULL');
    }

    public function testLongAnswerPayloadExceeding64KB()
    {
        $largeText = str_repeat('ABCDEFGH12345678', 5000); // 80,000 bytes (> 65,535 bytes)
        $this->assertGreaterThan(65535, strlen($largeText));

        $row = [
            'id'          => 1,
            'student_id'  => 2,
            'test_id'     => 3,
            'question_id' => 4,
            'answer'      => $largeText,
            'score'       => 50.0,
        ];

        $stream = EspataCanonicalSerializer::serialize($row);
        $deserialized = EspataCanonicalSerializer::deserialize($stream);

        $this->assertSame($largeText, $deserialized['answer'], 'Serializer must losslessly handle answers exceeding 64KB');
    }

    public function testOneByteSensitivity()
    {
        $rowA = [
            'id'          => 10,
            'student_id'  => 20,
            'test_id'     => 30,
            'question_id' => 40,
            'answer'      => 'A',
            'score'       => 1.0,
        ];

        $rowB = [
            'id'          => 10,
            'student_id'  => 20,
            'test_id'     => 30,
            'question_id' => 40,
            'answer'      => 'B', // 1-byte change
            'score'       => 1.0,
        ];

        $hashA = EspataCanonicalSerializer::hash($rowA);
        $hashB = EspataCanonicalSerializer::hash($rowB);

        $this->assertNotSame($hashA, $hashB, 'A single byte change must completely alter the hash');
    }

    public function testMerkleTreeSingleLeaf()
    {
        $record = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A']
        ];

        $root = MerkleTreeEngine::computeRootFromRecords($record);
        $this->assertSame(64, strlen($root));
    }

    public function testMerkleTreeTwoLeaves()
    {
        $records = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
        ];

        $root = MerkleTreeEngine::computeRootFromRecords($records);
        $this->assertSame(64, strlen($root));
    }

    public function testMerkleTreeThreeLeavesOddPolicy()
    {
        $records = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C'],
        ];

        $root = MerkleTreeEngine::computeRootFromRecords($records);
        $this->assertSame(64, strlen($root));
    }

    public function testMerkleTreeFiveLeaves()
    {
        $records = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C'],
            ['id' => 4, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1003, 'answer' => 'D'],
            ['id' => 5, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1004, 'answer' => 'E'],
        ];

        $root = MerkleTreeEngine::computeRootFromRecords($records);
        $this->assertSame(64, strlen($root));
    }

    public function testMerkleTreeDeterministicOrderInvariance()
    {
        $recordsOrder1 = [
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C'],
        ];

        $recordsOrder2 = [
            ['id' => 3, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1002, 'answer' => 'C'],
            ['id' => 1, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1000, 'answer' => 'A'],
            ['id' => 2, 'student_id' => 10, 'test_id' => 100, 'question_id' => 1001, 'answer' => 'B'],
        ];

        $root1 = MerkleTreeEngine::computeRootFromRecords($recordsOrder1);
        $root2 = MerkleTreeEngine::computeRootFromRecords($recordsOrder2);

        $this->assertSame($root1, $root2, 'Merkle root must sort records by ID ASC and remain invariant to input ordering');
    }

    public function testMerkleTreeEmptyBatch()
    {
        $rootEmpty1 = MerkleTreeEngine::computeRootFromRecords([]);
        $rootEmpty2 = MerkleTreeEngine::computeRootFromLeafHashes([]);

        $this->assertSame(64, strlen($rootEmpty1));
        $this->assertSame($rootEmpty1, $rootEmpty2);
    }

    public function testQuarantineLosslessRecovery()
    {
        $originalLegacy = [
            'id'               => 789,
            'student_id'       => 456,
            'test_id'          => 123,
            'question_id'      => 999,
            'answer'           => 'Pilihan Esai Siswa',
            'updated_at_micro' => '1708654200.654321',
            'version'          => 2,
            'is_doubtful'      => 1,
            'score'            => 85.5,
            'updated_at'       => '2026-02-23 10:15:30',
        ];

        // Simulate quarantine storage
        $quarantineRow = [
            'id'                      => 1,
            'legacy_answer_id'        => $originalLegacy['id'],
            'student_id'              => $originalLegacy['student_id'],
            'test_id'                 => $originalLegacy['test_id'],
            'question_id'             => $originalLegacy['question_id'],
            'answer_raw'              => $originalLegacy['answer'],
            'score_raw'               => $originalLegacy['score'],
            'is_doubtful_raw'         => $originalLegacy['is_doubtful'],
            'version_raw'             => $originalLegacy['version'],
            'updated_at_raw'          => $originalLegacy['updated_at'],
            'updated_at_micro_raw'    => $originalLegacy['updated_at_micro'],
            'quarantine_reason'       => 'RESET_AFFECTED_DISCREPANCY',
            'quarantine_details_json' => json_encode(['note' => 'Session deleted prior to answer insertion']),
            'canonical_hash'          => EspataCanonicalSerializer::hash($originalLegacy),
            'quarantined_at'          => '2026-11-01 09:00:00',
        ];

        // Execute recovery reconstruction
        $reconstructed = CbtAnswerQuarantineModel::reconstructLegacyRecord($quarantineRow);

        $this->assertSame($originalLegacy, $reconstructed, 'Quarantine recovery must be 100% bit-for-bit identical to original legacy row');

        // Verify recovered row produces identical canonical hash
        $recoveredHash = EspataCanonicalSerializer::hash($reconstructed);
        $this->assertSame($quarantineRow['canonical_hash'], $recoveredHash, 'Recovered row must produce identical canonical hash');
    }
}

