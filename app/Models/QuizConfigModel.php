<?php

namespace App\Models;

use CodeIgniter\Model;

class QuizConfigModel extends Model
{
    protected $table         = 'quiz_configs';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'bank_id', 'material_id', 'title', 'description', 'class_ids',
        'show_pg_count', 'show_pgk_count', 'show_bs_count', 'show_esai_count',
        'bobot_pg', 'bobot_pgk', 'bobot_bs', 'bobot_esai',
        'shuffle_question', 'shuffle_option',
        'duration', 'max_attempts', 'show_answer',
        'is_published', 'created_by', 'teacher_id',
    ];

    // ── Daftar kuis yang tersedia untuk satu kelas + tahun ajaran ─────────
    // Berguna untuk portal siswa
    public function getForClass(int $classId): array
    {
        $db = \Config\Database::connect();
        // Ambil semua published, lalu filter PHP-side berdasarkan class_ids JSON
        $all = $db->table('quiz_configs qc')
            ->select('qc.*, qb.code as bank_code, s.name as subject_name, t.name as teacher_name')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('teachers t', 't.id = qc.teacher_id', 'left')
            ->where('qc.is_published', 1)
            ->orderBy('qc.created_at', 'DESC')
            ->get()->getResultArray();

        return array_values(array_filter($all, function ($q) use ($classId) {
            if (empty($q['class_ids'])) return true; // null = semua kelas
            $ids = json_decode($q['class_ids'], true) ?? [];
            return in_array($classId, $ids);
        }));
    }

    // ── Daftar kuis milik guru (atau semua jika admin) ─────────────────────
    public function getForTeacher(?int $teacherId): array
    {
        $db = \Config\Database::connect();
        $builder = $db->table('quiz_configs qc')
            ->select('qc.*, qb.code as bank_code, s.name as subject_name')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->orderBy('qc.created_at', 'DESC');

        if ($teacherId) {
            $builder->where('qc.teacher_id', $teacherId);
        }

        return $builder->get()->getResultArray();
    }

    // ── Ambil detail satu kuis + info bank soal + subject ─────────────────
    public function getDetail(int $id): ?array
    {
        return $this->db->table('quiz_configs qc')
            ->select('qc.*, qb.code as bank_code, qb.subject_id, qb.total_pg, qb.total_pg_kompleks, qb.total_bs, qb.total_esai, s.name as subject_name, t.name as teacher_name')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('teachers t', 't.id = qc.teacher_id', 'left')
            ->where('qc.id', $id)
            ->get()->getRowArray() ?: null;
    }
}
