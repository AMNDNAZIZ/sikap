<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixCbtExecutionSchemaAndSingleDeviceLogin extends Migration
{
    public function up()
    {
        $dbName = $this->db->getDatabase();

        // 1. users: Full Single-Device Login columns
        if (!$this->db->fieldExists('active_session_id', 'users')) {
            $this->forge->addColumn('users', [
                'active_session_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 128,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'is_active',
                ],
            ]);
        }

        if (!$this->db->fieldExists('last_activity', 'users')) {
            $this->forge->addColumn('users', [
                'last_activity' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'active_session_id',
                ],
            ]);
        }

        if (!$this->indexExists($dbName, 'users', 'idx_users_active_session')) {
            $this->db->query('CREATE INDEX `idx_users_active_session` ON `users` (`active_session_id`)');
        }

        // 2. cbt_sessions: Fix truncated timestamps, standardize INT(11) UNSIGNED, add locked & unique student_test key
        $this->db->query("UPDATE `cbt_sessions` SET `last_activity` = `started_at` WHERE `last_activity` IS NOT NULL AND `last_activity` < 1000000000");
        $this->db->query("UPDATE `cbt_sessions` SET `finished_at` = CASE WHEN `status` = 'finished' THEN `started_at` ELSE NULL END WHERE `finished_at` IS NOT NULL AND `finished_at` < 1000000000");

        $this->db->query('ALTER TABLE `cbt_sessions` MODIFY COLUMN `last_activity` INT(11) UNSIGNED NULL DEFAULT NULL, MODIFY COLUMN `finished_at` INT(11) UNSIGNED NULL DEFAULT NULL');

        if (!$this->db->fieldExists('locked', 'cbt_sessions')) {
            $this->forge->addColumn('cbt_sessions', [
                'locked' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 0,
                    'after'      => 'cheat_locked',
                ],
            ]);
        }

        // Deduplicate (student_id, test_id) keeping highest id before adding UNIQUE KEY
        $this->db->query("
            DELETE s1 FROM `cbt_sessions` s1
            INNER JOIN `cbt_sessions` s2
                ON s1.student_id = s2.student_id
                AND s1.test_id = s2.test_id
                AND s1.id < s2.id
        ");

        if (!$this->indexExists($dbName, 'cbt_sessions', 'uq_cbt_sessions_student_test')) {
            $this->db->query('ALTER TABLE `cbt_sessions` ADD UNIQUE KEY `uq_cbt_sessions_student_test` (`student_id`, `test_id`)');
        }
        if ($this->indexExists($dbName, 'cbt_sessions', 'student_test_idx')) {
            $this->db->query('ALTER TABLE `cbt_sessions` DROP INDEX `student_test_idx`');
        }

        // 3. cbt_answers: Expand answer to LONGTEXT & add is_doubtful column
        $this->db->query('ALTER TABLE `cbt_answers` MODIFY COLUMN `answer` LONGTEXT NULL');

        if (!$this->db->fieldExists('is_doubtful', 'cbt_answers')) {
            $this->forge->addColumn('cbt_answers', [
                'is_doubtful' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => false,
                    'default'    => 0,
                    'after'      => 'answer',
                ],
            ]);
        }

        // 4. cbt_question_banks: Drop contradictory ON DELETE CASCADE teacher FK so fk_cbt_bank_teacher (ON DELETE SET NULL) governs
        if ($this->fkExists($dbName, 'cbt_question_banks', 'cbt_question_banks_ibfk_2')) {
            $this->db->query('ALTER TABLE `cbt_question_banks` DROP FOREIGN KEY `cbt_question_banks_ibfk_2`');
        }

        // 5. cbt_session_items & cbt_answers_v3: Allow ON DELETE CASCADE on session FK to avoid resetSession/delete crashes
        $this->ensureCascadeFk(
            $dbName,
            'cbt_session_items',
            'fk_si_session_test',
            'FOREIGN KEY (`session_id`, `test_id`) REFERENCES `cbt_sessions` (`id`, `test_id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );

        $this->ensureCascadeFk(
            $dbName,
            'cbt_answers_v3',
            'fk_ans_session_student',
            'FOREIGN KEY (`session_id`, `student_id`) REFERENCES `cbt_sessions` (`id`, `student_id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );

        $this->ensureCascadeFk(
            $dbName,
            'cbt_answers_v3',
            'fk_ans_session_item',
            'FOREIGN KEY (`session_id`, `item_version_id`) REFERENCES `cbt_session_items` (`session_id`, `item_version_id`) ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }

    public function down()
    {
        $dbName = $this->db->getDatabase();

        if ($this->indexExists($dbName, 'users', 'idx_users_active_session')) {
            $this->db->query('DROP INDEX `idx_users_active_session` ON `users`');
        }
        if ($this->db->fieldExists('last_activity', 'users')) {
            $this->forge->dropColumn('users', 'last_activity');
        }
        if ($this->db->fieldExists('active_session_id', 'users')) {
            $this->forge->dropColumn('users', 'active_session_id');
        }

        if ($this->db->fieldExists('locked', 'cbt_sessions')) {
            $this->forge->dropColumn('cbt_sessions', 'locked');
        }
        if (!$this->indexExists($dbName, 'cbt_sessions', 'student_test_idx')) {
            $this->db->query('ALTER TABLE `cbt_sessions` ADD KEY `student_test_idx` (`student_id`, `test_id`)');
        }
        if ($this->indexExists($dbName, 'cbt_sessions', 'uq_cbt_sessions_student_test')) {
            $this->db->query('ALTER TABLE `cbt_sessions` DROP INDEX `uq_cbt_sessions_student_test`');
        }

        if ($this->db->fieldExists('is_doubtful', 'cbt_answers')) {
            $this->forge->dropColumn('cbt_answers', 'is_doubtful');
        }
    }

    private function indexExists(string $dbName, string $table, string $indexName): bool
    {
        $row = $this->db->query(
            'SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$dbName, $table, $indexName]
        )->getRowArray();

        return !empty($row);
    }

    private function fkExists(string $dbName, string $table, string $fkName): bool
    {
        $row = $this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = 'FOREIGN KEY' LIMIT 1",
            [$dbName, $table, $fkName]
        )->getRowArray();

        return !empty($row);
    }

    private function ensureCascadeFk(string $dbName, string $table, string $fkName, string $definition): void
    {
        $row = $this->db->query(
            'SELECT DELETE_RULE FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = ? AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? LIMIT 1',
            [$dbName, $table, $fkName]
        )->getRowArray();

        if ($row && strtoupper((string) ($row['DELETE_RULE'] ?? '')) !== 'CASCADE') {
            $this->db->query("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fkName}`");
            $this->db->query("ALTER TABLE `{$table}` ADD CONSTRAINT `{$fkName}` {$definition}");
        }
    }
}
