<?php

namespace App\Models;

use CodeIgniter\Model;

class AtpElemenModel extends Model
{
    protected $table      = 'atp_elemen';
    protected $primaryKey = 'id';
    protected $allowedFields = ['atp_id', 'cp_master_id', 'urutan'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
