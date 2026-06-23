<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAtpElemenTable extends Migration
{
    public function up()
    {
        // 1. Buat tabel atp_elemen
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'atp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cp_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'urutan' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 1,
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
            'updated_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('atp_id');
        $this->forge->addKey('cp_master_id');
        $this->forge->createTable('atp_elemen', true);

        // 2. Tambah kolom atp_elemen_id ke tujuan_pembelajaran (setelah atp_id)
        $fields = [
            'atp_elemen_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'atp_id',
            ],
        ];
        $this->forge->addColumn('tujuan_pembelajaran', $fields);

        // 3. Jadikan cp_master_id nullable di alur_tujuan_pembelajaran
        $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
            'cp_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => false,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down()
    {
        // Hapus kolom atp_elemen_id dari tujuan_pembelajaran
        $this->forge->dropColumn('tujuan_pembelajaran', 'atp_elemen_id');

        // Drop tabel atp_elemen
        $this->forge->dropTable('atp_elemen', true);

        // Kembalikan cp_master_id ke NOT NULL
        $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
            'cp_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => false,
                'null'       => false,
            ],
        ]);
    }
}
