<?php

namespace App\Models;

use CodeIgniter\Model;

class LandingLinkModel extends Model
{
    protected $table = 'landing_links';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = ['title', 'url', 'icon', 'description', 'is_active', 'order_no'];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
}
