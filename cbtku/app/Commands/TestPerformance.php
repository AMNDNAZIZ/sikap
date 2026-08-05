<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Performance Testing Command
 * 
 * Test session performance and database query speed
 * 
 * Usage: php spark test:performance
 */
class TestPerformance extends BaseCommand
{
    protected $group       = 'Testing';
    protected $name        = 'test:performance';
    protected $description = 'Test session and database performance';

    protected $usage = 'test:performance [options]';
    protected $options = [
        '--iterations' => 'Number of test iterations (default: 100)',
        '--type'       => 'Test type: session, database, cache, all (default: all)',
    ];

    public function run(array $params)
    {
        $iterations = (int) ($params['iterations'] ?? 100);
        $type = $params['type'] ?? 'all';

        CLI::write('═══════════════════════════════════════════════════', 'yellow');
        CLI::write('    Performance Test - ' . date('Y-m-d H:i:s'), 'yellow');
        CLI::write('    Iterations: ' . $iterations, 'yellow');
        CLI::write('═══════════════════════════════════════════════════', 'yellow');
        CLI::newLine();

        if ($type === 'all' || $type === 'session') {
            $this->testSessionPerformance($iterations);
            CLI::newLine();
        }

        if ($type === 'all' || $type === 'database') {
            $this->testDatabasePerformance($iterations);
            CLI::newLine();
        }

        if ($type === 'all' || $type === 'cache') {
            $this->testCachePerformance($iterations);
            CLI::newLine();
        }

        CLI::write('═══════════════════════════════════════════════════', 'yellow');
        CLI::write('✅ Performance test completed!', 'green');
    }

    /**
     * Test session read/write performance
     */
    protected function testSessionPerformance(int $iterations)
    {
        CLI::write('1️⃣  Testing Session Performance...', 'cyan');

        // Check if using Redis
        $sessionConfig = new \Config\Session();
        $usingRedis = strpos($sessionConfig->driver, 'RedisHandler') !== false;

        CLI::write('   Driver: ' . ($usingRedis ? 'Redis' : 'Database/File'), 'white');

        if ($usingRedis && extension_loaded('redis')) {
            $this->testRedisPerformance($iterations);
        } else {
            CLI::write('   ⚠️  Redis not available, skipping session test', 'yellow');
        }
    }

    /**
     * Test Redis performance
     */
    protected function testRedisPerformance(int $iterations)
    {
        try {
            $redis = new \Redis();
            
            if (!$redis->connect('127.0.0.1', 6379, 2)) {
                CLI::write('   ❌ Cannot connect to Redis', 'red');
                return;
            }

            // Test WRITE performance
            $startWrite = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $redis->set("test_perf_key_{$i}", "test_value_{$i}", 60);
            }
            $writeTime = (microtime(true) - $startWrite) * 1000;

            // Test READ performance
            $startRead = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $redis->get("test_perf_key_{$i}");
            }
            $readTime = (microtime(true) - $startRead) * 1000;

