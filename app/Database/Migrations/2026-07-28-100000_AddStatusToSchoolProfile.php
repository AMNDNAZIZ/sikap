<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusToSchoolProfile extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('school_profile');
        if (!in_array('status', $fields)) {
            $this->forge->addColumn('school_profile', [
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => ['negeri', 'swasta'],
                    'null'       => true,
                    'default'    => null,
                    'after'      => 'level'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->fieldExists('status', 'school_profile')) {
            $this->forge->dropColumn('school_profile', 'status');
        }
    }
}
