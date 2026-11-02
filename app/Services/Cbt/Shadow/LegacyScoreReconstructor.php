<?php

namespace App\Services\Cbt\Shadow;

/**
 * LegacyScoreReconstructor
 *
 * ESPATA Assessment Intelligence V3 - Stage F1
 * Reconstructs legacy CBT scores based on actual historical implementation in CbtConvertNilai
 * and cbt_sessions without performing any DML operations.
 *
 * ACTUAL LEGACY FORMULAS & BEHAVIORS:
 * 1. Raw Score Source: $session['total_score'] ?? $session['score'] ?? null
 * 2. Unparticipating / Absent Student Fallback:
 *    - In CbtConvertNilai preview: raw_score = null, converted_score = 0, ikut_ujian = false
 *    - In CbtConvertNilai save: $newScore === null || '' => saved as 0.0
 * 3. Linear Min-Max Normalization (if YA/YB configured):
 *    Converted = ((YA - YB) / (XA - XB)) * (NX - XB) + YB
 *    where XA = max(participant_scores), XB = min(participant_scores), NX = student raw score
 * 4. PTS Raw Import (previewPtsImport):
 *    Direct clamping of (total_score ?? score) to [0, 100] without linear conversion.
 */
class LegacyScoreReconstructor
{
    public const SOURCE_STORED_SESSION_TOTAL  = 'STORED_SESSION_TOTAL';
    public const SOURCE_STORED_SESSION_SCORE  = 'STORED_SESSION_SCORE';
    public const SOURCE_RECONSTRUCTED_ANSWERS = 'RECONSTRUCTED_ANSWERS';
    public const SOURCE_ABSENCE_ZERO_FALLBACK = 'ABSENCE_ZERO_FALLBACK';
    public const SOURCE_UNVERIFIABLE          = 'UNVERIFIABLE';

    public const STATUS_ABSENT            = 'ABSENT';
    public const STATUS_EARNED_ZERO       = 'EARNED_ZERO';
    public const STATUS_LEGACY_UNVERIFIED = 'LEGACY_UNVERIFIED';

    public const EVIDENCE_CONFIRMED_ABSENT  = 'CONFIRMED_ABSENT';
    public const EVIDENCE_CONFIRMED_PRESENT = 'CONFIRMED_PRESENT';
    public const EVIDENCE_INSUFFICIENT      = 'INSUFFICIENT_EVIDENCE';

    public const DECISION_ID = 'DEC-ABS-01';

    /**
     * Validate and normalize attendance evidence against student and session context.
     * Enforces DEC-ABS-01 strict provenance:
     * - Empty/missing evidence yields INSUFFICIENT_EVIDENCE (never assumed ABSENT)
     * - Student ID mismatch yields INSUFFICIENT_EVIDENCE
     * - Session ID mismatch yields INSUFFICIENT_EVIDENCE
     * - Incomplete evidence yields INSUFFICIENT_EVIDENCE
     *
     * @param mixed $evidence String or array containing attendance evidence
     * @param mixed $expectedStudentId Expected student ID
     * @param mixed $expectedSessionId Expected session ID (if known)
     * @return string One of EVIDENCE_CONFIRMED_ABSENT, EVIDENCE_CONFIRMED_PRESENT, or EVIDENCE_INSUFFICIENT
     */
    public function validateAttendanceEvidence($evidence, $expectedStudentId = null, $expectedSessionId = null): string
    {
        if (empty($evidence)) {
            return self::EVIDENCE_INSUFFICIENT;
        }

        if (is_array($evidence)) {
            $status = strtoupper(trim((string)($evidence['status'] ?? $evidence['type'] ?? '')));
            if ($status === '' || !in_array($status, [self::EVIDENCE_CONFIRMED_ABSENT, 'ABSENT', self::EVIDENCE_CONFIRMED_PRESENT, 'PRESENT'], true)) {
                return self::EVIDENCE_INSUFFICIENT;
            }

            // Cross-student contamination check: student_id must match if provided
            if (isset($evidence['student_id']) && $expectedStudentId !== null) {
                if ((string)$evidence['student_id'] !== (string)$expectedStudentId) {
                    return self::EVIDENCE_INSUFFICIENT;
                }
            }

            // Cross-session contamination check: session_id must match if provided
            if (isset($evidence['session_id']) && $expectedSessionId !== null) {
                if ((string)$evidence['session_id'] !== (string)$expectedSessionId) {
                    return self::EVIDENCE_INSUFFICIENT;
                }
            }

            if (in_array($status, [self::EVIDENCE_CONFIRMED_ABSENT, 'ABSENT'], true)) {
                return self::EVIDENCE_CONFIRMED_ABSENT;
            }
            if (in_array($status, [self::EVIDENCE_CONFIRMED_PRESENT, 'PRESENT'], true)) {
                return self::EVIDENCE_CONFIRMED_PRESENT;
            }

            return self::EVIDENCE_INSUFFICIENT;
        }

        if (is_string($evidence)) {
            $normalized = strtoupper(trim($evidence));
            if (in_array($normalized, [self::EVIDENCE_CONFIRMED_ABSENT, 'ABSENT'], true)) {
                return self::EVIDENCE_CONFIRMED_ABSENT;
            }
            if (in_array($normalized, [self::EVIDENCE_CONFIRMED_PRESENT, 'PRESENT'], true)) {
                return self::EVIDENCE_CONFIRMED_PRESENT;
            }
            return self::EVIDENCE_INSUFFICIENT;
        }

        return self::EVIDENCE_INSUFFICIENT;
    }

