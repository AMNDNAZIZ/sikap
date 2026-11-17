<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Ai\RequestIdempotencyManager;
use App\Services\Ai\AdminQuotaManager;
use Config\Database;

/**
 * Pengujian Unit RequestIdempotencyManager (Fase 1C)
 * 
 * Memvalidasi:
 * 1. Pembuatan worker token dan determinisme kalkulasi request hash.
 * 2. Pendaftaran klaim request pertama kali (action = CREATED, status = IN_FLIGHT).
 * 3. Safe Replay respon untuk status COMPLETED (kode HTTP 200).
 * 4. Penanganan payload yang kedaluwarsa / dipruning (kode HTTP 410 Gone).
 * 5. Perlindungan Anti-Enumerasi (Generic 404 jika UUID diakses user lain).
 * 6. Penolakan hash mismatch jika UUID sama dikirim dengan payload berbeda (kode 409).
 * 7. Gerbang Pre-Dispatch (Worker Fencing) dan pencegahan dispatch ganda.
 * 8. Penyimpanan respon sebelum ke klien (persist-before-respond) dan batas 64 KB.
 * 9. Pemulihan request terlantar (Orphan Recovery Case A vs Case B).
 */
final class RequestIdempotencyManagerTest extends CIUnitTestCase
{
    protected $db;
    protected RequestIdempotencyManager $idempManager;
    protected AdminQuotaManager $quotaManager;
    protected string $testDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('default');
        $this->idempManager = new RequestIdempotencyManager($this->db);
        $this->quotaManager = new AdminQuotaManager($this->db);
        $this->testDate = '2099-12-30';
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        parent::tearDown();
    }

    protected function cleanTestData(): void
    {
        $this->db->table('ai_request_idempotency')->like('client_message_id', 'test-idemp-')->delete();
        $this->db->table('ai_admin_quota_daily')->where('quota_date', $this->testDate)->delete();
    }

    public function testGenerateWorkerToken(): void
    {
        $token1 = $this->idempManager->generateWorkerToken();
        $token2 = $this->idempManager->generateWorkerToken();

        $this->assertSame(32, strlen($token1));
        $this->assertSame(32, strlen($token2));
        $this->assertNotSame($token1, $token2);
    }

    public function testComputeRequestHashIsDeterministic(): void
    {
        $hash1 = $this->idempManager->computeRequestHash(10, 'Buatkan kisi-kisi', 'Mapel Matematika');
        $hash2 = $this->idempManager->computeRequestHash(10, 'Buatkan kisi-kisi', 'Mapel Matematika');
        $hashDiff = $this->idempManager->computeRequestHash(10, 'Buatkan soal esai', 'Mapel Matematika');

        $this->assertSame(64, strlen($hash1));
        $this->assertSame($hash1, $hash2);
        $this->assertNotSame($hash1, $hashDiff);
    }

    public function testClaimNewRequest(): void
    {
        $uuid = 'test-idemp-new-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(5, 'Query perdana');

        $res = $this->idempManager->claimOrCreateRequest($uuid, 5, $hash, 'self', $worker);

        $this->assertSame('CREATED', $res['action']);
        $this->assertSame('IN_FLIGHT', $res['status']);
        $this->assertSame($worker, $res['worker_token']);
        $this->assertSame(200, $res['http_code']);
    }

    public function testSafeReplayOnCompletedRequest(): void
    {
        $uuid = 'test-idemp-completed-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(5, 'Query sukses');

        // Claim dan simpan respon
        $this->idempManager->claimOrCreateRequest($uuid, 5, $hash, 'self', $worker);
        $payload = ['reply' => 'Ini jawaban AI', 'meta' => ['tokens' => 150]];
        $persisted = $this->idempManager->persistResponseBeforeClient($uuid, $worker, $payload);
        $this->assertTrue($persisted);

        // Klien melakukan retry jaringan dengan UUID yang sama
        $replay = $this->idempManager->claimOrCreateRequest($uuid, 5, $hash, 'self');

        $this->assertSame('REPLAY', $replay['action']);
        $this->assertSame(200, $replay['http_code']);
        $this->assertTrue($replay['replayed']);
        $this->assertSame('Ini jawaban AI', $replay['payload']['reply']);
    }

    public function testExpiredPayloadReturns410Gone(): void
    {
        $uuid = 'test-idemp-expired-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(5, 'Query kedaluwarsa');

        // Insert row dengan status COMPLETED tetapi response_payload NULL (tombstone)
        $this->db->table('ai_request_idempotency')->insert([
            'client_message_id' => $uuid,
            'user_id'           => 5,
            'request_hash'      => $hash,
            'key_source'        => 'self',
            'quota_status'      => 'NONE',
            'status'            => 'COMPLETED',
            'response_payload'  => null,
            'worker_token'      => $worker,
            'created_at'        => date('Y-m-d H:i:s', strtotime('-10 days')),
            'updated_at'        => date('Y-m-d H:i:s', strtotime('-10 days')),
        ]);

        $replay = $this->idempManager->claimOrCreateRequest($uuid, 5, $hash, 'self');

        $this->assertSame('PAYLOAD_EXPIRED', $replay['action']);
        $this->assertSame(410, $replay['http_code']);
        $this->assertSame('IDEMPOTENCY_PAYLOAD_EXPIRED', $replay['error']);
    }

    public function testAntiEnumerationGeneric404OnDifferentUser(): void
    {
        $uuid = 'test-idemp-cross-user';
        $worker = $this->idempManager->generateWorkerToken();
        $hashUserA = $this->idempManager->computeRequestHash(1, 'Query User A');

        // User A membuat request
        $this->idempManager->claimOrCreateRequest($uuid, 1, $hashUserA, 'self', $worker);

        // User B mencoba mengakses UUID yang sama
        $hashUserB = $this->idempManager->computeRequestHash(2, 'Query User B');
        $res = $this->idempManager->claimOrCreateRequest($uuid, 2, $hashUserB, 'self');

        // WAJIB mengembalikan 404 Not Found generik, bukan 403 atau 409
        $this->assertSame('FORBIDDEN_USER_MISMATCH', $res['action']);
        $this->assertSame(404, $res['http_code']);
        $this->assertSame('Not Found', $res['error']);
    }

    public function testPayloadMismatchReturns409Conflict(): void
    {
        $uuid = 'test-idemp-mismatch-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hashOriginal = $this->idempManager->computeRequestHash(1, 'Query Asli');
        $hashModified = $this->idempManager->computeRequestHash(1, 'Query Berbeda');

        $this->idempManager->claimOrCreateRequest($uuid, 1, $hashOriginal, 'self', $worker);

        // UUID sama dikirim ulang oleh user yang sama tetapi isinya berbeda
        $res = $this->idempManager->claimOrCreateRequest($uuid, 1, $hashModified, 'self');

        $this->assertSame('REJECTED_HASH_MISMATCH', $res['action']);
        $this->assertSame(409, $res['http_code']);
        $this->assertSame('IDEMPOTENCY_KEY_REUSED_WITH_DIFFERENT_PAYLOAD', $res['error']);
    }

    public function testPreDispatchGateAndWorkerFencing(): void
    {
        $uuid = 'test-idemp-fence-1';
        $worker1 = $this->idempManager->generateWorkerToken();
        $worker2 = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'Fencing test');

        $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'self', $worker1);

        // Worker 2 (bukan pemilik) mencoba membuka pre-dispatch gate -> DITOLAK
        $denied = $this->idempManager->preDispatchGate($uuid, $worker2);
        $this->assertFalse($denied);

        // Worker 1 (pemilik sah) membuka pre-dispatch gate -> DITERIMA
        $approved = $this->idempManager->preDispatchGate($uuid, $worker1);
        $this->assertTrue($approved);

        // Upaya dispatch kedua oleh Worker 1 -> DITOLAK (karena dispatch_started_at sudah tidak NULL)
        $secondAttempt = $this->idempManager->preDispatchGate($uuid, $worker1);
        $this->assertFalse($secondAttempt);
    }

    public function testPayloadTruncationAt64KB(): void
    {
        $uuid = 'test-idemp-large-payload';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'Large payload query');

        $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'self', $worker);

        // Buat payload berukuran 70 KB
        $largeString = str_repeat('A', 70000);
        $payload = ['content' => $largeString, 'meta' => ['chars' => 70000]];

        $saved = $this->idempManager->persistResponseBeforeClient($uuid, $worker, $payload);
        $this->assertTrue($saved);

        // Ambil dari DB dan periksa ukurannya tidak melebihi 64 KB
        $row = $this->db->table('ai_request_idempotency')->where('client_message_id', $uuid)->get()->getRowArray();
        $this->assertLessThanOrEqual(RequestIdempotencyManager::MAX_PAYLOAD_SIZE, strlen($row['response_payload']));
        $this->assertStringContainsString('truncated', $row['response_payload']);
    }

    public function testOrphanRecoveryCaseA(): void
    {
        $uuid = 'test-idemp-orphan-a';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'Orphan Case A');

        // Simulasikan request yang mogok sebelum dispatch dengan lease sudah expired
        $this->db->table('ai_request_idempotency')->insert([
            'client_message_id'   => $uuid,
            'user_id'             => 1,
            'request_hash'        => $hash,
            'key_source'          => 'admin_fallback',
            'quota_date'          => $this->testDate,
            'quota_status'        => 'RESERVED',
            'status'              => 'IN_FLIGHT',
            'worker_token'        => $worker,
            'dispatch_started_at' => null,
            'lease_expires_at'    => date('Y-m-d H:i:s', time() - 30),
            'created_at'          => date('Y-m-d H:i:s', time() - 30),
            'updated_at'          => date('Y-m-d H:i:s', time() - 30),
        ]);

        $this->db->table('ai_admin_quota_daily')->insert([
            'quota_date' => $this->testDate,
            'used_count' => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        // Pulihkan orphan
        $recovery = $this->idempManager->recoverOrphan($uuid, $this->quotaManager);

        $this->assertTrue($recovery['recovered']);
        $this->assertSame('A_REFUNDED', $recovery['case']);

        // Pastikan kuota di-refund
        $this->assertSame(0, $this->quotaManager->getDailyUsage($this->testDate)['used_count']);
    }

    public function testOrphanRecoveryCaseB(): void
    {
        $uuid = 'test-idemp-orphan-b';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'Orphan Case B');

        // Simulasikan request yang sudah dispatch, tapi koneksi putus > 10 menit
        $elevenMinAgo = date('Y-m-d H:i:s', time() - 660);
        $this->db->table('ai_request_idempotency')->insert([
            'client_message_id'   => $uuid,
            'user_id'             => 1,
            'request_hash'        => $hash,
            'key_source'          => 'admin_fallback',
            'quota_date'          => $this->testDate,
            'quota_status'        => 'RESERVED',
            'status'              => 'IN_FLIGHT',
            'worker_token'        => $worker,
            'dispatch_started_at' => $elevenMinAgo,
            'lease_expires_at'    => $elevenMinAgo,
            'created_at'          => $elevenMinAgo,
            'updated_at'          => $elevenMinAgo,
        ]);

        $recovery = $this->idempManager->recoverOrphan($uuid, $this->quotaManager);

        $this->assertTrue($recovery['recovered']);
        $this->assertSame('B_DELIVERY_UNKNOWN', $recovery['case']);

        // Status di DB berubah menjadi DELIVERY_UNKNOWN dan kuota COMMITTED
        $row = $this->db->table('ai_request_idempotency')->where('client_message_id', $uuid)->get()->getRowArray();
        $this->assertSame('DELIVERY_UNKNOWN', $row['status']);
        $this->assertSame('COMMITTED', $row['quota_status']);
    }
}
