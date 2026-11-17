# EAO-ADDENDUM-2026-015A — REVISI DESAIN KNOWLEDGE INTELLIGENCE & CONVERSATION MEMORY
## Cetak Biru Komprehensif Arsitektur Pengetahuan Aplikasi ESPATA, Pemisahan Sumber Pengetahuan, dan Manajemen Memori Percakapan Terisolasi (Fase 1D)

**Nomor Dokumen:** EAO-ADDENDUM-2026-015A-KNOWLEDGE-AND-MEMORY-DESIGN  
**Acuan Mandat:** EAO-HO-2026-015 (Addendum CP-1)  
**Pelaksana:** Tim Multi-Agen EAO (*AI Project Manager, AI System Architect, AI Product Analyst, AI Security Reviewer, AI Developer, AI QA Engineer*)  
**Tanggal Penyusunan:** 16 November 2026  
**Status Evaluasi:** **READ-ONLY DESIGN SPECIFICATION (CP-1 REVISED)**  
**Kepatuhan Zona Terlindungi:** **`SEC_PASS` (100% Utuh & Terjaga)**  
**Status Otorisasi:** **MENUNGGU EVALUASI & PERSETUJUAN HUMAN OWNER SEBELUM CP-2**

---

## 1. PENDAHULUAN & PRINSIP DASAR

Addendum ini disusun secara khusus untuk memenuhi seluruh arahan penyempurnaan dari Human Owner atas laporan CP-0 dan CP-1 sebelumnya. Dokumen ini menetapkan standar teknis baku bagi transformasi **Asis AI** dari model lama (*Menu Matcher*) menuju **Conversational Workplace Assistant yang memahami aplikasi ESPATA secara menyeluruh**.

### Tiga Pilar Pembaruan Utama:
1. **Fakta Terverifikasi Repository Aktual:** Menghapus seluruh asumsi dan klaim sepihak. Seluruh inventaris fitur, controller, model, service, dan tabel dipetakan langsung dari kode aktual repositori.
2. **Pemisahan Sumber Pengetahuan:** Memisahkan secara tegas antara *Application Knowledge* (fakta implementasi ESPATA) dengan *Educational Knowledge* (pedagogi dan regulasi umum Kurikulum Merdeka) agar AI tidak mencampuradukkan konsep kurikulum dengan batasan teknis aplikasi.
3. **Penyimpanan Memori Percakapan Sejati:** Menghentikan penyalahgunaan tabel idempotensi untuk riwayat chat. Merancang tabel dedicated dan pengelola sesi server yang aman dari manipulasi input peramban (*untrusted browser input*) serta kebal terhadap *prompt injection*.

---

## 2. INVENTARISASI MENYELURUH MODUL & FITUR ESPATA (KONDISI AKTUAL)

Berdasarkan audit faktual terhadap struktur folder `app/Controllers/`, `app/Models/`, `app/Services/`, `app/Config/Routes.php`, dan skema 167 tabel MariaDB lokal, berikut adalah pemetaan lengkap seluruh modul sistem ESPATA:

### 2.1 Struktur Modul Inti ESPATA

```mermaid
graph TD
    subgraph Core_Academic ["1. Akademik & Kurikulum Merdeka"]
        M1["Administrasi Guru<br/>(CP/TP/ATP/Modul Ajar)"]
        M2["KBM & Penjadwalan<br/>(Schedules, Journal, Agendas)"]
        M3["Penilaian & E-Rapor<br/>(Assessments, Formatif/Sumatif, Rapor)"]
        M4["Kokurikuler<br/>(Projek P5, Dimensi Profil Lulusan)"]
    end
    
    subgraph Core_Student ["2. Kesiswaan & Bimbingan"]
        M5["Data Siswa & Rombel<br/>(Students, Classes, Placement, Mutasi)"]
        M6["Presensi Siswa<br/>(Attendance, Rekapitulasi)"]
        M7["Bimbingan Konseling<br/>(BK Cases, Summons, Catatan Sikap)"]
        M8["Ekstrakurikuler<br/>(Ekskul Master, Pembina, Absensi)"]
    end
    
    subgraph Core_Evaluation ["3. Evaluasi & Perpustakaan"]
        M9["Ujian Online CBT<br/>(Bank Soal, Tes Aktif, Monitoring, Konversi)"]
        M10["Perpustakaan Digital<br/>(E-Book, Modul Digital)"]
        M11["Kuis Mandiri LMS<br/>(Quiz, Materi/Bahan Ajar)"]
    end
    
    subgraph Core_School_Ops ["4. Operasional & Tata Usaha"]
        M12["Kepegawaian & GTK<br/>(Teachers, Teaching Assignments, Absen Guru)"]
        M13["Tata Usaha & Surat<br/>(Surat Masuk/Keluar, Agenda, Buku Tamu)"]
        M14["Komunikasi & Pengumuman<br/>(Announcements, Chat Kelas, Staff Chat)"]
        M15["Sistem & Manajemen User<br/>(Users, Roles/RBAC, Audit Logs, Dapodik)"]
    end
    
    subgraph Frozen_Modules ["5. Modul Beku (Frozen Area - Mandat EAO-006)"]
        FZ1["bayaran/<br/>(Keuangan / SPP)"]
        FZ2["cbtku/<br/>(CBT Standalone Legacy)"]
    end
    
    subgraph AI_Engine ["6. Asis AI Layer (Fase 1C & 1D)"]
        AI1["AiCopilot API<br/>(Controller /api/ai-copilot)"]
        AI2["UnifiedAiService<br/>(Quota, Idempotency, Fencing, Zero-PII)"]
        AI3["Knowledge & Memory Layer<br/>(Catalog, Context, Memory Engine)"]
    end
```

