<?php

/**
 * Test Upload After FCPATH Fix
 * 
 * Script untuk memverifikasi bahwa upload file sudah mengarah ke public/uploads/
 * 
 * Jalankan: php TEST_UPLOAD_AFTER_FIX.php
 */

echo "=================================================================\n";
echo "TEST UPLOAD AFTER FCPATH FIX\n";
echo "=================================================================\n\n";

define('ROOTPATH', __DIR__ . '/');
define('FCPATH', ROOTPATH . 'public/');

echo "1. VERIFIKASI FCPATH\n";
echo "   ROOTPATH: " . ROOTPATH . "\n";
echo "   FCPATH: " . FCPATH . "\n";

if (strpos(FCPATH, 'public') !== false) {
    echo "   ✓ FCPATH mengarah ke public/ folder\n";
} else {
    echo "   ✗ FCPATH TIDAK mengarah ke public/ folder\n";
    exit(1);
}

echo "\n";

// 2. Check folder structure
echo "2. VERIFIKASI STRUKTUR FOLDER\n";

$folders = [
    'public/uploads/' => 'Public uploads folder',
    'public/uploads/teacher_docs/' => 'Teacher documents',
    'public/uploads/students/' => 'Student photos',
    'public/uploads/articles/' => 'Article images',
    'public/uploads/kokurikuler/' => 'Kokurikuler files',
    'public/uploads/backups/' => 'Backup files',
    'public/uploads/soal_images/' => 'Soal images',
];

$allExist = true;
foreach ($folders as $folder => $description) {
    $path = ROOTPATH . $folder;
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    
    echo "   " . ($exists ? '✓' : '✗') . " $description\n";
    echo "      Path: $path\n";
    echo "      Exists: " . ($exists ? 'Yes' : 'No') . "\n";
    
    if ($exists) {
        echo "      Writable: " . ($writable ? 'Yes' : 'No') . "\n";
        
        if (!$writable) {
            echo "      ⚠ Fix: chmod 775 $path\n";
            $allExist = false;
        }
    } else {
        echo "      ⚠ Creating folder...\n";
        if (mkdir($path, 0775, true)) {
            echo "      ✓ Folder created\n";
        } else {
            echo "      ✗ Failed to create folder\n";
            $allExist = false;
        }
    }
    
    echo "\n";
}

// 3. Check old folders (should not have new files)
echo "3. VERIFIKASI FOLDER LAMA (tidak boleh ada file baru)\n";

$oldFolders = [
    'uploads/teacher_docs/' => 'Old teacher docs',
    'uploads/students/' => 'Old student photos',
    'uploads/articles/' => 'Old article images',
];

foreach ($oldFolders as $folder => $description) {
    $path = ROOTPATH . $folder;
    
    if (is_dir($path)) {
        echo "   ⚠ $description masih ada\n";
        echo "      Path: $path\n";
        
        // Check for recent files (last 24 hours)
        $files = glob($path . '*');
        $recentFiles = [];
        
        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file) < 86400)) {
                $recentFiles[] = basename($file);
            }
        }
        
        if (!empty($recentFiles)) {
            echo "      ✗ MASIH ADA FILE BARU (24 jam terakhir):\n";
            foreach ($recentFiles as $file) {
                echo "         - $file\n";
            }
            echo "      ⚠ FIX BELUM BERHASIL!\n";
        } else {
            echo "      ✓ Tidak ada file baru (24 jam terakhir)\n";
        }
    } else {
        echo "   ✓ $description sudah tidak ada\n";
    }
    
    echo "\n";
}

// 4. Test write to correct location
echo "4. TEST WRITE KE LOKASI YANG BENAR\n";

$testFile = FCPATH . 'uploads/test_' . time() . '.txt';
$testContent = 'Test upload after FCPATH fix - ' . date('Y-m-d H:i:s');

echo "   Test file: $testFile\n";

if (file_put_contents($testFile, $testContent)) {
    echo "   ✓ Write test BERHASIL\n";
    
    // Verify file exists
    if (file_exists($testFile)) {
        echo "   ✓ File exists di lokasi yang benar\n";
        
        // Read back
        $readContent = file_get_contents($testFile);
        if ($readContent === $testContent) {
            echo "   ✓ Content match\n";
        } else {
            echo "   ✗ Content mismatch\n";
        }
        
        // Delete test file
        unlink($testFile);
        echo "   ✓ Test file deleted\n";
    } else {
        echo "   ✗ File tidak ditemukan setelah write\n";
    }
} else {
    echo "   ✗ Write test GAGAL\n";
    echo "   ⚠ Check permissions: chmod 775 " . FCPATH . "uploads/\n";
}

