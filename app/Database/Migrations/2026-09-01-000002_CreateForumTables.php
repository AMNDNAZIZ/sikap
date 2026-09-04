<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateForumTables extends Migration
{
    public function up()
    {
        // ── Tabel 1: forum_threads ────────────────────────────────────────
        // Satu thread = satu topik diskusi, dikaitkan ke mapel + kelas.
        // Bisa juga dikaitkan ke materi tertentu (related_type=material, related_id=id).
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            // Konteks: mapel + kelas + tahun ajaran
            'subject_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → subjects.id',
            ],
            'class_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → classes.id',
            ],
            'academic_year_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → academic_years.id',
            ],

            // Relasi opsional ke resource LMS (materi, tugas, kuis)
            'related_type' => [
                'type'       => 'ENUM',
                'constraint' => ['none', 'material', 'tugas', 'quiz'],
                'default'    => 'none',
                'comment'    => 'Jenis resource terkait',
            ],
            'related_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'ID resource terkait',
            ],

            // Konten thread
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'body'  => ['type' => 'TEXT', 'null' => true],

            // Penulis
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → users.id',
            ],

            // Status
            'is_pinned' => [
                'type'    => 'TINYINT', 'constraint' => 1, 'default' => 0,
                'comment' => '1 = disematkan guru di atas',
            ],
            'is_locked' => [
                'type'    => 'TINYINT', 'constraint' => 1, 'default' => 0,
                'comment' => '1 = dikunci, tidak bisa dibalas',
            ],
            'is_answered' => [
                'type'    => 'TINYINT', 'constraint' => 1, 'default' => 0,
                'comment' => '1 = sudah ada jawaban terpilih',
            ],

            // Statistik de-normalized untuk performa
            'reply_count' => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'view_count'  => ['type' => 'INT', 'unsigned' => true, 'default' => 0],
            'last_reply_at' => ['type' => 'DATETIME', 'null' => true],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('subject_id');
        $this->forge->addKey('class_id');
        $this->forge->addKey(['class_id', 'subject_id']);
        $this->forge->addKey('user_id');
        $this->forge->addKey(['is_pinned', 'created_at']);
        $this->forge->createTable('forum_threads', true);

        // ── Tabel 2: forum_replies ────────────────────────────────────────
        // Reply ke thread atau reply ke reply lain (1 level nesting = sudah cukup).
        $this->forge->addField([
            'id' => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],

            'thread_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'FK → forum_threads.id',
            ],
            // NULL = langsung reply ke thread, != NULL = reply ke reply lain
            'parent_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'FK → forum_replies.id (untuk nested reply)',
            ],

            'user_id' => ['type' => 'INT', 'unsigned' => true],
            'body'    => ['type' => 'TEXT'],

            // Guru bisa menandai satu reply sebagai jawaban terbaik
            'is_best_answer' => [
                'type'    => 'TINYINT', 'constraint' => 1, 'default' => 0,
            ],

            // Soft delete (moderasi guru)
            'is_deleted' => [
                'type'    => 'TINYINT', 'constraint' => 1, 'default' => 0,
            ],
            'deleted_by' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],

            // Upvote sederhana (jumlah, bukan user track)
            'upvotes' => ['type' => 'SMALLINT', 'unsigned' => true, 'default' => 0],

            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('thread_id');
        $this->forge->addKey('parent_id');
        $this->forge->addKey('user_id');
        $this->forge->addKey(['thread_id', 'parent_id']);
        $this->forge->createTable('forum_replies', true);

        // ── Tabel 3: forum_upvotes ────────────────────────────────────────
        // Track siapa yang upvote apa (mencegah double-vote).
        $this->forge->addField([
            'id'       => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'reply_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'  => ['type' => 'INT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['reply_id', 'user_id']);
        $this->forge->createTable('forum_upvotes', true);

        // ── Tabel 4: forum_reads ─────────────────────────────────────────
        // Track thread yang sudah dibaca user (untuk badge "Baru").
        $this->forge->addField([
            'id'        => ['type' => 'INT', 'unsigned' => true, 'auto_increment' => true],
            'thread_id' => ['type' => 'INT', 'unsigned' => true],
            'user_id'   => ['type' => 'INT', 'unsigned' => true],
            'read_at'   => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->addUniqueKey(['thread_id', 'user_id']);
        $this->forge->createTable('forum_reads', true);
    }

    public function down()
    {
        $this->forge->dropTable('forum_reads',   true);
        $this->forge->dropTable('forum_upvotes', true);
        $this->forge->dropTable('forum_replies', true);
        $this->forge->dropTable('forum_threads', true);
    }
}
