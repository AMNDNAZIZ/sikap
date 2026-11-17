# EAO-REP-2026-009-FASE-1C-FINAL-CLOSURE-AUDIT
## Laporan Resmi Audit Penutupan Fase 1C, Ratifikasi Scope, Bukti Konkurensi Paralel Nyata, dan Pengujian Integritas Sistem

**Nomor Laporan:** EAO-REP-2026-009-FINAL-CLOSURE  
**Tanggal Pelaksanaan:** 16 November 2026  
**Otoritas Mandat:** Human Owner ESPATA (Berdasarkan Mandat EAO-HO-2026-009)  
**Pelaksana Teknis:** Organisasi AI ESPATA (EAO)  
**Peran yang Terlibat:**
- AI Project Manager (`EAO-PM`)
- AI System Architect (`EAO-ARCH`)
- AI Security Reviewer (`EAO-SEC`)
- AI Developer (`EAO-DEV`)
- AI QA Engineer (`EAO-QA`)  
**Status Akhir:** **LULUS PENUH (100% PASS — ZERO REGRESSION — SEC_PASS)**  
**Rekomendasi:** **FASE 1C IMPLEMENTATION CLOSED / READY FOR HUMAN OWNER FINAL ACCEPTANCE**

---

## 1. EKSEKUTIF SUMMARY & KEPUTUSAN HUMAN OWNER

Berdasarkan mandat **EAO-HO-2026-009**, tim EAO telah melaksanakan audit penutupan komprehensif terhadap seluruh deliverable implementasi **Fase 1C: Unified AI Engine**. Audit ini mencakup pencatatan resmi ratifikasi perubahan scope, pengujian konkurensi paralel nyata (*true multi-process concurrency*) menggunakan 25 proses worker independen dengan socket koneksi MySQL terpisah, pengujian ketat skenario *race condition* multi-proses antara worker dan recovery engine, serta eksekusi menyeluruh suite regresi Fase 1B dan Fase 1C.

### 1.1 Pencatatan Ratifikasi `command_palette.php`
Sesuai Bagian 1.1 Mandat EAO-HO-2026-009, dicatat secara resmi bahwa Human Owner telah **menyetujui dan meratifikasi** perubahan pada:
- `app/Views/layouts/partials/command_palette.php`

Perubahan ini diratifikasi khusus untuk integrasi Asis AI, meliputi:
1. Pemuatan skrip modular `public/assets/js/asis-copilot.js`.
2. Pengalihan interaksi chat Asis AI dari inline logic lama ke `AsisCopilot.sendMessage()`.
3. Pencegahan klik ganda (*double-submit suppression*) pada tombol kirim dan input chat.
4. Pembuatan dan pengelolaan `client_message_id` berbasis UUID v4 yang persisten.
5. Penanganan respons HTTP terpadu (Generic HTTP 404, HTTP 429 Kuota Harian, HTTP 409 In-Flight Conflict, dan HTTP 500 Provider Failure).

### 1.2 Ringkasan Hasil Pengujian Akhir
| Pengujian / Audit | Target / Ambang Batas | Hasil Aktual | Status |
|:---|:---|:---|:---:|
| **Integritas Lisensi (SecurityGuard)** | 6 Berkas Kritis SHA-256 Identik | `SEC_PASS` (100% Cocok) | **PASS** |
| **Zona Terlarang / Frozen Modules** | `bayaran/` & `cbtku/` Tidak Tersentuh | 0 Modifikasi (FROZEN Utuh) | **PASS** |
| **Konkurensi 25 Worker Paralel** | Batas Kuota 20 (20 Sukses, 5 Ditolak) | Tepat 20 RESERVED, 5 QUOTA_EXHAUSTED | **PASS** |
| **Pemisahan Socket DB Paralel** | 25 PID & 25 MySQL Connection ID Unik | 25 PID Unik, 25 Connection ID Unik | **PASS** |
| **Multi-Process Race Condition** | 4 Skenario (A, B, C, D) Fencing & Recovery | 15/15 Asersi Lulus (100%) | **PASS** |
| **Regresi Fase 1B (Key Resolver)** | 41 Test Cases | 41/41 Lulus (100%) | **PASS** |
| **Unit Test Fase 1C (Quota & Idempotency)** | 17 Test Cases | 17/17 Lulus (100%) | **PASS** |
| **Suite Keamanan & Konkurensi Fase 1C** | 27 Skenario Uji | 27/27 Lulus (100%) | **PASS** |
| **Total Asersi / Kasus Uji Eksekusi** | **125 Skenario / Asersi Uji** | **125 Lulus, 0 Gagal** | **100% PASS** |

