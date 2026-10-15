<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Erapor — Input Nilai Erapor (Prerogratif Guru)
 *
 * Nilai erapor adalah nilai rapor final yang diinput langsung oleh guru.
 * Sistem menyediakan nilai acuan (hasil perhitungan % formatif + sumatif)
 * sebagai referensi, tapi keputusan nilai akhir ada di tangan guru.
 *
 * Alur:
 *   Admin/Kepsek → pilih guru → daftar mapel → input nilai
 *   Guru         → langsung ke daftar mapel yang diampu → input nilai
 */
class Erapor extends BaseController
{
    protected $db;
    protected $yearModel;

    public function __construct()
    {
        $this->db        = \Config\Database::connect();
        $this->yearModel = new AcademicYearModel();
    }

    private function currentUser(): array
    {
        return session()->get('user') ?? [];
    }

    private function getTeacherId(): ?int
    {
        $user = $this->currentUser();
        if ($user['role_id'] == 3) {
            return (int) ($user['related_id'] ?? 0) ?: null;
        }
        return null;
    }

    // =========================================================
    // INDEX — Pilih guru (admin) atau langsung daftar mapel (guru)
    // =========================================================
    public function index()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        $activeYear = $this->yearModel->getActiveYear();
        $years      = $this->db->table('academic_years')->orderBy('year', 'DESC')->get()->getResultArray();

        if ($roleId == 1 || $roleId == 2) {
            // Admin / Kepsek: tampilkan daftar guru
            $teachers = $this->db->table('teachers t')
                ->select('t.id, t.name, u.username')
                ->join('users u', 'u.id = t.user_id', 'left')
                ->orderBy('t.name', 'ASC')
                ->get()->getResultArray();

            return view('admin/erapor/index_admin', [
                'title'      => 'Nilai Erapor',
                'teachers'   => $teachers,
                'activeYear' => $activeYear,
                'years'      => $years,
            ]);
        }

        if ($roleId == 3) {
            // Guru: langsung ke daftar mapel
            return $this->subjectList();
        }

