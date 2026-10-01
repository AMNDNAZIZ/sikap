<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\ClassModel;
use App\Models\TeacherModel;
use App\Models\SchoolModel;
use Config\Database;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * BkLaporan — Laporan & Rekapitulasi Bimbingan Konseling
 *
 * Laporan 1: Rekap Layanan BK       (bk/laporan/layanan)
 * Laporan 2: Penanganan Kasus        (bk/laporan/kasus)
 * Laporan 3: Eksekutif Kepala Sekolah(bk/laporan/eksekutif)
 *
 * Pola: filter (GET) → preview HTML (POST) → cetak PDF (POST).
 * Semua query menghindari field sensitif (confidential_notes, session_notes,
 * assessment_result, needs_complaint, dll.).
 * Authorization dilakukan di level route (auth filter) DAN di controller ini.
 */
class BkLaporan extends BaseController
{
    protected $db;
    protected $academicYearModel;
    protected $classModel;
    protected $teacherModel;
    protected $schoolModel;

    public function __construct()
    {
        $this->db               = Database::connect();
        $this->academicYearModel= new AcademicYearModel();
        $this->classModel       = new ClassModel();
        $this->teacherModel     = new TeacherModel();
        $this->schoolModel      = new SchoolModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // HELPER — ambil user login, tahun ajaran aktif, school profile
    // ─────────────────────────────────────────────────────────────────────────

    private function currentUser(): array  { return session()->get('user') ?? []; }
    private function currentRoleId(): int  { return (int)($this->currentUser()['role_id'] ?? 0); }

    /** Hanya Admin (1), Kepsek (2), Guru (3) boleh cetak laporan BK. */
    private function canAccessBkReport(): bool
    {
        // Cek via tabel permission (dinamis) — helper has_permission() membaca role_permissions.
        // Fallback ke role_id bila helper belum tersedia.
        if (function_exists('has_permission')) {
            return has_permission('bk.report') || has_permission('bk.manage');
        }
        return in_array($this->currentRoleId(), [1, 2, 3], true);
    }

    /** Laporan Eksekutif: perlu permission bk.report_executive. */
    private function canAccessEksekutif(): bool
    {
        if (function_exists('has_permission')) {
            return has_permission('bk.report_executive');
        }
        return in_array($this->currentRoleId(), [1, 2, 3], true);
    }

    private function denyJson(): \CodeIgniter\HTTP\Response
    {
        return $this->response->setStatusCode(403)
            ->setJSON(['error' => 'Akses ditolak.']);
    }

    private function denyRedirect(): \CodeIgniter\HTTP\RedirectResponse
    {
        return redirect()->to('admin/bk/laporan')->with('error', 'Anda tidak memiliki akses ke laporan ini.');
    }

    private function getActiveYear(): array
    {
        return $this->academicYearModel->getActiveYear() ?? [];
    }

    /** Dropdown data untuk semua filter form. */
    private function getFilterDropdowns(): array
    {
        return [
            'academicYears' => $this->academicYearModel->orderBy('start_date', 'DESC')->findAll(),
            'classes'       => $this->classModel->where('is_active', 1)->orderBy('level')->orderBy('name')->findAll(),
            'counselors'    => $this->db->table('teachers')->select('id, name')->orderBy('name')->get()->getResultArray(),
        ];
    }

    /** Ambil logo sekolah sebagai base64 (agar tampil di Dompdf tanpa remote). */
    private function getLogoBase64(): string
    {
        $school = $this->schoolModel->getProfile();
        if (empty($school['logo'])) return '';
        $path = FCPATH . 'uploads/' . ltrim($school['logo'], '/');
        if (!is_file($path)) {
            $path = FCPATH . ltrim($school['logo'], '/');
        }
        if (!is_file($path)) return '';
        $mime = mime_content_type($path) ?: 'image/png';
        return 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($path));
    }

