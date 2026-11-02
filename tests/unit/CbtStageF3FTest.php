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
 * CbtStageF3FTest
 *
 * ESPATA Assessment Intelligence V3 - Stage F3F Test Suite
 * Historical-to-Operational Data Mapping & Read-Only Access Preparation Audit
 *
 * Covers 10 Technical Mapping & Failure Scenarios:
 * 1. Known table mapping (cbt_aktivitas -> cbt_sessions).
 * 2. Known table mapping (cbt_soal_siswa -> cbt_answers).
 * 3. Unknown historical table (unknown_table -> UNMAPPED_SOURCE).
 * 4. Record not found in operational database (MAPPED_RECORD_NOT_FOUND).
 * 5. Record found with exact matching values (MAPPED_VERIFIED).
 * 6. Record found but value mismatch (MAPPED_VALUE_MISMATCH).
 * 7. Multi-session composite key mapping (1007_1008 -> cbt_sessions).
 * 8. Attendance resolution (absence confirmation vs contradiction).
 * 9. Essay resolution (pending essay submission verification).
 * 10. Read-only safety verification (rejection of non-SELECT / mutating queries).
 * 11. Traceability Matrix generation for the 5 shadow review cases.
 * 12. Two-dimensional readiness separation (Decision Readiness vs Evidence Readiness).
 */
class CbtStageF3FTest extends TestCase
{
    private function createMockOperationalDb(): PDO
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
        ");