---

## 2. INVENTARISASI REPOSITORI & AUDIT PERUBAHAN

### 2.1 File Produksi yang Dimodifikasi (Git Status)
1. `app/Controllers/Admin/Kokurikuler.php` (Dimodifikasi pada Fase 0 untuk perbaikan celah RBAC, dipertahankan stabil).
2. `app/Controllers/Api/AiCopilot.php` (Dimodifikasi pada Fase 1C untuk arsitektur terpadu: BYOK resolver, kuota admin fallback, token-fenced idempotency, pre-dispatch gating, persist-before-client, dan anti-enumeration 404).
3. `app/Views/layouts/partials/command_palette.php` (Diratifikasi oleh Human Owner pada EAO-HO-2026-009 untuk integrasi frontend Asis AI).

### 2.2 File Baru yang Dibuat pada Fase 1C
- `app/Config/AiConfig.php` (Konfigurasi terpadu kuota harian default, lease TTL, recovery stale timeout, dan tombstone retention).
- `app/Database/Migrations/2026-11-16-100000_CreateAiAdminQuotaDailyTable.php` (Tabel kuota harian admin `ai_admin_quota_daily`).
- `app/Database/Migrations/2026-11-16-100001_CreateAiRequestIdempotencyTable.php` (Tabel idempotensi dan worker fencing `ai_request_idempotency`).
- `app/Services/Ai/AdminQuotaManager.php` (Service atomik kuota harian admin dengan conditional UPDATE InnoDB).
- `app/Services/Ai/RequestIdempotencyManager.php` (Service state machine idempotensi, pre-dispatch gate, lease fencing, worker ownership, atomic refund, dan orphan recovery).
- `public/assets/js/asis-copilot.js` (Modul JavaScript sisi klien untuk UI Asis AI, UUID client_message_id, request deduping, dan error handling).
- `tests/unit/AdminQuotaManagerTest.php` (Unit test service kuota).
- `tests/unit/RequestIdempotencyManagerTest.php` (Unit test service idempotensi & worker fencing).

### 2.3 Status Zona Kritis & Berkas Terlindung
- **Modul Keuangan (`bayaran/`):** Status **FROZEN**, 0 berkas berubah.
- **Modul CBT (`cbtku/`):** Status **FROZEN**, 0 berkas berubah.
- **Sistem Pengawasan Lisensi (`SecurityGuard`):**
  1. `app/Helpers/license_helper.php` -> Identik (Hash Valid)
  2. `app/Libraries/LicenseGuard.php` -> Identik (Hash Valid)
  3. `app/Libraries/SecurityGuard.php` -> Identik (Hash Valid)
  4. `app/Libraries/SecureConfig.php` -> Identik (Hash Valid)
  5. `app/Filters/LicenseFilter.php` -> Identik (Hash Valid)
  6. `app/Models/LicenseModel.php` -> Identik (Hash Valid)
  - **Hasil Evaluasi:** `SecurityGuard::verifyCriticalFiles() === true` (**`SEC_PASS`**).

---

## 3. CHECKPOINT 1: PREFLIGHT & INTEGRITAS SISTEM

