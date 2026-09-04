<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class EnrichSubjectMaterials extends Migration
{
    public function up()
    {
        // Tambah kolom konten & publikasi ke tabel subject_materials yang sudah ada
        $fields = [
            // Tipe konten: teks HTML, PDF upload, embed video, atau link eksternal
            'content_type' => [
                'type'       => 'ENUM',
                'constraint' => ['text', 'pdf', 'video', 'link'],
                'default'    => 'text',
                'after'      => 'description',
            ],
            // Konten teks (HTML dari CKEditor)
            'content' => [
                'type' => 'LONGTEXT',
                'null' => true,
                'after' => 'content_type',
            ],
            // Path file PDF yang di-upload (relatif dari FCPATH/uploads/materials/)
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'content',
            ],
            // URL video (YouTube, Vimeo, dsb — diembed via iframe)
            'video_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'file_path',
            ],
            // Link eksternal (website, Google Drive, dll)
            'external_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'after'      => 'video_url',
            ],
            // Estimasi waktu baca dalam menit (untuk progress tracking)
            'estimated_minutes' => [
                'type'       => 'TINYINT',
                'unsigned'   => true,
                'null'       => true,
                'comment'    => 'Estimasi menit untuk membaca/menonton',
                'after'      => 'external_link',
            ],
            // Urutan tampil (untuk sorting manual oleh guru)
            'sort_order' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
                'after'    => 'estimated_minutes',
            ],
            // Status publikasi: hanya materi terpublish yang bisa diakses siswa
            'is_published' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '0=draft, 1=published',
                'after'   => 'sort_order',
            ],
            // Siapa yang terakhir publish/unpublish
            'published_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'is_published',
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'after' => 'published_by',
            ],
            // Created by (siapa guru yang membuat)
            'created_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'after'    => 'published_at',
            ],
        ];

        $this->forge->addColumn('subject_materials', $fields);

        // Index untuk query portal siswa (hanya published)
        $this->db->query('ALTER TABLE subject_materials ADD INDEX idx_published (is_published)');
        $this->db->query('ALTER TABLE subject_materials ADD INDEX idx_subject_year_semester (subject_id, year_id, semester)');
    }

    public function down()
    {
        $this->forge->dropColumn('subject_materials', [
            'content_type', 'content', 'file_path', 'video_url',
            'external_link', 'estimated_minutes', 'sort_order',
            'is_published', 'published_by', 'published_at', 'created_by',
        ]);
    }
}
