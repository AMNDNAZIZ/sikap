# EAO-DESIGN-2026-006A-ADMIN-QUOTA
## Desain Teknis Manajemen Kuota Kunci Administrator: Maksimal 20 Generasi per Hari (Opsi B)

**Nomor Dokumen:** EAO-DESIGN-2026-006A-ADMIN-QUOTA  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, QA Engineer, Developer)  
**Mandat Terkait:** EAO-MANDATE-2026-006 (Keputusan 1.2 Human Owner)  
**Status Desain:** PROPOSAL TEKNIS UNTUK PERSETUJUAN HUMAN OWNER  
**Batas Implementasi:** Belum diimplementasikan; menunggu persetujuan tertulis.

---

## 1. Latar Belakang & Aturan Kebijakan Human Owner

Berdasarkan penetapan resmi Human Owner pada **EAO-MANDATE-2026-006 (Bagian 1.2)**:
* **Batas Maksimal:** **20 generasi per hari kalender**.
* **Sifat Batas:** Akumulatif untuk **seluruh pengguna** yang meminjam kunci Administrator sebagai fallback. Bukan 20 generasi per individu pengguna.
* **Isolasi Kuota Pribadi:** Penggunaan kunci pribadi pengguna (`keySource = 'self'`) **TIDAK MENGURANGI** kuota Administrator.
* **Zona Waktu:** Perhitungan reset harian wajib menggunakan zona waktu resmi **`Asia/Jakarta`** (WIB, UTC+7).
* **Anti-Race Condition:** Mekanisme alokasi slot kuota wajib bersifat atomik (*concurrency-safe*) agar lonjakan permintaan bersamaan (*concurrent requests*) tidak menembus batas 20.
* **Perilaku Kuota Habis:** Menolak penggunaan kunci Administrator dan secara anggun mengalihkan permintaan ke sistem lokal / non-AI (*zero crash / zero regression*).
* **Dilarang Keras:** Dilarang mengalihkan permintaan ke kunci milik guru lain.

---

## 2. Sepuluh Jawaban Wajib Ketentuan Desain

### 1. Definisi Satu Generasi yang Dihitung
Satu generasi didefinisikan sebagai:
> **Setiap satu permintaan konten AI yang berhasil dialokasikan slotnya dan dikirimkan (*dispatched*) ke endpoint vendor AI (Groq atau Gemini) dengan menyertakan API Key milik Administrator.**

### 2. Kapan Kuota Dikurangi (*Deduction Lifecycle*)
Kuota dikurangi pada saat **sebelum panggilan HTTP cURL dikirimkan (*Pre-Dispatch Reservation*)**:
- Jika sistem baru mengurangi kuota setelah respons selesai, permintaan bersamaan (*race condition*) dapat lolos dan menghasilkan lebih dari 20 pemanggilan.
- Oleh karena itu, diterapkan pola **Atomic Slot Reservation**:
  1. Sistem memeriksa dan mengklaim 1 slot kuota secara atomik.
  2. Jika slot berhasil diklaim (nilai counter $\le 20$), panggilan ke vendor AI dikirimkan.
  3. Jika slot ditolak (nilai counter sudah $\ge 20$), panggilan dibatalkan seketika tanpa menyentuh jaringan vendor.

### 3. Penanganan Timeout dan HTTP Error dari Provider
- **Kebijakan Biaya Vendor:** Pihak penyedia LLM (khususnya jika menggunakan paid tier atau limited RPM/TPM) tetap menghitung request yang masuk ke server mereka meskipun koneksi klien terputus karena timeout (misal timeout 6 detik di sisi Copilot).
- **Keputusan Desain:** **Slot kuota yang sudah dikirimkan ke provider TETAP DIHITUNG dan TIDAK DIKEMBALIKAN (No Rollback on Dispatch)**.
- Hal ini menjamin bahwa batas kuota 20 benar-benar melindungi kuota dan anggaran API Administrator dari kebobolan akibat akumulasi retry atau timeout beruntun.
- Pengecualian: Jika permintaan ditolak *sebelum* pemanggilan jaringan (misalnya gagal validasi Zero PII atau sistem offline lokal), slot tidak pernah dipotong.

### 4. Pencegahan Permintaan Bersamaan Melewati Batas (*Concurrency & Anti-Race Condition*)
Untuk mencegah kondisi balapan (*race condition*) ketika beberapa guru menekan tombol kirim pada detik yang sama di shared hosting:
- Dilarang menggunakan pola naif `SELECT count ... IF count < 20 THEN INSERT/UPDATE`.
- Diterapkan **Operasi Atomik Tunggal pada Basis Data**:
  ```sql
  UPDATE ai_admin_quota_daily
  SET used_count = used_count + 1,
      updated_at = NOW()
  WHERE quota_date = '2026-11-16'
    AND used_count < 20;
  ```
