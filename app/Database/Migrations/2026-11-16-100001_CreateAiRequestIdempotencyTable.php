<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiRequestIdempotencyTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ai_request_idempotency')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'client_message_id' => [
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
                'request_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'key_source' => [
                    'type'       => 'ENUM',
                    'constraint' => ['self', 'admin_fallback'],
                    'default'    => 'self',
                    'null'       => false,
                ],
                'quota_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'quota_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['NONE', 'RESERVED', 'COMMITTED', 'REFUNDED'],
                    'default'    => 'NONE',
                    'null'       => false,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['IN_FLIGHT', 'COMPLETED', 'FAILED_BEFORE_HTTP_DISPATCH', 'TIMEOUT', 'PROVIDER_ERROR', 'DELIVERY_UNKNOWN'],
                    'default'    => 'IN_FLIGHT',
                    'null'       => false,
                ],
                'response_payload' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
                'worker_token' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => true,
                ],
                'dispatch_started_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'lease_expires_at' => [
                    'type' => 'DATETIME',
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
            $this->forge->addUniqueKey('client_message_id');
            $this->forge->addKey(['user_id', 'created_at']);
            $this->forge->addKey('status');
            $this->forge->addKey('lease_expires_at');
            $this->forge->createTable('ai_request_idempotency', true, [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_request_idempotency', true);
    }
}
