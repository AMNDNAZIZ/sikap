<?php

namespace App\Models;

use CodeIgniter\Model;

class CpMasterModel extends Model
{
    protected $table = 'cp_master';
    protected $primaryKey = 'id';
    protected $allowedFields = ['mapel_master_id', 'elemen', 'fase', 'deskripsi', 'nomor_sk', 'tahun', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
