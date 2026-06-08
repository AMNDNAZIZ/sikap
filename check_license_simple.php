<?php
/**
 * Simple License Check
 */

// Database config
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'siakad';

echo "=== LICENSE BROWSER DIAGNOSTIC ===\n\n";

// Connect to database
$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get license from database
$result = $db->query("SELECT * FROM app_license ORDER BY id DESC LIMIT 1");

if ($result && $row = $result->fetch_assoc()) {
    echo "LICENSE INFO:\n";
    echo "  License Key: " . substr($row['license_key'], 0, 20) . "...\n";
    echo "  Status: " . ($row['is_active'] ? 'Active' : 'Inactive') . "\n";
    echo "  Valid Until: " . $row['valid_until'] . "\n";
    echo "  Machine ID: " . ($row['machine_id'] ? substr($row['machine_id'], 0, 32) . '...' : 'NULL') . "\n";
    echo "  Hardware Sig: " . ($row['hardware_signature'] ? substr($row['hardware_signature'], 0, 32) . '...' : 'NULL') . "\n\n";
    
    // Check if hardware signature is empty
    if (empty($row['hardware_signature'])) {
        echo "⚠ ISSUE FOUND:\n";
        echo "  Hardware signature is NULL/empty in database\n";
        echo "  This causes different browsers to fail validation\n\n";
        
        echo "SOLUTION:\n";
        echo "  The hardware signature will be auto-bound on next access\n";
        echo "  OR you can manually clear it to allow re-binding:\n\n";
        echo "  SQL:\n";
        echo "  UPDATE app_license SET \n";
        echo "    machine_id = NULL,\n";
        echo "    hardware_signature = NULL\n";
        echo "  WHERE id = {$row['id']};\n\n";
        echo "  Then access from Edge browser to auto-bind\n";
    } else {
        echo "✓ Hardware signature is set\n";
        echo "  License should work in all browsers\n";
        echo "  If Edge still asks for activation, try:\n";
        echo "  1. Clear Edge browser cache and cookies\n";
        echo "  2. Close and reopen Edge\n";
        echo "  3. Access the application again\n";
    }
} else {
    echo "✗ No license found in database\n";
}

$db->close();

echo "\n=== END ===\n";
