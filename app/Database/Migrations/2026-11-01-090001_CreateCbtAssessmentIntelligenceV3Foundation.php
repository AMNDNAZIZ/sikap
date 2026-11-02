<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateCbtAssessmentIntelligenceV3Foundation (V3.23 Hardened)
 * Stage A: Database Foundation for ESPATA Assessment Intelligence V3
 *
 * Implements:
 * - cbt_test_items
 * - cbt_test_item_versions
 * - cbt_session_items
 * - cbt_answers_v3
 * - cbt_answers_quarantine (Lossless, 10 physical legacy columns)
 * - cbt_migration_manifest
 *
 * Hardening:
 * - Safe rollback: Only drops objects created by this migration
 * - Complete snapshot immutability on session items (including assigned_order) and item versions
 * - Lossless quarantine without truncation (LONGTEXT for answer_raw)
 * - Zero modifications to legacy tables or data
 */
class CreateCbtAssessmentIntelligenceV3Foundation extends Migration
{
    public function up()
    {
        $db = $this->db;
        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0;");

        // --------------------------------------------------------------------
        // Step 1: Ensure Parent Composite Unique Keys on `cbt_sessions`
        // --------------------------------------------------------------------
        if ($this->db->tableExists('cbt_sessions')) {
            $existingIndexes = $this->getIndexNames('cbt_sessions');

            if (!in_array('uq_cbt_sessions_id_test', $existingIndexes, true)) {
                $db->query("ALTER TABLE `cbt_sessions` ADD UNIQUE KEY `uq_cbt_sessions_id_test` (`id`, `test_id`)");
            }
            if (!in_array('uq_cbt_sessions_id_student', $existingIndexes, true)) {
                $db->query("ALTER TABLE `cbt_sessions` ADD UNIQUE KEY `uq_cbt_sessions_id_student` (`id`, `student_id`)");
            }
        }

        // --------------------------------------------------------------------
        // Step 2: Table `cbt_test_items` (Test Question Container)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_test_items')) {
            $db->query("
                CREATE TABLE `cbt_test_items` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `test_id` int(10) unsigned NOT NULL,
                  `bank_question_id` int(10) unsigned NOT NULL,
                  `item_order` int(11) unsigned NOT NULL DEFAULT 0,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_test_item` (`test_id`, `bank_question_id`),
                  UNIQUE KEY `uq_ti_id_test` (`id`, `test_id`),
                  KEY `idx_ti_bank_question` (`bank_question_id`),
                  CONSTRAINT `fk_ti_test` FOREIGN KEY (`test_id`) 
                      REFERENCES `cbt_test_status` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
                  CONSTRAINT `fk_ti_bank_question` FOREIGN KEY (`bank_question_id`) 
                      REFERENCES `cbt_questions` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 3: Table `cbt_test_item_versions` (Immutable Item Snapshot)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_test_item_versions')) {
            $db->query("
                CREATE TABLE `cbt_test_item_versions` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `test_item_id` bigint(20) unsigned NOT NULL,
                  `test_id` int(10) unsigned NOT NULL,
                  `version_number` int(11) unsigned NOT NULL DEFAULT 1,
                  `item_type` enum('pg','esai','pg_kompleks','benar_salah') NOT NULL,
                  `max_score` decimal(6,3) NOT NULL DEFAULT 1.000,
                  `content_payload_json` longtext NOT NULL,
                  `payload_sha256` char(64) NOT NULL,
                  `is_active` tinyint(1) NOT NULL DEFAULT 1,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_item_version` (`test_item_id`, `version_number`),
                  UNIQUE KEY `uq_tiv_id_item_test` (`id`, `test_item_id`, `test_id`),
                  KEY `idx_tiv_test` (`test_id`),
                  CONSTRAINT `fk_tiv_item_test` FOREIGN KEY (`test_item_id`, `test_id`) 
                      REFERENCES `cbt_test_items` (`id`, `test_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 4: Table `cbt_session_items` (Allocated Questions per Session)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_session_items')) {
            $db->query("
                CREATE TABLE `cbt_session_items` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `session_id` int(10) unsigned NOT NULL,
                  `test_id` int(10) unsigned NOT NULL,
                  `test_item_id` bigint(20) unsigned NOT NULL,
                  `item_version_id` bigint(20) unsigned NOT NULL,
                  `assigned_order` int(11) unsigned NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_session_item` (`session_id`, `item_version_id`),
                  KEY `idx_si_item_version` (`item_version_id`),
                  KEY `idx_si_session_test_item` (`session_id`, `test_item_id`),
                  -- Enforce Condition 1: Session only contains items from the same test
                  CONSTRAINT `fk_si_session_test` FOREIGN KEY (`session_id`, `test_id`) 
                      REFERENCES `cbt_sessions` (`id`, `test_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
                  -- Enforce Conditions 2 & 3: Item version and item match the exact test
                  CONSTRAINT `fk_si_version_item_test` FOREIGN KEY (`item_version_id`, `test_item_id`, `test_id`) 
                      REFERENCES `cbt_test_item_versions` (`id`, `test_item_id`, `test_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 5: Table `cbt_answers_v3` (Operational Answers V3)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_answers_v3')) {
            $db->query("
                CREATE TABLE `cbt_answers_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_answer_id` int(10) unsigned DEFAULT NULL,
                  `session_id` int(10) unsigned NOT NULL,
                  `item_version_id` bigint(20) unsigned NOT NULL,
                  `student_id` int(10) unsigned NOT NULL,
                  `answer_payload` longtext DEFAULT NULL,
                  `allocated_score` decimal(6,3) DEFAULT NULL,
                  `is_doubtful` tinyint(1) NOT NULL DEFAULT 0,
                  `answer_version` int(11) unsigned NOT NULL DEFAULT 1,
                  `provenance_status` enum('PROBABLE_MATCH','OPERATIONAL_V3') NOT NULL DEFAULT 'OPERATIONAL_V3',
                  `canonical_hash` char(64) NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_ans_session_item` (`session_id`, `item_version_id`),
                  UNIQUE KEY `uq_ans_legacy_id` (`legacy_answer_id`),
                  KEY `idx_ans_student` (`student_id`),
                  -- Enforce Condition 4: Answer only references item version assigned to that session
                  CONSTRAINT `fk_ans_session_item` FOREIGN KEY (`session_id`, `item_version_id`) 
                      REFERENCES `cbt_session_items` (`session_id`, `item_version_id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
                  -- Enforce Condition 5: Student strictly matches session owner
                  CONSTRAINT `fk_ans_session_student` FOREIGN KEY (`session_id`, `student_id`) 
                      REFERENCES `cbt_sessions` (`id`, `student_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 6: Table `cbt_answers_quarantine` (Lossless Reversible Quarantine)
        // Reflects the exact 10 physical legacy columns with LONGTEXT safety
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_answers_quarantine')) {
            $db->query("
                CREATE TABLE `cbt_answers_quarantine` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `legacy_answer_id` int(10) unsigned NOT NULL,
                  `student_id` int(10) unsigned NOT NULL,
                  `test_id` int(10) unsigned NOT NULL,
                  `question_id` int(10) unsigned NOT NULL,
                  `answer_raw` longtext DEFAULT NULL,
                  `score_raw` float DEFAULT NULL,
                  `is_doubtful_raw` tinyint(1) NOT NULL DEFAULT 0,
                  `version_raw` int(11) NOT NULL DEFAULT 1,
                  `updated_at_raw` datetime DEFAULT NULL,
                  `updated_at_micro_raw` decimal(16,6) DEFAULT NULL,
                  `quarantine_reason` varchar(64) NOT NULL,
                  `quarantine_details_json` longtext NOT NULL,
                  `canonical_hash` char(64) NOT NULL,
                  `quarantined_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_quar_legacy_id` (`legacy_answer_id`),
                  KEY `idx_quar_lookup` (`student_id`, `test_id`, `question_id`),
                  KEY `idx_quar_reason` (`quarantine_reason`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 7: Table `cbt_migration_manifest` (Batch Audit Trail)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_migration_manifest')) {
            $db->query("
                CREATE TABLE `cbt_migration_manifest` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `batch_number` int(11) unsigned NOT NULL,
                  `start_legacy_id` int(10) unsigned NOT NULL,
                  `end_legacy_id` int(10) unsigned NOT NULL,
                  `row_count` int(11) unsigned NOT NULL,
                  `migrated_v3_count` int(11) unsigned NOT NULL,
                  `quarantined_count` int(11) unsigned NOT NULL,
                  `batch_merkle_root` char(64) NOT NULL,
                  `status` enum('IN_PROGRESS','VERIFIED','FAILED') NOT NULL DEFAULT 'IN_PROGRESS',
                  `executed_by` varchar(64) NOT NULL,
                  `execution_duration_ms` int(11) unsigned NOT NULL DEFAULT 0,
                  `committed_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_manifest_batch` (`batch_number`),
                  KEY `idx_manifest_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // Step 8: Database Triggers for Explicit Immutability Protection
        // --------------------------------------------------------------------
        $this->createImmutabilityTriggers($db);
        } finally {
            $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    public function down()
    {
        $db = $this->db;
        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0;");

            // 1. Drop Triggers with exact names
            $db->query("DROP TRIGGER IF EXISTS `trg_cbt_tiv_immutable`");
            $db->query("DROP TRIGGER IF EXISTS `trg_cbt_si_immutable`");

            // 2. Drop V3 Tables in Strict Reverse Dependency Order
            $v3Tables = [
                'cbt_answers_v3',
                'cbt_session_items',
                'cbt_test_item_versions',
                'cbt_test_items',
                'cbt_answers_quarantine',
                'cbt_migration_manifest',
            ];

            foreach ($v3Tables as $table) {
                $db->query("DROP TABLE IF EXISTS `{$table}`");
            }

            // 3. Drop Auxiliary Unique Keys Added to cbt_sessions
            // Only drop if the table exists
            if ($this->db->tableExists('cbt_sessions')) {
                $existingIndexes = $this->getIndexNames('cbt_sessions');

                if (in_array('uq_cbt_sessions_id_test', $existingIndexes, true)) {
                    $db->query("ALTER TABLE `cbt_sessions` DROP INDEX `uq_cbt_sessions_id_test`");
                }
                if (in_array('uq_cbt_sessions_id_student', $existingIndexes, true)) {
                    $db->query("ALTER TABLE `cbt_sessions` DROP INDEX `uq_cbt_sessions_id_student`");
                }
            }
        } finally {
            $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    /**
     * Retrieve index names for a given table safely across MariaDB / MySQL.
     */
    private function getIndexNames(string $table): array
    {
        $db = $this->db;
        $dbName = $db->database;

        $query = $db->query("
            SELECT DISTINCT INDEX_NAME 
            FROM information_schema.STATISTICS 
            WHERE TABLE_SCHEMA = '{$dbName}' 
              AND TABLE_NAME = '{$table}'
        ");

        if (!$query) {
            return [];
        }

        $names = [];
        foreach ($query->getResultArray() as $row) {
            $names[] = $row['INDEX_NAME'] ?? $row['index_name'] ?? '';
        }

        return $names;
    }

    /**
     * Create explicit database triggers to prevent mutation of frozen snapshots.
     */
    private function createImmutabilityTriggers($db): void
    {
        // Trigger on cbt_test_item_versions:
        // Blocks mutation of ANY content, type, score, or identity attribute.
        // Permits toggling `is_active` without altering frozen snapshot content.
        $db->query("DROP TRIGGER IF EXISTS `trg_cbt_tiv_immutable`");
        $db->query("
            CREATE TRIGGER `trg_cbt_tiv_immutable`
            BEFORE UPDATE ON `cbt_test_item_versions`
            FOR EACH ROW
            BEGIN
                IF (OLD.content_payload_json != NEW.content_payload_json 
                    OR OLD.payload_sha256 != NEW.payload_sha256
                    OR OLD.max_score != NEW.max_score 
                    OR OLD.item_type != NEW.item_type
                    OR OLD.version_number != NEW.version_number
                    OR OLD.test_item_id != NEW.test_item_id 
                    OR OLD.test_id != NEW.test_id) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'CBT_V3_IMMUTABLE_ERROR: Test item version snapshot identity and content are strictly immutable';
                END IF;
            END
        ");

        // Trigger on cbt_session_items:
        // Prevents any change to assigned items or their display order once allocated.
        $db->query("DROP TRIGGER IF EXISTS `trg_cbt_si_immutable`");
        $db->query("
            CREATE TRIGGER `trg_cbt_si_immutable`
            BEFORE UPDATE ON `cbt_session_items`
            FOR EACH ROW
            BEGIN
                IF (OLD.session_id != NEW.session_id 
                    OR OLD.test_id != NEW.test_id
                    OR OLD.test_item_id != NEW.test_item_id 
                    OR OLD.item_version_id != NEW.item_version_id
                    OR OLD.assigned_order != NEW.assigned_order) THEN
                    SIGNAL SQLSTATE '45000'
                    SET MESSAGE_TEXT = 'CBT_V3_IMMUTABLE_ERROR: Session items allocation and order are strictly immutable';
                END IF;
            END
        ");
    }
}
