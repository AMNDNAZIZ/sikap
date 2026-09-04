<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectMaterialModel;
use App\Models\MaterialProgressModel;
use App\Models\ForumReplyModel;
use App\Models\ForumThreadModel;
use App\Models\SubjectModel;
use App\Models\AcademicYearModel;

/**
 * Admin/Materials — Kelola Materi & Sub Materi
 *
 * Alur baru:
 *   - Materi dikaitkan ke subject_id + level (bukan class_id)
 *   - Publikasi dikelola terpisah via subject_material_publishes
 *   - Guru bisa "gunakan" sub materi milik guru lain (shared reference)
 *
 * Routes:
 *   GET  /admin/materials                          → subjectList
 *   GET  /admin/materials/{subjectId}              → index (hierarki)
 *   GET  /admin/materials/create/{subjectId}       → create
 *   POST /admin/materials/store                    → store
 *   GET  /admin/materials/edit/{id}                → edit
 *   POST /admin/materials/update/{id}              → update
 *   POST /admin/materials/delete/{id}              → delete (AJAX)
 *   GET  /admin/materials/publish/{id}             → publishPage
 *   POST /admin/materials/publish/{id}             → publishSave
 *   GET  /admin/materials/use/{subjectId}          → usePage
 *   POST /admin/materials/use                      → useSave
 *   GET  /admin/materials/progress/{subjectId}     → progress
 *   GET  /admin/materials/progress-detail/{id}     → progressDetail
 */
class Materials extends BaseController
{
    protected SubjectMaterialModel  $mat;
    protected MaterialProgressModel $prog;
    protected SubjectModel          $subjectModel;
    protected AcademicYearModel     $yearModel;
    protected ForumReplyModel       $replyModel;
    protected ForumThreadModel      $threadModel;
    protected $db;

    public function __construct()
    {
        $this->mat          = new SubjectMaterialModel();
        $this->prog         = new MaterialProgressModel();
        $this->subjectModel = new SubjectModel();
        $this->yearModel    = new AcademicYearModel();
        $this->replyModel   = new ForumReplyModel();
        $this->threadModel  = new ForumThreadModel();
        $this->db           = \Config\Database::connect();
    }

    // ── Helpers ──────────────────────────────────────────────────────────
    private function user(): array  { return session()->get('user') ?? []; }
    private function userId(): int  { return (int)($this->user()['id'] ?? 0); }
    private function roleId(): int  { return (int)($this->user()['role_id'] ?? 0); }
    private function isAdmin(): bool { return in_array($this->roleId(), [1, 2]); }

    private function teacherId(): ?int
    {
        $u = $this->user();
        if ($u['role_id'] == 3) {
            $t = $this->db->table('teachers')->where('user_id', $u['id'])->get()->getRowArray();
            return $t ? (int)$t['id'] : null;
        }
        return null;
    }

    private function activeYear(): array { return $this->yearModel->getActiveYear() ?? []; }

    /** Level-level kelas yang diajar guru ini untuk mapel tertentu */
    private function getLevelsForSubject(int $subjectId, int $yearId): array
    {
        $builder = $this->db->table('teaching_assignments ta')
            ->select('c.level')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id',       $subjectId)
            ->where('ta.academic_year_id', $yearId)
            ->groupBy('c.level')
            ->orderBy('c.level', 'ASC');

        if (!$this->isAdmin()) {
            $tid = $this->teacherId();
            if ($tid) $builder->where('ta.teacher_id', $tid);
        }

        return array_column($builder->get()->getResultArray(), 'level');
    }

