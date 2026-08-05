<?php

namespace App\Models;

use CodeIgniter\Model;

class PosBayarModel extends Model
{
    protected $table = 'pos_bayar';
    protected $allowedFields = ['nama_pos', 'keterangan', 'aktif', 'created_at', 'updated_at'];
    protected $useTimestamps = true;
}