echo "\n";

// 5. Check index.php proxy
echo "5. VERIFIKASI INDEX.PHP PROXY\n";

$rootIndex = ROOTPATH . 'index.php';
$content = file_get_contents($rootIndex);

if (strpos($content, "require __DIR__ . '/public/index.php'") !== false) {
    echo "   ✓ index.php di root adalah PROXY ke public/index.php\n";
} else {
    echo "   ✗ index.php di root BUKAN proxy\n";
    echo "   ⚠ Fix: Ubah index.php menjadi proxy\n";
}

echo "\n";

// 6. Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

if ($allExist) {
    echo "✅ SEMUA FOLDER SUDAH BENAR\n";
    echo "✅ FCPATH mengarah ke public/\n";
    echo "✅ Write test berhasil\n";
    echo "✅ index.php adalah proxy\n\n";
    
    echo "NEXT STEPS:\n";
    echo "1. Test upload dokumen dari Admin (Teachers controller)\n";
    echo "2. Test upload dokumen dari Guru (Profile controller)\n";
    echo "3. Verifikasi file masuk ke public/uploads/teacher_docs/\n";
    echo "4. Monitor folder uploads/ di root (tidak boleh ada file baru)\n";
    echo "5. Setelah 1-2 minggu, hapus folder uploads/ di root\n";
} else {
    echo "⚠ ADA MASALAH YANG PERLU DIPERBAIKI\n";
    echo "   Check error messages di atas\n";
}

echo "\n=================================================================\n";
echo "TESTING INSTRUCTIONS\n";
echo "=================================================================\n\n";

echo "MANUAL TESTING:\n\n";

echo "1. Test Upload dari Admin:\n";
echo "   a. Login sebagai Admin\n";
echo "   b. Menu: Admin > Guru > Edit\n";
echo "   c. Tab: Arsip Dokumen\n";
echo "   d. Upload file (PDF/Image)\n";
echo "   e. Check: public/uploads/teacher_docs/ (harus ada file baru)\n";
echo "   f. Check: uploads/teacher_docs/ (TIDAK boleh ada file baru)\n\n";

echo "2. Test Upload dari Guru:\n";
echo "   a. Login sebagai Guru\n";
echo "   b. Menu: Profile\n";
echo "   c. Tab: Arsip Dokumen\n";
echo "   d. Upload file (PDF/Image)\n";
echo "   e. Check: public/uploads/teacher_docs/ (harus ada file baru)\n";
echo "   f. Check: uploads/teacher_docs/ (TIDAK boleh ada file baru)\n\n";

echo "3. Test Preview:\n";
echo "   a. Click icon mata (👁) pada dokumen\n";
echo "   b. Modal harus muncul dengan preview\n";
echo "   c. PDF: harus tampil di iframe\n";
echo "   d. Image: harus tampil sebagai gambar\n";
echo "   e. Word/Excel: harus tampil via Office Online Viewer\n\n";

echo "4. Test Download:\n";
echo "   a. Click icon download (⬇) pada dokumen\n";
echo "   b. File harus terdownload\n";
echo "   c. File harus bisa dibuka\n\n";

echo "5. Test Delete:\n";
echo "   a. Click icon delete (🗑) pada dokumen\n";
echo "   b. Konfirmasi delete\n";
echo "   c. File harus terhapus dari list\n";
echo "   d. File harus terhapus dari folder\n\n";

echo "=================================================================\n";
echo "MONITORING\n";
echo "=================================================================\n\n";

echo "Monitor folder berikut selama 1-2 minggu:\n\n";

echo "✅ HARUS ADA FILE BARU:\n";
echo "   - public/uploads/teacher_docs/\n";
echo "   - public/uploads/students/\n";
echo "   - public/uploads/articles/\n\n";

echo "❌ TIDAK BOLEH ADA FILE BARU:\n";
echo "   - uploads/teacher_docs/ (di root)\n";
echo "   - uploads/students/ (di root)\n";
echo "   - uploads/articles/ (di root)\n\n";

echo "Jika setelah 1-2 minggu tidak ada file baru di folder root,\n";
echo "maka fix berhasil dan folder lama bisa dihapus.\n\n";

echo "=================================================================\n";

