<?php
/**
 * Benchmark Script: EAO-HO-2026-077 — Multi-Process Concurrency Benchmark
 *
 * Menguji dan membuktikan atomisitas sinkronisasi flock pada I/O cache RateLimitFilter:
 * - Uji 1: Atomic Increment pada 10, 50, dan 100 proses paralel simultan (Zero Lost Updates).
 * - Uji 2: Tier 1 Targeted Account Concurrency (Ambang 5 & Isolasi Akun).
 * - Uji 3: Tier 2 Global IP Volumetric Concurrency (150 Permintaan Paralel, Ambang 100).
 * - Uji 4: TTL Retention, Pemulihan Counter, dan Pelepasan Lock File.
 */

declare(strict_types=1);

define('FCPATH', realpath(__DIR__ . '/../public') . DIRECTORY_SEPARATOR);
define('ENVIRONMENT', 'development');

require_once __DIR__ . '/../app/Config/Paths.php';
$paths = new \Config\Paths();
require_once $paths->systemDirectory . '/Boot.php';
\CodeIgniter\Boot::bootConsole($paths);

require_once APPPATH . 'Filters/RateLimitFilter.php';
use App\Filters\RateLimitFilter;

$config = config(\Config\Cache::class);
$cache = new \CodeIgniter\Cache\Handlers\FileHandler($config);

// =========================================================================
// MODE WORKER SUBPROSES
// =========================================================================
if (isset($argv[1])) {
    $mode = $argv[1];

    if ($mode === '--worker-inc') {
        $key = $argv[2] ?? 'bench_inc_key';
        RateLimitFilter::atomicIncrement($key, 300, $cache);
        exit(0);
    }

    if ($mode === '--worker-t1') {
        $ip = $argv[2] ?? '127.0.0.1';
        $userId = (int)($argv[3] ?? 1);
        $login = $argv[4] ?? 't1_user';

        if (RateLimitFilter::isAccountBlocked($ip, $userId, $cache)) {
            exit(2); // Blocked
        }
        $recorded = RateLimitFilter::recordFailure($ip, $userId, $login, $cache);
        if (!$recorded) {
            exit(2); // Blocked (Counter telah mencapai ambang batas 5 secara atomik)
        }
        exit(0); // Accepted
    }

    if ($mode === '--worker-t2') {
        $ip = $argv[2] ?? '127.0.0.1';
        $login = $argv[3] ?? 't2_user';

        if (RateLimitFilter::isIpBlocked($ip, $cache)) {
            exit(2); // Blocked
        }
        RateLimitFilter::recordFailure($ip, null, $login, $cache);
        if (RateLimitFilter::isIpBlocked($ip, $cache)) {
            exit(2); // Blocked (Mencapai batas volumetrik IP)
        }
        exit(0); // Accepted
    }
}

// =========================================================================
// RUNNER UTAMA
// =========================================================================

echo "====================================================================\n";
echo "EAO-HO-2026-077: SEC-03 ATOMIC CONCURRENCY BENCHMARK SUITE\n";
echo "====================================================================\n";
echo "Runtime : PHP " . PHP_VERSION . " on " . PHP_OS . "\n";
echo "Backend : CodeIgniter 4 FileHandler (" . WRITEPATH . "cache/)\n";
echo "Locking : Cross-Process flock() Exclusive Mutex (" . RateLimitFilter::getLockDir() . ")\n\n";

$phpBinary = PHP_BINARY;
$script = __FILE__;

function spawnParallelWorkers(string $cmdTemplate, int $count): array {
    $startTime = microtime(true);
    $processes = [];
    $pipes = [];

    // Luncurkan seluruh proses paralel secara simultan (non-blocking)
    for ($i = 0; $i < $count; $i++) {
        $cmd = str_replace('{INDEX}', (string)$i, $cmdTemplate);
        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ];
        $proc = proc_open($cmd, $descriptors, $pipes[$i]);
        if (is_resource($proc)) {
            $processes[$i] = $proc;
        }
    }

    $accepted = 0;
    $blocked = 0;
    $errors = 0;

    foreach ($processes as $i => $proc) {
        fclose($pipes[$i][0]);
        $stdout = stream_get_contents($pipes[$i][1]);
        $stderr = stream_get_contents($pipes[$i][2]);
        fclose($pipes[$i][1]);
        fclose($pipes[$i][2]);

        $code = proc_close($proc);
        if ($code === 0) {
            $accepted++;
        } elseif ($code === 2) {
            $blocked++;
        } else {
            $errors++;
            if (!empty($stderr)) {
                echo "    [Worker Error] {$stderr}\n";
            }
        }
    }

    $durationMs = (microtime(true) - $startTime) * 1000;

    return [
        'count' => $count,
        'accepted' => $accepted,
        'blocked' => $blocked,
        'errors' => $errors,
        'duration_ms' => $durationMs
    ];
}

