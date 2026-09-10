<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Menambahkan nilai 'html' ke enum content_type di subject_materials.
 *
 * Tipe konten baru: "Halaman HTML Interaktif" — HTML mentah (termasuk CSS & JS)
 * disimpan di kolom `content` yang sudah ada, lalu dirender ke siswa/admin di
 * dalam <iframe sandbox srcdoc> agar interaktif namun terisolasi dari aplikasi
 * utama (aman dari XSS ke sesi pengguna).
 */
class AddHtmlContentTypeToSubjectMaterials extends Migration
{
    public function up()
    {
        // MODIFY enum untuk menambah 'html' (idempotent secara efektif:
        // menjalankan ulang hanya menetapkan definisi enum yang sama).
        $this->db->query(
            "ALTER TABLE subject_materials
             MODIFY COLUMN content_type ENUM('text','pdf','video','link','html')
             NULL DEFAULT 'text'"
        );
    }

    public function down()
    {
        // Kembalikan materi 'html' ke 'text' agar tidak melanggar enum lama
        $this->db->query(
            "UPDATE subject_materials SET content_type = 'text' WHERE content_type = 'html'"
        );
        $this->db->query(
            "ALTER TABLE subject_materials
             MODIFY COLUMN content_type ENUM('text','pdf','video','link')
             NULL DEFAULT 'text'"
        );
    }
}
