<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCaraBayarToTagihanSiswa extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tagihan_siswa', [
            'cara_bayar' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true,
                'after'      => 'status' // letakkan setelah kolom status
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tagihan_siswa', 'cara_bayar');
    }
}
