<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBookTypeToEbooks extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('book_type', 'ebooks')) {
            $this->forge->addColumn('ebooks', [
                'book_type' => [
                    'type' => 'ENUM',
                    'constraint' => ['mapel', 'umum'],
                    'default' => 'mapel',
                    'null' => false,
                    'after' => 'title',
                ],
            ]);
        }

        // Make level nullable for umum books
        $this->forge->modifyColumn('ebooks', [
            'level' => [
                'type' => 'TINYINT',
                'constraint' => 3,
                'unsigned' => true,
                'null' => true,
                'default' => null,
            ],
            'subject_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'default' => null,
            ],
        ]);
    }

    public function down()
    {
        if ($this->db->fieldExists('book_type', 'ebooks')) {
            $this->forge->dropColumn('ebooks', 'book_type');
        }
    }
}
