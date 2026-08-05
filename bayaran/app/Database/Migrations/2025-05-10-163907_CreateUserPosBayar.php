<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateUserPosBayar extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'user_id' => ['type' => 'INT'],
            'pos_bayar_id' => ['type' => 'INT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('user_pos_bayar', true);
    }


    public function down()
    {
        //
    }
}
