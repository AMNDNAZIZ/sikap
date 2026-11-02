<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateCbtAssessmentIntelligenceV3GovernanceScoring
 * Stage B: Scoring, Essay Revisions, Review Workflow & Cryptographic Audit Trail
 *
 * Implements V3.30-R1A specification:
 * - cbt_essay_grades_v3 (Append-only essay grading revisions, single-confirmed active)
 * - cbt_session_scores_v3 (DEC-ESS-01 publication gating, score aggregation)
 * - cbt_bap_documents_v3 (Authoritative physical proctor exam records)
 * - cbt_bap_students_v3 (Student attendance & anomaly reporting per BAP)
 * - cbt_review_cases_v3 (Discrepancy tickets, retake/lineage/absence reviews)
 * - cbt_review_decisions_v3 (Official reviewer decisions & session ratification)
 * - cbt_keys_v3 (Data Encryption Key management for HMAC audit signing)
 * - cbt_audit_head_v3 (Singleton state lock table for concurrency serialization)
 * - cbt_audit_trail_v3 (Tamper-evident HMAC-SHA256 chained audit log)
 */
class CreateCbtAssessmentIntelligenceV3GovernanceScoring extends Migration
{
    public function up()
    {
        $db = $this->db;
        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0;");

        // --------------------------------------------------------------------
        // 1. Table `cbt_essay_grades_v3` (Append-Only Revision History)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_essay_grades_v3')) {
            $db->query("
                CREATE TABLE `cbt_essay_grades_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `answer_id` bigint(20) unsigned NOT NULL,
                  `session_id` int(10) unsigned NOT NULL,
                  `grader_id` bigint(20) unsigned NOT NULL,
                  `version_number` int(11) unsigned NOT NULL DEFAULT 1,
                  `score_awarded` decimal(6,3) NOT NULL,
                  `feedback` text DEFAULT NULL,
                  `is_current_confirmed` tinyint(1) NOT NULL DEFAULT 1,
                  `graded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_eg_ans_version` (`answer_id`, `version_number`),
                  KEY `idx_eg_session` (`session_id`),
                  KEY `idx_eg_grader` (`grader_id`, `graded_at`),
                  CONSTRAINT `fk_eg_answer` FOREIGN KEY (`answer_id`) 
                      REFERENCES `cbt_answers_v3` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 2. Table `cbt_session_scores_v3` (Final Score & DEC-ESS-01 Gating)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_session_scores_v3')) {
            $db->query("
                CREATE TABLE `cbt_session_scores_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `session_id` int(10) unsigned NOT NULL,
                  `score_pg` decimal(6,3) NOT NULL DEFAULT 0.000,
                  `score_esai` decimal(6,3) DEFAULT NULL,
                  `score_pg_kompleks` decimal(6,3) NOT NULL DEFAULT 0.000,
                  `score_bs` decimal(6,3) NOT NULL DEFAULT 0.000,
                  `final_score` decimal(6,3) DEFAULT NULL,
                  `report_score` decimal(6,3) DEFAULT NULL,
                  `raw_legacy_score` varchar(64) DEFAULT NULL,
                  `rounding_delta` decimal(5,4) NOT NULL DEFAULT 0.0000,
                  `pending_essay_count` int(11) unsigned NOT NULL DEFAULT 0,
                  `publication_eligibility` tinyint(1) NOT NULL DEFAULT 0,
                  `calculated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_sc_session` (`session_id`),
                  KEY `idx_sc_eligibility` (`publication_eligibility`, `final_score`),
                  CONSTRAINT `fk_sc_session` FOREIGN KEY (`session_id`) 
                      REFERENCES `cbt_sessions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 3. Table `cbt_bap_documents_v3` (Proctor Physical Record)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_bap_documents_v3')) {
            $db->query("
                CREATE TABLE `cbt_bap_documents_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `test_id` int(10) unsigned NOT NULL,
                  `bap_number` varchar(128) NOT NULL,
                  `room_code` varchar(32) NOT NULL,
                  `proctor_name` varchar(128) NOT NULL,
                  `supervisor_name` varchar(128) NOT NULL,
                  `total_registered` int(11) unsigned NOT NULL,
                  `total_present` int(11) unsigned NOT NULL,
                  `total_absent` int(11) unsigned NOT NULL,
                  `document_sha256` char(64) NOT NULL,
                  `signer_identity` varchar(255) NOT NULL,
                  `signed_at` datetime NOT NULL,
                  `verification_status` enum('UNVERIFIED','PROBABLE','AUTHENTICATED') NOT NULL DEFAULT 'AUTHENTICATED',
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_bap_doc_hash` (`document_sha256`),
                  UNIQUE KEY `uq_bap_test_room` (`test_id`, `room_code`, `bap_number`),
                  KEY `idx_bap_verification` (`verification_status`, `signed_at`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 4. Table `cbt_bap_students_v3` (Student Attendance Record in BAP)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_bap_students_v3')) {
            $db->query("
                CREATE TABLE `cbt_bap_students_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `bap_id` bigint(20) unsigned NOT NULL,
                  `student_id` int(10) unsigned NOT NULL,
                  `reported_status` enum('HADIR','TIDAK_HADIR','SAKIT','IZIN','DISPENSASI') NOT NULL,
                  `proctor_notes` text DEFAULT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_baps_bap_student` (`bap_id`, `student_id`),
                  KEY `idx_baps_student` (`student_id`, `reported_status`),
                  CONSTRAINT `fk_baps_bap` FOREIGN KEY (`bap_id`) 
                      REFERENCES `cbt_bap_documents_v3` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 5. Table `cbt_review_cases_v3` (Governance Discrepancy Tickets)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_review_cases_v3')) {
            $db->query("
                CREATE TABLE `cbt_review_cases_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `case_code` varchar(64) NOT NULL,
                  `scope` enum('SESSION_LEVEL','TEST_LEVEL') NOT NULL DEFAULT 'SESSION_LEVEL',
                  `test_id` int(10) unsigned NOT NULL,
                  `student_id` int(10) unsigned NOT NULL,
                  `session_id` int(10) unsigned DEFAULT NULL,
                  `anomaly_type` enum('MULTIPLE_RETAKES','ABSENCE_DISCREPANCY','SCORE_CONTRADICTION','UNVERIFIED_LINEAGE') NOT NULL,
                  `severity` enum('LOW','MEDIUM','HIGH','CRITICAL') NOT NULL DEFAULT 'HIGH',
                  `status` enum('PENDING','IN_REVIEW','RESOLVED','REJECTED') NOT NULL DEFAULT 'PENDING',
                  `evidence_summary` text NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_rc_case_code` (`case_code`),
                  KEY `idx_rc_status_type` (`status`, `anomaly_type`, `severity`),
                  KEY `idx_rc_student` (`student_id`, `test_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 6. Table `cbt_review_decisions_v3` (Ratified Review Decisions)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_review_decisions_v3')) {
            $db->query("
                CREATE TABLE `cbt_review_decisions_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `case_id` bigint(20) unsigned NOT NULL,
                  `reviewer_id` bigint(20) unsigned NOT NULL,
                  `decision` enum('RATIFY_SESSION','CONFIRM_ABSENCE','QUARANTINE_DATA','GRANT_RETEST') NOT NULL,
                  `selected_session_id` int(10) unsigned DEFAULT NULL,
                  `student_id` int(10) unsigned NOT NULL,
                  `test_id` int(10) unsigned NOT NULL,
                  `owner_consent_verified` tinyint(1) NOT NULL DEFAULT 0,
                  `justification` text NOT NULL,
                  `effective_score` decimal(6,3) DEFAULT NULL,
                  `decided_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_rd_case` (`case_id`),
                  KEY `idx_rd_reviewer` (`reviewer_id`, `decided_at`),
                  KEY `idx_rd_selected_session` (`selected_session_id`),
                  CONSTRAINT `fk_rd_case` FOREIGN KEY (`case_id`) 
                      REFERENCES `cbt_review_cases_v3` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 7. Table `cbt_keys_v3` (Cryptographic Key Management)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_keys_v3')) {
            $db->query("
                CREATE TABLE `cbt_keys_v3` (
                  `key_id` varchar(64) NOT NULL,
                  `key_purpose` varchar(32) NOT NULL DEFAULT 'AUDIT_SIGNING',
                  `key_version` int(11) unsigned NOT NULL DEFAULT 1,
                  `encrypted_key_bytes` blob NOT NULL,
                  `iv_bytes` varbinary(16) NOT NULL,
                  `auth_tag` varbinary(16) NOT NULL,
                  `status` enum('STAGING','ACTIVE','RETIRED','REVOKED') NOT NULL DEFAULT 'STAGING',
                  `activated_at` datetime DEFAULT NULL,
                  `retired_at` datetime DEFAULT NULL,
                  `revoked_at` datetime DEFAULT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`key_id`),
                  UNIQUE KEY `uq_keys_purpose_ver` (`key_purpose`, `key_version`),
                  KEY `idx_keys_status` (`status`, `key_purpose`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 8. Table `cbt_audit_head_v3` (Singleton Concurrency Row Lock)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_audit_head_v3')) {
            $db->query("
                CREATE TABLE `cbt_audit_head_v3` (
                  `id` int(10) unsigned NOT NULL DEFAULT 1,
                  `last_seq_no` bigint(20) unsigned NOT NULL DEFAULT 0,
                  `last_record_hash` char(64) NOT NULL DEFAULT '0000000000000000000000000000000000000000000000000000000000000000',
                  `active_key_id` varchar(64) NOT NULL,
                  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  CONSTRAINT `fk_ah_key` FOREIGN KEY (`active_key_id`) 
                      REFERENCES `cbt_keys_v3` (`key_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        // --------------------------------------------------------------------
        // 9. Table `cbt_audit_trail_v3` (Tamper-Evident HMAC Hash Chaining)
        // --------------------------------------------------------------------
        if (!$this->db->tableExists('cbt_audit_trail_v3')) {
            $db->query("
                CREATE TABLE `cbt_audit_trail_v3` (
                  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                  `seq_no` bigint(20) unsigned NOT NULL,
                  `event_type` varchar(64) NOT NULL,
                  `entity_table` varchar(64) NOT NULL,
                  `entity_id` varchar(64) NOT NULL,
                  `actor_id` bigint(20) unsigned NOT NULL,
                  `actor_role` varchar(32) NOT NULL,
                  `payload_canonical` mediumtext NOT NULL,
                  `key_id` varchar(64) NOT NULL,
                  `prev_hash` char(64) NOT NULL,
                  `record_hash` char(64) NOT NULL,
                  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `uq_at_seq` (`seq_no`),
                  KEY `idx_at_entity` (`entity_table`, `entity_id`),
                  KEY `idx_at_actor` (`actor_id`, `created_at`),
                  KEY `idx_at_event` (`event_type`, `created_at`),
                  CONSTRAINT `fk_at_key` FOREIGN KEY (`key_id`) 
                      REFERENCES `cbt_keys_v3` (`key_id`) ON DELETE RESTRICT ON UPDATE RESTRICT
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        }

        } finally {
            $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }

    public function down()
    {
        $db = $this->db;
        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0;");

            $v3Tables = [
                'cbt_audit_trail_v3',
                'cbt_audit_head_v3',
                'cbt_keys_v3',
                'cbt_review_decisions_v3',
                'cbt_review_cases_v3',
                'cbt_bap_students_v3',
                'cbt_bap_documents_v3',
                'cbt_session_scores_v3',
                'cbt_essay_grades_v3',
            ];

            foreach ($v3Tables as $table) {
                $db->query("DROP TABLE IF EXISTS `{$table}`");
            }
        } finally {
            $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }
}

