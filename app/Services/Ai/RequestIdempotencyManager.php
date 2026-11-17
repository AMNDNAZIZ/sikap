<?php

namespace App\Services\Ai;

use App\Services\BaseService;
use Throwable;

/**
 * Pengelola Idempotensi & Worker Fencing AI ESPATA (RequestIdempotencyManager)
 * 
 * Mengimplementasikan:
 * 1. Pencegahan duplikasi eksekusi request berbasis client_message_id (UUIDv4).
 * 2. Worker Fencing melalui worker_token dan validasi sewa (lease) pre-dispatch.
 * 3. Safe Replay respon untuk mencegah pemanggilan vendor AI berulang kali.
 * 4. Perlindungan Enumerasi Identitas (Generic 404 untuk UUID milik pengguna lain).
 * 5. Pemulihan Orphan terkontrol (Case A: pre-dispatch refund vs Case B: post-dispatch delivery_unknown).
 * 6. Batas ukuran penyimpanan payload maksimum 64 KB dan retensi tombstone permanen.
 */
class RequestIdempotencyManager extends BaseService
{
    public const LEASE_DURATION_SECONDS = 60;
    public const MAX_PAYLOAD_SIZE = 65536; // 64 KB
    public const RETENTION_PAYLOAD_DAYS = 7;

    public function __construct($db = null)
    {
        parent::__construct($db);
    }

    /**
     * Membuat worker token unik untuk memagari (fence) eksekusi proses
     */
    public function generateWorkerToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * Menghitung hash unik parameter request
     */
    public function computeRequestHash(int $userId, string $query, string $context = ''): string
    {
        return hash('sha256', $userId . '|' . trim($query) . '|' . trim($context));
    }

    /**
     * Mengklaim kepemilikan request atau mendeteksi entri idempotensi yang sudah ada
     * 
     * @param string $clientMessageId UUID pesan klien
     * @param int $userId ID pengguna pemohon
     * @param string $requestHash Hash konten request
     * @param string $keySource Sumber kunci ('self' atau 'admin_fallback')
     * @param string|null $workerToken Token unik worker yang mengeksekusi
     * @return array Status klaim, aksi yang harus diambil, dan kode HTTP
     */
    public function claimOrCreateRequest(
        string $clientMessageId,
        int $userId,
        string $requestHash,
        string $keySource,
        ?string $workerToken = null
    ): array {
        $token = $workerToken ?: $this->generateWorkerToken();

        // 1. Cek keberadaan baris idempotensi
        $existing = $this->db->table('ai_request_idempotency')
            ->where('client_message_id', $clientMessageId)
            ->get()
            ->getRowArray();

        if ($existing) {
            return $this->handleExistingRequest($existing, $userId, $requestHash);
        }

        // 2. Baris belum ada: lakukan pendaftaran klaim baru (INSERT)
        try {
            $this->db->query(
                "INSERT INTO ai_request_idempotency 
                 (client_message_id, user_id, request_hash, key_source, quota_status, status, worker_token, lease_expires_at, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, 'NONE', 'IN_FLIGHT', ?, NOW() + INTERVAL ? SECOND, NOW(), NOW())",
                [$clientMessageId, $userId, $requestHash, $keySource, $token, self::LEASE_DURATION_SECONDS]
            );

            return [
                'action'       => 'CREATED',
                'status'       => 'IN_FLIGHT',
                'worker_token' => $token,
                'key_source'   => $keySource,
                'http_code'    => 200,
            ];
        } catch (Throwable $e) {
            // Jika ada race condition dua thread INSERT bersamaan (Duplicate Key 1062)
            $existing = $this->db->table('ai_request_idempotency')
                ->where('client_message_id', $clientMessageId)
                ->get()
                ->getRowArray();

            if ($existing) {
                return $this->handleExistingRequest($existing, $userId, $requestHash);
            }

            log_message('error', "[RequestIdempotencyManager] Error inserting idempotency row: " . $e->getMessage());
            return [
                'action'    => 'ERROR',
                'http_code' => 500,
                'error'     => 'FAILED_TO_CLAIM_REQUEST',
            ];
        }
    }

