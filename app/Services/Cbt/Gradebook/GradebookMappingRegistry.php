<?php

namespace App\Services\Cbt\Gradebook;

use InvalidArgumentException;

/**
 * GradebookMappingRegistry
 *
 * ESPATA Assessment Intelligence V3 - Stage E
 * Manages explicit mappings and strict allowlists for the five ESPATA gradebook target tables:
 * 1. material_scores   (Formatif / Lingkup Materi ATP)
 * 2. pts_scores        (Penilaian Tengah Semester - standalone)
 * 3. summative_scores  (Sumatif / SAS)
 * 4. final_exam_scores (Ujian Akhir Semester / ASAS)
 * 5. subject_scores    (Nilai Akhir Rapor per Mapel)
 *
 * Prevents polymorphic table injection through hardcoded allowlist validation.
 */
class GradebookMappingRegistry
{
    public const TABLE_MATERIAL_SCORES   = 'material_scores';
    public const TABLE_PTS_SCORES        = 'pts_scores';
    public const TABLE_SUMMATIVE_SCORES  = 'summative_scores';
    public const TABLE_FINAL_EXAM_SCORES = 'final_exam_scores';
    public const TABLE_SUBJECT_SCORES    = 'subject_scores';

    public const ALLOWED_TABLES = [
        self::TABLE_MATERIAL_SCORES,
        self::TABLE_PTS_SCORES,
        self::TABLE_SUMMATIVE_SCORES,
        self::TABLE_FINAL_EXAM_SCORES,
        self::TABLE_SUBJECT_SCORES,
    ];

    /**
     * Map component identifier / test type to canonical target gradebook table.
     *
     * @param string $componentType
     * @return string
     * @throws InvalidArgumentException
     */
    public function resolveTargetTable(string $componentType): string
    {
        $clean = strtolower(trim($componentType));

        $map = [
            'formatif'        => self::TABLE_MATERIAL_SCORES,
            'material'        => self::TABLE_MATERIAL_SCORES,
            'lingkup_materi'  => self::TABLE_MATERIAL_SCORES,
            'atp'             => self::TABLE_MATERIAL_SCORES,
            'uh'              => self::TABLE_MATERIAL_SCORES,
            'tp'              => self::TABLE_MATERIAL_SCORES,

            'pts'             => self::TABLE_PTS_SCORES,
            'sts'             => self::TABLE_PTS_SCORES,
            'uts'             => self::TABLE_PTS_SCORES,
            'mid'             => self::TABLE_PTS_SCORES,

            'sumatif'         => self::TABLE_SUMMATIVE_SCORES,
            'summative'       => self::TABLE_SUMMATIVE_SCORES,
            'sas'             => self::TABLE_SUMMATIVE_SCORES,
            'penugasan'       => self::TABLE_SUMMATIVE_SCORES,

            'final'           => self::TABLE_FINAL_EXAM_SCORES,
            'pas'             => self::TABLE_FINAL_EXAM_SCORES,
            'pat'             => self::TABLE_FINAL_EXAM_SCORES,
            'asas'            => self::TABLE_FINAL_EXAM_SCORES,
            'exam'            => self::TABLE_FINAL_EXAM_SCORES,

            'subject'         => self::TABLE_SUBJECT_SCORES,
            'rapor'           => self::TABLE_SUBJECT_SCORES,
            'report'          => self::TABLE_SUBJECT_SCORES,
        ];

        if (isset($map[$clean])) {
            return $map[$clean];
        }

        // Direct table name check
        if (in_array($clean, self::ALLOWED_TABLES, true)) {
            return $clean;
        }

        throw new InvalidArgumentException(
            "Target gradebook table or component '{$componentType}' is not in the authorized allowlist."
        );
    }

    /**
     * Check if table name is in the strictly authorized allowlist.
     */
    public function isTableAllowed(string $table): bool
    {
        return in_array($table, self::ALLOWED_TABLES, true);
    }

    /**
     * Get unique match criteria columns for a target table.
     * Used to locate existing records and avoid duplicates.
     *
     * @param string $table
     * @return string[]
     * @throws InvalidArgumentException
     */
    public function getMatchCriteria(string $table): array
    {
        if (!$this->isTableAllowed($table)) {
            throw new InvalidArgumentException("Unauthorized target table: '{$table}'");
        }

        switch ($table) {
            case self::TABLE_MATERIAL_SCORES:
                return ['student_id', 'material_id', 'type'];

            case self::TABLE_PTS_SCORES:
                return ['student_id', 'subject_id', 'year_id', 'semester'];

            case self::TABLE_SUMMATIVE_SCORES:
                return ['student_id', 'subject_id', 'year_id', 'semester', 'type'];

            case self::TABLE_FINAL_EXAM_SCORES:
                // CRITICAL SCHEMA NOTE: final_exam_scores does NOT have a semester column in schema!
                return ['student_id', 'subject_id', 'year_id'];

            case self::TABLE_SUBJECT_SCORES:
                return ['student_id', 'subject_id', 'year_id', 'semester'];

            default:
                throw new InvalidArgumentException("No match criteria defined for table: '{$table}'");
        }
    }

    /**
     * Build match criteria where array from provided context.
     *
     * @param string $table
     * @param array $context
     * @return array
     * @throws InvalidArgumentException
     */
    public function buildMatchWhere(string $table, array $context): array
    {
        $criteria = $this->getMatchCriteria($table);
        $where = [];

        foreach ($criteria as $field) {
            if (!isset($context[$field]) || $context[$field] === '' || $context[$field] === null) {
                throw new InvalidArgumentException(
                    "Missing required match field '{$field}' for target table '{$table}'"
                );
            }
            $where[$field] = $context[$field];
        }

        return $where;
    }

    /**
     * Build full payload to insert/update into target table.
     *
     * @param string $table
     * @param array $context
     * @param float $score
     * @param int|null $operatorId
     * @return array
     */
    public function buildPayload(string $table, array $context, float $score, ?int $operatorId = null): array
    {
        $payload = $this->buildMatchWhere($table, $context);

        if ($table === self::TABLE_SUBJECT_SCORES) {
            $payload['report_score'] = $score;
        } else {
            $payload['score'] = $score;
        }

        if ($table === self::TABLE_MATERIAL_SCORES && $operatorId !== null) {
            $payload['created_by'] = $operatorId;
        }

        if ($table === self::TABLE_PTS_SCORES) {
            $payload['source'] = 'cbt_v3';
            if ($operatorId !== null) {
                $payload['created_by'] = $operatorId;
            }
        }

        return $payload;
    }
}

