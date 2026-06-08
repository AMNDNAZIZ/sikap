<?php
/**
 * Clear PHP OPcache
 * Access via browser: http://localhost:8080/cbtku/clear_opcache.php
 */

echo "<h1>Clear PHP OPcache</h1>";

if (function_exists('opcache_reset')) {
    if (opcache_reset()) {
        echo "<p style='color: green; font-size: 18px;'>✅ OPcache cleared successfully!</p>";
        echo "<p>Code changes will now take effect.</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to clear OPcache.</p>";
    }
    
    // Show opcache status
    $status = opcache_get_status();
    echo "<h2>OPcache Status:</h2>";
    echo "<pre>";
    echo "Enabled: " . ($status['opcache_enabled'] ? 'Yes' : 'No') . "\n";
    echo "Cache full: " . ($status['cache_full'] ? 'Yes' : 'No') . "\n";
    echo "Cached scripts: " . $status['opcache_statistics']['num_cached_scripts'] . "\n";
    echo "Hits: " . $status['opcache_statistics']['hits'] . "\n";
    echo "Misses: " . $status['opcache_statistics']['misses'] . "\n";
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️  OPcache is not enabled.</p>";
    echo "<p>This is normal for development. Code changes should take effect immediately.</p>";
}

echo "<hr>";
echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>Hard refresh browser: <strong>Ctrl + Shift + R</strong></li>";
echo "<li>Check aktivitas detail page</li>";
echo "<li>Verify timestamp shows current time</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.php/admin/cbt/aktivitas'>← Back to Aktivitas</a></p>";