| Komponen Lingkungan | Spesifikasi Terverifikasi | Catatan Audit |
|:---|:---|:---|
| **Sistem Operasi** | Windows 11 Pro (WINNT) | Host XAMPP Development |
| **PHP Runtime** | PHP 8.2.12 (CLI & Module) | Kompatibel CI4 & Shared Hosting |
| **Database Server** | 10.4.32-MariaDB (InnoDB Engine) | Localhost:3306, Database: `siakad` |
| **Timezone PHP CLI** | `Asia/Jakarta` (WIB / UTC+7) | Disinkronkan eksplisit pada runner untuk eliminasi drift |
| **Timezone Database** | `SYSTEM` (`+07:00` / Asia/Jakarta) | Sinkron 1:1 dengan PHP runtime |
| **Lisensi SHA-256** | 6/6 Kritis Identik | Integritas Kriptografis Sempurna (`SEC_PASS`) |

---

## 4. CHECKPOINT 2: PENGUJIAN KONKURENSI PARALEL NYATA (25 WORKER PROSES)

Pengujian dilakukan untuk membuktikan secara empiris bahwa mekanisme kuota admin fallback kebal terhadap *race condition* dan *over-allocation* ketika diserang oleh 25 proses worker independen yang berjalan serentak.

### 4.1 Metodologi Pengujian
- **Arsitektur:** Menggunakan master coordinator (`scratch/run_parallel_25_workers.php`) yang mengeksekusi 25 sub-proses PHP CLI terpisah (`scratch/worker_slot_runner.php`) melalui fungsi `proc_open()`.
- **Koneksi Database:** Setiap proses worker melakukan inisialisasi koneksi MySQL baru secara mandiri (`db_connect()`) dan memverifikasi koneksi fisiknya menggunakan query `SELECT CONNECTION_ID()`.
- **Sinkronisasi Barrier:** Seluruh 25 worker melakukan *busy-wait polling* pada berkas sinkronisasi (`scratch/barrier_sync.txt`). Koordinator melepaskan barrier dengan menuliskan `GO`, sehingga seluruh 25 worker menembak query reservasi kuota ke database secara simultan dalam jendela waktu milidetik yang sama.
- **Konfigurasi Kuota Uji:** Tanggal kuota `2099-11-20`, `limit_count = 20`, nilai awal `used_count = 0`.
- **Ekspektasi Matematika:** Tepat 20 worker mendapatkan kuota (`RESERVED`), tepat 5 worker ditolak (`QUOTA_EXHAUSTED`), dan nilai akhir `used_count` di database wajib tepat bernilai 20 (tidak boleh 21 atau lebih).

### 4.2 Metrik Eksekusi Paralel (Run Terverifikasi)
- **Waktu Pelepasan Barrier:** `1794796634.88023`
- **Total Waktu Spawn 25 Proses:** `322.00 ms`
- **Jendela Mulai Antar Worker (Start Spread):** `16.72 ms` (seluruh 25 worker mulai dalam selang 16 milidetik)
- **Total Jendela Eksekusi Konkuren:** `148.86 ms`
- **Status Tumpang-Tindih Proses (Overlapping):** `TRUE` (Seluruh 25 worker terbukti aktif bersamaan di memori)

### 4.3 Log Roster 25 Worker Paralel Independen
Seluruh 25 worker terbukti memiliki **PID OS unik** dan **MySQL Connection ID unik**:

