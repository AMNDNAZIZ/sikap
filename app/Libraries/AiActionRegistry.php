<?php

namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;

/**
 * AiActionRegistry
 * 
 * Central registry for actions, navigation intents, and contextual commands
 * tailored to each user's authenticated role, permissions, and assigned classes/subjects.
 */
class AiActionRegistry
{
    /**
     * Get all permitted actions and quick intents for the active user
     */
    public static function getUserActions(?array $user = null): array
    {
        if ($user === null) {
            $user = session()->get('user');
        }

        if (empty($user)) {
            return [];
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $userId = (int) ($user['id'] ?? 0);
        $teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);

        $actions = [];

        // 1. Ambil seluruh menu yang diizinkan dari MenuRegistry
        $userMenus = MenuRegistry::getUserMenu($user);
        foreach ($userMenus as $groupKey => $group) {
            $groupTitle = $group['title'] ?? 'Menu';
            foreach ($group['items'] as $item) {
                $actions[] = [
                    'id'          => 'menu_' . md5($item['url']),
                    'title'       => $item['label'],
                    'subtitle'    => $groupTitle,
                    'category'    => 'Menu & Navigasi',
                    'url'         => site_url($item['url']),
                    'icon'        => !empty($item['icon']) ? $item['icon'] : 'bi bi-arrow-right-circle',
                    'keywords'    => self::generateKeywords($item['label'], $groupTitle),
                    'badge'       => 'Menu',
                    'action_type' => 'navigate',
                ];
            }
        }

        $db = db_connect();

        // 2. Aksi Kontekstual Khusus GURU (Role 3)
        if ($roleId === 3 && $teacherId > 0) {
            $teacherActions = self::getTeacherContextActions($db, $teacherId);
            $actions = array_merge($actions, $teacherActions);
        }

        // 3. Aksi Kontekstual Khusus KEPALA SEKOLAH (Role 2)
        if ($roleId === 2) {
            $kepsekActions = self::getKepsekContextActions();
            $actions = array_merge($actions, $kepsekActions);
        }

        // 4. Aksi Kontekstual Khusus ADMIN (Role 1)
        if ($roleId === 1) {
            $adminActions = self::getAdminContextActions();
            $actions = array_merge($actions, $adminActions);
        }

        return $actions;
    }

