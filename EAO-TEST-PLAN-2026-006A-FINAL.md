# EAO-TEST-PLAN-2026-006A-FINAL
## Rencana Pengujian Final & Matriks Verifikasi Fase 1C: AiCopilot Alignment, Kuota Admin & Keamanan Konkurensi

**Nomor Dokumen:** EAO-TEST-PLAN-2026-006A-FINAL  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (QA Engineer, AI Security Reviewer, System Architect)  
**Keputusan Terkait:** Human Owner Directive EAO-HO-006A (Bagian B.6 & F)  
**Status Desain:** FINAL SPECIFICATION FOR IMPLEMENTATION APPROVAL  
**Batasan Kerja:** Desain rencana uji; eksekusi dijalankan setelah izin implementasi resmi diberikan.

---

## 1. Strategi & Cakupan Verifikasi Kualitas

Rencana pengujian Fase 1C difokuskan untuk membuktikan secara empiris bahwa:
1. Batas kuota 20 generasi/hari Administrator dihitung secara akurat dan tidak dapat ditembus oleh lonjakan transaksi bersamaan (*concurrency-safe*).
2. Guru yang memiliki API Key pribadi tidak pernah terhambat oleh kuota Administrator yang habis (*BYOK exemption*).
3. Guru tanpa kunci dialihkan secara anggun ke sistem lokal saat kuota Administrator mencapai batas (*graceful fallback*).
4. Tidak ada kebocoran kunci mentah dan tidak ada duplikasi pencatatan log audit.
5. Kontrak respons JSON frontend tidak mengalami perubahan atau kerusakan (*zero regression*).

---

## 2. Matriks Rinci Skenario Pengujian Fase 1C

### Domain 1: Pengujian Unit `AdminQuotaManager`

| ID Uji | Skenario | Data & Kondisi Awal | Ekspektasi Hasil | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---|:---|:---|
| **U-01** | Alokasi slot pertama hari baru | Tabel kosong untuk tanggal hari ini | Baris dibuat, `used_count = 1`, `allowed = true`. | PASS jika baris tanggal kalender terbuat dengan counter 1. |
| **U-02** | Alokasi slot berulang secara sekuensial | `used_count` berjalan 1 s.d. 19 | Setiap pemanggilan menambah counter tepat 1. | PASS jika counter bertambah sekuensial tanpa loncatan. |
| **U-03** | Pembacaan status tanpa mutasi | `getStatus()` saat `used_count = 14` | Mengembalikan `used_count = 14`, `remaining = 6`. Counter di DB tetap 14. | PASS jika tidak ada mutasi counter pada operasi read-only. |
| **U-04** | Fail-Closed saat koneksi DB terputus | Mock DB mengembalikan exception | `allowed = false`, `reason = 'DB_ERROR'`. Alokasi ditolak demi keamanan biaya. | PASS jika sistem menolak penggunaan kunci saat DB bermasalah. |

---

### Domain 2: Pengujian Konkurensi & Anti-Race Condition (Stress Testing)

| ID Uji | Skenario | Data & Kondisi Awal | Ekspektasi Hasil | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---|:---|:---|
| **C-01** | **25 Permintaan Serentak Saat Kuota Kosong (0/20)** | 25 proses/thread konkuren memanggil `acquireSlot()` secara bersamaan | Tepat **20 permintaan mendapatkan `allowed = true`**, dan tepat **5 permintaan mendapatkan `allowed = false`**. Counter akhir tepat bernilai 20. | **KRITIKAL:** Nilai counter akhir di database TIDAK BOLEH $> 20$ dan request yang disetujui TIDAK BOLEH $> 20$. |
| **C-02** | **10 Permintaan Serentak Saat Kuota Sisa 1 (19/20)** | 10 proses konkuren memanggil `acquireSlot()` saat `used_count = 19` | Tepat **1 permintaan disetujui** (slot ke-20), dan **9 permintaan ditolak**. Counter akhir tepat 20. | **KRITIKAL:** Hanya 1 transaksi yang berhasil melakukan update; tidak ada slot ganda yang lolos. |
| **C-03** | Permintaan beruntun saat kuota sudah habis (20/20) | 15 permintaan beruntun saat `used_count = 20` | Seluruh 15 permintaan langsung ditolak (`allowed = false`, `reason = 'QUOTA_EXHAUSTED'`). | PASS jika tidak ada pemanggilan cURL ke vendor AI. |

---

### Domain 3: Pengujian Ambang Batas Kuota (Boundary Testing 19, 20, dan 21)

| ID Uji | Skenario | Nilai Counter Awal | Hasil Permintaan | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---:|:---|:---|
| **B-01** | Ambang ke-19 | `used_count = 18` | Request diproses, provider cURL dijalankan, counter menjadi 19. Respons AI berlabel `groq:admin_fallback:1`. | PASS jika berhasil diproses AI. |
| **B-02** | Ambang batas pamungkas ke-20 | `used_count = 19` | Request diproses, provider cURL dijalankan, counter menjadi 20. Respons AI berlabel `groq:admin_fallback:1`. | PASS jika berhasil diproses AI dan counter = 20. |
| **B-03** | Ambang batas ke-21 (Melampaui Batas) | `used_count = 20` | **DITOLAK DARI JALUR AI**. Permintaan dialihkan ke Smart Local Matcher. Respons HTTP 200 berlabel `source: 'local'`, pesan kuota habis. | PASS jika zero crash, zero provider call, dan counter tetap 20. |

