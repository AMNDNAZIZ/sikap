<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\SubjectMaterialModel;
use App\Models\MaterialProgressModel;
use App\Models\QuizSessionModel;
use App\Models\AcademicYearModel;
use App\Models\ForumReplyModel;

/**
 * Proses Belajar — Portal Pembelajaran Terintegrasi (Refactored)
 *
 * Akses materi berdasarkan subject_material_publishes (class_id-based),
 * bukan is_published langsung.
 * Forum thread shared (class_id = NULL) — semua kelas yang dapat sub materi bisa diskusi.
 *
 * GET /siswa/belajar                  → index   : daftar mata pelajaran
 * GET /siswa/belajar/{subjectId}      → subject : hierarki Materi per mapel
 * GET /siswa/belajar/sub/{subMatId}   → show    : Sub Materi (konten + diskusi + kuis)
 * POST /siswa/belajar/sub/{id}/complete → tandai selesai (AJAX)
 * GET  /siswa/belajar/sub/{id}/file   → stream PDF
 */
class Belajar extends BaseController
{
    protected SubjectMaterialModel  $mat;
    protected MaterialProgressModel $prog;
    protected QuizSessionModel      $quizSess;
    protected AcademicYearModel     $yearModel;
    protected $db;

    public function __construct()
    {
        $this->mat       = new SubjectMaterialModel();
        $this->prog      = new MaterialProgressModel();
        $this->quizSess  = new QuizSessionModel();
        $this->yearModel = new AcademicYearModel();
        $this->db        = \Config\Database::connect();
    }

    // ── Helpers ──────────────────────────────────────────────────────────

    private function studentId(): int
    {
        $u = session()->get('user') ?? [];
        return (int)($u['student_id'] ?? $u['related_id'] ?? 0);
    }

    private function activeRecord(int $studentId): ?array
    {
        $year = $this->yearModel->getActiveYear();
        if (!$year) return null;
        return $this->db->table('student_records')
            ->where('student_id',       $studentId)
            ->where('academic_year_id', $year['id'])
            ->where('status',           'aktif')
            ->get()->getRowArray() ?: null;
    }

    /** Cek apakah mapel diajarkan di kelas siswa */
    private function canAccessSubject(int $studentId, int $subjectId): bool
    {
        $year   = $this->yearModel->getActiveYear();
        $record = $this->activeRecord($studentId);
        if (!$year || !$record) return false;
        return (bool)$this->db->table('teaching_assignments')
            ->where('class_id',         $record['class_id'])
            ->where('subject_id',       $subjectId)
            ->where('academic_year_id', $year['id'])
            ->countAllResults();
    }

    /**
     * Cek apakah sub materi dipublish ke kelas siswa ini.
     * Acuan: subject_material_publishes.class_id = class_id siswa, is_active = 1.
     */
    private function canAccessSubMat(int $studentId, int $subMatId): bool
    {
        $record = $this->activeRecord($studentId);
        if (!$record) return false;

        return (bool)$this->db->table('subject_material_publishes')
            ->where('material_id', $subMatId)
            ->where('class_id',    $record['class_id'])
            ->where('is_active',   1)
            ->countAllResults();
    }

