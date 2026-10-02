<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class MakeStudentIdNullableInStudentMutations extends Migration
{
    public function up()
    {
        try {
            $this->forge->modifyColumn('student_mutations', [
                'student_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'default'    => null,
                ],
            ]);
        } catch (\Exception $e) {
            log_message('warning', '[MakeStudentIdNullable] modifyColumn: ' . $e->getMessage());
        }
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
