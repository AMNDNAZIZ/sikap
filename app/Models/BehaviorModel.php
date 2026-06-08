<?php

namespace App\Models;

use CodeIgniter\Model;

class BehaviorModel extends Model
{
    protected $table      = 'behaviors';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'points', 'type'];

    public function getPositive()
    {
        return $this->where('type', 'positive')->findAll();
    }

    public function getNegative()
    {
        return $this->where('type', 'negative')->findAll();
    }
}