    /**
     * Reconstruct legacy score for a student session.
     *
     * @param array|null $session Historical session record from cbt_sessions
     * @param array $options Optional conversion parameters:
     *                       ['ya' => float, 'yb' => float, 'xa' => float, 'xb' => float, 'mode' => 'pts'|'convert', 'attendance_evidence' => 'CONFIRMED_ABSENT'|'CONFIRMED_PRESENT'|'INSUFFICIENT_EVIDENCE'|array]
     * @return array Reconstructed legacy outcome:
     *               [
     *                 'student_id'          => int|string,
     *                 'raw_score'           => float|null,
     *                 'converted_score'     => float|null,
     *                 'is_participating'    => bool,
     *                 'is_zero_fallback'    => bool,
     *                 'semantic_status'     => string,
     *                 'source_type'         => string,
     *                 'is_verifiable'       => bool,
     *                 'decision_id'         => string|null,
     *                 'audit_trail'         => array
     *               ]
     */
    public function reconstructFromSession(?array $session, array $options = []): array
    {
        $studentId = $session['student_id'] ?? ($options['student_id'] ?? 'unknown');
        $sessionId = $session['id'] ?? ($options['session_id'] ?? null);

        $evidenceInput = $options['attendance_evidence'] ?? null;
        $validatedEvidence = $this->validateAttendanceEvidence($evidenceInput, $studentId, $sessionId);

        // Case 1: Student has no session record (legacy wrote fallback 0.0)
        if ($session === null) {
            if ($validatedEvidence === self::EVIDENCE_CONFIRMED_ABSENT) {
                return [
                    'student_id'       => $studentId,
                    'session_id'       => null,
                    'raw_score'        => null,
                    'converted_score'  => 0.0,
                    'is_participating' => false,
                    'is_zero_fallback' => true,
                    'semantic_status'  => self::STATUS_ABSENT,
                    'source_type'      => self::SOURCE_ABSENCE_ZERO_FALLBACK,
                    'is_verifiable'    => true,
                    'decision_id'      => self::DECISION_ID,
                    'audit_trail'      => [
                        'reason' => 'Confirmed student absence with valid evidence. Legacy CbtConvertNilai treated absent students as 0.0.',
                    ],
                ];
            }

            if ($validatedEvidence === self::EVIDENCE_CONFIRMED_PRESENT) {
                return [
                    'student_id'       => $studentId,
                    'session_id'       => null,
                    'raw_score'        => 0.0,
                    'converted_score'  => 0.0,
                    'is_participating' => true,
                    'is_zero_fallback' => false,
                    'semantic_status'  => self::STATUS_EARNED_ZERO,
                    'source_type'      => self::SOURCE_STORED_SESSION_SCORE,
                    'is_verifiable'    => true,
                    'decision_id'      => self::DECISION_ID,
                    'audit_trail'      => [
                        'reason' => 'Confirmed student presence with valid evidence despite unrecorded session.',
                    ],
                ];
            }

            // Zero without valid absence evidence MUST NOT become ABSENT; defaults to LEGACY_UNVERIFIED
            return [
                'student_id'       => $studentId,
                'session_id'       => null,
                'raw_score'        => null,
                'converted_score'  => 0.0,
                'is_participating' => false,
                'is_zero_fallback' => true,
                'semantic_status'  => self::STATUS_LEGACY_UNVERIFIED,
                'source_type'      => self::SOURCE_UNVERIFIABLE,
                'is_verifiable'    => false,
                'decision_id'      => self::DECISION_ID,
                'audit_trail'      => [
                    'reason' => 'No session record found and attendance evidence is insufficient/unverified. Kept as LEGACY_UNVERIFIED under DEC-ABS-01.',
                ],
            ];
        }

        $sessionId = $session['id'] ?? null;
        $totalScore = isset($session['total_score']) && $session['total_score'] !== null ? (float)$session['total_score'] : null;
        $score = isset($session['score']) && $session['score'] !== null ? (float)$session['score'] : null;

        $hasScore = ($totalScore !== null || $score !== null);

        if (!$hasScore) {
            // Session exists but has no score recorded
            $hasAbsenceEvidence = ($validatedEvidence === self::EVIDENCE_CONFIRMED_ABSENT) || (empty($evidenceInput) && isset($session['status']) && strtolower((string)$session['status']) === 'absent');

            if ($hasAbsenceEvidence) {
                return [
                    'student_id'       => $studentId,
                    'session_id'       => $sessionId,
                    'raw_score'        => null,
                    'converted_score'  => 0.0,
                    'is_participating' => false,
                    'is_zero_fallback' => true,
                    'semantic_status'  => self::STATUS_ABSENT,
                    'source_type'      => self::SOURCE_ABSENCE_ZERO_FALLBACK,
                    'is_verifiable'    => true,
                    'decision_id'      => self::DECISION_ID,
                    'audit_trail'      => [
                        'reason' => 'Session has null score and student confirmed absent. Legacy CbtConvertNilai saved as 0.0.',
                    ],
                ];
            }

            return [
                'student_id'       => $studentId,
                'session_id'       => $sessionId,
                'raw_score'        => null,
                'converted_score'  => 0.0,
                'is_participating' => false,
                'is_zero_fallback' => true,
                'semantic_status'  => self::STATUS_LEGACY_UNVERIFIED,
                'source_type'      => self::SOURCE_UNVERIFIABLE,
                'is_verifiable'    => false,
                'decision_id'      => self::DECISION_ID,
                'audit_trail'      => [
                    'reason' => 'Session has null score and attendance evidence is insufficient/unverified. Kept as LEGACY_UNVERIFIED under DEC-ABS-01.',
                ],
            ];
        }

        $sourceType = ($totalScore !== null) ? self::SOURCE_STORED_SESSION_TOTAL : self::SOURCE_STORED_SESSION_SCORE;
        $rawScore = $totalScore ?? $score ?? 0.0;
        $rawScore = max(0.0, min(100.0, (float)$rawScore));

        // Evaluate zero score provenance under DEC-ABS-01
        $semanticStatus = 'EARNED_SCORE';
        $isZeroFallback = false;
        $isParticipating = true;
        $isVerifiable = true;

        if ($rawScore == 0.0) {
            $hasAnswers = (!empty($session['answers_count']) && (int)$session['answers_count'] > 0)
                || (!empty($session['answers']) && count($session['answers']) > 0)
                || (!empty($session['started_at']));

            if ($validatedEvidence === self::EVIDENCE_CONFIRMED_ABSENT || (empty($evidenceInput) && isset($session['status']) && strtolower((string)$session['status']) === 'absent')) {
                $semanticStatus = self::STATUS_ABSENT;
                $isZeroFallback = true;
                $isParticipating = false;
                $sourceType = self::SOURCE_ABSENCE_ZERO_FALLBACK;
            } elseif ($validatedEvidence === self::EVIDENCE_CONFIRMED_PRESENT || $hasAnswers) {
                $semanticStatus = self::STATUS_EARNED_ZERO;
                $isZeroFallback = false;
                $isParticipating = true;
            } else {
                // Score is 0.0, no answers, and evidence is insufficient/unverified
                // "Legacy zero tanpa evidence tidak boleh menjadi ABSENT."
                $semanticStatus = self::STATUS_LEGACY_UNVERIFIED;
                $isZeroFallback = true;
                $isParticipating = false;
                $isVerifiable = false;
                $sourceType = self::SOURCE_UNVERIFIABLE;
            }
        }



        // Check if linear normalization (YA/YB) was applied
        $mode = $options['mode'] ?? 'pts'; // 'pts' = direct raw, 'convert' = linear
        $convertedScore = $rawScore;

        if ($mode === 'convert' && isset($options['ya'], $options['yb'])) {
            $ya = (float)$options['ya'];
            $yb = (float)$options['yb'];
            $xa = (float)($options['xa'] ?? 100.0);
            $xb = (float)($options['xb'] ?? 0.0);

            if ($xa == $xb) {
                $convertedScore = $ya;
            } else {
                $denominator = ($xa - $xb) ?: 1.0;
                $convertedScore = (($ya - $yb) / $denominator) * ($rawScore - $xb) + $yb;
            }
            $convertedScore = round($convertedScore, 2);
        }

        return [
            'student_id'       => $studentId,
            'session_id'       => $sessionId,
            'raw_score'        => $rawScore,
            'converted_score'  => $convertedScore,
            'is_participating' => $isParticipating,
            'is_zero_fallback' => $isZeroFallback,
            'semantic_status'  => $semanticStatus,
            'source_type'      => $sourceType,
            'is_verifiable'    => $isVerifiable,
            'decision_id'      => $isZeroFallback ? self::DECISION_ID : null,
            'audit_trail'      => [
                'mode'                 => $mode,
                'stored_total_score'   => $totalScore,
                'stored_score'         => $score,
                'raw_score'            => $rawScore,
                'converted_score'      => $convertedScore,
                'semantic_status'      => $semanticStatus,
                'attendance_evidence'  => $evidenceInput,
                'validated_evidence'   => $validatedEvidence,
            ],
        ];
    }

