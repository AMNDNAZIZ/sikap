<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Memastikan kolom announcements.academic_year_id benar-benar ADA.
 *
 * Migration lama (2026-09-02-000001_AddAcademicYearToAnnouncements) di sebagian
 * database sempat tercatat "sudah jalan" namun kolomnya gagal terbuat (akibat
 * bentrok "Duplicate column" dari migration duplikat). Karena CI4 tidak mengulang
 * migration yang sudah tercatat, kolom tak pernah dibuat → query dashboard/pengumuman
 * siswa error "Unknown column 'a.academic_year_id'".
 *
 * Migration ini (versi lebih baru) memastikan kolom ada secara idempotent.
 */
class EnsureAnnouncementAcademicYear extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('academic_year_id', 'announcements')) {
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

            // Backfill: pengumuman lama yang terikat kelas dikaitkan ke tahun ajaran aktif
            try {
                $this->db->query(
                    'UPDATE announcements a
                     JOIN academic_years ay ON ay.is_active = 1
                     SET a.academic_year_id = ay.id
                     WHERE a.academic_year_id IS NULL AND a.class_id IS NOT NULL'
                );
            } catch (\Throwable $e) {
                // abaikan bila backfill gagal (tidak kritikal)
            }
        }
    }

    public function down()
    {
        // Tidak menghapus kolom pada down() untuk mencegah kehilangan data;
        // kolom dikelola oleh migration aslinya.
    }
}
