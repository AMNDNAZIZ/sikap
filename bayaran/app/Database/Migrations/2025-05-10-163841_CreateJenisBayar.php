<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJenisBayar extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'auto_increment' => true],
            'pos_bayar_id' => ['type' => 'INT'],
            'tahun_ajaran_id' => ['type' => 'INT'],
            'nama_jenis_bayar' => ['type' => 'VARCHAR', 'constraint' => 100],
            'tipe' => ['type' => 'ENUM', 'constraint' => ['bulanan', 'bebas']],
            'tarif_default' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'wajib' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'update_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('jenis_bayar', true);
    }


    public function down()
    {
        //
    }
}
