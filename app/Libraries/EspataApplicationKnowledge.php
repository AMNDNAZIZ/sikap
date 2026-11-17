<?php

namespace App\Libraries;

/**
 * ESPATA Application Knowledge Base (EAKB)
 * Fondasi Katalog Pengetahuan Aplikasi ESPATA untuk Asis AI (Fase 1)
 * 
 * Standar Mandat: EAO-HO-2026-043
 * Arsitektur: Pure Declarative PHP Structure (Zero Database Query for Static Catalog)
 * Lingkungan: PHP 8.1+ / CodeIgniter 4
 */
class EspataApplicationKnowledge
{
    public const SCHEMA_VERSION = '1.0.0';
    public const DATA_VERSION   = '2026.11.17';

    /**
     * Kebijakan Final Akses Asis AI (Human Owner Mandate EAO-HO-2026-043):
     * Asis AI HANYA diizinkan untuk 4 role berikut.
     */
    public const ASIS_AI_ALLOWED_ROLES = [1, 2, 3, 7]; // Admin, Kepala Sekolah, Guru, Tata Usaha / Staf
    public const ASIS_AI_DENIED_ROLES  = [4, 5, 6, 8]; // Orang Tua, Siswa, Kontributor, Coach

    /**
     * Kamus Sinonim & Historical Query Aliases
     * Memetakan istilah awam/lama pengguna ke modul resmi ESPATA
     */
    public const HISTORICAL_ALIASES = [
        'p5' => [
            'target_feature' => 'feat_kok_planning',
            'official_term'  => 'Kokurikuler',
            'type'           => 'HISTORICAL_ALIAS',
            'guidance'       => 'Di ESPATA, projek penguatan karakter dikelola melalui modul Kokurikuler dengan 8 Dimensi Profil Lulusan dan KAIH.',
        ],
        'projek_p5' => [
            'target_feature' => 'feat_kok_planning',
            'official_term'  => 'Kokurikuler',
            'type'           => 'HISTORICAL_ALIAS',
            'guidance'       => 'Projek penguatan karakter siswa dikelola pada modul Kurikulum > Kokurikuler.',
        ],
        'absen_siswa' => [
            'target_feature' => 'feat_att_daily_class',
            'official_term'  => 'Presensi Siswa Harian',
            'type'           => 'SYNONYM',
            'guidance'       => 'Presensi siswa per rombel diakses melalui menu Akademik & KBM > Presensi Siswa.',
        ],
        'absen_guru' => [
            'target_feature' => 'feat_adm_teacher_attendance',
            'official_term'  => 'Presensi Guru & GTK',
            'type'           => 'SYNONYM',
            'guidance'       => 'Presensi kehadiran mandiri GTK diakses melalui menu Kepegawaian > Presensi Guru/GTK.',
        ],
        'bikin_soal' => [
            'target_feature' => 'feat_cbt_bank_soal',
            'official_term'  => 'Bank Soal CBT',
            'type'           => 'SYNONYM',
            'guidance'       => 'Penyusunan butir soal ujian pilihan ganda dan essay dikelola pada menu Ujian Online CBT > Bank Soal.',
        ],
        'raport_semester' => [
            'target_feature' => 'feat_grd_erapor',
            'official_term'  => 'E-Rapor Semester',
            'type'           => 'SYNONYM',
            'guidance'       => 'Pengolahan dan penerbitan rapor semester akhir dikelola pada menu Penilaian & Rapor > E-Rapor.',
        ],
        'raport_pts' => [
            'target_feature' => 'feat_grd_rapor_pts',
            'official_term'  => 'Rapor Tengah Semester (PTS)',
            'type'           => 'SYNONYM',
            'guidance'       => 'Pencetakan rapor sisipan tengah semester diakses pada menu Penilaian & Rapor > Cetak Rapor PTS.',
        ],
        'buat_surat' => [
            'target_feature' => 'feat_com_letters_in_out',
            'official_term'  => 'Agenda Surat Masuk & Keluar',
            'type'           => 'SYNONYM',
            'guidance'       => 'Pengelolaan penomoran surat keluar dan arsip surat masuk diakses pada menu Tata Usaha & Surat.',
        ],
    ];

    /**
     * 8 Dimensi Profil Lulusan Resmi ESPATA (Config\SubDimensiProfilLulusan)
     */
    public const PROFIL_LULUSAN_DIMENSI = [
        'Keimanan dan Ketakwaan Terhadap Tuhan YME',
        'Kewargaan',
        'Penalaran Kritis',
        'Kreativitas',
        'Kemandirian',
        'Kolaborasi',
        'Kesehatan',
        'Komunikasi',
    ];

    /**
     * Metadata Katalog
     */
    public static function getMetadata(): array
    {
        return [
            'catalog_name'       => 'ESPATA Application Knowledge Base (EAKB)',
            'schema_version'     => self::SCHEMA_VERSION,
            'data_version'       => self::DATA_VERSION,
            'total_features'     => 70,
            'active_features'    => 68,
            'limited_features'   => 1,
            'inactive_features'  => 1,
            'out_of_scope'       => ['bayaran', 'cbtku'],
            'environment'        => 'PHP 8.1+ / CodeIgniter 4 / MySQL 8.x',
            'last_verified_at'   => '2026-11-17T07:30:00+07:00',
            'proof_hierarchy'    => '5-Tier End-to-End Proof (Routes, Controllers, Views, DB, RBAC)',
        ];
    }

    /**
     * Periksa apakah Role ID diizinkan mengakses Asis AI
     */
    public static function isRoleAllowedForAsisAi(int $roleId): bool
    {
        return in_array($roleId, self::ASIS_AI_ALLOWED_ROLES, true);
    }

    /**
     * Ambil seluruh fitur terdaftar (70 Fitur)
     *
     * @return array<string, array>
     */
    public static function getAllFeatures(): array
    {
        return self::$catalog;
    }

    /**
     * Ambil fitur berdasarkan feature_id
     */
    public static function getFeature(string $featureId): ?array
    {
        return self::$catalog[$featureId] ?? null;
    }

    /**
     * Ambil seluruh Feature ID unik
     *
     * @return string[]
     */
    public static function getFeatureIds(): array
    {
        return array_keys(self::$catalog);
    }

    /**
     * Ambil fitur yang diizinkan untuk peran tertentu
     *
     * @return array<string, array>
     */
    public static function getFeaturesByRole(int $roleId): array
    {
        return array_filter(self::$catalog, function (array $feat) use ($roleId) {
            return in_array($roleId, $feat['allowed_roles'], true);
        });
    }

    /**
     * Ambil fitur berdasarkan kategori modul
     *
     * @return array<string, array>
     */
    public static function getFeaturesByCategory(string $category): array
    {
        return array_filter(self::$catalog, function (array $feat) use ($category) {
            return strcasecmp($feat['category'], $category) === 0;
        });
    }

    /**
     * Ambil kamus alias historis dan sinonim
     */
    public static function getHistoricalAliases(): array
    {
        return self::HISTORICAL_ALIASES;
    }

    /**
     * =========================================================================
     * KATALOG DEKLARATIF 70 FITUR INTI ESPATA
     * Kontrak Data 15 Atribut Terstandarisasi (Mandat EAO-HO-2026-043)
     * =========================================================================
     */
    private static array $catalog = [

        // ---------------------------------------------------------------------
        // KELOMPOK 1: PENGATURAN SISTEM & PROFIL
        // ---------------------------------------------------------------------

        'feat_sys_users' => [
            'feature_id' => 'feat_sys_users',
            'title' => 'Manajemen Akun Pengguna',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => [
                'Memiliki hak akses Administrator (Role 1)',
                'Tersedia role pengguna aktif dalam sistem'
            ],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Pengguna (/admin/users)',
                'Klik tombol Tambah User untuk membuat akun baru',
                'Isi formulir username, email, password, role, dan pengikatan profil GTK/Siswa',
                'Klik Simpan Data Pengguna'
            ],
            'validation_rules_and_errors' => [
                'Username wajib unik minimal 4 karakter (Duplikat: Username sudah terdaftar)',
                'Email wajib format valid',
                'Password minimal 6 karakter',
                'Role ID wajib dipilih sesuai tabel roles'
            ],
            'troubleshooting_faq' => [
                'Bagaimana jika user lupa password? Admin dapat menggunakan aksi Reset Password pada baris pengguna terkait.',
                'Mengapa akun nonaktif tidak bisa login? Pastikan status is_active bernilai 1 pada tabel users.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/User.php (Routes: GET /admin/users, POST /admin/users/store)',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_roles_rbac', 'feat_sys_account_sync'],
            'known_limitations' => ['Hanya Administrator sistem yang memiliki hak membuka dan memanipulasi data akun.']
        ],

        'feat_sys_roles_rbac' => [
            'feature_id' => 'feat_sys_roles_rbac',
            'title' => 'Manajemen Role & Hak Akses RBAC',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => [
                'Login sebagai Administrator (Role 1)',
                'Tabel permissions terisi permission resmi'
            ],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Hak Akses Role (/admin/roles)',
                'Pilih role yang ingin diatur permission-nya',
                'Centang modul dan aksi yang diizinkan',
                'Klik Simpan Permission Role'
            ],
            'validation_rules_and_errors' => [
                'Role Admin (Role 1) memiliki proteksi sistem dan tidak boleh dikurangi permission intinya'
            ],
            'troubleshooting_faq' => [
                'Mengapa perubahan hak akses tidak langsung aktif? Sistem menggunakan session cache; pengguna perlu logout dan login ulang.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/RolePermission.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_users'],
            'known_limitations' => ['Pengaturan permission dinamis hanya berlaku untuk modul admin/staff; portal siswa/ortu diisolasi di tingkat rute.']
        ],

