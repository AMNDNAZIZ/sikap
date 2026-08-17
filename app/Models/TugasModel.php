<?php

namespace App\Models;

use CodeIgniter\Model;

class TugasModel extends Model
{
    protected $table         = 'tugas';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'teacher_id', 'class_id', 'subject_id',
        'judul', 'deskripsi', 'mulai_at', 'selesai_at', 'created_by',
    ];

    /**
     * Status tugas berdasarkan waktu sekarang
     */
    public static function getStatus(array $tugas): string
    {
        $now = date('Y-m-d H:i:s');
        if ($now < $tugas['mulai_at'])  return 'belum';
        if ($now > $tugas['selesai_at']) return 'terlewat';
        return 'aktif';
    }

    /**
     * Daftar tugas untuk kelas tertentu dengan info submission siswa
     */
    public function getForStudent(int $classId, int $studentId): array
    {
        $db = \Config\Database::connect();
        return $db->table('tugas t')
            ->select('t.*, s.name as subject_name, ts.id as submission_id,
                      ts.dikumpul_at, ts.nilai, ts.catatan_guru, ts.jawaban')
            ->join('subjects s', 's.id = t.subject_id', 'left')
            ->join('tugas_submissions ts', "ts.tugas_id = t.id AND ts.student_id = {$studentId}", 'left')
            ->where('t.class_id', $classId)
            ->orderBy('t.selesai_at', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Daftar tugas yang diampu guru (atau semua jika admin)
     */
    public function getForTeacher(?int $teacherId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('tugas t')
            ->select('t.*, c.name as class_name, s.name as subject_name,
                      COUNT(ts.id) as total_submit,
                      SUM(CASE WHEN ts.nilai IS NOT NULL THEN 1 ELSE 0 END) as total_dinilai')
            ->join('classes c', 'c.id = t.class_id', 'left')
            ->join('subjects s', 's.id = t.subject_id', 'left')
            ->join('tugas_submissions ts', 'ts.tugas_id = t.id', 'left')
            ->groupBy('t.id')
            ->orderBy('t.created_at', 'DESC');

        if ($teacherId) {
            $builder->where('t.teacher_id', $teacherId);
        }

        return $builder->get()->getResultArray();
    }
}
