<?php

namespace App\Libraries;

/**
 * MenuRegistry
 * 
 * Central registry for all application menus and their associated permissions.
 * Enables Dynamic RBAC: menus appear automatically based on role_permissions,
 * and custom roles (e.g., Pelatih, Bendahara, Wakasek) render automatically.
 */
class MenuRegistry
{
    /**
     * Get complete menu definition with modules, actions, and permissions
     */
    public static function getMenuDefinition(): array
    {
        return [
            'kesiswaan' => [
                'title'       => 'Administrasi Siswa',
                'id'          => 'menuKesiswaanDyn',
                'icon'        => 'bi bi-mortarboard-fill',
                'active_urls' => ['admin/students*', 'admin/classes*', 'admin/active-classes*', 'admin/placement*', 'admin/promotions*', 'admin/student-mutation*', 'admin/student-records*', 'admin/alumni*', 'admin/student-map*'],
                'items'       => [
                    [
                        'label'       => 'Data Siswa',
                        'url'         => 'admin/students',
                        'icon'        => 'bi bi-people me-1',
                        'permission'  => ['students.view', 'students.manage'],
                        'active_rule' => 'admin/students*',
                    ],
                    [
                        'label'       => 'Master Kelas',
                        'url'         => 'admin/classes',
                        'icon'        => 'bi bi-collection me-1',
                        'permission'  => 'classes.manage',
                        'active_rule' => 'admin/classes*',
                    ],
                    [
                        'label'       => 'Kelas Aktif',
                        'url'         => 'admin/active-classes',
                        'icon'        => 'bi bi-door-open me-1',
                        'permission'  => ['classes.manage', 'placement.manage'],
                        'active_rule' => 'admin/active-classes*',
                    ],
                    [
                        'label'       => 'Penempatan Siswa',
                        'url'         => 'admin/placement',
                        'icon'        => 'bi bi-pin-angle me-1',
                        'permission'  => 'placement.manage',
                        'active_rule' => 'admin/placement*',
                    ],
                    [
                        'label'       => 'Kenaikan & Kelulusan',
                        'url'         => 'admin/promotions',
                        'icon'        => 'bi bi-award me-1',
                        'permission'  => 'promotions.manage',
                        'active_rule' => 'admin/promotions*',
                    ],
                    [
                        'label'       => 'Buku Mutasi Siswa',
                        'url'         => 'admin/student-mutation',
                        'icon'        => 'bi bi-arrow-left-right me-1',
                        'permission'  => 'student_mutation.manage',
                        'active_rule' => 'admin/student-mutation*',
                    ],
                    [
                        'label'       => 'Riwayat Siswa',
                        'url'         => 'admin/student-records',
                        'icon'        => 'bi bi-clock-history me-1',
                        'permission'  => ['students.view', 'students.manage'],
                        'active_rule' => 'admin/student-records*',
                    ],
                    [
                        'label'       => 'Peta Sebaran Siswa',
                        'url'         => 'admin/student-map',
                        'icon'        => 'bi bi-geo-alt me-1',
                        'permission'  => 'student_map.view',
                        'active_rule' => 'admin/student-map*',
                    ],
                    [
                        'label'       => 'Alumni',
                        'url'         => 'admin/alumni',
                        'icon'        => 'bi bi-patch-check me-1',
                        'permission'  => 'alumni.view',
                        'active_rule' => 'admin/alumni*',
                    ],
                ]
            ],

            'tata_usaha' => [
                'title'       => 'Tata Usaha & Surat',
                'id'          => 'menuTataUsahaDyn',
                'icon'        => 'bi bi-folder2-open',
                'active_urls' => ['admin/surat-masuk*', 'admin/surat-keluar*', 'admin/agenda-surat*', 'admin/buku-tamu*', 'admin/tata-usaha*', 'admin/settings/kop-surat*'],
                'items'       => [
                    [
                        'label'       => 'Surat Masuk',
                        'url'         => 'admin/surat-masuk',
                        'icon'        => 'bi bi-inbox me-1',
                        'permission'  => 'surat_masuk.manage',
                        'active_rule' => 'admin/surat-masuk*',
                    ],
                    [
                        'label'       => 'Surat Keluar',
                        'url'         => 'admin/surat-keluar',
                        'icon'        => 'bi bi-send me-1',
                        'permission'  => 'surat_keluar.manage',
                        'active_rule' => 'admin/surat-keluar*',
                    ],
                    [
                        'label'       => 'Agenda Surat',
                        'url'         => 'admin/agenda-surat',
                        'icon'        => 'bi bi-journal me-1',
                        'permission'  => 'agenda_surat.view',
                        'active_rule' => 'admin/agenda-surat*',
                    ],
                    [
                        'label'       => 'Buku Tamu',
                        'url'         => 'admin/buku-tamu',
                        'icon'        => 'bi bi-book-half me-1',
                        'permission'  => 'buku_tamu.manage',
                        'active_rule' => 'admin/buku-tamu*',
                    ],
                    [
                        'label'       => 'Cetak Dokumen & Hadir',
                        'url'         => 'admin/tata-usaha/cetak-daftar-hadir',
                        'icon'        => 'bi bi-printer me-1',
                        'permission'  => 'tata_usaha.manage',
                        'active_rule' => 'admin/tata-usaha*',
                    ],
                    [
                        'label'       => 'KOP Surat',
                        'url'         => 'admin/settings/kop-surat',
                        'icon'        => 'bi bi-file-earmark-richtext me-1',
                        'permission'  => ['tata_usaha.manage', 'settings.manage'],
                        'active_rule' => 'admin/settings/kop-surat*',
                    ],
                ]
            ],

            'kepegawaian' => [
                'title'       => 'Kepegawaian',
                'id'          => 'menuGuruDyn',
                'icon'        => 'bi bi-person-badge-fill',
                'active_urls' => ['admin/teachers*', 'admin/teachingassignments*', 'admin/teacher-attendance*'],
                'items'       => [
                    [
                        'label'       => 'Data Guru & Pegawai',
                        'url'         => 'admin/teachers',
                        'icon'        => 'bi bi-person-vcard me-1',
                        'permission'  => ['teachers.view', 'teachers.manage'],
                        'active_rule' => 'admin/teachers*',
                    ],
                    [
                        'label'       => 'Penugasan Mengajar',
                        'url'         => 'admin/teachingassignments',
                        'icon'        => 'bi bi-briefcase me-1',
                        'permission'  => 'teaching_assignments.manage',
                        'active_rule' => 'admin/teachingassignments*',
                    ],
                    [
                        'label'       => 'Laporan Absensi Guru',
                        'url'         => 'admin/teacher-attendance/report',
                        'icon'        => 'bi bi-calendar2-check me-1',
                        'permission'  => ['teacher_attendance.view', 'teacher_attendance.manage'],
                        'active_rule' => 'admin/teacher-attendance*',
                    ],
                ]
            ],

            'akademik' => [
                'title'       => 'Akademik & KBM',
                'id'          => 'menuAkademikDyn',
                'icon'        => 'bi bi-book-half',
                'active_urls' => ['admin/academic-year*', 'admin/holidays*', 'admin/subjects*', 'admin/mapel-master*', 'admin/schedules*', 'admin/attendance*', 'admin/teaching-journal*', 'admin/agendas*'],
                'items'       => [
                    [
                        'label'       => 'Tahun Pelajaran',
                        'url'         => 'admin/academic-year',
                        'icon'        => 'bi bi-calendar-range me-1',
                        'permission'  => 'academic_year.manage',
                        'active_rule' => 'admin/academic-year*',
                    ],
                    [
                        'label'       => 'Mata Pelajaran',
                        'url'         => 'admin/subjects',
                        'icon'        => 'bi bi-journals me-1',
                        'permission'  => 'subjects.manage',
                        'active_rule' => 'admin/subjects*',
                    ],
                    [
                        'label'       => 'Jadwal Pelajaran',
                        'url'         => 'admin/schedules',
                        'icon'        => 'bi bi-calendar3 me-1',
                        'permission'  => ['schedules.view', 'schedules.manage'],
                        'active_rule' => 'admin/schedules*',
                    ],
                    [
                        'label'       => 'Presensi Siswa',
                        'url'         => 'admin/attendance',
                        'icon'        => 'bi bi-calendar-check me-1',
                        'permission'  => ['attendance.view', 'attendance.manage'],
                        'active_rule' => 'admin/attendance*',
                    ],
                    [
                        'label'       => 'Jurnal Mengajar',
                        'url'         => 'admin/teaching-journal',
                        'icon'        => 'bi bi-journal-text me-1',
                        'permission'  => 'teaching_journal.manage',
                        'active_rule' => 'admin/teaching-journal*',
                    ],
                    [
                        'label'       => 'Agenda Kelas',
                        'url'         => 'admin/agendas',
                        'icon'        => 'bi bi-calendar-event me-1',
                        'permission'  => 'agendas.manage',
                        'active_rule' => 'admin/agendas*',
                    ],
                    [
                        'label'       => 'Hari Libur',
                        'url'         => 'admin/holidays',
                        'icon'        => 'bi bi-calendar2-x me-1',
                        'permission'  => 'holidays.manage',
                        'active_rule' => 'admin/holidays*',
                    ],
                ]
            ],

            'kurikulum' => [
                'title'       => 'Kurikulum & Pembelajaran',
                'id'          => 'menuKurikulumDyn',
                'icon'        => 'bi bi-journal-bookmark-fill',
                'active_urls' => ['admin/administrasi-guru*', 'admin/kokurikuler*', 'admin/materials*', 'admin/quiz*', 'admin/forum*'],
                'items'       => [
                    [
                        'label'       => 'Perangkat Ajar (CP/TP/ATP)',
                        'url'         => 'admin/administrasi-guru/modul-ajar',
                        'icon'        => 'bi bi-file-earmark-ruled me-1',
                        'permission'  => 'administrasi_guru.manage',
                        'active_rule' => 'admin/administrasi-guru*',
                    ],
                    [
                        'label'       => 'Kokurikuler (P5)',
                        'url'         => 'admin/kokurikuler',
                        'icon'        => 'bi bi-compass me-1',
                        'permission'  => 'kokurikuler.manage',
                        'active_rule' => 'admin/kokurikuler*',
                    ],
                    [
                        'label'       => 'Materi & Bahan Ajar',
                        'url'         => 'admin/materials',
                        'icon'        => 'bi bi-folder-symlink me-1',
                        'permission'  => 'materials.manage',
                        'active_rule' => 'admin/materials*',
                    ],
                    [
                        'label'       => 'Kuis Mandiri (LMS)',
                        'url'         => 'admin/quiz',
                        'icon'        => 'bi bi-patch-question me-1',
                        'permission'  => 'quiz.manage',
                        'active_rule' => 'admin/quiz*',
                    ],
                    [
                        'label'       => 'Forum Diskusi',
                        'url'         => 'admin/forum',
                        'icon'        => 'bi bi-chat-dots me-1',
                        'permission'  => 'forum.manage',
                        'active_rule' => 'admin/forum*',
                    ],
                ]
            ],

            'penilaian' => [
                'title'       => 'Penilaian & Rapor',
                'id'          => 'menuPenilaianDyn',
                'icon'        => 'bi bi-award-fill',
                'active_urls' => ['admin/assessments*', 'admin/grades*', 'admin/erapor*'],
                'items'       => [
                    [
                        'label'       => 'Asesmen & Penilaian',
                        'url'         => 'admin/assessments',
                        'icon'        => 'bi bi-clipboard2-check me-1',
                        'permission'  => 'assessments.manage',
                        'active_rule' => 'admin/assessments*',
                    ],
                    [
                        'label'       => 'Leger & Nilai Rapor',
                        'url'         => 'admin/grades',
                        'icon'        => 'bi bi-table me-1',
                        'permission'  => ['grades.view', 'grades.manage'],
                        'active_rule' => 'admin/grades*',
                    ],
                    [
                        'label'       => 'E-Rapor',
                        'url'         => 'admin/erapor',
                        'icon'        => 'bi bi-file-earmark-pdf me-1',
                        'permission'  => 'erapor.manage',
                        'active_rule' => 'admin/erapor*',
                    ],
                ]
            ],

            'cbt' => [
                'title'       => 'Ujian Online (CBT)',
                'id'          => 'menuCbtDyn',
                'icon'        => 'bi bi-laptop',
                'active_urls' => ['admin/cbt*'],
                'items'       => [
                    [
                        'label'       => 'Dashboard CBT',
                        'url'         => 'admin/cbt',
                        'icon'        => 'bi bi-speedometer me-1',
                        'permission'  => ['cbt.view', 'cbt.manage'],
                        'active_rule' => 'admin/cbt',
                    ],
                    [
                        'label'       => 'Bank Soal',
                        'url'         => 'admin/cbt/questions',
                        'icon'        => 'bi bi-collection me-1',
                        'permission'  => 'cbt.manage',
                        'active_rule' => 'admin/cbt/questions*',
                    ],
                    [
                        'label'       => 'Jadwal Ujian CBT',
                        'url'         => 'admin/cbt/exams',
                        'icon'        => 'bi bi-calendar2-range me-1',
                        'permission'  => 'cbt.manage',
                        'active_rule' => 'admin/cbt/exams*',
                    ],
                    [
                        'label'       => 'Konversi Nilai CBT',
                        'url'         => 'admin/cbt/convert-grades',
                        'icon'        => 'bi bi-calculator me-1',
                        'permission'  => 'cbt.convert',
                        'active_rule' => 'admin/cbt/convert-grades*',
                    ],
                ]
            ],

            'bk' => [
                'title'       => 'Bimbingan Konseling (BK)',
                'id'          => 'menuBkDyn',
                'icon'        => 'bi bi-heart-pulse-fill',
                'active_urls' => ['admin/bk*', 'admin/student-notes*'],
                'items'       => [
                    [
                        'label'       => 'Dashboard & Kasus BK',
                        'url'         => 'admin/bk',
                        'icon'        => 'bi bi-shield-exclamation me-1',
                        'permission'  => ['bk.view', 'bk.manage'],
                        'active_rule' => 'admin/bk',
                    ],
                    [
                        'label'       => 'Catatan & Sikap Siswa',
                        'url'         => 'admin/student-notes',
                        'icon'        => 'bi bi-journal-check me-1',
                        'permission'  => 'student_notes.manage',
                        'active_rule' => 'admin/student-notes*',
                    ],
                ]
            ],

            'ekskul' => [
                'title'       => 'Ekstrakurikuler',
                'id'          => 'menuEkskulDyn',
                'icon'        => 'bi bi-trophy-fill',
                'active_urls' => ['admin/ekskul*', 'admin/ekskul-pembina*', 'admin/ekskul-absensi*'],
                'items'       => [
                    [
                        'label'       => 'Master Ekskul',
                        'url'         => 'admin/ekskul',
                        'icon'        => 'bi bi-trophy me-1',
                        'permission'  => 'ekskul.manage_pembina',
                        'active_rule' => 'admin/ekskul',
                    ],
                    [
                        'label'       => 'Pembina & Pelatih',
                        'url'         => 'admin/ekskul-pembina',
                        'icon'        => 'bi bi-dribbble me-1',
                        'permission'  => 'ekskul.manage_pembina',
                        'active_rule' => 'admin/ekskul-pembina*',
                    ],
                    [
                        'label'       => 'Absensi Ekskul',
                        'url'         => 'admin/ekskul-absensi',
                        'icon'        => 'bi bi-check2-circle me-1',
                        'permission'  => ['ekskul_absensi.view', 'ekskul_absensi.verify'],
                        'active_rule' => 'admin/ekskul-absensi*',
                    ],
                ]
            ],

            'cms' => [
                'title'       => 'Publikasi & Website',
                'id'          => 'menuCmsDyn',
                'icon'        => 'bi bi-globe',
                'active_urls' => ['admin/cms*', 'admin/school*'],
                'items'       => [
                    [
                        'label'       => 'Berita & Artikel',
                        'url'         => 'admin/cms/articles',
                        'icon'        => 'bi bi-newspaper me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/articles*',
                    ],
                    [
                        'label'       => 'Sambutan Kepsek',
                        'url'         => 'admin/cms/sambutan',
                        'icon'        => 'bi bi-person-badge me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/sambutan*',
                    ],
                    [
                        'label'       => 'Slider Hero',
                        'url'         => 'admin/cms/sliders',
                        'icon'        => 'bi bi-images me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/sliders*',
                    ],
                    [
                        'label'       => 'Sarana Prasarana',
                        'url'         => 'admin/cms/facilities',
                        'icon'        => 'bi bi-buildings me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/facilities*',
                    ],
                    [
                        'label'       => 'Dokumentasi',
                        'url'         => 'admin/cms/activities',
                        'icon'        => 'bi bi-camera me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/activities*',
                    ],
                    [
                        'label'       => 'Tautan Pintar',
                        'url'         => 'admin/cms/links',
                        'icon'        => 'bi bi-link-45deg me-1',
                        'permission'  => 'cms.manage',
                        'active_rule' => 'admin/cms/links*',
                    ],
                    [
                        'label'       => 'Identitas & Medsos',
                        'url'         => 'admin/school',
                        'icon'        => 'bi bi-building me-1',
                        'permission'  => 'school.manage',
                        'active_rule' => 'admin/school*',
                    ],
                ]
            ],

            'ebook' => [
                'title'       => 'Perpustakaan Digital',
                'id'          => 'menuEbookDyn',
                'icon'        => 'bi bi-book',
                'active_urls' => ['admin/ebook*'],
                'items'       => [
                    [
                        'label'       => 'Koleksi E-Book',
                        'url'         => 'admin/ebook',
                        'icon'        => 'bi bi-journal-album me-1',
                        'permission'  => ['ebook.view', 'ebook.manage'],
                        'active_rule' => 'admin/ebook*',
                    ],
                ]
            ],

            'komunikasi' => [
                'title'       => 'Komunikasi',
                'id'          => 'menuKomunikasiDyn',
                'icon'        => 'bi bi-chat-square-text-fill',
                'active_urls' => ['admin/announcements*', 'admin/chat*', 'admin/staff-chat*'],
                'items'       => [
                    [
                        'label'       => 'Pengumuman',
                        'url'         => 'admin/announcements',
                        'icon'        => 'bi bi-megaphone me-1',
                        'permission'  => 'announcements.manage',
                        'active_rule' => 'admin/announcements*',
                    ],
                    [
                        'label'       => 'Chat Staff Internal',
                        'url'         => 'admin/staff-chat',
                        'icon'        => 'bi bi-chat-left-dots me-1',
                        'permission'  => 'staff_chat.manage',
                        'active_rule' => 'admin/staff-chat*',
                    ],
                    [
                        'label'       => 'Chat Kelas',
                        'url'         => 'admin/chat',
                        'icon'        => 'bi bi-chat-dots me-1',
                        'permission'  => 'chat.manage',
                        'active_rule' => 'admin/chat*',
                    ],
                ]
            ],

            'sistem' => [
                'title'       => 'Pengaturan Sistem',
                'id'          => 'menuSistemDyn',
                'icon'        => 'bi bi-gear-fill',
                'active_urls' => ['admin/users*', 'admin/role-permission*', 'admin/audit-logs*', 'admin/updater*', 'admin/settings*'],
                'items'       => [
                    [
                        'label'       => 'User Management',
                        'url'         => 'admin/users',
                        'icon'        => 'bi bi-people-fill me-1',
                        'permission'  => ['users.view', 'users.manage'],
                        'active_rule' => 'admin/users*',
                    ],
                    [
                        'label'       => 'Role & Hak Akses',
                        'url'         => 'admin/role-permission',
                        'icon'        => 'bi bi-shield-lock me-1',
                        'permission'  => 'roles.manage',
                        'active_rule' => 'admin/role-permission*',
                    ],
                    [
                        'label'       => 'Audit Trail / Log',
                        'url'         => 'admin/audit-logs',
                        'icon'        => 'bi bi-clock-history me-1',
                        'permission'  => 'audit_logs.view',
                        'active_rule' => 'admin/audit-logs*',
                    ],
                    [
                        'label'       => 'Integrasi Dapodik',
                        'url'         => 'admin/dapodik',
                        'icon'        => 'bi bi-cloud-arrow-up me-1',
                        'permission'  => 'dapodik.manage',
                        'active_rule' => 'admin/dapodik*',
                    ],
                    [
                        'label'       => 'Update Sistem',
                        'url'         => 'admin/updater',
                        'icon'        => 'bi bi-arrow-repeat me-1',
                        'permission'  => 'updater.manage',
                        'active_rule' => 'admin/updater*',
                    ],
                    [
                        'label'       => 'Pengaturan Umum',
                        'url'         => 'admin/settings',
                        'icon'        => 'bi bi-sliders me-1',
                        'permission'  => 'settings.manage',
                        'active_rule' => 'admin/settings*',
                    ],
                ]
            ],
        ];
    }