    /**
     * Memproses baris idempotensi yang sudah terdaftar sebelumnya
     */
    protected function handleExistingRequest(array $existing, int $userId, string $requestHash): array
    {
        // A. Anti-Enumeration: Jika UUID milik user lain, jangan ungkap keberadaannya!
        if ((int) $existing['user_id'] !== (int) $userId) {
            return [
                'action'    => 'FORBIDDEN_USER_MISMATCH',
                'http_code' => 404,
                'error'     => 'Not Found',
                'message'   => 'The requested resource was not found.',
            ];
        }

        // B. Validasi Konsistensi Hash Request: Jika UUID sama dikirim dengan payload berbeda
        if ($existing['request_hash'] !== $requestHash) {
            return [
                'action'    => 'REJECTED_HASH_MISMATCH',
                'http_code' => 409,
                'error'     => 'IDEMPOTENCY_KEY_REUSED_WITH_DIFFERENT_PAYLOAD',
                'message'   => 'Conflict: client_message_id has already been used with different query parameters.',
            ];
        }

        // C. Status Terminal: COMPLETED
        if ($existing['status'] === 'COMPLETED') {
            if ($existing['response_payload'] !== null && $existing['response_payload'] !== '') {
                $decoded = json_decode($existing['response_payload'], true);
                return [
                    'action'    => 'REPLAY',
                    'http_code' => 200,
                    'status'    => 'COMPLETED',
                    'payload'   => $decoded ?: $existing['response_payload'],
                    'replayed'  => true,
                ];
            }

            // Payload sudah kedaluwarsa / dipruning (> 7 hari) tapi UUID tetap tersimpan
            return [
                'action'    => 'PAYLOAD_EXPIRED',
                'http_code' => 410,
                'error'     => 'IDEMPOTENCY_PAYLOAD_EXPIRED',
                'message'   => 'The cached response for this request has expired (>7 days) and cannot be replayed.',
            ];
        }

        // D. Status Terminal Lain (FAILED_BEFORE_HTTP_DISPATCH, TIMEOUT, PROVIDER_ERROR, DELIVERY_UNKNOWN)
        if (in_array($existing['status'], ['FAILED_BEFORE_HTTP_DISPATCH', 'TIMEOUT', 'PROVIDER_ERROR', 'DELIVERY_UNKNOWN'], true)) {
            return [
                'action'    => 'TERMINAL_FAILURE',
                'http_code' => 200,
                'status'    => $existing['status'],
                'replayed'  => true,
                'message'   => 'Request previously completed with status: ' . $existing['status'],
            ];
        }

        // E. Status Aktif: IN_FLIGHT
        if ($existing['status'] === 'IN_FLIGHT') {
            $leaseExpires = !empty($existing['lease_expires_at']) ? strtotime($existing['lease_expires_at']) : 0;
            $now = time();

            // Jika masa sewa (lease) masih aktif, proses sedang dikerjakan oleh worker lain
            if ($leaseExpires > $now) {
                return [
                    'action'    => 'IN_FLIGHT',
                    'http_code' => 409,
                    'error'     => 'REQUEST_IN_FLIGHT',
                    'message'   => 'A request with this ID is currently in progress.',
                ];
            }

            // Masa sewa habis -> Terdeteksi sebagai Orphan
            return [
                'action'             => 'ORPHAN_DETECTED',
                'http_code'          => 409,
                'row'                => $existing,
                'worker_token'       => $existing['worker_token'],
                'dispatch_started_at'=> $existing['dispatch_started_at'],
            ];
        }

        return [
            'action'    => 'UNKNOWN_STATUS',
            'http_code' => 500,
            'status'    => $existing['status'],
        ];
    }