- **Prinsip Jaminan:**  
  Dalam mesin basis data (InnoDB/MySQL), perintah `UPDATE` dengan klausa `WHERE used_count < 20` menjalankan *row-level locking* secara otomatis.
  - Jika 5 request masuk bersamaan saat `used_count = 19`:
    - Tepat 1 transaksi berhasil mengupdate baris (`affected_rows() === 1`), menjadi request ke-20.
    - 4 transaksi lainnya mendapatkan `affected_rows() === 0` dan langsung dinyatakan **QUOTA_EXHAUSTED**.
  - Batas 20 dijamin secara matematis tidak akan pernah terlampaui.

### 5. Mekanisme Reset Harian Berbasis `Asia/Jakarta`
- Tanggal kuota ditentukan dengan mengevaluasi tanggal kalender pada zona waktu `Asia/Jakarta`:
  ```php
  $tz = new \DateTimeZone('Asia/Jakarta');
  $now = new \DateTime('now', $tz);
  $quotaDate = $now->format('Y-m-d'); // Contoh: '2026-11-16'
  ```
- Setiap pergantian hari pukul 00:00:00 WIB, baris baru untuk tanggal tersebut akan dibuat secara otomatis (*lazy initialization via INSERT IGNORE / ON DUPLICATE KEY UPDATE*), sehingga counter otomatis mulai kembali dari angka 0 tanpa memerlukan background cron daemon.

### 6. Penanganan Kegagalan Penyimpanan Counter (*Fail-Safe Behavior*)
- Jika tabel penyimpanan kuota mengalami kendala (misal database lock timeout):
  - Sistem mengadopsi prinsip **Fail-Closed for Fallback (Aman Secara Finansial)**:  
    Jika sistem tidak dapat memastikan secara pasti bahwa kuota masih tersedia, sistem **menolak** penggunaan kunci Administrator dan beralih ke mode offline lokal.
  - Dengan demikian, kegagalan database tidak akan menyebabkan kunci Administrator terkuras tanpa batas.

### 7. Tampilan Kondisi Kuota Habis kepada Pengguna
- Respons disajikan dalam Bahasa Indonesia yang santun, informatif, dan tidak menimbulkan kesan aplikasi rusak:
  ```json
  {
    "status": "success",
    "source": "local",
    "explanation": "Kuota harian bantuan AI sekolah telah mencapai batas maksimal (20 generasi/hari). Asis AI mengarahkan Anda menggunakan rekomendasi pencocokan lokal.",
    "matched_action": { ... },
    "quota_info": {
      "exhausted": true,
      "limit": 20,
      "resets_at": "Besok pukul 00:00 WIB"
    }
  }
  ```
- Guru yang memiliki API Key pribadi (`teachers.gemini_api_key`) diberi saran ramah pada profilnya bahwa mereka dapat memasukkan kunci pribadi untuk menikmati akses tanpa batasan kuota sekolah.

### 8. Cara Administrator Memantau Penggunaan Kuota
- Admin dapat melihat status kuota secara transparan pada:
  1. Header widget Asis AI Copilot (khusus login role Administrator):  
     Badge kecil: `Kuota AI Sekolah Hari Ini: 14/20`.
  2. Tabel log audit: Dapat memfilter `ai_assistant_logs` dengan `source LIKE '%:admin_fallback:%'` pada tanggal berjalan.

### 9. Pemeliharaan Pencatatan Pemohon dan Pemilik Kunci
- Setiap penggunaan kuota Administrator tetap merekam dua ID secara terpisah:
  - `requester_id` = ID guru/staf yang meminta.
  - `key_owner_id` = ID akun Administrator pemilik kuota.
  - Dicatat dalam tag telemetri kolom `source`: `groq:admin_fallback:1`.

### 10. Isolasi Kunci Pribadi Pengguna (BYOK Self-Exemption)
- Ketika `AiKeyResolver` mendeteksi bahwa pemohon memiliki kunci pribadi (`keySource = 'self'`), alur pengecekan kuota Administrator **DILEWATI SEPENUHNYA (*BYPASSED*)**.
- Guru dengan kunci sendiri dapat mengajukan permintaan sebanyak kuota pribadi mereka tanpa pernah diblokir oleh kuota Administrator sekolah.

---

## 3. Komponen Layanan: `App\Services\Ai\AdminQuotaManager`

Untuk memusatkan seluruh logika di atas, dirancang satu kelas layanan khusus:

