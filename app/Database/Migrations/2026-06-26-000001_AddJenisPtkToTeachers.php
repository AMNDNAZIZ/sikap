<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddJenisPtkToTeachers extends Migration
{
    public function up()
    {
        // Tambah kolom jenis_ptk jika belum ada
        if (!$this->db->fieldExists('jenis_ptk', 'teachers')) {
            $fields = [
                'jenis_ptk' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'default'    => null,
                    'comment'    => 'Jenis PTK dari Dapodik (Guru Kelas, Tendik, dll)',
                    'after'      => 'employment_status',
                ],
            ];
            $this->forge->addColumn('teachers', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('jenis_ptk', 'teachers')) {
            $this->forge->dropColumn('teachers', 'jenis_ptk');
        }
    }
}
