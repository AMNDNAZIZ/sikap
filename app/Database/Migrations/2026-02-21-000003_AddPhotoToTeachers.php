<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPhotoToTeachers extends Migration
{
    public function up()
    {
        // Cek apakah kolom sudah ada sebelum menambahkan
        if (!$this->db->fieldExists('photo', 'teachers')) {
            $fields = [
                'photo' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'certification_year'
                ]
            ];
            $this->forge->addColumn('teachers', $fields);
        }
    }

    public function down()
    {
        // Cek apakah kolom ada sebelum menghapus
        if ($this->db->fieldExists('photo', 'teachers')) {
            $this->forge->dropColumn('teachers', 'photo');
        }
    }
}
