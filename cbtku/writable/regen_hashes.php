<?php
/**
 * Standalone Hash Regenerator for CBTku (Writable Version)
 * If you access this via URL, ensure your server allows direct access to the 'writable' folder.
 * Usage: domain.com/writable/regen_hashes.php?key=SaKa2026_FixHash
 */

// 🔒 SECURITY: Change this or use it as is
$secretKey = "SaKa2026_FixHash";

if (!isset($_GET['key']) || $_GET['key'] !== $secretKey) {
    header('HTTP/1.0 403 Forbidden');
    die("Unauthorized access.");
}

// Detection of paths
$basePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

if (!is_dir($basePath)) {
    die("Error: Could not find 'app' directory.");
}

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
$output = "<h2>Regenerating Hashes...</h2><ul>";

foreach ($protectedFiles as $file) {
    $fullPath = $basePath . str_replace('/', DIRECTORY_SEPARATOR, $file);
    if (file_exists($fullPath)) {
        $hash = sha1_file($fullPath);
        $hashes[$file] = $hash;
        $output .= "<li>✅ <b>$file</b>: <small>$hash</small></li>";
    } else {
        $output .= "<li>❌ <b style='color:red'>Not Found</b>: $file</li>";
    }
}

$output .= "</ul>";

$hashFile = $basePath . 'Config' . DIRECTORY_SEPARATOR . '.lic_hash';

if (file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))) {
    $output .= "<h3 style='color:green'>SUCCESS! .lic_hash updated with " . count($hashes) . " files.</h3>";
} else {
    $output .= "<h3 style='color:red'>FAILED! Could not write to $hashFile.</h3>";
}

echo $output;
