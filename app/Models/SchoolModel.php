<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table = 'school_profile';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'name', 'npsn', 'nis_nss_nds', 'address', 'village', 'district', 'city_regency', 'province',
        'phone', 'email', 'website', 'tinymce_api_key',
        'logo', 'regional_logo', 'headmaster', 'principal_nip', 'principal_signature', 'level', 'status',
        'vision', 'mission', 'vision_image',
        'sambutan_title', 'sambutan_subtitle', 'sambutan_quote', 'sambutan_isi', 'sambutan_image',
        'facebook', 'instagram', 'youtube', 'tiktok', 'twitter', 'latitude', 'longitude'
    ];

    public function getProfile()
    {
        return $this->first();
    }
}
