<?php
/**
 * DIAGNOSE: Random Logout Issue
 * 
 * Comprehensive check untuk menemukan penyebab random logout
 */

require_once __DIR__ . '/vendor/autoload.php';

// Load CodeIgniter
$paths = new Config\Paths();
$bootstrap = rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';
$app = require realpath($bootstrap) ?: $bootstrap;

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNOSE: RANDOM LOGOUT ISSUE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$db = \Config\Database::connect();

// 1. Check Session Configuration
echo "1. SESSION CONFIGURATION\n";
echo "─────────────────────────────────────────────────────────────\n";

$sessionConfig = new \Config\Session();
echo "Driver: " . $sessionConfig->driver . "\n";
echo "Cookie Name: " . $sessionConfig->cookieName . "\n";
echo "Expiration: " . $sessionConfig->expiration . " seconds (" . ($sessionConfig->expiration / 60) . " minutes)\n";
echo "Save Path: " . $sessionConfig->savePath . "\n";
echo "Match IP: " . ($sessionConfig->matchIP ? 'Yes' : 'No') . "\n";
echo "Time to Update: " . $sessionConfig->timeToUpdate . " seconds\n";
echo "Regenerate Destroy: " . ($sessionConfig->regenerateDestroy ? 'Yes' : 'No') . "\n\n";

// Check if driver matches save path
if (strpos($sessionConfig->driver, 'DatabaseHandler') !== false) {
    if (strpos($sessionConfig->savePath, 'session') !== false && strpos($sessionConfig->savePath, WRITEPATH) !== false) {
        echo "⚠️  WARNING: Driver is DatabaseHandler but savePath points to file directory!\n";
        echo "   This is inconsistent and may cause issues.\n";
        echo "   For DatabaseHandler, savePath should be table name (e.g., 'ci_sessions')\n\n";
    }
}

// 2. Check Session Table
echo "2. SESSION TABLE CHECK\n";
echo "─────────────────────────────────────────────────────────────\n";

