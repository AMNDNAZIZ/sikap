<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtExamNameModel extends Model
{
    protected $table = 'cbt_exam_names';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Optional: fungsi pencarian
    public function search($keyword = null)
    {
        if ($keyword) {
            return $this->like('name', $keyword)->findAll();
        }
        return $this->findAll();
    }
}
