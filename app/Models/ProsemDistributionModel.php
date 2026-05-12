<?php

namespace App\Models;

use CodeIgniter\Model;

class ProsemDistributionModel extends Model
{
    protected $table = 'prosem_distributions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['atp_id', 'month', 'week', 'jp'];
    protected $useTimestamps = true;
}
