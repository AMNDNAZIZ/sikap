<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKokurikulerTables extends Migration
{
    public function up()
    {
        // Tabel kokurikuler_documents (Dokumen Rencana)
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'year_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'fase' => [
                'type' => 'VARCHAR',
                'constraint' => 10,
                'comment' => 'A, B, C, D, E, F',
            ],
            'level_kelas' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'comment' => 'Contoh: 1,2,3 atau 7,8,9',
            ],
            'jumlah_pertemuan' => [
                'type' => 'INT',
                'constraint' => 11,
            ],
            'dimensi_profil' => [
                'type' => 'TEXT',
                'comment' => 'JSON array of selected dimensions (min 1, max 3)',
            ],
            'tema' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'bentuk_kegiatan' => [
                'type' => 'ENUM',
                'constraint' => ['lintas_disiplin', '7kaih', 'lainnya'],
            ],
            'kegiatan_detail' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON for lintas_disiplin or 7kaih, text for lainnya',
            ],
            'tujuan_pembelajaran' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'AI Generated',
            ],
            'praktik_pedagogis' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'AI Generated',
            ],
            'lingkungan_belajar' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'AI Generated',
            ],
            'kemitraan' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array',
            ],
            'teknologi_digital' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'JSON array',
            ],
            'kegiatan_kokurikuler' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'AI Generated - rincian per pertemuan',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['draft', 'completed'],
                'default' => 'draft',
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
        $this->forge->addKey('year_id');
        $this->forge->addKey('created_by');
        $this->forge->createTable('kokurikuler_documents');
    }

    public function down()
    {
        $this->forge->dropTable('kokurikuler_documents');
    }
}
