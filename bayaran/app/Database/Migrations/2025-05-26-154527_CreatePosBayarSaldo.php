<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePosBayarSaldo extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'           => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'pos_bayar_id' => ['type' => 'INT', 'unsigned' => true],
            'masuk'        => ['type' => 'INT', 'default' => 0],
            'keluar'       => ['type' => 'INT', 'default' => 0],
            'saldo'        => ['type' => 'INT', 'default' => 0],
            'updated_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('pos_bayar_id');
        $this->forge->addForeignKey('pos_bayar_id', 'pos_bayar', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('pos_bayar_saldo', true);
    }

    public function down()
    {
        $this->forge->dropTable('pos_bayar_saldo');
    }
}
