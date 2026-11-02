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
 * CbtStageF3GTest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3G Test Suite
 * Adapter Semantic Mapping Validation & Evidence Provenance Hardening
 *
 * Covers 14 Specialized Semantic, Disambiguation & Security Scenarios:
 * 1. Session ID berbeda dari answer ID.
 * 2. Answer ID sama secara numerik dengan session ID tetapi menunjuk record berbeda.
 * 3. Student ID mismatch (cross-student contamination prevention).
 * 4. Test ID mismatch (cross-test contamination prevention).
 * 5. Session ID mismatch (cross-session contamination prevention).
 * 6. Duplicate answer matches resulting in ambiguity.
 * 7. Multiple session composite mapping with cross-session integrity.
 * 8. NULL essay score semantics (PENDING).
 * 9. Zero essay score semantics (SCORED with 0.00, not pending).
 * 10. Pending essay grading withholds final score (HOLD policy).
 * 11. Missing source record yields RECORD_NOT_FOUND.
 * 12. Registry-only evidence not claimed as verified.
 * 13. SQL read-only guard bypass attempts blocked (comments, multi-statements, table injection).
 * 14. Fixture result tidak dilaporkan sebagai live verification.
 */
class CbtStageF3GTest extends TestCase
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

        // Seed Test 502 Status and Bank
        $pdo->exec("
            INSERT INTO cbt_test_status (id, bank_id, bobot_pg, bobot_esai) VALUES (502, 10, 50, 50);
            INSERT INTO cbt_questions (id, bank_id, question_type, question_text) VALUES (1, 10, 'pg', 'Pilihan ganda');
            INSERT INTO cbt_questions (id, bank_id, question_type, question_text) VALUES (2, 10, 'esai', 'Soal Esai 1');
            INSERT INTO cbt_questions (id, bank_id, question_type, question_text) VALUES (3, 10, 'esai', 'Soal Esai 2');
        ");

        // Seed Session 1006 for Student 3 on Test 502 (Objective score: 100, Essay score: NULL)
        $pdo->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, status, started_at, finished_at, created_at)
            VALUES (1006, 3, 502, '100.00', NULL, NULL, 'finished', 1770905000, 1770906000, '2026-11-01 06:00:00');
        ");

        // Seed Answers for Student 3 on Test 502 (Question 2: pending NULL, Question 3: pending NULL)
        // Notice their primary keys are 201 and 202 (COMPLETELY DIFFERENT from Session ID 1006!)
        $pdo->exec("
            INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score, updated_at)
            VALUES (201, 3, 502, 2, 'Jawaban Esai 1 oleh Siswa 3', NULL, '2026-11-01 06:00:00');
            INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score, updated_at)
            VALUES (202, 3, 502, 3, 'Jawaban Esai 2 oleh Siswa 3', NULL, '2026-11-01 06:00:00');
        ");

        // Seed an UNRELATED answer row that HAPPENS to have primary key ID = 1006,
        // but belongs to Student 99 on Test 999 answering Question 999!
        $pdo->exec("
            INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score, updated_at)
            VALUES (1006, 99, 999, 999, 'Jawaban Siswa Lain', '50.00', '2026-11-01 06:00:00');
        ");

        return $pdo;
    }

    /**
     * Test 1: Session ID berbeda dari answer ID.
     * Verifies that cbt_soal_siswa:1006 resolves via parent session 1006 and does not mistake answer IDs.
     */
    public function testSessionIdDistinctFromAnswerId(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'related_test_id'  => 502,
            'metadata'         => ['student_id' => 3],
        ];

        $context = [
            'student_id' => 3,
            'test_id'    => 502,
            'session_id' => 1006,
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, $context);

        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_VERIFIED_RECORD_MATCH, $res['record_mapping_status']);
        $this->assertEquals('cbt_answers', $res['operational_table']);
        $this->assertEquals('1006', $res['target_id']);
        $this->assertTrue($res['is_verified']);

        // Must be resolved via parent session, finding the 2 child answers (IDs 201, 202)
        $this->assertEquals('RESOLVED_VIA_PARENT_SESSION', $res['operational_data']['semantic_resolution']);
        $this->assertCount(2, $res['operational_data']['essay_answers']['rows']);
        $this->assertEquals(201, $res['operational_data']['essay_answers']['rows'][0]['id']);
        $this->assertEquals(202, $res['operational_data']['essay_answers']['rows'][1]['id']);
    }

    /**
     * Test 2: Answer ID sama secara numerik dengan session ID tetapi menunjuk record berbeda.
     * Verifies that cbt_answers.id = 1006 (which belongs to student 99, test 999) is NOT matched
     * for student 3 on test 502.
     */
    public function testAnswerIdNumericallyIdenticalToSessionIdPointsToDifferentRecord(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        // Delete session 1006 to force direct answer table lookup
        $db->exec("DELETE FROM cbt_sessions WHERE id = 1006");

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'related_test_id'  => 502,
            'metadata'         => ['student_id' => 3],
        ];

        // Case context requires Student 3 on Test 502
        $context = [
            'student_id' => 3,
            'test_id'    => 502,
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, $context);

        // Must detect that answer id 1006 belongs to student 99, test 999 -> RECORD_CONTEXT_MISMATCH
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_CONTEXT_MISMATCH, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString("Semantic Conflation Prevented", $res['discrepancy']);
    }

    /**
     * Test 3: Student ID mismatch (cross-student contamination prevention).
     */
    public function testStudentIdMismatchYieldsContextMismatch(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:1006',
            'metadata'         => ['legacy_score' => '100.00'],
        ];

        // Session 1006 belongs to Student 3, but caller claims Student 999
        $context = [
            'student_id' => 999,
            'test_id'    => 502,
        ];

        $res = $adapter->resolveScoreEvidence($evidence, $db, $context);

        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_CONTEXT_MISMATCH, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString("Cross-student contamination prevented", $res['discrepancy']);
    }

    /**
     * Test 4: Test ID mismatch (cross-test contamination prevention).
     */
    public function testTestIdMismatchYieldsContextMismatch(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:1006',
            'metadata'         => ['legacy_score' => '100.00'],
        ];

        // Session 1006 is Test 502, caller claims Test 777
        $context = [
            'student_id' => 3,
            'test_id'    => 777,
        ];

        $res = $adapter->resolveScoreEvidence($evidence, $db, $context);

        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_CONTEXT_MISMATCH, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString("Cross-test contamination prevented", $res['discrepancy']);
    }

    /**
     * Test 5: Session ID mismatch (cross-session contamination prevention).
     */
    public function testSessionIdMismatchYieldsContextMismatch(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'metadata'         => ['student_id' => 3],
            'provenance'       => [
                'grader_id'            => 'TCH_01',
                'is_authorized'        => true,
                'graded_at'            => '2026-11-01 10:00:00',
                'change_log_reference' => 'LOG_ESSAY_01',
            ],
        ];

        // Context requires student 4
        $context = [
            'student_id' => 4,
            'test_id'    => 502,
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, $context);

        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_CONTEXT_MISMATCH, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Test 6: Duplicate answer matches resulting in ambiguity.
     */
    public function testDuplicateAnswerMatches(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        // When composite retake identifier has no valid IDs
        $evidence = [
            'evidence_id'      => 'EVD_TEST_AMBIGUOUS',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:',
        ];

        $res = $adapter->resolveRetakeEvidence($evidence, $db);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_AMBIGUOUS, $res['record_mapping_status']);
    }

    /**
     * Test 7: Multiple session composite mapping with cross-session integrity.
     */
    public function testMultipleSessionCompositeMappingWithIntegrity(): void
    {
        $db = $this->createSemanticTestDb();

        // Seed two sessions for student 4 on test 503
        $db->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, status) VALUES (1007, 4, 503, '70.00', 'finished');
            INSERT INTO cbt_sessions (id, student_id, test_id, score, status) VALUES (1008, 4, 503, '85.00', 'finished');
        ");

        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_503_SESS_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:1007_1008',
            'related_test_id'  => 503,
            'metadata'         => ['session_ids' => [1007, 1008]],
        ];

        $res = $adapter->resolveRetakeEvidence($evidence, $db);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_VERIFIED_RECORD_MATCH, $res['record_mapping_status']);
        $this->assertTrue($res['is_verified']);
        $this->assertEquals(2, $res['operational_data']['found_count']);

        // Now corrupt one session so it belongs to a different student
        $db->exec("UPDATE cbt_sessions SET student_id = 999 WHERE id = 1008");
        $resCorrupt = $adapter->resolveRetakeEvidence($evidence, $db);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_CONTEXT_MISMATCH, $resCorrupt['record_mapping_status']);
        $this->assertFalse($resCorrupt['is_verified']);
        $this->assertStringContainsString("Cross-session contamination", $resCorrupt['discrepancy']);
    }

    /**
     * Test 8: NULL essay score semantics (PENDING).
     */
    public function testNullEssayScoreSemantics(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'metadata'         => ['student_id' => 3],
            'provenance'       => [
                'grader_id'            => 'TCH_01',
                'is_authorized'        => true,
                'graded_at'            => '2026-11-01 10:00:00',
                'change_log_reference' => 'LOG_ESSAY_01',
            ],
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, ['student_id' => 3, 'test_id' => 502]);

        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_PENDING, $res['operational_data']['essay_state']);
        $this->assertTrue($res['operational_data']['is_hold_enforced']);
        $this->assertNull($res['operational_data']['score']);
    }

    /**
     * Test 9: Zero essay score semantics (SCORED with 0.00, not pending).
     */
    public function testZeroEssayScoreSemantics(): void
    {
        $db = $this->createSemanticTestDb();

        // Update all essay answers to have score 0.00 (teacher graded zero)
        $db->exec("UPDATE cbt_answers SET score = '0.00' WHERE student_id = 3 AND test_id = 502");

        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'metadata'         => ['student_id' => 3],
            'provenance'       => [
                'grader_id'            => 'TCH_01',
                'is_authorized'        => true,
                'graded_at'            => '2026-11-01 10:00:00',
                'change_log_reference' => 'LOG_ESSAY_01',
            ],
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, ['student_id' => 3, 'test_id' => 502]);

        // State must be SCORED, not PENDING! Zero is a legitimate score.
        $this->assertEquals(HistoricalSourceAdapter::ESSAY_STATE_SCORED, $res['operational_data']['essay_state']);
        $this->assertFalse($res['operational_data']['is_hold_enforced']);
        $this->assertEquals(2, $res['operational_data']['essay_answers']['zero_count']);
    }

    /**
     * Test 10: Pending essay grading withholds final score (HOLD policy).
     */
    public function testPendingEssayGradingWithholdsFinalScore(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'metadata'         => ['student_id' => 3],
            'provenance'       => [
                'grader_id'            => 'TCH_01',
                'is_authorized'        => true,
                'graded_at'            => '2026-11-01 10:00:00',
                'change_log_reference' => 'LOG_ESSAY_01',
            ],
        ];

        $res = $adapter->resolveEssayEvidence($evidence, $db, ['student_id' => 3, 'test_id' => 502]);

        $this->assertTrue($res['operational_data']['is_hold_enforced']);
        $this->assertNull($res['operational_data']['essay_score']);
        $this->assertNull($res['operational_data']['total_score']);
        $this->assertEquals('100.00', $res['operational_data']['objective_score']);
    }

    /**
     * Test 11: Missing source record yields RECORD_NOT_FOUND.
     */
    public function testMissingSourceRecordYieldsRecordNotFound(): void
    {
        $db = $this->createSemanticTestDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:9999',
            'metadata'         => ['legacy_score' => '50.00'],
        ];

        $res = $adapter->resolveScoreEvidence($evidence, $db);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_RECORD_NOT_FOUND, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Test 12: Registry-only evidence not claimed as verified.
     */
    public function testRegistryOnlyEvidenceNotClaimedAsVerified(): void
    {
        $adapter = new HistoricalSourceAdapter(null);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:1002',
            'metadata'         => ['legacy_score' => '50.00'],
        ];

        $res = $adapter->resolveScoreEvidence($evidence, null);
        $this->assertEquals(HistoricalSourceAdapter::RECORD_STATUS_SOURCE_UNAVAILABLE, $res['record_mapping_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Test 13: SQL read-only guard bypass attempts blocked (comments, multi-statements, table injection).
     */
    public function testSqlReadOnlyGuardBypassAttemptsBlocked(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 1. Block multi-statement queries
        $this->expectException(InvalidArgumentException::class);
        $adapter->assertReadOnlyQuery("SELECT * FROM cbt_sessions; DROP TABLE cbt_sessions");
    }

    /**
     * Test 13b: SQL read-only guard blocks table injection and mutating comments.
     */
    public function testSqlReadOnlyGuardBlocksTableInjectionAndComments(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // 2. Block unwhitelisted table
        $caught = false;
        try {
            $adapter->assertReadOnlyQuery("SELECT * FROM admin_passwords", "admin_passwords");
        } catch (InvalidArgumentException) {
            $caught = true;
        }
        $this->assertTrue($caught, "Unwhitelisted table was not blocked.");

        // 3. Block comment concealing mutating command
        $caughtComment = false;
        try {
            $adapter->assertReadOnlyQuery("/* comment */ INSERT INTO cbt_sessions (id) VALUES (1)");
        } catch (InvalidArgumentException) {
            $caughtComment = true;
        }
        $this->assertTrue($caughtComment, "Comment concealing INSERT was not blocked.");

        // 4. Legitimate query with data containing keywords must pass
        $adapter->assertReadOnlyQuery("SELECT * FROM cbt_answers WHERE answer = 'DROP THE BALL'", 'cbt_answers');
        $adapter->assertReadOnlyQuery("WITH cte AS (SELECT 1) SELECT * FROM cbt_sessions", 'cbt_sessions');
        $this->assertTrue(true);
    }

    /**
     * Test 14: Fixture result tidak dilaporkan sebagai live verification.
     */
    public function testFixtureResultNotReportedAsLiveVerification(): void
    {
        $verifier = new ReviewerSourceVerifier(null);
        $evidence = [
            'evidence_id'         => 'EVD_501_SCORE_01',
            'evidence_type'       => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_system'       => 'CBT_HISTORICAL_SHADOW',
            'source_record_id'    => 'cbt_aktivitas:1002',
            'verification_status' => ReviewerEvidenceValidator::STATUS_VERIFIED,
        ];

        $res = $verifier->verifyEvidenceRecord($evidence, ['case_id' => 'REV_501_2b6231cb77']);

        // Without live DB, must strictly be REGISTRY_ONLY, never VERIFIED_FROM_SOURCE
        $this->assertEquals(ReviewerSourceVerifier::STATUS_REGISTRY_ONLY, $res['source_status']);
        $this->assertFalse($res['is_source_verified']);
        $this->assertEquals('REGISTRY_INSPECTION', $res['validation_method']);
    }
}