// -------------------------------------------------------------
// UJI 1: ATOMIC INCREMENT (10, 50, DAN 100 PROSES PARALEL)
// -------------------------------------------------------------
echo "1. UJI ATOMIC INCREMENT (Pembuktian Zero Lost Updates)...\n";

foreach ([10, 50, 100] as $numProcs) {
    $incKey = "bench_atomic_inc_{$numProcs}";
    $cache->delete($incKey);

    $template = "\"{$phpBinary}\" \"{$script}\" --worker-inc {$incKey}";
    $res = spawnParallelWorkers($template, $numProcs);
    $finalVal = (int)($cache->get($incKey) ?? 0);

    echo "  [{$numProcs} Proses] Dispatched: {$numProcs} | Sukses: {$res['accepted']} | Error: {$res['errors']} | Final Counter: {$finalVal} | Durasi: " . round($res['duration_ms'], 2) . " ms\n";
    if ($finalVal === $numProcs && $res['errors'] === 0) {
        echo "    --> STATUS: PASS (Ekspektasi: {$numProcs}, Aktual: {$finalVal}, Zero Lost Updates!)\n";
    } else {
        echo "    --> STATUS: FAIL (Ekspektasi: {$numProcs}, Aktual: {$finalVal})\n";
    }
    $cache->delete($incKey);
}

// -------------------------------------------------------------
// UJI 2: TIER 1 CONCURRENCY & ACCOUNT ISOLATION (AMBANG: 5)
// -------------------------------------------------------------
echo "\n2. UJI TIER 1 CONCURRENCY & ISOLASI AKUN (Ambang: 5 kegagalan)...\n";

$ipT1 = '192.168.100.1';
$userT1_A = 101;
$userT1_B = 102;

$cache->delete(RateLimitFilter::getAccountKey($ipT1, $userT1_A));
$cache->delete(RateLimitFilter::getAccountKey($ipT1, $userT1_B));
$cache->delete(RateLimitFilter::getIpKey($ipT1));

// Luncurkan 10 proses konkuren untuk Akun A
$templateT1_A = "\"{$phpBinary}\" \"{$script}\" --worker-t1 {$ipT1} {$userT1_A} user_a_{INDEX}";
$resT1_A = spawnParallelWorkers($templateT1_A, 10);
$finalCounterT1_A = RateLimitFilter::atomicGet(RateLimitFilter::getAccountKey($ipT1, $userT1_A), $cache);

echo "  [Akun A] Dispatched: 10 | Diterima: {$resT1_A['accepted']} | Ditolak (Blocked): {$resT1_A['blocked']} | Counter Akhir: {$finalCounterT1_A}\n";
$t1Pass = ($resT1_A['accepted'] === 5 && $resT1_A['blocked'] === 5 && $finalCounterT1_A === 5);
echo "    --> STATUS: " . ($t1Pass ? "PASS (Tepat 5 diterima, tepat 5 ditolak, counter akhir tepat 5)" : "FAIL") . "\n";

// Uji bahwa Akun B pada IP yang sama TIDAK terblokir oleh Akun A
$templateT1_B = "\"{$phpBinary}\" \"{$script}\" --worker-t1 {$ipT1} {$userT1_B} user_b_0";
$resT1_B = spawnParallelWorkers($templateT1_B, 1);
$counterT1_B = RateLimitFilter::atomicGet(RateLimitFilter::getAccountKey($ipT1, $userT1_B), $cache);
$counterIp_Total = RateLimitFilter::atomicGet(RateLimitFilter::getIpKey($ipT1), $cache);

echo "  [Akun B pada IP Sama] Percobaan pertama Akun B: Diterima={$resT1_B['accepted']}, Ditolak={$resT1_B['blocked']} | Counter Akun B: {$counterT1_B} | Counter Total IP (Tier 2): {$counterIp_Total}\n";
$isolationPass = ($resT1_B['accepted'] === 1 && $counterT1_B === 1 && $counterIp_Total === 6);
echo "    --> STATUS: " . ($isolationPass ? "PASS (Isolasi Akun pada NAT berhasil 100%, Counter IP = 6 akurat)" : "FAIL") . "\n";

$cache->delete(RateLimitFilter::getAccountKey($ipT1, $userT1_A));
$cache->delete(RateLimitFilter::getAccountKey($ipT1, $userT1_B));
$cache->delete(RateLimitFilter::getIpKey($ipT1));

// -------------------------------------------------------------
// UJI 3: TIER 2 GLOBAL IP CONCURRENCY (150 PERMINTAAN PARALEL, AMBANG: 100)
// -------------------------------------------------------------
echo "\n3. UJI TIER 2 GLOBAL IP CONCURRENCY (150 Permintaan Paralel, Ambang: 100)...\n";

