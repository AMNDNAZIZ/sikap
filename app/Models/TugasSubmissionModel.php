<?php

namespace App\Models;

use CodeIgniter\Model;

class TugasSubmissionModel extends Model
{
    protected $table         = 'tugas_submissions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'tugas_id', 'student_id', 'jawaban', 'dikumpul_at',
        'nilai', 'catatan_guru', 'dinilai_at', 'dinilai_oleh',
    ];

    /**
     * Semua submission untuk satu tugas, lengkap dengan data siswa
     */
    public function getByTugas(int $tugasId): array
    {
        return $this->select('tugas_submissions.*, students.name as student_name, students.nis')
            ->join('students', 'students.id = tugas_submissions.student_id', 'left')
            ->where('tugas_id', $tugasId)
            ->orderBy('students.name', 'ASC')
            ->findAll();
    }

    /**
     * Submission milik satu siswa untuk satu tugas
     */
    public function getByTugasAndStudent(int $tugasId, int $studentId): ?array
    {
        return $this->where('tugas_id', $tugasId)
            ->where('student_id', $studentId)
            ->first();
    }
}
