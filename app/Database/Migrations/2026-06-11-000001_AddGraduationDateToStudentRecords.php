<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddGraduationDateToStudentRecords extends Migration
{
    public function up(): void
    {
        if (!$this->db->fieldExists('graduation_date', 'student_records')) {
            $this->forge->addColumn('student_records', [
                'graduation_date' => [
                    'type' => 'DATE',
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }
    }

    public function down(): void
    {
        if ($this->db->fieldExists('graduation_date', 'student_records')) {
            $this->forge->dropColumn('student_records', 'graduation_date');
        }
    }
}
