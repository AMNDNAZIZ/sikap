<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulPembinaModel extends Model
{
    protected $table            = 'ekskul_pembina';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'user_id', 'academic_year_id', 'is_external'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get list of ekskul with their assigned pembina
     */
    public function getPembinaWithDetails($academicYearId = null)
    {
        $builder = $this->select('ekskul_pembina.*, ekskul_master.name as ekskul_name, users.fullname as pembina_name, users.username')
            ->join('ekskul_master', 'ekskul_master.id = ekskul_pembina.ekskul_id')
            ->join('users', 'users.id = ekskul_pembina.user_id');
        
        if ($academicYearId) {
            $builder->where('ekskul_pembina.academic_year_id', $academicYearId);
        }
        
        return $builder->findAll();
    }
}
