<?php
/**
 * Emergency Security Hash Regenerator
 * Jalankan sekali via browser: https://yourdomain.com/rehash_security.php?token=REHASH2026
 * HAPUS FILE INI SETELAH DIJALANKAN.
 */

// Token sederhana agar tidak sembarang orang bisa akses
$token = $_GET['token'] ?? '';
if ($token !== 'REHASH2026') {
    http_response_code(403);
    die('Forbidden. Tambahkan ?token=REHASH2026 di URL.');
}

$appPath = __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

$criticalFiles = [
    'Helpers/license_helper.php',
    'Libraries/LicenseGuard.php',
    'Libraries/SecurityGuard.php',
    'Libraries/SecureConfig.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];

$hashes = [];
$missing = [];

foreach ($criticalFiles as $file) {
    $path = $appPath . $file;
    if (file_exists($path)) {
        $hashes[$file] = hash_file('sha256', $path);
        echo "✅ Hashed: $file<br>";
    } else {
        $missing[] = $file;
        echo "❌ MISSING: $file<br>";
    }
}

$hashFile = $appPath . 'Config/.security_hash';
$result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "<br><strong>✅ Security hash berhasil di-regenerate (" . count($hashes) . " files)</strong><br>";
    echo "<br><span style='color:red'>⚠️ HAPUS FILE INI SEKARANG dari hosting!</span><br>";
    echo "<a href='/'>Kembali ke Beranda</a>";
} else {
    echo "<br><strong>❌ GAGAL menulis file. Cek permission folder app/Config/</strong>";
}
