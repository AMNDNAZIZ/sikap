# RENCANA PENGUJIAN KOMPREHENSIF (TEST PLAN)
## VERIFIKASI UNIFIED AI MULTI-ACCOUNT CORE (FASE 1)

**Nomor Dokumen:** EAO-TEST-PLAN-2026-005A  
**Nomor Mandat:** EAO-MANDATE-2026-005  
**Tanggal:** 15 November 2026  
**Status:** DRAFT TEST PLAN — MENUNGGU PERSETUJUAN HUMAN OWNER  
**Penyusun:** AI QA Engineer & AI Security Reviewer EAO  
**Cakupan:** Unit Testing, Integration Testing, Security/Privacy Testing, Functional Regression  
**File Produksi:** DILARANG DIUBAH (Zero Production Modification)

---

## 1. STRATEGI & METODOLOGI PENGUJIAN

Pengujian Multi-Account Core pada Fase 1 dirancang untuk memvalidasi pemisahan hak kepemilikan kunci, akuntabilitas kuota token, dan ketahanan sistem terhadap galat jaringan atau manipulasi data.

### Prinsip Metodologi:
1. **Mock vs Live Separation:**
   - **Mock Testing (Isolated Unit & Integration):** Seluruh pengujian otomatis dijalankan menggunakan mock adapter (`setHttpExecutor()`) atau mock database untuk memverifikasi cabang logika tanpa menghabiskan kuota token nyata.
   - **Live Smoke Testing (Terbatas):** Pengujian live hanya dijalankan satu kali menggunakan prompt sintetis non-PII (`{"status":"connected"}`) setelah persetujuan Human Owner.
2. **Zero Secret Exposure:**
   - Skrip pengujian hanya menggunakan string dummy sintetis (misal: `gsk_mock_salma_personal_99999`, `gsk_mock_admin_school_12345`).
   - Tidak ada kunci asli yang disimpan di file pengujian.
3. **Multi-Role Matrix:** Pengujian mencakup peran Guru (Role 3), Admin (Role 1), dan Siswa/Non-Authorized (Role 5).

---

## 2. MATRIKS PENGUJIAN MANDATORI (12 SKENARIO UTAMA)

Berikut adalah 12 skenario pengujian utama yang diinstruksikan oleh Human Owner pada Mandat Bagian 5:

| ID Uji | Kategori | Skenario Pengujian | Kondisi Masukan & Lingkungan | Ekspektasi Hasil Terukur | Kriteria Penerimaan (*Acceptance Criteria*) |
|:---:|:---:|:---|:---|:---|:---|
| **TP-01** | Unit | **Guru memiliki Groq Key** | Sesi Guru Salma (ID 1), kolom `gemini_api_key` terisi kunci Groq pribadi. | Kunci yang di-resolve adalah kunci pribadi Guru 1. | `keySource === 'self'`, `keyOwnerId === 1`, kuota Admin tidak terpakai. |
| **TP-02** | Integrasi | **Guru tanpa kunci, Admin memiliki kunci** | Sesi Guru Baru (ID 99), kunci pribadi kosong. Akun Admin (ID 1) memiliki kunci aktif. | Kunci yang di-resolve adalah kunci Administrator. | `keySource === 'admin_fallback'`, `keyOwnerId === 1`, `isFallback === true`. |
| **TP-03** | Fungsional | **Guru & Admin sama-sama tanpa kunci** | Kunci Guru kosong, kunci Admin kosong, file `.env` kosong. | Sistem tidak crash (No HTTP 500), mengalihkan ke fallback lokal. | Copilot mengembalikan `source: 'local'`, Kokurikuler mengembalikan `curated_matrix`. |
| **TP-04** | Keamanan | **Guru lain memiliki kunci pribadi** | Guru B tanpa kunci memanggil AI saat Guru A memiliki kunci aktif di database. | Kunci Guru A **DILARANG KERAS** digunakan oleh Guru B. | `api_key` tidak memuat kunci Guru A, kuota Guru A tidak berkurang 1 token pun. |
| **TP-05** | Keamanan | **Manipulasi identitas (Anti-IDOR)** | Request mencoba memalsukan parameter `user_id` atau `teacher_id` di luar data sesi login. | Permintaan ditolak / dipaksa menggunakan sesi asli. | Resolver menolak parameter spoofing; hanya membaca data sesi terotentikasi. |
| **TP-06** | Unit | **API Key tidak valid / Corrupted** | Kunci memiliki format rusak (panjang < 10 karakter, atau karakter acak). | Resolver menandai `isValid === false` dengan error terklasifikasi. | DDTO mengembalikan `is_valid: false`, `validationError: 'INVALID_FORMAT'`. |
| **TP-07** | Keamanan | **Provider mengalami Rate Limit (HTTP 429)** | Server Groq mengembalikan HTTP 429 saat memproses kunci pribadi guru. | Sistem **TIDAK OTOMATIS** beralih ke kunci guru lain atau kunci Admin. | Respons menampilkan pesan ramah rate limit; tidak ada perpindahan kunci diam-diam. |
| **TP-08** | Ketahanan | **Provider Timeout (cURL Errno 28)** | Koneksi jaringan melampaui batas waktu (Copilot: 6 detik, Modul: 30 detik). | Timeout tertangkap tanpa exception fatal, degradasi anggun. | `errorCode === 'TIMEOUT'`, pengguna menerima fallback lokal tanpa HTTP 500. |
| **TP-09** | Telemetri | **Permintaan berhasil (Success Tracking)** | Permintaan AI sukses menghasilkan token respons. | Dual-traceability tercatat lengkap di `ai_assistant_logs`. | Log memuat `requester_id`, `key_owner_id`, `key_source`, `tokens`, `latency`. |
| **TP-10** | Keamanan | **Secret Logging Audit** | Terjadi error vendor HTTP 401/500 yang menyertakan string kunci pada pesan error. | String kunci asli disamarkan sebelum masuk log/exception. | Log hanya memuat `[API_KEY_MASKED]`, 0 kemunculan string secret mentah. |
| **TP-11** | Regresi | **Gemini BYOK Backward Compatibility** | Guru menggunakan kunci Google Gemini untuk penulisan modul ajar panjang. | Kunci Gemini pribadi guru tetap ter-resolve dan diproses normal. | `provider === 'gemini'`, `keySource === 'self'`, model Gemini merespons normal. |
| **TP-12** | Akuntabilitas | **Admin menggunakan kunci pribadi** | Sesi Administrator (Role 1) menjalankan kueri Asis AI. | Kunci ter-resolve sebagai kunci pribadi Admin (bukan fallback). | `keySource === 'self'`, `keyOwnerId === 1`, `isFallback === false`. |