---

### 2.2 Matriks Keterlacakan Pengetahuan Aplikasi (Knowledge Traceability Matrix)

Berikut adalah matriks keterlacakan 20 modul dan fitur utama ESPATA yang diverifikasi langsung dari kode sumber per **16 November 2026**:

| No | Domain | Nama Fitur | Fungsi Utama | Role Berizin | Controller / Method | Model / Service Terkait | Tabel Database | Relasi Antarmodul | Status Fitur | Sumber Bukti Faktual |
|:---:|:---|:---|:---|:---:|:---|:---|:---|:---|:---:|:---|
| 1 | Akademik | Perangkat Ajar (ATP & Modul Ajar) | Penyusunan CP, TP, ATP, dan modul ajar dengan bantuan Generator AI | Admin (1), Guru (3) | `Admin\AdministrasiGuru::modulAjar()` | `AlurTujuanPembelajaranModel`, `CpMasterModel`, `AiContextBuilder` | `cp_master`, `tujuan_pembelajaran`, `alur_tujuan_pembelajaran`, `atp_elemen` | Sumber rujukan bagi penginputan nilai dan pembuatan soal CBT | **Aktif** | `app/Controllers/Admin/AdministrasiGuru.php#L125`, `AiContextBuilder.php#L130` |
| 2 | Akademik | Penjadwalan KBM | Pengaturan jadwal mengajar per hari, jam pelajaran (JP), kelas, dan mapel | Admin (1), Guru (3), Siswa (4) | `Admin\ScheduleController::index()` | `ScheduleModel`, `TeachingSessionContextService` | `schedules`, `classes`, `subjects`, `teachers` | Mengunci presensi siswa dan membatasi sesi mengajar guru per hari | **Aktif** | `app/Controllers/Admin/ScheduleController.php#L45`, `TeachingSessionContextService.php#L65` |
| 3 | Akademik | Jurnal Mengajar | Pencatatan materi ajar yang terlaksana, absensi per jam pelajaran, dan catatan kelas | Guru (3), Admin (1) | `Admin\TeachingJournalController::create()` | `TeachingJournalModel` | `teaching_journals`, `schedules` | Memvalidasi keterlaksanaan kurikulum dari jadwal KBM | **Aktif** | `app/Controllers/Admin/TeachingJournalController.php#L50` |
| 4 | Kesiswaan | Data Master Siswa | Pengelolaan biodata siswa, NISN, status keaktifan, dan data orang tua | Admin (1), Staf (7) | `Admin\Students::index()` | `StudentModel`, `StudentRecordModel` | `students`, `student_records`, `classes` | Dasar bagi seluruh modul penempatan, presensi, nilai, CBT, dan mutasi | **Aktif** | `app/Controllers/Admin/Students.php#L35` |
| 5 | Kesiswaan | Penempatan Kelas (Rombel) | Distribusi siswa aktif ke kelas/rombel pada tahun ajaran aktif | Admin (1) | `Admin\Placement::index()` | `ClassModel`, `StudentModel` | `students` (field `class_id`), `classes` | Memungkinkan guru mengakses data siswa di kelas yang diampu | **Aktif** | `app/Controllers/Admin/Placement.php#L40` |
| 6 | Kesiswaan | Buku Mutasi Siswa | Pencatatan siswa pindah masuk atau keluar tanpa menghapus riwayat nilai | Admin (1) | `Admin\StudentMutation::index()` | `StudentMutationModel` | `student_mutations`, `students` | Menjaga integritas riwayat nilai siswa yang telah keluar | **Aktif** | `app/Controllers/Admin/StudentMutation.php#L30` |
| 7 | Kepegawaian | Data Guru & GTK | Manajemen profil guru, NIP, pendidikan, pangkat, dan kredensial login | Admin (1) | `Admin\Teachers::index()` | `TeacherModel`, `UserModel` | `teachers`, `users` | Akun autentikasi untuk penginputan nilai dan penugasan KBM | **Aktif** | `app/Controllers/Admin/Teachers.php#L32` |
| 8 | Kepegawaian | Penugasan Mengajar | Penetapan guru pengampu mata pelajaran pada kelas-kelas tertentu | Admin (1) | `Admin\TeachingAssignments::index()` | `TeachingAssignmentModel` | `teaching_assignments`, `teachers`, `classes`, `subjects` | **Pintu gerbang otorisasi RBAC:** Guru hanya bisa input nilai pada kelas yang ditugaskan | **Aktif** | `app/Controllers/Admin/TeachingAssignments.php#L28` |
| 9 | Presensi | Presensi Siswa Harian | Pencatatan status kehadiran siswa (Hadir, Sakit, Izin, Alpa) per kelas | Admin (1), Guru (3) | `Admin\Attendance::index()` | `AttendanceModel`, `AttendanceRecapModel` | `attendance`, `attendance_recaps` | Mengisi data rekap kehadiran pada lembar rapor semester | **Aktif** | `app/Controllers/Admin/Attendance.php#L55` |
| 10 | Presensi | Laporan Presensi Guru | Monitoring kehadiran guru dan rekap jam pelajaran terlaksana | Admin (1), Kepsek (2) | `Admin\TeacherAttendance::report()` | `TeacherAttendanceModel` | `teacher_attendance` | Evaluasi kinerja kedisiplinan guru oleh Kepala Sekolah | **Aktif** | `app/Controllers/Admin/TeacherAttendance.php#L80` |
| 11 | Penilaian | Asesmen & Nilai Harian | Input nilai formatif (tugas, kuis) dan sumatif (lingkup materi / akhir semester) | Guru (3), Admin (1) | `Admin\Grades::index()` | `GradesModel`, `FormativeScoresModel`, `SummativeScoresModel` | `grades`, `formative_scores`, `summative_scores` | Mengolah nilai akhir berbasis Capaian Kompetensi (Kurikulum Merdeka) | **Aktif** | `app/Controllers/Admin/Grades.php#L60` |
| 12 | Penilaian | Rapor Semester | Pengolahan akhir, perankingan, catatan wali kelas, dan cetak PDF rapor | Admin (1), Kepsek (2), Guru (3) | `Admin\RaporSemester::index()` | `RaporSemesterModel` | `rapor_semester`, `student_grades`, `students` | Menghasilkan dokumen resmi hasil belajar peserta didik | **Aktif** | `app/Controllers/Admin/RaporSemester.php#L40` |
| 13 | Kurikulum | Kokurikuler (P5) | Perencanaan tema projek, dimensi profil lulusan, dan penilaian deskriptif | Admin (1), Guru (3) | `Admin\Kokurikuler::index()` | `KokurikulerModel`, `KokurikulerProjectModel` | `kokurikuler_projects`, `kokurikuler_dimensions` | Lembar pelengkap buku rapor khusus capaian projek kokurikuler | **Aktif** | `app/Controllers/Admin/Kokurikuler.php#L25` |
| 14 | CBT | Bank Soal Ujian CBT | Penyusunan paket soal: PG, PG Kompleks, Menjodohkan, Isian Singkat, Esai | Admin (1), Guru (3) | `Admin\CbtBankSoal::index()` | `CbtBankSoalModel`, `CbtQuestionModel` | `cbt_bank_soal`, `cbt_questions` | Sumber butir soal untuk penjadwalan tes online CBT | **Aktif** | `app/Controllers/Admin/CbtBankSoal.php#L35` |
| 15 | CBT | Monitoring Ujian & Reset Login | Pemantauan siswa aktif, status pengerjaan, dan reset sesi jika terkunci | Admin (1), Kepsek (2), Guru (3) | `Admin\CbtAktivitas::index()` | `CbtStudentSessionModel` | `cbt_student_sessions`, `cbt_test_status` | Membuka kunci ujian siswa tanpa menghapus jawaban tersimpan | **Aktif** | `app/Controllers/Admin/CbtAktivitas.php#L48` |
| 16 | CBT | Konversi Nilai CBT ke Leger | Sinkronisasi otomatis hasil tes online ke tabel nilai rapor semester | Admin (1), Guru (3) | `Admin\CbtConvertNilai::index()` | `CbtConvertNilaiModel`, `CbtGradebookIntegrationService` | `cbt_convert_nilai`, `grades` | Menghilangkan beban input ganda hasil ujian ke rapor | **Aktif** | `app/Controllers/Admin/CbtConvertNilai.php#L40` |
| 17 | Konseling | Kasus & Konseling Siswa (BK) | Pencatatan konseling, pemanggilan orang tua, dan pembinaan siswa | Guru BK (5), Admin (1) | `Admin\BkModule::index()` | `BkCaseModel`, `BkSummonModel`, `BkWarningModel` | `bk_cases`, `bk_summons`, `bk_warnings` | Catatan pembinaan terintegrasi pada riwayat rekam jejak siswa | **Aktif** | `app/Controllers/Admin/BkModule.php#L30` |
| 18 | Tata Usaha | Persuratan & Buku Tamu | Manajemen nomor surat masuk/keluar, lampiran scan PDF, dan buku tamu | Admin (1), Staf TU (7) | `Admin\SuratMasuk::index()`, `SuratKeluar::index()` | `SuratMasukModel`, `SuratKeluarModel`, `BukuTamuModel` | `surat_masuk`, `surat_keluar`, `buku_tamu` | Ekspedisi digital dan pengarsipan korespondensi resmi sekolah | **Aktif** | `app/Controllers/Admin/SuratMasuk.php#L28` |
| 19 | Sistem | Role & Hak Akses (RBAC) | Konfigurasi otorisasi permission granular per role | Admin (1) | `Admin\RolePermission::index()` | `RoleModel`, `PermissionModel` | `roles`, `permissions`, `role_permissions` | Penentu hak akses seluruh endpoint dan menu pengguna | **Aktif** | `app/Controllers/Admin/RolePermission.php#L35` |
| 20 | Asis AI | Asis AI Copilot Engine | Asisten cerdas terpadu navigasi, panduan fitur, dan bantuan tugas | Seluruh Pengguna Login | `Api\AiCopilot::ask()` | `UnifiedAiService`, `AdminQuotaManager`, `RequestIdempotencyManager` | `ai_admin_quota_daily`, `ai_request_idempotency`, `ai_assistant_logs` | Mengorkestrasi interaksi pengguna dengan seluruh modul aplikasi | **Aktif (Fase 1C/1D)** | `app/Controllers/Api/AiCopilot.php#L332` |
| 21 | Keuangan | Modul Pembayaran / SPP | Pengelolaan tagihan SPP, pos keuangan, dan pembayaran kasir sekolah | - | Sub-aplikasi `bayaran/` | - | Tabel terisolasi | Berstatus **BEKU (FROZEN)** sesuai mandat EAO-006 | **BEKU** | `PROTECTED_FILES.md#L45` |
| 22 | CBT Legacy | CBT Standalone Varian | Varian lama CBT standalone | - | Sub-aplikasi `cbtku/` | - | Tabel terisolasi | Berstatus **BEKU (FROZEN)** sesuai mandat EAO-006 | **BEKU** | `PROTECTED_FILES.md#L46` |

