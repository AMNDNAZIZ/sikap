<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToStudents extends Migration
{
    public function up()
    {
        // Cek dan tambah kolom class_id
        if (!$this->db->fieldExists('class_id', 'students')) {
            $fields = [
                'class_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'unsigned' => true,
                    'null' => true,
                    'after' => 'user_id'
                ]
            ];
            $this->forge->addColumn('students', $fields);
        }

        // Cek dan tambah kolom room
        if (!$this->db->fieldExists('room', 'students')) {
            $fields = [
                'room' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'class_id'
                ]
            ];
            $this->forge->addColumn('students', $fields);
        }

        // Cek dan tambah kolom plain_password
        if (!$this->db->fieldExists('plain_password', 'students')) {
            $fields = [
                'plain_password' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'room'
                ]
            ];
            $this->forge->addColumn('students', $fields);
        }
    }

    public function down()
    {
        // Cek apakah kolom ada sebelum menghapus
        $columnsToRemove = [];
        
        if ($this->db->fieldExists('class_id', 'students')) {
            $columnsToRemove[] = 'class_id';
        }
        
        if ($this->db->fieldExists('room', 'students')) {
            $columnsToRemove[] = 'room';
        }
        
        if ($this->db->fieldExists('plain_password', 'students')) {
            $columnsToRemove[] = 'plain_password';
        }
        
        if (!empty($columnsToRemove)) {
            $this->forge->dropColumn('students', $columnsToRemove);
        }
    }
}
