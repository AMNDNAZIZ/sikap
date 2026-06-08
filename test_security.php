<?php

/**
 * Test Security - Web Interface
 * 
 * Simple web interface untuk test security features
 * Akses: http://localhost/siakad/test_security.php
 */

// Only allow from localhost
$allowedHosts = ['127.0.0.1', '::1', 'localhost'];
$remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
$httpHost = $_SERVER['HTTP_HOST'] ?? '';

$isLocalhost = in_array($remoteAddr, $allowedHosts) || 
               strpos($httpHost, 'localhost') !== false ||
               strpos($httpHost, '127.0.0.1') !== false;

if (!$isLocalhost) {
    die('Access denied. This tool can only be accessed from localhost.');
}

$action = $_GET['action'] ?? 'menu';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Tools - SIAKAD</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .menu-item {
            background: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px;
            text-decoration: none;
            transition: background 0.3s;
        }
        .menu-item:hover {
            background: #0056b3;
        }
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 15px;
            margin: 20px 0;
            font-family: 'Courier New', monospace;
            white-space: pre-wrap;
            max-height: 500px;
            overflow-y: auto;
        }
        .success {
            color: #28a745;
            font-weight: bold;
        }
        .error {
            color: #dc3545;
            font-weight: bold;
        }
        .warning {
            color: #ffc107;
            font-weight: bold;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Security Tools - SIAKAD</h1>
        
        <?php if ($action === 'menu'): ?>
            <p>Pilih tool yang ingin dijalankan:</p>
            <div class="menu">
                <a href="?action=generate" class="menu-item">
                    🔨 Generate Hash
                </a>
                <a href="?action=verify" class="menu-item">
                    ✓ Verify Hash
                </a>
                <a href="?action=info" class="menu-item">
                    ℹ️ System Info
                </a>
            </div>
            
            <div style="margin-top: 30px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
                <strong>ℹ️ Info:</strong>
                <ul>
                    <li>Generate Hash: Buat security hash untuk file kritis</li>
                    <li>Verify Hash: Cek integritas file kritis</li>
                    <li>System Info: Lihat informasi sistem</li>
                </ul>
            </div>
            
        <?php elseif ($action === 'generate'): ?>
            <h2>Generate Security Hash</h2>
            <div class="output">
<?php
$appPath = __DIR__ . '/app/';
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
        $hash = hash_file('sha256', $path);
        $hashes[$file] = $hash;
        echo "✓ {$file}\n";
        echo "  Hash: " . substr($hash, 0, 16) . "...\n";
        $found++;
    } else {
        echo "✗ {$file} - NOT FOUND\n";
    }
}

$hashFile = $appPath . 'Config/.security_hash';
$result = file_put_contents($hashFile, json_encode($hashes, JSON_PRETTY_PRINT));

if ($result !== false) {
    echo "\n<span class='success'>✅ SUCCESS: Security hashes saved</span>\n";
    echo "   File: app/Config/.security_hash\n";
    echo "   Protected files: {$found}\n";
} else {
    echo "\n<span class='error'>❌ FAILED: Could not save security hashes</span>\n";
}
?>
            </div>
            <a href="?" class="back-link">← Kembali ke Menu</a>
            
        <?php elseif ($action === 'verify'): ?>
            <h2>Verify Security Hash</h2>
            <div class="output">
<?php
$appPath = __DIR__ . '/app/';
$hashFile = $appPath . 'Config/.security_hash';

if (!file_exists($hashFile)) {
    echo "<span class='error'>❌ ERROR: Security hash file not found</span>\n";
    echo "   Run: Generate Hash first\n";
} else {
    $expectedHashes = json_decode(file_get_contents($hashFile), true);
    
    $passed = 0;
    $failed = 0;
    
    foreach ($expectedHashes as $file => $expectedHash) {
        $path = $appPath . $file;
        
        if (!file_exists($path)) {
            echo "✗ {$file} - FILE MISSING\n";
            $failed++;
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
    
    echo "\nSummary:\n";
    echo "--------\n";
    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n";
    echo "Total:  " . count($expectedHashes) . "\n\n";
    
    if ($failed > 0) {
        echo "<span class='error'>❌ SECURITY VIOLATION DETECTED!</span>\n";
    } else {
        echo "<span class='success'>✅ SUCCESS: All critical files are intact</span>\n";
    }
}
?>
            </div>
            <a href="?" class="back-link">← Kembali ke Menu</a>
            
        <?php elseif ($action === 'info'): ?>
            <h2>System Information</h2>
            <div class="output">
<?php
echo "Hostname:       " . php_uname('n') . "\n";
echo "OS:             " . php_uname('s') . " " . php_uname('r') . "\n";
echo "Architecture:   " . php_uname('m') . "\n";
echo "PHP Version:    " . PHP_VERSION . "\n";
echo "System User:    " . get_current_user() . "\n";
echo "Document Root:  " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Server Software:" . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "\n";

$hashFile = __DIR__ . '/app/Config/.security_hash';
if (file_exists($hashFile)) {
    $hashes = json_decode(file_get_contents($hashFile), true);
    echo "<span class='success'>✓ Security hash file exists</span>\n";
    echo "  Protected files: " . count($hashes) . "\n";
} else {
    echo "<span class='warning'>⚠️  Security hash file not found</span>\n";
    echo "  Run: Generate Hash\n";
}
?>
            </div>
            <a href="?" class="back-link">← Kembali ke Menu</a>
        <?php endif; ?>
        
        <div style="margin-top: 30px; padding: 15px; background: #fff3cd; border-radius: 5px; border: 1px solid #ffc107;">
            <strong>⚠️ PENTING:</strong> File ini hanya untuk development/testing. Hapus atau proteksi di production!
        </div>
    </div>
</body>
</html>