---

## 3. PENGUJIAN REGRESI PER MODUL (REGRESSION SUITE)

Selain 12 skenario utama, pengujian regresi wajib memastikan modul yang sudah berjalan tidak mengalami degradasi fungsional:

### A. Regresi Asis AI Copilot (`AiCopilot.php`)
- **REG-COP-01:** Verifikasi bahwa respons JSON `AiCopilot::ask()` tetap mempertahankan field kontrak frontend:
  `status`, `source`, `query`, `explanation`, `matched_action`, `suggestions`, `ai_content`, `csrf_hash`.
- **REG-COP-02:** Pengujian whitelist RBAC: Inferensi AI yang mengembalikan `matched_id` di luar hak akses role pengguna wajib ditolak seketika dan dialihkan ke pencocokan lokal.
- **REG-COP-03:** Pengujian Morning Voice Briefing tetap menghasilkan teks sambutan yang ramah dan sadar akhir pekan.

### B. Regresi Modul Kokurikuler (`Kokurikuler.php`)
- **REG-KOK-01:** Verifikasi bahwa `recommendAI()` menghasilkan paket rekomendasi yang valid baik dalam mode AI maupun mode offline *Curated Matrix*.
- **REG-KOK-02:** Verifikasi bahwa `callAIAPI()` berhasil men-generate dokumen kokurikuler menggunakan kunci guru atau fallback Admin.
- **REG-KOK-03:** Verifikasi bahwa tidak ada query `$anyTeacher` yang hidup kembali.

### C. Regresi Lisensi & Sistem Terlindungi
- **REG-LIC-01:** Verifikasi integritas 6 berkas lisensi melalui `SecurityGuard::verifyCriticalFiles()` dengan hasil wajib **`SEC_PASS`**.
- **REG-FROZEN-01:** Verifikasi status git pada direktori `bayaran/` dan `cbtku/` tetap berstatus tidak berubah (*clean / frozen*).

---

## 4. METRIK & KRITERIA PENERIMAAN FASE 1 (ACCEPTANCE GATES)

Rangkaian pengujian Fase 1 dinyatakan lulus (*Sign-off*) jika memenuhi ambang batas kualitas berikut:
1. **Pass Rate:** **100% Lulus (0 Gagal)** pada seluruh 12 skenario mandatori dan rangkaian uji regresi.
2. **Linting Check:** `php -l` lulus 100% pada seluruh berkas yang disentuh.
3. **Zero Secret Leak:** Tidak ditemukan string kunci API asli pada response JSON, log audit, maupun exception trace.
4. **License Integrity:** `SecurityGuard` menghasilkan status **`SEC_PASS`**.

---

> ### 🛑 KONDISI BERHENTI (STOP CONDITION)
> Dokumen ini adalah rencana pengujian (*Test Plan*). Skrip pengujian aktual pada `scratch/` baru akan dieksekusi setelah implementasi Fase 1B disetujui oleh Human Owner.
