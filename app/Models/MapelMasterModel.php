<?php

namespace App\Models;

use CodeIgniter\Model;

class MapelMasterModel extends Model
{
    protected $table = 'mapel_master';
    protected $primaryKey = 'id';
    protected $allowedFields = ['jenjang_id', 'kode', 'nama', 'kelompok', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