    /** Stream PDF ke browser. */
    private function streamPdf(string $html, string $filename, string $orientation = 'portrait'): void
    {
        $options = new Options();
        $options->set('isRemoteEnabled', false); // logo via base64, tidak perlu remote
        $options->set('isHtml5ParserEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        if (ob_get_length()) { @ob_end_clean(); }
        $dompdf->stream($filename, ['Attachment' => false]);
        exit;
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LAPORAN 1 — REKAP LAYANAN BK
    // ═════════════════════════════════════════════════════════════════════════

    /** GET  bk/laporan/layanan — halaman filter */
    public function filterLayanan()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $activeYear  = $this->getActiveYear();
        $dd          = $this->getFilterDropdowns();
        $serviceTypes = [
            'Bimbingan Klasikal','Bimbingan Kelompok','Konseling Individual',
            'Konseling Kelompok','Konsultasi','Rujukan','Home Visit',
            'Orientasi','Informasi','Penempatan','Lainnya',
        ];

        return view('admin/bk/laporan/layanan_filter', array_merge($dd, [
            'title'        => 'Filter Laporan Rekap Layanan BK',
            'activeYear'   => $activeYear,
            'serviceTypes' => $serviceTypes,
        ]));
    }

    /**
     * Query data layanan sesuai filter.
     * Kolom sensitif TIDAK diambil: session_notes, needs_complaint,
     * assessment_result, agreement, evaluation_notes.
     */
    private function queryLayanan(array $f): array
    {
        $yearId    = (int)($f['academic_year_id'] ?? 0);
        $classId   = (int)($f['class_id']   ?? 0);
        $counselorId = (int)($f['counselor_id'] ?? 0);
        $svcType   = $f['service_type'] ?? '';
        $status    = $f['status']       ?? '';
        $dateFrom  = $f['date_from']    ?? '';
        $dateTo    = $f['date_to']      ?? '';
        $semester  = (int)($f['semester'] ?? 0);

        $q = $this->db->table('bk_services s')
            ->select('s.id, s.service_date, s.service_type, s.field, s.topic,
                      s.status, s.start_time, s.end_time, s.material,
                      s.follow_up,
                      cl.name   AS class_name,
                      t.name    AS counselor_name,
                      ay.year   AS academic_year,
                      (SELECT COUNT(*) FROM bk_service_participants sp
                       WHERE sp.service_id = s.id
                         AND sp.attendance_status = \'Hadir\') AS jumlah_peserta')
            ->join('classes   cl','cl.id = s.class_id',      'left')
            ->join('teachers  t', 't.id  = s.counselor_id',  'left')
            ->join('academic_years ay','ay.id = s.academic_year_id','left');

        // ── Filter ──────────────────────────────────────────────────────────
        if ($yearId)      $q->where('s.academic_year_id', $yearId);
        if ($classId)     $q->where('s.class_id', $classId);
        if ($counselorId) $q->where('s.counselor_id', $counselorId);
        if ($svcType)     $q->where('s.service_type', $svcType);
        if ($status)      $q->where('s.status', $status);
        if ($dateFrom)    $q->where('s.service_date >=', $dateFrom);
        if ($dateTo)      $q->where('s.service_date <=', $dateTo);

        // Semester: Ganjil = Jul-Des, Genap = Jan-Jun (simpel)
        if ($semester === 1) {
            $q->groupStart()
                ->where('MONTH(s.service_date) >=', 7)
                ->where('MONTH(s.service_date) <=', 12)
              ->groupEnd();
        } elseif ($semester === 2) {
            $q->groupStart()
                ->where('MONTH(s.service_date) >=', 1)
                ->where('MONTH(s.service_date) <=', 6)
              ->groupEnd();
        }

        return $q->orderBy('s.service_date','ASC')->get()->getResultArray();
    }

    /** Rekap agregasi dari daftar layanan. */
    private function rekapLayanan(array $rows): array
    {
        $byType  = [];
        $byField = [];
        $byClass = [];

        foreach ($rows as $r) {
            $byType[$r['service_type']]  = ($byType[$r['service_type']]  ?? 0) + 1;
            $byField[$r['field']]         = ($byField[$r['field']]         ?? 0) + 1;
            $kelas = $r['class_name'] ?: '(Individu)';
            $byClass[$kelas]              = ($byClass[$kelas]              ?? 0) + 1;
        }

        arsort($byType);
        arsort($byField);
        arsort($byClass);

        return compact('byType', 'byField', 'byClass');
    }

    /** POST  bk/laporan/layanan/preview */
    public function previewLayanan()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $f     = $this->request->getPost();
        $rows  = $this->queryLayanan($f);
        $rekap = $this->rekapLayanan($rows);
        $dd    = $this->getFilterDropdowns();
        $school= $this->schoolModel->getProfile();
        $year  = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();

        return view('admin/bk/laporan/preview_layanan', [
            'title'    => 'Preview Laporan Rekap Layanan BK',
            'rows'     => $rows,
            'rekap'    => $rekap,
            'filter'   => $f,
            'school'   => $school,
            'year'     => $year,
            'classes'  => $dd['classes'],
            'counselors'=> $dd['counselors'],
            'academicYears' => $dd['academicYears'],
        ]);
    }

    /** POST  bk/laporan/layanan/pdf */
    public function pdfLayanan()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $f       = $this->request->getPost();
        $rows    = $this->queryLayanan($f);

        if (empty($rows)) {
            return redirect()->back()->with('error',
                'Tidak ditemukan data layanan BK pada periode/filter yang dipilih.');
        }

        $rekap   = $this->rekapLayanan($rows);
        $school  = $this->schoolModel->getProfile();
        $logoB64 = $this->getLogoBase64();
        $year    = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();
        $user    = $this->currentUser();

        $html = view('admin/bk/laporan/pdf_layanan', [
            'rows'    => $rows,
            'rekap'   => $rekap,
            'filter'  => $f,
            'school'  => $school,
            'logoB64' => $logoB64,
            'year'    => $year,
            'user'    => $user,
            'printAt' => date('d/m/Y H:i'),
        ]);

        $semester  = ($f['semester'] ?? '');
        $semLabel  = $semester === '1' ? 'Ganjil' : ($semester === '2' ? 'Genap' : 'All');
        $filename  = 'Laporan_Rekap_Layanan_BK_Sem' . $semLabel . '_' . date('Ymd') . '.pdf';
        $landscape = count($rows) > 0; // detail layanan butuh landscape

        $this->streamPdf($html, $filename, 'landscape');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LAPORAN 2 — PENANGANAN KASUS BK
    // ═════════════════════════════════════════════════════════════════════════

    /** GET  bk/laporan/kasus — halaman filter */
    public function filterKasus()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $activeYear  = $this->getActiveYear();
        $dd          = $this->getFilterDropdowns();
        $categories  = ['Kedisiplinan','Bullying','Akademik/Belajar','Gadget/Game',
                        'Sosialisasi','Keluarga','Perilaku/Emosi','Lainnya'];
        $severities  = ['Ringan','Sedang','Berat'];
        $statuses    = ['DRAFT','REPORTED','VERIFIED','IN_ASSESSMENT','IN_PROGRESS',
                        'MONITORING','REFERRED','RESOLVED','CLOSED'];

        return view('admin/bk/laporan/kasus_filter', array_merge($dd, [
            'title'       => 'Filter Laporan Penanganan Kasus BK',
            'activeYear'  => $activeYear,
            'categories'  => $categories,
            'severities'  => $severities,
            'caseStatuses'=> $statuses,
        ]));
    }

