<?php
/**
 * FORCE CLEAR OPCACHE
 * Akses file ini, lalu hapus file ini
 */

echo "<h1>Force Clear OPcache</h1>";

// Clear OPcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    if ($result) {
        echo "<p style='color:green; font-size:20px;'>✅ OPcache cleared successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Failed to clear OPcache</p>";
    }
    
    // Show OPcache status
    $status = opcache_get_status();
    echo "<h2>OPcache Status:</h2>";
    echo "<pre>";
    echo "Enabled: " . ($status['opcache_enabled'] ? 'YES' : 'NO') . "\n";
    echo "Cache full: " . ($status['cache_full'] ? 'YES' : 'NO') . "\n";
    echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "Misses: " . $status['opcache_statistics']['misses'] . "\n";
    echo "</pre>";
} else {
    echo "<p style='color:orange;'>⚠️ OPcache is not enabled</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li><strong>Close ALL browser tabs</strong></li>";
echo "<li><strong>Restart Apache</strong> (XAMPP Control Panel → Stop → Start)</li>";
echo "<li><strong>Open NEW Incognito window</strong> (Ctrl + Shift + N)</li>";
echo "<li><strong>Login and test</strong> penilaian</li>";
echo "<li><strong>DELETE this file</strong> (force_clear_opcache.php)</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='admin/kokurikuler/penilaian/detail/14' style='font-size:18px; padding:10px 20px; background:blue; color:white; text-decoration:none; border-radius:5px;'>Test Penilaian</a></p>";
?>
