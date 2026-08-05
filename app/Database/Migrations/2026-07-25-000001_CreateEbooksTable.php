<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEbooksTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => false],
            'level' => ['type' => 'TINYINT', 'constraint' => 3, 'unsigned' => true, 'null' => false, 'comment' => 'Level kelas 1-6'],
            'subject_id' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'religion' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'default' => null],
            'description' => ['type' => 'TEXT', 'null' => true, 'default' => null],
            'filename' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'comment' => 'UUID.pdf'],
            'original_filename' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'file_size' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false, 'default' => 0],
            'uploaded_by' => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'null' => false],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('level', false, false, 'idx_ebooks_level');
        $this->forge->addKey('subject_id', false, false, 'idx_ebooks_subject_id');
        $this->forge->addKey('religion', false, false, 'idx_ebooks_religion');
        $this->forge->addKey('title', false, false, 'idx_ebooks_title');
        $this->forge->addForeignKey('subject_id', 'subjects', 'id', 'RESTRICT', 'RESTRICT', 'fk_ebooks_subject');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', 'RESTRICT', 'RESTRICT', 'fk_ebooks_user');
        $this->forge->createTable('ebooks', true);
    }

    public function down()
    {
        $this->forge->dropTable('ebooks', true);
    }
}
