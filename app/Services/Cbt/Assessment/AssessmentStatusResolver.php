<?php

namespace App\Services\Cbt\Assessment;

/**
 * AssessmentStatusResolver
 * Centralized, multi-domain status constants and resolver for Assessment Intelligence V3.
 *
 * Enforces strict separation of concerns across 7 distinct operational domains:
 * 1. Test Status
 * 2. Session Status
 * 3. Participation Status
 * 4. Integrity Status
 * 5. Objective Grading Status
 * 6. Essay Grading Status
 * 7. TP Evaluation Status
 */
class AssessmentStatusResolver
{
    // ========================================================================
    // DOMAIN IDENTIFIERS
    // ========================================================================
    public const DOMAIN_TEST          = 'test';
    public const DOMAIN_SESSION       = 'session';
    public const DOMAIN_PARTICIPATION = 'participation';
    public const DOMAIN_INTEGRITY     = 'integrity';
    public const DOMAIN_OBJECTIVE     = 'objective';
    public const DOMAIN_ESSAY         = 'essay';
    public const DOMAIN_TP            = 'tp';

    // ========================================================================
    // 1. TEST STATUS (Tingkat Jadwal / Tes Keseluruhan)
    // ========================================================================
    public const TEST_DRAFT              = 'DRAFT';
    public const TEST_PUBLISHED          = 'PUBLISHED';
    public const TEST_ACTIVE             = 'ACTIVE';
    public const TEST_PAUSED             = 'PAUSED';
    public const TEST_FINISHED           = 'FINISHED';
    public const TEST_CLOSED             = 'CLOSED';
    public const TEST_ARCHIVED           = 'ARCHIVED';
    public const TEST_NO_VALID_QUESTIONS = 'NO_VALID_QUESTIONS'; // Terminal test-level status

    // ========================================================================
    // 2. SESSION STATUS (Tingkat Pengerjaan Sesi Siswa)
    // ========================================================================
    public const SESSION_NOT_STARTED = 'NOT_STARTED';
    public const SESSION_ACTIVE      = 'ACTIVE';
    public const SESSION_SUBMITTED   = 'SUBMITTED';
    public const SESSION_EXPIRED     = 'EXPIRED';
    public const SESSION_FINISHED    = 'FINISHED';
    public const SESSION_CANCELLED   = 'CANCELLED';
    public const SESSION_VOID        = 'VOID';
    public const SESSION_RESET       = 'RESET';

    // ========================================================================
    // 3. PARTICIPATION STATUS (Keikutsertaan Siswa)
    // ========================================================================
    public const PARTICIPATION_PRESENT       = 'PRESENT';
    public const PARTICIPATION_ATTEMPTED     = 'ATTEMPTED';
    public const PARTICIPATION_ABSENT        = 'ABSENT';
    public const PARTICIPATION_NOT_ATTEMPTED = 'NOT_ATTEMPTED';
    public const PARTICIPATION_EXCLUDED      = 'EXCLUDED';

    // ========================================================================
    // 4. INTEGRITY STATUS (Integritas dan Anti-Cheat Sesi)
    // ========================================================================
    public const INTEGRITY_UNCHECKED      = 'UNCHECKED';
    public const INTEGRITY_VALID          = 'VALID';
    public const INTEGRITY_VERIFIED_CLEAN = 'VERIFIED_CLEAN';
    public const INTEGRITY_FLAGGED        = 'FLAGGED';
    public const INTEGRITY_SUSPICIOUS     = 'SUSPICIOUS';
    public const INTEGRITY_LOCKED_CHEAT   = 'LOCKED_CHEAT';
    public const INTEGRITY_INVALIDATED    = 'INVALIDATED';

    // ========================================================================
    // 5. OBJECTIVE GRADING STATUS (Penilaian Soal Objektif)
    // ========================================================================
    public const OBJ_NOT_APPLICABLE = 'NOT_APPLICABLE';
    public const OBJ_PENDING        = 'PENDING';
    public const OBJ_GRADED         = 'GRADED';
    public const OBJ_COMPLETED      = 'COMPLETED';
    public const OBJ_VOID_EXCLUDED  = 'VOID_EXCLUDED';

    // ========================================================================
    // 6. ESSAY GRADING STATUS (Penilaian Soal Subjektif / Esai)
    // ========================================================================
    public const ESSAY_NOT_APPLICABLE = 'NOT_APPLICABLE';
    public const ESSAY_UNGRADED       = 'UNGRADED';
    public const ESSAY_IN_PROGRESS    = 'IN_PROGRESS';
    public const ESSAY_GRADED         = 'GRADED';
    public const ESSAY_NEEDS_REVIEW   = 'NEEDS_REVIEW';

    // ========================================================================
    // 7. TP EVALUATION STATUS (Kecukupan Bukti & Status Penguasaan TP)
    // ========================================================================
    public const TP_NOT_APPLICABLE        = 'NOT_APPLICABLE';
    public const TP_INSUFFICIENT_EVIDENCE = 'INSUFFICIENT_EVIDENCE';
    public const TP_MASTERED              = 'MASTERED';
    public const TP_EVALUATED_MASTERED    = 'MASTERED';
    public const TP_NOT_MASTERED          = 'NOT_MASTERED';
    public const TP_EVALUATED_NOT_MASTERED = 'NOT_MASTERED';
    public const TP_NOT_ATTEMPTED         = 'NOT_ATTEMPTED';
    public const TP_NOT_EVALUATED         = 'NOT_EVALUATED';

    /**
     * Determine if a test status is terminal and produces zero valid questions.
     */
    public static function isTerminalVoidTest(string $testStatus): bool
    {
        return $testStatus === self::TEST_NO_VALID_QUESTIONS;
    }

    /**
     * Determine if a session is eligible for definitive grading.
     */
    public static function isSessionGradable(string $sessionStatus, string $integrityStatus): bool
    {
        $validSession = in_array($sessionStatus, [self::SESSION_SUBMITTED, self::SESSION_FINISHED, self::SESSION_EXPIRED], true);
        $validIntegrity = !in_array($integrityStatus, [self::INTEGRITY_FLAGGED, self::INTEGRITY_LOCKED_CHEAT, self::INTEGRITY_INVALIDATED], true);

        return $validSession && $validIntegrity;
    }

    /**
     * Determine if an evaluation result is eligible to be synchronized to the gradebook.
     */
    public static function isEligibleForGradebook(array $statuses): bool
    {
        $testStatus = $statuses[self::DOMAIN_TEST] ?? null;
        if ($testStatus === self::TEST_NO_VALID_QUESTIONS) {
            return false;
        }

        $sessionStatus = $statuses[self::DOMAIN_SESSION] ?? null;
        if (in_array($sessionStatus, [self::SESSION_RESET, self::SESSION_VOID, self::SESSION_CANCELLED], true)) {
            return false;
        }

        $integrityStatus = $statuses[self::DOMAIN_INTEGRITY] ?? null;
        if (in_array($integrityStatus, [self::INTEGRITY_FLAGGED, self::INTEGRITY_SUSPICIOUS, self::INTEGRITY_LOCKED_CHEAT, self::INTEGRITY_INVALIDATED], true)) {
            return false;
        }

        $essayStatus = $statuses[self::DOMAIN_ESSAY] ?? null;
        if ($essayStatus === self::ESSAY_UNGRADED || $essayStatus === self::ESSAY_IN_PROGRESS) {
            return false;
        }

        return true;
    }
}
