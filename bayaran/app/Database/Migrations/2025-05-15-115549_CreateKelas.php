<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKelas extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'nama_kelas' => ['type' => 'VARCHAR', 'constraint' => 100],
            'keterangan' => ['type' => 'TEXT', 'null' => true],
            'aktif' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('kelas', true);
    }

    public function down()
    {
        $this->forge->dropTable('kelas');
    }
}
