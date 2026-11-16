# ESPATA PROJECT MASTER SPECIFICATION
**Dokumen Induk Spesifikasi, Arsitektur, dan Standar Sistem ESPATA**

---

| Parameter | Keterangan Faktual |
|:---|:---|
| **Nama Aplikasi** | ESPATA (School Management Platform) |
| **Versi Aplikasi** | `v2.0.7` (Didefinisikan pada `app/Config/Constants.php`) |
| **Status Lingkungan** | **Localhost Development** (XAMPP Windows, Apache/MySQL) |
| **Target Produksi** | Linux Shared Hosting (cPanel / LiteSpeed Web Server) |
| **Bahasa & Runtime** | PHP `^8.1` (Lingkungan aktif saat ini: PHP 8.2) |
| **Framework Inti** | CodeIgniter `v4.6.3` (`system/`) |
| **Basis Data** | MySQL 8.x / MariaDB (`siakad`, 167 tabel aktif, 108 berkas migrasi) |
| **Prioritas Pilot EAO**| **Asis AI ESPATA** (Asisten Cerdas Akademik & Operasional Sekolah) |

---

## 1. Identitas & Visi Produk

**ESPATA** adalah platform manajemen sekolah terpadu yang dirancang untuk mendukung operasional tata kelola pendidikan modern sesuai standar resmi **Kurikulum Merdeka**. Sistem dirancang dengan keunggulan:
- **Ramah Shared Hosting:** Mengoptimalkan konsumsi memori dan mengelola penguncian sesi (*session lock management*) secara efisien tanpa memerlukan dependensi daemon background yang berat.
- **Dukungan Multi-Jenjang Nasional:** Melayani jenjang SD/MI, SMP/MTs, dan SMA/SMK/MA dalam satu arsitektur terintegrasi.
- **Asis AI Terintegrasi:** Menghadirkan asisten kecerdasan buatan terpadu untuk konsultasi guru, panduan penggunaan sistem (*built-in knowledge base*), generator modul ajar, rekomendasi kokurikuler kolaboratif, penilaian esai CBT otomatis, dan sambutan suara harian (*Voice Text-to-Speech*).

---

## 2. Ruang Lingkup Sistem & Rencana Pemisahan Aplikasi

Berdasarkan audit repositori dan keputusan resmi pemilik proyek:
1. **Aplikasi Inti (ESPATA Core):** Seluruh direktori `app/`, `public/`, `system/`, dan modul-modul akademik sekolah menjadi fokus utama proyek ini.
2. **Status Pemisahan Sub-Aplikasi (Decommissioned & Removed from ESPATA Repository):**
   - **`bayaran/` (Aplikasi SPP/Keuangan) & `cbtku/` (Aplikasi CBT Standalone):** Berdasarkan keputusan dan mandat resmi Human Owner (EAO-HO-2026-027, ADR-003, dan EAO-HO-2026-029-REV1), kedua sub-aplikasi tersebut **bukan merupakan bagian dari ruang lingkup pengembangan ESPATA** dan telah **dihapus secara fisik dan direkonsiliasi permanen dari repositori ESPATA**.
   - **Pengelolaan Salinan Cadangan (Backup):** Salinan cadangan (*backup*) kedua aplikasi dikelola dan diamankan secara terpisah di luar repositori oleh Human Owner.
   - **Batasan Pengembangan:** Seluruh pengembangan fitur, arsitektur, dan kecerdasan Asis AI pada repositori ESPATA **dilarang bergantung pada direktori atau *source code* kedua aplikasi tersebut**. Kebutuhan integrasi di masa depan (misal: penarikan tagihan atau pertukaran data nilai) wajib dilakukan melalui antarmuka API/protokol pertukaran data resmi berdasarkan keputusan dan mandat tersendiri.
   - **`bridge/` (SIAKAD Sync Agent):** Script integrasi PowerShell untuk menjembatani sinkronisasi Web Service Dapodik lokal (port 5774) menuju server online via endpoint `api/dapodik/receive`.

---

## 3. Peta 13 Modul Utama Aplikasi

Berdasarkan registry menu dinamis pada `App\Libraries\MenuRegistry.php` dan tabel database aktual:

