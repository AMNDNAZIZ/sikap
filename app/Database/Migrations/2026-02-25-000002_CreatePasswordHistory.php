<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordHistory extends Migration
{
    public function up()
    {
        // --- Step 1: Pre-clean orphan FK constraint in InnoDB dictionary ---
        // errno 121 fires during CREATE TABLE when the FK name is already
        // registered in InnoDB's internal dictionary (even if the table doesn't exist).
        // We must clean it up BEFORE calling createTable.
        $this->db->query('SET foreign_key_checks = 0');

        $existingFk = $this->db->query("
            SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_password_history_user'
            LIMIT 1
        ")->getRow();

        if ($existingFk) {
            try {
                $this->db->query("ALTER TABLE `{$existingFk->TABLE_NAME}` DROP FOREIGN KEY fk_password_history_user");
            } catch (\Exception $e) {
                // Orphan — ignore, InnoDB will resolve on table drop
            }
        }

        // Also drop the table if it exists as a half-created orphan
        $this->forge->dropTable('password_history', true);

        $this->db->query('SET foreign_key_checks = 1');

        // --- Step 2: Create the table fresh ---
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'password_hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('created_at');

        $this->forge->createTable('password_history', true);

        // --- Step 3: Add foreign key ---
        $this->db->query('ALTER TABLE password_history ADD CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down()
    {
        $this->db->query('SET foreign_key_checks = 0');
        try {
            $this->db->query('ALTER TABLE password_history DROP FOREIGN KEY fk_password_history_user');
        } catch (\Exception $e) {
            // FK may not exist, ignore
        }
        $this->forge->dropTable('password_history', true);
        $this->db->query('SET foreign_key_checks = 1');
    }
}
