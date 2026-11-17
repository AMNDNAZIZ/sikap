# EAO-DESIGN-2026-006A-DUAL-TRACEABILITY
## Desain Teknis Telemetri Dual-Traceability, Akuntabilitas Biaya, dan Analisis Skema Database

**Nomor Dokumen:** EAO-DESIGN-2026-006A-DUAL-TRACEABILITY  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, QA Engineer)  
**Mandat Terkait:** EAO-MANDATE-2026-006 (Bagian 1.1, 1.3, dan 3.D)  
**Status Desain:** PROPOSAL TEKNIS UNTUK PERSETUJUAN HUMAN OWNER  
**Batasan Khusus:** Kolom `matched_id` DILARANG digunakan untuk menyisipkan metadata token.

---

## 1. Latar Belakang & Filosofi Akuntabilitas Ganda

Dalam model AI multi-akun (*Bring Your Own Key / BYOK*) dengan pembagian beban biaya, akuntabilitas telemetri harus mampu menjawab 5 pertanyaan kunci pada setiap transaksi AI:

1. **Siapa yang meminta?** (*Requester Identity*: `user_id`, `role_id`).
2. **Kunci siapa yang digunakan?** (*Key Owner Identity*: `key_owner_id`).
3. **Bagaimana status relasi kuncinya?** (*Key Source*: `self` vs `admin_fallback` vs `override` vs `none`).
4. **Ke vendor mana permintaan dikirim?** (*Provider & Model*: `groq` vs `gemini`, nama model).
5. **Berapa konsumsi sumber dayanya?** (*Token Usage*: `prompt_tokens`, `completion_tokens`, `latency_ms`).

---

## 2. Analisis Kompatibilitas Skema Tabel Saat Ini (`ai_assistant_logs`)

### 2.1 Struktur Aktual di Database Produksi
Berdasarkan berkas migrasi `2026-11-13-180000_CreateAiAssistantLogsTable.php`:

| Kolom | Tipe | Status Dukungan Dual-Traceability |
|:---|:---|:---|
| `id` | INT(11) | Auto Increment Primary Key |
| `user_id` | INT(11) | **Dukung Penuh**: Menyimpan ID pengguna pemohon (*requester_id*). |
| `role_id` | INT(5) | **Dukung Penuh**: Menyimpan role pengguna pemohon (1=Admin, 3=Guru, dll). |
| `query` | VARCHAR(500) | **Dukung Penuh**: Teks pertanyaan setelah melalui 4-lapis Zero PII scrubber. |
| `intent` | VARCHAR(100) | **Dukung Penuh**: Menyimpan status hasil (`AI_COMPLETION`, `ERR_AUTH_ERROR`, dll). |
| `matched_id` | VARCHAR(100) | **TIDAK DIGUNAKAN UNTUK TOKEN**: Mematuhi instruksi Human Owner, kolom ini dikhususkan murni untuk ID navigasi aksi lokal. |
| `source` | VARCHAR(50) | **Dukung Penuh**: Menyimpan tag gabungan `provider:key_source:key_owner_id`. |
| `ip_address` | VARCHAR(45) | **Dukung Penuh**: IP address pemohon. |
| `created_at` | DATETIME | **Dukung Penuh**: Waktu eksekusi. |

### 2.2 Evaluasi Format Tag Kolom `source` pada Skema Saat Ini
Format tag terstruktur yang diterapkan sejak Fase 1B:
`{provider}:{key_source}:{key_owner_id}`

Contoh nilai aktual:
- `groq:self:12` (Panjang: 12 char) $\rightarrow$ Guru ID 12 menggunakan kunci Groq miliknya sendiri.
- `groq:admin_fallback:1` (Panjang: 22 char) $\rightarrow$ Pemohon menggunakan kunci Administrator (User ID 1) sebagai fallback sekolah.
- `gemini:self:4` (Panjang: 14 char) $\rightarrow$ Guru ID 4 menggunakan kunci Gemini miliknya sendiri.
- `local:fallback` (Panjang: 14 char) $\rightarrow$ Mode offline lokal (tanpa pemanggilan AI eksternal).

**Kesesuaian:**  
Kolom `source` bertipe `VARCHAR(50)`. Tag terpanjang hanya memakan 27 karakter, menyisakan 23 karakter buffer keamanan. Format ini terbukti bekerja 100% tanpa risiko pemotongan string.

---

## 3. Analisis Kebutuhan Persistensi Penggunaan Token

### 3.1 Status Penggunaan Token Saat Ini
- `GroqAdapter` dan `GeminiAdapter` berhasil mengekstrak token masuk (`prompt_tokens`) dan token keluar (`completion_tokens`) dari respons resmi vendor ke dalam objek `AiResponse`.
- Namun, karena skema `ai_assistant_logs` belum memiliki kolom khusus untuk token dan kolom `matched_id` dilarang digunakan untuk menyisipkan token, maka **data jumlah token saat ini hanya tersimpan di memori jangka pendek (*transient*) selama siklus HTTP request dan belum tersimpan permanen di database**.

