<?php
// Test apakah server bisa akses GitHub raw URL
$url = 'https://raw.githubusercontent.com/Sakajaya/sikap-update/main/update_manifest.json';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
$body = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h3>Test Koneksi ke GitHub</h3>";
echo "<p><b>URL:</b> {$url}</p>";
echo "<p><b>HTTP Code:</b> {$httpCode}</p>";

if ($error) {
    echo "<p style='color:red'><b>Error:</b> {$error}</p>";
} else {
    $data = json_decode($body, true);
    echo "<p style='color:green'><b>Sukses!</b> Versi: " . ($data['version'] ?? 'N/A') . " | Files: " . ($data['total_files'] ?? 'N/A') . "</p>";
}

echo "<hr><p><b>PHP Version:</b> " . phpversion() . "</p>";
echo "<p><b>cURL Version:</b> " . (function_exists('curl_version') ? curl_version()['version'] : 'N/A') . "</p>";
echo "<p><b>allow_url_fopen:</b> " . ini_get('allow_url_fopen') . "</p>";
