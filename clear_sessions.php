<?php
// Clear all sessions - Simple version

$db = new mysqli(
    'localhost',
    'root',
    '',
    'siakad'
);

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("TRUNCATE TABLE ci_sessions");

if ($result) {
    echo "✅ All sessions cleared successfully!\n";
    echo "You can now login again.\n";
} else {
    echo "❌ Error clearing sessions: " . $db->error . "\n";
}

$db->close();
?>
