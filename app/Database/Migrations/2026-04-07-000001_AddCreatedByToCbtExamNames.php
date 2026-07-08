<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedByToCbtExamNames extends Migration
{
    public function up()
    {
        $fields = $this->db->getFieldNames('cbt_exam_names');
        if (!in_array('created_by', $fields)) {
            $columnData = [
                'created_by' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'name'
                ]
            ];
            $this->forge->addColumn('cbt_exam_names', $columnData);

            // Cek apakah FK sudah terlanjur ada untuk menghindari error
            $existingFk = $this->db->query("
                SELECT 1 FROM information_schema.REFERENTIAL_CONSTRAINTS
                WHERE CONSTRAINT_SCHEMA = DATABASE()
                AND CONSTRAINT_NAME = 'fk_cbt_exam_names_created_by'
                LIMIT 1
            ")->getRow();

            if (!$existingFk) {
                $this->db->query('ALTER TABLE cbt_exam_names ADD CONSTRAINT fk_cbt_exam_names_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL ON UPDATE CASCADE');
            }
        }
    }

    public function down()
    {
        // Drop foreign key first
        if ($this->db->DBDriver !== 'SQLite3') {
            $this->forge->dropForeignKey('cbt_exam_names', 'fk_cbt_exam_names_created_by');
        }
        
        // Drop column
        $this->forge->dropColumn('cbt_exam_names', 'created_by');
    }
}
