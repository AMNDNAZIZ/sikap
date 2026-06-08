<?php
/**
 * Apply Performance Improvements
 * 
 * Script untuk menerapkan peningkatan kinerja database
 * Jalankan: php apply_performance_improvements.php
 * 
 * SECURITY: Only run from localhost
 */

// Security check
if (php_sapi_name() !== 'cli' && $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This script can only be run from localhost or CLI.');
}

// Load CodeIgniter
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap
$pathsConfig = require __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
define('ROOTPATH', __DIR__ . DIRECTORY_SEPARATOR);
define('FCPATH', ROOTPATH . 'public' . DIRECTORY_SEPARATOR);
define('APPPATH', realpath($paths->appDirectory) . DIRECTORY_SEPARATOR);
define('WRITEPATH', realpath($paths->writableDirectory) . DIRECTORY_SEPARATOR);
define('SYSTEMPATH', realpath($paths->systemDirectory) . DIRECTORY_SEPARATOR);

// Load database config
require_once APPPATH . 'Config/Database.php';
$dbConfig = new Config\Database();
$config = $dbConfig->default;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  APPLY PERFORMANCE IMPROVEMENTS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Connect to database
try {
    $mysqli = new mysqli(
        $config['hostname'],
        $config['username'],
        $config['password'],
        $config['database']
    );
    
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    
    echo "✓ Database connected: {$config['database']}\n\n";
    
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Read SQL file
$sqlFile = __DIR__ . '/add_database_indexes.sql';
if (!file_exists($sqlFile)) {
    echo "✗ SQL file not found: $sqlFile\n";
    exit(1);
}

echo "Reading SQL file: add_database_indexes.sql\n\n";
$sql = file_get_contents($sqlFile);

// Split into individual statements
$statements = array_filter(
    array_map('trim', explode(';', $sql)),
    function($stmt) {
        // Filter out comments and empty statements
        return !empty($stmt) && 
               !preg_match('/^--/', $stmt) && 
               !preg_match('/^\/\*/', $stmt);
    }
);

echo "Found " . count($statements) . " SQL statements\n\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "  APPLYING INDEXES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$successCount = 0;
$skipCount = 0;
$errorCount = 0;
$errors = [];

foreach ($statements as $i => $statement) {
    $statement = trim($statement);
    
    // Skip SELECT and SHOW statements (verification queries)
    if (preg_match('/^(SELECT|SHOW)/i', $statement)) {
        $skipCount++;
        continue;
    }
    
    // Extract table and index name for display
    if (preg_match('/ALTER TABLE (\w+).*ADD INDEX.*idx_(\w+)/i', $statement, $matches)) {
        $table = $matches[1];
        $index = 'idx_' . $matches[2];
        echo "Adding index {$index} to {$table}... ";
    } elseif (preg_match('/ANALYZE TABLE (\w+)/i', $statement, $matches)) {
        $table = $matches[1];
        echo "Analyzing table {$table}... ";
    } else {
        echo "Executing statement " . ($i + 1) . "... ";
    }
    
    try {
        $result = $mysqli->query($statement);
        
        if ($result === false) {
            // Check if error is "Duplicate key name" (index already exists)
            if ($mysqli->errno === 1061) {
                echo "⚠ Already exists\n";
                $skipCount++;
            } else {
                throw new Exception($mysqli->error);
            }
        } else {
            echo "✓ Success\n";
            $successCount++;
        }
        
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        $errorCount++;
        $errors[] = [
            'statement' => substr($statement, 0, 100) . '...',
            'error' => $e->getMessage()
        ];
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✓ Success: $successCount\n";
echo "⚠ Skipped: $skipCount\n";
echo "✗ Errors: $errorCount\n\n";

if ($errorCount > 0) {
    echo "═══════════════════════════════════════════════════════════════\n";
    echo "  ERRORS DETAIL\n";
    echo "═══════════════════════════════════════════════════════════════\n\n";
    
    foreach ($errors as $i => $error) {
        echo ($i + 1) . ". Statement: {$error['statement']}\n";
        echo "   Error: {$error['error']}\n\n";
    }
}

// Verify indexes
echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$tables = ['students', 'users', 'attendance', 'teaching_assignments', 'grades'];

foreach ($tables as $table) {
    $result = $mysqli->query("SHOW INDEX FROM $table WHERE Key_name LIKE 'idx_%'");
    
    if ($result) {
        $count = $result->num_rows;
        echo "✓ Table '$table': $count indexes\n";
    } else {
        echo "⚠ Table '$table': Could not verify\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  NEXT STEPS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "1. Test application performance\n";
echo "2. Monitor query execution times\n";
echo "3. Use EXPLAIN to analyze query plans\n";
echo "4. Check slow query log\n\n";

echo "Expected improvement: 30-50% faster queries\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "  COMPLETED\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$mysqli->close();