    /**
     * Query data kasus. PRIVASI: tidak mengambil confidential_notes, description,
     * related_parties. Hanya data rekapitulasi yang diperlukan laporan.
     */
    private function queryKasus(array $f): array
    {
        $yearId    = (int)($f['academic_year_id'] ?? 0);
        $classId   = (int)($f['class_id']   ?? 0);
        $category  = $f['category']  ?? '';
        $severity  = $f['severity']  ?? '';
        $status    = $f['status']    ?? '';
        $dateFrom  = $f['date_from'] ?? '';
        $dateTo    = $f['date_to']   ?? '';
        $counselorId = (int)($f['counselor_id'] ?? 0);

        // Subquery jumlah tindak lanjut per kasus
        $q = $this->db->table('bk_cases c')
            ->select('c.id, c.case_code, c.category, c.severity, c.status,
                      c.incident_date, c.created_at, c.closed_at, c.source,
                      c.is_confidential,
                      s.name       AS student_name,
                      s.nis        AS student_nis,
                      cl.name      AS class_name,
                      (SELECT COUNT(*) FROM bk_case_actions ca WHERE ca.case_id = c.id) AS total_actions,
                      (SELECT COUNT(*) FROM bk_case_referrals cr WHERE cr.case_id = c.id) AS total_referrals')
            ->join('students s', 's.id = c.student_id', 'left')
            ->join('student_records sr',
                'sr.student_id = s.id AND sr.status = \'aktif\'', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left');

        // Filter tahun ajaran via tanggal kasus dibuat
        if ($yearId) {
            $ay = $this->academicYearModel->find($yearId);
            if ($ay) {
                $q->where('c.created_at >=', $ay['start_date'] . ' 00:00:00')
                  ->where('c.created_at <=', $ay['end_date']   . ' 23:59:59');
            }
        }
        if ($classId)    $q->where('sr.class_id', $classId);
        if ($category)   $q->where('c.category', $category);
        if ($severity)   $q->where('c.severity', $severity);
        if ($status)     $q->where('c.status', $status);
        if ($dateFrom)   $q->where('c.incident_date >=', $dateFrom);
        if ($dateTo)     $q->where('c.incident_date <=', $dateTo);

        return $q->groupBy('c.id')->orderBy('c.created_at','DESC')->get()->getResultArray();
    }

    /** Rekap agregasi kasus. */
    private function rekapKasus(array $rows): array
    {
        $byCategory = [];
        $byStatus   = [];
        $bySeverity = [];
        $totalActive = $totalProses = $totalMonitor = $totalSelesai = $totalReferred = 0;

        foreach ($rows as $r) {
            $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + 1;
            $byStatus[$r['status']]     = ($byStatus[$r['status']]     ?? 0) + 1;
            $bySeverity[$r['severity']] = ($bySeverity[$r['severity']] ?? 0) + 1;

            match($r['status']) {
                'REPORTED','VERIFIED','IN_ASSESSMENT' => $totalActive++,
                'IN_PROGRESS'                          => $totalProses++,
                'MONITORING'                           => $totalMonitor++,
                'RESOLVED','CLOSED'                    => $totalSelesai++,
                'REFERRED'                             => $totalReferred++,
                default                                => null,
            };
        }
        return compact('byCategory','byStatus','bySeverity',
                       'totalActive','totalProses','totalMonitor','totalSelesai','totalReferred');
    }

    /** POST  bk/laporan/kasus/preview */
    public function previewKasus()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $f     = $this->request->getPost();
        $rows  = $this->queryKasus($f);
        $rekap = $this->rekapKasus($rows);
        $dd    = $this->getFilterDropdowns();
        $school= $this->schoolModel->getProfile();
        $year  = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();

        return view('admin/bk/laporan/preview_kasus', [
            'title'       => 'Preview Laporan Penanganan Kasus BK',
            'rows'        => $rows,
            'rekap'       => $rekap,
            'filter'      => $f,
            'school'      => $school,
            'year'        => $year,
            'classes'     => $dd['classes'],
            'counselors'  => $dd['counselors'],
            'academicYears'=> $dd['academicYears'],
        ]);
    }

    /** POST  bk/laporan/kasus/pdf */
    public function pdfKasus()
    {
        if (!$this->canAccessBkReport()) return $this->denyRedirect();

        $f    = $this->request->getPost();
        $rows = $this->queryKasus($f);

        if (empty($rows)) {
            return redirect()->back()->with('error',
                'Tidak ditemukan data kasus BK pada periode/filter yang dipilih.');
        }

        $rekap   = $this->rekapKasus($rows);
        $school  = $this->schoolModel->getProfile();
        $logoB64 = $this->getLogoBase64();
        $year    = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();
        $user    = $this->currentUser();

        $html = view('admin/bk/laporan/pdf_kasus', [
            'rows'    => $rows,
            'rekap'   => $rekap,
            'filter'  => $f,
            'school'  => $school,
            'logoB64' => $logoB64,
            'year'    => $year,
            'user'    => $user,
            'printAt' => date('d/m/Y H:i'),
        ]);

        $filename = 'Laporan_Penanganan_Kasus_BK_' . date('Ymd') . '.pdf';
        $this->streamPdf($html, $filename, 'portrait');
    }

    // ═════════════════════════════════════════════════════════════════════════
    // LAPORAN 3 — EKSEKUTIF KEPALA SEKOLAH
    // ═════════════════════════════════════════════════════════════════════════

    /** GET  bk/laporan/eksekutif — halaman filter */
    public function filterEksekutif()
    {
        if (!$this->canAccessEksekutif()) return $this->denyRedirect();

        $activeYear = $this->getActiveYear();
        $dd         = $this->getFilterDropdowns();

        return view('admin/bk/laporan/eksekutif_filter', array_merge($dd, [
            'title'      => 'Filter Laporan Eksekutif BK – Kepala Sekolah',
            'activeYear' => $activeYear,
        ]));
    }

    /**
     * Kumpulkan data eksekutif (statistik ringkas, tanpa detail sensitif).
     * Ini laporan RINGKASAN — tidak menampilkan nama siswa atau catatan konseling.
     */
    private function queryEksekutif(array $f): array
    {
        $yearId   = (int)($f['academic_year_id'] ?? 0);
        $dateFrom = $f['date_from'] ?? '';
        $dateTo   = $f['date_to']   ?? '';
        // Ambil data tahun ajaran (dipakai filter kasus & tindak lanjut)
        $ay = $yearId ? $this->academicYearModel->find($yearId) : null;

        // ── A. Data Program ──────────────────────────────────────────────────
        $progQ = $this->db->table('bk_programs')->where('status !=', 'Draft');
        if ($yearId) $progQ->where('academic_year_id', $yearId);
        $totalProgram = $progQ->countAllResults();

        // Target layanan dari RPL (bk_rpl)
        $rplQ = $this->db->table('bk_rpl r')
            ->join('bk_programs p','p.id = r.program_id','left');
        if ($yearId) $rplQ->where('p.academic_year_id', $yearId);
        $targetLayanan = $rplQ->countAllResults();

        // ── B. Data Layanan ──────────────────────────────────────────────────
        $svcQ = $this->db->table('bk_services');
        if ($yearId)   $svcQ->where('academic_year_id', $yearId);
        if ($dateFrom) $svcQ->where('service_date >=', $dateFrom);
        if ($dateTo)   $svcQ->where('service_date <=', $dateTo);
        $totalLayanan = $svcQ->countAllResults(false);

        // Layanan per jenis
        $byType = $this->db->table('bk_services')
            ->select('service_type, COUNT(*) as jumlah')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($dateFrom, fn($q) => $q->where('service_date >=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('service_date <=', $dateTo))
            ->groupBy('service_type')
            ->orderBy('jumlah','DESC')
            ->get()->getResultArray();

        // Layanan per bidang
        $byField = $this->db->table('bk_services')
            ->select('field, COUNT(*) as jumlah')
            ->when($yearId, fn($q) => $q->where('academic_year_id', $yearId))
            ->when($dateFrom, fn($q) => $q->where('service_date >=', $dateFrom))
            ->when($dateTo, fn($q) => $q->where('service_date <=', $dateTo))
            ->groupBy('field')
            ->orderBy('jumlah','DESC')
            ->get()->getResultArray();

        // Total siswa yang menerima layanan (unik, dari peserta + student_id)
        $totalSiswa = (int)$this->db->query(
            'SELECT COUNT(DISTINCT student_id) AS n FROM (
                SELECT student_id FROM bk_services
                WHERE student_id IS NOT NULL'
                . ($yearId ? " AND academic_year_id = $yearId" : '')
                . ($dateFrom ? " AND service_date >= '$dateFrom'" : '')
                . ($dateTo ? " AND service_date <= '$dateTo'" : '')
            . ' UNION
                SELECT student_id FROM bk_service_participants sp
                JOIN bk_services s ON s.id = sp.service_id
                WHERE sp.attendance_status = \'Hadir\''
                . ($yearId ? " AND s.academic_year_id = $yearId" : '')
                . ($dateFrom ? " AND s.service_date >= '$dateFrom'" : '')
                . ($dateTo ? " AND s.service_date <= '$dateTo'" : '')
            . ') unik'
        )->getRowArray()['n'] ?? 0;

        // ── C. Data Kasus ───────────────────────────────────────────────────
        // Bangun kondisi kasus untuk digunakan berulang
        $kasusAyStart = isset($ay) ? $ay['start_date'].' 00:00:00' : null;
        $kasusAyEnd   = isset($ay) ? $ay['end_date'].' 23:59:59' : null;

        // Closure membangun query kasus baru (menghindari shared builder state)
        $buildKasusQ = function() use ($kasusAyStart, $kasusAyEnd, $dateFrom, $dateTo) {
            $q = $this->db->table('bk_cases c')
                ->join('students s','s.id = c.student_id','left')
                ->join('student_records sr',
                    'sr.student_id = s.id AND sr.status = \'aktif\'','left');
            if ($kasusAyStart) {
                $q->where('c.created_at >=', $kasusAyStart)
                  ->where('c.created_at <=', $kasusAyEnd);
            }
            if ($dateFrom) $q->where('c.incident_date >=', $dateFrom);
            if ($dateTo)   $q->where('c.incident_date <=', $dateTo);
            return $q;
        };

        $totalKasus    = $buildKasusQ()->countAllResults();
        $kasusSelesai  = $buildKasusQ()->whereIn('c.status', ['RESOLVED','CLOSED'])->countAllResults();
        $kasusProses   = $buildKasusQ()->whereIn('c.status', ['IN_PROGRESS','MONITORING'])->countAllResults();
        $kasusReferred = $buildKasusQ()->where('c.status','REFERRED')->countAllResults();

        // Kasus per kategori (tanpa nama/detail sensitif)
        $kasusByKategori = $this->db->table('bk_cases c')
            ->select('c.category, COUNT(*) as jumlah')
            ->join('students s','s.id = c.student_id','left')
            ->join('student_records sr',
                'sr.student_id = s.id AND sr.status = \'aktif\'','left')
            ->when($yearId && isset($ay), function($q) use ($ay) {
                $q->where('c.created_at >=', $ay['start_date'].' 00:00:00')
                  ->where('c.created_at <=', $ay['end_date'].' 23:59:59');
            })
            ->when($dateFrom, fn($q) => $q->where('c.incident_date >=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->where('c.incident_date <=', $dateTo))
            ->groupBy('c.category')
            ->orderBy('jumlah','DESC')
            ->get()->getResultArray();

        // ── D. Tindak Lanjut ────────────────────────────────────────────────
        $totalTindakLanjut = $this->db->table('bk_case_actions ca')
            ->join('bk_cases c','c.id = ca.case_id','left')
            ->when($yearId && isset($ay), function($q) use ($ay) {
                $q->where('c.created_at >=', $ay['start_date'].' 00:00:00')
                  ->where('c.created_at <=', $ay['end_date'].' 23:59:59');
            })
            ->when($dateFrom, fn($q) => $q->where('ca.action_date >=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->where('ca.action_date <=', $dateTo))
            ->countAllResults();

        // ── E. Rujukan ──────────────────────────────────────────────────────
        $totalRujukan = $this->db->table('bk_case_referrals cr')
            ->join('bk_cases c','c.id = cr.case_id','left')
            ->when($yearId && isset($ay), function($q) use ($ay) {
                $q->where('c.created_at >=', $ay['start_date'].' 00:00:00')
                  ->where('c.created_at <=', $ay['end_date'].' 23:59:59');
            })
            ->when($dateFrom, fn($q) => $q->where('cr.referral_date >=', $dateFrom))
            ->when($dateTo,   fn($q) => $q->where('cr.referral_date <=', $dateTo))
            ->countAllResults();

        // ── F. Pemetaan Kebutuhan (asesmen) — agregasi, tanpa data individu ─
        $asesmenAgg = $this->db->table('bk_student_results sr')
            ->select('inst.type, COUNT(DISTINCT sr.student_id) AS total_siswa')
            ->join('bk_assessments a','a.id = sr.assessment_id','left')
            ->join('bk_instruments inst','inst.id = a.instrument_id','left')
            ->when($yearId, fn($q) => $q->where('a.academic_year_id', $yearId))
            ->groupBy('inst.type')
            ->get()->getResultArray();

        // ── G. Capaian Program (target vs realisasi) ────────────────────────
        $capaian = ($targetLayanan > 0)
            ? round($totalLayanan / $targetLayanan * 100, 1)
            : null;

        return compact(
            'totalProgram','targetLayanan','totalLayanan','byType','byField','totalSiswa',
            'totalKasus','kasusSelesai','kasusProses','kasusReferred','kasusByKategori',
            'totalTindakLanjut','totalRujukan','asesmenAgg','capaian'
        );
    }

    /** POST  bk/laporan/eksekutif/preview */
    public function previewEksekutif()
    {
        if (!$this->canAccessEksekutif()) return $this->denyRedirect();

        $f      = $this->request->getPost();
        $data   = $this->queryEksekutif($f);
        $dd     = $this->getFilterDropdowns();
        $school = $this->schoolModel->getProfile();
        $year   = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();

        return view('admin/bk/laporan/preview_eksekutif', array_merge($data, [
            'title'        => 'Preview Laporan Eksekutif BK',
            'filter'       => $f,
            'school'       => $school,
            'year'         => $year,
            'academicYears'=> $dd['academicYears'],
        ]));
    }

    /** POST  bk/laporan/eksekutif/pdf */
    public function pdfEksekutif()
    {
        if (!$this->canAccessEksekutif()) return $this->denyRedirect();

        $f    = $this->request->getPost();
        $data = $this->queryEksekutif($f);

        // Cek minimal ada data (layanan atau kasus)
        if ($data['totalLayanan'] === 0 && $data['totalKasus'] === 0
                && $data['totalProgram'] === 0) {
            return redirect()->back()->with('error',
                'Tidak terdapat data BK pada periode yang dipilih. '
                . 'Pastikan data layanan, kasus, atau program sudah diinput.');
        }

        $school  = $this->schoolModel->getProfile();
        $logoB64 = $this->getLogoBase64();
        $year    = !empty($f['academic_year_id'])
            ? $this->academicYearModel->find($f['academic_year_id'])
            : $this->getActiveYear();
        $user    = $this->currentUser();

        $html = view('admin/bk/laporan/pdf_eksekutif', array_merge($data, [
            'filter'  => $f,
            'school'  => $school,
            'logoB64' => $logoB64,
            'year'    => $year,
            'user'    => $user,
            'printAt' => date('d/m/Y H:i'),
        ]));

        $filename = 'Laporan_Eksekutif_BK_' . date('Ymd') . '.pdf';
        $this->streamPdf($html, $filename, 'portrait');
    }
}
