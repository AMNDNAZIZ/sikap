<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateExamSchedules extends Migration
{
    public function up()
    {
        // Cek apakah tabel sudah ada
        if ($this->db->tableExists('exam_schedules')) {
            return; // Skip jika sudah ada
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'subject_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'class_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'exam_date' => [
                'type' => 'DATE',
                'null' => false,
            ],
            'start_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'end_time' => [
                'type' => 'TIME',
                'null' => true,
            ],
            'description' => [
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
        $this->forge->addKey('subject_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('exam_date');
        
        // Cek apakah tabel parent ada sebelum menambah foreign key
        if ($this->db->tableExists('subjects')) {
            $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'CASCADE', 'CASCADE');
        }
        
        if ($this->db->tableExists('classes')) {
            $this->forge->addForeignKey('class_id', 'classes', 'id', 'CASCADE', 'CASCADE');
        }
        
        $this->forge->createTable('exam_schedules');
    }

    public function down()
    {
        // Cek apakah tabel ada sebelum menghapus
        if ($this->db->tableExists('exam_schedules')) {
            $this->forge->dropTable('exam_schedules');
        }
    }
}
