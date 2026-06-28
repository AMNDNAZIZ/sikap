<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherAttendanceModel extends Model
{
    protected $table      = 'teacher_attendances';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'schedule_id',
        'jp_ke',
        'date',
        'status',
        'keterangan',
        'recorded_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil semua record TH satu guru di bulan tertentu (per JP).
     */
    public function getAbsensiGuruByMonth(int $teacherId, string $yearMonth): array
    {
        $start = $yearMonth . '-01';
        $end   = date('Y-m-t', strtotime($start));

        return $this->db->table('teacher_attendances ta')
            ->select('ta.*, ta.jp_ke, s.day_of_week, s.start_time, s.end_time, sub.name AS subject_name, c.name AS class_name, t.name AS teacher_name, t.nip')
            ->join('schedules s', 's.id = ta.schedule_id')
            ->join('subjects sub', 'sub.id = s.subject_id', 'left')
            ->join('classes c', 'c.id = s.class_id', 'left')
            ->join('teachers t', 't.id = s.teacher_id', 'left')
            ->where('s.teacher_id', $teacherId)
            ->where('ta.status', 'TH')
            ->where('ta.date >=', $start)
            ->where('ta.date <=', $end)
            ->orderBy('ta.date', 'ASC')
            ->orderBy('ta.jp_ke', 'ASC')
            ->get()
            ->getResultArray();
    }
}
