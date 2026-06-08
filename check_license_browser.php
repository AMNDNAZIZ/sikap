<?php
/**
 * Check License Status per Browser
 * Diagnostic tool untuk melihat perbedaan antara browser
 */

require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Load CI helpers
require_once __DIR__ . '/app/Helpers/license_helper.php';

echo "=== LICENSE BROWSER DIAGNOSTIC ===\n\n";

// 1. Check current environment
echo "1. ENVIRONMENT:\n";
echo "   PHP_OS_FAMILY: " . PHP_OS_FAMILY . "\n";
echo "   Is Local: " . (is_local_environment() ? 'Yes' : 'No') . "\n";
echo "   Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n\n";

// 2. Generate identifiers
echo "2. IDENTIFIERS:\n";
$machineId = generate_machine_id();
$hardwareSignature = generate_hardware_signature();
$installId = \App\Libraries\LicenseGuard::getInstallationId();

echo "   Machine ID: " . substr($machineId, 0, 32) . "...\n";
echo "   Hardware Signature: " . substr($hardwareSignature, 0, 32) . "...\n";
echo "   Installation ID: " . substr($installId, 0, 32) . "...\n\n";

// 3. Check database
$db = new mysqli(
    $_ENV['database_default_hostname'] ?? 'localhost',
    $_ENV['database_default_username'] ?? 'root',
    $_ENV['database_default_password'] ?? '',
    $_ENV['database_default_database'] ?? 'siakad'
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "3. DATABASE LICENSE:\n";
$result = $db->query("SELECT * FROM app_license ORDER BY id DESC LIMIT 1");

if ($result && $row = $result->fetch_assoc()) {
    echo "   License Key: " . substr($row['license_key'], 0, 20) . "...\n";
    echo "   Status: " . ($row['is_active'] ? 'Active' : 'Inactive') . "\n";
    echo "   Valid Until: " . $row['valid_until'] . "\n";
    echo "   Machine ID (DB): " . substr($row['machine_id'] ?? 'NULL', 0, 32) . "...\n";
    echo "   Hardware Sig (DB): " . substr($row['hardware_signature'] ?? 'NULL', 0, 32) . "...\n\n";
    
    // 4. Compare
    echo "4. COMPARISON:\n";
    $machineMatch = ($row['machine_id'] === $machineId);
    $hardwareMatch = empty($row['hardware_signature']) || ($row['hardware_signature'] === $hardwareSignature);
    
    echo "   Machine ID Match: " . ($machineMatch ? '✓ Yes' : '✗ No') . "\n";
    echo "   Hardware Sig Match: " . ($hardwareMatch ? '✓ Yes' : '✗ No') . "\n\n";
    
    if (!$machineMatch) {
        echo "   ⚠ Machine ID mismatch!\n";
        echo "   Expected: " . substr($row['machine_id'], 0, 32) . "...\n";
        echo "   Current:  " . substr($machineId, 0, 32) . "...\n\n";
    }
    
    if (!$hardwareMatch) {
        echo "   ⚠ Hardware Signature mismatch!\n";
        echo "   Expected: " . substr($row['hardware_signature'] ?? 'NULL', 0, 32) . "...\n";
        echo "   Current:  " . substr($hardwareSignature, 0, 32) . "...\n\n";
    }
    
    // 5. Recommendation
    echo "5. RECOMMENDATION:\n";
    if ($machineMatch && $hardwareMatch) {
        echo "   ✓ License should work in all browsers\n";
    } elseif (is_local_environment()) {
        echo "   ℹ Local environment - validation skipped\n";
    } else {
        echo "   ⚠ License binding issue detected\n";
        echo "   Solution: Update database with current signatures\n\n";
        echo "   SQL to fix:\n";
        echo "   UPDATE app_license SET \n";
        echo "     machine_id = '$machineId',\n";
        echo "     hardware_signature = '$hardwareSignature'\n";
        echo "   WHERE id = {$row['id']};\n";
    }
} else {
    echo "   ✗ No license found in database\n";
}

$db->close();

echo "\n=== END ===\n";