    /**
     * Get filtered menu categories and items for the current authenticated user
     */
    public static function getUserMenu(?array $user = null): array
    {
        if ($user === null) {
            $user = session()->get('user');
        }

        if (empty($user)) {
            return [];
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        $all = self::getMenuDefinition();
        $isAdmin = ($roleId === 1);

        if (function_exists('helper')) {
            helper('authorization');
        }
        $filtered = [];

        foreach ($all as $groupKey => $group) {
            $permittedItems = [];

            foreach ($group['items'] as $item) {
                if ($isAdmin) {
                    $permittedItems[] = $item;
                    continue;
                }

                $perms = (array) ($item['permission'] ?? []);
                if (empty($perms)) {
                    $permittedItems[] = $item;
                    continue;
                }

                $hasAccess = false;
                foreach ($perms as $perm) {
                    if (has_permission($perm)) {
                        $hasAccess = true;
                        break;
                    }
                }

                if ($hasAccess) {
                    $permittedItems[] = $item;
                }
            }

            if (!empty($permittedItems)) {
                $groupCopy = $group;
                $groupCopy['items'] = $permittedItems;
                $filtered[$groupKey] = $groupCopy;
            }
        }

        return $filtered;
    }

    /**
     * Map a URI path to required permission(s)
     * Used by AuthFilter fallback to allow dynamic access
     */
    public static function getPermissionsForPath(string $path): array
    {
        $path = trim($path, '/');
        $all = self::getMenuDefinition();
        $matched = [];

        foreach ($all as $group) {
            foreach ($group['items'] as $item) {
                $itemUrl = trim($item['url'], '/');
                $rule = trim($item['active_rule'] ?? $itemUrl, '/');

                // Check exact match or prefix match with wildcard
                $pattern = str_replace('*', '.*', $rule);
                if (preg_match('#^' . $pattern . '#i', $path) || strpos($path, $itemUrl) === 0) {
                    $perms = (array) ($item['permission'] ?? []);
                    foreach ($perms as $p) {
                        $matched[] = $p;
                    }
                }
            }
        }

        return array_unique($matched);
    }
}
