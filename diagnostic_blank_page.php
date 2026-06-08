<?php

/**
 * Diagnostic: Blank Page & Redirect Loop
 * 
 * Script untuk diagnose masalah blank page dan redirect loop
 * setelah regenerate hash
 */

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre>";
echo "=================================================================\n";
echo "DIAGNOSTIC: Blank Page & Redirect Loop\n";
echo "=================================================================\n\n";

// 1. Check Database Connection
echo "1. CHECKING DATABASE CONNECTION\n";
echo "-----------------------------------\n";

try {
    // Load .env
    $envFile = __DIR__ . '/.env';
    if (!file_exists($envFile)) {
        echo "❌ File .env tidak ditemukan!\n";
        echo "   Path: {$envFile}\n\n";
        exit;
    }
    
    $envContent = file_get_contents($envFile);
    preg_match('/database\.default\.hostname\s*=\s*(.+)/', $envContent, $host);
    preg_match('/database\.default\.database\s*=\s*(.+)/', $envContent, $dbname);
    preg_match('/database\.default\.username\s*=\s*(.+)/', $envContent, $user);
    preg_match('/database\.default\.password\s*=\s*(.+)/', $envContent, $pass);
    
    $hostname = trim($host[1] ?? 'localhost');
    $database = trim($dbname[1] ?? '');
    $username = trim($user[1] ?? '');
    $password = trim($pass[1] ?? '');
    
    echo "Host: {$hostname}\n";
    echo "Database: {$database}\n";
    echo "Username: {$username}\n";
    
    $conn = new mysqli($hostname, $username, $password, $database);
    
    if ($conn->connect_error) {
        echo "❌ Koneksi database GAGAL!\n";
        echo "   Error: " . $conn->connect_error . "\n\n";
        exit;
    }
    
    echo "✅ Koneksi database BERHASIL\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit;
}

// 2. Check app_license table
echo "2. CHECKING APP_LICENSE TABLE\n";
echo "-----------------------------------\n";

$result = $conn->query("SHOW TABLES LIKE 'app_license'");
if ($result->num_rows == 0) {
    echo "❌ Tabel app_license TIDAK ADA!\n";
    echo "   Solusi: Import update_database_manual.sql\n\n";
    
    echo "QUICK FIX:\n";
    echo "1. Akses phpMyAdmin\n";
    echo "2. Import file: update_database_manual.sql\n";
    echo "3. Refresh halaman ini\n\n";
    exit;
}

echo "✅ Tabel app_license ADA\n\n";

// 3. Check app_license structure
echo "3. CHECKING APP_LICENSE STRUCTURE\n";
echo "-----------------------------------\n";

$result = $conn->query("DESCRIBE app_license");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[] = $row['Field'];
}

echo "Kolom yang ada:\n";
foreach ($columns as $col) {
    echo "  - {$col}\n";
}

$requiredColumns = ['id', 'license_key', 'domain', 'machine_id', 'status', 'expires_at', 'hash'];
$missingColumns = array_diff($requiredColumns, $columns);

if (!empty($missingColumns)) {
    echo "\n❌ Kolom yang HILANG:\n";
    foreach ($missingColumns as $col) {
        echo "  - {$col}\n";
    }
    echo "\nSolusi: Import update_database_manual.sql\n\n";
} else {
    echo "\n✅ Struktur tabel LENGKAP\n\n";
}

// 4. Check license data
echo "4. CHECKING LICENSE DATA\n";
echo "-----------------------------------\n";

$result = $conn->query("SELECT * FROM app_license LIMIT 1");
if ($result->num_rows == 0) {
    echo "❌ Tidak ada data lisensi!\n";
    echo "   Status: Aplikasi belum diaktivasi\n";
    echo "   Redirect ke /activate adalah NORMAL\n\n";
    
    echo "SOLUSI:\n";
    echo "1. Akses: http://yourdomain.com/activate\n";
    echo "2. Masukkan kode lisensi\n";
    echo "3. Klik Aktivasi\n\n";
} else {
    $license = $result->fetch_assoc();
    echo "✅ Data lisensi ADA\n\n";
    
    echo "Detail Lisensi:\n";
    echo "  License Key: " . substr($license['license_key'], 0, 20) . "...\n";
    echo "  Domain: " . ($license['domain'] ?? 'NULL') . "\n";
    echo "  Machine ID: " . substr($license['machine_id'] ?? 'NULL', 0, 20) . "...\n";
    echo "  Status: " . ($license['status'] ?? 'NULL') . "\n";
    echo "  Expires At: " . ($license['expires_at'] ?? 'NULL') . "\n";
    echo "  Last Check: " . ($license['last_check'] ?? 'NULL') . "\n";
    
    // Check if expired
    if (isset($license['expires_at'])) {
        $expiresAt = strtotime($license['expires_at']);
        $now = time();
        
        if ($expiresAt < $now) {
            $daysExpired = floor(($now - $expiresAt) / 86400);
            echo "\n⚠️  Lisensi EXPIRED {$daysExpired} hari yang lalu!\n";
            echo "   Solusi: Perpanjang lisensi atau aktivasi ulang\n";
        } else {
            $daysLeft = floor(($expiresAt - $now) / 86400);
            echo "\n✅ Lisensi AKTIF (sisa {$daysLeft} hari)\n";
        }
    }
    
    // Check status
    if (isset($license['status']) && $license['status'] !== 'active') {
        echo "\n⚠️  Status lisensi: " . $license['status'] . "\n";
        echo "   Solusi: Aktivasi ulang atau hubungi support\n";
    }
    
    echo "\n";
}

