# EAO-REP-2026-006E-IDEMPOTENCY-VERIFICATION
## Laporan Audit & Pengujian Ekstrem Idempotensi: Evaluasi 10 Skenario, Kerapuhan Cache-Only, dan Pengajuan Skema Persisten

**Nomor Dokumen:** EAO-REP-2026-006E-IDEMPOTENCY-VERIFICATION  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, Developer, QA Engineer)  
**Dasar Mandat:** EAO-HO-006E (Bagian C — Idempotency Verification)  
**Status Audit:** READ-ONLY VERIFICATION & ARCHITECTURAL PROPOSAL  
**Batasan Kerja:** Desain & verifikasi arsitektur; dilarang mengubah kode dan database sebelum persetujuan resmi Human Owner.

---

## 1. Audit Alur `client_message_id` & Evaluasi 10 Skenario Ekstrem

Tim arsitek melakukan audit komprehensif terhadap arsitektur idempotensi permintaan Asis AI Copilot. Setiap penekanan tombol submit di antarmuka web menghasilkan satu identitas unik **`client_message_id`** (berformat UUID v4: `asis-xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx`).

Berikut hasil evaluasi terhadap **10 Skenario Uji Ekstrem**:

| No | Skenario Uji Ekstrem | Perilaku Sistem pada Model Cache-Only (15s TTL) | Perilaku Sistem pada Model Database Persisten (`ai_request_idempotency`) | Evaluasi Ketahanan & Risiko |
|:---:|:---|:---|:---|:---:|
| 1 | **Dua permintaan berbeda dengan teks identik tetapi UUID berbeda** (misal guru bertanya hal sama berurutan). | **LOLOS:** Karena UUID berbeda, kedua request diproses independen. Tidak ada false-conflict. | **LOLOS:** Masing-masing UUID memiliki baris sendiri. Diproses lancar tanpa saling blokir. | **Sangat Baik** |
| 2 | **Satu permintaan dikirim ulang dengan UUID yang sama** (retry jaringan). | **RENTAN:** Jika request pertama selesai dalam 3 detik dan lock dihapus, pengiriman ulang diproses ulang dan **memotong kuota ke-2**. | **LOLOS:** Status baris tercatat `COMPLETED`. Sistem langsung mengembalikan respons tersimpan (*replay*) **tanpa memotong kuota**. | **DB Persisten Jauh Lebih Unggul** |
| 3 | **Double click sebelum respons frontend** (jeda 100–300 ms). | **LOLOS:** Request ke-2 tertolak oleh in-flight lock. Hanya 1 proses yang jalan dan 1 slot kuota terpotong. | **LOLOS:** Request ke-2 tertolak oleh *unique constraint* baris atau status `IN_FLIGHT`. Tepat 1 slot kuota. | **Keduanya Lolos** |
| 4 | **Retry setelah timeout** (setelah 6 detik klien disconnect dan kirim ulang dengan UUID sama). | **RENTAN:** Lock mungkin sudah dihapus atau kedaluwarsa; request diproses ulang sebagai permintaan baru. | **LOLOS:** Sistem mendeteksi UUID berstatus `TIMEOUT`. Menolak pemotongan kuota baru dan memberitahu klien. | **DB Persisten Jauh Lebih Unggul** |
| 5 | **Proses berlangsung lebih lama daripada TTL** (misal inferensi butuh 18 detik, TTL lock 15 detik). | **GAGAL FATAL:** Pada detik ke-15, file lock kedaluwarsa. Jika klien retry pada detik ke-16, **terjadi 2 proses simultan untuk 1 UUID**. | **LOLOS:** Baris tetap berstatus `IN_FLIGHT` hingga koneksi cURL ditutup oleh skrip PHP, terlepas dari durasi waktu. | **Cache-Only GAGAL** |
| 6 | **Lock kedaluwarsa ketika proses masih berjalan** (kasus edge case OOM atau delay jaringan). | **GAGAL FATAL:** Terjadi *race condition* akibat lock hilang sebelum eksekusi tuntas. | **LOLOS:** Mekanisme atomik database mencegah pembuatan baris baru dengan UUID yang sama. | **Cache-Only GAGAL** |
| 7 | **Dua worker/server menerima UUID yang sama** (cluster multi-node di shared hosting/load balancer). | **GAGAL FATAL:** File cache Node A tidak dapat dibaca oleh Node B. Kedua node memproses request bersamaan. | **LOLOS:** Database MySQL bersifat terpusat. Unique key `client_message_id` menolak worker kedua secara instan. | **Cache-Only GAGAL** |
| 8 | **Cache tidak tersedia** (izin folder `writable/cache/` berubah atau disk penuh). | **GAGAL FATAL:** `cache()->save()` mengembalikan false. Sistem kehilangan seluruh fungsi in-flight locking. | **LOLOS:** Mengandalkan koneksi database yang merupakan dependensi utama aplikasi. | **Cache-Only GAGAL** |
| 9 | **Cache kehilangan data setelah restart** (server reboot atau cache purge). | **GAGAL:** Seluruh riwayat lock aktif hilang seketika. | **LOLOS:** Data tersimpan persisten di media penyimpanan InnoDB (ACID compliant). | **Cache-Only GAGAL** |
| 10 | **Request selesai tetapi frontend belum menerima respons** (paket drop saat perjalanan balik). | **GAGAL:** Frontend melakukan retry, backend menganggap proses baru, kuota terpotong ganda, dan LLM dihitung 2x. | **LOLOS:** Frontend mengirim UUID yang sama, backend mengambil `response_payload` dari database dan mengirim ulang instan. | **DB Persisten Jauh Lebih Unggul** |

