<?php

namespace App\Models;

use CodeIgniter\Model;

class BkJournalModel extends Model
{
    protected $table            = 'bk_journals';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'student_id',
        'counselor_id',
        'counseling_type',
        'session_date',
        'problem_description',
        'diagnosis',
        'treatment',
        'follow_up',
        'is_confidential',
        'status'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
