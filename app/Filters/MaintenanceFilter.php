<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Maintenance Mode Filter
 * 
 * Blocks all requests during system update/maintenance
 * Except for admin who initiated the update
 */
class MaintenanceFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $maintenanceFile = WRITEPATH . '.maintenance';

        // Check if maintenance mode is active
        if (!is_file($maintenanceFile)) {
            return; // Not in maintenance mode
        }

        // Read maintenance data
        $data = json_decode(file_get_contents($maintenanceFile), true);

        // Allow admin who initiated the update
        // Gunakan session_status() untuk cek apakah session sudah aktif sebelum memanggil session()
        // Ini mencegah konflik dengan CSRF filter yang juga menginisialisasi session
        $currentUserId = null;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $currentUserId = session()->get('user_id');
        }
        if ($currentUserId && isset($data['admin_session']) && $currentUserId == $data['admin_session']) {
            return; // Allow admin to continue
        }

        // Allow access to maintenance page itself and critical pages
        $path = $request->getUri()->getPath();
        $rawUri = $_SERVER['REQUEST_URI'] ?? '';
        $skip = ['maintenance', 'activate', 'login', 'assets', 'favicon.ico'];
        foreach ($skip as $s) {
            if (strpos($path, $s) !== false || strpos($rawUri, $s) !== false) {
                return;
            }
        }

        // Block all other requests with maintenance page
        return $this->showMaintenancePage($data);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }

    /**
     * Show maintenance page
     */
    private function showMaintenancePage($data)
    {
        $html = '<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance Mode</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            text-align: center;
        }
        .icon {
            font-size: 80px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        h1 {
            color: #333;
            font-size: 28px;
            margin-bottom: 15px;
        }
        p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 10px;
        }
        .time {
            color: #999;
            font-size: 14px;
            margin-top: 20px;
        }
        .spinner {
            margin: 30px auto;
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .refresh-btn {
            margin-top: 20px;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s;
        }
        .refresh-btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">🔧</div>
        <h1>Sistem Sedang Maintenance</h1>
        <p>Kami sedang melakukan pembaruan sistem untuk meningkatkan performa dan fitur aplikasi.</p>
        <p><strong>Mohon tunggu beberapa saat...</strong></p>
        
        <div class="spinner"></div>
        
        <button class="refresh-btn" onclick="location.reload()">
            🔄 Coba Lagi
        </button>
        
        <div class="time">
            Dimulai: ' . ($data['enabled_at'] ?? 'Unknown') . '
        </div>
    </div>
    
    <script>
        // Auto-refresh every 5 seconds
        setTimeout(function() {
            location.reload();
        }, 5000);
    </script>
</body>
</html>';

        return service('response')
            ->setStatusCode(503)
            ->setBody($html);
    }
}
