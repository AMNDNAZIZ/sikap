<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTransaksiPos extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'            => ['type' => 'INT', 'auto_increment' => true],
            'pos_bayar_id'  => ['type' => 'INT', 'unsigned' => true],
            'tanggal'       => ['type' => 'DATE'],
            'tipe'          => ['type' => 'ENUM', 'constraint' => ['masuk', 'keluar']],
            'jumlah'        => ['type' => 'INT'],
            'keterangan'    => ['type' => 'TEXT', 'null' => true],
            'created_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
            'updated_at'    => ['type' => 'DATETIME', 'null' => true, 'default' => null],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('pos_bayar_id', 'pos_bayar', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('transaksi_pos', true);
    }

    public function down()
    {
        $this->forge->dropTable('transaksi_pos');
    }
}
