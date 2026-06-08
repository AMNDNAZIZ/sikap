<?php

/**
 * Security Tools - Helper script untuk maintenance sistem keamanan
 * 
 * PENTING: File ini hanya untuk development/maintenance
 * Hapus atau proteksi file ini di production!
 */

// Only allow from localhost
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1') {
    die('Access denied. This tool can only be accessed from localhost.');
}

// Define path constants
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);

require __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$pathsConfig = APPPATH . 'Config/Paths.php';
$paths = require realpath($pathsConfig) ?: $pathsConfig;

// Get our framework paths
$paths = new Config\Paths();

// Load environment bootstrap
require_once SYSTEMPATH . 'bootstrap.php';

// Get action from command line or GET parameter
$action = $argv[1] ?? $_GET['action'] ?? 'help';

echo "=================================================================\n";
echo "Security Tools - SIAKAD License System\n";
echo "=================================================================\n\n";

switch ($action) {
    case 'generate-hash':
        generateSecurityHash();
        break;
        
    case 'verify-hash':
        verifySecurityHash();
        break;
        
    case 'migrate-config':
        migrateToEncryptedConfig();
        break;
        
    case 'check-license':
        checkLicenseStatus();
        break;
        
    case 'show-machine-id':
        showMachineInfo();
        break;
        
    case 'test-security':
        testSecurityFeatures();
        break;
        
    case 'help':
    default:
        showHelp();
        break;
}

echo "\n=================================================================\n";

// ============================================================================
// FUNCTIONS
// ============================================================================

function showHelp()
{
    echo "Available Commands:\n\n";
    echo "  generate-hash     Generate security hashes for critical files\n";
    echo "  verify-hash       Verify integrity of critical files\n";
    echo "  migrate-config    Migrate configuration to encrypted storage\n";
    echo "  check-license     Check current license status\n";
    echo "  show-machine-id   Show machine ID and hardware signature\n";
    echo "  test-security     Test all security features\n";
    echo "  help              Show this help message\n\n";
    
    echo "Usage:\n";
    echo "  CLI:     php security_tools.php [command]\n";
    echo "  Browser: http://localhost/siakad/security_tools.php?action=[command]\n\n";
    
    echo "Examples:\n";
    echo "  php security_tools.php generate-hash\n";
    echo "  php security_tools.php check-license\n";
}

