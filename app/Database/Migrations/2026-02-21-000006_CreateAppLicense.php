<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAppLicense extends Migration
{
    public function up()
    {
        // Cek apakah tabel sudah ada
        if ($this->db->tableExists('app_license')) {
            return; // Skip jika sudah ada
        }

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'license_key' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'domain' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'machine_id' => [
                'type' => 'VARCHAR',
                'constraint' => 500,
                'null' => true,
            ],
            'status' => [
                'type' => 'ENUM',
                'constraint' => ['active', 'inactive', 'expired'],
                'default' => 'inactive',
            ],
            'last_check' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expires_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'hash' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
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
        $this->forge->createTable('app_license');
    }

    public function down()
    {
        // Cek apakah tabel ada sebelum menghapus
        if ($this->db->tableExists('app_license')) {
            $this->forge->dropTable('app_license');
        }
    }
}
