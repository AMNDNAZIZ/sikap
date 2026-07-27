<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLingkupMateriAndAlurDeskripsi extends Migration
{
    public function up()
    {
        // Defensif: cek kolom sebelum menambahkan
        if (!$this->db->fieldExists('lingkup_materi', 'tujuan_pembelajaran')) {
            $this->forge->addColumn('tujuan_pembelajaran', [
                'lingkup_materi' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'elemen'
                ],
            ]);
        }

        if (!$this->db->fieldExists('alur_tujuan', 'alur_tujuan_pembelajaran')) {
            $this->forge->addColumn('alur_tujuan_pembelajaran', [
                'alur_tujuan' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'tp_id'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('alur_tujuan', 'alur_tujuan_pembelajaran')) {
            $this->forge->dropColumn('alur_tujuan_pembelajaran', 'alur_tujuan');
        }
        if ($this->db->fieldExists('lingkup_materi', 'tujuan_pembelajaran')) {
            $this->forge->dropColumn('tujuan_pembelajaran', 'lingkup_materi');
        }
    }
}
