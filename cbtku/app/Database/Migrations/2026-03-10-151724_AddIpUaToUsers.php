<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIpUaToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'last_ip' => [
                'type' => 'VARCHAR',
                'constraint' => '45',
                'null' => true,
                'after' => 'active_session_id',
            ],
            'user_agent' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
                'null' => true,
                'after' => 'last_ip',
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', ['last_ip', 'user_agent']);
    }
}
