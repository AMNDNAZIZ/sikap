<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCbtTestTpMappings extends Migration
{
    public function up()
    {
        // 1. Tabel konfigurasi asesmen TP per ujian (Formatif / Sumatif & pilihan ATP/TP)
        if (!$this->db->tableExists('cbt_test_tp_configs')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'test_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'assessment_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'formatif', // 'formatif' atau 'sumatif'
                ],
                'selected_atp_ids' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'selected_tp_ids' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
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
            $this->forge->addKey('test_id');
            $this->forge->createTable('cbt_test_tp_configs', true);
        }

        // 2. Tabel pemetaan butir soal ke Tujuan Pembelajaran (AI-assisted / Manual)
        if (!$this->db->tableExists('cbt_test_tp_mappings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'test_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'question_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'tp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'confidence' => [
                    'type'       => 'DECIMAL',
                    'constraint' => '5,2',
                    'null'       => true,
                ],
                'reasoning' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                ],
                'mapping_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 30,
                    'default'    => 'ai', // 'ai' atau 'manual'
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
            $this->forge->addKey('test_id');
            $this->forge->addKey('question_id');
            $this->forge->addKey('tp_id');
            $this->forge->addKey(['test_id', 'question_id']);
            $this->forge->createTable('cbt_test_tp_mappings', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('cbt_test_tp_mappings', true);
        $this->forge->dropTable('cbt_test_tp_configs', true);
    }
}