    // =========================================================
    // INDEX — daftar mata pelajaran yang diajarkan di kelas siswa
    // =========================================================
    public function index(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId  = $this->studentId();
        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = (int)($activeYear['id'] ?? 0);
        $record     = $this->activeRecord($studentId);
        $classId    = (int)($record['class_id'] ?? 0);

        if (!$classId) {
            return redirect()->to('dashboard')->with('error', 'Kelas aktif tidak ditemukan.');
        }

        // Ambil semua mapel yang diajarkan di kelas siswa
        $subjects = $this->db->table('teaching_assignments ta')
            ->select('s.id, s.name')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id',         $classId)
            ->where('ta.academic_year_id', $yearId)
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Statistik per mapel berdasarkan publish record kelas siswa
        foreach ($subjects as &$sub) {
            $sid = $sub['id'];

            // Sub materi yang dipublish ke kelas siswa ini untuk mapel ini
            $pubSubMatIds = array_column(
                $this->db->table('subject_material_publishes smp')
                    ->select('smp.material_id')
                    ->join('subject_materials sm', 'sm.id = smp.material_id')
                    ->where('smp.class_id',   $classId)
                    ->where('smp.is_active',  1)
                    ->where('sm.subject_id',  $sid)
                    ->where('sm.year_id',     $yearId)
                    ->where('sm.is_published', 1)
                    ->get()->getResultArray(),
                'material_id'
            );

            $sub['total_sub'] = count($pubSubMatIds);

            // Berapa materi induk yang punya sub materi published
            $sub['total_parents'] = empty($pubSubMatIds) ? 0 :
                $this->db->table('subject_materials')
                    ->select('parent_id')
                    ->whereIn('id', $pubSubMatIds)
                    ->where('parent_id IS NOT NULL')
                    ->groupBy('parent_id')
                    ->countAllResults();

            // Progress
            $done = 0;
            if (!empty($pubSubMatIds)) {
                $done = $this->db->table('material_progress')
                    ->whereIn('material_id', $pubSubMatIds)
                    ->where('student_id',   $studentId)
                    ->where('status',       'completed')
                    ->countAllResults();
            }
            $sub['completed_sub'] = $done;
            $sub['progress_pct']  = $sub['total_sub'] > 0
                ? round($done / $sub['total_sub'] * 100) : 0;
        }
        unset($sub);

        return view('siswa/belajar/index', [
            'title'      => 'Proses Belajar',
            'subjects'   => $subjects,
            'activeYear' => $activeYear,
        ]);
    }

