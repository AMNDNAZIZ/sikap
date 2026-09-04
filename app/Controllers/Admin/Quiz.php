<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\QuizConfigModel;
use App\Models\QuizSessionModel;
use App\Models\AcademicYearModel;

class Quiz extends BaseController
{
    protected QuizConfigModel  $quizModel;
    protected QuizSessionModel $sessionModel;
    protected $db;

    public function __construct()
    {
        $this->quizModel    = new QuizConfigModel();
        $this->sessionModel = new QuizSessionModel();
        $this->db           = \Config\Database::connect();
    }

    private function user(): array  { return session()->get('user') ?? []; }
    private function roleId(): int  { return (int) ($this->user()['role_id'] ?? 0); }
    private function isAdmin(): bool { return in_array($this->roleId(), [1, 2]); }

    private function teacherId(): ?int
    {
        $user = $this->user();
        if ($user['role_id'] == 3) {
            $t = $this->db->table('teachers')->where('user_id', $user['id'])->get()->getRowArray();
            return $t ? (int) $t['id'] : null;
        }
        return null;
    }

    // =========================================================
    // BANK INFO — AJAX, ambil jumlah soal live dari DB
    // GET /admin/quiz/bank-info/{bankId}
    // =========================================================
    public function bankInfo(int $bankId)
    {
        $bank = $this->db->table('cbt_question_banks qb')
            ->select('qb.id, qb.total_pg, qb.total_pg_kompleks, qb.total_bs, qb.total_esai')
            ->where('qb.id', $bankId)
            ->where('qb.is_active', 1)
            ->get()->getRowArray();

        if (!$bank) {
            return $this->response->setJSON(['success' => false]);
        }

        // Live count langsung dari tabel soal sebagai double-check
        $live = $this->db->table('cbt_questions')
            ->select("
                SUM(CASE WHEN question_type='pg'           THEN 1 ELSE 0 END) as pg,
                SUM(CASE WHEN question_type='pg_kompleks'  THEN 1 ELSE 0 END) as pgk,
                SUM(CASE WHEN question_type='benar_salah'  THEN 1 ELSE 0 END) as bs,
                SUM(CASE WHEN question_type='esai'         THEN 1 ELSE 0 END) as esai
            ")
            ->where('bank_id', $bankId)
            ->get()->getRowArray();

        // Jika live count berbeda dari cache, update cache sekaligus
        $pg   = (int)($live['pg']  ?? $bank['total_pg']);
        $pgk  = (int)($live['pgk'] ?? $bank['total_pg_kompleks']);
        $bs   = (int)($live['bs']  ?? $bank['total_bs']);
        $esai = (int)($live['esai'] ?? $bank['total_esai']);

        $cacheOutdated = ($pg   != (int)$bank['total_pg'])
                      || ($pgk  != (int)$bank['total_pg_kompleks'])
                      || ($bs   != (int)$bank['total_bs'])
                      || ($esai != (int)$bank['total_esai']);

        if ($cacheOutdated) {
            $this->db->table('cbt_question_banks')->where('id', $bankId)->update([
                'total_pg'           => $pg,
                'total_pg_kompleks'  => $pgk,
                'total_bs'           => $bs,
                'total_esai'         => $esai,
                'total_questions'    => $pg + $pgk + $bs + $esai,
            ]);
        }

        return $this->response->setJSON([
            'success' => true,
            'pg'      => $pg,
            'pgk'     => $pgk,
            'bs'      => $bs,
            'esai'    => $esai,
        ]);
    }

    // =========================================================
    // INDEX — daftar kuis milik guru / semua (admin)
    // =========================================================
    public function index()
    {
        $tid   = $this->teacherId();
        $quizzes = $this->quizModel->getForTeacher($this->isAdmin() ? null : $tid);

        // Tambah statistik ringkas per kuis
        foreach ($quizzes as &$q) {
            $q['total_attempts'] = $this->db->table('quiz_sessions')
                ->where('quiz_id', $q['id'])->where('status', 'finished')->countAllResults();
            $q['class_ids_arr'] = json_decode($q['class_ids'] ?? '[]', true) ?? [];
        }
        unset($q);

        return view('admin/quiz/index', [
            'title'   => 'Kuis Mandiri',
            'quizzes' => $quizzes,
        ]);
    }

    // =========================================================
    // CREATE — form buat kuis baru
    // =========================================================
    public function create()
    {
        $tid        = $this->teacherId();
        $banks      = $this->_getBanks($tid);
        $classes    = $this->_getActiveClasses();
        $subMats    = $this->_getSubMaterials($tid);
        $activeYear = (new AcademicYearModel())->getActiveYear();

        return view('admin/quiz/create', [
            'title'      => 'Buat Kuis Baru',
            'banks'      => $banks,
            'classes'    => $classes,
            'subMats'    => $subMats,
            'activeYear' => $activeYear,
            'quiz'       => null,
        ]);
    }

    // =========================================================
    // STORE — simpan kuis baru
    // =========================================================
    public function store()
    {
        $user = $this->user();
        $tid  = $this->teacherId();

        if (!$this->validate([
            'title'   => 'required|max_length[255]',
            'bank_id' => 'required|is_natural_no_zero',
        ])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $classIds = $this->request->getPost('class_ids') ?? [];
        $classIds = array_filter(array_map('intval', $classIds));

        $bobot      = $this->_parseBobot();
        $materialId = (int)($this->request->getPost('material_id') ?: 0) ?: null;

        $this->quizModel->insert([
            'bank_id'           => (int) $this->request->getPost('bank_id'),
            'material_id'       => $materialId,
            'title'             => $this->request->getPost('title'),
            'description'       => $this->request->getPost('description') ?: null,
            'class_ids'         => empty($classIds) ? null : json_encode(array_values($classIds)),
            'show_pg_count'     => (int) $this->request->getPost('show_pg_count'),
            'show_pgk_count'    => (int) $this->request->getPost('show_pgk_count'),
            'show_bs_count'     => (int) $this->request->getPost('show_bs_count'),
            'show_esai_count'   => (int) $this->request->getPost('show_esai_count'),
            'bobot_pg'          => $bobot['pg'],
            'bobot_pgk'         => $bobot['pgk'],
            'bobot_bs'          => $bobot['bs'],
            'bobot_esai'        => $bobot['esai'],
            'shuffle_question'  => $this->request->getPost('shuffle_question') === 'ya' ? 'ya' : 'tidak',
            'shuffle_option'    => $this->request->getPost('shuffle_option')   === 'ya' ? 'ya' : 'tidak',
            'duration'          => max(0, (int) $this->request->getPost('duration')),
            'max_attempts'      => max(0, (int) $this->request->getPost('max_attempts')),
            'show_answer'       => $this->request->getPost('show_answer') === 'ya' ? 'ya' : 'tidak',
            'is_published'      => isset($_POST['is_published']) ? 1 : 0,
            'created_by'        => $user['id'] ?? null,
            'teacher_id'        => $tid,
        ]);

        $newId = $this->quizModel->getInsertID();

        // Notifikasi ke siswa jika langsung publish
        if (isset($_POST['is_published'])) {
            $this->_notifyPublished((int) $newId, $classIds, $this->request->getPost('title'));
        }

        return redirect()->to('admin/quiz')->with('success', 'Kuis berhasil dibuat.');
    }

    // =========================================================
    // EDIT — form edit kuis
    // =========================================================
    public function edit(int $id)
    {
        $quiz = $this->quizModel->getDetail($id);
        if (!$quiz) return redirect()->to('admin/quiz')->with('error', 'Kuis tidak ditemukan.');

        // Guru hanya boleh edit miliknya
        if (!$this->isAdmin() && $quiz['teacher_id'] != $this->teacherId()) {
            return redirect()->to('admin/quiz')->with('error', 'Akses ditolak.');
        }

        $tid        = $this->teacherId();
        $banks      = $this->_getBanks($this->isAdmin() ? null : $tid);
        $classes    = $this->_getActiveClasses();
        $subMats    = $this->_getSubMaterials($this->isAdmin() ? null : $tid);
        $activeYear = (new AcademicYearModel())->getActiveYear();

        $quiz['class_ids_arr'] = json_decode($quiz['class_ids'] ?? '[]', true) ?? [];

        return view('admin/quiz/create', [
            'title'      => 'Edit Kuis',
            'banks'      => $banks,
            'classes'    => $classes,
            'subMats'    => $subMats,
            'activeYear' => $activeYear,
            'quiz'       => $quiz,
        ]);
    }

    // =========================================================
    // UPDATE — simpan perubahan
    // =========================================================
    public function update(int $id)
    {
        $quiz = $this->quizModel->find($id);
        if (!$quiz) return redirect()->to('admin/quiz')->with('error', 'Kuis tidak ditemukan.');

        if (!$this->isAdmin() && $quiz['teacher_id'] != $this->teacherId()) {
            return redirect()->to('admin/quiz')->with('error', 'Akses ditolak.');
        }

        if (!$this->validate(['title' => 'required|max_length[255]'])) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $wasPublished = (bool) ($quiz['is_published'] ?? false);
        $nowPublish   = isset($_POST['is_published']);

        $classIds = $this->request->getPost('class_ids') ?? [];
        $classIds = array_filter(array_map('intval', $classIds));

        $bobot      = $this->_parseBobot();
        $materialId = (int)($this->request->getPost('material_id') ?: 0) ?: null;

        $this->quizModel->update($id, [
            'bank_id'          => (int) $this->request->getPost('bank_id'),
            'material_id'      => $materialId,
            'title'            => $this->request->getPost('title'),
            'description'      => $this->request->getPost('description') ?: null,
            'class_ids'        => empty($classIds) ? null : json_encode(array_values($classIds)),
            'show_pg_count'    => (int) $this->request->getPost('show_pg_count'),
            'show_pgk_count'   => (int) $this->request->getPost('show_pgk_count'),
            'show_bs_count'    => (int) $this->request->getPost('show_bs_count'),
            'show_esai_count'  => (int) $this->request->getPost('show_esai_count'),
            'bobot_pg'         => $bobot['pg'],
            'bobot_pgk'        => $bobot['pgk'],
            'bobot_bs'         => $bobot['bs'],
            'bobot_esai'       => $bobot['esai'],
            'shuffle_question' => $this->request->getPost('shuffle_question') === 'ya' ? 'ya' : 'tidak',
            'shuffle_option'   => $this->request->getPost('shuffle_option')   === 'ya' ? 'ya' : 'tidak',
            'duration'         => max(0, (int) $this->request->getPost('duration')),
            'max_attempts'     => max(0, (int) $this->request->getPost('max_attempts')),
            'show_answer'      => $this->request->getPost('show_answer') === 'ya' ? 'ya' : 'tidak',
            'is_published'     => $nowPublish ? 1 : 0,
        ]);

        // Notifikasi hanya jika baru dipublish
        if ($nowPublish && !$wasPublished) {
            $this->_notifyPublished($id, $classIds, $this->request->getPost('title'));
        }

        return redirect()->to('admin/quiz')->with('success', 'Kuis berhasil diperbarui.');
    }

    // =========================================================
    // DELETE — hapus kuis (AJAX POST)
    // =========================================================
    public function delete(int $id)
    {
        $quiz = $this->quizModel->find($id);
        if (!$quiz) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kuis tidak ditemukan.']);
        }
        if (!$this->isAdmin() && $quiz['teacher_id'] != $this->teacherId()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        // Cascade delete
        $sessionIds = array_column(
            $this->db->table('quiz_sessions')->select('id')->where('quiz_id', $id)->get()->getResultArray(),
            'id'
        );
        if (!empty($sessionIds)) {
            $this->db->table('quiz_answers')->whereIn('session_id', $sessionIds)->delete();
        }
        $this->db->table('quiz_sessions')->where('quiz_id', $id)->delete();
        $this->quizModel->delete($id);

        return $this->response->setJSON(['success' => true, 'message' => 'Kuis berhasil dihapus.']);
    }

    // =========================================================
    // RESULTS — laporan hasil kuis
    // =========================================================
    public function results(int $id)
    {
        $quiz = $this->quizModel->getDetail($id);
        if (!$quiz) return redirect()->to('admin/quiz')->with('error', 'Kuis tidak ditemukan.');

        if (!$this->isAdmin() && $quiz['teacher_id'] != $this->teacherId()) {
            return redirect()->to('admin/quiz')->with('error', 'Akses ditolak.');
        }

        $summary = $this->sessionModel->getSummaryByQuiz($id);

        // Statistik agregat
        $scores      = array_filter(array_column($summary, 'best_score'), fn($s) => $s !== null);
        $avgScore    = count($scores) > 0 ? round(array_sum($scores) / count($scores), 1) : 0;
        $maxScore    = count($scores) > 0 ? max($scores) : 0;
        $minScore    = count($scores) > 0 ? min($scores) : 0;

        return view('admin/quiz/results', [
            'title'    => 'Hasil Kuis: ' . esc($quiz['title']),
            'quiz'     => $quiz,
            'summary'  => $summary,
            'avgScore' => $avgScore,
            'maxScore' => $maxScore,
            'minScore' => $minScore,
        ]);
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function _getBanks(?int $teacherId): array
    {
        $builder = $this->db->table('cbt_question_banks qb')
            ->select('qb.id, qb.code, qb.total_pg, qb.total_pg_kompleks, qb.total_bs, qb.total_esai, s.name as subject_name')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->where('qb.is_active', 1)
            ->orderBy('s.name', 'ASC')
            ->orderBy('qb.code', 'ASC');

        if ($teacherId) {
            $builder->where('qb.teacher_id', $teacherId);
        }

        return $builder->get()->getResultArray();
    }

    private function _getActiveClasses(): array
    {
        if ($this->isAdmin()) {
            // Admin/kepsek: semua kelas aktif
            return $this->db->table('classes')
                ->where('is_active', 1)
                ->orderBy('level', 'ASC')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        }

        // Guru: hanya kelas yang se-level dengan kelas yang diampu
        $tid = $this->teacherId();
        if (!$tid) return [];

        $year = (new AcademicYearModel())->getActiveYear();
        if (!$year) return [];

        // Ambil level-level yang diampu guru ini di tahun aktif
        $levels = array_column(
            $this->db->table('teaching_assignments ta')
                ->select('c.level')
                ->join('classes c', 'c.id = ta.class_id')
                ->where('ta.teacher_id', $tid)
                ->where('ta.academic_year_id', $year['id'])
                ->groupBy('c.level')
                ->orderBy('c.level', 'ASC')
                ->get()->getResultArray(),
            'level'
        );

        // Juga tambahkan level dari kelas wali (classes.teacher_id = tid)
        $waliLevels = array_column(
            $this->db->table('classes')
                ->select('level')
                ->where('teacher_id', $tid)
                ->where('is_active', 1)
                ->groupBy('level')
                ->get()->getResultArray(),
            'level'
        );

        $levels = array_unique(array_merge($levels, $waliLevels));

        if (empty($levels)) return [];

        // Semua kelas aktif yang levelnya ada di set level guru tersebut
        return $this->db->table('classes')
            ->whereIn('level', $levels)
            ->where('is_active', 1)
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * Daftar sub materi yang bisa dikaitkan dengan kuis.
     * Admin: semua sub materi published.
     * Guru: sub materi dari mapel yang dia ajar, dikelompokkan per mapel → materi induk.
     */
    private function _getSubMaterials(?int $teacherId): array
    {
        $year   = (new AcademicYearModel())->getActiveYear();
        $yearId = (int)($year['id'] ?? 0);

        $builder = $this->db->table('subject_materials sm')
            ->select('sm.id, sm.title as sub_title,
                      sm.sort_order,
                      p.title as parent_title,
                      s.id as subject_id,
                      s.name as subject_name')
            ->join('subject_materials p', 'p.id = sm.parent_id')
            ->join('subjects s',          's.id = sm.subject_id')
            ->where('sm.parent_id IS NOT NULL')
            ->where('sm.is_published', 1)
            ->where('sm.year_id', $yearId)
            ->orderBy('s.name', 'ASC')
            ->orderBy('p.sort_order', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->orderBy('sm.sort_order', 'ASC')
            ->orderBy('sm.id', 'ASC');

        if ($teacherId) {
            // Hanya sub materi dari mapel yang diajar guru ini
            $subjectIds = array_column(
                $this->db->table('teaching_assignments')
                    ->select('subject_id')->distinct()
                    ->where('teacher_id', $teacherId)
                    ->where('academic_year_id', $yearId)
                    ->get()->getResultArray(),
                'subject_id'
            );
            if (empty($subjectIds)) return [];
            $builder->whereIn('sm.subject_id', $subjectIds);
        }

        return $builder->get()->getResultArray();
    }

    private function _parseBobot(): array
    {
        return [
            'pg'   => max(0, min(100, (int) $this->request->getPost('bobot_pg'))),
            'pgk'  => max(0, min(100, (int) $this->request->getPost('bobot_pgk'))),
            'bs'   => max(0, min(100, (int) $this->request->getPost('bobot_bs'))),
            'esai' => max(0, min(100, (int) $this->request->getPost('bobot_esai'))),
        ];
    }

    private function _notifyPublished(int $quizId, array $classIds, string $title): void
    {
        try {
            // Tentukan URL tujuan notifikasi berdasarkan apakah kuis dikaitkan ke sub materi
            $quiz = $this->quizModel->find($quizId);
            $notifUrl = !empty($quiz['material_id'])
                ? base_url('siswa/belajar/sub/' . $quiz['material_id'] . '#kuis')
                : base_url('siswa/quiz');   // tidak ada halaman detail per-kuis, arahkan ke daftar

            if (!empty($classIds)) {
                notify_classes($classIds, 'materi_baru',
                    'Kuis Baru: ' . $title,
                    'Kuis latihan mandiri tersedia. Silakan kerjakan kapan saja.',
                    $notifUrl
                );
            } else {
                // Publish ke semua siswa aktif tahun ini
                $activeYear = (new AcademicYearModel())->getActiveYear();
                if ($activeYear) {
                    $userIds = $this->db->table('student_records sr')
                        ->select('u.id as user_id')
                        ->join('students st', 'st.id = sr.student_id')
                        ->join('users u', 'u.id = st.user_id', 'left')
                        ->where('sr.academic_year_id', $activeYear['id'])
                        ->where('sr.status', 'aktif')
                        ->where('u.id IS NOT NULL')
                        ->get()->getResultArray();
                    $ids = array_column($userIds, 'user_id');
                    if (!empty($ids)) {
                        notify_users($ids, 'materi_baru',
                            'Kuis Baru: ' . $title,
                            'Kuis latihan mandiri tersedia.',
                            $notifUrl
                        );
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Quiz] Notifikasi gagal: ' . $e->getMessage());
        }
    }
}
