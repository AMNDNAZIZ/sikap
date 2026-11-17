# ASIS AI CONVERSATION DESIGN
**Rancangan Sistem Memori Percakapan Berkelanjutan (Multi-Turn Chat Architecture)**
*Dokumen Desain Mandat EAO-002B — Disempurnakan oleh AI System Architect & AI Security Reviewer*

---

| Parameter | Keterangan Desain Teknis |
|:---|:---|
| **Fitur Target** | Multi-Turn Contextual Conversation (Percakapan Bersambung Sesuai Peran) |
| **Model Data** | Relasi Dua Tingkat Ternormalisasi (`ai_conversations` 1 $\leftrightarrow$ N `ai_messages`) |
| **Kunci Utama & Relasi** | UUIDv4 Primary Key, Foreign Key Terindeks dengan `ON DELETE CASCADE` |
| **Pemisahan Audit** | Audit Operasional (`ai_assistant_logs`) Terpisah Mutlak dari State Percakapan |
| **Status Pesan** | Siklus Status: `pending` $\rightarrow$ `completed` / `failed` / `timeout` / `aborted` |
| **Idempotency Guard** | Pencegahan Duplikasi Permintaan via `client_message_id` |
| **Pemulihan Zombie Pending** | Transisi Otomatis Pesan `pending` Kedaluwarsa (> 60 Detik) $\rightarrow$ `timeout` |
| **Alokasi Anggaran Token** | 3.000 Token Input (Termasuk 200 Token Overhead) + 1.096 / 2.048 Token Output |
| **Status Dokumen** | **Rancangan Teknis Final Tervalidasi (Menunggu Otorisasi Pemilik Proyek)** |

---

## 1. Analisis Kebutuhan & Evaluasi Model Penyimpanan

### A. Kondisi Saat Ini di Repositori ESPATA
Interaksi Asis AI saat ini bersifat **Stateless (Single-Turn)**. Setiap kali pengguna mengirimkan pertanyaan di [command_palette.php](file:///c:/xampp/htdocs/siakad/app/Views/layouts/partials/command_palette.php), sistem hanya menerima teks pertanyaan saat itu dan mencatatnya ke tabel `ai_assistant_logs`:

```sql
-- Struktur aktual tabel ai_assistant_logs (Migration: 2026-11-13-180000)
CREATE TABLE `ai_assistant_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NULL,
  `role_id` INT NULL,
  `query` VARCHAR(255) NOT NULL,
  `intent` VARCHAR(100) NULL,
  `matched_id` INT NULL,
  `source` VARCHAR(50) NOT NULL,
  `ip_address` VARCHAR(45) NULL,
  `created_at` DATETIME NULL
);
```

### B. Evaluasi Arsitektur: Memperluas `ai_assistant_logs` vs Membuat Tabel Khusus

Berdasarkan analisis arsitektural mendalam, opsi memperluas `ai_assistant_logs` ditolak dan opsi tabel khusus disetujui:

| Dimensi Evaluasi | Opsi A: Memperluas Tabel `ai_assistant_logs` | Opsi B: Membuat Tabel Khusus (`ai_conversations` & `ai_messages`) |
|:---|:---|:---|
| **Tujuan & Semantik Data** | **Tercampur aduk**. Log forensik/keamanan tercampur dengan teks dialog yang bersifat privat dan temporer. | **Pemisahan Tanggung Jawab (Separation of Concerns)**. Log audit sistem terpisah bersih dari konten obrolan pengguna. |
| **Beban & Performa Basis Data** | Tabel log audit membesar sangat cepat. Kueri riwayat percakapan akan memindai baris log yang tidak relevan. | **Kueri Terarah & Cepat**. Pembacaan riwayat hanya memindai tabel `ai_messages` berdasarkan foreign key terindeks. |
| **Siklus Hidup & Retensi Data** | Menghapus obrolan lama berisiko merusak kepatuhan audit keamanan sekolah. | **Retensi Mandiri & Selaras Regulasi**. Obrolan dibersihkan otomatis setelah 30 hari tanpa merusak log audit keamanan (365 hari). |
| **Integritas Relasi Data** | Melanggar bentuk normal relasional (1 baris query tunggal dipaksa menyimpan konteks multi-turn). | **Ternormalisasi Penuh (3NF)**. Hubungan 1 sesi ke N pesan dengan integritas referensial dan cascading delete. |
| **Keputusan Arsitektur** | ❌ **DITOLAK** | 🏆 **DISETUJUI SEBAGAI STANDAR RESMI ESPATA** |

---

## 2. Rancangan Skema Basis Data Relasional Detail

*(Catatan: Ini adalah spesifikasi desain teknis untuk migrasi resmi pada Fase 3, belum dieksekusi di database).*

```
┌────────────────────────────────────────────────────────┐
│                   ai_conversations                     │
├────────────────────────────────────────────────────────┤
│ id: VARCHAR(36) [PK, UUIDv4]                           │
│ user_id: INT UNSIGNED [NOT NULL]                       │
│ role_id: INT NOT NULL                                  │
│ title: VARCHAR(150) NOT NULL                           │
│ context_type: VARCHAR(50) NOT NULL DEFAULT 'copilot'   │
│ is_active: TINYINT(1) NOT NULL DEFAULT 1               │
│ created_at: DATETIME NOT NULL                          │
│ updated_at: DATETIME NOT NULL                          │
└───────────────────────────┬────────────────────────────┘
                            │ 1
                            │ (Foreign Key: conversation_id)
                            │ ON DELETE CASCADE
                            ▼ N