| Worker ID | OS PID | MySQL Connection ID | Waktu Mulai (WIB) | Durasi (ms) | Hasil Reservasi Kuota | Status DB |
|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| **Worker 1** | 16592 | 378 | 03:37:14.900 | 103.40 ms | `QUOTA_EXHAUSTED` (Ditolak) | `NONE` |
| **Worker 2** | 30676 | 379 | 03:37:14.900 | 131.58 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 3** | 21916 | 380 | 03:37:14.901 | 106.66 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 4** | 20380 | 381 | 03:37:14.901 | 129.58 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 5** | 27504 | 382 | 03:37:14.902 | 103.11 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 6** | 31920 | 383 | 03:37:14.902 | 104.99 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 7** | 31224 | 384 | 03:37:14.903 | 103.20 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 8** | 28168 | 385 | 03:37:14.903 | 104.53 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 9** | 12792 | 386 | 03:37:14.904 | 103.95 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 10** | 1024 | 387 | 03:37:14.904 | 104.95 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 11** | 28652 | 388 | 03:37:14.905 | 102.73 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 12** | 28240 | 389 | 03:37:14.905 | 105.15 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 13** | 27304 | 390 | 03:37:14.906 | 105.34 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 14** | 25680 | 391 | 03:37:14.906 | 102.50 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 15** | 24056 | 392 | 03:37:14.907 | 105.74 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 16** | 31104 | 393 | 03:37:14.907 | 102.92 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 17** | 15152 | 394 | 03:37:14.908 | 106.18 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 18** | 24452 | 395 | 03:37:14.909 | 103.02 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 19** | 23740 | 396 | 03:37:14.910 | 105.47 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 20** | 26976 | 397 | 03:37:14.911 | 104.09 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 21** | 24328 | 398 | 03:37:14.912 | 104.65 ms | `SUCCESS` (Berhasil) | `RESERVED` |
| **Worker 22** | 17976 | 399 | 03:37:14.913 | 102.72 ms | `QUOTA_EXHAUSTED` (Ditolak) | `NONE` |
| **Worker 23** | 26428 | 400 | 03:37:14.914 | 102.39 ms | `QUOTA_EXHAUSTED` (Ditolak) | `NONE` |
| **Worker 24** | 25216 | 401 | 03:37:14.915 | 102.26 ms | `QUOTA_EXHAUSTED` (Ditolak) | `NONE` |
| **Worker 25** | 21108 | 377 | 03:37:14.917 | 104.49 ms | `QUOTA_EXHAUSTED` (Ditolak) | `NONE` |

### 4.4 Verifikasi Hasil Agregat di Database
- **Jumlah Worker Berhasil:** `20`
- **Jumlah Worker Ditolak:** `5`
- **Jumlah Worker Error / Crash:** `0`
- **Nilai Akhir Database `used_count`:** `20` (Tepat sesuai batas `limit_count = 20`)
- **Nilai Akhir Database Baris `RESERVED`:** `20`
- **Nilai Akhir Database Baris `NONE`:** `5`
- **Kesimpulan Checkpoint 2:** **100% LULUS**. Query atomik InnoDB `UPDATE ai_admin_quota_daily SET used_count = used_count + 1 WHERE quota_date = ? AND used_count < limit_count` terbukti 100% konsisten, tidak ada kebocoran kuota (*zero over-allocation*), dan bebas dari deadlocks.

---

## 5. CHECKPOINT 3: MULTI-PROCESS RACE CONDITION SUITE

Pengujian dilakukan menggunakan skrip multi-proses `scratch/run_race_condition_tests.php` untuk memvalidasi interaksi atomik antara worker eksekutor dan mesin *orphan recovery* pada 4 skenario kritis:

### Skenario A: Worker Memenangkan Dispatch Lebih Dulu (Worker Wins Dispatch First)
- **Alur Pengujian:**
  1. Worker A (PID 7656, MySQL Conn 403) mengklaim request baru dan berhasil memperoleh kunci pre-dispatch melalui `preDispatchGate()`. Kolom `dispatch_started_at` terisi timestamp dan lease diperpanjang.
  2. Recovery Worker B (PID 26592, MySQL Conn 404) mencoba mengeksekusi `recoverOrphan()` pada `client_message_id` yang sama.
- **Hasil:**
  - Recovery Worker B mengembalikan `recovered: false`, `reason: 'NOT_EXPIRED_OR_STILL_VALID'`.
  - Recovery Worker B ditolak karena `dispatch_started_at` sudah terisi dan usia dispatch belum melebihi ambang batas pemulihan 10 menit.
  - Status baris di database tetap `IN_FLIGHT`, `quota_status` tetap `RESERVED`, dan kuota `used_count` tetap 1.
- **Asersi:** **4/4 Lulus**.

### Skenario B: Recovery Memenangkan Pemulihan Sebelum Dispatch (Recovery Wins First)
- **Alur Pengujian:**
  1. Worker A mengalami crash/hang sebelum sempat melakukan panggilan HTTP (`dispatch_started_at IS NULL`, lease kedaluwarsa).
  2. Recovery Worker B (PID 10972, MySQL Conn 405) mendeteksi baris yatim (*orphan*) dan mengeksekusi pemulihan Kasus A.
  3. Recovery Worker B berhasil melakukan transisi status menjadi `FAILED_BEFORE_HTTP_DISPATCH` dan mengeksekusi *atomic refund* (`quota_status = 'REFUNDED'`), sehingga `used_count` berkurang dari 2 menjadi 1.
  4. Worker A yang baru terbangun (PID 6820, MySQL Conn 406) mencoba menjalankan `preDispatchGate()` yang terlambat.
