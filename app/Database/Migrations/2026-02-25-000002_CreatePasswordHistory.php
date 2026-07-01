<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordHistory extends Migration
{
    public function up()
    {
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
        
        // Drop constraint if already exists anywhere in this DB (prevents errno 121 on re-run)
        $existingFk = $this->db->query("
            SELECT TABLE_NAME FROM information_schema.KEY_COLUMN_USAGE
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND CONSTRAINT_NAME = 'fk_password_history_user'
            LIMIT 1
        ")->getRow();
        
        if ($existingFk) {
            $this->db->query("ALTER TABLE `{$existingFk->TABLE_NAME}` DROP FOREIGN KEY fk_password_history_user");
        }
        
        // Add foreign key
        $this->db->query('ALTER TABLE password_history ADD CONSTRAINT fk_password_history_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');
    }

    public function down()
    {
        // Drop FK before dropping table
        try {
            $this->db->query('ALTER TABLE password_history DROP FOREIGN KEY fk_password_history_user');
        } catch (\Exception $e) {
            // FK may not exist, ignore
        }
        $this->forge->dropTable('password_history', true);
    }
}
