<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAcademicYearToAnnouncements extends Migration
{
    public function up()
    {
        // Tambah kolom academic_year_id ke tabel announcements
        // Pengumuman ke kelas tertentu harus dikaitkan ke tahun ajaran
        // agar tidak bocor ke tahun ajaran berikutnya
        $this->forge->addColumn('announcements', [
            'academic_year_id' => [
                'type'       => 'INT',
                'constraint' => 10,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
                'after'      => 'class_id',
            ],
        ]);

        // Backfill: pengumuman lama yang punya class_id dikaitkan ke tahun ajaran aktif
        $this->db->query(
            'UPDATE announcements a
             JOIN academic_years ay ON ay.is_active = 1
             SET a.academic_year_id = ay.id
             WHERE a.academic_year_id IS NULL AND a.class_id IS NOT NULL'
        );
    }

    public function down()
    {
        $this->forge->dropColumn('announcements', 'academic_year_id');
    }
}
