<?php

namespace App\Models;

use CodeIgniter\Model;

class QuizSessionModel extends Model
{
    protected $table         = 'quiz_sessions';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'quiz_id', 'student_id', 'question_order', 'option_orders',
        'status', 'started_at', 'finished_at',
        'score', 'total_score', 'attempt_number',
    ];

    // ── Hitung attempt berikutnya untuk siswa ini ──────────────────────────
    public function nextAttemptNumber(int $quizId, int $studentId): int
    {
        $max = $this->where('quiz_id', $quizId)
                    ->where('student_id', $studentId)
                    ->selectMax('attempt_number')
                    ->first();
        return (int) ($max['attempt_number'] ?? 0) + 1;
    }

    // ── Ambil sesi aktif (status=active) milik siswa untuk kuis ini ───────
    public function getActive(int $quizId, int $studentId): ?array
    {
        return $this->where('quiz_id', $quizId)
                    ->where('student_id', $studentId)
                    ->where('status', 'active')
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    // ── Ambil semua sesi (riwayat) milik siswa untuk kuis ini ─────────────
    public function getHistory(int $quizId, int $studentId): array
    {
        return $this->where('quiz_id', $quizId)
                    ->where('student_id', $studentId)
                    ->orderBy('attempt_number', 'DESC')
                    ->findAll();
    }

    // ── Jumlah attempt yang sudah selesai ─────────────────────────────────
    public function countFinished(int $quizId, int $studentId): int
    {
        return $this->where('quiz_id', $quizId)
                    ->where('student_id', $studentId)
                    ->where('status', 'finished')
                    ->countAllResults();
    }

    // ── Nilai terbaik siswa untuk kuis ini ────────────────────────────────
    public function bestScore(int $quizId, int $studentId): ?float
    {
        $row = $this->where('quiz_id', $quizId)
                    ->where('student_id', $studentId)
                    ->where('status', 'finished')
                    ->selectMax('total_score')
                    ->first();
        return isset($row['total_score']) ? (float) $row['total_score'] : null;
    }

    // ── Ringkasan per kuis (untuk laporan guru): jumlah attempt + avg score ─
    public function getSummaryByQuiz(int $quizId): array
    {
        $db = \Config\Database::connect();
        return $db->table('quiz_sessions qs')
            ->select('s.id as student_id, s.name as student_name, s.nis,
                      COUNT(qs.id) as total_attempts,
                      MAX(qs.total_score) as best_score,
                      AVG(qs.total_score) as avg_score,
                      MAX(qs.finished_at) as last_attempt_at')
            ->join('students s', 's.id = qs.student_id', 'left')
            ->where('qs.quiz_id', $quizId)
            ->where('qs.status', 'finished')
            ->groupBy('qs.student_id')
            ->orderBy('best_score', 'DESC')
            ->get()->getResultArray();
    }
}
