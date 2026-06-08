<?php
/**
 * Diagnostic Tool - Cek Masalah Aplikasi
 * Akses: http://namadomain.com/diagnostic.php
 * 
 * PENTING: Hapus file ini setelah troubleshooting selesai!
 */

// Security: Only allow from localhost or specific IP
$allowedIPs = ['127.0.0.1', '::1', 'localhost'];
$clientIP = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

// Uncomment line below to allow access from any IP (TEMPORARY ONLY!)
// $allowedIPs[] = $clientIP;

if (!in_array($clientIP, $allowedIPs)) {
    die('Access denied. This tool can only be accessed from localhost or allowed IPs.');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Tool - SIKAP</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 16px r