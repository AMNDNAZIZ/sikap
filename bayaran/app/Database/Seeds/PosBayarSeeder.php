<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PosBayarSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nama_pos' => 'SPP', 'keterangan' => 'Sumbangan Pembinaan Pendidikan'],
            ['nama_pos' => 'PPDB', 'keterangan' => 'Penerimaan Peserta Didik Baru'],
            ['nama_pos' => 'Kegiatan Tahunan', 'keterangan' => 'Outbond, Class Meeting, dll'],
            ['nama_pos' => 'Seragam', 'keterangan' => 'Pembelian seragam siswa'],
        ];

        foreach ($data as &$d) {
            $d['aktif'] = true;
            $d['created_at'] = date('Y-m-d H:i:s');
        }

        $this->db->table('pos_bayar')->insertBatch($data);
    }
}
