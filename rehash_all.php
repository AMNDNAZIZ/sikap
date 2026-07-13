<?php
/**
 * Emergency Full Hash + Status Fix
 * Jalankan: https://yourdomain.com/rehash_all.php?token=REHASH2026
 * HAPUS SETELAH SELESAI
 */
if (($_GET['token'] ?? '') !== 'REHASH2026') { http_response_code(403); die('Forbidden'); }

$appPath = __DIR__ . '/app/';

// Baca .env
$env = [];
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env') as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\"'");
        }
    }
}

echo '<pre style="font-family:monospace;font-size:13px;">';
echo "=== EMERGENCY HASH + LICENSE FIX ===\n\n";

// 1. Fix database: set status = active
$dbHost = $env['database.default.hostname'] ?? 'localhost';
$dbUser = $env['database.default.username'] ?? 'root';
$dbPass = $env['database.default.password'] ?? '';
$dbName = $env['database.default.database'] ?? '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $row = $pdo->query("SELECT id, status, expires_at FROM app_license LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        if ($row['status'] !== 'active') {
            $pdo->prepare("UPDATE app_license SET status='active', last_check=? WHERE id=?")
                ->execute([date('Y-m-d H:i:s'), $row['id']]);
            echo "✅ DB status: '{$row['status']}' → 'active'\n";
        } else {
            echo "✅ DB status sudah active\n";
        }
        echo "   expires_at: {$row['expires_at']}\n\n";
    }
} catch (Exception $e) {
    echo "⚠️  DB error: " . $e->getMessage() . "\n\n";
}

// 2. Regenerate .lic_hash (SHA1, untuk LicenseGuard::checkIntegrity)
$licFiles = [
    'Libraries/LicenseGuard.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];
$licHashes = [];
foreach ($licFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) {
        $licHashes[$f] = sha1_file($p);
        echo "✅ lic_hash: $f\n";
    } else {
        echo "❌ MISSING: $f\n";
    }
}
file_put_contents($appPath . 'Config/.lic_hash', json_encode($licHashes, JSON_PRETTY_PRINT));
echo "\n✅ .lic_hash ditulis (" . count($licHashes) . " files)\n\n";

// 3. Regenerate .security_hash (SHA256, untuk SecurityGuard::verifyCriticalFiles)
$secFiles = [
    'Helpers/license_helper.php',
    'Libraries/LicenseGuard.php',
    'Libraries/SecurityGuard.php',
    'Libraries/SecureConfig.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];
$secHashes = [];
foreach ($secFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) {
        $secHashes[$f] = hash_file('sha256', $p);
        echo "✅ security_hash: $f\n";
    } else {
        echo "❌ MISSING: $f\n";
    }
}
file_put_contents($appPath . 'Config/.security_hash', json_encode($secHashes, JSON_PRETTY_PRINT));
echo "\n✅ .security_hash ditulis (" . count($secHashes) . " files)\n\n";

// 4. Bersihkan semua cache yang berkaitan dengan license
$cacheDir = __DIR__ . '/writable/cache/';
$cleaned = 0;
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '*') as $f) {
        if (is_file($f) && basename($f) !== 'index.html') {
            @unlink($f);
            $cleaned++;
        }
    }
}
echo "✅ Cache dibersihkan ($cleaned files)\n\n";

// 5. Verifikasi akhir
echo "=== VERIFIKASI ===\n";
$licFile = $appPath . 'Config/.lic_hash';
$secFile = $appPath . 'Config/.security_hash';
echo "lic_hash   exists: " . (file_exists($licFile) ? "✅" : "❌") . "\n";
echo "security_hash exists: " . (file_exists($secFile) ? "✅" : "❌") . "\n\n";

echo "🎉 SELESAI. Coba akses aplikasi sekarang.\n";
echo "⚠️  HAPUS FILE INI SEGERA!\n";
echo '</pre>';
