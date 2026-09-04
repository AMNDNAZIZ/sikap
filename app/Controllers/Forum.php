<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\ForumThreadModel;
use App\Models\ForumReplyModel;
use App\Models\AcademicYearModel;

/**
 * Forum Diskusi per Topik
 *
 * Satu controller untuk semua role (filter per-role dilakukan di sini).
 * Guru/admin bisa: pin, lock, delete reply, set best answer.
 * Siswa/ortu bisa: buat thread (jika tidak terkunci), reply, upvote.
 *
 * Routes:
 *   GET  /forum                           → pilih kelas+mapel (redirect)
 *   GET  /forum/{classId}/{subjectId}     → daftar thread
 *   GET  /forum/thread/{id}               → detail thread + replies
 *   POST /forum/{classId}/{subjectId}/store   → buat thread baru
 *   POST /forum/thread/{id}/reply             → balas thread
 *   POST /forum/thread/{id}/pin               → pin/unpin (guru/admin)
 *   POST /forum/thread/{id}/lock              → lock/unlock (guru/admin)
 *   POST /forum/thread/{id}/delete            → hapus thread (owner/guru/admin)
 *   POST /forum/reply/{id}/delete             → hapus reply (soft-delete)
 *   POST /forum/reply/{id}/upvote             → toggle upvote (AJAX)
 *   POST /forum/reply/{id}/best               → set best answer (guru/admin)
 */
class Forum extends BaseController
{
    protected ForumThreadModel $threadModel;
    protected ForumReplyModel  $replyModel;
    protected AcademicYearModel $yearModel;
    protected $db;

    public function __construct()
    {
        $this->threadModel = new ForumThreadModel();
        $this->replyModel  = new ForumReplyModel();
        $this->yearModel   = new AcademicYearModel();
        $this->db          = \Config\Database::connect();
    }

    // ─── Helpers ─────────────────────────────────────────────────────────
    private function user(): array  { return session()->get('user') ?? []; }
    private function userId(): int  { return (int) ($this->user()['id'] ?? 0); }
    private function roleId(): int  { return (int) ($this->user()['role_id'] ?? 0); }
    private function studentId(): int
    {
        $u = $this->user();
        return (int) ($u['student_id'] ?? $u['related_id'] ?? 0);
    }

    private function isModRole(): bool
    {
        return in_array($this->roleId(), [1, 2, 3, 7]);
    }

    private function activeYear(): array
    {
        return $this->yearModel->getActiveYear() ?? [];
    }

    // Ambil class_id aktif siswa
    private function studentClassId(): int
    {
        $year = $this->activeYear();
        if (!$year) return 0;
        $rec = $this->db->table('student_records')
            ->where('student_id', $this->studentId())
            ->where('academic_year_id', $year['id'])
            ->where('status', 'aktif')
            ->get()->getRowArray();
        return (int) ($rec['class_id'] ?? 0);
    }

    // Cek apakah user boleh akses class+subject ini
    private function canAccess(int $classId, int $subjectId): bool
    {
        $role = $this->roleId();
        $year = $this->activeYear();
        if (!$year) return false;

        if (in_array($role, [1, 2, 7])) return true;   // admin/kepsek/staf: semua

        if ($role == 3) {
            // Guru: cek teaching_assignments
            $teacher = $this->db->table('teachers')
                ->where('user_id', $this->userId())->get()->getRowArray();
            if (!$teacher) return false;
            return (bool) $this->db->table('teaching_assignments')
                ->where('teacher_id', $teacher['id'])
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $year['id'])
                ->countAllResults();
        }

