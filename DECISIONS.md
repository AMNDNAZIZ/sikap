# DECISIONS.md — Catatan Keputusan Teknis & Arsitektur (ADR Log)
**Rekam Jejak Keputusan Resmi Pemilik Proyek dan Tim Arsitektur ESPATA**

Dokumen ini menggunakan format *Architecture Decision Record (ADR)* untuk mendokumentasikan keputusan strategis yang telah disetujui, latar belakang masalahnya, dan konsekuensi teknisnya.

---

### ADR-001: Penetapan Arsitektur Modular Monolith CI4 Ramah Shared Hosting
- **Tanggal:** 2026-10-01 (Dikonfirmasi ulang 2026-11-15)
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** ESPATA harus dapat di-hosting dengan biaya terjangkau di layanan shared hosting berbasis cPanel/LiteSpeed oleh berbagai sekolah di Indonesia, tanpa memerlukan VPS khusus atau daemon background rumit.
- **Keputusan:** Menggunakan arsitektur Modular Monolith berbasis framework CodeIgniter 4.6.x (MVC + Service Layer). Komunikasi asinkron menggunakan Web API standar dan AJAX. Session dikelola via database/file dengan pelepasan kunci sesi (`session_write_close()`) sebelum pemrosesan lama.
- **Konsekuensi:** Pengembang dilarang memasang dependensi yang memerlukan antrean Redis mandiri, supervisor, atau websocket server persisten di luar kemampuan shared hosting standar.

---

### ADR-002: Pembentukan Model Kolaborasi Tim AI (EAO) & Prioritas Asis AI
- **Tanggal:** 2026-11-15
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** Pengembangan aplikasi melibatkan beberapa agen AI dengan spesialisasi berbeda. Dibutuhkan tata kelola resmi agar tidak terjadi tabrakan kode, halusinasi asumsi, atau kerusakan sistem.
- **Keputusan:**
  1. Menerapkan tata kelola **EAO (ESPATA AI Organization)** dengan pembagian 6 peran spesialisasi (`.agents/roles/`).
  2. Menetapkan **Asis AI ESPATA** sebagai **proyek pertama sekaligus proyek percontohan prioritas EAO**.
- **Konsekuensi:** Seluruh perancangan alur kerja, pengujian, dan tata kelola harus mengawal siklus hidup Asis AI secara terukur dan stabil.

---

### ADR-003: Pemisahan & Penghapusan Sub-Aplikasi `bayaran` dan `cbtku`
- **Tanggal:** 2026-11-15 (Diperbarui 2026-11-16)
- **Status:** Diterima & Dituntaskan (*Accepted & Completed*)
- **Konteks:** Folder `bayaran/` (aplikasi SPP/pembayaran) dan `cbtku/` (varian CBT mandiri) sebelumnya berada di dalam satu direktori fisik yang sama dengan aplikasi inti ESPATA.
- **Keputusan:**
  1. Pada 2026-11-15, kedua sub-aplikasi dibekukan (*frozen*).
  2. Pada 2026-11-16, berdasarkan mandat resmi tertulis Human Owner (Pemilihan Opsi C), sub-aplikasi `bayaran/` dan `cbtku/` **telah dihapus secara permanen** dari repositori fisik `siakad`.
- **Konsekuensi:** Repositori `siakad` kini 100% bersih dan ramping, terfokus eksklusif pada aplikasi inti ESPATA dan percontohan Asis AI. Modul `bayaran/` dan `cbtku/` tidak lagi menjadi bagian dari struktur direktori proyek.

---

