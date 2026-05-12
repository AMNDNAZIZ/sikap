<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddCreatedByToCbtExamNames extends Migration
{
    public function up()
    {
        // Add created_by column to cbt_exam_names table
        $fields = [
            'created_by' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'null' => true,
                'after' => 'name'
            ]
        ];
        
        $this->forge->addColumn('cbt_exam_names', $fields);
        
        // Add foreign key to users table
        $this->forge->addForeignKey('created_by', 'users', 'id', 'SET NULL', 'CASCADE', 'fk_cbt_exam_names_created_by');
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
