<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTeacherDocuments extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true, 'auto_increment' => true],
            'teacher_id'  => ['type' => 'INT', 'constraint' => 11, 'unsigned' => true],
            'title'       => ['type' => 'VARCHAR', 'constraint' => 255],
            'filename'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'original_name' => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_type'   => ['type' => 'VARCHAR', 'constraint' => 20, 'comment' => 'image or pdf'],
            'file_size'   => ['type' => 'INT', 'constraint' => 11, 'default' => 0, 'comment' => 'bytes'],
            'created_at'  => ['type' => 'DATETIME', 'null' => true],
            'updated_at'  => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('teacher_id');
        $this->forge->addForeignKey('teacher_id', 'teachers', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('teacher_documents');
    }

    public function down()
    {
        $this->forge->dropTable('teacher_documents');
    }
}
