<?php
/**
 * Simple Upload Path Checker
 * Checks upload configuration without loading full CI framework
 */

echo "=== VERIFIKASI KONFIGURASI UPLOAD ===\n\n";

// Simulate FCPATH (public folder)
$fcpath = __DIR__ . '/public/';
$uploadPath = $fcpath . 'uploads/';
$soalImagesPath = $uploadPath . 'soal_images/';

echo "1. PATH CONFIGURATION:\n";
echo "   Root: " . __DIR__ . "\n";
echo "   FCPATH (public): $fcpath\n";
echo "   UPLOAD_PATH: $uploadPath\n";
echo "   Soal Images: $soalImagesPath\n\n";

echo "2. FOLDER STATUS:\n";

// Check public folder
echo "   public/ folder:\n";
if (is_dir($fcpath)) {
    echo "     ✓ Exists\n";
    echo "     Writable: " . (is_writable($fcpath) ? '✓ Yes' : '✗ No') . "\n";
} else {
    echo "     ✗ NOT FOUND!\n";
}

// Check uploads folder
echo "\n   public/uploads/ folder:\n";
if (is_dir($uploadPath)) {
    echo "     ✓ Exists\n";
    echo "     Writable: " . (is_writable($uploadPath) ? '✓ Yes' : '✗ No') . "\n";
    echo "     Permissions: " . substr(sprintf('%o', fileperms($uploadPath)), -4) . "\n";
} else {
    echo "     ✗ NOT FOUND!\n";
}

// Check soal_images folder
echo "\n   public/uploads/soal_images/ folder:\n";
if (is_dir($soalImagesPath)) {
    echo "     ✓ Exists\n";
    echo "     Writable: " . (is_writable($soalImagesPath) ? '✓ Yes' : '✗ No') . "\n";
    echo "     Permissions: " . substr(sprintf('%o', fileperms($soalImagesPath)), -4) . "\n";
    
    $files = glob($soalImagesPath . '*');
    echo "     Files: " . count($files) . "\n";
    
    if (count($files) > 0) {
        echo "     Sample files:\n";
        foreach (array_slice($files, 0, 5) as $file) {
            $size = filesize($file);
            echo "       - " . basename($file) . " (" . number_format($size/1024, 2) . " KB)\n";
        }
    }
} else {
    echo "     ⚠ Folder belum ada (akan dibuat otomatis)\n";
}

echo "\n3. CONSTANTS.PHP CHECK:\n";
$constantsFile = __DIR__ . '/app/Config/Constants.php';
$constantsContent = file_get_contents($constantsFile);

if (strpos($constantsContent, "FCPATH . 'uploads/'") !== false) {
    echo "   ✓ UPLOAD_PATH sudah benar: FCPATH . 'uploads/'\n";
} elseif (strpos($constantsContent, "FCPATH . 'public/uploads/'") !== false) {
    echo "   ✗ UPLOAD_PATH SALAH: FCPATH . 'public/uploads/' (path ganda!)\n";
    echo "   ⚠ Harus diperbaiki menjadi: FCPATH . 'uploads/'\n";
} else {
    echo "   ⚠ UPLOAD_PATH tidak ditemukan atau format berbeda\n";
}

echo "\n4. CBTBANKSOAL.PHP CHECK:\n";
$controllerFile = __DIR__ . '/app/Controllers/Admin/CbtBankSoal.php';
if (file_exists($controllerFile)) {
    $controllerContent = file_get_contents($controllerFile);
    
    // Check uploadImage method
    if (strpos($controllerContent, "UPLOAD_PATH . 'soal_images/'") !== false) {
        echo "   ✓ uploadImage() menggunakan UPLOAD_PATH dengan benar\n";
    } else {
        echo "   ⚠ uploadImage() mungkin tidak menggunakan UPLOAD_PATH\n";
    }
    
    // Check backup method
    if (strpos($controllerContent, "WRITEPATH . 'uploads/'") !== false) {
        echo "   ✓ backup() menggunakan WRITEPATH dengan benar\n";
    } elseif (strpos($controllerContent, "WRITEPATH . UPLOAD_PATH") !== false) {
        echo "   ✗ backup() SALAH: menggunakan WRITEPATH . UPLOAD_PATH\n";
        echo "   ⚠ Harus diperbaiki menjadi: WRITEPATH . 'uploads/'\n";
    }
} else {
    echo "   ✗ File controller tidak ditemukan\n";
}

echo "\n=== KESIMPULAN ===\n";

$issues = [];
if (!is_dir($uploadPath)) {
    $issues[] = "Folder public/uploads/ tidak ada";
}
if (is_dir($uploadPath) && !is_writable($uploadPath)) {
    $issues[] = "Folder public/uploads/ tidak writable";
}
if (strpos($constantsContent, "FCPATH . 'public/uploads/'") !== false) {
    $issues[] = "UPLOAD_PATH di Constants.php salah (path ganda)";
}

if (empty($issues)) {
    echo "✓ Semua konfigurasi sudah benar!\n";
    echo "\nAnda bisa test upload gambar dari halaman tambah soal.\n";
    echo "URL: http://localhost/siakad/index.php/admin/cbt/banksoal/tambah_soal\n";
} else {
    echo "⚠ Ditemukan " . count($issues) . " masalah:\n";
    foreach ($issues as $i => $issue) {
        echo "   " . ($i+1) . ". $issue\n";
    }
    echo "\nSilakan perbaiki masalah di atas terlebih dahulu.\n";
}

echo "\n";
