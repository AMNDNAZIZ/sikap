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

        // Parameter Semester: 1 (Ganjil) atau 2 (Genap)
        // Default semester berdasarkan kalender: Juli-Desember = 1, Januari-Juni = 2
        $currentMonth    = (int) date('n');
        $defaultSemester = ($currentMonth >= 1 && $currentMonth <= 6) ? 2 : 1;
        $semester        = (int) ($this->request->getGet('semester') ?? $defaultSemester);
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

        // Pre-fetch daftar ID guru yang menjadi wali kelas / guru kelas di tabel classes
        $waliKelasRows = $this->db->table('classes')
            ->select('teacher_id')
            ->where('is_active', 1)
            ->where('teacher_id IS NOT NULL', null, false)
            ->get()->getResultArray();
        $waliKelasTeacherIds = array_filter(array_column($waliKelasRows, 'teacher_id'));

        // 4. Query Semua Penugasan Mengajar (Plotting) Guru Aktif di Tahun Ajaran Ini
        $assignmentQuery = $this->db->table('teaching_assignments ta')
            ->select('ta.id, ta.teacher_id, ta.class_id, ta.subject_id, ta.role as assignment_role, t.name as teacher_name, t.nip as teacher_nip, t.phone as teacher_phone, c.name as class_name, c.level as class_level, s.name as subject_name')
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

        // 5. BATCH PRE-FETCH AGREGASI (Untuk Performa Cepat & Mencegah Masalah N+1 Query)

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

        // 5.2 Formatif: Hitung Materi ATP per Class & Subject sesuai SEMESTER yang berjalan
        $atpCountsRaw = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.class_id, atp.subject_id, c.level as class_level, COUNT(atp.id) as total_atp')
            ->join('classes c', 'c.id = atp.class_id', 'left')
            ->where('atp.semester', $semester)
            ->groupBy('atp.class_id, atp.subject_id, c.level')
            ->get()->getResultArray();

        $atpCountMap      = [];
        $atpLevelCountMap = [];
        foreach ($atpCountsRaw as $atp) {
            if (!empty($atp['class_id'])) {
                $atpCountMap[$atp['class_id'] . '_' . $atp['subject_id']] = (int) $atp['total_atp'];
            }
            if (!empty($atp['class_level'])) {
                $lKey = $atp['class_level'] . '_' . $atp['subject_id'];
                $atpLevelCountMap[$lKey] = max($atpLevelCountMap[$lKey] ?? 0, (int) $atp['total_atp']);
            }
        }

        // 5.3 Formatif: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Formatif di material_scores untuk semester berjalan
        $formatifCountsRaw = $this->db->table('material_scores ms')
            ->select('sr.class_id, atp.subject_id, COUNT(DISTINCT ms.student_id) as student_count, COUNT(DISTINCT ms.material_id) as scored_atp_count')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
            ->join('student_records sr', 'sr.student_id = ms.student_id')
            ->where('sr.academic_year_id', $activeYearId)
            ->where('sr.status', 'aktif')
            ->where('atp.semester', $semester)
            ->where('ms.score IS NOT NULL', null, false)
            ->groupBy('sr.class_id, atp.subject_id')
            ->get()->getResultArray();

        $formatifMap          = [];
        $formatifScoredAtpMap = [];
        foreach ($formatifCountsRaw as $fc) {
            $key = $fc['class_id'] . '_' . $fc['subject_id'];
            $formatifMap[$key]          = (int) $fc['student_count'];
            $formatifScoredAtpMap[$key] = (int) $fc['scored_atp_count'];
        }

        // 5.4 Sumatif: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Sumatif di summative_scores untuk semester berjalan
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

        // 5.5 Rapor: Hitung Jumlah Siswa Aktif yang Memiliki Nilai Akhir Rapor di grades untuk semester berjalan
        $semesterLabels    = [$semester, $semester == 1 ? 'ganjil' : 'genap'];
        $gradeFields       = $this->db->getFieldNames('grades');
        $hasCompetencyDesc = in_array('competency_desc', $gradeFields);
        
        $descSql = $hasCompetencyDesc 
            ? 'COUNT(DISTINCT CASE WHEN (g.competency_desc IS NOT NULL AND g.competency_desc != "") THEN g.student_id END) as desc_count'
            : '0 as desc_count';

        $raporCountsRaw = $this->db->table('grades g')
            ->select('sr.class_id, g.subject_id,
                      COUNT(DISTINCT CASE WHEN (g.erapor_score IS NOT NULL OR g.report_score IS NOT NULL) THEN g.student_id END) as score_count, ' . $descSql)
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
            // Ambil jumlah ATP kelas, jika belum ada cek tingkat level
            $atpCount      = $atpCountMap[$key] ?? ($atpLevelCountMap[($row['class_level'] ?? '') . '_' . $sid] ?? 0);
            $formatifCount = $formatifMap[$key] ?? 0;
            $sumatifCount  = $sumatifMap[$key] ?? 0;
            $raporScoreCnt = $raporScoreMap[$key] ?? 0;
            $raporDescCnt  = $raporDescMap[$key] ?? 0;

            $fThreshold = $totalStudents > 0 ? max(1, (int) round($totalStudents * 0.9)) : 0;
            $sThreshold = $totalStudents > 0 ? max(1, (int) round($totalStudents * 0.9)) : 0;

            // --- A. Status Formatif (ATP) ---
            // Selesai jika: minimal ada ATP di semester ini dan >= 90% siswa sudah dinilai
            if ($totalStudents > 0 && $formatifCount >= $fThreshold && $atpCount > 0) {
                $fStatus   = 'complete';
                $fInfo     = "{$formatifCount}/{$totalStudents} Siswa ({$atpCount} ATP)";
                $fProgress = 1.0;
            } elseif ($formatifCount > 0) {
                // Ada nilai formatif masuk tapi belum tuntas 90%
                $fStatus   = 'partial';
                $fInfo     = "{$formatifCount}/{$totalStudents} Siswa ({$atpCount} ATP)";
                $fProgress = min(0.99, round($formatifCount / max(1, $fThreshold), 2));
            } else {
                // BELUM ADA NILAI FORMATIF SAMA SEKALI DARI GURU
                $fStatus   = 'empty';
                $fInfo     = ($atpCount > 0) ? "0/{$totalStudents} Siswa ({$atpCount} ATP)" : "Kosong (0 ATP)";
                $fProgress = 0.0;
            }
            $formatifStats[$fStatus]++;

            // --- B. Status Sumatif ---
            if ($totalStudents > 0 && $sumatifCount >= $sThreshold) {
                $sStatus   = 'complete';
                $sInfo     = "{$sumatifCount}/{$totalStudents} Siswa";
                $sProgress = 1.0;
            } elseif ($sumatifCount > 0) {
                $sStatus   = 'partial';
                $sInfo     = "{$sumatifCount}/{$totalStudents} Siswa";
                $sProgress = min(0.99, round($sumatifCount / max(1, $sThreshold), 2));
            } else {
                $sStatus   = 'empty';
                $sInfo     = "Kosong";
                $sProgress = 0.0;
            }
            $sumatifStats[$sStatus]++;

            // --- C. Status Rapor ---
            if ($totalStudents > 0 && $raporScoreCnt >= $totalStudents) {
                if (!$hasCompetencyDesc || $raporDescCnt >= $totalStudents) {
                    $rStatus   = 'complete';
                    $rInfo     = "Lengkap ({$raporScoreCnt} Siswa)";
                    $rProgress = 1.0;
                } else {
                    $rStatus   = 'partial';
                    $rInfo     = "Nilai ada, Deskripsi {$raporDescCnt}/{$totalStudents}";
                    $rProgress = 0.8;
                }
            } elseif ($raporScoreCnt > 0) {
                $rStatus   = 'partial';
                $rInfo     = "Nilai {$raporScoreCnt}/{$totalStudents} Siswa";
                $rProgress = min(0.79, round(($raporScoreCnt / max(1, $totalStudents)) * 0.7, 2));
            } else {
                $rStatus   = 'empty';
                $rInfo     = "Kosong";
                $rProgress = 0.0;
            }
            $raporStats[$rStatus]++;

            // --- D. Progress Penugasan ini (0 - 100%) ---
            // Bobot: Formatif 33.3%, Sumatif 33.3%, Rapor 33.3%
            // Jika ketiga komponen nilai = 0, progres mutlak 0%!
            $assignmentProgress = (int) round((($fProgress + $sProgress + $rProgress) / 3.0) * 100);

            if ($assignmentProgress >= 100) {
                $overallStatus = 'complete';
            } elseif ($assignmentProgress === 0) {
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
                'id'                  => $row['id'],
                'teacher_id'          => $tid,
                'teacher_name'        => $row['teacher_name'],
                'teacher_nip'         => $row['teacher_nip'],
                'teacher_phone'       => $row['teacher_phone'],
                'class_id'            => $cid,
                'class_name'          => $row['class_name'],
                'class_level'         => $row['class_level'],
                'subject_id'          => $sid,
                'subject_name'        => $row['subject_name'],
                'total_students'      => $totalStudents,
                'atp_count'           => $atpCount,
                'formatif_count'      => $formatifCount,
                'formatif_status'     => $fStatus,
                'formatif_info'       => $fInfo,
                'sumatif_count'       => $sumatifCount,
                'sumatif_status'      => $sStatus,
                'sumatif_info'        => $sInfo,
                'rapor_score_cnt'     => $raporScoreCnt,
                'rapor_desc_cnt'      => $raporDescCnt,
                'rapor_status'        => $rStatus,
                'rapor_info'          => $rInfo,
                'assignment_progress' => $assignmentProgress,
                'overall_status'      => $overallStatus,
            ];
            $assignmentsList[] = $assignmentData;

            // Inisialisasi Ringkasan Guru
            if (!isset($teacherSummary[$tid])) {
                $cleanPhone = $this->formatWhatsAppNumber($row['teacher_phone'] ?? null);
                $teacherSummary[$tid] = [
                    'id'                 => $tid,
                    'name'               => $row['teacher_name'],
                    'nip'                => $row['teacher_nip'] ?: '-',
                    'phone'              => $row['teacher_phone'],
                    'whatsapp_phone'     => $cleanPhone,
                    'total_assignments'  => 0,
                    'complete'           => 0,
                    'partial'            => 0,
                    'empty'              => 0,
                    'f_complete'         => 0,
                    's_complete'         => 0,
                    'r_complete'         => 0,
                    'total_progress_sum' => 0,
                    'classes'            => [],
                    'subjects'           => [],
                    'roles'              => [],
                ];
            }

            $teacherSummary[$tid]['total_assignments']++;
            $teacherSummary[$tid]['total_progress_sum'] += $assignmentProgress;
            $teacherSummary[$tid]['classes'][$cid]       = true;
            $teacherSummary[$tid]['subjects'][$sid]      = true;
            if (!empty($row['assignment_role'])) {
                $teacherSummary[$tid]['roles'][] = $row['assignment_role'];
            }

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

        // Kalkulasi Persentase Guru, Tipe Guru (Guru Kelas vs Guru Bidang Studi), & Pesan WhatsApp
        $yearName = $activeYear['year'] ?? '-';
        foreach ($teacherSummary as $tid => &$ts) {
            $total = $ts['total_assignments'];

            // Progres Nyata Guru: Rata-rata dari akumulasi nilai progres seluruh penugasan
            // Jika semua nilai 0, progres mutlak 0%!
            $pct = $total > 0 ? (int) round($ts['total_progress_sum'] / $total) : 0;
            $ts['progress_pct'] = min(100, max(0, $pct));

            // Klasifikasi Guru Kelas vs Guru Bidang Studi
            // 1. Role di penugasan = 'guru_kelas'
            // 2. Atau terdaftar sebagai wali kelas/guru kelas di tabel classes
            // 3. Atau mengajar 1 kelas dengan banyak mapel (pola SD)
            $isGuruKelas = false;
            if (in_array('guru_kelas', $ts['roles'])) {
                $isGuruKelas = true;
            } elseif (in_array($tid, $waliKelasTeacherIds)) {
                $isGuruKelas = true;
            } elseif (count($ts['classes']) === 1 && count($ts['subjects']) > 1) {
                $isGuruKelas = true;
            }

            $ts['is_guru_kelas'] = $isGuruKelas;
            $ts['unit_label']    = $isGuruKelas ? 'Mapel' : 'Rombel';
            $ts['role_label']    = $isGuruKelas ? 'Guru Kelas' : 'Guru Bidang Studi';

            // Pesan WhatsApp Nudge
            if (!empty($ts['whatsapp_phone'])) {
                $waText = $this->buildWhatsAppMessage($ts['name'], $yearName, $semester, [
                    'total'             => $total,
                    'unit_label'        => $ts['unit_label'],
                    'role_label'        => $ts['role_label'],
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
        $unit     = $summary['unit_label'] ?? 'Rombel';

        $msg  = "Assalamu'alaikum wr. wb. / Yth. Bapak/Ibu {$teacherName},\n\n";
        $msg .= "Mohon izin mengingatkan dari Kepala Sekolah terkait progres penginputan nilai Semester {$semLabel} TA {$yearName}:\n";
        $msg .= "• Beban Mengajar: {$summary['total']} {$unit}\n";
        $msg .= "• Nilai Formatif (ATP): {$summary['formatif_complete']} dari {$summary['total']} {$unit} Selesai\n";
        $msg .= "• Nilai Sumatif: {$summary['sumatif_complete']} dari {$summary['total']} {$unit} Selesai\n";
        $msg .= "• Nilai Rapor: {$summary['rapor_complete']} dari {$summary['total']} {$unit} Selesai\n";
        $msg .= "• Progres Keseluruhan: {$summary['progress_pct']}%\n\n";
        $msg .= "Mohon kesediaannya untuk mempercepat pelengkapan data nilai sebelum batas akhir pembagian rapor. Terima kasih atas dedikasi dan kerja samanya. 🙏";

        return $msg;
    }
}
