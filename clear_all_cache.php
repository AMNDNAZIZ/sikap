<?php
/**
 * CLEAR ALL CACHE - Jalankan sekali lalu hapus file ini
 */

echo "<h2>Clearing All Cache...</h2>";

// 1. Clear OPcache
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "✅ OPcache cleared<br>";
} else {
    echo "⚠️ OPcache not enabled<br>";
}

// 2. Clear APC cache
if (function_exists('apc_clear_cache')) {
    apc_clear_cache();
    echo "✅ APC cache cleared<br>";
} else {
    echo "⚠️ APC not enabled<br>";
}

// 3. Clear CodeIgniter cache
$cacheDir = __DIR__ . '/writable/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
        }
    }
    echo "✅ CodeIgniter cache cleared<br>";
}

// 4. Clear session files
$sessionDir = __DIR__ . '/writable/session';
if (is_dir($sessionDir)) {
    $files = glob($sessionDir . '/*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
        }
    }
    echo "✅ Session files cleared<br>";
}

echo "<hr>";
echo "<h3>✅ All cache cleared!</h3>";
echo "<p><strong>NEXT STEPS:</strong></p>";
echo "<ol>";
echo "<li>Close this browser tab</li>";
echo "<li>Restart Apache (XAMPP Control Panel → Stop → Start)</li>";
echo "<li>Open new browser tab (Incognito/Private mode recommended)</li>";
echo "<li>Login and test penilaian again</li>";
echo "<li><strong>DELETE THIS FILE (clear_all_cache.php) after use!</strong></li>";
echo "</ol>";
?>
