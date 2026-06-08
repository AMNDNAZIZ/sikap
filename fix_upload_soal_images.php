<?php

/**
 * Fix Upload Soal Images
 * 
 * Script untuk memastikan folder soal_images ada dan writable
 */

echo "=================================================================\n";
echo "FIX UPLOAD SOAL IMAGES\n";
echo "=================================================================\n\n";

define('ROOTPATH', __DIR__ . '/');
define('FCPATH', ROOTPATH . 'public/');
define('UPLOAD_PATH', FCPATH . 'uploads/');

$soalImagesPath = UPLOAD_PATH . 'soal_images/';

echo "Checking folder: $soalImagesPath\n\n";

// 1. Check if folder exists
if (is_dir($soalImagesPath)) {
    echo "✓ Folder exists\n";
} else {
    echo "✗ Folder does not exist\n";
    echo "  Creating folder...\n";
    
    if (mkdir($soalImagesPath, 0775, true)) {
        echo "  ✓ Folder created successfully\n";
    } else {
        echo "  ✗ Failed to create folder\n";
        exit(1);
    }
}

// 2. Check if folder is writable
if (is_writable($soalImagesPath)) {
    echo "✓ Folder is writable\n";
} else {
    echo "✗ Folder is NOT writable\n";
    echo "  Fixing permissions...\n";
    
    if (chmod($soalImagesPath, 0775)) {
        echo "  ✓ Permissions fixed\n";
    } else {
        echo "  ✗ Failed to fix permissions\n";
        echo "  Run manually: chmod 775 public/uploads/soal_images\n";
    }
}

// 3. Check folder permissions
$perms = substr(sprintf('%o', fileperms($soalImagesPath)), -4);
echo "✓ Current permissions: $perms\n";

// 4. Test write
$testFile = $soalImagesPath . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✓ Write test successful\n";
    unlink($testFile);
} else {
    echo "✗ Write test failed\n";
}

// 5. Count existing files
$files = glob($soalImagesPath . '*');
$fileCount = count($files);
echo "✓ Existing files: $fileCount\n";

echo "\n";
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

echo "Folder: $soalImagesPath\n";
echo "Status: " . (is_dir($soalImagesPath) && is_writable($soalImagesPath) ? "✓ READY" : "✗ NOT READY") . "\n";
echo "Permissions: $perms\n";
echo "Files: $fileCount\n";

echo "\n";
echo "=================================================================\n";
echo "TESTING UPLOAD\n";
echo "=================================================================\n\n";

echo "1. Go to: https://localhost/siakad/index.php/admin/cbt/banksoal/tambah_soal\n";
echo "2. Paste soal dengan gambar dari Word\n";
echo "3. Gambar should upload successfully\n";
echo "4. Check folder: public/uploads/soal_images/\n";
echo "\n";

echo "If upload still fails:\n";
echo "1. Check browser console (F12) for errors\n";
echo "2. Check error log: writable/logs/log-*.log\n";
echo "3. Check Apache error log\n";
echo "\n";

echo "=================================================================\n";
echo "DONE!\n";
echo "=================================================================\n";