### ADR-004: Penetapan Terminologi Resmi "Kokurikuler" pada Kurikulum
- **Tanggal:** 2026-11-06 (Dikonfirmasi ulang 2026-11-15)
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** Dalam implementasi Kurikulum Merdeka dan PPAI (Pendidikan Pancasila dan Agama Islam), istilah kegiatan pendukung kurikulum sering tertukar antara P5, Kokurikuler, atau Proyek.
- **Keputusan:** Menggunakan terminologi resmi **Kokurikuler** untuk seluruh antarmuka, rute (`/admin/kokurikuler`), nama tabel (`kokurikuler_*`), controller, dan modul generator AI perencana kegiatan kolaboratif lintas mata pelajaran. Istilah "P5" tidak dihidupkan kembali sebagai nama modul utama.
- **Konsekuensi:** Semua agen AI wajib konsisten menggunakan istilah "Kokurikuler".

---

### ADR-005: Standarisasi Multi-Tier Jenjang Pendidikan SD, SMP, SMA
- **Tanggal:** 2026-11-06
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** ESPATA melayani jenjang pendidikan berbeda dengan kebutuhan durasi jam pelajaran (JP), fase capaian kompetensi, dan beban belajar yang tidak seragam.
- **Keputusan:** Mengonsolidasikan seluruh konversi fase dan durasi JP ke dalam kelas terpusat `App\Services\JenjangService.php`:
  - **SD (Level 1):** Kelas 1-6, Fase A (1-2), Fase B (3-4), Fase C (5-6), Durasi: 35 menit/JP.
  - **SMP (Level 2):** Kelas 7-9, Fase D, Durasi: 40 menit/JP.
  - **SMA (Level 3):** Kelas 10-12, Fase E (10), Fase F (11-12), Durasi: 45 menit/JP.
- **Konsekuensi:** Seluruh modul KBM, modul ajar, jadwal, dan asesmen wajib memanggil `JenjangService` dan tidak boleh melakukan pengkodean kaku (*hardcoding*).

---

### ADR-006: Mesin Penilaian CBT V3 dengan Jejak Audit Kriptografis (Merkle Tree)
- **Tanggal:** 2026-11-01
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** Kredibilitas ujian online rentan diragukan jika terjadi manipulasi data jawaban siswa atau perselisihan penilaian esai.
- **Keputusan:** Mengimplementasikan mesin CBT V3 Assessment Intelligence dengan rantai tanda tangan digital (*Merkle Tree*) pada `cbt_audit_trail_v3`, penilaian esai berbasis rubrik terisolasi, Berita Acara Digital (BAP), dan verifikasi *Cutover Verification Gate*.
- **Konsekuensi:** Skema tabel CBT V3 dilindungi dengan *Zero-DML Guard* untuk mencegah perubahan nilai tanpa jejak audit.

---

### ADR-007: Optimalisasi Voice TTS Asis AI dengan Sentence Chunking & Sadar Libur
- **Tanggal:** 2026-11-14
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** Mesin Web Speech API bawaan peramban Chromium (Google Chrome / Edge di Windows) memiliki batasan *timeout freeze* 15 detik pada teks panjang dan memblokir suara otomatis tanpa gestur pengguna (*cold autoplay policy*). Selain itu, pada hari Sabtu/Minggu jadwal tatap muka kosong sehingga sapaan harus mencerminkan hari libur.
- **Keputusan:**
  1. Memecah naskah suara menjadi kalimat-kalimat alami (*sequential sentence queue*) agar tidak terkena timeout 15 detik.
  2. Menambahkan logika deteksi hari libur akhir pekan (Sabtu & Minggu) pada `AiCopilot.php`.
  3. Memasang penangkap gestur pengguna pertama (*first user gesture trigger*) dan tombol berdenyut (*pulse button*) pada kartu briefing untuk mengatasi pemblokiran autoplay peramban.
- **Konsekuensi:** Sambutan suara berjalan ramah, lancar, dan empatik di seluruh peramban modern.

---

