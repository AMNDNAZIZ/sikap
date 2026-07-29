<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSetting extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'                => ['type' => 'INT', 'auto_increment' => true],
            'nama_sekolah'      => ['type' => 'VARCHAR', 'constraint' => 255],
            'alamat'            => ['type' => 'TEXT'],
            'no_hp'             => ['type' => 'VARCHAR', 'constraint' => 20],
            'kepala_sekolah'    => ['type' => 'VARCHAR', 'constraint' => 100],
            'logo'              => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'website'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'email'             => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'created_at'        => ['type' => 'DATETIME', 'null' => true],
            'updated_at'        => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('setting');
    }

    public function down()
    {
        $this->forge->dropTable('setting');
    }
}
