<?php

/**
 * Generate License Hash
 * 
 * Script untuk generate .lic_hash (berbeda dengan .security_hash)
 * File ini untuk license integrity check
 */

// Only allow from localhost or CLI
if (php_sapi_name() !== 'cli') {
    $allowedHosts = ['127.0.0.1', '::1', 'localhost'];
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
    $httpHost = $_SERVER['HTTP_HOST'] ?? '';
    
    $isLocalhost = in_array($remoteAddr, $allowedHosts) || 
                   strpos($httpHost, 'localhost') !== false ||
                   strpos($httpHost, '127.0.0.1') !== false;
    
    if (!$isLocalhost) {
        die('Access denied. This tool can only be accessed from localhost.');
    }
}

echo "=================================================================\n";
echo "Generate License Hash (.lic_hash)\n";
echo "=================================================================\n\n";

// Define paths
$appPath = __DIR__ . '/app/';

// License files to protect (using SHA-1 for compatibility)
$licenseFiles = [
    'Libraries/LicenseGuard.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
];

echo "Generating license hashes...\n\n";

$hashes = [];
$found = 0;
$missing = 0;

foreach ($licenseFiles as $file) {
    $path = $appPath . $file;
    
    if (file_exists($path)) {
        $hash = sha1_file($path); // Using SHA-1 for .lic_hash
        $hashes[$file] = $hash;
        echo "✓ {$file}\n";
        echo "  Hash: " . substr($hash, 0, 16) . "...\n";
        $found++;
    } else {
        echo "✗ {$file} - NOT FOUND\n";
        $missing++;
    }
}

echo "\n";

if ($missing > 0) {
    echo "⚠️  WARNING: {$missing} file(s) not found\n";
}

// Save hashes to file
$hashFile = $appPath . 'Config/.lic_hash';
$result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "✅ SUCCESS: License hashes saved\n";
    echo "   File: app/Config/.lic_hash\n";
    echo "   Protected files: {$found}\n";
    
    // Add to .gitignore
    $gitignore = $appPath . 'Config/.gitignore';
    $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';
    
    if (strpos($content, '.lic_hash') === false) {
        file_put_contents($gitignore, $content . "\n.lic_hash\n");
        echo "   Added to .gitignore\n";
    }
} else {
    echo "❌ FAILED: Could not save license hashes\n";
    echo "   Check write permissions on app/Config/\n";
}

echo "\n=================================================================\n";
echo "Done!\n";
echo "\n";
echo "Note: .lic_hash uses SHA-1 (for license check)\n";
echo "      .security_hash uses SHA-256 (for security check)\n";
echo "      Both are needed for full protection\n";
echo "=================================================================\n";
