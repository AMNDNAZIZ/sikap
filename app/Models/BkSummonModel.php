<?php

namespace App\Models;

use CodeIgniter\Model;

class BkSummonModel extends Model
{
    protected $table            = 'bk_summons';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    
    // Status: Pending, Attended, Ignored
    protected $allowedFields    = [
        'student_id', 'issue_date', 'summon_date', 'reason', 'level', 'status', 'created_by'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    public function getSummonsWithDetails()
    {
        return $this->select('bk_summons.*, students.name as student_name, students.nis, classes.name as class_name, users.fullname as creator_name')
                    ->join('students', 'students.id = bk_summons.student_id')
                    ->join('classes', 'classes.id = students.class_id', 'left')
                    ->join('users', 'users.id = bk_summons.created_by', 'left')
                    ->orderBy('bk_summons.created_at', 'DESC')
                    ->findAll();
    }
}
