<?php
/**
 * Emergency Hash + License Fix (includes domain fix)
 * Jalankan: https://yourdomain.com/rehash_all.php?token=REHASH2026
 * HAPUS SETELAH SELESAI
 */
if (($_GET['token'] ?? '') !== 'REHASH2026') { http_response_code(403); die('Forbidden'); }

$appPath = __DIR__ . '/app/';
$env = [];
if (file_exists(__DIR__ . '/.env')) {
    foreach (file(__DIR__ . '/.env') as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \t\n\r\"'");
        }
    }
}

echo '<pre style="font-family:monospace;font-size:13px;">';
echo "=== EMERGENCY HASH + LICENSE FIX ===\n";
echo "Server time : " . date('Y-m-d H:i:s') . "\n";
echo "Current domain: " . ($_SERVER['HTTP_HOST'] ?? 'unknown') . "\n\n";

$dbHost = $env['database.default.hostname'] ?? 'localhost';
$dbUser = $env['database.default.username'] ?? 'root';
$dbPass = $env['database.default.password'] ?? '';
$dbName = $env['database.default.database'] ?? '';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) { die("❌ DB: " . $e->getMessage()); }

$row = $pdo->query("SELECT * FROM app_license LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$row) { die("❌ Tidak ada data lisensi."); }

echo "Status DB      : " . $row['status'] . "\n";
echo "Domain di DB   : " . $row['domain'] . "\n";
echo "Expires at     : " . $row['expires_at'] . "\n\n";

$currentDomain = $_SERVER['HTTP_HOST'] ?? '';
$fixes = [];

// Fix 1: status
if ($row['status'] !== 'active') {
    $fixes['status'] = 'active';
    echo "✅ Fix status: {$row['status']} → active\n";
}

// Fix 2: domain mismatch
$normDomain = fn($d) => preg_replace('/^www\./i', '', strtolower(trim($d)));
if ($currentDomain && $normDomain($row['domain']) !== $normDomain($currentDomain)) {
    $fixes['domain'] = $currentDomain;
    echo "✅ Fix domain: {$row['domain']} → $currentDomain\n";
}

if (!empty($fixes)) {
    $fixes['last_check'] = date('Y-m-d H:i:s');
    $setClauses = implode(', ', array_map(fn($k) => "$k = ?", array_keys($fixes)));
    $values = array_values($fixes);
    $values[] = $row['id'];
    $pdo->prepare("UPDATE app_license SET $setClauses WHERE id = ?")->execute($values);
    echo "\n";
}

// Fix 3: regenerate .lic_hash
$licFiles = ['Libraries/LicenseGuard.php', 'Filters/LicenseFilter.php', 'Models/LicenseModel.php'];
$licH = [];
foreach ($licFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) { $licH[$f] = sha1_file($p); echo "✅ lic_hash: $f\n"; }
    else echo "❌ MISSING: $f\n";
}
file_put_contents($appPath . 'Config/.lic_hash', json_encode($licH, JSON_PRETTY_PRINT));
echo "✅ .lic_hash ditulis\n\n";

// Fix 4: regenerate .security_hash
$secFiles = ['Helpers/license_helper.php','Libraries/LicenseGuard.php','Libraries/SecurityGuard.php','Libraries/SecureConfig.php','Filters/LicenseFilter.php','Models/LicenseModel.php'];
$secH = [];
foreach ($secFiles as $f) {
    $p = $appPath . $f;
    if (file_exists($p)) { $secH[$f] = hash_file('sha256', $p); echo "✅ security_hash: $f\n"; }
    else echo "❌ MISSING: $f\n";
}
file_put_contents($appPath . 'Config/.security_hash', json_encode($secH, JSON_PRETTY_PRINT));
echo "✅ .security_hash ditulis\n\n";

// Fix 5: clear cache
$cacheDir = __DIR__ . '/writable/cache/';
$c = 0;
if (is_dir($cacheDir)) {
    foreach (glob($cacheDir . '*') as $f) {
        if (is_file($f) && basename($f) !== 'index.html') { @unlink($f); $c++; }
    }
}
echo "✅ Cache dibersihkan ($c files)\n\n";

echo "🎉 SELESAI. Hapus file ini dan coba akses aplikasi.\n";
echo "⚠️  HAPUS FILE INI SEKARANG!\n";
echo '</pre>';
