<?php

namespace App\Models;

use CodeIgniter\Model;

class MaterialProgressModel extends Model
{
    protected $table         = 'material_progress';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'student_id', 'material_id', 'status',
        'opened_at', 'completed_at', 'view_count', 'last_accessed_at',
    ];

    // ─── Catat pembukaan materi (upsert) ─────────────────────────────────
    // Dipanggil saat siswa pertama kali/kembali membuka halaman materi.
    public function recordOpen(int $studentId, int $materialId): void
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->where('student_id', $studentId)
                         ->where('material_id', $materialId)
                         ->first();

        if (!$existing) {
            $this->insert([
                'student_id'       => $studentId,
                'material_id'      => $materialId,
                'status'           => 'in_progress',
                'opened_at'        => $now,
                'view_count'       => 1,
                'last_accessed_at' => $now,
            ]);
        } else {
            // Hanya update view_count & last_accessed_at; jangan downgrade status
            $update = [
                'view_count'       => $existing['view_count'] + 1,
                'last_accessed_at' => $now,
            ];
            // Jika sebelumnya unread, naikkan ke in_progress
            if ($existing['status'] === 'unread') {
                $update['status']    = 'in_progress';
                $update['opened_at'] = $now;
            }
            $this->where('id', $existing['id'])->set($update)->update();
        }
    }

    // ─── Tandai materi selesai ────────────────────────────────────────────
    public function markCompleted(int $studentId, int $materialId): bool
    {
        $now      = date('Y-m-d H:i:s');
        $existing = $this->where('student_id', $studentId)
                         ->where('material_id', $materialId)
                         ->first();

        if (!$existing) {
            return $this->insert([
                'student_id'       => $studentId,
                'material_id'      => $materialId,
                'status'           => 'completed',
                'opened_at'        => $now,
                'completed_at'     => $now,
                'view_count'       => 1,
                'last_accessed_at' => $now,
            ]) !== false;
        }

        if ($existing['status'] === 'completed') {
            return true; // sudah selesai, tidak perlu update
        }

        return $this->where('id', $existing['id'])->set([
            'status'       => 'completed',
            'completed_at' => $now,
        ])->update();
    }

    // ─── Ambil map progress siswa: [material_id => status] ───────────────
    // Berguna untuk render badge/status di daftar materi
    public function getProgressMap(int $studentId, array $materialIds): array
    {
        if (empty($materialIds)) {
            return [];
        }

        $rows = $this->select('material_id, status, completed_at, view_count')
                     ->where('student_id', $studentId)
                     ->whereIn('material_id', $materialIds)
                     ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['material_id']] = $r;
        }
        return $map;
    }

    // ─── Progress satu siswa untuk satu materi ───────────────────────────
    public function getOne(int $studentId, int $materialId): ?array
    {
        return $this->where('student_id', $studentId)
                    ->where('material_id', $materialId)
                    ->first();
    }

    // ─── Ringkasan progress siswa untuk satu kelas & tahun ajaran ────────
    // Dipakai guru untuk dashboard monitoring per kelas
    // Return: [ material_id => [ 'completed' => N, 'in_progress' => N, 'total_students' => N ] ]
    public function getSummaryByClass(int $classId, int $academicYearId): array
    {
        $db = \Config\Database::connect();

        // Total siswa aktif di kelas ini
        $totalStudents = $db->table('student_records')
            ->where('class_id', $classId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'aktif')
            ->countAllResults();

        if (!$totalStudents) {
            return [];
        }

        // Ambil student_id di kelas ini
        $studentRows = $db->table('student_records sr')
            ->select('s.id as student_id')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $academicYearId)
            ->where('sr.status', 'aktif')
            ->get()->getResultArray();

        $studentIds = array_column($studentRows, 'student_id');

        if (empty($studentIds)) {
            return [];
        }

        // Ambil progress per materi
        $rows = $db->table('material_progress')
            ->select('material_id, status, COUNT(*) as cnt')
            ->whereIn('student_id', $studentIds)
            ->groupBy(['material_id', 'status'])
            ->get()->getResultArray();

        $summary = [];
        foreach ($rows as $r) {
            $mid = (int) $r['material_id'];
            if (!isset($summary[$mid])) {
                $summary[$mid] = [
                    'completed'     => 0,
                    'in_progress'   => 0,
                    'total_students'=> $totalStudents,
                ];
            }
            $summary[$mid][$r['status']] = (int) $r['cnt'];
        }

        return $summary;
    }

    // ─── Progress detail per siswa untuk satu materi (tabel guru) ────────
    public function getDetailByMaterial(int $materialId, int $classId, int $academicYearId): array
    {
        $db = \Config\Database::connect();

        // Semua siswa aktif di kelas
        $students = $db->table('student_records sr')
            ->select('s.id as student_id, s.name as student_name, s.nis')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $academicYearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            return [];
        }

        $studentIds = array_column($students, 'student_id');

        // Progress yang sudah ada
        $progressRows = $db->table('material_progress')
            ->select('student_id, status, opened_at, completed_at, view_count')
            ->where('material_id', $materialId)
            ->whereIn('student_id', $studentIds)
            ->get()->getResultArray();

        $progressMap = array_column($progressRows, null, 'student_id');

        // Merge
        foreach ($students as &$s) {
            $sid = $s['student_id'];
            $p   = $progressMap[$sid] ?? null;
            $s['status']       = $p['status']       ?? 'unread';
            $s['opened_at']    = $p['opened_at']    ?? null;
            $s['completed_at'] = $p['completed_at'] ?? null;
            $s['view_count']   = $p['view_count']   ?? 0;
        }
        unset($s);

        return $students;
    }

    // ─── Hitung % progress siswa (jumlah completed / total published materi) ─
    public function getStudentCompletionRate(int $studentId, array $materialIds): array
    {
        if (empty($materialIds)) {
            return ['completed' => 0, 'total' => 0, 'percent' => 0];
        }

        $completed = $this->where('student_id', $studentId)
                          ->whereIn('material_id', $materialIds)
                          ->where('status', 'completed')
                          ->countAllResults();

        $total   = count($materialIds);
        $percent = $total > 0 ? round(($completed / $total) * 100) : 0;

        return [
            'completed' => $completed,
            'total'     => $total,
            'percent'   => $percent,
        ];
    }
}
