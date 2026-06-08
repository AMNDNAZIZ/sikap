<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKokurikulerPelaksanaan extends Migration
{
    public function up()
    {
        // Tabel untuk tracking pelaksanaan per kegiatan
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
                'comment' => 'ID dokumen rencana kokurikuler',
            ],
            'pertemuan_ke' => [
                'type' => 'INT',
                'constraint' => 11,
                'comment' => 'Pertemuan ke berapa (1, 2, 3, dst)',
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['terlaksana', 'tidak_terlaksana', 'belum_dilaksanakan'],
                'default' => 'belum_dilaksanakan',
            ],
            'tanggal_pelaksanaan' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Tanggal pelaksanaan (jika terlaksana)',
            ],
            'catatan_pelaksanaan' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Catatan pelaksanaan (jika terlaksana)',
            ],
            'dokumentasi' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Path file dokumentasi (foto/dokumen), bisa multiple (JSON array)',
            ],
            'alasan_tidak_terlaksana' => [
                'type' => 'TEXT',
                'null' => true,
                'comment' => 'Alasan jika tidak terlaksana',
            ],
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('document_id', 'kokurikuler_documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kokurikuler_pelaksanaan', true);
    }

    public function down()
    {
        $this->forge->dropTable('kokurikuler_pelaksanaan', true);
    }
}
