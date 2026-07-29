<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePembayaran extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'auto_increment' => true],
            'tagihan_id'     => ['type' => 'INT'],
            'tanggal'        => ['type' => 'DATE'],
            'jumlah_bayar'   => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'cara_bayar'     => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'keterangan'     => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
            'updated_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pembayaran', true);
    }

    public function down()
    {
        $this->forge->dropTable('pembayaran');
    }
}