    /**
     * Reconstruct legacy score from raw legacy answers table (cbt_answers).
     *
     * @param array $answers List of legacy answer rows: [['question_id' => ..., 'score' => float, 'answer' => ...], ...]
     * @param array $questions List of legacy questions
     * @param array $testConfig Optional bobot_pg, bobot_esai, etc.
     * @return array
     */
    public function reconstructFromAnswers(array $answers, array $questions, array $testConfig = []): array
    {
        if (empty($answers)) {
            return [
                'raw_score'        => null,
                'converted_score'  => 0.0,
                'is_participating' => false,
                'is_zero_fallback' => true,
                'source_type'      => self::SOURCE_ABSENCE_ZERO_FALLBACK,
                'is_verifiable'    => false,
                'audit_trail'      => ['reason' => 'Zero answers found for session'],
            ];
        }

        // Sum up answer scores
        $totalEarned = 0.0;
        $answeredCount = 0;

        foreach ($answers as $ans) {
            $ansScore = (float)($ans['score'] ?? 0.0);
            $totalEarned += $ansScore;
            if (isset($ans['answer']) && trim((string)$ans['answer']) !== '') {
                $answeredCount++;
            }
        }

        $totalEarned = round(max(0.0, min(100.0, $totalEarned)), 2);

        return [
            'raw_score'        => $totalEarned,
            'converted_score'  => $totalEarned,
            'is_participating' => true,
            'is_zero_fallback' => false,
            'source_type'      => self::SOURCE_RECONSTRUCTED_ANSWERS,
            'is_verifiable'    => true,
            'audit_trail'      => [
                'total_answers'   => count($answers),
                'answered_count'  => $answeredCount,
                'raw_score_sum'   => $totalEarned,
            ],
        ];
    }
}