        // Siswa/ortu: cek kelas aktif
        return $this->studentClassId() === $classId;
    }

    /**
     * Cek akses ke thread — menangani dua kasus:
     * 1. Thread biasa (class_id != NULL) → pakai canAccess() normal
     * 2. Thread materi shared (is_system=1, class_id=NULL, related_type='material')
     *    → cek apakah sub materi (related_id) dipublish ke kelas aktif siswa
     *
     * @param array $thread  Row dari forum_threads (wajib punya class_id, subject_id, is_system, related_type, related_id)
     */
    private function canAccessThread(array $thread): bool
    {
        // Admin/kepsek/staf selalu boleh
        if ($this->isModRole()) return true;

        // Normalisasi class_id — bisa null atau string kosong dari DB
        $classId = isset($thread['class_id']) && $thread['class_id'] !== '' && $thread['class_id'] !== null
            ? (int)$thread['class_id']
            : null;

        // Guru: cek lewat canAccess normal (class_id NULL → 0 → tetap lolos lewat teaching_assignments check di atas)
        if ($this->roleId() == 3) {
            // Untuk thread materi shared, guru yang mapelnya relevan boleh akses
            if ($classId === null && (int)($thread['is_system'] ?? 0) === 1) {
                return true; // Guru sudah punya akses ke semua materi mapelnya
            }
            return $this->canAccess((int)($classId ?? 0), (int)$thread['subject_id']);
        }

        // Siswa/ortu:
        // Kasus 1 — thread materi shared (class_id = NULL)
        if ($classId === null
            && (int)($thread['is_system'] ?? 0) === 1
            && ($thread['related_type'] ?? '') === 'material'
        ) {
            $studentClassId = $this->studentClassId();
            if (!$studentClassId) return false;

            // Cek apakah sub materi ini dipublish ke kelas siswa
            return (bool) $this->db->table('subject_material_publishes')
                ->where('material_id', (int)$thread['related_id'])
                ->where('class_id', $studentClassId)
                ->where('is_active', 1)
                ->countAllResults();
        }

        // Kasus 2 — thread kelas biasa
        return $this->canAccess((int)($classId ?? 0), (int)$thread['subject_id']);
    }

    // Tandai thread sudah dibaca
    private function markRead(int $threadId): void
    {
        $userId = $this->userId();
        if (!$userId) return;
        $now = date('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO forum_reads (thread_id, user_id, read_at)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)',
            [$threadId, $userId, $now]
        );
    }

    // =========================================================
    // INDEX — redirect ke kelas+mapel yang sesuai role
    // GET /forum
    // =========================================================
    public function index(): \CodeIgniter\HTTP\RedirectResponse
    {
        $role   = $this->roleId();
        $year   = $this->activeYear();
        $yearId = (int) ($year['id'] ?? 0);

        if (in_array($role, [4, 5])) {
            // Siswa/Ortu → kelas aktif mereka
            $classId = $this->studentClassId();
            if (!$classId) {
                return redirect()->to('dashboard')->with('error', 'Kelas aktif tidak ditemukan.');
            }
            // Ambil mapel pertama yang diajarkan di kelas ini
            $firstSubject = $this->db->table('teaching_assignments')
                ->select('subject_id')
                ->where('class_id', $classId)
                ->where('academic_year_id', $yearId)
                ->limit(1)->get()->getRowArray();

            $subjectId = $firstSubject['subject_id'] ?? 0;
            return redirect()->to("forum/{$classId}/{$subjectId}");
        }

        // Guru/Admin → daftar kelas yang bisa diakses
        return redirect()->to('forum/kelas');
    }

    // =========================================================
    // KELAS — guru/admin pilih kelas
    // GET /forum/kelas
    // =========================================================
    public function kelasList(): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isModRole()) {
            return redirect()->to('forum');
        }

        $year   = $this->activeYear();
        $yearId = (int) ($year['id'] ?? 0);
        $role   = $this->roleId();
        $user   = $this->user();

        if (in_array($role, [1, 2, 7])) {
            $classes = $this->db->table('classes c')
                ->select('c.id, c.name, COUNT(sr.id) as total_students')
                ->join('student_records sr',
                       "sr.class_id = c.id AND sr.academic_year_id = {$yearId} AND sr.status='aktif'",
                       'left')
                ->where('c.is_active', 1)
                ->groupBy('c.id')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray();
        } else {
            // Guru: hanya kelas yang diajarkan
            $teacher = $this->db->table('teachers')->where('user_id', $user['id'])->get()->getRowArray();
            if (!$teacher) {
                return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan.');
            }
            $classIds = array_column(
                $this->db->table('teaching_assignments')
                    ->select('class_id')->distinct()
                    ->where('teacher_id', $teacher['id'])
                    ->where('academic_year_id', $yearId)
                    ->get()->getResultArray(),
                'class_id'
            );
            $wali = $this->db->table('classes')
                ->select('id')->where('teacher_id', $teacher['id'])->get()->getRowArray();
            if ($wali) $classIds[] = $wali['id'];
            $classIds = array_unique($classIds);

            $classes = empty($classIds) ? [] : $this->db->table('classes c')
                ->select('c.id, c.name, COUNT(sr.id) as total_students')
                ->join('student_records sr',
                       "sr.class_id = c.id AND sr.academic_year_id = {$yearId} AND sr.status='aktif'",
                       'left')
                ->whereIn('c.id', $classIds)
                ->groupBy('c.id')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray();
        }

        return view('forum/kelas', [
            'title'      => 'Forum Diskusi — Pilih Kelas',
            'classes'    => $classes,
            'activeYear' => $year,
        ]);
    }

    // =========================================================
    // THREAD LIST — daftar thread per kelas+mapel
    // GET /forum/{classId}/{subjectId}
    // =========================================================
    public function threadList(int $classId, int $subjectId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->canAccess($classId, $subjectId)) {
            return redirect()->to('forum')->with('error', 'Akses ditolak.');
        }

        $year   = $this->activeYear();
        $yearId = (int) ($year['id'] ?? 0);
        $userId = $this->userId();

        $threads = $this->threadModel->getList($classId, $subjectId, $yearId, $userId);

        // Mapel lain di kelas ini (untuk tab navigasi)
        $allSubjects = $this->db->table('teaching_assignments ta')
            ->select('s.id, s.name')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $classId)
            ->where('ta.academic_year_id', $yearId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $class   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        return view('forum/index', [
            'title'       => 'Forum: ' . esc($subject['name'] ?? '') . ' — ' . esc($class['name'] ?? ''),
            'threads'     => $threads,
            'classId'     => $classId,
            'subjectId'   => $subjectId,
            'class'       => $class,
            'subject'     => $subject,
            'allSubjects' => $allSubjects,
            'activeYear'  => $year,
            'isMod'       => $this->isModRole(),
        ]);
    }

    // =========================================================
    // SHOW — detail thread + semua reply
    // GET /forum/thread/{id}
    // =========================================================
    public function show(int $threadId): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $thread = $this->threadModel->getDetail($threadId);
        if (!$thread) {
            return redirect()->to('forum')->with('error', 'Thread tidak ditemukan.');
        }

        if (!$this->canAccessThread($thread)) {
            return redirect()->to('forum')->with('error', 'Akses ditolak.');
        }

        $userId = $this->userId();

        // Increment view (sekali per session)
        $viewKey = 'forum_viewed_' . $threadId;
        if (!session()->get($viewKey)) {
            $this->threadModel->incrementView($threadId);
            session()->set($viewKey, true);
        }

        // Tandai sudah dibaca
        $this->markRead($threadId);

        // Ambil replies dan bangun tree
        $flatReplies = $this->replyModel->getByThread($threadId, $userId);
        $replyTree   = ForumReplyModel::buildTree($flatReplies);

        return view('forum/show', [
            'title'       => esc($thread['title']),
            'thread'      => $thread,
            'replyTree'   => $replyTree,
            'isMod'       => $this->isModRole(),
            'userId'      => $userId,
            'roleId'      => $this->roleId(),
        ]);
    }

    // =========================================================
    // STORE THREAD — buat thread baru
    // POST /forum/{classId}/{subjectId}/store
    // =========================================================
    public function storeThread(int $classId, int $subjectId): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->canAccess($classId, $subjectId)) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $title = trim($this->request->getPost('title') ?? '');
        $body  = trim($this->request->getPost('body')  ?? '');

        if (mb_strlen($title) < 5) {
            return redirect()->back()->withInput()->with('error', 'Judul minimal 5 karakter.');
        }

        $year   = $this->activeYear();
        $yearId = (int) ($year['id'] ?? 0);
        $userId = $this->userId();

        $relatedType = $this->request->getPost('related_type') ?: 'none';
        $relatedId   = (int) ($this->request->getPost('related_id') ?: 0);

        $threadId = $this->threadModel->insert([
            'subject_id'        => $subjectId,
            'class_id'          => $classId,
            'academic_year_id'  => $yearId,
            'related_type'      => in_array($relatedType, ['none','material','tugas','quiz']) ? $relatedType : 'none',
            'related_id'        => $relatedId ?: null,
            'title'             => $title,
            'body'              => $body,
            'user_id'           => $userId,
        ]);

        // Notifikasi ke guru yang mengajar mapel ini
        try {
            $teachers = $this->db->table('teaching_assignments ta')
                ->select('u.id as user_id')
                ->join('teachers t', 't.id = ta.teacher_id')
                ->join('users u', 'u.id = t.user_id', 'left')
                ->where('ta.class_id', $classId)
                ->where('ta.subject_id', $subjectId)
                ->where('ta.academic_year_id', $yearId)
                ->where('u.id IS NOT NULL')
                ->get()->getResultArray();

            $teacherUserIds = array_column($teachers, 'user_id');
            // Hapus user pembuat dari notifikasi
            $teacherUserIds = array_filter($teacherUserIds, fn($id) => (int)$id !== $userId);

            if (!empty($teacherUserIds)) {
                $subject = $this->db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRowArray();
                notify_users(
                    array_values($teacherUserIds),
                    'info',
                    'Thread Baru: ' . $title,
                    'Mapel: ' . ($subject['name'] ?? ''),
                    base_url('forum/thread/' . $threadId)
                );
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Forum] Notifikasi gagal: ' . $e->getMessage());
        }

        return redirect()->to("forum/thread/{$threadId}")
            ->with('success', 'Diskusi berhasil dibuat.');
    }

    // =========================================================
    // STORE REPLY — balas thread atau reply
    // POST /forum/thread/{id}/reply
    // =========================================================
    public function storeReply(int $threadId): \CodeIgniter\HTTP\RedirectResponse|\CodeIgniter\HTTP\Response
    {
        $thread = $this->threadModel->find($threadId);
        if (!$thread) {
            return redirect()->back()->with('error', 'Thread tidak ditemukan.');
        }

        if ($thread['is_locked'] && !$this->isModRole()) {
            return redirect()->back()->with('error', 'Thread ini sudah dikunci.');
        }

        if (!$this->canAccessThread($thread)) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $body     = trim($this->request->getPost('body') ?? '');
        $parentId = (int) ($this->request->getPost('parent_id') ?: 0);

        if (mb_strlen($body) < 2) {
            return redirect()->back()->withInput()->with('error', 'Balasan terlalu pendek.');
        }

        $userId = $this->userId();

        $this->replyModel->insert([
            'thread_id' => $threadId,
            'parent_id' => $parentId ?: null,
            'user_id'   => $userId,
            'body'      => $body,
        ]);

        // Update stats thread
        $this->threadModel->refreshStats($threadId);

        // Reset read_at pembuat thread (biar dia dapat notifikasi balasan baru)
        if ((int)$thread['user_id'] !== $userId) {
            $this->db->query(
                'UPDATE forum_reads SET read_at = DATE_SUB(NOW(), INTERVAL 1 SECOND)
                 WHERE thread_id = ? AND user_id = ?',
                [$threadId, $thread['user_id']]
            );

            // Notifikasi ke pemilik thread — link selalu ke forum/thread (bukan halaman belajar)
            // agar semua role (guru, admin) bisa langsung buka dari notifikasi
            try {
                $ownerUser = $this->db->table('users')
                    ->select('id')->where('id', $thread['user_id'])->get()->getRowArray();
                if ($ownerUser) {
                    notify_user(
                        (int)$ownerUser['id'],
                        'info',
                        'Balasan baru di diskusimu',
                        mb_substr($body, 0, 100),
                        base_url('forum/thread/' . $threadId)
                    );
                }
            } catch (\Throwable $e) {
                log_message('warning', '[Forum] Notifikasi reply gagal: ' . $e->getMessage());
            }
        }

        // Redirect: kembali ke halaman asal jika ada (misal: siswa/belajar/sub/{id}#diskusi),
        // fallback ke halaman forum/thread untuk guru/admin
        $redirectTo = trim($this->request->getPost('redirect_to') ?? '');

        // Validasi URL redirect_to — hanya boleh path relatif ke aplikasi ini (cegah open redirect)
        if ($redirectTo !== '') {
            $baseUrl = rtrim(base_url(), '/');
            // Pastikan URL dimulai dari base_url aplikasi
            if (str_starts_with($redirectTo, $baseUrl . '/') || str_starts_with($redirectTo, '/')) {
                return redirect()->to($redirectTo)->with('success', 'Pesan berhasil dikirim.');
            }
        }

        return redirect()->to("forum/thread/{$threadId}#replies")
            ->with('success', 'Balasan berhasil dikirim.');
    }

    // =========================================================
    // PIN — toggle pin thread (guru/admin)
    // POST /forum/thread/{id}/pin
    // =========================================================
    public function pin(int $threadId): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isModRole()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $thread  = $this->threadModel->find($threadId);
        if (!$thread) return redirect()->back()->with('error', 'Thread tidak ditemukan.');

        $newPin  = $thread['is_pinned'] ? 0 : 1;
        $this->threadModel->update($threadId, ['is_pinned' => $newPin]);
        $msg = $newPin ? 'Thread disematkan.' : 'Pin dihapus.';

        return redirect()->back()->with('success', $msg);
    }

    // =========================================================
    // LOCK — toggle lock thread (guru/admin)
    // POST /forum/thread/{id}/lock
    // =========================================================
    public function lock(int $threadId): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isModRole()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $thread   = $this->threadModel->find($threadId);
        if (!$thread) return redirect()->back()->with('error', 'Thread tidak ditemukan.');

        $newLock  = $thread['is_locked'] ? 0 : 1;
        $this->threadModel->update($threadId, ['is_locked' => $newLock]);
        $msg = $newLock ? 'Thread dikunci.' : 'Thread dibuka kembali.';

        return redirect()->back()->with('success', $msg);
    }

    // =========================================================
    // DELETE THREAD — hapus thread (owner atau guru/admin)
    // POST /forum/thread/{id}/delete
    // =========================================================
    public function deleteThread(int $threadId): \CodeIgniter\HTTP\RedirectResponse
    {
        $thread = $this->threadModel->find($threadId);
        if (!$thread) return redirect()->to('forum')->with('error', 'Thread tidak ditemukan.');

        $userId = $this->userId();
        $isOwner = (int)$thread['user_id'] === $userId;

        if (!$isOwner && !$this->isModRole()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        // Hard delete thread + replies
        $this->db->table('forum_reads')->where('thread_id', $threadId)->delete();
        $replyIds = array_column(
            $this->db->table('forum_replies')->select('id')->where('thread_id', $threadId)->get()->getResultArray(),
            'id'
        );
        if (!empty($replyIds)) {
            $this->db->table('forum_upvotes')->whereIn('reply_id', $replyIds)->delete();
        }
        $this->db->table('forum_replies')->where('thread_id', $threadId)->delete();
        $this->threadModel->delete($threadId);

        $classId   = $thread['class_id'];
        $subjectId = $thread['subject_id'];

        return redirect()->to("forum/{$classId}/{$subjectId}")
            ->with('success', 'Thread berhasil dihapus.');
    }

    // =========================================================
    // DELETE REPLY — soft-delete reply (owner atau guru/admin)
    // POST /forum/reply/{id}/delete
    // =========================================================
    public function deleteReply(int $replyId): \CodeIgniter\HTTP\RedirectResponse
    {
        $reply  = $this->replyModel->find($replyId);
        if (!$reply) return redirect()->back()->with('error', 'Balasan tidak ditemukan.');

        $userId  = $this->userId();
        $isOwner = (int)$reply['user_id'] === $userId;

        if (!$isOwner && !$this->isModRole()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $this->replyModel->softDelete($replyId, $userId);
        $this->threadModel->refreshStats((int)$reply['thread_id']);

        // Redirect ke halaman asal jika ada (misal: admin/materials/show/{id}#diskusi)
        $redirectTo = trim($this->request->getPost('redirect_to') ?? '');
        if ($redirectTo !== '') {
            $baseUrl = rtrim(base_url(), '/');
            if (str_starts_with($redirectTo, $baseUrl . '/') || str_starts_with($redirectTo, '/')) {
                return redirect()->to($redirectTo)->with('success', 'Pesan dihapus.');
            }
        }

        return redirect()->to('forum/thread/' . $reply['thread_id'])
            ->with('success', 'Balasan dihapus.');
    }

    // =========================================================
    // UPVOTE — toggle upvote reply (AJAX)
    // POST /forum/reply/{id}/upvote
    // =========================================================
    public function upvote(int $replyId): \CodeIgniter\HTTP\Response
    {
        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false]);
        }

        $reply = $this->replyModel->find($replyId);
        if (!$reply) {
            return $this->response->setStatusCode(404)->setJSON(['success' => false]);
        }

        $userId  = $this->userId();
        $newCount = $this->replyModel->toggleUpvote($replyId, $userId);

        return $this->response->setJSON([
            'success' => true,
            'count'   => $newCount,
        ]);
    }

    // =========================================================
    // BEST ANSWER — tandai reply sebagai jawaban terbaik (guru/admin)
    // POST /forum/reply/{id}/best
    // =========================================================
    public function setBestAnswer(int $replyId): \CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->isModRole()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $reply = $this->replyModel->find($replyId);
        if (!$reply) return redirect()->back()->with('error', 'Balasan tidak ditemukan.');

        $this->replyModel->setBestAnswer((int)$reply['thread_id'], $replyId);

        return redirect()->to('forum/thread/' . $reply['thread_id'])
            ->with('success', 'Jawaban terbaik ditandai.');
    }
}
