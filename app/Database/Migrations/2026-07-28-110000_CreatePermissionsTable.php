<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionsTable extends Migration
{
    public function up()
    {
        // Tabel permissions — daftar modul/aksi
        if (!$this->db->tableExists('permissions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'module' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'comment'    => 'Nama modul (e.g. students, cbt, attendance)',
                ],
                'action' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '30',
                    'comment'    => 'Aksi (view, create, update, delete, manage)',
                ],
                'label' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '100',
                    'comment'    => 'Label tampilan di UI',
                ],
                'group_name' => [
                    'type'       => 'VARCHAR',
                    'constraint' => '50',
                    'null'       => true,
                    'comment'    => 'Kategori grup untuk tampilan (Akademik, Kesiswaan, dll)',
                ],
                'sort_order' => [
                    'type'       => 'INT',
                    'constraint' => 5,
                    'default'    => 0,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['module', 'action']);
            $this->forge->createTable('permissions');
        }

        // Tabel role_permissions — pivot
        if (!$this->db->tableExists('role_permissions')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'role_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'permission_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey(['role_id', 'permission_id']);
            $this->forge->addForeignKey('permission_id', 'permissions', 'id', 'CASCADE', 'CASCADE');
            $this->forge->createTable('role_permissions');
        }
    }

    public function down()
    {
        $this->forge->dropTable('role_permissions', true);
        $this->forge->dropTable('permissions', true);
    }
}
