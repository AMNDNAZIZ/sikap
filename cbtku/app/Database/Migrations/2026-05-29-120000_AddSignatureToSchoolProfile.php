<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSignatureToSchoolProfile extends Migration
{
    public function up()
    {
        // Add 'signature' column (nullable string) to school_profile table
        $fields = [
            'signature' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
        ];
        $this->forge->addColumn('school_profile', $fields);
    }

    public function down()
    {
        // Remove the 'signature' column
        $this->forge->dropColumn('school_profile', 'signature');
    }
}
?>
