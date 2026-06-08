<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table = 'school_profile';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'name',
        'address',
        'phone',
        'email',
        'logo',
        'headmaster',
        'level'
    ];

    public function getProfile()
    {
        return cache()->remember('school_profile', 3600, function () {
            return $this->first();
        });
    }
}
