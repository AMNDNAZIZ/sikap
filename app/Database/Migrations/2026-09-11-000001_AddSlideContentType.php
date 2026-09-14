<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Tipe konten materi baru: "Slide Gambar".
 *
 * - Tambah nilai 'slide' ke enum subject_materials.content_type.
 * - Tabel subject_material_slides menyimpan gambar-gambar slide (1 baris = 1 gambar),
 *   dengan urutan dan caption opsional. Materi berperan sebagai kontainer slide.
 *
 * Siswa melihatnya sebagai slideshow (swipe/geser). Progres "selesai" dipicu saat
 * siswa mencapai slide terakhir. Gambar boleh diunduh siswa.
 */
class AddSlideContentType extends Migration
{
    public function up()
    {
        // 1) Tambah 'slide' ke enum content_type
        $this->db->query(
            "ALTER TABLE subject_materials
             MODIFY COLUMN content_type ENUM('text','pdf','video','link','html','slide')
             NULL DEFAULT 'text'"
        );

        // 2) Tabel slide gambar
        if (! $this->db->tableExists('subject_material_slides')) {
            $this->forge->addField([
                'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
                'material_id' => [
                    'type'     => 'INT',
                    'unsigned' => true,
                    'comment'  => 'FK → subject_materials.id',
                ],
                'image_path'  => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'comment'    => 'Nama file gambar di uploads/materials/slides/',
                ],
                'caption'     => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'comment' => 'Keterangan slide (opsional)',
                ],
                'sort_order'  => [
                    'type'     => 'INT',
                    'default'  => 0,
                    'comment'  => 'Urutan tampil slide',
                ],
                'created_at'  => ['type' => 'DATETIME', 'null' => true],
            ]);
            $this->forge->addPrimaryKey('id');
            $this->forge->addKey('material_id');
            $this->forge->addKey(['material_id', 'sort_order']);
            $this->forge->createTable('subject_material_slides', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('subject_material_slides', true);

        // Kembalikan materi 'slide' ke 'text' lalu perkecil enum
        $this->db->query(
            "UPDATE subject_materials SET content_type = 'text' WHERE content_type = 'slide'"
        );
        $this->db->query(
            "ALTER TABLE subject_materials
             MODIFY COLUMN content_type ENUM('text','pdf','video','link','html')
             NULL DEFAULT 'text'"
        );
    }
}
