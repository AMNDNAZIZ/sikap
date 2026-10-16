<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Migration;

/**
 * @property BaseConnection $db
 */
class CreateEraporTpTables extends Migration
{
    public function up()
    {
        /** @var BaseConnection $db */
        $db = $this->db;

        // 1. Tabel erapor_tp_settings (Pengaturan TP terpilih tingkat level kelas)
        if (!$db->tableExists('erapor_tp_settings')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'academic_year_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'class_level' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '10',
                ],
                'subject_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'semester' => [
                    'type'       => 'INT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'tp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
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
            $this->forge->addKey(['academic_year_id', 'class_level', 'subject_id', 'semester'], false, false, 'idx_tp_setting_level');
            $this->forge->createTable('erapor_tp_settings', true);
        }

        // 2. Tabel erapor_student_tp (Pilihan TP Maksimal & Minimal per Siswa)
        if (!$db->tableExists('erapor_student_tp')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'academic_year_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'class_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'student_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'subject_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'semester' => [
                    'type'       => 'INT',
                    'constraint' => 1,
                    'default'    => 1,
                ],
                'tp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'type' => [
                    'type'       => 'ENUM',
                    'constraint' => ['maksimal', 'minimal'],
                    'default'    => 'maksimal',
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
            $this->forge->addKey(['academic_year_id', 'class_id', 'student_id', 'subject_id', 'semester'], false, false, 'idx_student_tp_query');
            $this->forge->createTable('erapor_student_tp', true);
        }
    }

    public function down()
    {
        /** @var BaseConnection $db */
        $db = $this->db;

        if ($db->tableExists('erapor_student_tp')) {
            $this->forge->dropTable('erapor_student_tp', true);
        }
        if ($db->tableExists('erapor_tp_settings')) {
            $this->forge->dropTable('erapor_tp_settings', true);
        }
    }
}
