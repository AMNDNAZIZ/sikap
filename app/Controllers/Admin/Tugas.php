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

    private function isHeadmaster(): bool
    {
        return ($this->getUser()['role_id'] ?? 0) == 2;
    }

    /** Kepala sekolah: hanya boleh lihat, tidak boleh buat/edit/hapus */
    private function isViewOnly(): bool
    {
        return $this->isHeadmaster();
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
            // Admin: semua kelas aktif; subjects untuk pre-populate edit mode (kecuali BP/BK)
            $subjects = [];
            if ($forClassId) {
                $activeYear = (new AcademicYearModel())->getActiveYear();
                $subjects   = $this->db->table('teaching_assignments ta')
                    ->select('s.id, s.name')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.class_id', $forClassId)
                    ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
                    ->where('s.is_bk', 0)  // Kecualikan BP/BK dari tugas
                    ->groupBy('s.id')->orderBy('s.name')
                    ->get()->getResultArray();
                // Fallback jika belum ada assignment
                if (empty($subjects)) {
                    $subjects = $this->db->table('subjects')
                        ->select('id, name')->where('is_bk', 0)->orderBy('name')
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
            ->where('s.is_bk', 0)  // Kecualikan BP/BK dari tugas
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
            'title'      => 'Kirim Tugas',
            'tugasList'  => $tugasList,
            'isAdmin'    => $this->isAdmin(),
            'isViewOnly' => $this->isViewOnly(),
        ]);
    }

    // ─────────────────────────────────────────────
    // CREATE — form buat tugas baru
    // ─────────────────────────────────────────────
    public function create()
    {
        if ($this->isViewOnly()) {
            return redirect()->to('admin/tugas')->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

        $data = $this->getClassesAndSubjects();

        return view('admin/tugas/create', [
            'title'         => 'Buat Tugas Baru',
            'classes'       => $data['classes'],
            'subjects'      => $data['subjects'],
            'assignmentMap' => $data['assignmentMap'],
            'isAdmin'       => $this->isAdmin(),
            'isViewOnly'    => $this->isViewOnly(),
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
        if ($this->isViewOnly()) {
            return redirect()->to('admin/tugas')->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

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

        // ── Notifikasi: kirim ke semua siswa di kelas yang mendapat tugas ──
        $subjectName = $this->db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRowArray()['name'] ?? '';
        $deadline    = date('d M Y H:i', strtotime($selesai));
        foreach ($classIds as $cid) {
            $tugasId = $this->db->table('tugas')
                ->where('class_id', $cid)
                ->where('subject_id', $subjectId)
                ->where('created_by', $user['id'])
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->get()->getRowArray()['id'] ?? 0;

            notify_class(
                $cid,
                'tugas_baru',
                'Tugas Baru: ' . $judul,
                "Mapel: {$subjectName} · Deadline: {$deadline}",
                $tugasId ? base_url('siswa/tugas/' . $tugasId) : null,
                'tugas',
                $tugasId ?: null
            );
        }
        // ──────────────────────────────────────────────────────────────────

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

        $students = [];
        if (!empty($tugas['class_id'])) {
            $students = $this->db->table('students st')
                ->select('st.id, st.name, st.nis')
                ->join('student_records sr', 'sr.student_id = st.id')
                ->where('sr.class_id', $tugas['class_id'])
                ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
                ->where('sr.status', 'aktif')
                ->orderBy('st.name')
                ->get()->getResultArray();
        } elseif (!empty($tugas['material_id'])) {
            $students = $this->db->table('students st')
                ->select('st.id, st.name, st.nis')
                ->join('student_records sr', 'sr.student_id = st.id')
                ->join('subject_material_publishes smp', 'smp.class_id = sr.class_id')
                ->where('smp.material_id', $tugas['material_id'])
                ->where('smp.is_active', 1)
                ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
                ->where('sr.status', 'aktif')
                ->groupBy('st.id')
                ->orderBy('st.name')
                ->get()->getResultArray();
        }

        $submissions   = $this->submissionModel->getByTugas($id);
        $submissionMap = array_column($submissions, null, 'student_id');

        $tugas['status'] = TugasModel::getStatus($tugas);

        $class    = !empty($tugas['class_id']) ? $this->db->table('classes')->where('id', $tugas['class_id'])->get()->getRowArray() : null;
        $subject  = $this->db->table('subjects')->where('id', $tugas['subject_id'])->get()->getRowArray();
        $material = !empty($tugas['material_id'])
            ? $this->db->table('subject_materials')->where('id', $tugas['material_id'])->get()->getRowArray()
            : null;
        $segment  = !empty($tugas['segment_id'])
            ? $this->db->table('material_segments')->where('id', $tugas['segment_id'])->get()->getRowArray()
            : null;

        return view('admin/tugas/show', [
            'title'         => 'Detail Tugas: ' . esc($tugas['judul']),
            'tugas'         => $tugas,
            'class'         => $class,
            'subject'       => $subject,
            'material'      => $material,
            'segment'       => $segment,
            'students'      => $students,
            'submissionMap' => $submissionMap,
            'isAdmin'       => $this->isAdmin(),
            'isViewOnly'    => $this->isViewOnly(),
        ]);
    }

    // ─────────────────────────────────────────────
    // EDIT
    // ─────────────────────────────────────────────
    public function edit($id)
    {
        if ($this->isViewOnly()) {
            return redirect()->to('admin/tugas')->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

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
            'isViewOnly'    => $this->isViewOnly(),
        ]);
    }

    // ─────────────────────────────────────────────
    // UPDATE
    // ─────────────────────────────────────────────
    public function update($id)
    {
        if ($this->isViewOnly()) {
            return redirect()->to('admin/tugas')->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

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
        if ($this->isViewOnly()) {
            return redirect()->to('admin/tugas')->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

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
    // NILAI — beri penilaian angka (0-100) ke submission siswa
    // ─────────────────────────────────────────────
    public function nilai($tugasId, $studentId)
    {
        if ($this->isViewOnly()) {
            return redirect()->back()->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

        $tugas = $this->tugasModel->find($tugasId);
        if (!$tugas) {
            return redirect()->back()->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $nilaiAngkaRaw = $this->request->getPost('nilai_angka');
        $nilaiKual     = $this->request->getPost('nilai');
        $catatan       = $this->request->getPost('catatan_guru');
        $user          = $this->getUser();

        // Validasi nilai angka (0 - 100)
        $nilaiAngka = null;
        if ($nilaiAngkaRaw !== null && $nilaiAngkaRaw !== '') {
            $numVal = (float)$nilaiAngkaRaw;
            if ($numVal < 0 || $numVal > 100) {
                return redirect()->back()->with('error', 'Nilai angka harus berada dalam rentang 0 sampai 100.');
            }
            $nilaiAngka = $numVal;

            // Hitung predikat ENUM otomatis dari angka
            if ($nilaiAngka >= 85) {
                $nilaiKual = 'sangat_bagus';
            } elseif ($nilaiAngka >= 70) {
                $nilaiKual = 'bagus';
            } elseif ($nilaiAngka >= 55) {
                $nilaiKual = 'kurang';
            } else {
                $nilaiKual = 'belajar_lagi';
            }
        } elseif (!empty($nilaiKual)) {
            // Fallback jika dikirim predikat lama
            $mapFallback = [
                'sangat_bagus' => 90.0,
                'bagus'        => 80.0,
                'kurang'       => 65.0,
                'belajar_lagi' => 50.0,
            ];
            $nilaiAngka = $mapFallback[$nilaiKual] ?? 75.0;
        } else {
            return redirect()->back()->with('error', 'Masukkan nilai angka tugas (0 - 100).');
        }

        $existing = $this->submissionModel->getByTugasAndStudent($tugasId, $studentId);
        if (!$existing) {
            return redirect()->back()->with('error', 'Siswa belum mengumpulkan tugas.');
        }

        $this->submissionModel->update($existing['id'], [
            'nilai'        => $nilaiKual,
            'nilai_angka'  => $nilaiAngka,
            'catatan_guru' => $catatan,
            'dinilai_at'   => date('Y-m-d H:i:s'),
            'dinilai_oleh' => $user['id'],
        ]);

        // Kirim notifikasi ke siswa dan orang tua
        try {
            helper('notification');
            $labels = [
                'sangat_bagus' => 'Sangat Bagus ⭐⭐⭐⭐',
                'bagus'        => 'Bagus ⭐⭐⭐',
                'kurang'       => 'Cukup / Perlu Ditingkatkan',
                'belajar_lagi' => 'Perlu Belajar Lagi',
            ];
            $labelNilai = $labels[$nilaiKual] ?? ucfirst(str_replace('_', ' ', $nilaiKual));
            $notifMsg   = "Nilai: {$nilaiAngka} ({$labelNilai})" . (!empty($catatan) ? " · Catatan: {$catatan}" : '');

            notify_student_and_parents(
                (int) $studentId,
                'tugas_dinilai',
                'Tugas Dinilai: ' . $tugas['judul'],
                $notifMsg,
                base_url('siswa/tugas/' . $tugasId),
                'tugas',
                (int) $tugasId
            );
        } catch (\Throwable $e) {
            log_message('error', '[Tugas::nilai] Gagal kirim notifikasi penilaian: ' . $e->getMessage());
        }

        return redirect()->to('admin/tugas/' . $tugasId)->with('success', "Penilaian berhasil disimpan (Nilai: {$nilaiAngka}).");
    }

    // ─────────────────────────────────────────────
    // KIRIM KE BUKU NILAI — Formatif / Sumatif (Best Score)
    // ─────────────────────────────────────────────
    public function kirimKeBukuNilai($tugasId)
    {
        if ($this->isViewOnly()) {
            return redirect()->back()->with('error', 'Kepala Sekolah hanya dapat melihat data tugas.');
        }

        $tugas = $this->tugasModel->find($tugasId);
        if (!$tugas) {
            return redirect()->back()->with('error', 'Tugas tidak ditemukan.');
        }
        if (!$this->isAdmin() && $tugas['teacher_id'] != $this->getTeacherId()) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        // Ambil semua submission siswa yang sudah memiliki nilai_angka
        $submissions = $this->submissionModel
            ->where('tugas_id', $tugasId)
            ->where('nilai_angka IS NOT NULL')
            ->findAll();

        if (empty($submissions)) {
            return redirect()->back()->with('error', 'Belum ada siswa yang dinilai dengan angka pada tugas ini.');
        }

        $user = $this->getUser();
        $isFormatif = !empty($tugas['material_id']) || !empty($tugas['segment_id']);
        $countUpdated = 0;
        $now = date('Y-m-d H:i:s');

        $this->db->transStart();

        if ($isFormatif) {
            // ── FORMATIF: Masuk ke material_scores (tipe 'penugasan') ──
            $targetMaterialId = null;
            if (!empty($tugas['material_id'])) {
                $subMat = $this->db->table('subject_materials')->where('id', $tugas['material_id'])->get()->getRowArray();
                if ($subMat) {
                    $parentMatId = !empty($subMat['parent_id']) ? (int)$subMat['parent_id'] : (int)$subMat['id'];
                    $parentMat = $this->db->table('subject_materials')->where('id', $parentMatId)->get()->getRowArray();
                    
                    if ($parentMat) {
                        $atpRow = $this->db->table('alur_tujuan_pembelajaran')
                            ->where('subject_id', $tugas['subject_id'])
                            ->where('lingkup_materi', $parentMat['title'])
                            ->get()->getRowArray();
                        if ($atpRow) {
                            $targetMaterialId = (int)$atpRow['id'];
                        }
                    }
                    if (!$targetMaterialId) {
                        $atpClass = $this->db->table('alur_tujuan_pembelajaran')
                            ->where('subject_id', $tugas['subject_id'])
                            ->where('class_id', $tugas['class_id'])
                            ->get()->getRowArray();
                        $targetMaterialId = $atpClass ? (int)$atpClass['id'] : $parentMatId;
                    }
                }
            }

            if (!$targetMaterialId) {
                $atpAny = $this->db->table('alur_tujuan_pembelajaran')->where('subject_id', $tugas['subject_id'])->get()->getRowArray();
                $targetMaterialId = $atpAny ? (int)$atpAny['id'] : 1;
            }

            foreach ($submissions as $sub) {
                $studentId  = (int)$sub['student_id'];
                $nilaiTugas = (float)$sub['nilai_angka'];

                $existing = $this->db->table('material_scores')
                    ->where('student_id', $studentId)
                    ->where('material_id', $targetMaterialId)
                    ->where('type', 'penugasan')
                    ->get()->getRowArray();

                if ($existing) {
                    // Aturan Nilai Tertinggi: gunakan nilai terbesar (Best Score)
                    $bestScore = max((float)$existing['score'], $nilaiTugas);
                    $this->db->table('material_scores')->where('id', $existing['id'])->update([
                        'score'      => $bestScore,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->db->table('material_scores')->insert([
                        'student_id' => $studentId,
                        'material_id'=> $targetMaterialId,
                        'type'       => 'penugasan',
                        'score'      => $nilaiTugas,
                        'created_by' => $user['id'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $countUpdated++;
            }

            $targetLabel = "Buku Nilai Formatif (ATP Lingkup Materi, Metode: Penugasan)";
        } else {
            // ── SUMATIF: Masuk ke summative_scores (tipe 'penugasan') ──
            $activeYear = (new AcademicYearModel())->getActiveYear();
            $yearId = $activeYear['id'] ?? 1;
            $semester = (string)($activeYear['semester'] ?? '1');

            foreach ($submissions as $sub) {
                $studentId  = (int)$sub['student_id'];
                $nilaiTugas = (float)$sub['nilai_angka'];

                $existing = $this->db->table('summative_scores')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $tugas['subject_id'])
                    ->where('year_id', $yearId)
                    ->where('semester', $semester)
                    ->where('type', 'penugasan')
                    ->get()->getRowArray();

                if ($existing) {
                    // Aturan Nilai Tertinggi: gunakan nilai terbesar (Best Score)
                    $bestScore = max((float)$existing['score'], $nilaiTugas);
                    $this->db->table('summative_scores')->where('id', $existing['id'])->update([
                        'score'      => $bestScore,
                        'updated_at' => $now,
                    ]);
                } else {
                    $this->db->table('summative_scores')->insert([
                        'student_id' => $studentId,
                        'subject_id' => $tugas['subject_id'],
                        'year_id'    => $yearId,
                        'semester'   => $semester,
                        'type'       => 'penugasan',
                        'score'      => $nilaiTugas,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $countUpdated++;
            }

            $targetLabel = "Buku Nilai Sumatif (Semester {$semester}, Tipe: Penugasan)";
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai ke Buku Nilai.');
        }

        return redirect()->to('admin/tugas/' . $tugasId)
            ->with('success', "Berhasil mengirim {$countUpdated} nilai siswa ke {$targetLabel}. Aturan nilai tertinggi (best score) telah diterapkan.");
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
