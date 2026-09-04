<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\SubjectMaterialModel;
use App\Models\MaterialProgressModel;
use App\Models\QuizSessionModel;

/**
 * LearningAnalytics
 *
 * Dashboard analitik pembelajaran terpadu — menggabungkan:
 *   - Progress membaca materi
 *   - Hasil kuis mandiri
 *   - Nilai (formatif, sumatif, erapor)
 *   - Kehadiran
 *
 * Route prefix: /learning
 * Diakses semua role yang sudah login (filter 'auth').
 *
 * GET /learning             → siswa: profil belajar diri sendiri
 * GET /learning/class       → guru/admin: pilih kelas
 * GET /learning/class/{id}  → guru/admin: monitoring kelas
 * GET /learning/student/{id}→ guru/admin: detail satu siswa
 */
class LearningAnalytics extends BaseController
{
    protected AcademicYearModel     $yearModel;
    protected SubjectMaterialModel  $materialModel;
    protected MaterialProgressModel $progressModel;
    protected QuizSessionModel      $quizSessionModel;
    protected $db;

    public function __construct()
    {
        $this->yearModel        = new AcademicYearModel();
        $this->materialModel    = new SubjectMaterialModel();
        $this->progressModel    = new MaterialProgressModel();
        $this->quizSessionModel = new QuizSessionModel();
        $this->db               = \Config\Database::connect();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────
    private function user(): array  { return session()->get('user') ?? []; }
    private function roleId(): int  { return (int) ($this->user()['role_id'] ?? 0); }
    private function studentId(): int
    {
        $u = $this->user();
        return (int) ($u['student_id'] ?? $u['related_id'] ?? 0);
    }

    // =========================================================
    // SISWA — profil belajar diri sendiri
    // GET /learning
    // =========================================================
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $roleId    = $this->roleId();
        $activeYear = $this->yearModel->getActiveYear();

        // Guru/admin: redirect ke halaman kelas
        if (in_array($roleId, [1, 2, 3, 7])) {
            return redirect()->to('learning/class');
        }

        $studentId = $this->studentId();
        if (!$studentId || !$activeYear) {
            return redirect()->to('dashboard')->with('error', 'Data tidak ditemukan.');
        }

        $yearId = (int) $activeYear['id'];
        $data   = $this->_buildStudentData($studentId, $yearId);

        return view('siswa/learning/index', array_merge($data, [
            'title'      => 'Analitik Belajarku',
            'activeYear' => $activeYear,
        ]));
    }

