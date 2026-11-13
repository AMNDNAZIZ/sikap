<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiAssistantLogsTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ai_assistant_logs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                ],
                'role_id' => [
                    'type'       => 'INT',
                    'constraint' => 5,
                    'null'       => true,
                ],
                'query' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 500,
                ],
                'intent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'matched_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'default'    => 'local',
                ],
                'ip_address' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 45,
                    'null'       => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['user_id', 'created_at']);
            $this->forge->createTable('ai_assistant_logs', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_assistant_logs', true);
    }
}

