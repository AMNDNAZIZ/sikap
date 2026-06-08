<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddElemenToCpAndTp extends Migration
{
    public function up()
    {
        $this->forge->addColumn('cp_master', [
            'elemen' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'after'      => 'mapel_master_id',
                'null'       => true,
            ],
        ]);

        $this->forge->addColumn('tujuan_pembelajaran', [
            'elemen' => [
                'type'       => 'VARCHAR',
                'constraint' => '100',
                'after'      => 'cp_master_id',
                'null'       => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('tujuan_pembelajaran', 'elemen');
        $this->forge->dropColumn('cp_master', 'elemen');
    }
}