        // Seed representative operational rows
        // Session 1002 (Score round check)
        $pdo->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, status, started_at, finished_at, created_at)
            VALUES (1002, 2, 501, '49.98', NULL, '49.98', 'finished', 1770905999, 1770907000, '2026-11-01 06:00:00');
        ");

        // Sessions 1007 and 1008 (Retake session check)
        $pdo->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, status, started_at, finished_at, created_at)
            VALUES (1007, 4, 503, '75.00', NULL, '75.00', 'finished', 1770905000, 1770906000, '2026-11-01 06:00:00');
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, status, started_at, finished_at, created_at)
            VALUES (1008, 4, 503, '82.50', NULL, '82.50', 'finished', 1770907000, 1770908000, '2026-11-01 06:30:00');
        ");

        // Session 1006 and Answer 1006 (Pending Essay check)
        $pdo->exec("
            INSERT INTO cbt_sessions (id, student_id, test_id, score, essay_score, total_score, status, started_at, finished_at, created_at)
            VALUES (1006, 3, 502, '100.00', NULL, NULL, 'finished', 1770905000, 1770906000, '2026-11-01 06:00:00');
            INSERT INTO cbt_answers (id, student_id, test_id, question_id, answer, score, updated_at)
            VALUES (1006, 3, 502, 10, 'Jawaban esai siswa', NULL, '2026-11-01 06:00:00');
        ");

        return $pdo;
    }

    /**
     * Test 1: Known table mapping (cbt_aktivitas -> cbt_sessions).
     */
    public function testKnownTableMappingCbtAktivitas(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $mapping = $adapter->mapRecordIdentifier('cbt_aktivitas:1002');

        $this->assertTrue($mapping['is_mapped']);
        $this->assertEquals('cbt_aktivitas', $mapping['historical_table']);
        $this->assertEquals('cbt_sessions', $mapping['operational_table']);
        $this->assertEquals('1002', $mapping['record_id']);
        $this->assertFalse($mapping['is_multi']);
        $this->assertEquals('HIGH', $mapping['mapping_confidence']);
    }

    /**
     * Test 2: Known table mapping (cbt_soal_siswa -> cbt_answers).
     */
    public function testKnownTableMappingCbtSoalSiswa(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $mapping = $adapter->mapRecordIdentifier('cbt_soal_siswa:1006');

        $this->assertTrue($mapping['is_mapped']);
        $this->assertEquals('cbt_soal_siswa', $mapping['historical_table']);
        $this->assertEquals('cbt_answers', $mapping['operational_table']);
        $this->assertEquals('1006', $mapping['record_id']);
        $this->assertFalse($mapping['is_multi']);
        $this->assertEquals('HIGH', $mapping['mapping_confidence']);
    }

    /**
     * Test 3: Unknown historical table (unknown_table -> UNMAPPED_SOURCE).
     */
    public function testUnknownHistoricalTableYieldsUnmappedSource(): void
    {
        $adapter = new HistoricalSourceAdapter();
        $mapping = $adapter->mapRecordIdentifier('legacy_audit_log:999');

        $this->assertFalse($mapping['is_mapped']);
        $this->assertEquals('legacy_audit_log', $mapping['historical_table']);
        $this->assertNull($mapping['operational_table']);
        $this->assertEquals('UNMAPPED', $mapping['mapping_confidence']);

        $res = $adapter->resolveEvidence([
            'evidence_id'      => 'EVD_TEST_UNKNOWN',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'legacy_audit_log:999',
        ]);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_UNMAPPED_SOURCE, $res['adapter_status']);
        $this->assertFalse($res['is_verified']);
    }

    /**
     * Test 4: Record not found in operational database (MAPPED_RECORD_NOT_FOUND).
     */
    public function testMappedRecordNotFoundInOperationalDatabase(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:9999', // ID 9999 does not exist
            'metadata'         => ['legacy_score' => '49.98'],
        ];

        $res = $adapter->resolveScoreEvidence($evidence);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_RECORD_NOT_FOUND, $res['adapter_status']);
        $this->assertEquals('cbt_sessions', $res['operational_table']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString("Record ID '9999' not found in operational table 'cbt_sessions'", $res['discrepancy']);
    }

    /**
     * Test 5: Record found with exact matching values (MAPPED_VERIFIED).
     */
    public function testMappedRecordFoundWithMatchingValues(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:1002',
            'metadata'         => ['legacy_score' => '49.98'],
        ];

        $res = $adapter->resolveScoreEvidence($evidence);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VERIFIED, $res['adapter_status']);
        $this->assertEquals('cbt_sessions', $res['operational_table']);
        $this->assertTrue($res['is_verified']);
        $this->assertNull($res['discrepancy']);
        $this->assertNotNull($res['operational_data']);
        $this->assertEquals('49.98', $res['operational_data']['score']);
    }

    /**
     * Test 6: Record found but value mismatch (MAPPED_VALUE_MISMATCH).
     */
    public function testMappedRecordFoundWithValueMismatch(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_501_SCORE_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SCORE_COMPARISON,
            'source_record_id' => 'cbt_aktivitas:1002',
            'metadata'         => ['legacy_score' => '60.00'], // DB has 49.98, claimed 60.00
        ];

        $res = $adapter->resolveScoreEvidence($evidence);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VALUE_MISMATCH, $res['adapter_status']);
        $this->assertFalse($res['is_verified']);
        $this->assertStringContainsString("Operational score '49.98' does not match claimed legacy score '60.00'", $res['discrepancy']);
    }

    /**
     * Test 7: Multi-session composite key mapping (1007_1008 -> cbt_sessions).
     */
    public function testMultiSessionCompositeKeyMapping(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_503_SESS_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:1007_1008',
            'metadata'         => ['session_ids' => [1007, 1008]],
        ];

        $res = $adapter->resolveRetakeEvidence($evidence);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VERIFIED, $res['adapter_status']);
        $this->assertEquals('cbt_sessions', $res['operational_table']);
        $this->assertTrue($res['is_verified']);
        $this->assertEquals(2, $res['operational_data']['found_count']);
        $this->assertStringContainsString('DEC-RET-01 requires explicit administrative decree', $res['operational_data']['policy_note']);
    }

    /**
     * Test 8: Attendance resolution (absence confirmation vs contradiction).
     */
    public function testAttendanceResolution(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        // Case A: Student has finished session with score > 0 -> Contradicts absent!
        $evidenceContradicted = [
            'evidence_id'      => 'EVD_TEST_ATT_CONT',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:1002',
            'metadata'         => ['student_id' => '2'],
        ];
        $resA = $adapter->resolveAttendanceEvidence($evidenceContradicted);
        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VALUE_MISMATCH, $resA['adapter_status']);
        $this->assertFalse($resA['is_verified']);
        $this->assertStringContainsString('absent classification contradicted', $resA['discrepancy']);

        // Case B: Student has no session record (null session) -> Consistent with absent
        $evidenceAbsent = [
            'evidence_id'      => 'EVD_501_SESS_02',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_SESSION_RECORD,
            'source_record_id' => 'cbt_aktivitas:null',
            'related_test_id'  => 501,
            'metadata'         => ['student_id' => '999'], // student 999 has no sessions
        ];
        $resB = $adapter->resolveAttendanceEvidence($evidenceAbsent);
        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VERIFIED, $resB['adapter_status']);
        $this->assertTrue($resB['is_verified']);
        $this->assertEquals(0, $resB['operational_data']['session_count']);
    }

    /**
     * Test 9: Essay resolution (pending essay submission verification).
     */
    public function testEssayResolution(): void
    {
        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $evidence = [
            'evidence_id'      => 'EVD_502_SUBM_01',
            'evidence_type'    => ReviewerEvidenceValidator::TYPE_ESSAY_SUBMISSION,
            'source_record_id' => 'cbt_soal_siswa:1006',
            'metadata'         => ['student_id' => '3'],
        ];

        $res = $adapter->resolveEssayEvidence($evidence);

        $this->assertEquals(HistoricalSourceAdapter::STATUS_MAPPED_VERIFIED, $res['adapter_status']);
        $this->assertEquals('cbt_answers', $res['operational_table']);
        $this->assertTrue($res['is_verified']);
        $this->assertNull($res['operational_data']['score']); // Score remains NULL (pending)
    }

    /**
     * Test 10: Read-only safety verification (rejection of non-SELECT / mutating queries).
     */
    public function testReadOnlySafetyVerification(): void
    {
        $adapter = new HistoricalSourceAdapter();

        // Valid read-only queries
        $this->expectNotToPerformAssertions();
        $adapter->assertReadOnlyQuery("SELECT * FROM cbt_sessions WHERE id = 1");
        $adapter->assertReadOnlyQuery("SHOW TABLES");

        // Mutating queries must throw InvalidArgumentException
        $mutating = [
            "INSERT INTO cbt_sessions (id) VALUES (1)",
            "UPDATE cbt_sessions SET score = 100 WHERE id = 1",
            "DELETE FROM cbt_sessions WHERE id = 1",
            "DROP TABLE cbt_sessions",
            "ALTER TABLE cbt_sessions ADD COLUMN fake INT",
            "CREATE TABLE hack (id INT)",
            "GRANT ALL PRIVILEGES ON cbt.* TO 'user'@'localhost'",
        ];

        foreach ($mutating as $sql) {
            $caught = false;
            try {
                $adapter->assertReadOnlyQuery($sql);
            } catch (InvalidArgumentException) {
                $caught = true;
            }
            if (!$caught) {
                throw new \RuntimeException("Mutating SQL was not rejected: {$sql}");
            }
        }
    }

    /**
     * Test 11: Traceability Matrix generation for the 5 shadow review cases.
     */
    public function testTraceabilityMatrixGeneration(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $cases = $workflow->getQueue();

        $evidenceValidator = new ReviewerEvidenceValidator();
        $evidenceList = $evidenceValidator->getAllEvidence();

        $db = $this->createMockOperationalDb();
        $adapter = new HistoricalSourceAdapter($db);

        $matrix = $adapter->auditTraceability($cases, $evidenceList, $db);

        $this->assertCount(5, $matrix);
        $this->assertArrayHasKey('REV_501_2b6231cb77', $matrix);
        $this->assertArrayHasKey('REV_501_7b48bc8a1f', $matrix);
        $this->assertArrayHasKey('REV_502_3ff144b11c', $matrix);
        $this->assertArrayHasKey('REV_503_4266a4ed7f', $matrix);
        $this->assertArrayHasKey('REV_503_fc68cd64f2', $matrix);

        // Verify Case 1 mapped to cbt_sessions:1002
        $c1 = $matrix['REV_501_2b6231cb77'];
        $this->assertEquals('cbt_sessions', $c1['operational_table']);
        $this->assertEquals('1002', $c1['operational_id']);
        $this->assertEquals('HIGH', $c1['mapping_confidence']);
    }

    /**
     * Test 12: Two-dimensional readiness separation (Decision Readiness vs Evidence Readiness).
     */
    public function testTwoDimensionalReadinessSeparation(): void
    {
        $workflow = new ReviewerWorkflowService();
        $workflow->loadFromShadowDeviations();
        $cases = $workflow->getQueue();

        $evidenceValidator = new ReviewerEvidenceValidator();
        $evidenceList = $evidenceValidator->getAllEvidence();

        // When DB is null (simulating no DB connected)
        $adapterNoDb = new HistoricalSourceAdapter(null);
        $matrixNoDb = $adapterNoDb->auditTraceability($cases, $evidenceList, null);

        // For Case 1 (DEC-ROUND-01):
        // Decision Readiness is TRUE (policy ACK delta <= 0.05 is established)
        // Evidence Readiness is FALSE (no DB connected / unverified live source)
        $c1 = $matrixNoDb['REV_501_2b6231cb77'];
        $this->assertTrue($c1['decision_readiness']);
        $this->assertFalse($c1['evidence_readiness']);
    }
}

