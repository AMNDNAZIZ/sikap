<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RefactorMaterialPublishSystem extends Migration
{
    public function up()
    {
        // ═══════════════════════════════════════════════════════════════
        // 1. subject_materials: tambah kolom 'level'
        //    Materi dikaitkan ke level kelas (int), bukan class_id.
        //    Contoh: level=7 → untuk kelas 7A, 7B, 7C sekaligus.
        //    level=0 → berlaku untuk semua level (umum).
        // ═══════════════════════════════════════════════════════════════
        $this->forge->addColumn('subject_materials', [
            'level' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'default'  => 0,
                'comment'  => 'Level kelas (0=semua, 7=kelas 7, 8=kelas 8, dst). FK via classes.level',
                'after'    => 'parent_id',
            ],
        ]);

        // Kolom is_published di subject_materials sekarang hanya menandai
        // apakah materi sudah "selesai dibuat" — bukan dipublikasikan ke kelas.
        // Publikasi ke kelas dikelola oleh tabel subject_material_publishes.
        // Rename makna: is_published → is_ready (tapi tetap pakai nama lama agar
        // backward-compatible). Kita hanya ubah comment-nya secara dokumentasi.

        $this->db->query(
            "ALTER TABLE subject_materials ADD INDEX idx_level (level)"
        );
        $this->db->query(
            "ALTER TABLE subject_materials ADD INDEX idx_subject_level (subject_id, level)"
        );

        // ═══════════════════════════════════════════════════════════════
        // 2. subject_material_publishes — tabel baru
        //    Satu baris = satu sub materi dipublish ke satu kelas.
        //    Sub materi sama bisa dipublish ke beberapa kelas (multi-row).
        // ═══════════════════════════════════════════════════════════════
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            // Sub Materi yang dipublish (harus parent_id != NULL)
            'material_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → subject_materials.id (sub materi)',
            ],

            // Kelas yang mendapat akses
            'class_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → classes.id',
            ],

            // Siapa yang publish
            'published_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'FK → users.id',
            ],
            'published_at' => ['type' => 'DATETIME', 'null' => true],

            // Apakah publikasi aktif atau dicabut
            'is_active' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 1,
                'comment' => '1=aktif, 0=dicabut/unpublish',
            ],
        ]);
        $this->forge->addPrimaryKey('id');
        // Satu sub materi hanya boleh dipublish sekali per kelas
        $this->forge->addUniqueKey(['material_id', 'class_id']);
        $this->forge->addKey('material_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey(['class_id', 'is_active']);
        $this->forge->createTable('subject_material_publishes', true);

        // ═══════════════════════════════════════════════════════════════
        // 3. forum_threads.class_id — ubah menjadi nullable
        //    Thread diskusi materi bersifat shared (class_id = NULL),
        //    sehingga semua kelas yang punya akses materi bisa bergabung.
        //    Thread kelas biasa tetap pakai class_id.
        // ═══════════════════════════════════════════════════════════════
        $this->db->query(
            "ALTER TABLE forum_threads MODIFY COLUMN class_id INT UNSIGNED NULL DEFAULT NULL
             COMMENT 'FK → classes.id. NULL = thread materi shared (semua kelas)'"
        );
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