        return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
    }

    // =========================================================
    // SUBJECT LIST — Daftar mapel yang diampu guru
    // =========================================================
    public function subjectList($teacherId = null)
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        // Tentukan teacher_id
        if ($roleId == 3) {
            $teacherId = $this->getTeacherId();
        } elseif ($roleId == 1 || $roleId == 2) {
            $teacherId = $teacherId ?? (int) $this->request->getGet('teacher_id');
        }

        if (!$teacherId) {
            return redirect()->to('admin/erapor')->with('error', 'Guru tidak ditemukan.');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = $this->request->getGet('year_id') ?? ($activeYear['id'] ?? null);

        $teacher = $this->db->table('teachers')->where('id', $teacherId)->get()->getRowArray();
        if (!$teacher) {
            return redirect()->to('admin/erapor')->with('error', 'Data guru tidak ditemukan.');
        }

        // Ambil semua assignment guru ini
        $assignments = $this->db->table('teaching_assignments ta')
            ->select('ta.id, ta.class_id, ta.subject_id, ta.academic_year_id,
                      c.name as class_name, s.name as subject_name, s.id as sid,
                      ay.year as year_name')
            ->join('classes c', 'c.id = ta.class_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->join('academic_years ay', 'ay.id = ta.academic_year_id')
            ->where('ta.teacher_id', $teacherId)
            ->where('ta.academic_year_id', $yearId)
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        $years = $this->db->table('academic_years')->orderBy('year', 'DESC')->get()->getResultArray();

        return view('admin/erapor/subject_list', [
            'title'       => 'Nilai Erapor — Daftar Mapel',
            'teacher'     => $teacher,
            'assignments' => $assignments,
            'activeYear'  => $activeYear,
            'years'       => $years,
            'yearId'      => $yearId,
            'isAdmin'     => in_array($roleId, [1, 2]),
        ]);
    }

    // =========================================================
    // INPUT — Form input nilai erapor per kelas/mapel/semester
    // =========================================================
    public function input($classId, $subjectId, $semester = 1)
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = $this->request->getGet('year_id') ?? ($activeYear['id'] ?? null);

        // Validasi akses guru
        if ($roleId == 3) {
            $teacherId = $this->getTeacherId();
            $hasAccess = $this->db->table('teaching_assignments')
                ->where('teacher_id', $teacherId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $yearId)
                ->countAllResults();
            if (!$hasAccess) {
                return redirect()->to('admin/erapor')->with('error', 'Anda tidak memiliki akses ke kelas/mapel ini.');
            }
        }

        $class   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        $year    = $this->db->table('academic_years')->where('id', $yearId)->get()->getRowArray();

        if (!$class || !$subject || !$year) {
            return redirect()->back()->with('error', 'Data tidak ditemukan.');
        }

        // Daftar siswa di kelas ini pada tahun ajaran yang dipilih
        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.religion')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            $students = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.religion')
                ->join('students s', 's.id = sr.student_id')
                ->where('sr.class_id', $classId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        if (empty($students)) {
            $students = $this->db->table('students')
                ->select('id, name, religion')
                ->where('class_id', $classId)
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        }

        if (empty($students)) {
            return redirect()->back()->with('error', 'Tidak ada siswa di kelas ini.');
        }

        $studentIds = array_column($students, 'id');

        // ── Hitung nilai acuan (% formatif + sumatif) ──────────────────
        $fWeight = ($year['formatif_weight'] ?? 60) / 100;
        $sWeight = ($year['sumatif_weight']  ?? 40) / 100;

        // Nilai formatif per siswa
        $fRows = $this->db->table('material_scores ms')
            ->select('ms.student_id, AVG(ms.score) as avg_score')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
            ->whereIn('ms.student_id', $studentIds)
            ->where('atp.subject_id', $subjectId)
            ->where('atp.class_id', $classId)
            ->where('atp.semester', $semester)
            ->groupBy('ms.student_id')
            ->get()->getResultArray();
        $formatifAvg = array_column($fRows, 'avg_score', 'student_id');

        // Nilai sumatif per siswa
        $sRows = $this->db->table('summative_scores')
            ->select('student_id, AVG(score) as avg_score')
            ->whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('year_id', $yearId)
            ->where('semester', $semester)
            ->groupBy('student_id')
            ->get()->getResultArray();
        $sumatifAvg = array_column($sRows, 'avg_score', 'student_id');

        // Nilai erapor yang sudah tersimpan
        $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
        $savedRows = $this->db->table('grades')
            ->select('student_id, erapor_score, report_score, competency_desc')
            ->whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('year_id', $yearId)
            ->where('semester', $semesterLabel)
            ->get()->getResultArray();
        $savedErapor  = array_column($savedRows, 'erapor_score', 'student_id');
        $savedRapor   = array_column($savedRows, 'report_score', 'student_id');
        $savedDesc    = array_column($savedRows, 'competency_desc', 'student_id');

        // Gabungkan data per siswa
        $data = [];
        foreach ($students as $stu) {
            $sid  = $stu['id'];
            $fAvg = isset($formatifAvg[$sid]) ? round((float)$formatifAvg[$sid], 2) : null;
            $sAvg = isset($sumatifAvg[$sid])  ? round((float)$sumatifAvg[$sid], 2)  : null;

            // Nilai acuan = perhitungan sistem
            $acuan = null;
            if ($fAvg !== null || $sAvg !== null) {
                $acuan = round((($fAvg ?? 0) * $fWeight) + (($sAvg ?? 0) * $sWeight), 2);
            }

            $data[] = [
                'id'           => $sid,
                'name'         => $stu['name'],
                'religion'     => $stu['religion'],
                'formatif_avg' => $fAvg,
                'sumatif_avg'  => $sAvg,
                'acuan'        => $acuan,                          // nilai acuan sistem
                'erapor'       => $savedErapor[$sid] ?? null,      // nilai erapor tersimpan
                'desc'         => $savedDesc[$sid] ?? '',          // deskripsi capaian tersimpan
            ];
        }

        $classLevel = (string) ($class['level'] ?? '');

        // 1. Ambil semua TP untuk mapel, tingkat kelas, dan semester ini
        $allTpsBuilder = $this->db->table('tujuan_pembelajaran tp')
            ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.elemen, tp.kelas, atp.semester')
            ->join('atp_elemen ae', 'ae.id = tp.atp_elemen_id', 'left')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = COALESCE(tp.atp_id, ae.atp_id)', 'left')
            ->join('classes c', 'c.id = atp.class_id', 'left')
            ->where('tp.subject_id', $subjectId);

        if ($classLevel !== '') {
            $allTpsBuilder->groupStart()
                ->where('tp.kelas', $classLevel)
                ->orWhere('c.level', $classLevel)
                ->orWhere('tp.kelas IS NULL')
                ->orWhere('tp.kelas', '')
            ->groupEnd();
        }

        $allTpsBuilder->groupStart()
            ->where('atp.semester', (int) $semester)
            ->orWhere('atp.semester IS NULL')
        ->groupEnd();

        $allTps = $allTpsBuilder->groupBy('tp.id')
            ->orderBy('tp.kode_tp', 'ASC')
            ->orderBy('tp.id', 'ASC')
            ->get()->getResultArray();

        // Fallback jika kosong: ambil semua TP mapel ini
        if (empty($allTps)) {
            $allTps = $this->db->table('tujuan_pembelajaran tp')
                ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.elemen, tp.kelas')
                ->where('tp.subject_id', $subjectId)
                ->orderBy('tp.kode_tp', 'ASC')
                ->get()->getResultArray();
        }

        // 2. Ambil TP yang sudah dipilih di tingkat level kelas
        $selectedTpRows = $this->db->table('erapor_tp_settings')
            ->where('academic_year_id', $yearId)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('semester', (int) $semester)
            ->get()->getResultArray();
        $selectedTpIds = array_map('intval', array_column($selectedTpRows, 'tp_id'));

        // Filter TP aktif yang terpilih
        $activeSelectedTps = [];
        if (!empty($selectedTpIds)) {
            foreach ($allTps as $tp) {
                if (in_array((int)$tp['id'], $selectedTpIds, true)) {
                    $activeSelectedTps[] = $tp;
                }
            }
        }

        // 3. Ambil pilihan TP Maksimal & Minimal per siswa
        $studentTpRows = $this->db->table('erapor_student_tp')
            ->where('academic_year_id', $yearId)
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->where('semester', (int) $semester)
            ->whereIn('student_id', $studentIds)
            ->get()->getResultArray();

        $studentTpMap = [];
        foreach ($studentTpRows as $str) {
            $studentTpMap[$str['student_id']][$str['tp_id']] = $str['type'];
        }

        return view('admin/erapor/input', [
            'title'             => 'Input Nilai Erapor',
            'class'             => $class,
            'subject'           => $subject,
            'year'              => $year,
            'semester'          => (int) $semester,
            'students'          => $data,
            'fWeight'           => $fWeight * 100,
            'sWeight'           => $sWeight * 100,
            'isAdmin'           => in_array($roleId, [1, 2]),
            'allTps'            => $allTps,
            'selectedTpIds'     => $selectedTpIds,
            'activeSelectedTps' => $activeSelectedTps,
            'studentTpMap'      => $studentTpMap,
            'classLevel'        => $classLevel,
        ]);
    }

    // =========================================================
    // SAVE — Simpan nilai erapor (AJAX POST)
    // =========================================================
    public function save()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        if (!in_array($roleId, [1, 2, 3])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $classId   = (int) $this->request->getPost('class_id');
        $subjectId = (int) $this->request->getPost('subject_id');
        $yearId    = (int) $this->request->getPost('year_id');
        $semester  = (int) $this->request->getPost('semester');
        $scores    = $this->request->getPost('scores'); // array [student_id => nilai]

        if (!$classId || !$subjectId || !$yearId || !$semester || empty($scores)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data tidak lengkap.']);
        }

        // Validasi akses guru
        if ($roleId == 3) {
            $teacherId = $this->getTeacherId();
            $hasAccess = $this->db->table('teaching_assignments')
                ->where('teacher_id', $teacherId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $yearId)
                ->countAllResults();
            if (!$hasAccess) {
                return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
            }
        }

        $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
        $descriptions  = $this->request->getPost('descriptions') ?? [];
        $saved = 0;

        foreach ($scores as $studentId => $nilai) {
            $studentId = (int) $studentId;
            $nilai     = $nilai === '' ? null : round((float) $nilai, 2);
            $desc      = isset($descriptions[$studentId]) ? trim($descriptions[$studentId]) : null;

            // Validasi range nilai
            if ($nilai !== null && ($nilai < 0 || $nilai > 100)) {
                continue;
            }

            // Upsert ke tabel grades
            $existing = $this->db->table('grades')
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getRowArray();

            $updateData = [
                'erapor_score' => $nilai,
                'updated_at'   => date('Y-m-d H:i:s'),
            ];
            if ($desc !== null) {
                $updateData['competency_desc'] = $desc;
            }

            if ($existing) {
                $this->db->table('grades')
                    ->where('id', $existing['id'])
                    ->update($updateData);
            } else {
                $insertData = [
                    'student_id'      => $studentId,
                    'subject_id'      => $subjectId,
                    'year_id'         => $yearId,
                    'semester'        => $semesterLabel,
                    'erapor_score'    => $nilai,
                    'competency_desc' => $desc,
                    'created_at'      => date('Y-m-d H:i:s'),
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $this->db->table('grades')->insert($insertData);
            }
            $saved++;
        }

        // ── Simpan pilihan TP Maksimal & Minimal per Siswa ──────────
        $studentTps = $this->request->getPost('student_tp'); // [student_id => [tp_id => 'maksimal'|'minimal']]
        if (is_array($studentTps)) {
            $this->db->table('erapor_student_tp')
                ->where('academic_year_id', $yearId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('semester', $semester)
                ->delete();

            $insertStudentTps = [];
            foreach ($studentTps as $sid => $tpSelections) {
                if (!is_array($tpSelections)) continue;
                foreach ($tpSelections as $tpId => $tpType) {
                    if (in_array($tpType, ['maksimal', 'minimal'], true)) {
                        $insertStudentTps[] = [
                            'academic_year_id' => $yearId,
                            'class_id'         => $classId,
                            'student_id'       => (int) $sid,
                            'subject_id'       => $subjectId,
                            'semester'         => $semester,
                            'tp_id'            => (int) $tpId,
                            'type'             => $tpType,
                            'created_at'       => date('Y-m-d H:i:s'),
                            'updated_at'       => date('Y-m-d H:i:s'),
                        ];
                    }
                }
            }
            if (!empty($insertStudentTps)) {
                $this->db->table('erapor_student_tp')->insertBatch($insertStudentTps);
            }
        }

        log_message('info', "Erapor saved: class=$classId subject=$subjectId year=$yearId semester=$semester count=$saved by user={$user['id']}");

        // ── Notifikasi nilai erapor: kirim ke siswa yang nilainya disimpan ───
        try {
            $subjectName   = $this->db->table('subjects')->select('name')->where('id', $subjectId)->get()->getRowArray()['name'] ?? 'Mata Pelajaran';
            $semesterLabel = $semester == 1 ? 'Ganjil' : 'Genap';

            $studentIds = array_map('intval', array_keys($scores));
            $studentIds = array_filter($studentIds, fn($id) => $id > 0);

            if (!empty($studentIds)) {
                $userRows = $this->db->table('students s')
                    ->select('u.id as user_id')
                    ->join('users u', 'u.id = s.user_id', 'left')
                    ->whereIn('s.id', $studentIds)
                    ->where('u.id IS NOT NULL')
                    ->where('u.is_active', 1)
                    ->get()->getResultArray();

                $userIds = array_column($userRows, 'user_id');

                if (!empty($userIds)) {
                    notify_users(
                        $userIds,
                        'nilai_masuk',
                        "Nilai Rapor Masuk: {$subjectName}",
                        "Nilai e-Rapor semester {$semesterLabel} untuk {$subjectName} telah diinput.",
                        base_url('siswa/grades')
                    );
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[Erapor::save] Notifikasi gagal: ' . $e->getMessage());
        }
        // ─────────────────────────────────────────────────────────────────────

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Nilai erapor, pilihan TP, dan deskripsi berhasil disimpan ($saved siswa).",
            'saved'   => $saved,
        ]);
    }

    // =========================================================
    // SAVE TP SETTINGS — Simpan TP Acuan Rapor Level Kelas
    // =========================================================
    public function saveTpSettings()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);
        if (!in_array($roleId, [1, 2, 3])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $yearId     = (int) $this->request->getPost('year_id');
        $classLevel = trim((string) $this->request->getPost('class_level'));
        $subjectId  = (int) $this->request->getPost('subject_id');
        $semester   = (int) $this->request->getPost('semester');
        $tpIds      = $this->request->getPost('tp_ids'); // array of int

        if (!$yearId || $classLevel === '' || !$subjectId || !$semester) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        }

        // Hapus pengaturan TP sebelumnya untuk level, mapel, semester & tahun ini
        $this->db->table('erapor_tp_settings')
            ->where('academic_year_id', $yearId)
            ->where('class_level', $classLevel)
            ->where('subject_id', $subjectId)
            ->where('semester', $semester)
            ->delete();

        $count = 0;
        if (!empty($tpIds) && is_array($tpIds)) {
            $inserts = [];
            foreach ($tpIds as $tId) {
                $tId = (int) $tId;
                if ($tId > 0) {
                    $inserts[] = [
                        'academic_year_id' => $yearId,
                        'class_level'      => $classLevel,
                        'subject_id'       => $subjectId,
                        'semester'         => $semester,
                        'tp_id'            => $tId,
                        'created_by'       => $user['id'] ?? null,
                        'created_at'       => date('Y-m-d H:i:s'),
                        'updated_at'       => date('Y-m-d H:i:s'),
                    ];
                    $count++;
                }
            }
            if (!empty($inserts)) {
                $this->db->table('erapor_tp_settings')->insertBatch($inserts);
            }
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Berhasil menyimpan {$count} TP Acuan untuk Tingkat Kelas {$classLevel}.",
            'count'   => $count,
        ]);
    }

    // =========================================================
    // GENERATE DESC — Otomatisasi deskripsi capaian berbasis TP
    // =========================================================
    public function generateDesc()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);
        if (!in_array($roleId, [1, 2, 3])) {
            return $this->response->setStatusCode(403)->setJSON(['status' => 'error', 'message' => 'Akses ditolak.']);
        }

        $classId   = (int) $this->request->getPost('class_id');
        $subjectId = (int) $this->request->getPost('subject_id');
        $yearId    = (int) $this->request->getPost('year_id');
        $semester  = (int) $this->request->getPost('semester');

        if (!$classId || !$subjectId || !$yearId || !$semester) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Parameter tidak lengkap.']);
        }

        // Siswa aktif di kelas
        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name')
            ->join('students s', 's.id = sr.student_id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->where('sr.status', 'aktif')
            ->get()->getResultArray();

        // 1. Cek pilihan student_tp dari POST atau dari database
        $postStudentTps = $this->request->getPost('student_tp');
        $studentTpsMap  = [];

        if (is_array($postStudentTps)) {
            $studentTpsMap = $postStudentTps;
        } else {
            $dbStudentTps = $this->db->table('erapor_student_tp')
                ->where('academic_year_id', $yearId)
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->where('semester', $semester)
                ->get()->getResultArray();

            foreach ($dbStudentTps as $str) {
                $studentTpsMap[$str['student_id']][$str['tp_id']] = $str['type'];
            }
        }

        // 2. Preload seluruh data TP untuk mapel ini
        $allTpsList = $this->db->table('tujuan_pembelajaran')
            ->where('subject_id', $subjectId)
            ->get()->getResultArray();
        $tpDescMap = [];
        foreach ($allTpsList as $t) {
            $tpDescMap[(int)$t['id']] = $t['deskripsi'] ?? '';
        }

        $cleanTpText = function($raw) {
            $clean = trim((string) $raw);
            $clean = preg_replace('/^(mampu|siswa dapat|peserta didik dapat|dapat)\s+/i', '', $clean);
            $clean = rtrim($clean, " .\t\n\r\0\x0B");
            if (mb_strlen($clean) > 85) {
                $clean = mb_substr($clean, 0, 82) . '...';
            }
            return lcfirst($clean);
        };

        $descs = [];
        $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';

        foreach ($students as $stu) {
            $sid = (int) $stu['id'];
            $stuSelections = $studentTpsMap[$sid] ?? [];

            $maksList = [];
            $minList  = [];

            if (!empty($stuSelections) && is_array($stuSelections)) {
                foreach ($stuSelections as $tpId => $type) {
                    $tpId = (int) $tpId;
                    $tpDesc = $tpDescMap[$tpId] ?? '';
                    if ($tpDesc === '') continue;

                    if ($type === 'maksimal') {
                        $maksList[] = $cleanTpText($tpDesc);
                    } elseif ($type === 'minimal') {
                        $minList[] = $cleanTpText($tpDesc);
                    }
                }
            }

            // Jika ada pilihan TP Maksimal atau Minimal untuk siswa ini
            if (!empty($maksList) || !empty($minList)) {
                $parts = [];
                if (!empty($maksList)) {
                    $parts[] = "Menunjukkan penguasaan yang sangat baik dalam " . implode(' dan ', $maksList) . ".";
                }
                if (!empty($minList)) {
                    $parts[] = "Perlu bimbingan dan peningkatan dalam " . implode(' serta ', $minList) . ".";
                }
                $descs[$sid] = implode(' ', $parts);
                continue;
            }

            // Fallback jika belum memilih TP: gunakan skor formatif/materi atau nilai akhir
            $materiScores = $this->db->table('material_scores ms')
                ->select('ms.score, atp.materi, atp.tujuan_pembelajaran')
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id', 'inner')
                ->where('ms.student_id', $sid)
                ->where('atp.subject_id', $subjectId)
                ->where('ms.score IS NOT NULL')
                ->orderBy('ms.score', 'DESC')
                ->get()->getResultArray();

            $existing = $this->db->table('grades')
                ->where('student_id', $sid)
                ->where('subject_id', $subjectId)
                ->where('year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getRowArray();
            $nilaiAkhir = $existing['erapor_score'] ?? null;

            if (!empty($materiScores)) {
                $highest = $materiScores[0];
                $lowest  = end($materiScores);

                $topText = !empty($highest['materi']) ? trim($highest['materi']) : (!empty($highest['tujuan_pembelajaran']) ? trim($highest['tujuan_pembelajaran']) : 'materi pokok');
                $lowText = !empty($lowest['materi']) ? trim($lowest['materi']) : (!empty($lowest['tujuan_pembelajaran']) ? trim($lowest['tujuan_pembelajaran']) : '');

                if (mb_strlen($topText) > 65) {
                    $topText = mb_substr($topText, 0, 62) . '...';
                }
                if (mb_strlen($lowText) > 65) {
                    $lowText = mb_substr($lowText, 0, 62) . '...';
                }

                $kalimat = "Menunjukkan penguasaan yang sangat baik dalam memahami " . lcfirst($topText) . ".";

                if ($lowest && (float)$lowest['score'] < 75 && strcasecmp($topText, $lowText) !== 0) {
                    $kalimat .= " Perlu bimbingan dan peningkatan dalam " . lcfirst($lowText) . ".";
                } elseif ($lowest && ((float)$highest['score'] - (float)$lowest['score'] >= 15) && strcasecmp($topText, $lowText) !== 0) {
                    $kalimat .= " Perlu penguatan lebih lanjut dalam " . lcfirst($lowText) . ".";
                }

                $descs[$sid] = $kalimat;
            } else {
                if ($nilaiAkhir !== null) {
                    if ((float)$nilaiAkhir >= 85) {
                        $descs[$sid] = "Menunjukkan penguasaan yang sangat baik dalam mencapai seluruh tujuan pembelajaran yang ditetapkan.";
                    } elseif ((float)$nilaiAkhir >= 75) {
                        $descs[$sid] = "Menunjukkan penguasaan yang baik dalam mencapai tujuan pembelajaran, pertahankan dan tingkatkan prestasi.";
                    } else {
                        $descs[$sid] = "Cukup menguasai materi pembelajaran, perlu pendampingan dan latihan lebih giat untuk optimalisasi capaian kompetensi.";
                    }
                } else {
                    $descs[$sid] = "Menunjukkan capaian pembelajaran yang baik pada semester ini.";
                }
            }
        }

        return $this->response->setJSON([
            'status'       => 'success',
            'descriptions' => $descs,
        ]);
    }

    // =========================================================
    // WIZARD IMPORT LEGER — LANGKAH 0: Form Upload File
    // =========================================================
    public function importForm()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId == 2) {
            return redirect()->to('admin/erapor')->with('error', 'Kepala Sekolah tidak memiliki akses untuk import nilai.');
        }

        $activeYear = $this->yearModel->getActiveYear();
        $years      = $this->db->table('academic_years')->orderBy('year', 'DESC')->get()->getResultArray();
        $yearId     = $this->request->getGet('year_id') ?? ($activeYear['id'] ?? null);
        $semester   = $this->request->getGet('semester') ?? 'ganjil';

        return view('admin/erapor/import_form', [
            'title'    => 'Import Leger Nilai Erapor',
            'years'    => $years,
            'yearId'   => $yearId,
            'semester' => $semester,
        ]);
    }

    // =========================================================
    // WIZARD IMPORT LEGER — LANGKAH 0 → 1: Parsing File & Mapping
    // =========================================================
    public function importParse()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId == 2) {
            return redirect()->to('admin/erapor')->with('error', 'Akses ditolak.');
        }

        $file = $this->request->getFile('file_leger');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File upload tidak valid atau kosong.');
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return redirect()->back()->with('error', 'Format file harus .xlsx atau .xls.');
        }

        if ($file->getSize() > 10 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Ukuran file melebihi batas 10 MB.');
        }

        $yearId        = (int) $this->request->getPost('year_id');
        $semester      = $this->request->getPost('semester');
        $importAbsence = (bool) $this->request->getPost('import_absence');

        try {
            $spreadsheet = IOFactory::load($file->getTempName());
        } catch (\Throwable $e) {
            log_message('error', '[Erapor::importParse] Load excel error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal membaca file Excel. Pastikan file tidak terproteksi/corrupt.');
        }

        $sheet = $spreadsheet->getSheet(0);
        $rows  = $sheet->toArray(null, true, true, false);

        if (empty($rows) || count($rows) < 5) {
            return redirect()->back()->with('error', 'Format file leger tidak sesuai atau baris terlalu sedikit.');
        }

        // Metadata detection
        $detectedClass = '';
        if (isset($rows[2][2]) && !empty(trim((string)$rows[2][2]))) {
            $detectedClass = trim((string)$rows[2][2]);
        } elseif (isset($rows[2][1]) && !empty(trim((string)$rows[2][1]))) {
            $detectedClass = trim((string)$rows[2][1]);
        }

        $classId = null;
        if (!empty($detectedClass)) {
            $cleanClassName = trim(str_replace([':', 'Kelas', 'kelas'], '', $detectedClass));
            $cleanClassName = preg_replace('/\s+/', ' ', $cleanClassName);

            $cls = $this->db->table('classes')
                ->where('name', $cleanClassName)
                ->orWhere('name', 'Kelas ' . $cleanClassName)
                ->orWhere('name', $detectedClass)
                ->get()->getRowArray();

            if (!$cls) {
                $cls = $this->db->table('classes')
                    ->like('name', $cleanClassName)
                    ->get()->getRowArray();
            }

            if ($cls) {
                $classId = (int) $cls['id'];
            }
        }

        if (!$classId) {
            $cls = $this->db->table('classes')->where('is_active', 1)->get()->getRowArray();
            if ($cls) {
                $classId = (int) $cls['id'];
            }
        }

        // Attendance columns detection (Row 6)
        $sakitColIdx = null;
        if (isset($rows[6])) {
            foreach ($rows[6] as $cIdx => $cellVal) {
                $cellValStr = strtolower(trim((string) $cellVal));
                if (str_contains($cellValStr, 'sakit') || $cellValStr === 's') {
                    $sakitColIdx = $cIdx;
                    break;
                }
            }
        }

        $canImportAbsence = ($sakitColIdx !== null);
        $izinColIdx       = $sakitColIdx !== null ? $sakitColIdx + 1 : null;
        $alpaColIdx       = $sakitColIdx !== null ? $sakitColIdx + 2 : null;

        // Subject headers detection (Row 4)
        $subjectHeaders = [];
        if (isset($rows[4])) {
            foreach ($rows[4] as $cIdx => $cellVal) {
                if ($cIdx < 4) continue; // Skip No, Name, NISN, NIS
                if ($canImportAbsence && $cIdx >= $sakitColIdx) break;

                $headerName = trim((string) $cellVal);
                if (!empty($headerName)) {
                    $subjectHeaders[$cIdx] = $headerName;
                }
            }
        }

        if (empty($subjectHeaders)) {
            return redirect()->back()->with('error', 'Tidak dapat menemukan kolom mata pelajaran pada file leger.');
        }

        // Fetch DB subjects
        $subjects = $this->db->table('subjects')->orderBy('name', 'ASC')->get()->getResultArray();

        // Auto-suggest mapping
        $suggestedMapping = [];
        foreach ($subjectHeaders as $cIdx => $hName) {
            $suggestedMapping[$cIdx] = $this->suggestSubjectId($hName, $subjects);
        }

        // Fetch DB students in detected class for this academic year
        $dbStudents = [];
        if ($classId) {
            $dbStudents = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.nisn, s.nis')
                ->join('students s', 's.id = sr.student_id')
                ->where('sr.class_id', $classId)
                ->where('sr.academic_year_id', $yearId)
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();

            if (empty($dbStudents)) {
                $dbStudents = $this->db->table('students')
                    ->select('id, name, nisn, nis')
                    ->where('class_id', $classId)
                    ->get()->getResultArray();
            }
        }

        // Fetch all students as fallback pool
        $allDbStudents = $this->db->table('students')
            ->select('id, name, nisn, nis')
            ->get()->getResultArray();

        // Parse student rows starting from row index 7
        $parsedStudents = [];
        for ($r = 7; $r < count($rows); $r++) {
            $row = $rows[$r];
            $noCell = isset($row[0]) ? trim((string)$row[0]) : '';

            // Skip footer / legend rows (rows where No is not numeric)
            if (!is_numeric($noCell)) {
                continue;
            }

            $fileName = isset($row[1]) ? trim((string)$row[1]) : ''; // Col B: NAMA SISWA
            $fileNisn = isset($row[2]) ? trim((string)$row[2]) : ''; // Col C: NISN
            $fileNis  = isset($row[3]) ? trim((string)$row[3]) : ''; // Col D: NIS

            if (str_contains($fileNisn, 'E+')) {
                $fileNisn = number_format((float)$fileNisn, 0, '', '');
            }
            if (str_contains($fileNis, 'E+')) {
                $fileNis = number_format((float)$fileNis, 0, '', '');
            }

            if (empty($fileName) && empty($fileNisn) && empty($fileNis)) {
                continue;
            }

            $dbStudentId   = null;
            $dbStudentName = '';
            $matchStatus   = 'not_found';

            $cleanFileNisn = ltrim($fileNisn, '0');
            $cleanFileNis  = ltrim($fileNis, '0');
            $cleanFileName = strtolower(preg_replace('/\s+/', ' ', trim($fileName)));

            $findMatch = function (array $pool) use ($cleanFileNisn, $cleanFileNis, $cleanFileName, &$dbStudentId, &$dbStudentName, &$matchStatus) {
                foreach ($pool as $dbSt) {
                    $dbNisn = ltrim(trim((string)($dbSt['nisn'] ?? '')), '0');
                    $dbNis  = ltrim(trim((string)($dbSt['nis'] ?? '')), '0');
                    $dbName = strtolower(preg_replace('/\s+/', ' ', trim((string)($dbSt['name'] ?? ''))));

                    // Pass 1: NISN match
                    if (!empty($cleanFileNisn) && !empty($dbNisn) && $cleanFileNisn === $dbNisn) {
                        $dbStudentId   = (int) $dbSt['id'];
                        $dbStudentName = $dbSt['name'];
                        $matchStatus   = 'nisn_match';
                        return true;
                    }
                    // Pass 2: NIS match
                    if (!empty($cleanFileNis) && !empty($dbNis) && $cleanFileNis === $dbNis) {
                        $dbStudentId   = (int) $dbSt['id'];
                        $dbStudentName = $dbSt['name'];
                        $matchStatus   = 'nis_match';
                        return true;
                    }
                    // Pass 3: Exact Name match
                    if (!empty($cleanFileName) && !empty($dbName) && $cleanFileName === $dbName) {
                        $dbStudentId   = (int) $dbSt['id'];
                        $dbStudentName = $dbSt['name'];
                        $matchStatus   = 'name_match';
                        return true;
                    }
                }
                return false;
            };

            // 1st search in class pool
            $matched = $findMatch($dbStudents);

            // 2nd search in all students pool if not found in class pool
            if (!$matched && !empty($allDbStudents)) {
                $findMatch($allDbStudents);
            }

            $scores = [];
            foreach ($subjectHeaders as $cIdx => $hName) {
                $rawScore = $row[$cIdx] ?? null;
                if ($rawScore !== null && $rawScore !== '') {
                    $scores[$cIdx] = trim((string)$rawScore);
                } else {
                    $scores[$cIdx] = null;
                }
            }

            $sick = ($canImportAbsence && isset($row[$sakitColIdx])) ? (int) $row[$sakitColIdx] : 0;
            $permitted = ($canImportAbsence && isset($row[$izinColIdx])) ? (int) $row[$izinColIdx] : 0;
            $absent = ($canImportAbsence && isset($row[$alpaColIdx])) ? (int) $row[$alpaColIdx] : 0;

            $parsedStudents[] = [
                'file_nis'        => $fileNis,
                'nis'             => $fileNis,
                'nisn'            => $fileNisn,
                'file_name'       => $fileName,
                'match_status'    => $matchStatus,
                'db_student_id'   => $dbStudentId,
                'db_student_name' => $dbStudentName,
                'scores'          => $scores,
                'sick'            => max(0, $sick),
                'permitted'       => max(0, $permitted),
                'absent'          => max(0, $absent),
            ];
        }

        $yearRow = $this->db->table('academic_years')->where('id', $yearId)->get()->getRowArray();
        $selectedYearText = $yearRow['year'] ?? '';

        return view('admin/erapor/import_mapping', [
            'title'            => 'Mapping Mata Pelajaran — Import Leger',
            'subjectHeaders'   => $subjectHeaders,
            'suggestedMapping' => $suggestedMapping,
            'subjects'         => $subjects,
            'detectedClass'    => $detectedClass,
            'selectedYearText' => $selectedYearText,
            'parsedStudents'   => $parsedStudents,
            'studentsJson'     => json_encode($parsedStudents),
            'canImportAbsence' => $canImportAbsence,
            'yearId'           => $yearId,
            'semester'         => $semester,
            'classId'          => $classId,
            'importAbsence'    => $importAbsence,
        ]);
    }

    // Helper: Smart auto-suggest subject ID based on header text
    private function suggestSubjectId(string $headerName, array $subjects): int
    {
        $hClean = strtoupper(trim($headerName));
        $hClean = preg_replace('/[^A-Z0-9\s]/', '', $hClean);

        $aliases = [
            'MTK'   => ['MATEMATIKA'],
            'MAT'   => ['MATEMATIKA'],
            'PAI'   => ['PENDIDIKAN AGAMA ISLAM', 'AGAMA ISLAM', 'PAIBP'],
            'AGAMA' => ['PENDIDIKAN AGAMA'],
            'PKN'   => ['PENDIDIKAN PANCASILA', 'PPKN', 'PANCASILA'],
            'PPKN'  => ['PENDIDIKAN PANCASILA', 'PANCASILA'],
            'BIN'   => ['BAHASA INDONESIA'],
            'BIND'  => ['BAHASA INDONESIA'],
            'BIG'   => ['BAHASA INGGRIS'],
            'BING'  => ['BAHASA INGGRIS'],
            'IPA'   => ['ILMU PENGETAHUAN ALAM', 'IPAS'],
            'IPAS'  => ['ILMU PENGETAHUAN ALAM DAN SOSIAL', 'IPA'],
            'IPS'   => ['ILMU PENGETAHUAN SOSIAL'],
            'PJOK'  => ['PENDIDIKAN JASMANI', 'PENJAS'],
            'SBD'   => ['SENI BUDAYA', 'SBDP', 'SENI'],
            'SBDP'  => ['SENI BUDAYA DAN PRAKARYA', 'SENI BUDAYA'],
            'TIK'   => ['INFORMATIKA'],
        ];

        foreach ($subjects as $s) {
            $sName = strtoupper(trim($s['name']));
            $sCode = strtoupper(trim($s['code'] ?? ''));
            if ($hClean === $sName || ($sCode !== '' && $hClean === $sCode)) {
                return (int) $s['id'];
            }
        }

        if (isset($aliases[$hClean])) {
            foreach ($aliases[$hClean] as $targetName) {
                foreach ($subjects as $s) {
                    $sName = strtoupper(trim($s['name']));
                    if (str_contains($sName, $targetName)) {
                        return (int) $s['id'];
                    }
                }
            }
        }

        foreach ($subjects as $s) {
            $sName = strtoupper(trim($s['name']));
            if (str_contains($sName, $hClean) || str_contains($hClean, $sName)) {
                return (int) $s['id'];
            }
        }

        return 0;
    }

    // =========================================================
    // WIZARD IMPORT LEGER — LANGKAH 1 → 2: Preview & Anti-Tamper Check
    // =========================================================
    public function importPreview()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId == 2) {
            return redirect()->to('admin/erapor')->with('error', 'Akses ditolak.');
        }

        $studentsJson     = $this->request->getPost('students_json');
        $mapping          = (array) $this->request->getPost('mapping');
        $yearId           = (int) $this->request->getPost('year_id');
        $semester         = $this->request->getPost('semester');
        $classId          = (int) $this->request->getPost('class_id');
        $importAbsence    = (bool) $this->request->getPost('import_absence');
        $overwriteScores  = (bool) $this->request->getPost('overwrite_scores');
        $overwriteAbsence = (bool) $this->request->getPost('overwrite_absence');

        if (empty($studentsJson) || empty($mapping) || !$yearId) {
            return redirect()->to('admin/erapor/import')->with('error', 'Data form import tidak lengkap.');
        }

        $parsedStudents = json_decode($studentsJson, true) ?: [];
        $activeMapping  = array_filter($mapping, fn($val) => (int)$val > 0);

        if (empty($activeMapping)) {
            return redirect()->back()->with('error', 'Pilih minimal 1 kolom mata pelajaran untuk diimport.');
        }

        $subjectRows = $this->db->table('subjects')->whereIn('id', array_values($activeMapping))->get()->getResultArray();
        $validSubjectIds = array_column($subjectRows, 'id');
        $subjectNames = [];
        foreach ($subjectRows as $sr) {
            $subjectNames[$sr['id']] = $sr['name'];
        }

        $isWaliKelas = true;
        $skippedSubjectNamesTeacher = [];
        if ($roleId == 3) {
            $teacherId = $this->getTeacherId();
            $cls = $this->db->table('classes')->where('id', $classId)->where('teacher_id', $teacherId)->get()->getRowArray();
            $isWaliKelas = ($cls !== null);

            $assignments = $this->db->table('teaching_assignments')
                ->select('subject_id')
                ->where('teacher_id', $teacherId)
                ->where('class_id', $classId)
                ->groupStart()
                    ->where('academic_year_id', $yearId)
                    ->orWhere('year_id', $yearId)
                ->groupEnd()
                ->get()->getResultArray();
            $assignedSubjectIds = array_column($assignments, 'subject_id');

            foreach ($activeMapping as $cIdx => $subId) {
                if (!in_array($subId, $assignedSubjectIds)) {
                    $skippedSubjectNamesTeacher[] = $subjectNames[$subId] ?? "ID: {$subId}";
                }
            }
        }

        $studentIds = array_filter(array_column($parsedStudents, 'db_student_id'));
        $existingScores = [];
        if (!empty($studentIds) && !empty($validSubjectIds)) {
            $semesterLabel = ($semester == '1' || $semester == 'ganjil') ? 'ganjil' : 'genap';
            $scoreRows = $this->db->table('grades')
                ->select('student_id, subject_id, erapor_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $validSubjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->where('erapor_score IS NOT NULL')
                ->get()->getResultArray();
            foreach ($scoreRows as $sr) {
                $existingScores[$sr['student_id']][$sr['subject_id']] = true;
            }
        }

        $matchedCount  = 0;
        $notFoundCount = 0;
        foreach ($parsedStudents as $st) {
            if ($st['match_status'] === 'not_found') {
                $notFoundCount++;
            } else {
                $matchedCount++;
            }
        }

        $canImportAbsence = isset($parsedStudents[0]['sick']);

        return view('admin/erapor/import_preview', [
            'title'                      => 'Preview Import Leger Nilai',
            'parsedStudents'             => $parsedStudents,
            'studentsJson'               => $studentsJson,
            'mapping'                    => $mapping,
            'activeMapping'              => $activeMapping,
            'subjectNames'               => $subjectNames,
            'yearId'                     => $yearId,
            'semester'                   => $semester,
            'classId'                    => $classId,
            'importAbsence'              => $importAbsence,
            'overwriteScores'            => $overwriteScores,
            'overwriteAbsence'           => $overwriteAbsence,
            'existingScores'             => $existingScores,
            'isWaliKelas'                => $isWaliKelas,
            'canImportAbsence'           => $canImportAbsence,
            'matchedCount'               => $matchedCount,
            'notFoundCount'              => $notFoundCount,
            'skippedSubjectNamesTeacher' => $skippedSubjectNamesTeacher,
        ]);
    }

    // =========================================================
    // WIZARD IMPORT LEGER — LANGKAH 2 → SIMPAN DB: Executing Import
    // =========================================================
    public function importConfirm()
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId == 2) {
            return redirect()->to('admin/erapor')->with('error', 'Akses ditolak.');
        }

        $studentsJson     = $this->request->getPost('students_json');
        $mapping          = (array) $this->request->getPost('mapping');
        $yearId           = (int) $this->request->getPost('year_id');
        $semester         = $this->request->getPost('semester');
        $classId          = (int) $this->request->getPost('class_id');
        $importAbsence    = (bool) $this->request->getPost('import_absence');
        $overwriteScores  = (bool) $this->request->getPost('overwrite_scores');
        $overwriteAbsence = (bool) $this->request->getPost('overwrite_absence');

        if (empty($studentsJson) || empty($mapping) || !$yearId) {
            return redirect()->to('admin/erapor/import')->with('error', 'Data konfirmasi import tidak lengkap.');
        }

        $parsedStudents = json_decode($studentsJson, true) ?: [];
        $activeMapping  = array_filter($mapping, fn($val) => (int)$val > 0);

        if (empty($activeMapping)) {
            return redirect()->to('admin/erapor/import')->with('error', 'Tidak ada kolom mata pelajaran yang dipilih.');
        }

        $semesterLabel = ($semester == '1' || $semester == 'ganjil') ? 'ganjil' : 'genap';

        $isWaliKelas = true;
        $allowedSubjectIds = null;

        if ($roleId == 3) {
            $teacherId = $this->getTeacherId();
            $cls = $this->db->table('classes')->where('id', $classId)->where('teacher_id', $teacherId)->get()->getRowArray();
            $isWaliKelas = ($cls !== null);

            $assignments = $this->db->table('teaching_assignments')
                ->select('subject_id')
                ->where('teacher_id', $teacherId)
                ->where('class_id', $classId)
                ->groupStart()
                    ->where('academic_year_id', $yearId)
                    ->orWhere('year_id', $yearId)
                ->groupEnd()
                ->get()->getResultArray();
            $allowedSubjectIds = array_column($assignments, 'subject_id');
        }

        $scoresSaved    = 0;
        $scoresSkipped  = 0;
        $absenceSaved   = 0;
        $absenceSkipped = 0;
        $scoreErrors    = [];
        $warnings       = [];

        $this->db->transStart();

        foreach ($parsedStudents as $st) {
            if ($st['match_status'] === 'not_found' || empty($st['db_student_id'])) {
                $warnings[] = "Siswa '{$st['file_name']}' tidak ditemukan di database — data dilewati.";
                $scoresSkipped += count($activeMapping);
                if ($importAbsence) $absenceSkipped++;
                continue;
            }

            $studentId = (int) $st['db_student_id'];

            foreach ($activeMapping as $cIdx => $subId) {
                $subId = (int) $subId;

                if ($allowedSubjectIds !== null && !in_array($subId, $allowedSubjectIds)) {
                    $scoresSkipped++;
                    continue;
                }

                $rawScore = $st['scores'][$cIdx] ?? null;
                if ($rawScore === null || $rawScore === '' || $rawScore === '-') {
                    $scoresSkipped++;
                    continue;
                }

                $scoreNum = (float) str_replace(',', '.', $rawScore);
                if ($scoreNum < 0 || $scoreNum > 100) {
                    $scoreErrors[] = "Siswa '{$st['file_name']}': nilai {$scoreNum} out of range (0-100) untuk mapel ID #{$subId}.";
                    $scoresSkipped++;
                    continue;
                }

                $existingScore = $this->db->table('grades')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $subId)
                    ->where('year_id', $yearId)
                    ->where('semester', $semesterLabel)
                    ->get()->getRowArray();

                if ($existingScore) {
                    if ($overwriteScores) {
                        $this->db->table('grades')
                            ->where('id', $existingScore['id'])
                            ->update([
                                'erapor_score' => $scoreNum,
                                'report_score' => $scoreNum,
                                'updated_at'   => date('Y-m-d H:i:s'),
                            ]);
                        $scoresSaved++;
                    } else {
                        $scoresSkipped++;
                    }
                } else {
                    $this->db->table('grades')->insert([
                        'student_id'   => $studentId,
                        'subject_id'   => $subId,
                        'year_id'      => $yearId,
                        'semester'     => $semesterLabel,
                        'erapor_score' => $scoreNum,
                        'report_score' => $scoreNum,
                        'created_at'   => date('Y-m-d H:i:s'),
                        'updated_at'   => date('Y-m-d H:i:s'),
                    ]);
                    $scoresSaved++;
                }
            }

            if ($importAbsence && $isWaliKelas && isset($st['sick'])) {
                $sick      = max(0, (int) $st['sick']);
                $permitted = max(0, (int) $st['permitted']);
                $absent    = max(0, (int) $st['absent']);

                $existingRecap = $this->db->table('attendance_recaps')
                    ->where('student_id', $studentId)
                    ->where('academic_year_id', $yearId)
                    ->where('semester', $semesterLabel)
                    ->get()->getRowArray();

                if ($existingRecap) {
                    if ($overwriteAbsence) {
                        $this->db->table('attendance_recaps')
                            ->where('id', $existingRecap['id'])
                            ->update([
                                'class_id'   => $classId,
                                'sick'       => $sick,
                                'permitted'  => $permitted,
                                'absent'     => $absent,
                                'source'     => 'import_leger',
                                'updated_at' => date('Y-m-d H:i:s'),
                            ]);
                        $absenceSaved++;
                    } else {
                        $absenceSkipped++;
                    }
                } else {
                    $this->db->table('attendance_recaps')->insert([
                        'student_id'       => $studentId,
                        'class_id'         => $classId,
                        'academic_year_id' => $yearId,
                        'semester'         => $semesterLabel,
                        'sick'             => $sick,
                        'permitted'        => $permitted,
                        'absent'           => $absent,
                        'source'           => 'import_leger',
                        'created_at'       => date('Y-m-d H:i:s'),
                        'updated_at'       => date('Y-m-d H:i:s'),
                    ]);
                    $absenceSaved++;
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to('admin/erapor/import')->with('error', 'Gagal menyimpan data import ke database (Database transaction error).');
        }

        log_message('info', "Import Leger Success: year=$yearId semester=$semester class=$classId scores_saved=$scoresSaved absence_saved=$absenceSaved by user={$user['id']}");

        $firstSubjectId = reset($activeMapping);
        $redirectInputUrl = ($classId && $firstSubjectId) 
            ? "admin/erapor/input/{$classId}/{$firstSubjectId}/" . ($semesterLabel === 'ganjil' ? '1' : '2') . "?year_id={$yearId}"
            : "admin/erapor";

        return view('admin/erapor/import_result', [
            'title'            => 'Hasil Import Leger Nilai',
            'stats'            => [
                'scores_saved'    => $scoresSaved,
                'scores_skipped'  => $scoresSkipped,
                'absence_saved'   => $absenceSaved,
                'absence_skipped' => $absenceSkipped,
                'score_errors'    => $scoreErrors,
                'warnings'        => array_unique($warnings),
            ],
            'redirectInputUrl' => $redirectInputUrl,
        ]);
    }

    // =========================================================
    // LEGER VIEW — Tampilan Leger Nilai & Rekap Absen Per Kelas
    // =========================================================
    public function legerView($classId, $semester = 1)
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = $this->request->getGet('year_id') ?? ($activeYear['id'] ?? null);

        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $year  = $this->db->table('academic_years')->where('id', $yearId)->get()->getRowArray();

        if (!$class || !$year) {
            return redirect()->back()->with('error', 'Kelas atau tahun ajaran tidak ditemukan.');
        }

        // Fetch students in class for specified academic year
        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.nisn, s.nis')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Only if truly no records at all for this class (no year filter fallback)
        if (empty($students)) {
            $students = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.nisn, s.nis')
                ->join('students s', 's.id = sr.student_id')
                ->where('sr.class_id', $classId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        $semesterLabel = ($semester == 1 || $semester == 'ganjil') ? 'ganjil' : 'genap';
        $studentIds    = array_column($students, 'id');

        // Fetch subjects assigned to this class in teaching_assignments
        $taSubjects = $this->db->table('teaching_assignments ta')
            ->select('s.id, s.name, s.code')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $classId)
            ->where('ta.academic_year_id', $yearId)
            ->get()->getResultArray();

        // ALSO fetch subjects that have scores saved in `grades` for students of this class
        $gradeSubjects = [];
        if (!empty($studentIds)) {
            $gradeSubjects = $this->db->table('grades g')
                ->select('s.id, s.name, s.code')
                ->join('subjects s', 's.id = g.subject_id')
                ->whereIn('g.student_id', $studentIds)
                ->where('g.year_id', $yearId)
                ->where('g.semester', $semesterLabel)
                ->where('(g.erapor_score IS NOT NULL OR g.report_score IS NOT NULL)')
                ->get()->getResultArray();
        }

        // Merge both sources and remove duplicates
        $subjectMap = [];
        foreach (array_merge($taSubjects, $gradeSubjects) as $sub) {
            $subjectMap[$sub['id']] = $sub;
        }

        if (empty($subjectMap)) {
            $allSubs = $this->db->table('subjects')
                ->select('id, name, code')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
            foreach ($allSubs as $sub) {
                $subjectMap[$sub['id']] = $sub;
            }
        }

        $assignedSubjects = array_values($subjectMap);
        usort($assignedSubjects, fn($a, $b) => strcmp($a['name'], $b['name']));

        // Fetch erapor scores from grades
        $scores = [];
        if (!empty($studentIds) && !empty($assignedSubjects)) {
            $subjectIds = array_column($assignedSubjects, 'id');
            $gradeRows = $this->db->table('grades')
                ->select('student_id, subject_id, erapor_score, report_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getResultArray();

            foreach ($gradeRows as $gr) {
                $val = $gr['erapor_score'] ?? $gr['report_score'];
                $scores[$gr['student_id']][$gr['subject_id']] = $val;
            }
        }

        // Fetch attendance recaps
        $attendanceRecaps = [];
        if (!empty($studentIds)) {
            $recapRows = $this->db->table('attendance_recaps')
                ->select('student_id, sick, permitted, absent')
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getResultArray();

            foreach ($recapRows as $rr) {
                $attendanceRecaps[$rr['student_id']] = [
                    'sick'      => $rr['sick'],
                    'permitted' => $rr['permitted'],
                    'absent'    => $rr['absent'],
                ];
            }
        }

        // Fetch School Profile & Principal Info
        $settingsModel = new \App\Models\SettingsModel();
        $schoolModel   = new \App\Models\SchoolModel();
        $school        = $schoolModel->first() ?? [];

        $pName = trim($settingsModel->getValue('principal_name') ?? '');
        if ($pName === '' || $pName === '-') {
            $pName = trim($school['headmaster'] ?? '');
        }
        if ($pName === '' || $pName === '-') {
            $kepsek = $this->db->table('teachers')->like('functional_position', 'Kepala Sekolah')->get()->getRowArray();
            if ($kepsek) {
                $pName = trim($kepsek['name']);
            }
        }

        $pNip = trim($settingsModel->getValue('principal_nip') ?? '');
        if ($pNip === '' || $pNip === '-') {
            $pNip = trim($school['principal_nip'] ?? '');
        }
        if ($pNip === '' || $pNip === '-') {
            if (!isset($kepsek)) {
                $kepsek = $this->db->table('teachers')->like('functional_position', 'Kepala Sekolah')->get()->getRowArray();
            }
            if ($kepsek && !empty($kepsek['nip'])) {
                $pNip = trim($kepsek['nip']);
            }
        }

        // Fetch Wali Kelas (Homeroom Teacher)
        $waliKelas = null;
        if (!empty($class['teacher_id'])) {
            $waliKelas = $this->db->table('teachers')
                ->select('name, nip')
                ->where('id', $class['teacher_id'])
                ->get()->getRowArray();
        }

        return view('admin/erapor/leger_view', [
            'title'            => 'Leger Nilai & Rekap Absen — ' . $class['name'],
            'class'            => $class,
            'year'             => $year,
            'semester'         => $semester,
            'semesterLabel'    => ucfirst($semesterLabel),
            'subjects'         => $assignedSubjects,
            'students'         => $students,
            'scores'           => $scores,
            'attendanceRecaps' => $attendanceRecaps,
            'school'           => $school,
            'principal'        => [
                'name' => ($pName !== '' && $pName !== '-') ? $pName : '—',
                'nip'  => ($pNip !== '' && $pNip !== '-') ? $pNip : '—',
            ],
            'waliKelas'        => [
                'name' => $waliKelas['name'] ?? '—',
                'nip'  => $waliKelas['nip'] ?? '—',
            ],
            'isAdmin'          => in_array($roleId, [1, 2]),
        ]);
    }

    // =========================================================
    // PRINT LEGER — Standalone Printable View (Tab Baru)
    // =========================================================
    public function printLeger($classId, $semester = 1)
    {
        $user   = $this->currentUser();
        $roleId = (int) ($user['role_id'] ?? 0);

        $activeYear = $this->yearModel->getActiveYear();
        $yearId     = $this->request->getGet('year_id') ?? ($activeYear['id'] ?? null);

        // Options: Kop & Tanda Tangan
        $useKop       = $this->request->getGet('use_kop') !== '0';
        $useSignature = $this->request->getGet('use_signature') !== '0';

        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $year  = $this->db->table('academic_years')->where('id', $yearId)->get()->getRowArray();

        if (!$class || !$year) {
            return 'Data kelas atau tahun ajaran tidak ditemukan.';
        }

        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.nisn, s.nis')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            $students = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.nisn, s.nis')
                ->join('students s', 's.id = sr.student_id')
                ->where('sr.class_id', $classId)
                ->groupBy('s.id')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        }

        $semesterLabel = ($semester == 1 || $semester == 'ganjil') ? 'ganjil' : 'genap';
        $studentIds    = array_column($students, 'id');

        // Fetch subjects assigned to this class in teaching_assignments
        $taSubjects = $this->db->table('teaching_assignments ta')
            ->select('s.id, s.name, s.code')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $classId)
            ->where('ta.academic_year_id', $yearId)
            ->get()->getResultArray();

        // ALSO fetch subjects that have scores saved in `grades` for students of this class
        $gradeSubjects = [];
        if (!empty($studentIds)) {
            $gradeSubjects = $this->db->table('grades g')
                ->select('s.id, s.name, s.code')
                ->join('subjects s', 's.id = g.subject_id')
                ->whereIn('g.student_id', $studentIds)
                ->where('g.year_id', $yearId)
                ->where('g.semester', $semesterLabel)
                ->where('(g.erapor_score IS NOT NULL OR g.report_score IS NOT NULL)')
                ->get()->getResultArray();
        }

        // Merge both sources and remove duplicates
        $subjectMap = [];
        foreach (array_merge($taSubjects, $gradeSubjects) as $sub) {
            $subjectMap[$sub['id']] = $sub;
        }

        if (empty($subjectMap)) {
            $allSubs = $this->db->table('subjects')
                ->select('id, name, code')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
            foreach ($allSubs as $sub) {
                $subjectMap[$sub['id']] = $sub;
            }
        }

        $assignedSubjects = array_values($subjectMap);
        usort($assignedSubjects, fn($a, $b) => strcmp($a['name'], $b['name']));

        $scores = [];
        if (!empty($studentIds) && !empty($assignedSubjects)) {
            $subjectIds = array_column($assignedSubjects, 'id');
            $gradeRows = $this->db->table('grades')
                ->select('student_id, subject_id, erapor_score, report_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getResultArray();

            foreach ($gradeRows as $gr) {
                $val = $gr['erapor_score'] ?? $gr['report_score'];
                $scores[$gr['student_id']][$gr['subject_id']] = $val;
            }
        }

        $attendanceRecaps = [];
        if (!empty($studentIds)) {
            $recapRows = $this->db->table('attendance_recaps')
                ->select('student_id, sick, permitted, absent')
                ->whereIn('student_id', $studentIds)
                ->where('academic_year_id', $yearId)
                ->where('semester', $semesterLabel)
                ->get()->getResultArray();

            foreach ($recapRows as $rr) {
                $attendanceRecaps[$rr['student_id']] = [
                    'sick'      => $rr['sick'],
                    'permitted' => $rr['permitted'],
                    'absent'    => $rr['absent'],
                ];
            }
        }

        // Fetch School Profile & Principal Info
        $settingsModel = new \App\Models\SettingsModel();
        $schoolModel   = new \App\Models\SchoolModel();
        $school        = $schoolModel->first() ?? [];

        $pName = trim($settingsModel->getValue('principal_name') ?? '');
        if ($pName === '' || $pName === '-') {
            $pName = trim($school['headmaster'] ?? '');
        }
        if ($pName === '' || $pName === '-') {
            $kepsek = $this->db->table('teachers')->like('functional_position', 'Kepala Sekolah')->get()->getRowArray();
            if ($kepsek) {
                $pName = trim($kepsek['name']);
            }
        }

        $pNip = trim($settingsModel->getValue('principal_nip') ?? '');
        if ($pNip === '' || $pNip === '-') {
            $pNip = trim($school['principal_nip'] ?? '');
        }
        if ($pNip === '' || $pNip === '-') {
            if (!isset($kepsek)) {
                $kepsek = $this->db->table('teachers')->like('functional_position', 'Kepala Sekolah')->get()->getRowArray();
            }
            if ($kepsek && !empty($kepsek['nip'])) {
                $pNip = trim($kepsek['nip']);
            }
        }

        $waliKelas = null;
        if (!empty($class['teacher_id'])) {
            $waliKelas = $this->db->table('teachers')
                ->select('name, nip')
                ->where('id', $class['teacher_id'])
                ->get()->getRowArray();
        }

        $logoBase64 = null;
        if (!empty($school['logo']) && file_exists(FCPATH . $school['logo'])) {
            $ext = pathinfo(FCPATH . $school['logo'], PATHINFO_EXTENSION);
            $mime = 'image/' . ($ext === 'jpg' || $ext === 'jpeg' ? 'jpeg' : 'png');
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents(FCPATH . $school['logo']));
        }

        $pdfGenerator = new \App\Libraries\PdfGenerator();
        $safeClassName = preg_replace('/[^A-Za-z0-9_\-]/', '_', $class['name']);
        $filename = "Leger_Nilai_{$safeClassName}_Sem{$semester}.pdf";

        $pdfGenerator->stream(
            'admin/erapor/pdf_leger_template',
            [
                'title'            => 'Leger Nilai — ' . $class['name'],
                'class'            => $class,
                'year'             => $year,
                'semester'         => $semester,
                'semesterLabel'    => ucfirst($semesterLabel),
                'subjects'         => $assignedSubjects,
                'students'         => $students,
                'scores'           => $scores,
                'attendanceRecaps' => $attendanceRecaps,
                'school'           => $school,
                'principal'        => [
                    'name' => ($pName !== '' && $pName !== '-') ? $pName : '—',
                    'nip'  => ($pNip !== '' && $pNip !== '-') ? $pNip : '—',
                ],
                'waliKelas'        => [
                    'name' => $waliKelas['name'] ?? '—',
                    'nip'  => $waliKelas['nip'] ?? '—',
                ],
                'useKop'           => $useKop,
                'useSignature'     => $useSignature,
                'logo_base64'      => $logoBase64,
            ],
            $filename,
            'landscape',
            $useKop,
            false, // Attachment = false (Stream langsung di tab browser)
            'A4'
        );
    }
}




