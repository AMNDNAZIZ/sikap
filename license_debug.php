<?php
/**
 * License Debug Script
 * Jalankan: https://yourdomain.com/license_debug.php?token=DEBUG2026
 * HAPUS SETELAH SELESAI
 */
if (($_GET['token'] ?? '') !== 'DEBUG2026') { http_response_code(403); die('Forbidden'); }

// Bootstrap minimal CI4
define('FCPATH', __DIR__ . '/public/');
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
$dbUser = $env['database.default.username'] ?? 'root';
$dbPass = $env['database.default.password'] ?? '';
$dbName = $env['database.default.database'] ?? '';

echo '<pre style="font-family:monospace;font-size:13px;">';
echo "=== LICENSE DIAGNOSTIC ===\n\n";
echo "Server time    : " . date('Y-m-d H:i:s') . " (" . date_default_timezone_get() . ")\n";
echo "DB Host        : $dbHost\n";
echo "DB Name        : $dbName\n\n";

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ DB Connected\n\n";
} catch (Exception $e) {
    die("❌ DB Error: " . $e->getMessage());
}

// Ambil lisensi
$row = $pdo->query("SELECT * FROM app_license LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$row) { die("❌ Tidak ada data di tabel app_license"); }

echo "=== DATA LISENSI DI DB ===\n";
echo "id             : " . $row['id'] . "\n";
echo "status         : " . $row['status'] . "\n";
echo "expires_at     : " . $row['expires_at'] . "\n";
echo "last_check     : " . $row['last_check'] . "\n";
echo "hash (16 char) : " . substr($row['hash'] ?? '', 0, 16) . "...\n";
echo "machine_id     : " . substr($row['machine_id'] ?? '', 0, 20) . "...(encrypted)\n\n";

// Cek expiry
$expiresTs = strtotime($row['expires_at'] ?? '');
$nowTs     = time();
echo "=== CEK EXPIRY ===\n";
echo "expires_at (strtotime) : $expiresTs → " . date('Y-m-d H:i:s', $expiresTs) . "\n";
echo "time() sekarang        : $nowTs → " . date('Y-m-d H:i:s', $nowTs) . "\n";
echo "Status expiry          : " . ($expiresTs > $nowTs ? "✅ BELUM EXPIRED" : "❌ EXPIRED") . "\n\n";

// Cek hash
$encKey = $env['license.encryptionKey'] ?? '';
$hashSecret = base64_decode($env['license.hashSecret'] ?? 'U2FLYVNhTGlLYTIwMjZTZWNyZXRLZXlGb3JIYXNoVmVyaWZpY2F0aW9u');

if ($encKey) {
    // Decrypt machine_id dan license_key
    function decryptField($value, $key) {
        if (empty($value)) return $value;
        try {
            $data = base64_decode($value);
            $iv = substr($data, 0, 16);
            $enc = substr($data, 16);
            return openssl_decrypt($enc, 'AES-256-CBC', $key, 0, $iv);
        } catch (Exception $e) {
            return $value;
        }
    }

    $licKey   = decryptField($row['license_key'], $encKey);
    $machineId = decryptField($row['machine_id'], $encKey);
    $expiresAt = $row['expires_at'];

    $expectedHash = hash('sha256', $licKey . $expiresAt . $machineId . $hashSecret);
    $storedHash   = $row['hash'];

    echo "=== CEK HASH ===\n";
    echo "license_key (dec, 10) : " . substr($licKey, 0, 10) . "...\n";
    echo "machine_id  (dec, 16) : " . substr($machineId, 0, 16) . "...\n";
    echo "expires_at            : $expiresAt\n";
    echo "expected hash (16)    : " . substr($expectedHash, 0, 16) . "...\n";
    echo "stored hash   (16)    : " . substr($storedHash, 0, 16) . "...\n";
    echo "Hash match            : " . ($expectedHash === $storedHash ? "✅ MATCH" : "❌ MISMATCH") . "\n\n";

    // Jika hash mismatch, regenerate
    if ($expectedHash !== $storedHash) {
        echo "=== REGENERATE HASH ===\n";
        $newHash = hash('sha256', $licKey . $expiresAt . $machineId . $hashSecret);
        $pdo->prepare("UPDATE app_license SET hash = ? WHERE id = ?")->execute([$newHash, $row['id']]);
        echo "✅ Hash di database sudah diperbarui.\n";
        echo "   Sekarang coba akses aplikasi kembali.\n\n";

        // Regenerate .lic_hash
        $licFiles = [
            $appPath . 'Libraries/LicenseGuard.php',
            $appPath . 'Filters/LicenseFilter.php',
            $appPath . 'Models/LicenseModel.php',
        ];
        $licHashes = [];
        foreach ($licFiles as $f) {
            if (file_exists($f)) $licHashes[str_replace($appPath, '', $f)] = sha1_file($f);
        }
        file_put_contents($appPath . 'Config/.lic_hash', json_encode($licHashes, JSON_PRETTY_PRINT));
        echo "✅ .lic_hash diperbarui\n";

        // Regenerate .security_hash
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
            if (file_exists($p)) $secHashes[$f] = hash_file('sha256', $p);
        }
        file_put_contents($appPath . 'Config/.security_hash', json_encode($secHashes, JSON_PRETTY_PRINT));
        echo "✅ .security_hash diperbarui\n\n";
        echo "🎉 Semua sudah diperbaiki. Hapus file ini dan coba akses aplikasi.\n";
    } else {
        echo "Hash sudah cocok. Masalah mungkin ada di tempat lain.\n";
        echo "Cek: apakah .security_hash perlu diperbarui?\n";

        // Cek security hash
        $secHashFile = $appPath . 'Config/.security_hash';
        if (file_exists($secHashFile)) {
            $secHashes = json_decode(file_get_contents($secHashFile), true);
            echo "\n=== CEK SECURITY_HASH ===\n";
            $secFiles = ['Helpers/license_helper.php','Libraries/LicenseGuard.php','Libraries/SecurityGuard.php','Libraries/SecureConfig.php','Filters/LicenseFilter.php','Models/LicenseModel.php'];
            $needRegen = false;
            foreach ($secFiles as $f) {
                $p = $appPath . $f;
                if (!file_exists($p)) { echo "❌ MISSING: $f\n"; continue; }
                $current = hash_file('sha256', $p);
                $stored  = $secHashes[$f] ?? 'NOT IN HASH';
                $match   = ($current === $stored);
                if (!$match) $needRegen = true;
                echo ($match ? "✅" : "❌") . " $f\n";
            }
            if ($needRegen) {
                echo "\n⚠️  Security hash tidak cocok — regenerating...\n";
                $newSec = [];
                foreach ($secFiles as $f) {
                    $p = $appPath . $f;
                    if (file_exists($p)) $newSec[$f] = hash_file('sha256', $p);
                }
                file_put_contents($secHashFile, json_encode($newSec, JSON_PRETTY_PRINT));
                echo "✅ .security_hash diperbarui\n";
            } else {
                echo "\n✅ Semua security hash cocok.\n";
            }
        }
    }
} else {
    echo "❌ license.encryptionKey tidak ditemukan di .env\n";
    echo "   Tidak bisa decrypt machine_id untuk verifikasi hash.\n";
}

echo "\n⚠️  HAPUS FILE INI SEKARANG!\n";
echo '</pre>';
