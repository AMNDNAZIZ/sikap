<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSubjectScores extends Migration
{
    public function up()
    {
        // Cek apakah tabel sudah ada
        if ($this->db->tableExists('subject_scores')) {
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
                'constraint' => ['ganjil', 'genap'],
                'null' => false,
            ],
            'formatif_score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'sumatif_score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'final_exam_score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
                'null' => true,
            ],
            'report_score' => [
                'type' => 'DECIMAL',
                'constraint' => '5,2',
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
        $this->forge->addKey('student_id');
        $this->forge->addKey('subject_id');
        $this->forge->addKey('year_id');
        
        // Cek apakah tabel parent ada sebelum menambah foreign key
        if ($this->db->tableExists('students')) {
            $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        }
        
        if ($this->db->tableExists('subjects')) {
            $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        }
        
        if ($this->db->tableExists('academic_years')) {
            $this->forge->addForeignKey('year_id', 'academic_years', 'id', 'CASCADE', 'CASCADE');
        }
        
        $this->forge->createTable('subject_scores');
    }

    public function down()
    {
        // Cek apakah tabel ada sebelum menghapus
        if ($this->db->tableExists('subject_scores')) {
            $this->forge->dropTable('subject_scores');
        }
    }
}
