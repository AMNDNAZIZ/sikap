<?php
// Test script untuk memeriksa apakah kelas ArrayHelper dapat dimuat

// Include autoloader
require_once 'vendor/autoload.php';

echo "Testing ArrayHelper class...\n";

// Cek apakah kelas ada
if (class_exists('CodeIgniter\Helpers\Arrays\ArrayHelper')) {
    echo "SUCCESS: Kelas ArrayHelper ditemukan!\n";
    
    // Coba akses kelas
    try {
        // Coba panggil method statis
        $result = CodeIgniter\Helpers\Arrays\ArrayHelper::dotSearch('test', ['test' => 'value']);
        echo "Kelas ArrayHelper berhasil diakses.\n";
    } catch (Exception $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
} else {
    echo "ERROR: Kelas ArrayHelper tidak ditemukan!\n";
}

// Cek file
$file_path = __DIR__ . '/system/Helpers/Arrays/ArrayHelper.php';
if (file_exists($file_path)) {
    echo "File ditemukan: $file_path\n";
    
    // Cek namespace di file
    $content = file_get_contents($file_path);
    if (strpos($content, 'namespace CodeIgniter\Helpers\Arrays;') !== false) {
        echo "Namespace di file: CodeIgniter\\Helpers\\Arrays\n";
    } else {
        echo "Namespace tidak ditemukan di file.\n";
    }
} else {
    echo "File tidak ditemukan: $file_path\n";
}

echo "Selesai.\n";