    // =========================================================
    // SUBJECT LIST
    // =========================================================
    public function subjectList()
    {
        $year   = $this->activeYear();
        $yearId = (int)($year['id'] ?? 0);

        if ($this->isAdmin()) {
            $subjects = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name,
                          COUNT(DISTINCT ta.class_id) as total_classes,
                          COUNT(DISTINCT sm.id) as total_parents')
                ->join('subjects s', 's.id = ta.subject_id')
                ->join('subject_materials sm',
                    "sm.subject_id = s.id AND sm.year_id = {$yearId} AND sm.parent_id IS NULL", 'left')
                ->where('ta.academic_year_id', $yearId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        } else {
            $tid = $this->teacherId();
            if (!$tid) return redirect()->to('dashboard')->with('error', 'Data guru tidak ditemukan.');

            $subjects = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name,
                          COUNT(DISTINCT ta.class_id) as total_classes,
                          COUNT(DISTINCT sm.id) as total_parents')
                ->join('subjects s', 's.id = ta.subject_id')
                ->join('subject_materials sm',
                    "sm.subject_id = s.id AND sm.year_id = {$yearId} AND sm.parent_id IS NULL
                     AND sm.created_by = {$this->userId()}", 'left')
                ->where('ta.teacher_id', $tid)
                ->where('ta.academic_year_id', $yearId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        return view('admin/materials/subject_list', [
            'title'      => 'Materi Pelajaran',
            'subjects'   => $subjects,
            'activeYear' => $year,
        ]);
    }

    // =========================================================
    // INDEX — hierarki Materi per mapel
    // =========================================================
    public function index(int $subjectId)
    {
        $subject    = $this->subjectModel->find($subjectId);
        $year       = $this->activeYear();
        $yearId     = (int)($year['id'] ?? 0);
        $hierarchy  = $this->mat->getAllWithHierarchy($subjectId);

        // Progress summary (hanya sub materi)
        $progSummary = [];
        foreach ($hierarchy as $parent) {
            foreach (($parent['children'] ?? []) as $child) {
                $progSummary[$child['id']] = [
                    'completed'   => $this->db->table('material_progress')
                        ->where('material_id', $child['id'])->where('status', 'completed')->countAllResults(),
                    'in_progress' => $this->db->table('material_progress')
                        ->where('material_id', $child['id'])->where('status', 'in_progress')->countAllResults(),
                ];
            }
        }

        return view('admin/materials/index', [
            'title'       => 'Materi: ' . ($subject['name'] ?? ''),
            'subject'     => $subject,
            'hierarchy'   => $hierarchy,
            'progSummary' => $progSummary,
            'returnUrl'   => $this->request->getGet('return'),
            'activeYear'  => $year,
        ]);
    }

    // =========================================================
    // SHOW — Lihat konten sub materi + diskusi (admin/guru)
    // GET /admin/materials/show/{id}
    // =========================================================
    public function show(int $subMatId)
    {
        $subMat = $this->mat->find($subMatId);

        if (!$subMat || empty($subMat['parent_id'])) {
            return redirect()->to('admin/materials')->with('error', 'Sub Materi tidak ditemukan.');
        }

        $parent  = $this->mat->find($subMat['parent_id']);
        $subject = $this->subjectModel->find($subMat['subject_id']);
        $year    = $this->activeYear();

        // Embed video
        if ($subMat['content_type'] === 'video' && !empty($subMat['video_url'])) {
            $subMat['embed_url'] = SubjectMaterialModel::toEmbedUrl($subMat['video_url']);
        }

        // Sibling sub materi (dalam satu materi induk) untuk navigasi
        $siblings = $this->db->table('subject_materials')
            ->where('parent_id', $subMat['parent_id'])
            ->where('is_published', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        $currentIdx = array_search($subMatId, array_column($siblings, 'id'));
        $prevSub    = ($currentIdx !== false && $currentIdx > 0)
                        ? $siblings[$currentIdx - 1] : null;
        $nextSub    = ($currentIdx !== false && $currentIdx < count($siblings) - 1)
                        ? $siblings[$currentIdx + 1] : null;

        // ── DISKUSI ──────────────────────────────────────────
        $thread    = $this->mat->getForumThread($subMatId);
        $replyTree = [];
        $threadId  = null;
        $userId    = $this->userId();

        if ($thread) {
            $threadId    = (int)$thread['id'];
            $flatReplies = $this->replyModel->getByThread($threadId, $userId);
            $replyTree   = ForumReplyModel::buildTree($flatReplies);

            // Tandai sudah dibaca
            $this->db->query(
                'INSERT INTO forum_reads (thread_id, user_id, read_at)
                 VALUES (?, ?, ?)
                 ON DUPLICATE KEY UPDATE read_at = VALUES(read_at)',
                [$threadId, $userId, date('Y-m-d H:i:s')]
            );
        }

        // ── PROGRESS SINGKAT (jumlah siswa selesai) ──────────
        $progStats = [
            'completed'   => $this->db->table('material_progress')
                ->where('material_id', $subMatId)->where('status', 'completed')->countAllResults(),
            'in_progress' => $this->db->table('material_progress')
                ->where('material_id', $subMatId)->where('status', 'in_progress')->countAllResults(),
        ];

        return view('admin/materials/show', [
            'title'      => esc($subMat['title']),
            'subMat'     => $subMat,
            'parent'     => $parent,
            'subject'    => $subject,
            'activeYear' => $year,
            'thread'     => $thread,
            'threadId'   => $threadId,
            'replyTree'  => $replyTree,
            'isMod'      => true,   // admin/guru selalu mod
            'userId'     => $userId,
            'roleId'     => $this->roleId(),
            'prevSub'    => $prevSub,
            'nextSub'    => $nextSub,
            'currentIdx' => (int)($currentIdx !== false ? $currentIdx : 0),
            'siblings'   => $siblings,
            'progStats'  => $progStats,
        ]);
    }

    // =========================================================
    // CREATE
    // =========================================================
    public function create(int $subjectId)
    {
        $subject    = $this->subjectModel->find($subjectId);
        $year       = $this->activeYear();
        $yearId     = (int)($year['id'] ?? 0);
        $parentId   = (int)($this->request->getGet('parent_id') ?? 0);
        $parent     = $parentId ? $this->mat->find($parentId) : null;

        // Level-level yang diajar guru ini untuk mapel ini
        $levels = $this->getLevelsForSubject($subjectId, $yearId);
        sort($levels);

        // Daftar materi induk yang bisa jadi parent
        $parents = $this->mat->getParentsBySubject($subjectId, $yearId);

        return view('admin/materials/create', [
            'title'     => $parent ? 'Tambah Sub Materi' : 'Tambah Materi',
            'subject'   => $subject,
            'activeYear'=> $year,
            'returnUrl' => $this->request->getGet('return'),
            'parentId'  => $parentId,
            'parent'    => $parent,
            'parents'   => $parents,
            'levels'    => $levels,
        ]);
    }

    // =========================================================
    // STORE
    // =========================================================
    public function store()
    {
        $data     = $this->request->getPost();
        $parentId = !empty($data['parent_id']) ? (int)$data['parent_id'] : null;
        $filePath = null;

        // Upload PDF
        if (($data['content_type'] ?? '') === 'pdf') {
            $file = $this->request->getFile('file_upload');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if ($file->getMimeType() !== 'application/pdf') {
                    return redirect()->back()->withInput()->with('error', 'File harus PDF.');
                }
                if ($file->getSize() > 10 * 1024 * 1024) {
                    return redirect()->back()->withInput()->with('error', 'File maks 10 MB.');
                }
                $newName = $file->getRandomName();
                $file->move(FCPATH . 'uploads/materials/', $newName);
                $filePath = $newName;
            }
        }

        // Tentukan level: sub materi mewarisi level dari parent-nya
        $level = (int)($data['level'] ?? 0);
        if ($parentId) {
            $parentRow = $this->mat->find($parentId);
            $level     = (int)($parentRow['level'] ?? $level);
        }

        $contentType = $data['content_type'] ?? 'text';
        $isPublished = isset($data['is_published']) ? 1 : 0;

        $insertData = [
            'parent_id'         => $parentId,
            'level'             => $level,
            'subject_id'        => $data['subject_id'],
            'year_id'           => $data['year_id'],
            'semester'          => $data['semester'],
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'content_type'      => $contentType,
            'content'           => $contentType === 'text'  ? ($data['content']        ?? null) : null,
            'file_path'         => $contentType === 'pdf'   ? $filePath                         : null,
            'video_url'         => $contentType === 'video' ? ($data['video_url']      ?? null) : null,
            'external_link'     => $contentType === 'link'  ? ($data['external_link']  ?? null) : null,
            'estimated_minutes' => !empty($data['estimated_minutes']) ? (int)$data['estimated_minutes'] : null,
            'sort_order'        => !empty($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'is_published'      => $isPublished,
            'published_by'      => $isPublished ? $this->userId() : null,
            'published_at'      => $isPublished ? date('Y-m-d H:i:s') : null,
            'created_by'        => $this->userId(),
        ];

        $this->mat->insert($insertData);
        $newId = (int)$this->mat->getInsertID();

        $msg = $parentId ? 'Sub Materi berhasil ditambahkan.' : 'Materi berhasil ditambahkan.';

        $returnUrl = $this->request->getPost('return');
        return redirect()->to($returnUrl ?: '/admin/materials/' . $data['subject_id'])
                         ->with('success', $msg);
    }

    // =========================================================
    // EDIT
    // =========================================================
    public function edit(int $id)
    {
        $material = $this->mat->find($id);
        if (!$material) return redirect()->to('admin/materials')->with('error', 'Tidak ditemukan.');

        $subject    = $this->subjectModel->find($material['subject_id']);
        $year       = $this->activeYear();
        $yearId     = (int)($year['id'] ?? 0);
        $isSubMat   = !empty($material['parent_id']);

        $levels  = $this->getLevelsForSubject((int)$material['subject_id'], $yearId);
        sort($levels);
        $parents = $isSubMat ? [] : []; // tidak bisa ubah parent pada edit

        return view('admin/materials/edit', [
            'title'     => $isSubMat ? 'Edit Sub Materi' : 'Edit Materi',
            'material'  => $material,
            'subject'   => $subject,
            'activeYear'=> $year,
            'levels'    => $levels,
            'isSubMat'  => $isSubMat,
        ]);
    }

    // =========================================================
    // UPDATE
    // =========================================================
    public function update(int $id)
    {
        $material    = $this->mat->find($id);
        if (!$material) return redirect()->to('admin/materials')->with('error', 'Tidak ditemukan.');

        $data        = $this->request->getPost();
        $contentType = $data['content_type'] ?? 'text';
        $filePath    = $material['file_path'];
        $wasPublished= (bool)($material['is_published'] ?? false);
        $nowPublish  = isset($data['is_published']);

        // Upload PDF baru
        if ($contentType === 'pdf') {
            $file = $this->request->getFile('file_upload');
            if ($file && $file->isValid() && !$file->hasMoved()) {
                if ($file->getMimeType() !== 'application/pdf') {
                    return redirect()->back()->withInput()->with('error', 'File harus PDF.');
                }
                if (!empty($material['file_path']) &&
                    file_exists(FCPATH . 'uploads/materials/' . $material['file_path'])) {
                    @unlink(FCPATH . 'uploads/materials/' . $material['file_path']);
                }
                $newName = $file->getRandomName();
                $file->move(FCPATH . 'uploads/materials/', $newName);
                $filePath = $newName;
            }
        } elseif ($material['content_type'] === 'pdf' && $contentType !== 'pdf') {
            if (!empty($material['file_path']) &&
                file_exists(FCPATH . 'uploads/materials/' . $material['file_path'])) {
                @unlink(FCPATH . 'uploads/materials/' . $material['file_path']);
            }
            $filePath = null;
        }

        $updateData = [
            'level'             => (int)($data['level'] ?? $material['level']),
            'semester'          => $data['semester'],
            'title'             => $data['title'],
            'description'       => $data['description'] ?? null,
            'content_type'      => $contentType,
            'content'           => $contentType === 'text'  ? ($data['content']       ?? null) : null,
            'file_path'         => $contentType === 'pdf'   ? $filePath                        : null,
            'video_url'         => $contentType === 'video' ? ($data['video_url']     ?? null) : null,
            'external_link'     => $contentType === 'link'  ? ($data['external_link'] ?? null) : null,
            'estimated_minutes' => !empty($data['estimated_minutes']) ? (int)$data['estimated_minutes'] : null,
            'sort_order'        => !empty($data['sort_order']) ? (int)$data['sort_order'] : 0,
            'is_published'      => $nowPublish ? 1 : 0,
        ];

        if ($nowPublish && !$wasPublished) {
            $updateData['published_by'] = $this->userId();
            $updateData['published_at'] = date('Y-m-d H:i:s');
        } elseif (!$nowPublish) {
            $updateData['published_by'] = null;
            $updateData['published_at'] = null;
        }

        $this->mat->update($id, $updateData);

        return redirect()->to('/admin/materials/' . $material['subject_id'])
                         ->with('success', 'Berhasil diperbarui.');
    }

    // =========================================================
    // DELETE (AJAX POST)
    // =========================================================
    public function delete(int $id)
    {
        $material = $this->mat->find($id);
        if (!$material) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ditemukan.']);
        }

        // Hapus children + publish records + forum threads
        $children = $this->mat->getAllChildren($id);
        foreach ($children as $child) {
            $this->_deleteSubMat($child['id']);
        }
        $this->_deleteSubMat($id);

        // Hapus file PDF parent jika ada
        if (!empty($material['file_path']) &&
            file_exists(FCPATH . 'uploads/materials/' . $material['file_path'])) {
            @unlink(FCPATH . 'uploads/materials/' . $material['file_path']);
        }
        $this->mat->delete($id);

        return $this->response->setJSON([
            'status'    => 'success',
            'message'   => 'Berhasil dihapus.',
            'subjectId' => $material['subject_id'],
        ]);
    }

    // =========================================================
    // PUBLISH PAGE — kelola publikasi sub materi ke kelas
    // GET /admin/materials/publish/{subMatId}
    // =========================================================
    public function publishPage(int $subMatId)
    {
        $subMat = $this->mat->find($subMatId);
        if (!$subMat || empty($subMat['parent_id'])) {
            return redirect()->to('admin/materials')->with('error', 'Hanya sub materi yang bisa dipublish.');
        }

        $year   = $this->activeYear();
        $yearId = (int)($year['id'] ?? 0);

        // Semua kelas yang level-nya sama dan mapelnya sama
        // Guru non-admin hanya lihat kelas yang dia ampu
        $teacherId = $this->isAdmin() ? null : $this->teacherId();
        $classes = $this->mat->getClassesForPublish(
            (int)$subMat['subject_id'],
            (int)$subMat['level'],
            $yearId,
            $teacherId
        );

        // Status publish per kelas
        $publishStatus = $this->mat->getPublishStatusForClasses($subMatId, $classes);

        $parent  = $this->mat->find($subMat['parent_id']);
        $subject = $this->subjectModel->find($subMat['subject_id']);

        return view('admin/materials/publish', [
            'title'         => 'Publikasi: ' . esc($subMat['title']),
            'subMat'        => $subMat,
            'parent'        => $parent,
            'subject'       => $subject,
            'classes'       => $classes,
            'publishStatus' => $publishStatus,
            'activeYear'    => $year,
        ]);
    }

    // =========================================================
    // PUBLISH SAVE — simpan perubahan publikasi (AJAX POST)
    // POST /admin/materials/publish/{subMatId}
    // =========================================================
    public function publishSave(int $subMatId)
    {
        $subMat = $this->mat->find($subMatId);
        if (!$subMat || empty($subMat['parent_id'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Sub materi tidak valid.']);
        }

        $year   = $this->activeYear();
        $yearId = (int)($year['id'] ?? 0);

        // classIds yang dicentang user
        $checkedClassIds = array_map('intval',
            (array)($this->request->getPost('class_ids') ?? [])
        );

        // Semua kelas yang relevan untuk mapel+level ini
        // Guru non-admin hanya kelola kelas yang dia ampu
        $teacherId2 = $this->isAdmin() ? null : $this->teacherId();
        $allClasses = $this->mat->getClassesForPublish(
            (int)$subMat['subject_id'],
            (int)$subMat['level'],
            $yearId,
            $teacherId2
        );
        $allClassIds = array_column($allClasses, 'id');

        // Publish ke kelas yang dicentang, unpublish dari yang tidak
        foreach ($allClassIds as $cid) {
            if (in_array($cid, $checkedClassIds)) {
                $this->mat->publishToClass($subMatId, $cid, $this->userId(), $yearId);
            } else {
                $this->mat->unpublishFromClass($subMatId, $cid);
            }
        }

        // Kirim notifikasi ke siswa di kelas yang baru dipublish
        if (!empty($checkedClassIds)) {
            $this->_notifyPublish($subMatId, $subMat, $checkedClassIds);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Pengaturan publikasi berhasil disimpan.',
        ]);
    }

    // =========================================================
    // USE PAGE — daftar sub materi level yang sama untuk digunakan
    // GET /admin/materials/use/{subjectId}
    // =========================================================
    public function usePage(int $subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->to('admin/materials')->with('error', 'Mapel tidak ditemukan.');

        $year   = $this->activeYear();
        $yearId = (int)($year['id'] ?? 0);
        $userId = $this->userId();

        // Level-level yang diajar guru ini
        $levels = $this->getLevelsForSubject($subjectId, $yearId);
        sort($levels);

        // Sub materi tersedia per level
        $available = [];
        foreach ($levels as $lv) {
            $items = $this->mat->getAvailableForTeacher($subjectId, $lv, $yearId, $userId);
            if (!empty($items)) {
                $available[$lv] = $items;
            }
        }

        // Kelas yang diajar guru ini per level
        $classesByLevel = [];
        $tidForUse      = $this->isAdmin() ? null : $this->teacherId();
        foreach ($levels as $lv) {
            $classesByLevel[$lv] = $this->mat->getClassesForPublish($subjectId, $lv, $yearId, $tidForUse);
        }

        return view('admin/materials/use', [
            'title'         => 'Gunakan Materi: ' . esc($subject['name']),
            'subject'       => $subject,
            'available'     => $available,
            'classesByLevel'=> $classesByLevel,
            'activeYear'    => $year,
        ]);
    }

    // =========================================================
    // USE SAVE — simpan penggunaan sub materi (AJAX POST)
    // POST /admin/materials/use
    // =========================================================
    public function useSave()
    {
        $subMatId  = (int)$this->request->getPost('sub_mat_id');
        $classIds  = array_map('intval',
            (array)($this->request->getPost('class_ids') ?? [])
        );

        if (!$subMatId || empty($classIds)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap.']);
        }

        $year = $this->activeYear();

        $ok = $this->mat->useSubMaterial($subMatId, $classIds, $this->userId(), (int)($year['id'] ?? 0));

        if ($ok) {
            $sub = $this->mat->find($subMatId);
            $this->_notifyPublish($subMatId, $sub ?? [], $classIds);
        }

        return $this->response->setJSON([
            'success' => $ok,
            'message' => $ok ? 'Sub materi berhasil digunakan.' : 'Gagal menggunakan sub materi.',
        ]);
    }

    // =========================================================
    // UPLOAD IMAGE — CKEditor Simple Upload Adapter
    // POST /admin/materials/upload-image
    // =========================================================
    public function uploadImage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['error' => ['message' => 'Forbidden']]);
        }

        $file = $this->request->getFile('upload');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['error' => ['message' => 'File tidak valid.']]);
        }

        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed)) {
            return $this->response->setJSON(['error' => ['message' => 'Hanya file gambar yang diizinkan (jpg, png, gif, webp).']]);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->response->setJSON(['error' => ['message' => 'Ukuran maksimal 5 MB.']]);
        }

        $uploadDir = FCPATH . 'uploads/materials/images/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($uploadDir, $newName);

        // CKEditor 5 Simple Upload Adapter mengharapkan format: { "url": "..." }
        return $this->response->setJSON([
            'url' => base_url('uploads/materials/images/' . $newName),
        ]);
    }

    // =========================================================
    // PROGRESS
    // =========================================================
    public function progress(int $subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        $year    = $this->activeYear();
        $yearId  = (int)($year['id'] ?? 0);

        $tidForProg = $this->isAdmin() ? null : $this->teacherId();
        $classes = $this->mat->getClassesForPublish($subjectId, 0, $yearId, $tidForProg);
        $selectedClassId = $this->request->getGet('class_id') ?: ($classes[0]['id'] ?? null);
        $materials = $summary = [];

        if ($selectedClassId) {
            $materials = $this->mat->getPublishedByClass((int)$selectedClassId, $yearId);
            $summary   = $this->prog->getSummaryByClass((int)$selectedClassId, $yearId);
        }

        return view('admin/materials/progress', [
            'title'           => 'Progress: ' . ($subject['name'] ?? ''),
            'subject'         => $subject,
            'classes'         => $classes,
            'selectedClassId' => (int)$selectedClassId,
            'materials'       => $materials,
            'summary'         => $summary,
            'activeYear'      => $year,
        ]);
    }

    public function progressDetail(int $materialId)
    {
        $material = $this->mat->find($materialId);
        if (!$material) return redirect()->to('admin/materials')->with('error', 'Sub materi tidak ditemukan.');

        $subject  = $this->subjectModel->find($material['subject_id'] ?? 0);
        $year     = $this->activeYear();
        $yearId   = (int)($year['id'] ?? 0);

        // Ambil class_id dari query param; jika tidak ada, ambil kelas pertama yang punya publish record
        $classId = $this->request->getGet('class_id');
        if (!$classId) {
            $firstPublish = $this->db->table('subject_material_publishes')
                ->select('class_id')
                ->where('material_id', $materialId)
                ->where('is_active', 1)
                ->orderBy('class_id', 'ASC')
                ->limit(1)
                ->get()->getRowArray();
            $classId = $firstPublish['class_id'] ?? null;
        }

        // Semua kelas yang punya publish record untuk sub materi ini (untuk tab/filter kelas)
        $publishedClasses = $this->db->table('subject_material_publishes smp')
            ->select('c.id, c.name')
            ->join('classes c', 'c.id = smp.class_id')
            ->where('smp.material_id', $materialId)
            ->where('smp.is_active', 1)
            ->orderBy('c.name', 'ASC')
            ->get()->getResultArray();

        $students = $classId
            ? $this->prog->getDetailByMaterial($materialId, (int)$classId, $yearId)
            : [];

        // back_url: dari show/{id} atau dari progress/{subjectId}
        $backUrl = $this->request->getGet('back_url')
            ?: site_url('admin/materials/progress/' . ($subject['id'] ?? '') . '?class_id=' . $classId);

        return view('admin/materials/progress_detail', [
            'title'            => 'Detail Progress: ' . ($material['title'] ?? ''),
            'material'         => $material,
            'subject'          => $subject,
            'students'         => $students,
            'classId'          => $classId,
            'publishedClasses' => $publishedClasses,
            'backUrl'          => $backUrl,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────

    private function _deleteSubMat(int $id): void
    {
        $sub = $this->mat->find($id);
        if ($sub && !empty($sub['file_path']) &&
            file_exists(FCPATH . 'uploads/materials/' . $sub['file_path'])) {
            @unlink(FCPATH . 'uploads/materials/' . $sub['file_path']);
        }
        $this->db->table('material_progress')->where('material_id', $id)->delete();
        $this->db->table('subject_material_publishes')->where('material_id', $id)->delete();
        $this->db->table('forum_threads')
            ->where('related_type', 'material')
            ->where('related_id', $id)
            ->where('is_system', 1)
            ->delete();
    }

    private function _notifyPublish(int $subMatId, array $subMat, array $classIds): void
    {
        try {
            $subjectName = $this->db->table('subjects')
                ->select('name')
                ->where('id', $subMat['subject_id'] ?? 0)
                ->get()->getRowArray()['name'] ?? '';

            notify_classes(
                $classIds,
                'materi_baru',
                'Sub Materi Baru: ' . ($subMat['title'] ?? ''),
                "Mapel: {$subjectName}",
                base_url('siswa/belajar/sub/' . $subMatId),
                'material',
                $subMatId
            );
        } catch (\Throwable $e) {
            log_message('warning', '[Materials] Notif gagal: ' . $e->getMessage());
        }
    }
}