// 5. Check migrations table
echo "5. CHECKING MIGRATIONS TABLE\n";
echo "-----------------------------------\n";

$result = $conn->query("SHOW TABLES LIKE 'migrations'");
if ($result->num_rows == 0) {
    echo "❌ Tabel migrations TIDAK ADA!\n";
    echo "   Solusi: Import update_database_manual.sql\n\n";
} else {
    echo "✅ Tabel migrations ADA\n";
    
    $result = $conn->query("SELECT COUNT(*) as total FROM migrations");
    $row = $result->fetch_assoc();
    echo "   Total migrations: " . $row['total'] . "\n\n";
}

// 6. Check hash files
echo "6. CHECKING HASH FILES\n";
echo "-----------------------------------\n";

$securityHash = __DIR__ . '/app/Config/.security_hash';
$licHash = __DIR__ . '/app/Config/.lic_hash';

if (file_exists($securityHash)) {
    $content = file_get_contents($securityHash);
    $hashes = json_decode($content, true);
    if ($hashes && is_array($hashes)) {
        echo "✅ .security_hash ADA dan VALID\n";
        echo "   Protected files: " . count($hashes) . "\n";
    } else {
        echo "⚠️  .security_hash ADA tapi INVALID\n";
        echo "   Solusi: Regenerate hash\n";
    }
} else {
    echo "❌ .security_hash TIDAK ADA\n";
    echo "   Solusi: Regenerate hash\n";
}

if (file_exists($licHash)) {
    $content = file_get_contents($licHash);
    $hashes = json_decode($content, true);
    if ($hashes && is_array($hashes)) {
        echo "✅ .lic_hash ADA dan VALID\n";
        echo "   Protected files: " . count($hashes) . "\n";
    } else {
        echo "⚠️  .lic_hash ADA tapi INVALID\n";
        echo "   Solusi: Regenerate hash\n";
    }
} else {
    echo "❌ .lic_hash TIDAK ADA\n";
    echo "   Solusi: Regenerate hash\n";
}

echo "\n";

// 7. Check log files
echo "7. CHECKING LOG FILES\n";
echo "-----------------------------------\n";

$logDir = __DIR__ . '/writable/logs/';
if (is_dir($logDir)) {
    $logFiles = glob($logDir . 'log-*.log');
    if (!empty($logFiles)) {
        // Get latest log file
        usort($logFiles, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        $latestLog = $logFiles[0];
        echo "✅ Log file ditemukan\n";
        echo "   Latest: " . basename($latestLog) . "\n\n";
        
        echo "Last 20 lines:\n";
        echo "-----------------------------------\n";
        $lines = file($latestLog);
        $lastLines = array_slice($lines, -20);
        foreach ($lastLines as $line) {
            echo $line;
        }
    } else {
        echo "⚠️  Tidak ada log file\n";
    }
} else {
    echo "❌ Folder logs tidak ada\n";
}

echo "\n";

// 8. Summary & Recommendations
echo "=================================================================\n";
echo "SUMMARY & RECOMMENDATIONS\n";
echo "=================================================================\n\n";

// Determine the issue
$issues = [];
$solutions = [];

if ($result->num_rows == 0) {
    $issues[] = "Tidak ada data lisensi";
    $solutions[] = "Aktivasi aplikasi di /activate";
}

if (!empty($missingColumns)) {
    $issues[] = "Struktur database belum lengkap";
    $solutions[] = "Import update_database_manual.sql";
}

if (!file_exists($securityHash) || !file_exists($licHash)) {
    $issues[] = "File hash tidak ada";
    $solutions[] = "Regenerate hash dengan regenerate_hash_after_update.php";
}

if (empty($issues)) {
    echo "✅ TIDAK ADA MASALAH TERDETEKSI\n\n";
    echo "Jika masih blank page, kemungkinan:\n";
    echo "1. Error PHP (check error_log)\n";
    echo "2. Permission issue (check folder writable/)\n";
    echo "3. .htaccess issue (check mod_rewrite)\n\n";
} else {
    echo "❌ MASALAH TERDETEKSI:\n";
    foreach ($issues as $i => $issue) {
        echo ($i + 1) . ". {$issue}\n";
    }
    
    echo "\n✅ SOLUSI:\n";
    foreach ($solutions as $i => $solution) {
        echo ($i + 1) . ". {$solution}\n";
    }
    echo "\n";
}

echo "=================================================================\n";
echo "DONE!\n";
echo "=================================================================\n";

$conn->close();
echo "</pre>";