function generateSecurityHash()
{
    echo "Generating security hashes...\n\n";
    
    try {
        $result = \App\Libraries\SecurityGuard::generateSecurityHashes();
        
        if ($result) {
            echo "✅ SUCCESS: Security hashes generated\n";
            echo "   File: app/Config/.security_hash\n\n";
            
            // Show generated hashes
            $hashFile = APPPATH . 'Config/.security_hash';
            if (file_exists($hashFile)) {
                $hashes = json_decode(file_get_contents($hashFile), true);
                echo "   Protected files:\n";
                foreach ($hashes as $file => $hash) {
                    echo "   - {$file}: " . substr($hash, 0, 16) . "...\n";
                }
            }
        } else {
            echo "❌ FAILED: Could not generate security hashes\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

function verifySecurityHash()
{
    echo "Verifying file integrity...\n\n";
    
    try {
        $result = \App\Libraries\SecurityGuard::verifyCriticalFiles();
        
        if ($result) {
            echo "✅ SUCCESS: All critical files are intact\n";
            echo "   No tampering detected\n";
        } else {
            echo "❌ FAILED: File tampering detected!\n";
            echo "   One or more critical files have been modified\n";
            echo "   Check logs for details\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

function migrateToEncryptedConfig()
{
    echo "Migrating configuration to encrypted storage...\n\n";
    
    try {
        $result = \App\Libraries\SecureConfig::migrateFromEnv();
        
        if ($result) {
            echo "✅ SUCCESS: Configuration migrated\n";
            echo "   File: app/Config/.secure_config\n";
            echo "   Secrets are now encrypted with machine-specific key\n\n";
            echo "   IMPORTANT: Keep your .env file as backup!\n";
        } else {
            echo "❌ FAILED: Could not migrate configuration\n";
        }
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

function checkLicenseStatus()
{
    echo "Checking license status...\n\n";
    
    try {
        $model = new \App\Models\LicenseModel();
        $license = $model->first();
        
        if (!$license) {
            echo "⚠️  WARNING: No license found\n";
            echo "   Application needs activation\n";
            return;
        }
        
        echo "License Information:\n";
        echo "-------------------\n";
        echo "Status:           " . ($license['status'] === 'active' ? '✅ Active' : '❌ Inactive') . "\n";
        echo "License Key:      " . substr($license['license_key'], 0, 4) . "-****-****-" . substr($license['license_key'], -4) . "\n";
        echo "Domain:           " . ($license['domain'] ?? 'Not set') . "\n";
        echo "Machine ID:       " . substr($license['machine_id'], 0, 16) . "...\n";
        echo "Hardware Sig:     " . (isset($license['hardware_signature']) ? substr($license['hardware_signature'], 0, 16) . "..." : 'Not set') . "\n";
        echo "Expires At:       " . ($license['expires_at'] ?? 'Never') . "\n";
        echo "Last Check:       " . ($license['last_check'] ?? 'Never') . "\n";
        
        // Check if expired
        if ($license['expires_at']) {
            $expiry = strtotime($license['expires_at']);
            $now = time();
            
            if ($expiry < $now) {
                $daysExpired = floor(($now - $expiry) / 86400);
                echo "\n⚠️  WARNING: License expired {$daysExpired} days ago\n";
            } else {
                $daysLeft = floor(($expiry - $now) / 86400);
                echo "\n✅ License valid for {$daysLeft} more days\n";
            }
        }
        
        // Validate hash integrity
        helper('license');
        $machineId = generate_machine_id();
        $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
        $expectedHash = hash('sha256', $license['license_key'] . $license['expires_at'] . $machineId . $hashSecret);
        
        if ($license['hash'] === $expectedHash) {
            echo "✅ Hash integrity: Valid\n";
        } else {
            echo "❌ Hash integrity: INVALID (possible tampering)\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

function showMachineInfo()
{
    echo "Machine Information:\n\n";
    
    try {
        helper('license');
        
        $machineId = generate_machine_id();
        $hardwareSignature = generate_hardware_signature();
        $installId = \App\Libraries\LicenseGuard::getInstallationId();
        
        echo "Machine ID:        {$machineId}\n";
        echo "Hardware Sig:      {$hardwareSignature}\n";
        echo "Installation ID:   {$installId}\n\n";
        
        echo "System Information:\n";
        echo "-------------------\n";
        echo "Hostname:          " . php_uname('n') . "\n";
        echo "OS:                " . php_uname('s') . " " . php_uname('r') . "\n";
        echo "Architecture:      " . php_uname('m') . "\n";
        echo "PHP Version:       " . PHP_VERSION . "\n";
        echo "System User:       " . get_current_user() . "\n";
        echo "Document Root:     " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
        echo "Server Software:   " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
        
        // Check if local environment
        if (is_local_environment()) {
            echo "\n⚠️  Running in LOCAL environment (license checks may be relaxed)\n";
        } else {
            echo "\n✅ Running in PRODUCTION environment\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}

function testSecurityFeatures()
{
    echo "Testing security features...\n\n";
    
    $passed = 0;
    $failed = 0;
    
    // Test 1: Security hash generation
    echo "1. Testing security hash generation...\n";
    try {
        $result = \App\Libraries\SecurityGuard::generateSecurityHashes();
        if ($result) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Test 2: File integrity verification
    echo "2. Testing file integrity verification...\n";
    try {
        $result = \App\Libraries\SecurityGuard::verifyCriticalFiles();
        if ($result) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: Tampering detected\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Test 3: License filter active
    echo "3. Testing license filter status...\n";
    try {
        $result = \App\Libraries\SecurityGuard::isLicenseFilterActive();
        if ($result) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: License filter not active\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Test 4: Machine ID generation
    echo "4. Testing machine ID generation...\n";
    try {
        helper('license');
        $machineId = generate_machine_id();
        if (!empty($machineId) && strlen($machineId) === 64) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: Invalid machine ID\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Test 5: Hardware signature generation
    echo "5. Testing hardware signature generation...\n";
    try {
        helper('license');
        $hwSig = generate_hardware_signature();
        if (!empty($hwSig) && strlen($hwSig) === 64) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: Invalid hardware signature\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Test 6: Secure config
    echo "6. Testing secure config access...\n";
    try {
        $serverUrl = \App\Libraries\LicenseGuard::getServerURL();
        $hashSecret = \App\Libraries\LicenseGuard::getHashSecret();
        
        if (!empty($serverUrl) && !empty($hashSecret)) {
            echo "   ✅ PASS\n";
            $passed++;
        } else {
            echo "   ❌ FAIL: Config values empty\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "   ❌ FAIL: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    // Summary
    echo "\n";
    echo "Test Summary:\n";
    echo "-------------\n";
    echo "Passed: {$passed}\n";
    echo "Failed: {$failed}\n";
    echo "Total:  " . ($passed + $failed) . "\n\n";
    
    if ($failed === 0) {
        echo "✅ All security features are working correctly!\n";
    } else {
        echo "⚠️  Some security features need attention\n";
    }
}