### 1. Administrasi Siswa (`menuKesiswaanDyn`)
- **Data Induk Siswa:** Master profil, biodata orang tua, NISN/NIS, riwayat kelas (`students`).
- **Master Kelas & Kelas Aktif:** Manajemen tingkat rombel dan penugasan wali kelas (`classes`).
- **Penempatan Siswa (*Placement*):** Pembagian siswa ke kelas aktif tahun ajaran berjalan.
- **Kenaikan & Kelulusan:** Pemrosesan status kenaikan tingkat atau alumni secara massal.
- **Buku Mutasi Siswa:** Pencatatan keluar/masuk siswa (`student_mutations`).
- **Riwayat Siswa (*Student Records*):** Jejak perjalanan prestasi dan pelanggaran (`student_records`).
- **Peta Sebaran Siswa:** Visualisasi geografis lokasi tempat tinggal siswa berbasis Leaflet.js & OpenStreetMap.
- **Alumni:** Basis data penelusuran lulusan dan jenjang lanjutan.

### 2. Tata Usaha & Persuratan (`menuTataUsahaDyn`)
- **Surat Masuk:** Pencatatan surat dinas masuk, pengarsipan berkas fisik (`incoming_letters`).
- **Surat Keluar:** Penomoran otomatis, pembuatan surat via template dinamis (`outgoing_letters`, `letter_templates`).
- **Agenda Surat:** Rekapitulasi agenda persuratan sekolah.
- **Buku Tamu Digital:** Form pencatatan kunjungan tamu publik dan dashboard verifikasi (`buku_tamu`).
- **Pengaturan Kop Surat & Identitas Pemda:** Penyesuaian header resmi, logo sekolah, dan logo pemerintah daerah pada profil sekolah (`school_profile`).

### 3. Kepegawaian & GTK (`menuKepegawaianDyn`)
- **Data Guru & Tenaga Kependidikan:** NIP, NUPTK, jenis PTK, gelar, riwayat pendidikan, pelatihan, dan karir (`teachers`, `teacher_careers`, `teacher_educations`, `teacher_trainings`).
- **Penugasan Mengajar:** Distribusi alokasi beban mengajar guru per mata pelajaran dan kelas (`teaching_assignments`).
- **Presensi Pegawai:** Presensi harian GTK berbasis sesi atau jam kerja (`teacher_attendances`, `teacher_attendance_sessions`).
- **Rekap Presensi Guru:** Laporan persentase kehadiran dan rekapitulasi kedisiplinan.

### 4. Akademik & KBM (`menuAkademikDyn`)
- **Master Mata Pelajaran:** Pengelompokan mapel umum, muatan lokal, dan peminatan (`subjects`, `mapel_master`).
- **Jadwal Pelajaran:** Plotting jadwal mingguan per jam pelajaran (Senin s/d Kamis aktif di database saat audit) (`schedules`).
- **Jurnal Mengajar Guru:** Jurnal harian KBM tatap muka terintegrasi materi dan kehadiran siswa (`teaching_journals`).
- **Presensi Siswa per JP:** Pencatatan kehadiran siswa per mata pelajaran secara real-time (`attendances`).
- **Rekap Kehadiran Siswa:** Statistik kehadiran (Hadir, Sakit, Izin, Alpa) bulanan dan semesteran (`attendance_recaps`).
- **Catatan Karakter Siswa:** Rekam jejak pembiasaan positif dan catatan perilaku (`student_notes`, `behaviors`).
- **Kalender Akademik & Hari Libur:** Pengaturan agenda pendidikan tahunan dan hari libur sekolah (`holidays`).

### 5. Kurikulum & Modul Ajar (`menuKurikulumDyn`)
- **Capaian Pembelajaran (CP):** Bank data CP resmi per fase pendidikan (`cp_master`).
- **Alur Tujuan Pembelajaran (ATP):** Penjabaran elemen CP menjadi tujuan pembelajaran berurutan (`alur_tujuan_pembelajaran`, `atp_elemen`, `tujuan_pembelajaran`).
- **Perangkat Ajar (Modul Ajar):** Penyusunan modul ajar Kurikulum Merdeka terintegrasi **AI Generator Modul** (`modul_ajar`).
- **Program Semester (Prosem):** Distribusi alokasi jam tatap muka efektif semester (`prosem_distributions`).
- **KKTP:** Kriteria Ketercapaian Tujuan Pembelajaran per mata pelajaran (`kktp`).
- **Kokurikuler Terintegrasi AI:** Perencanaan kegiatan kokurikuler kolaboratif lintas mata pelajaran terintegrasi **Asisten AI Perencana Kokurikuler** (`kokurikuler_documents`, `kokurikuler_rubrik`, `kokurikuler_pelaksanaan`, `kokurikuler_penilaian`). *Catatan: Nomenklatur resmi adalah Kokurikuler.*

