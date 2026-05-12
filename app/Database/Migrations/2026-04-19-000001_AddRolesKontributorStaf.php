<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddRolesKontributorStaf extends Migration
{
    public function up()
    {
        $existing6 = $this->db->table('roles')->where('id', 6)->get()->getRowArray();
        if (!$existing6) {
            $this->db->table('roles')->insert(['id' => 6, 'name' => 'Kontributor']);
        }

        $existing7 = $this->db->table('roles')->where('id', 7)->get()->getRowArray();
        if (!$existing7) {
            $this->db->table('roles')->insert(['id' => 7, 'name' => 'Staf']);
        }
    }

    public function down()
    {
        $this->db->table('roles')->whereIn('id', [6, 7])->delete();
    }
}
