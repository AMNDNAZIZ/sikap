# EAO-REP-2026-006G-ATOMIC-QUOTA-SQL-FINAL
## Spesifikasi Arsitektur Tingkat Lanjut: Urutan Transaksi SQL Lengkap, Pseudocode Atomik, dan Penanganan Galat Basis Data InnoDB

**Nomor Dokumen:** EAO-REP-2026-006G-ATOMIC-QUOTA-SQL-FINAL  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (System Architect, AI Security Reviewer, Developer, QA Engineer)  
**Dasar Mandat:** EAO-HO-006G (Butir B.3 — Atomic Quota Transaction)  
**Status Dokumen:** FINAL SPECIFICATION — READ-ONLY DESIGN REFINEMENT  
**Batasan Kerja:** Desain & analisis murni; dilarang mengubah kode, database, dan 6 file terlindung lisensi.

---

## 1. Urutan Transaksi SQL Lengkap (*Full Sequential SQL Operations*)

Untuk menjamin konsistensi mutlak antara pencatatan identitas idempotensi dan counter kuota harian Administrator, seluruh operasi diatur dalam urutan transaksional deterministik:

### Tahap 1: Pendaftaran Identitas Permintaan (Idempotency Claim)
Dieksekusi segera saat permintaan tiba di controller:
```sql
-- Kueri 1: Pendaftaran Kunci Idempotensi & Worker Fencing Token
INSERT INTO ai_request_idempotency (
    client_message_id,
    user_id,
    request_hash,
    key_source,
    quota_status,
    status,
    worker_token,
    created_at,
    updated_at
) VALUES (
    ?, -- client_message_id (UUID v4)
    ?, -- user_id (dari sesi login aktif)
    ?, -- request_hash (SHA256 dari user_id + prompt bersih)
    ?, -- key_source ('self' atau 'admin_fallback')
    'NONE',
    'IN_FLIGHT',
    ?, -- worker_token (string acak unik worker)
    NOW(),
    NOW()
);
```

---

### Tahap 2: Reservasi Atomik Kuota Administrator (Hanya jika `key_source = 'admin_fallback'`)
Dieksekusi tepat sebelum pemanggilan adapter vendor AI dalam transaksi manual:

```sql
-- Buka Transaksi Manual
START TRANSACTION;

-- 2.1 Inisialisasi Baris Harian Tanggal Asia/Jakarta (Lazy Upsert)
INSERT INTO ai_admin_quota_daily (quota_date, used_count, created_at, updated_at)
VALUES (?, 0, NOW(), NOW())
ON DUPLICATE KEY UPDATE id = id;

-- 2.2 Pemotongan Slot Kuota Bersyarat (Row-Level Exclusive Lock)
UPDATE ai_admin_quota_daily
SET used_count = used_count + 1,
    updated_at = NOW()
WHERE quota_date = ?
  AND used_count < 20; -- BATAS KERAS MUTLAK: 20 Generasi/Hari

-- 2.3 Sinkronisasi Status Kuota pada Baris Idempotensi
UPDATE ai_request_idempotency
SET quota_status = 'RESERVED',
    quota_date = ?,
    updated_at = NOW()
WHERE client_message_id = ?
  AND worker_token = ?
  AND status = 'IN_FLIGHT';

-- 2.4 Commit Transaksi
COMMIT;
```

---

### Tahap 3: Pengembalian Kuota Satu Kali (*Atomic Single Refund*)
Dieksekusi hanya jika terbukti terjadi kegagalan lokal sebelum pengiriman HTTP (`FAILED_BEFORE_HTTP_DISPATCH`):

