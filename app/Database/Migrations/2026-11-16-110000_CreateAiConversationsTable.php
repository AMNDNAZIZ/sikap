<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiConversationsTable extends Migration
{
    public function up()
    {
        // 1. Tabel ai_conversations (Induk Sesi Percakapan Multi-turn)
        if (!$this->db->tableExists('ai_conversations')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'conversation_uuid' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'role_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ],
                'title' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'last_intent' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'context_summary' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('conversation_uuid');
            $this->forge->addKey(['user_id', 'updated_at']);
            $this->forge->createTable('ai_conversations', true, [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }

        // 2. Tabel ai_conversation_messages (Butir Giliran Percakapan User & Asisten)
        if (!$this->db->tableExists('ai_conversation_messages')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'conversation_id' => [
                    'type'       => 'BIGINT',
                    'constraint' => 20,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'sender_role' => [
                    'type'       => 'ENUM',
                    'constraint' => ['user', 'assistant'],
                    'null'       => false,
                ],
                'client_message_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'message_text' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => false,
                ],
                'ai_content' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
                'intent_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'matched_action_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                ],
                'token_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'default'    => 0,
                    'null'       => false,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey(['conversation_id', 'id']);
            $this->forge->addKey('client_message_id');
            $this->forge->createTable('ai_conversation_messages', true, [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_conversation_messages', true);
        $this->forge->dropTable('ai_conversations', true);
    }
}
