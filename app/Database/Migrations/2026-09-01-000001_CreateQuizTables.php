<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateQuizTables extends Migration
{
    public function up()
    {
        // ── Tabel 1: quiz_configs ─────────────────────────────────────────
        // Konfigurasi kuis yang dibuat guru. Lebih sederhana dari cbt_test_status:
        // tanpa token, tanpa jadwal formal, tanpa anti-cheat.
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            // Relasi ke bank soal CBT (soal diambil dari sana)
            'bank_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → cbt_question_banks.id',
            ],

            // Metadata kuis
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],

            // Kelas yang boleh akses (JSON array class_id integers)
            'class_ids' => [
                'type'    => 'TEXT',
                'null'    => true,
                'comment' => 'JSON array of class IDs. NULL = semua kelas',
            ],

            // Jumlah soal per tipe yang ditampilkan
            'show_pg_count'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'show_pgk_count'         => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'show_bs_count'          => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'show_esai_count'        => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],

            // Bobot penilaian (total harus = 100, atau semua 0 = tidak berbobot)
            'bobot_pg'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 100],
            'bobot_pgk'   => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'bobot_bs'    => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],
            'bobot_esai'  => ['type' => 'TINYINT', 'unsigned' => true, 'default' => 0],

            // Opsi kuis
            'shuffle_question' => [
                'type'    => 'ENUM',
                'constraint' => ['ya', 'tidak'],
                'default' => 'ya',
            ],
            'shuffle_option' => [
                'type'    => 'ENUM',
                'constraint' => ['ya', 'tidak'],
                'default' => 'ya',
            ],

            // Durasi dalam menit (0 = tidak terbatas)
            'duration' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'default'  => 0,
                'comment'  => '0 = tidak ada batas waktu',
            ],

            // Boleh diulang berapa kali (0 = tak terbatas)
            'max_attempts' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'default'  => 0,
                'comment'  => '0 = tidak terbatas',
            ],

            // Apakah tampilkan pembahasan (kunci + penjelasan) setelah submit
            'show_answer' => [
                'type'    => 'ENUM',
                'constraint' => ['ya', 'tidak'],
                'default' => 'ya',
                'comment' => 'ya = tampilkan jawaban benar setelah submit',
            ],

            // Publikasi
            'is_published' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
                'comment' => '0 = draft, 1 = aktif diakses siswa',
            ],

            // Pemilik kuis
            'created_by' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'teacher_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'FK → teachers.id',
            ],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('bank_id');
        $this->forge->addKey('teacher_id');
        $this->forge->addKey('is_published');
        $this->forge->createTable('quiz_configs', true);

        // ── Tabel 2: quiz_sessions ────────────────────────────────────────
        // Sesi pengerjaan kuis per siswa. Satu baris per attempt.
        // (berbeda dari CBT: boleh ada beberapa baris per quiz_id + student_id)
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            'quiz_id'    => ['type' => 'INT', 'unsigned' => true],
            'student_id' => ['type' => 'INT', 'unsigned' => true],

            // Urutan soal yang ditampilkan ke siswa ini (JSON array question IDs)
            'question_order' => ['type' => 'TEXT', 'null' => true],
            // Urutan opsi per soal (JSON object {qid: [opsi shuffled]})
            'option_orders'  => ['type' => 'TEXT', 'null' => true],

            // Status sesi
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['active', 'finished'],
                'default'    => 'active',
            ],

            // Waktu mulai (Unix timestamp — konsisten dengan cbt_sessions)
            'started_at'  => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'finished_at' => ['type' => 'INT', 'unsigned' => true, 'null' => true],

            // Nilai final
            'score'       => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],
            'total_score' => ['type' => 'DECIMAL', 'constraint' => '6,2', 'null' => true],

            // Attempt ke-berapa untuk siswa + kuis ini
            'attempt_number' => [
                'type'     => 'TINYINT',
                'unsigned' => true,
                'default'  => 1,
            ],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey(['quiz_id', 'student_id']);
        $this->forge->addKey('student_id');
        $this->forge->addKey('status');
        $this->forge->createTable('quiz_sessions', true);

        // ── Tabel 3: quiz_answers ─────────────────────────────────────────
        // Jawaban siswa per sesi kuis.
        // Terpisah dari cbt_answers agar tidak ada konflik test_id/session_id.
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'session_id'  => ['type' => 'INT', 'unsigned' => true],
            'question_id' => ['type' => 'INT', 'unsigned' => true],
            'answer'      => ['type' => 'TEXT', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        // Unique: satu jawaban per soal per sesi
        $this->forge->addUniqueKey(['session_id', 'question_id']);
        $this->forge->addKey('session_id');
        $this->forge->createTable('quiz_answers', true);
    }

    public function down()
    {
        $this->forge->dropTable('quiz_answers',  true);
        $this->forge->dropTable('quiz_sessions', true);
        $this->forge->dropTable('quiz_configs',  true);
    }
}
