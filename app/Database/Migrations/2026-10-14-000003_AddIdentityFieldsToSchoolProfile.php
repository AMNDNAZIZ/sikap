<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIdentityFieldsToSchoolProfile extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('school_profile');
        $newColumns = [];

        if (!in_array('npsn', $fields)) {
            $newColumns['npsn'] = [
                'type'       => 'VARCHAR',
                'constraint' => 30,
                'null'       => true,
                'default'    => null,
                'after'      => 'name',
            ];
        }

        if (!in_array('nis_nss_nds', $fields)) {
            $newColumns['nis_nss_nds'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'default'    => null,
                'after'      => 'npsn',
            ];
        }

        if (!in_array('village', $fields)) {
            $newColumns['village'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'address',
            ];
        }

        if (!in_array('district', $fields)) {
            $newColumns['district'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'village',
            ];
        }

        if (!in_array('province', $fields)) {
            $newColumns['province'] = [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
                'default'    => null,
                'after'      => 'city_regency',
            ];
        }

        if (!in_array('website', $fields)) {
            $newColumns['website'] = [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => true,
                'default'    => null,
                'after'      => 'email',
            ];
        }

        if (!empty($newColumns)) {
            $this->forge->addColumn('school_profile', $newColumns);
        }
    }

    public function down()
    {
        $cols = ['npsn', 'nis_nss_nds', 'village', 'district', 'province', 'website'];
        foreach ($cols as $col) {
            if ($this->db->fieldExists($col, 'school_profile')) {
                $this->forge->dropColumn('school_profile', $col);
            }
        }
    }
}
