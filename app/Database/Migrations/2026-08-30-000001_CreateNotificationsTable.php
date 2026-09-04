<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            // Penerima notifikasi
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'comment'  => 'users.id penerima notifikasi',
            ],
            // Jenis notifikasi untuk filtering & ikon
            'type' => [
                'type'       => 'ENUM',
                'constraint' => [
                    'tugas_baru',
                    'pengumuman_baru',
                    'nilai_masuk',
                    'agenda_baru',
                    'tugas_dinilai',
                    'cbt_dibuka',
                    'materi_baru',
                    'info',
                ],
                'default' => 'info',
            ],
            // Judul singkat notifikasi
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            // Pesan detail
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            // URL tujuan saat notifikasi diklik (nullable = tidak ada link)
            'url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            // Relasi ke resource sumber (opsional, untuk future deep-link)
            'related_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'e.g. tugas, announcement, assessment',
            ],
            'related_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            // Status baca
            'is_read' => [
                'type'    => 'TINYINT',
                'constraint' => 1,
                'default' => 0,
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addPrimaryKey('id');
        $this->forge->addKey('user_id');
        $this->forge->addKey('is_read');
        $this->forge->addKey(['user_id', 'is_read']);
        $this->forge->addKey('created_at');

        $this->forge->createTable('notifications', true);
    }

    public function down()
    {
        $this->forge->dropTable('notifications', true);
    }
}
