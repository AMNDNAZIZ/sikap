<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCiSessions extends Migration
{
    public function up()
    {
        // Cek apakah tabel sudah ada
        if ($this->db->tableExists('ci_sessions')) {
            return; // Skip jika sudah ada
        }

        $this->forge->addField([
            'id' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
            ],
            'ip_address' => [
                'type' => 'VARCHAR',
                'constraint' => 45,
            ],
            'timestamp' => [
                'type' => 'TIMESTAMP',
                'default' => new \CodeIgniter\Database\RawSql('CURRENT_TIMESTAMP'),
            ],
            'data' => [
                'type' => 'BLOB',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('timestamp');
        $this->forge->createTable('ci_sessions');
    }

    public function down()
    {
        // Cek apakah tabel ada sebelum menghapus
        if ($this->db->tableExists('ci_sessions')) {
            $this->forge->dropTable('ci_sessions');
        }
    }
}
