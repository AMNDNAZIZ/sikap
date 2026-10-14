<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulJurnalModel extends Model
{
    protected $table            = 'ekskul_jurnal';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'ekskul_id', 'academic_year_id', 'date', 'materi', 'pembina_user_id',
        'verification_status', 'verified_by', 'verified_at', 'verification_notes'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get jurnal with pembina info
     */
    public function getJurnal($ekskulId, $academicYearId = null)
    {
        $builder = $this->select('ekskul_jurnal.*, users.fullname as pembina_name')
            ->join('users', 'users.id = ekskul_jurnal.pembina_user_id')
            ->where('ekskul_jurnal.ekskul_id', $ekskulId);
            
        if ($academicYearId) {
            $builder->where('ekskul_jurnal.academic_year_id', $academicYearId);
        }
        
        return $builder->orderBy('date', 'DESC')->findAll();
    }
}
