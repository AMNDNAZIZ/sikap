<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRaporSemesterTables extends Migration
{
    public function up()
    {
        // 1. Tambah kolom competency_desc di tabel grades jika belum ada
        if (!$this->db->fieldExists('competency_desc', 'grades')) {
            $this->forge->addColumn('grades', [
                'competency_desc' => [
                    'type'       => 'TEXT',
                    'null'       => true,
                    'after'      => 'erapor_score',
                ],
            ]);
        }

        // 2. Buat tabel rapor_notes (Catatan Wali Kelas & Status Kenaikan Kelas)
        if (!$this->db->tableExists('rapor_notes')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'student_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'academic_year_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                ],
                'class_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'teacher_note' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'promotion_status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['naik', 'tinggal', 'lulus'],
                    'null'       => true,
                ],
                'promotion_class_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
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
            $this->forge->addKey(['student_id', 'academic_year_id', 'semester']);
            $this->forge->addKey('class_id');
            $this->forge->createTable('rapor_notes', true);
        }

        // 3. Buat tabel rapor_configs (Pengaturan Titimangsa & Tanggal Rapor)
        if (!$this->db->tableExists('rapor_configs')) {
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
                'semester' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 10,
                ],
                'report_date' => [
                    'type' => 'DATE',
                    'null' => true,
                ],
                'place_city' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
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
            $this->forge->addUniqueKey(['academic_year_id', 'semester']);
            $this->forge->createTable('rapor_configs', true);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('rapor_configs')) {
            $this->forge->dropTable('rapor_configs', true);
        }
        if ($this->db->tableExists('rapor_notes')) {
            $this->forge->dropTable('rapor_notes', true);
        }
        if ($this->db->fieldExists('competency_desc', 'grades')) {
            $this->forge->dropColumn('grades', 'competency_desc');
        }
    }
}