---

## 2. Analisis Kritis terhadap Pertanyaan Mandat Human Owner

### 2.1 Apakah TTL 15 Detik Masih Layak?
**JAWABAN TEGAS: TIDAK LAYAK.**  
Batas waktu 15 detik adalah asumsi yang terlalu sempit dan berbahaya:
1. Batas timeout HTTP klien pada `AiCopilot.php` adalah 6 detik, namun pada beban puncak (*peak load*) vendor LLM (Groq / Gemini) dapat mengalami latensi antrean pemrosesan token hingga 10–20 detik.
2. Jika TTL lock hanya 15 detik, terdapat celah di mana proses cURL masih memblokir eksekusi di latar belakang sementara file lock telah kedaluwarsa di detik ke-15. Retry yang masuk pada detik ke-16 akan menembus sistem dan memicu eksekusi ganda (*double spending*).
3. **Ketetapan:** TTL in-flight minimum harus **60 hingga 120 detik** jika menggunakan mekanisme berbasis waktu.

### 2.2 Apakah Lock Dapat Diperpanjang (*Heartbeat Locking*)?
Di lingkungan PHP murni pada shared hosting (arsitektur *share-nothing synchronous process*):
- Eksekusi cURL via `curl_exec()` adalah pemanggilan sistem yang bersifat memblokir (*blocking I/O call*).
- Selama cURL menunggu respons, *thread* PHP tidur (*sleep/block*) di kernel level dan **tidak dapat mengeksekusi kode perpanjangan lock secara berkala** (*no asynchronous heartbeat timer in single-threaded PHP execution*).
- Oleh karena itu, pendekatan perpanjangan lock dinamis tidak realistis di shared hosting. Solusi yang benar adalah menetapkan status siklus hidup berbasis status transaksi database.

### 2.3 Bagaimana Mencegah Dua Worker Memproses UUID yang Sama?
Pencegahan mutlak hanya dapat dicapai melalui **Unique Constraint di tingkat Storage Engine Database Terpusat**:
- Kueri `INSERT INTO ai_request_idempotency (client_message_id, status, ...)` dengan indeks unik pada `client_message_id`.
- Worker pertama yang berhasil melakukan `INSERT` memegang hak eksekusi tunggal.
- Worker kedua yang mencoba `INSERT` dengan UUID yang sama akan langsung ditolak oleh MySQL dengan galat `Duplicate entry (Error 1062)`.

### 2.4 Apakah Cache yang Digunakan Bersifat Shared/Distributed?
**TIDAK.**  
Layanan cache bawaan CodeIgniter 4 yang digunakan saat ini adalah `FileHandler` (`writable/cache/`). Cache ini bertempat di filesystem lokal satu server. Jika aplikasi dijalankan di lingkungan multi-server di balik load balancer, cache ini **TIDAK TERBAGI (*Not Shared*)**, sehingga tidak mampu memberikan jaminan konkurensi antar-node.