```sql
START TRANSACTION;

-- 3.1 Kueri Pengembalian Bersyarat (Menjamin Anti-Double-Refund)
UPDATE ai_request_idempotency
SET quota_status = 'REFUNDED',
    status = 'FAILED_BEFORE_HTTP_DISPATCH',
    updated_at = NOW()
WHERE client_message_id = ?
  AND worker_token = ?
  AND quota_status = 'RESERVED'; -- SYARAT MUTLAK: Hanya yang berstatus RESERVED!

-- 3.2 Kurangi Counter Kuota Harian HANYA JIKA Kueri 3.1 Menghasilkan affected_rows == 1
UPDATE ai_admin_quota_daily
SET used_count = GREATEST(0, used_count - 1),
    updated_at = NOW()
WHERE quota_date = ?
  AND used_count > 0;

COMMIT;
```

---

## 2. Pseudocode Lengkap Metode `AdminQuotaManager::acquireSlot()`

Berikut rancangan pseudocode tangguh berstandar industri dengan mitigasi seluruh kelas galat:

```php
public function acquireSlot(string $clientMessageId, string $workerToken, string $keySource): array
{
    // 1. Jika pengguna memakai kunci pribadi (self), bypass kuota Admin
    if ($keySource === 'self') {
        return ['allowed' => true, 'key_source' => 'self', 'reason' => 'BYPASS_ADMIN_QUOTA'];
    }

    $tz = new \DateTimeZone('Asia/Jakarta');
    $today = (new \DateTime('now', $tz))->format('Y-m-d');

    $maxAttempts = 2;
    $attempt = 0;

    while ($attempt < $maxAttempts) {
        $attempt++;
        
        $this->db->transBegin();

        try {
            // A. Lazy Upsert Baris Tanggal Baru
            $this->db->query("
                INSERT INTO ai_admin_quota_daily (quota_date, used_count, created_at, updated_at)
                VALUES (?, 0, NOW(), NOW())
                ON DUPLICATE KEY UPDATE id = id
            ", [$today]);

            // B. Increment Atomik dengan Syarat used_count < 20
            $this->db->query("
                UPDATE ai_admin_quota_daily
                SET used_count = used_count + 1,
                    updated_at = NOW()
                WHERE quota_date = ?
                  AND used_count < 20
            ", [$today]);

            $slotAcquired = ($this->db->affectedRows() > 0);

            if ($slotAcquired) {
                // C. Ikat Status Reservasi ke Baris Idempotensi
                $this->db->query("
                    UPDATE ai_request_idempotency
                    SET quota_status = 'RESERVED',
                        quota_date = ?,
                        updated_at = NOW()
                    WHERE client_message_id = ?
                      AND worker_token = ?
                      AND status = 'IN_FLIGHT'
                ", [$today, $clientMessageId, $workerToken]);

                // D. Commit Transaksi
                $this->db->transCommit();

                return [
                    'allowed'    => true,
                    'key_source' => 'admin_fallback',
                    'quota_date' => $today,
                    'resets_at'  => $today . ' 23:59:59 WIB'
                ];
            }

            // Kuota Harian Penuh (>= 20)
            $this->db->transCommit();
            return [
                'allowed'    => false,
                'key_source' => 'admin_fallback',
                'reason'     => 'QUOTA_EXHAUSTED',
                'resets_at'  => $today . ' 23:59:59 WIB'
            ];

        } catch (\Throwable $e) {
            // PROTOKOL WAJIB: ROLLBACK BERSIH SEBELUM AMBIL TINDAKAN
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
            }

            $msg = $e->getMessage();

            // PENANGANAN 1: DEADLOCK 1213 -> Retry dengan Jitter
            if ($attempt < $maxAttempts && (str_contains($msg, '1213') || stripos($msg, 'deadlock') !== false)) {
                usleep(random_int(15000, 45000)); // Full Jitter 15-45ms
                continue;
            }

            // PENANGANAN 2: LOCK WAIT TIMEOUT 1205 -> Fail-Closed Segera (Jangan Bebani DB)
            if (str_contains($msg, '1205') || stripos($msg, 'lock wait timeout') !== false) {
                log_message('critical', "[QuotaLockWaitTimeout] Server DB saturasi: {$msg}");
                return ['allowed' => false, 'reason' => 'DB_TIMEOUT_FAIL_CLOSED'];
            }

            // PENANGANAN 3: KONEKSI PUTUS (2006/2013) -> Rekonsiliasi Ambigu
            if (str_contains($msg, '2006') || str_contains($msg, '2013') || stripos($msg, 'gone away') !== false) {
                return $this->handleAmbiguousCommit($clientMessageId, $workerToken, $today);
            }

            // PENANGANAN 4: ERROR UMUM -> FAIL-CLOSED MUTLAK
            log_message('error', "[AdminQuotaManager::acquireSlot] Gagal: {$msg}");
            return ['allowed' => false, 'reason' => 'DB_ERROR_FAIL_CLOSED'];
        }
    }

    return ['allowed' => false, 'reason' => 'DB_ERROR_EXHAUSTED'];
}
```