    /**
     * Gerbang Validasi Pre-Dispatch (Pre-Dispatch Gate)
     * Memastikan worker yang berhak mengirim HTTP request ke vendor adalah pemegang sah lease aktif.
     * Jika affected_rows == 0, worker WAJIB BERHENTI dan tidak boleh memanggil vendor.
     * 
     * @param string $clientMessageId UUID pesan klien
     * @param string $workerToken Token unik worker
     * @return bool True jika diizinkan dispatch, False jika kehilangan kepemilikan
     */
    public function preDispatchGate(string $clientMessageId, string $workerToken): bool
    {
        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET dispatch_started_at = NOW(), 
                 lease_expires_at = NOW() + INTERVAL ? SECOND, 
                 updated_at = NOW() 
             WHERE client_message_id = ? 
               AND worker_token = ? 
               AND status = 'IN_FLIGHT' 
               AND dispatch_started_at IS NULL 
               AND lease_expires_at > NOW()",
            [self::LEASE_DURATION_SECONDS, $clientMessageId, $workerToken]
        );

        $affected = $this->db->affectedRows();
        if ($affected === 1) {
            return true;
        }

        log_message('warning', "[RequestIdempotencyManager] Pre-dispatch gate FAILED for {$clientMessageId} with worker {$workerToken}. Worker fenced out.");
        return false;
    }

    /**
     * Menyimpan respon AI secara persisten sebelum mengembalikannya kepada pengguna
     * Memenuhi kaidah persist-before-respond.
     * 
     * @param string $clientMessageId UUID pesan klien
     * @param string $workerToken Token unik worker
     * @param array|string $payload Data respon
     * @param string $finalStatus Status akhir (default: COMPLETED)
     * @param string $finalQuotaStatus Status kuota akhir (COMMITTED)
     * @return bool True jika sukses tersimpan
     */
    public function persistResponseBeforeClient(
        string $clientMessageId,
        string $workerToken,
        $payload,
        string $finalStatus = 'COMPLETED',
        string $finalQuotaStatus = 'COMMITTED'
    ): bool {
        $encoded = is_string($payload) ? $payload : json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Batasi ukuran payload maksimum 64 KB
        if (strlen($encoded) > self::MAX_PAYLOAD_SIZE) {
            $truncatedPayload = [
                'warning' => 'Response payload exceeded 64KB limit and was truncated for storage.',
                'meta'    => is_array($payload) ? ($payload['meta'] ?? []) : [],
            ];
            $encoded = json_encode($truncatedPayload, JSON_UNESCAPED_UNICODE);
        }

        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET status = ?, 
                 quota_status = ?, 
                 response_payload = ?, 
                 updated_at = NOW() 
             WHERE client_message_id = ? 
               AND worker_token = ? 
               AND (status = 'IN_FLIGHT' OR status = 'PROVIDER_ERROR')",
            [$finalStatus, $finalQuotaStatus, $encoded, $clientMessageId, $workerToken]
        );

        $affected = $this->db->affectedRows();
        return ($affected === 1);
    }

    /**
     * Mencatat kegagalan terminal (TIMEOUT, PROVIDER_ERROR, DELIVERY_UNKNOWN, FAILED_BEFORE_HTTP_DISPATCH)
     */
    public function recordFailure(
        string $clientMessageId,
        string $workerToken,
        string $terminalStatus,
        ?string $quotaStatus = null
    ): bool {
        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET status = ?, 
                 quota_status = COALESCE(?, quota_status), 
                 updated_at = NOW() 
             WHERE client_message_id = ? 
               AND worker_token = ? 
               AND status = 'IN_FLIGHT'",
            [$terminalStatus, $quotaStatus, $clientMessageId, $workerToken]
        );

        return ($this->db->affectedRows() === 1);
    }

    /**
     * Memperpanjang masa sewa worker (Heartbeat lease renewal)
     */
    public function renewLease(string $clientMessageId, string $workerToken, int $extraSeconds = 60): bool
    {
        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET lease_expires_at = NOW() + INTERVAL ? SECOND, 
                 updated_at = NOW() 
             WHERE client_message_id = ? 
               AND worker_token = ? 
               AND status = 'IN_FLIGHT' 
               AND lease_expires_at > NOW()",
            [$extraSeconds, $clientMessageId, $workerToken]
        );

        return ($this->db->affectedRows() === 1);
    }

    /**
     * Pemulihan request yang terlantar (Orphan Recovery)
     * 
     * Kasus A (Pre-Dispatch Stall):
     * - dispatch_started_at IS NULL AND lease_expires_at <= NOW() AND status = 'IN_FLIGHT'
     * - Lakukan refund kuota atomik jika kuota sempat di-reserve
     * - Ubah status menjadi FAILED_BEFORE_HTTP_DISPATCH
     * 
     * Kasus B (Post-Dispatch Stall):
     * - dispatch_started_at IS NOT NULL AND lease_expires_at <= NOW() AND updated_at < NOW() - INTERVAL 10 MINUTE AND status = 'IN_FLIGHT'
     * - Ubah status menjadi DELIVERY_UNKNOWN, kuota COMMITTED (TIDAK ADA REFUND karena vendor sudah dihubungi)
     */
    public function recoverOrphan(string $clientMessageId, AdminQuotaManager $quotaManager): array
    {
        $row = $this->db->table('ai_request_idempotency')
            ->where('client_message_id', $clientMessageId)
            ->get()
            ->getRowArray();

        if (!$row || $row['status'] !== 'IN_FLIGHT') {
            return [
                'recovered' => false,
                'reason'    => 'ROW_NOT_IN_FLIGHT',
            ];
        }

        // KASUS A: Pre-Dispatch Stall
        if ($row['dispatch_started_at'] === null) {
            $leaseExpired = !empty($row['lease_expires_at']) && (strtotime($row['lease_expires_at']) <= time());
            if ($leaseExpired) {
                // Eksekusi refund atomik jika reservasi ada
                if ($row['quota_status'] === 'RESERVED' && $row['key_source'] === 'admin_fallback') {
                    $refundOk = $quotaManager->executeAtomicRefund($clientMessageId, 'Orphan Recovery Case A (Pre-dispatch stall)');
                    return [
                        'recovered' => true,
                        'case'      => 'A_REFUNDED',
                        'refund_ok' => $refundOk,
                    ];
                }

                // Update ke FAILED_BEFORE_HTTP_DISPATCH jika bukan admin_fallback atau tidak di-reserve
                $this->db->query(
                    "UPDATE ai_request_idempotency 
                     SET status = 'FAILED_BEFORE_HTTP_DISPATCH', updated_at = NOW() 
                     WHERE client_message_id = ? 
                       AND status = 'IN_FLIGHT' 
                       AND dispatch_started_at IS NULL 
                       AND lease_expires_at <= NOW()",
                    [$clientMessageId]
                );

                return [
                    'recovered' => true,
                    'case'      => 'A_TRANSITIONED',
                ];
            }
        }

        // KASUS B: Post-Dispatch Stall (> 10 menit setelah dispatch)
        if ($row['dispatch_started_at'] !== null) {
            $updatedAt = !empty($row['updated_at']) ? strtotime($row['updated_at']) : 0;
            $tenMinAgo = time() - 600;

            if ($updatedAt < $tenMinAgo) {
                $this->db->query(
                    "UPDATE ai_request_idempotency 
                     SET status = 'DELIVERY_UNKNOWN', 
                         quota_status = 'COMMITTED', 
                         updated_at = NOW() 
                     WHERE client_message_id = ? 
                       AND status = 'IN_FLIGHT' 
                       AND dispatch_started_at IS NOT NULL 
                       AND lease_expires_at <= NOW() 
                       AND updated_at < NOW() - INTERVAL 10 MINUTE",
                    [$clientMessageId]
                );

                $aff = $this->db->affectedRows();
                return [
                    'recovered' => ($aff === 1),
                    'case'      => 'B_DELIVERY_UNKNOWN',
                ];
            }
        }

        return [
            'recovered' => false,
            'reason'    => 'CONDITIONS_NOT_MET',
        ];
    }

    /**
     * Menghapus payload lama (> 7 hari) untuk menghemat ruang disk,
     * tetapi tetap mempertahankan UUID dan status terminal (Tombstone permanen).
     */
    public function pruneOldPayloads(int $days = self::RETENTION_PAYLOAD_DAYS): int
    {
        $this->db->query(
            "UPDATE ai_request_idempotency 
             SET response_payload = NULL, updated_at = NOW() 
             WHERE created_at < NOW() - INTERVAL ? DAY 
               AND response_payload IS NOT NULL",
            [$days]
        );

        return $this->db->affectedRows();
    }
}