            // Test DELETE performance
            $startDelete = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $redis->del("test_perf_key_{$i}");
            }
            $deleteTime = (microtime(true) - $startDelete) * 1000;

            // Display results
            CLI::write('   📊 Results:', 'white');
            CLI::write('      • Write: ' . round($writeTime, 2) . ' ms (' . round($iterations / ($writeTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($writeTime, 100));
            CLI::write('      • Read:  ' . round($readTime, 2) . ' ms (' . round($iterations / ($readTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($readTime, 50));
            CLI::write('      • Delete: ' . round($deleteTime, 2) . ' ms (' . round($iterations / ($deleteTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($deleteTime, 100));

            // Overall assessment
            $avgTime = ($writeTime + $readTime + $deleteTime) / 3;
            if ($avgTime < 50) {
                CLI::write('   ✅ Performance: EXCELLENT', 'green');
            } elseif ($avgTime < 200) {
                CLI::write('   ✅ Performance: GOOD', 'green');
            } elseif ($avgTime < 500) {
                CLI::write('   ⚠️  Performance: ACCEPTABLE', 'yellow');
            } else {
                CLI::write('   ❌ Performance: POOR (check Redis config)', 'red');
            }

            $redis->close();

        } catch (\Exception $e) {
            CLI::write('   ❌ Redis test error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Test database query performance
     */
    protected function testDatabasePerformance(int $iterations)
    {
        CLI::write('2️⃣  Testing Database Performance...', 'cyan');

        try {
            $db = \Config\Database::connect();

            // Test SELECT performance
            $startSelect = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $db->query("SELECT 1");
            }
            $selectTime = (microtime(true) - $startSelect) * 1000;

            // Test with actual table (if exists)
            if ($db->tableExists('users')) {
                $startTable = microtime(true);
                for ($i = 0; $i < $iterations; $i++) {
                    $db->query("SELECT id, username FROM users LIMIT 1");
                }
                $tableTime = (microtime(true) - $startTable) * 1000;
            } else {
                $tableTime = 0;
            }

            // Display results
            CLI::write('   📊 Results:', 'white');
            CLI::write('      • Simple Query: ' . round($selectTime, 2) . ' ms (' . round($iterations / ($selectTime / 1000), 0) . ' queries/sec)', 
                $this->getColorForTime($selectTime, 200));
            
            if ($tableTime > 0) {
                CLI::write('      • Table Query:  ' . round($tableTime, 2) . ' ms (' . round($iterations / ($tableTime / 1000), 0) . ' queries/sec)', 
                    $this->getColorForTime($tableTime, 300));
            }

            // Check connection pool
            $connQuery = $db->query("SHOW STATUS LIKE 'Threads_connected'");
            $connResult = $connQuery->getRow();
            $connections = $connResult->Value ?? 0;

            $maxQuery = $db->query("SHOW VARIABLES LIKE 'max_connections'");
            $maxResult = $maxQuery->getRow();
            $maxConnections = $maxResult->Value ?? 0;

            CLI::write('   🔌 Connections: ' . $connections . ' / ' . $maxConnections . ' (' . round(($connections / $maxConnections) * 100, 1) . '%)', 'white');

            // Overall assessment
            $avgTime = $tableTime > 0 ? ($selectTime + $tableTime) / 2 : $selectTime;
            if ($avgTime < 100) {
                CLI::write('   ✅ Performance: EXCELLENT', 'green');
            } elseif ($avgTime < 300) {
                CLI::write('   ✅ Performance: GOOD', 'green');
            } elseif ($avgTime < 1000) {
                CLI::write('   ⚠️  Performance: ACCEPTABLE', 'yellow');
            } else {
                CLI::write('   ❌ Performance: POOR (check database config)', 'red');
            }

        } catch (\Exception $e) {
            CLI::write('   ❌ Database test error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Test cache performance
     */
    protected function testCachePerformance(int $iterations)
    {
        CLI::write('3️⃣  Testing Cache Performance...', 'cyan');

        try {
            $cache = \Config\Services::cache();

            // Test WRITE performance
            $startWrite = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $cache->save("test_cache_key_{$i}", "test_value_{$i}", 60);
            }
            $writeTime = (microtime(true) - $startWrite) * 1000;

            // Test READ performance
            $startRead = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $cache->get("test_cache_key_{$i}");
            }
            $readTime = (microtime(true) - $startRead) * 1000;

            // Test DELETE performance
            $startDelete = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                $cache->delete("test_cache_key_{$i}");
            }
            $deleteTime = (microtime(true) - $startDelete) * 1000;

            // Display results
            CLI::write('   📊 Results:', 'white');
            CLI::write('      • Write: ' . round($writeTime, 2) . ' ms (' . round($iterations / ($writeTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($writeTime, 100));
            CLI::write('      • Read:  ' . round($readTime, 2) . ' ms (' . round($iterations / ($readTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($readTime, 50));
            CLI::write('      • Delete: ' . round($deleteTime, 2) . ' ms (' . round($iterations / ($deleteTime / 1000), 0) . ' ops/sec)', 
                $this->getColorForTime($deleteTime, 100));

            // Overall assessment
            $avgTime = ($writeTime + $readTime + $deleteTime) / 3;
            if ($avgTime < 50) {
                CLI::write('   ✅ Performance: EXCELLENT', 'green');
            } elseif ($avgTime < 200) {
                CLI::write('   ✅ Performance: GOOD', 'green');
            } elseif ($avgTime < 500) {
                CLI::write('   ⚠️  Performance: ACCEPTABLE', 'yellow');
            } else {
                CLI::write('   ❌ Performance: POOR (check cache config)', 'red');
            }

        } catch (\Exception $e) {
            CLI::write('   ❌ Cache test error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Get color based on time threshold
     */
    protected function getColorForTime(float $time, float $threshold): string
    {
        if ($time < $threshold) {
            return 'green';
        } elseif ($time < $threshold * 2) {
            return 'yellow';
        } else {
            return 'red';
        }
    }
}
