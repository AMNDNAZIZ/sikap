<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table = 'teachers';
    protected $primaryKey = 'id';
    protected $useTimestamps = true; // pastikan tabel memiliki kolom created_at dan updated_at
    protected $allowedFields = ['name','username','user_id'];

    public function withUser ()
    {
        return $this->select('teachers.*, users.username')
                    ->join('users','users.id = teachers.user_id','left');
    }
}