```php
namespace App\Services\Ai;

use App\Services\BaseService;

class AdminQuotaManager extends BaseService
{
    public const DAILY_LIMIT = 20;
    public const TIMEZONE    = 'Asia/Jakarta';

    /**
     * Mencoba memesan 1 slot kuota secara atomik
     * 
     * @return array [
     *   'allowed'     => bool,
     *   'used_count'  => int,
     *   'limit'       => int,
     *   'resets_at'   => string
     * ]
     */
    public function acquireSlot(): array
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $now = new \DateTime('now', $tz);
        $today = $now->format('Y-m-d');

        if (!$this->db) {
            return ['allowed' => false, 'reason' => 'DB_UNAVAILABLE'];
        }

        try {
            // 1. Pastikan baris untuk hari ini sudah ada (Lazy initialization)
            $this->db->query("
                INSERT INTO ai_admin_quota_daily (quota_date, used_count, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON DUPLICATE KEY UPDATE id=id
            ", [$today]);

            // 2. Operasi Atomik Pemesanan Slot
            $this->db->query("
                UPDATE ai_admin_quota_daily
                SET used_count = used_count + 1,
                    updated_at = NOW()
                WHERE quota_date = ?
                  AND used_count < ?
            ", [$today, self::DAILY_LIMIT]);

            $affected = $this->db->affectedRows();

            // 3. Ambil total pemakaian terkini
            $row = $this->db->table('ai_admin_quota_daily')
                ->where('quota_date', $today)
                ->get()
                ->getRowArray();

            $used = (int) ($row['used_count'] ?? self::DAILY_LIMIT);

            if ($affected > 0) {
                return [
                    'allowed'    => true,
                    'used_count' => $used,
                    'limit'      => self::DAILY_LIMIT,
                    'resets_at'  => $today . ' 23:59:59 WIB',
                ];
            }

            return [
                'allowed'    => false,
                'used_count' => $used,
                'limit'      => self::DAILY_LIMIT,
                'reason'     => 'QUOTA_EXHAUSTED',
                'resets_at'  => $today . ' 23:59:59 WIB',
            ];
        } catch (\Throwable $e) {
            log_message('error', '[AdminQuotaManager::acquireSlot] ' . $e->getMessage());
            // Fail-safe closed: tolak alokasi jika database bermasalah
            return ['allowed' => false, 'reason' => 'DB_ERROR'];
        }
    }

    /**
     * Mendapatkan status pemakaian kuota saat ini tanpa menambah hitungan (Read-only)
     */
    public function getStatus(): array
    {
        $tz = new \DateTimeZone(self::TIMEZONE);
        $now = new \DateTime('now', $tz);
        $today = $now->format('Y-m-d');

        try {
            $row = $this->db->table('ai_admin_quota_daily')
                ->where('quota_date', $today)
                ->get()
                ->getRowArray();

            $used = (int) ($row['used_count'] ?? 0);
            return [
                'quota_date' => $today,
                'used_count' => $used,
                'limit'      => self::DAILY_LIMIT,
                'remaining'  => max(0, self::DAILY_LIMIT - $used),
                'exhausted'  => ($used >= self::DAILY_LIMIT),
            ];
        } catch (\Throwable $e) {
            return [
                'quota_date' => $today,
                'used_count' => 0,
                'limit'      => self::DAILY_LIMIT,
                'remaining'  => self::DAILY_LIMIT,
                'exhausted'  => false,
            ];
        }
    }
}
```

---

## 4. Usulan Skema Database: Tabel `ai_admin_quota_daily`

Untuk mendukung operasi atomik di atas secara optimal tanpa membebani tabel konfigurasi umum, diajukan rancangan berkas migrasi CodeIgniter 4:

* **Nama Berkas Migrasi Usulan:** `app/Database/Migrations/2026-11-16-100000_CreateAiAdminQuotaDailyTable.php`
* **Status:** PROPOSAL SAJA (TIDAK DIJALANKAN PADA FASE 1C SEBELUM PERSETUJUAN).

```php
<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiAdminQuotaDailyTable extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('ai_admin_quota_daily')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'quota_date' => [
                    'type' => 'DATE',
                    'null' => false,
                ],
                'used_count' => [
                    'type'       => 'INT',
                    'constraint' => 5,
                    'unsigned'   => true,
                    'default'    => 0,
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
            $this->forge->addUniqueKey('quota_date');
            $this->forge->createTable('ai_admin_quota_daily', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('ai_admin_quota_daily', true);
    }
}
```

### Opsi Cadangan Tanpa Migrasi Database Baru:
Apabila Human Owner belum mengizinkan pembuatan tabel baru `ai_admin_quota_daily`, sistem dapat memanfaatkan tabel `settings` yang sudah ada (`setting_key = 'ai_admin_quota_YYYY-MM-DD'`), dengan serialisasi JSON atau nilai integer yang diperbarui secara atomik. Namun pembuatan tabel khusus `ai_admin_quota_daily` jauh lebih direkomendasikan demi integritas penguncian baris (*row-level locking*).