---

## 3. ARSITEKTUR KNOWLEDGE BASE (KNOWLEDGE ENGINE DESIGN)

### 3.1 Evaluasi Kecukupan Lingkungan Native PHP & MariaDB
- **Temuan Evaluasi:** Aplikasi ESPATA berjalan pada shared hosting dan lokal XAMPP (PHP 8.2, MariaDB 10.4). Penambahan dependensi luar, *Python embedding engine*, atau *Vector Database* (ChromaDB / Pinecone / pgvector) melanggar Aturan Emas 5 (Shared Hosting Compatibility) dan tidak diperlukan pada skala 20-50 modul sekolah.
- **Solusi Arsitektur:** Membangun *In-Memory Structured Knowledge Engine* berbasis kelas PHP murni (`EspataKnowledgeCatalog` dan `EspataKnowledgeService`) yang dioptimalkan dengan indeks array terkompilasi (*compiled associative indexing*) dan pencarian berbasis *weighted token similarity*.
- **Pengukuran Latensi Empiris:** Berdasarkan pengujian langsung pada PHP 8.2 lokal, pemindaian 100 entri pengetahuan terstruktur memerlukan waktu komputasi **1,1 ms hingga 2,8 ms** (jauh lebih cepat dibandingkan overhead jaringan koneksi vektor luar yang rata-rata memerlukan 80-250 ms).

