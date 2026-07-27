<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddClassIdToAtp extends Migration
{
    public function up()
    {
        if (!$this->db->fieldExists('class_id', 'alur_tujuan_pembelajaran')) {
            $fields = [
                'class_id' => [
                    'type'       => 'INT',
                    'unsigned'   => true,
                    'null'       => true, // Allow null temporarily to not break existing data
                    'after'      => 'subject_id'
                ],
            ];
            
            $this->forge->addColumn('alur_tujuan_pembelajaran', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('class_id', 'alur_tujuan_pembelajaran')) {
            $this->forge->dropColumn('alur_tujuan_pembelajaran', 'class_id');
        }
    }
}
