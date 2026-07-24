<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeStudentIdNullableInStudentMutations extends Migration
{
    public function up()
    {
        $this->forge->modifyColumn('student_mutations', [
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => true,
                'default'    => null,
            ],
        ]);
    }

    public function down()
    {
        $this->forge->modifyColumn('student_mutations', [
            'student_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
                'null'       => false,
            ],
        ]);
    }
}
