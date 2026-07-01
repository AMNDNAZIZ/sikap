<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSchoolDaysToAcademicYears extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('academic_years');
        if (!in_array('school_days', $fields)) {
            $this->forge->addColumn('academic_years', [
                'school_days' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 5,
                    'comment' => 'Jumlah hari sekolah per minggu: 5 (Senin-Jumat) atau 6 (Senin-Sabtu)',
                    'after' => 'sumatif_weight'
                ]
            ]);
        }
    }

    public function down()
    {
        $this->forge->dropColumn('academic_years', 'school_days');
    }
}
