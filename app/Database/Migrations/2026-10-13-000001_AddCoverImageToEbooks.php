<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCoverImageToEbooks extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('cover_image', 'ebooks')) {
            $this->forge->addColumn('ebooks', [
                'cover_image' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 255,
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'description',
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('cover_image', 'ebooks')) {
            $this->forge->dropColumn('ebooks', 'cover_image');
        }
    }
}
