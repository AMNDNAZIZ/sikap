<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulScheduleModel extends Model
{
    protected $table            = 'ekskul_schedules';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'academic_year_id', 'day_of_week', 'start_time', 'end_time', 'location'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
}
