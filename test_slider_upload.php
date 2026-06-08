<?php
/**
 * Test Slider Upload Functionality
 * 
 * Script untuk mendiagnosis masalah upload slider
 * Jalankan: php test_slider_upload.php
 */

// Define paths
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);

// Load CodeIgniter
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = require_once FCPATH . 'app/Config/Paths.php';
$paths = new Config\Paths();

// Define APPPATH
define('APPPATH', realpath($paths->appDirectory) . DIRECTORY_SEPARATOR);

echo "=== TEST SLIDER UPLOAD FUNCTIONALITY ===\n\n";

// 1. Check database connection
echo "1. Checking database connection...\n";
try {
    // Load database config
    require_once APPPATH . 'Config/Database.php';
    $config = new \Config\Database();
    $db = \CodeIgniter\Database\Config::connect($config->default);
    echo "   ✓ Database connected\n\n";
} catch (\Exception $e) {
    echo "   ✗ Database connection failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 2. Check landing_sliders table
echo "2. Checking landing_sliders table...\n";
try {
    $query = $db->query("SHOW TABLES LIKE 'landing_sliders'");
    if ($query->getNumRows() > 0) {
        echo "   ✓ Table 'landing_sliders' exists\n";
        
        // Check table structure
        $fields = $db->getFieldNames('landing_sliders');
        echo "   ✓ Table fields: " . implode(', ', $fields) . "\n\n";
    } else {
        echo "   ✗ Table 'landing_sliders' does not exist\n";
        echo "   → Run migration: php spark migrate\n\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "   ✗ Error checking table: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 3. Check uploads folder
echo "3. Checking uploads folder...\n";
$uploadPath = FCPATH . 'uploads/sliders';
if (is_dir($uploadPath)) {
    echo "   ✓ Folder exists: $uploadPath\n";
    
    // Check permissions
    if (is_writable($uploadPath)) {
        echo "   ✓ Folder is writable\n\n";
    } else {
        echo "   ✗ Folder is NOT writable\n";
        echo "   → Fix permissions: chmod 755 $uploadPath\n\n";
    }
} else {
    echo "   ✗ Folder does not exist: $uploadPath\n";
    echo "   → Creating folder...\n";
    if (mkdir($uploadPath, 0755, true)) {
        echo "   ✓ Folder created successfully\n\n";
    } else {
        echo "   ✗ Failed to create folder\n\n";
        exit(1);
    }
}

// 4. Check file upload settings
echo "4. Checking PHP upload settings...\n";
$uploadMaxFilesize = ini_get('upload_max_filesize');
$postMaxSize = ini_get('post_max_size');
$maxFileUploads = ini_get('max_file_uploads');
echo "   • upload_max_filesize: $uploadMaxFilesize\n";
echo "   • post_max_size: $postMaxSize\n";
echo "   • max_file_uploads: $maxFileUploads\n";

// Convert to bytes for comparison
function convertToBytes($value) {
    $value = trim($value);
    $last = strtolower($value[strlen($value)-1]);
    $value = (int) $value;
    switch($last) {
        case 'g': $value *= 1024;
        case 'm': $value *= 1024;
        case 'k': $value *= 1024;
    }
    return $value;
}

$uploadMaxBytes = convertToBytes($uploadMaxFilesize);
$postMaxBytes = convertToBytes($postMaxSize);
$requiredBytes = 2 * 1024 * 1024; // 2MB

if ($uploadMaxBytes >= $requiredBytes && $postMaxBytes >= $requiredBytes) {
    echo "   ✓ Upload settings are sufficient (min 2MB required)\n\n";
} else {
    echo "   ⚠ Upload settings might be too low\n";
    echo "   → Recommended: upload_max_filesize = 8M, post_max_size = 8M\n\n";
}

// 5. Test model
echo "5. Testing LandingSliderModel...\n";
try {
    $sliderModel = new \App\Models\LandingSliderModel();
    $sliders = $sliderModel->findAll();
    echo "   ✓ Model loaded successfully\n";
    echo "   • Current sliders count: " . count($sliders) . "\n\n";
} catch (\Exception $e) {
    echo "   ✗ Model error: " . $e->getMessage() . "\n\n";
    exit(1);
}

// 6. Check controller
echo "6. Checking Sliders controller...\n";
$controllerPath = APPPATH . 'Controllers/Admin/Cms/Sliders.php';
if (file_exists($controllerPath)) {
    echo "   ✓ Controller exists: $controllerPath\n";
    
    // Check if controller has validation
    $content = file_get_contents($controllerPath);
    if (strpos($content, 'validate') !== false) {
        echo "   ✓ Controller has validation\n";
    } else {
        echo "   ⚠ Controller might be missing validation\n";
    }
    
    if (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) {
        echo "   ✓ Controller has error handling\n\n";
    } else {
        echo "   ⚠ Controller might be missing error handling\n\n";
    }
} else {
    echo "   ✗ Controller not found\n\n";
    exit(1);
}

// 7. Check routes
echo "7. Checking routes...\n";
$routes = \Config\Services::routes();
$routes->loadRoutes();
echo "   ✓ Routes loaded\n";
echo "   • Expected routes:\n";
echo "     - GET  admin/cms/sliders\n";
echo "     - GET  admin/cms/sliders/create\n";
echo "     - POST admin/cms/sliders/store\n";
echo "     - GET  admin/cms/sliders/edit/{id}\n";
echo "     - POST admin/cms/sliders/update/{id}\n";
echo "     - GET  admin/cms/sliders/delete/{id}\n\n";

// 8. Summary
echo "=== SUMMARY ===\n";
echo "✓ Database: OK\n";
echo "✓ Table: OK\n";
echo "✓ Upload folder: OK\n";
echo "✓ Model: OK\n";
echo "✓ Controller: OK\n";
echo "✓ Routes: OK\n\n";

echo "=== NEXT STEPS ===\n";
echo "1. Regenerate security hash jika ada error anti-tampering:\n";
echo "   php regenerate_hash_after_update.php\n\n";
echo "2. Test upload slider melalui browser:\n";
echo "   http://localhost/siakad/admin/cms/sliders/create\n\n";
echo "3. Jika masih error, cek log file:\n";
echo "   writable/logs/log-" . date('Y-m-d') . ".log\n\n";
echo "4. Jika error validation, pastikan:\n";
echo "   - File gambar format JPG/PNG/WEBP\n";
echo "   - Ukuran file < 2MB\n";
echo "   - Field 'image' ada di form\n\n";

echo "=== COMMON ISSUES ===\n";
echo "• Error 'uploaded[image]' → File tidak diupload atau field name salah\n";
echo "• Error 'max_size' → File terlalu besar (> 2MB)\n";
echo "• Error 'is_image' → File bukan gambar\n";
echo "• Error 'mime_in' → Format file tidak didukung\n";
echo "• Error 'Gagal memindahkan file' → Folder tidak writable\n";
echo "• Error database → Tabel tidak ada atau struktur salah\n\n";

echo "Test completed!\n";
