<?php

namespace App\Controllers\Siswa;

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

    private function getStudentInfo(): ?array
    {
        $user = session()->get('user');
        if (!$user || !in_array((int)($user['role_id'] ?? 0), [4, 5])) return null;

        $activeYear = (new AcademicYearModel())->getActiveYear();
        $record = $this->db->table('student_records')
            ->where('student_id', $user['related_id'])
            ->where('academic_year_id', $activeYear['id'] ?? 0)
            ->where('status', 'aktif')
            ->get()->getRowArray();

        if (!$record) return null;

        return [
            'student_id' => (int) $user['related_id'],
            'class_id'   => (int) $record['class_id'],
            'user'       => $user,
        ];
    }

    // ─────────────────────────────────────────────
    // INDEX — daftar tugas siswa
    // ─────────────────────────────────────────────
    public function index()
    {
        $info = $this->getStudentInfo();
        if (!$info) return redirect()->to('/login')->with('error', 'Akses ditolak.');

        $tugasList = $this->tugasModel->getForStudent($info['class_id'], $info['student_id']);

        $now = date('Y-m-d H:i:s');
        foreach ($tugasList as &$t) {
            $t['status_waktu'] = TugasModel::getStatus($t);
        }
        unset($t);

        return view('siswa/tugas/index', [
            'title'     => 'Tugas Saya',
            'tugasList' => $tugasList,
        ]);
    }

    // ─────────────────────────────────────────────
    // SHOW — detail + form kerjakan tugas
    // ─────────────────────────────────────────────
    public function show($id)
    {
        $info = $this->getStudentInfo();
        if (!$info) return redirect()->to('/login')->with('error', 'Akses ditolak.');

        $tugas = $this->tugasModel->find($id);
        if (!$tugas || (int)$tugas['class_id'] !== $info['class_id']) {
            return redirect()->to('siswa/tugas')->with('error', 'Tugas tidak ditemukan.');
        }

        $statusWaktu = TugasModel::getStatus($tugas);
        $submission  = $this->submissionModel->getByTugasAndStudent($id, $info['student_id']);
        $subject     = $this->db->table('subjects')->where('id', $tugas['subject_id'])->get()->getRowArray();

        return view('siswa/tugas/show', [
            'title'       => esc($tugas['judul']),
            'tugas'       => $tugas,
            'subject'     => $subject,
            'submission'  => $submission,
            'statusWaktu' => $statusWaktu,
        ]);
    }

    // ─────────────────────────────────────────────
    // SUBMIT — kumpulkan jawaban
    // ─────────────────────────────────────────────
    public function submit($id)
    {
        $info = $this->getStudentInfo();
        if (!$info) return redirect()->to('/login')->with('error', 'Akses ditolak.');

        $tugas = $this->tugasModel->find($id);
        if (!$tugas || (int)$tugas['class_id'] !== $info['class_id']) {
            return redirect()->to('siswa/tugas')->with('error', 'Tugas tidak ditemukan.');
        }

        // Validasi waktu — hanya bisa submit saat status aktif
        if (TugasModel::getStatus($tugas) !== 'aktif') {
            return redirect()->back()->with('error', 'Tugas tidak dapat dikumpulkan di luar batas waktu.');
        }

        $jawaban = $this->request->getPost('jawaban');
        if (empty(strip_tags($jawaban))) {
            return redirect()->back()->with('error', 'Jawaban tidak boleh kosong.');
        }

        $existing = $this->submissionModel->getByTugasAndStudent($id, $info['student_id']);
        $now = date('Y-m-d H:i:s');

        if ($existing) {
            // Update — hanya boleh jika belum dinilai
            if (!empty($existing['nilai'])) {
                return redirect()->back()->with('error', 'Tugas sudah dinilai dan tidak dapat diubah.');
            }
            $this->submissionModel->update($existing['id'], [
                'jawaban'     => $jawaban,
                'dikumpul_at' => $now,
            ]);
        } else {
            $this->submissionModel->insert([
                'tugas_id'    => $id,
                'student_id'  => $info['student_id'],
                'jawaban'     => $jawaban,
                'dikumpul_at' => $now,
            ]);
        }

        return redirect()->to('siswa/tugas/' . $id)->with('success', 'Tugas berhasil dikumpulkan.');
    }

    // ─────────────────────────────────────────────
    // UPLOAD IMAGE — CKEditor di form pengerjaan
    // ─────────────────────────────────────────────
    public function uploadImage()
    {
        $info = $this->getStudentInfo();
        if (!$info || !$this->request->isAJAX()) {
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
