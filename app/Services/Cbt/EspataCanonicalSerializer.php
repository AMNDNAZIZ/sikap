<?php

namespace App\Services\Cbt;

use InvalidArgumentException;
use RangeException;

/**
 * EspataCanonicalSerializer
 * Lossless, bit-preserving canonical binary serializer for legacy `cbt_answers`.
 *
 * Implements strict type tagging, explicit NULL handling, IEEE 754 float binary preservation,
 * raw byte preservation without Unicode normalization, and domain separation.
 */
class EspataCanonicalSerializer
{
    public const DOMAIN_PREFIX = "ESPATA-C34-V3\0";

    // Column Tags (0x01 - 0x0A)
    public const TAG_ID               = 0x01;
    public const TAG_STUDENT_ID       = 0x02;
    public const TAG_TEST_ID          = 0x03;
    public const TAG_QUESTION_ID      = 0x04;
    public const TAG_ANSWER           = 0x05;
    public const TAG_UPDATED_AT_MICRO = 0x06;
    public const TAG_VERSION          = 0x07;
    public const TAG_IS_DOUBTFUL      = 0x08;
    public const TAG_SCORE            = 0x09;
    public const TAG_UPDATED_AT       = 0x0A;

    // Type Indicators
    public const TYPE_UINT32       = 0x01;
    public const TYPE_INT32        = 0x02;
    public const TYPE_UINT8        = 0x03;
    public const TYPE_FLOAT32      = 0x04;
    public const TYPE_BYTES        = 0x05;
    public const TYPE_DECIMAL_STR  = 0x06;
    public const TYPE_DATETIME_STR = 0x07;

    // Null Indicators
    public const INDICATOR_NOT_NULL = 0x00;
    public const INDICATOR_NULL     = 0x01;

    /**
     * Compute deterministic 64-character hex SHA-256 of the legacy cbt_answers record.
     *
     * @param array $row
     * @return string
     */
    public static function hash(array $row): string
    {
        return hash('sha256', self::serialize($row), false);
    }

    /**
     * Compute raw 32-byte binary SHA-256 of the legacy cbt_answers record.
     *
     * @param array $row
     * @return string
     */
    public static function hashBinary(array $row): string
    {
        return hash('sha256', self::serialize($row), true);
    }

    /**
     * Serialize the exact 10 physical columns of legacy cbt_answers into a canonical binary stream.
     *
     * @param array $row
     * @return string Binary stream
     * @throws InvalidArgumentException
     */
    public static function serialize(array $row): string
    {
        self::validateRequiredFields($row);

        $stream = self::DOMAIN_PREFIX;

        // 1. ID (int(10) unsigned -> UInt32 BE)
        $stream .= self::encodeUInt32(self::TAG_ID, (int)$row['id']);

        // 2. Student ID (int(10) unsigned -> UInt32 BE)
        $stream .= self::encodeUInt32(self::TAG_STUDENT_ID, (int)$row['student_id']);

        // 3. Test ID (int(10) unsigned -> UInt32 BE)
        $stream .= self::encodeUInt32(self::TAG_TEST_ID, (int)$row['test_id']);

        // 4. Question ID (int(10) unsigned -> UInt32 BE)
        $stream .= self::encodeUInt32(self::TAG_QUESTION_ID, (int)$row['question_id']);

        // 5. Answer (varchar(500) / text -> Raw bytes, preserving exact bits)
        $stream .= self::encodeRawBytes(self::TAG_ANSWER, $row['answer'] ?? null);

        // 6. Updated At Micro (decimal(16,6) -> Canonical ASCII decimal string)
        $stream .= self::encodeDecimalString(self::TAG_UPDATED_AT_MICRO, $row['updated_at_micro'] ?? null);

        // 7. Version (int(11) -> Int32 BE)
        $stream .= self::encodeInt32(self::TAG_VERSION, (int)($row['version'] ?? 1));

        // 8. Is Doubtful (tinyint(1) -> UInt8)
        $stream .= self::encodeUInt8(self::TAG_IS_DOUBTFUL, (int)($row['is_doubtful'] ?? 0));

        // 9. Score (float -> IEEE 754 32-bit single-precision Big-Endian binary)
        $stream .= self::encodeFloat32(self::TAG_SCORE, array_key_exists('score', $row) ? $row['score'] : null);

        // 10. Updated At (datetime -> Canonical ASCII string)
        $stream .= self::encodeDatetimeString(self::TAG_UPDATED_AT, $row['updated_at'] ?? null);

        return $stream;
    }

