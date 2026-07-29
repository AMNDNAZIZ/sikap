<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePengumuman extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'judul'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'isi'       => ['type' => 'TEXT'],
            'aktif'     => ['type' => 'BOOLEAN', 'default' => true],
            'created_at'=> ['type' => 'DATETIME', 'null' => true],
            'updated_at'=> ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('pengumuman');
    }

    public function down()
    {
        $this->forge->dropTable('pengumuman');
    }
}
