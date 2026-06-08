<?php

namespace App\Models;

use CodeIgniter\Model;

class HolidayModel extends Model
{
    protected $table            = 'holidays';
    protected $primaryKey       = 'id';
    protected $allowedFields    = ['date', 'description'];
    protected $useTimestamps    = false;
    protected $returnType       = 'array';

    /**
     * Ambil daftar libur dalam rentang tanggal tertentu
     *
     * @param string $start (format Y-m-d)
     * @param string $end   (format Y-m-d)
     * @return array
     */
    public function getInRange(string $start, string $end): array
    {
        return $this->where('date >=', $start)
                    ->where('date <=', $end)
                    ->findAll();
    }
}
