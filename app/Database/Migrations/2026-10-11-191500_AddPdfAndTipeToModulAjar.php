<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddPdfAndTipeToModulAjar extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('tipe', 'modul_ajar')) {
            $fields['tipe'] = [
                'type'       => 'VARCHAR',
                'constraint' => '20',
                'default'    => 'editor',
                'after'      => 'content',
            ];
        }

        if (!$this->db->fieldExists('file_pdf', 'modul_ajar')) {
            $fields['file_pdf'] = [
                'type'       => 'VARCHAR',
                'constraint' => '255',
                'null'       => true,
                'after'      => 'tipe',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('modul_ajar', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('file_pdf', 'modul_ajar')) {
            $this->forge->dropColumn('modul_ajar', 'file_pdf');
        }

        if ($this->db->fieldExists('tipe', 'modul_ajar')) {
            $this->forge->dropColumn('modul_ajar', 'tipe');
        }
    }
}
