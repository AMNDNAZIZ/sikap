<?php
require 'app/Config/Paths.php';
$paths = new \Config\Paths();
require $paths->systemDirectory . '/Boot.php';

// Bootstrap CI4
\CodeIgniter\Boot::bootWeb($paths);

$db = \Config\Database::connect();
try {
    $db->query("ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL AFTER active_session_id");
    echo "Added last_ip column\n";
} catch (\Exception $e) {
    echo "last_ip column might already exist: " . $e->getMessage() . "\n";
}

try {
    $db->query("ALTER TABLE users ADD COLUMN user_agent VARCHAR(255) NULL AFTER last_ip");
    echo "Added user_agent column\n";
} catch (\Exception $e) {
    echo "user_agent column might already exist: " . $e->getMessage() . "\n";
}
