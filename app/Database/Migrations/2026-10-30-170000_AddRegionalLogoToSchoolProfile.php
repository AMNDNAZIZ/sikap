<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRegionalLogoToSchoolProfile extends Migration
{
    public function up()
    {
        $fields = [];

        if (!$this->db->fieldExists('regional_logo', 'school_profile')) {
            $fields['regional_logo'] = [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
                'default'    => null,
                'after'      => 'logo',
            ];
        }

        if (!empty($fields)) {
            $this->forge->addColumn('school_profile', $fields);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('regional_logo', 'school_profile')) {
            $this->forge->dropColumn('school_profile', 'regional_logo');
        }
    }
}

