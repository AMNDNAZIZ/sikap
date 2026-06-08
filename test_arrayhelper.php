<?php
// Test script untuk memeriksa apakah kelas ArrayHelper dapat dimuat

// Include autoloader
require_once 'vendor/autoload.php';

echo "Testing ArrayHelper class...\n";

try {
    // Coba gunakan kelas ArrayHelper
    if (class_exists('CodeIgniter\Helpers\Arrays\ArrayHelper')) {
        echo "SUCCESS: Kelas ArrayHelper ditemukan!\n";
        
        // Coba panggil method statis untuk tes
        $result = CodeIgniter\Helpers\Arrays\ArrayHelper::dotSearch('test', ['test' => 'value']);
        echo "Kelas ArrayHelper berhasil diakses.\n";
    } else {
        echo "ERROR: Kelas ArrayHelper tidak ditemukan!\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

// Coba cara lain
echo "\nMencoba cara lain...\n";

// Note: 'use' statement cannot be placed inside a try-catch block in PHP.
// It must be in the global scope.
echo "Skip: 'use' statement cannot be tested dynamically inside a try-catch block.\n";

// Cek apakah file ada
echo "\nMemeriksa file ArrayHelper.php...\n";
$file_path = __DIR__ . '/system/Helpers/Arrays/ArrayHelper.php';
if (file_exists($file_path)) {
    echo "File ditemukan: $file_path\n";
    
    // Cek isi file
    $content = file_get_contents($file_path);
    if (strpos($content, 'namespace CodeIgniter\Helpers\Arrays') !== false) {
        echo "Namespace di file: CodeIgniter\\Helpers\\Arrays\n";
    } else {
        echo "Namespace tidak ditemukan di file.\n";
    }
} else {
    echo "File tidak ditemukan: $file_path\n";
}

// Cek autoloader
echo "\nMengecek autoloader...\n";
if (function_exists('spl_autoload_functions')) {
    echo "Autoloader tersedia.\n";
}

// Cek apakah kelas bisa diinstansiasi
echo "\nMencoba menginstansiasi kelas...\n";
try {
    // Coba akses kelas
    if (class_exists('CodeIgniter\Helpers\Arrays\ArrayHelper')) {
        echo "Kelas ArrayHelper ditemukan melalui class_exists.\n";
    } else {
        echo "Kelas ArrayHelper TIDAK ditemukan melalui class_exists.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nSelesai.\n";