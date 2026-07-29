<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKelasSiswa extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'              => ['type' => 'INT', 'auto_increment' => true],
            'siswa_id'        => ['type' => 'INT'],
            'kelas_id'        => ['type' => 'INT'],
            'tahun_ajaran_id' => ['type' => 'INT'],
            'status'          => ['type' => 'ENUM', 'constraint' => ['aktif', 'lulus', 'pindah', 'do'], 'default' => 'aktif'],
            'tanggal_masuk'   => ['type' => 'DATE', 'null' => true],
            'tanggal_keluar'  => ['type' => 'DATE', 'null' => true],
            'created_at'      => ['type' => 'DATETIME', 'null' => true],
            'updated_at'      => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('kelas_siswa', true);
    }

    public function down()
    {
        $this->forge->dropTable('kelas_siswa');
    }
}
