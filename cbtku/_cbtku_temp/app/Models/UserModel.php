<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'username',
        'password',
        'active_session_id',
        'last_ip',
        'user_agent',
        'last_activity',
        'fullname',
        'role_id',
        'related_id',
        'related_type'
    ];
}
