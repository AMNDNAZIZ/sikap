<?php
/**
 * CBT Debug Tool v2 - HAPUS FILE INI SETELAH SELESAI DEBUG!
 * Akses: https://cbtsmpistora.sch.id/cbt_debug.php?key=cbt_debug_2026
 */

// Tampilkan semua error PHP
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic security
$debugKey = $_GET['key'] ?? '';
if ($debugKey !== 'cbt_debug_2026') {
    http_response_code(403);
    die('Forbidden. Tambahkan ?key=cbt_debug_2026 di URL.');
}

echo '<pre style="font-family:monospace;font-size:13px;background:#1e1e1e;color:#d4d4d4;padding:20px;margin:0;">';
echo "=== CBT PRODUCTION DEBUG TOOL v2 ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ─── PHP Info ─────────────────────────────────────────────────
echo "=== PHP ===\n";
echo "Version: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n\n";

// ─── Test: Bootstrap CodeIgniter ──────────────────────────────
echo "=== CODEIGNITER BOOTSTRAP TEST ===\n";
$appRoot = dirname(__DIR__);

// Check index.php exists
echo file_exists($appRoot . '/index.php') ? "✅ index.php found\n" : "❌ index.php NOT found\n";
echo file_exists($appRoot . '/app/Config/App.php') ? "✅ App.php found\n" : "❌ App.php NOT found\n";
echo file_exists($appRoot . '/system/CodeIgniter.php') ? "✅ system/CodeIgniter.php found\n" : "❌ system/ NOT found\n";
echo file_exists($appRoot . '/vendor/autoload.php') ? "✅ vendor/autoload.php found\n" : "❌ vendor/autoload.php NOT found\n";
echo "\n";

