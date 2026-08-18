<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToTeachers extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('is_active', 'teachers')) {
            $this->forge->addColumn('teachers', [
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'null'       => false,
                    // 'after' dihilangkan — ai_provider tidak dijamin ada di semua instalasi
                ],
            ]);

            // Set semua guru yang sudah ada menjadi aktif
            $this->db->query('UPDATE teachers SET is_active = 1 WHERE is_active IS NULL');
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('is_active', 'teachers')) {
            $this->forge->dropColumn('teachers', 'is_active');
        }
    }
}
