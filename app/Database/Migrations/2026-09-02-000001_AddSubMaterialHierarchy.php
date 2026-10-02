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
        if (!$this->db->fieldExists('parent_id', 'subject_materials')) {
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
            try { $this->db->query('ALTER TABLE subject_materials ADD INDEX idx_parent_id (parent_id)'); } catch (\Exception $e) {}
        } else {
            try { $this->db->query('ALTER TABLE subject_materials ADD INDEX idx_parent_id (parent_id)'); } catch (\Exception $e) {}
        }

        // ── 2. quiz_configs: tambah material_id ───────────────────────────
        if (!$this->db->fieldExists('material_id', 'quiz_configs')) {
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
        }
        try { $this->db->query('ALTER TABLE quiz_configs ADD INDEX idx_material_id (material_id)'); } catch (\Exception $e) {}

        // ── 3. forum_threads: tambah kolom system_thread ──────────────────
        if (!$this->db->fieldExists('is_system', 'forum_threads')) {
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
