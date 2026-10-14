<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEraporScoreToGrades extends Migration
{
    public function up()
    {
        if ($this->db->tableExists('grades')) {
            if (!$this->db->fieldExists('erapor_score', 'grades')) {
                $fields = [
                    'erapor_score' => [
                        'type'       => 'DECIMAL',
                        'constraint' => '5,2',
                        'null'       => true,
                        'after'      => 'report_score',
                    ],
                ];
                $this->forge->addColumn('grades', $fields);
            }
        }
    }

    public function down()
    {
        if ($this->db->tableExists('grades')) {
            if ($this->db->fieldExists('erapor_score', 'grades')) {
                $this->forge->dropColumn('grades', 'erapor_score');
            }
        }
    }
}
