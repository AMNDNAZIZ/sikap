<?php
namespace App\Models;

use CodeIgniter\Model;

class LandingSliderModel extends Model
{
    protected $table = 'landing_sliders';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['image', 'title', 'description', 'link', 'order', 'is_active'];
}
