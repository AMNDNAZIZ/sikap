<?php

/**
 * Generate Security Hash - Simple version
 * 
 * Script sederhana untuk generate security hash tanpa bootstrap penuh
 */

// Only allow from localhost or CLI
if (php_sapi_name() !== 'cli') {
    // If running via web, check if localhost
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
echo "Generate Security Hash\n";
echo "=================================================================\n\n";

// Define paths
$appPath = __DIR__ . '/app/';

// Critical files to protect
$criticalFiles = [
    'Helpers/license_helper.php',
    'Libraries/LicenseGuard.php',
    'Libraries/SecurityGuard.php',
    'Libraries/SecureConfig.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
    'Config/Filters.php'
];

echo "Generating security hashes...\n\n";

$hashes = [];
$found = 0;
$missing = 0;

foreach ($criticalFiles as $file) {
    $path = $appPath . $file;
    
    if (file_exists($path)) {
        $hash = hash_file('sha256', $path);
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
$hashFile = $appPath . 'Config/.security_hash';
$result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "✅ SUCCESS: Security hashes saved\n";
    echo "   File: app/Config/.security_hash\n";
    echo "   Protected files: {$found}\n";
    
    // Add to .gitignore
    $gitignore = $appPath . 'Config/.gitignore';
    $content = file_exists($gitignore) ? file_get_contents($gitignore) : '';
    
    if (strpos($content, '.security_hash') === false) {
        file_put_contents($gitignore, $content . "\n.security_hash\n");
        echo "   Added to .gitignore\n";
    }
} else {
    echo "❌ FAILED: Could not save security hashes\n";
    echo "   Check write permissions on app/Config/\n";
}

echo "\n=================================================================\n";
echo "Done!\n";
echo "=================================================================\n";
