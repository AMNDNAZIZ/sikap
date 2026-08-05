<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('users')->insert([
            'username' => 'superadmin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'nama' => 'Super Admin',
            'role' => 'superadmin',
            'aktif' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        // Admin biasa
        $this->db->table('users')->insert([
            'username' => 'adminkegiatan',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'nama' => 'Admin Kegiatan',
            'role' => 'admin',
            'aktif' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