### 6. Penilaian & Rapor (`menuPenilaianDyn`)
- **Input Nilai Formatif & Sumatif:** Penginputan nilai harian, tugas, kuis, PTS, dan PAS (`grades`, `subject_scores`, `pts_scores`, `final_exam_scores`).
- **Nilai Rapor Semester:** Kalkulasi bobot nilai akhir berdasarkan bobot mata pelajaran (`subject_weights`, `rapor_notes`).
- **E-Rapor Kurikulum Merdeka:** Pencetakan rapor berbasis Capaian Kompetensi / Tujuan Pembelajaran (`erapor_student_tp`, `erapor_tp_settings`).
- **SNBP / Portofolio Siswa:** Analitik perankingan nilai siswa untuk seleksi nasional masuk perguruan tinggi.
- **Monitoring Penilaian:** Panel Kepala Sekolah untuk memantau status penyelesaian input nilai oleh seluruh guru.

### 7. Ujian Online / CBT V3 (`menuCbtDyn`)
- **Bank Soal:** Soal Pilihan Ganda tunggal, Pilihan Ganda Kompleks, Benar/Salah, dan Esai (`cbt_bank_soal`, `cbt_questions`).
- **Status Tes & Jadwal:** Pengaturan token ujian, durasi, dan aktivasi rombel peserta (`cbt_test_status`).
- **Monitoring Real-Time:** Pengawasan aktivitas siswa selama ujian dengan deteksi anti-curang (*cheat log*) (`cbt_student_sessions`, `cbt_cheat_logs`).
- **Koreksi Esai & Auto-Grading:** Penilaian esai manual oleh guru atau terintegrasi kecerdasan buatan (`cbt_essay_grades_v3`).
- **Berita Acara Ujian Digital (Digital BAP):** Pencatatan kehadiran peserta, pengawas, dan penandatanganan digital (`cbt_bap_documents_v3`, `cbt_bap_students_v3`).
- **Audit Trail Kriptografis V3:** Integritas jawaban siswa dilindungi rantai hash Merkle Tree (`cbt_audit_trail_v3`).
- **Rekap & Konversi Nilai:** Konversi skor CBT ke dalam gradebook penilaian akademik.
- **Kartu Peserta Ujian:** Cetak kartu ujian siswa dilengkapi foto dan QR Code verifikasi.

### 8. Bimbingan Konseling / BK (`menuBkDyn`)
- **Data Kasus & Pelanggaran:** Penanganan pelanggaran tata tertib dan poin kedisiplinan (`bk_cases`, `bk_case_logs`, `bk_case_actions`).
- **Surat Panggilan Ortu/Siswa:** Pembuatan surat panggilan konseling resmi (`bk_summons`).
- **Layanan Konseling:** Konseling individual, bimbingan kelompok, dan konseling kelompok (`bk_services`, `bk_service_participants`).
- **Instrumen Asesmen BK:** Angket kebutuhan siswa, instrumen diagnostik, dan interpretasi hasil (`bk_instruments`, `bk_instrument_questions`, `bk_student_answers`, `bk_student_results`).
- **Program Kerja & RPL BK:** Rencana Pelaksanaan Layanan BK semesteran dan tahunan (`bk_programs`, `bk_rpl`).
- **Bimbingan Karir & Studi Lanjutan:** Pemetaan minat bakat dan rencana karir siswa (`bk_career_profiles`, `bk_individual_plans`).

### 9. Ekstrakurikuler (`menuEkskulDyn`)
- **Master Ekstrakurikuler:** Daftar kegiatan ekskul wajib dan pilihan (`ekskul_master`).
- **Pembina & Anggota:** Penugasan guru pembina dan keanggotaan siswa (`ekskul_pembina`, `ekskul_members`).
- **Jadwal & Presensi Ekskul:** Agenda mingguan dan absensi kegiatan ekskul (`ekskul_schedules`, `ekskul_attendances`, `ekskul_jurnal`).
- **Penilaian & Rapor Ekskul:** Predikat dan deskripsi capaian kegiatan ekskul (`ekskul_scores`).

