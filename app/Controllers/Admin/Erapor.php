<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;

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

        // Daftar siswa aktif di kelas ini
        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.religion')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif di kelas ini.');
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
            ->select('student_id, erapor_score, report_score')
            ->whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('year_id', $yearId)
            ->where('semester', $semesterLabel)
            ->get()->getResultArray();
        $savedErapor  = array_column($savedRows, 'erapor_score', 'student_id');
        $savedRapor   = array_column($savedRows, 'report_score', 'student_id');

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
            ];
        }

        return view('admin/erapor/input', [
            'title'     => 'Input Nilai Erapor',
            'class'     => $class,
            'subject'   => $subject,
            'year'      => $year,
            'semester'  => (int) $semester,
            'students'  => $data,
            'fWeight'   => $fWeight * 100,
            'sWeight'   => $sWeight * 100,
            'isAdmin'   => in_array($roleId, [1, 2]),
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
        $saved = 0;

        foreach ($scores as $studentId => $nilai) {
            $studentId = (int) $studentId;
            $nilai     = $nilai === '' ? null : round((float) $nilai, 2);

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

            if ($existing) {
                $this->db->table('grades')
                    ->where('id', $existing['id'])
                    ->update(['erapor_score' => $nilai, 'updated_at' => date('Y-m-d H:i:s')]);
            } else {
                $this->db->table('grades')->insert([
                    'student_id'   => $studentId,
                    'subject_id'   => $subjectId,
                    'year_id'      => $yearId,
                    'semester'     => $semesterLabel,
                    'erapor_score' => $nilai,
                    'created_at'   => date('Y-m-d H:i:s'),
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }
            $saved++;
        }

        log_message('info', "Erapor saved: class=$classId subject=$subjectId year=$yearId semester=$semester count=$saved by user={$user['id']}");

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Nilai erapor berhasil disimpan ($saved siswa).",
            'saved'   => $saved,
        ]);
    }
}
