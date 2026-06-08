<?php

namespace App\Models;

use CodeIgniter\Model;

class JenjangMasterModel extends Model
{
    protected $table = 'jenjang_master';
    protected $primaryKey = 'id';
    protected $allowedFields = ['kode', 'nama'];
    protected $useTimestamps = false;
}
