<?php
/**
 * SakaSalika License Hash Regenerator
 * Jalankan file ini melalui terminal: php update_hashes.php
 */

define('APPPATH', __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR);

// Daftar file yang dilindungi (harus sama dengan yang ada di LicenseGuard.php)
$protectedFiles = [
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
    'Controllers/Activate.php',
    'Controllers/BaseController.php',
    'Controllers/Admin/License.php',
    'Libraries/LicenseGuard.php',
    'Libraries/LicenseProtector.php',
    'Helpers/license_helper.php',
    'Config/Filters.php',
    'Config/LService.php',
    'Config/Events.php'
];

$hashes = [];
echo "--- Memulai Regenerasi Hash ---\n";

foreach ($protectedFiles as $file) {
    $fullPath = APPPATH . $file;

    if (file_exists($fullPath)) {
        $hashes[$file] = sha1_file($fullPath);
        echo "[OK] Hashed: $file\n";
    } else {
        echo "[ERROR] File tidak ditemukan: $file\n";
        exit(1);
    }
}

$hashFile = APPPATH . 'Config' . DIRECTORY_SEPARATOR . '.lic_hash';
$json = json_encode($hashes, JSON_PRETTY_PRINT);

if (file_put_contents($hashFile, $json)) {
    echo "-------------------------------\n";
    echo "BERHASIL: File .lic_hash telah diperbarui!\n";
} else {
    echo "-------------------------------\n";
    echo "GAGAL: Tidak dapat menulis ke $hashFile. Periksa izin folder.\n";
}
