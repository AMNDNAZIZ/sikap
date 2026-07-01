<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCityRegencyToSchoolProfile extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('school_profile');
        if (!in_array('city_regency', $fields)) {
            $this->forge->addColumn('school_profile', [
                'city_regency' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'null'       => true,
                    'after'      => 'address'
                ],
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('school_profile', 'city_regency');
    }
}