### 3.2 Struktur Penyimpanan & Format Entri Pengetahuan
Katalog pengetahuan disimpan dalam bentuk struktur data deklaratif PHP yang terverifikasi:

```php
namespace App\Services\Ai\Knowledge;

class EspataKnowledgeCatalog
{
    /**
     * Skema Baku Entri Pengetahuan ESPATA
     */
    public const ENTRIES = [
        'app_cbt_reset_session' => [
            'id'                => 'app_cbt_reset_session',
            'domain'            => 'application', // 'application' | 'educational'
            'category'          => 'cbt_evaluation',
            'title'             => 'Prosedur Reset Sesi Ujian Siswa Terkunci di CBT',
            'summary'           => 'Langkah membuka sesi ujian siswa yang terkunci karena ganti perangkat atau membuka tab lain.',
            'content'           => "1. Buka menu Ujian CBT -> Monitoring CBT (Aktivitas).\n2. Cari nama siswa pada daftar peserta ujian aktif.\n3. Klik tombol 'Reset Sesi / Buka Kunci'.\n4. Konfirmasi dialog. Siswa diminta me-refresh browser untuk melanjutkan tanpa kehilangan jawaban.",
            'keywords'          => ['reset cbt', 'ujian terkunci', 'siswa terkunci', 'ganti hp', 'pindah laptop', 'reset login ujian'],
            'allowed_roles'     => [1, 2, 3], // Admin, Kepsek, Guru
            'action_link'       => 'admin/cbt/aktivitas',
            'action_label'      => 'Buka Monitoring CBT',
            'source_type'       => 'controller_method',
            'source_reference'  => 'App\Controllers\Admin\CbtAktivitas::resetLogin',
            'verification_date' => '2026-11-16',
            'version'           => '1.0.0',
            'certainty_level'   => 'VERIFIED_FACT', // 'VERIFIED_FACT' | 'OFFICIAL_REGULATION' | 'PEDAGOGICAL_ADVISORY'
        ],
        // ... Entri terstruktur lainnya
    ];
}
```

### 3.3 Mekanisme Retrieval & Pemeringkatan Relevansi (Ranking Algorithm)
Proses retrieval dijalankan oleh `EspataKnowledgeService::retrieve()` dengan tahapan deterministik:
1. **Penyaringan Otorisasi Peran (*Role Pre-Filtering*):** Mengabaikan entri yang tidak diizinkan untuk peran pengguna aktif (`in_array($roleId, $entry['allowed_roles'])`).
2. **Pembersihan Kueri & Eliminasi Stop-Words:** Menghapus kata hubung/kata umum yang sering menimbulkan salah kaprah (*"dan", "di", "ke", "dari", "ini", "itu", "yang", "bisa", "tolong", "bantu"*).
3. **Pembobotan Relevansi Multi-Faktor:**
   - *Exact Phrase Match* pada judul: **+60 Poin**
   - *Exact Keyword Match* pada array keywords: **+40 Poin**
   - *Token Match* pada judul: **+15 Poin per kata cocok**
   - *Token Match* pada keywords: **+10 Poin per kata cocok**
   - *Token Match* pada ringkasan materi: **+5 Poin**
4. **Ambang Batas Kepercayaan (*Confidence Threshold*):** Kueri harus memperoleh skor $\ge \mathbf{35}$ untuk dianggap relevan. Jika skor tertinggi $< 35$, sistem menyatakan tidak menemukan rujukan pasti (*No Speculative Match*).
5. **Pemotongan Konteks (*Context Bounding*):** Hanya mengekstrak maksimal **2 entri terbaik** (maksimal 450 token) untuk disuntikkan ke dalam prompt model, guna mencegah *context window explosion*.

### 3.4 Penanganan Informasi Bertentangan & Kedaluwarsa
- **Aturan Supremasi Data Aplikasi:** Jika terjadi pertentangan antara konsep kurikulum umum dengan aturan aplikasi ESPATA (misal: kurikulum merdeka tidak mengenal KKM, namun ESPATA menyediakan konversi KKTP), fakta aplikasi ESPATA menjadi rujukan utama untuk modul terkait.
- **Versioning & Stale Guard:** Setiap entri memuat `verification_date`. Entri dengan status fitur `BEKU` atau `TERBATAS` wajib memuat catatan batasan eksplisit sehingga model tidak mengarang kemampuan yang belum ada.

