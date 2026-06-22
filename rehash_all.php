<?php
/**
 * Emergency Full Hash Regenerator
 * Update BOTH .lic_hash AND .security_hash
 * Jalankan: https://yourdomain.com/rehash_all.php?token=REHASH2026
 * HAPUS FILE INI SETELAH DIJALANKAN.
 */

$token = $_GET['token'] ?? '';
if ($token !== 'REHASH2026') {
    http_response_code(403);
    die('Forbidden. Tambahkan ?token=REHASH2026 di URL.');
}

$appPath = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

echo '<pre>';

// ─── 1. Regenerate .lic_hash ──────────────────────────────────────────────
$licFiles = [
    'Libraries/LicenseGuard.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];

$licHashes = [];
foreach ($licFiles as $file) {
    $path = $appPath . $file;
    if (file_exists($path)) {
        $licHashes[$file] = sha1_file($path);
        echo "✅ lic_hash: $file\n";
    } else {
        echo "❌ MISSING: $file\n";
    }
}

$licHashFile = $appPath . 'Config/.lic_hash';
if (file_put_contents($licHashFile, json_encode($licHashes, JSON_PRETTY_PRINT)) !== false) {
    echo "\n✅ .lic_hash berhasil diperbarui (" . count($licHashes) . " files)\n\n";
} else {
    echo "\n❌ GAGAL menulis .lic_hash — cek permission\n\n";
}

// ─── 2. Regenerate .security_hash ────────────────────────────────────────
$secFiles = [
    'Helpers/license_helper.php',
    'Libraries/LicenseGuard.php',
    'Libraries/SecurityGuard.php',
    'Libraries/SecureConfig.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];

$secHashes = [];
foreach ($secFiles as $file) {
    $path = $appPath . $file;
    if (file_exists($path)) {
        $secHashes[$file] = hash_file('sha256', $path);
        echo "✅ security_hash: $file\n";
    } else {
        echo "❌ MISSING: $file\n";
    }
}

$secHashFile = $appPath . 'Config/.security_hash';
if (file_put_contents($secHashFile, json_encode($secHashes, JSON_PRETTY_PRINT)) !== false) {
    echo "\n✅ .security_hash berhasil diperbarui (" . count($secHashes) . " files)\n";
} else {
    echo "\n❌ GAGAL menulis .security_hash — cek permission\n";
}

echo "\n<strong>Selesai. Coba akses aplikasi sekarang.</strong>";
echo "\n\n⚠️  HAPUS FILE INI SEKARANG dari hosting!";
echo '</pre>';
