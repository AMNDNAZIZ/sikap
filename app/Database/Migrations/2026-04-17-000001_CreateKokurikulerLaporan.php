<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKokurikulerLaporan extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'document_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'refleksi' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Refleksi pelaksanaan kegiatan'],
            'rekomendasi' => ['type' => 'TEXT', 'null' => true, 'comment' => 'Rekomendasi perbaikan'],
            'created_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('document_id');
        $this->forge->addForeignKey('document_id', 'kokurikuler_documents', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('kokurikuler_laporan');
    }

    public function down()
    {
        $this->forge->dropTable('kokurikuler_laporan');
    }
}