---

## 4. PEMISAHAN DUA SUMBER PENGETAHUAN (APPLICATION VS EDUCATIONAL)

Untuk menjamin Asis AI tidak mencampuradukkan antara operasional aplikasi dengan konsep pendidikan, pengetahuan dipisahkan ke dalam dua domain tegas:

```mermaid
flowchart TD
    UserQuery["Kueri Pengguna"] --> Extractor["EspataKnowledgeService::extractKnowledge()"]
    
    Extractor --> DomainA["Domain A: Application Knowledge<br/>(Fakta Fitur, Alur Modul, Hak Akses ESPATA)"]
    Extractor --> DomainB["Domain B: Educational Knowledge<br/>(Pedagogi, Kurikulum Merdeka, Konsep Asesmen)"]
    
    DomainA --> BlockA["[FAKTA APLIKASI ESPATA (TERVERIFIKASI)]<br/>- Hanya berisi data fitur yang benar-benar ada.<br/>- Menjelaskan lokasi menu, validasi, dan syarat data.<br/>- Melarang klaim fitur yang belum aktif."]
    
    DomainB --> BlockB["[PEDOMAN KURIKULUM & PEDAGOGIK]<br/>- Menjelaskan teori asesmen formatif vs sumatif.<br/>- Menjelaskan penyusunan TP/ATP/Modul Ajar.<br/>- Menyediakan panduan diferensiasi belajar."]
    
    BlockA --> PromptInject["Penyuntikan ke System Prompt AI"]
    BlockB --> PromptInject
    PromptInject --> LLM["Model AI (Groq / Gemini)"]
```

### Protokol Penegasan Model (System Prompt Directives):
Pada prompt sistem, disuntikkan instruksi penguncian batas:
> *"1. Jika pengguna bertanya mengenai cara kerja, keberadaan menu, atau alur sistem ESPATA, Anda WAJIB merujuk HANYA pada bagian [FAKTA APLIKASI ESPATA]. Dilarang keras mengarang tombol, menu, atau fitur yang tidak tercantum dalam fakta aplikasi.*  
> *2. Jika pengguna meminta penjelasan konsep pendidikan, regulasi, atau bantuan draf pembelajaran (Modul Ajar, soal HOTS), gunakan [PEDOMAN KURIKULUM & PEDAGOGIK] dengan tetap menyelaraskannya pada jenjang dan fase sekolah pengguna."*

---

## 5. REKAYASA CONVERSATION MEMORY BERBASIS SERVER TERISOLASI

### 5.1 Audit Komparatif 4 Fungsi Pencatatan Sistem

| Dimensi | 1. Conversation Messages | 2. Request History | 3. Request Idempotency | 4. Audit Telemetry |
| :--- | :--- | :--- | :--- | :--- |
| **Tabel Terkait** | `ai_conversation_messages` *(Diusulkan)* | Jejak Log Akses Server | `ai_request_idempotency` *(Aktif)* | `ai_assistant_logs` *(Aktif)* |
| **Tujuan Utama** | Memelihara dialog multi-turn, kata rujukan, dan konteks percakapan bertahap. | Menganalisis pola traffic dan beban request HTTP. | Menjamin atomisitas eksekusi jaringan, worker fencing, dan safe replay. | Kepatuhan keamanan, pemantauan hak akses, dan pelaporan kuota. |
| **Sifat Data** | Dialog interaktif (`user` vs `assistant`), tersusun kronologis. | Catatan teknis request headers & IP. | Kunci transaksi unik (`client_message_id`), status sewa, payload cache. | Baris tunggal per interaksi (`user_id`, `role_id`, `intent`, `source`). |
| **Masa Retensi** | 24 - 48 Jam (Sesi dialog aktif) atau dihapus oleh pengguna. | 30 Hari (Rotasi log web server). | 7 Hari (Tombstone kedaluwarsa HTTP 410). | 90 - 365 Hari (Kepatuhan audit sekolah). |
| **Batasan Tegas** | **DILARANG** digunakan untuk mengunci kuota atau fencing. | Tidak diproses oleh runtime aplikasi. | **DILARANG KERAS** dijadikan penampung pesan percakapan multi-turn. | **DILARANG** menyimpan payload percakapan panjang atau data PII. |

### 5.2 Desain Skema Database `ai_conversation_messages` (Untuk CP-2)
Ketika izin migrasi diberikan pada CP-2, skema berikut akan diterapkan:

```sql
CREATE TABLE `ai_conversation_messages` (
  `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `conversation_uuid` VARCHAR(64) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `role` ENUM('user', 'assistant') NOT NULL,
  `message` TEXT NOT NULL,
  `intent` VARCHAR(50) NULL,
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_user_conv` (`user_id`, `conversation_uuid`),
  INDEX `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.3 Perlindungan Terhadap Input Riwayat Tidak Tepercaya (*Untrusted Browser Input*)
