<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAdministrasiGuruTables extends Migration
{
    public function up()
    {
        // 1. Update subjects table
        $this->forge->addColumn('subjects', [
            'mapel_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'id'
            ],
        ]);

        // 2. Create tujuan_pembelajaran table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'cp_master_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'kode_tp' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
            ],
            'deskripsi' => [
                'type' => 'TEXT',
            ],
            'fase' => [
                'type'       => 'VARCHAR',
                'constraint' => '5',
            ],
            'kelas' => [
                'type'       => 'VARCHAR',
                'constraint' => '10',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('tujuan_pembelajaran');

        // 3. Create alur_tujuan_pembelajaran table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'urutan' => [
                'type'       => 'INT',
                'constraint' => 11,
            ],
            'semester' => [
                'type'       => 'INT',
                'constraint' => 1,
            ],
            'alokasi_waktu' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('alur_tujuan_pembelajaran');

        // 4. Create dokumen_administrasi table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'academic_year_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'tipe_dokumen' => [
                'type'       => 'VARCHAR',
                'constraint' => '50', // PROTA, PROSEM, MODUL_AJAR
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
            ],
            'content_json' => [
                'type' => 'LONGTEXT', // Untuk simpan struktur dinamis jika perlu
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'draft',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('dokumen_administrasi');
    }

    public function down()
    {
        $this->forge->dropTable('dokumen_administrasi');
        $this->forge->dropTable('alur_tujuan_pembelajaran');
        $this->forge->dropTable('tujuan_pembelajaran');
        $this->forge->dropColumn('subjects', 'mapel_master_id');
    }
}
