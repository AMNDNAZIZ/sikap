<?php
$key = "AIzaSyDbB3Q_OjuVttV3hTQJN_u9NbOCXy7vAw8";
$url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . $key;
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
if (isset($data['models'])) {
    foreach ($data['models'] as $m) {
        if (strpos($m['name'], 'gemini') !== false && in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
            echo $m['name'] . "\n";
        }
    }
} else {
    echo "Failed: \n" . $response;
}
