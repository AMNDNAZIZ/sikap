<?php

namespace App\Models;

use CodeIgniter\Model;

class LicenseModel extends Model
{
    protected $table = 'app_license';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['license_key', 'domain', 'machine_id', 'status', 'last_check', 'expires_at', 'hash'];

    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function getActiveLicense()
    {
        return $this->where('status', 'active')->first();
    }
}
