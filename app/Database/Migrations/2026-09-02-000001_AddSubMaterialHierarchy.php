<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSubMaterialHierarchy extends Migration
{
    public function up()
    {
        // ── 1. subject_materials: tambah parent_id ────────────────────────
        // NULL  = Materi induk
        // !NULL = Sub Materi (parent_id merujuk ke id Materi induk)
        $this->forge->addColumn('subject_materials', [
            'parent_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'comment'  => 'NULL = Materi, NOT NULL = Sub Materi (FK → subject_materials.id)',
                'after'    => 'id',
            ],
        ]);

        // Index untuk query anak-anak sebuah materi
        $this->db->query(
            'ALTER TABLE subject_materials ADD INDEX idx_parent_id (parent_id)'
        );

        // ── 2. quiz_configs: tambah material_id ───────────────────────────
        // Menghubungkan kuis ke Sub Materi tertentu.
        // NULL = kuis hanya terkait mapel (via bank_id), tanpa pin ke sub materi
        $this->forge->addColumn('quiz_configs', [
            'material_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'default'  => null,
                'comment'  => 'FK → subject_materials.id (pin kuis ke sub materi tertentu)',
                'after'    => 'bank_id',
            ],
        ]);

        $this->db->query(
            'ALTER TABLE quiz_configs ADD INDEX idx_material_id (material_id)'
        );

        // ── 3. forum_threads: tambah kolom system_thread ──────────────────
        // Menandai thread yang dibuat otomatis oleh sistem saat sub materi dipublish.
        // Thread sistem tidak bisa dihapus siswa, hanya guru/admin.
        $this->forge->addColumn('forum_threads', [
            'is_system' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '1 = dibuat otomatis sistem saat sub materi publish',
                'after'   => 'is_answered',
            ],
        ]);
    }

    public function down()
    {
        // Hapus index dulu sebelum kolom
        $this->db->query('ALTER TABLE subject_materials DROP INDEX idx_parent_id');
        $this->db->query('ALTER TABLE quiz_configs DROP INDEX idx_material_id');
        $this->forge->dropColumn('subject_materials', 'parent_id');
        $this->forge->dropColumn('quiz_configs', 'material_id');
        $this->forge->dropColumn('forum_threads', 'is_system');
    }
}