---

## 3. Protokol Rekonsiliasi Status saat Koneksi Terputus Pasca-Commit

### 3.1 Bahaya Melakukan Increment Membabi Buta (*No Blind Re-Increment Rule*):
Jika soket MySQL terputus saat PHP menunggu balasan `COMMIT`:
- Ada kemungkinan 50% bahwa server MySQL **telah berhasil melakukan commit** ke storage engine sebelum koneksi TCP putus.
- Jika PHP secara membabi buta menjalankan kueri `UPDATE used_count = used_count + 1` pada koneksi baru, kuota harian sekolah akan **terpotong 2 kali untuk 1 permintaan**.

### 3.2 Kueri Rekonsiliasi Bersih (`handleAmbiguousCommit`):
```php
private function handleAmbiguousCommit(string $clientMessageId, string $workerToken, string $today): array
{
    // Buka koneksi baru yang bersih
    $freshDb = \Config\Database::connect();

    // Periksa status baris idempotensi
    $query = $freshDb->query("
        SELECT quota_status, status 
        FROM ai_request_idempotency 
        WHERE client_message_id = ? 
          AND worker_token = ?
        LIMIT 1
    ", [$clientMessageId, $workerToken]);

    $row = $query->getRowArray();

    // Jika baris tercatat 'RESERVED', berarti COMMIT sebelumnya BERHASIL di server DB!
    if ($row && $row['quota_status'] === 'RESERVED') {
        log_message('info', "[QuotaReconciled] Commit ambigu terverifikasi BERHASIL untuk UUID: {$clientMessageId}");
        return [
            'allowed'    => true,
            'key_source' => 'admin_fallback',
            'quota_date' => $today,
            'resets_at'  => $today . ' 23:59:59 WIB'
        ];
    }

    // Jika baris belum 'RESERVED', berarti transaksi sebelumnya telah di-rollback oleh MySQL
    // Ambil kebijakan FAIL-CLOSED defensif demi keamanan keuangan sekolah
    log_message('warning', "[QuotaReconciled] Commit terbukti GAGAL/Rollback untuk UUID: {$clientMessageId}");
    return ['allowed' => false, 'reason' => 'DB_DISCONNECT_FAIL_CLOSED'];
}
```

---

## 4. Penanganan Duplicate Key 1062 pada Pembuatan Identitas

Ketika dua worker menerima request dengan `client_message_id` yang sama persis:
1. Worker 1 mengeksekusi `INSERT` $\rightarrow$ Sukses (`affected_rows == 1`).
2. Worker 2 mengeksekusi `INSERT` $\rightarrow$ Ditangkap oleh unique key `client_message_id` dan melempar **MySQL Error 1062 (Duplicate entry)**.
3. **Penanganan Worker 2:**
   - Worker 2 menangkap Exception 1062, memanggil `$db->transRollback()`.
   - Worker 2 membaca status baris eksisting:
     * Jika `status == 'IN_FLIGHT'`: Worker 2 langsung mengembalikan respons `HTTP 409 Conflict` ("Permintaan sedang diproses").
     * Jika `status == 'COMPLETED'`: Worker 2 mengembalikan respons tersimpan (*Safe Replay*).
   - Worker 2 **tidak pernah menjalankan alur reservasi kuota**.