try {
    // Check if table exists
    $tableExists = $db->tableExists('ci_sessions');
    
    if (!$tableExists) {
        echo "❌ ERROR: Table 'ci_sessions' does not exist!\n";
        echo "   Session data cannot be stored in database.\n\n";
    } else {
        echo "✓ Table 'ci_sessions' exists\n\n";
        
        // Get table structure
        $fields = $db->getFieldData('ci_sessions');
        echo "Table Structure:\n";
        foreach ($fields as $field) {
            echo "  - {$field->name} ({$field->type}";
            if ($field->max_length) echo ", max: {$field->max_length}";
            if ($field->primary_key) echo ", PRIMARY KEY";
            echo ")\n";
        }
        echo "\n";
        
        // Check for corrupt sessions
        $query = $db->query("
            SELECT 
                id,
                ip_address,
                timestamp,
                FROM_UNIXTIME(timestamp) as readable_time,
                LENGTH(data) as data_size
            FROM ci_sessions
            ORDER BY timestamp DESC
            LIMIT 10
        ");
        
        $sessions = $query->getResultArray();
        
        echo "Recent Sessions (last 10):\n";
        if (empty($sessions)) {
            echo "  No sessions found in database\n";
        } else {
            foreach ($sessions as $s) {
                $age = time() - $s['timestamp'];
                $ageMin = round($age / 60, 1);
                echo "  - ID: {$s['id']}\n";
                echo "    IP: {$s['ip_address']}\n";
                echo "    Time: {$s['readable_time']} ({$ageMin} min ago)\n";
                echo "    Data Size: {$s['data_size']} bytes\n";
                
                // Check if expired
                if ($age > $sessionConfig->expiration) {
                    echo "    ⚠️  EXPIRED (age: {$ageMin} min > expiration: " . ($sessionConfig->expiration / 60) . " min)\n";
                }
                echo "\n";
            }
        }
        
        // Check for sessions with future timestamps
        $futureQuery = $db->query("
            SELECT COUNT(*) as count
            FROM ci_sessions
            WHERE timestamp > UNIX_TIMESTAMP()
        ");
        $futureCount = $futureQuery->getRow()->count;
        
        if ($futureCount > 0) {
            echo "⚠️  WARNING: Found {$futureCount} sessions with future timestamps!\n";
            echo "   This indicates timestamp corruption.\n\n";
        }
        
        // Check for very old sessions
        $oldQuery = $db->query("
            SELECT COUNT(*) as count
            FROM ci_sessions
            WHERE timestamp < UNIX_TIMESTAMP() - " . ($sessionConfig->expiration * 2) . "
        ");
        $oldCount = $oldQuery->getRow()->count;
        
        if ($oldCount > 0) {
            echo "⚠️  WARNING: Found {$oldCount} expired sessions not cleaned up!\n";
            echo "   Garbage collection may not be working properly.\n\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR checking session table: " . $e->getMessage() . "\n\n";
}

// 3. Check PHP Session Settings
echo "3. PHP SESSION SETTINGS\n";
echo "─────────────────────────────────────────────────────────────\n";
echo "session.gc_probability: " . ini_get('session.gc_probability') . "\n";
echo "session.gc_divisor: " . ini_get('session.gc_divisor') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . " seconds\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . " seconds\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_samesite: " . ini_get('session.cookie_samesite') . "\n\n";

// Calculate GC probability
$gcProb = ini_get('session.gc_probability');
$gcDiv = ini_get('session.gc_divisor');
if ($gcDiv > 0) {
    $gcChance = ($gcProb / $gcDiv) * 100;
    echo "Garbage Collection Chance: {$gcChance}% per request\n";
    if ($gcChance < 1) {
        echo "⚠️  WARNING: GC chance is very low! Old sessions may not be cleaned up.\n";
    }
}
echo "\n";

// 4. Check for Session Lock Issues
echo "4. SESSION LOCK CHECK\n";
echo "─────────────────────────────────────────────────────────────\n";

try {
    // Check for user_session_locks table
    $lockTableExists = $db->tableExists('user_session_locks');
    
    if ($lockTableExists) {
        echo "✓ Table 'user_session_locks' exists\n\n";
        
        // Check for active locks
        $lockQuery = $db->query("
            SELECT 
                user_id,
                session_id,
                ip_address,
                user_agent,
                FROM_UNIXTIME(locked_at) as locked_time,
                UNIX_TIMESTAMP() - locked_at as age_seconds
            FROM user_session_locks
            ORDER BY locked_at DESC
            LIMIT 10
        ");
        
        $locks = $lockQuery->getResultArray();
        
        if (empty($locks)) {
            echo "No active session locks found\n\n";
        } else {
            echo "Active Session Locks:\n";
            foreach ($locks as $lock) {
                $ageMin = round($lock['age_seconds'] / 60, 1);
                echo "  - User ID: {$lock['user_id']}\n";
                echo "    Session ID: {$lock['session_id']}\n";
                echo "    IP: {$lock['ip_address']}\n";
                echo "    Locked: {$lock['locked_time']} ({$ageMin} min ago)\n";
                
                // Check if lock is stale
                if ($lock['age_seconds'] > 3600) { // 1 hour
                    echo "    ⚠️  STALE LOCK (older than 1 hour)\n";
                }
                echo "\n";
            }
        }
    } else {
        echo "ℹ️  Table 'user_session_locks' does not exist (session lock feature not used)\n\n";
    }
} catch (\Exception $e) {
    echo "❌ ERROR checking session locks: " . $e->getMessage() . "\n\n";
}

// 5. Check for Concurrent Session Issues
echo "5. CONCURRENT SESSION CHECK\n";
echo "─────────────────────────────────────────────────────────────\n";

try {
    // Check for users with multiple active sessions
    $concurrentQuery = $db->query("
        SELECT 
            SUBSTRING_INDEX(SUBSTRING_INDEX(data, 'user_id\";i:', -1), ';', 1) as user_id,
            COUNT(*) as session_count,
            GROUP_CONCAT(ip_address) as ip_addresses
        FROM ci_sessions
        WHERE data LIKE '%user_id%'
        GROUP BY user_id
        HAVING session_count > 1
        ORDER BY session_count DESC
        LIMIT 10
    ");
    
    $concurrent = $concurrentQuery->getResultArray();
    
    if (empty($concurrent)) {
        echo "No users with multiple active sessions\n\n";
    } else {
        echo "Users with Multiple Active Sessions:\n";
        foreach ($concurrent as $c) {
            echo "  - User ID: {$c['user_id']}\n";
            echo "    Session Count: {$c['session_count']}\n";
            echo "    IP Addresses: {$c['ip_addresses']}\n";
            echo "    ⚠️  Multiple sessions may cause conflicts\n\n";
        }
    }
} catch (\Exception $e) {
    echo "ℹ️  Could not check concurrent sessions (data format may vary)\n\n";
}

// 6. Recommendations
echo "6. RECOMMENDATIONS\n";
echo "─────────────────────────────────────────────────────────────\n";

$issues = [];

// Check savePath issue
if (strpos($sessionConfig->driver, 'DatabaseHandler') !== false) {
    if (strpos($sessionConfig->savePath, WRITEPATH) !== false) {
        $issues[] = "Fix savePath in Session.php: Change to 'ci_sessions' for DatabaseHandler";
    }
}

// Check expiration
if ($sessionConfig->expiration < 3600) {
    $issues[] = "Consider increasing session expiration (currently " . ($sessionConfig->expiration / 60) . " min)";
}

// Check timeToUpdate
if ($sessionConfig->timeToUpdate < 300) {
    $issues[] = "timeToUpdate is very frequent (" . $sessionConfig->timeToUpdate . "s), may cause session regeneration issues";
}

if (empty($issues)) {
    echo "✓ No major configuration issues detected\n\n";
} else {
    echo "Issues Found:\n";
    foreach ($issues as $i => $issue) {
        echo "  " . ($i + 1) . ". " . $issue . "\n";
    }
    echo "\n";
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "  DIAGNOSIS COMPLETE\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Next Steps:\n";
echo "1. Review the warnings and errors above\n";
echo "2. Fix configuration issues in app/Config/Session.php\n";
echo "3. Clean up expired sessions and stale locks\n";
echo "4. Monitor logs for session-related errors\n";
echo "5. Test with multiple users to reproduce the issue\n\n";