┌────────────────────────────────────────────────────────┐
│                     ai_messages                        │
├────────────────────────────────────────────────────────┤
│ id: BIGINT UNSIGNED AUTO_INCREMENT [PK]                │
│ conversation_id: VARCHAR(36) [FK -> ai_conversations]  │
│ client_message_id: VARCHAR(64) NULL                    │
│ role: ENUM('user', 'assistant', 'system') NOT NULL     │
│ content: TEXT NOT NULL                                 │
│ tokens_in: INT UNSIGNED NOT NULL DEFAULT 0             │
│ tokens_out: INT UNSIGNED NOT NULL DEFAULT 0            │
│ provider: VARCHAR(30) NULL                             │
│ model: VARCHAR(50) NULL                                │
│ status: ENUM('pending','completed','failed',           │
│              'timeout','aborted') NOT NULL             │
│ error_code: VARCHAR(50) NULL                           │
│ latency_ms: INT UNSIGNED NULL                          │
│ created_at: DATETIME NOT NULL                          │
└────────────────────────────────────────────────────────┘
```

### Evaluasi Teknis Integritas Referensial:
1. **Evaluasi `ON DELETE CASCADE`:**
   - **Keputusan: SANGAT TEPAT & DISETUJUI.**
   - *Justifikasi Arsitektur:* Tabel `ai_messages` merupakan *weak entity* (entitas bergantung penuh) yang keberadaannya tidak memiliki makna tanpa header sesi `ai_conversations`. Penerapan `ON DELETE CASCADE` di tingkat basis data menjamin pembersihan atomik seluruh riwayat percakapan saat pengguna menghapus sesi, sehingga mencegah timbulnya *orphan records* yang memboroskan ruang penyimpanan shared hosting.
2. **Evaluasi `ON UPDATE CASCADE` vs `ON UPDATE RESTRICT`:**
   - **Keputusan: ON UPDATE RESTRICT (Direkomendasikan) / ON UPDATE CASCADE (Defensif).**
   - *Justifikasi Arsitektur:* Kolom `id` pada `ai_conversations` menggunakan UUIDv4 yang digenerate secara permanen saat inisiasi sesi dan bersifat *immutable* (tidak pernah diubah). Mengingat nilainya tidak pernah di-update, klausul `ON UPDATE RESTRICT` mencegah mutasi ID yang tidak disengaja. Namun jika disertakan `ON UPDATE CASCADE`, dampaknya netral dan berfungsi sebagai *defensive constraint*.

---

## 3. Siklus Hidup Pesan, Idempotency, dan Penanganan Stale Pending

```
                  ┌──────────────────────┐
                  │ Pengguna Kirim Pesan │
                  └──────────┬───────────┘
                             │
                             ▼
              [ Cek Idempotency Key di DB ]
             /                             \
     (Ada & 'pending')             (Belum ada / Baru)
           /                                 \
  ┌─────────────────┐             ┌─────────────────────────┐
  │ Tolak Permintaan │             │ Simpan Pesan 'pending'  │
  │ Status HTTP 409 │             └────────────┬────────────┘
  │ (Sedang Proses) │                          │
  └─────────────────┘                          ▼
                                   [ Jalankan cURL LLM ]
                                  /          |          \
                             (Sukses)     (Timeout)    (Error API)
                                /            |            \
                               ▼             ▼             ▼
                        ┌───────────┐  ┌───────────┐  ┌───────────┐
                        │'completed'│  │ 'timeout' │  │ 'failed'  │
                        └───────────┘  └───────────┘  └───────────┘
