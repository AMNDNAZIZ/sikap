# EAO-DESIGN-2026-006A-DUAL-TRACEABILITY-FINAL
## Spesifikasi Teknis Final: Akuntabilitas Ganda (Dual-Traceability) & Eliminasi Duplikasi Telemetri

**Nomor Dokumen:** EAO-DESIGN-2026-006A-DUAL-TRACEABILITY-FINAL  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, QA Engineer)  
**Keputusan Terkait:** Human Owner Directive EAO-HO-006A (Bagian B.5 & C)  
**Status Desain:** FINAL SPECIFICATION FOR IMPLEMENTATION APPROVAL  
**Batasan Khusus:** Kolom `matched_id` DILARANG digunakan untuk menyisipkan metadata token atau kuota.

---

## 1. Prinsip Utama Akuntabilitas Ganda (Dual-Traceability)

Dalam ekosistem sekolah multi-pengguna dengan model peminjaman kunci bersama (BYOK + Admin Fallback), sistem wajib mencatat pemisahan tanggung jawab secara tegas:

1. **Pemohon Transaksi (*Requester Identity*):**  
   Akun yang sedang login dan menekan tombol instruksi AI (contoh: Guru Matematika dengan User ID 12).
2. **Pemilik Sumber Daya (*Key Owner Identity*):**  
   Akun pemilik API Key yang kuota/biayanya digunakan untuk melayani permintaan tersebut (contoh: Administrator dengan User ID 1).
3. **Jenis Hubungan (*Key Source Relationship*):**  
   - `self`: Pengguna menggunakan kunci miliknya sendiri yang terdaftar di profil.
   - `admin_fallback`: Pengguna meminjam kuota Administrator karena belum memiliki kunci pribadi.
   - `override`: Kunci disuntikkan secara eksplisit melalui parameter khusus.
   - `none`: Tidak ada kunci yang valid; dialihkan ke sistem non-AI lokal.
4. **Kerahasiaan Mutlak Kunci Mentah (*Zero Secret Exposure*):**  
   String API Key mentah dilarang keras masuk ke dalam tabel database, file log teks, pesan exception, atau respons JSON frontend.

---

## 2. Pemetaan Telemetri pada Skema Basis Data Eksisting (`ai_assistant_logs`)

Mematuhi batasan bahwa pada Fase 1C belum ada perubahan skema database umum, telemetri akuntabilitas ganda dipetakan ke dalam kolom-kolom yang sudah tersedia pada tabel `ai_assistant_logs`:

| Kolom Database | Tipe Data | Peran dalam Dual-Traceability | Contoh Nilai Tersimpan |
|:---|:---|:---|:---|
| `user_id` | `INT(11)` | ID Akun Pemohon (*Requester ID*) | `12` (ID Guru Pemohon) |
| `role_id` | `INT(5)` | Role Pemohon (RBAC) | `3` (Guru) / `1` (Admin) |
| `query` | `VARCHAR(500)` | Teks instruksi (tersanitasi 4-lapis Zero PII) | `"Tolong jelaskan materi aljabar kelas 7"` |
| `intent` | `VARCHAR(100)` | Status eksekusi AI atau kode kegagalan | `'AI_COMPLETION'` / `'ERR_QUOTA_EXHAUSTED'` |
| `matched_id` | `VARCHAR(100)` | **Murni ID Aksi Navigasi Lokal** (*Bebas Token/Kuota*) | `'action_jadwal_full'` / `NULL` |
| `source` | `VARCHAR(50)` | **Tag Akuntabilitas Ganda Terstruktur** | `'groq:admin_fallback:1'` / `'groq:self:12'` |
| `ip_address` | `VARCHAR(45)` | Alamat IP pemohon | `'127.0.0.1'` |
| `created_at` | `DATETIME` | Waktu pencatatan transaksi | `'2026-11-16 08:30:15'` |

### Format Standar Tag Kolom `source`:
`{provider}:{key_source}:{key_owner_id}`

