<?php

namespace App\Models;

use CodeIgniter\Model;

class AcademicYearModel extends Model
{
    protected $table = 'academic_years';
    protected $primaryKey = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = ['year', 'start_date', 'end_date', 'is_active', 'formatif_weight', 'sumatif_weight', 'school_days'];

    public function getActiveYear()
    {
        // Cari berdasarkan flag is_active
        $active = $this->where('is_active', 1)->first();

        if ($active) {
            return $active;
        }

        // Jika tidak ada, fallback ke tahun ajaran terbaru
        return $this->orderBy('start_date', 'DESC')->first();
    }
}
