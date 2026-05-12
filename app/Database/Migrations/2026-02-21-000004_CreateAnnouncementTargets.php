<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAnnouncementTargets extends Migration
{
    public function up()
    {
        // Cek apakah tabel sudah ada
        if ($this->db->tableExists('announcement_targets')) {
            return; // Skip jika sudah ada
        }

        // Buat tabel announcement_targets
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'announcement_id' => [
                'type' => 'INT',
                'constraint' => 10,
                'unsigned' => true,
            ],
            'target_type' => [
                'type' => 'ENUM',
                'constraint' => ['role', 'class', 'student'],
                'null' => false,
            ],
            'target_value' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('announcement_id');
        
        // Cek apakah tabel announcements ada sebelum menambah foreign key
        if ($this->db->tableExists('announcements')) {
            $this->forge->addForeignKey('announcement_id', 'announcements', 'id', 'CASCADE', 'CASCADE');
        }
        
        $this->forge->createTable('announcement_targets');
    }

    public function down()
    {
        // Cek apakah tabel ada sebelum menghapus
        if ($this->db->tableExists('announcement_targets')) {
            $this->forge->dropTable('announcement_targets');
        }
    }
}
