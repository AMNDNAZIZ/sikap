# EAO-IMPLEMENTATION-MANDATE-2026-006F
## Rancangan Mandat Implementasi Final Fase 1C: AI Copilot Alignment, Dual-Traceability, Dedicated Admin Quota, dan Persistent Request Idempotency

**Nomor Dokumen:** EAO-MANDATE-2026-006F  
**Tanggal Pengajuan:** 16 November 2026  
**Status Pengajuan:** DRAF USULAN FINAL — MENUNGGU PERSETUJUAN TERTULIS HUMAN OWNER (*PENDING APPROVAL*)  
**Tahap Proyek:** Fase 1C — Implementasi Inti AI Copilot & Penegakan Batas Kuota Terpadu  
**Pelaksana Teknis:** Tim Multi-Agen EAO (Project Manager, System Architect, Developer, QA Engineer, Security Reviewer)  
**Tingkat Risiko:** Sedang — Terkendali Penuh (*Strictly Controlled*)

---

## 1. Latar Belakang & Ruang Lingkup Penyempurnaan 006F

Menyusul arahan pada mandat evaluasi konsistensi **EAO-HO-006F**, mandat implementasi Fase 1C ini memutakhirkan seluruh spesifikasi teknis ke standar tertinggi:
1. **Konsistensi Transaksi Kuota & Idempotensi:** Menghubungkan secara eksplisit tabel `ai_admin_quota_daily` dengan `ai_request_idempotency` melalui status transaksional (`quota_status`).
2. **Eliminasi Risiko Replay Amnesia:** Menerapkan pemisahan 3 siklus retensi (pembersihan payload respons setelah 7 hari tanpa menghapus baris identitas selama 90 hari).
3. **Penyempurnaan Semantik HTTP Dispatch:** Menegakkan batas *Strict No-Refund* tepat pada status `HTTP_DISPATCH_STARTED` dengan pembedaan waktu socket melalui `dispatch_started_at`.
4. **Isolasi Mutlak Privasi Pengguna:** Menjamin pencarian safe replay terikat ketat pada `user_id` pemilik sah dan membatasi ukuran penyimpanan maksimal 64 KB.

---

## 2. Batasan Wewenang Mutlak (The 6 Golden Rules)

Seluruh proses implementasi terikat oleh batasan baku:
1. **Dilarang Menyentuh 6 Berkas Lisensi Terlindung:**  
   `license_helper.php`, `LicenseGuard.php`, `SecurityGuard.php`, `SecureConfig.php`, `LicenseFilter.php`, `LicenseModel.php`, `.security_hash`, dan `.lic_hash`. Status wajib `SEC_PASS`.
2. **Dilarang Mengubah Modul Frozen:**  
   Direktori `bayaran/` dan `cbtku/` tetap berstatus Frozen tanpa perubahan apa pun.
3. **Disiplin Basis Data:**  
   Perubahan skema hanya melalui migrasi resmi CodeIgniter 4 di `app/Database/Migrations/`.
4. **Kepatuhan Shared Hosting:**  
   Wajib memanggil `session_write_close()` sebelum proses AI dan tidak boleh menggunakan daemon persisten.

---

## 3. Daftar Berkas yang Akan Dikerjakan (*File Scope*)

### A. Berkas Migrasi Baru (Database Layer):
1. `app/Database/Migrations/2026-11-16-100000_CreateAiAdminQuotaDailyTable.php`  
   *Tabel pencatat kuota harian Admin (batas keras 20 per hari Asia/Jakarta, engine InnoDB, unique key `quota_date`).*
2. `app/Database/Migrations/2026-11-16-100001_CreateAiRequestIdempotencyTable.php`  
   *Tabel pelacak idempotensi persisten, safe replay, dan in-flight concurrency lock (engine InnoDB, unique key `client_message_id`, foreign key/indeks `user_id`, kolom `quota_status`, `key_source`, `dispatch_started_at`).*

### B. Berkas Service Baru & Diperbarui (Service Layer):
1. `app/Services/Ai/AdminQuotaManager.php` (Baru)  
   *Pengelola reservasi atomik kuota harian, rollback sebelum retry pada deadlock 1213, jitter backoff 15–45ms, dan kebijakan fail-closed.*
2. `app/Services/Ai/RequestIdempotencyManager.php` (Baru)  
   *Pengelola status request idempotensi (`IN_FLIGHT`, `COMPLETED`, `TIMEOUT`), safe replay respons, pemisahan retensi payload, dan pemulihan orphan process.*
3. `app/Services/Ai/UnifiedAiService.php` (Diperbarui)  
   *Integrasi presisi: Zero PII $\rightarrow$ Resolusi Kunci $\rightarrow$ Idempotency Claim $\rightarrow$ Quota Reservation $\rightarrow$ HTTP Dispatch Demarcation $\rightarrow$ Response Storage $\rightarrow$ Telemetri.*

