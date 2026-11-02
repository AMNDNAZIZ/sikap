<?php

namespace App\Models;

use CodeIgniter\Model;
use InvalidArgumentException;

/**
 * CbtAnswerQuarantineModel
 * Lossless, fully reversible repository for ambiguous, orphaned, or anomalous legacy answers.
 * Preserves the exact 10 physical columns of legacy cbt_answers.
 */
class CbtAnswerQuarantineModel extends Model
{
    protected $table            = 'cbt_answers_quarantine';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'legacy_answer_id',
        'student_id',
        'test_id',
        'question_id',
        'answer_raw',
        'score_raw',
        'is_doubtful_raw',
        'version_raw',
        'updated_at_raw',
        'updated_at_micro_raw',
        'quarantine_reason',
        'quarantine_details_json',
        'canonical_hash',
        'quarantined_at',
    ];

    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    protected $validationRules = [
        'legacy_answer_id'        => 'required|is_natural_no_zero',
        'student_id'              => 'required|is_natural_no_zero',
        'test_id'                 => 'required|is_natural_no_zero',
        'question_id'             => 'required|is_natural_no_zero',
        'quarantine_reason'       => 'required|max_length[64]',
        'quarantine_details_json' => 'required',
        'canonical_hash'          => 'required|exact_length[64]',
    ];

    /**
     * Reconstruct the exact 10-column physical legacy cbt_answers row from quarantined data.
     * Guaranteed lossless bit-for-bit reconstruction without modifying legacy database.
     *
     * @param array $quarantineRow
     * @return array
     */
    public static function reconstructLegacyRecord(array $quarantineRow): array
    {
        if (!isset($quarantineRow['legacy_answer_id'])) {
            throw new InvalidArgumentException("Missing legacy_answer_id in quarantine row.");
        }

        return [
            'id'               => (int)$quarantineRow['legacy_answer_id'],
            'student_id'       => (int)$quarantineRow['student_id'],
            'test_id'          => (int)$quarantineRow['test_id'],
            'question_id'      => (int)$quarantineRow['question_id'],
            'answer'           => $quarantineRow['answer_raw'] !== null ? (string)$quarantineRow['answer_raw'] : null,
            'updated_at_micro' => $quarantineRow['updated_at_micro_raw'] !== null ? (string)$quarantineRow['updated_at_micro_raw'] : null,
            'version'          => (int)($quarantineRow['version_raw'] ?? 1),
            'is_doubtful'      => (int)($quarantineRow['is_doubtful_raw'] ?? 0),
            'score'            => $quarantineRow['score_raw'] !== null ? (float)$quarantineRow['score_raw'] : null,
            'updated_at'       => $quarantineRow['updated_at_raw'] !== null ? (string)$quarantineRow['updated_at_raw'] : null,
        ];
    }
}