### 10. Publikasi & CMS Website (`menuCmsDyn`)
- **Artikel & Berita:** Pengelolaan publikasi portal berita sekolah (`landing_articles`).
- **Aktivitas & Galeri Foto:** Dokumentasi kegiatan sekolah (`landing_activities`, `landing_activity_photos`).
- **Sambutan Kepala Sekolah:** Sambutan resmi pimpinan sekolah untuk halaman landing.
- **Fasilitas Sekolah:** Direktori sarana dan prasarana sekolah (`landing_facilities`).
- **Tautan Penting & Slider:** Tautan eksternal dan banner slider carousel beranda (`landing_links`, `landing_sliders`).

### 11. Perpustakaan Digital / E-Book (`menuPerpusDyn`)
- **Katalog Buku Elektronik:** Koleksi buku teks pelajaran Kurikulum Merdeka dan buku referensi (`ebooks`).
- **Manajemen Berkas Dokumen:** Unggah berkas PDF materi pembelajaran dengan kontrol hak akses siswa/guru.

### 12. Komunikasi & Interaksi (`menuKomunikasiDyn`)
- **Pengumuman Bertarget:** Distribusi informasi sekolah bertarget peran (Semua, Guru, Siswa, Orang Tua) atau kelas tertentu (`announcements`, `announcement_targets`).
- **Chat Kelas:** Komunikasi interaktif guru dan siswa dalam rombel kelas (`chat_rooms`, `chat_messages`, `chat_mentions`).
- **Chat Staf & GTK:** Saluran komunikasi privat antar-pegawai sekolah (`staff_chat_rooms`, `staff_chat_messages`).
- **Forum Diskusi Akademik:** Wadah tanya jawab pembelajaran (`forum_threads`, `forum_replies`, `forum_upvotes`).

### 13. Pengaturan Sistem & Keamanan (`menuSettingsDyn`)
- **Identitas Sekolah:** Nama sekolah, NPSN, akreditasi, alamat, koordinat peta, kontak, dan logo (`school_profile`).
- **Tahun Ajaran & Semester:** Pengaturan semester aktif (Ganjil/Genap) dan tahun akademik (`academic_years`).
- **Jenjang & Tingkat Sekolah:** Multi-Tier SD, SMP, SMA (`jenjang_master`).
- **Manajemen Pengguna:** Pengelolaan akun, reset password massal, dan proteksi ganti password default (`users`).
- **Manajemen Hak Akses Dinamis (Dynamic RBAC):** Pengaturan matriks hak akses menu dan controller (`roles`, `permissions`, `role_permissions`).
- **Audit Trail:** Log rekaman aktivitas login, logout, create, update, dan delete pengguna (`audit_logs`).
- **Pembaruan Sistem (Updater):** Pemeriksaan integritas berkas terhadap `update_manifest.json` dan aplikasi pembaruan berkas.
- **Lisensi & Proteksi Hardware Binding:** Validasi domain, aktivasi online, dan enkripsi status lisensi (`app_license`).

---

## 4. Arsitektur Multi-Tier Jenjang Pendidikan

ESPATA mengelola tingkatan kelas dan fase kurikulum melalui `App\Services\JenjangService.php`:

| Tingkat Sekolah | Kode Level | Rentang Kelas | Fase Kurikulum Merdeka | Standar Durasi per JP |
|:---|:---:|:---:|:---|:---:|
| **SD / MI** | `1` | Kelas 1 – 6 | **Fase A** (Kelas 1–2)<br>**Fase B** (Kelas 3–4)<br>**Fase C** (Kelas 5–6) | **35 Menit** |
| **SMP / MTs** | `2` | Kelas 7 – 9 | **Fase D** (Kelas 7–9) | **40 Menit** |
| **SMA / SMK / MA** | `3` | Kelas 10 – 12 | **Fase E** (Kelas 10 - Fondasi)<br>**Fase F** (Kelas 11–12 - Peminatan) | **45 Menit** |

---

## 5. Struktur Otorisasi & Peran Pengguna (RBAC)