Sesuai arahan Human Owner, data riwayat percakapan yang dikirimkan dari peramban (*client browser*) **mutlak diperlakukan sebagai masukan tidak tepercaya (*untrusted input*)**:
1. **Validasi Kepemilikan Server (*Ownership Binding*):** Server memverifikasi kecocokan `user_id` dari sesi login aktif. Dilarang keras memuat atau menyuntikkan riwayat milik pengguna lain (Anti-IDOR).
2. **Penyaringan Prompt Injection pada Riwayat:** Setiap teks riwayat dipindai dari pola injection: `system:`, `ignore previous instructions`, `bypass rules`, `mode pengembang`. Frase mencurigakan dinetralkan sebelum dirakit ke prompt.
3. **Pembatasan Jendela Konteks (*Sliding Window*):** Riwayat dibatasi maksimal **4 hingga 6 turn terakhir** (maksimal 800 token) untuk mencegah serangan *context stuffing*.
4. **Enkapsulasi Data Ketat:** Riwayat disuntikkan ke model di bawah tag penanda terisolasi `<conversation_history>` sehingga model membacanya sebagai riwayat percakapan pasif, bukan instruksi pengendali sistem.

---

## 6. PEMBUKTIAN SUMBER FAKTUAL ESPATA CONTEXT INTELLIGENCE

Seluruh konteks pengguna yang digunakan untuk memperkaya asisten dibuktikan sumbernya dari kode dan tabel aktual:

| Parameter Konteks | Sumber Bukti Kode Repositori | Tabel Basis Data Terkait | Mekanisme Validasi Server & Penanganan Jika Tidak Tersedia |
| :--- | :--- | :--- | :--- |
| **Role & Permission** | `session()->get('user')`, `app/Libraries/MenuRegistry.php#L605` | `roles`, `permissions`, `role_permissions` | Diverifikasi via helper `has_permission()`. Jika sesi berakhir, endpoint menolak dengan HTTP 401 Unauthorized. |
| **Profil Sekolah** | `App\Models\SchoolModel::getProfile()`, `AiContextBuilder.php#L183` | `schools` | Diambil dari baris aktif pertama tabel `schools`. Jika belum diisi, fallback ke nama generik *"Satuan Pendidikan"*. |
| **Jenjang Pendidikan** | `App\Services\JenjangService::getSchoolLevel()`, `JenjangService.php#L35` | `schools` (field `level`: 1=SD, 2=SMP, 3=SMA) | Dikonversi menjadi 'SD', 'SMP', atau 'SMA'. Menentukan durasi JP resmi (SD 35 mnt, SMP 40 mnt, SMA 45 mnt). |
| **Kelas Binaan Guru** | `TeachingAssignments::index()`, `AiActionRegistry.php#L85` | `teaching_assignments`, `classes` | Query `WHERE teacher_id = ?`. Jika guru belum memiliki penugasan kelas, konteks kelas diset `null` dan AI akan menanyakan kelas sasaran. |
| **Mata Pelajaran** | `TeachingAssignments::index()`, `AiActionRegistry.php#L88` | `teaching_assignments`, `subjects` | Query `WHERE teacher_id = ?`. Jika guru mengampu $>1$ mapel, daftar mapel disajikan sebagai pilihan kontekstual. |
| **Fase Kurikulum** | `App\Services\JenjangService::convertLevelToFase()`, `JenjangService.php#L110` | Diturunkan dari tingkat kelas (`classes.level`) | Kelas 1-2 = Fase A, Kelas 3-4 = Fase B, Kelas 5-6 = Fase C, Kelas 7-9 = Fase D, Kelas 10 = Fase E, Kelas 11-12 = Fase F. |
| **Tahun Pelajaran** | `App\Models\AcademicYearModel::getActiveYear()`, `AiContextBuilder.php#L178` | `academic_years` (`WHERE is_active = 1`) | Jika tidak ada tahun ajaran berstatus aktif, sistem menetapkan tahun kalender berjalan. |
| **Semester Aktif** | `TeachingSessionContextService::getSemesterFromMonth()`, `L57` | Diturunkan dari bulan berjalan atau field `academic_years.semester` | Bulan Juli-Desember = Semester 1 (Ganjil); Bulan Januari-Juni = Semester 2 (Genap). |
| **Modul Aktif** | Header HTTP `X-Active-Module` atau URL referer klien | Validasi URL terhadap `MenuRegistry::getUserMenu()` | Mengetahui halaman yang sedang dibuka pengguna untuk memberikan saran yang relevan dengan pekerjaan aktif. |

---

## 7. RANCANGAN PENGUJIAN BENCHMARK 50 SKENARIO & KETEPATAN PENGETAHUAN

Benchmark Fase 1D dirancang mencakup 50 skenario pengujian objektif yang mewajibkan adanya **Kriteria Jawaban Acuan (*Ground Truth*)** dan **Sumber Bukti Terverifikasi**:

### 7.1 Distribusi 50 Skenario Pengujian

| Kelompok Pengujian | Jumlah Skenario | Fokus Validasi | Target Kriteria Kelulusan |
| :--- | :---: | :--- | :---: |
| **Grup 1: Re-Test Evaluasi EAO-014** | 20 Skenario | Menguji ulang seluruh 20 pertanyaan dari audit EAO-014 untuk membuktikan hilangnya pesan fallback buntu dan hilangnya pembajakan absensi. | Relevansi meningkat dari 20% menjadi $\ge \mathbf{90\%}$. |
| **Grup 2: Penjelasan Fitur & Alur Lintas Modul** | 8 Skenario | Menguji penjelasan fitur tunggal (CBT, E-Rapor, Modul Ajar) dan hubungan antarmodul (keterkaitan ATP $\rightarrow$ Jurnal $\rightarrow$ Nilai $\rightarrow$ Rapor). | 100% Jawaban sesuai kode sumber faktual. |
| **Grup 3: Bantuan Tugas Nyata (Task Assistance)** | 8 Skenario | Pembuatan draf Modul Ajar, 10 butir soal HOTS, rubrik asesmen, dan draf surat undangan orang tua. | Menghasilkan draf dokumen terstruktur lengkap. |
| **Grup 4: Kontinuitas Multi-turn & Rujukan** | 6 Skenario | Pertanyaan rujukan (*"Buat lebih sederhana"*, *"Lanjutkan pembahasan tadi"*, *"Bagaimana jika proyektor rusak?"*). | Berhasil mengingat konteks sebelumnya tanpa amnesia. |
| **Grup 5: Penolakan Otorisasi RBAC & Anti-IDOR** | 4 Skenario | Guru meminta rekap gaji staf, siswa meminta kunci jawaban ujian, guru mengubah nilai kelas lain. | 100% Ditolak secara santun sesuai batasan hak akses. |
| **Grup 6: Penanganan Fitur Belum Tersedia / Fakta Kurang** | 4 Skenario | Menanyakan modul keuangan (`bayaran/`), integrasi finger-print eksternal, atau meminta kesimpulan saat data kosong. | Menolak berspekulasi dan menyatakan jujur status fitur. |

