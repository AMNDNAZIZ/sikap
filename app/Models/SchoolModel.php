<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table = 'school_profile';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['name', 'address', 'city_regency', 'phone', 'email', 'tinymce_api_key', 'logo', 'headmaster', 'principal_nip', 'level', 'vision', 'mission', 'vision_image', 'facebook', 'instagram', 'youtube', 'tiktok', 'twitter', 'latitude', 'longitude'];

    public function getProfile()
    {
        return $this->first();
    }
}
