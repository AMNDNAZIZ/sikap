<?php
namespace App\Models;

use CodeIgniter\Model;

class AttendanceModel extends Model
{
    protected $table         = 'attendances';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['student_id','class_id','date','status','note','created_by'];
    protected $useTimestamps = true;

    public function getMonthMap(int $classId, string $month): array
    {
        // $month format 'Y-m' (misal '2025-09')
        return $this->select('student_id, date, status')
            ->where('class_id', $classId)
            ->where("DATE_FORMAT(date, '%Y-%m') =", $month)
            ->findAll();
    }

    public function getAttendanceForMonth($classId, $month)
    {
        return $this->select('student_id, date, status')
            ->where('class_id', $classId)
            ->like('date', $month, 'after')
            ->findAll();
    }

    // Ambil daftar hari libur custom untuk bulan tertentu
    public function getCustomHolidays($month)
    {
        $db = db_connect();
        return $db->table('holidays')
            ->select('date, description')
            ->like('date', $month, 'after')
            ->get()
            ->getResultArray();
    }
}


