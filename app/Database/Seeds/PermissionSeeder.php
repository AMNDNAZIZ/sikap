<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run()
    {
        $permissions = [
            // ═══════ Pengaturan Sistem ═══════
            ['module' => 'users', 'action' => 'view', 'label' => 'Lihat Data User', 'group_name' => 'Pengaturan', 'sort_order' => 1],
            ['module' => 'users', 'action' => 'create', 'label' => 'Tambah User', 'group_name' => 'Pengaturan', 'sort_order' => 2],
            ['module' => 'users', 'action' => 'update', 'label' => 'Edit User', 'group_name' => 'Pengaturan', 'sort_order' => 3],
            ['module' => 'users', 'action' => 'delete', 'label' => 'Hapus User', 'group_name' => 'Pengaturan', 'sort_order' => 4],
            ['module' => 'roles', 'action' => 'manage', 'label' => 'Kelola Role & Permission', 'group_name' => 'Pengaturan', 'sort_order' => 5],
            ['module' => 'school', 'action' => 'manage', 'label' => 'Kelola Identitas Sekolah', 'group_name' => 'Pengaturan', 'sort_order' => 6],
            ['module' => 'academic_year', 'action' => 'manage', 'label' => 'Kelola Tahun Pelajaran', 'group_name' => 'Pengaturan', 'sort_order' => 7],
            ['module' => 'settings', 'action' => 'manage', 'label' => 'Pengaturan Sistem', 'group_name' => 'Pengaturan', 'sort_order' => 8],
            ['module' => 'updater', 'action' => 'manage', 'label' => 'Update Sistem', 'group_name' => 'Pengaturan', 'sort_order' => 9],

            // ═══════ Kesiswaan ═══════
            ['module' => 'students', 'action' => 'view', 'label' => 'Lihat Data Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 10],
            ['module' => 'students', 'action' => 'create', 'label' => 'Tambah Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 11],
            ['module' => 'students', 'action' => 'update', 'label' => 'Edit Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 12],
            ['module' => 'students', 'action' => 'delete', 'label' => 'Hapus Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 13],
            ['module' => 'students', 'action' => 'import', 'label' => 'Import Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 14],
            ['module' => 'classes', 'action' => 'manage', 'label' => 'Kelola Kelas', 'group_name' => 'Kesiswaan', 'sort_order' => 15],
            ['module' => 'placement', 'action' => 'manage', 'label' => 'Penempatan Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 16],
            ['module' => 'promotions', 'action' => 'manage', 'label' => 'Kenaikan Kelas / Kelulusan', 'group_name' => 'Kesiswaan', 'sort_order' => 17],
            ['module' => 'alumni', 'action' => 'view', 'label' => 'Lihat Data Alumni', 'group_name' => 'Kesiswaan', 'sort_order' => 18],
            ['module' => 'student_mutation', 'action' => 'manage', 'label' => 'Mutasi Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 19],
            ['module' => 'student_map', 'action' => 'view', 'label' => 'Peta Sebaran Siswa', 'group_name' => 'Kesiswaan', 'sort_order' => 20],

            // ═══════ Personalia ═══════
            ['module' => 'teachers', 'action' => 'view', 'label' => 'Lihat Data Guru/Pegawai', 'group_name' => 'Personalia', 'sort_order' => 21],
            ['module' => 'teachers', 'action' => 'create', 'label' => 'Tambah Guru/Pegawai', 'group_name' => 'Personalia', 'sort_order' => 22],
            ['module' => 'teachers', 'action' => 'update', 'label' => 'Edit Guru/Pegawai', 'group_name' => 'Personalia', 'sort_order' => 23],
            ['module' => 'teachers', 'action' => 'delete', 'label' => 'Hapus Guru/Pegawai', 'group_name' => 'Personalia', 'sort_order' => 24],
            ['module' => 'teacher_attendance', 'action' => 'manage', 'label' => 'Absensi Guru', 'group_name' => 'Personalia', 'sort_order' => 25],
            ['module' => 'teacher_attendance', 'action' => 'view', 'label' => 'Lihat Laporan Absensi Guru', 'group_name' => 'Personalia', 'sort_order' => 26],

            // ═══════ Akademik ═══════
            ['module' => 'subjects', 'action' => 'manage', 'label' => 'Kelola Mata Pelajaran', 'group_name' => 'Akademik', 'sort_order' => 27],
            ['module' => 'teaching_assignments', 'action' => 'manage', 'label' => 'Penugasan Mengajar', 'group_name' => 'Akademik', 'sort_order' => 28],
            ['module' => 'schedules', 'action' => 'view', 'label' => 'Lihat Jadwal Pelajaran', 'group_name' => 'Akademik', 'sort_order' => 29],
            ['module' => 'schedules', 'action' => 'manage', 'label' => 'Kelola Jadwal Pelajaran', 'group_name' => 'Akademik', 'sort_order' => 30],
            ['module' => 'attendance', 'action' => 'view', 'label' => 'Lihat Presensi Siswa', 'group_name' => 'Akademik', 'sort_order' => 31],
            ['module' => 'attendance', 'action' => 'manage', 'label' => 'Kelola Presensi Siswa', 'group_name' => 'Akademik', 'sort_order' => 32],
            ['module' => 'grades', 'action' => 'view', 'label' => 'Lihat Nilai', 'group_name' => 'Akademik', 'sort_order' => 33],
            ['module' => 'grades', 'action' => 'manage', 'label' => 'Input/Kelola Nilai', 'group_name' => 'Akademik', 'sort_order' => 34],
            ['module' => 'assessments', 'action' => 'manage', 'label' => 'Kelola Asesmen', 'group_name' => 'Akademik', 'sort_order' => 35],
            ['module' => 'erapor', 'action' => 'manage', 'label' => 'Kelola E-Rapor', 'group_name' => 'Akademik', 'sort_order' => 36],
            ['module' => 'materials', 'action' => 'manage', 'label' => 'Kelola Materi Pelajaran', 'group_name' => 'Akademik', 'sort_order' => 37],
            ['module' => 'agendas', 'action' => 'manage', 'label' => 'Kelola Agenda Kelas', 'group_name' => 'Akademik', 'sort_order' => 38],
            ['module' => 'teaching_journal', 'action' => 'manage', 'label' => 'Jurnal Mengajar', 'group_name' => 'Akademik', 'sort_order' => 39],
            ['module' => 'holidays', 'action' => 'manage', 'label' => 'Kelola Hari Libur', 'group_name' => 'Akademik', 'sort_order' => 40],

            // ═══════ Kurikulum Merdeka ═══════
            ['module' => 'administrasi_guru', 'action' => 'manage', 'label' => 'Administrasi Guru (CP/TP/ATP)', 'group_name' => 'Kurikulum', 'sort_order' => 41],
            ['module' => 'kokurikuler', 'action' => 'manage', 'label' => 'Kelola Kokurikuler (P5)', 'group_name' => 'Kurikulum', 'sort_order' => 42],

            // ═══════ CBT (Ujian Online) ═══════
            ['module' => 'cbt', 'action' => 'view', 'label' => 'Lihat Data CBT', 'group_name' => 'CBT', 'sort_order' => 43],
            ['module' => 'cbt', 'action' => 'manage', 'label' => 'Kelola Bank Soal & Ujian', 'group_name' => 'CBT', 'sort_order' => 44],
            ['module' => 'cbt', 'action' => 'take', 'label' => 'Mengerjakan Ujian (Siswa)', 'group_name' => 'CBT', 'sort_order' => 45],

            // ═══════ Tata Usaha ═══════
            ['module' => 'surat_masuk', 'action' => 'manage', 'label' => 'Kelola Surat Masuk', 'group_name' => 'Tata Usaha', 'sort_order' => 46],
            ['module' => 'surat_keluar', 'action' => 'manage', 'label' => 'Kelola Surat Keluar', 'group_name' => 'Tata Usaha', 'sort_order' => 47],
            ['module' => 'agenda_surat', 'action' => 'view', 'label' => 'Lihat Agenda Surat', 'group_name' => 'Tata Usaha', 'sort_order' => 48],
            ['module' => 'buku_tamu', 'action' => 'manage', 'label' => 'Kelola Buku Tamu', 'group_name' => 'Tata Usaha', 'sort_order' => 49],
            ['module' => 'tata_usaha', 'action' => 'manage', 'label' => 'Cetak Daftar Hadir & Dokumen', 'group_name' => 'Tata Usaha', 'sort_order' => 50],

            // ═══════ CMS ═══════
            ['module' => 'cms', 'action' => 'manage', 'label' => 'Kelola Konten Website', 'group_name' => 'CMS', 'sort_order' => 51],

            // ═══════ Lainnya ═══════
            ['module' => 'announcements', 'action' => 'manage', 'label' => 'Kelola Pengumuman', 'group_name' => 'Lainnya', 'sort_order' => 52],
            ['module' => 'chat', 'action' => 'manage', 'label' => 'Chat Kelas', 'group_name' => 'Lainnya', 'sort_order' => 53],
            ['module' => 'staff_chat', 'action' => 'manage', 'label' => 'Chat Staff Internal', 'group_name' => 'Lainnya', 'sort_order' => 54],
            ['module' => 'student_notes', 'action' => 'manage', 'label' => 'Catatan Siswa', 'group_name' => 'Lainnya', 'sort_order' => 55],
            ['module' => 'behaviors', 'action' => 'manage', 'label' => 'Kelola Perilaku/ESPATA', 'group_name' => 'Lainnya', 'sort_order' => 56],
            ['module' => 'ebook', 'action' => 'manage', 'label' => 'Kelola Perpustakaan Digital', 'group_name' => 'Lainnya', 'sort_order' => 57],
            ['module' => 'ebook', 'action' => 'view', 'label' => 'Akses Perpustakaan Digital', 'group_name' => 'Lainnya', 'sort_order' => 58],
            ['module' => 'dapodik', 'action' => 'manage', 'label' => 'Integrasi Dapodik', 'group_name' => 'Lainnya', 'sort_order' => 59],
            ['module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan', 'group_name' => 'Lainnya', 'sort_order' => 60],
            // ═══════ Notifikasi ═══════
            ['module' => 'notifications', 'action' => 'view', 'label' => 'Lihat Notifikasi', 'group_name' => 'Lainnya', 'sort_order' => 61],
            ['module' => 'dapodik', 'action' => 'manage', 'label' => 'Integrasi Dapodik', 'group_name' => 'Lainnya', 'sort_order' => 59],
            ['module' => 'reports', 'action' => 'view', 'label' => 'Lihat Laporan', 'group_name' => 'Lainnya', 'sort_order' => 60],
        ];

        foreach ($permissions as $perm) {
            $existing = $this->db->table('permissions')
                ->where('module', $perm['module'])
                ->where('action', $perm['action'])
                ->get()->getRowArray();

            if (!$existing) {
                $this->db->table('permissions')->insert($perm);
            }
        }

        // ═══════ Default role_permissions untuk role yang sudah ada ═══════
        $this->seedDefaultRolePermissions();
    }

    private function seedDefaultRolePermissions()
    {
        // Ambil semua permission IDs
        $allPerms = $this->db->table('permissions')->get()->getResultArray();
        $permMap = [];
        foreach ($allPerms as $p) {
            $permMap[$p['module'] . '.' . $p['action']] = $p['id'];
        }

        // Admin (role_id = 1) — semua permission
        $adminPerms = array_values($permMap);
        $this->assignPermissions(1, $adminPerms);

        // Kepala Sekolah (role_id = 2) — view semua + beberapa manage
        $kepsekPerms = [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'school.manage', 'academic_year.manage',
            'students.view', 'classes.manage', 'alumni.view', 'student_mutation.manage', 'student_map.view',
            'teachers.view', 'teacher_attendance.view',
            'subjects.manage', 'teaching_assignments.manage', 'schedules.view',
            'attendance.view', 'grades.view', 'assessments.manage', 'erapor.manage',
            'administrasi_guru.manage', 'kokurikuler.manage',
            'cbt.view', 'cbt.manage',
            'surat_masuk.manage', 'surat_keluar.manage', 'agenda_surat.view',
            'buku_tamu.manage', 'tata_usaha.manage',
            'cms.manage', 'announcements.manage',
            'reports.view', 'dapodik.manage',
            'teaching_journal.manage', 'agendas.manage',
            'materials.manage', 'student_notes.manage', 'behaviors.manage',
            'chat.manage', 'staff_chat.manage',
        ];
        $this->assignPermissions(2, $this->resolvePermIds($permMap, $kepsekPerms));

        // Guru (role_id = 3) — fokus akademik
        $guruPerms = [
            'students.view', 'student_map.view',
            'attendance.view', 'attendance.manage',
            'grades.view', 'grades.manage',
            'assessments.manage', 'erapor.manage',
            'materials.manage', 'agendas.manage', 'teaching_journal.manage',
            'administrasi_guru.manage', 'kokurikuler.manage',
            'schedules.view',
            'cbt.view', 'cbt.manage',
            'announcements.manage',
            'chat.manage', 'staff_chat.manage',
            'student_notes.manage', 'behaviors.manage',
            'ebook.manage', 'ebook.view',
            'teacher_attendance.manage',
        ];
        $this->assignPermissions(3, $this->resolvePermIds($permMap, $guruPerms));

        // Kontributor (role_id = 6) — CMS saja
        $kontributorPerms = ['cms.manage', 'notifications.view'];
        $this->assignPermissions(6, $this->resolvePermIds($permMap, $kontributorPerms));

        // Staf / TU (role_id = 7)
        $stafPerms = [
            'school.manage', 'academic_year.manage',
            'students.view', 'students.create', 'students.update', 'students.delete', 'students.import',
            'classes.manage', 'placement.manage', 'promotions.manage', 'alumni.view',
            'student_mutation.manage', 'student_map.view',
            'teachers.view', 'teachers.create', 'teachers.update', 'teachers.delete',
            'teacher_attendance.manage', 'teacher_attendance.view',
            'subjects.manage', 'teaching_assignments.manage',
            'schedules.view', 'schedules.manage',
            'attendance.view',
            'grades.view',
            'surat_masuk.manage', 'surat_keluar.manage', 'agenda_surat.view',
            'buku_tamu.manage', 'tata_usaha.manage',
            'holidays.manage',
            'dapodik.manage',
            'staff_chat.manage',
            'ebook.manage', 'ebook.view',
            'reports.view',
            'notifications.view',
        ];
        $this->assignPermissions(7, $this->resolvePermIds($permMap, $stafPerms));

        // Role 4 (Orang Tua) dan 5 (Siswa) — biasanya tidak perlu admin permissions
        // Mereka aksesnya lewat route group siswa, bukan admin. Bisa ditambahkan jika perlu.
    }

    private function resolvePermIds(array $permMap, array $keys): array
    {
        $ids = [];
        foreach ($keys as $key) {
            if (isset($permMap[$key])) {
                $ids[] = $permMap[$key];
            }
        }
        return $ids;
    }

    private function assignPermissions(int $roleId, array $permissionIds)
    {
        foreach ($permissionIds as $permId) {
            $exists = $this->db->table('role_permissions')
                ->where('role_id', $roleId)
                ->where('permission_id', $permId)
                ->get()->getRowArray();

            if (!$exists) {
                $this->db->table('role_permissions')->insert([
                    'role_id'       => $roleId,
                    'permission_id' => $permId,
                ]);
            }
        }
    }
}
