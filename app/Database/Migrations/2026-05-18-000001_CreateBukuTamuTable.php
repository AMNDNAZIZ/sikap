<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBukuTamuTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id'             => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'guest_type'     => ['type' => 'ENUM', 'constraint' => ['umum', 'dinas'], 'null' => false],
            'nama'           => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
            'no_hp'          => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true],
            'nip'            => ['type' => 'VARCHAR', 'constraint' => 30, 'null' => true],
            'instansi'       => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'alamat'         => ['type' => 'TEXT', 'null' => true],
            'is_ortu_siswa'  => ['type' => 'TINYINT', 'constraint' => 1, 'default' => 0],
            'tujuan'         => ['type' => 'TEXT', 'null' => false],
            'bertemu_dengan' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true],
            'ip_address'     => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at'     => ['type' => 'DATETIME', 'null' => true],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('guest_type');
        $this->forge->addKey('created_at');
        $this->forge->createTable('buku_tamu');
    }

    public function down(): void
    {
        $this->forge->dropTable('buku_tamu');
    }
}
