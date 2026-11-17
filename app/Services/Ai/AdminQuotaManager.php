<?php

namespace App\Services\Ai;

use App\Services\BaseService;
use Throwable;

/**
 * Pengelola Kuota Administrator AI (Admin Quota Manager)
 * 
 * Mengelola kuota agregat harian (20 request/hari) untuk API Key fallback Administrator.
 * Menerapkan:
 * 1. Reservasi atomik via UPDATE ... WHERE used_count < 20 pada InnoDB.
 * 2. Penanganan Deadlock (1213) dengan jitter 15-45ms dan 1x retry.
 * 3. Penanganan Lock Wait Timeout (1205) dengan fail-closed seketika.
 * 4. Refund atomik ganda (Dual-Exact-Match) tanpa GREATEST(), verifikasi used_count > 0.
 * 5. Rekonsiliasi ambiguous commit dengan koneksi segar dan pesimistik lock FOR UPDATE.
 */
class AdminQuotaManager extends BaseService
{
    public const DAILY_LIMIT = 20;
    public const TIMEZONE = 'Asia/Jakarta';

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Mendapatkan tanggal hari ini sesuai zona waktu resmi Asia/Jakarta
     */
    public function getTodayDate(): string
    {
        $dt = new \DateTime('now', new \DateTimeZone(self::TIMEZONE));
        return $dt->format('Y-m-d');
    }

    /**
     * Mendapatkan sisa kuota untuk tanggal tertentu (default hari ini)
     */
    public function getRemainingQuota(?string $date = null): int
    {
        $quotaDate = $date ?: $this->getTodayDate();
        $row = $this->db->table('ai_admin_quota_daily')
            ->where('quota_date', $quotaDate)
            ->get()
            ->getRowArray();

        $used = $row ? (int) $row['used_count'] : 0;
        return max(0, self::DAILY_LIMIT - $used);
    }

    /**
     * Mendapatkan rincian kuota harian
     */
    public function getDailyUsage(?string $date = null): array
    {
        $quotaDate = $date ?: $this->getTodayDate();
        $row = $this->db->table('ai_admin_quota_daily')
            ->where('quota_date', $quotaDate)
            ->get()
            ->getRowArray();

        $used = $row ? (int) $row['used_count'] : 0;
        return [
            'quota_date' => $quotaDate,
            'used_count' => $used,
            'limit'      => self::DAILY_LIMIT,
            'remaining'  => max(0, self::DAILY_LIMIT - $used),
        ];
    }

    /**
     * Mengakuisisi 1 slot kuota secara atomik
     * 
     * @param string $clientMessageId UUID pesan klien
     * @param string $workerToken Token unik worker yang memiliki request
     * @param string $keySource 'self' atau 'admin_fallback'
     * @param string|null $quotaDate Tanggal kuota spesifik (opsional)
     * @return array ['success' => bool, 'quota_status' => string, 'quota_date' => ?string, 'error' => ?string]
     */
    public function acquireSlot(
        string $clientMessageId,
        string $workerToken,
        string $keySource,
        ?string $quotaDate = null
    ): array {
        // Jika kunci pribadi (BYOK), kuota sekolah tidak dikurangi sama sekali
        if ($keySource !== 'admin_fallback') {
            return [
                'success'      => true,
                'key_source'   => $keySource,
                'quota_date'   => null,
                'quota_status' => 'NONE',
                'bypassed'     => true,
            ];
        }

        $targetDate = $quotaDate ?: $this->getTodayDate();
        $attempt = 0;
        $maxAttempts = 2; // 1 normal + 1 retry on deadlock 1213

        while ($attempt < $maxAttempts) {
            $attempt++;
            try {
                return $this->executeAcquireTransaction($clientMessageId, $workerToken, $targetDate);
            } catch (Throwable $e) {
                $errCode = (int) $e->getCode();
                $errMsg = $e->getMessage();

                // Cek apakah deadlock MySQL (Error 1213)
                $isDeadlock = ($errCode === 1213) || str_contains($errMsg, '1213') || str_contains($errMsg, 'Deadlock');
                // Cek apakah lock wait timeout (Error 1205)
                $isLockTimeout = ($errCode === 1205) || str_contains($errMsg, '1205') || str_contains($errMsg, 'Lock wait timeout');

                if ($isDeadlock && $attempt < $maxAttempts) {
                    // Jitter 15-45 ms
                    usleep(random_int(15000, 45000));
                    continue;
                }

                if ($isLockTimeout) {
                    log_message('error', "[AdminQuotaManager] Lock wait timeout (1205) on acquireSlot: {$errMsg}");
                    return [
                        'success'    => false,
                        'error'      => 'LOCK_TIMEOUT',
                        'quota_date' => $targetDate,
                    ];
                }

                // Cek kemungkinan ambiguous commit
                if (str_contains($errMsg, 'MySQL server has gone away') || str_contains($errMsg, 'Lost connection')) {
                    log_message('warning', "[AdminQuotaManager] Connection lost during acquireSlot, reconciling: {$clientMessageId}");
                    return $this->reconcileAmbiguousCommit($clientMessageId);
                }

                log_message('error', "[AdminQuotaManager] Error acquiring quota slot: {$errMsg}");
                return [
                    'success'    => false,
                    'error'      => 'TRANSACTION_FAILED',
                    'quota_date' => $targetDate,
                ];
            }
        }

        return [
            'success'    => false,
            'error'      => 'ACQUIRE_EXHAUSTED_RETRIES',
            'quota_date' => $targetDate,
        ];
    }

