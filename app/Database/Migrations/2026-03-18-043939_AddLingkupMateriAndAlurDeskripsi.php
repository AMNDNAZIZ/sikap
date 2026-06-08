<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLingkupMateriAndAlurDeskripsi extends Migration
{
    public function up()
    {
        $this->forge->addColumn('tujuan_pembelajaran', [
            'lingkup_materi' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'elemen'
            ],
        ]);

        $this->forge->addColumn('alur_tujuan_pembelajaran', [
            'alur_tujuan' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'tp_id'
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('alur_tujuan_pembelajaran', 'alur_tujuan');
        $this->forge->dropColumn('tujuan_pembelajaran', 'lingkup_materi');
    }
}
