<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKasbon extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'tanggal'    => ['type' => 'DATE'],
            'nama'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'jumlah'     => ['type' => 'INT'],
            'dibayar'    => ['type' => 'INT', 'default' => 0],
            'status'     => ['type' => 'ENUM', 'constraint' => ['belum', 'lunas'], 'default' => 'belum'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('kasbon');
    }

    public function down()
    {
        $this->forge->dropTable('kasbon');
    }
}
