<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddLkpdContentToModulAjar extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('lkpd_content', 'modul_ajar')) {
            $fields = [
                'lkpd_content' => [
                    'type'       => 'LONGTEXT',
                    'null'       => true,
                    'after'      => 'file_pdf',
                ]
            ];
            $this->forge->addColumn('modul_ajar', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('lkpd_content', 'modul_ajar')) {
            $this->forge->dropColumn('modul_ajar', 'lkpd_content');
        }
    }
}
