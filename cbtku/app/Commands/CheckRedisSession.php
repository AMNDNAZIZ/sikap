<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Check Redis Session Command
 * 
 * Verifikasi apakah session sudah menggunakan Redis
 * 
 * Usage: php spark redis:check
 */
class CheckRedisSession extends BaseCommand
{
    protected $group       = 'Redis';
    protected $name        = 'redis:check';
    protected $description = 'Verifikasi apakah session sudah menggunakan Redis';

    public function run(array $params)
    {
        CLI::write('╔════════════════════════════════════════════════════════════╗', 'yellow');
        CLI::write('║        Redis Session Verification Tool (CLI)              ║', 'yellow');
        CLI::write('╚════════════════════════════════════════════════════════════╝', 'yellow');
        CLI::newLine();

        // 1. Check PHP Redis Extension
        CLI::write('1️⃣  Checking PHP Redis Extension...', 'cyan');
        if (extension_loaded('redis')) {
            CLI::write('   ✅ PHP Redis extension: INSTALLED', 'green');
            CLI::write('   📦 Version: ' . phpversion('redis'), 'white');
        } else {
            CLI::write('   ❌ PHP Redis extension: NOT INSTALLED', 'red');
            CLI::write('   💡 Install: sudo apt-get install php-redis', 'yellow');
            return;
        }
        CLI::newLine();

        // 2. Check Redis Server Connection
        CLI::write('2️⃣  Checking Redis Server Connection...', 'cyan');
        try {
            $redis = new \Redis();
            
            $host = '127.0.0.1';
            $port = 6379;
            
            if ($redis->connect($host, $port, 2)) {
                CLI::write('   ✅ Redis Server: CONNECTED', 'green');
                
                $info = $redis->info();
                CLI::write('   📊 Redis Version: ' . ($info['redis_version'] ?? 'N/A'), 'white');
                CLI::write('   📊 Uptime: ' . round(($info['uptime_in_seconds'] ?? 0) / 86400, 2) . ' days', 'white');
                CLI::write('   📊 Connected Clients: ' . ($info['connected_clients'] ?? 'N/A'), 'white');
                CLI::write('   📊 Used Memory: ' . ($info['used_memory_human'] ?? 'N/A'), 'white');
                CLI::write('   📊 Total Keys: ' . $redis->dbSize(), 'white');
            } else {
                CLI::write('   ❌ Redis Server: NOT CONNECTED', 'red');
                CLI::write('   💡 Check: sudo systemctl status redis', 'yellow');
                return;
            }
        } catch (\Exception $e) {
            CLI::write('   ❌ Error: ' . $e->getMessage(), 'red');
            return;
        }
        CLI::newLine();

        // 3. Check CodeIgniter Session Config
        CLI::write('3️⃣  Checking CodeIgniter Session Config...', 'cyan');
        
        $sessionConfig = new \Config\Session();
        $driverClass = $sessionConfig->driver;
        
        if (strpos($driverClass, 'RedisHandler') !== false) {
            CLI::write('   ✅ Session Driver: RedisHandler', 'green');
        } elseif (strpos($driverClass, 'DatabaseHandler') !== false) {
            CLI::write('   ❌ Session Driver: DatabaseHandler (MASIH DATABASE!)', 'red');
            CLI::write('   💡 Fix: Edit app/Config/Session.php', 'yellow');
            CLI::write('   💡 Set: public string $driver = RedisHandler::class;', 'yellow');
            return;
        } else {
            CLI::write('   ⚠️  Session Driver: ' . $driverClass, 'yellow');
        }
        
        CLI::write('   📁 Save Path: ' . $sessionConfig->savePath, 'white');
        CLI::write('   ⏱️  Expiration: ' . $sessionConfig->expiration . ' seconds (' . round($sessionConfig->expiration / 3600, 1) . ' hours)', 'white');
        CLI::write('   🔄 Time to Update: ' . $sessionConfig->timeToUpdate . ' seconds', 'white');
        
        CLI::newLine();

        // 4. Check Active Sessions in Redis
        CLI::write('4️⃣  Checking Active Sessions in Redis...', 'cyan');
        
        try {
            $sessionKeys = $redis->keys('ci_session:*');
            
            if (empty($sessionKeys)) {
                CLI::write('   ⚠️  No active sessions found in Redis', 'yellow');
                CLI::write('   💡 This is normal if no users are logged in', 'white');
            } else {
                CLI::write('   ✅ Found ' . count($sessionKeys) . ' active sessions in Redis', 'green');
                
                // Show sample sessions
                CLI::newLine();
                CLI::write('   📋 Sample Sessions:', 'white');
                
                $table = new \CodeIgniter\CLI\Table();
                $table->setHeading(['No', 'Session Key', 'TTL (sec)', 'Size (bytes)', 'Status']);
                
                $limit = min(10, count($sessionKeys));
                for ($i = 0; $i < $limit; $i++) {
                    $key = $sessionKeys[$i];
                    $ttl = $redis->ttl($key);
                    $size = strlen($redis->get($key));
                    
                    $status = 'Active';
                    if ($ttl < 300) $status = 'Expiring';
                    if ($ttl < 0) $status = 'Expired';
                    
                    $table->addRow([
                        $i + 1,
                        substr($key, 0, 40) . '...',
                        $ttl > 0 ? $ttl : 'N/A',
                        number_format($size),
                        $status
                    ]);
                }
                
                if (count($sessionKeys) > 10) {
                    $table->addRow(['...', '... and ' . (count($sessionKeys) - 10) . ' more', '', '', '']);
                }
                
                CLI::write($table->generate());
            }
        } catch (\Exception $e) {
            CLI::write('   ❌ Error reading sessions: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();

        // 5. Check Database ci_sessions Table
        CLI::write('5️⃣  Checking Database ci_sessions Table (Legacy)...', 'cyan');
        
        try {
            $db = \Config\Database::connect();
            
            // Check if table exists
            if ($db->tableExists('ci_sessions')) {
                $query = $db->query("SELECT COUNT(*) as count FROM ci_sessions WHERE timestamp > UNIX_TIMESTAMP() - 3600");
                $result = $query->getRow();
                $dbSessionCount = $result->count;
                
                if ($dbSessionCount > 0) {
                    CLI::write('   ⚠️  Found ' . $dbSessionCount . ' sessions in DATABASE!', 'yellow');
                    CLI::write('   ❌ Sessions are still using DATABASE, NOT Redis!', 'red');
                    CLI::newLine();
                    CLI::write('   💡 Fix Steps:', 'yellow');
                    CLI::write('      1. Clear cache: php spark cache:clear', 'white');
                    CLI::write('      2. Restart web server: sudo systemctl restart php8.1-fpm nginx', 'white');
                    CLI::write('      3. Logout all users and login again', 'white');
                    CLI::write('      4. Run this command again', 'white');
                } else {
                    CLI::write('   ✅ No sessions in database (Good!)', 'green');
                }
            } else {
                CLI::write('   ℹ️  Table ci_sessions does not exist', 'white');
            }
        } catch (\Exception $e) {
            CLI::write('   ⚠️  Cannot check database: ' . $e->getMessage(), 'yellow');
        }
        
        CLI::newLine();

        // 6. Performance Test
        CLI::write('6️⃣  Running Performance Test...', 'cyan');
        
        try {
            // Test write speed
            $startWrite = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $redis->set('test_key_' . $i, 'test_value_' . $i, 60);
            }
            $writeTime = (microtime(true) - $startWrite) * 1000;
            
            // Test read speed
            $startRead = microtime(true);
            for ($i = 0; $i < 100; $i++) {
                $redis->get('test_key_' . $i);
            }
            $readTime = (microtime(true) - $startRead) * 1000;
            
            // Cleanup
            for ($i = 0; $i < 100; $i++) {
                $redis->del('test_key_' . $i);
            }
            
            CLI::write('   ⚡ Write Speed: ' . round($writeTime, 2) . ' ms (100 operations)', 'white');
            CLI::write('   ⚡ Read Speed: ' . round($readTime, 2) . ' ms (100 operations)', 'white');
            
            if ($writeTime < 100 && $readTime < 50) {
                CLI::write('   ✅ Performance: EXCELLENT', 'green');
            } elseif ($writeTime < 500 && $readTime < 200) {
                CLI::write('   ✅ Performance: GOOD', 'green');
            } else {
                CLI::write('   ⚠️  Performance: SLOW (check Redis config)', 'yellow');
            }
        } catch (\Exception $e) {
            CLI::write('   ❌ Performance test failed: ' . $e->getMessage(), 'red');
        }
        
        CLI::newLine();

        // 7. Summary
        CLI::write('╔════════════════════════════════════════════════════════════╗', 'yellow');
        CLI::write('║                        SUMMARY                             ║', 'yellow');
        CLI::write('╚════════════════════════════════════════════════════════════╝', 'yellow');
        CLI::newLine();
        
        $allGood = true;
        
        if (!extension_loaded('redis')) {
            $allGood = false;
            CLI::write('❌ PHP Redis extension not installed', 'red');
        }
        
        if (!$redis->isConnected()) {
            $allGood = false;
            CLI::write('❌ Redis server not connected', 'red');
        }
        
        if (strpos($driverClass, 'RedisHandler') === false) {
            $allGood = false;
            CLI::write('❌ Session driver not using RedisHandler', 'red');
        }
        
        if (empty($sessionKeys) && isset($dbSessionCount) && $dbSessionCount > 0) {
            $allGood = false;
            CLI::write('❌ Sessions still in database, not in Redis', 'red');
        }
        
        if ($allGood) {
            CLI::write('🎉 CONGRATULATIONS! Session is using Redis correctly!', 'green');
            CLI::newLine();
            CLI::write('✅ Benefits you get:', 'green');
            CLI::write('   • 10x faster than database', 'white');
            CLI::write('   • No lock contention', 'white');
            CLI::write('   • Can handle 500-1000+ concurrent users', 'white');
            CLI::write('   • More stable and reliable sessions', 'white');
            CLI::newLine();
            CLI::write('📊 Next Steps:', 'cyan');
            CLI::write('   • Monitor Redis: redis-cli monitor', 'white');
            CLI::write('   • Check memory: redis-cli info memory', 'white');
            CLI::write('   • Load test with 100+ users', 'white');
        } else {
            CLI::write('⚠️  Session is NOT using Redis yet!', 'yellow');
            CLI::write('Please fix the issues above and run this command again.', 'white');
        }
        
        CLI::newLine();
        
        // Close Redis connection
        if ($redis) {
            $redis->close();
        }
    }
}
