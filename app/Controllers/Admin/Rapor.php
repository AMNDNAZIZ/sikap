<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\SchoolModel;
use App\Models\TeacherModel;

/**
 * Modul Rapor.
 * Tahap 1: Rapor PTS (Penilaian Tengah Semester) — disederhanakan dari
 * format rapor Kurikulum Merdeka: hanya Nilai Materi (rata-rata formatif)
 * dan Nilai PTS per mata pelajaran, tanpa deskripsi.
 *
 * Akses cetak: Admin (semua kelas) & Guru yang menjadi Wali Kelas (kelas walian).
 */
class Rapor extends BaseController
{
    protected $db;
    protected $yearModel;
    protected $schoolModel;
    protected $teacherModel;

    public function __construct()
    {
        $this->db          = \Config\Database::connect();
        $this->yearModel   = new AcademicYearModel();
        $this->schoolModel = new SchoolModel();
        $this->teacherModel = new TeacherModel();
    }

    /**
     * Daftar kelas yang bisa dicetak rapornya oleh user.
     * - Admin (1): semua kelas aktif.
     * - Guru (3): hanya kelas yang diwalikan (classes.teacher_id).
     */
    public function index()
    {
        $user   = session()->get('user');
        $roleId = (int) ($user['role_id'] ?? 0);

        // Hanya Admin & Guru (wali kelas). Kepsek/role lain: tolak.
        if (!in_array($roleId, [1, 3], true)) {
            return redirect()->to('/dashboard')->with('error', 'Menu cetak rapor hanya untuk Admin dan Wali Kelas.');
        }

        $activeYear = $this->yearModel->getActiveYear();

        $builder = $this->db->table('classes c')
            ->select('c.id, c.name, c.level, t.name as wali_name')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.is_active', 1);

        if ($roleId === 3) {
            // Guru hanya kelas yang diwalikan
            $teacherId = (int) ($user['related_id'] ?? 0);
            $builder->where('c.teacher_id', $teacherId);
        }

        $classes = $builder->orderBy('c.level', 'ASC')->orderBy('c.name', 'ASC')->get()->getResultArray();

        return view('admin/rapor/index', [
            'title'      => 'Cetak Rapor',
            'classes'    => $classes,
            'activeYear' => $activeYear,
            'roleId'     => $roleId,
        ]);
    }

    /**
     * Verifikasi apakah user boleh mengakses kelas tsb.
     */
    protected function canAccessClass(int $classId): bool
    {
        $user   = session()->get('user');
        $roleId = (int) ($user['role_id'] ?? 0);

        if ($roleId === 1) {
            return true; // Admin
        }
        if ($roleId === 3) {
            $teacherId = (int) ($user['related_id'] ?? 0);
            $cls = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            return $cls && (int) $cls['teacher_id'] === $teacherId;
        }
        return false;
    }

    /**
     * Cetak Rapor PTS untuk satu kelas & semester.
     * $semester: 1 (ganjil) atau 2 (genap).
     */
    public function cetakPts($classId, $semester)
    {
        $classId  = (int) $classId;
        $semester = (string) $semester;

        if (!in_array($semester, ['1', '2'], true)) {
            return redirect()->to('admin/rapor')->with('error', 'Semester tidak valid.');
        }

        if (!$this->canAccessClass($classId)) {
            return redirect()->to('admin/rapor')->with('error', 'Anda tidak memiliki akses ke kelas ini.');
        }

        $activeYear   = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        if (!$activeYearId) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $class = $this->db->table('classes c')
            ->select('c.*, t.name as wali_name, t.nip as wali_nip')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('c.id', $classId)
            ->get()->getRowArray();
        if (!$class) {
            return redirect()->to('admin/rapor')->with('error', 'Kelas tidak ditemukan.');
        }

        $school = $this->schoolModel->getProfile();

        // Mata pelajaran untuk kelas ini: berdasarkan teaching_assignments di tahun aktif.
        // Fallback: semua subject aktif bila belum ada penugasan.
        $subjects = $this->db->table('subjects s')
            ->select('s.id, s.name, s.subject_group, s.sort_order')
            ->distinct()
            ->join('teaching_assignments ta', 'ta.subject_id = s.id AND ta.class_id = ' . $classId . ' AND ta.academic_year_id = ' . (int) $activeYearId, 'inner')
            ->where('s.is_active', 1)
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($subjects)) {
            $subjects = $this->db->table('subjects')
                ->select('id, name, subject_group, sort_order')
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        }

        $classLevel = (int) ($class['level'] ?? 0);

        // Siswa aktif di kelas & tahun aktif
        $students = $this->db->table('student_records sr')
            ->select('s.id, s.name, s.nisn, s.nis')
            ->join('students s', 's.id = sr.student_id', 'inner')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Semester label untuk filter nilai materi (ATP.semester menyimpan 1/2 atau ganjil/genap)
        $semesterAltLabels = $semester === '1' ? ['1', 'ganjil', 'Ganjil'] : ['2', 'genap', 'Genap'];

        // Bangun data nilai per siswa
        $reportData = [];
        foreach ($students as $stu) {
            $sid = (int) $stu['id'];
            $rows = [];
            foreach ($subjects as $subj) {
                $subjectId = (int) $subj['id'];

                // Nilai Materi = rata-rata seluruh nilai formatif (material_scores) siswa
                // untuk materi (ATP) mapel ini, di semester terpilih bila kolom semester ada.
                $materiAvg = $this->db->table('material_scores ms')
                    ->select('AVG(ms.score) as avg_score')
                    ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id', 'inner')
                    ->where('ms.student_id', $sid)
                    ->where('atp.subject_id', $subjectId)
                    ->whereIn('atp.semester', $semesterAltLabels)
                    ->where('ms.score IS NOT NULL')
                    ->get()->getRowArray();
                $nilaiMateri = isset($materiAvg['avg_score']) && $materiAvg['avg_score'] !== null
                    ? round((float) $materiAvg['avg_score'], 1)
                    : null;

                // Nilai PTS dari pts_scores
                $ptsRow = $this->db->table('pts_scores')
                    ->select('score')
                    ->where('student_id', $sid)
                    ->where('subject_id', $subjectId)
                    ->where('year_id', $activeYearId)
                    ->where('semester', $semester)
                    ->get()->getRowArray();
                $nilaiPts = $ptsRow && $ptsRow['score'] !== null ? round((float) $ptsRow['score'], 1) : null;

                $rows[] = [
                    'subject_name' => $subj['name'],
                    'nilai_materi' => $nilaiMateri,
                    'nilai_pts'    => $nilaiPts,
                ];
            }

            // Rekap ketidakhadiran (semester terkait tahun ajaran aktif)
            $att = [
                'sakit' => $this->db->table('attendances')->where('student_id', $sid)->where('status', 'Sakit')->countAllResults(),
                'izin'  => $this->db->table('attendances')->where('student_id', $sid)->where('status', 'Izin')->countAllResults(),
                'alpa'  => $this->db->table('attendances')->where('student_id', $sid)->where('status', 'Alpa')->countAllResults(),
            ];

            $reportData[] = [
                'student'    => $stu,
                'rows'       => $rows,
                'attendance' => $att,
            ];
        }

        return view('admin/rapor/pts_print', [
            'class'      => $class,
            'school'     => $school,
            'semester'   => $semester,
            'activeYear' => $activeYear,
            'reportData' => $reportData,
            'subjects'   => $subjects,
        ]);
    }
}
