<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\SchoolModel;
use App\Models\SettingsModel;
use App\Models\RaporNoteModel;
use App\Models\RaporConfigModel;

/**
 * Controller Rapor Semester (Laporan Hasil Belajar Akhir Semester)
 * Standar Kurikulum Merdeka (Permendikbudristek 12/2024 & Panduan PPA 2024)
 * - Kelengkapan Awal: Cover Depan, Petunjuk, Identitas Sekolah & Peserta Didik
 * - Tata Letak Nilai: Tanpa Kop, Identitas 2 Kolom, Garis, Judul
 *   Lembar 1: Intrakurikuler (Nilai Akhir + Deskripsi Capaian TP tanpa Predikat)
 *   Lembar 2: Identitas 2 Kolom, Kokurikuler, Ekskul, Presensi, Catatan Wali, Kenaikan Kelas, Tanggapan Orang Tua
 * - Opsi Layout Tanda Tangan: 1 Baris (3 Kolom) vs 2 Baris (Segitiga)
 * - Alternatif Foto Tanda Tangan Guru & Kepala Sekolah
 * - Watermark Logo Transparan di Tiap Lembar Rapor
 * - Catatan Mutasi Siswa (Pindah Masuk & Keluar)
 */
class RaporSemester extends BaseController
{
    protected $db;
    protected $yearModel;
    protected $schoolModel;
    protected $settingsModel;
    protected $noteModel;
    protected $configModel;

    public function __construct()
    {
        $this->db            = \Config\Database::connect();
        $this->yearModel     = new AcademicYearModel();
        $this->schoolModel   = new SchoolModel();
        $this->settingsModel = new SettingsModel();
        $this->noteModel     = new RaporNoteModel();
        $this->configModel   = new RaporConfigModel();
    }

    /**
     * Konversi jenjang kelas ke Fase Kurikulum Merdeka
     */
    protected function levelToFase(int $level): string
    {
        if ($level <= 0)  return '-';
        if ($level <= 2)  return 'A';
        if ($level <= 4)  return 'B';
        if ($level <= 6)  return 'C';
        if ($level <= 8)  return 'D';
        if ($level === 9) return 'E';
        return 'F';
    }

