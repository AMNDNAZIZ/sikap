<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration: CreateCbtGradebookPublicationsV3
 *
 * ESPATA Assessment Intelligence V3 - Iteration 3
 * Manages tamper-evident linkages between finalized CBT V3 assessment results
 * and ESPATA academic gradebook records (summative_scores, final_exam_scores, pts_scores).
 */
class CreateCbtGradebookPublicationsV3 extends Migration
{
    public function up()
    {
        $db = $this->db;
        try {
            $db->query("SET FOREIGN_KEY_CHECKS = 0;");

            if (!$this->db->tableExists('cbt_gradebook_publications_v3')) {
                $db->query("
                    CREATE TABLE `cbt_gradebook_publications_v3` (
                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                      `session_id` int(10) unsigned NOT NULL,
                      `test_id` int(10) unsigned NOT NULL,
                      `student_id` int(10) unsigned NOT NULL,
                      `target_table` varchar(64) NOT NULL,
                      `target_record_id` int(10) unsigned DEFAULT NULL,
                      `published_score` decimal(6,3) NOT NULL,
                      `previous_score` decimal(6,3) DEFAULT NULL,
                      `status` enum('PUBLISHED','REVISED','RETRACTED') NOT NULL DEFAULT 'PUBLISHED',
                      `published_by` int(10) unsigned NOT NULL,
                      `published_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
                      `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                      `audit_hash` char(64) NOT NULL,
                      PRIMARY KEY (`id`),
                      UNIQUE KEY `uq_pub_session_target` (`session_id`, `target_table`),
                      KEY `idx_pub_student_test` (`student_id`, `test_id`),
                      KEY `idx_pub_target` (`target_table`, `target_record_id`),
                      CONSTRAINT `fk_pub_session` FOREIGN KEY (`session_id`) 
                          REFERENCES `cbt_sessions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
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
            $db->query("DROP TABLE IF EXISTS `cbt_gradebook_publications_v3`");
        } finally {
            $db->query("SET FOREIGN_KEY_CHECKS = 1;");
        }
    }
}

