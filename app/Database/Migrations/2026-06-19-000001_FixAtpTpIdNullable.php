<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixAtpTpIdNullable extends Migration
{
    public function up()
    {
        // tp_id di alur_tujuan_pembelajaran harus nullable
        // karena pada struktur baru, ATP disimpan dulu baru TP-nya
        $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
            'tp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
            'tp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
    }
}
