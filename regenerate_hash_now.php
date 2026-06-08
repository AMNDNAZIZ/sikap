<?php
/**
 * Quick Regenerate Hash Script
 * Run this after file modifications
 */

echo "=================================================================\n";
echo "Regenerate Hash After Update\n";
echo "=================================================================\n\n";

$appPath = __DIR__ . '/app/';

// 1. Regenerate Security Hash
echo "1. Regenerating Security Hash...\n";

$criticalFiles = [
    'Helpers/license_helper.php',
    'Libraries/LicenseGuard.php',
    'Libraries/SecurityGuard.php',
    'Libraries/SecureConfig.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php',
    'Config/Filters.php'
];

$hashes = [];
$found = 0;

foreach ($criticalFiles as $file) {
    $path = $appPath . $file;
    
    if (file_exists($path)) {
        $hashes[$file] = hash_file('sha256', $path);
        $found++;
        echo "   ✓ {$file}\n";
    } else {
        echo "   ✗ {$file} (not found)\n";
    }
}

$hashFile = $appPath . 'Config/.security_hash';
$result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "   SUCCESS: Security hash saved ({$found} files)\n\n";
} else {
    echo "   ERROR: Failed to save security hash\n\n";
}

// 2. Regenerate License Hash
echo "2. Regenerating License Hash...\n";

$licenseFiles = [
    'Libraries/LicenseGuard.php',
    'Filters/LicenseFilter.php',
    'Models/LicenseModel.php'
];

$licHashes = [];
$licFound = 0;

foreach ($licenseFiles as $file) {
    $path = $appPath . $file;
    
    if (file_exists($path)) {
        $licHashes[$file] = sha1_file($path);
        $licFound++;
        echo "   ✓ {$file}\n";
    } else {
        echo "   ✗ {$file} (not found)\n";
    }
}

$licHashFile = $appPath . 'Config/.lic_hash';
$licResult = file_put_contents($licHashFile, json_encode($licHashes, JSON_PRETTY_PRINT));

if ($licResult !== false) {
    echo "   SUCCESS: License hash saved ({$licFound} files)\n\n";
} else {
    echo "   ERROR: Failed to save license hash\n\n";
}

// Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";
echo "Security Hash: " . ($result !== false ? "✓ SUCCESS" : "✗ FAILED") . " ({$found} files)\n";
echo "License Hash:  " . ($licResult !== false ? "✓ SUCCESS" : "✗ FAILED") . " ({$licFound} files)\n";
echo "\n";

if ($result !== false && $licResult !== false) {
    echo "✓ All hashes regenerated successfully!\n";
    echo "✓ Anti-tampering protection updated\n";
    echo "✓ Application ready to use\n";
} else {
    echo "✗ Some hashes failed to regenerate\n";
    echo "  Check write permissions on app/Config/\n";
}

echo "=================================================================\n";