* Validasi Panjang String:  
  Panjang string terpanjang yang mungkin dihasilkan (`groq:admin_fallback:999999`) adalah **27 karakter**, jauh di bawah batas kolom `VARCHAR(50)`. Data lama (`'local'`, `'groq'`, `'gemini'`) tetap terbaca tanpa konflik (*100% backward compatible*).

---

## 3. Eliminasi Redundansi Logging (Single Source of Truth Logging)

### 3.1 Temuan Duplikasi Logging Saat Ini
Berdasarkan audit `EAO-REP-2026-006A-AICOPILOT-AUDIT.md`:
- Jalur 1: `UnifiedAiService::recordAuditTelemetry()` (baris 445) melakukan `INSERT` ke `ai_assistant_logs` dengan format dual-traceability (`source = groq:self:12`).
- Jalur 2: `AiCopilot::ask()` (baris 410) memanggil `$this->logInteraction()` yang melakukan `INSERT` kedua ke `ai_assistant_logs` dengan `source = 'groq'` (polos tanpa detail kepemilikan kunci).
- **Akibat:** Setiap 1 interaksi AI menghasilkan 2 baris log duplikat di database.

### 3.2 Desain Jalur Pencatatan Tunggal Fase 1C
Untuk mengeliminasi redundansi secara total:
1. **Untuk Interaksi AI (Jalur `callUnifiedAiNlu()`):**  
   Hapus pemanggilan `$this->logInteraction()` pada baris 410 `AiCopilot.php`. Seluruh pencatatan telemetri diserahkan 100% ke `UnifiedAiService::recordAuditTelemetry()`.
2. **Untuk Interaksi Non-AI (Direct Live Query, Feature Knowledge, Local Matcher):**  
   Pemanggilan `$this->logInteraction()` di `AiCopilot.php` tetap dipertahankan dengan tag spesifik:
   - Live Data: `source = 'database'`, `intent = 'JADWAL_GURU'`
   - Knowledge Base: `source = 'knowledge_base'`, `intent = 'FEATURE_GUIDE'`
   - Fallback Lokal: `source = 'local'`, `intent = 'LOCAL'`

Dengan aturan ini, **setiap satu interaksi pengguna dijamin menghasilkan tepat satu baris audit log**.

---

## 4. Status Persistensi Token & Usulan Migrasi Skema Masa Depan

### 4.1 Kebijakan Penggunaan Kolom `matched_id`
Sesuai instruksi mutlak Human Owner pada **EAO-HO-006A Bagian C**:
> *"Jangan menggunakan kolom `matched_id` untuk menyimpan metadata token atau kuota."*

Tim EAO secara tegas membatalkan opsi penyandian token ke dalam kolom `matched_id`. Kolom `matched_id` tetap dipertahankan murni sesuai fungsi aslinya sebagai penyimpan ID aksi antarmuka (`action_id`).

### 4.2 Usulan Skema Migrasi Resmi (Untuk Persetujuan Fase Terpisah)
Data konsumsi token (`prompt_tokens` dan `completion_tokens`) yang saat ini berhasil dihitung di memori (`AiResponse`) akan disimpan permanen setelah Human Owner menyetujui migrasi penambahan kolom khusus pada tabel `ai_assistant_logs`:

* **Rancangan Berkas Migrasi:** `app/Database/Migrations/2026-11-16-110000_AddTokenAndTelemetryColumnsToAiLogs.php`
* **Definisi Kolom Tambahan:**
  - `key_owner_id` (INT UNSIGNED, NULLABLE)
  - `key_source` (VARCHAR(20), DEFAULT 'none')
  - `model` (VARCHAR(50), NULLABLE)
  - `prompt_tokens` (INT UNSIGNED, DEFAULT 0)
  - `completion_tokens` (INT UNSIGNED, DEFAULT 0)
  - `latency_ms` (FLOAT, NULLABLE)
* **Status:** Belum dijalankan. Menunggu keputusan terpisah setelah Fase 1C.
