<?php
/**
 * 🔧 TOOL: Clear Session Lock
 * 
 * Tool untuk membersihkan session lock dari user yang terkunci
 * Gunakan ketika user tidak bisa login karena "sedang login di perangkat lain"
 * 
 * CARA PAKAI:
 * 1. Via Browser: http://localhost:8080/cbtku/clear_session_lock.php?username=admin
 * 2. Via CLI: php clear_session_lock.php admin
 * 
 * KEAMANAN:
 * - Tool ini hanya bisa diakses dari localhost
 * - Tidak bisa diakses dari IP eksternal
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

// Get username from CLI or GET parameter
$username = null;
if (php_sapi_name() === 'cli') {
    // CLI mode
    $username = $argv[1] ?? null;
} else {
    // Web mode
    $username = $_GET['username'] ?? null;
}

// Validate input
if (empty($username)) {
    echo "❌ Error: Username tidak boleh kosong\n\n";
    echo "CARA PAKAI:\n";
    echo "1. Via Browser: http://localhost:8080/cbtku/clear_session_lock.php?username=admin\n";
    echo "2. Via CLI: php clear_session_lock.php admin\n";
    exit(1);
}

// Find user
$user = $db->table('users')
    ->where('username', $username)
    ->get()
    ->getRowArray();

if (!$user) {
    echo "❌ Error: User '{$username}' tidak ditemukan\n";
    exit(1);
}

// Check if user has session lock
if (empty($user['active_session_id']) && empty($user['last_activity'])) {
    echo "✅ User '{$username}' tidak memiliki session lock\n";
    echo "User sudah bisa login tanpa masalah.\n";
    exit(0);
}

// Show current status
echo "📊 STATUS SEBELUM:\n";
echo "   Username: {$user['username']}\n";
echo "   Fullname: {$user['fullname']}\n";
echo "   Role: " . getRoleName($user['role_id']) . "\n";
echo "   Active Session ID: " . ($user['active_session_id'] ?? 'null') . "\n";
echo "   Last Activity: " . ($user['last_activity'] ?? 'null') . "\n";
echo "\n";

// Clear session lock
$result = $db->table('users')
    ->where('id', $user['id'])
    ->update([
        'active_session_id' => null,
        'last_activity' => null
    ]);

if ($result) {
    echo "✅ SUCCESS: Session lock berhasil dihapus!\n";
    echo "\n";
    echo "📊 STATUS SESUDAH:\n";
    echo "   Active Session ID: null\n";
    echo "   Last Activity: null\n";
    echo "\n";
    echo "User '{$username}' sekarang bisa login kembali.\n";
} else {
    echo "❌ ERROR: Gagal menghapus session lock\n";
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
