<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkCaseModel;
use App\Models\BkServiceModel;
use App\Models\BkAssessmentModel;
use App\Models\BkProgramModel;
use App\Models\BkRplModel;
use App\Models\BkIndividualPlanModel;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\AcademicYearModel;
use App\Models\BkServiceParticipantModel;
use App\Models\BkCaseActionModel;
use App\Models\BkCaseReferralModel;
use App\Models\BkCaseLogModel;
use Config\Database;

class BkModule extends BaseController
{
    protected $caseModel;
    protected $serviceModel;
    protected $assessmentModel;
    protected $programModel;
    protected $rplModel;
    protected $planModel;
    protected $studentModel;
    protected $classModel;
    protected $teacherModel;
    protected $academicYearModel;
    protected $participantModel;
    protected $caseActionModel;
    protected $caseReferralModel;
    protected $caseLogModel;
    protected $db;

    public function __construct()
    {
        $this->caseModel          = new BkCaseModel();
        $this->serviceModel       = new BkServiceModel();
        $this->assessmentModel    = new BkAssessmentModel();
        $this->programModel       = new BkProgramModel();
        $this->rplModel           = new BkRplModel();
        $this->planModel          = new BkIndividualPlanModel();
        $this->studentModel       = new StudentModel();
        $this->classModel         = new ClassModel();
        $this->teacherModel       = new TeacherModel();
        $this->academicYearModel  = new AcademicYearModel();
        $this->participantModel   = new BkServiceParticipantModel();
        $this->caseActionModel    = new BkCaseActionModel();
        $this->caseReferralModel  = new BkCaseReferralModel();
        $this->caseLogModel       = new BkCaseLogModel();
        $this->db                 = Database::connect();
    }

    // =========================================================
    // HELPER: Dapatkan Tahun Ajaran Aktif
    // =========================================================
    protected function getActiveYear()
    {
        return $this->academicYearModel->getActiveYear();
    }