### 2.5 Bagaimana Sistem Berperilaku Ketika Cache Gagal?
- Pada model *cache-only*, jika penyimpanan cache gagal (misalnya permission error), sistem berada dalam dilema:
  * *Fail-Open:* Mengabaikan kegagalan cache dan tetap memproses AI $\rightarrow$ **Proteksi klik ganda lumpuh total**.
  * *Fail-Closed:* Menolak permintaan pengguna $\rightarrow$ **Fitur AI mati total**.
- Kerapuhan ini membuktikan bahwa cache file lokal tidak memenuhi standar keandalan tinggi yang diharapkan Human Owner.

### 2.6 Apakah Fase 1C Memerlukan Tabel Idempotensi Persisten?
**KESIMPULAN AUDIT: YA, WAJIB MEMERLUKAN TABEL PERSISTEN.**  
Berdasarkan kegagalan model *cache-only* pada 6 dari 10 skenario ekstrem di atas, mengandalkan file cache berumur 15 detik **TIDAK MEMENUHI ACCEPTANCE CRITERIA PRODUKSI**.

Oleh karena itu, Tim Multi-Agen EAO merekomendasikan secara resmi agar **Tabel Idempotensi Persisten `ai_request_idempotency` dimasukkan sebagai bagian integral dari lingkup implementasi Fase 1C**.

---

## 3. Usulan Rancang Bangun Tabel Persisten: `ai_request_idempotency`

Sebagai pemenuhan mandat, berikut rancangan berkas migrasi resmi CodeIgniter 4 yang telah disiapkan secara lengkap (disajikan untuk evaluasi, **TIDAK DIJALANKAN / TIDAK DIBUAT SEBELUM IZIN RESMI**):

### 3.1 Berkas Migrasi yang Diajukan
**Nama File:** `app/Database/Migrations/2026-11-16-100001_CreateAiRequestIdempotencyTable.php`

```php
namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiRequestIdempotencyTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ai_request_idempotency')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'BIGINT',
                    'constraint'     => 20,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'client_message_id' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'user_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                    'null'       => false,
                ],
                'request_hash' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 64,
                    'null'       => false,
                ],
                'status' => [
                    'type'       => 'ENUM',
                    'constraint' => [
                        'IN_FLIGHT',
                        'COMPLETED',
                        'FAILED_BEFORE_HTTP_DISPATCH',
                        'TIMEOUT',
                        'PROVIDER_ERROR',
                        'DELIVERY_UNKNOWN'
                    ],
                    'default'    => 'IN_FLIGHT',
                ],
                'quota_deducted' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                ],
                'response_payload' => [
                    'type' => 'MEDIUMTEXT',
                    'null' => true,
                ],
                'created_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                ],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addUniqueKey('client_message_id');
            $this->forge->addKey(['user_id', 'created_at']);
            $this->forge->addKey('status');

            $attributes = [
                'ENGINE'  => 'InnoDB',
                'CHARSET' => 'utf8mb4',
                'COLLATE' => 'utf8mb4_unicode_ci',
            ];

            $this->forge->createTable('ai_request_idempotency', true, $attributes);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_request_idempotency', true);
    }
}
```

---

## 4. Analisis Dampak Penerapan Tabel `ai_request_idempotency`

### 4.1 Dampak Penyimpanan & I/O Basis Data
- **Ukuran Baris Rata-rata:** ~1,5 KB (termasuk JSON respons ringkas).
- **Volume Pertumbuhan:** Pada penggunaan normal 20–100 prompt per hari, pertumbuhan tabel hanya ~150 KB per hari ($\approx 55$ MB per tahun).
- **Aturan Pembersihan Otomatis (Auto-Pruning):**  
  Data idempotensi yang berusia lebih dari **7 hari** dapat dibersihkan secara otomatis (*lazy pruning*) pada saat query harian dijalankan, sehingga ukuran tabel tetap sangat kecil dan efisien.

### 4.2 Nilai Tambah Langsung bagi Pengguna & Sekolah
1. **Safe Replay:** Klien yang mengalami putus koneksi di detik terakhir dapat mengirim ulang permintaan dan seketika menerima respons tersimpan tanpa menunggu inferensi LLM ulang dan **tanpa menghabiskan kuota tambahan**.
2. **Pemberantasan Klik Ganda 100%:** Dijamin secara fisik oleh mesin InnoDB terpusat.
3. **Audit Trail Tak Terbantahkan:** Menyediakan jejak verifikasi lengkap yang menghubungkan setiap interaksi UI dengan pemotongan kuota harian.
