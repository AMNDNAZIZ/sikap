<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AcademicYearModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Models\TeachingAssignmentModel;

class GradeMonitoringController extends BaseController
{
    protected $db;
    protected $academicYearModel;
    protected $teacherModel;
    protected $classModel;
    protected $subjectModel;
    protected $teachingAssignmentModel;

    public function __construct()
    {
        $this->db                      = db_connect();
        $this->academicYearModel       = new AcademicYearModel();
        $this->teacherModel            = new TeacherModel();
        $this->classModel              = new ClassModel();
        $this->subjectModel            = new SubjectModel();
        $this->teachingAssignmentModel = new TeachingAssignmentModel();
    }

    public function index()
    {
        // 1. Otorisasi - Khusus Admin (1) dan Kepala Sekolah (2)
        $user = session()->get('user');
        if (!$user || !in_array($user['role_id'], [1, 2])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses khusus untuk Kepala Sekolah dan Administrator.');
        }

        $data['title'] = 'Monitoring Input Nilai Guru';
        $data['user']  = $user;

        // 2. Dapatkan Tahun Ajaran Aktif (atau fallback ke tahun ajaran terbaru)
        $activeYear   = $this->academicYearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? null;
        $data['active_year'] = $activeYear;

        // Parameter Semester: 1 (Ganjil) atau 2 (Genap), default 1
        $semester = (int) ($this->request->getGet('semester') ?? 1);
        if ($semester !== 1 && $semester !== 2) {
            $semester = 1;
        }
        $data['semester'] = $semester;

        // Parameter Filter
        $filterTeacher = $this->request->getGet('teacher_id');
        $filterClass   = $this->request->getGet('class_id');
        $filterSubject = $this->request->getGet('subject_id');
        $filterStatus  = $this->request->getGet('status') ?? 'all'; // all, complete, partial, empty

        $data['filter_teacher'] = $filterTeacher;
        $data['filter_class']   = $filterClass;
        $data['filter_subject'] = $filterSubject;
        $data['filter_status']  = $filterStatus;

        if (!$activeYearId) {
            $data['teachers']        = [];
            $data['classes']         = [];
            $data['subjects']        = [];
            $data['assignments']     = [];
            $data['teacher_summary'] = [];
            $data['formatif_stats']  = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['sumatif_stats']   = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['rapor_stats']     = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['overall_stats']   = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['kpi']             = ['total_plotting' => 0, 'total_teachers' => 0, 'complete_formatif' => 0, 'complete_sumatif' => 0, 'complete_rapor' => 0, 'complete_all' => 0];
            return view('admin/grades/monitoring', $data);
        }

        // 3. Dropdown: HANYA Guru yang AKTIF dan memiliki plotting di Tahun Ajaran Aktif berjalan
        $data['teachers'] = $this->db->table('teachers t')
            ->select('DISTINCT(t.id), t.name, t.nip, t.phone')
            ->join('teaching_assignments ta', 'ta.teacher_id = t.id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.academic_year_id', $activeYearId)
            ->where('t.is_active', 1)
            ->where('s.is_bk', 0)
            ->orderBy('t.name', 'ASC')
            ->get()->getResultArray();

        // Dropdown Kelas Aktif
        $data['classes'] = $this->classModel->getClassesByAcademicYear((int) $activeYearId);

        // Dropdown Mapel Akademik (Non-BK)
        $data['subjects'] = $this->db->table('subjects s')
            ->select('DISTINCT(s.id), s.name')
            ->join('teaching_assignments ta', 'ta.subject_id = s.id')
            ->where('ta.academic_year_id', $activeYearId)
            ->where('s.is_bk', 0)
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // 4. Query Semua Penugasan Mengajar (Plotting) Guru Aktif di Tahun Ajaran Ini
        $assignmentQuery = $this->db->table('teaching_assignments ta')
            ->select('ta.id, ta.teacher_id, ta.class_id, ta.subject_id, t.name as teacher_name, t.nip as teacher_nip, t.phone as teacher_phone, c.name as class_name, c.level as class_level, s.name as subject_name')
            ->join('teachers t', 't.id = ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.academic_year_id', $activeYearId)
            ->where('t.is_active', 1)
            ->where('s.is_bk', 0);

        if ($filterTeacher) {
            $assignmentQuery->where('ta.teacher_id', $filterTeacher);
        }
        if ($filterClass) {
            $assignmentQuery->where('ta.class_id', $filterClass);
        }
        if ($filterSubject) {
            $assignmentQuery->where('ta.subject_id', $filterSubject);
        }

        $allAssignments = $assignmentQuery->orderBy('t.name', 'ASC')
            ->orderBy('c.name', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // 5. BATCH PRE-FETCH AGREGASI (Untuk Performa Instan & Mencegah Masalah N+1 Query)

        // 5.1 Hitung Jumlah Siswa Aktif per Rombel di Tahun Ajaran Aktif
        $studentCountsRaw = $this->db->table('student_records')
            ->select('class_id, COUNT(id) as total_students')
            ->where('academic_year_id', $activeYearId)
            ->where('status', 'aktif')
            ->groupBy('class_id')
            ->get()->getResultArray();
        $studentCountMap = [];
        foreach ($studentCountsRaw as $sc) {
            $studentCountMap[(int) $sc['class_id']] = (int) $sc['total_students'];
        }

        // 5.2 Formatif: Hitung Materi ATP yang ada per Class & Subject
        $atpCountsRaw = $this->db->table('alur_tujuan_pembelajaran')
            ->select('class_id, subject_id, COUNT(id) as total_tp')
            ->where('semester', $semester)
            ->groupBy('class_id, subject_id')
            ->get()->getResultArray();
        $atpCountMap = [];
        foreach ($atpCountsRaw as $atp) {
            $atpCountMap[$atp['class_id'] . '_' . $atp['subject_id']] = (int) $atp['total_tp'];
        }

        // 5.3 Formatif: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Formatif di material_scores
        $formatifCountsRaw = $this->db->table('material_scores ms')
            ->select('atp.class_id, atp.subject_id, COUNT(DISTINCT ms.student_id) as student_count')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
            ->join('student_records sr', 'sr.student_id = ms.student_id AND sr.class_id = atp.class_id')
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->where('atp.semester', $semester)
            ->where('ms.score IS NOT NULL', null, false)
            ->groupBy('atp.class_id, atp.subject_id')
            ->get()->getResultArray();
        $formatifMap = [];
        foreach ($formatifCountsRaw as $fc) {
            $formatifMap[$fc['class_id'] . '_' . $fc['subject_id']] = (int) $fc['student_count'];
        }

        // 5.4 Sumatif: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Sumatif di summative_scores
        $sumatifCountsRaw = $this->db->table('summative_scores ss')
            ->select('sr.class_id, ss.subject_id, COUNT(DISTINCT ss.student_id) as student_count')
            ->join('student_records sr', 'sr.student_id = ss.student_id')
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->where('ss.semester', $semester)
            ->where('ss.score IS NOT NULL', null, false)
            ->groupBy('sr.class_id, ss.subject_id')
            ->get()->getResultArray();
        $sumatifMap = [];
        foreach ($sumatifCountsRaw as $sc) {
            $sumatifMap[$sc['class_id'] . '_' . $sc['subject_id']] = (int) $sc['student_count'];
        }

        // 5.5 Rapor: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Akhir Rapor di grades
        $semesterLabels = [$semester, $semester == 1 ? 'ganjil' : 'genap'];
        $raporCountsRaw = $this->db->table('grades g')
            ->select('sr.class_id, g.subject_id,
                      COUNT(DISTINCT CASE WHEN (g.erapor_score IS NOT NULL OR g.report_score IS NOT NULL) THEN g.student_id END) as score_count,
                      COUNT(DISTINCT CASE WHEN ((g.competency_desc IS NOT NULL AND g.competency_desc != "") 
                                             OR (g.deskripsi_capaian_tinggi IS NOT NULL AND g.deskripsi_capaian_tinggi != "")) THEN g.student_id END) as desc_count')
            ->join('student_records sr', 'sr.student_id = g.student_id')
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->where('g.year_id', $activeYearId)
            ->whereIn('g.semester', $semesterLabels)
            ->groupBy('sr.class_id, g.subject_id')
            ->get()->getResultArray();
        $raporScoreMap = [];
        $raporDescMap  = [];
        foreach ($raporCountsRaw as $rc) {
            $key = $rc['class_id'] . '_' . $rc['subject_id'];
            $raporScoreMap[$key] = (int) $rc['score_count'];
            $raporDescMap[$key]  = (int) $rc['desc_count'];
        }

        // 6. Evaluasi Setiap Penugasan Mengajar (Assignments)
        $assignmentsList = [];
        $teacherSummary  = [];

        // Inisialisasi statistik akumulatif
        $formatifStats = ['complete' => 0, 'partial' => 0, 'empty' => 0];
        $sumatifStats  = ['complete' => 0, 'partial' => 0, 'empty' => 0];
        $raporStats    = ['complete' => 0, 'partial' => 0, 'empty' => 0];
        $overallStats  = ['complete' => 0, 'partial' => 0, 'empty' => 0];

        foreach ($allAssignments as $row) {
            $cid = (int) $row['class_id'];
            $sid = (int) $row['subject_id'];
            $tid = (int) $row['teacher_id'];
            $key = $cid . '_' . $sid;

            $totalStudents = $studentCountMap[$cid] ?? 0;
            $atpCount      = $atpCountMap[$key] ?? 0;
            $formatifCount = $formatifMap[$key] ?? 0;
            $sumatifCount  = $sumatifMap[$key] ?? 0;
            $raporScoreCnt = $raporScoreMap[$key] ?? 0;
            $raporDescCnt  = $raporDescMap[$key] ?? 0;

            // --- A. Status Formatif ---
            // Syarat Complete: ada minimal materi ATP dan >= 90% siswa sudah dinilai (atau 100% jika siswa <= 5)
            $fThreshold = $totalStudents > 0 ? max(1, (int) round($totalStudents * 0.9)) : 0;
            if ($totalStudents > 0 && $formatifCount >= $fThreshold && $atpCount > 0) {
                $fStatus = 'complete';
                $fInfo   = "{$formatifCount}/{$totalStudents} Siswa ({$atpCount} TP)";
            } elseif ($formatifCount > 0 || $atpCount > 0) {
                $fStatus = 'partial';
                $fInfo   = $formatifCount > 0 ? "{$formatifCount}/{$totalStudents} Siswa ({$atpCount} TP)" : "Belum dinilai ({$atpCount} TP dibuat)";
            } else {
                $fStatus = 'empty';
                $fInfo   = 'Kosong (0 TP)';
            }
            $formatifStats[$fStatus]++;

            // --- B. Status Sumatif ---
            $sThreshold = $totalStudents > 0 ? max(1, (int) round($totalStudents * 0.9)) : 0;
            if ($totalStudents > 0 && $sumatifCount >= $sThreshold) {
                $sStatus = 'complete';
                $sInfo   = "{$sumatifCount}/{$totalStudents} Siswa";
            } elseif ($sumatifCount > 0) {
                $sStatus = 'partial';
                $sInfo   = "{$sumatifCount}/{$totalStudents} Siswa";
            } else {
                $sStatus = 'empty';
                $sInfo   = 'Kosong';
            }
            $sumatifStats[$sStatus]++;

            // --- C. Status Rapor ---
            if ($totalStudents > 0 && $raporScoreCnt >= $totalStudents) {
                if ($raporDescCnt >= $totalStudents) {
                    $rStatus = 'complete';
                    $rInfo   = "Lengkap ({$raporScoreCnt} Siswa)";
                } else {
                    $rStatus = 'partial';
                    $rInfo   = "Nilai ada, Deskripsi {$raporDescCnt}/{$totalStudents}";
                }
            } elseif ($raporScoreCnt > 0) {
                $rStatus = 'partial';
                $rInfo   = "Nilai {$raporScoreCnt}/{$totalStudents} Siswa";
            } else {
                $rStatus = 'empty';
                $rInfo   = 'Kosong';
            }
            $raporStats[$rStatus]++;

            // --- D. Status Overall Assignment ---
            if ($fStatus === 'complete' && $sStatus === 'complete' && $rStatus === 'complete') {
                $overallStatus = 'complete';
            } elseif ($fStatus === 'empty' && $sStatus === 'empty' && $rStatus === 'empty') {
                $overallStatus = 'empty';
            } else {
                $overallStatus = 'partial';
            }
            $overallStats[$overallStatus]++;

            // Filter status jika dipilih pengguna
            if ($filterStatus !== 'all' && $overallStatus !== $filterStatus) {
                continue;
            }

            $assignmentData = [
                'id'              => $row['id'],
                'teacher_id'      => $tid,
                'teacher_name'    => $row['teacher_name'],
                'teacher_nip'     => $row['teacher_nip'],
                'teacher_phone'   => $row['teacher_phone'],
                'class_id'        => $cid,
                'class_name'      => $row['class_name'],
                'class_level'     => $row['class_level'],
                'subject_id'      => $sid,
                'subject_name'    => $row['subject_name'],
                'total_students'  => $totalStudents,
                'atp_count'       => $atpCount,
                'formatif_count'  => $formatifCount,
                'formatif_status' => $fStatus,
                'formatif_info'   => $fInfo,
                'sumatif_count'   => $sumatifCount,
                'sumatif_status'  => $sStatus,
                'sumatif_info'    => $sInfo,
                'rapor_score_cnt' => $raporScoreCnt,
                'rapor_desc_cnt'  => $raporDescCnt,
                'rapor_status'    => $rStatus,
                'rapor_info'      => $rInfo,
                'overall_status'  => $overallStatus,
            ];
            $assignmentsList[] = $assignmentData;

            // Update Teacher Summary
            if (!isset($teacherSummary[$tid])) {
                $cleanPhone = $this->formatWhatsAppNumber($row['teacher_phone'] ?? null);
                $teacherSummary[$tid] = [
                    'id'                => $tid,
                    'name'              => $row['teacher_name'],
                    'nip'               => $row['teacher_nip'] ?: '-',
                    'phone'             => $row['teacher_phone'],
                    'whatsapp_phone'    => $cleanPhone,
                    'total_assignments' => 0,
                    'complete'          => 0,
                    'partial'           => 0,
                    'empty'             => 0,
                    'f_complete'        => 0,
                    's_complete'        => 0,
                    'r_complete'        => 0,
                ];
            }

            $teacherSummary[$tid]['total_assignments']++;
            if ($overallStatus === 'complete') {
                $teacherSummary[$tid]['complete']++;
            } elseif ($overallStatus === 'empty') {
                $teacherSummary[$tid]['empty']++;
            } else {
                $teacherSummary[$tid]['partial']++;
            }

            if ($fStatus === 'complete') $teacherSummary[$tid]['f_complete']++;
            if ($sStatus === 'complete') $teacherSummary[$tid]['s_complete']++;
            if ($rStatus === 'complete') $teacherSummary[$tid]['r_complete']++;
        }

        // Kalkulasi Persentase Guru & Generate Pesan WhatsApp
        $yearName = $activeYear['year'] ?? '-';
        foreach ($teacherSummary as $tid => &$ts) {
            $total = $ts['total_assignments'];
            // Bobot perhitungan: Complete 100%, Partial 50%
            $score = ($ts['complete'] * 1.0) + ($ts['partial'] * 0.5);
            $pct   = $total > 0 ? (int) round(($score / $total) * 100) : 0;
            $ts['progress_pct'] = min(100, $pct);

            // Pesan WhatsApp Nudge
            if (!empty($ts['whatsapp_phone'])) {
                $waText = $this->buildWhatsAppMessage($ts['name'], $yearName, $semester, [
                    'total'             => $total,
                    'formatif_complete' => $ts['f_complete'],
                    'sumatif_complete'  => $ts['s_complete'],
                    'rapor_complete'    => $ts['r_complete'],
                    'progress_pct'      => $ts['progress_pct'],
                ]);
                $ts['whatsapp_url'] = 'https://api.whatsapp.com/send?phone=' . $ts['whatsapp_phone'] . '&text=' . rawurlencode($waText);
            } else {
                $ts['whatsapp_url'] = null;
            }
        }
        unset($ts);

        // Urutkan Guru: Progres Terendah di Atas (Agar kepsek mudah melihat siapa yang perlu diingatkan)
        uasort($teacherSummary, function ($a, $b) {
            if ($a['progress_pct'] === $b['progress_pct']) {
                return strcmp($a['name'], $b['name']);
            }
            return ($a['progress_pct'] < $b['progress_pct']) ? -1 : 1;
        });

        // 7. KPI Metrics Card
        $totalPlotting = count($allAssignments);
        $totalTeachers = count($teacherSummary);
        $completeTeachers = 0;
        foreach ($teacherSummary as $ts) {
            if ($ts['progress_pct'] >= 100) {
                $completeTeachers++;
            }
        }

        $data['kpi'] = [
            'total_plotting'    => $totalPlotting,
            'total_teachers'    => $totalTeachers,
            'complete_formatif' => $formatifStats['complete'],
            'complete_sumatif'  => $sumatifStats['complete'],
            'complete_rapor'    => $raporStats['complete'],
            'complete_all'      => $overallStats['complete'],
            'complete_teachers' => $completeTeachers,
        ];

        $data['assignments']     = $assignmentsList;
        $data['teacher_summary'] = $teacherSummary;
        $data['formatif_stats']  = $formatifStats;
        $data['sumatif_stats']   = $sumatifStats;
        $data['rapor_stats']     = $raporStats;
        $data['overall_stats']   = $overallStats;

        return view('admin/grades/monitoring', $data);
    }

    /**
     * Sanitasi format nomor WhatsApp Indonesia ke format internasional 628xxx
     */
    protected function formatWhatsAppNumber(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }

        // Hapus karakter non-angka
        $clean = preg_replace('/[^0-9]/', '', $phone);
        if (empty($clean)) {
            return null;
        }

        // Ubah format 08xxx menjadi 628xxx
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }

        return (strlen($clean) >= 10) ? $clean : null;
    }