### C. Berkas Controller & Frontend (Interface Layer):
1. `app/Controllers/Api/AiCopilot.php` (Diperbarui)  
   *Pelepasan session lock awal (`session_write_close()`), validasi header/body `client_message_id`, integrasi pipeline UnifiedAiService, dan penanganan safe replay.*
2. Antarmuka Chat Frontend (Diperbarui)  
   *Pembangkitan UUID v4 `client_message_id` tunggal per klik, penonaktifan tombol submit saat in-flight, dan pelestarian UUID pada saat retry.*

### D. Berkas Pengujian (Test Suite):
1. `tests/unit/AdminQuotaManagerTest.php` (Baru)
2. `tests/unit/RequestIdempotencyManagerTest.php` (Baru)

---

## 4. Alur Kerja Implementasi Sekuensial (*Step-by-Step Execution Plan*)

```mermaid
flowchart TD
    S1["1. Pembuatan & Eksekusi Migrasi CI4\n(ai_admin_quota_daily & ai_request_idempotency)"] --> S2["2. Implementasi Service Layer\n(AdminQuotaManager & RequestIdempotencyManager)"]
    S2 --> S3["3. Penyelarasan Pipeline UnifiedAiService\n(Idempotency Gate, Pre-Dispatch Refund, No-Refund Boundary)"]
    S3 --> S4["4. Refactoring AiCopilot.php & Frontend Script\n(session_write_close, client_message_id UUID v4, Rapid Lock)"]
    S4 --> S5["5. Rangkaian Pengujian Menyeluruh & Verifikasi\n(Unit Tests, Double-Click Simulation, License SEC_PASS)"]
    S5 --> S6["6. Pelaporan Hasil Akhir & Berita Acara kepada Human Owner"]
```

---

## 5. Kriteria Penerimaan Mutlak (*Acceptance Criteria*)

1. [ ] **AC-01:** Dua migrasi basis data dieksekusi sukses dengan engine InnoDB dan indeks unik terpasang sempurna.
2. [ ] **AC-02:** Kuota fallback Admin terkunci tepat pada batas maksimal 20 generasi berhasil per hari kalender Asia/Jakarta.
3. [ ] **AC-03:** Guru dengan API Key pribadi (`self`) bypass kuota Admin dan tidak pernah terhambat oleh kuota Admin yang habis.
4. [ ] **AC-04:** Penolakan sebelum gerbang HTTP (RBAC, format salah, Zero PII leak) terbukti tidak memotong kuota sama sekali.
5. [ ] **AC-05:** Kegagalan lokal sebelum pengiriman HTTP (`FAILED_BEFORE_HTTP_DISPATCH`) berhasil mengembalikan 1 slot kuota (atomic refund).
6. [ ] **AC-06:** Permintaan yang telah mencapai status `HTTP_DISPATCH_STARTED` (termasuk timeout dan 500 error vendor) terbukti **TETAP MENGURANGI KUOTA (STRICT NO-REFUND)**.
7. [ ] **AC-07:** Penekanan tombol ganda (*double-click*) dengan UUID yang sama terbukti hanya memproses 1 request dan memotong tepat 1 slot kuota.
8. [ ] **AC-08:** Pengiriman ulang request yang telah selesai mengembalikan respons tersimpan (*safe replay*) secara instan tanpa memotong kuota baru.
9. [ ] **AC-09:** Permintaan dengan UUID yang sama tetapi `user_id` berbeda ditolak dengan status `HTTP 403 Forbidden`.
10. [ ] **AC-10:** Permintaan dengan UUID yang sama tetapi payload hash berbeda ditolak dengan status `HTTP 409 Conflict`.
11. [ ] **AC-11:** Seluruh berkas terlindung lisensi tetap utuh dengan verifikasi `SecurityGuard::verifyCriticalFiles()` bernilai `SEC_PASS` (1).
12. [ ] **AC-12:** Modul `bayaran/` dan `cbtku/` tetap berstatus Frozen tanpa modifikasi berkas.

---

## 6. Lembar Pengesahan Persetujuan Human Owner

Dokumen ini diserahkan kepada Pemilik Proyek (Human Owner) untuk memperoleh persetujuan tertulis sebelum pekerjaan implementasi Fase 1C dimulai.

```markdown
STATUS KEPUTUSAN HUMAN OWNER:

[   ] DISETUJUI UNTUK DIIMPLEMENTASIKAN (Tim EAO diizinkan memulai eksekusi Fase 1C sesuai EAO-MANDATE-2026-006F)
[   ] DISETUJUI DENGAN CATATAN (Tuliskan catatan perbaikan di bawah)
[   ] DITOLAK / DITUNDA (Tuliskan arahan perbaikan di bawah)

Catatan / Arahan:
___________________________________________________________________________________
___________________________________________________________________________________

Tanda Tangan Human Owner: ______________________
Tanggal: ______________________
```
