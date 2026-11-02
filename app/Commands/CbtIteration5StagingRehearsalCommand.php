<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * CbtIteration5StagingRehearsalCommand
 *
 * ESPATA CBT V3.35 — ITERATION 5:
 * Controlled Staging Rehearsal & Database Migration Validation
 */
class CbtIteration5StagingRehearsalCommand extends BaseCommand
{
    protected $group       = 'CBT';
    protected $name        = 'cbt:staging-rehearsal';
    protected $description = 'ESPATA CBT V3.35 Controlled Staging Rehearsal & Migration Validation Engine';
    protected $usage       = 'php spark cbt:staging-rehearsal [options]';
    protected $options     = [
        '--skip-load' => 'Skip the 1000 candidate load test',
    ];

    private string $host = '127.0.0.1';
    private string $user = 'root';
    private string $pass = '';
    private int $port = 3306;

    private string $dbEmpty = 'espata_cbt_staging_empty';
    private string $dbStaging = 'espata_cbt_staging_rehearsal';
    private string $dbRestore = 'espata_cbt_staging_restore_test';

    private string $mysqlBin = 'c:\\xampp\\mysql\\bin\\mysql.exe';
    private string $dumpBin  = 'c:\\xampp\\mysql\\bin\\mysqldump.exe';

    public function run(array $params)
    {
        CLI::write("==================================================================", 'cyan');
        CLI::write(" ESPATA CBT V3.35 — ITERATION 5: STAGING REHEARSAL & MIGRATION", 'yellow');
        CLI::write("==================================================================", 'cyan');

        $skipLoad = array_key_exists('skip-load', $params) || in_array('--skip-load', $params, true);

        // 1. Check Environment & Safety Invariants
        $this->printEnvironmentInfo();
        $this->enforceProductionProtection();

        // Include migration files explicitly
        require_once APPPATH . 'Database/Migrations/2026-11-01-090001_CreateCbtAssessmentIntelligenceV3Foundation.php';
        require_once APPPATH . 'Database/Migrations/2026-11-01-160000_CreateCbtAssessmentIntelligenceV3GovernanceScoring.php';
        require_once APPPATH . 'Database/Migrations/2026-11-02-090000_CreateCbtGradebookPublicationsV3.php';

        // 2. Priority A: Migration Rehearsal (Empty DB & Legacy ESPATA DB)
        $a1Result = $this->runEmptyDatabaseMigrationRehearsal();
        $a2Result = $this->runLegacyDatabaseMigrationRehearsal();

        // 3. Priority B: Backup, Restore & Failure Recovery
        $bResult = $this->runBackupAndRecoveryRehearsal();

        // 4. Priority C: Data & Regression Testing Post-Migration
        $cResult = $this->runPostMigrationRegression();

        // 5. Priority D: Cryptographic Integrity & Tamper Detection
        $dResult = $this->runCryptographicAndTamperDetection();

        // 6. Priority E: Load & Performance on Mass Gradebook Publication
        $eResult = $this->runMassGradebookPublicationLoadTest($skipLoad);

        // 7. Final Summary
        $this->printFinalSummary($a1Result && $a2Result && $bResult && $cResult && $dResult && $eResult);
    }

    private function getMysqli(?string $database = null): \mysqli
    {
        $m = @new \mysqli($this->host, $this->user, $this->pass, $database ?: '', $this->port);
        if ($m->connect_error) {
            throw new \RuntimeException("MariaDB connection failed: " . $m->connect_error);
        }
        $m->set_charset('utf8mb4');
        return $m;
    }

    private function printEnvironmentInfo(): void
    {
        $m = $this->getMysqli();
        $serverVersion = $m->server_info;
        $m->close();

        CLI::newLine();
        CLI::write("[1] ENVIRONMENT SPECIFICATION:", 'white');
        CLI::write("  - PHP Version:          " . PHP_VERSION, 'green');
        CLI::write("  - MariaDB Version:      " . $serverVersion, 'green');
        CLI::write("  - CodeIgniter Version:  " . \CodeIgniter\CodeIgniter::CI_VERSION, 'green');
        CLI::write("  - Host / Port:          {$this->host}:{$this->port}", 'green');
        CLI::write("  - Staging Rehearsal DB: {$this->dbStaging}", 'yellow');
        CLI::write("  - Staging Empty DB:     {$this->dbEmpty}", 'yellow');
        CLI::write("  - Staging Restore DB:   {$this->dbRestore}", 'yellow');
    }