    /**
     * Eksekusi transaksi akuisisi slot InnoDB
     */
    protected function executeAcquireTransaction(string $clientMessageId, string $workerToken, string $targetDate): array
    {
        $this->db->transBegin();

        // 1. Lazy insert / ensure row exists for today
        $this->db->query(
            "INSERT INTO ai_admin_quota_daily (quota_date, used_count, created_at, updated_at) 
             VALUES (?, 0, NOW(), NOW()) 
             ON DUPLICATE KEY UPDATE id = id",
            [$targetDate]
        );

        // 2. Atomic increment kondisional (used_count < 20)
        $this->db->query(
            "UPDATE ai_admin_quota_daily 
             SET used_count = used_count + 1, updated_at = NOW() 
             WHERE quota_date = ? AND used_count < ?",
            [$targetDate, self::DAILY_LIMIT]
        );

        $affectedRows = $this->db->affectedRows();
        if ($affectedRows !== 1) {
            // Kuota harian sudah habis (>= 20)
            $this->db->transRollback();
            return [
                'success'      => false,
                'error'        => 'QUOTA_EXHAUSTED',
                'quota_date'   => $targetDate,
                'quota_status' => 'NONE',
            ];
        }

        // 3. Bind quota status ke baris idempotensi
        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET quota_date = ?, quota_status = 'RESERVED', updated_at = NOW() 
             WHERE client_message_id = ? AND worker_token = ? AND status = 'IN_FLIGHT'",
            [$targetDate, $clientMessageId, $workerToken]
        );

        $idempAffected = $this->db->affectedRows();
        if ($idempAffected !== 1) {
            // Baris idempotensi tidak cocok atau tidak dalam status IN_FLIGHT
            $this->db->transRollback();
            return [
                'success'      => false,
                'error'        => 'IDEMPOTENCY_ROW_MISMATCH',
                'quota_date'   => $targetDate,
                'quota_status' => 'NONE',
            ];
        }

        // 4. Commit transaksi
        $this->db->transCommit();

