<?php
namespace App\Models;

use CodeIgniter\Model;

class FacilityModel extends Model
{
    protected $table = 'landing_facilities';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'description', 'image'];
}
