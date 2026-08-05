<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['username', 'password_hash', 'nama', 'role', 'aktif'];
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';

    protected $beforeInsert = ['hashPassword'];
    protected $beforeUpdate = ['hashPassword'];

    protected function hashPassword(array $data)
    {
        if (!empty($data['data']['password_hash'])) {
            $data['data']['password_hash'] = password_hash($data['data']['password_hash'], PASSWORD_BCRYPT);
        } else {
            unset($data['data']['password_hash']);
        }

        return $data;
    }
}
