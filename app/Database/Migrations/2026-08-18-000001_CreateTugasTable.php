<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTugasTable extends Migration
{
    public function up()
    {
        // Tabel tugas (dibuat oleh guru/admin)
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'teacher_id'  => ['type' => 'INT', 'unsigned' => true, 'null' => true, 'comment' => 'NULL jika dibuat admin'],
            'class_id'    => ['type' => 'INT', 'unsigned' => true],
            'subject_id'  => ['type' => 'INT', 'unsigned' => true],
            'judul'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'deskripsi'   => ['type' => 'LONGTEXT', 'null' => true, 'comment' => 'HTML dari CKEditor'],
            'mulai_at'    => ['type' => 'DATETIME', 'comment' => 'Waktu tugas mulai bisa dikerjakan'],
            'selesai_at'  => ['type' => 'DATETIME', 'comment' => 'Deadline pengerjaan'],
            'created_by'  => ['type' => 'INT', 'unsigned' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('class_id');
        $this->forge->addKey('subject_id');
        $this->forge->addKey('teacher_id');
        $this->forge->addKey(['mulai_at', 'selesai_at']);
        $this->forge->createTable('tugas', true);

        // Tabel pengumpulan tugas oleh siswa
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'tugas_id'    => ['type' => 'INT', 'unsigned' => true],
            'student_id'  => ['type' => 'INT', 'unsigned' => true],
            'jawaban'     => ['type' => 'LONGTEXT', 'null' => true, 'comment' => 'HTML dari CKEditor'],
            'dikumpul_at' => ['type' => 'DATETIME', 'null' => true],
            // Penilaian deskriptif oleh guru
            'nilai'       => ['type' => 'ENUM', 'constraint' => ['sangat_bagus', 'bagus', 'kurang', 'belajar_lagi'], 'null' => true],
            'catatan_guru'=> ['type' => 'TEXT', 'null' => true],
            'dinilai_at'  => ['type' => 'DATETIME', 'null' => true],
            'dinilai_oleh'=> ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['tugas_id', 'student_id']);
        $this->forge->addKey('student_id');
        $this->forge->createTable('tugas_submissions', true);
    }

    public function down()
    {
        $this->forge->dropTable('tugas_submissions', true);
        $this->forge->dropTable('tugas', true);
    }
}
