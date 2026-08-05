<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKasbonBayar extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'INT', 'auto_increment' => true],
            'kasbon_id'  => ['type' => 'INT'],
            'tanggal'    => ['type' => 'DATE'],
            'jumlah'     => ['type' => 'INT'],
            'keterangan' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('kasbon_bayar');
    }

    public function down()
    {
        $this->forge->dropTable('kasbon_bayar');
    }
}
