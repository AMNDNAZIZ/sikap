<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddElemenToCpAndTp extends Migration
{
    public function up()
    {
        // Cek dan tambahkan kolom 'elemen' ke cp_master jika belum ada
        $cpFields = $this->db->getFieldNames('cp_master');
        if (!in_array('elemen', $cpFields)) {
            $this->forge->addColumn('cp_master', [
                'elemen' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'after'      => 'mapel_master_id',
                    'null'       => true,
                ],
            ]);
        }

        // Cek dan tambahkan kolom 'elemen' ke tujuan_pembelajaran jika belum ada
        $tpFields = $this->db->getFieldNames('tujuan_pembelajaran');
        if (!in_array('elemen', $tpFields)) {
            $this->forge->addColumn('tujuan_pembelajaran', [
                'elemen' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'after'      => 'cp_master_id',
                    'null'       => true,
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('tujuan_pembelajaran', 'elemen');
        $this->forge->dropColumn('cp_master', 'elemen');
    }
}