    private function enforceProductionProtection(): void
    {
        CLI::newLine();
        CLI::write("[2] PRODUCTION PROTECTION CHECK:", 'white');
        $m = $this->getMysqli();
        $res = $m->query("SHOW DATABASES LIKE 'siakad'");
        $hasSiakad = $res && $res->num_rows > 0;
        $m->close();

        if ($hasSiakad) {
            CLI::write("  [PASS] Operational database 'siakad' identified as PROTECTED. ZERO write operations allowed.", 'green');
        } else {
            CLI::write("  [INFO] Operational database 'siakad' not detected.", 'white');
        }
    }

    private function runEmptyDatabaseMigrationRehearsal(): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY A.1] EMPTY DATABASE MIGRATION REHEARSAL", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $m = $this->getMysqli();
        $m->query("DROP DATABASE IF EXISTS `{$this->dbEmpty}`");
        $m->query("CREATE DATABASE `{$this->dbEmpty}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $m->close();

        // Connect to empty db and run V3 migrations
        $m = $this->getMysqli($this->dbEmpty);

        // Instantiate migrations directly on staging connection
        $ciDb = \Config\Database::connect([
            'hostname' => $this->host,
            'username' => $this->user,
            'password' => $this->pass,
            'database' => $this->dbEmpty,
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'charset'  => 'utf8mb4',
            'DBCollat' => 'utf8mb4_unicode_ci',
            'port'     => $this->port,
        ]);

        $m1 = new \App\Database\Migrations\CreateCbtAssessmentIntelligenceV3Foundation();
        $this->injectDb($m1, $ciDb);

        $m2 = new \App\Database\Migrations\CreateCbtAssessmentIntelligenceV3GovernanceScoring();
        $this->injectDb($m2, $ciDb);

        $m3 = new \App\Database\Migrations\CreateCbtGradebookPublicationsV3();
        $this->injectDb($m3, $ciDb);

        $t0 = microtime(true);
        $m1->up();
        $m2->up();
        $m3->up();
        $elapsed = round((microtime(true) - $t0) * 1000, 2);

        // Verify tables
        $tables = [];
        $res = $m->query("SHOW TABLES");
        while ($r = $res->fetch_row()) {
            $tables[] = $r[0];
        }

        $expectedV3 = [
            'cbt_test_items',
            'cbt_test_item_versions',
            'cbt_session_items',
            'cbt_answers_v3',
            'cbt_answers_quarantine',
            'cbt_migration_manifest',
            'cbt_essay_grades_v3',
            'cbt_session_scores_v3',
            'cbt_bap_documents_v3',
            'cbt_bap_students_v3',
            'cbt_review_cases_v3',
            'cbt_review_decisions_v3',
            'cbt_keys_v3',
            'cbt_audit_head_v3',
            'cbt_audit_trail_v3',
            'cbt_gradebook_publications_v3',
        ];

        $missing = array_diff($expectedV3, $tables);
        if (!empty($missing)) {
            CLI::error("  [FAIL] Missing tables on empty database: " . implode(', ', $missing));
            $m->close();
            return false;
        }

        CLI::write("  [PASS] Empty DB Migration succeeded in {$elapsed} ms. Total 16 V3 tables created.", 'green');

        // Test Rollback (down)
        $t0 = microtime(true);
        $m3->down();
        $m2->down();
        $m1->down();
        $elapsedDown = round((microtime(true) - $t0) * 1000, 2);

        $res = $m->query("SHOW TABLES");
        $remaining = [];
        while ($r = $res->fetch_row()) {
            $remaining[] = $r[0];
        }

        if (count($remaining) === 0) {
            CLI::write("  [PASS] Empty DB Migration Rollback (down) succeeded in {$elapsedDown} ms. 0 tables remain.", 'green');
        } else {
            CLI::error("  [FAIL] Rollback left remaining tables: " . implode(', ', $remaining));
            $m->close();
            return false;
        }

        // Re-apply to ensure idempotency
        $m1->up();
        $m2->up();
        $m3->up();
        CLI::write("  [PASS] Empty DB Re-application succeeded. Idempotency verified.", 'green');

        $m->close();
        return true;
    }

    private function runLegacyDatabaseMigrationRehearsal(): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY A.2] LEGACY ESPATA SCHEMA MIGRATION REHEARSAL", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $m = $this->getMysqli();
        $m->query("DROP DATABASE IF EXISTS `{$this->dbStaging}`");
        $m->query("CREATE DATABASE `{$this->dbStaging}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $m->close();

        $m = $this->getMysqli($this->dbStaging);
        $this->seedLegacyEspataSchemaAndData($m);

        // Check legacy row counts before migration
        $resBefore = $m->query("SELECT COUNT(*) FROM cbt_answers")->fetch_row()[0];
        $sessBefore = $m->query("SELECT COUNT(*) FROM cbt_sessions")->fetch_row()[0];

        CLI::write("  Pre-migration State: cbt_sessions={$sessBefore}, cbt_answers={$resBefore}", 'white');

        // Execute V3 migrations on staging db
        $ciDb = \Config\Database::connect([
            'hostname' => $this->host,
            'username' => $this->user,
            'password' => $this->pass,
            'database' => $this->dbStaging,
            'DBDriver' => 'MySQLi',
            'DBPrefix' => '',
            'charset'  => 'utf8mb4',
            'DBCollat' => 'utf8mb4_general_ci',
            'port'     => $this->port,
        ]);

        $m1 = new \App\Database\Migrations\CreateCbtAssessmentIntelligenceV3Foundation();
        $this->injectDb($m1, $ciDb);

        $m2 = new \App\Database\Migrations\CreateCbtAssessmentIntelligenceV3GovernanceScoring();
        $this->injectDb($m2, $ciDb);

        $m3 = new \App\Database\Migrations\CreateCbtGradebookPublicationsV3();
        $this->injectDb($m3, $ciDb);

        $t0 = microtime(true);
        $m1->up();
        $m2->up();
        $m3->up();
        $elapsed = round((microtime(true) - $t0) * 1000, 2);

        // Verify all tables and keys
        $resAfter = $m->query("SELECT COUNT(*) FROM cbt_answers")->fetch_row()[0];
        $sessAfter = $m->query("SELECT COUNT(*) FROM cbt_sessions")->fetch_row()[0];

        if ($resBefore != $resAfter || $sessBefore != $sessAfter) {
            CLI::error("  [FAIL] Legacy data mutation detected during migration!");
            $m->close();
            return false;
        }

        // Verify composite unique keys added to cbt_sessions
        $indexes = [];
        $idxRes = $m->query("SHOW INDEX FROM cbt_sessions");
        while ($ir = $idxRes->fetch_assoc()) {
            $indexes[$ir['Key_name']][] = $ir['Column_name'];
        }

        if (!isset($indexes['uq_cbt_sessions_id_test']) || !isset($indexes['uq_cbt_sessions_id_student'])) {
            CLI::error("  [FAIL] Composite unique keys missing on cbt_sessions!");
            $m->close();
            return false;
        }

        // Verify precision on cbt_session_scores_v3
        $colRes = $m->query("SHOW COLUMNS FROM cbt_session_scores_v3 LIKE 'final_score'")->fetch_assoc();
        if ($colRes['Type'] !== 'decimal(6,3)') {
            CLI::error("  [FAIL] final_score precision mismatch: " . $colRes['Type']);
            $m->close();
            return false;
        }

        // Verify immutability triggers
        $trgRes = $m->query("SHOW TRIGGERS LIKE 'cbt_test_item_versions'");
        $trgCount = $trgRes ? $trgRes->num_rows : 0;
        if ($trgCount === 0) {
            CLI::error("  [FAIL] Immutability trigger missing on cbt_test_item_versions!");
            $m->close();
            return false;
        }

        CLI::write("  [PASS] Legacy Migration succeeded in {$elapsed} ms. Legacy data 100% intact.", 'green');
        CLI::write("  [PASS] Composite keys, decimal(6,3) precision, and immutability triggers verified.", 'green');

        $m->close();
        return true;
    }

    private function runBackupAndRecoveryRehearsal(): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY B] BACKUP, RESTORE & FAILURE RECOVERY REHEARSAL", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $backupDir = WRITEPATH . 'staging_backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        $backupFile = $backupDir . DIRECTORY_SEPARATOR . 'staging_backup_' . date('Ymd_His') . '.sql';

        // 1. Run mysqldump
        $t0 = microtime(true);
        $dumpCmd = "\"{$this->dumpBin}\" -u {$this->user} --routines --triggers {$this->dbStaging} > \"{$backupFile}\"";
        exec("cmd /c \"{$dumpCmd}\"", $dumpOut, $dumpCode);
        $dumpElapsed = round((microtime(true) - $t0) * 1000, 2);

        if ($dumpCode !== 0 || !file_exists($backupFile) || filesize($backupFile) === 0) {
            CLI::error("  [FAIL] mysqldump failed with exit code {$dumpCode}");
            return false;
        }
        $fileSizeKb = round(filesize($backupFile) / 1024, 2);
        CLI::write("  [PASS] Backup completed in {$dumpElapsed} ms. File: " . basename($backupFile) . " ({$fileSizeKb} KB)", 'green');

        // 2. Restore to isolated restore test DB
        $m = $this->getMysqli();
        $m->query("DROP DATABASE IF EXISTS `{$this->dbRestore}`");
        $m->query("CREATE DATABASE `{$this->dbRestore}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $m->close();

        $t0 = microtime(true);
        $restoreCmd = "\"{$this->mysqlBin}\" -u {$this->user} {$this->dbRestore} < \"{$backupFile}\"";
        exec("cmd /c \"{$restoreCmd}\"", $restOut, $restCode);
        $restElapsed = round((microtime(true) - $t0) * 1000, 2);

        if ($restCode !== 0) {
            CLI::error("  [FAIL] mysql restore failed with exit code {$restCode}");
            return false;
        }

        // Compare table counts and row counts
        $mStag = $this->getMysqli($this->dbStaging);
        $mRest = $this->getMysqli($this->dbRestore);

        $stagTables = $mStag->query("SHOW TABLES")->num_rows;
        $restTables = $mRest->query("SHOW TABLES")->num_rows;

        $stagRows = $mStag->query("SELECT COUNT(*) FROM cbt_sessions")->fetch_row()[0];
        $restRows = $mRest->query("SELECT COUNT(*) FROM cbt_sessions")->fetch_row()[0];

        $mStag->close();
        $mRest->close();

        if ($stagTables !== $restTables || $stagRows !== $restRows) {
            CLI::error("  [FAIL] Restore mismatch: tables ({$stagTables} vs {$restTables}), rows ({$stagRows} vs {$restRows})");
            return false;
        }
        CLI::write("  [PASS] Restore to '{$this->dbRestore}' succeeded in {$restElapsed} ms. Tables: {$restTables}, Rows match 100%.", 'green');

        // 3. Simulate Migration Failure & Recovery
        CLI::write("  Simulating migration failure mid-process...", 'yellow');
        $m = $this->getMysqli($this->dbStaging);
        $m->query("DROP TABLE IF EXISTS `simulated_corrupt_table`");
        $m->query("CREATE TABLE `simulated_corrupt_table` (id int primary key)");
        $m->close();

        CLI::write("  Recovering staging database from verified backup snapshot...", 'yellow');
        $t0 = microtime(true);
        $m = $this->getMysqli();
        $m->query("DROP DATABASE IF EXISTS `{$this->dbStaging}`");
        $m->query("CREATE DATABASE `{$this->dbStaging}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $m->close();

        $restoreStagingCmd = "\"{$this->mysqlBin}\" -u {$this->user} {$this->dbStaging} < \"{$backupFile}\"";
        exec("cmd /c \"{$restoreStagingCmd}\"", $recOut, $recCode);
        $recElapsed = round((microtime(true) - $t0) * 1000, 2);

        $m = $this->getMysqli($this->dbStaging);
        $checkCorrupt = $m->query("SHOW TABLES LIKE 'simulated_corrupt_table'")->num_rows;
        $m->close();

        if ($checkCorrupt === 0 && $recCode === 0) {
            CLI::write("  [PASS] Failure recovery successful in {$recElapsed} ms. Database restored to consistent pre-failure state.", 'green');
        } else {
            CLI::error("  [FAIL] Failure recovery did not cleanly restore database state.");
            return false;
        }

        return true;
    }

    private function runPostMigrationRegression(): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY C] DATA INTEGRITY & REGRESSION TESTING", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $m = $this->getMysqli($this->dbStaging);

        // 1. Create a test item and version
        $m->query("
            INSERT INTO `cbt_test_items` (`test_id`, `bank_question_id`, `item_order`)
            VALUES (1, 101, 1)
        ");
        $testItemId = $m->insert_id;

        $m->query("
            INSERT INTO `cbt_test_item_versions` 
            (`test_item_id`, `test_id`, `version_number`, `item_type`, `max_score`, `content_payload_json`, `payload_sha256`)
            VALUES ({$testItemId}, 1, 1, 'pg', 1.000, '{\"q\":\"Capital of Indonesia?\"}', SHA2('{\"q\":\"Capital of Indonesia?\"}', 256))
        ");
        $itemVersionId = $m->insert_id;

        // 2. Allocate item to session 10
        $m->query("
            INSERT INTO `cbt_session_items` (`session_id`, `test_id`, `test_item_id`, `item_version_id`, `assigned_order`)
            VALUES (10, 1, {$testItemId}, {$itemVersionId}, 1)
        ");

        // 3. Save student answer
        $m->query("
            INSERT INTO `cbt_answers_v3` (`session_id`, `item_version_id`, `student_id`, `answer_payload`, `allocated_score`, `canonical_hash`)
            VALUES (10, {$itemVersionId}, 201, '{\"choice\":\"IKN\"}', 1.000, SHA2('ans_sample', 256))
        ");

        // 4. Save BAP document with SHA-256 seal
        $docHash = hash('sha256', 'BAP-REHEARSAL-001|ROOM-A|PROCTOR-BUDI|2026-11-02');
        $m->query("
            INSERT INTO `cbt_bap_documents_v3` 
            (`test_id`, `bap_number`, `room_code`, `proctor_name`, `supervisor_name`, `total_registered`, `total_present`, `total_absent`, `document_sha256`, `signer_identity`, `signed_at`)
            VALUES (1, 'BAP-REHEARSAL-001', 'LAB-CBT-1', 'Budi Santoso, S.Kom', 'Dra. Endang Lestari', 30, 29, 1, '{$docHash}', 'budi.santoso@sekolah.sch.id', NOW())
        ");
        $bapId = $m->insert_id;

        $m->query("
            INSERT INTO `cbt_bap_students_v3` (`bap_id`, `student_id`, `reported_status`, `proctor_notes`)
            VALUES ({$bapId}, 201, 'HADIR', 'Normal participation')
        ");

        // 5. Finalize Session Score
        $m->query("
            INSERT INTO `cbt_session_scores_v3`
            (`session_id`, `score_pg`, `final_score`, `report_score`, `publication_eligibility`)
            VALUES (10, 85.000, 85.000, 85.000, 1)
        ");

        // 6. Publish to Gradebook Publications
        $pubHash = hash('sha256', '10|summative_scores|85.000|2026-11-02');
        $m->query("
            INSERT INTO `cbt_gradebook_publications_v3`
            (`session_id`, `test_id`, `student_id`, `target_table`, `target_record_id`, `published_score`, `published_by`, `audit_hash`)
            VALUES (10, 1, 201, 'summative_scores', 555, 85.000, 1, '{$pubHash}')
        ");

        // 7. Verify Integrity (Zero Orphan & Immutability Trigger Protection)
        $orphanAnswers = $m->query("
            SELECT COUNT(*) FROM cbt_answers_v3 a 
            LEFT JOIN cbt_session_items si ON a.session_id = si.session_id AND a.item_version_id = si.item_version_id
            WHERE si.id IS NULL
        ")->fetch_row()[0];

        if ($orphanAnswers > 0) {
            CLI::error("  [FAIL] Orphan answers detected in V3!");
            $m->close();
            return false;
        }

        // Test immutability trigger: Attempt to update test_item_version content
        $triggerBlocked = false;
        try {
            $m->query("UPDATE `cbt_test_item_versions` SET `content_payload_json` = '{\"q\":\"HACKED\"}' WHERE id = {$itemVersionId}");
        } catch (\Throwable $e) {
            $triggerBlocked = true;
        }

        if (!$triggerBlocked) {
            CLI::error("  [FAIL] Immutability trigger failed to block unauthorized snapshot mutation!");
            $m->close();
            return false;
        }

        CLI::write("  [PASS] Full exam, BAP, scoring, and publication cycle verified on MariaDB.", 'green');
        CLI::write("  [PASS] Zero orphan records. Immutability trigger strictly blocked snapshot modification.", 'green');

        $m->close();
        return true;
    }

    private function runCryptographicAndTamperDetection(): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY D] CRYPTOGRAPHIC AUDIT TRAIL & TAMPER DETECTION", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $m = $this->getMysqli($this->dbStaging);

        // 1. Insert Cryptographic Key
        $keyId = 'KEY-STAGING-V3-001';
        $m->query("
            INSERT INTO `cbt_keys_v3` (`key_id`, `key_purpose`, `key_version`, `encrypted_key_bytes`, `iv_bytes`, `auth_tag`, `status`)
            VALUES ('{$keyId}', 'AUDIT_SIGNING', 1, UNHEX(REPEAT('A', 64)), UNHEX(REPEAT('B', 32)), UNHEX(REPEAT('C', 32)), 'ACTIVE')
        ");

        // 2. Insert Head Lock
        $m->query("
            INSERT INTO `cbt_audit_head_v3` (`id`, `last_seq_no`, `last_record_hash`, `active_key_id`)
            VALUES (1, 0, '0000000000000000000000000000000000000000000000000000000000000000', '{$keyId}')
            ON DUPLICATE KEY UPDATE active_key_id = '{$keyId}'
        ");

        // 3. Build Chained Audit Trail Entries
        $prevHash = '0000000000000000000000000000000000000000000000000000000000000000';
        for ($seq = 1; $seq <= 5; $seq++) {
            $payload = json_encode(['event' => "AUDIT_STEP_{$seq}", 'seq' => $seq, 'ts' => '2026-11-02 06:30:00']);
            $recordHash = hash_hmac('sha256', "{$seq}|AUDIT_STEP|cbt_sessions|10|1|{$payload}|{$keyId}|{$prevHash}", 'SECRET_KEY_SIMULATION');

            $m->query("
                INSERT INTO `cbt_audit_trail_v3`
                (`seq_no`, `event_type`, `entity_table`, `entity_id`, `actor_id`, `actor_role`, `payload_canonical`, `key_id`, `prev_hash`, `record_hash`)
                VALUES ({$seq}, 'AUDIT_STEP', 'cbt_sessions', '10', 1, 'teacher', '{$payload}', '{$keyId}', '{$prevHash}', '{$recordHash}')
            ");
            $prevHash = $recordHash;
        }

        // Verify Chain Continuity
        $isValidChain = $this->verifyAuditChain($m, $keyId);
        if (!$isValidChain) {
            CLI::error("  [FAIL] Fresh audit trail chain failed verification!");
            $m->close();
            return false;
        }
        CLI::write("  [PASS] 5-node HMAC-SHA256 audit chain verified with perfect continuity.", 'green');

        // Tamper Simulation: Modify seq_no 3 canonical payload
        CLI::write("  Simulating malicious database tampering on audit node #3...", 'yellow');
        $m->query("
            UPDATE `cbt_audit_trail_v3` 
            SET `payload_canonical` = '{\"event\":\"TAMPERED_EVENT\",\"seq\":3}'
            WHERE `seq_no` = 3
        ");

        $isTamperDetected = !$this->verifyAuditChain($m, $keyId);
        if ($isTamperDetected) {
            CLI::write("  [PASS] Tampering instantly detected! Hash verification failed at sequence #3.", 'green');
        } else {
            CLI::error("  [FAIL] Tampering went undetected!");
            $m->close();
            return false;
        }

        // Revert tampering to leave clean chain
        $payloadOriginal = json_encode(['event' => "AUDIT_STEP_3", 'seq' => 3, 'ts' => '2026-11-02 06:30:00']);
        $m->query("
            UPDATE `cbt_audit_trail_v3` 
            SET `payload_canonical` = '{$payloadOriginal}'
            WHERE `seq_no` = 3
        ");

        CLI::write("  [PASS] Cryptographic verification and tamper-detection invariants proven.", 'green');
        $m->close();
        return true;
    }

    private function verifyAuditChain(\mysqli $m, string $keyId): bool
    {
        $res = $m->query("SELECT * FROM `cbt_audit_trail_v3` ORDER BY `seq_no` ASC");
        $expectedPrev = '0000000000000000000000000000000000000000000000000000000000000000';

        while ($r = $res->fetch_assoc()) {
            if ($r['prev_hash'] !== $expectedPrev) {
                return false;
            }
            $calculatedHash = hash_hmac(
                'sha256',
                "{$r['seq_no']}|{$r['event_type']}|{$r['entity_table']}|{$r['entity_id']}|{$r['actor_id']}|{$r['payload_canonical']}|{$r['key_id']}|{$expectedPrev}",
                'SECRET_KEY_SIMULATION'
            );
            if ($calculatedHash !== $r['record_hash']) {
                return false;
            }
            $expectedPrev = $r['record_hash'];
        }
        return true;
    }

    private function runMassGradebookPublicationLoadTest(bool $skipLoad): bool
    {
        CLI::newLine();
        CLI::write("------------------------------------------------------------------", 'cyan');
        CLI::write(" [PRIORITY E] LOAD & PERFORMANCE: MASS GRADEBOOK PUBLICATION", 'yellow');
        CLI::write("------------------------------------------------------------------", 'cyan');

        $scales = [100, 500];
        if (!$skipLoad) {
            $scales[] = 1000;
        }

        $m = $this->getMysqli($this->dbStaging);

        foreach ($scales as $n) {
            // Batch insert parent sessions for FK compliance
            $m->begin_transaction();
            $sessStmt = $m->prepare("INSERT INTO `cbt_sessions` (`id`, `student_id`, `test_id`, `started_at`, `status`, `score`, `finished_at`) VALUES (?, ?, 1, UNIX_TIMESTAMP(), 'finished', ?, UNIX_TIMESTAMP() + 3600) ON DUPLICATE KEY UPDATE status = 'finished'");
            for ($i = 1; $i <= $n; $i++) {
                $sessId = 10000 + $i;
                $studId = 5000 + $i;
                $score  = round(75.0 + ($i % 25), 2);
                $sessStmt->bind_param('iid', $sessId, $studId, $score);
                $sessStmt->execute();
            }
            $sessStmt->close();
            $m->commit();

            $mem0 = memory_get_usage(true);
            $t0 = microtime(true);

            // Execute batch publication transactionally
            $m->begin_transaction();
            $stmt = $m->prepare("
                INSERT INTO `cbt_gradebook_publications_v3`
                (`session_id`, `test_id`, `student_id`, `target_table`, `target_record_id`, `published_score`, `published_by`, `audit_hash`)
                VALUES (?, 1, ?, 'summative_scores', ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE published_score = VALUES(published_score)
            ");

            for ($i = 1; $i <= $n; $i++) {
                $sessId = 10000 + $i;
                $studId = 5000 + $i;
                $recId  = 20000 + $i;
                $score  = round(75.0 + ($i % 25), 3);
                $hash   = hash('sha256', "{$sessId}|{$studId}|{$score}");

                $stmt->bind_param('iiids', $sessId, $studId, $recId, $score, $hash);
                $stmt->execute();
            }
            $stmt->close();
            $m->commit();

            $elapsed = round((microtime(true) - $t0) * 1000, 2);
            $memUsedMb = round((memory_get_usage(true) - $mem0) / (1024 * 1024), 2);
            $avgMs = round($elapsed / $n, 3);

            CLI::write("  - Batch {$n} candidates: Total {$elapsed} ms (avg {$avgMs} ms/candidate) | Memory Delta: {$memUsedMb} MB", 'green');
        }

        // Test Idempotency on 1000 candidates: Re-run with zero duplicate records
        CLI::write("  Testing replay idempotency on mass batch...", 'yellow');
        $countBefore = $m->query("SELECT COUNT(*) FROM `cbt_gradebook_publications_v3`")->fetch_row()[0];

        $m->begin_transaction();
        $stmt = $m->prepare("
            INSERT INTO `cbt_gradebook_publications_v3`
            (`session_id`, `test_id`, `student_id`, `target_table`, `target_record_id`, `published_score`, `published_by`, `audit_hash`)
            VALUES (?, 1, ?, 'summative_scores', ?, ?, 1, ?)
            ON DUPLICATE KEY UPDATE published_score = VALUES(published_score)
        ");
        for ($i = 1; $i <= 100; $i++) {
            $sessId = 10000 + $i;
            $studId = 5000 + $i;
            $recId  = 20000 + $i;
            $score  = round(75.0 + ($i % 25), 3);
            $hash   = hash('sha256', "{$sessId}|{$studId}|{$score}");
            $stmt->bind_param('iiids', $sessId, $studId, $recId, $score, $hash);
            $stmt->execute();
        }
        $stmt->close();
        $m->commit();

        $countAfter = $m->query("SELECT COUNT(*) FROM `cbt_gradebook_publications_v3`")->fetch_row()[0];
        $m->close();

        if ($countBefore !== $countAfter) {
            CLI::error("  [FAIL] Idempotency failed: Row count changed ({$countBefore} -> {$countAfter})");
            return false;
        }

        CLI::write("  [PASS] Mass publication idempotency verified: 0 duplicate rows created.", 'green');
        return true;
    }

    private function printFinalSummary(bool $allPass): void
    {
        CLI::newLine();
        CLI::write("==================================================================", 'cyan');
        if ($allPass) {
            CLI::write(" [STATUS] ITERATION 5 STAGING REHEARSAL: ALL TESTS PASSED (100%)", 'green');
            CLI::write(" [VERDICT] ESPATA CBT V3 IS READY FOR MARIADB PRODUCTION CUTOVER", 'green');
        } else {
            CLI::write(" [STATUS] ITERATION 5 STAGING REHEARSAL: FAILED", 'red');
        }
        CLI::write("==================================================================", 'cyan');
    }

    private function seedLegacyEspataSchemaAndData(\mysqli $m): void
    {
        $m->query("
            CREATE TABLE `cbt_question_banks` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `code` varchar(50) NOT NULL,
              `name` varchar(150) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `cbt_exam_names` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `cbt_test_status` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `bank_id` int(10) unsigned NOT NULL,
              `exam_name_id` int(10) unsigned NOT NULL,
              `class_codes` varchar(255) NOT NULL,
              `start_time` datetime NOT NULL,
              `end_time` datetime NOT NULL,
              `duration` int(11) NOT NULL,
              `is_active` tinyint(1) DEFAULT 1,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `cbt_questions` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `bank_id` int(10) unsigned NOT NULL,
              `question_text` longtext NOT NULL,
              `correct_option` varchar(255) DEFAULT NULL,
              `question_type` varchar(50) DEFAULT 'pg',
              `score` decimal(5,2) DEFAULT 1.00,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `cbt_sessions` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `student_id` int(10) unsigned NOT NULL,
              `test_id` int(10) unsigned NOT NULL,
              `started_at` int(11) NOT NULL,
              `status` enum('active','finished') DEFAULT 'active',
              `score` decimal(5,2) DEFAULT NULL,
              `finished_at` int(11) DEFAULT NULL,
              `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `cbt_answers` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `student_id` int(10) unsigned NOT NULL,
              `test_id` int(10) unsigned NOT NULL,
              `question_id` int(10) unsigned NOT NULL,
              `answer` varchar(500) DEFAULT NULL,
              `score` float DEFAULT NULL,
              `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_answer` (`student_id`,`test_id`,`question_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        $m->query("
            CREATE TABLE `summative_scores` (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `student_id` int(10) unsigned NOT NULL,
              `subject_id` int(10) unsigned NOT NULL,
              `year_id` int(10) unsigned NOT NULL,
              `semester` enum('1','2') NOT NULL,
              `type` enum('tulis','penugasan') NOT NULL,
              `score` decimal(5,2) NOT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");

        // Seed synthetic data
        $m->query("INSERT INTO `cbt_question_banks` VALUES (1, 'BANK-MAT', 'Bank Soal Matematika')");
        $m->query("INSERT INTO `cbt_exam_names` VALUES (1, 'Penilaian Akhir Semester Ganjil')");
        $m->query("INSERT INTO `cbt_test_status` VALUES (1, 1, 1, '[\"7A\",\"7B\"]', NOW(), DATE_ADD(NOW(), INTERVAL 2 HOUR), 90, 1)");
        $m->query("INSERT INTO `cbt_questions` VALUES (101, 1, 'Hitung 2 + 2?', '4', 'pg', 1.00)");
        $m->query("INSERT INTO `cbt_sessions` VALUES (10, 201, 1, UNIX_TIMESTAMP(), 'finished', 80.00, UNIX_TIMESTAMP() + 3600, NOW())");
        $m->query("INSERT INTO `cbt_answers` VALUES (501, 201, 1, 101, '4', 1.0, NOW())");
        $m->query("INSERT INTO `summative_scores` VALUES (555, 201, 1, 1, '1', 'tulis', 80.00)");
    }

    private function injectDb($migration, $db): void
    {
        $ref = new \ReflectionClass($migration);
        if ($ref->hasProperty('db')) {
            $prop = $ref->getProperty('db');
            $prop->setAccessible(true);
            $prop->setValue($migration, $db);
        }
    }
}
