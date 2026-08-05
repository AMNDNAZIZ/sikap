<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UbahTarifDefaultNullable extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('jenis_bayar', [
            'tarif_default' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true, // <- ini kuncinya
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('jenis_bayar', [
            'tarif_default' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => false,
                'default'    => 0,
            ],
        ]);
    }
}
