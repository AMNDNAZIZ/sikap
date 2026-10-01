<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorMaterialPublishSystem extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. subject_materials: tambah kolom 'level'
        // ═══════════════════════════════════════════════════════════════
        if (!$this->db->fieldExists('level', 'subject_materials')) {
            $this->forge->addColumn('subject_materials', [
                'level' => [
                    'type'     => 'TINYINT',
                    'unsigned' => true,
                    'default'  => 0,
                    'comment'  => 'Level kelas (0=semua, 7=kelas 7, 8=kelas 8, dst). FK via classes.level',
                    'after'    => 'parent_id',
                ],
            ]);
        }
        try { $this->db->query("ALTER TABLE subject_materials ADD INDEX idx_level (level)"); } catch (\Exception $e) {}
        try { $this->db->query("ALTER TABLE subject_materials ADD INDEX idx_subject_level (subject_id, level)"); } catch (\Exception $e) {}

        // ═══════════════════════════════════════════════════════════════
        // 2. subject_material_publishes — tabel baru
        // ═══════════════════════════════════════════════════════════════
        if (!$this->db->tableExists('subject_material_publishes')) {
            $this->forge->addField([
                'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'material_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'comment'  => 'FK → subject_materials.id (sub materi)',
                ],
                'class_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'comment'  => 'FK → classes.id',
                ],
                'published_by' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'null'     => true,
                    'comment'  => 'FK → users.id',
                ],
                'published_at' => ['type' => 'DATETIME', 'null' => true],
                'is_active' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 1,
                    'comment'    => '1=aktif, 0=dicabut/unpublish',
                ],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addUniqueKey(['material_id', 'class_id']);
            $this->forge->addKey('material_id');
            $this->forge->addKey('class_id');
            $this->forge->addKey(['class_id', 'is_active']);
            $this->forge->createTable('subject_material_publishes', true);
        }

        // ═══════════════════════════════════════════════════════════════
        // 3. forum_threads.class_id — ubah menjadi nullable
        // ═══════════════════════════════════════════════════════════════
        try {
            $this->db->query(
                "ALTER TABLE forum_threads MODIFY COLUMN class_id INT UNSIGNED NULL DEFAULT NULL"
            );
        } catch (\Exception $e) {
            // Column may already be nullable
        }
    }

    public function down()
    {
        // Kembalikan forum_threads.class_id ke NOT NULL
        $this->db->query(
            "ALTER TABLE forum_threads MODIFY COLUMN class_id INT UNSIGNED NOT NULL"
        );

        $this->forge->dropTable('subject_material_publishes', true);

        $this->db->query("ALTER TABLE subject_materials DROP INDEX idx_level");
        $this->db->query("ALTER TABLE subject_materials DROP INDEX idx_subject_level");
        $this->forge->dropColumn('subject_materials', 'level');
    }
}
