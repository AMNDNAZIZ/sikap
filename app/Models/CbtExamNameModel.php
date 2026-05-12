<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtExamNameModel extends Model
{
    protected $table = 'cbt_exam_names';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'created_by'];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get exam names filtered by user context
     * 
     * @param int|null $userId User ID for filtering (null = all)
     * @param bool $includeShared Include shared/legacy exam names (created_by = null)
     * @return array
     */
    public function getFiltered(?int $userId = null, bool $includeShared = true): array
    {
        $builder = $this->builder();
        
        if ($userId !== null) {
            if ($includeShared) {
                $builder->groupStart()
                    ->where('created_by', $userId)
                    ->orWhere('created_by', null)
                    ->groupEnd();
            } else {
                $builder->where('created_by', $userId);
            }
        }
        
        return $builder->orderBy('name', 'ASC')->get()->getResultArray();
    }

    // Optional: fungsi pencarian
    public function search($keyword = null, ?int $userId = null)
    {
        $builder = $this->builder();
        
        if ($keyword) {
            $builder->like('name', $keyword);
        }
        
        if ($userId !== null) {
            $builder->groupStart()
                ->where('created_by', $userId)
                ->orWhere('created_by', null)
                ->groupEnd();
        }
        
        return $builder->findAll();
    }
}