    /**
     * Lossless binary deserializer to verify round-trip reconstruction.
     *
     * @param string $stream
     * @return array
     */
    public static function deserialize(string $stream): array
    {
        $prefixLen = strlen(self::DOMAIN_PREFIX);
        if (substr($stream, 0, $prefixLen) !== self::DOMAIN_PREFIX) {
            throw new InvalidArgumentException("Invalid domain prefix in serialized stream.");
        }

        $offset = $prefixLen;
        $streamLen = strlen($stream);
        $record = [];

        while ($offset < $streamLen) {
            if ($offset + 7 > $streamLen) {
                throw new RangeException("Truncated TLV header at offset {$offset}.");
            }

            $tag       = ord($stream[$offset]);
            $type      = ord($stream[$offset + 1]);
            $isNull    = ord($stream[$offset + 2]) === self::INDICATOR_NULL;
            $lengthArr = unpack('Nlen', substr($stream, $offset + 3, 4));
            $length    = $lengthArr['len'];
            $offset   += 7;

            if ($offset + $length > $streamLen) {
                throw new RangeException("Truncated value payload for tag {$tag}.");
            }

            $rawVal = substr($stream, $offset, $length);
            $offset += $length;

            switch ($tag) {
                case self::TAG_ID:
                    $record['id'] = unpack('Nval', $rawVal)['val'];
                    break;
                case self::TAG_STUDENT_ID:
                    $record['student_id'] = unpack('Nval', $rawVal)['val'];
                    break;
                case self::TAG_TEST_ID:
                    $record['test_id'] = unpack('Nval', $rawVal)['val'];
                    break;
                case self::TAG_QUESTION_ID:
                    $record['question_id'] = unpack('Nval', $rawVal)['val'];
                    break;
                case self::TAG_ANSWER:
                    $record['answer'] = $isNull ? null : $rawVal;
                    break;
                case self::TAG_UPDATED_AT_MICRO:
                    $record['updated_at_micro'] = $isNull ? null : $rawVal;
                    break;
                case self::TAG_VERSION:
                    $record['version'] = unpack('lval', $rawVal)['val'];
                    break;
                case self::TAG_IS_DOUBTFUL:
                    $record['is_doubtful'] = ord($rawVal);
                    break;
                case self::TAG_SCORE:
                    if ($isNull) {
                        $record['score'] = null;
                    } else {
                        $floatArr = unpack('Gval', $rawVal);
                        $record['score'] = (float)$floatArr['val'];
                    }
                    break;
                case self::TAG_UPDATED_AT:
                    $record['updated_at'] = $isNull ? null : $rawVal;
                    break;
                default:
                    throw new InvalidArgumentException("Unknown tag {$tag} encountered.");
            }
        }

        return $record;
    }

    // ------------------------------------------------------------------------
    // Internal Field Encoders
    // ------------------------------------------------------------------------

    private static function encodeUInt32(int $tag, int $val): string
    {
        return pack('C', $tag)
            . pack('C', self::TYPE_UINT32)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', 4)
            . pack('N', $val);
    }

    private static function encodeInt32(int $tag, int $val): string
    {
        return pack('C', $tag)
            . pack('C', self::TYPE_INT32)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', 4)
            . pack('l', $val);
    }

    private static function encodeUInt8(int $tag, int $val): string
    {
        return pack('C', $tag)
            . pack('C', self::TYPE_UINT8)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', 1)
            . pack('C', $val & 0xFF);
    }

    private static function encodeRawBytes(int $tag, ?string $val): string
    {
        if ($val === null) {
            return pack('C', $tag)
                . pack('C', self::TYPE_BYTES)
                . pack('C', self::INDICATOR_NULL)
                . pack('N', 0);
        }

        $len = strlen($val);
        return pack('C', $tag)
            . pack('C', self::TYPE_BYTES)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', $len)
            . $val;
    }

    private static function encodeDecimalString(int $tag, $val): string
    {
        if ($val === null) {
            return pack('C', $tag)
                . pack('C', self::TYPE_DECIMAL_STR)
                . pack('C', self::INDICATOR_NULL)
                . pack('N', 0);
        }

        $str = (string)$val;
        return pack('C', $tag)
            . pack('C', self::TYPE_DECIMAL_STR)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', strlen($str))
            . $str;
    }

    private static function encodeDatetimeString(int $tag, ?string $val): string
    {
        if ($val === null) {
            return pack('C', $tag)
                . pack('C', self::TYPE_DATETIME_STR)
                . pack('C', self::INDICATOR_NULL)
                . pack('N', 0);
        }

        return pack('C', $tag)
            . pack('C', self::TYPE_DATETIME_STR)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', strlen($val))
            . $val;
    }

    private static function encodeFloat32(int $tag, $val): string
    {
        if ($val === null) {
            return pack('C', $tag)
                . pack('C', self::TYPE_FLOAT32)
                . pack('C', self::INDICATOR_NULL)
                . pack('N', 0);
        }

        // Pack IEEE 754 32-bit single-precision float (Big-Endian)
        $binary = pack('G', (float)$val);

        return pack('C', $tag)
            . pack('C', self::TYPE_FLOAT32)
            . pack('C', self::INDICATOR_NOT_NULL)
            . pack('N', 4)
            . $binary;
    }

    private static function validateRequiredFields(array $row): void
    {
        $required = ['id', 'student_id', 'test_id', 'question_id'];
        foreach ($required as $field) {
            if (!isset($row[$field])) {
                throw new InvalidArgumentException("Missing mandatory legacy field: '{$field}'.");
            }
        }
    }
}

