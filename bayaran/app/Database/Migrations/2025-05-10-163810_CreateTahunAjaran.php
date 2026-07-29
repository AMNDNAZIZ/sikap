<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTahunAjaran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'auto_increment' => true],
            'nama'      => ['type' => 'VARCHAR', 'constraint' => 20],
            'urutan'    => ['type' => 'INT', 'default' => 0],
            'aktif'     => ['type' => 'BOOLEAN', 'default' => false],
            'created_at'=> ['type' => 'DATETIME', 'null' => true],
            'updated_at'=> ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('tahun_ajaran', true);
    }

    public function down()
    {
        $this->forge->dropTable('tahun_ajaran');
    }
}

