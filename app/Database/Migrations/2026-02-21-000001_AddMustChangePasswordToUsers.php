<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMustChangePasswordToUsers extends Migration
{
    public function up()
    {
        // Cek apakah kolom sudah ada sebelum menambahkan
        if (!$this->db->fieldExists('must_change_password', 'users')) {
            $fields = [
                'must_change_password' => [
                    'type' => 'TINYINT',
                    'constraint' => 1,
                    'default' => 0,
                    'null' => false,
                    'after' => 'related_type'
                ]
            ];
            $this->forge->addColumn('users', $fields);
        }

        if (!$this->db->fieldExists('password_changed_at', 'users')) {
            $fields = [
                'password_changed_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after' => 'must_change_password'
                ]
            ];
            $this->forge->addColumn('users', $fields);
        }
    }

    public function down()
    {
        // Cek apakah kolom ada sebelum menghapus
        $columnsToRemove = [];
        
        if ($this->db->fieldExists('must_change_password', 'users')) {
            $columnsToRemove[] = 'must_change_password';
        }
        
        if ($this->db->fieldExists('password_changed_at', 'users')) {
            $columnsToRemove[] = 'password_changed_at';
        }
        
        if (!empty($columnsToRemove)) {
            $this->forge->dropColumn('users', $columnsToRemove);
        }
    }
}
