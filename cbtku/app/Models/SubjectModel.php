<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectModel extends Model
{
    protected $table         = 'subjects';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['code', 'name', 'subject_type', 'religion', 'is_active'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
