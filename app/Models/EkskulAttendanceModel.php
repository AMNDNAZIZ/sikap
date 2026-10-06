<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulAttendanceModel extends Model
{
    protected $table            = 'ekskul_attendances';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['jurnal_id', 'user_id', 'user_type', 'status', 'notes'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get absences for a specific jurnal
     */
    public function getAbsencesByJurnal($jurnalId)
    {
        return $this->where('jurnal_id', $jurnalId)->findAll();
    }
}
