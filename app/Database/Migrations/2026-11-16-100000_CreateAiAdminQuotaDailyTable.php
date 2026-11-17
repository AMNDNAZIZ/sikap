<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiAdminQuotaDailyTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ai_admin_quota_daily')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'quota_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'used_count' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'null'       => false,
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
            $this->forge->addUniqueKey('quota_date');
            $this->forge->createTable('ai_admin_quota_daily', true, [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_admin_quota_daily', true);
    }
}