    // =========================================================
    // HELPER: Query Siswa Aktif di Tahun Ajaran Berjalan
    // Menggunakan student_records.status = 'aktif' dan academic_year_id
    // =========================================================
    protected function getActiveStudentsQuery($academicYearId = null, $classId = null)
    {
        if (empty($academicYearId)) {
            $activeYear     = $this->getActiveYear();
            $academicYearId = $activeYear['id'] ?? null;
        }

        $builder = $this->db->table('students s')
            ->select('s.id, s.name, s.nisn, s.nis, s.gender, s.photo,
                      cl.id as class_id, cl.name as class_name, cl.level as class_level,
                      sr.academic_year_id, sr.status as record_status,
                      t.name as wali_kelas_name')
            ->join('student_records sr', 'sr.student_id = s.id', 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('teachers t', 't.id = cl.teacher_id', 'left')
            ->where('sr.status', 'aktif');

        if (!empty($academicYearId)) {
            $builder->where('sr.academic_year_id', $academicYearId);
        }

        if (!empty($classId)) {
            $builder->where('sr.class_id', $classId);
        }

        return $builder->orderBy('cl.name', 'ASC')->orderBy('s.name', 'ASC');
    }

    // =========================================================
    // HELPER: Cek Apakah Guru BK atau Admin
    // =========================================================
    protected function isGuruBkOrAdmin()
    {
        $user = session()->get('user');
        if (!$user) return false;
        // Role 1 = Admin, 7 = Guru BK (sesuai sistem existing)
        if (in_array((int)($user['role_id'] ?? 0), [1, 7])) {
            return true;
        }
        if (isset($user['id'])) {
            $teacher = $this->teacherModel->where('user_id', $user['id'])->first();
            if ($teacher && stripos($teacher['jenis_ptk'] ?? '', 'BK') !== false) {
                return true;
            }
        }
        return false;
    }

    // =========================================================
    // HELPER: Map Status ke Step Workflow
    // =========================================================
    protected function mapStatusToWorkflowStep($status)
    {
        $map = [
            'DRAFT'         => 'Laporan/Pengaduan',
            'REPORTED'      => 'Laporan/Pengaduan',
            'VERIFIED'      => 'Verifikasi',
            'IN_ASSESSMENT' => 'Asesmen',
            'IN_PROGRESS'   => 'Intervensi',
            'MONITORING'    => 'Monitoring',
            'REFERRED'      => 'Selesai / Rujukan',
            'RESOLVED'      => 'Evaluasi',
            'CLOSED'        => 'Selesai / Rujukan',
        ];
        return $map[$status] ?? 'Laporan/Pengaduan';
    }

    // =========================================================
    // HELPER: Escape HTML output (XSS prevention)
    // =========================================================
    protected function e($value)
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    // =========================================================
    // HELPER: Validasi kepemilikan kasus (IDOR Prevention)
    // =========================================================
    protected function findCaseOrFail($id)
    {
        $case = $this->caseModel->find((int)$id);
        if (!$case) {
            return null;
        }
        return $case;
    }

    // =========================================================
    // A. Dashboard BP/BK (Berorientasi Tindakan & Early Warning)
    // =========================================================
    public function index()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        // 1. Total siswa aktif di tahun ajaran berjalan
        $totalStudents = $this->db->table('student_records')
            ->where('status', 'aktif')
            ->where('academic_year_id', $activeYearId)
            ->countAllResults();

        // 2. Siswa yang menerima layanan BK (unik, tahun berjalan)
        $servedStudentsCount = $this->db->table('bk_service_participants bsp')
            ->select('bsp.student_id')
            ->join('bk_services bs', 'bs.id = bsp.service_id', 'inner')
            ->where('bs.academic_year_id', $activeYearId)
            ->groupBy('bsp.student_id')
            ->countAllResults();

        // 3. Layanan bulan berjalan
        $currentMonth = date('Y-m');
        $currentMonthServices = $this->db->table('bk_services')
            ->like('service_date', $currentMonth)
            ->countAllResults();

        // 4. Kasus aktif (dalam workflow penanganan)
        $activeCasesCount = $this->db->table('bk_cases')
            ->whereIn('status', ['REPORTED', 'VERIFIED', 'IN_ASSESSMENT', 'IN_PROGRESS'])
            ->countAllResults();

        // 5. Kasus dalam monitoring
        $monitoringCasesCount = $this->db->table('bk_cases')
            ->where('status', 'MONITORING')
            ->countAllResults();

        // 6. Tindak lanjut yang masih berlangsung
        $pendingFollowUpsCount = $this->db->table('bk_cases')
            ->whereIn('status', ['IN_PROGRESS', 'MONITORING', 'IN_ASSESSMENT'])
            ->countAllResults();

        // 7. Rujukan eksternal
        $referralsCount = $this->db->table('bk_cases')
            ->where('status', 'REFERRED')
            ->countAllResults();

        // 8. Program BK berjalan
        $activeProgramsCount = $this->db->table('bk_programs')
            ->whereIn('status', ['Active', 'Approved'])
            ->countAllResults();

        // ===================================================
        // BAGIAN "PERLU PERHATIAN" - hanya siswa aktif tahun berjalan
        // ===================================================

        // 🟡 Kehadiran Menurun (Alpa >= 2 pada tahun berjalan)
        $lowAttendanceStudents = $this->db->table('attendances a')
            ->select('s.id, s.name, s.nisn, cl.name as class_name, COUNT(a.id) as total_alpa')
            ->join('students s', 's.id = a.student_id', 'inner')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->where('a.status', 'Alpa')
            ->groupBy('a.student_id')
            ->having('total_alpa >= 2')
            ->orderBy('total_alpa', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // 🟡 Capaian Akademik Menurun (Rata-rata Nilai < 75)
        $lowAcademicStudents = $this->db->table('subject_scores ss')
            ->select('s.id, s.name, s.nisn, cl.name as class_name, ROUND(AVG(ss.report_score), 1) as avg_score')
            ->join('students s', 's.id = ss.student_id', 'inner')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->groupBy('ss.student_id')
            ->having('avg_score > 0 AND avg_score < 75')
            ->orderBy('avg_score', 'ASC')
            ->limit(10)
            ->get()->getResultArray();

        // 🟡 Perubahan Perilaku (Catatan Observasi Guru Mapel / Wali Kelas)
        $behaviorNotesStudents = $this->db->table('student_notes sn')
            ->select('s.id, s.name, s.nisn, cl.name as class_name, COUNT(sn.id) as total_notes, MAX(sn.created_at) as last_note_date')
            ->join('students s', 's.id = sn.student_id', 'inner')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->groupBy('sn.student_id')
            ->orderBy('last_note_date', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // 🟡 Kasus Aktif Siswa
        $activeCaseStudents = $this->db->table('bk_cases bc')
            ->select('s.id, s.name, s.nisn, cl.name as class_name, bc.case_code, bc.category, bc.severity, bc.status, bc.workflow_step')
            ->join('students s', 's.id = bc.student_id', 'inner')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->whereIn('bc.status', ['REPORTED', 'VERIFIED', 'IN_ASSESSMENT', 'IN_PROGRESS', 'MONITORING', 'REFERRED'])
            ->orderBy('bc.created_at', 'DESC')
            ->limit(10)
            ->get()->getResultArray();

        // Dropdown Quick Action - siswa aktif tahun berjalan
        $studentsList = $this->getActiveStudentsQuery($activeYearId)->get()->getResultArray();

        $classesList = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classesList)) {
            $classesList = $this->classModel->findAll();
        }

        $academicYears = $this->academicYearModel->orderBy('start_date', 'DESC')->findAll();

        return view('admin/bk/dashboard', [
            'title'                 => 'Dashboard Bimbingan Konseling (BP/BK)',
            'activeYear'            => $activeYear,
            'totalStudents'         => $totalStudents,
            'servedStudentsCount'   => $servedStudentsCount,
            'currentMonthServices'  => $currentMonthServices,
            'activeCasesCount'      => $activeCasesCount,
            'monitoringCasesCount'  => $monitoringCasesCount,
            'pendingFollowUpsCount' => $pendingFollowUpsCount,
            'referralsCount'        => $referralsCount,
            'activeProgramsCount'   => $activeProgramsCount,
            'lowAttendanceStudents' => $lowAttendanceStudents,
            'lowAcademicStudents'   => $lowAcademicStudents,
            'behaviorNotesStudents' => $behaviorNotesStudents,
            'activeCaseStudents'    => $activeCaseStudents,
            'studentsList'          => $studentsList,
            'classesList'           => $classesList,
            'academicYears'         => $academicYears,
        ]);
    }

    // =========================================================
    // B. Pemetaan Siswa
    // =========================================================
    public function pemetaan()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        $instrumentModel = new \App\Models\BkInstrumentModel();
        $assessments = $instrumentModel->findAll();

        $classes = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classes)) {
            $classes = $this->classModel->findAll();
        }

        // Hasil asesmen dari siswa aktif
        $responses = $this->db->table('bk_student_results bsr')
            ->select('bsr.*, s.name as student_name, s.nisn, cl.name as class_name, bi.title as assessment_title, bi.assessment_type')
            ->join('students s', 's.id = bsr.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('bk_instruments bi', 'bi.id = bsr.instrument_id', 'left')
            ->orderBy('bsr.calculated_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/bk/pemetaan', [
            'title'       => 'Pemetaan Siswa & Asesmen BK',
            'assessments' => $assessments,
            'classes'     => $classes,
            'responses'   => $responses,
            'activeYear'  => $activeYear,
        ]);
    }

    // =========================================================
    // C. Program BK
    // =========================================================
    public function program()
    {
        $activeYear = $this->getActiveYear();
        $programs   = $this->programModel->findAll();
        $rpls       = $this->db->table('bk_rpl r')
            ->select('r.*, cl.name as class_name')
            ->join('classes cl', 'cl.id = r.target_class_id', 'left')
            ->orderBy('r.created_at', 'DESC')
            ->get()->getResultArray();

        $classes = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classes)) {
            $classes = $this->classModel->findAll();
        }

        return view('admin/bk/program', [
            'title'      => 'Program & RPL BK',
            'programs'   => $programs,
            'rpls'       => $rpls,
            'classes'    => $classes,
            'activeYear' => $activeYear,
        ]);
    }

    // ------------------------------------------------------------------
    // PROGRAM CRUD
    // ------------------------------------------------------------------
    public function storeProgram()
    {
        $activeYear = $this->getActiveYear();
        $user       = session()->get('user');
        $teacher    = $this->teacherModel->where('user_id', $user['id'] ?? 0)->first();

        $data = [
            'academic_year_id' => $this->request->getPost('academic_year_id') ?: ($activeYear['id'] ?? null),
            'counselor_id'     => $teacher['id'] ?? null,
            'title'            => $this->request->getPost('title'),
            'program_type'     => $this->request->getPost('program_type'),
            'period_month'     => $this->request->getPost('period_month'),
            'field'            => $this->request->getPost('field'),
            'target_class_id'  => $this->request->getPost('target_class_id') ?: null,
            'description'      => $this->request->getPost('description'),
            'status'           => 'draft',
        ];

        $this->programModel->insert($data);
        return redirect()->to('admin/bk/program')->with('success', 'Program BK berhasil dibuat.');
    }

    public function updateProgram($id)
    {
        $data = [
            'title'           => $this->request->getPost('title'),
            'program_type'    => $this->request->getPost('program_type'),
            'period_month'    => $this->request->getPost('period_month'),
            'field'           => $this->request->getPost('field'),
            'target_class_id' => $this->request->getPost('target_class_id') ?: null,
            'description'     => $this->request->getPost('description'),
            'status'          => $this->request->getPost('status') ?: 'draft',
        ];
        $this->programModel->update($id, $data);
        return redirect()->to('admin/bk/program')->with('success', 'Program BK berhasil diperbarui.');
    }

    public function deleteProgram($id)
    {
        $this->programModel->delete($id);
        return redirect()->to('admin/bk/program')->with('success', 'Program BK berhasil dihapus.');
    }

    // ------------------------------------------------------------------
    // RPL BK CRUD
    // ------------------------------------------------------------------
    public function storeRpl()
    {
        $user    = session()->get('user');
        $teacher = $this->teacherModel->where('user_id', $user['id'] ?? 0)->first();

        $data = [
            'program_id'       => $this->request->getPost('program_id') ?: null,
            'title'            => $this->request->getPost('title'),
            'service_type'     => $this->request->getPost('service_type'),
            'field'            => $this->request->getPost('field'),
            'target_class_id'  => $this->request->getPost('target_class_id') ?: null,
            'duration_minutes' => (int)$this->request->getPost('duration_minutes') ?: 45,
            'purpose'          => $this->request->getPost('purpose'),
            'media_tools'      => $this->request->getPost('media_tools'),
            'methods'          => $this->request->getPost('methods'),
            'evaluation_steps' => $this->request->getPost('evaluation_steps'),
            'created_by'       => $teacher['id'] ?? null,
        ];

        $this->rplModel->insert($data);
        return redirect()->to('admin/bk/program')->with('success', 'RPL BK berhasil dibuat.');
    }

    public function updateRpl($id)
    {
        $data = [
            'program_id'       => $this->request->getPost('program_id') ?: null,
            'title'            => $this->request->getPost('title'),
            'service_type'     => $this->request->getPost('service_type'),
            'field'            => $this->request->getPost('field'),
            'target_class_id'  => $this->request->getPost('target_class_id') ?: null,
            'duration_minutes' => (int)$this->request->getPost('duration_minutes') ?: 45,
            'purpose'          => $this->request->getPost('purpose'),
            'media_tools'      => $this->request->getPost('media_tools'),
            'methods'          => $this->request->getPost('methods'),
            'evaluation_steps' => $this->request->getPost('evaluation_steps'),
        ];
        $this->rplModel->update($id, $data);
        return redirect()->to('admin/bk/program')->with('success', 'RPL BK berhasil diperbarui.');
    }

    public function deleteRpl($id)
    {
        $this->rplModel->delete($id);
        return redirect()->to('admin/bk/program')->with('success', 'RPL BK berhasil dihapus.');
    }

    public function detailRpl($id)
    {
        $rpl = $this->db->table('bk_rpl r')
            ->select('r.*, cl.name as class_name, p.title as program_title')
            ->join('classes cl', 'cl.id = r.target_class_id', 'left')
            ->join('bk_programs p', 'p.id = r.program_id', 'left')
            ->where('r.id', $id)
            ->get()->getRowArray();

        if (!$rpl) {
            return redirect()->to('admin/bk/program')->with('error', 'RPL tidak ditemukan.');
        }
        return $this->response->setJSON(['status' => 'success', 'rpl' => $rpl]);
    }

    // =========================================================
    // D. Layanan BK (Bimbingan Klasikal, Konseling Individual,
    //    Bimbingan Kelompok, Konseling Kelompok, Konsultasi, Rujukan, dll.)
    // =========================================================
    public function layanan()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        // Parameter filter dari GET
        $filters = [
            'search'           => $this->request->getGet('search'),
            'class_id'         => $this->request->getGet('class_id'),
            'academic_year_id' => $this->request->getGet('academic_year_id') ?: $activeYearId,
            'counselor_id'     => $this->request->getGet('counselor_id'),
            'service_type'     => $this->request->getGet('service_type'),
            'status'           => $this->request->getGet('status'),
            'date'             => $this->request->getGet('date'),
        ];

        $services = $this->serviceModel->getServicesWithDetails($filters);

        foreach ($services as &$srv) {
            $srv['participants']      = $this->participantModel->getParticipantsWithDetails($srv['id']);
            $srv['total_participants'] = count($srv['participants']);
        }
        unset($srv);

        // Selector: siswa AKTIF di tahun berjalan
        $students = $this->getActiveStudentsQuery($activeYearId)->get()->getResultArray();

        $classes = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classes)) {
            $classes = $this->classModel->findAll();
        }

        $teachers = $this->teacherModel->where('is_active', 1)->findAll();
        if (empty($teachers)) {
            $teachers = $this->teacherModel->findAll();
        }

        $academicYears = $this->academicYearModel->orderBy('start_date', 'DESC')->findAll();

        return view('admin/bk/layanan', [
            'title'         => 'Layanan Bimbingan Konseling (BP/BK)',
            'services'      => $services,
            'classes'       => $classes,
            'students'      => $students,
            'teachers'      => $teachers,
            'academicYears' => $academicYears,
            'activeYear'    => $activeYear,
            'filters'       => $filters,
        ]);
    }

    /**
     * Simpan Realisasi Layanan BK
     */
    public function storeLayanan()
    {
        $user       = session()->get('user');
        $activeYear = $this->getActiveYear();

        $counselorId = $this->request->getPost('counselor_id');
        if (empty($counselorId) && isset($user['id'])) {
            $teacher     = $this->teacherModel->where('user_id', $user['id'])->first();
            $counselorId = $teacher['id'] ?? null;
        }

        $academicYearId = $this->request->getPost('academic_year_id') ?: ($activeYear['id'] ?? null);
        $studentId      = $this->request->getPost('student_id') ?: null;
        $serviceType    = $this->request->getPost('service_type') ?: 'Bimbingan Klasikal';

        // Validasi service_type
        $allowedTypes = [
            'Bimbingan Klasikal', 'Bimbingan Kelompok', 'Konseling Individual',
            'Konseling Kelompok', 'Konsultasi', 'Rujukan',
            'Orientasi', 'Informasi', 'Penempatan', 'Kunjungan Rumah',
            'Mediasi', 'Advokasi', 'Layanan Lainnya',
        ];
        if (!in_array($serviceType, $allowedTypes)) {
            $serviceType = 'Layanan Lainnya';
        }

        $serviceData = [
            'rpl_id'            => $this->request->getPost('rpl_id') ?: null,
            'academic_year_id'  => $academicYearId,
            'service_type'      => $serviceType,
            'field'             => $this->request->getPost('field') ?: 'Pribadi',
            'counselor_id'      => $counselorId,
            'class_id'          => $this->request->getPost('class_id') ?: null,
            'student_id'        => $studentId,
            'service_date'      => $this->request->getPost('service_date') ?: date('Y-m-d'),
            'start_time'        => $this->request->getPost('start_time') ?: null,
            'end_time'          => $this->request->getPost('end_time') ?: null,
            'status'            => $this->request->getPost('status') ?: 'Pelaksanaan',
            'topic'             => $this->request->getPost('topic'),
            'purpose'           => $this->request->getPost('purpose') ?: null,
            'material'          => $this->request->getPost('material') ?: null,
            'needs_complaint'   => $this->request->getPost('needs_complaint') ?: null,
            'assessment_result' => $this->request->getPost('assessment_result') ?: null,
            'activity_summary'  => $this->request->getPost('activity_summary') ?: null,
            'session_notes'     => $this->request->getPost('session_notes') ?: null,
            'agreement'         => $this->request->getPost('agreement') ?: null,
            'evaluation_notes'  => $this->request->getPost('evaluation_notes') ?: null,
            'follow_up'         => $this->request->getPost('follow_up') ?: null,
        ];

        $serviceId = $this->serviceModel->insert($serviceData);

        if (!$serviceId) {
            return redirect()->to(base_url('admin/bk/layanan'))->with('error', 'Gagal menyimpan data layanan BK.');
        }

        // Simpan peserta layanan
        $studentIds         = $this->request->getPost('student_ids');
        $attendanceStatuses = $this->request->getPost('attendance_status') ?? [];
        $notesList          = $this->request->getPost('participant_notes') ?? [];

        if (empty($studentIds) && !empty($studentId)) {
            $studentIds = [$studentId];
        }

        // Klasikal: masukkan seluruh siswa aktif kelas tersebut
        if (empty($studentIds) && !empty($serviceData['class_id']) && $serviceData['service_type'] === 'Bimbingan Klasikal') {
            $classStudents = $this->getActiveStudentsQuery($academicYearId, $serviceData['class_id'])->get()->getResultArray();
            $studentIds    = array_column($classStudents, 'id');
        }

        if (!empty($studentIds) && is_array($studentIds)) {
            foreach ($studentIds as $sId) {
                $sId       = (int)$sId;
                $statusAtt = $attendanceStatuses[$sId] ?? 'Hadir';
                $notesAtt  = $notesList[$sId] ?? null;

                $this->db->table('bk_service_participants')->insert([
                    'service_id'        => $serviceId,
                    'student_id'        => $sId,
                    'attendance_status' => $statusAtt,
                    'notes'             => $notesAtt,
                ]);
            }
        }

        return redirect()->to(base_url('admin/bk/layanan'))->with('success', 'Data Layanan BK (' . $serviceData['service_type'] . ') berhasil dicatat.');
    }

    /**
     * Update Layanan BK
     */
    public function updateLayanan($id)
    {
        $id      = (int)$id;
        $service = $this->serviceModel->find($id);
        if (!$service) {
            return redirect()->to(base_url('admin/bk/layanan'))->with('error', 'Data Layanan BK tidak ditemukan.');
        }

        $counselorId = $this->request->getPost('counselor_id') ?: $service['counselor_id'];
        $studentId   = $this->request->getPost('student_id') ?: null;

        $serviceData = [
            'academic_year_id'  => $this->request->getPost('academic_year_id') ?: $service['academic_year_id'],
            'service_type'      => $this->request->getPost('service_type') ?: $service['service_type'],
            'field'             => $this->request->getPost('field') ?: $service['field'],
            'counselor_id'      => $counselorId,
            'class_id'          => $this->request->getPost('class_id') ?: null,
            'student_id'        => $studentId,
            'service_date'      => $this->request->getPost('service_date') ?: $service['service_date'],
            'start_time'        => $this->request->getPost('start_time') ?: null,
            'end_time'          => $this->request->getPost('end_time') ?: null,
            'status'            => $this->request->getPost('status') ?: $service['status'],
            'topic'             => $this->request->getPost('topic'),
            'purpose'           => $this->request->getPost('purpose') ?: null,
            'material'          => $this->request->getPost('material') ?: null,
            'needs_complaint'   => $this->request->getPost('needs_complaint') ?: null,
            'assessment_result' => $this->request->getPost('assessment_result') ?: null,
            'activity_summary'  => $this->request->getPost('activity_summary') ?: null,
            'session_notes'     => $this->request->getPost('session_notes') ?: null,
            'agreement'         => $this->request->getPost('agreement') ?: null,
            'evaluation_notes'  => $this->request->getPost('evaluation_notes') ?: null,
            'follow_up'         => $this->request->getPost('follow_up') ?: null,
        ];

        $this->serviceModel->update($id, $serviceData);

        $studentIds         = $this->request->getPost('student_ids');
        $attendanceStatuses = $this->request->getPost('attendance_status') ?? [];

        if (empty($studentIds) && !empty($studentId)) {
            $studentIds = [$studentId];
        }

        if (is_array($studentIds)) {
            $this->db->table('bk_service_participants')->where('service_id', $id)->delete();
            foreach ($studentIds as $sId) {
                $sId       = (int)$sId;
                $statusAtt = $attendanceStatuses[$sId] ?? 'Hadir';
                $this->db->table('bk_service_participants')->insert([
                    'service_id'        => $id,
                    'student_id'        => $sId,
                    'attendance_status' => $statusAtt,
                ]);
            }
        }

        return redirect()->to(base_url('admin/bk/layanan'))->with('success', 'Data Layanan BK berhasil diperbarui.');
    }

    /**
     * AJAX Detail Layanan BK
     */
    public function detailLayanan($id)
    {
        $id      = (int)$id;
        $service = $this->db->table('bk_services s')
            ->select('s.*, cl.name as class_name, t.name as counselor_name, ay.year as academic_year_name, st.name as primary_student_name, st.nisn as primary_student_nisn')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->join('teachers t', 't.id = s.counselor_id', 'left')
            ->join('academic_years ay', 'ay.id = s.academic_year_id', 'left')
            ->join('students st', 'st.id = s.student_id', 'left')
            ->where('s.id', $id)
            ->get()->getRowArray();

        if (!$service) {
            return $this->response->setJSON(['status' => false, 'message' => 'Layanan tidak ditemukan']);
        }

        $participants = $this->participantModel->getParticipantsWithDetails($id);

        return $this->response->setJSON([
            'status'       => true,
            'data'         => $service,
            'participants' => $participants,
        ]);
    }

    /**
     * Hapus Layanan BK
     */
    public function deleteLayanan($id)
    {
        $id      = (int)$id;
        $service = $this->serviceModel->find($id);
        if (!$service) {
            return redirect()->to(base_url('admin/bk/layanan'))->with('error', 'Data layanan tidak ditemukan.');
        }

        $this->db->table('bk_service_participants')->where('service_id', $id)->delete();
        $this->serviceModel->delete($id);

        return redirect()->to(base_url('admin/bk/layanan'))->with('success', 'Data Layanan BK berhasil dihapus.');
    }

    // =========================================================
    // E. Penanganan Kasus BP/BK
    // =========================================================
    public function kasus()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        $filters = [
            'search'     => $this->request->getGet('search'),
            'status'     => $this->request->getGet('status'),
            'severity'   => $this->request->getGet('severity'),
            'category'   => $this->request->getGet('category'),
            'class_id'   => $this->request->getGet('class_id'),
            'student_id' => $this->request->getGet('student_id'),
            'date'       => $this->request->getGet('date'),
        ];

        $isGuruBk = $this->isGuruBkOrAdmin();
        $cases    = $this->caseModel->getCasesWithDetails($filters, $isGuruBk);
        $stats    = $this->caseModel->getStatistics();

        // Selector: hanya siswa aktif di tahun berjalan
        $students = $this->getActiveStudentsQuery($activeYearId)->get()->getResultArray();

        $classes = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classes)) {
            $classes = $this->classModel->findAll();
        }

        $teachers = $this->teacherModel->where('is_active', 1)->findAll();
        if (empty($teachers)) {
            $teachers = $this->teacherModel->findAll();
        }

        return view('admin/bk/kasus', [
            'title'      => 'Penanganan Kasus & Rujukan BP/BK',
            'cases'      => $cases,
            'stats'      => $stats,
            'students'   => $students,
            'classes'    => $classes,
            'teachers'   => $teachers,
            'filters'    => $filters,
            'isGuruBk'   => $isGuruBk,
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Simpan Kasus BK Baru (Dengan Otomatisasi & Audit Log)
     */
    public function storeKasus()
    {
        $user = session()->get('user');

        // Generasi Kode Kasus Otomatis unik (e.g. KASUS-202609-001)
        $prefix   = 'KASUS-' . date('Ym') . '-';
        $lastCase = $this->db->table('bk_cases')
            ->like('case_code', $prefix)
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->get()->getRowArray();

        $seq = 1;
        if ($lastCase) {
            $parts = explode('-', $lastCase['case_code']);
            $seq   = (int)end($parts) + 1;
        }
        $caseCode = $prefix . sprintf('%03d', $seq);

        $status       = $this->request->getPost('status') ?: 'REPORTED';
        $allowedStatus = ['DRAFT','REPORTED','VERIFIED','IN_ASSESSMENT','IN_PROGRESS','MONITORING','REFERRED','RESOLVED','CLOSED'];
        if (!in_array($status, $allowedStatus)) {
            $status = 'REPORTED';
        }
        $workflowStep = $this->mapStatusToWorkflowStep($status);

        $studentId = (int)$this->request->getPost('student_id');
        if (!$studentId) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Siswa harus dipilih.');
        }

        $data = [
            'case_code'          => $caseCode,
            'student_id'         => $studentId,
            'reporter_type'      => $this->request->getPost('reporter_type') ?: 'Wali Kelas',
            'reporter_id'        => $this->request->getPost('reporter_id') ?: ($user['id'] ?? null),
            'related_parties'    => $this->request->getPost('related_parties') ?: null,
            'source'             => $this->request->getPost('source') ?: 'Rujukan Internal',
            'category'           => $this->request->getPost('category') ?: 'Kedisiplinan',
            'severity'           => $this->request->getPost('severity') ?: 'Ringan',
            'incident_date'      => $this->request->getPost('incident_date') ?: date('Y-m-d'),
            'description'        => $this->request->getPost('description'),
            'confidential_notes' => $this->isGuruBkOrAdmin() ? ($this->request->getPost('confidential_notes') ?: null) : null,
            'status'             => $status,
            'workflow_step'      => $workflowStep,
            'is_confidential'    => $this->request->getPost('is_confidential') ? 1 : 0,
        ];

        $caseId = $this->caseModel->insert($data);

        if (!$caseId) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Gagal menyimpan kasus.');
        }

        // Audit Log
        $this->caseLogModel->logStatusChange($caseId, null, $status, $user['id'] ?? null, 'Laporan Kasus Baru Dibuat');

        // Initial Timeline Action
        $this->caseActionModel->insert([
            'case_id'      => $caseId,
            'action_date'  => $data['incident_date'],
            'action_type'  => 'Laporan Kasus',
            'description'  => $data['description'],
            'result_notes' => 'Laporan Kasus Diterima',
            'next_step'    => 'Verifikasi & Asesmen Kasus',
            'performed_by' => $data['reporter_id'],
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Penanganan Kasus (' . $caseCode . ') berhasil dicatat.');
    }

    /**
     * Update Data Kasus BK
     */
    public function updateKasus($id)
    {
        $id   = (int)$id;
        $case = $this->findCaseOrFail($id);
        if (!$case) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Kasus tidak ditemukan.');
        }

        $allowedStatus = ['DRAFT','REPORTED','VERIFIED','IN_ASSESSMENT','IN_PROGRESS','MONITORING','REFERRED','RESOLVED','CLOSED'];
        $status        = $this->request->getPost('status') ?: $case['status'];
        if (!in_array($status, $allowedStatus)) {
            $status = $case['status'];
        }

        $updateData = [
            'reporter_type'      => $this->request->getPost('reporter_type') ?: $case['reporter_type'],
            'related_parties'    => $this->request->getPost('related_parties') ?: $case['related_parties'],
            'source'             => $this->request->getPost('source') ?: $case['source'],
            'category'           => $this->request->getPost('category') ?: $case['category'],
            'severity'           => $this->request->getPost('severity') ?: $case['severity'],
            'incident_date'      => $this->request->getPost('incident_date') ?: $case['incident_date'],
            'description'        => $this->request->getPost('description') ?: $case['description'],
            'status'             => $status,
            'workflow_step'      => $this->mapStatusToWorkflowStep($status),
            'is_confidential'    => $this->request->getPost('is_confidential') ? 1 : 0,
        ];

        if ($this->isGuruBkOrAdmin()) {
            $updateData['confidential_notes'] = $this->request->getPost('confidential_notes') ?: $case['confidential_notes'];
        }

        if (in_array($status, ['RESOLVED', 'CLOSED']) && empty($case['closed_at'])) {
            $updateData['closed_at']    = date('Y-m-d H:i:s');
            $updateData['closed_notes'] = $this->request->getPost('description') ?: null;
        }

        $this->caseModel->update($id, $updateData);

        // Log jika status berubah
        if ($status !== $case['status']) {
            $user = session()->get('user');
            $this->caseLogModel->logStatusChange($id, $case['status'], $status, $user['id'] ?? null, 'Data kasus diperbarui');
        }

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Data Kasus ' . $case['case_code'] . ' berhasil diperbarui.');
    }

    /**
     * Update Status Kasus & Transisi Audit Log
     */
    public function updateStatusKasus($id)
    {
        $id   = (int)$id;
        $case = $this->findCaseOrFail($id);
        if (!$case) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Kasus tidak ditemukan.');
        }

        $user      = session()->get('user');
        $oldStatus = $case['status'];
        $newStatus = $this->request->getPost('status');
        $notes     = $this->request->getPost('notes');

        $allowedStatus = ['DRAFT','REPORTED','VERIFIED','IN_ASSESSMENT','IN_PROGRESS','MONITORING','REFERRED','RESOLVED','CLOSED'];
        if (!$newStatus || !in_array($newStatus, $allowedStatus)) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Status tidak valid.');
        }

        if ($newStatus !== $oldStatus) {
            $workflowStep = $this->mapStatusToWorkflowStep($newStatus);
            $updateData   = [
                'status'        => $newStatus,
                'workflow_step' => $workflowStep,
            ];

            if (in_array($newStatus, ['RESOLVED', 'CLOSED'])) {
                $updateData['closed_at'] = date('Y-m-d H:i:s');
                if (!empty($notes)) {
                    $updateData['closed_notes'] = $notes;
                }
            }

            $this->caseModel->update($id, $updateData);

            // Audit Log
            $this->caseLogModel->logStatusChange($id, $oldStatus, $newStatus, $user['id'] ?? null, $notes);

            // Timeline action otomatis
            $this->caseActionModel->insert([
                'case_id'      => $id,
                'action_date'  => date('Y-m-d'),
                'action_type'  => 'Perubahan Status',
                'description'  => 'Status Kasus Diubah dari ' . $oldStatus . ' menjadi ' . $newStatus,
                'result_notes' => $notes ?: 'Transisi status workflow penanganan kasus',
                'next_step'    => 'Tahap Penanganan: ' . $workflowStep,
                'performed_by' => $user['id'] ?? null,
                'created_at'   => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Status Kasus ' . $case['case_code'] . ' berhasil diperbarui ke ' . $newStatus);
    }

    /**
     * Tambah Catatan Tindakan (Timeline Kronologis)
     */
    public function addActionKasus($id)
    {
        $id   = (int)$id;
        $case = $this->findCaseOrFail($id);
        if (!$case) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Kasus tidak ditemukan.');
        }

        $user = session()->get('user');

        $this->caseActionModel->insert([
            'case_id'      => $id,
            'action_date'  => $this->request->getPost('action_date') ?: date('Y-m-d'),
            'action_type'  => $this->request->getPost('action_type') ?: 'Konseling Individual',
            'description'  => $this->request->getPost('description'),
            'result_notes' => $this->request->getPost('result_notes'),
            'next_step'    => $this->request->getPost('next_step'),
            'performed_by' => $this->request->getPost('performed_by') ?: ($user['id'] ?? null),
            'created_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Tindakan & kronologi penanganan kasus berhasil ditambahkan.');
    }

    /**
     * Tambah Rujukan Eksternal
     */
    public function addReferralKasus($id)
    {
        $id   = (int)$id;
        $case = $this->findCaseOrFail($id);
        if (!$case) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Kasus tidak ditemukan.');
        }

        $user = session()->get('user');
        $this->caseReferralModel->insert([
            'case_id'                 => $id,
            'student_id'              => $case['student_id'],
            'referral_target'         => $this->request->getPost('referral_target') ?: 'Psikolog',
            'target_name'             => $this->request->getPost('target_name'),
            'referral_date'           => $this->request->getPost('referral_date') ?: date('Y-m-d'),
            'reason'                  => $this->request->getPost('reason'),
            'recommendation_received' => $this->request->getPost('recommendation_received'),
            'status'                  => $this->request->getPost('status') ?: 'Proses',
        ]);

        $oldStatus = $case['status'];
        $this->caseModel->update($id, [
            'status'        => 'REFERRED',
            'workflow_step' => 'Selesai / Rujukan',
        ]);

        $this->caseLogModel->logStatusChange($id, $oldStatus, 'REFERRED', $user['id'] ?? null, 'Kasus Dirujuk ke Pihak Eksternal (' . $this->request->getPost('target_name') . ')');

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Rujukan Eksternal berhasil dicatat.');
    }

    /**
     * AJAX Detail Lengkap Kasus, Timeline & Audit Log
     */
    public function detailKasus($id)
    {
        $id       = (int)$id;
        $isGuruBk = $this->isGuruBkOrAdmin();

        $case = $this->db->table('bk_cases c')
            ->select('c.*, s.name as student_name, s.nisn, s.gender, cl.name as class_name, t.name as reporter_name')
            ->join('students s', 's.id = c.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('teachers t', 't.id = c.reporter_id', 'left')
            ->where('c.id', $id)
            ->get()->getRowArray();

        if (!$case) {
            return $this->response->setJSON(['status' => false, 'message' => 'Kasus tidak ditemukan']);
        }

        // Sensor catatan rahasia jika bukan Guru BK/Admin
        if (!$isGuruBk && $case['is_confidential'] == 1) {
            $case['confidential_notes'] = '🔒 [CATATAN RAHASIA GURU BK - Akses Terbatas Khusus Konselor]';
        }

        $actions   = $this->caseActionModel->getActionsByCase($id);
        $referrals = $this->caseReferralModel->getReferralsByCase($id);
        $logs      = $this->caseLogModel->getLogsByCase($id);

        return $this->response->setJSON([
            'status'    => true,
            'isGuruBk'  => $isGuruBk,
            'data'      => $case,
            'actions'   => $actions,
            'referrals' => $referrals,
            'logs'      => $logs,
        ]);
    }

    /**
     * Hapus Kasus BK
     */
    public function deleteKasus($id)
    {
        $id   = (int)$id;
        $case = $this->findCaseOrFail($id);
        if (!$case) {
            return redirect()->to(base_url('admin/bk/kasus'))->with('error', 'Kasus tidak ditemukan.');
        }

        $this->db->table('bk_case_actions')->where('case_id', $id)->delete();
        $this->db->table('bk_case_referrals')->where('case_id', $id)->delete();
        $this->db->table('bk_case_logs')->where('case_id', $id)->delete();
        $this->caseModel->delete($id);

        return redirect()->to(base_url('admin/bk/kasus'))->with('success', 'Data Kasus berhasil dihapus.');
    }

    // =========================================================
    // F. Kolaborasi
    // =========================================================
    public function kolaborasi()
    {
        $referrals = $this->db->table('bk_cases c')
            ->select('c.*, s.name as student_name, s.nisn, cl.name as class_name, t.name as reporter_name')
            ->join('students s', 's.id = c.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('teachers t', 't.id = c.reporter_id', 'left')
            ->whereIn('c.source', ['Rujukan Internal', 'Pengaduan'])
            ->orderBy('c.created_at', 'DESC')
            ->get()->getResultArray();

        return view('admin/bk/kolaborasi', [
            'title'     => 'Kolaborasi Guru BK, Wali Kelas & Kepsek',
            'referrals' => $referrals,
        ]);
    }

    // =========================================================
    // G. Perencanaan Individual Siswa
    // =========================================================
    public function perencanaanIndividual()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        $plans = $this->db->table('bk_individual_plans ip')
            ->select('ip.*, s.name as student_name, s.nisn, s.gender, cl.name as class_name')
            ->join('students s', 's.id = ip.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'inner')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->orderBy('cl.name', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Siswa aktif untuk selector
        $students = $this->getActiveStudentsQuery($activeYearId)->get()->getResultArray();

        return view('admin/bk/individual_planning', [
            'title'      => 'Perencanaan Individual Siswa',
            'plans'      => $plans,
            'students'   => $students,
            'activeYear' => $activeYear,
        ]);
    }

    public function storeIndividualPlan()
    {
        $activeYear = $this->getActiveYear();

        // Cek apakah siswa sudah punya rencana di tahun ajaran ini
        $existing = $this->planModel
            ->where('student_id', $this->request->getPost('student_id'))
            ->where('academic_year_id', $activeYear['id'] ?? 1)
            ->first();

        if ($existing) {
            return redirect()->to('admin/bk/perencanaan-individual')
                ->with('error', 'Siswa ini sudah memiliki rencana individual di tahun ajaran berjalan. Silakan edit yang sudah ada.');
        }

        $data = [
            'student_id'       => $this->request->getPost('student_id'),
            'academic_year_id' => $activeYear['id'] ?? null,
            'strengths'        => $this->request->getPost('strengths'),
            'growth_areas'     => $this->request->getPost('growth_areas'),
            'interests'        => $this->request->getPost('interests'),
            'learning_style'   => $this->request->getPost('learning_style'),
            'career_target'    => $this->request->getPost('career_target'),
            'academic_target'  => $this->request->getPost('academic_target'),
            'habit_target'     => $this->request->getPost('habit_target'),
            'progress_percent' => 0,
            'counselor_notes'  => $this->request->getPost('counselor_notes'),
        ];

        $this->planModel->insert($data);
        return redirect()->to('admin/bk/perencanaan-individual')->with('success', 'Target individual siswa berhasil dibuat.');
    }

    public function updateIndividualPlan($id)
    {
        $data = [
            'strengths'       => $this->request->getPost('strengths'),
            'growth_areas'    => $this->request->getPost('growth_areas'),
            'interests'       => $this->request->getPost('interests'),
            'learning_style'  => $this->request->getPost('learning_style'),
            'career_target'   => $this->request->getPost('career_target'),
            'academic_target' => $this->request->getPost('academic_target'),
            'habit_target'    => $this->request->getPost('habit_target'),
            'counselor_notes' => $this->request->getPost('counselor_notes'),
        ];
        $this->planModel->update($id, $data);
        return redirect()->to('admin/bk/perencanaan-individual')->with('success', 'Target individual berhasil diperbarui.');
    }

    public function deleteIndividualPlan($id)
    {
        $this->planModel->delete($id);
        return redirect()->to('admin/bk/perencanaan-individual')->with('success', 'Target individual berhasil dihapus.');
    }

    public function updateIndividualProgress($id)
    {
        $progress = min(100, max(0, (int)$this->request->getPost('progress_percent')));
        $this->planModel->update($id, ['progress_percent' => $progress]);
        return redirect()->to('admin/bk/perencanaan-individual')->with('success', 'Progress berhasil diperbarui.');
    }

    // =========================================================
    // H. Laporan BK
    // =========================================================
    public function laporan()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        $academicYears = $this->academicYearModel->orderBy('start_date', 'DESC')->findAll();

        $filterYearId = $this->request->getGet('academic_year_id') ?: $activeYearId;

        // Rekapitulasi layanan per tipe
        $serviceStats = $this->db->table('bk_services')
            ->select('service_type, COUNT(*) as total')
            ->where('academic_year_id', $filterYearId)
            ->groupBy('service_type')
            ->get()->getResultArray();

        // Rekapitulasi kasus per status
        $caseStats = $this->db->table('bk_cases c')
            ->select('c.status, COUNT(*) as total')
            ->join('students s', 's.id = c.student_id', 'left')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$filterYearId, 'inner')
            ->groupBy('c.status')
            ->get()->getResultArray();

        return view('admin/bk/laporan', [
            'title'        => 'Laporan BK',
            'activeYear'   => $activeYear,
            'academicYears' => $academicYears,
            'filterYearId' => $filterYearId,
            'serviceStats' => $serviceStats,
            'caseStats'    => $caseStats,
        ]);
    }

    // =========================================================
    // I. Daftar Profil Siswa BK (Integrasi Data Existing ESPATA)
    // =========================================================
    public function siswaIndex()
    {
        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        $search          = $this->request->getGet('search');
        $classId         = $this->request->getGet('class_id');
        $filterYearId    = $this->request->getGet('academic_year_id') ?: $activeYearId;

        $builder = $this->getActiveStudentsQuery($filterYearId, $classId);

        if (!empty($search)) {
            $builder->groupStart()
                ->like('s.name', $search)
                ->orLike('s.nisn', $search)
                ->orLike('cl.name', $search)
                ->groupEnd();
        }

        $students = $builder->get()->getResultArray();

        // Tambahkan indikator ringkas untuk setiap siswa
        foreach ($students as &$student) {
            $sid = $student['id'];

            // Jumlah alpa (cepat)
            $student['total_alpa'] = $this->db->table('attendances')
                ->where('student_id', $sid)
                ->where('status', 'Alpa')
                ->countAllResults();

            // Kasus aktif
            $student['active_cases'] = $this->db->table('bk_cases')
                ->where('student_id', $sid)
                ->whereIn('status', ['REPORTED', 'VERIFIED', 'IN_ASSESSMENT', 'IN_PROGRESS', 'MONITORING'])
                ->countAllResults();

            // Layanan BK diterima
            $student['total_services'] = $this->db->table('bk_service_participants bsp')
                ->join('bk_services bs', 'bs.id = bsp.service_id', 'inner')
                ->where('bsp.student_id', $sid)
                ->where('bs.academic_year_id', $filterYearId)
                ->countAllResults();
        }
        unset($student);

        $classes = $this->classModel->where('is_active', 1)->findAll();
        if (empty($classes)) {
            $classes = $this->classModel->findAll();
        }

        $academicYears = $this->academicYearModel->orderBy('start_date', 'DESC')->findAll();

        return view('admin/bk/siswa_index', [
            'title'         => 'Profil & Pemantauan Siswa BK',
            'students'      => $students,
            'classes'       => $classes,
            'academicYears' => $academicYears,
            'activeYear'    => $activeYear,
            'filterYearId'  => $filterYearId,
            'search'        => $search,
            'classId'       => $classId,
        ]);
    }

    // =========================================================
    // J. Halaman Profil Komprehensif Siswa BK
    //    Indikator Early Warning "Perlu Perhatian"
    // =========================================================
    public function siswaProfil($id)
    {
        $id       = (int)$id;
        $isGuruBk = $this->isGuruBkOrAdmin();

        $activeYear   = $this->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;

        // 1. Identitas, Rombel, Wali Kelas (melalui student_records aktif)
        $student = $this->db->table('students s')
            ->select('s.*, sr.academic_year_id, sr.status as record_status,
                      cl.id as class_id, cl.name as class_name, cl.level as class_level,
                      t.name as wali_kelas_name, t.phone as wali_kelas_phone, t.email as wali_kelas_email')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)$activeYearId, 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('teachers t', 't.id = cl.teacher_id', 'left')
            ->where('s.id', $id)
            ->get()->getRowArray();

        if (!$student) {
            return redirect()->to(base_url('admin/bk/siswa'))->with('error', 'Data siswa tidak ditemukan.');
        }

        // 2. Kehadiran / Absensi (READ-ONLY)
        $attendanceSummary = [
            'hadir' => $this->db->table('attendances')->where('student_id', $id)->where('status', 'Hadir')->countAllResults(),
            'sakit' => $this->db->table('attendances')->where('student_id', $id)->where('status', 'Sakit')->countAllResults(),
            'izin'  => $this->db->table('attendances')->where('student_id', $id)->where('status', 'Izin')->countAllResults(),
            'alpa'  => $this->db->table('attendances')->where('student_id', $id)->where('status', 'Alpa')->countAllResults(),
        ];
        $totalAtt             = array_sum($attendanceSummary);
        $attendancePercentage = $totalAtt > 0 ? round(($attendanceSummary['hadir'] / $totalAtt) * 100, 1) : 100;

        // 3. Ringkasan Akademik (READ-ONLY)
        $academicScores = $this->db->table('subject_scores ss')
            ->select('ss.*, sub.name as subject_name, sub.code as subject_code')
            ->join('subjects sub', 'sub.id = ss.subject_id', 'left')
            ->where('ss.student_id', $id)
            ->get()->getResultArray();

        $avgScore = 0;
        if (!empty($academicScores)) {
            $scoresList = array_filter(array_column($academicScores, 'report_score'), function ($v) { return $v > 0; });
            if (!empty($scoresList)) {
                $avgScore = round(array_sum($scoresList) / count($scoresList), 1);
            }
        }

        // 4. Catatan Observasi Perilaku Guru (READ-ONLY)
        $studentNotes = $this->db->table('student_notes sn')
            ->select('sn.*, t.name as teacher_name')
            ->join('teachers t', 't.id = sn.teacher_id', 'left')
            ->where('sn.student_id', $id)
            ->orderBy('sn.created_at', 'DESC')
            ->get()->getResultArray();

        // 5. Riwayat Layanan BK (melalui peserta atau sebagai siswa utama)
        $services = $this->db->table('bk_services s')
            ->select('s.*, cl.name as class_name, t.name as counselor_name')
            ->join('classes cl', 'cl.id = s.class_id', 'left')
            ->join('teachers t', 't.id = s.counselor_id', 'left')
            ->groupStart()
                ->where('s.student_id', $id)
                ->orWhere("s.id IN (SELECT service_id FROM bk_service_participants WHERE student_id = {$id})")
            ->groupEnd()
            ->orderBy('s.service_date', 'DESC')
            ->get()->getResultArray();

        // 6. Kasus Aktif & Riwayat Kasus
        $cases = $this->caseModel->getCasesWithDetails(['student_id' => $id], $isGuruBk);

        // 7. Perencanaan Individual & Tindak Lanjut
        $individualPlan = $this->planModel->where('student_id', $id)->first();

        // 8. Indikator Deteksi Sinyal "Perlu Perhatian" (Tidak memberi label diagnostik)
        $signals = [];

        if ($attendanceSummary['alpa'] >= 2 || $attendancePercentage < 85) {
            $signals[] = [
                'type'        => 'danger',
                'label'       => 'Perlu Perhatian (Kehadiran)',
                'description' => 'Terdeteksi penurunan persentase kehadiran (' . $attendancePercentage . '%) dan akumulasi ' . $attendanceSummary['alpa'] . ' hari Alpa.',
                'link'        => base_url('admin/attendance?student_id=' . $id),
            ];
        }

        if ($avgScore > 0 && $avgScore < 75) {
            $signals[] = [
                'type'        => 'warning',
                'label'       => 'Perlu Monitoring (Akademik)',
                'description' => 'Terdeteksi fluktuasi/perubahan rata-rata capaian akademik (' . $avgScore . ').',
                'link'        => base_url('admin/scores?student_id=' . $id),
            ];
        }

        if (!empty($studentNotes)) {
            $signals[] = [
                'type'        => 'info',
                'label'       => 'Perlu Verifikasi (Catatan Perilaku)',
                'description' => 'Terdapat ' . count($studentNotes) . ' entri catatan observasi perilaku dari guru mapel / wali kelas.',
                'link'        => base_url('admin/student-notes?student_id=' . $id),
            ];
        }

        $activeCasesCount = 0;
        foreach ($cases as $c) {
            if (in_array($c['status'], ['REPORTED', 'VERIFIED', 'IN_ASSESSMENT', 'IN_PROGRESS', 'MONITORING', 'REFERRED'])) {
                $activeCasesCount++;
            }
        }

        if ($activeCasesCount > 0) {
            $signals[] = [
                'type'        => 'danger',
                'label'       => 'Perlu Tindak Lanjut (Penanganan BK)',
                'description' => 'Siswa memiliki ' . $activeCasesCount . ' penanganan kasus aktif yang sedang berjalan dalam alur BK.',
                'link'        => base_url('admin/bk/kasus?student_id=' . $id),
            ];
        }

        return view('admin/bk/siswa_profil', [
            'title'                => 'Profil Komprehensif BK - ' . $student['name'],
            'student'              => $student,
            'activeYear'           => $activeYear,
            'attendanceSummary'    => $attendanceSummary,
            'attendancePercentage' => $attendancePercentage,
            'academicScores'       => $academicScores,
            'avgScore'             => $avgScore,
            'studentNotes'         => $studentNotes,
            'services'             => $services,
            'cases'                => $cases,
            'activeCasesCount'     => $activeCasesCount,
            'individualPlan'       => $individualPlan,
            'signals'              => $signals,
            'isGuruBk'             => $isGuruBk,
        ]);
    }
}
