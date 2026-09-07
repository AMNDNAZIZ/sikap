<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan kolom scheduled_at ke subject_material_publishes.
 *
 * Tujuan: jadwal publish materi PER KELAS. Materi hanya tampil ke siswa
 * ketika waktu sekarang >= scheduled_at.
 *
 *   scheduled_at = NULL  → tampil segera (perilaku lama, langsung publish)
 *   scheduled_at > NOW() → terjadwal, belum tampil ke siswa
 *   scheduled_at <= NOW()→ sudah tampil ke siswa
 *
 * Catatan: kolom published_at yang sudah ada TIDAK diubah maknanya
 * (mencatat kapan record publish dibuat). Jadwal tampil pakai kolom baru ini
 * agar tidak merusak tampilan "Dipublish {tanggal}".
 *
 * Materi yang masih terjadwal TETAP bisa dikaitkan dengan kuis, karena
 * pemilihan materi untuk kuis hanya mengecek subject_materials.is_published,
 * bukan tabel publish ini.
 */
class AddScheduledAtToMaterialPublishes extends Migration
{
    public function up()
    {
        if (! $this->db->fieldExists('scheduled_at', 'subject_material_publishes')) {
            $this->forge->addColumn('subject_material_publishes', [
                'scheduled_at' => [
                    'type'    => 'DATETIME',
                    'null'    => true,
                    'default' => null,
                    'comment' => 'Jadwal materi mulai tampil ke siswa. NULL = tampil segera.',
                    'after'   => 'published_at',
                ],
            ]);

            // Index gabungan agar filter visibility siswa cepat
            $this->db->query(
                "ALTER TABLE subject_material_publishes
                 ADD INDEX idx_class_active_sched (class_id, is_active, scheduled_at)"
            );
        }
    }

    public function down()
    {
        // Hapus index dulu (abaikan error bila tidak ada)
        try {
            $this->db->query(
                "ALTER TABLE subject_material_publishes DROP INDEX idx_class_active_sched"
            );
        } catch (\Throwable $e) {
            // ignore
        }

        if ($this->db->fieldExists('scheduled_at', 'subject_material_publishes')) {
            $this->forge->dropColumn('subject_material_publishes', 'scheduled_at');
        }
    }
}
