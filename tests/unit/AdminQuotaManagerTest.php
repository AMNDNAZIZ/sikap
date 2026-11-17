<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Services\Ai\AdminQuotaManager;
use App\Services\Ai\RequestIdempotencyManager;
use Config\Database;

/**
 * Pengujian Unit AdminQuotaManager (Fase 1C)
 * 
 * Memvalidasi:
 * 1. Kuota agregat harian 20 request/hari pada zona waktu Asia/Jakarta.
 * 2. Bypass kuota untuk permintaan Bring Your Own Key (key_source = 'self').
 * 3. Reservasi atomik slot kuota untuk fallback admin (key_source = 'admin_fallback').
 * 4. Penolakan ketika kuota habis (QUOTA_EXHAUSTED).
 * 5. Refund atomik ganda (Dual-Exact-Match) yang hanya berlaku sebelum HTTP dispatch.
 * 6. Penolakan refund jika HTTP dispatch sudah berjalan atau status tidak memenuhi syarat.
 */
final class AdminQuotaManagerTest extends CIUnitTestCase
{
    protected $db;
    protected AdminQuotaManager $quotaManager;
    protected RequestIdempotencyManager $idempManager;
    protected string $testDate;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('default');
        $this->quotaManager = new AdminQuotaManager($this->db);
        $this->idempManager = new RequestIdempotencyManager($this->db);
        // Gunakan tanggal uji khusus agar tidak mengganggu data produksi
        $this->testDate = '2099-12-31';
        $this->cleanTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanTestData();
        parent::tearDown();
    }

    protected function cleanTestData(): void
    {
        $this->db->table('ai_request_idempotency')->like('client_message_id', 'test-quota-')->delete();
        $this->db->table('ai_admin_quota_daily')->where('quota_date', $this->testDate)->delete();
    }

    public function testGetTodayDateFormat(): void
    {
        $today = $this->quotaManager->getTodayDate();
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $today);
    }

    public function testByokBypassesQuota(): void
    {
        $uuid = 'test-quota-byok-1';
        $worker = $this->idempManager->generateWorkerToken();

        $result = $this->quotaManager->acquireSlot($uuid, $worker, 'self', $this->testDate);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['bypassed']);
        $this->assertSame('NONE', $result['quota_status']);
        $this->assertNull($result['quota_date']);

        // Pastikan tidak ada baris kuota yang dibuat untuk tanggal uji
        $row = $this->db->table('ai_admin_quota_daily')->where('quota_date', $this->testDate)->get()->getRowArray();
        $this->assertNull($row);
    }

    public function testAdminFallbackAcquiresSlotAtomically(): void
    {
        $uuid = 'test-quota-admin-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'halo');

        // Daftarkan request di tabel idempotensi terlebih dahulu
        $claim = $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'admin_fallback', $worker);
        $this->assertSame('CREATED', $claim['action']);

        // Akuisisi slot
        $result = $this->quotaManager->acquireSlot($uuid, $worker, 'admin_fallback', $this->testDate);

        $this->assertTrue($result['success']);
        $this->assertFalse($result['bypassed']);
        $this->assertSame('RESERVED', $result['quota_status']);
        $this->assertSame($this->testDate, $result['quota_date']);

        // Verifikasi counter di database bernilai 1
        $usage = $this->quotaManager->getDailyUsage($this->testDate);
        $this->assertSame(1, $usage['used_count']);
        $this->assertSame(19, $usage['remaining']);

        // Verifikasi status pada tabel idempotensi
        $idemp = $this->db->table('ai_request_idempotency')->where('client_message_id', $uuid)->get()->getRowArray();
        $this->assertSame('RESERVED', $idemp['quota_status']);
        $this->assertSame($this->testDate, $idemp['quota_date']);
    }

    public function testQuotaExhaustionAtTwenty(): void
    {
        // Set kuota ke 20 langsung di DB
        $this->db->table('ai_admin_quota_daily')->insert([
            'quota_date' => $this->testDate,
            'used_count' => 20,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        $uuid = 'test-quota-admin-overflow';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'overflow');
        $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'admin_fallback', $worker);

        // Coba akuisisi slot ke-21
        $result = $this->quotaManager->acquireSlot($uuid, $worker, 'admin_fallback', $this->testDate);

        $this->assertFalse($result['success']);
        $this->assertSame('QUOTA_EXHAUSTED', $result['error']);

        // Counter tetap 20
        $usage = $this->quotaManager->getDailyUsage($this->testDate);
        $this->assertSame(20, $usage['used_count']);
        $this->assertSame(0, $usage['remaining']);
    }

    public function testExecuteAtomicRefundSuccessBeforeDispatch(): void
    {
        $uuid = 'test-quota-refund-1';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'refund query');

        $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'admin_fallback', $worker);
        $this->quotaManager->acquireSlot($uuid, $worker, 'admin_fallback', $this->testDate);

        $this->assertSame(1, $this->quotaManager->getDailyUsage($this->testDate)['used_count']);

        // Eksekusi refund atomik sebelum HTTP dispatch dimulai
        $refundOk = $this->quotaManager->executeAtomicRefund($uuid, 'Uji coba refund pra-dispatch');

        $this->assertTrue($refundOk);

        // Counter berkurang kembali menjadi 0
        $this->assertSame(0, $this->quotaManager->getDailyUsage($this->testDate)['used_count']);
        $this->assertSame(20, $this->quotaManager->getDailyUsage($this->testDate)['remaining']);

        // Status di tabel idempotensi berubah menjadi FAILED_BEFORE_HTTP_DISPATCH dan REFUNDED
        $idemp = $this->db->table('ai_request_idempotency')->where('client_message_id', $uuid)->get()->getRowArray();
        $this->assertSame('FAILED_BEFORE_HTTP_DISPATCH', $idemp['status']);
        $this->assertSame('REFUNDED', $idemp['quota_status']);
    }

    public function testExecuteAtomicRefundRejectedAfterDispatchStarted(): void
    {
        $uuid = 'test-quota-refund-dispatched';
        $worker = $this->idempManager->generateWorkerToken();
        $hash = $this->idempManager->computeRequestHash(1, 'query dispatched');

        $this->idempManager->claimOrCreateRequest($uuid, 1, $hash, 'admin_fallback', $worker);
        $this->quotaManager->acquireSlot($uuid, $worker, 'admin_fallback', $this->testDate);

        // Buka gerbang pre-dispatch (dispatch_started_at terisi)
        $dispatchOk = $this->idempManager->preDispatchGate($uuid, $worker);
        $this->assertTrue($dispatchOk);

        // Upaya refund WAJIB DITOLAK karena request sudah dikirim ke vendor
        $refundOk = $this->quotaManager->executeAtomicRefund($uuid, 'Upaya refund setelah dispatch');

        $this->assertFalse($refundOk);

        // Counter kuota TIDAK BOLEH berkurang (tetap 1)
        $this->assertSame(1, $this->quotaManager->getDailyUsage($this->testDate)['used_count']);
    }
}
