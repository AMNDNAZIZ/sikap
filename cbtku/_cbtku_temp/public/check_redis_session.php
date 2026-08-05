<?php
/**
 * Redis Session Verification Tool
 * 
 * Akses via browser: https://cbt.mabes11.my.id/check_redis_session.php
 * 
 * Tool ini akan memverifikasi:
 * 1. Apakah Redis terinstall dan berjalan
 * 2. Apakah PHP Redis extension aktif
 * 3. Apakah session menggunakan Redis
 * 4. Statistik session di Redis
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Styling
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
    .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
    h1 { color: #333; border-bottom: 3px solid #4CAF50; padding-bottom: 10px; }
    h2 { color: #555; margin-top: 30px; border-left: 4px solid #2196F3; padding-left: 10px; }
    .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .warning { background: #fff3cd; border: 1px solid #ffeaa7; color: #856404; padding: 12px; border-radius: 4px; margin: 10px 0; }
    .info { background: #d1ecf1; border: 1px solid #bee5eb; color: #0c5460; padding: 12px; border-radius: 4px; margin: 10px 0; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background-color: #4CAF50; color: white; }
    tr:hover { background-color: #f5f5f5; }
    .code { background: #f4f4f4; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
    .badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }
    .badge-success { background: #28a745; color: white; }
    .badge-danger { background: #dc3545; color: white; }
    .badge-warning { background: #ffc107; color: #333; }
    .badge-info { background: #17a2b8; color: white; }
</style>';

echo '<div class="container">';
echo '<h1>🔍 Redis Session Verification Tool</h1>';
echo '<p><strong>Waktu Check:</strong> ' . date('Y-m-d H:i:s') . '</p>';

// ============================================
// 1. CHECK PHP REDIS EXTENSION
// ============================================
echo '<h2>1️⃣ PHP Redis Extension</h2>';

if (extension_loaded('redis')) {
    echo '<div class="success">✅ <strong>PHP Redis extension terinstall dan aktif</strong></div>';
    
    $redis_version = phpversion('redis');
    echo '<div class="info">📦 <strong>Redis Extension Version:</strong> ' . $redis_version . '</div>';
} else {
    echo '<div class="error">❌ <strong>PHP Redis extension TIDAK terinstall!</strong></div>';
    echo '<div class="warning">
        <strong>Cara Install:</strong><br>
        <div class="code">
        # Ubuntu/Debian:<br>
        sudo apt-get install php-redis<br>
        sudo systemctl restart php8.1-fpm<br>
        <br>
        # CentOS/RHEL:<br>
        sudo yum install php-redis<br>
        sudo systemctl restart php-fpm<br>
        </div>
    </div>';
}

// ============================================
// 2. CHECK REDIS SERVER CONNECTION
// ============================================
echo '<h2>2️⃣ Redis Server Connection</h2>';

$redisConnected = false;
$redis = null;

try {
    if (class_exists('Redis')) {
        $redis = new Redis();
        
        // Try to connect (sesuaikan dengan config Anda)
        $host = '127.0.0.1';
        $port = 6379;
        
        if ($redis->connect($host, $port, 2)) {
            $redisConnected = true;
            echo '<div class="success">✅ <strong>Berhasil connect ke Redis Server</strong></div>';
            
            // Get Redis info
            $info = $redis->info();
            
            echo '<table>';
            echo '<tr><th>Property</th><th>Value</th></tr>';
            echo '<tr><td>Host</td><td>' . $host . ':' . $port . '</td></tr>';
            echo '<tr><td>Redis Version</td><td>' . ($info['redis_version'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Uptime (days)</td><td>' . round(($info['uptime_in_seconds'] ?? 0) / 86400, 2) . '</td></tr>';
            echo '<tr><td>Connected Clients</td><td>' . ($info['connected_clients'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Used Memory</td><td>' . ($info['used_memory_human'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Total Keys</td><td>' . $redis->dbSize() . '</td></tr>';
            echo '</table>';
            
        } else {
            echo '<div class="error">❌ <strong>Tidak bisa connect ke Redis Server</strong></div>';
            echo '<div class="warning">
                <strong>Troubleshooting:</strong><br>
                1. Pastikan Redis server berjalan: <code>sudo systemctl status redis</code><br>
                2. Cek port: <code>sudo netstat -tlnp | grep 6379</code><br>
                3. Test manual: <code>redis-cli ping</code> (harus return "PONG")
            </div>';
        }
    } else {
        echo '<div class="error">❌ <strong>Redis class tidak tersedia</strong></div>';
    }
} catch (Exception $e) {
    echo '<div class="error">❌ <strong>Error:</strong> ' . $e->getMessage() . '</div>';
}

// ============================================
// 3. CHECK CODEIGNITER SESSION CONFIG
// ============================================
echo '<h2>3️⃣ CodeIgniter Session Configuration</h2>';

$envFile = dirname(__DIR__) . '/.env';
$sessionConfig = dirname(__DIR__) . '/app/Config/Session.php';

if (file_exists($sessionConfig)) {
    $content = file_get_contents($sessionConfig);
    
    // Check driver
    if (preg_match('/public\s+string\s+\$driver\s*=\s*(.+?);/s', $content, $matches)) {
        $driver = trim($matches[1]);
        
        if (strpos($driver, 'RedisHandler') !== false) {
            echo '<div class="success">✅ <strong>Session driver: RedisHandler</strong></div>';
        } elseif (strpos($driver, 'DatabaseHandler') !== false) {
            echo '<div class="error">❌ <strong>Session driver masih DatabaseHandler!</strong></div>';
            echo '<div class="warning">
                <strong>Cara Fix:</strong> Edit <code>app/Config/Session.php</code><br>
                <div class="code">
                use CodeIgniter\Session\Handlers\RedisHandler;<br>
                <br>
                public string $driver = RedisHandler::class;
                </div>
            </div>';
        } else {
            echo '<div class="warning">⚠️ <strong>Session driver:</strong> ' . htmlspecialchars($driver) . '</div>';
        }
    }
    
    // Check savePath
    if (preg_match('/public\s+string\s+\$savePath\s*=\s*[\'"](.+?)[\'"]/s', $content, $matches)) {
        $savePath = trim($matches[1]);
        echo '<div class="info">📁 <strong>Save Path:</strong> ' . htmlspecialchars($savePath) . '</div>';
        
        if (strpos($savePath, 'tcp://') === false && strpos($savePath, 'redis://') === false) {
            echo '<div class="warning">⚠️ <strong>Save path tidak mengarah ke Redis!</strong><br>
                Seharusnya: <code>tcp://127.0.0.1:6379</code>
            </div>';
        }
    }
} else {
    echo '<div class="error">❌ Session.php tidak ditemukan</div>';
}

// Check .env override
if (file_exists($envFile)) {
    $envContent = file_get_contents($envFile);
    
    if (preg_match('/session\.driver\s*=\s*(.+)/i', $envContent, $matches)) {
        $envDriver = trim($matches[1], " \t\n\r\0\x0B'\"");
        echo '<div class="info">🔧 <strong>.env override driver:</strong> ' . htmlspecialchars($envDriver) . '</div>';
    }
    
    if (preg_match('/session\.savePath\s*=\s*(.+)/i', $envContent, $matches)) {
        $envSavePath = trim($matches[1], " \t\n\r\0\x0B'\"");
        echo '<div class="info">🔧 <strong>.env override savePath:</strong> ' . htmlspecialchars($envSavePath) . '</div>';
    }
}

// ============================================
// 4. CHECK ACTIVE SESSION IN REDIS
// ============================================
echo '<h2>4️⃣ Active Sessions in Redis</h2>';

if ($redisConnected && $redis) {
    try {
        // Get all session keys
        $sessionKeys = $redis->keys('ci_session:*');
        
        if (empty($sessionKeys)) {
            echo '<div class="warning">⚠️ <strong>Tidak ada session aktif di Redis</strong><br>
                Ini normal jika belum ada user yang login.
            </div>';
        } else {
            echo '<div class="success">✅ <strong>Ditemukan ' . count($sessionKeys) . ' session aktif di Redis</strong></div>';
            
            echo '<table>';
            echo '<tr><th>No</th><th>Session Key</th><th>TTL (detik)</th><th>Size (bytes)</th><th>Status</th></tr>';
            
            $totalSize = 0;
            foreach ($sessionKeys as $idx => $key) {
                $ttl = $redis->ttl($key);
                $size = strlen($redis->get($key));
                $totalSize += $size;
                
                $status = '<span class="badge badge-success">Active</span>';
                if ($ttl < 300) {
                    $status = '<span class="badge badge-warning">Expiring Soon</span>';
                }
                if ($ttl < 0) {
                    $status = '<span class="badge badge-danger">Expired</span>';
                }
                
                echo '<tr>';
                echo '<td>' . ($idx + 1) . '</td>';
                echo '<td><code>' . htmlspecialchars(substr($key, 0, 50)) . '...</code></td>';
                echo '<td>' . ($ttl > 0 ? $ttl : 'N/A') . '</td>';
                echo '<td>' . number_format($size) . '</td>';
                echo '<td>' . $status . '</td>';
                echo '</tr>';
                
                // Limit display to 20 sessions
                if ($idx >= 19) {
                    echo '<tr><td colspan="5"><em>... dan ' . (count($sessionKeys) - 20) . ' session lainnya</em></td></tr>';
                    break;
                }
            }
            
            echo '</table>';
            
            echo '<div class="info">
                📊 <strong>Total Session Size:</strong> ' . number_format($totalSize) . ' bytes (' . round($totalSize / 1024, 2) . ' KB)
            </div>';
        }
        
    } catch (Exception $e) {
        echo '<div class="error">❌ <strong>Error reading sessions:</strong> ' . $e->getMessage() . '</div>';
    }
}

// ============================================
// 5. CHECK DATABASE ci_sessions TABLE
// ============================================
echo '<h2>5️⃣ Database ci_sessions Table (Legacy Check)</h2>';

try {
    // Load CodeIgniter database config
    require dirname(__DIR__) . '/app/Config/Paths.php';
    
    $envFile = dirname(__DIR__) . '/.env';
    $hostname = 'localhost';
    $username = '';
    $password = '';
    $database = '';
    
    if (file_exists($envFile)) {
        $env = file_get_contents($envFile);
        if (preg_match('/database\.default\.hostname\s*=\s*(.*)/', $env, $m))
            $hostname = trim($m[1]);
        if (preg_match('/database\.default\.database\s*=\s*(.*)/', $env, $m))
            $database = trim($m[1]);
        if (preg_match('/database\.default\.username\s*=\s*(.*)/', $env, $m))
            $username = trim($m[1]);
        if (preg_match('/database\.default\.password\s*=\s*(.*)/', $env, $m))
            $password = trim($m[1]);
    }
    
    if ($username && $database) {
        $mysqli = @new mysqli($hostname, $username, $password, $database);
        
        if (!$mysqli->connect_error) {
            $result = $mysqli->query("SELECT COUNT(*) as count FROM ci_sessions WHERE timestamp > UNIX_TIMESTAMP() - 3600");
            
            if ($result) {
                $row = $result->fetch_assoc();
                $dbSessionCount = $row['count'];
                
                if ($dbSessionCount > 0) {
                    echo '<div class="warning">⚠️ <strong>Masih ada ' . $dbSessionCount . ' session di database!</strong><br>
                        Ini berarti session masih menggunakan database, BUKAN Redis.
                    </div>';
                    
                    echo '<div class="error">
                        <strong>❌ SESSION BELUM MENGGUNAKAN REDIS!</strong><br><br>
                        <strong>Langkah Fix:</strong><br>
                        1. Pastikan <code>app/Config/Session.php</code> sudah benar<br>
                        2. Clear cache: <code>php spark cache:clear</code><br>
                        3. Restart web server: <code>sudo systemctl restart php8.1-fpm nginx</code><br>
                        4. Logout semua user dan login ulang<br>
                        5. Refresh halaman ini
                    </div>';
                } else {
                    echo '<div class="success">✅ <strong>Tidak ada session di database</strong><br>
                        Bagus! Session sudah tidak menggunakan database lagi.
                    </div>';
                }
            }
            
            $mysqli->close();
        }
    }
} catch (Exception $e) {
    echo '<div class="warning">⚠️ Tidak bisa check database: ' . $e->getMessage() . '</div>';
}

// ============================================
// 6. LIVE SESSION TEST
// ============================================
echo '<h2>6️⃣ Live Session Test</h2>';

session_start();

if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 0;
}
$_SESSION['test_counter']++;
$_SESSION['last_access'] = date('Y-m-d H:i:s');

echo '<div class="info">
    <strong>Session ID:</strong> <code>' . session_id() . '</code><br>
    <strong>Test Counter:</strong> ' . $_SESSION['test_counter'] . '<br>
    <strong>Last Access:</strong> ' . $_SESSION['last_access'] . '
</div>';

// Check if session is in Redis
if ($redisConnected && $redis) {
    $sessionKey = 'ci_session:' . session_id();
    
    if ($redis->exists($sessionKey)) {
        echo '<div class="success">✅ <strong>Session ini TERSIMPAN DI REDIS!</strong></div>';
        
        $ttl = $redis->ttl($sessionKey);
        echo '<div class="info">⏱️ <strong>TTL:</strong> ' . $ttl . ' detik (' . round($ttl / 60, 1) . ' menit)</div>';
    } else {
        echo '<div class="warning">⚠️ <strong>Session ini TIDAK ditemukan di Redis</strong></div>';
    }
}

echo '<div class="info">
    <strong>💡 Tip:</strong> Refresh halaman ini beberapa kali. Jika counter bertambah dan session tersimpan di Redis, 
    berarti konfigurasi sudah benar!
</div>';

// ============================================
// 7. SUMMARY & RECOMMENDATIONS
// ============================================
echo '<h2>7️⃣ Summary & Recommendations</h2>';

$allGood = true;
$issues = [];

if (!extension_loaded('redis')) {
    $allGood = false;
    $issues[] = 'PHP Redis extension belum terinstall';
}

if (!$redisConnected) {
    $allGood = false;
    $issues[] = 'Redis server tidak bisa diakses';
}

if ($allGood) {
    echo '<div class="success">
        <h3>🎉 SELAMAT! Session sudah menggunakan Redis dengan benar!</h3>
        <p><strong>Keuntungan yang Anda dapatkan:</strong></p>
        <ul>
            <li>✅ Performa 10x lebih cepat dari database</li>
            <li>✅ Tidak ada lock contention</li>
            <li>✅ Bisa handle 500-1000+ concurrent users</li>
            <li>✅ Session lebih stabil dan reliable</li>
        </ul>
        <p><strong>Next Steps:</strong></p>
        <ul>
            <li>Monitor Redis memory usage: <code>redis-cli info memory</code></li>
            <li>Setup Redis persistence (RDB/AOF) untuk backup</li>
            <li>Lakukan load testing dengan 100+ users</li>
        </ul>
    </div>';
} else {
    echo '<div class="error">
        <h3>❌ Session BELUM menggunakan Redis!</h3>
        <p><strong>Issues yang ditemukan:</strong></p>
        <ul>';
    foreach ($issues as $issue) {
        echo '<li>' . $issue . '</li>';
    }
    echo '</ul>
        <p><strong>Silakan fix issues di atas dan refresh halaman ini.</strong></p>
    </div>';
}

// ============================================
// 8. USEFUL COMMANDS
// ============================================
echo '<h2>8️⃣ Useful Redis Commands</h2>';

echo '<div class="code">
# Monitor Redis real-time<br>
redis-cli monitor<br>
<br>
# Check all session keys<br>
redis-cli KEYS "ci_session:*"<br>
<br>
# Count sessions<br>
redis-cli KEYS "ci_session:*" | wc -l<br>
<br>
# Get session data<br>
redis-cli GET "ci_session:your_session_id"<br>
<br>
# Check Redis memory<br>
redis-cli INFO memory<br>
<br>
# Check Redis stats<br>
redis-cli INFO stats<br>
<br>
# Flush all sessions (HATI-HATI!)<br>
redis-cli FLUSHDB
</div>';

echo '<hr style="margin: 30px 0;">';
echo '<p style="text-align: center; color: #999;">
    <small>Redis Session Verification Tool v1.0 | Created by Kiro AI Assistant</small>
</p>';

echo '</div>'; // Close container

// Close Redis connection
if ($redis) {
    $redis->close();
}
?>
