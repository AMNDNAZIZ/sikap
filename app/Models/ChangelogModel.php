<?php

namespace App\Models;

use CodeIgniter\Model;

class ChangelogModel extends Model
{
    protected $table = 'changelogs';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'version',
        'release_date',
        'description',
        'is_stable'
    ];
}
