<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TruncateLicense extends Seeder
{
    public function run()
    {
        $this->db->table('app_license')->truncate();
    }
}
