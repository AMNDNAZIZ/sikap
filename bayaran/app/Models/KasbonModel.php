<?php

namespace App\Models;

use CodeIgniter\Model;

class KasbonModel extends Model
{
    protected $table            = 'kasbon';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['tanggal', 'nama', 'jumlah', 'dibayar', 'status'];
    protected $useTimestamps    = true;
}