    /**
     * Dapatkan aksi kontekstual berdasarkan mapel dan kelas yang diampu oleh Guru
     */
    protected static function getTeacherContextActions(BaseConnection $db, int $teacherId): array
    {
        $actions = [];

        try {
            // Ambil penugasan mengajar guru
            $assignments = $db->table('teaching_assignments')
                ->select('teaching_assignments.class_id, teaching_assignments.subject_id, classes.name as class_name, subjects.name as subject_name')
                ->join('classes', 'classes.id = teaching_assignments.class_id', 'left')
                ->join('subjects', 'subjects.id = teaching_assignments.subject_id', 'left')
                ->where('teaching_assignments.teacher_id', $teacherId)
                ->get()
                ->getResultArray();

            $uniqueSubjects = [];
            foreach ($assignments as $row) {
                $subId = (int) ($row['subject_id'] ?? 0);
                $clsId = (int) ($row['class_id'] ?? 0);
                $subName = trim($row['subject_name'] ?? '');
                $clsName = trim($row['class_name'] ?? '');

                if ($subId && $subName) {
                    $uniqueSubjects[$subId] = $subName;
                }

                if ($subId && $clsId && $subName && $clsName) {
                    // A. Modul Ajar spesifik Kelas
                    $actions[] = [
                        'id'          => "modul_{$subId}_{$clsId}",
                        'title'       => "Modul Ajar {$subName} ({$clsName})",
                        'subtitle'    => "Administrasi Guru & Modul Ajar",
                        'category'    => 'Modul & Perangkat Ajar',
                        'url'         => site_url("admin/administrasi-guru/modul-ajar?subject_id={$subId}&class_id={$clsId}"),
                        'icon'        => 'bi bi-journal-bookmark-fill',
                        'keywords'    => self::generateKeywords("modul ajar {$subName} kelas {$clsName} buat rpp atp cp perangkat ajar", $subName),
                        'badge'       => 'Modul Ajar',
                        'action_type' => 'navigate',
                    ];

                    // B. Input Nilai Kelas
                    $actions[] = [
                        'id'          => "nilai_{$subId}_{$clsId}",
                        'title'       => "Input Nilai {$subName} ({$clsName})",
                        'subtitle'    => "Penilaian Akademik",
                        'category'    => 'Penilaian & Rapor',
                        'url'         => site_url("admin/grades?class_id={$clsId}&subject_id={$subId}"),
                        'icon'        => 'bi bi-calculator-fill',
                        'keywords'    => self::generateKeywords("input nilai {$subName} kelas {$clsName} asesmen rapor", $subName),
                        'badge'       => 'Nilai',
                        'action_type' => 'navigate',
                    ];

                    // C. Presensi Harian Kelas
                    $actions[] = [
                        'id'          => "presensi_{$clsId}",
                        'title'       => "Presensi Siswa ({$clsName})",
                        'subtitle'    => "Absensi & Kehadiran",
                        'category'    => 'Presensi & KBM',
                        'url'         => site_url("admin/attendance?class_id={$clsId}"),
                        'icon'        => 'bi bi-calendar-check-fill',
                        'keywords'    => self::generateKeywords("presensi absen kehadiran siswa kelas {$clsName}", $clsName),
                        'badge'       => 'Presensi',
                        'action_type' => 'navigate',
                    ];
                }
            }

            // D. Modul Ajar & Materi tingkat Mapel (Tanpa kelas spesifik)
            foreach ($uniqueSubjects as $subId => $subName) {
                $actions[] = [
                    'id'          => "materials_{$subId}",
                    'title'       => "Materi & Slide E-Learning: {$subName}",
                    'subtitle'    => "Bahan Ajar & Presentasi Interaktif",
                    'category'    => 'E-Learning & Materi',
                    'url'         => site_url("materials/{$subId}"),
                    'icon'        => 'bi bi-easel-fill',
                    'keywords'    => self::generateKeywords("materi slide presentasi bahan ajar elearning {$subName}", $subName),
                    'badge'       => 'Materi',
                    'action_type' => 'navigate',
                ];

                $actions[] = [
                    'id'          => "cbt_bank_{$subId}",
                    'title'       => "Bank Soal CBT: {$subName}",
                    'subtitle'    => "Kelola Soal Ujian Online",
                    'category'    => 'Ujian CBT',
                    'url'         => site_url("admin/cbt/bank-soal?subject_id={$subId}"),
                    'icon'        => 'bi bi-card-checklist',
                    'keywords'    => self::generateKeywords("soal cbt ujian bank tes asesmen {$subName}", $subName),
                    'badge'       => 'CBT Soal',
                    'action_type' => 'navigate',
                ];
            }
        } catch (\Throwable $th) {
            log_message('error', '[AiActionRegistry::getTeacherContextActions] ' . $th->getMessage());
        }

        return $actions;
    }

    /**
     * Dapatkan aksi kontekstual untuk Kepala Sekolah
     */
    protected static function getKepsekContextActions(): array
    {
        return [
            [
                'id'          => 'kepsek_rekap_guru',
                'title'       => 'Rekap Kehadiran Guru Hari Ini',
                'subtitle'    => 'Laporan Presensi Tenaga Pendidik',
                'category'    => 'Supervisi & Laporan',
                'url'         => site_url('admin/teacher-attendance/report'),
                'icon'        => 'bi bi-person-check-fill',
                'keywords'    => ['rekap', 'absen', 'kehadiran', 'guru', 'hari ini', 'presensi', 'laporan', 'monitoring'],
                'badge'       => 'Monitoring',
                'action_type' => 'navigate',
            ],
            [
                'id'          => 'kepsek_rekap_siswa',
                'title'       => 'Rekap Kehadiran Siswa',
                'subtitle'    => 'Monitoring Absensi Seluruh Kelas',
                'category'    => 'Supervisi & Laporan',
                'url'         => site_url('admin/attendance/report'),
                'icon'        => 'bi bi-people-fill',
                'keywords'    => ['rekap', 'siswa', 'kehadiran', 'absensi', 'kelas', 'bolos', 'sakit', 'izin'],
                'badge'       => 'Monitoring',
                'action_type' => 'navigate',
            ],
            [
                'id'          => 'kepsek_monitoring_cbt',
                'title'       => 'Monitoring Ujian CBT Berjalan',
                'subtitle'    => 'Aktivitas Ujian Real-Time',
                'category'    => 'Supervisi & Laporan',
                'url'         => site_url('admin/cbt/aktivitas'),
                'icon'        => 'bi bi-speedometer2',
                'keywords'    => ['cbt', 'ujian', 'monitoring', 'aktivitas', 'siswa ujian', 'realtime'],
                'badge'       => 'Real-Time',
                'action_type' => 'navigate',
            ],
            [
                'id'          => 'kepsek_rapor_semester',
                'title'       => 'Progres Rapor & Nilai Semester',
                'subtitle'    => 'Kelengkapan Nilai Siswa',
                'category'    => 'Supervisi & Laporan',
                'url'         => site_url('admin/rapor-semester'),
                'icon'        => 'bi bi-award-fill',
                'keywords'    => ['rapor', 'nilai', 'semester', 'progres', 'kelulusan', 'kenaikan'],
                'badge'       => 'Rapor',
                'action_type' => 'navigate',
            ],
        ];
    }

