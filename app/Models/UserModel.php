<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id';

    protected $useTimestamps = true;

    protected $allowedFields = [
        'username',
        'password',
        'fullname',
        'email',
        'role_id',
        'related_id',
        'related_type',
        'must_change_password',
        'password_changed_at',
        'is_active',
        'gemini_api_key',
        'ai_provider'
    ];
}
