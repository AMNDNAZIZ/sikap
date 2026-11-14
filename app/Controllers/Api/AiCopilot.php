<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Libraries\AiActionRegistry;
use CodeIgniter\API\ResponseTrait;

class AiCopilot extends BaseController
{
    use ResponseTrait;

    /**
     * Dapatkan daftar seluruh aksi yang diizinkan untuk pengguna yang sedang login
     */
    public function getActions()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->failUnauthorized('Sesi Anda telah berakhir.');
        }

        $actions = AiActionRegistry::getUserActions($user);

        return $this->respond([
            'status'    => 'success',
            'count'     => count($actions),
            'actions'   => $actions,
            'csrf_hash' => csrf_hash(),
        ]);
    }

    /**
     * Dapatkan data Morning Briefing dan teks sambutan suara untuk Guru
     */
    public function morningBriefing()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->failUnauthorized('Sesi Anda telah berakhir.');
        }

        // 🚀 SHARED HOSTING OPTIMIZATION: Lepaskan session lock sesegera mungkin
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);
        $db = db_connect();

        // Cek jika ada request preview sebagai Guru (untuk mempermudah Admin menguji suara guru)
        $previewTeacher = $this->request->getGet('preview') === 'teacher';

        // Jika teacher_id belum ada di sesi tetapi role adalah Guru, cari berdasarkan user_id
        if ($teacherId <= 0 && $roleId === 3) {
            $tRow = $db->table('teachers')->where('user_id', (int) ($user['id'] ?? 0))->get()->getRowArray();
            if ($tRow) {
                $teacherId = (int) $tRow['id'];
            }
        }

        // Jika Admin / Penguji meminta preview suara Guru, ambil satu profil guru contoh
        if ($previewTeacher && $teacherId <= 0) {
            $sampleTeacher = $db->table('teachers')->where('is_active', 1)->orderBy('id', 'ASC')->get()->getRowArray();
            if (!$sampleTeacher) {
                $sampleTeacher = $db->table('teachers')->orderBy('id', 'ASC')->get()->getRowArray();
            }
            if ($sampleTeacher) {
                $teacherId = (int) $sampleTeacher['id'];
            }
        }

        // Tentukan salam waktu berdasarkan jam login
        $hour = (int) date('G');
        if ($hour >= 5 && $hour < 11) {
            $timeGreeting = 'Selamat pagi';
        } elseif ($hour >= 11 && $hour < 15) {
            $timeGreeting = 'Selamat siang';
        } elseif ($hour >= 15 && $hour < 18) {
            $timeGreeting = 'Selamat sore';
        } else {
            $timeGreeting = 'Selamat malam';
        }

        $todayDate = date('Y-m-d');
        // Mendukung simulasi hari (misal ?day=1 untuk menguji hari Senin yang memiliki jadwal mengajar)
        $simDay = (int) $this->request->getGet('day');
        if ($simDay >= 1 && $simDay <= 7) {
            $dayOfWeek = $simDay;
        } else {
            $dayOfWeek = (int) date('N'); // 1 = Senin, ..., 7 = Minggu
        }
        $daysIndo = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        $dayName = $daysIndo[$dayOfWeek] ?? 'Hari Ini';
        $isWeekend = ($dayOfWeek === 6 || $dayOfWeek === 7);

        // =========================================================================
        // SKENARIO 1: BRIEFING PERSONAL UNTUK GURU (Role 3 atau User dengan Teacher ID)
        // =========================================================================
        if ($teacherId > 0 || $roleId === 3) {
            // 1. Ambil data profil guru
            $teacher = $db->table('teachers')->where('id', $teacherId)->get()->getRowArray();
            $rawName = trim($teacher['name'] ?? ($user['fullname'] ?? $user['name'] ?? 'Bapak/Ibu Guru'));
            $gender = strtoupper(trim((string) ($teacher['gender'] ?? '')));

            // Tentukan sebutan sopan (Bapak / Ibu)
            $honorific = 'Bapak atau Ibu';
            if ($gender === 'P' || $gender === 'PEREMPUAN') {
                $honorific = 'Ibu';
            } elseif ($gender === 'L' || $gender === 'LAKI-LAKI') {
                $honorific = 'Bapak';
            } else {
                if (preg_match('/\b(dra|hj|siti|nur|anita|dewi|tri|retno|endang|wati|lestari|ida|ayu)\b/i', $rawName)) {
                    $honorific = 'Ibu';
                } elseif (preg_match('/\b(drs|h|muhammad|ahmad|bambang|agus|joko|budi|sugeng|hendro|ali|supri)\b/i', $rawName)) {
                    $honorific = 'Bapak';
                }
            }

            $greetingTitle = "{$timeGreeting}, {$honorific} {$rawName}!";

            // 2. Ambil jadwal mengajar hari ini
            $schedules = $db->table('schedules')
                ->select('schedules.start_time, schedules.end_time, subjects.name as subject_name, classes.name as class_name')
                ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
                ->join('classes', 'classes.id = schedules.class_id', 'left')
                ->where('schedules.teacher_id', $teacherId)
                ->where('schedules.day_of_week', $dayOfWeek)
                ->orderBy('schedules.start_time', 'ASC')
                ->get()
                ->getResultArray();

            // 3. Ambil agenda ujian CBT hari ini jika ada
            $cbtAgendas = [];
            try {
                $cbtAgendas = $db->table('cbt_test_status')
                    ->select('cbt_exam_names.name as exam_name, cbt_bank_soal.subject_name')
                    ->join('cbt_bank_soal', 'cbt_bank_soal.id = cbt_test_status.bank_id', 'left')
                    ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                    ->where('cbt_test_status.is_active', 1)
                    ->where('cbt_bank_soal.teacher_id', $teacherId)
                    ->like('cbt_test_status.start_time', $todayDate, 'after')
                    ->get()
                    ->getResultArray();
            } catch (\Throwable $e) {}

            // 4. Susun Naskah Tutur (Spoken Script)
            $spokenParts = [];
            $spokenParts[] = "{$timeGreeting}, {$honorific} {$rawName}.";

            if (!empty($schedules)) {
                $count = count($schedules);
                $scheduleNarratives = [];
                foreach ($schedules as $s) {
                    $cls = $s['class_name'] ?? 'Kelas';
                    $sub = $s['subject_name'] ?? 'Mata Pelajaran';
                    $time = substr($s['start_time'] ?? '', 0, 5);
                    $scheduleNarratives[] = "{$sub} di kelas {$cls} pukul {$time}";
                }

                if ($count === 1) {
                    $spokenParts[] = "Hari ini Anda memiliki satu jadwal mengajar, yaitu " . $scheduleNarratives[0] . ".";
                } else {
                    $last = array_pop($scheduleNarratives);
                    $spokenParts[] = "Hari ini Anda memiliki {$count} jadwal mengajar, yaitu " . implode(', ', $scheduleNarratives) . ", dan {$last}.";
                }
            } else {
                if ($isWeekend) {
                    $spokenParts[] = "Hari ini adalah hari {$dayName} (libur akhir pekan), sehingga tidak ada jadwal mengajar di kelas. Selamat berlibur, beristirahat, dan menikmati waktu berkualitas bersama keluarga tercinta.";
                } else {
                    $spokenParts[] = "Hari ini Anda tidak memiliki jadwal mengajar di kelas. Anda dapat memanfaatkannya untuk menyusun modul ajar, memeriksa tugas siswa, atau kegiatan akademik lainnya.";
                }
            }

            if (!empty($cbtAgendas)) {
                $agendaNames = [];
                foreach ($cbtAgendas as $ag) {
                    $agendaNames[] = ($ag['exam_name'] ?? 'Ujian') . " " . ($ag['subject_name'] ?? '');
                }
                $spokenParts[] = "Anda juga memiliki agenda pelaksanaan ujian online CBT yaitu " . implode(' dan ', $agendaNames) . ".";
            }

            if (!$isWeekend) {
                $spokenParts[] = "Selamat beraktivitas, semoga proses pembelajaran hari ini berjalan lancar dan menyenangkan bersama peserta didik.";
            }

            $spokenText = implode(' ', $spokenParts);

            return $this->respond([
                'status'         => 'success',
                'can_brief'      => true,
                'briefing_type'  => 'teacher',
                'date'           => $todayDate,
                'day_name'       => $dayName,
                'is_weekend'     => $isWeekend,
                'teacher_id'     => $teacherId,
                'greeting'       => $greetingTitle,
                'spoken_text'    => $spokenText,
                'schedules'      => $schedules,
                'agendas'        => $cbtAgendas,
                'schedule_count' => count($schedules),
                'csrf_hash'      => csrf_hash(),
            ]);
        }

        // =========================================================================
        // SKENARIO 2: BRIEFING EKSEKUTIF UNTUK KEPALA SEKOLAH (Role 2), ADMIN (Role 1), STAF (Role 7)
        // =========================================================================
        if (in_array($roleId, [1, 2, 7], true)) {
            $userTitle = 'Administrator ESPATA';
            if ($roleId === 2) {
                $userTitle = 'Bapak atau Ibu Kepala Sekolah';
            } elseif ($roleId === 7) {
                $userTitle = 'Bapak atau Ibu Staf Manajemen';
            }

            $greetingTitle = "{$timeGreeting}, {$userTitle}!";

            // Hitung statistik operasional hari ini
            $totalSchedules = 0;
            $totalTeachers = 0;
            $totalCbt = 0;

            try {
                $totalSchedules = $db->table('schedules')->where('day_of_week', $dayOfWeek)->countAllResults();
                $totalTeachers = $db->table('schedules')
                    ->where('day_of_week', $dayOfWeek)
                    ->select('teacher_id')
                    ->distinct()
                    ->countAllResults();
            } catch (\Throwable $e) {}

            try {
                $totalCbt = $db->table('cbt_test_status')
                    ->where('is_active', 1)
                    ->like('start_time', $todayDate, 'after')
                    ->countAllResults();
            } catch (\Throwable $e) {}

            $spokenParts = [];
            $spokenParts[] = "{$timeGreeting}, {$userTitle}.";
            $spokenParts[] = "Sistem akademik ESPATA siap beroperasi secara optimal.";
            
            if ($totalSchedules > 0) {
                $spokenParts[] = "Hari ini tercatat {$totalSchedules} jadwal pembelajaran di kelas yang diampu oleh {$totalTeachers} guru.";
            } else {
                if ($isWeekend) {
                    $spokenParts[] = "Hari ini adalah hari {$dayName} (hari libur akhir pekan), sehingga tidak ada agenda pembelajaran reguler di kelas.";
                } else {
                    $spokenParts[] = "Hari ini tidak ada jadwal pembelajaran reguler yang tercatat.";
                }
            }

            if ($totalCbt > 0) {
                $spokenParts[] = "Terdapat juga {$totalCbt} agenda ujian online CBT yang aktif hari ini.";
            }

            $spokenParts[] = "Semoga seluruh aktivitas akademik dan operasional sekolah hari ini berlangsung tertib, aman, dan lancar.";

            $spokenText = implode(' ', $spokenParts);

            return $this->respond([
                'status'         => 'success',
                'can_brief'      => true,
                'briefing_type'  => 'executive',
                'date'           => $todayDate,
                'day_name'       => $dayName,
                'is_weekend'     => $isWeekend,
                'role_id'        => $roleId,
                'greeting'       => $greetingTitle,
                'spoken_text'    => $spokenText,
                'stats'          => [
                    'schedules' => $totalSchedules,
                    'teachers'  => $totalTeachers,
                    'cbt'       => $totalCbt,
                ],
                'schedules'      => [],
                'schedule_count' => $totalSchedules,
                'csrf_hash'      => csrf_hash(),
            ]);
        }

        // Peran lainnya (Siswa / Orang Tua) tidak memicu briefing harian
        return $this->respond([
            'status'    => 'not_applicable',
            'can_brief' => false,
            'message'   => 'Morning briefing dipersonalisasi untuk Pendidik dan Manajemen Sekolah.',
        ]);
    }

    /**
     * Proses perintah bahasa alami / pertanyaan melalui AI Copilot
     */
    public function ask()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->failUnauthorized('Sesi Anda telah berakhir.');
        }

        // 🚀 SHARED HOSTING OPTIMIZATION: Lepaskan session lock sesegera mungkin
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        // 🛡️ FLOOD & RATE LIMIT PROTECTION (Maksimal 30 request / menit per user)
        $throttler = service('throttler');
        $throttleKey = 'ai_copilot_' . ($user['id'] ?? $this->request->getIPAddress());
        if ($throttler->check($throttleKey, 30, MINUTE) === false) {
            return $this->respond([
                'status'      => 'rate_limited',
                'explanation' => 'Anda mengirim perintah terlalu cepat. Harap tunggu beberapa saat sebelum mencoba lagi.',
                'csrf_hash'   => csrf_hash(),
            ], 429);
        }

        $json = $this->request->getJSON(true) ?? [];
        $rawQuery = (string) ($this->request->getPost('query') ?? $json['query'] ?? '');
        
        // Sanitasi input: Maksimal 300 karakter, hapus kontrol karakter berbahaya
        $query = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', mb_substr($rawQuery, 0, 300)));

        if (empty($query)) {
            return $this->fail('Pertanyaan atau perintah tidak boleh kosong.', 400);
        }

        // 1. Cek apakah pengguna meminta data langsung (Direct Live Data Queries)
        $dataWidget = $this->tryExecuteLiveQuery($query, $user);
        if ($dataWidget !== null) {
            $this->logInteraction($user, $query, $dataWidget['intent'] ?? 'LIVE_DATA', null, 'live_data');
            return $this->respond(array_merge([
                'status'    => 'success',
                'csrf_hash' => csrf_hash(),
            ], $dataWidget));
        }

        // 2. Cek apakah pengguna menanyakan panduan penggunaan fitur aplikasi (Built-in Knowledge Base)
        $roleId = (int) ($user['role_id'] ?? 0);
        $guide = \App\Libraries\EspataFeatureKnowledge::findGuide($query, $roleId);
        if ($guide !== null) {
            $this->logInteraction($user, $query, 'FEATURE_GUIDE', $guide['id'], 'knowledge_base');
            return $this->respond([
                'status'         => 'success',
                'source'         => 'knowledge_base',
                'query'          => $query,
                'widget_type'    => 'guide_card',
                'explanation'    => $guide['summary'],
                'widget_data'    => [
                    'guide_id' => $guide['id'],
                    'title'    => $guide['title'],
                    'category' => $guide['category'],
                    'steps'    => $guide['steps'],
                ],
                'matched_action' => [
                    'id'       => $guide['id'],
                    'title'    => $guide['action_label'],
                    'url'      => $guide['action_url'],
                    'icon'     => $guide['action_icon'],
                    'category' => $guide['category'],
                ],
                'csrf_hash'      => csrf_hash(),
            ]);
        }

        // 3. Ambil daftar aksi yang diizinkan untuk pengguna ini
        $allowedActions = AiActionRegistry::getUserActions($user);

        // 4. Ambil konfigurasi AI Key pengguna jika ada
        $aiConfig = $this->resolveAiKey($user);

        // 5. Jika API Key Gemini tersedia, gunakan Gemini Flash untuk pemahaman bahasa alami
        if (!empty($aiConfig['api_key'])) {
            $aiResult = $this->callGeminiNlu($query, $allowedActions, $aiConfig['api_key']);
            if ($aiResult['success'] && !empty($aiResult['matched_action'])) {
                $matched = $aiResult['matched_action'];
                $this->logInteraction($user, $query, $matched['category'] ?? 'NLU', $matched['id'] ?? null, 'gemini');

                return $this->respond([
                    'status'         => 'success',
                    'source'         => 'gemini',
                    'query'          => $query,
                    'explanation'    => $aiResult['explanation'],
                    'matched_action' => $matched,
                    'suggestions'    => $aiResult['suggestions'] ?? [],
                    'ai_content'     => $aiResult['ai_content'] ?? null,
                    'csrf_hash'      => csrf_hash(),
                ]);
            }
        }

        // 5. Fallback: Smart Local Intent Matcher (Berjalan di PHP murni, 0 ms, tanpa kuota API)
        $localResult = $this->matchLocally($query, $allowedActions);
        $matched = $localResult['matched_action'];
        $this->logInteraction($user, $query, $matched['category'] ?? 'LOCAL', $matched['id'] ?? null, 'local');

        return $this->respond([
            'status'         => 'success',
            'source'         => 'local',
            'query'          => $query,
            'explanation'    => $localResult['explanation'],
            'matched_action' => $matched,
            'suggestions'    => $localResult['suggestions'],
            'csrf_hash'      => csrf_hash(),
        ]);
    }

    /**
     * Eksekusi kueri data real-time langsung ke database berdasarkan hak akses
     */
    protected function tryExecuteLiveQuery(string $query, array $user): ?array
    {
        $q = strtolower($query);
        $roleId = (int) ($user['role_id'] ?? 0);
        $teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);
        $db = db_connect();

        // A. JADWAL MENGAJAR HARI INI (GURU)
        if ($roleId === 3 && $teacherId > 0 && (strpos($q, 'jadwal') !== false || strpos($q, 'jam mengajar') !== false)) {
            $dayOfWeek = (int) date('N'); // 1 = Senin, ..., 7 = Minggu
            $daysIndo = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
            $dayName = $daysIndo[$dayOfWeek] ?? 'Hari Ini';

            try {
                $schedules = $db->table('schedules')
                    ->select('schedules.start_time, schedules.end_time, subjects.name as subject_name, classes.name as class_name')
                    ->join('subjects', 'subjects.id = schedules.subject_id', 'left')
                    ->join('classes', 'classes.id = schedules.class_id', 'left')
                    ->where('schedules.teacher_id', $teacherId)
                    ->where('schedules.day_of_week', $dayOfWeek)
                    ->orderBy('schedules.start_time', 'ASC')
                    ->get()
                    ->getResultArray();

                return [
                    'source'      => 'database',
                    'intent'      => 'JADWAL_GURU',
                    'query'       => $query,
                    'widget_type' => 'schedule_card',
                    'explanation' => !empty($schedules) 
                        ? "Berikut jadwal mengajar Anda hari ini ({$dayName}, " . date('d M Y') . "):" 
                        : "Anda tidak memiliki jadwal mengajar hari ini ({$dayName}). Selamat beristirahat atau mempersiapkan perangkat ajar!",
                    'widget_data' => [
                        'day'       => $dayName,
                        'date'      => date('d F Y'),
                        'schedules' => $schedules,
                    ],
                    'matched_action' => [
                        'id'       => 'action_jadwal_full',
                        'title'    => 'Lihat Jadwal Mengajar Lengkap',
                        'url'      => site_url('admin/schedules'),
                        'icon'     => 'bi bi-calendar3',
                        'category' => 'Akademik'
                    ]
                ];
            } catch (\Throwable $e) {}
        }

        // B. REKAP KEHADIRAN GURU HARI INI (KEPSEK & ADMIN)
        if (($roleId === 1 || $roleId === 2) && (strpos($q, 'absen guru') !== false || strpos($q, 'kehadiran guru') !== false || strpos($q, 'rekap guru') !== false)) {
            try {
                $today = date('Y-m-d');
                $totalTeachers = $db->table('teachers')->where('is_active', 1)->countAllResults();
                $thCount = $db->table('teacher_attendances')
                    ->where('date', $today)
                    ->where('status', 'TH')
                    ->countAllResults();

                $hadirCount = max(0, $totalTeachers - $thCount);
                $persentase = $totalTeachers > 0 ? round(($hadirCount / $totalTeachers) * 100, 1) : 100;

                return [
                    'source'      => 'database',
                    'intent'      => 'REKAP_ABSEN_GURU',
                    'query'       => $query,
                    'widget_type' => 'stat_card',
                    'explanation' => "Rekap kehadiran guru hari ini (" . date('d M Y') . "): Persentase kehadiran {$persentase}%.",
                    'widget_data' => [
                        'label'      => 'Presensi Guru Hari Ini',
                        'total'      => $totalTeachers,
                        'hadir'      => $hadirCount,
                        'tidak_hadir'=> $thCount,
                        'percentage' => $persentase . '%',
                    ],
                    'matched_action' => [
                        'id'       => 'action_rekap_guru',
                        'title'    => 'Buka Laporan Presensi Guru Lengkap',
                        'url'      => site_url('admin/teacher-attendance/report'),
                        'icon'     => 'bi bi-person-check-fill',
                        'category' => 'Kepegawaian'
                    ]
                ];
            } catch (\Throwable $e) {}
        }

        // C. MONITORING UJIAN CBT AKTIF (ADMIN, KEPSEK, GURU)
        if (strpos($q, 'cbt') !== false || strpos($q, 'ujian aktif') !== false || strpos($q, 'sedang ujian') !== false) {
            try {
                $activeCount = $db->table('cbt_sessions')->where('status', 'active')->countAllResults();
                $activeTests = $db->table('cbt_tests')
                    ->select('id, name, duration')
                    ->where('is_active', 1)
                    ->limit(3)
                    ->get()
                    ->getResultArray();

                return [
                    'source'      => 'database',
                    'intent'      => 'MONITORING_CBT',
                    'query'       => $query,
                    'widget_type' => 'cbt_card',
                    'explanation' => $activeCount > 0 
                        ? "Saat ini terdapat {$activeCount} siswa yang sedang aktif mengerjakan ujian CBT secara bersamaan." 
                        : "Tidak ada siswa yang sedang aktif mengerjakan ujian CBT saat ini.",
                    'widget_data' => [
                        'active_students' => $activeCount,
                        'tests'           => $activeTests,
                    ],
                    'matched_action' => [
                        'id'       => 'action_monitoring_cbt',
                        'title'    => 'Buka Aktivitas & Monitoring CBT',
                        'url'      => site_url('admin/cbt/aktivitas'),
                        'icon'     => 'bi bi-speedometer2',
                        'category' => 'Ujian Online'
                    ]
                ];
            } catch (\Throwable $e) {}
        }

        // D. PENCARIAN SISWA CEPAT (ADMIN, GURU, KEPSEK)
        if (preg_match('/(?:cari siswa|siswa bernama|data siswa)\s+([a-zA-Z0-9\s]{2,40})/i', $query, $matches)) {
            $searchName = trim($matches[1]);
            if (!empty($searchName)) {
                try {
                    $students = $db->table('students')
                        ->select('students.id, students.nisn, students.nama_lengkap, classes.name as class_name')
                        ->join('classes', 'classes.id = students.class_id', 'left')
                        ->like('students.nama_lengkap', $searchName)
                        ->limit(4)
                        ->get()
                        ->getResultArray();

                    if (!empty($students)) {
                        return [
                            'source'      => 'database',
                            'intent'      => 'CARI_SISWA',
                            'query'       => $query,
                            'widget_type' => 'student_search_card',
                            'explanation' => "Ditemukan " . count($students) . " siswa yang cocok dengan nama '{$searchName}':",
                            'widget_data' => [
                                'students' => $students,
                            ],
                            'matched_action' => [
                                'id'       => 'action_students_all',
                                'title'    => 'Buka Data Master Siswa',
                                'url'      => site_url('admin/students'),
                                'icon'     => 'bi bi-people',
                                'category' => 'Kesiswaan'
                            ]
                        ];
                    }
                } catch (\Throwable $e) {}
            }
        }

        return null;
    }

    /**
     * Dapatkan API Key Gemini dari profil Guru, Pengguna, atau Environment
     */
    protected function resolveAiKey(array $user): array
    {
        $apiKey = '';
        $provider = 'gemini';
        $roleId = (int) ($user['role_id'] ?? 0);
        $db = db_connect();

        try {
            if ($roleId === 3 && !empty($user['related_id'])) {
                $teacher = $db->table('teachers')->where('id', $user['related_id'])->get()->getRowArray();
                $apiKey = $teacher['gemini_api_key'] ?? '';
                $provider = $teacher['ai_provider'] ?? 'gemini';
            } elseif ($roleId === 1 && !empty($user['id'])) {
                $userData = $db->table('users')->where('id', $user['id'])->get()->getRowArray();
                $apiKey = $userData['gemini_api_key'] ?? '';
                $provider = $userData['ai_provider'] ?? 'gemini';
            }
        } catch (\Throwable $e) {}

        if (empty($apiKey)) {
            $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        }

        return [
            'api_key'  => trim((string) $apiKey),
            'provider' => $provider ?: 'gemini',
        ];
    }

    /**
     * Hubungkan ke Gemini API untuk Natural Language Understanding
     */
    protected function callGeminiNlu(string $query, array $allowedActions, string $apiKey): array
    {
        try {
            // Saring tindakan menjadi ringkasan ringkas agar hemat token
            $actionCatalog = [];
            foreach (array_slice($allowedActions, 0, 80) as $act) {
                $actionCatalog[] = [
                    'id'       => $act['id'],
                    'title'    => $act['title'],
                    'category' => $act['category'],
                    'url'      => $act['url'],
                ];
            }

            $prompt = "Anda adalah asisten cerdas sistem informasi akademik sekolah (ESPATA). "
                . "Pengguna memberikan perintah/pertanyaan teks: \"{$query}\".\n"
                . "Tugas Anda adalah memberikan penjelasan, panduan langkah-langkah penggunaan fitur, atau memilih 1 tindakan terbaik dari daftar tindakan yang diizinkan berikut ini:\n"
                . json_encode($actionCatalog, JSON_UNESCAPED_UNICODE) . "\n\n"
                . "Instruksi Khusus:\n"
                . "1. Jika pengguna bertanya bagaimana cara menggunakan suatu fitur (misal 'bagaimana cara membuat modul', 'cara input nilai', 'panduan ujian CBT', dsb.), berikan panduan langkah demi langkah yang jelas, ramah, dan bernomor (1, 2, 3) di dalam field 'ai_content'.\n"
                . "2. Selalu hubungkan dengan ID tindakan yang paling relevan pada 'matched_id' agar pengguna dapat langsung membuka fiturnya dengan 1 klik.\n"
                . "3. Jika pengguna meminta ide materi/soal, tuliskan poin-poin ringkas di dalam 'ai_content'.\n\n"
                . "Berikan respon HANYA dalam format JSON valid berikut (tanpa markdown atau teks pembuka lain):\n"
                . "{\n"
                . '  "matched_id": "id tindakan yang paling cocok atau null jika tidak ada yang relevan",' . "\n"
                . '  "explanation": "Penjelasan singkat ramah 1 kalimat mengapa tindakan/panduan ini dipilih",' . "\n"
                . '  "ai_content": "Teks panduan langkah demi langkah (format bernomor 1, 2, 3) atau ide materi, atau null jika hanya navigasi sederhana",' . "\n"
                . '  "secondary_ids": ["id pilihan alternatif 1", "id pilihan alternatif 2"]' . "\n"
                . "}";

            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . urlencode($apiKey);

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature'     => 0.2,
                    'maxOutputTokens' => 600,
                    'responseMimeType'=> 'application/json'
                ]
            ];

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_TIMEOUT, 6); // Maksimal 6 detik
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && $response) {
                $resData = json_decode($response, true);
                $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $parsed = json_decode($rawText, true);

                if (!empty($parsed['matched_id'])) {
                    $actionMap = [];
                    foreach ($allowedActions as $act) {
                        $actionMap[$act['id']] = $act;
                    }

                    if (isset($actionMap[$parsed['matched_id']])) {
                        $suggestions = [];
                        foreach ((array) ($parsed['secondary_ids'] ?? []) as $secId) {
                            if (isset($actionMap[$secId])) {
                                $suggestions[] = $actionMap[$secId];
                            }
                        }

                        return [
                            'success'        => true,
                            'matched_action' => $actionMap[$parsed['matched_id']],
                            'explanation'    => $parsed['explanation'] ?? 'Tindakan yang sesuai ditemukan.',
                            'ai_content'     => $parsed['ai_content'] ?? null,
                            'suggestions'    => $suggestions,
                        ];
                    }
                }
            } else {
                log_message('warning', '[AiCopilot::callGeminiNlu] HTTP error: ' . $httpCode);
            }
        } catch (\Throwable $e) {
            log_message('error', '[AiCopilot::callGeminiNlu] ' . $e->getMessage());
        }

        return ['success' => false];
    }

    /**
     * Pencocokan lokal menggunakan token scoring dan keyword relevance (0 ms)
     */
    protected function matchLocally(string $query, array $allowedActions): array
    {
        $qClean = strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $query));
        $tokens = array_filter(explode(' ', $qClean), function ($t) {
            return strlen(trim($t)) >= 2;
        });

        $scored = [];
        foreach ($allowedActions as $act) {
            $score = 0;
            $titleLower = strtolower($act['title']);
            $keywords = $act['keywords'] ?? [];

            // Exact match pada judul
            if (strpos($titleLower, $qClean) !== false) {
                $score += 50;
            }

            foreach ($tokens as $token) {
                if (strpos($titleLower, $token) !== false) {
                    $score += 15;
                }
                if (in_array($token, $keywords)) {
                    $score += 8;
                }
                if (strpos(strtolower($act['subtitle']), $token) !== false) {
                    $score += 5;
                }
            }

            if ($score > 0) {
                $act['_score'] = $score;
                $scored[] = $act;
            }
        }

        usort($scored, function ($a, $b) {
            return $b['_score'] <=> $a['_score'];
        });

        $matched = !empty($scored) ? $scored[0] : null;
        $suggestions = !empty($scored) ? array_slice($scored, 1, 4) : [];

        $explanation = $matched 
            ? "Mengarahkan ke {$matched['title']} ({$matched['category']})." 
            : "Tidak ditemukan tindakan yang cocok dengan kata kunci tersebut. Silakan pilih menu terkait di bawah.";

        return [
            'matched_action' => $matched,
            'explanation'    => $explanation,
            'suggestions'    => $suggestions,
        ];
    }

    /**
     * Catat audit log interaksi asisten ke database
     */
    protected function logInteraction(array $user, string $query, ?string $intent, ?string $matchedId, string $source)
    {
        try {
            $db = db_connect();
            $db->table('ai_assistant_logs')->insert([
                'user_id'    => (int) ($user['id'] ?? 0),
                'role_id'    => (int) ($user['role_id'] ?? 0),
                'query'      => mb_substr($query, 0, 500),
                'intent'     => $intent ? mb_substr($intent, 0, 100) : null,
                'matched_id' => $matchedId ? mb_substr($matchedId, 0, 100) : null,
                'source'     => $source,
                'ip_address' => $this->request->getIPAddress(),
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[AiCopilot::logInteraction] ' . $e->getMessage());
        }
    }
}
