<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TahunAjaranSeeder extends Seeder
{
    public function run()
    {
        $this->db->table('tahun_ajaran')->insert([
            'nama' => '2024/2025',
            'aktif' => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
