<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TugasModel;
use App\Models\TugasSubmissionModel;
use App\Models\TeachingAssignmentModel;
use App\Models\AcademicYearModel;
use App\Models\StudentModel;

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

    // ─────────────────────────────────────────────
    // INDEX — daftar tugas
    // ─────────────────────────────────────────────
    public function index()
    {
        $teacherId = $this->isAdmin() ? null : $this->getTeacherId();
        $tugasList = $this->tugasModel->getForTeacher($teacherId);

        $now = date('Y-m-d H:i:s');
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
        $user      = $this->getUser();
        $teacherId = $this->getTeacherId();
        $activeYear = (new AcademicYearModel())->getActiveYear();

        // Ambil kelas & mapel yang diampu
        if ($this->isAdmin()) {
            $classes  = $this->db->table('classes')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
            $subjects = $this->db->table('subjects')->orderBy('name')->get()->getResultArray();
        } else {
            $assignments = $this->db->table('teaching_assignments ta')
                ->select('DISTINCT c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                ->orderBy('c.name')
                ->get()->getResultArray();

            $classes  = array_values(array_unique(array_map(fn($a) => ['id' => $a['class_id'], 'name' => $a['class_name']], $assignments), SORT_REGULAR));
            $subjects = array_values(array_unique(array_map(fn($a) => ['id' => $a['subject_id'], 'name' => $a['subject_name']], $assignments), SORT_REGULAR));
        }

        return view('admin/tugas/create', [
            'title'    => 'Buat Tugas Baru',
            'classes'  => $classes,
            'subjects' => $subjects,
            'isAdmin'  => $this->isAdmin(),
        ]);
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
            'class_id'   => 'required|is_natural_no_zero',
            'subject_id' => 'required|is_natural_no_zero',
            'mulai_at'   => 'required',
            'selesai_at' => 'required',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $mulai   = $this->request->getPost('mulai_at');
        $selesai = $this->request->getPost('selesai_at');

        if ($selesai <= $mulai) {
            return redirect()->back()->withInput()->with('error', 'Waktu selesai harus setelah waktu mulai.');
        }

        $this->tugasModel->insert([
            'teacher_id' => $teacherId,
            'class_id'   => $this->request->getPost('class_id'),
            'subject_id' => $this->request->getPost('subject_id'),
            'judul'      => $this->request->getPost('judul'),
            'deskripsi'  => $this->request->getPost('deskripsi'),
            'mulai_at'   => $mulai,
            'selesai_at' => $selesai,
            'created_by' => $user['id'],
        ]);

        return redirect()->to('admin/tugas')->with('success', 'Tugas berhasil dibuat.');
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

        // Pastikan guru hanya bisa lihat tugasnya sendiri
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        $activeYear = (new AcademicYearModel())->getActiveYear();

        // Ambil semua siswa di kelas tersebut
        $students = $this->db->table('students st')
            ->select('st.id, st.name, st.nis')
            ->join('student_records sr', 'sr.student_id = st.id')
            ->where('sr.class_id', $tugas['class_id'])
            ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
            ->where('sr.status', 'aktif')
            ->orderBy('st.name')
            ->get()->getResultArray();

        // Ambil semua submission
        $submissions = $this->submissionModel->getByTugas($id);
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
        if (!$tugas) return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->to('admin/tugas')->with('error', 'Akses ditolak.');
        }

        $user      = $this->getUser();
        $teacherId = $this->getTeacherId();
        $activeYear = (new AcademicYearModel())->getActiveYear();

        if ($this->isAdmin()) {
            $classes  = $this->db->table('classes')->where('is_active', 1)->orderBy('name')->get()->getResultArray();
            $subjects = $this->db->table('subjects')->orderBy('name')->get()->getResultArray();
        } else {
            $assignments = $this->db->table('teaching_assignments ta')
                ->select('DISTINCT c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                ->orderBy('c.name')
                ->get()->getResultArray();

            $classes  = array_values(array_unique(array_map(fn($a) => ['id' => $a['class_id'], 'name' => $a['class_name']], $assignments), SORT_REGULAR));
            $subjects = array_values(array_unique(array_map(fn($a) => ['id' => $a['subject_id'], 'name' => $a['subject_name']], $assignments), SORT_REGULAR));
        }

        return view('admin/tugas/create', [
            'title'    => 'Edit Tugas',
            'tugas'    => $tugas,
            'classes'  => $classes,
            'subjects' => $subjects,
            'isAdmin'  => $this->isAdmin(),
        ]);
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────
    public function update($id)
    {
        $tugas = $this->tugasModel->find($id);
        if (!$tugas) return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
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
        if (!$tugas) return redirect()->to('admin/tugas')->with('error', 'Tugas tidak ditemukan.');
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
        if (!$tugas) return redirect()->back()->with('error', 'Tugas tidak ditemukan.');
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $nilai       = $this->request->getPost('nilai');
        $catatanGuru = $this->request->getPost('catatan_guru');
        $user        = $this->getUser();

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
            'catatan_guru' => $catatanGuru,
            'dinilai_at'   => date('Y-m-d H:i:s'),
            'dinilai_oleh' => $user['id'],
        ]);

        return redirect()->to('admin/tugas/' . $tugasId)->with('success', 'Penilaian berhasil disimpan.');
    }

    // ─────────────────────────────────────────────
    // UPLOAD IMAGE — untuk CKEditor di form tugas
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
