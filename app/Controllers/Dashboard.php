<?php

namespace App\Controllers;

use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\ScheduleModel;

class Dashboard extends BaseController
{
    public function index()
    {
        helper(['holiday', 'jp']); // Load holiday and JP helpers
        
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $db = \Config\Database::connect();
        $today = date('Y-m-d');
        
        // Check if this is a fresh installation (for admin only)
        if ($roleId == 1) {
            $schoolModel = new \App\Models\SchoolModel();
            $school = $schoolModel->first();
            
            // If no school data, redirect to school setup
            if (!$school || empty($school['name'])) {
                return redirect()->to('/admin/school')
                    ->with('info', 'Selamat datang! Silakan lengkapi data sekolah terlebih dahulu.');
            }
            
            // Check if academic year exists
            $yearModel = new \App\Models\AcademicYearModel();
            $yearCount = $yearModel->countAll();
            
            // If no academic year at all, redirect to create
            if ($yearCount == 0) {
                return redirect()->to('/admin/academic-year/create')
                    ->with('info', 'Silakan buat tahun ajaran terlebih dahulu untuk memulai menggunakan sistem.');
            }
        }
        
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        
        // If no active year, redirect based on role
        if (!$activeYear) {
            // For admin, redirect to academic year list to activate one
            if ($roleId == 1) {
                return redirect()->to('/admin/academic-year')
                    ->with('warning', 'Tidak ada tahun ajaran yang aktif. Silakan aktifkan salah satu tahun ajaran yang sudah ada.');
            }
            
            // For other users, show informative error
            $data = [
                'title' => 'Sistem Belum Siap',
                'heading' => 'Sistem Belum Dikonfigurasi',
                'message' => 'Sistem sedang dalam proses konfigurasi awal. Administrator sedang mengatur tahun ajaran. Silakan hubungi administrator atau coba lagi nanti.',
                'backUrl' => base_url('logout')
            ];
            return view('errors/custom_error', $data);
        }

        // Date for rekap (defaults to today)
        $rekapDate = $this->request->getGet('date') ?? $today;
        
        // Check if selected date is a holiday
        $holidayInfo = get_holiday_info($rekapDate);

        // Common Data: Rekap Ketidakhadiran (needed for Admin, Guru & Kepsek)
        $absenceRekap = [];
        if ($roleId == 1 || $roleId == 3 || $roleId == 2) {
            $absences = $db->table('attendances a')
                ->select('c.name as class_name, s.name as student_name, a.status')
                ->join('students s', 's.id = a.student_id')
                ->join('classes c', 'c.id = a.class_id')
                ->where('a.date', $rekapDate)
                ->whereIn('a.status', ['S', 'I', 'A'])
                ->orderBy('c.name', 'ASC')
                ->orderBy('s.name', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($absences as $abs) {
                $absenceRekap[$abs['class_name']][] = [
                    'name' => $abs['student_name'],
                    'status' => $abs['status'],
                ];
            }
        }

        switch ($roleId) {
            case 1: // Admin
                $stats = [
                    'total_students' => $db->table('students')->countAllResults(),
                    'total_teachers' => $db->table('teachers')->countAllResults(),
                    'total_classes' => $db->table('classes')->where('is_active', 1)->countAllResults(),
                    'active_students' => $db->table('student_records')
                        ->where('academic_year_id', $activeYear['id'])
                        ->where('status', 'aktif')
                        ->countAllResults(),
                    'active_teachers' => $db->table('teachers')->countAllResults(), // Assume all teachers are active if no status column
                    'total_subjects' => $db->table('subjects')->countAllResults(),
                ];

                $upcomingAgendas = $db->table('agendas a')
                    ->select('a.*, c.name as class_name')
                    ->join('classes c', 'c.id = a.class_id', 'left')
                    ->where('a.date >=', $today)
                    ->orderBy('a.date', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

                // Get class filter from request
                $selectedClassId = $this->request->getGet('class_id');
                
                // Get schedule for the selected date (or today)
                $dayOfWeek = date('N', strtotime($rekapDate));
                $filterParams = [];
                if ($selectedClassId) {
                    $filterParams['class_id'] = $selectedClassId;
                }
                $todaySchedule = (new ScheduleModel())->getScheduleForDay($dayOfWeek, $filterParams, $activeYear['id']);

                // Generate real system notifications
                $systemNotifications = [];
                
                // Check for users with must_change_password flag
                $usersNeedPasswordChange = $db->table('users')
                    ->where('must_change_password', 1)
                    ->countAllResults();
                if ($usersNeedPasswordChange > 0) {
                    $systemNotifications[] = [
                        'type' => 'warning',
                        'icon' => 'exclamation-triangle',
                        'title' => 'Pengguna Perlu Ganti Password',
                        'message' => $usersNeedPasswordChange . ' pengguna perlu mengganti password mereka',
                    ];
                }
                
                // Check for classes without homeroom teacher
                $classesWithoutTeacher = $db->table('classes')
                    ->where('teacher_id IS NULL')
                    ->orWhere('teacher_id', 0)
                    ->countAllResults();
                if ($classesWithoutTeacher > 0) {
                    $systemNotifications[] = [
                        'type' => 'info',
                        'icon' => 'info-circle',
                        'title' => 'Kelas Tanpa Wali Kelas',
                        'message' => $classesWithoutTeacher . ' kelas belum memiliki wali kelas',
                    ];
                }
                
                // Check for students without active class
                $studentsWithoutClass = $db->table('students s')
                    ->select('s.id')
                    ->join('student_records sr', 'sr.student_id = s.id AND sr.status = "aktif"', 'left')
                    ->where('sr.id IS NULL')
                    ->countAllResults();
                if ($studentsWithoutClass > 0) {
                    $systemNotifications[] = [
                        'type' => 'warning',
                        'icon' => 'exclamation-triangle',
                        'title' => 'Siswa Tanpa Kelas Aktif',
                        'message' => $studentsWithoutClass . ' siswa belum memiliki kelas aktif di tahun ajaran ini',
                    ];
                }
                
                // Check for academic year ending soon (within 30 days)
                if ($activeYear) {
                    $endDate = strtotime($activeYear['end_date']);
                    $todayTimestamp = strtotime($today);
                    $daysRemaining = floor(($endDate - $todayTimestamp) / 86400);
                    
                    if ($daysRemaining > 0 && $daysRemaining <= 30) {
                        $systemNotifications[] = [
                            'type' => 'info',
                            'icon' => 'calendar-event',
                            'title' => 'Tahun Ajaran Akan Berakhir',
                            'message' => 'Tahun ajaran ' . $activeYear['year'] . ' akan berakhir dalam ' . $daysRemaining . ' hari',
                        ];
                    }
                }
                
                // Check for license expiration (if license exists)
                $licenseModel = new \App\Models\LicenseModel();
                $license = $licenseModel->first();
                if ($license && !empty($license['expires_at'])) {
                    $expiresAt = strtotime($license['expires_at']);
                    $todayTimestamp = strtotime($today);
                    $daysRemaining = floor(($expiresAt - $todayTimestamp) / 86400);
                    
                    if ($daysRemaining > 0 && $daysRemaining <= 30) {
                        $systemNotifications[] = [
                            'type' => 'warning',
                            'icon' => 'shield-exclamation',
                            'title' => 'Lisensi Akan Berakhir',
                            'message' => 'Lisensi aplikasi akan berakhir dalam ' . $daysRemaining . ' hari',
                        ];
                    } elseif ($daysRemaining <= 0) {
                        $systemNotifications[] = [
                            'type' => 'danger',
                            'icon' => 'shield-x',
                            'title' => 'Lisensi Telah Berakhir',
                            'message' => 'Lisensi aplikasi telah berakhir. Silakan perpanjang lisensi Anda',
                        ];
                    }
                }
                
                // If no notifications, add a success message
                if (empty($systemNotifications)) {
                    $systemNotifications[] = [
                        'type' => 'success',
                        'icon' => 'check-circle',
                        'title' => 'Sistem Berjalan Normal',
                        'message' => 'Tidak ada masalah yang terdeteksi pada sistem',
                    ];
                }

                // Get system activity data
                $systemActivity = [
                    'last_login' => null,
                    'online_users' => 0,
                    'today_requests' => 0,
                ];
                
                // Get last login time from current user session
                if (session()->has('login_time')) {
                    $systemActivity['last_login'] = session()->get('login_time');
                } else {
                    $systemActivity['last_login'] = date('Y-m-d H:i:s');
                }
                
                // Count online users (users with last_activity in last 15 minutes)
                $fifteenMinutesAgo = date('Y-m-d H:i:s', strtotime('-15 minutes'));
                
                // Check if last_activity column exists
                $fields = $db->getFieldNames('users');
                if (in_array('last_activity', $fields)) {
                    $systemActivity['online_users'] = $db->table('users')
                        ->where('last_activity >=', $fifteenMinutesAgo)
                        ->countAllResults();
                } else {
                    // Fallback: count all users
                    $systemActivity['online_users'] = $db->table('users')->countAllResults();
                }
                
                // Count today's activities (attendances + agendas + announcements created today)
                $todayStart = date('Y-m-d 00:00:00');
                $todayEnd = date('Y-m-d 23:59:59');
                
                $attendanceCount = $db->table('attendances')
                    ->where('date', $today)
                    ->countAllResults();
                    
                $agendaCount = $db->table('agendas')
                    ->where('created_at >=', $todayStart)
                    ->where('created_at <=', $todayEnd)
                    ->countAllResults();
                    
                $announcementCount = $db->table('announcements')
                    ->where('created_at >=', $todayStart)
                    ->where('created_at <=', $todayEnd)
                    ->countAllResults();
                
                $systemActivity['today_requests'] = $attendanceCount + $agendaCount + $announcementCount;

                // --- Statistik Siswa per Kelas ---
                $statsAcademicYearId = (int)($this->request->getGet('stats_year_id') ?? $activeYear['id']);
                $allAcademicYears    = (new \App\Models\AcademicYearModel())->orderBy('start_date', 'DESC')->findAll();
                $statsAcademicYear   = (new \App\Models\AcademicYearModel())->find($statsAcademicYearId) ?? $activeYear;
                $studentStatsPerClass = $this->getStudentStatsPerClass($db, $statsAcademicYearId);

                return view('dashboard/admin_modern', [
                    'user'                 => $user,
                    'stats'                => $stats,
                    'absenceRekap'         => $absenceRekap,
                    'upcomingAgendas'      => $upcomingAgendas,
                    'todaySchedule'        => $todaySchedule,
                    'today'                => $today,
                    'rekapDate'            => $rekapDate,
                    'selectedClassId'      => $selectedClassId,
                    'holidayInfo'          => $holidayInfo,
                    'systemNotifications'  => $systemNotifications,
                    'systemActivity'       => $systemActivity,
                    'studentStatsPerClass' => $studentStatsPerClass,
                    'academicYears'        => $allAcademicYears,
                    'statsAcademicYearId'  => $statsAcademicYearId,
                    'statsActiveYear'      => $statsAcademicYear,
                ]);

            case 2: // Kepala Sekolah
                $stats = [
                    'total_students' => $db->table('students')->countAllResults(),
                    'total_teachers' => $db->table('teachers')->countAllResults(),
                    'total_classes' => $db->table('classes')->where('is_active', 1)->countAllResults(),
                ];

                $upcomingAgendas = $db->table('agendas a')
                    ->select('a.*, c.name as class_name')
                    ->join('classes c', 'c.id = a.class_id', 'left')
                    ->where('a.date >=', $today)
                    ->orderBy('a.date', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

                // Get class filter from request
                $selectedClassId = $this->request->getGet('class_id');
                
                // Get schedule for the selected date (or today)
                $dayOfWeek = date('N', strtotime($rekapDate));
                $filterParams = [];
                if ($selectedClassId) {
                    $filterParams['class_id'] = $selectedClassId;
                }
                $todaySchedule = (new ScheduleModel())->getScheduleForDay($dayOfWeek, $filterParams, $activeYear['id']);

                // --- Statistik Siswa per Kelas ---
                $statsAcademicYearId = (int)($this->request->getGet('stats_year_id') ?? $activeYear['id']);
                $allAcademicYears    = (new \App\Models\AcademicYearModel())->orderBy('start_date', 'DESC')->findAll();
                $statsAcademicYear   = (new \App\Models\AcademicYearModel())->find($statsAcademicYearId) ?? $activeYear;
                $studentStatsPerClass = $this->getStudentStatsPerClass($db, $statsAcademicYearId);

                return view('dashboard/kepsek', [
                    'user'                 => $user,
                    'stats'                => $stats,
                    'absenceRekap'         => $absenceRekap,
                    'upcomingAgendas'      => $upcomingAgendas,
                    'todaySchedule'        => $todaySchedule,
                    'today'                => $today,
                    'rekapDate'            => $rekapDate,
                    'selectedClassId'      => $selectedClassId,
                    'holidayInfo'          => $holidayInfo,
                    'studentStatsPerClass' => $studentStatsPerClass,
                    'academicYears'        => $allAcademicYears,
                    'statsAcademicYearId'  => $statsAcademicYearId,
                    'statsActiveYear'      => $statsAcademicYear,
                ]);

            case 3: // Guru
                $teacherModel = new TeacherModel();
                $teacher = null;

                if (!empty($user['related_id'])) {
                    $teacher = $teacherModel->find($user['related_id']);
                }

                $upcomingAgendas = $db->table('agendas a')
                    ->select('a.*, c.name as class_name')
                    ->join('classes c', 'c.id = a.class_id', 'left')
                    ->where('a.date >=', $today)
                    ->groupStart()
                    ->where('a.created_by', $user['id'])
                    ->orWhere('a.is_public', 1)
                    ->groupEnd()
                    ->orderBy('a.date', 'ASC')
                    ->limit(5)
                    ->get()
                    ->getResultArray();

                // Check if teacher is Wali Kelas
                $waliClass = $db->table('classes')
                    ->where('teacher_id', $user['related_id'])
                    ->get()
                    ->getRowArray();

                // Calculate Student Stats for Wali Kelas
                $waliClassStats = null;
                if ($waliClass) {
                    $students = $db->table('student_records')
                        ->select('students.gender')
                        ->join('students', 'students.id = student_records.student_id')
                        ->where('student_records.class_id', $waliClass['id'])
                        ->where('student_records.academic_year_id', $activeYear['id'])
                        ->where('student_records.status', 'aktif')
                        ->get()
                        ->getResultArray();

                    $waliClassStats = [
                        'total' => count($students),
                        'L' => 0,
                        'P' => 0
                    ];
                    foreach ($students as $s) {
                        if ($s['gender'] == 'L')
                            $waliClassStats['L']++;
                        elseif ($s['gender'] == 'P')
                            $waliClassStats['P']++;
                    }
                }

                // Latest relevant announcement for teachers (from Admin/Kepsek)
                $latestAnnouncement = $db->table('announcements a')
                    ->select('a.*, u.fullname as creator_name')
                    ->join('users u', 'u.id = a.created_by', 'left')
                    ->where('u.role_id IN (1, 2)', null, false) // Only from Admin (1) or Kepsek (2)
                    ->groupStart()
                    ->like('a.target', 'guru', 'both')
                    ->orLike('a.target', 'all', 'both')
                    ->groupEnd()
                    ->orderBy('a.created_at', 'DESC')
                    ->limit(1)
                    ->get()
                    ->getRowArray();

                $scheduleModel = new ScheduleModel();
                $dayOfWeek = date('N', strtotime($rekapDate));
                $activeYearId = (new \App\Models\AcademicYearModel())->getActiveYear()['id'];

                // Schedule for Wali Kelas
                $waliClassSchedule = [];
                if ($waliClass) {
                    $waliClassSchedule = $scheduleModel->getScheduleForDay($dayOfWeek, ['class_id' => $waliClass['id']], $activeYearId);
                }

                // Personal teacher schedule
                $personalSchedule = $scheduleModel->getScheduleForDay($dayOfWeek, ['teacher_id' => $user['related_id']], $activeYearId);
                
                // Get teacher statistics
                $teacherStats = [
                    'total_sessions_today' => count($personalSchedule),
                    'total_jp_today' => 0,
                    'unique_classes' => 0,
                    'unique_subjects' => 0,
                ];
                
                // Calculate total JP (jam pelajaran) menggunakan helper
                foreach ($personalSchedule as $item) {
                    $teacherStats['total_jp_today'] += hitung_jp_dari_waktu($item['start_time'], $item['end_time']);
                }
                
                // Count unique classes and subjects
                $uniqueClasses = [];
                $uniqueSubjects = [];
                foreach ($personalSchedule as $item) {
                    if (isset($item['class_id'])) {
                        $uniqueClasses[$item['class_id']] = true;
                    }
                    if (isset($item['subject_id'])) {
                        $uniqueSubjects[$item['subject_id']] = true;
                    }
                }
                $teacherStats['unique_classes'] = count($uniqueClasses);
                $teacherStats['unique_subjects'] = count($uniqueSubjects);

                return view('dashboard/guru_fixed', [
                    'teacher' => $teacher,
                    'user' => $user,
                    'absenceRekap' => $absenceRekap,
                    'upcomingAgendas' => $upcomingAgendas,
                    'waliClassSchedule' => $waliClassSchedule,
                    'personalSchedule' => $personalSchedule,
                    'today' => $today,
                    'rekapDate' => $rekapDate,
                    'waliClass' => $waliClass,
                    'waliClassStats' => $waliClassStats,
                    'teacherStats' => $teacherStats,
                    'latestAnnouncement' => $latestAnnouncement,
                    'holidayInfo' => $holidayInfo,
                ]);

            case 4: // Orang Tua
                $data = $this->getStudentDashboardData($user, $db, $today, $rekapDate);
                return view('dashboard/siswa_simple', $data);

            case 5: // Siswa
                $data = $this->getStudentDashboardData($user, $db, $today, $rekapDate);
                return view('dashboard/siswa_simple', $data);

            case 6: // Kontributor
                $stats = [
                    'total_articles' => $db->table('landing_articles')->countAllResults(),
                    'total_activities' => $db->table('landing_activities')->countAllResults(),
                ];

                return view('dashboard/kontributor', [
                    'user' => $user,
                    'stats' => $stats,
                ]);

            case 7: // Staf
                $stats = [
                    'total_teachers'  => $db->table('teachers')->countAllResults(),
                    'total_students'  => $db->table('students')->countAllResults(),
                    'total_classes'   => $db->table('classes')->where('is_active', 1)->countAllResults(),
                    'active_students' => $db->table('student_records')
                        ->where('academic_year_id', $activeYear['id'])
                        ->where('status', 'aktif')->countAllResults(),
                ];
                $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
                // --- Statistik Siswa per Kelas ---
                $statsAcademicYearId = (int)($this->request->getGet('stats_year_id') ?? $activeYear['id']);
                $allAcademicYears    = (new \App\Models\AcademicYearModel())->orderBy('start_date', 'DESC')->findAll();
                $statsAcademicYear   = (new \App\Models\AcademicYearModel())->find($statsAcademicYearId) ?? $activeYear;
                $studentStatsPerClass = $this->getStudentStatsPerClass($db, $statsAcademicYearId);

                return view('dashboard/staf', [
                    'user'                 => $user,
                    'stats'                => $stats,
                    'activeYear'           => $activeYear,
                    'studentStatsPerClass' => $studentStatsPerClass,
                    'academicYears'        => $allAcademicYears,
                    'statsAcademicYearId'  => $statsAcademicYearId,
                    'statsActiveYear'      => $statsAcademicYear,
                ]);

            default:
                return redirect()->to('/login');
        }
    }

    /**
     * Get student statistics per class (total, male, female) for a given academic year.
     */
    private function getStudentStatsPerClass($db, $academicYearId)
    {
        return $db->table('student_records sr')
            ->select('c.name as class_name, c.teacher_id,
                      COUNT(sr.id) as total,
                      SUM(CASE WHEN s.gender = "L" THEN 1 ELSE 0 END) as total_l,
                      SUM(CASE WHEN s.gender = "P" THEN 1 ELSE 0 END) as total_p,
                      t.name as teacher_name')
            ->join('students s', 's.id = sr.student_id')
            ->join('classes c', 'c.id = sr.class_id')
            ->join('teachers t', 't.id = c.teacher_id', 'left')
            ->where('sr.academic_year_id', $academicYearId)
            ->where('sr.status', 'aktif')
            ->groupBy('sr.class_id')
            ->orderBy('c.name', 'ASC')
            ->get()
            ->getResultArray();
    }

    private function getStudentDashboardData($user, $db, $today, $rekapDate = null)
    {
        helper('holiday'); // Load holiday helper
        
        if (!$rekapDate) $rekapDate = $today;
        $studentId = $user['student_id'] ?? null;
        
        // Check if selected date is a holiday
        $holidayInfo = get_holiday_info($rekapDate);

        // Get student info
        $student = $db->table('students')->where('id', $studentId)->get()->getRowArray();

        // Get student's class
        $record = $db->table('student_records sr')
            ->select('sr.class_id, c.name as class_name')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->where('sr.student_id', $studentId)
            ->where('sr.status', 'aktif')
            ->orderBy('sr.id', 'DESC')
            ->get()->getRowArray();

        $classId = $record['class_id'] ?? null;
        $className = $record['class_name'] ?? null;

        $upcomingAgendas = [];
        if ($classId) {
            $upcomingAgendas = $db->table('agendas a')
                ->select('a.*, c.name as class_name')
                ->join('classes c', 'c.id = a.class_id', 'left')
                ->where('a.date >=', $today)
                ->groupStart()
                ->where('a.class_id', $classId)
                ->orWhere('a.is_public', 1)
                ->groupEnd()
                ->orderBy('a.date', 'ASC')
                ->limit(5)
                ->get()
                ->getResultArray();
        }

        // Monthly attendance summary logic
        $currentMonth = date('Y-m');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-d'); // limit until today

        $attendances = $db->table('attendances')
            ->where('student_id', $studentId)
            ->where('date >=', $monthStart)
            ->where('date <=', $monthEnd)
            ->get()->getResultArray();

        $attMap = [];
        foreach ($attendances as $a)
            $attMap[$a['date']] = $a['status'];

        $holidays = $db->table('holidays')
            ->where('date >=', $monthStart)
            ->where('date <=', $monthEnd)
            ->get()->getResultArray();
        $holidayMap = [];
        foreach ($holidays as $h)
            $holidayMap[$h['date']] = true;

        // Get school_days from active academic year
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        $schoolDays = (int) ($activeYear['school_days'] ?? 5);

        $attSummary = ['H' => 0, 'S' => 0, 'I' => 0, 'A' => 0];
        $activeDays = 0;
        for ($d = strtotime($monthStart); $d <= strtotime($monthEnd); $d = strtotime('+1 day', $d)) {
            $dateStr = date('Y-m-d', $d);
            $dayNum = (int) date('N', $d);
            
            // Skip weekends based on school_days setting
            if ($schoolDays == 5 && $dayNum >= 6) {
                continue; // Skip Saturday and Sunday for 5-day school
            } elseif ($schoolDays == 6 && $dayNum == 7) {
                continue; // Skip only Sunday for 6-day school
            }
            
            // Skip holidays
            if (isset($holidayMap[$dateStr]))
                continue;

            $activeDays++;
            $status = $attMap[$dateStr] ?? 'H';
            if (isset($attSummary[$status]))
                $attSummary[$status]++;
            else
                $attSummary['H']++;
        }

        $attendanceRate = $activeDays > 0 ? round(($attSummary['H'] / $activeDays) * 100) : 100;

        // Latest relevant announcement
        $latestAnnouncement = $db->table('announcements a')
            ->select('a.*, u.fullname as creator_name')
            ->join('users u', 'u.id = a.created_by', 'left')
            ->where("FIND_IN_SET('siswa', a.target) > 0")
            ->groupStart()
            ->where('a.class_id', $classId)
            ->orWhere('a.class_id', null)
            ->groupEnd()
            ->orderBy('a.created_at', 'DESC')
            ->limit(1)
            ->get()
            ->getRowArray();

        return [
            'user' => $user,
            'student' => $student,
            'className' => $className,
            'upcomingAgendas' => $upcomingAgendas,
            'attSummary' => $attSummary,
            'attendanceRate' => $attendanceRate,
            'latestAnnouncement' => $latestAnnouncement,
            'rekapDate' => $rekapDate,
            'todaySchedule' => (new ScheduleModel())->getScheduleForDay(date('N', strtotime($rekapDate)), ['class_id' => $classId], (new \App\Models\AcademicYearModel())->getActiveYear()['id']),
            'holidayInfo' => $holidayInfo,
        ];
    }
}