```

### A. Penanganan Pesan Pending yang Kedaluwarsa (*Zombie Pending Cleanup*)
- **Masalah:** Jika server PHP mengalami pemutusan mendadak (*abrupt termination*) atau koneksi cURL terputus tanpa men-trigger callback error, baris pesan dapat tertinggal dalam status `'pending'` secara permanen, yang akan memblokir pengiriman pesan berikutnya karena terhambat proteksi idempotency.
- **Solusi Protokol Pemulihan:**
  Setiap kali controller menerima permintaan baru pada suatu sesi, sistem menjalankan verifikasi kedaluwarsa:
  ```php
  // Tandai pesan pending yang berusia > 60 detik sebagai timeout
  $db->table('ai_messages')
      ->where('conversation_id', $conversationId)
      ->where('status', 'pending')
      ->where('created_at <', date('Y-m-d H:i:s', time() - 60))
      ->update([
          'status'     => 'timeout',
          'error_code' => 'STALE_PENDING_EXPIRED'
      ]);
  ```
  Dengan aturan ini, pengguna tidak akan pernah terkunci selamanya oleh pesan zombie.

### B. Penghapusan Sesi Ketika Masih Ada Proses Aktif (*Soft Tombstone Guard*)
- **Masalah:** Pengguna mengklik tombol *"Hapus Percakapan"* tepat saat cURL ke vendor AI masih berjalan di latar belakang.
- **Solusi Protokol Tombstone:**
  1. Controller mengubah status sesi induk menjadi `is_active = 0` (soft tombstone) dan memutus tampilan di sisi peramban seketika.
  2. Saat callback cURL selesai menerima respon, service memeriksa status `is_active` pada `ai_conversations`.
  3. Jika `is_active == 0`, hasil respon LLM langsung dibuang (*discarded*), tidak di-commit ke `ai_messages`, dan baris sesi dihapus secara tuntas (*hard delete*) dari database.

---

## 4. Anggaran Token Presisi & Manajemen Konteks Berlapis

Untuk menjaga latensi respon tetap di bawah 2 detik dan mencegah lonjakan biaya/kuota API, anggaran token dihitung secara matematis dengan memperhitungkan **overhead protokol vendor dan format respons**:

```
┌────────────────────────────────────────────────────────────────────────┐
│            ALOKASI ANGGARAN TOKEN PRESISI (EXACT TOKEN BUDGET)         │
├───────────────────────────────────────────────────────┬────────────────┤
│ Komponen Konteks Payload                              │ Alokasi Token  │
├───────────────────────────────────────────────────────┼────────────────┤
│ 1. System Prompt (Instruksi Peran, Batasan Zero PII)  │ 600 token      │
│ 2. Konteks Data Sekolah (Jadwal/Fase/Rubrik Lokal)    │ 800 token      │
│ 3. Riwayat Dialog (Sliding Window Maks 6 Pesan)       │ 1.000 token    │
│ 4. Pertanyaan Baru Pengguna Saat Ini                  │ 400 token      │
│ 5. Overhead Protokol & Format Respon                  │ 200 token      │
│    (Framing tag vendor <|im_start|>, skema JSON, dll) │                │
├───────────────────────────────────────────────────────┼────────────────┤
│ TOTAL ANGGARAN MASUKAN (INPUT / PROMPT TOKENS)        │ 3.000 token    │
├───────────────────────────────────────────────────────┼────────────────┤
│ BATAS MAKSIMAL KELUARAN (COMPLETION TOKENS):          │                │
│ - Skenario A: Interaksi Chat Copilot Default          │ 1.096 token    │
│ - Skenario B: Generator Dokumen Panjang (Modul Ajar)  │ 2.048 token    │
├───────────────────────────────────────────────────────┼────────────────┤
│ TOTAL ANGGARAN INTERAKSI LENGKAP (INPUT + OUTPUT):    │                │
│ - Total Skenario A (Copilot Default): 3.000 + 1.096   │ 4.096 token    │
│ - Total Skenario B (Extended Doc Gen): 3.000 + 2.048  │ 5.048 token    │
└───────────────────────────────────────────────────────┴────────────────┘
```

> [!NOTE]
> **Penjelasan Komponen 5 (Overhead Protokol & Format Respon - 200 Token):**  
> Komponen ini dicadangkan khusus untuk mengantisipasi token pembungkus protokol perpesanan vendor AI (misalnya token kontrol format ChatML `<|im_start|>system...<|im_end|>`, definisi skema JSON jika `jsonMode = true`, serta tag penutup respon), sehingga tidak memotong jatah teks pengguna.

---

## 5. Harmonisasi Kebijakan Retensi: Memori Percakapan vs Log Audit

Pemisahan periode retensi dirancang agar selaras dengan **prinsip hukum perlindungan data pribadi (UU PDP No. 27/2022)** dan **kebutuhan audit operasional sekolah**:

| Parameter Tata Kelola | Memori Percakapan (`ai_messages`) | Log Audit Operasional (`ai_assistant_logs`) |
|:---|:---|:---|
| **Landasan Kebijakan** | Prinsip Minimasi Data (*Data Minimization*) & Batasan Penyimpanan (*Storage Limitation*). | Prinsip Akuntabilitas Sistem (*System Accountability*) & Jejak Forensik Keamanan. |
| **Karakteristik Data** | Dinamis, privat, memuat dialog panjang guru/siswa, berpotensi membebani basis data. | Statis, telemetri teknis, ringkas (user ID, role ID, IP, intent, sumber), tanpa teks percakapan. |
| **Periode Retensi** | **30 Hari Kalender** sejak pesan dibuat. | **365 Hari Kalender (1 Tahun Ajaran Penuh)**. |
| **Mekanisme Purge** | Task pembersihan harian terjadwal via CLI/Cron internal aplikasi. | Pengarsipan tahunan berkala oleh Administrator pada pergantian tahun ajaran. |
| **Hak Penghapusan Pengguna** | **Diizinkan Penuh**. Guru/siswa berhak mengklik *"Hapus Riwayat"* kapan saja. | **Dilarang Mutlak**. Jejak audit bersifat *immutable* demi integritas kepatuhan sekolah. |
| **Penyelarasan Regulasi** | Tidak bertentangan: data dialog pribadi dibersihkan cepat demi privasi, sedangkan log keamanan teknis disimpan untuk audit. |

---

## 6. Protokol Keamanan Akses Sesi (Anti-IDOR)

1. **Validasi Kepemilikan Pre-Query Mutlak:**
   Setiap kali pengguna meminta pembacaan riwayat obrolan atau menambahkan pesan ke suatu sesi, controller wajib mengeksekusi validasi kepemilikan ganda:
   ```php
   $conversation = $conversationModel
       ->where('id', $conversationId)
       ->where('user_id', session()->get('user_id'))
       ->first();

   if (!$conversation) {
       return $this->response->setStatusCode(403)->setJSON([
           'status'  => 'error',
           'message' => 'Sesi percakapan tidak ditemukan atau Anda tidak memiliki hak akses.'
       ]);
   }
   ```
2. **Pembersihan Bersama (Cascading Purge):**
   Ketika sebuah sesi dihapus melalui `ai_conversations`, constraint basis data `ON DELETE CASCADE` secara otomatis membersihkan seluruh baris pesan terkait pada `ai_messages` tanpa meninggalkan baris yatim (*orphan records*).