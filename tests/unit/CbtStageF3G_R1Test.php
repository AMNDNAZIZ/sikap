<?php

namespace Tests\Unit;

spl_autoload_register(function ($class) {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__, 2) . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
    if (str_starts_with($class, 'Config\\')) {
        $file = dirname(__DIR__, 2) . '/app/Config/' . str_replace('\\', '/', substr($class, 7)) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

use PHPUnit\Framework\TestCase;
use App\Services\Cbt\Review\ReviewerWorkflowService;
use App\Services\Cbt\Review\ReviewerDecisionValidator;
use App\Services\Cbt\Review\ReviewerAuditService;
use App\Services\Cbt\Review\ReviewerEvidenceValidator;
use App\Services\Cbt\Review\ReviewerSourceVerifier;
use App\Services\Cbt\Review\HistoricalSourceAdapter;
use PDO;
use InvalidArgumentException;

/**
 * CbtStageF3G_R1Test
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G-R1 Test Suite
 * Session Ownership & Evidence Semantics Hardening
 *
 * Covers 15 Mandatory Audit & Semantic Scenarios:
 * 1. Single session with single answer set yields SESSION_CONTEXT_MATCH (fail-closed, not promoted to VERIFIED).
 * 2. Two sessions with same student/test context yields SESSION_OWNERSHIP_AMBIGUOUS.
 * 3. Answers that cannot be bound to a session yields SESSION_OWNERSHIP_UNPROVEN.
 * 4. Retake scenario with overwritten answers (Scenario B).
 * 5. Retake scenario with history separated/isolated (Scenario A/E).
 * 6. Retake scenario with ambiguous/insufficient timestamps (Scenario D).
 * 7. NULL essay score yields PENDING with HOLD enforced.
 * 8. Default 0.00 in session table without teacher scoring yields DEFAULT_ZERO_UNVERIFIED.
 * 9. Explicit 0.00 awarded by teacher yields SCORED_ALL_ZERO (valid score, HOLD not enforced).
 * 10. Partially scored essay yields PARTIALLY_SCORED (HOLD enforced).
 * 11. Test without essay questions yields NO_ESSAY_QUESTIONS (HOLD not enforced).
 * 12. Legacy essay data with unknown provenance yields LEGACY_UNVERIFIED (HOLD enforced).
 * 13. Query with subquery, CTE, or table outside allowlist blocked.
 * 14. Free-form SQL from external input rejected.
 * 15. Connection failure of read-only account yields SOURCE_UNAVAILABLE without root fallback.
 */
class CbtStageF3G_R1Test extends TestCase
{
    private function createSemanticTestDb(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $pdo->exec("
            CREATE TABLE cbt_sessions (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                test_id INTEGER,
                score TEXT,
                essay_score TEXT,
                total_score TEXT,
                status TEXT,
                started_at INTEGER,
                finished_at INTEGER,
                created_at TEXT
            );

            CREATE TABLE cbt_answers (
                id INTEGER PRIMARY KEY,
                student_id INTEGER,
                test_id INTEGER,
                question_id INTEGER,
                answer TEXT,
                score TEXT,
                updated_at_micro TEXT,
                version INTEGER,
                is_doubtful INTEGER,
                updated_at TEXT
            );

            CREATE TABLE cbt_questions (
                id INTEGER PRIMARY KEY,
                bank_id INTEGER,
                question_type TEXT,
                question_text TEXT
            );

            CREATE TABLE cbt_test_status (
                id INTEGER PRIMARY KEY,
                bank_id INTEGER,
                bobot_pg INTEGER,
                bobot_esai INTEGER
            );
        ");

        return $pdo;
    }

    /**
     * Scenario 1: Satu sesi ujian dengan satu set jawaban (SESSION_CONTEXT_MATCH, fail-closed).
     * Mandatory Governance Rule: SESSION_CONTEXT_MATCH must NEVER be automatically promoted to SESSION_OWNERSHIP_VERIFIED.
     */
    public function testSingleSessionYieldsSessionContextMatchNotVerified(): void
    {
        $db = $this->createSemanticTestDb();
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (101, 1, 501, '78.50', 'finished', 1700000000, 1700003600)");
        $db->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score) 
                   VALUES (201, 1, 501, 1, 'A', '1.00')");

        $adapter = new HistoricalSourceAdapter($db);
        $res = $adapter->evaluateSessionOwnership(101, 1, 501, $db);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_CONTEXT_MATCH, $res['ownership_status']);
        $this->assertFalse($res['is_verified'], 'SESSION_CONTEXT_MATCH must fail-closed and not be promoted to VERIFIED.');
        $this->assertEquals(1, $res['session_count']);
        $this->assertNotEmpty($res['discrepancy']);
    }

    /**
     * Scenario 2: Dua sesi ujian dengan konteks siswa dan tes yang sama (SESSION_OWNERSHIP_AMBIGUOUS).
     */
    public function testTwoSessionsWithSameContextYieldsSessionOwnershipAmbiguous(): void
    {
        $db = $this->createSemanticTestDb();
        // Session 1: attempt 1
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1007, 4, 503, '60.00', 'finished', 1700000000, 1700003600)");
        // Session 2: retake attempt
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1008, 4, 503, '85.00', 'finished', 1700010000, 1700013600)");

        $adapter = new HistoricalSourceAdapter($db);
        $res = $adapter->evaluateSessionOwnership(1007, 4, 503, $db);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_AMBIGUOUS, $res['ownership_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertEquals(2, $res['session_count']);
        $this->assertStringContainsString('AMBIGUOUS', $res['discrepancy']);
    }

    /**
     * Scenario 3: Set jawaban tanpa bukti sesi yang sah (SESSION_OWNERSHIP_UNPROVEN).
     */
    public function testAnswersWithoutValidSessionYieldsSessionOwnershipUnproven(): void
    {
        $db = $this->createSemanticTestDb();
        // Answers exist but session does not exist in cbt_sessions
        $db->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score) 
                   VALUES (301, 99, 599, 1, 'B', '1.00')");

        $adapter = new HistoricalSourceAdapter($db);
        $res = $adapter->evaluateSessionOwnership(null, 99, 599, $db);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_UNPROVEN, $res['ownership_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertEquals(0, $res['session_count']);
    }

    /**
     * Scenario 4: Skenario retake dengan jawaban ter-overwrite (Scenario B).
     * In the legacy CBT schema, cbt_answers has unique (student_id, test_id, question_id).
     * When student takes a retake, answers from session 1 are overwritten.
     */
    public function testRetakeWithOverwrittenAnswersScenarioB(): void
    {
        $db = $this->createSemanticTestDb();
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1007, 4, 503, '60.00', 'finished', 1700000000, 1700003600)");
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1008, 4, 503, '85.00', 'finished', 1700010000, 1700013600)");

        // Answers in cbt_answers only contain the latest state
        $db->exec("INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score, updated_at) 
                   VALUES (401, 4, 503, 1, 'C', '1.00', '2026-11-01 10:30:00')");

        $adapter = new HistoricalSourceAdapter($db);

        // Attempting to resolve session 1007 must fail to claim definitive ownership
        $res = $adapter->evaluateSessionOwnership(1007, 4, 503, $db);
        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_AMBIGUOUS, $res['ownership_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Scenario 5: Skenario retake dengan riwayat jawaban terpisah (Scenario A/E).
     * Even if both sessions are registered, operational schema lacks session_id in answers.
     */
    public function testRetakeWithAvailableHistoryScenarioAOrE(): void
    {
        $db = $this->createSemanticTestDb();
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1007, 4, 503, '60.00', 'finished', 1700000000, 1700003600)");
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1008, 4, 503, '85.00', 'finished', 1700010000, 1700013600)");

        $adapter = new HistoricalSourceAdapter($db);
        $evidence = [
            'evidence_id'      => 'EVD_503_RETAKE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:1007,1008',
            'metadata'         => ['student_id' => 4, 'test_id' => 503],
        ];

        $res = $adapter->resolveRetakeEvidence($evidence, $db, ['student_id' => 4, 'test_id' => 503]);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_AMBIGUOUS, $res['session_ownership_status']);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_VERIFIED_RECORD_MATCH, $res['record_mapping_status']);

        // Directly evaluating session ownership on retake must fail-closed with is_verified = false
        $ownershipRes = $adapter->evaluateSessionOwnership(null, 4, 503, $db);
        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_AMBIGUOUS, $ownershipRes['ownership_status']);
        $this->assertFalse($ownershipRes['is_verified']);
    }

    /**
     * Scenario 6: Skenario retake dengan timestamp tidak cukup membuktikan kepemilikan (Scenario D).
     */
    public function testRetakeWithAmbiguousTimestampsScenarioD(): void
    {
        $db = $this->createSemanticTestDb();
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1007, 4, 503, '60.00', 'finished', 1700000000, 1700003600)");
        $db->exec("INSERT INTO cbt_sessions (id, student_id, test_id, score, status, started_at, finished_at) 
                   VALUES (1008, 4, 503, '85.00', 'finished', 1700003600, 1700007200)");

        $adapter = new HistoricalSourceAdapter($db);
        $res = $adapter->evaluateSessionOwnership(1008, 4, 503, $db);

        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_AMBIGUOUS, $res['ownership_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Scenario 7: Nilai esai NULL (PENDING, HOLD enforced).
     * Enforces DEC-ESS-01: Final score & report score withheld (NULL).
     */
    public function testNullEssayScoreYieldsPendingHoldEnforced(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [
            ['id' => 1, 'score' => null],
            ['id' => 2, 'score' => null],
        ];

        $res = $adapter->evaluateEssayGradingState(null, $answers, 2);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_PENDING, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);
        $this->assertTrue($res['final_score_withheld']);
        $this->assertEquals(2, $res['pending_count']);
    }

    /**
     * Scenario 8: Nilai esai 0.00 default database tanpa koreksi guru (DEFAULT_ZERO_UNVERIFIED).
     */
    public function testDefaultZeroEssayScoreYieldsDefaultZeroUnverified(): void
    {
        $adapter = new HistoricalSourceAdapter();
        // Session table has essay_score = 0.00, but answers have no scores evaluated
        $answers = [];

        $res = $adapter->evaluateEssayGradingState(0.0, $answers, 2);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_DEFAULT_ZERO, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);
        $this->assertTrue($res['final_score_withheld']);
        $this->assertStringContainsString('default', $res['discrepancy']);
    }

    /**
     * Scenario 9: Nilai esai 0.00 hasil koreksi guru (SCORED_ALL_ZERO).
     * 0.00 awarded by teacher is a legitimate grade; HOLD must NOT be enforced.
     */
    public function testTeacherAwardedZeroScoreYieldsScoredAllZero(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [
            ['id' => 1, 'score' => '0.00'],
            ['id' => 2, 'score' => '0.00'],
        ];

        $res = $adapter->evaluateEssayGradingState(0.0, $answers, 2, false, [
            'grader_id'            => 'TCH_01',
            'is_authorized'        => true,
            'graded_at'            => '2026-11-01 10:00:00',
            'change_log_reference' => 'LOG_ESSAY_ZERO',
        ]);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORED_ALL_ZERO, $res['state']);
        $this->assertFalse($res['is_hold_enforced']);
        $this->assertFalse($res['final_score_withheld']);
        $this->assertNull($res['discrepancy']);
    }

    /**
     * Scenario 10: Sebagian esai dinilai dan sebagian pending (PARTIALLY_SCORED).
     */
    public function testPartiallyScoredEssayYieldsPartiallyScored(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [
            ['id' => 1, 'score' => '10.00'],
            ['id' => 2, 'score' => null],
        ];

        $res = $adapter->evaluateEssayGradingState(null, $answers, 2);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_PARTIALLY_SCORED, $res['state']);
        $this->assertTrue($res['is_hold_enforced']);
        $this->assertTrue($res['final_score_withheld']);
        $this->assertEquals(1, $res['pending_count']);
        $this->assertEquals(1, $res['scored_count']);
    }

    /**
     * Scenario 11: Tes tanpa soal esai (NO_ESSAY_QUESTIONS).
     */
    public function testTestWithoutEssayQuestionsYieldsNoEssayQuestions(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $answers = [];

        $res = $adapter->evaluateEssayGradingState(null, $answers, 0);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_NO_QUESTIONS, $res['state']);
        $this->assertFalse($res['is_hold_enforced']);
        $this->assertFalse($res['final_score_withheld']);
    }

    /**
     * Scenario 12: Data historis esai dengan status tidak dapat diverifikasi (LEGACY_UNVERIFIED).
     */
    public function testLegacyDataYieldsLegacyUnverified(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // Case A: Explicit isLegacy flag
        $resA = $adapter->evaluateEssayGradingState(85.0, [], 2, true);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_LEGACY_UNVERIFIED, $resA['state']);
        $this->assertTrue($resA['is_hold_enforced']);
        $this->assertTrue($resA['final_score_withheld']);

        // Case B: Session has positive essay score but operational database has 0 answer rows
        $resB = $adapter->evaluateEssayGradingState(75.5, [], 2, false);
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_LEGACY_UNVERIFIED, $resB['state']);
        $this->assertTrue($resB['is_hold_enforced']);
        $this->assertTrue($resB['final_score_withheld']);
    }

    /**
     * Scenario 13: Percobaan query dengan subquery atau CTE di luar tabel yang diizinkan (blocked).
     */
    public function testQueryWithSubqueryOrCteOutsideAllowlistBlocked(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 1. Table injection via subquery outside allowlist
        $this->expectException(InvalidArgumentException::class);
        $adapter->assertReadOnlyQuery("SELECT * FROM (SELECT * FROM users) u");
    }

    /**
     * Scenario 14: Upaya passing SQL bebas dari input luar (rejected).
     */
    public function testFreeFormSqlFromExternalInputRejected(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // Mutating statement
        $rejected = false;
        try {
            $adapter->assertReadOnlyQuery("UPDATE cbt_sessions SET score = '100.00' WHERE id = 1");
        } catch (InvalidArgumentException $e) {
            $rejected = true;
        }
        $this->assertTrue($rejected, 'Mutating statements must be rejected.');

        // External non-SELECT execution
        $rejectedExec = false;
        try {
            $adapter->assertReadOnlyQuery("EXEC sp_msforeachtable");
        } catch (InvalidArgumentException $e) {
            $rejectedExec = true;
        }
        $this->assertTrue($rejectedExec, 'EXEC/CALL statements must be rejected.');
    }

    /**
     * Scenario 15: Kegagalan koneksi akun read-only tanpa fallback ke root (SOURCE_UNAVAILABLE).
     */
    public function testReadOnlyAccountConnectionFailureYieldsSourceUnavailableWithoutRootFallback(): void
    {
        // Null PDO simulates connection failure to isolated read-only account
        $adapter = new HistoricalSourceAdapter(null);

        $evidence = [
            'evidence_id'      => 'EVD_TEST_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_soal_siswa:1001',
            'metadata'         => ['student_id' => 1, 'test_id' => 501],
        ];

        $res = $adapter->resolveScoreEvidence($evidence, null, ['student_id' => 1, 'test_id' => 501]);

        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_SOURCE_UNAVAILABLE, $res['record_mapping_status']);
        $this->assertEquals(HistoricalSourceAdapter::SESSION_OWNERSHIP_UNPROVEN, $res['session_ownership_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertNull($res['operational_data']);
        $this->assertStringContainsString('No operational database connection', $res['discrepancy']);
    }
}
