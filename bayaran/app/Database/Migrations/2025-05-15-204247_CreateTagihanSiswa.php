<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTagihanSiswa extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'siswa_id'        => ['type' => 'INT'],
            'jenis_bayar_id'  => ['type' => 'INT'],
            'tahun_ajaran_id' => ['type' => 'INT'],
            'tipe'            => ['type' => 'ENUM', 'constraint' => ['bulanan', 'bebas']],
            'bulan'           => ['type' => 'TINYINT', 'null' => true], // hanya untuk bulanan (1-12)
            'jumlah'          => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'terbayar'        => ['type' => 'DECIMAL', 'constraint' => '10,2', 'default' => 0],
            'status'          => ['type' => 'ENUM', 'constraint' => ['belum', 'lunas'], 'default' => 'belum'],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tagihan_siswa');
    }

    public function down()
    {
        $this->forge->dropTable('tagihan_siswa');
    }
}