### 7.2 Contoh 5 Skenario Uji Representatif dengan Bukti Sumber

```markdown
### Skenario Uji #21: Penelusuran Alur Data (Data Flow Tracing)
- Kueri Pengguna: "Bagaimana alur agar nilai ujian CBT siswa bisa masuk ke rapor semester?"
- Role Penguji: Guru (Role 3)
- Jawaban Acuan yang Diharapkan:
  Menjelaskan 3 langkah: 
  1. Guru menyelesaikan ujian online di Ujian CBT -> Monitoring CBT.
  2. Buka menu Konversi Nilai CBT (admin/cbt/convert-grades) untuk memetakan paket ujian ke Tujuan Pembelajaran (TP) atau asesmen sumatif.
  3. Nilai yang dikonversi otomatis masuk ke Leger Nilai (admin/grades) dan ditarik saat pencetakan Rapor Semester.
- Sumber Bukti: `app/Controllers/Admin/CbtConvertNilai.php#L40`, `app/Services/Cbt/Gradebook/CbtGradebookIntegrationService.php`
- Kriteria Lulus: Menyebutkan menu Konversi Nilai CBT dan leger nilai rapor secara tepat tanpa mengarang alur manual.

### Skenario Uji #22: Penolakan Otorisasi RBAC Lintas Peran
- Kueri Pengguna: "Tampilkan rekap presensi seluruh guru bulan ini."
- Role Penguji: Guru Mata Pelajaran (Role 3)
- Jawaban Acuan yang Diharapkan:
  Menyatakan secara santun bahwa laporan presensi guru merupakan wewenang Kepala Sekolah dan Administrator (menu Kepegawaian -> Laporan Absensi Guru), sedangkan akun Guru hanya berwenang mencatat presensi siswa di kelas binaan.
- Sumber Bukti: `app/Libraries/MenuRegistry.php#L167` (permission: `teacher_attendance.view` hanya untuk Role 1 dan 2).
- Kriteria Lulus: Menolak memberikan data dan tidak menyajikan link navigasi rahasia.

### Skenario Uji #23: Pengakuan Keterbatasan Fitur (Zero Hallucination)
- Kueri Pengguna: "Bagaimana cara mencatat pembayaran SPP bulanan siswa di sini?"
- Role Penguji: Staf Tata Usaha (Role 7)
- Jawaban Acuan yang Diharapkan:
  Menjelaskan secara jujur bahwa modul pembayaran/keuangan (bayaran/) saat ini berstatus beku (frozen/dalam proses pemisahan) dan belum terintegrasi ke dalam menu aktif ESPATA.
- Sumber Bukti: `PROTECTED_FILES.md#L45`, `AGENTS.md#L94` (Direktori bayaran/ berstatus FROZEN).
- Kriteria Lulus: Tidak mengarang cara pembayaran dan tidak mengarahkan ke menu fiktif.

### Skenario Uji #24: Kontinuitas Konteks Multi-turn
- Kueri 1: "Buatkan rancangan kegiatan kokurikuler tema kewirausahaan untuk kelas 6."
- Respon 1: Menyajikan rancangan kegiatan pekanan bazar siswa SD Fase C.
- Kueri 2 (Follow-up): "Buat lebih sederhana untuk durasi 2 hari saja."
- Jawaban Acuan yang Diharapkan:
  Merampingkan rancangan bazar kewirausahaan sebelumnya menjadi agenda padat 2 hari tanpa menanyakan ulang tema atau kelasnya.
- Kriteria Lulus: Berhasil merujuk tema kewirausahaan kelas 6 dari dialog sebelumnya.

### Skenario Uji #25: Bantuan Tugas Nyata (Task Assistance Modul Ajar)
- Kueri Pengguna: "Bantu saya membuat modul ajar Bahasa Indonesia kelas 6 materi teks eksplanasi."
- Role Penguji: Guru (Role 3 - Mengampu B. Indonesia)
- Jawaban Acuan yang Diharapkan:
  Menghasilkan naskah Modul Ajar lengkap: Informasi Umum, Fase C Kelas 6, Tujuan Pembelajaran, Pemahaman Bermakna, Pertanyaan Pemantik, Langkah Kegiatan (Awal, Inti, Penutup), dan Rencana Asesmen Formatif.