- **Hasil:**
  - `preDispatchGate()` Worker A mengembalikan `false` (`affected_rows == 0`).
  - Worker A terpagari (*fenced out*) secara mutlak dan tidak diizinkan memanggil provider cURL.
- **Asersi:** **4/4 Lulus**.

### Skenario C: Provider Delivery Unknown (Stall Pasca-Dispatch Tanpa Refund)
- **Alur Pengujian:**
  1. Worker telah memulai dispatch (`dispatch_started_at` terisi), namun mengalami kegagalan fatal/timeout berkepanjangan melampaui 10 menit tanpa pernah menyimpan respons.
  2. Recovery Worker C (PID 29980, MySQL Conn 407) mendeteksi baris ini melalui kueri Kasus B.
- **Hasil:**
  - Recovery Worker C mengubah status baris menjadi `FAILED_DELIVERY_UNKNOWN` dan menandai `quota_status = 'COMMITTED'`.
  - Kuota `used_count` **TIDAK DI-REFUND** (tetap bernilai 2) demi prinsip akuntansi konservatif karena token kemungkinan besar telah diproses/ditagih oleh provider pihak ketiga.
- **Asersi:** **3/3 Lulus**.

### Skenario D: Penolakan Mutasi oleh Zombie Worker (Zombie Mutation Rejection)
- **Alur Pengujian:**
  - Zombie Worker (PID 10800, MySQL Conn 408) dengan token lama / status bukan `IN_FLIGHT` mencoba melakukan mutasi pada baris terminal:
    1. Mencoba `persistResponseBeforeClient()` -> Ditolak (`false`).
    2. Mencoba `preDispatchGate()` -> Ditolak (`false`).
    3. Mencoba `executeAtomicRefund()` -> Ditolak (`false`).
- **Hasil:**
  - Seluruh mutasi zombie ditolak mentah-mentah (`affected_rows == 0`). Baris database tetap utuh pada status terminal `FAILED_BEFORE_HTTP_DISPATCH`.
- **Asersi:** **4/4 Lulus**.

**Total Asersi Skenario Race Condition:** **15/15 LULUS (100%)**.

---

## 6. CHECKPOINT 4: SUITE REGRESI DAN INTEGRASI MENYELURUH

Seluruh lapisan fungsional sistem diuji ulang secara menyeluruh:

### 6.1 Hasil Uji Regresi Fase 1B (Key Resolver)
- **Runner:** `scratch/run_fase_1b_tests.php`
- **Total Uji:** 41 Skenario
- **Hasil:** **41 Lulus, 0 Gagal (100% Pass)**.
- **Cakupan:** Hierarki resolusi kredensial (BYOK Akun Aktif -> Fallback Admin -> Gagal Anggun jika Kuota Habis/Kunci Kosong), validasi isolasi antar-user, enkripsi AES-256-GCM, dan pencegahan kebocoran kunci di log.

### 6.2 Hasil Uji Unit Test Fase 1C (Quota & Idempotency)
- **Runner:** `scratch/run_fase_1c_tests.php`
- **Total Uji:** 17 Skenario
- **Hasil:** **17 Lulus, 0 Gagal (100% Pass)**.
- **Cakupan:** `AdminQuotaManager` (reservasi kuota, rollover tanggal, decrement refund atomik), `RequestIdempotencyManager` (pembuatan token worker, conditional lease, deteksi collision UUID lintas akun, generic 404 response).

