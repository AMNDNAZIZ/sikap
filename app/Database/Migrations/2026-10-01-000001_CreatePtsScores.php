<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tabel nilai PTS (Penilaian Tengah Semester).
 * SENGAJA TERPISAH dari summative_scores agar PTS TIDAK ikut dalam
 * perhitungan nilai rapor akhir (formatif/sumatif/final).
 */
class CreatePtsScores extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('pts_scores')) {
            return; // Skip jika sudah ada
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'student_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'subject_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'year_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'semester' => [
                'type' => 'ENUM',
                'constraint' => ['1', '2'],
                'null' => false,
            ],
            'score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'source' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
                'comment' => 'manual | cbt | cbt_convert',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
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
        // Unik: satu nilai PTS per siswa+mapel+tahun+semester
        $this->forge->addUniqueKey(['student_id', 'subject_id', 'year_id', 'semester']);
        $this->forge->addKey('subject_id');
        $this->forge->addKey('year_id');

        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        if ($this->db->tableExists('subjects')) {
            $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        }
        if ($this->db->tableExists('academic_years')) {
            $this->forge->addForeignKey('year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        }

        $this->forge->createTable('pts_scores');
    }

    public function down()
    {
        if ($this->db->tableExists('pts_scores')) {
            $this->forge->dropTable('pts_scores');
        }
    }
}
