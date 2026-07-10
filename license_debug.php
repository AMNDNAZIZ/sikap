<?php
/**
 * License Debug & Fix Script
 * Jalankan: https://yourdomain.com/license_debug.php?token=DEBUG2026
 * HAPUS SETELAH SELESAI
 */
if (($_GET['token'] ?? '') !== 'DEBUG2026') { http_response_code(403); die('Forbidden'); }

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

$dbHost = $env['database.default.hostname'] ?? 'localhost';
$dbUser = $env['database.default.username'] ?? 'smpi3426_web';
$dbPass = $env['database.default.password'] ?? 'S@lm4100185';
$dbName = $env['database.default.database'] ?? 'smpi3426_web';

echo '<pre style="font-family:monospace;font-size:13px;">';
echo "=== LICENSE FIX ===\n\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("❌ DB Error: " . $e->getMessage());
}

$row = $pdo->query("SELECT * FROM app_license LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$row) { die("❌ Tidak ada data di tabel app_license"); }

echo "Status saat ini : " . $row['status'] . "\n";
echo "Expires at      : " . $row['expires_at'] . "\n";
echo "Server time     : " . date('Y-m-d H:i:s') . "\n\n";

$expiresTs = strtotime($row['expires_at'] ?? '');
if ($expiresTs < time()) {
    die("❌ Lisensi memang sudah expired (" . $row['expires_at'] . "). Perlu perpanjang lisensi.");
}

echo "✅ Expiry valid hingga " . $row['expires_at'] . "\n\n";

// ACTION: Ubah status ke active
if ($row['status'] !== 'active') {
    $pdo->prepare("UPDATE app_license SET status = 'active', last_check = ? WHERE id = ?")
        ->execute([date('Y-m-d H:i:s'), $row['id']]);
    echo "✅ Status diubah dari '{$row['status']}' → 'active'\n\n";
} else {
    echo "Status sudah active, tidak perlu diubah.\n\n";
}

// Jika encryptionKey tersedia, perbaiki hash juga
$encKey = $env['license.encryptionKey'] ?? '';
$hashSecret = base64_decode($env['license.hashSecret'] ?? 'U2FLYVNhTGlLYTIwMjZTZWNyZXRLZXlGb3JIYXNoVmVyaWZpY2F0aW9u');

if ($encKey) {
    function decryptF($value, $key) {
        try {
            $data = base64_decode($value);
            $iv = substr($data, 0, 16);
            $enc = substr($data, 16);
            return openssl_decrypt($enc, 'AES-256-CBC', $key, 0, $iv);
        } catch (Exception $e) { return $value; }
    }

    // Re-read after update
    $row = $pdo->query("SELECT * FROM app_license LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $licKey    = decryptF($row['license_key'], $encKey);
    $machineId = decryptF($row['machine_id'], $encKey);
    $expiresAt = $row['expires_at'];

    $expectedHash = hash('sha256', $licKey . $expiresAt . $machineId . $hashSecret);

    if ($row['hash'] !== $expectedHash) {
        $pdo->prepare("UPDATE app_license SET hash = ? WHERE id = ?")
            ->execute([$expectedHash, $row['id']]);
        echo "✅ Hash DB diperbaiki\n";
    } else {
        echo "✅ Hash DB sudah cocok\n";
    }
} else {
    echo "⚠️  encryptionKey tidak ada di .env — hash tidak bisa diverifikasi.\n";
    echo "   Tapi status sudah diubah ke 'active', coba akses aplikasi dulu.\n\n";
}

// Regenerate .lic_hash dan .security_hash
$licFiles = ['Libraries/LicenseGuard.php', 'Filters/LicenseFilter.php', 'Models/LicenseModel.php'];
$licHashes = [];
foreach ($licFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) $licHashes[$f] = sha1_file($p);
}
file_put_contents($appPath . 'Config/.lic_hash', json_encode($licHashes, JSON_PRETTY_PRINT));
echo "✅ .lic_hash diperbarui (" . count($licHashes) . " files)\n";

$secFiles = ['Helpers/license_helper.php','Libraries/LicenseGuard.php','Libraries/SecurityGuard.php','Libraries/SecureConfig.php','Filters/LicenseFilter.php','Models/LicenseModel.php'];
$secHashes = [];
foreach ($secFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) $secHashes[$f] = hash_file('sha256', $p);
}
file_put_contents($appPath . 'Config/.security_hash', json_encode($secHashes, JSON_PRETTY_PRINT));
echo "✅ .security_hash diperbarui (" . count($secHashes) . " files)\n\n";

// Reset cache fail count
$cacheDir = __DIR__ . '/writable/cache/';
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '*license*') as $f) @unlink($f);
    foreach (glob($cacheDir . '*fail_count*') as $f) @unlink($f);
    echo "✅ Cache license dibersihkan\n";
}

echo "\n🎉 SELESAI. Sekarang coba akses aplikasi.\n";
echo "⚠️  HAPUS FILE INI SEKARANG!\n";
echo '</pre>';
