<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Monitor Active Sessions Command
 * 
 * Monitor active sessions and alert if threshold exceeded
 * 
 * Usage: php spark monitor:sessions
 */
class MonitorSessions extends BaseCommand
{
    protected $group       = 'Monitoring';
    protected $name        = 'monitor:sessions';
    protected $description = 'Monitor active sessions and alert if threshold exceeded';

    protected $usage = 'monitor:sessions [options]';
    protected $arguments = [];
    protected $options = [
        '--threshold' => 'Session count threshold for alert (default: 45)',
        '--email'     => 'Email address for alerts',
        '--verbose'   => 'Show detailed output',
    ];

    public function run(array $params)
    {
        $threshold = $params['threshold'] ?? 45;
        $email = $params['email'] ?? null;
        $verbose = isset($params['verbose']);

        CLI::write('═══════════════════════════════════════════════════', 'yellow');
        CLI::write('    Session Monitor - ' . date('Y-m-d H:i:s'), 'yellow');
        CLI::write('═══════════════════════════════════════════════════', 'yellow');
        CLI::newLine();

        // Check if Redis is available
        if (extension_loaded('redis')) {
            $this->monitorRedis($threshold, $email, $verbose);
        } else {
            $this->monitorDatabase($threshold, $email, $verbose);
        }

        CLI::newLine();
        CLI::write('═══════════════════════════════════════════════════', 'yellow');
    }