    /**
     * Helper: Konversi file gambar ke base64 data URI agar aman di cetak/offline
     */
    protected function imageToBase64(?string $path): string
    {
        if (empty($path)) return '';
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        $candidatePaths = [
            FCPATH . $cleanPath,
            FCPATH . 'uploads/logo/' . $cleanPath,
            FCPATH . 'uploads/' . $cleanPath,
            ROOTPATH . 'public/' . $cleanPath,
            ROOTPATH . 'public/uploads/logo/' . $cleanPath,
            ROOTPATH . 'public/uploads/' . $cleanPath,
        ];

        $abs = null;
        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate) && !is_dir($candidate)) {
                $abs = $candidate;
                break;
            }
        }

        if (!$abs) {
            return '';
        }

        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = in_array($ext, ['jpg', 'jpeg']) ? 'jpeg' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'gif'));
        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
    }

    /**
     * Verifikasi hak akses kelas: Admin, Kepsek, atau Wali Kelas terkait
     */
    protected function canAccessClass(int $classId): bool
    {
        $user   = session()->get('user');
        $roleId = (int) ($user['role_id'] ?? 0);

        if (in_array($roleId, [1, 2], true)) {
            return true; // Admin & Kepsek
        }
        if ($roleId === 3) {
            $teacherId = (int) ($user['related_id'] ?? 0);
            $cls = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            return $cls && (int) $cls['teacher_id'] === $teacherId;
        }
        return false;
    }

    // =========================================================
    // INDEX — Dashboard Kelas Rapor Semester
    // =========================================================
    public function index()
    {
        $user   = session()->get('user');
        $roleId = (int) ($user['role_id'] ?? 0);

        if (!in_array($roleId, [1, 2, 3], true)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $activeYear   = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        if (!$activeYearId) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $selectedSemester = (string) $this->request->getGet('semester');
        if (!in_array($selectedSemester, ['1', '2'], true)) {
            $selectedSemester = (string) ($activeYear['semester'] ?? '1');
            if ($selectedSemester === 'ganjil') $selectedSemester = '1';
            if ($selectedSemester === 'genap')  $selectedSemester = '2';
            if (!in_array($selectedSemester, ['1', '2'], true)) $selectedSemester = '1';
        }

        $builder = $this->db->table('classes c')
            ->select('c.id, c.name, c.level, t.name as wali_name, t.id as wali_id')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.is_active', 1);

        if ($roleId === 3) {
            $teacherId = (int) ($user['related_id'] ?? 0);
            $builder->where('c.teacher_id', $teacherId);
        }

        $classes = $builder->orderBy('c.level', 'ASC')->orderBy('c.name', 'ASC')->get()->getResultArray();

        $classStats = [];
        foreach ($classes as $cls) {
            $cid = (int) $cls['id'];

            $studentCount = $this->db->table('student_records')
                ->where('class_id', $cid)
                ->where('academic_year_id', $activeYearId)
                ->where('status', 'aktif')
                ->countAllResults();

            $subjectCount = $this->db->table('teaching_assignments')
                ->where('class_id', $cid)
                ->where('academic_year_id', $activeYearId)
                ->countAllResults();

            $notesCount = $this->db->table('rapor_notes')
                ->where('class_id', $cid)
                ->where('academic_year_id', $activeYearId)
                ->where('semester', $selectedSemester)
                ->where('teacher_note IS NOT NULL')
                ->where("teacher_note !=", '')
                ->countAllResults();

            $kokuCount = $this->db->table('kokurikuler_documents')
                ->where('year_id', $activeYearId)
                ->where('semester', $selectedSemester)
                ->groupStart()
                    ->where('class_id', $cid)
                    ->orWhere('level_kelas', (string) $cls['level'])
                ->groupEnd()
                ->countAllResults();

            $classStats[$cid] = [
                'students' => $studentCount,
                'subjects' => $subjectCount,
                'notes'    => $notesCount,
                'has_koku' => $kokuCount > 0,
            ];
        }

        $config = $this->configModel->getConfig($activeYearId, $selectedSemester);
        $school = $this->schoolModel->getProfile();

        return view('admin/rapor_semester/index', [
            'title'            => 'Rapor Semester (Kurikulum Merdeka)',
            'classes'          => $classes,
            'classStats'       => $classStats,
            'activeYear'       => $activeYear,
            'selectedSemester' => $selectedSemester,
            'config'           => $config,
            'school'           => $school,
            'roleId'           => $roleId,
        ]);
    }

    // =========================================================
    // KELAS DETAIL — Ruang Kerja Wali Kelas
    // =========================================================
    public function kelasDetail($classId)
    {
        $classId = (int) $classId;
        if (!$this->canAccessClass($classId)) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Anda tidak memiliki akses ke kelas ini.');
        }

        $activeYear   = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        if (!$activeYearId) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $semester = (string) $this->request->getGet('semester');
        if (!in_array($semester, ['1', '2'], true)) {
            $semester = (string) ($activeYear['semester'] ?? '1');
            if ($semester === 'ganjil') $semester = '1';
            if ($semester === 'genap')  $semester = '2';
            if (!in_array($semester, ['1', '2'], true)) $semester = '1';
        }

        $class = $this->db->table('classes c')
            ->select('c.*, t.name as wali_name, t.nip as wali_nip, t.signature as wali_signature')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.id', $classId)
            ->get()->getRowArray();
        if (!$class) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Kelas tidak ditemukan.');
        }

        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.nisn, s.nis, s.religion')
            ->join('students s', 's.id = sr.student_id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $notesMap = $this->noteModel->getNotesByClass($classId, $activeYearId, $semester);
        $config   = $this->configModel->getConfig($activeYearId, $semester);
        $school   = $this->schoolModel->getProfile();

        $targetClasses = $this->db->table('classes')
            ->where('is_active', 1)
            ->orderBy('level', 'ASC')
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $kokuDocs = $this->db->table('kokurikuler_documents')
            ->where('year_id', $activeYearId)
            ->where('semester', $semester)
            ->groupStart()
                ->where('class_id', $classId)
                ->orWhere('level_kelas', (string) $class['level'])
            ->groupEnd()
            ->get()->getResultArray();

        return view('admin/rapor_semester/detail', [
            'title'         => 'Kelola Rapor Semester – ' . $class['name'],
            'class'         => $class,
            'activeYear'    => $activeYear,
            'semester'      => $semester,
            'students'      => $students,
            'notesMap'      => $notesMap,
            'config'        => $config,
            'school'        => $school,
            'targetClasses' => $targetClasses,
            'kokuDocs'      => $kokuDocs,
        ]);
    }

    // =========================================================
    // SAVE NOTE — Simpan Catatan Wali Kelas & Status Kenaikan (AJAX)
    // =========================================================
    public function saveNote()
    {
        $studentId   = (int) $this->request->getPost('student_id');
        $classId     = (int) $this->request->getPost('class_id');
        $yearId      = (int) $this->request->getPost('year_id');
        $semester    = (string) $this->request->getPost('semester');
        $teacherNote = trim((string) $this->request->getPost('teacher_note'));
        $promotion   = $this->request->getPost('promotion_status') ?: null;
        $targetClass = trim((string) $this->request->getPost('promotion_class_name')) ?: null;

        if (!$studentId || !$classId || !$yearId || !$semester) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        }

        if (!$this->canAccessClass($classId)) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $data = [
            'student_id'           => $studentId,
            'academic_year_id'     => $yearId,
            'semester'             => $semester,
            'class_id'             => $classId,
            'teacher_note'         => $teacherNote,
            'promotion_status'     => $promotion,
            'promotion_class_name' => $targetClass,
        ];

        $saved = $this->noteModel->saveNote($data);

        return $this->response->setJSON([
            'status'  => $saved ? 'success' : 'error',
            'message' => $saved ? 'Catatan & status berhasil disimpan.' : 'Gagal menyimpan catatan.',
        ]);
    }

    // =========================================================
    // SAVE CONFIG — Simpan Titimangsa & Pengaturan Cetak (AJAX)
    // =========================================================
    public function saveConfig()
    {
        $yearId     = (int) $this->request->getPost('year_id');
        $semester   = (string) $this->request->getPost('semester');
        $reportDate = $this->request->getPost('report_date') ?: null;
        $placeCity  = trim((string) $this->request->getPost('place_city')) ?: null;
        $ttdLayout  = $this->request->getPost('ttd_layout') ?: '1_baris';
        $showSig    = (int) ($this->request->getPost('show_signature') ? 1 : 0);
        $showWm     = (int) ($this->request->getPost('show_watermark') ? 1 : 0);

        if (!$yearId || !$semester) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        }

        $saved = $this->configModel->saveConfig($yearId, $semester, $reportDate, $placeCity, $ttdLayout, $showSig, $showWm);

        return $this->response->setJSON([
            'status'  => $saved ? 'success' : 'error',
            'message' => $saved ? 'Pengaturan cetak & titimangsa berhasil disimpan.' : 'Gagal menyimpan pengaturan.',
        ]);
    }

    // =========================================================
    // UPLOAD SIGNATURE — Upload Tanda Tangan Guru / Kepala Sekolah
    // =========================================================
    public function uploadSignature()
    {
        $type      = $this->request->getPost('type'); // 'teacher' atau 'principal'
        $teacherId = (int) $this->request->getPost('teacher_id');
        $file      = $this->request->getFile('signature_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File tidak valid atau gagal diunggah.']);
        }

        $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array(strtolower($file->getExtension()), $allowedExt, true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Format file harus PNG, JPG, atau WEBP. Disarankan PNG transparan.']);
        }

        $targetDir = FCPATH . 'uploads/signatures/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newName = ($type === 'principal' ? 'principal_' : 'teacher_' . $teacherId . '_') . time() . '.' . $file->getExtension();
        $file->move($targetDir, $newName);
        $relPath = 'uploads/signatures/' . $newName;

        if ($type === 'principal') {
            $school = $this->schoolModel->getProfile();
            if ($school) {
                $this->schoolModel->update($school['id'], ['principal_signature' => $relPath]);
            }
        } elseif ($type === 'teacher' && $teacherId > 0) {
            $this->db->table('teachers')->where('id', $teacherId)->update(['signature' => $relPath]);
        }

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Tanda tangan berhasil diunggah.',
            'filepath' => base_url($relPath),
        ]);
    }

    // =========================================================
    // UPLOAD REGIONAL LOGO — Upload Logo Daerah (AJAX)
    // =========================================================
    public function uploadRegionalLogo()
    {
        $file = $this->request->getFile('regional_logo_file');

        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'File tidak valid atau gagal diunggah.']);
        }

        $allowedExt = ['png', 'jpg', 'jpeg', 'webp'];
        if (!in_array(strtolower($file->getExtension()), $allowedExt, true)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Format file harus PNG, JPG, atau WEBP. Disarankan PNG transparan.']);
        }

        $targetDir = FCPATH . 'uploads/logo/';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newName = 'regional_logo_' . time() . '.' . $file->getExtension();
        $file->move($targetDir, $newName);
        $relPath = 'uploads/logo/' . $newName;

        $school = $this->schoolModel->getProfile();
        if ($school) {
            $this->schoolModel->update($school['id'], ['regional_logo' => $relPath]);
        }

        return $this->response->setJSON([
            'status'   => 'success',
            'message'  => 'Logo daerah berhasil diunggah.',
            'filepath' => base_url($relPath),
        ]);
    }

    // =========================================================
    // CETAK KELAS & SISWA — Bagian Nilai (Lembar 1 & Lembar 2)
    // =========================================================
    public function cetakKelas($classId, $semester)
    {
        return $this->renderPrint((int) $classId, (string) $semester, null);
    }

    public function cetakSiswa($classId, $semester, $studentId)
    {
        return $this->renderPrint((int) $classId, (string) $semester, (int) $studentId);
    }

    /**
     * Mesin Cetak Bagian Nilai Rapor Semester
     */
    protected function renderPrint(int $classId, string $semester, ?int $singleStudentId = null)
    {
        if (!in_array($semester, ['1', '2'], true)) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Semester tidak valid.');
        }

        if (!$this->canAccessClass($classId)) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Anda tidak memiliki akses ke kelas ini.');
        }

        $activeYear   = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        if (!$activeYearId) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $class = $this->db->table('classes c')
            ->select('c.*, t.name as wali_name, t.nip as wali_nip, t.signature as wali_signature')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.id', $classId)
            ->get()->getRowArray();
        if (!$class) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Kelas tidak ditemukan.');
        }

        $school     = $this->schoolModel->getProfile();
        $classLevel = (int) ($class['level'] ?? 0);
        $fase       = $this->levelToFase($classLevel);

        // Ambil konfigurasi titimangsa, layout tanda tangan, dan watermark
        $config = $this->configModel->getConfig($activeYearId, $semester);
        $titimangsaDate = !empty($config['report_date']) ? date('d F Y', strtotime($config['report_date'])) : date('d F Y');
        $titimangsaCity = !empty($config['place_city']) ? $config['place_city'] : ($school['city_regency'] ?? 'Kota/Kab.');
        $ttdLayout      = $config['ttd_layout'] ?? '1_baris';
        $showSignature  = !empty($config['show_signature']);
        $showWatermark  = !empty($config['show_watermark']);

        // Base64 Logo & TTD Kepala Sekolah
        $logoBase64         = $this->imageToBase64($school['logo'] ?? '');
        $principalSigBase64 = $this->imageToBase64($school['principal_signature'] ?? '');
        $waliSigBase64      = $this->imageToBase64($class['wali_signature'] ?? '');

        // Kumpulkan mapel aktif non-BK
        $bkKeywords = ['%Bimbingan%', '%Konseling%'];

        $subjectsRegular = $this->db->table('subjects s')
            ->select('s.id, s.name, s.subject_group, s.sort_order, s.religion')
            ->distinct()
            ->join('teaching_assignments ta', 'ta.subject_id = s.id AND ta.class_id = ' . $classId . ' AND ta.academic_year_id = ' . (int) $activeYearId, 'inner')
            ->where('s.is_active', 1)
            ->where("(s.religion IS NULL OR s.religion = '')")
            ->groupStart()
                ->where('s.name NOT LIKE', $bkKeywords[0])
                ->where('s.name NOT LIKE', $bkKeywords[1])
            ->groupEnd()
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $subjectsAgama = $this->db->table('subjects s')
            ->select('s.id, s.name, s.subject_group, s.sort_order, s.religion')
            ->where('s.is_active', 1)
            ->where("s.religion IS NOT NULL")
            ->where("s.religion !=", '')
            ->groupStart()
                ->where('s.name NOT LIKE', $bkKeywords[0])
                ->where('s.name NOT LIKE', $bkKeywords[1])
            ->groupEnd()
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $subjectMap = [];
        foreach (array_merge($subjectsAgama, $subjectsRegular) as $s) {
            $subjectMap[$s['id']] = $s;
        }

        if (empty($subjectMap)) {
            $all = $this->db->table('subjects')
                ->select('id, name, subject_group, sort_order, religion')
                ->where('is_active', 1)
                ->groupStart()
                    ->where('name NOT LIKE', $bkKeywords[0])
                    ->where('name NOT LIKE', $bkKeywords[1])
                ->groupEnd()
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
            foreach ($all as $s) $subjectMap[$s['id']] = $s;
        }

        uasort($subjectMap, function ($a, $b) {
            $diff = (int)$a['sort_order'] - (int)$b['sort_order'];
            return $diff !== 0 ? $diff : strcmp($a['name'], $b['name']);
        });
        $subjects = array_values($subjectMap);

        // Siswa yang akan dicetak
        $stuBuilder = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.nisn, s.nis, s.religion')
            ->join('students s', 's.id = sr.student_id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif');

        if ($singleStudentId) {
            $stuBuilder->where('s.id', $singleStudentId);
        }

        $students = $stuBuilder->orderBy('s.name', 'ASC')->get()->getResultArray();

        // Dokumen Kokurikuler
        $kokuDocs = $this->db->table('kokurikuler_documents')
            ->where('year_id', $activeYearId)
            ->where('semester', $semester)
            ->groupStart()
                ->where('class_id', $classId)
                ->orWhere('level_kelas', (string) $class['level'])
            ->groupEnd()
            ->get()->getResultArray();

        $notesMap      = $this->noteModel->getNotesByClass($classId, $activeYearId, $semester);
        $semesterLabel = $semester === '1' ? 'ganjil' : 'genap';
        $semValues     = ($semester === '2') ? ['2', 'genap'] : ['1', 'ganjil'];

        $reportData = [];
        foreach ($students as $stu) {
            $sid = (int) $stu['id'];

            // 1. INTRAKURIKULER
            $rows = [];
            foreach ($subjects as $subj) {
                $subjectId = (int) $subj['id'];

                $subjReligion = $subj['religion'] ?? '';
                if ($subjReligion !== '' && $subjReligion !== null) {
                    $stuReligion = $stu['religion'] ?? '';
                    if (strcasecmp(trim($subjReligion), trim($stuReligion)) !== 0) {
                        continue;
                    }
                }

                $gradeRow = $this->db->table('grades')
                    ->where('student_id', $sid)
                    ->where('subject_id', $subjectId)
                    ->where('year_id', $activeYearId)
                    ->where('semester', $semesterLabel)
                    ->get()->getRowArray();

                $nilaiAkhir = null;
                $deskripsi  = '';

                if ($gradeRow && $gradeRow['erapor_score'] !== null) {
                    $nilaiAkhir = round((float) $gradeRow['erapor_score'], 0);
                    $deskripsi  = trim((string) ($gradeRow['competency_desc'] ?? ''));
                } else {
                    $materiAvg = $this->db->table('material_scores ms')
                        ->select('AVG(ms.score) as avg_score')
                        ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id', 'inner')
                        ->where('ms.student_id', $sid)
                        ->where('atp.subject_id', $subjectId)
                        ->where('ms.score IS NOT NULL')
                        ->get()->getRowArray();
                    $nilaiMateri = isset($materiAvg['avg_score']) ? (float)$materiAvg['avg_score'] : null;

                    $ptsRow = $this->db->table('pts_scores')
                        ->select('score')
                        ->where('student_id', $sid)
                        ->where('subject_id', $subjectId)
                        ->where('year_id', $activeYearId)
                        ->where('semester', $semester)
                        ->get()->getRowArray();
                    $nilaiPts = isset($ptsRow['score']) ? (float)$ptsRow['score'] : null;

                    if ($nilaiMateri !== null && $nilaiPts !== null) {
                        $nilaiAkhir = round(($nilaiMateri * 0.6) + ($nilaiPts * 0.4), 0);
                    } elseif ($nilaiMateri !== null) {
                        $nilaiAkhir = round($nilaiMateri, 0);
                    } elseif ($nilaiPts !== null) {
                        $nilaiAkhir = round($nilaiPts, 0);
                    }
                }

                if (empty($deskripsi)) {
                    if ($nilaiAkhir !== null) {
                        if ($nilaiAkhir >= 85) {
                            $deskripsi = "Menunjukkan penguasaan yang sangat baik dalam mencapai seluruh tujuan pembelajaran mata pelajaran ini.";
                        } elseif ($nilaiAkhir >= 75) {
                            $deskripsi = "Menunjukkan penguasaan yang baik dalam mencapai tujuan pembelajaran, terus pertahankan dan tingkatkan prestasi.";
                        } else {
                            $deskripsi = "Cukup menguasai materi pembelajaran, perlu bimbingan dan latihan lebih giat untuk optimalisasi capaian kompetensi.";
                        }
                    } else {
                        $deskripsi = "-";
                    }
                }

                $rows[] = [
                    'subject_name' => $subj['name'],
                    'nilai'        => $nilaiAkhir,
                    'deskripsi'    => $deskripsi,
                ];
            }

            // 2. KOKURIKULER (Terintegrasi)
            $kokurikulerList = [];
            foreach ($kokuDocs as $kDoc) {
                $docId = (int) $kDoc['id'];
                $penilaian = $this->db->table('kokurikuler_penilaian')
                    ->where('document_id', $docId)
                    ->where('student_id', $sid)
                    ->get()->getRowArray();

                $narasiKoku = '';
                if ($penilaian && !empty($penilaian['catatan_tambahan'])) {
                    $narasiKoku = trim($penilaian['catatan_tambahan']);
                } else {
                    $kegiatan = !empty($kDoc['bentuk_kegiatan_konkret']) ? $kDoc['bentuk_kegiatan_konkret'] : $kDoc['tema'];
                    $narasiKoku = "Ananda aktif berpartisipasi dan menunjukkan perkembangan karakter serta dimensi profil yang sangat baik dalam projek {$kegiatan}.";
                }

                $kokurikulerList[] = [
                    'tema'      => $kDoc['tema'],
                    'kegiatan'  => $kDoc['bentuk_kegiatan_konkret'] ?: $kDoc['tema'],
                    'deskripsi' => $narasiKoku,
                ];
            }

            // 3. EKSTRAKURIKULER
            $ekskul = $this->db->table('ekskul_scores es')
                ->select('em.name as ekskul_name, es.predicate, es.description')
                ->join('ekskul_master em', 'em.id = es.ekskul_id')
                ->where('es.student_id', $sid)
                ->where('es.academic_year_id', $activeYearId)
                ->whereIn('es.semester', $semValues)
                ->get()->getResultArray();

            // 4. PRESENSI
            $att = [
                'sakit' => $this->db->table('attendances')
                    ->where('student_id', $sid)
                    ->where('class_id', $classId)
                    ->where('status', 'S')
                    ->countAllResults(),
                'izin'  => $this->db->table('attendances')
                    ->where('student_id', $sid)
                    ->where('class_id', $classId)
                    ->where('status', 'I')
                    ->countAllResults(),
                'alpa'  => $this->db->table('attendances')
                    ->where('student_id', $sid)
                    ->where('class_id', $classId)
                    ->where('status', 'A')
                    ->countAllResults(),
            ];

            // 5. CATATAN WALI KELAS & KENAIKAN KELAS
            $noteData = $notesMap[$sid] ?? null;
            $waliNote = $noteData['teacher_note'] ?? 'Tingkatkan terus semangat belajar dan pertahankan prestasi yang telah dicapai.';
            $promStatus = $noteData['promotion_status'] ?? 'naik';
            $promClass  = $noteData['promotion_class_name'] ?? '';

            $reportData[] = [
                'student'                => $stu,
                'rows'                   => $rows,
                'kokurikuler'            => $kokurikulerList,
                'ekskul'                 => $ekskul,
                'attendance'             => $att,
                'wali_note'              => $waliNote,
                'prom_status'            => $promStatus,
                'prom_class'             => $promClass,
                'wali_signature_base64'  => $waliSigBase64,
            ];
        }

        return view('admin/rapor_semester/print', [
            'class'              => $class,
            'school'             => $school,
            'semester'           => $semester,
            'activeYear'         => $activeYear,
            'reportData'         => $reportData,
            'fase'               => $fase,
            'titimangsaDate'     => $titimangsaDate,
            'titimangsaCity'     => $titimangsaCity,
            'ttdLayout'          => $ttdLayout,
            'showSignature'      => $showSignature,
            'showWatermark'      => $showWatermark,
            'logoBase64'         => $logoBase64,
            'principalSigBase64' => $principalSigBase64,
            'isSingle'           => $singleStudentId !== null,
        ]);
    }

    // =========================================================
    // CETAK BAGIAN AWAL (Cover, Petunjuk & Identitas Siswa)
    // =========================================================
    public function cetakAwal($classId)
    {
        return $this->renderAwal((int) $classId, null);
    }

    public function cetakAwalSiswa($classId, $studentId)
    {
        return $this->renderAwal((int) $classId, (int) $studentId);
    }

    protected function renderAwal(int $classId, ?int $studentId = null)
    {
        if (!$this->canAccessClass($classId)) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Akses ditolak.');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $class = $this->db->table('classes c')
            ->select('c.*, t.name as wali_name, t.nip as wali_nip')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.id', $classId)
            ->get()->getRowArray();

        $stuBuilder = $this->db->table('students s')
            ->select('s.*, sr.class_id')
            ->join('student_records sr', 'sr.student_id = s.id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYear['id'])
            ->where('sr.status', 'aktif');

        if ($studentId) {
            $stuBuilder->where('s.id', $studentId);
        }

        $students = $stuBuilder->orderBy('s.name', 'ASC')->get()->getResultArray();
        $school   = $this->schoolModel->getProfile();

        $config   = $this->configModel->getConfig($activeYear['id'], '1');
        $titimangsaDate = !empty($config['report_date']) ? date('d F Y', strtotime($config['report_date'])) : date('d F Y');
        $titimangsaCity = !empty($config['place_city']) ? $config['place_city'] : ($school['city_regency'] ?? 'Kota/Kab.');
        $showSignature  = !empty($config['show_signature']);
        $showWatermark  = !empty($config['show_watermark']);

        $logoBase64         = $this->imageToBase64($school['logo'] ?? '');
        if (empty($logoBase64)) {
            $logoBase64 = $this->imageToBase64('uploads/logo/logo_sekolah_from_cover.png');
        }
        $principalSigBase64 = $this->imageToBase64($school['principal_signature'] ?? '');

        // Logo Daerah (Pemerintah Provinsi / Kota / Kabupaten)
        $regionalLogoPath = !empty($school['regional_logo']) ? $school['regional_logo'] : 'uploads/logo/logo_daerah_default.png';
        $regionalLogoBase64 = $this->imageToBase64($regionalLogoPath);
        if (empty($regionalLogoBase64)) {
            $regionalLogoBase64 = $this->imageToBase64('uploads/logo/logo_daerah_default.png');
        }

        // Tentukan Jenjang Pendidikan Dinamis Berdasarkan Level Sekolah
        $schoolLevel = isset($school['level']) ? (int) $school['level'] : 0;
        $classLevel  = isset($class['level']) ? (int) $class['level'] : 0;

        if ($schoolLevel === 1) {
            $jenjangText = 'SEKOLAH DASAR';
            $jenjangCode = '( SD )';
        } elseif ($schoolLevel === 2) {
            $jenjangText = 'SEKOLAH MENENGAH PERTAMA';
            $jenjangCode = '( SMP )';
        } elseif ($schoolLevel === 3) {
            $jenjangText = 'SEKOLAH MENENGAH ATAS';
            $jenjangCode = '( SMA )';
        } elseif ($schoolLevel === 4) {
            $jenjangText = 'SEKOLAH MENENGAH KEJURUAN';
            $jenjangCode = '( SMK )';
        } else {
            // Fallback jika level sekolah di profil belum dipilih/kosong
            if ($classLevel >= 1 && $classLevel <= 6) {
                $jenjangText = 'SEKOLAH DASAR';
                $jenjangCode = '( SD )';
            } elseif ($classLevel >= 7 && $classLevel <= 9) {
                $jenjangText = 'SEKOLAH MENENGAH PERTAMA';
                $jenjangCode = '( SMP )';
            } elseif ($classLevel >= 10 && $classLevel <= 12) {
                $jenjangText = 'SEKOLAH MENENGAH ATAS';
                $jenjangCode = '( SMA )';
            } elseif (stripos($school['name'] ?? '', 'SD') !== false) {
                $jenjangText = 'SEKOLAH DASAR';
                $jenjangCode = '( SD )';
            } elseif (stripos($school['name'] ?? '', 'SMA') !== false) {
                $jenjangText = 'SEKOLAH MENENGAH ATAS';
                $jenjangCode = '( SMA )';
            } elseif (stripos($school['name'] ?? '', 'SMK') !== false) {
                $jenjangText = 'SEKOLAH MENENGAH KEJURUAN';
                $jenjangCode = '( SMK )';
            } else {
                $jenjangText = 'SEKOLAH MENENGAH PERTAMA';
                $jenjangCode = '( SMP )';
            }
        }

        return view('admin/rapor_semester/cover_print', [
            'class'              => $class,
            'school'             => $school,
            'activeYear'         => $activeYear,
            'students'           => $students,
            'titimangsaDate'     => $titimangsaDate,
            'titimangsaCity'     => $titimangsaCity,
            'showSignature'      => $showSignature,
            'showWatermark'      => $showWatermark,
            'logoBase64'         => $logoBase64,
            'regionalLogoBase64' => $regionalLogoBase64,
            'jenjangText'        => $jenjangText,
            'jenjangCode'        => $jenjangCode,
            'principalSigBase64' => $principalSigBase64,
            'isSingle'           => $studentId !== null,
        ]);
    }

    // =========================================================
    // CETAK BAGIAN AKHIR (Lembar Catatan Mutasi Siswa)
    // =========================================================
    public function cetakMutasi($classId)
    {
        return $this->renderMutasi((int) $classId, null);
    }

    public function cetakMutasiSiswa($classId, $studentId)
    {
        return $this->renderMutasi((int) $classId, (int) $studentId);
    }

    protected function renderMutasi(int $classId, ?int $studentId = null)
    {
        if (!$this->canAccessClass($classId)) {
            return redirect()->to('admin/rapor-semester')->with('error', 'Akses ditolak.');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        $stuBuilder = $this->db->table('students s')
            ->select('s.*, sr.class_id')
            ->join('student_records sr', 'sr.student_id = s.id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYear['id'])
            ->where('sr.status', 'aktif');

        if ($studentId) {
            $stuBuilder->where('s.id', $studentId);
        }

        $students = $stuBuilder->orderBy('s.name', 'ASC')->get()->getResultArray();
        $school   = $this->schoolModel->getProfile();

        $config   = $this->configModel->getConfig($activeYear['id'], '1');
        $showWatermark = !empty($config['show_watermark']);
        $logoBase64    = $this->imageToBase64($school['logo'] ?? '');

        return view('admin/rapor_semester/mutasi_print', [
            'class'         => $class,
            'school'        => $school,
            'activeYear'    => $activeYear,
            'students'      => $students,
            'showWatermark' => $showWatermark,
            'logoBase64'    => $logoBase64,
            'isSingle'      => $studentId !== null,
        ]);
    }
}
