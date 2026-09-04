<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMaterialProgress extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // Siswa yang membaca
            'student_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            // Materi yang dibaca
            'material_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            // Status progres
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['unread', 'in_progress', 'completed'],
                'default'    => 'in_progress',
            ],
            // Pertama kali dibuka
            'opened_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Saat siswa klik "Tandai Selesai"
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            // Berapa kali siswa membuka materi ini
            'view_count' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 1,
            ],
            // Terakhir diakses (untuk sorting "recently viewed")
            'last_accessed_at' => [
                'type' => 'DATETIME',
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

        $this->forge->addPrimaryKey('id');
        // Satu siswa hanya boleh punya satu record progress per materi
        $this->forge->addUniqueKey(['student_id', 'material_id']);
        $this->forge->addKey('student_id');
        $this->forge->addKey('material_id');
        $this->forge->addKey(['student_id', 'status']);

        $this->forge->createTable('material_progress', true);
    }

    public function down()
    {
        $this->forge->dropTable('material_progress', true);
    }
}