### 3.2 Dampak Ketiadaan Kolom Token di Database
- Sistem dapat membatasi kuota Administrator berbasis **jumlah pemanggilan (20 generasi/hari)**, namun belum dapat menghitung total pengeluaran token kumulatif harian (misal: 15.000 prompt tokens + 4.200 completion tokens).
- Audit biaya mendalam memerlukan pencatatan token secara permanen.

---

## 4. Usulan Skema Masa Depan: Migrasi Skema Telemetri Bersih

Mematuhi ketentuan Human Owner:
> *"Jika penyimpanan permanen token atau counter kuota membutuhkan perubahan database, buat usulan skema dan migrasi terpisah. Jangan menjalankannya."*

Tim EAO menyusun rancangan migrasi resmi CodeIgniter 4 untuk dievaluasi oleh Human Owner:

* **Nama Berkas Migrasi Usulan:** `app/Database/Migrations/2026-11-16-110000_AddTokenAndTelemetryColumnsToAiLogs.php`
* **Status:** PROPOSAL RESMI (MENUNGGU PERSETUJUAN — TIDAK DIJALANKAN).

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddTokenAndTelemetryColumnsToAiLogs extends Migration
{
    public function up()
    {
        // Penambahan kolom khusus telemetri biaya dan model AI ke tabel ai_assistant_logs
        if ($this->db->tableExists('ai_assistant_logs')) {
            $fields = [
                'key_owner_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => true,
                    'after'      => 'user_id',
                ],
                'key_source' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20,
                    'default'    => 'none',
                    'after'      => 'key_owner_id',
                ],
                'model' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'source',
                ],
                'prompt_tokens' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'model',
                ],
                'completion_tokens' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'default'    => 0,
                    'after'      => 'prompt_tokens',
                ],
                'latency_ms' => [
                    'type'       => 'FLOAT',
                    'null'       => true,
                    'after'      => 'completion_tokens',
                ],
            ];

            // Tambahkan kolom jika belum ada
            foreach ($fields as $fieldName => $fieldDef) {
                if (!$this->db->fieldExists($fieldName, 'ai_assistant_logs')) {
                    $this->forge->addColumn('ai_assistant_logs', [$fieldName => $fieldDef]);
                }
            }

            // Tambahkan indeks komposit untuk pelaporan efisien
            $this->db->query("CREATE INDEX idx_ai_owner_created ON ai_assistant_logs (key_owner_id, created_at)");
        }
    }

    public function down()
    {
        if ($this->db->tableExists('ai_assistant_logs')) {
            $columnsToDrop = ['key_owner_id', 'key_source', 'model', 'prompt_tokens', 'completion_tokens', 'latency_ms'];
            foreach ($columnsToDrop as $col) {
                if ($this->db->fieldExists($col, 'ai_assistant_logs')) {
                    $this->forge->dropColumn('ai_assistant_logs', $col);
                }
            }
        }
    }
}
```

---

## 5. Matriks Hasil Keputusan Desain Dual-Traceability

| Komponen Telemetri | Solusi Fase 1C (Skema Eksisting Tanpa Migrasi) | Solusi Pasca-Migrasi (Usulan Fase 2) |
|:---|:---|:---|
| **Requester ID** | Disimpan pada kolom `user_id`. | Disimpan pada kolom `user_id`. |
| **Key Owner ID** | Dikodekan pada kolom `source` (`groq:admin_fallback:1`). | Memiliki kolom mandiri `key_owner_id`. |
| **Key Source** | Dikodekan pada kolom `source` (`self` / `admin_fallback`). | Memiliki kolom mandiri `key_source`. |
| **Provider** | Prefiks kolom `source` (`groq` / `gemini`). | Prefiks kolom `source` (`groq` / `gemini`). |
| **Token Usage** | Dihitung di memori (`AiResponse`), belum disimpan permanen ke database (sesuai larangan penggunaan `matched_id`). | Tersimpan permanen di kolom `prompt_tokens` & `completion_tokens`. |
| **Latency cURL** | Dihitung di memori (`latencyMs`). | Tersimpan permanen di kolom `latency_ms`. |
| **Outcome / Status** | Disimpan pada kolom `intent` (`AI_COMPLETION` / `ERR_*`). | Disimpan pada kolom `intent`. |

**Jaminan Integritas Keamanan:**  
Pada kedua solusi di atas, **API Key mentah tidak pernah masuk ke database, log file, atau exception**. Yang disimpan murni identitas peran, ID pengguna, dan angka kuantitatif.
