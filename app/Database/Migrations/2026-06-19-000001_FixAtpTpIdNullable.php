<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixAtpTpIdNullable extends Migration
{
    public function up()
    {
        // tp_id di alur_tujuan_pembelajaran harus nullable
        try {
            $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
                'tp_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('warning', '[FixAtpTpIdNullable] modifyColumn tp_id: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $this->forge->modifyColumn('alur_tujuan_pembelajaran', [
            'tp_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
    }
}
