# EAO-TEST-PLAN-2026-006A
## Rencana Pengujian Komprehensif Fase 1C: AiCopilot Alignment, Admin Quota & Concurrency Safety

**Nomor Dokumen:** EAO-TEST-PLAN-2026-006A  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (QA Engineer, System Architect, AI Security Reviewer)  
**Mandat Terkait:** EAO-MANDATE-2026-006 (Bagian 3.C, 3.E, dan 5)  
**Status Dokumen:** PROPOSAL RENCANA UJI UNTUK PERSETUJUAN HUMAN OWNER  
**Batas Pengujian:** Desain skenario uji; eksekusi dilakukan setelah izin implementasi diberikan.

---

## 1. Strategi & Ruang Lingkup Pengujian

Rencana pengujian Fase 1C dirancang untuk memverifikasi secara ketat penyelarasan `AiCopilot`, keandalan manajemen kuota Administrator (maksimal 20 generasi/hari), keamanan konkurensi (*anti-race condition*), serta kepatuhan penuh terhadap kontrak frontend tanpa regresi.

Pengujian terbagi ke dalam 6 domain utama:
1. **Domain 1: Unit Test AdminQuotaManager** (Operasi atomik, reset harian, fail-closed).
2. **Domain 2: Concurrency & Stress Test** (Simulasi 20+ permintaan bersamaan).
3. **Domain 3: Boundary Testing Kuota (Batas 19, 20, dan 21)**.
4. **Domain 4: Timezone & Cross-Date Testing** (`Asia/Jakarta`).
5. **Domain 5: Integrasi AiCopilot & BYOK Routing** (Guru ber-key vs tanpa key).
6. **Domain 6: Verifikasi Kontrak Frontend & Zero Regression**.

---

## 2. Matriks Detail Skenario Pengujian

### Domain 1: Pengujian Unit `AdminQuotaManager`

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **QUOTA-01** | Alokasi slot pertama pada hari baru | Kuota awal 0, request masuk | Slot disetujui (`allowed = true`), `used_count = 1`. | PASS jika counter = 1 dan baris tanggal dibuat. |
| **QUOTA-02** | Alokasi bertahap hingga mendekati batas | Permintaan berulang dari beberapa pengguna berbeda | `used_count` bertambah sekuensial (1, 2, 3... 19). | PASS jika seluruh slot disetujui. |
| **QUOTA-03** | Pembacaan status tanpa menambah hitungan | Panggilan `getStatus()` saat kuota = 10 | Mengembalikan `used_count = 10`, `remaining = 10`, counter tetap 10. | PASS jika counter tidak bertambah. |
| **QUOTA-04** | Perilaku Fail-Closed saat koneksi DB error | Matikan koneksi DB mock | `allowed = false`, `reason = 'DB_ERROR'`, menolak alokasi demi keamanan biaya. | PASS jika tidak ada slot yang bocor. |

---

### Domain 2: Pengujian Konkurensi & Anti-Race Condition (Stress Testing)

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **CONC-01** | **Simulasi 25 Permintaan Bersamaan Saat Kuota Kosong** | 25 proses/thread konkuren memanggil `acquireSlot()` serentak pada `used_count = 0` | Tepat **20 permintaan disetujui** (`allowed = true`), dan tepat **5 permintaan ditolak** (`allowed = false`). Counter akhir tepat bernilai 20. | **KRITIKAL:** Nilai counter akhir tidak boleh $> 20$ dan request lolos tidak boleh $> 20$. |
| **CONC-02** | **Simulasi Lonjakan 10 Permintaan Bersamaan Saat Kuota = 19** | 10 proses serentak saat sisa kuota = 1 | Tepat **1 permintaan disetujui** (slot ke-20), dan **9 permintaan ditolak**. Counter akhir = 20. | **KRITIKAL:** Tidak ada slot ganda yang lolos akibat race condition. |
| **CONC-03** | Permintaan beruntun saat kuota sudah 20 | 10 permintaan masuk saat `used_count = 20` | Seluruh 10 permintaan langsung ditolak (`allowed = false`, `reason = 'QUOTA_EXHAUSTED'`). | PASS jika tidak ada pemanggilan cURL ke provider. |

---

### Domain 3: Boundary Testing Kuota (Batas 19, 20, dan 21)

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **BOUND-01** | Permintaan pada ambang batas ke-19 | `used_count = 18` | Request diproses, provider dipanggil, counter menjadi 19. Respons AI berlabel `groq:admin_fallback:1`. | PASS jika berhasil diproses AI. |
| **BOUND-02** | Permintaan pada batas pamungkas ke-20 | `used_count = 19` | Request diproses, provider dipanggil, counter menjadi 20. Respons AI berlabel `groq:admin_fallback:1`. | PASS jika berhasil diproses AI. |
| **BOUND-03** | Permintaan yang melampaui batas ke-21 | `used_count = 20` | **DITOLAK DARI JALUR AI**. Permintaan dialihkan ke Smart Local Matcher. Respons HTTP 200 berlabel `source: 'local'`, menyertakan pesan kuota sekolah habis. | PASS jika zero crash dan zero cURL call. |

