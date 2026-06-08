<?php

/**
 * Verify Security Hash - Simple version
 * 
 * Script untuk verifikasi integritas file kritis
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
echo "Verify Security Hash\n";
echo "=================================================================\n\n";

// Define paths
$appPath = __DIR__ . '/app/';
$hashFile = $appPath . 'Config/.security_hash';

// Check if hash file exists
if (!file_exists($hashFile)) {
    echo "❌ ERROR: Security hash file not found\n";
    echo "   File: app/Config/.security_hash\n";
    echo "   Run: php generate_security_hash.php\n";
    exit(1);
}

// Load expected hashes
$expectedHashes = json_decode(file_get_contents($hashFile), true);

if (!is_array($expectedHashes)) {
    echo "❌ ERROR: Invalid hash file format\n";
    exit(1);
}

echo "Verifying file integrity...\n\n";

$passed = 0;
$failed = 0;
$missing = 0;

foreach ($expectedHashes as $file => $expectedHash) {
    $path = $appPath . $file;
    
    if (!file_exists($path)) {
        echo "✗ {$file} - FILE MISSING\n";
        $missing++;
        continue;
    }
    
    $currentHash = hash_file('sha256', $path);
    
    if ($currentHash === $expectedHash) {
        echo "✓ {$file} - OK\n";
        $passed++;
    } else {
        echo "✗ {$file} - MODIFIED!\n";
        echo "  Expected: " . substr($expectedHash, 0, 16) . "...\n";
        echo "  Current:  " . substr($currentHash, 0, 16) . "...\n";
        $failed++;
    }
}

echo "\n";
echo "Summary:\n";
echo "--------\n";
echo "Passed:  {$passed}\n";
echo "Failed:  {$failed}\n";
echo "Missing: {$missing}\n";
echo "Total:   " . count($expectedHashes) . "\n";
echo "\n";

if ($failed > 0 || $missing > 0) {
    echo "❌ SECURITY VIOLATION DETECTED!\n";
    echo "   One or more critical files have been modified or are missing\n";
    echo "   This could indicate tampering or unauthorized changes\n";
    echo "\n";
    echo "   Actions:\n";
    echo "   1. Review the changes in modified files\n";
    echo "   2. If changes are legitimate, regenerate hash:\n";
    echo "      php generate_security_hash.php\n";
    echo "   3. If changes are unauthorized, restore from backup\n";
    exit(1);
} else {
    echo "✅ SUCCESS: All critical files are intact\n";
    echo "   No tampering detected\n";
    exit(0);
}

echo "\n=================================================================\n";