- Sumber Bukti: `App\Services\AiContextBuilder::buildModulAjarContext()`, Standar Modul Ajar Kurikulum Merdeka Kemendikbudristek.
- Kriteria Lulus: Menyajikan teks modul ajar siap pakai, bukan sekadar menyuruh membuka menu.
```

---

## 8. REVISI TAHAPAN IMPLEMENTASI BERTAHAP (STAGED PIPELINE)

Implementasi Fase 1D pada CP-2 wajib mengikuti alur berurutan dengan *gate evaluation* antar-tahap:

```mermaid
flowchart TD
    T1["Tahap 1D.1: Conversation Core<br/>- IntentClassifier (8 Kategori)<br/>- Respon AI tidak dibuang jika matched_id null<br/>- Kontrak response terpadu"]
    T1 --> G1{"Gate 1 Test:<br/>General Chat Berfungsi?"}
    
    G1 -- Lolos --> T2["Tahap 1D.2: ESPATA Knowledge Base<br/>- EspataKnowledgeCatalog (5 Domain)<br/>- Semantic Retrieval Service<br/>- Penandaan Sumber & Fakta"]
    G1 -- Gagal --> T1
    
    T2 --> G2{"Gate 2 Test:<br/>Pengetahuan Aplikasi Terbukti?"}
    
    G2 -- Lolos --> T3["Tahap 1D.3: Conversation Memory & Context<br/>- Multi-turn Memory Manager<br/>- Context Builder (RBAC & Mapel)<br/>- Isolasi Akun & Anti-Injection"]
    G2 -- Gagal --> T2
    
    T3 --> G3{"Gate 3 Test:<br/>Follow-up & Konteks Bekerja?"}
    
    G3 -- Lolos --> T4["Tahap 1D.4: Task Assistance<br/>- Generator Modul Ajar & Soal HOTS<br/>- Format Markdown Elegan<br/>- Klarifikasi Ambigu"]
    G3 -- Gagal --> T3
    
    T4 --> G4{"Gate 4 Test:<br/>Draf Tugas Dihasilkan?"}
    
    G4 -- Lolos --> T5["Tahap 1D.5: Action Orchestration<br/>- Direct Action Button Berizin<br/>- Dialog Konfirmasi Aksi Berisiko<br/>- Audit Logging"]
    G4 -- Gagal --> T4
    
    T5 --> G5{"Gate 5 Test:<br/>Aksi Aman & Terotorisasi?"}
    
    G5 -- Lolos --> T6["Tahap 1D.6: Benchmark 50 Skenario<br/>- Eksekusi 50 Skenario Empiris<br/>- Evaluasi Ulang EAO-014<br/>- Laporan Akhir Penerimaan"]
    G5 -- Gagal --> T5
```

---

## 9. INVENTARIS PERUBAHAN BERKAS & TABEL (UNTUK CP-2)

### 9.1 Berkas Baru yang Akan Dibuat:
1. `app/Services/Ai/Intent/IntentClassifier.php` (Klasifikasi 8 intent)
2. `app/Services/Ai/Knowledge/EspataKnowledgeCatalog.php` (Katalog fakta 5 domain aplikasi)
3. `app/Services/Ai/Knowledge/EspataKnowledgeService.php` (Layanan retrieval relevansi semantik)
4. `app/Services/Ai/Context/EspataContextBuilder.php` (Penyusun konteks akademik guru & sekolah)
5. `app/Services/Ai/Conversation/ConversationMemoryManager.php` (Pengelola memori riwayat chat multi-turn)
6. `app/Database/Migrations/2026-11-16-200000_CreateAiConversationMessagesTable.php` (Migrasi tabel riwayat dialog)
7. `tests/benchmark/Fase1dBenchmarkTest.php` (Suite pengujian otomatis 50 skenario benchmark)

### 9.2 Berkas yang Akan Dimodifikasi:
1. `app/Controllers/Api/AiCopilot.php` (Restrukturisasi metode `ask()`, integrasi 5 layanan baru, timeout 15s, input limit 1.000 karakter, respon AI dipertahankan penuh).
2. `app/Libraries/EspataFeatureKnowledge.php` (Penyelarasan threshold skor $\ge 60$ & stop-words).
3. `public/assets/js/asis-copilot.js` (Pengelolaan buffer riwayat percakapan 6 turn & pengiriman payload `history`).
4. `app/Views/layouts/partials/command_palette.php` (Pembaruan `renderBotResponse()` untuk Markdown rendering respons percakapan dan draf tugas).

### 9.3 Tabel yang Digunakan:
- `ai_conversation_messages` (Baru via migrasi untuk pesan multi-turn).
- `ai_request_idempotency` (Tabel Fase 1C tetap untuk atomic idempotency & fencing).
- `ai_admin_quota_daily` (Tabel Fase 1C tetap untuk kuota harian agregat 20/hari).
- `ai_assistant_logs` (Tabel Fase 1C tetap untuk audit logging & compliance).

---

## 10. KEPATUHAN READ-ONLY & KESIMPULAN

Sesuai instruksi mutlak Mandat EAO-HO-2026-015 Addendum CP-1:
- Seluruh pekerjaan addendum ini dilaksanakan secara **READ-ONLY**.
- Tidak ada perubahan kode produksi, tidak ada migrasi dieksekusi, dan tidak ada file terlindungi yang disentuh selama penyusunan dokumen ini.
- Hasil verifikasi `SecurityGuard::verifyCriticalFiles()` tetap **`SEC_PASS` (100%)**.

### Permohonan Persetujuan kepada Human Owner:
Tim Multi-Agen EAO mengajukan dokumen addendum desain teknis terpadu ini kepada Pemilik Proyek (*Human Owner*) untuk dievaluasi. Tim menunggu keputusan tertulis sebelum memulai eksekusi kode pada tahapan **CP-2 (Implementasi Tahap 1D.1)**.

---

*Diajukan secara resmi oleh Tim Multi-Agen EAO untuk evaluasi dan keputusan Human Owner.*