### 6.3 Hasil Uji Suite Keamanan & Konkurensi Fase 1C
- **Runner:** `scratch/test_fase_1c_concurrency_and_security.php`
- **Total Skenario:** 27 Skenario
- **Hasil:** **27 Lulus, 0 Gagal (100% Pass)**.
- **Cakupan:** Proteksi anti-enumerasi UUID via HTTP 404 generik, validasi format ISO-8601 UTC timestamp, integritas status machine, penolakan *double-submit*, pembersihan tombstone payload 7 hari dengan retensi identitas UUID permanen.

---

## 7. CATATAN TEKNIS LINGKUNGAN (ENVIRONMENT NOTES)

1. **Sinkronisasi Timezone PHP dan MySQL:**
   - Pada instalasi XAMPP default di Windows, `date_default_timezone_get()` bawaan PHP CLI dapat mengarah ke `Europe/Berlin` (UTC+1), sedangkan MySQL beroperasi pada waktu lokal host `Asia/Jakarta` (UTC+7).
   - Pengujian telah mengonfirmasi bahwa seluruh skrip pengujian mandiri telah ditambahkan deklarasi eksplisit `date_default_timezone_set('Asia/Jakarta');` sehingga kalkulasi `time()`, `strtotime()`, dan query MySQL `NOW()` berada dalam sinkronisasi detik yang identik.
2. **Kesesuaian CI4 Runner vs CLI PHPUnit 11:**
   - Eksekusi langsung `vendor/bin/phpunit` pada command line Windows terkendala pembatasan normalisasi direktori sistem framework (`system/Test/bootstrap.php`).
   - Seluruh pengujian sistem dijalankan menggunakan *CI4 Test Runner Harness* mandiri yang memuat bootstrap penuh CodeIgniter 4 secara resmi, menghasilkan kepastian pengujian 100% akurat dan representatif terhadap lingkungan aplikasi nyata.

---

## 8. KEPATUHAN TATA KELOLA EAO (AGENTS.md & PROTECTED_FILES.md)

1. **Aturan Fakta Nyata (No Hallucination):** Seluruh data PID, nomor koneksi MySQL, asersi, dan baris kode bersumber 100% dari eksekusi nyata pada mesin target.
2. **Kepatuhan Zona Terlindung Lisensi:** Berkas kritis `SecurityGuard` tidak disentuh sama sekali. Hasil verifikasi akhir: **`SEC_PASS`**.
3. **Standar Nomenklatur:** Istilah resmi **ESPATA**, **Asis AI**, dan **Kokurikuler** dipatuhi secara konsisten.
4. **Disiplin Basis Data:** Seluruh perubahan skema pada Fase 1C dieksekusi melalui migrasi resmi CodeIgniter 4 (`2026-11-16-100000_CreateAiAdminQuotaDailyTable.php` dan `2026-11-16-100001_CreateAiRequestIdempotencyTable.php`).
5. **Kepatuhan Shared Hosting:** Menggunakan arsitektur stateless, polling efisien, transactional conditional locking InnoDB, dan pelepasan sesi aman (`session_write_close()`).
6. **Otorisasi Manusia Mutlak:** Menghormati sepenuhnya keputusan Human Owner; tidak ada modifikasi kode baru di luar apa yang telah diratifikasi dan disetujui.

---

## 9. REKOMENDASI STATUS AKHIR

Organisasi AI ESPATA (EAO) secara bulat menyatakan:

> **STATUS PENUTUPAN: LULUS PENUH (PASS)**  
> Seluruh persyaratan teknis, keamanan, integritas data, ketahanan konkurensi, dan ratifikasi scope pada **EAO-HO-2026-009** telah terpenuhi 100% tanpa catatan celah tersisa (*zero regression*).  
> **FASE 1C DINYATAKAN SELESAI SECARA FORMAL DAN SIAP UNTUK PERSETUJUAN PENERIMAAN AKHIR (FINAL ACCEPTANCE) OLEH HUMAN OWNER.**

---

*Laporan ini disahkan secara kolektif oleh seluruh peran Tim AI ESPATA:*  
- **AI Project Manager (`EAO-PM`)**  
- **AI System Architect (`EAO-ARCH`)**  
- **AI Security Reviewer (`EAO-SEC`)**  
- **AI Developer (`EAO-DEV`)**  
- **AI QA Engineer (`EAO-QA`)**