    /**
     * Susun draft pesan santun & resmi dari Kepala Sekolah untuk pengingat WhatsApp
     */
    protected function buildWhatsAppMessage(string $teacherName, string $yearName, int $semester, array $summary): string
    {
        $semLabel = $semester === 1 ? 'Ganjil' : 'Genap';

        $msg  = "Assalamu'alaikum wr. wb. / Yth. Bapak/Ibu {$teacherName},\n\n";
        $msg .= "Mohon izin mengingatkan dari Kepala Sekolah terkait progres penginputan nilai Semester {$semLabel} TA {$yearName}:\n";
        $msg .= "• Total Beban Mengajar: {$summary['total']} Rombel\n";
        $msg .= "• Nilai Formatif: {$summary['formatif_complete']} dari {$summary['total']} Rombel Selesai\n";
        $msg .= "• Nilai Sumatif: {$summary['sumatif_complete']} dari {$summary['total']} Rombel Selesai\n";
        $msg .= "• Nilai Rapor: {$summary['rapor_complete']} dari {$summary['total']} Rombel Selesai\n";
        $msg .= "• Progres Keseluruhan: {$summary['progress_pct']}%\n\n";
        $msg .= "Mohon kesediaannya untuk mempercepat pelengkapan data nilai sebelum batas akhir pembagian rapor. Terima kasih atas dedikasi dan kerja samanya. 🙏";

        return $msg;
    }
}