### ADR-008: Proteksi Mutlak 6 Berkas Lisensi Anti-Tampering
- **Tanggal:** 2026-08-18 (Dikonfirmasi ulang 2026-11-15)
- **Status:** Diterima & Berlaku (*Accepted*)
- **Konteks:** Sistem keamanan lisensi diawasi secara otomatis oleh hash SHA-256 pada `.security_hash`.
- **Keputusan:** Menetapkan 6 berkas keamanan (`license_helper.php`, `LicenseGuard.php`, `SecurityGuard.php`, `SecureConfig.php`, `LicenseFilter.php`, `LicenseModel.php`) sebagai berkas yang tidak boleh diubah oleh agen AI manapun.
- **Konsekuensi:** Setiap perubahan pada sistem wajib melalui pemeriksaan `SecurityGuard::verifyCriticalFiles()` dengan hasil wajib `SEC_PASS`.

---

### ADR-009: Unified AI Service Layer, Groq/Gemini Multi-Provider, dan Memori Percakapan Ternormalisasi
- **Tanggal:** 2026-11-15 (Mandat EAO-002, EAO-002A & EAO-002B)
- **Status:** Diterima & Berlaku (*Accepted - Technical Consistency Validated*)
- **Konteks:** Pemanggilan AI di ESPATA sebelumnya terfragmentasi di berbagai controller (`AiCopilot.php`, `Kokurikuler.php`, `AdministrasiGuru.php`, `ElearningPackageGeneratorService.php`), percakapan bersifat stateless tanpa riwayat multi-turn, dan lingkungan shared hosting rentan terhadap DNS hang koneksi IPv6.
- **Keputusan Arsitektur Resmi:**
  1. **Unified AI Service Layer:** Menyetujui perancangan `App\Services\Ai\UnifiedAiService` sebagai pintu utama multi-provider berbasis *Adapter Pattern* (`GroqAdapter`, `GeminiAdapter`) yang terpisah dari logika controller. Konsolidasi dimulai bertahap dari `AiCopilot.php`.
  2. **Strategi Provider & Model:** Menetapkan **Groq Cloud API** sebagai kandidat penyedia utama dan **Google Gemini API** sebagai kandidat penyedia cadangan resmi. Nama model awal berstatus sebagai kandidat rekomendasi yang wajib diuji empiris dan dapat diubah secara dinamis via `app/Config/AiConfig.php`.
  3. **Kebijakan Auto-Failover:** Fitur perpindahan otomatis (*auto-failover*) ditetapkan **NONAKTIF MUTLAK (`enableAutoFailover = false`)** untuk mencegah request ganda dan pembengkakan biaya token; sistem menerapkan *graceful degradation* dengan pesan ramah pengguna.
  4. **Resiliensi Jaringan Shared Hosting:** Mewajibkan opsi `CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4` dan pelepasan kunci sesi (`session_write_close()`) sebelum eksekusi cURL AI.
  5. **Pemisahan Audit & Memori Percakapan:** Tabel `ai_assistant_logs` dipertahankan murni untuk log audit operasional (retensi 365 hari). Memori percakapan berlanjut dirancang menggunakan struktur ternormalisasi baru: `ai_conversations` (UUID header) dan `ai_messages` (retensi 30 hari, status pesan, idempotency, pemulihan stale pending > 60s, soft tombstone deletion guard, evaluasi ON DELETE CASCADE) dengan alokasi token presisi 3.000 input (termasuk 200 overhead) + 1.096 output (total hard cap 4.096 token copilot).
  6. **Keamanan & Tata Kelola Privasi:** Menerapkan kontrol akses sebelum kueri dieksekusi (*pre-query RBAC*), perlindungan data pribadi 4-lapis (*Zero PII* dan mitigasi quasi-identifier ditegaskan sebagai mitigasi risiko terukur bukan jaminan matematis absolut), isolasi sesi anti-IDOR, serta pembatasan BYOK guru khusus untuk Google Gemini sementara Groq eksklusif kunci sekolah di `.env`.
- **Konsekuensi & Batasan:** Seluruh dokumen desain telah diselaraskan tanpa pertentangan. **Implementasi kode dan eksekusi migrasi basis data DILARANG KERAS** sebelum Pemilik Proyek memberikan otorisasi tertulis eksplisit untuk memulai Fase 2.
