<?php
/**
 * Fix License Binding
 * Clear machine binding to allow re-binding with hardware signature
 */

// Database config
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'siakad';

echo "=== FIX LICENSE BINDING ===\n\n";

$db = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

// Get current license
$result = $db->query("SELECT * FROM app_license ORDER BY id DESC LIMIT 1");

if ($result && $row = $result->fetch_assoc()) {
    echo "CURRENT LICENSE:\n";
    echo "  ID: {$row['id']}\n";
    echo "  Machine ID: " . ($row['machine_id'] ? 'SET' : 'NULL') . "\n";
    echo "  Hardware Sig: " . ($row['hardware_signature'] ? 'SET' : 'NULL') . "\n\n";
    
    // Clear binding
    echo "CLEARING BINDING...\n";
    $stmt = $db->prepare("UPDATE app_license SET machine_id = NULL, hardware_signature = NULL WHERE id = ?");
    $stmt->bind_param('i', $row['id']);
    
    if ($stmt->execute()) {
        echo "✓ Binding cleared successfully\n\n";
        echo "NEXT STEPS:\n";
        echo "1. Access application from any browser (Chrome or Edge)\n";
        echo "2. System will auto-bind to current hardware\n";
        echo "3. All browsers on same machine will work\n";
    } else {
        echo "✗ Failed to clear binding: " . $stmt->error . "\n";
    }
    
    $stmt->close();
} else {
    echo "✗ No license found\n";
}

$db->close();

echo "\n=== DONE ===\n";
