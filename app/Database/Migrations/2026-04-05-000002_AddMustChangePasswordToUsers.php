<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Migration ini merupakan duplikat dari 2026-02-21-000001_AddMustChangePasswordToUsers.
 * Dibuat untuk kompatibilitas dengan server yang memiliki entri ini di tabel migrations.
 * Semua operasi bersifat idempotent (cek dulu sebelum alter).
 */
class AddMustChangePasswordToUsers extends Migration
{
    public function up()
    {
        // Kolom ini seharusnya sudah ada dari migration 2026-02-21-000001.
        // Guard ketat: skip bila sudah ada.
        if (!$this->db->fieldExists('must_change_password', 'users')) {
            $this->forge->addColumn('users', [
                'must_change_password' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                    'after'      => 'related_type',
                ],
            ]);
        }

        if (!$this->db->fieldExists('password_changed_at', 'users')) {
            $this->forge->addColumn('users', [
                'password_changed_at' => [
                    'type'  => 'DATETIME',
                    'null'  => true,
                    'after' => 'must_change_password',
                ],
            ]);
        }
    }

    public function down()
    {
        // Tidak menghapus kolom di down() karena migration original (2026-02-21-000001)
        // yang bertanggung jawab atas lifecycle kolom ini.
    }
}
