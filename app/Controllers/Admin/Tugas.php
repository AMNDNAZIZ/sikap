<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TugasModel;
use App\Models\TugasSubmissionModel;
use App\Models\AcademicYearModel;

class Tugas extends BaseController
{
    protected $tugasModel;
    protected $submissionModel;
    protected $db;

    public function __construct()
    {
        $this->tugasModel      = new TugasModel();
        $this->submissionModel = new TugasSubmissionModel();
        $this->db              = \Config\Database::connect();
    }

    private function getUser(): array
    {
        return session()->get('user') ?? [];
    }

    private function isAdmin(): bool
    {
        return in_array($this->getUser()['role_id'] ?? 0, [1, 2]);
    }

    private function getTeacherId(): ?int
    {
        $user = $this->getUser();
        return ($user['role_id'] ?? 0) == 3 ? (int) $user['related_id'] : null;
    }

    /**
     * Ambil daftar kelas dan mapel yang bisa dipilih saat buat/edit tugas.
     * Admin → semua kelas & mapel (mapel diisi AJAX per kelas).
     * Guru  → hanya kelas yang diampu + mapping class_id→subjects dari ploting
     *         tahun ajaran aktif. Tidak perlu AJAX tambahan.
     */
    private function getClassesAndSubjects(?int $forClassId = null): array
    {
        if ($this->isAdmin()) {
            // Admin: semua kelas aktif; subjects untuk pre-populate edit mode
            $subjects = [];
            if ($forClassId) {
                $activeYear = (new AcademicYearModel())->getActiveYear();
                $subjects   = $this->db->table('teaching_assignments ta')
                    ->select('s.id, s.name')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.class_id', $forClassId)
                    ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                    ->groupBy('s.id')->orderBy('s.name')
                    ->get()->getResultArray();
                // Fallback jika belum ada assignment
                if (empty($subjects)) {
                    $subjects = $this->db->table('subjects')
                        ->select('id, name')->orderBy('name')
                        ->get()->getResultArray();
                }
            }
            return [
                'classes'       => $this->db->table('classes')
                    ->where('is_active', 1)->orderBy('name')->get()->getResultArray(),
                'subjects'      => $subjects,
                'assignmentMap' => null, // admin: gunakan AJAX
            ];
        }

        // ── Guru: filter berdasarkan ploting pembelajaran ──────────────────
        $teacherId  = $this->getTeacherId();
        $activeYear = (new AcademicYearModel())->getActiveYear();

        $rows = $this->db->table('teaching_assignments ta')
            ->select('c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
            ->join('classes c', 'c.id = ta.class_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.teacher_id', $teacherId)
            ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
            ->groupBy('c.id, s.id')
            ->orderBy('c.name, s.name')
            ->get()->getResultArray();

        $classes       = [];
        $assignmentMap = []; // class_id => [ ['id'=>..,'name'=>..], .. ]
        $seenClass     = [];

        foreach ($rows as $r) {
            if (!isset($seenClass[$r['class_id']])) {
                $classes[]                 = ['id' => $r['class_id'], 'name' => $r['class_name']];
                $seenClass[$r['class_id']] = true;
            }
            $assignmentMap[$r['class_id']][] = ['id' => $r['subject_id'], 'name' => $r['subject_name']];
        }

        // subjects: ambil dari kelas yang sudah dipilih (edit/old) agar pre-populate benar
        $subjects = $forClassId ? ($assignmentMap[$forClassId] ?? []) : [];

        return [
            'classes'       => $classes,
            'subjects'      => $subjects,
            'assignmentMap' => $assignmentMap,
        ];
    }

    // ─────────────────────────────────────────────
    // INDEX — daftar tugas
    // ─────────────────────────────────────────────
    public function index()
    {
        $teacherId = $this->isAdmin() ? null : $this->getTeacherId();
        $tugasList = $this->tugasModel->getForTeacher($teacherId);

        foreach ($tugasList as &$t) {
            $t['status'] = TugasModel::getStatus($t);
        }
        unset($t);

        return view('admin/tugas/index', [
            'title'     => 'Kirim Tugas',
            'tugasList' => $tugasList,
            'isAdmin'   => $this->isAdmin(),
        ]);
    }

    // ─────────────────────────────────────────────
    // CREATE — form buat tugas baru
    // ─────────────────────────────────────────────
    public function create()
    {
        $data = $this->getClassesAndSubjects();

        return view('admin/tugas/create', [
            'title'         => 'Buat Tugas Baru',
            'classes'       => $data['classes'],
            'subjects'      => $data['subjects'],
            'assignmentMap' => $data['assignmentMap'],
            'isAdmin'       => $this->isAdmin(),
        ]);
    }

    // ─────────────────────────────────────────────
    // AJAX — ambil mapel berdasarkan kelas
    // ─────────────────────────────────────────────
    public function getSubjectsByClass()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $classId   = (int) $this->request->getGet('class_id');
        $teacherId = $this->getTeacherId();

        if (!$classId) {
            return $this->response->setJSON(['success' => true, 'subjects' => []]);
        }

        if ($this->isAdmin()) {
            // Admin: semua mapel yang diajarkan di kelas ini (dari teaching_assignments tahun aktif)
            $activeYear = (new AcademicYearModel())->getActiveYear();
            $rows = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $classId)
                ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                ->groupBy('s.id')
                ->orderBy('s.name')
                ->get()->getResultArray();

            // Fallback: jika belum ada assignment, tampilkan semua mapel
            if (empty($rows)) {
                $rows = $this->db->table('subjects')
                    ->select('id, name')
                    ->orderBy('name')
                    ->get()->getResultArray();
            }
        } else {
            // Guru: hanya mapel yang dia ampu di kelas ini, tahun ajaran aktif
            $activeYear = (new AcademicYearModel())->getActiveYear();
            $rows = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $classId)
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                ->groupBy('s.id')
                ->orderBy('s.name')
                ->get()->getResultArray();
        }

        return $this->response->setJSON(['success' => true, 'subjects' => $rows]);
    }

    // ─────────────────────────────────────────────
    // STORE — simpan tugas baru
    // ─────────────────────────────────────────────
    public function store()
    {
        $user      = $this->getUser();
        $teacherId = $this->getTeacherId();

        $rules = [
            'judul'      => 'required|max_length[255]',
            'subject_id' => 'required|is_natural_no_zero',
            'mulai_at'   => 'required',
            'selesai_at' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Ambil class_ids (multi) atau class_id (edit-fallback)
        $classIds  = $this->request->getPost('class_ids') ?? [];
        if (empty($classIds)) {
            return redirect()->back()->withInput()
                ->with('error', 'Pilih minimal satu kelas untuk tugas ini.');
        }
        $classIds = array_map('intval', array_filter($classIds));

        $subjectId = (int) $this->request->getPost('subject_id');
        $mulai     = $this->request->getPost('mulai_at');
        $selesai   = $this->request->getPost('selesai_at');

        if ($selesai <= $mulai) {
            return redirect()->back()->withInput()->with('error', 'Waktu selesai harus setelah waktu mulai.');
        }

        // Validasi server-side: guru hanya boleh buat tugas sesuai ploting-nya
        if (!$this->isAdmin()) {
            $activeYear = (new AcademicYearModel())->getActiveYear();
            foreach ($classIds as $cid) {
                $valid = $this->db->table('teaching_assignments')
                    ->where('teacher_id',      $teacherId)
                    ->where('class_id',        $cid)
                    ->where('subject_id',      $subjectId)
                    ->where('academic_year_id', $activeYear['id'] ?? 0)
                    ->countAllResults();
                if (!$valid) {
                    return redirect()->back()->withInput()
                        ->with('error', 'Anda tidak memiliki ploting mengajar untuk salah satu kelas yang dipilih.');
                }
            }
        }

        // Insert satu tugas per kelas
        $judul    = $this->request->getPost('judul');
        $deskripsi = $this->request->getPost('deskripsi');
        $inserted  = 0;

        foreach ($classIds as $cid) {
            $this->tugasModel->insert([
                'teacher_id' => $teacherId,
                'class_id'   => $cid,
                'subject_id' => $subjectId,
                'judul'      => $judul,
                'deskripsi'  => $deskripsi,
                'mulai_at'   => $mulai,
                'selesai_at' => $selesai,
                'created_by' => $user['id'],
            ]);
            $inserted++;
        }

        $msg = $inserted > 1
            ? "Tugas berhasil dibuat untuk {$inserted} kelas."
            : 'Tugas berhasil dibuat.';

        return redirect()->to('admin/tugas')->with('success', $msg);
    }

    // ─────────────────────────────────────────────
    // SHOW — detail tugas + daftar submission siswa
    // ─────────────────────────────────────────────
    public function show($id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) {
            return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
        }

        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        $activeYear = (new AcademicYearModel())->getActiveYear();

        $students = $this->db->table('students st')
            ->select('st.id, st.name, st.nis')
            ->join('student_records sr', 'sr.student_id = st.id')
            ->where('sr.class_id', $tugas['class_id'])
            ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
            ->where('sr.status', 'aktif')
            ->orderBy('st.name')
            ->get()->getResultArray();

        $submissions   = $this->submissionModel->getByTugas($id);
        $submissionMap = array_column($submissions, null, 'student_id');

        $tugas['status'] = TugasModel::getStatus($tugas);

        $class   = $this->db->table('classes')->where('id', $tugas['class_id'])->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $tugas['subject_id'])->get()->getRowArray();

        return view('admin/tugas/show', [
            'title'         => 'Detail Tugas: ' . esc($tugas['judul']),
            'tugas'         => $tugas,
            'class'         => $class,
            'subject'       => $subject,
            'students'      => $students,
            'submissionMap' => $submissionMap,
            'isAdmin'       => $this->isAdmin(),
        ]);
    }

    // ─────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────
    public function edit($id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) {
            return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        // Kirim class_id agar subjects langsung ter-pre-populate di view
        $data = $this->getClassesAndSubjects((int) $tugas['class_id']);

        return view('admin/tugas/create', [
            'title'         => 'Edit Tugas',
            'tugas'         => $tugas,
            'classes'       => $data['classes'],
            'subjects'      => $data['subjects'],
            'assignmentMap' => $data['assignmentMap'],
            'isAdmin'       => $this->isAdmin(),
        ]);
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────
    public function update($id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) {
            return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        $mulai   = $this->request->getPost('mulai_at');
        $selesai = $this->request->getPost('selesai_at');

        if ($selesai <= $mulai) {
            return redirect()->back()->withInput()->with('error', 'Waktu selesai harus setelah waktu mulai.');
        }

        $this->tugasModel->update($id, [
            'class_id'   => $this->request->getPost('class_id'),
            'subject_id' => $this->request->getPost('subject_id'),
            'judul'      => $this->request->getPost('judul'),
            'deskripsi'  => $this->request->getPost('deskripsi'),
            'mulai_at'   => $mulai,
            'selesai_at' => $selesai,
        ]);

        return redirect()->to('admin/tugas/' . $id)->with('success', 'Tugas berhasil diperbarui.');
    }

    // ─────────────────────────────────────────────
    // DELETE
    // ─────────────────────────────────────────────
    public function delete($id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) {
            return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        $this->db->table('tugas_submissions')->where('tugas_id', $id)->delete();
        $this->tugasModel->delete($id);

        return redirect()->to('admin/tugas')->with('success', 'Tugas berhasil dihapus.');
    }

    // ─────────────────────────────────────────────
    // NILAI — beri penilaian ke submission siswa
    // ─────────────────────────────────────────────
    public function nilai($tugasId, $studentId)
    {
        $tugas = $this->tugasModel->find($tugasId);
        if (!$tugas) {
            return redirect()->back()->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $nilai      = $this->request->getPost('nilai');
        $catatan    = $this->request->getPost('catatan_guru');
        $user       = $this->getUser();

        $nilaiValid = ['sangat_bagus', 'bagus', 'kurang', 'belajar_lagi'];
        if (!in_array($nilai, $nilaiValid)) {
            return redirect()->back()->with('error', 'Nilai tidak valid.');
        }

        $existing = $this->submissionModel->getByTugasAndStudent($tugasId, $studentId);
        if (!$existing) {
            return redirect()->back()->with('error', 'Siswa belum mengumpulkan tugas.');
        }

        $this->submissionModel->update($existing['id'], [
            'nilai'        => $nilai,
            'catatan_guru' => $catatan,
            'dinilai_at'   => date('Y-m-d H:i:s'),
            'dinilai_oleh' => $user['id'],
        ]);

        return redirect()->to('admin/tugas/' . $tugasId)->with('success', 'Penilaian berhasil disimpan.');
    }

    // ─────────────────────────────────────────────
    // UPLOAD IMAGE — CKEditor
    // ─────────────────────────────────────────────
    public function uploadImage()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403)->setJSON(['success' => false]);
        }

        $file = $this->request->getFile('file');
        if (!$file || !$file->isValid()) {
            return $this->response->setJSON(['success' => false, 'message' => 'File tidak valid.']);
        }

        $allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file->getMimeType(), $allowed)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya gambar yang diizinkan.']);
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return $this->response->setJSON(['success' => false, 'message' => 'Ukuran maksimal 5MB.']);
        }

        $newName    = $file->getRandomName();
        $uploadPath = UPLOAD_PATH . 'tugas/';
        if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

        $file->move($uploadPath, $newName);

        return $this->response->setJSON([
            'success' => true,
            'url'     => base_url('uploads/tugas/' . $newName),
        ]);
    }
}
