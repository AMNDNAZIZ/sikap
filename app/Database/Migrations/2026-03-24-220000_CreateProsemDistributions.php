<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProsemDistributions extends Migration
{
    public function up()
    {
        // Create prosem_distributions table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'atp_id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
            ],
            'month' => [
                'type'           => 'TINYINT',
                'constraint'     => 2,
            ],
            'week' => [
                'type'           => 'TINYINT',
                'constraint'     => 1,
            ],
            'jp' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'default'        => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('atp_id', 'alur_tujuan_pembelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('prosem_distributions');
    }

    public function down()
    {
        $this->forge->dropTable('prosem_distributions');
    }
}