    /**
     * Dapatkan aksi kontekstual untuk Administrator
     */
    protected static function getAdminContextActions(): array
    {
        return [
            [
                'id'          => 'admin_manage_users',
                'title'       => 'Kelola Pengguna & Reset Password',
                'subtitle'    => 'Manajemen Akun Guru, Siswa, & Staf',
                'category'    => 'Administrasi Sistem',
                'url'         => site_url('admin/users'),
                'icon'        => 'bi bi-shield-lock-fill',
                'keywords'    => ['user', 'pengguna', 'reset', 'password', 'akun', 'login', 'ganti password'],
                'badge'       => 'Sistem',
                'action_type' => 'navigate',
            ],
            [
                'id'          => 'admin_reset_cbt_session',
                'title'       => 'Reset Sesi Ujian Siswa (CBT)',
                'subtitle'    => 'Lepaskan Sesi Terkunci / Pindah Perangkat',
                'category'    => 'Ujian CBT',
                'url'         => site_url('admin/cbt/aktivitas'),
                'icon'        => 'bi bi-arrow-clockwise',
                'keywords'    => ['reset', 'ujian', 'cbt', 'terkunci', 'buka sesi', 'siswa'],
                'badge'       => 'CBT',
                'action_type' => 'navigate',
            ],
            [
                'id'          => 'admin_school_settings',
                'title'       => 'Profil & Pengaturan Sekolah',
                'subtitle'    => 'Identitas Sekolah, KOP Surat, Logo',
                'category'    => 'Pengaturan',
                'url'         => site_url('admin/settings'),
                'icon'        => 'bi bi-gear-fill',
                'keywords'    => ['profil', 'pengaturan', 'sekolah', 'logo', 'kop surat', 'kepsek'],
                'badge'       => 'Konfigurasi',
                'action_type' => 'navigate',
            ],
        ];
    }

    /**
     * Helper untuk membuat array kata kunci pencarian yang kaya sinonim
     */
    protected static function generateKeywords(string ...$sources): array
    {
        $raw = strtolower(implode(' ', $sources));
        // Bersihkan tanda baca
        $clean = preg_replace('/[^a-z0-9\s]/', ' ', $raw);
        $words = array_filter(explode(' ', $clean), function ($w) {
            return strlen(trim($w)) >= 2;
        });

        // Tambahkan sinonim umum
        $synonyms = [
            'pancasila'  => ['pkn', 'ppkn', 'kewarganegaraan'],
            'matematika' => ['mtk', 'hitung'],
            'inggris'    => ['english', 'bhs inggris'],
            'indonesia'  => ['bhs indonesia'],
            'agama'      => ['pai', 'pak', 'pabp', 'islam', 'kristen'],
            'jasmani'    => ['pjok', 'olahraga', 'penjas'],
            'absen'      => ['presensi', 'kehadiran', 'bolos', 'hadir'],
            'modul'      => ['rpp', 'perangkat', 'atp', 'cp', 'materi'],
            'ujian'      => ['cbt', 'tes', 'ulangan', 'penilaian', 'asesmen'],
            'nilai'      => ['angka', 'rapor', 'skor', 'grade'],
        ];

        $extra = [];
        foreach ($words as $w) {
            if (isset($synonyms[$w])) {
                $extra = array_merge($extra, $synonyms[$w]);
            }
        }

        return array_values(array_unique(array_merge($words, $extra)));
    }
}

