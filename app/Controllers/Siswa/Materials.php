<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\SubjectMaterialModel;
use App\Models\MaterialProgressModel;
use App\Models\AcademicYearModel;

/**
 * Portal Materi Pelajaran untuk Siswa
 *
 * Alur:
 *   /siswa/materials           → index: semua materi dikelompokkan per mapel
 *   /siswa/materials/{id}      → show: baca konten materi + auto-track progress
 *   POST /siswa/materials/{id}/complete → tandai selesai (AJAX)
 */
class Materials extends BaseController
{
    protected SubjectMaterialModel  $materialModel;
    protected MaterialProgressModel $progressModel;
    protected AcademicYearModel     $yearModel;
    protected $db;

    public function __construct()
    {
        $this->materialModel = new SubjectMaterialModel();
        $this->progressModel = new MaterialProgressModel();
        $this->yearModel     = new AcademicYearModel();
        $this->db            = \Config\Database::connect();
    }

    // ─── Helper ──────────────────────────────────────────────────────────
    private function currentUser(): array
    {
        return session()->get('user') ?? [];
    }

    private function studentId(): int
    {
        return (int) (session()->get('user')['student_id'] ?? 0);
    }

    // =========================================================
    // INDEX — semua materi yang bisa diakses siswa,
    //         dikelompokkan per mapel, dengan progress masing-masing
    // =========================================================
    public function index(): string
    {
        $studentId  = $this->studentId();
        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = $activeYear['id'] ?? 0;

        // Ambil semua materi published yang sesuai kelas siswa
        $materials  = $this->materialModel->getForStudent($studentId, $yearId);

        // Kelompokkan per mapel
        $grouped = [];
        foreach ($materials as $m) {
            $grouped[$m['subject_id']]['name']      = $m['subject_name'];
            $grouped[$m['subject_id']]['items'][]   = $m;
        }

        // Ambil progress map untuk semua material IDs
        $allIds     = array_column($materials, 'id');
        $progressMap = !empty($allIds)
            ? $this->progressModel->getProgressMap($studentId, $allIds)
            : [];

        // Hitung completion rate per mapel
        foreach ($grouped as $sid => &$grp) {
            $subIds  = array_column($grp['items'], 'id');
            $rate    = $this->progressModel->getStudentCompletionRate($studentId, $subIds);
            $grp['completion'] = $rate;
        }
        unset($grp);

        return view('siswa/materials/index', [
            'title'       => 'Materi Pelajaran',
            'grouped'     => $grouped,
            'progressMap' => $progressMap,
            'activeYear'  => $activeYear,
        ]);
    }

    // =========================================================
    // SHOW — tampilkan satu materi, otomatis catat progress
    // =========================================================
    public function show(int $id): string|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $material  = $this->materialModel->find($id);

        // Validasi: materi harus published
        if (!$material || !$material['is_published']) {
            return redirect()->to('siswa/materials')
                             ->with('error', 'Materi tidak ditemukan atau belum tersedia.');
        }

        // Validasi: materi harus untuk mapel yang diajarkan di kelas siswa
        if (!$this->_canStudentAccess($studentId, $material)) {
            return redirect()->to('siswa/materials')
                             ->with('error', 'Anda tidak memiliki akses ke materi ini.');
        }

        // Catat pembukaan materi (upsert progress → in_progress)
        $this->progressModel->recordOpen($studentId, $id);

        // Ambil progress saat ini
        $progress = $this->progressModel->getOne($studentId, $id);

        // Konversi video URL ke embed URL
        if ($material['content_type'] === 'video' && !empty($material['video_url'])) {
            $material['embed_url'] = SubjectMaterialModel::toEmbedUrl($material['video_url']);
        }

        // Navigasi prev/next dalam mapel yang sama
        $activeYear = $this->yearModel->getActiveYear();
        $siblings   = $this->materialModel->getForStudent($studentId, $activeYear['id'] ?? 0);
        $siblings   = array_values(array_filter(
            $siblings,
            fn($m) => $m['subject_id'] == $material['subject_id']
        ));

        $currentIdx = array_search($id, array_column($siblings, 'id'));
        $prevMat    = $currentIdx > 0 ? $siblings[$currentIdx - 1] : null;
        $nextMat    = $currentIdx !== false && $currentIdx < count($siblings) - 1
                        ? $siblings[$currentIdx + 1]
                        : null;

        return view('siswa/materials/show', [
            'title'    => esc($material['title']),
            'material' => $material,
            'progress' => $progress,
            'prevMat'  => $prevMat,
            'nextMat'  => $nextMat,
        ]);
    }

    // =========================================================
    // MARK COMPLETE — siswa tandai materi sudah selesai (AJAX POST)
    // =========================================================
    public function markComplete(int $id): \CodeIgniter\HTTP\Response
    {
        if (!$this->request->isAJAX() && !$this->request->is('post')) {
            return $this->response->setStatusCode(405)->setJSON(['success' => false]);
        }

        $studentId = $this->studentId();
        $material  = $this->materialModel->find($id);

        if (!$material || !$material['is_published']) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Materi tidak ditemukan.',
            ]);
        }

        if (!$this->_canStudentAccess($studentId, $material)) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Akses ditolak.',
            ]);
        }

        $this->progressModel->markCompleted($studentId, $id);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Materi ditandai selesai!',
        ]);
    }

    // ─── Serve file PDF materi (bukan URL langsung ke folder public) ─────
    public function serveFile(int $id): \CodeIgniter\HTTP\Response|\CodeIgniter\HTTP\RedirectResponse
    {
        $studentId = $this->studentId();
        $material  = $this->materialModel->find($id);

        if (!$material || $material['content_type'] !== 'pdf' || empty($material['file_path'])) {
            return redirect()->to('siswa/materials')->with('error', 'File tidak tersedia.');
        }

        if (!$this->_canStudentAccess($studentId, $material)) {
            return redirect()->to('siswa/materials')->with('error', 'Akses ditolak.');
        }

        $filePath = FCPATH . 'uploads/materials/' . $material['file_path'];
        if (!file_exists($filePath)) {
            return redirect()->to('siswa/materials')->with('error', 'File tidak ditemukan.');
        }

        // Catat juga sebagai view
        $this->progressModel->recordOpen($studentId, $id);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
            ->setBody(file_get_contents($filePath));
    }

    // ─── Cek apakah siswa berhak akses materi (berdasarkan kelas aktif) ──
    private function _canStudentAccess(int $studentId, array $material): bool
    {
        $activeYear = $this->yearModel->getActiveYear();
        if (!$activeYear) return false;

        // Cari class_id aktif siswa
        $record = $this->db->table('student_records')
            ->where('student_id', $studentId)
            ->where('academic_year_id', $activeYear['id'])
            ->where('status', 'aktif')
            ->get()->getRowArray();

        if (!$record) return false;

        // Cek apakah mapel diajarkan di kelas siswa ini
        return (bool) $this->db->table('teaching_assignments')
            ->where('class_id', $record['class_id'])
            ->where('subject_id', $material['subject_id'])
            ->where('academic_year_id', $activeYear['id'])
            ->countAllResults();
    }
}
