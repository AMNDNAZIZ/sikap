<?php
/**
 * Script Verifikasi Upload Path
 * Memeriksa konfigurasi path upload dan folder permissions
 */

// Load CodeIgniter
require_once __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

echo "=== VERIFIKASI UPLOAD PATH ===\n\n";

// 1. Check Constants
echo "1. KONSTANTA PATH:\n";
echo "   FCPATH: " . FCPATH . "\n";
echo "   UPLOAD_PATH: " . UPLOAD_PATH . "\n";
echo "   Expected: " . FCPATH . "uploads/\n";
echo "   Match: " . (UPLOAD_PATH === FCPATH . 'uploads/' ? '✓ BENAR' : '✗ SALAH') . "\n\n";

// 2. Check soal_images folder
$soalImagesPath = UPLOAD_PATH . 'soal_images/';
echo "2. FOLDER SOAL IMAGES:\n";
echo "   Path: $soalImagesPath\n";
echo "   Exists: " . (is_dir($soalImagesPath) ? '✓ Ya' : '✗ Tidak') . "\n";

if (is_dir($soalImagesPath)) {
    echo "   Writable: " . (is_writable($soalImagesPath) ? '✓ Ya' : '✗ Tidak') . "\n";
    echo "   Permissions: " . substr(sprintf('%o', fileperms($soalImagesPath)), -4) . "\n";
    
    $files = glob($soalImagesPath . '*');
    echo "   Files: " . count($files) . " files\n";
    
    if (count($files) > 0) {
        echo "   Sample files:\n";
        foreach (array_slice($files, 0, 3) as $file) {
            echo "     - " . basename($file) . "\n";
        }
    }
} else {
    echo "   ⚠ Folder tidak ada, akan dibuat otomatis saat upload\n";
}

echo "\n3. URL MAPPING:\n";
$testFile = 'test_image.jpg';
$expectedUrl = base_url('uploads/soal_images/' . $testFile);
echo "   Base URL: " . base_url() . "\n";
echo "   Sample URL: $expectedUrl\n";
echo "   Expected format: http://localhost/siakad/public/uploads/soal_images/test_image.jpg\n";

echo "\n4. WRITABLE FOLDER (untuk backup):\n";
$writablePath = WRITEPATH . 'uploads/';
echo "   Path: $writablePath\n";
echo "   Exists: " . (is_dir($writablePath) ? '✓ Ya' : '✗ Tidak') . "\n";
if (is_dir($writablePath)) {
    echo "   Writable: " . (is_writable($writablePath) ? '✓ Ya' : '✗ Tidak') . "\n";
}

echo "\n=== KESIMPULAN ===\n";
$allGood = true;

if (UPLOAD_PATH !== FCPATH . 'uploads/') {
    echo "✗ UPLOAD_PATH salah konfigurasi\n";
    $allGood = false;
}

if (!is_dir($soalImagesPath)) {
    echo "⚠ Folder soal_images belum ada (normal untuk instalasi baru)\n";
} elseif (!is_writable($soalImagesPath)) {
    echo "✗ Folder soal_images tidak writable\n";
    $allGood = false;
}

if ($allGood) {
    echo "✓ Semua konfigurasi sudah benar!\n";
    echo "\nSilakan test upload gambar dari CKEditor.\n";
} else {
    echo "\n⚠ Ada masalah yang perlu diperbaiki.\n";
}

echo "\n";
