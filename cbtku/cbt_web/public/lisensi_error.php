<?php
/**
 * REGENERATE LICENSE HASH FILE - CUSTOM STRUCTURE SUPPORT
 * 
 * Upload ke public_html/ dan akses via browser
 * https://yourdomain.com/regenerate_hash_custom.php
 * 
 * Support untuk struktur folder custom:
 * - public_html/ (file public)
 * - cbtku/ (root aplikasi - sejajar dengan public_html)
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<!DOCTYPE html><html><head>";
echo "<title>Regenerate License Hash - Custom Structure</title>";
echo "<style>
body { font-family: monospace; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
h1 { color: #4ec9b0; }
.success { background: #1f3c1f; color: #4ec9b0; padding: 15px; margin: 10px 0; border-left: 4px solid #4ec9b0; }
.error { background: #4a1f1f; color: #ff6b6b; padding: 15px; margin: 10px 0; border-left: 4px solid #ff0000; }
.warning { background: #3c3c1f; color: #dcdcaa; padding: 15px; margin: 10px 0; border-left: 4px solid #dcdcaa; }
.info { background: #1f3c3c; color: #4ec9b0; padding: 10px; margin: 10px 0; }
pre { background: #252526; padding: 15px; overflow-x: auto; font-size: 12px; }
.btn { background: #0e639c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block; margin: 10px 5px; border: none; cursor: pointer; }
code { background: #252526; padding: 2px 6px; border-radius: 3px; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
td { padding: 8px; border: 1px solid #3c3c3c; }
td:first-child { width: 40%; }
.status-ok { color: #4ec9b0; }
.status-fail { color: #ff6b6b; }
input[type=text] { background: #252526; color: #d4d4d4; border: 1px solid #3c3c3c; padding: 8px; width: 100%; max-width: 500px; }
</style></head><body>";

echo "<h1>🔐 Regenerate License Hash - Custom Structure</h1>";

// Detect app root - Support custom structure
$possibleRoots = [
    __DIR__ . '/',                          // Same folder as script
    __DIR__ . '/../',                       // Parent folder
    __DIR__ . '/../cbtku/',                 // Sibling folder 'cbtku'
    dirname(__DIR__) . '/',                 // Parent via dirname
    dirname(__DIR__) . '/cbtku/',           // Parent/cbtku
    dirname(dirname(__DIR__)) . '/cbtku/',  // Grandparent/cbtku
];

// Add custom path from query string
if (isset($_GET['custom_root'])) {
    $customPath = rtrim($_GET['custom_root'], '/') . '/';
    array_unshift($possibleRoots, $customPath);
}

$appRoot = null;
$triedPaths = [];

foreach ($possibleRoots as $root) {
    $configPath = $root . 'app/Config';
    $triedPaths[] = [
        'path' => $root,
        'config' => $configPath,
        'exists' => is_dir($configPath),
        'real' => @realpath($root)
    ];
    
    if (is_dir($configPath)) {
        $appRoot = $root;
        break;
    }
}

if (!$appRoot) {
    echo "<div class='error'>❌ Cannot find app/Config/ folder!</div>";
    echo "<p><strong>Current script location:</strong> <code>" . __DIR__ . "</code></p>";
    
    echo "<hr>";
    echo "<h2>📁 Paths Tried:</h2>";
    echo "<table>";
    echo "<tr><td><strong>Root Path</strong></td><td><strong>Config Path</strong></td><td><strong>Status</strong></td></tr>";
    
    foreach ($triedPaths as $tried) {
        $status = $tried['exists'] ? "<span class='status-ok'>✅ EXISTS</span>" : "<span class='status-fail'>❌ NOT FOUND</span>";
        $realPath = $tried['real'] ? $tried['real'] : 'N/A';
        echo "<tr>";
        echo "<td><code>" . htmlspecialchars($tried['path']) . "</code><br><small>Real: $realPath</small></td>";
        echo "<td><code>" . htmlspecialchars($tried['config']) . "</code></td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<hr>";
    echo "<h2>🔧 Manual Path Configuration</h2>";
    echo "<div class='info'>";
    echo "<p><strong>Struktur folder Anda:</strong></p>";
    echo "<pre>";
    echo "/home/username/\n";
    echo "├── public_html/          ← Script ini ada di sini\n";
    echo "└── cbtku/                ← Root aplikasi (app/, system/, writable/)\n";
    echo "    ├── app/\n";
    echo "    │   └── Config/\n";
    echo "    ├── system/\n";
    echo "    └── writable/\n";
    echo "</pre>";
    echo "</div>";
    
    echo "<form method='get'>";
    echo "<p><strong>Masukkan path lengkap ke folder root aplikasi:</strong></p>";
    echo "<p><input type='text' name='custom_root' placeholder='/home/username/cbtku/' value='" . ($_GET['custom_root'] ?? '') . "' /></p>";
    echo "<p><button type='submit' class='btn'>🔍 Check Path</button></p>";
    echo "</form>";
    
    echo "<div class='warning'>";
    echo "<p><strong>💡 Tips mencari path:</strong></p>";
    echo "<ol>";
    echo "<li>Via cPanel File Manager: Lihat path di address bar</li>";
    echo "<li>Via SSH: <code>pwd</code> untuk melihat current directory</li>";
    echo "<li>Biasanya: <code>/home/username/cbtku/</code></li>";
    echo "<li>Atau: <code>/home/username/public_html/../cbtku/</code></li>";
    echo "</ol>";
    echo "</div>";
    
    // Try to help find the path
    echo "<hr>";
    echo "<h2>🔍 Auto-detect Attempt</h2>";
    echo "<p>Scanning parent directories...</p>";
    
    $currentDir = __DIR__;
    $maxLevels = 3;
    $foundPaths = [];
    
    for ($i = 0; $i < $maxLevels; $i++) {
        $currentDir = dirname($currentDir);
        
        // Check for cbtku folder
        $cbtPath = $currentDir . '/cbtku/app/Config';
        if (is_dir($cbtPath)) {
            $foundPaths[] = $currentDir . '/cbtku/';
        }
        
        // Check for app folder directly
        $appPath = $currentDir . '/app/Config';
        if (is_dir($appPath)) {
            $foundPaths[] = $currentDir . '/';
        }
        
        // List directories
        if (is_readable($currentDir)) {
            $dirs = @scandir($currentDir);
            if ($dirs) {
                foreach ($dirs as $dir) {
                    if ($dir === '.' || $dir === '..') continue;
                    $fullPath = $currentDir . '/' . $dir;
                    if (is_dir($fullPath . '/app/Config')) {
                        $foundPaths[] = $fullPath . '/';
                    }
                }
            }
        }
    }
    
    if (count($foundPaths) > 0) {
        echo "<div class='success'>✅ Found " . count($foundPaths) . " possible path(s):</div>";
        echo "<ul>";
        foreach (array_unique($foundPaths) as $path) {
            echo "<li><a href='?custom_root=" . urlencode($path) . "' class='btn'>Use: <code>$path</code></a></li>";
        }
        echo "</ul>";
    } else {
        echo "<div class='warning'>⚠️ No app/Config folder found in nearby directories</div>";
    }
    
    exit;
}

echo "<div class='success'>✅ App root found: <code>" . realpath($appRoot) . "</code></div>";

// Store custom root in session for next requests
if (isset($_GET['custom_root'])) {
    $customRootParam = '&custom_root=' . urlencode($_GET['custom_root']);
} else {
    $customRootParam = '';
}

// Define protected files (sesuai dengan LicenseGuard.php)
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

// Check if action is to regenerate
if (isset($_GET['action']) && $_GET['action'] === 'regenerate') {
    echo "<h2>🔄 Regenerating Hash Files...</h2>";
    
    // Step 1: Generate .lic_hash
    echo "<h3>Step 1: Generate .lic_hash</h3>";
    
    $hashes = [];
    $missingFiles = [];
    
    foreach ($protectedFiles as $file) {
        $fullPath = $appRoot . 'app/' . $file;
        
        if (file_exists($fullPath)) {
            $hash = sha1_file($fullPath);
            $hashes[$file] = $hash;
            echo "<div class='info'>✅ $file: <code>" . substr($hash, 0, 16) . "...</code></div>";
        } else {
            $missingFiles[] = $file;
            echo "<div class='error'>❌ $file: NOT FOUND at <code>$fullPath</code></div>";
        }
    }
    
    if (count($missingFiles) > 0) {
        echo "<div class='warning'>";
        echo "<p>⚠️ Warning: " . count($missingFiles) . " file(s) not found:</p>";
        echo "<ul>";
        foreach ($missingFiles as $file) {
            echo "<li><code>$file</code></li>";
        }
        echo "</ul>";
        echo "<p>Upload these files from your localhost before proceeding.</p>";
        echo "</div>";
    }
    
    // Write .lic_hash
    $hashFile = $appRoot . 'app/Config/.lic_hash';
    $hashJson = json_encode($hashes, JSON_PRETTY_PRINT);
    
    // Backup old hash file
    if (file_exists($hashFile)) {
        $backupFile = $hashFile . '.backup.' . date('YmdHis');
        if (copy($hashFile, $backupFile)) {
            echo "<div class='success'>✅ Backup created: <code>" . basename($backupFile) . "</code></div>";
        }
    }
    
    $result = @file_put_contents($hashFile, $hashJson);
    
    if ($result === false) {
        echo "<div class='error'>❌ Failed to write .lic_hash file!</div>";
        echo "<p>Path: <code>$hashFile</code></p>";
        echo "<p>Possible reasons:</p>";
        echo "<ul>";
        echo "<li>Permission denied (folder not writable)</li>";
        echo "<li>Path incorrect</li>";
        echo "</ul>";
        echo "<p>Try manual fix:</p>";
        echo "<pre>chmod 755 " . $appRoot . "app/Config/\nchmod 644 $hashFile</pre>";
    } else {
        echo "<div class='success'>✅ .lic_hash file written successfully! ($result bytes)</div>";
        echo "<p>Location: <code>$hashFile</code></p>";
    }
    
    // Step 2: Generate .lic_checksum
    echo "<hr>";
    echo "<h3>Step 2: Generate .lic_checksum</h3>";
    
    $checksumGenerated = false;
    
    // Try to load CodeIgniter to access database
    if (file_exists($appRoot . 'app/Config/Database.php')) {
        try {
            // Simple database connection
            require_once $appRoot . 'app/Config/Database.php';
            $dbConfig = new \Config\Database();
            $db = $dbConfig->default;
            
            $conn = new mysqli(
                $db['hostname'],
                $db['username'],
                $db['password'],
                $db['database']
            );
            
            if ($conn->connect_error) {
                echo "<div class='error'>❌ Database connection failed: " . $conn->connect_error . "</div>";
            } else {
                echo "<div class='success'>✅ Database connected</div>";
                
                // Get active license
                $result = $conn->query("SELECT * FROM app_license WHERE status = 'active' LIMIT 1");
                
                if ($result && $result->num_rows > 0) {
                    $license = $result->fetch_assoc();
                    
                    echo "<div class='info'>📄 License found: " . substr($license['license_key'], 0, 8) . "...</div>";
                    
                    // Generate checksum
                    $checksumData = [
                        'license_key' => $license['license_key'],
                        'machine_id' => $license['machine_id'],
                        'expires_at' => $license['expires_at'],
                        'created_at' => $license['created_at']
                    ];
                    
                    // Load secret from LService.php
                    $secret = 'default_secret_key_change_this'; // Default
                    if (file_exists($appRoot . 'app/Config/LService.php')) {
                        $lserviceContent = file_get_contents($appRoot . 'app/Config/LService.php');
                        if (preg_match("/protected \\\$secret = '([^']+)'/", $lserviceContent, $matches)) {
                            $secret = $matches[1];
                            echo "<div class='info'>🔑 Secret loaded from LService.php</div>";
                        }
                    }
                    
                    $checksum = hash('sha256', json_encode($checksumData) . $secret);
                    
                    $checksumFile = $appRoot . 'app/Config/.lic_checksum';
                    
                    // Backup old checksum
                    if (file_exists($checksumFile)) {
                        $backupFile = $checksumFile . '.backup.' . date('YmdHis');
                        copy($checksumFile, $backupFile);
                    }
                    
                    $result = @file_put_contents($checksumFile, $checksum);
                    
                    if ($result === false) {
                        echo "<div class='error'>❌ Failed to write .lic_checksum file!</div>";
                    } else {
                        echo "<div class='success'>✅ .lic_checksum file written successfully!</div>";
                        echo "<p>Checksum: <code>" . substr($checksum, 0, 32) . "...</code></p>";
                        $checksumGenerated = true;
                    }
                } else {
                    echo "<div class='warning'>⚠️ No active license found in database</div>";
                    echo "<p>You need to activate the application first.</p>";
                }
                
                $conn->close();
            }
        } catch (Exception $e) {
            echo "<div class='error'>❌ Error: " . $e->getMessage() . "</div>";
        }
    }
    
    if (!$checksumGenerated) {
        echo "<div class='info'>ℹ️ Checksum not generated (no active license or database error)</div>";
        echo "<p>This is OK if you haven't activated the application yet.</p>";
    }
    
    // Step 3: Summary
    echo "<hr>";
    echo "<h2>✅ Hash Regeneration Complete!</h2>";
    
    echo "<div class='success'>";
    echo "<p><strong>What was done:</strong></p>";
    echo "<ul>";
    echo "<li>✅ Generated .lic_hash with " . count($hashes) . " file hashes</li>";
    if ($checksumGenerated) {
        echo "<li>✅ Generated .lic_checksum from database</li>";
    }
    echo "<li>✅ Created backups of old files</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div class='info'>";
    echo "<p><strong>Next steps:</strong></p>";
    echo "<ol>";
    echo "<li>Clear cache: Delete all files in <code>" . $appRoot . "writable/cache/</code></li>";
    echo "<li>Test access: <a href='activate' class='btn'>Go to /activate</a></li>";
    echo "<li>If successful, delete this file: <code>rm regenerate_hash_custom.php</code></li>";
    echo "</ol>";
    echo "</div>";
    
    if (count($missingFiles) > 0) {
        echo "<div class='warning'>";
        echo "<p><strong>⚠️ Action required:</strong></p>";
        echo "<p>Upload these missing files from localhost:</p>";
        echo "<ul>";
        foreach ($missingFiles as $file) {
            echo "<li><code>app/$file</code></li>";
        }
        echo "</ul>";
        echo "<p>Then run this script again.</p>";
        echo "</div>";
    }
    
} else {
    // Show current status
    echo "<h2>📊 Current Status</h2>";
    
    echo "<table>";
    echo "<tr><td><strong>File</strong></td><td><strong>Status</strong></td><td><strong>Current Hash</strong></td></tr>";
    
    $allFilesExist = true;
    
    foreach ($protectedFiles as $file) {
        $fullPath = $appRoot . 'app/' . $file;
        $exists = file_exists($fullPath);
        
        if ($exists) {
            $hash = sha1_file($fullPath);
            $status = "<span class='status-ok'>✅ EXISTS</span>";
            $hashDisplay = "<code>" . substr($hash, 0, 16) . "...</code>";
        } else {
            $status = "<span class='status-fail'>❌ MISSING</span>";
            $hashDisplay = "-";
            $allFilesExist = false;
        }
        
        echo "<tr><td>$file</td><td>$status</td><td>$hashDisplay</td></tr>";
    }
    
    echo "</table>";
    
    // Check current hash file
    echo "<hr>";
    echo "<h2>📄 Current Hash File</h2>";
    
    $hashFile = $appRoot . 'app/Config/.lic_hash';
    
    if (file_exists($hashFile)) {
        $hashContent = file_get_contents($hashFile);
        $currentHashes = json_decode($hashContent, true);
        
        if ($currentHashes) {
            echo "<div class='success'>✅ Hash file exists and valid JSON</div>";
            echo "<p>Files tracked: " . count($currentHashes) . "</p>";
            
            // Check for mismatches
            $mismatches = [];
            foreach ($protectedFiles as $file) {
                $fullPath = $appRoot . 'app/' . $file;
                if (file_exists($fullPath)) {
                    $actualHash = sha1_file($fullPath);
                    $storedHash = $currentHashes[$file] ?? null;
                    
                    if ($storedHash && $actualHash !== $storedHash) {
                        $mismatches[] = $file;
                    }
                }
            }
            
            if (count($mismatches) > 0) {
                echo "<div class='error'>";
                echo "<p>❌ Hash mismatch detected for " . count($mismatches) . " file(s):</p>";
                echo "<ul>";
                foreach ($mismatches as $file) {
                    echo "<li><code>$file</code></li>";
                }
                echo "</ul>";
                echo "<p><strong>This is why you're seeing the error!</strong></p>";
                echo "</div>";
            } else {
                echo "<div class='success'>✅ All hashes match!</div>";
            }
        } else {
            echo "<div class='error'>❌ Hash file exists but invalid JSON</div>";
        }
    } else {
        echo "<div class='error'>❌ Hash file not found: <code>$hashFile</code></div>";
    }
    
    // Check checksum file
    echo "<hr>";
    echo "<h2>📄 Current Checksum File</h2>";
    
    $checksumFile = $appRoot . 'app/Config/.lic_checksum';
    
    if (file_exists($checksumFile)) {
        $checksum = file_get_contents($checksumFile);
        echo "<div class='success'>✅ Checksum file exists</div>";
        echo "<p>Checksum: <code>" . substr($checksum, 0, 32) . "...</code></p>";
    } else {
        echo "<div class='warning'>⚠️ Checksum file not found</div>";
        echo "<p>This is OK if you haven't activated the application yet.</p>";
    }
    
    // Action button
    echo "<hr>";
    echo "<h2>🔧 Action</h2>";
    
    if (!$allFilesExist) {
        echo "<div class='warning'>";
        echo "<p>⚠️ Some files are missing. Upload them first before regenerating hash.</p>";
        echo "</div>";
    }
    
    echo "<p><a href='?action=regenerate$customRootParam' class='btn'>🔄 Regenerate Hash Files</a></p>";
    
    echo "<div class='info'>";
    echo "<p><strong>What this will do:</strong></p>";
    echo "<ul>";
    echo "<li>Backup current .lic_hash and .lic_checksum</li>";
    echo "<li>Generate new .lic_hash with current file hashes</li>";
    echo "<li>Generate new .lic_checksum from database</li>";
    echo "<li>Fix hash mismatch error</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";
echo "<p><strong>⚠️ DELETE THIS FILE after fixing:</strong> <code>rm regenerate_hash_custom.php</code></p>";
echo "<p><small>Generated: " . date('Y-m-d H:i:s') . " | App root: " . realpath($appRoot) . "</small></p>";

echo "</body></html>";
?>