        'feat_auth_login_profile' => [
            'feature_id' => 'feat_auth_login_profile',
            'title' => 'Otentikasi & Profil Pengguna',
            'category' => 'Sistem & Profil',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => [
                'Memiliki username dan password aktif'
            ],
            'workflow_steps' => [
                'Akses halaman login (/login)',
                'Masukkan username/NISN dan kata sandi',
                'Klik tombol Masuk',
                'Untuk ubah profil/password: klik avatar kanan atas > Edit Profil'
            ],
            'validation_rules_and_errors' => [
                'Username dan password wajib diisi',
                'Jika salah 5 kali berturut-turut, terkena rate limiting filter'
            ],
            'troubleshooting_faq' => [
                'Muncul pesan Akun Dinonaktifkan: Hubungi Admin untuk aktivasi kembali.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Auth.php, app/Controllers/Profile.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_users'],
            'known_limitations' => ['Wajib ubah password default saat pertama kali login jika fitur MustChangePassword aktif.']
        ],

        'feat_sys_account_sync' => [
            'feature_id' => 'feat_sys_account_sync',
            'title' => 'Sinkronisasi Massal Akun',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => [
                'Data GTK di tabel teachers atau siswa di tabel students sudah terinput'
            ],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Sinkronisasi Akun (/admin/account-sync)',
                'Pilih entitas target: Guru, Siswa, atau Orang Tua',
                'Klik tombol Generate Akun Otomatis',
                'Sistem membuat username dan password default massal'
            ],
            'validation_rules_and_errors' => [
                'Entitas yang sudah memiliki akun tidak akan diduplikasi'
            ],
            'troubleshooting_faq' => [
                'Akun orang tua tidak terbuat? Pastikan data siswa memiliki NIS/NISN yang valid.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AccountSync.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_users'],
            'known_limitations' => ['Proses sinkronisasi massal dalam jumlah ribuan siswa membutuhkan waktu eksekusi bertahap.']
        ],

        'feat_sch_identity' => [
            'feature_id' => 'feat_sch_identity',
            'title' => 'Profil Sekolah & KOP Surat',
            'category' => 'Pengaturan Sekolah',
            'allowed_roles' => [1, 2],
            'prerequisites' => ['Hak akses Admin atau Kepala Sekolah'],
            'workflow_steps' => [
                'Buka menu Pengaturan Sekolah > Identitas Sekolah (/admin/school)',
                'Perbarui nama sekolah, NPSN, alamat, kontak, dan unggah logo KOP surat',
                'Klik Simpan Profil Sekolah'
            ],
            'validation_rules_and_errors' => [
                'Nama sekolah dan NPSN wajib diisi',
                'Logo harus berformat PNG/JPG maks 2MB'
            ],
            'troubleshooting_faq' => [
                'KOP surat tidak muncul di rapor? Pastikan logo sekolah telah diunggah dan path tersimpan valid.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/School.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_erapor', 'feat_com_letters_in_out'],
            'known_limitations' => ['Pengaturan identitas sekolah berdampak ke seluruh kop dokumen resmi.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 2: AKADEMIK & KBM
        // ---------------------------------------------------------------------

        'feat_sch_academic_year' => [
            'feature_id' => 'feat_sch_academic_year',
            'title' => 'Pengelolaan Tahun Pelajaran',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Akademik & KBM > Tahun Pelajaran (/admin/academic-years)',
                'Klik Tambah Tahun Pelajaran baru (misal: 2026/2027 Ganjil)',
                'Pilih tahun ajaran aktif dengan menekan tombol Aktifkan'
            ],
            'validation_rules_and_errors' => [
                'Hanya tepat 1 tahun ajaran yang boleh berstatus is_active = 1'
            ],
            'troubleshooting_faq' => [
                'Data nilai tidak muncul? Pastikan tahun ajaran aktif sesuai dengan semester penilaian.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AcademicYear.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_active_classes', 'feat_sch_teaching_assignments'],
            'known_limitations' => ['Mengubah tahun ajaran aktif mengubah seluruh filter data operasional KBM di seluruh aplikasi.']
        ],

        'feat_sch_jenjang_master' => [
            'feature_id' => 'feat_sch_jenjang_master',
            'title' => 'Master Jenjang Multi-Tier (SD/SMP/SMA)',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Pengaturan > Jenjang Pendidikan (/admin/jenjang-master)',
                'Konfigurasi jenjang sekolah (SD level 1, SMP level 2, SMA/SMK level 3)'
            ],
            'validation_rules_and_errors' => ['Level jenjang wajib integer 1, 2, atau 3'],
            'troubleshooting_faq' => ['Fase kurikulum salah terpetakan? Pastikan jenjang sekolah pada profil terpasang benar.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/JenjangMaster.php, app/Services/JenjangService.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_identity', 'feat_cur_cp_tp_atp'],
            'known_limitations' => ['Jenjang menentukan ketersediaan modul khusus seperti SNBP Tracker.']
        ],

        'feat_sch_classes_master' => [
            'feature_id' => 'feat_sch_classes_master',
            'title' => 'Master Kelas & Ruang',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Administrasi Siswa > Master Kelas (/admin/classes)',
                'Tambah tingkatan dan nama rombel dasar (misal: 6A, 6B)'
            ],
            'validation_rules_and_errors' => ['Nama kelas wajib diisi dan unik per tingkat'],
            'troubleshooting_faq' => ['Kelas tidak bisa dihapus? Pastikan tidak ada data siswa atau penugasan yang terikat.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Classes.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_active_classes'],
            'known_limitations' => ['Master kelas mendefinisikan ruang fisik; penempatan rombel berjalan di kelas aktif.']
        ],

        'feat_sch_active_classes' => [
            'feature_id' => 'feat_sch_active_classes',
            'title' => 'Kelas Aktif & Penetapan Wali Kelas',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 2],
            'prerequisites' => ['Tahun ajaran aktif sudah ditentukan', 'Master kelas dan guru tersedia'],
            'workflow_steps' => [
                'Buka menu Administrasi Siswa > Kelas Aktif (/admin/active-classes)',
                'Tetapkan guru sebagai wali kelas untuk rombel pada tahun ajaran berjalan'
            ],
            'validation_rules_and_errors' => ['Satu rombel hanya boleh memiliki 1 wali kelas utama'],
            'troubleshooting_faq' => ['Guru tidak bisa cetak rapor wali? Pastikan guru sudah ditetapkan sebagai wali kelas di menu ini.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/ActiveClasses.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_classes_master', 'feat_grd_rapor_pas'],
            'known_limitations' => ['Berlaku per tahun ajaran aktif.']
        ],

        'feat_sch_student_placement' => [
            'feature_id' => 'feat_sch_student_placement',
            'title' => 'Penempatan Rombel Siswa',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 7],
            'prerequisites' => ['Data siswa terdaftar di tabel students', 'Kelas aktif telah dibuka'],
            'workflow_steps' => [
                'Buka menu Administrasi Siswa > Penempatan Rombel (/admin/placement)',
                'Pilih kelas tujuan dan centang siswa yang ditempatkan',
                'Klik Simpan Penempatan Rombel'
            ],
            'validation_rules_and_errors' => ['Siswa tidak boleh terdaftar di 2 rombel aktif sekaligus pada tahun ajaran yang sama'],
            'troubleshooting_faq' => ['Siswa tidak muncul di daftar absensi? Periksa penempatan rombel siswa di menu ini.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Placement.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_master_records', 'feat_sch_active_classes'],
            'known_limitations' => ['Wajib dilakukan di awal tahun ajaran sebelum input nilai dan presensi dimulai.']
        ],

        'feat_sch_promotions' => [
            'feature_id' => 'feat_sch_promotions',
            'title' => 'Kenaikan Kelas & Kelulusan',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 7],
            'prerequisites' => ['Penilaian akhir semester genap selesai', 'Rapat pleno kelulusan'],
            'workflow_steps' => [
                'Buka menu Administrasi Siswa > Kenaikan Kelas (/admin/promotions)',
                'Pilih rombel asal, tentukan status (Naik Kelas / Tinggal / Lulus)',
                'Pilih kelas tujuan di tahun ajaran baru dan proses kenaikan'
            ],
            'validation_rules_and_errors' => ['Tahun ajaran baru harus sudah dibuat sebelum memproses kenaikan'],
            'troubleshooting_faq' => ['Siswa salah naik kelas? Gunakan fitur rollback kenaikan kelas sebelum data baru diinput.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Promotions.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_student_placement'],
            'known_limitations' => ['Operasi massal kritikal yang mengubah riwayat student_records.']
        ],

        'feat_sch_teaching_assignments' => [
            'feature_id' => 'feat_sch_teaching_assignments',
            'title' => 'SK Penugasan Mengajar Guru',
            'category' => 'Kepegawaian',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Data guru aktif, master mapel, dan kelas aktif tersedia'],
            'workflow_steps' => [
                'Buka menu Kepegawaian > Penugasan Mengajar (/admin/teaching-assignments)',
                'Tetapkan guru, mata pelajaran yang diampu, kelas sasaran, dan alokasi JP',
                'Simpan penugasan mengajar'
            ],
            'validation_rules_and_errors' => [
                'Kombinasi guru, mata pelajaran, kelas, dan tahun ajaran harus valid'
            ],
            'troubleshooting_faq' => [
                'Guru mengeluh kelas tidak muncul di input nilai? Cek apakah SK penugasan mengajar guru tersebut sudah terinput di menu ini.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/TeachingAssignments.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_adm_schedules'],
            'known_limitations' => ['Menjadi fondasi otorisasi data scoping Guru (Role 3) di seluruh modul KBM.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 3: KURIKULUM & ADMINISTRASI GURU
        // ---------------------------------------------------------------------

        'feat_cur_subjects_master' => [
            'feature_id' => 'feat_cur_subjects_master',
            'title' => 'Master Mata Pelajaran',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 7],
            'prerequisites' => ['Hak akses Admin atau Tata Usaha'],
            'workflow_steps' => [
                'Buka menu Kurikulum > Mata Pelajaran (/admin/subjects)',
                'Tambah kode mapel, nama mapel, kelompok (Umum/Kejuruan/Pilihan), dan urutan cetak rapor'
            ],
            'validation_rules_and_errors' => ['Kode mapel wajib unik'],
            'troubleshooting_faq' => ['Mapel tidak tampil di rapor? Pastikan status mapel aktif dan urutan cetak terisi.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Subjects.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_teaching_assignments'],
            'known_limitations' => ['Master mapel berlaku institusional di seluruh rombel.']
        ],

        'feat_cur_cp_tp_atp' => [
            'feature_id' => 'feat_cur_cp_tp_atp',
            'title' => 'Perumusan CP, TP, & ATP Kurikulum Merdeka',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['SK Penugasan Mengajar aktif pada mapel terkait'],
            'workflow_steps' => [
                'Buka menu Administrasi Guru > CP, TP, & ATP (/admin/administrasi-guru/cp-tp-atp)',
                'Pilih mata pelajaran dan fase kurikulum (Fase A/B/C/D/E/F)',
                'Input Capaian Pembelajaran (CP) dan turunkan menjadi Tujuan Pembelajaran (TP)',
                'Urutkan TP ke dalam Alur Tujuan Pembelajaran (ATP)'
            ],
            'validation_rules_and_errors' => ['Kode TP wajib unik dalam satu mata pelajaran dan fase'],
            'troubleshooting_faq' => ['TP tidak muncul saat input nilai? Pastikan TP sudah disimpan dengan status aktif.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AdministrasiGuru.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cur_modul_ajar', 'feat_grd_input'],
            'known_limitations' => ['Prasyarat utama sebelum Modul Ajar dan Penilaian Kurikulum Merdeka dapat dijalankan.']
        ],

        'feat_cur_modul_ajar' => [
            'feature_id' => 'feat_cur_modul_ajar',
            'title' => 'Modul Ajar Kurikulum Merdeka',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['TP/ATP sudah dirumuskan'],
            'workflow_steps' => [
                'Buka menu Administrasi Guru > Modul Ajar (/admin/administrasi-guru/modul-ajar)',
                'Klik Buat Modul Ajar, pilih target TP, alokasi jam, langkah KBM, dan instrumen asesmen',
                'Simpan atau cetak format PDF Modul Ajar'
            ],
            'validation_rules_and_errors' => ['Target TP wajib dipilih'],
            'troubleshooting_faq' => ['Komponen modul terpotong saat cetak PDF? Gunakan tata letak standar portrait A4.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AdministrasiGuru.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cur_cp_tp_atp', 'feat_cur_modul_ajar_ai'],
            'known_limitations' => ['Mendukung lampiran materi dan asesmen formatif terintegrasi.']
        ],

        'feat_cur_modul_ajar_ai' => [
            'feature_id' => 'feat_cur_modul_ajar_ai',
            'title' => 'Generator AI Modul Ajar',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Koneksi AI aktif / API Key terpasang'],
            'workflow_steps' => [
                'Buka menu Modul Ajar > klik tombol Bantuan AI Generator',
                'Pilih materi dan tujuan pembelajaran sasaran',
                'Asis AI menyusun draf langkah pembelajaran berdiferensiasi dan asesmen',
                'Guru mereviu dan menyimpan hasil generasi ke modul ajar'
            ],
            'validation_rules_and_errors' => ['Guru wajib mereviu dan menyetujui draf sebelum disimpan (Human-in-the-Loop)'],
            'troubleshooting_faq' => ['AI gagal merespons? Periksa kuota harian atau status koneksi provider di pengaturan AI.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AdministrasiGuru.php, app/Controllers/Api/AiCopilot.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cur_modul_ajar', 'feat_ai_asis_copilot'],
            'known_limitations' => ['Menghasilkan draf; tanggung jawab pedagogis tetap pada guru pengampu.']
        ],

        'feat_cur_prota_promes' => [
            'feature_id' => 'feat_cur_prota_promes',
            'title' => 'Distribusi Prota & Promes',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Kalender akademik aktif dan TP mapel tersedia'],
            'workflow_steps' => [
                'Buka menu Administrasi Guru > Prota & Promes (/admin/administrasi-guru/prota-promes)',
                'Tentukan alokasi pekan efektif dan distribusikan jam per TP per bulan'
            ],
            'validation_rules_and_errors' => ['Total alokasi jam tidak boleh melebihi pekan efektif semester'],
            'troubleshooting_faq' => ['Jumlah jam tidak cocok? Periksa kembali kalender akademik dan hari libur.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AdministrasiGuru.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_calendar_holidays', 'feat_cur_cp_tp_atp'],
            'known_limitations' => ['Digunakan sebagai panduan distribusi alokasi waktu tahunan dan semesteran guru.']
        ],

        'feat_cur_kktp' => [
            'feature_id' => 'feat_cur_kktp',
            'title' => 'Kriteria Ketuntasan Tujuan Pembelajaran (KKTP)',
            'category' => 'Kurikulum',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['TP telah dirumuskan'],
            'workflow_steps' => [
                'Buka menu Administrasi Guru > KKTP (/admin/administrasi-guru/kktp)',
                'Tentukan interval nilai ketuntasan (misal: 0-60 Perlu Bimbingan, 61-75 Cukup, 76-85 Baik, 86-100 Sangat Baik)'
            ],
            'validation_rules_and_errors' => ['Rentang interval skala nilai wajib berkesinambungan 0 sampai 100'],
            'troubleshooting_faq' => ['Kategori capaian rapor salah? Periksa interval KKTP mapel pada semester bersangkutan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AdministrasiGuru.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_grd_erapor'],
            'known_limitations' => ['Menjadi acuan pembentukan deskripsi otomatis capaian kompetensi pada rapor siswa.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 4: JADWAL, JURNAL, & PRESENSI KBM
        // ---------------------------------------------------------------------

        'feat_adm_schedules' => [
            'feature_id' => 'feat_adm_schedules',
            'title' => 'Jadwal Pelajaran Mingguan',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3, 4, 5, 7],
            'prerequisites' => ['Penugasan mengajar guru dan kelas aktif tersedia'],
            'workflow_steps' => [
                'Admin/TU menyusun jadwal per hari dan jam di /admin/schedule',
                'Guru/Siswa/Ortu melihat jadwal pelajaran pada dashboard masing-masing'
            ],
            'validation_rules_and_errors' => ['Guru atau kelas tidak boleh bentrok pada jam dan hari yang sama'],
            'troubleshooting_faq' => ['Jadwal tidak tampil di portal siswa? Pastikan rombel siswa sudah aktif di kelas tersebut.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/ScheduleController.php, app/Controllers/Siswa/Schedules.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_teaching_assignments', 'feat_adm_teaching_journal'],
            'known_limitations' => ['Deteksi bentrok otomatis mencegah jadwal ganda guru.']
        ],

        'feat_adm_teaching_journal' => [
            'feature_id' => 'feat_adm_teaching_journal',
            'title' => 'Jurnal Mengajar Harian Guru',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Jadwal pelajaran aktif terpasang'],
            'workflow_steps' => [
                'Buka menu Akademik & KBM > Jurnal Mengajar (/admin/teaching-journal)',
                'Pilih pertemuan KBM, isi materi/TP yang diajarkan, absensi siswa, dan catatan evaluasi',
                'Simpan jurnal harian'
            ],
            'validation_rules_and_errors' => ['Materi pertemuan dan tanggal wajib diisi'],
            'troubleshooting_faq' => ['Jurnal tidak tersimpan? Pastikan guru memiliki penugasan mengajar di rombel tersebut.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/TeachingJournalController.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_adm_schedules', 'feat_att_daily_class'],
            'known_limitations' => ['Menjadi bukti fisik kehadiran KBM guru dan rekap kinerja harian.']
        ],

        'feat_adm_class_agendas' => [
            'feature_id' => 'feat_adm_class_agendas',
            'title' => 'Agenda Kelas & Catatan Pertemuan',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3, 5],
            'prerequisites' => ['Kelas aktif'],
            'workflow_steps' => [
                'Wali kelas atau guru piket mengisi agenda kelas harian di /admin/agendas',
                'Siswa dapat melihat catatan agenda kelas di portal siswa'
            ],
            'validation_rules_and_errors' => ['Tanggal agenda wajib valid'],
            'troubleshooting_faq' => ['Agenda tidak muncul di siswa? Pastikan rombel siswa sama dengan rombel agenda.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Agendas.php, app/Controllers/Siswa/Agendas.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_adm_teaching_journal'],
            'known_limitations' => ['Digunakan untuk pengawasan kegiatan harian di ruang kelas.']
        ],

        'feat_adm_teacher_attendance' => [
            'feature_id' => 'feat_adm_teacher_attendance',
            'title' => 'Presensi Harian Guru & GTK',
            'category' => 'Kepegawaian',
            'allowed_roles' => [1, 2, 3, 7],
            'prerequisites' => ['Akun GTK aktif'],
            'workflow_steps' => [
                'Buka menu Kepegawaian > Presensi Guru/GTK (/admin/teacher-attendance)',
                'Lakukan Check-In saat tiba dan Check-Out saat jam kerja berakhir',
                'Atau dicatat massal oleh operator absensi sekolah'
            ],
            'validation_rules_and_errors' => ['Hanya bisa check-in satu kali per tanggal kalender'],
            'troubleshooting_faq' => ['Lokasi presensi ditolak? Pastikan GPS aktif jika presensi berbasis radius diaktifkan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/TeacherAttendance.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_adm_attendance_report'],
            'known_limitations' => ['Berbeda dengan presensi siswa di kelas; ini mencatat kehadiran kerja pegawai.']
        ],

        'feat_adm_attendance_report' => [
            'feature_id' => 'feat_adm_attendance_report',
            'title' => 'Laporan Rekapitulasi Absensi GTK',
            'category' => 'Kepegawaian',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Data presensi guru harian terisi'],
            'workflow_steps' => [
                'Buka menu Kepegawaian > Laporan Absensi GTK',
                'Pilih rentang bulan/tahun dan klik Ekspor Excel atau Cetak PDF'
            ],
            'validation_rules_and_errors' => ['Rentang tanggal laporan valid'],
            'troubleshooting_faq' => ['Rekap kosong? Pastikan filter bulan dan tahun ajaran sesuai.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/TeacherAttendance.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_adm_teacher_attendance'],
            'known_limitations' => ['Digunakan untuk pelaporan ke dinas atau yayasan sekolah.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 5: KOKURIKULER (PROJEK PENGUATAN KARAKTER ESPATA)
        // ---------------------------------------------------------------------

        'feat_kok_planning' => [
            'feature_id' => 'feat_kok_planning',
            'title' => 'Perencanaan Projek Kokurikuler',
            'category' => 'Kokurikuler',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Tahun ajaran aktif dan tim fasilitator projek ditunjuk'],
            'workflow_steps' => [
                'Buka menu Kurikulum > Kokurikuler (/admin/kokurikuler)',
                'Klik Tambah Projek, tentukan tema projek dan alokasi jam',
                'Pilih dimensi target dari 8 Dimensi Profil Lulusan ESPATA dan opsi KAIH',
                'Tentukan koordinator dan kelas sasaran'
            ],
            'validation_rules_and_errors' => [
                'Judul tema minimal 3 karakter',
                'Dimensi profil wajib dipilih minimal 1 dimensi'
            ],
            'troubleshooting_faq' => [
                'Mencari modul P5? Di ESPATA gunakan modul Kokurikuler. Seluruh tema dan rapor projek dikelola di menu ini.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Kokurikuler.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_kok_ai_planner', 'feat_kok_execution', 'feat_kok_assessment_rapor'],
            'known_limitations' => ['Menggunakan 8 Dimensi Profil Lulusan ESPATA dan KAIH (bukan kerangka lama).']
        ],

        'feat_kok_ai_planner' => [
            'feature_id' => 'feat_kok_ai_planner',
            'title' => 'Generator AI Perencana Projek Kokurikuler',
            'category' => 'Kokurikuler',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Tema projek dan fase sasaran dipilih'],
            'workflow_steps' => [
                'Pada form projek kokurikuler, klik tombol Susun dengan Asis AI',
                'Asis AI merumuskan ide aktivitas kontekstual, dimensi profil, dan instrumen asesmen',
                'Fasilitator mereviu dan menyetujui draf modul projek'
            ],
            'validation_rules_and_errors' => ['Fasilitator wajib mereviu draf sebelum aktivasi'],
            'troubleshooting_faq' => ['Respon AI lambat? Pastikan kueri mencantumkan topik projek yang spesifik.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Kokurikuler.php, app/Controllers/Api/AiCopilot.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_kok_planning', 'feat_ai_asis_copilot'],
            'known_limitations' => ['Membantu percepatan penyusunan modul projek kokurikuler.']
        ],

        'feat_kok_execution' => [
            'feature_id' => 'feat_kok_execution',
            'title' => 'Log Pelaksanaan Projek Kokurikuler',
            'category' => 'Kokurikuler',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Projek kokurikuler telah berstatus aktif'],
            'workflow_steps' => [
                'Buka detail projek kokurikuler, pilih tab Pelaksanaan',
                'Catat tanggal aktivitas, dokumentasi kegiatan, dan observasi perkembangan siswa'
            ],
            'validation_rules_and_errors' => ['Tanggal log wajib diisi'],
            'troubleshooting_faq' => ['Tidak bisa tambah log? Pastikan Anda terdaftar sebagai koordinator/fasilitator projek tersebut.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Kokurikuler.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_kok_planning', 'feat_kok_assessment_rapor'],
            'known_limitations' => ['Merekam jurnal lapangan selama projek berjalan.']
        ],

        'feat_kok_assessment_rapor' => [
            'feature_id' => 'feat_kok_assessment_rapor',
            'title' => 'Asesmen Dimensi & Rapor Projek Kokurikuler',
            'category' => 'Kokurikuler',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Projek selesai dilaksanakan'],
            'workflow_steps' => [
                'Buka tab Penilaian Projek, nilai capaian siswa pada setiap sub-elemen dimensi (MB/SB/BSH/SAB)',
                'Cetak format Rapor Projek Kokurikuler PDF'
            ],
            'validation_rules_and_errors' => ['Seluruh sub-dimensi target wajib dinilai sebelum cetak rapor'],
            'troubleshooting_faq' => ['Deskripsi rapor kosong? Pastikan catatan proses fasilitator telah diisi.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Kokurikuler.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_kok_planning'],
            'known_limitations' => ['Format rapor terpisah dari rapor akademik reguler sesuai panduan kurikulum kementerian.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 6: UJIAN ONLINE CBT
        // ---------------------------------------------------------------------

        'feat_cbt_bank_soal' => [
            'feature_id' => 'feat_cbt_bank_soal',
            'title' => 'Bank Soal CBT (Pilihan Ganda & Essay)',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['SK Penugasan Mengajar aktif pada mapel terkait'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Bank Soal (/admin/cbt/banksoal)',
                'Klik Tambah Bank Soal, tentukan kode bank, mapel, dan tingkatan kelas',
                'Input butir soal pilihan ganda (kunci jawaban & opsi A-E) atau soal uraian',
                'Atau gunakan fitur Import Soal via format Excel'
            ],
            'validation_rules_and_errors' => [
                'Kode bank soal wajib unik',
                'Kunci jawaban soal pilihan ganda wajib valid',
                'Bobot butir soal wajib terisi'
            ],
            'troubleshooting_faq' => [
                'Gambar pada soal tidak muncul? Pastikan gambar diunggah melalui tombol upload gambar editor dan berukuran maks 1MB.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CbtBankSoal.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_exam_schedule', 'feat_cbt_student_room'],
            'known_limitations' => ['Mendukung soal acak (randomize) dan acak pilihan jawaban saat jadwal ujian dibuat.']
        ],

        'feat_cbt_exam_schedule' => [
            'feature_id' => 'feat_cbt_exam_schedule',
            'title' => 'Penjadwalan & Rilis Token CBT',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Bank soal telah terisi butir soal lengkap'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Jadwal Ujian (/admin/cbt/jadwal)',
                'Klik Tambah Jadwal, pilih bank soal, kelas peserta, durasi pengerjaan, dan rentang waktu ujian',
                'Saat ujian berlangsung: klik tombol Rilis Token untuk mengaktifkan token 6 karakter bagi peserta'
            ],
            'validation_rules_and_errors' => [
                'Waktu mulai harus lebih kecil dari waktu selesai ujian',
                'Durasi ujian minimal 10 menit'
            ],
            'troubleshooting_faq' => [
                'Siswa tidak melihat token? Pastikan rentang waktu ujian sudah masuk dan tombol Rilis Token sudah ditekan.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/ExamSchedule.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_bank_soal', 'feat_cbt_student_room', 'feat_cbt_monitoring'],
            'known_limitations' => ['Token dapat di-refresh secara berkala oleh pengawas untuk mencegah kebocoran.']
        ],

        'feat_cbt_student_room' => [
            'feature_id' => 'feat_cbt_student_room',
            'title' => 'Ruang Ujian Online CBT Siswa',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [5],
            'prerequisites' => [
                'Login sebagai Siswa (Role 5)',
                'Jadwal ujian aktif dan token telah dirilis pengawas'
            ],
            'workflow_steps' => [
                'Siswa masuk ke portal siswa > Ujian CBT (/siswa/cbt)',
                'Pilih ujian yang sedang berlangsung dan masukkan Token Ujian',
                'Baca peraturan ujian dan klik Mulai Ujian',
                'Jawab butir soal pilihan ganda/essay sebelum waktu durasi habis',
                'Klik Selesai Ujian dan konfirmasi pengiriman jawaban'
            ],
            'validation_rules_and_errors' => [
                'Token ujian wajib cocok dengan token aktif pada jadwal',
                'Waktu habis otomatis mengirimkan jawaban tersimpan (auto-submit)',
                'Siswa dilarang membuka tab lain jika fitur deteksi kecurangan aktif'
            ],
            'troubleshooting_faq' => [
                'Ujian tiba-tiba logout? Jangan panik, jawaban tersimpan otomatis di server. Hubungi pengawas untuk reset login jika terkunci.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Siswa/Cbt.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_exam_schedule', 'feat_cbt_monitoring'],
            'known_limitations' => ['Hanya dapat diakses melalui akun Siswa (Role 5); Orang tua tidak dapat mengerjakan ujian anak.']
        ],

        'feat_cbt_monitoring' => [
            'feature_id' => 'feat_cbt_monitoring',
            'title' => 'Monitoring CBT & Reset Login Siswa',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Ujian sedang berlangsung'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Monitoring Aktivitas (/admin/cbt/aktivitas)',
                'Pantau status peserta (Mengerjakan, Selesai, Terputus, Nilai Sementara)',
                'Jika browser siswa crash: klik tombol Reset Login pada baris siswa terkait'
            ],
            'validation_rules_and_errors' => ['Reset login hanya dapat dilakukan saat sesi ujian masih berlangsung'],
            'troubleshooting_faq' => ['Siswa tidak bisa login ulang setelah listrik padam? Lakukan Reset Login di menu monitoring ini.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CbtAktivitas.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_student_room'],
            'known_limitations' => ['Alat utama proktor/pengawas saat pelaksanaan ujian berlangsung.']
        ],

        'feat_cbt_essay_grading' => [
            'feature_id' => 'feat_cbt_essay_grading',
            'title' => 'Koreksi & Penilaian Jawaban Essay CBT',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Siswa telah menyelesaikan ujian yang memuat butir essay'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Koreksi Essay (/admin/cbt/koreksi-essay)',
                'Pilih jadwal ujian, baca jawaban essay siswa, dan masukkan nilai per butir soal',
                'Simpan nilai koreksi untuk mengakumulasi skor akhir'
            ],
            'validation_rules_and_errors' => ['Nilai essay tidak boleh melebihi bobot maksimum butir soal'],
            'troubleshooting_faq' => ['Nilai total siswa belum keluar? Pastikan seluruh jawaban essay telah dinilai oleh guru pengampu.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CbtAktivitasEssay.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_convert_grades'],
            'known_limitations' => ['Nilai pilihan ganda otomatis dikoreksi sistem; soal essay dinilai secara manual oleh guru.']
        ],

        'feat_cbt_bap' => [
            'feature_id' => 'feat_cbt_bap',
            'title' => 'Berita Acara Ujian CBT (BAP)',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Sesi ujian telah selesai dilaksanakan'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Berita Acara Ujian (/admin/cbt/bap)',
                'Pilih jadwal ujian, catat jumlah peserta hadir, tidak hadir, nama pengawas, dan catatan kejadian penting',
                'Cetak format Berita Acara resmi PDF'
            ],
            'validation_rules_and_errors' => ['Nama pengawas wajib diisi'],
            'troubleshooting_faq' => ['Jumlah peserta tidak cocok? Pastikan seluruh siswa telah menekan tombol Selesai Ujian.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CbtBap.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_exam_schedule'],
            'known_limitations' => ['Dokumen akreditasi dan administrasi resmi pelaksanaan ujian.']
        ],

        'feat_cbt_convert_grades' => [
            'feature_id' => 'feat_cbt_convert_grades',
            'title' => 'Konversi Nilai CBT ke Nilai KBM',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 3],
            'prerequisites' => ['Nilai CBT telah final (koreksi essay selesai)'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Konversi Nilai (/admin/cbt/convert-nilai)',
                'Pilih jadwal CBT dan pilih komponen penilaian tujuan (misal: Sumatif Akhir Semester / SAS)',
                'Klik tombol Konversi Nilai ke Buku Nilai'
            ],
            'validation_rules_and_errors' => ['Komponen penilaian sasaran harus sudah dibuka di tabel grades'],
            'troubleshooting_faq' => ['Nilai tidak masuk ke buku nilai? Pastikan mapel CBT sama dengan mapel buku nilai.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CbtConvertNilai.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_cbt_exam_schedule'],
            'known_limitations' => ['Mengintegrasikan hasil tes CBT langsung ke dalam leger nilai tanpa input manual ulang.']
        ],

        'feat_cbt_exam_cards' => [
            'feature_id' => 'feat_cbt_exam_cards',
            'title' => 'Cetak Kartu Peserta Ujian',
            'category' => 'Ujian Online CBT',
            'allowed_roles' => [1, 3, 7],
            'prerequisites' => ['Siswa terdaftar di rombel aktif dan memiliki nomor peserta/NISN'],
            'workflow_steps' => [
                'Buka menu Ujian Online CBT > Kartu Peserta (/admin/kartu-peserta)',
                'Pilih rombel dan sesi ujian, lalu klik Cetak Kartu Peserta PDF (format 8 kartu per lembar A4)'
            ],
            'validation_rules_and_errors' => ['Data siswa wajib memiliki foto dan nomor peserta'],
            'troubleshooting_faq' => ['Foto tidak muncul di kartu? Pastikan foto profil siswa telah diunggah di data induk kesiswaan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/KartuPeserta.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_master_records'],
            'known_limitations' => ['Memuat barcode/QR verifikasi data peserta ujian.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 7: PENILAIAN & RAPOR
        // ---------------------------------------------------------------------

        'feat_grd_input' => [
            'feature_id' => 'feat_grd_input',
            'title' => 'Input Nilai Formatif & Sumatif',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => [
                'Tahun ajaran aktif',
                'SK Penugasan Mengajar aktif pada rombel bersangkutan',
                'Tujuan Pembelajaran (TP) telah dirumuskan'
            ],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > Input Nilai (/admin/grades)',
                'Pilih rombel dan mata pelajaran ampu',
                'Pilih jenis penilaian: Asesmen Formatif per TP atau Asesmen Sumatif Lingkup Materi / Akhir Semester',
                'Input skor nilai siswa (skala 0–100)',
                'Klik tombol Simpan Nilai'
            ],
            'validation_rules_and_errors' => [
                'Nilai wajib berada dalam rentang angka 0 sampai 100',
                'Bobot persentase penilaian total wajib tepat 100% (Error: Bobot belum valid)'
            ],
            'troubleshooting_faq' => [
                'Rombel kelas tidak muncul di pilihan? Anda hanya dapat memilih kelas yang terdaftar pada SK Penugasan Mengajar Anda.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Grades.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_teaching_assignments', 'feat_grd_leger', 'feat_grd_erapor'],
            'known_limitations' => ['Guru Role 3 terkunci hanya pada rombel ampunya; Admin dan Kepsek dapat mengakses semua rombel.']
        ],

        'feat_grd_leger' => [
            'feature_id' => 'feat_grd_leger',
            'title' => 'Leger Nilai Siswa Per Rombel',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Nilai mata pelajaran telah diinput'],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > Leger Nilai (/admin/scores)',
                'Pilih kelas dan semester aktif, sistem menampilkan tabel rekapitulasi seluruh nilai mapel dan peringkat siswa',
                'Klik tombol Ekspor Excel'
            ],
            'validation_rules_and_errors' => ['Kelas wajib dipilih'],
            'troubleshooting_faq' => ['Ada nilai kosong di leger? Hubungi guru mapel terkait melalui fitur monitoring nilai.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Scores.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_grd_erapor'],
            'known_limitations' => ['Menghitung rata-rata nilai dan peringkat rombel secara otomatis.']
        ],

        'feat_grd_monitoring' => [
            'feature_id' => 'feat_grd_monitoring',
            'title' => 'Monitoring Keterisian Nilai Guru',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2],
            'prerequisites' => ['SK penugasan mengajar aktif terpasang'],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > Monitoring Nilai (/admin/grade-monitoring)',
                'Lihat matriks keterisian nilai per guru dan per rombel (status Belum, Sebagian, Selesai)',
                'Kirimkan notifikasi pengingat batas waktu pengisian nilai'
            ],
            'validation_rules_and_errors' => ['Hanya dapat diakses Admin dan Kepala Sekolah'],
            'troubleshooting_faq' => ['Guru sudah isi tapi status belum selesai? Periksa apakah komponen sumatif akhir sudah tersimpan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/GradeMonitoringController.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_sch_teaching_assignments'],
            'known_limitations' => ['Alat supervisi manajerial kepala sekolah menjelang pencetakan rapor semester.']
        ],

        'feat_grd_erapor' => [
            'feature_id' => 'feat_grd_erapor',
            'title' => 'E-Rapor Semester Akhir',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Seluruh nilai mapel, ekstrakurikuler, dan catatan wali kelas telah terisi'],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > E-Rapor (/admin/erapor)',
                'Pilih rombel dan siswa, periksa deskripsi capaian otomatis Kurikulum Merdeka',
                'Klik tombol Cetak E-Rapor PDF resmi (lengkap dengan KOP, tanda tangan digital, dan QR code)'
            ],
            'validation_rules_and_errors' => ['Deskripsi capaian kompetensi otomatis terbentuk berdasarkan TP tertinggi dan terendah'],
            'troubleshooting_faq' => ['Deskripsi rapor tidak muncul? Periksa input nilai formatif per TP pada mapel bersangkutan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Erapor.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_grd_rapor_pas', 'feat_adv_qr_verify'],
            'known_limitations' => ['Format rapor sesuai regulasi Kurikulum Merdeka kementerian pendidikan.']
        ],

        'feat_grd_rapor_pts' => [
            'feature_id' => 'feat_grd_rapor_pts',
            'title' => 'Cetak Rapor Tengah Semester (PTS)',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Nilai formatif dan sumatif tengah semester telah diinput'],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > Cetak Rapor PTS (/admin/rapor)',
                'Pilih rombel dan cetak format rapor sisipan tengah semester'
            ],
            'validation_rules_and_errors' => ['Rombel wajib dipilih'],
            'troubleshooting_faq' => ['Nilai PTS belum tampil? Pastikan guru sudah memilih jenis penilaian PTS saat menyimpan nilai.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Rapor.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_input', 'feat_grd_erapor'],
            'known_limitations' => ['Digunakan untuk laporan kemajuan belajar mid-semester kepada orang tua siswa.']
        ],

        'feat_grd_rapor_pas' => [
            'feature_id' => 'feat_grd_rapor_pas',
            'title' => 'Catatan Wali Kelas & Cetak Rapor PAS',
            'category' => 'Penilaian & Rapor',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Guru bertindak sebagai Wali Kelas sah pada rombel aktif'],
            'workflow_steps' => [
                'Buka menu Penilaian & Rapor > Catatan Wali Kelas (/admin/rapor-semester)',
                'Input catatan motivasi wali kelas, presensi rekap semester, dan catatan kesehatan siswa',
                'Simpan catatan untuk digabungkan ke cetakan E-Rapor'
            ],
            'validation_rules_and_errors' => ['Hanya wali kelas dari rombel terkait yang berhak menginput catatan'],
            'troubleshooting_faq' => ['Tidak bisa input catatan? Pastikan Anda ditetapkan sebagai wali kelas di menu Kelas Aktif.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/RaporSemester.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_active_classes', 'feat_grd_erapor'],
            'known_limitations' => ['Khusus wali kelas dan pimpinan sekolah.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 8: PRESENSI KESISWAAN
        // ---------------------------------------------------------------------

        'feat_att_daily_class' => [
            'feature_id' => 'feat_att_daily_class',
            'title' => 'Presensi Siswa Harian Per Rombel',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Penempatan rombel siswa selesai'],
            'workflow_steps' => [
                'Buka menu Akademik & KBM > Presensi Siswa (/admin/attendance)',
                'Pilih kelas dan tanggal, tandai status siswa (Hadir, Sakit, Izin, Alpa)',
                'Klik Simpan Presensi'
            ],
            'validation_rules_and_errors' => ['Status kehadiran wajib salah satu: H, S, I, A'],
            'troubleshooting_faq' => ['Siswa pindah rombel masih muncul? Periksa buku mutasi dan penempatan rombel siswa.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Attendance.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_att_recap', 'feat_att_student_portal'],
            'known_limitations' => ['Menjadi sumber agregasi rekapitulasi kehadiran siswa di rapor.']
        ],

        'feat_att_recap' => [
            'feature_id' => 'feat_att_recap',
            'title' => 'Rekapitulasi Presensi Siswa',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3, 7],
            'prerequisites' => ['Presensi harian terisi'],
            'workflow_steps' => [
                'Buka menu Presensi > Rekapitulasi Presensi',
                'Pilih kelas dan rentang bulan, lihat persentase kehadiran siswa, dan ekspor ke Excel'
            ],
            'validation_rules_and_errors' => ['Rentang tanggal valid'],
            'troubleshooting_faq' => ['Persentase kehadiran 0%? Pastikan presensi harian pada bulan tersebut telah disimpan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Attendance.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_att_daily_class'],
            'known_limitations' => ['Digunakan oleh kesiswaan dan wali kelas untuk deteksi dini ketidakhadiran siswa.']
        ],

        'feat_att_student_portal' => [
            'feature_id' => 'feat_att_student_portal',
            'title' => 'Rekap Presensi Portal Siswa & Orang Tua',
            'category' => 'Portal Siswa',
            'allowed_roles' => [4, 5],
            'prerequisites' => ['Login sebagai Siswa (Role 5) atau Orang Tua (Role 4)'],
            'workflow_steps' => [
                'Buka portal siswa/ortu > menu Presensi (/siswa/attendance)',
                'Sistem menampilkan kalender kehadiran pribadi anak kandung atau siswa yang login'
            ],
            'validation_rules_and_errors' => ['Read-only; tidak dapat mengubah data presensi mandiri'],
            'troubleshooting_faq' => ['Ada status Alpa yang salah? Hubungi wali kelas untuk koreksi presensi di sistem admin.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Siswa/AttendanceController.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_att_daily_class'],
            'known_limitations' => ['Terkunci mutlak pada student_id sesi pengguna; kebal manipulasi ID.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 9: ADMINISTRASI KESISWAAN & BK
        // ---------------------------------------------------------------------

        'feat_stu_master_records' => [
            'feature_id' => 'feat_stu_master_records',
            'title' => 'Data Induk & Riwayat Siswa',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 2, 3, 7],
            'prerequisites' => ['Hak akses Admin, Kepsek, Guru, atau Tata Usaha'],
            'workflow_steps' => [
                'Buka menu Kesiswaan > Data Siswa (/admin/students)',
                'Cari siswa, lihat profil lengkap (NISN, biodata, data ortu, riwayat rombel, foto)',
                'Admin/TU dapat melakukan tambah data atau import file Excel'
            ],
            'validation_rules_and_errors' => ['NIS dan NISN wajib unik', 'Nama lengkap siswa wajib diisi'],
            'troubleshooting_faq' => ['Data siswa duplikat? Lakukan pengecekan NISN sebelum mengimpor file Excel baru.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Students.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_student_placement', 'feat_stu_mutation'],
            'known_limitations' => ['Guru memiliki hak baca; perubahan data induk dilakukan oleh Tata Usaha / Admin.']
        ],

        'feat_stu_mutation' => [
            'feature_id' => 'feat_stu_mutation',
            'title' => 'Buku Mutasi Masuk & Keluar Siswa',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Siswa terdaftar di buku induk'],
            'workflow_steps' => [
                'Buka menu Kesiswaan > Mutasi Siswa (/admin/student-mutation)',
                'Catat mutasi keluar (sekolah tujuan, alasan, no surat pindah) atau mutasi masuk'
            ],
            'validation_rules_and_errors' => ['Nomor surat mutasi dan tanggal mutasi wajib diisi'],
            'troubleshooting_faq' => ['Siswa keluar masih ada di rombel? Memproses mutasi keluar otomatis melepaskan siswa dari kelas aktif.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/StudentMutation.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_master_records'],
            'known_limitations' => ['Mengubah status siswa menjadi pindah/mutasi secara permanen.']
        ],

        'feat_stu_map' => [
            'feature_id' => 'feat_stu_map',
            'title' => 'Peta Sebaran Domisili Siswa (GIS)',
            'category' => 'Administrasi Siswa',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Koordinat latitude/longitude siswa terisi'],
            'workflow_steps' => [
                'Buka menu Kesiswaan > Peta Sebaran Siswa (/admin/student-map)',
                'Sistem menampilkan peta interaktif sebaran zonasi tempat tinggal siswa'
            ],
            'validation_rules_and_errors' => ['Koordinat GPS valid'],
            'troubleshooting_faq' => ['Marker siswa tidak muncul? Pastikan alamat siswa dilengkapi titik koordinat latitude dan longitude.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/StudentMap.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_master_records'],
            'known_limitations' => ['Membantu analisis zonasi sekolah dan transportasi antar-jemput.']
        ],

        'feat_stu_behaviors_notes' => [
            'feature_id' => 'feat_stu_behaviors_notes',
            'title' => 'Poin Pelanggaran & Catatan Sikap',
            'category' => 'Kesiswaan & BK',
            'allowed_roles' => [1, 2, 3, 4, 5],
            'prerequisites' => ['Master kategori pelanggaran dan apresiasi sikap'],
            'workflow_steps' => [
                'Guru/BK membuka menu Kesiswaan > Catatan Siswa (/admin/student-notes)',
                'Catat insiden pelanggaran (poin minus) atau prestasi (poin plus)',
                'Siswa dan Orang Tua dapat melihat catatan tersebut pada portal'
            ],
            'validation_rules_and_errors' => ['Deskripsi kejadian dan poin wajib terisi'],
            'troubleshooting_faq' => ['Poin akumulasi melebihi batas? Sistem otomatis menandai rekomendasi panggilan orang tua.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/StudentNotes.php, app/Controllers/Siswa/StudentNotes.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_bk_cases_services', 'feat_bk_summons_assessments'],
            'known_limitations' => ['Mendukung sistem poin reward & punishment terukur.']
        ],

        'feat_bk_cases_services' => [
            'feature_id' => 'feat_bk_cases_services',
            'title' => 'Penanganan Kasus & Layanan Konseling BK',
            'category' => 'Bimbingan Konseling',
            'allowed_roles' => [1, 2, 3, 5],
            'prerequisites' => ['Guru BK aktif'],
            'workflow_steps' => [
                'Guru BK membuka menu Bimbingan Konseling > Kasus Konseling (/admin/bk)',
                'Catat identitas kasus, tindakan bimbingan pribadi/sosial/belajar/karir, dan tindak lanjut',
                'Siswa dapat mengajukan janji temu konseling melalui portal siswa'
            ],
            'validation_rules_and_errors' => ['Kerahasiaan catatan konseling terisolasi untuk Guru BK'],
            'troubleshooting_faq' => ['Guru mapel tidak bisa buka detail kasus? Modul BK bersifat rahasia dan hanya dapat diakses Guru BK, Kepsek, dan Admin.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/BkModule.php, app/Controllers/Siswa/Counseling.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_behaviors_notes', 'feat_bk_summons_assessments'],
            'known_limitations' => ['Catatan rahasia psikologis siswa terproteksi RBAC ketat.']
        ],

        'feat_bk_summons_assessments' => [
            'feature_id' => 'feat_bk_summons_assessments',
            'title' => 'Surat Panggilan Ortu & Asesmen Diagnostik BK',
            'category' => 'Bimbingan Konseling',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Kasus konseling membutuhkan kehadiran orang tua'],
            'workflow_steps' => [
                'Buka menu BK > Panggilan Orang Tua (/admin/counseling-summon)',
                'Pilih siswa, masukkan waktu pemanggilan dan ruang pertemuan, lalu cetak Surat Panggilan resmi PDF',
                'Kelola instrumen asesmen diagnostik non-kognitif'
            ],
            'validation_rules_and_errors' => ['Nomor surat panggilan dan jadwal pertemuan wajib diisi'],
            'troubleshooting_faq' => ['Surat panggilan tidak tercatat? Pastikan status kasus konseling telah diperbarui.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/CounselingSummon.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_bk_cases_services', 'feat_com_letters_in_out'],
            'known_limitations' => ['Menghubungkan layanan konseling dengan tata usaha surat menyurat.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 10: LMS & PEMBELAJARAN
        // ---------------------------------------------------------------------

        'feat_lms_materials_seg' => [
            'feature_id' => 'feat_lms_materials_seg',
            'title' => 'Materi Pembelajaran Bersegmen (LMS)',
            'category' => 'LMS & Pembelajaran',
            'allowed_roles' => [1, 3, 5],
            'prerequisites' => ['Penugasan mengajar guru aktif'],
            'workflow_steps' => [
                'Guru membuka menu LMS > Materi Pembelajaran (/admin/materials)',
                'Unggah materi teks, file dokumen (PDF/PPT), atau sematkan video YouTube',
                'Siswa membuka materi pada portal siswa dan menandai status selesai'
            ],
            'validation_rules_and_errors' => ['Judul materi dan kelas sasaran wajib dipilih'],
            'troubleshooting_faq' => ['Video YouTube tidak bisa diputar? Pastikan URL disematkan dalam format embed yang sah.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Materials.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_lms_tasks', 'feat_lms_quiz'],
            'known_limitations' => ['Mendukung pembelajaran blended learning asinkron.']
        ],

        'feat_lms_tasks' => [
            'feature_id' => 'feat_lms_tasks',
            'title' => 'Tugas Siswa & Pengumpulan Online',
            'category' => 'LMS & Pembelajaran',
            'allowed_roles' => [1, 3, 5],
            'prerequisites' => ['Rombel siswa aktif'],
            'workflow_steps' => [
                'Guru membuat penugasan dengan tenggat waktu di /admin/tugas',
                'Siswa mengunggah file jawaban atau teks pada portal siswa (/siswa/tugas)',
                'Guru memeriksa dan memberikan nilai serta catatan umpan balik'
            ],
            'validation_rules_and_errors' => ['Tenggat waktu pengumpulan wajib di masa depan'],
            'troubleshooting_faq' => ['File siswa tidak bisa diunggah? Pastikan ukuran file di bawah 5MB dan format sesuai ketentuan (PDF/DOCX/JPG).'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Tugas.php, app/Controllers/Siswa/Tugas.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_lms_materials_seg'],
            'known_limitations' => ['Sistem mencatat waktu pengumpulan untuk menandai status terlambat.']
        ],

        'feat_lms_quiz' => [
            'feature_id' => 'feat_lms_quiz',
            'title' => 'Kuis Mandiri Siswa (LMS Quiz)',
            'category' => 'LMS & Pembelajaran',
            'allowed_roles' => [1, 3, 5],
            'prerequisites' => ['Materi kuis dibuat oleh guru'],
            'workflow_steps' => [
                'Guru menyusun kuis interaktif di /admin/quiz',
                'Siswa mengerjakan kuis latihan mandiri dengan skor langsung tampil'
            ],
            'validation_rules_and_errors' => ['Jumlah percobaan kuis dibatasi sesuai setting guru'],
            'troubleshooting_faq' => ['Perbedaan Kuis LMS dan Ujian CBT? Kuis LMS untuk latihan mandiri tanpa token; CBT untuk ujian resmi dengan token.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Quiz.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cbt_bank_soal', 'feat_lms_materials_seg'],
            'known_limitations' => ['Dirancang untuk latihan formatif mandiri siswa.']
        ],

        'feat_lms_forum' => [
            'feature_id' => 'feat_lms_forum',
            'title' => 'Forum Diskusi Pembelajaran',
            'category' => 'LMS & Pembelajaran',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => ['Akun pengguna aktif'],
            'workflow_steps' => [
                'Buka menu Forum Diskusi (/forum)',
                'Buat utas topik diskusi baru sesuai mata pelajaran atau kelas',
                'Pengguna lain dapat memberikan komentar balasan'
            ],
            'validation_rules_and_errors' => ['Judul utas minimal 5 karakter'],
            'troubleshooting_faq' => ['Utas ditutup moderator? Administrator dan guru dapat mengunci komentar pada topik yang telah selesai.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Forum.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_lms_materials_seg'],
            'known_limitations' => ['Mendukung interaksi kolaboratif antarcivitas sekolah.']
        ],

        'feat_lms_ebook' => [
            'feature_id' => 'feat_lms_ebook',
            'title' => 'Perpustakaan Digital (E-Book)',
            'category' => 'Perpustakaan',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => ['Koleksi e-book terunggah'],
            'workflow_steps' => [
                'Admin/Pustakawan mengunggah e-book PDF di /admin/ebook',
                'Seluruh pengguna dapat membaca buku digital langsung melalui web reader'
            ],
            'validation_rules_and_errors' => ['File buku wajib berformat PDF'],
            'troubleshooting_faq' => ['E-book tidak terbuka? Pastikan browser mengizinkan penampil PDF bawaan.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Ebook.php, app/Controllers/Siswa/Ebook.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_lms_materials_seg'],
            'known_limitations' => ['Mendukung literasi digital sekolah.']
        ],

        'feat_lms_ekskul' => [
            'feature_id' => 'feat_lms_ekskul',
            'title' => 'Pengelolaan Ekstrakurikuler & Pembina',
            'category' => 'Kesiswaan & Ekskul',
            'allowed_roles' => [1, 2, 5, 7, 8],
            'prerequisites' => ['Master ekskul dan pembina terdaftar'],
            'workflow_steps' => [
                'Admin mengelola master ekskul di /admin/ekskul',
                'Pembina/Pelatih (Role 8) mengelola anggota, presensi latihan, dan nilai di /admin/ekskul-pembina',
                'Siswa mendaftar ekskul melalui portal siswa (/siswa/ekskul)'
            ],
            'validation_rules_and_errors' => [
                'Pelatih Role 8 hanya dapat mengakses ekskul yang dibina langsung (checkAccess)'
            ],
            'troubleshooting_faq' => [
                'Pelatih ditolak dengan Error 403? Pastikan user ID pelatih telah dipasangkan pada ekskul terkait di tabel ekskul_pembina.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Ekskul.php, app/Controllers/Admin/EkskulPembina.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_erapor'],
            'known_limitations' => ['Nilai ekskul otomatis ditarik ke dalam E-Rapor semester siswa.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 11: KOMUNIKASI & SURAT MENYURAT
        // ---------------------------------------------------------------------

        'feat_com_announcements' => [
            'feature_id' => 'feat_com_announcements',
            'title' => 'Pengumuman Sekolah Berperingkat',
            'category' => 'Komunikasi',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => ['Hak akses publikasi pengumuman'],
            'workflow_steps' => [
                'Admin/TU membuat pengumuman baru di /admin/announcements dengan sasaran role tertentu',
                'Pengumuman tampil di dashboard pengguna sasaran'
            ],
            'validation_rules_and_errors' => ['Judul dan isi pengumuman wajib terisi'],
            'troubleshooting_faq' => ['Siswa tidak melihat pengumuman? Pastikan sasaran role Siswa dicentang saat pengumuman dibuat.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Announcement.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cms_school_web'],
            'known_limitations' => ['Mendukung pengumuman internal sekolah.']
        ],

        'feat_com_letters_in_out' => [
            'feature_id' => 'feat_com_letters_in_out',
            'title' => 'Surat Masuk (OCR) & Surat Keluar',
            'category' => 'Tata Usaha & Surat',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Hak akses Tata Usaha'],
            'workflow_steps' => [
                'Buka menu Tata Usaha & Surat > Surat Masuk (/admin/surat-masuk) atau Surat Keluar (/admin/surat-keluar)',
                'Catat nomor surat, asal/tujuan, perihal, dan unggah pindaian surat',
                'Sistem dapat mengekstrak teks pindaian menggunakan OCR'
            ],
            'validation_rules_and_errors' => ['Nomor surat wajib diisi dan unik pada buku agenda surat keluar'],
            'troubleshooting_faq' => ['OCR gagal membaca teks? Pastikan resolusi pindaian surat jelas dan tidak miring.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/SuratMasuk.php, app/Controllers/Admin/SuratKeluar.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_com_guest_book'],
            'known_limitations' => ['Sentralisasi arsip persuratan resmi sekolah.']
        ],

        'feat_com_guest_book' => [
            'feature_id' => 'feat_com_guest_book',
            'title' => 'Buku Tamu & Tanda Tangan Digital',
            'category' => 'Tata Usaha & Surat',
            'allowed_roles' => [1, 2, 7],
            'prerequisites' => ['Tersedia perangkat resepsionis/front-desk'],
            'workflow_steps' => [
                'Buka menu Buku Tamu (/buku-tamu)',
                'Tamu mengisi nama, instansi, tujuan kunjungan, foto, dan tanda tangan pada kanvas digital',
                'Simpan catatan buku tamu'
            ],
            'validation_rules_and_errors' => ['Nama tamu dan tujuan wajib terisi'],
            'troubleshooting_faq' => ['Tanda tangan digital tidak tersimpan? Pastikan browser mendukung fitur HTML5 Canvas.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/BukuTamu.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_com_letters_in_out'],
            'known_limitations' => ['Dapat diakses publik pada perangkat resepsionis sekolah.']
        ],

        'feat_com_chat' => [
            'feature_id' => 'feat_com_chat',
            'title' => 'Chat Kelas & Staff Internal',
            'category' => 'Komunikasi',
            'allowed_roles' => [1, 2, 3, 5],
            'prerequisites' => ['Pengguna terdaftar pada rombel yang sama'],
            'workflow_steps' => [
                'Buka menu Chat Kelas (/admin/chat atau /siswa/chat)',
                'Kirim pesan instan antaranggota kelas atau ruang staf GTK'
            ],
            'validation_rules_and_errors' => ['Pesan tidak boleh kosong'],
            'troubleshooting_faq' => ['Pesan tidak terkirim? Pastikan koneksi internet stabil; sistem menggunakan polling interval.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/StaffChat.php, app/Controllers/Siswa/Chat.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_com_announcements'],
            'known_limitations' => ['Komunikasi internal aman tanpa melibatkan nomor telepon pribadi.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 12: ASIS AI & INTELIGENSI BUATAN
        // ---------------------------------------------------------------------

        'feat_ai_asis_copilot' => [
            'feature_id' => 'feat_ai_asis_copilot',
            'title' => 'Asis AI Workplace Copilot',
            'category' => 'Kecerdasan Buatan',
            'allowed_roles' => [1, 2, 3, 7],
            'prerequisites' => [
                'Login dengan peran yang diizinkan (Role 1, 2, 3, atau 7)',
                'API Key BYOK atau Kuota Admin harian aktif'
            ],
            'workflow_steps' => [
                'Klik widget Asis AI di pojok kanan bawah atau tekan pintasan Command Palette',
                'Ketik pertanyaan seputar penggunaan aplikasi, tugas pembelajaran, atau kurikulum',
                'Asis AI merespons secara cerdas, kontekstual, dan berbukti'
            ],
            'validation_rules_and_errors' => [
                'Hanya 4 role yang berhak mengakses (Admin, Kepsek, Guru, TU)',
                'Dilarang meminta data pribadi siswa (Zero PII Enforcement)'
            ],
            'troubleshooting_faq' => [
                'Muncul error Quota Exhausted? Masukkan API Key pribadi (BYOK) pada menu Akun Saya > Kunci AI untuk penggunaan tanpa batas.'
            ],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Api/AiCopilot.php, public/assets/js/asis-copilot.js',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_ai_voice_briefing', 'feat_cur_modul_ajar_ai'],
            'known_limitations' => ['Khusus membantu tugas operasional pendidik dan tenaga kependidikan; tidak diakses siswa/ortu.']
        ],

        'feat_ai_voice_briefing' => [
            'feature_id' => 'feat_ai_voice_briefing',
            'title' => 'Voice TTS Morning Briefing',
            'category' => 'Kecerdasan Buatan',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => ['Browser mendukung Web Audio / TTS API'],
            'workflow_steps' => [
                'Pada dashboard guru, klik tombol Dengarkan Briefing Pagi',
                'Asis AI membacakan ringkasan agenda mengajar dan jadwal hari ini dengan suara natural'
            ],
            'validation_rules_and_errors' => ['Volume audio aktif pada perangkat'],
            'troubleshooting_faq' => ['Suara tidak bersuara? Pastikan izin audio/sound pada browser telah diizinkan untuk situs sekolah.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Api/AiCopilot.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_ai_asis_copilot', 'feat_adm_schedules'],
            'known_limitations' => ['Membantu guru menyimak agenda harian secara cepat di pagi hari.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 13: PEMELIHARAAN SISTEM & AUDIT
        // ---------------------------------------------------------------------

        'feat_sys_settings_app' => [
            'feature_id' => 'feat_sys_settings_app',
            'title' => 'Pengaturan Aplikasi & Sesi Web',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Pengaturan Web (/admin/settings)',
                'Konfigurasi durasi timeout sesi, mode maintenance, dan parameter umum aplikasi'
            ],
            'validation_rules_and_errors' => ['Timeout sesi minimal 15 menit'],
            'troubleshooting_faq' => ['Website sedang maintenance? Admin dapat menonaktifkan maintenance mode di menu ini.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/SettingsController.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_audit_logs'],
            'known_limitations' => ['Pengaturan kritikal global aplikasi.']
        ],

        'feat_sys_calendar_holidays' => [
            'feature_id' => 'feat_sys_calendar_holidays',
            'title' => 'Kalender Akademik & Hari Libur',
            'category' => 'Akademik & KBM',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => ['Tahun ajaran aktif'],
            'workflow_steps' => [
                'Admin menginput tanggal libur nasional dan cuti sekolah di /admin/calendar',
                'Seluruh pengguna melihat kalender kegiatan akademik di dashboard'
            ],
            'validation_rules_and_errors' => ['Tanggal libur tidak boleh bentrok redundan'],
            'troubleshooting_faq' => ['Hari libur masih dihitung efektif? Pastikan tanggal libur sudah dimasukkan di kalender akademik.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/AcademicCalendar.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_cur_prota_promes', 'feat_adm_schedules'],
            'known_limitations' => ['Mempengaruhi perhitungan pekan efektif pada Prota/Promes.']
        ],

        'feat_sys_audit_logs' => [
            'feature_id' => 'feat_sys_audit_logs',
            'title' => 'Audit Trail Aktivitas Pengguna',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Log Aktivitas (/admin/audit-log)',
                'Telusuri rekaman aksi pengguna (login, input nilai, ubah password, mutasi)'
            ],
            'validation_rules_and_errors' => ['Log bersifat append-only dan tidak boleh diedit'],
            'troubleshooting_faq' => ['Siapa yang mengubah nilai? Periksa histori user_id dan timestamp pada modul terkait di audit log.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/AuditLog.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_users'],
            'known_limitations' => ['Instrumen forensik keamanan sistem.']
        ],

        'feat_sys_updater' => [
            'feature_id' => 'feat_sys_updater',
            'title' => 'System Updater & Auto-Migrate',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => ['Hak akses Admin'],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Pembaruan Sistem (/admin/updater)',
                'Periksa ketersediaan pembaruan skema database dan klik Jalankan Migrasi'
            ],
            'validation_rules_and_errors' => ['Wajib backup database sebelum menjalankan update'],
            'troubleshooting_faq' => ['Tabel baru belum terbentuk? Jalankan auto-migrate melalui updater ini.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Updater.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sys_settings_app'],
            'known_limitations' => ['Hanya memproses migration file resmi di app/Database/Migrations.']
        ],

        'feat_sys_dapodik_sync' => [
            'feature_id' => 'feat_sys_dapodik_sync',
            'title' => 'Integrasi Web Service Dapodik',
            'category' => 'Pengaturan Sistem',
            'allowed_roles' => [1],
            'prerequisites' => [
                'Koneksi Web Service Dapodik Lokal kementerian terpasang',
                'Kunci koneksi Web Service terverifikasi'
            ],
            'workflow_steps' => [
                'Buka menu Pengaturan Sistem > Integrasi Dapodik (/admin/dapodik)',
                'Masukkan URL Web Service dan token autentikasi Dapodik',
                'Lakukan sinkronisasi data GTK dan siswa'
            ],
            'validation_rules_and_errors' => ['Web Service Dapodik harus dapat dijangkau jaringan'],
            'troubleshooting_faq' => [
                'Status INACTIVE? Modul ini belum aktif di lingkungan lokal karena memerlukan koneksi resmi Web Service Dapodik kementerian.'
            ],
            'feature_status' => 'INACTIVE',
            'source_reference' => 'app/Controllers/Admin/Dapodik.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_stu_master_records', 'feat_sys_account_sync'],
            'known_limitations' => ['Status INACTIVE di lingkungan lokal development; menunggu instalasi Web Service resmi.']
        ],

        // ---------------------------------------------------------------------
        // KELOMPOK 14: PUBLIKASI, FITUR KHUSUS & VERIFIKASI DOKUMEN
        // ---------------------------------------------------------------------

        'feat_cms_school_web' => [
            'feature_id' => 'feat_cms_school_web',
            'title' => 'CMS Publikasi Berita & Profil Web Sekolah',
            'category' => 'Publikasi Website',
            'allowed_roles' => [1, 2, 6],
            'prerequisites' => ['Hak akses Admin, Kepsek, atau Kontributor'],
            'workflow_steps' => [
                'Buka menu Publikasi Web > Berita (/admin/cms/berita)',
                'Tulis artikel/kegiatan sekolah, lampirkan foto dokumentasi, dan klik Publikasikan',
                'Artikel langsung tampil di portal publik website sekolah (/)'
            ],
            'validation_rules_and_errors' => ['Judul artikel wajib diisi', 'Gambar unggahan maks 2MB'],
            'troubleshooting_faq' => ['Berita tidak muncul di halaman depan? Pastikan status artikel diset Publikasikan (bukan Draf).'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Admin/Cms/Berita.php, app/Controllers/Home.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_sch_identity'],
            'known_limitations' => ['Peran Kontributor (Role 6) dibatasi hanya pada modul CMS ini.']
        ],

        'feat_adv_snbp_tracker' => [
            'feature_id' => 'feat_adv_snbp_tracker',
            'title' => 'SNBP Tracker (Khusus Jenjang SMA/SMK)',
            'category' => 'Kesiswaan',
            'allowed_roles' => [1, 2, 3],
            'prerequisites' => [
                'Jenjang sekolah adalah SMA/SMK (Level 3)',
                'Nilai rapor semester 1-5 telah terisi'
            ],
            'workflow_steps' => [
                'Buka menu Kesiswaan > SNBP Tracker (/admin/snbp-tracker)',
                'Sistem menganalisis nilai siswa per jurusan dan memetakan kuota eligible (40% akreditasi A)'
            ],
            'validation_rules_and_errors' => [
                'Hanya aktif pada jenjang SMA/SMK; ditolak pada jenjang SD dan SMP'
            ],
            'troubleshooting_faq' => [
                'Status LIMITED? Fitur ini fungsional penuh namun hanya berlaku untuk jenjang SMA/SMK, tidak berlaku di SD.'
            ],
            'feature_status' => 'LIMITED',
            'source_reference' => 'app/Controllers/Admin/SnbpController.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_leger', 'feat_sch_jenjang_master'],
            'known_limitations' => ['Status LIMITED khusus jenjang menengah atas pelacakan eligibilitas perguruan tinggi negeri.']
        ],

        'feat_adv_qr_verify' => [
            'feature_id' => 'feat_adv_qr_verify',
            'title' => 'Verifikasi QR Dokumen Resmi Sekolah',
            'category' => 'Sistem & Publik',
            'allowed_roles' => [1, 2, 3, 4, 5, 6, 7, 8],
            'prerequisites' => ['Dokumen resmi telah dicetak dengan QR code keamanan'],
            'workflow_steps' => [
                'Pindai QR code pada lembar rapor atau surat resmi menggunakan kamera ponsel',
                'Browser membuka tautan verifikasi (/verify/doc/...) untuk memeriksa keaslian data'
            ],
            'validation_rules_and_errors' => ['Hash dokumen harus cocok dengan data arsip di server'],
            'troubleshooting_faq' => ['QR tidak valid? Dokumen palsu atau nomor surat tidak terdaftar dalam basis data sekolah.'],
            'feature_status' => 'ACTIVE',
            'source_reference' => 'app/Controllers/Verify.php',
            'source_version' => '1.0.0-ci4',
            'last_verified_at' => '2026-11-17T07:30:00+07:00',
            'confidence' => 5,
            'related_features' => ['feat_grd_erapor', 'feat_com_letters_in_out'],
            'known_limitations' => ['Dapat diakses oleh masyarakat umum / pihak luar tanpa login untuk verifikasi dokumen.']
        ]

    ];
}
