<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToClasses extends Migration
{
    public function up()
    {
        $this->forge->addColumn('classes', [
            'is_active' => [
                'type'       => 'BOOLEAN',
                'default'    => true,
                'null'       => false,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('classes', 'is_active');
    }
}
