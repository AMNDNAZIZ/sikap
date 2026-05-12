<?php

namespace App\Models;

use CodeIgniter\Model;

class ModulAjarModel extends Model
{
    protected $table = 'modul_ajar';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'atp_id',
        'subject_id',
        'class_id',
        'teacher_id',
        'content',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
