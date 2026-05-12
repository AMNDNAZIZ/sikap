<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKokurikulerRubrikAndPenilaian extends Migration
{
    public function up()
    {
        // Tabel Rubrik Penilaian (Auto-generated dari perencanaan)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'document_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'dimensi_profil' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'comment' => 'Nama dimensi profil pelajar pancasila',
            ],
            'aspek_dinilai' => [
                'type' => 'TEXT',
                'comment' => 'Aspek yang dinilai (dari TP atau 7KAIH)',
            ],
            'urutan' => [
                'type' => 'INT',
                'constraint' => 3,
                'default' => 0,
                'comment' => 'Urutan tampilan rubrik',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('document_id');
        $this->forge->addForeignKey('document_id', 'kokurikuler_documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kokurikuler_rubrik');

        // Tabel Penilaian Per Siswa
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'document_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'student_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'penilaian_detail' => [
                'type' => 'JSON',
                'comment' => 'Array of {rubrik_id, capaian}',
            ],
            'catatan_tambahan' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Catatan khusus/anekdot untuk siswa',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
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
        $this->forge->addKey('document_id');
        $this->forge->addKey('student_id');
        $this->forge->addForeignKey('document_id', 'kokurikuler_documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('student_id', 'students', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kokurikuler_penilaian');
    }

    public function down()
    {
        $this->forge->dropTable('kokurikuler_penilaian', true);
        $this->forge->dropTable('kokurikuler_rubrik', true);
    }
}