    /**
     * Monitor Redis sessions
     */
    protected function monitorRedis(int $threshold, ?string $email, bool $verbose)
    {
        try {
            $redis = new \Redis();
            
            if (!$redis->connect('127.0.0.1', 6379, 2)) {
                CLI::write('❌ Cannot connect to Redis', 'red');
                return;
            }

            // Count active sessions
            $sessionKeys = $redis->keys('ci_session:*');
            $sessionCount = count($sessionKeys);

            CLI::write("📊 Active Sessions (Redis): {$sessionCount}", 'cyan');

            // Check threshold
            if ($sessionCount > $threshold) {
                $percentage = round(($sessionCount / $threshold) * 100, 1);
                CLI::write("⚠️  WARNING: Session count ({$sessionCount}) exceeded threshold ({$threshold}) - {$percentage}%", 'red');
                
                $this->sendAlert($sessionCount, $threshold, $email, 'Redis');
            } else {
                $percentage = round(($sessionCount / $threshold) * 100, 1);
                CLI::write("✅ Session count within limit - {$percentage}% of threshold", 'green');
            }

            // Redis stats
            if ($verbose) {
                CLI::newLine();
                CLI::write('Redis Statistics:', 'cyan');
                
                $info = $redis->info();
                CLI::write('  • Used Memory: ' . ($info['used_memory_human'] ?? 'N/A'), 'white');
                CLI::write('  • Connected Clients: ' . ($info['connected_clients'] ?? 'N/A'), 'white');
                CLI::write('  • Total Keys: ' . $redis->dbSize(), 'white');
                CLI::write('  • Uptime: ' . round(($info['uptime_in_seconds'] ?? 0) / 86400, 1) . ' days', 'white');

                // Show expiring sessions
                $expiringSoon = 0;
                foreach ($sessionKeys as $key) {
                    $ttl = $redis->ttl($key);
                    if ($ttl > 0 && $ttl < 300) { // < 5 minutes
                        $expiringSoon++;
                    }
                }
                
                if ($expiringSoon > 0) {
                    CLI::write("  • Sessions expiring soon (<5 min): {$expiringSoon}", 'yellow');
                }
            }

            $redis->close();

        } catch (\Exception $e) {
            CLI::write('❌ Redis monitoring error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Monitor Database sessions
     */
    protected function monitorDatabase(int $threshold, ?string $email, bool $verbose)
    {
        try {
            $db = \Config\Database::connect();

            if (!$db->tableExists('ci_sessions')) {
                CLI::write('⚠️  Table ci_sessions not found', 'yellow');
                return;
            }

            // Count active sessions (last 1 hour)
            $query = $db->query("
                SELECT COUNT(*) as count 
                FROM ci_sessions 
                WHERE timestamp > UNIX_TIMESTAMP() - 3600
            ");
            
            $result = $query->getRow();
            $sessionCount = $result->count ?? 0;

            CLI::write("📊 Active Sessions (Database): {$sessionCount}", 'cyan');

            // Check threshold
            if ($sessionCount > $threshold) {
                $percentage = round(($sessionCount / $threshold) * 100, 1);
                CLI::write("⚠️  WARNING: Session count ({$sessionCount}) exceeded threshold ({$threshold}) - {$percentage}%", 'red');
                
                $this->sendAlert($sessionCount, $threshold, $email, 'Database');
            } else {
                $percentage = round(($sessionCount / $threshold) * 100, 1);
                CLI::write("✅ Session count within limit - {$percentage}% of threshold", 'green');
            }

            // Database stats
            if ($verbose) {
                CLI::newLine();
                CLI::write('Database Statistics:', 'cyan');

                // Total sessions
                $totalQuery = $db->query("SELECT COUNT(*) as count FROM ci_sessions");
                $totalResult = $totalQuery->getRow();
                CLI::write('  • Total Sessions: ' . ($totalResult->count ?? 0), 'white');

                // Table size
                $sizeQuery = $db->query("
                    SELECT 
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
                    FROM information_schema.TABLES 
                    WHERE table_schema = DATABASE() 
                    AND table_name = 'ci_sessions'
                ");
                $sizeResult = $sizeQuery->getRow();
                CLI::write('  • Table Size: ' . ($sizeResult->size_mb ?? 0) . ' MB', 'white');
            }

        } catch (\Exception $e) {
            CLI::write('❌ Database monitoring error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Monitor database connections
     */
    protected function monitorDatabaseConnections(bool $verbose)
    {
        try {
            $db = \Config\Database::connect();

            $query = $db->query("SHOW STATUS LIKE 'Threads_connected'");
            $result = $query->getRow();
            $connections = $result->Value ?? 0;

            CLI::write("🔌 Database Connections: {$connections}", 'cyan');

            if ($connections > 140) {
                CLI::write('⚠️  WARNING: Database connection pool almost full!', 'red');
            } else {
                CLI::write('✅ Database connections healthy', 'green');
            }

            if ($verbose) {
                // Max connections
                $maxQuery = $db->query("SHOW VARIABLES LIKE 'max_connections'");
                $maxResult = $maxQuery->getRow();
                $maxConnections = $maxResult->Value ?? 0;
                
                $percentage = round(($connections / $maxConnections) * 100, 1);
                CLI::write("  • Max Connections: {$maxConnections}", 'white');
                CLI::write("  • Usage: {$percentage}%", 'white');
            }

        } catch (\Exception $e) {
            CLI::write('❌ Connection monitoring error: ' . $e->getMessage(), 'red');
        }
    }

    /**
     * Send alert notification
     */
    protected function sendAlert(int $count, int $threshold, ?string $email, string $type)
    {
        $message = "Session count ({$count}) exceeded threshold ({$threshold}) in {$type}";
        
        // Log to system
        log_message('critical', "[SessionMonitor] {$message}");

        // Send email if configured
        if ($email) {
            try {
                $emailService = \Config\Services::email();
                
                $emailService->setTo($email);
                $emailService->setSubject('⚠️ Session Alert - CBT Application');
                $emailService->setMessage("
                    <h2>Session Threshold Alert</h2>
                    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
                    <p><strong>Type:</strong> {$type}</p>
                    <p><strong>Current Count:</strong> {$count}</p>
                    <p><strong>Threshold:</strong> {$threshold}</p>
                    <p><strong>Percentage:</strong> " . round(($count / $threshold) * 100, 1) . "%</p>
                    <hr>
                    <p>Please check the system and consider scaling if necessary.</p>
                ");

                if ($emailService->send()) {
                    CLI::write('📧 Alert email sent to: ' . $email, 'green');
                } else {
                    CLI::write('❌ Failed to send alert email', 'red');
                }
            } catch (\Exception $e) {
                CLI::write('❌ Email error: ' . $e->getMessage(), 'red');
            }
        }

        // Could also send SMS, Slack, etc. here
    }
}