ESPATA memiliki 7 peran sistem bawaan (*built-in roles*):
- **Role 1 (Administrator):** Pemegang wewenang penuh konfigurasi sistem, audit, perbaikan, dan manajemen pengguna.
- **Role 2 (Kepala Sekolah):** Akses monitoring eksekutif, rekap kehadiran pegawai, supervisi jurnal guru, dan pemantauan kelulusan.
- **Role 3 (Guru):** Pengelolaan KBM, presensi siswa per JP, jurnal mengajar harian, modul ajar, bank soal CBT, input nilai, dan rapor.
- **Role 4 (Orang Tua):** Portal pantau kehadiran putra/putri, catatan karakter, jadwal, dan nilai rapor.
- **Role 5 (Siswa):** Portal pembelajaran, akses materi/modul, pengerjaan tugas, ujian CBT, chat kelas, dan perpustakaan digital.
- **Role 6 (Kontributor CMS):** Pengelolaan artikel berita, dokumentasi aktivitas, dan publikasi website sekolah.
- **Role 7 (Staf Tata Usaha):** Manajemen persuratan dinas, buku tamu, kearsipan GTK, dan administrasi perkantoran.

*Catatan: Sistem mendukung custom role tambahan (misal: Pelatih Ekskul, Bendahara) yang menunya diatur secara dinamis melalui `App\Libraries\MenuRegistry.php`.*

---

## 6. Arsitektur & Spesifikasi Proyek Pilot: Asis AI ESPATA

Sebagai proyek pertama EAO, **Asis AI ESPATA** dibangun dengan komponen inti:
1. **Antarmuka Pengguna:** Command Palette (<kbd>Ctrl</kbd> + <kbd>K</kbd>), tab Navigasi Cepat, tab Chat Asisten AI, dan tombol mengambang (*Floating Action Button*).
2. **Mesin Pengetahuan Bawaan (*Offline Knowledge Base*):** `App\Libraries\EspataFeatureKnowledge.php` yang memetakan panduan resmi penggunaan modul ESPATA tanpa membutuhkan panggilan API luar untuk pertanyaan umum.
3. **Pencarian Aksi Kontekstual RBAC:** `App\Libraries\AiActionRegistry.php` menyaring menu dan tombol aksi sesuai izin role yang sedang aktif.
4. **Sambutan Suara Pagi (*Voice Text-to-Speech*):**
   - Mendeteksi hari kerja (Senin–Kamis), hari tanpa jadwal, dan hari libur akhir pekan (Sabtu & Minggu).
   - Menyapa nama guru dengan sebutan santun (Bapak/Ibu) berdasarkan jenis kelamin.
   - Menyajikan rekapitulasi eksekutif bagi Kepala Sekolah dan Admin.
   - Dilengkapi *Sequential Sentence Chunking* untuk mencegah bug timeout 15 detik pada engine Chromium peramban Windows.
5. **Provider Model Bahasa AI:** Mendukung model Groq API (`openai/gpt-oss-20b`, `qwen/qwen3.8-27b`, dll.) dan Google Gemini API dengan fallback adaptif.
6. **Audit & Log Percakapan:** Disimpan pada tabel `ai_assistant_logs` (migrasi `2026-11-13-180000_CreateAiAssistantLogsTable.php`).

---

## 7. Standar Teknis & Batasan Arsitektur

1. **Kompatibilitas Shared Hosting:**
   - Dilarang membuat dependensi proses background persisten (*daemon background worker*) yang membutuhkan supervisor atau socket sistem khusus.
   - Setiap proses eksternal yang memakan waktu (seperti request API AI) wajib segera melepaskan kunci sesi menggunakan `session_write_close()` sebelum pemrosesan data berat dimulai.
2. **Integritas Basis Data:**
   - Semua modifikasi skema tabel WAJIB menggunakan migration CodeIgniter 4 pada folder `app/Database/Migrations/`.
   - Dilarang keras melakukan perintah DDL/DML langsung ke database yang mengubah skema tanpa berkas migrasi tercatat.
3. **Zona Integritas Lisensi (Protected Area):**
   - 6 berkas keamanan lisensi diawasi secara kriptografis oleh `.security_hash` dan `.lic_hash`. Berkas ini tidak boleh disentuh tanpa otorisasi formal pemilik proyek.
