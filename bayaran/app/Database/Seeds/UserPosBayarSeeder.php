<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserPosBayarSeeder extends Seeder
{
    public function run()
    {
        // Anggap ID user = 2 (adminkegiatan), pos_bayar id 3 = Kegiatan, 2 = PPDB
        $data = [
            ['user_id' => 2, 'pos_bayar_id' => 3],
            ['user_id' => 2, 'pos_bayar_id' => 2],
        ];

        $this->db->table('user_pos_bayar')->insertBatch($data);
    }
}

