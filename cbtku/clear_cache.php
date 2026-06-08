<?php
/**
 * Clear Cache Script
 * Run this after fixing FCPATH to ensure no cached paths
 */

echo "Clearing cache..." . PHP_EOL;

// Clear writable/cache
$cacheDir = __DIR__ . '/writable/cache/';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
            echo "Deleted: " . basename($file) . PHP_EOL;
        }
    }
    echo "✓ Cache cleared" . PHP_EOL;
} else {
    echo "✗ Cache directory not found" . PHP_EOL;
}

// Clear writable/session
$sessionDir = __DIR__ . '/writable/session/';
if (is_dir($sessionDir)) {
    $files = glob($sessionDir . 'ci_session*');
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file);
            echo "Deleted: " . basename($file) . PHP_EOL;
        }
    }
    echo "✓ Sessions cleared" . PHP_EOL;
} else {
    echo "✗ Session directory not found" . PHP_EOL;
}

// Clear writable/debugbar
$debugbarDir = __DIR__ . '/writable/debugbar/';
if (is_dir($debugbarDir)) {
    $files = glob($debugbarDir . '*');
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'index.html') {
            unlink($file);
            echo "Deleted: " . basename($file) . PHP_EOL;
        }
    }
    echo "✓ Debugbar cleared" . PHP_EOL;
}

echo PHP_EOL;
echo "✓ All cache cleared successfully!" . PHP_EOL;
echo "✓ Please restart your web server (Apache/Nginx)" . PHP_EOL;
echo "✓ Then test upload gambar di halaman tambah soal" . PHP_EOL;
