<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToUsers extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('is_active', 'users')) {
            $this->forge->addColumn('users', [
                'is_active' => [
                    'type'    => 'TINYINT',
                    'constraint' => 1,
                    'null'    => false,
                    'default' => 1,
                    'after'   => 'must_change_password',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('is_active', 'users')) {
            $this->forge->dropColumn('users', 'is_active');
        }
    }
}