    // =========================================================
    // GURU — pilih kelas
    // GET /learning/class
    // =========================================================
    public function classList(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $roleId = $this->roleId();
        if (!in_array($roleId, [1, 2, 3, 7])) {
            return redirect()->to('learning');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = (int) ($activeYear['id'] ?? 0);

        $classes = $this->_getAccessibleClasses($yearId);

        return view('admin/learning/class_list', [
            'title'      => 'Monitoring Kelas — Analitik Pembelajaran',
            'classes'    => $classes,
            'activeYear' => $activeYear,
        ]);
    }

    // =========================================================
    // GURU — monitoring satu kelas
    // GET /learning/class/{classId}
    // =========================================================
    public function classDetail(int $classId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $roleId = $this->roleId();
        if (!in_array($roleId, [1, 2, 3, 7])) {
            return redirect()->to('learning');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = (int) ($activeYear['id'] ?? 0);

        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        if (!$class) {
            return redirect()->to('learning/class')->with('error', 'Kelas tidak ditemukan.');
        }

        // Daftar siswa aktif di kelas ini
        $students = $this->db->table('student_records sr')
            ->select('s.id as student_id, s.name as student_name, s.nis, u.id as user_id')
            ->join('students s', 's.id = sr.student_id')
            ->join('users u', 'u.id = s.user_id', 'left')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            return view('admin/learning/class', [
                'title'      => 'Monitoring Kelas: ' . esc($class['name']),
                'class'      => $class,
                'students'   => [],
                'matrix'     => [],
                'subjects'   => [],
                'activeYear' => $activeYear,
            ]);
        }

        $studentIds = array_column($students, 'student_id');

        // ── Mapel yang diajarkan di kelas ini ─────────────────────────
        $subjects = $this->db->table('teaching_assignments ta')
            ->select('s.id, s.name')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $classId)
            ->where('ta.academic_year_id', $yearId)
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $subjectIds = array_column($subjects, 'id');

        // ── Materi published per mapel ────────────────────────────────
        $materials = [];
        if (!empty($subjectIds)) {
            $matRows = $this->db->table('subject_materials')
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('is_published', 1)
                ->get()->getResultArray();
            foreach ($matRows as $m) {
                $materials[$m['subject_id']][] = $m;
            }
        }

        // ── Progress materi per siswa ─────────────────────────────────
        $allMatIds = [];
        foreach ($materials as $mList) {
            foreach ($mList as $m) $allMatIds[] = $m['id'];
        }

        $progressRows = [];
        if (!empty($allMatIds) && !empty($studentIds)) {
            $rows = $this->db->table('material_progress')
                ->whereIn('student_id', $studentIds)
                ->whereIn('material_id', $allMatIds)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $progressRows[$r['student_id']][$r['material_id']] = $r['status'];
            }
        }

