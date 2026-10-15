<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddBiodataFieldsToStudents extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('students');
        $newColumns = [];

        if (!in_array('phone', $fields)) {
            $newColumns['phone'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'address',
            ];
        }

        if (!in_array('school_origin', $fields)) {
            $newColumns['school_origin'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'after'      => 'phone',
            ];
        }

        if (!in_array('family_status', $fields)) {
            $newColumns['family_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => 'Anak Kandung',
                'after'      => 'religion',
            ];
        }

        if (!in_array('parent_address', $fields)) {
            $newColumns['parent_address'] = [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'mother_income',
            ];
        }

        if (!in_array('parent_phone', $fields)) {
            $newColumns['parent_phone'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'parent_address',
            ];
        }

        if (!in_array('guardian_address', $fields)) {
            $newColumns['guardian_address'] = [
                'type'       => 'TEXT',
                'null'       => true,
                'default'    => null,
                'after'      => 'guardian_income',
            ];
        }

        if (!in_array('guardian_phone', $fields)) {
            $newColumns['guardian_phone'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'guardian_address',
            ];
        }

        if (!empty($newColumns)) {
            $this->forge->addColumn('students', $newColumns);
        }
    }

    public function down()
    {
        $cols = ['phone', 'school_origin', 'family_status', 'parent_address', 'parent_phone', 'guardian_address', 'guardian_phone'];
        foreach ($cols as $col) {
            if ($this->db->fieldExists($col, 'students')) {
                $this->forge->dropColumn('students', $col);
            }
        }
    }
}