---

### Domain 4: Pengujian Lintas Tanggal & Zona Waktu (`Asia/Jakarta`)

| ID Uji | Skenario | Waktu Uji | Ekspektasi Hasil | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---|:---|:---|
| **T-01** | Evaluasi tanggal kalender WIB | Pukul 23:59:50 WIB | Tanggal kuota dievaluasi menggunakan tanggal hari ini WIB. | PASS jika string tanggal format `YYYY-MM-DD` sesuai Jakarta. |
| **T-02** | Reset otomatis pada pergantian hari 00:00:01 WIB | Pukul 00:00:05 WIB tanggal baru | Sistem otomatis membuat baris tanggal baru dengan `used_count = 1`. Kuota hari kemarin tetap tersimpan untuk audit. | PASS jika counter kembali ke 1 untuk tanggal kalender baru. |
| **T-03** | Server hosting berjalan di timezone UTC/GMT | Mock server timezone UTC saat WIB sudah ganti tanggal | Sistem secara eksplisit mengonversi ke `Asia/Jakarta`, mencegah pergeseran tanggal akibat timezone hosting. | PASS jika tanggal kuota tetap konsisten dengan WIB. |

---

### Domain 5: Pengujian Integrasi BYOK & Pemisahan Kuota

| ID Uji | Skenario | Konteks Pengguna | Ekspektasi Hasil | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---|:---|:---|
| **BYOK-01** | Guru A memiliki kunci pribadi Groq | Guru ID 2 (`gsk_guru_a...`) mengajukan kueri | Menggunakan kunci pribadi Guru A (`groq:self:2`). **Kuota Admin TIDAK BERKURANG**. | PASS jika counter Admin di tabel kuota tetap utuh. |
| **BYOK-02** | **Guru A mengajukan kueri saat kuota Admin = 20 (Habis)** | Guru ID 2 mengajukan kueri saat counter Admin sudah 20 | **Kueri Guru A TETAP BERJALAN LANCAR** via AI provider karena menggunakan kunci pribadi sendiri. | **KRITIKAL:** Kuota Admin yang habis tidak memblokir guru dengan kunci pribadi. |
| **BYOK-03** | Guru B tanpa kunci mengajukan kueri saat kuota Admin = 10 | Guru ID 3 (tanpa key) mengajukan kueri | Dialokasikan ke fallback Admin, counter Admin naik menjadi 11. | PASS jika fallback sukses dan tag tercatat `groq:admin_fallback:1`. |
| **BYOK-04** | Guru B tanpa kunci mengajukan kueri saat kuota Admin = 20 | Guru ID 3 mengajukan kueri saat kuota habis | Ditolak dari fallback Admin, dialihkan ke Smart Local Matcher (`source: 'local'`). | PASS jika beralih ke lokal tanpa error HTTP 500. |
| **BYOK-05** | **Zero Cross-Teacher Fallback Test** | Guru B mengajukan kueri saat Guru A memiliki kunci aktif | Guru B **TIDAK PERNAH** mengambil kunci Guru A. Hanya boleh Admin atau lokal. | **KRITIKAL:** String kunci Guru A tidak pernah dipakai Guru B. |

---

### Domain 6: Verifikasi Kontrak Frontend & Eliminasi Duplikasi Log

| ID Uji | Skenario | Pemeriksaan | Ekspektasi Hasil | Kriteria Lolos (Acceptance Criteria) |
|:---|:---|:---|:---|:---|
| **FE-01** | Kontrak JSON Respons AI | Format JSON respons `/api/ai-copilot/ask` | Memuat `status`, `source`, `query`, `explanation`, `matched_action`, `csrf_hash`. | PASS jika struktur identik dengan kebutuhan widget JS. |
| **FE-02** | Kontrak JSON Respons Kuota Habis | Format JSON saat kuota habis | Memuat `status = 'success'`, `source = 'local'`, pesan kuota habis, navigasi lokal, `csrf_hash`. | PASS jika widget merender navigasi lokal secara anggun. |
| **FE-03** | **Verifikasi Single Audit Logging** | Periksa tabel `ai_assistant_logs` setelah 1 pemanggilan AI | Tepat **1 baris** yang tercatat (oleh `UnifiedAiService`). Tidak ada baris duplikat dari controller. | **KRITIKAL:** Nol duplikasi log per interaksi AI. |
| **FE-04** | Preservasi Token CSRF | Panggilan AJAX beruntun | Setiap respons menyertakan `csrf_hash` terbaru yang valid. | PASS jika tidak ada galat 403 CSRF mismatch. |

---

## 3. Metodologi Eksekusi Uji Mandiri

Seluruh 25 skenario di atas akan dirangkum dalam berkas pengujian terotomasi `tests/unit/AiCopilotAlignmentTest.php` dan skrip runner mandiri `scratch/run_fase_1c_tests.php` yang dapat dieksekusi secara independen di lingkungan CLI Windows.