        return [
            'success'      => true,
            'key_source'   => 'admin_fallback',
            'quota_date'   => $targetDate,
            'quota_status' => 'RESERVED',
            'bypassed'     => false,
        ];
    }

    /**
     * Refund kuota atomik ganda (Dual-Exact-Match)
     * Hanya berlaku untuk request dengan status IN_FLIGHT, dispatch_started_at IS NULL,
     * quota_status = 'RESERVED', key_source = 'admin_fallback'.
     * 
     * @param string $clientMessageId UUID pesan klien
     * @param string|null $reason Alasan pembatalan/refund
     * @return bool True jika berhasil di-refund, False jika tidak memenuhi syarat
     */
    public function executeAtomicRefund(string $clientMessageId, ?string $reason = null): bool
    {
        try {
            $this->db->transBegin();

            // 1. Kunci dan validasi baris idempotensi
            $idempRow = $this->db->query(
                "SELECT id, key_source, quota_date, quota_status, status, dispatch_started_at, worker_token 
                 FROM ai_request_idempotency 
                 WHERE client_message_id = ? 
                 FOR UPDATE",
                [$clientMessageId]
            )->getRowArray();

            if (!$idempRow) {
                $this->db->transRollback();
                return false;
            }

            // Validasi kelayakan refund
            if (
                $idempRow['key_source'] !== 'admin_fallback' ||
                $idempRow['quota_status'] !== 'RESERVED' ||
                $idempRow['status'] !== 'IN_FLIGHT' ||
                $idempRow['dispatch_started_at'] !== null
            ) {
                $this->db->transRollback();
                return false;
            }

            $quotaDate = $idempRow['quota_date'];
            if (!$quotaDate) {
                $this->db->transRollback();
                return false;
            }

            // 2. Kunci dan verifikasi baris kuota harian (used_count > 0)
            $quotaRow = $this->db->query(
                "SELECT id, used_count 
                 FROM ai_admin_quota_daily 
                 WHERE quota_date = ? 
                 FOR UPDATE",
                [$quotaDate]
            )->getRowArray();

            if (!$quotaRow || (int)$quotaRow['used_count'] <= 0) {
                // Kuota tidak konsisten / sudah 0, tidak boleh dikurangi
                $this->db->transRollback();
                return false;
            }

            // 3. Update status idempotensi menjadi FAILED_BEFORE_HTTP_DISPATCH dan REFUNDED
            $this->db->query(
                "UPDATE ai_request_idempotency 
                 SET status = 'FAILED_BEFORE_HTTP_DISPATCH', quota_status = 'REFUNDED', updated_at = NOW() 
                 WHERE client_message_id = ? 
                   AND status = 'IN_FLIGHT' 
                   AND dispatch_started_at IS NULL 
                   AND quota_status = 'RESERVED'",
                [$clientMessageId]
            );
            $affIdemp = $this->db->affectedRows();

            // 4. Update decrement counter kuota harian secara presisi
            $this->db->query(
                "UPDATE ai_admin_quota_daily 
                 SET used_count = used_count - 1, updated_at = NOW() 
                 WHERE quota_date = ? AND used_count > 0",
                [$quotaDate]
            );
            $affQuota = $this->db->affectedRows();

            // 5. Dual-exact-match verification
            if ($affIdemp === 1 && $affQuota === 1) {
                $this->db->transCommit();
                log_message('info', "[AdminQuotaManager] Atomic refund SUCCESS for {$clientMessageId} on {$quotaDate}. Reason: " . ($reason ?: 'None'));
                return true;
            }

            // Jika salah satu gagal mengembalikan 1 baris, batalkan seluruhnya
            $this->db->transRollback();
            log_message('error', "[AdminQuotaManager] Atomic refund MISMATCH (idemp: {$affIdemp}, quota: {$affQuota}) for {$clientMessageId}, rolling back.");
            return false;
        } catch (Throwable $e) {
            $this->db->transRollback();
            log_message('error', "[AdminQuotaManager] Atomic refund exception for {$clientMessageId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Rekonsiliasi ambiguous commit dengan koneksi baru & pesimistik lock
     */
    public function reconcileAmbiguousCommit(string $clientMessageId): array
    {
        try {
            // Reconnect / establish fresh transaction
            $this->db->transBegin();

            $row = $this->db->query(
                "SELECT quota_status, quota_date, status 
                 FROM ai_request_idempotency 
                 WHERE client_message_id = ? 
                 FOR UPDATE",
                [$clientMessageId]
            )->getRowArray();

            $this->db->transCommit();

            if ($row && $row['quota_status'] === 'RESERVED') {
                return [
                    'success'      => true,
                    'key_source'   => 'admin_fallback',
                    'quota_date'   => $row['quota_date'],
                    'quota_status' => 'RESERVED',
                    'bypassed'     => false,
                    'reconciled'   => true,
                ];
            }

            return [
                'success'    => false,
                'error'      => 'RECONCILIATION_NOT_RESERVED',
                'quota_date' => $row['quota_date'] ?? null,
            ];
        } catch (Throwable $e) {
            log_message('error', "[AdminQuotaManager] Error during reconciliation for {$clientMessageId}: " . $e->getMessage());
            return [
                'success' => false,
                'error'   => 'RECONCILIATION_ERROR',
            ];
        }
    }
}
