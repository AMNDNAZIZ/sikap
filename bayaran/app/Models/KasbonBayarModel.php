<?php

namespace App\Models;

use CodeIgniter\Model;

class KasbonBayarModel extends Model
{
    protected $table            = 'kasbon_bayar';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['kasbon_id', 'tanggal', 'jumlah', 'keterangan'];
    protected $useTimestamps    = true;
}
