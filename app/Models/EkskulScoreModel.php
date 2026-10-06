<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulScoreModel extends Model
{
    protected $table            = 'ekskul_scores';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'student_id', 'academic_year_id', 'semester', 'predicate', 'description'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