    // =========================================================
    // SUBJECT — hierarki Materi per mapel
    // =========================================================
    public function subject(int $subjectId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();

        if (!$this->canAccessSubject($studentId, $subjectId)) {
            return redirect()->to('siswa/belajar')
                ->with('error', 'Anda tidak memiliki akses ke mata pelajaran ini.');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = (int)($activeYear['id'] ?? 0);
        $record     = $this->activeRecord($studentId);
        $classId    = (int)($record['class_id'] ?? 0);
        $subject    = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        // ── Ambil sub materi yang dipublish ke kelas siswa untuk mapel ini ──
        $pubSubMatIds = array_column(
            $this->db->table('subject_material_publishes smp')
                ->select('smp.material_id')
                ->join('subject_materials sm', 'sm.id = smp.material_id')
                ->where('smp.class_id',    $classId)
                ->where('smp.is_active',   1)
                ->where('sm.subject_id',   $subjectId)
                ->where('sm.year_id',      $yearId)
                ->where('sm.is_published', 1)
                ->get()->getResultArray(),
            'material_id'
        );

        if (empty($pubSubMatIds)) {
            return view('siswa/belajar/subject', [
                'title'            => esc($subject['name'] ?? '') . ' — Proses Belajar',
                'subject'          => $subject,
                'hierarchy'        => [],
                'progressMap'      => [],
                'discussionCounts' => [],
                'quizCounts'       => [],
                'activeYear'       => $activeYear,
                'totalSub'         => 0,
                'doneSub'          => 0,
                'progressPct'      => 0,
            ]);
        }

        // Ambil sub materi + materi induknya
        $subMats = $this->db->table('subject_materials')
            ->whereIn('id', $pubSubMatIds)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id',         'ASC')
            ->get()->getResultArray();

        // Kumpulkan parent_id
        $parentIds = array_unique(array_filter(array_column($subMats, 'parent_id')));
        $parents   = [];
        if (!empty($parentIds)) {
            $pRows = $this->db->table('subject_materials')
                ->whereIn('id', $parentIds)
                ->get()->getResultArray();
            foreach ($pRows as $p) $parents[$p['id']] = $p;
        }

        // Bangun hierarki per semester/sort_order
        $allItems = array_merge(array_values($parents), $subMats);
        $hierarchy = SubjectMaterialModel::buildHierarchy($allItems);

        // Urutkan parents by semester → sort_order
        usort($hierarchy, fn($a,$b) =>
            ($a['semester'] <=> $b['semester']) ?: ($a['sort_order'] <=> $b['sort_order']) ?: ($a['id'] <=> $b['id'])
        );

        // Progress map
        $progressMap = $this->prog->getProgressMap($studentId, $pubSubMatIds);

        // Jumlah reply diskusi per sub materi
        // Forum thread shared: class_id IS NULL, related_type='material'
        $discussionCounts = [];
        $threadRows = $this->db->table('forum_threads')
            ->select('related_id, reply_count')
            ->where('related_type', 'material')
            ->whereIn('related_id', $pubSubMatIds)
            ->where('is_system',    1)
            ->where('class_id IS NULL')   // shared thread
            ->get()->getResultArray();
        foreach ($threadRows as $t) {
            $discussionCounts[(int)$t['related_id']] = (int)$t['reply_count'];
        }

        // Jumlah kuis per sub materi
        $quizCounts = [];
        $quizRows = $this->db->table('quiz_configs')
            ->select('material_id, COUNT(*) as cnt')
            ->whereIn('material_id', $pubSubMatIds)
            ->where('is_published', 1)
            ->groupBy('material_id')
            ->get()->getResultArray();
        foreach ($quizRows as $q) {
            $quizCounts[(int)$q['material_id']] = (int)$q['cnt'];
        }

        $totalSub = count($pubSubMatIds);
        $doneSub  = count(array_filter($progressMap, fn($p) => $p['status'] === 'completed'));

        return view('siswa/belajar/subject', [
            'title'            => esc($subject['name'] ?? '') . ' — Proses Belajar',
            'subject'          => $subject,
            'hierarchy'        => $hierarchy,
            'progressMap'      => $progressMap,
            'discussionCounts' => $discussionCounts,
            'quizCounts'       => $quizCounts,
            'activeYear'       => $activeYear,
            'totalSub'         => $totalSub,
            'doneSub'          => $doneSub,
            'progressPct'      => $totalSub > 0 ? round($doneSub / $totalSub * 100) : 0,
        ]);
    }

    // =========================================================
    // SHOW — Sub Materi (konten + diskusi + kuis)
    // =========================================================
    public function show(int $subMatId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $subMat    = $this->mat->find($subMatId);

        if (!$subMat || !$subMat['is_published'] || empty($subMat['parent_id'])) {
            return redirect()->to('siswa/belajar')
                ->with('error', 'Sub Materi tidak ditemukan atau belum tersedia.');
        }

        // Validasi: sub materi harus dipublish ke kelas siswa
        if (!$this->canAccessSubMat($studentId, $subMatId)) {
            return redirect()->to('siswa/belajar/' . $subMat['subject_id'])
                ->with('error', 'Sub Materi belum tersedia untuk kelas Anda.');
        }

        // Catat progress
        $this->prog->recordOpen($studentId, $subMatId);
        $progress = $this->prog->getOne($studentId, $subMatId);

        // Embed video
        if ($subMat['content_type'] === 'video' && !empty($subMat['video_url'])) {
            $subMat['embed_url'] = SubjectMaterialModel::toEmbedUrl($subMat['video_url']);
        }

        $parent  = $this->mat->find($subMat['parent_id']);
        $subject = $this->db->table('subjects')
            ->where('id', $subMat['subject_id'])->get()->getRowArray();

        $activeYear = $this->yearModel->getActiveYear();
        $record     = $this->activeRecord($studentId);
        $classId    = (int)($record['class_id'] ?? 0);

        // Navigasi prev/next — hanya sub materi yang juga dipublish ke kelas ini
        $pubSiblingIds = array_column(
            $this->db->table('subject_material_publishes smp')
                ->select('smp.material_id')
                ->join('subject_materials sm', 'sm.id = smp.material_id')
                ->where('smp.class_id',    $classId)
                ->where('smp.is_active',   1)
                ->where('sm.parent_id',    $subMat['parent_id'])
                ->where('sm.is_published', 1)
                ->orderBy('sm.sort_order', 'ASC')
                ->orderBy('sm.id',         'ASC')
                ->get()->getResultArray(),
            'material_id'
        );

        // Ambil data sibling dalam urutan yang benar
        $siblings = empty($pubSiblingIds) ? [] :
            $this->db->table('subject_materials')
                ->whereIn('id', $pubSiblingIds)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('id',         'ASC')
                ->get()->getResultArray();

        $currentIdx = array_search($subMatId, array_column($siblings, 'id'));
        $prevSub    = ($currentIdx !== false && $currentIdx > 0)
                        ? $siblings[$currentIdx - 1] : null;
        $nextSub    = ($currentIdx !== false && $currentIdx < count($siblings) - 1)
                        ? $siblings[$currentIdx + 1] : null;

        // ── DISKUSI — thread shared (class_id IS NULL) ────────────────────
        $thread    = $this->mat->getForumThread($subMatId);
        $replyTree = [];
        $threadId  = null;

        if ($thread) {
            $threadId    = (int)$thread['id'];
            $userId      = (int)(session()->get('user')['id'] ?? 0);
            $flatReplies = (new ForumReplyModel())->getByThread($threadId, $userId);
            $replyTree   = ForumReplyModel::buildTree($flatReplies);
            $this->_markThreadRead($threadId);

            // Increment view
            $this->db->query(
                'UPDATE forum_threads SET view_count = view_count + 1 WHERE id = ?',
                [$threadId]
            );
        }

        // ── KUIS ──────────────────────────────────────────────────────────
        $quizzes = $this->mat->getQuizzesForSubMaterial($subMatId, $classId);
        foreach ($quizzes as &$q) {
            $q['attempts_done']  = $this->quizSess->countFinished($q['id'], $studentId);
            $q['best_score']     = $this->quizSess->bestScore($q['id'], $studentId);
            $q['can_retry']      = ($q['max_attempts'] == 0) || ($q['attempts_done'] < $q['max_attempts']);
            $q['active_session'] = $this->quizSess->getActive($q['id'], $studentId);
        }
        unset($q);

        return view('siswa/belajar/show', [
            'title'      => esc($subMat['title']),
            'subMat'     => $subMat,
            'parent'     => $parent,
            'subject'    => $subject,
            'progress'   => $progress,
            'prevSub'    => $prevSub,
            'nextSub'    => $nextSub,
            'siblings'   => $siblings,
            'currentIdx' => (int)($currentIdx !== false ? $currentIdx : 0),
            'thread'     => $thread,
            'threadId'   => $threadId,
            'replyTree'  => $replyTree,
            'quizzes'    => $quizzes,
        ]);
    }

    // =========================================================
    // MARK COMPLETE
    // =========================================================
    public function markComplete(int $subMatId): \CodeIgniter\HTTP\Response
    {
        $studentId = $this->studentId();
        $subMat    = $this->mat->find($subMatId);

        if (!$subMat || !$subMat['is_published'] || empty($subMat['parent_id'])) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        if (!$this->canAccessSubMat($studentId, $subMatId)) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false, 'message' => 'Akses ditolak.']);
        }

        $this->prog->markCompleted($studentId, $subMatId);

        return $this->response->setJSON(['success' => true, 'message' => 'Sub Materi ditandai selesai!']);
    }

    // =========================================================
    // SERVE FILE
    // =========================================================
    public function serveFile(int $subMatId): \CodeIgniter\HTTP\Response|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $subMat    = $this->mat->find($subMatId);

        if (!$subMat || $subMat['content_type'] !== 'pdf' || empty($subMat['file_path'])) {
            return redirect()->to('siswa/belajar')->with('error', 'File tidak tersedia.');
        }

        if (!$this->canAccessSubMat($studentId, $subMatId)) {
            return redirect()->to('siswa/belajar')->with('error', 'Akses ditolak.');
        }

        $filePath = FCPATH . 'uploads/materials/' . $subMat['file_path'];
        if (!file_exists($filePath)) {
            return redirect()->to('siswa/belajar')->with('error', 'File tidak ditemukan di server.');
        }

        $this->prog->recordOpen($studentId, $subMatId);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    // ── Private ──────────────────────────────────────────────────────────

    private function _markThreadRead(int $threadId): void
    {
        $userId = (int)(session()->get('user')['id'] ?? 0);
        if (!$userId) return;
        $this->db->query(
            'INSERT INTO forum_reads (thread_id, user_id, read_at)
             VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)',
            [$threadId, $userId, date('Y-m-d H:i:s')]
        );
    }
}
