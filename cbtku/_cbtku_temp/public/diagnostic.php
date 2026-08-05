<?php
/**
 * CBTku self-diagnostic tool
 * Place this in your public/ folder and access it via browser.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>CBTku Diagnostic Tool</h1>";

// 1. Check PHP Version
echo "<h2>1. PHP Environment</h2>";
echo "PHP Version: " . PHP_VERSION . " (Required: >= 8.1 for CI 4.4+)<br>";
echo "Current Directory: " . __DIR__ . "<br>";

// 2. Check Directories
echo "<h2>2. Directory Permissions</h2>";
$writable = dirname(__DIR__) . '/writable';
if (is_dir($writable)) {
    echo "Writable folder: " . (is_writable($writable) ? "✅ Writable" : "❌ NOT Writable (Set to 775 or 777)") . "<br>";
} else {
    echo "❌ Writable folder NOT FOUND at $writable<br>";
}

// 3. Check Database
echo "<h2>3. Database Connection & Schema</h2>";
$envFile = dirname(__DIR__) . '/.env';
$hostname = 'localhost';
$username = '';
$password = '';
$database = '';

if (file_exists($envFile)) {
    echo "Found .env file. Reading credentials...<br>";
    $env = file_get_contents($envFile);
    if (preg_match('/database\.default\.hostname\s*=\s*(.*)/', $env, $m))
        $hostname = trim($m[1]);
    if (preg_match('/database\.default\.database\s*=\s*(.*)/', $env, $m))
        $database = trim($m[1]);
    if (preg_match('/database\.default\.username\s*=\s*(.*)/', $env, $m))
        $username = trim($m[1]);
    if (preg_match('/database\.default\.password\s*=\s*(.*)/', $env, $m))
        $password = trim($m[1]);
} else {
    echo "⚠️ .env file not found. Falling back to Config/Database.php...<br>";
    // Simplified detection for Config/Database.php
    $dbConfig = dirname(__DIR__) . '/app/Config/Database.php';
    if (file_exists($dbConfig)) {
        echo "Found Config/Database.php. Please ensure credentials there are correct.<br>";
    }
}

if ($username) {
    echo "Testing connection to $database @ $hostname...<br>";
    $mysqli = @new mysqli($hostname, $username, $password, $database);

    if ($mysqli->connect_error) {
        echo "❌ Connection failed: " . $mysqli->connect_error . "<br>";
    } else {
        echo "✅ Connected successfully.<br>";

        // Check for new columns
        echo "Checking 'users' table schema...<br>";
        $result = $mysqli->query("SHOW COLUMNS FROM users LIKE 'last_ip'");
        if ($result->num_rows == 0) {
            echo "❌ Column 'last_ip' component MISSING.<br>";
            echo "<b>Attempting to fix schema now...</b><br>";
            $fix1 = $mysqli->query("ALTER TABLE users ADD COLUMN last_ip VARCHAR(45) NULL AFTER active_session_id");
            $fix2 = $mysqli->query("ALTER TABLE users ADD COLUMN user_agent VARCHAR(255) NULL AFTER last_ip");
            if ($fix1 && $fix2) {
                echo "✅ Schema FIXED successfully.<br>";
            } else {
                echo "❌ Failed to fix schema: " . $mysqli->error . "<br>";
            }
        } else {
            echo "✅ 'users' table schema is up to date.<br>";
        }
        $mysqli->close();
    }
} else {
    echo "❌ Could not detect database credentials. Check your .env file.<br>";
}

// 4. Try to boot CI4 (Last step because it might crash)
echo "<h2>4. CodeIgniter 4 Boot Test</h2>";
try {
    require dirname(__DIR__) . '/app/Config/Paths.php';
    echo "✅ Paths.php loaded.<br>";
    require dirname(__DIR__) . '/vendor/autoload.php';
    echo "✅ Autoloader loaded.<br>";
    echo "If you don't see anything after this, then there is an error in App.php or Events.php logic.<br>";
} catch (\Throwable $e) {
    echo "❌ Boot failed: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "<br>";
}

echo "<h3>Diagnostic Complete.</h3>";
?>