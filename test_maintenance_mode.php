<?php

/**
 * Test Script: Maintenance Mode
 * 
 * Script ini untuk testing maintenance mode secara manual
 * Jalankan di browser untuk simulasi enable/disable maintenance mode
 * 
 * Usage:
 * 1. Akses: http://localhost/test_maintenance_mode.php
 * 2. Klik tombol untuk enable/disable maintenance mode
 * 3. Buka browser lain untuk test sebagai user biasa
 */

// Define paths (adjust if needed)
define('WRITEPATH', __DIR__ . '/writable/');

// Get action from query string
$action = $_GET['action'] ?? 'status';

// Function to enable maintenance mode
function enableMaintenanceMode($adminId = 1)
{
    $maintenanceFile = WRITEPATH . '.maintenance';
    $data = [
        'enabled_at' => date('Y-m-d H:i:s'),
        'reason' => 'Testing maintenance mode',
        'admin_session' => $adminId,
    ];
    file_put_contents($maintenanceFile, json_encode($data, JSON_PRETTY_PRINT));
    return true;
}

// Function to disable maintenance mode
function disableMaintenanceMode()
{
    $maintenanceFile = WRITEPATH . '.maintenance';
    if (is_file($maintenanceFile)) {
        unlink($maintenanceFile);
        return true;
    }
    return false;
}

// Function to check maintenance mode status
function getMaintenanceStatus()
{
    $maintenanceFile = WRITEPATH . '.maintenance';
    if (is_file($maintenanceFile)) {
        $data = json_decode(file_get_contents($maintenanceFile), true);
        return [
            'enabled' => true,
            'data' => $data,
        ];
    }
    return [
        'enabled' => false,
        'data' => null,
    ];
}

// Handle actions
$message = '';
$messageType = 'info';

if ($action === 'enable') {
    if (enableMaintenanceMode()) {
        $message = '✅ Maintenance mode ENABLED';
        $messageType = 'success';
    } else {
        $message = '❌ Failed to enable maintenance mode';
        $messageType = 'error';
    }
} elseif ($action === 'disable') {
    if (disableMaintenanceMode()) {
        $message = '✅ Maintenance mode DISABLED';
        $messageType = 'success';
    } else {
        $message = '⚠️ Maintenance mode was not enabled';
        $messageType = 'warning';
    }
}

// Get current status
$status = getMaintenanceStatus();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Maintenance Mode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .status-box {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        .status-enabled {
            background: #fff3cd;
            border: 2px solid #ffc107;
        }
        .status-disabled {
            background: #d1ecf1;
            border: 2px solid #17a2b8;
        }
        .status-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .status-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-top: 15px;
        }
        .status-info pre {
            margin: 0;
            font-size: 14px;
            color: #333;
        }
        .buttons {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        .btn {
            flex: 1;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-enable {
            background: #ffc107;
            color: #333;
        }
        .btn-enable:hover {
            background: #e0a800;
        }
        .btn-disable {
            background: #17a2b8;
            color: white;
        }
        .btn-disable:hover {
            background: #138496;
        }
        .btn-refresh {
            background: #6c757d;
            color: white;
        }
        .btn-refresh:hover {
            background: #5a6268;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        .message-success {
            background: #d4edda;
            color: #155724;
            border: 2px solid #c3e6cb;
        }
        .message-error {
            background: #f8d7da;
            color: #721c24;
            border: 2px solid #f5c6cb;
        }
        .message-warning {
            background: #fff3cd;
            color: #856404;
            border: 2px solid #ffeaa7;
        }
        .instructions {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 30px;
        }
        .instructions h3 {
            color: #333;
            margin-bottom: 15px;
        }
        .instructions ol {
            margin-left: 20px;
        }
        .instructions li {
            margin-bottom: 10px;
            color: #666;
        }
        .file-path {
            background: #e9ecef;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Test Maintenance Mode</h1>
        <p class="subtitle">Script untuk testing maintenance mode secara manual</p>

        <?php if ($message): ?>
            <div class="message message-<?= $messageType ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <div class="status-box <?= $status['enabled'] ? 'status-enabled' : 'status-disabled' ?>">
            <div class="status-title">
                <?php if ($status['enabled']): ?>
                    🔧 Maintenance Mode: ENABLED
                <?php else: ?>
                    ✅ Maintenance Mode: DISABLED
                <?php endif; ?>
            </div>
            
            <?php if ($status['enabled']): ?>
                <div class="status-info">
                    <strong>Maintenance Data:</strong>
                    <pre><?= json_encode($status['data'], JSON_PRETTY_PRINT) ?></pre>
                </div>
                <div style="margin-top: 15px; color: #856404;">
                    ⚠️ Saat ini user lain akan melihat halaman maintenance!
                </div>
            <?php else: ?>
                <div style="margin-top: 10px; color: #0c5460;">
                    ℹ️ Aplikasi berjalan normal. User bisa akses semua halaman.
                </div>
            <?php endif; ?>
        </div>

        <div class="buttons">
            <a href="?action=enable" class="btn btn-enable">
                🔧 Enable Maintenance
            </a>
            <a href="?action=disable" class="btn btn-disable">
                ✅ Disable Maintenance
            </a>
            <a href="?action=status" class="btn btn-refresh">
                🔄 Refresh Status
            </a>
        </div>

        <div class="instructions">
            <h3>📋 Cara Testing:</h3>
            <ol>
                <li>
                    <strong>Enable Maintenance Mode:</strong><br>
                    Klik tombol "Enable Maintenance" di atas
                </li>
                <li>
                    <strong>Test sebagai User Biasa:</strong><br>
                    Buka browser lain (atau incognito mode)<br>
                    Akses aplikasi utama: <span class="file-path">http://localhost/</span><br>
                    Expected: Muncul halaman maintenance dengan auto-refresh
                </li>
                <li>
                    <strong>Test sebagai Admin:</strong><br>
                    Login sebagai admin di browser utama<br>
                    Expected: Tetap bisa akses aplikasi normal
                </li>
                <li>
                    <strong>Disable Maintenance Mode:</strong><br>
                    Klik tombol "Disable Maintenance" di atas
                </li>
                <li>
                    <strong>Verify:</strong><br>
                    Browser user biasa akan auto-refresh dan bisa akses normal
                </li>
            </ol>

            <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px;">
                <strong>⚠️ Catatan:</strong><br>
                - File maintenance marker: <span class="file-path">writable/.maintenance</span><br>
                - Jika stuck, hapus manual file tersebut<br>
                - Auto-refresh setiap 5 detik untuk user yang terblokir
            </div>
        </div>
    </div>
</body>
</html>