// ─── Test: Load CI4 manually and catch errors ──────────────────
echo "=== CODEIGNITER LOAD TEST ===\n";
try {
    // Capture any output/errors during bootstrap
    ob_start();
    
    // Set error handler to catch all errors
    $errors = [];
    set_error_handler(function($errno, $errstr, $errfile, $errline) use (&$errors) {
        $errors[] = "[$errno] $errstr in $errfile:$errline";
        return true;
    });
    
    // Try to load autoloader
    if (file_exists($appRoot . '/vendor/autoload.php')) {
        require_once $appRoot . '/vendor/autoload.php';
        echo "✅ Composer autoload OK\n";
    }
    
    // Try to define constants like CI4 does
    define('FCPATH', $appRoot . '/public/');
    
    if (!defined('APPPATH')) {
        define('APPPATH', $appRoot . '/app/');
    }
    if (!defined('SYSTEMPATH')) {
        define('SYSTEMPATH', $appRoot . '/system/');
    }
    if (!defined('WRITEPATH')) {
        define('WRITEPATH', $appRoot . '/writable/');
    }
    
    ob_end_clean();
    restore_error_handler();
    
    if (!empty($errors)) {
        echo "⚠️  Errors during load:\n";
        foreach ($errors as $err) {
            echo "   $err\n";
        }
    } else {
        echo "✅ No PHP errors during bootstrap\n";
    }
    
} catch (Throwable $e) {
    ob_end_clean();
    echo "❌ EXCEPTION during bootstrap:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
echo "\n";

// ─── Test: Simulate mulai() request ───────────────────────────
echo "=== SIMULATE REQUEST TO siswa/cbt/mulai/1 ===\n";
echo "Testing what happens when CI4 handles this route...\n\n";

// Check if writable/logs is actually being written to
$logDir = $appRoot . '/writable/logs/';
$testLogFile = $logDir . 'test_write_' . time() . '.txt';
if (is_writable($logDir)) {
    file_put_contents($testLogFile, 'test');
    if (file_exists($testLogFile)) {
        echo "✅ Log directory is writable\n";
        unlink($testLogFile);
    }
} else {
    echo "❌ Log directory NOT writable: $logDir\n";
}

// Check all log files
$logFiles = glob($logDir . 'log-*.php');
if ($logFiles) {
    rsort($logFiles);
    echo "Log files found:\n";
    foreach (array_slice($logFiles, 0, 5) as $f) {
        echo "   " . basename($f) . " (" . number_format(filesize($f)) . " bytes)\n";
    }
    echo "\n";
    
    // Read most recent log
    $latestLog = $logFiles[0];
    echo "=== LATEST LOG: " . basename($latestLog) . " ===\n";
    $lines = file($latestLog, FILE_IGNORE_NEW_LINES);
    // Filter for errors and CBT-related
    $relevant = array_filter($lines, fn($l) => 
        stripos($l, 'ERROR') !== false || 
        stripos($l, 'CRITICAL') !== false ||
        stripos($l, 'mulai') !== false ||
        stripos($l, 'CBT') !== false ||
        stripos($l, 'Redis') !== false ||
        stripos($l, 'Exception') !== false ||
        stripos($l, 'Fatal') !== false ||
        stripos($l, 'License') !== false
    );
    $last50 = array_slice(array_values($relevant), -50);
    if (empty($last50)) {
        echo "(no relevant entries - showing last 20 lines)\n";
        $last20 = array_slice($lines, -20);
        foreach ($last20 as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    } else {
        foreach ($last50 as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    }
} else {
    echo "❌ No log files found in: $logDir\n";
    echo "   This means CI4 is not writing logs OR the path is wrong\n";
    
    // Check PHP error log
    $phpErrorLog = ini_get('error_log');
    echo "\nPHP error_log path: " . ($phpErrorLog ?: '(not set)') . "\n";
    if ($phpErrorLog && file_exists($phpErrorLog)) {
        echo "PHP error log (last 20 lines):\n";
        $phpLines = file($phpErrorLog, FILE_IGNORE_NEW_LINES);
        $last20 = array_slice($phpLines, -20);
        foreach ($last20 as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    }
}
echo "\n";

// ─── Test: License Check ──────────────────────────────────────
echo "=== LICENSE CHECK ===\n";
try {
    // Connect to DB directly
    $envFile = $appRoot . '/.env';
    $dbHost = 'localhost'; $dbName = ''; $dbUser = ''; $dbPass = '';
    if (file_exists($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
            if (strpos($line, '#') === 0) continue;
            if (strpos($line, 'database.default.hostname') !== false) $dbHost = trim(explode('=', $line, 2)[1]);
            if (strpos($line, 'database.default.database') !== false) $dbName = trim(explode('=', $line, 2)[1]);
            if (strpos($line, 'database.default.username') !== false) $dbUser = trim(explode('=', $line, 2)[1]);
            if (strpos($line, 'database.default.password') !== false) $dbPass = trim(explode('=', $line, 2)[1]);
        }
    }
    
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    
    // Check license table
    $stmt = $pdo->query("SHOW TABLES LIKE 'licenses'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("SELECT license_key, status, expires_at, last_check FROM licenses LIMIT 1");
        $lic = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($lic) {
            echo "License key: " . substr($lic['license_key'], 0, 8) . "...\n";
            echo "Status: " . $lic['status'] . "\n";
            echo "Expires: " . $lic['expires_at'] . "\n";
            echo "Last check: " . $lic['last_check'] . "\n";
            
            if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) {
                echo "❌ LICENSE IS EXPIRED! This will cause redirect to /activate\n";
            } else {
                echo "✅ License is valid\n";
            }
        } else {
            echo "❌ No license found in database!\n";
        }
    } else {
        echo "❌ 'licenses' table does not exist!\n";
    }
} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── Test: Try to INSERT into cbt_sessions ────────────────────
echo "=== TEST INSERT cbt_sessions ===\n";
echo "Testing if INSERT works with current table structure...\n";
try {
    // First check exact column types
    $stmt = $pdo->query("DESCRIBE cbt_sessions");
    $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Column types:\n";
    foreach ($cols as $col) {
        $highlight = in_array($col['Field'], ['last_activity', 'finished_at', 'started_at', 'reset_token', 'cheat_locked']) ? " <<<" : "";
        echo "  " . $col['Field'] . " | " . $col['Type'] . " | null=" . $col['Null'] . " | default=" . ($col['Default'] ?? 'NULL') . $highlight . "\n";
    }
    echo "\n";
    
    // Try a test insert (will rollback)
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("INSERT INTO cbt_sessions 
            (student_id, test_id, started_at, question_order, option_orders, last_activity, status, reset_token, cheat_locked, extra_time, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, 'active', ?, 0, 0, NOW(), NOW())");
        $stmt->execute([
            1,          // student_id
            1,          // test_id  
            time(),     // started_at (int)
            '[]',       // question_order
            '{}',       // option_orders
            time(),     // last_activity (int) ← FIXED
            'test_token_debug'  // reset_token
        ]);
        echo "✅ INSERT with time() for last_activity: SUCCESS\n";
        $pdo->rollBack();
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ INSERT failed: " . $e->getMessage() . "\n";
        
        // Try with string date
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare("INSERT INTO cbt_sessions 
                (student_id, test_id, started_at, question_order, option_orders, last_activity, status, reset_token, cheat_locked, extra_time, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 'active', ?, 0, 0, NOW(), NOW())");
            $stmt->execute([
                1, 1, time(), '[]', '{}',
                date('Y-m-d H:i:s'),  // last_activity as string
                'test_token_debug'
            ]);
            echo "⚠️  INSERT with date() string for last_activity: SUCCESS (but wrong type!)\n";
            $pdo->rollBack();
        } catch (Exception $e2) {
            $pdo->rollBack();
            echo "❌ INSERT with string also failed: " . $e2->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── Test: Check MySQL strict mode ────────────────────────────
echo "=== MYSQL STRICT MODE ===\n";
try {
    $stmt = $pdo->query("SELECT @@sql_mode");
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $mode = $row[0];
    echo "sql_mode: $mode\n";
    if (strpos($mode, 'STRICT') !== false) {
        echo "⚠️  STRICT MODE IS ON - this can cause errors with wrong data types!\n";
    } else {
        echo "✅ Strict mode is off\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── Test: Check cbt_test_status ──────────────────────────────
echo "=== CBT TEST STATUS ===\n";
try {
    $stmt = $pdo->query("SELECT id, is_visible, start_time, end_time, bank_id FROM cbt_test_status LIMIT 5");
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $now = date('Y-m-d H:i:s');
    foreach ($tests as $t) {
        $active = ($t['is_visible'] == 1 && $now >= $t['start_time'] && $now <= $t['end_time']);
        echo "Test ID " . $t['id'] . ": visible=" . $t['is_visible'] . 
             ", start=" . $t['start_time'] . 
             ", end=" . $t['end_time'] . 
             ", bank_id=" . $t['bank_id'] .
             ($active ? " ✅ ACTIVE NOW" : " ⚠️ not active") . "\n";
    }
    
    // Check if bank has questions
    foreach ($tests as $t) {
        $stmt2 = $pdo->query("SELECT COUNT(*) as c FROM cbt_question_banks WHERE id = " . (int)$t['bank_id']);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);
        $stmt3 = $pdo->query("SELECT COUNT(*) as c FROM cbt_questions WHERE bank_id = " . (int)$t['bank_id']);
        $row3 = $stmt3->fetch(PDO::FETCH_ASSOC);
        echo "   Bank " . $t['bank_id'] . ": bank_exists=" . $row['c'] . ", questions=" . $row3['c'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── Test: Check students table ───────────────────────────────
echo "=== STUDENTS & CLASSES ===\n";
try {
    $stmt = $pdo->query("SELECT s.id, s.name, s.class_id, c.name as class_name FROM students s LEFT JOIN classes c ON c.id = s.class_id LIMIT 3");
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($students as $s) {
        echo "Student " . $s['id'] . ": " . $s['name'] . " | class_id=" . $s['class_id'] . " | class_name=" . ($s['class_name'] ?? 'NULL') . "\n";
    }
    
    // Check class_codes in test
    $stmt = $pdo->query("SELECT id, class_codes FROM cbt_test_status LIMIT 3");
    $tests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($tests as $t) {
        echo "Test " . $t['id'] . " class_codes: " . $t['class_codes'] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== END DEBUG v2 ===\n";
echo '</pre>';
echo '<p style="color:red;font-weight:bold;font-family:monospace;padding:10px;">⚠️ HAPUS FILE INI SETELAH SELESAI: public/cbt_debug.php</p>';


echo '<pre style="font-family:monospace;font-size:13px;background:#1e1e1e;color:#d4d4d4;padding:20px;">';
echo "=== CBT PRODUCTION DEBUG TOOL ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

// ─── PHP Info ─────────────────────────────────────────────────
echo "=== PHP ===\n";
echo "Version: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "OS: " . PHP_OS . "\n\n";

// ─── Extensions ───────────────────────────────────────────────
echo "=== EXTENSIONS ===\n";
$required = ['redis', 'intl', 'mbstring', 'json', 'pdo_mysql', 'mysqli', 'openssl', 'curl'];
foreach ($required as $ext) {
    $loaded = extension_loaded($ext);
    echo ($loaded ? "✅" : "❌") . " $ext\n";
}
echo "\n";

// ─── Redis Test ───────────────────────────────────────────────
echo "=== REDIS ===\n";
if (extension_loaded('redis')) {
    try {
        $redis = new Redis();
        $connected = $redis->connect('127.0.0.1', 6379, 2);
        if ($connected) {
            $pong = $redis->ping();
            echo "✅ Redis connected: $pong\n";
            $redis->set('cbt_debug_test', 'ok', 10);
            $val = $redis->get('cbt_debug_test');
            echo "✅ Redis read/write: $val\n";
            $info = $redis->info('server');
            echo "   Version: " . ($info['redis_version'] ?? 'unknown') . "\n";
            $info2 = $redis->info('memory');
            echo "   Memory used: " . ($info2['used_memory_human'] ?? 'unknown') . "\n";
            $redis->close();
        } else {
            echo "❌ Redis connect() returned false\n";
        }
    } catch (Exception $e) {
        echo "❌ Redis Exception: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ Redis extension NOT loaded\n";
}
echo "\n";

// ─── Session Test ─────────────────────────────────────────────
echo "=== SESSION ===\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";

// Test session start
session_start();
$_SESSION['debug_test'] = 'ok_' . time();
echo "✅ Session started: " . session_id() . "\n";
echo "   Test value: " . ($_SESSION['debug_test'] ?? 'MISSING') . "\n";
session_write_close();
echo "\n";

// ─── Database Test ────────────────────────────────────────────
echo "=== DATABASE ===\n";
// Read .env for DB credentials
$envFile = __DIR__ . '/../.env';
$dbHost = 'localhost';
$dbName = '';
$dbUser = '';
$dbPass = '';

if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, 'database.default.hostname') !== false) $dbHost = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.database') !== false) $dbName = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.username') !== false) $dbUser = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.password') !== false) $dbPass = trim(explode('=', $line, 2)[1]);
    }
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    echo "✅ MySQL connected: $dbHost / $dbName\n";
    
    $stmt = $pdo->query("SHOW STATUS LIKE 'Threads_connected'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Active connections: " . ($row['Value'] ?? '?') . "\n";
    
    $stmt = $pdo->query("SHOW VARIABLES LIKE 'max_connections'");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   Max connections: " . ($row['Value'] ?? '?') . "\n";
    
    // Check tables
    $tables = ['cbt_sessions', 'cbt_test_status', 'students', 'users', 'cbt_cheat_logs'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as c FROM `$table`");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "   Table $table: " . $row['c'] . " rows\n";
    }
} catch (Exception $e) {
    echo "❌ MySQL Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── File Permissions ─────────────────────────────────────────
echo "=== FILE PERMISSIONS ===\n";
$appRoot = dirname(__DIR__);
$dirs = [
    $appRoot . '/writable',
    $appRoot . '/writable/cache',
    $appRoot . '/writable/logs',
    $appRoot . '/writable/session',
];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        $writable = is_writable($dir);
        echo ($writable ? "✅" : "❌") . " " . str_replace($appRoot, '', $dir) . "\n";
    } else {
        echo "❌ MISSING: " . str_replace($appRoot, '', $dir) . "\n";
    }
}
echo "\n";

// ─── Recent Error Log ─────────────────────────────────────────
echo "=== RECENT ERROR LOG (last 30 lines) ===\n";
$logFile = $appRoot . '/writable/logs/log-' . date('Y-m-d') . '.php';
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES);
    $relevant = array_filter($lines, fn($l) => 
        stripos($l, 'ERROR') !== false || 
        stripos($l, 'CRITICAL') !== false ||
        stripos($l, 'mulai') !== false ||
        stripos($l, 'CBT') !== false ||
        stripos($l, 'Redis') !== false ||
        stripos($l, 'Exception') !== false
    );
    $last30 = array_slice(array_values($relevant), -30);
    foreach ($last30 as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    if (empty($last30)) {
        echo "(no relevant errors found in today's log)\n";
        // Show last 10 lines anyway
        $last10 = array_slice($lines, -10);
        foreach ($last10 as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    }
} else {
    echo "Log file not found: $logFile\n";
    // Try to find any log
    $logs = glob($appRoot . '/writable/logs/log-*.php');
    if ($logs) {
        rsort($logs);
        echo "Most recent log: " . basename($logs[0]) . "\n";
        $lines = file($logs[0], FILE_IGNORE_NEW_LINES);
        $last10 = array_slice($lines, -10);
        foreach ($last10 as $line) {
            echo htmlspecialchars($line) . "\n";
        }
    }
}
echo "\n";

// ─── .env Check ───────────────────────────────────────────────
echo "=== .ENV CONFIG ===\n";
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0) continue;
        // Mask password
        if (stripos($line, 'password') !== false || stripos($line, 'key') !== false || stripos($line, 'secret') !== false) {
            $parts = explode('=', $line, 2);
            echo $parts[0] . "= [HIDDEN]\n";
        } else {
            echo $line . "\n";
        }
    }
} else {
    echo "❌ .env file not found!\n";
}
echo "\n";

echo "=== END DEBUG ===\n";
echo '</pre>';
echo '<p style="color:red;font-weight:bold;font-family:monospace;">⚠️ HAPUS FILE INI SETELAH SELESAI: public/cbt_debug.php</p>';
