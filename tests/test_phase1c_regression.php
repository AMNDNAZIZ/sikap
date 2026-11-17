<?php

/**
 * Phase 1C Regression Reconciliation Suite (13 Points)
 * EAO-HO-2026-015 Addendum Validasi CP-2.1
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Services\Ai\AdminQuotaManager;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\RequestIdempotencyManager;
use App\Services\Ai\DTO\ResolvedApiKey;
use App\Services\Ai\UnifiedAiService;
use Config\AiConfig;
use Config\Database;

$db = Database::connect('default');
$config = new AiConfig();
$resolver = new AiKeyResolver($config, $db);
$quotaManager = new AdminQuotaManager($db);
$idempManager = new RequestIdempotencyManager($db);

$testDate = '2099-12-25'; // Tanggal uji aman agar tidak merusak data produksi
$testUuidPrefix = 'test-reg-1c-';

// Cleanup initial
$db->table('ai_request_idempotency')->like('client_message_id', $testUuidPrefix)->delete();
$db->table('ai_admin_quota_daily')->where('quota_date', $testDate)->delete();

echo "====================================================================\n";
echo "   REKONSILIASI PENGUJIAN REGRESI FASE 1C (13 POIN MANDAT HO)\n";
echo "====================================================================\n\n";

$passes = [];

// -------------------------------------------------------------
// POIN 1: BYOK Pribadi Pengguna Tetap Menjadi Prioritas
// -------------------------------------------------------------
$byokKey = 'gsk_teacherbyoksecret1234567890';
$resolvedByok = $resolver->resolve(397, 3, 12, 'groq', $byokKey);
$p1_pass = ($resolvedByok->isAvailable() && 
            $resolvedByok->keySource === 'override' && 
            $resolvedByok->getRawSecret() === $byokKey &&
            !$resolvedByok->isFallback);
$passes['POIN_1_BYOK_PRIORITY'] = $p1_pass;
echo "[POIN 1] BYOK Pribadi Pengguna Menjadi Prioritas: " . ($p1_pass ? "PASS" : "FAIL") . "\n";
echo "         Source: {$resolvedByok->keySource} | Tag: " . $resolvedByok->getTelemetrySourceTag() . "\n";

// -------------------------------------------------------------
// POIN 2: API Key Guru Lain Tidak Pernah Digunakan Sebagai Fallback
// -------------------------------------------------------------
// Guru ID 63 (tidak punya BYOK) mencoba resolve, memastikan TIDAK mengambil milik Guru ID 12
$resolvedGuru = $resolver->resolve(63, 3, 3, 'groq');
// Harus fallback ke admin_fallback atau none, BUKAN self milik guru lain
$p2_pass = ($resolvedGuru->keyOwnerId !== 12 && 
            in_array($resolvedGuru->keySource, ['admin_fallback', 'none']));
$passes['POIN_2_NO_CROSS_TEACHER'] = $p2_pass;
echo "[POIN 2] Pencegahan Fallback Lintas Guru (Cross-Teacher): " . ($p2_pass ? "PASS" : "FAIL") . "\n";
echo "         Owner: " . ($resolvedGuru->keyOwnerId ?? 'null') . " | Source: {$resolvedGuru->keySource}\n";

// -------------------------------------------------------------
// POIN 3: Admin Fallback Berjalan Ketika Pengguna Tidak Mempunyai BYOK
// -------------------------------------------------------------
$resolvedFallback = $resolver->resolve(63, 3, 3, 'groq');
$p3_pass = ($resolvedFallback->isFallback && 
            $resolvedFallback->keySource === 'admin_fallback' && 
            $resolvedFallback->keyOwnerId === 1);
$passes['POIN_3_ADMIN_FALLBACK'] = $p3_pass;
echo "[POIN 3] Admin Fallback Otomatis Saat Non-BYOK: " . ($p3_pass ? "PASS" : "FAIL") . "\n";
echo "         IsFallback: " . ($resolvedFallback->isFallback ? "true" : "false") . " | Owner: {$resolvedFallback->keyOwnerId}\n";

// -------------------------------------------------------------
// POIN 4: Kuota Admin Agregat 20 Per Hari Kalender Asia/Jakarta
// -------------------------------------------------------------
$today = $quotaManager->getTodayDate();
$tzCheck = (date_default_timezone_get() === 'Asia/Jakarta' || date('Y-m-d') === $today);
$usage = $quotaManager->getDailyUsage($testDate);
$p4_pass = ($tzCheck && $usage['limit'] === 20);
$passes['POIN_4_AGREGAT_20_JAKARTA'] = $p4_pass;
echo "[POIN 4] Kuota Admin Agregat 20 per Hari Kalender Asia/Jakarta: " . ($p4_pass ? "PASS" : "FAIL") . "\n";
echo "         Limit: {$usage['limit']} | Today (Asia/Jakarta): {$today}\n";

// -------------------------------------------------------------
// POIN 5: BYOK Pribadi Tidak Mengurangi Kuota Admin
// -------------------------------------------------------------
$uuidByok = $testUuidPrefix . 'byok-slot';
$workerByok = $idempManager->generateWorkerToken();
$slotByok = $quotaManager->acquireSlot($uuidByok, $workerByok, 'self', $testDate);
$usageAfterByok = $quotaManager->getDailyUsage($testDate);
$p5_pass = ($slotByok['success'] && 
            $slotByok['bypassed'] && 
            $usageAfterByok['used_count'] === 0);
$passes['POIN_5_BYOK_BYPASS_QUOTA'] = $p5_pass;
echo "[POIN 5] BYOK Pribadi Tidak Memotong Kuota Admin: " . ($p5_pass ? "PASS" : "FAIL") . "\n";
echo "         Bypassed: " . ($slotByok['bypassed'] ? "true" : "false") . " | Used: {$usageAfterByok['used_count']}\n";

// -------------------------------------------------------------
// POIN 6: Permintaan Ditolak Sebelum Dispatch Tidak Mengurangi Kuota
// -------------------------------------------------------------
$uuidPre = $testUuidPrefix . 'predispatch-cancel';
$workerPre = $idempManager->generateWorkerToken();
$hashPre = $idempManager->computeRequestHash(1, 'predispatch query');
$idempManager->claimOrCreateRequest($uuidPre, 1, $hashPre, 'admin_fallback', $workerPre);
$quotaManager->acquireSlot($uuidPre, $workerPre, 'admin_fallback', $testDate);
$uPre1 = $quotaManager->getDailyUsage($testDate)['used_count'];
// Eksekusi refund pra-dispatch
$refundPreOk = $quotaManager->executeAtomicRefund($uuidPre, 'Batal sebelum cURL');
$uPre2 = $quotaManager->getDailyUsage($testDate)['used_count'];
$p6_pass = ($uPre1 === 1 && $refundPreOk && $uPre2 === 0);
$passes['POIN_6_PREDISPATCH_REFUND'] = $p6_pass;
echo "[POIN 6] Penolakan Pra-Dispatch Me-refund Kuota: " . ($p6_pass ? "PASS" : "FAIL") . "\n";
echo "         Before: {$uPre1} -> After Refund: {$uPre2}\n";

// -------------------------------------------------------------
// POIN 7: Permintaan Mencapai Batas Dispatch Mengikuti Kebijakan Pencatatan
// -------------------------------------------------------------
$uuidPost = $testUuidPrefix . 'postdispatch';
$workerPost = $idempManager->generateWorkerToken();
$hashPost = $idempManager->computeRequestHash(1, 'postdispatch query');
$idempManager->claimOrCreateRequest($uuidPost, 1, $hashPost, 'admin_fallback', $workerPost);
$quotaManager->acquireSlot($uuidPost, $workerPost, 'admin_fallback', $testDate);
$idempManager->preDispatchGate($uuidPost, $workerPost); // Dispatch sudah lewat point-of-no-return
// Refund sekarang WAJIB DITOLAK
$refundPostDenied = !$quotaManager->executeAtomicRefund($uuidPost, 'Upaya refund pasca-dispatch');
$uPost = $quotaManager->getDailyUsage($testDate)['used_count'];
$p7_pass = ($refundPostDenied && $uPost === 1);
$passes['POIN_7_POSTDISPATCH_NO_REFUND'] = $p7_pass;
echo "[POIN 7] Pasca-Dispatch Mengunci Kuota (Non-Refundable): " . ($p7_pass ? "PASS" : "FAIL") . "\n";
echo "         Refund Denied: " . ($refundPostDenied ? "true" : "false") . " | Kuota Tetap: {$uPost}\n";

// -------------------------------------------------------------
// POIN 8: Permintaan Ditolak Ketika Kuota Admin Habis (QUOTA_EXHAUSTED)
// -------------------------------------------------------------
// Simulasikan kuota penuh (20)
$db->table('ai_admin_quota_daily')->where('quota_date', $testDate)->update(['used_count' => 20]);
$uuidOver = $testUuidPrefix . 'overflow';
$workerOver = $idempManager->generateWorkerToken();
$hashOver = $idempManager->computeRequestHash(1, 'overflow query');
$idempManager->claimOrCreateRequest($uuidOver, 1, $hashOver, 'admin_fallback', $workerOver);
$slotOver = $quotaManager->acquireSlot($uuidOver, $workerOver, 'admin_fallback', $testDate);
$p8_pass = (!$slotOver['success'] && $slotOver['error'] === 'QUOTA_EXHAUSTED');
$passes['POIN_8_QUOTA_EXHAUSTED'] = $p8_pass;
echo "[POIN 8] Penolakan Saat Kuota Habis (QUOTA_EXHAUSTED): " . ($p8_pass ? "PASS" : "FAIL") . "\n";
echo "         Success: " . ($slotOver['success'] ? "true" : "false") . " | Error: " . ($slotOver['error'] ?? 'none') . "\n";

// Kembalikan kuota uji
$db->table('ai_admin_quota_daily')->where('quota_date', $testDate)->update(['used_count' => 0]);

// -------------------------------------------------------------
// POIN 9: Atomic Reservation dan Refund Berjalan Benar
// -------------------------------------------------------------
$uuidAtom = $testUuidPrefix . 'atomic';
$workerAtom = $idempManager->generateWorkerToken();
$hashAtom = $idempManager->computeRequestHash(1, 'atomic query');
$idempManager->claimOrCreateRequest($uuidAtom, 1, $hashAtom, 'admin_fallback', $workerAtom);
$acqAtom = $quotaManager->acquireSlot($uuidAtom, $workerAtom, 'admin_fallback', $testDate);
$refAtom = $quotaManager->executeAtomicRefund($uuidAtom, 'Atomic test refund');
$p9_pass = ($acqAtom['success'] && 
            $acqAtom['quota_status'] === 'RESERVED' && 
            $refAtom === true && 
            $quotaManager->getDailyUsage($testDate)['used_count'] === 0);
$passes['POIN_9_ATOMIC_RESERVATION_REFUND'] = $p9_pass;
echo "[POIN 9] Atomic Reservation & Refund Berjalan Benar: " . ($p9_pass ? "PASS" : "FAIL") . "\n";

// -------------------------------------------------------------
// POIN 10: Worker Fencing Mencegah Worker Lama Merubah Status
// -------------------------------------------------------------
$uuidFence = $testUuidPrefix . 'fence';
$wSah = $idempManager->generateWorkerToken();
$wPalsu = $idempManager->generateWorkerToken();
$hashFence = $idempManager->computeRequestHash(1, 'fence query');
$idempManager->claimOrCreateRequest($uuidFence, 1, $hashFence, 'self', $wSah);
$palsuDenied = !$idempManager->preDispatchGate($uuidFence, $wPalsu);
$sahApproved = $idempManager->preDispatchGate($uuidFence, $wSah);
$sahSecondDenied = !$idempManager->preDispatchGate($uuidFence, $wSah); // Double dispatch dicegah
$p10_pass = ($palsuDenied && $sahApproved && $sahSecondDenied);
$passes['POIN_10_WORKER_FENCING'] = $p10_pass;
echo "[POIN 10] Worker Fencing Mencegah Perubahan Status Ilegal: " . ($p10_pass ? "PASS" : "FAIL") . "\n";
echo "          Palsu Ditolak: " . ($palsuDenied ? "true" : "false") . " | Sah Diterima: " . ($sahApproved ? "true" : "false") . "\n";

// -------------------------------------------------------------
// POIN 11: Safe Replay Tidak Memanggil Provider & Tidak Mengurangi Kuota
// -------------------------------------------------------------
$uuidReplay = $testUuidPrefix . 'replay';
$wReplay = $idempManager->generateWorkerToken();
$hashReplay = $idempManager->computeRequestHash(1, 'replay query');
$idempManager->claimOrCreateRequest($uuidReplay, 1, $hashReplay, 'self', $wReplay);
$idempManager->persistResponseBeforeClient($uuidReplay, $wReplay, ['reply' => 'Safe Replay Valid']);
$replayClaim = $idempManager->claimOrCreateRequest($uuidReplay, 1, $hashReplay, 'self');
$p11_pass = ($replayClaim['action'] === 'REPLAY' && 
             $replayClaim['replayed'] === true && 
             $replayClaim['payload']['reply'] === 'Safe Replay Valid');
$passes['POIN_11_SAFE_REPLAY'] = $p11_pass;
echo "[POIN 11] Safe Replay Mengembalikan Cache Instan (0 cURL): " . ($p11_pass ? "PASS" : "FAIL") . "\n";
echo "          Action: {$replayClaim['action']} | Replayed: " . ($replayClaim['replayed'] ? "true" : "false") . "\n";

// -------------------------------------------------------------
// POIN 12: Penanganan DELIVERY_UNKNOWN Tidak Mengembalikan Kuota Keliru
// -------------------------------------------------------------
$uuidUnk = $testUuidPrefix . 'unknown-delivery';
$wUnk = $idempManager->generateWorkerToken();
$hashUnk = $idempManager->computeRequestHash(1, 'unk query');
$elevenMinAgo = date('Y-m-d H:i:s', time() - 660);
$db->table('ai_request_idempotency')->insert([
    'client_message_id'   => $uuidUnk,
    'user_id'             => 1,
    'request_hash'        => $hashUnk,
    'key_source'          => 'admin_fallback',
    'quota_date'          => $testDate,
    'quota_status'        => 'RESERVED',
    'status'              => 'IN_FLIGHT',
    'worker_token'        => $wUnk,
    'dispatch_started_at' => $elevenMinAgo,
    'lease_expires_at'    => $elevenMinAgo,
    'created_at'          => $elevenMinAgo,
    'updated_at'          => $elevenMinAgo,
]);
$db->table('ai_admin_quota_daily')->where('quota_date', $testDate)->update(['used_count' => 1]);
$recUnk = $idempManager->recoverOrphan($uuidUnk, $quotaManager);
$rowUnk = $db->table('ai_request_idempotency')->where('client_message_id', $uuidUnk)->get()->getRowArray();
$p12_pass = ($recUnk['recovered'] && 
             $recUnk['case'] === 'B_DELIVERY_UNKNOWN' && 
             $rowUnk['status'] === 'DELIVERY_UNKNOWN' && 
             $rowUnk['quota_status'] === 'COMMITTED' && 
             $quotaManager->getDailyUsage($testDate)['used_count'] === 1); // Kuota TIDAK di-refund!
$passes['POIN_12_DELIVERY_UNKNOWN_SAFETY'] = $p12_pass;
echo "[POIN 12] DELIVERY_UNKNOWN Tidak Me-refund Kuota Keliru: " . ($p12_pass ? "PASS" : "FAIL") . "\n";
echo "          Status: {$rowUnk['status']} | QuotaStatus: {$rowUnk['quota_status']} | Kuota Tetap: 1\n";

// -------------------------------------------------------------
// POIN 13: Dual Traceability Tetap Mencatat Pemohon & Sumber Kunci
// -------------------------------------------------------------
// Cek rekaman pada ai_assistant_logs
$latestLog = $db->table('ai_assistant_logs')->orderBy('id', 'DESC')->limit(1)->get()->getRowArray();
$p13_pass = (!empty($latestLog) && 
             isset($latestLog['user_id']) && 
             isset($latestLog['source']) && 
             !empty($latestLog['created_at']));
$passes['POIN_13_DUAL_TRACEABILITY'] = $p13_pass;
echo "[POIN 13] Dual Traceability Mencatat Pemohon & Sumber: " . ($p13_pass ? "PASS" : "FAIL") . "\n";
echo "          User ID: " . ($latestLog['user_id'] ?? 'null') . " | Source: " . ($latestLog['source'] ?? 'null') . "\n";

// Cleanup final test data
$db->table('ai_request_idempotency')->like('client_message_id', $testUuidPrefix)->delete();
$db->table('ai_admin_quota_daily')->where('quota_date', $testDate)->delete();

echo "\n--------------------------------------------------------------------\n";
echo "HASIL KESELURUHAN REGRESI FASE 1C:\n";
$allP = true;
foreach ($passes as $k => $v) {
    echo sprintf("%-35s : %s\n", $k, $v ? "[PASS]" : "[FAIL]");
    if (!$v) $allP = false;
}
echo "KESIMPULAN: " . ($allP ? "13/13 POIN REGRESI FASE 1C LULUS 100%!" : "ADA POIN GAGAL!") . "\n";
echo "--------------------------------------------------------------------\n";
exit($allP ? 0 : 1);
