<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateModulAjar extends Migration
{
    public function up()
    {
        // 1. Add gemini_api_key to teachers
        if (!$this->db->fieldExists('gemini_api_key', 'teachers')) {
            $fields = [
                'gemini_api_key' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '255',
                    'null'       => true,
                    'after'      => 'photo'
                ]
            ];
            $this->forge->addColumn('teachers', $fields);
        }

        // 2. Create modul_ajar table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'atp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'subject_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'class_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'teacher_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'content' => [
                'type' => 'LONGTEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('atp_id', 'alur_tujuan_pembelajaran', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('modul_ajar', true);
    }

    public function down()
    {
        // Drop modul_ajar
        $this->forge->dropTable('modul_ajar', true);
        
        // Remove gemini_api_key
        if ($this->db->fieldExists('gemini_api_key', 'teachers')) {
            $this->forge->dropColumn('teachers', 'gemini_api_key');
        }
    }
}
