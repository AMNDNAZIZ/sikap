<?php
/**
 * 🔧 TOOL: Clear All Session Locks
 * 
 * Tool untuk membersihkan session lock dari SEMUA user
 * Gunakan ketika banyak user terkena session lock false positive
 * 
 * CARA PAKAI:
 * 1. Via Browser: http://localhost:8080/cbtku/clear_all_session_locks.php
 * 2. Via CLI: php clear_all_session_locks.php
 * 
 * KEAMANAN:
 * - Tool ini hanya bisa diakses dari localhost
 * - Tidak bisa diakses dari IP eksternal
 * 
 * PERINGATAN:
 * - Ini akan clear session lock SEMUA user
 * - User yang sedang login di device lain akan bisa login lagi
 * - Gunakan dengan hati-hati!
 */

// 🔒 SECURITY: Only allow from localhost
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

if (!in_array($clientIP, $allowedIPs) && php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("❌ Access Denied: This tool can only be accessed from localhost");
}

// Load CodeIgniter
require_once __DIR__ . '/app/Config/Paths.php';
$paths = new Config\Paths();
require_once $paths->systemDirectory . '/bootstrap.php';

// Get database connection
$db = \Config\Database::connect();

echo "═══════════════════════════════════════════════════════════════\n";
echo "  CLEAR ALL SESSION LOCKS\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Get count of users with session lock
$count = $db->table('users')
    ->where('active_session_id IS NOT NULL')
    ->countAllResults();

if ($count === 0) {
    echo "✅ No users have session locks.\n";
    echo "All users are already clear.\n";
    exit(0);
}

echo "📊 Found {$count} user(s) with session locks.\n\n";

// Show users with session lock
$users = $db->table('users')
    ->select('id, username, fullname, role_id, active_session_id, last_activity')
    ->where('active_session_id IS NOT NULL')
    ->get()
    ->getResultArray();

echo "Users with session locks:\n";
echo str_repeat("-", 80) . "\n";
printf("%-5s %-20s %-30s %-10s %-15s\n", "ID", "Username", "Fullname", "Role", "Last Activity");
echo str_repeat("-", 80) . "\n";

foreach ($users as $user) {
    $roleName = getRoleName($user['role_id']);
    $lastActivity = $user['last_activity'] ?? 'null';
    printf("%-5s %-20s %-30s %-10s %-15s\n", 
        $user['id'], 
        substr($user['username'], 0, 20), 
        substr($user['fullname'], 0, 30), 
        $roleName,
        $lastActivity
    );
}

echo str_repeat("-", 80) . "\n\n";

// Ask for confirmation (only in CLI mode)
if (php_sapi_name() === 'cli') {
    echo "⚠️  WARNING: This will clear session locks for ALL {$count} user(s).\n";
    echo "Are you sure you want to continue? (yes/no): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    $confirmation = trim(strtolower($line));
    fclose($handle);
    
    if ($confirmation !== 'yes' && $confirmation !== 'y') {
        echo "\n❌ Operation cancelled.\n";
        exit(0);
    }
    echo "\n";
}

// Clear all session locks
$result = $db->table('users')
    ->where('active_session_id IS NOT NULL')
    ->update([
        'active_session_id' => null,
        'last_activity' => null
    ]);

if ($result) {
    echo "✅ SUCCESS: Cleared session locks for {$count} user(s)!\n\n";
    echo "📊 SUMMARY:\n";
    echo "   - Users affected: {$count}\n";
    echo "   - Active session IDs: cleared\n";
    echo "   - Last activity: cleared\n\n";
    echo "All users can now login without session lock errors.\n";
} else {
    echo "❌ ERROR: Failed to clear session locks\n";
    exit(1);
}

// Helper function
function getRoleName($roleId) {
    $roles = [
        1 => 'Admin',
        2 => 'Guru',
        3 => 'Siswa'
    ];
    return $roles[$roleId] ?? 'Unknown';
}