        // ── Hasil kuis terbaik per siswa per mapel ────────────────────
        $quizRows = [];
        if (!empty($subjectIds) && !empty($studentIds)) {
            $rows = $this->db->table('quiz_sessions qs')
                ->select('qs.student_id, qb.subject_id, MAX(qs.total_score) as best_score, COUNT(qs.id) as attempts')
                ->join('quiz_configs qc', 'qc.id = qs.quiz_id')
                ->join('cbt_question_banks qb', 'qb.id = qc.bank_id')
                ->whereIn('qs.student_id', $studentIds)
                ->whereIn('qb.subject_id', $subjectIds)
                ->where('qs.status', 'finished')
                ->groupBy(['qs.student_id', 'qb.subject_id'])
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $quizRows[$r['student_id']][$r['subject_id']] = [
                    'best_score' => (float) $r['best_score'],
                    'attempts'   => (int) $r['attempts'],
                ];
            }
        }

        // ── Absensi bulan ini per siswa ───────────────────────────────
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-d');
        $attRows    = [];
        if (!empty($studentIds)) {
            $rows = $this->db->table('attendances')
                ->select('student_id, status, COUNT(*) as cnt')
                ->whereIn('student_id', $studentIds)
                ->where('date >=', $monthStart)
                ->where('date <=', $monthEnd)
                ->groupBy(['student_id', 'status'])
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $attRows[$r['student_id']][$r['status']] = (int) $r['cnt'];
            }
        }

        // ── Nilai erapor per siswa per mapel (semester aktif) ─────────
        $eRaporRows = [];
        $semesterNow = date('n') <= 6 ? 'ganjil' : 'genap';
        if (!empty($studentIds) && !empty($subjectIds)) {
            $rows = $this->db->table('grades')
                ->select('student_id, subject_id, erapor_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semesterNow)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $eRaporRows[$r['student_id']][$r['subject_id']] = (float) $r['erapor_score'];
            }
        }

        // ── Bangun matrix per siswa ───────────────────────────────────
        $matrix = [];
        foreach ($students as $s) {
            $sid       = $s['student_id'];
            $att       = $attRows[$sid] ?? [];
            $hadir     = $att['H'] ?? 0;
            $total_att = array_sum($att);
            $attRate   = $total_att > 0 ? round($hadir / $total_att * 100) : 100;

            $subjectData = [];
            foreach ($subjects as $sub) {
                $subId     = $sub['id'];
                $matList   = $materials[$subId] ?? [];
                $totalMat  = count($matList);
                $doneMat   = 0;
                if ($totalMat > 0) {
                    foreach ($matList as $m) {
                        if (($progressRows[$sid][$m['id']] ?? '') === 'completed') $doneMat++;
                    }
                }
                $quiz = $quizRows[$sid][$subId] ?? null;

                $subjectData[$subId] = [
                    'materials_done'  => $doneMat,
                    'materials_total' => $totalMat,
                    'materials_pct'   => $totalMat > 0 ? round($doneMat / $totalMat * 100) : null,
                    'quiz_best'       => $quiz ? $quiz['best_score'] : null,
                    'quiz_attempts'   => $quiz ? $quiz['attempts'] : 0,
                    'erapor'          => $eRaporRows[$sid][$subId] ?? null,
                ];
            }

            // Skor agregat untuk sorting
            $matScores = array_filter(array_column($subjectData, 'materials_pct'), fn($v) => $v !== null);
            $quizScores= array_filter(array_column($subjectData, 'quiz_best'), fn($v) => $v !== null);

            $matrix[$sid] = [
                'info'          => $s,
                'subjects'      => $subjectData,
                'att_rate'      => $attRate,
                'att_hadir'     => $hadir,
                'att_sakit'     => $att['S'] ?? 0,
                'att_izin'      => $att['I'] ?? 0,
                'att_alpha'     => $att['A'] ?? 0,
                'avg_mat_pct'   => count($matScores) > 0 ? round(array_sum($matScores) / count($matScores)) : null,
                'avg_quiz'      => count($quizScores) > 0 ? round(array_sum($quizScores) / count($quizScores), 1) : null,
            ];
        }

        return view('admin/learning/class', [
            'title'      => 'Monitoring: ' . esc($class['name']),
            'class'      => $class,
            'students'   => $students,
            'subjects'   => $subjects,
            'matrix'     => $matrix,
            'materials'  => $materials,
            'activeYear' => $activeYear,
            'monthLabel' => date('F Y'),
        ]);
    }

    // =========================================================
    // GURU — detail satu siswa
    // GET /learning/student/{studentId}
    // =========================================================
    public function studentDetail(int $studentId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $roleId = $this->roleId();
        if (!in_array($roleId, [1, 2, 3, 7])) {
            return redirect()->to('learning');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = (int) ($activeYear['id'] ?? 0);
        $data       = $this->_buildStudentData($studentId, $yearId);

        $student = $this->db->table('students')->where('id', $studentId)->get()->getRowArray();
        if (!$student) {
            return redirect()->to('learning/class')->with('error', 'Siswa tidak ditemukan.');
        }

        return view('siswa/learning/index', array_merge($data, [
            'title'        => 'Analitik: ' . esc($student['name']),
            'activeYear'   => $activeYear,
            'viewAsTeacher'=> true,
            'studentInfo'  => $student,
        ]));
    }

    // ─── Private: data lengkap satu siswa ────────────────────────────────
    private function _buildStudentData(int $studentId, int $yearId): array
    {
        // Kelas aktif siswa
        $record = $this->db->table('student_records sr')
            ->select('sr.class_id, c.name as class_name')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->where('sr.student_id', $studentId)
            ->where('sr.academic_year_id', $yearId)
            ->where('sr.status', 'aktif')
            ->get()->getRowArray();

        $classId   = (int) ($record['class_id']  ?? 0);
        $className = $record['class_name'] ?? '—';

        // ── Mapel yang diajarkan di kelas ────────────────────────────
        $subjects = [];
        if ($classId) {
            $subjects = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $classId)
                ->where('ta.academic_year_id', $yearId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        $subjectIds = array_column($subjects, 'id');

        // ── Progress materi per mapel ─────────────────────────────────
        $materialsData = [];
        foreach ($subjects as $sub) {
            $sid    = $sub['id'];
            $mats   = $this->db->table('subject_materials')
                ->where('subject_id', $sid)
                ->where('year_id', $yearId)
                ->where('is_published', 1)
                ->orderBy('sort_order', 'ASC')
                ->get()->getResultArray();

            $matIds  = array_column($mats, 'id');
            $done    = 0;
            $reading = 0;
            $progMap = [];
            if (!empty($matIds)) {
                $rows = $this->db->table('material_progress')
                    ->whereIn('material_id', $matIds)
                    ->where('student_id', $studentId)
                    ->get()->getResultArray();
                $progMap = array_column($rows, null, 'material_id');
                foreach ($rows as $r) {
                    if ($r['status'] === 'completed')   $done++;
                    if ($r['status'] === 'in_progress') $reading++;
                }
            }

            $materialsData[$sid] = [
                'subject_name'  => $sub['name'],
                'total'         => count($mats),
                'completed'     => $done,
                'in_progress'   => $reading,
                'unread'        => max(0, count($mats) - $done - $reading),
                'pct'           => count($mats) > 0 ? round($done / count($mats) * 100) : null,
                'items'         => $mats,
                'progress_map'  => $progMap,
            ];
        }

        // ── Hasil kuis per mapel ─────────────────────────────────────
        $quizData = [];
        if (!empty($subjectIds)) {
            $rows = $this->db->table('quiz_sessions qs')
                ->select('qs.quiz_id, qs.total_score, qs.attempt_number, qs.finished_at,
                          qc.title as quiz_title, qb.subject_id')
                ->join('quiz_configs qc', 'qc.id = qs.quiz_id')
                ->join('cbt_question_banks qb', 'qb.id = qc.bank_id')
                ->whereIn('qb.subject_id', $subjectIds)
                ->where('qs.student_id', $studentId)
                ->where('qs.status', 'finished')
                ->orderBy('qs.finished_at', 'DESC')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $quizData[$r['subject_id']][] = $r;
            }
        }

        // Best + average per subject
        $quizSummary = [];
        foreach ($quizData as $subId => $attempts) {
            $scores = array_column($attempts, 'total_score');
            $quizSummary[$subId] = [
                'best'     => count($scores) > 0 ? max($scores) : null,
                'avg'      => count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : null,
                'attempts' => count($attempts),
                'history'  => array_slice($attempts, 0, 5),  // 5 terbaru
            ];
        }

        // ── Nilai erapor per mapel ───────────────────────────────────
        $semesterNow = date('n') <= 6 ? 'ganjil' : 'genap';
        $eraporData  = [];
        if (!empty($subjectIds)) {
            $rows = $this->db->table('grades')
                ->select('subject_id, erapor_score, report_score')
                ->whereIn('subject_id', $subjectIds)
                ->where('student_id', $studentId)
                ->where('year_id', $yearId)
                ->where('semester', $semesterNow)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $eraporData[$r['subject_id']] = $r;
            }
        }

        // ── Absensi bulan ini ────────────────────────────────────────
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-d');
        $attRows    = $this->db->table('attendances')
            ->select('status, COUNT(*) as cnt')
            ->where('student_id', $studentId)
            ->where('date >=', $monthStart)
            ->where('date <=', $monthEnd)
            ->groupBy('status')
            ->get()->getResultArray();

        $attMap   = array_column($attRows, 'cnt', 'status');
        $hadir    = (int) ($attMap['H'] ?? 0);
        $totalAtt = array_sum($attMap);
        $attRate  = $totalAtt > 0 ? round($hadir / $totalAtt * 100) : 100;

        // Trend nilai kuis: 10 sesi terakhir semua mapel
        $quizTrend = $this->db->table('quiz_sessions qs')
            ->select('qs.total_score, qs.finished_at, qc.title as quiz_title, s.name as subject_name')
            ->join('quiz_configs qc', 'qc.id = qs.quiz_id')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id')
            ->join('subjects s', 's.id = qb.subject_id')
            ->where('qs.student_id', $studentId)
            ->where('qs.status', 'finished')
            ->orderBy('qs.finished_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();
        $quizTrend = array_reverse($quizTrend);  // kronologis

        // Skor agregat keseluruhan
        $allMatPcts  = array_filter(array_column($materialsData, 'pct'), fn($v) => $v !== null);
        $allQuizBest = array_filter(array_column($quizSummary, 'best'),  fn($v) => $v !== null);
        $allErapor   = array_filter(array_map(
            fn($e) => isset($e['erapor_score']) ? (float) $e['erapor_score'] : null,
            $eraporData
        ), fn($v) => $v !== null);

        $overallStats = [
            'mat_avg_pct'  => count($allMatPcts)  > 0 ? round(array_sum($allMatPcts)  / count($allMatPcts))  : null,
            'quiz_avg'     => count($allQuizBest)  > 0 ? round(array_sum($allQuizBest) / count($allQuizBest), 1) : null,
            'erapor_avg'   => count($allErapor)    > 0 ? round(array_sum($allErapor)   / count($allErapor), 1)   : null,
            'att_rate'     => $attRate,
            'att_hadir'    => $hadir,
            'att_sakit'    => (int) ($attMap['S'] ?? 0),
            'att_izin'     => (int) ($attMap['I'] ?? 0),
            'att_alpha'    => (int) ($attMap['A'] ?? 0),
        ];

        return [
            'studentId'    => $studentId,
            'classId'      => $classId,
            'className'    => $className,
            'subjects'     => $subjects,
            'materialsData'=> $materialsData,
            'quizSummary'  => $quizSummary,
            'quizTrend'    => $quizTrend,
            'eraporData'   => $eraporData,
            'overallStats' => $overallStats,
            'monthLabel'   => date('F Y'),
        ];
    }

    // ─── Private: kelas yang bisa diakses guru/admin ──────────────────────
    private function _getAccessibleClasses(int $yearId): array
    {
        $roleId = $this->roleId();
        $user   = $this->user();

        if (in_array($roleId, [1, 2, 7])) {
            // Admin/Kepsek/Staf: semua kelas aktif
            return $this->db->table('classes c')
                ->select('c.*, t.name as teacher_name,
                          COUNT(sr.id) as total_students')
                ->join('teachers t', 't.id = c.teacher_id', 'left')
                ->join('student_records sr',
                       "sr.class_id = c.id AND sr.academic_year_id = {$yearId} AND sr.status = 'aktif'",
                       'left')
                ->where('c.is_active', 1)
                ->groupBy('c.id')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray();
        }

        // Guru: kelas yang diajarkan + kelas wali
        $teacherRow = $this->db->table('teachers')
            ->where('user_id', $user['id'])->get()->getRowArray();
        if (!$teacherRow) return [];

        $classIds = [];

        // Kelas yang diajarkan
        $taught = $this->db->table('teaching_assignments')
            ->select('class_id')->distinct()
            ->where('teacher_id', $teacherRow['id'])
            ->where('academic_year_id', $yearId)
            ->get()->getResultArray();
        foreach ($taught as $t) $classIds[] = $t['class_id'];

        // Kelas wali
        $wali = $this->db->table('classes')
            ->select('id')->where('teacher_id', $teacherRow['id'])->get()->getRowArray();
        if ($wali) $classIds[] = $wali['id'];

        $classIds = array_unique($classIds);
        if (empty($classIds)) return [];

        return $this->db->table('classes c')
            ->select('c.*, t.name as teacher_name, COUNT(sr.id) as total_students')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->join('student_records sr',
                   "sr.class_id = c.id AND sr.academic_year_id = {$yearId} AND sr.status = 'aktif'",
                   'left')
            ->whereIn('c.id', $classIds)
            ->groupBy('c.id')
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();
    }
}
