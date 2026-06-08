<?php namespace App\Models;

use CodeIgniter\Model;

class MaterialScoresModel extends Model
{
    protected $table = 'material_scores';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'material_id', 'type', 'score', 
        'created_by', 'created_at', 'updated_at'
    ];
    protected $useTimestamps = true; // biar CI otomatis isi created_at & updated_at
}