---

### Domain 4: Pengujian Lintas Tanggal & Zona Waktu (`Asia/Jakarta`)

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **TIME-01** | Evaluasi tanggal kalender WIB | Jam server 23:59:50 WIB | Menggunakan tanggal hari ini. | PASS jika format tanggal sesuai kalender Jakarta. |
| **TIME-02** | Reset otomatis pada pergantian hari 00:00:01 WIB | Request pertama masuk pada pukul 00:00:05 WIB tanggal baru | Sistem otomatis membuat baris tanggal baru dengan `used_count = 1`. Kuota hari kemarin tetap tersimpan untuk riwayat audit. | PASS jika counter kembali ke 1 untuk hari baru. |
| **TIME-03** | Server hosting memiliki perbedaan zona waktu (UTC/GMT) | Mock PHP timezone UTC saat waktu Jakarta sudah tanggal baru | Sistem secara eksplisit mengonversi ke `Asia/Jakarta` sebelum memformat tanggal kuota. | PASS jika tidak terjadi pergeseran tanggal akibat zona waktu server. |

---

### Domain 5: Pengujian Integrasi `AiCopilot` & BYOK Routing

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **BYOK-01** | Guru A memiliki kunci pribadi Groq | Guru ID 2 (`gsk_guru_a_key...`) mengajukan kueri | Menggunakan kunci Guru A (`source: groq:self:2`). **Kuota Admin TIDAK berkurang**. | PASS jika kuota Admin tetap utuh. |
| **BYOK-02** | Guru A mengajukan kueri saat kuota Admin = 20 (habis) | Guru ID 2 mengajukan kueri | **Kueri Guru A TETAP BERJALAN LANCAR** karena menggunakan kuota pribadinya sendiri. | **KRITIKAL:** Kuota Admin yang habis tidak memblokir guru yang memiliki kunci pribadi. |
| **BYOK-03** | Guru B tanpa kunci mengajukan kueri saat kuota Admin = 10 | Guru ID 3 (tanpa key) mengajukan kueri | Dialokasikan ke fallback Admin, counter Admin menjadi 11. | PASS jika fallback sukses. |
| **BYOK-04** | Guru B tanpa kunci mengajukan kueri saat kuota Admin = 20 | Guru ID 3 mengajukan kueri | Ditolak dari fallback Admin, dialihkan ke Smart Local Matcher (`source: 'local'`). | PASS jika beralih ke lokal tanpa error. |
| **BYOK-05** | **Zero Cross-Teacher Fallback Test** | Guru B mengajukan kueri saat Guru A memiliki kuota | Guru B **TIDAK PERNAH** mengambil kunci Guru A, melainkan hanya Administrator atau lokal. | **KRITIKAL:** Nilai kunci Guru A tidak pernah keluar. |

---

### Domain 6: Verifikasi Kontrak Frontend & Zero Regression

| Kode Uji | Skenario | Data Uji | Ekspektasi Hasil | Kriteria Lolos |
|:---|:---|:---|:---|:---|
| **FE-01** | Kontrak JSON pada respons AI Groq | Permintaan berhasil via AI | Format memuat `status`, `source: 'groq'`, `explanation`, `matched_action`, `csrf_hash`. | PASS jika validasi JSON schema sesuai frontend. |
| **FE-02** | Kontrak JSON pada respons Fallback Lokal | Permintaan saat kuota habis / offline | Format memuat `status`, `source: 'local'`, `explanation`, `matched_action`, `csrf_hash`. | PASS jika antarmuka frontend merender navigasi dengan benar. |
| **FE-03** | Header Morning Briefing Guru | Endpoint `/api/ai-copilot/morning-briefing` | Data jadwal mengajar dan spoken text tetap tersaji utuh. | PASS jika briefing guru tidak terganggu. |
| **FE-04** | Preservasi Token CSRF | Seluruh jenis respons (AI, lokal, error) | `csrf_hash` selalu ada dan valid untuk request lanjutan. | PASS jika tidak ada error 403 CSRF mismatch. |

---

## 3. Metodologi Eksekusi Pengujian Mandiri

Karena eksekusi PHPUnit langsung terblokir di lingkungan Windows (`is_readable()` issue), pengujian konkurensi dan unit akan dijalankan menggunakan runner terisolasi berbasis simulasi proses paralel multi-fiber / multi-curl worker pada skrip uji mandiri `tests/unit/AiCopilotAlignmentTest.php`.
