<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSambutanToSchoolProfile extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('sambutan_title', 'school_profile')) {
            $fields['sambutan_title'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'vision_image',
            ];
        }

        if (!$this->db->fieldExists('sambutan_subtitle', 'school_profile')) {
            $fields['sambutan_subtitle'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'sambutan_title',
            ];
        }

        if (!$this->db->fieldExists('sambutan_quote', 'school_profile')) {
            $fields['sambutan_quote'] = [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
                'default'    => null,
                'after'      => 'sambutan_subtitle',
            ];
        }

        if (!$this->db->fieldExists('sambutan_isi', 'school_profile')) {
            $fields['sambutan_isi'] = [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'sambutan_quote',
            ];
        }

        if (!$this->db->fieldExists('sambutan_image', 'school_profile')) {
            $fields['sambutan_image'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'sambutan_isi',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('school_profile', $fields);
        }
    }

    public function down()
    {
        $columns = ['sambutan_title', 'sambutan_subtitle', 'sambutan_quote', 'sambutan_isi', 'sambutan_image'];
        foreach ($columns as $column) {
            if ($this->db->fieldExists($column, 'school_profile')) {
                $this->forge->dropColumn('school_profile', $column);
            }
        }
    }
}