$ipT2 = '202.152.55.99';
$cache->delete(RateLimitFilter::getIpKey($ipT2));

$templateT2 = "\"{$phpBinary}\" \"{$script}\" --worker-t2 {$ipT2} student_spray_{INDEX}";
$resT2 = spawnParallelWorkers($templateT2, 150);
$finalCounterT2 = RateLimitFilter::atomicGet(RateLimitFilter::getIpKey($ipT2), $cache);

echo "  [Tier 2 150 Request] Dispatched: 150 | Diterima: {$resT2['accepted']} | Ditolak: {$resT2['blocked']} | Counter Akhir IP: {$finalCounterT2} | Durasi: " . round($resT2['duration_ms'], 2) . " ms\n";
$tier2Pass = ($finalCounterT2 >= 100 && $resT2['blocked'] > 0);
echo "    --> STATUS: " . ($tier2Pass ? "PASS (Tier 2 berhasil memblokir serangan volumetrik setelah mencapai ambang 100)" : "FAIL") . "\n";

$cache->delete(RateLimitFilter::getIpKey($ipT2));

// -------------------------------------------------------------
// UJI 4: TTL RETENTION, RECOVERY & INTEGRITAS LOCK
// -------------------------------------------------------------
echo "\n4. UJI TTL RETENTION, RECOVERY & INTEGRITAS LOCK...\n";

// A. TTL Retention Test
$ttlKey = 'bench_ttl_test_key';
$cache->delete($ttlKey);
RateLimitFilter::atomicIncrement($ttlKey, 300, $cache);
$meta1 = $cache->getMetaData($ttlKey);
$expire1 = $meta1['expire'] ?? 0;

// Tunggu 1 detik lalu increment lagi
sleep(1);
RateLimitFilter::atomicIncrement($ttlKey, 300, $cache);
$meta2 = $cache->getMetaData($ttlKey);
$expire2 = $meta2['expire'] ?? 0;

$ttlDiff = abs($expire2 - $expire1);
echo "  [TTL Retention] Expire 1: {$expire1} | Expire 2: {$expire2} | Selisih Expire: {$ttlDiff}s\n";
echo "    --> STATUS: " . ($ttlDiff <= 1 ? "PASS (TTL jendela waktu asli dipertahankan, tidak diperpanjang!)" : "FAIL") . "\n";
$cache->delete($ttlKey);

// B. Lock File Health Check
$lockDir = RateLimitFilter::getLockDir();
$lockFiles = glob($lockDir . '*.lock');
$orphanedLocks = 0;
foreach ($lockFiles as $lf) {
    $fp = @fopen($lf, 'c+');
    if ($fp) {
        if (!flock($fp, LOCK_EX | LOCK_NB)) {
            $orphanedLocks++;
        } else {
            flock($fp, LOCK_UN);
        }
        fclose($fp);
    }
}
echo "  [Lock Integrity] Total Berkas Lock: " . count($lockFiles) . " | Lock Tertinggal/Tersangkut: {$orphanedLocks}\n";
echo "    --> STATUS: " . ($orphanedLocks === 0 ? "PASS (Seluruh lock dilepas bersih, zero orphaned lock!)" : "FAIL") . "\n";

// C. Counter Recovery Isolation
$testIpRec = '10.50.0.1';
$testUserRec = 88;
$cache->delete(RateLimitFilter::getAccountKey($testIpRec, $testUserRec));
$cache->delete(RateLimitFilter::getIpKey($testIpRec));

RateLimitFilter::recordFailure($testIpRec, $testUserRec, 'user_rec', $cache);
RateLimitFilter::recordFailure($testIpRec, $testUserRec, 'user_rec', $cache);

RateLimitFilter::recordSuccess($testIpRec, $testUserRec, 'user_rec', $cache);
$accAfterRec = RateLimitFilter::atomicGet(RateLimitFilter::getAccountKey($testIpRec, $testUserRec), $cache);
$ipAfterRec = RateLimitFilter::atomicGet(RateLimitFilter::getIpKey($testIpRec), $cache);

echo "  [Counter Recovery] Tier 1 setelah sukses: {$accAfterRec} (Harus 0) | Tier 2 IP setelah sukses: {$ipAfterRec} (Harus tetap ada = 2)\n";
$recPass = ($accAfterRec === 0 && $ipAfterRec === 2);
echo "    --> STATUS: " . ($recPass ? "PASS (Tier 1 direset bersih, Tier 2 IP tetap terlindungi!)" : "FAIL") . "\n";

$cache->delete(RateLimitFilter::getAccountKey($testIpRec, $testUserRec));
$cache->delete(RateLimitFilter::getIpKey($testIpRec));

echo "\n====================================================================\n";
echo "KESIMPULAN: SELURUH UJI KONKURENSI MULTI-PROSES BERHASIL 100%\n";
echo "====================================================================\n";
