<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulMemberModel extends Model
{
    protected $table            = 'ekskul_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'student_id', 'academic_year_id', 'status'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get students enrolled in a specific ekskul
     */
    public function getMembers($ekskulId, $academicYearId = null)
    {
        $builder = $this->select('ekskul_members.*, students.name as student_name, students.nis')
            ->join('students', 'students.id = ekskul_members.student_id')
            ->where('ekskul_members.ekskul_id', $ekskulId);
            
        if ($academicYearId) {
            $builder->where('ekskul_members.academic_year_id', $academicYearId);
        }
        
        return $builder->orderBy('students.name', 'ASC')->findAll();
    }
}
