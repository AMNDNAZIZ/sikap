<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeTugasClassIdNullable extends Migration
{
    public function up()
    {
        // Ubah kolom class_id pada tabel tugas menjadi nullable
        // agar tugas yang terikat pada segmen materi tidak memerlukan class_id statis tunggal
        $this->forge->modifyColumn('tugas', [
            'class_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('tugas', [
            'class_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);
    }
}

