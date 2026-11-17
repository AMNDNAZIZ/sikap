<?php

/**
 * End-to-End Test Suite - Fase 1D.1 Conversational Core
 * ESPATA AI Organization (EAO)
 */

$baseUrl = 'http://localhost:8080/siakad';
$cookieFileAdmin = __DIR__ . '/cookie_admin.txt';
$cookieFileGuruByok = __DIR__ . '/cookie_guru_byok.txt';
$cookieFileGuruFallback = __DIR__ . '/cookie_guru_fallback.txt';

if (file_exists($cookieFileAdmin)) unlink($cookieFileAdmin);
if (file_exists($cookieFileGuruByok)) unlink($cookieFileGuruByok);
if (file_exists($cookieFileGuruFallback)) unlink($cookieFileGuruFallback);

function httpGet($url, $cookieFile = null) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

function httpPost($url, $data, $cookieFile = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

function extractCsrf($html) {
    if (preg_match('/<input[^>]+name="([^"]*csrf[^"]*)"[^>]+value="([^"]*)"/i', $html, $m)) {
        return ['name' => $m[1], 'hash' => $m[2]];
    }
    if (preg_match('/<input[^>]+value="([^"]*)"[^>]+name="([^"]*csrf[^"]*)"/i', $html, $m)) {
        return ['name' => $m[2], 'hash' => $m[1]];
    }
    if (preg_match('/CSRF_TOKEN_NAME\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $m1) &&
        preg_match('/CSRF_HASH\s*=\s*[\'"]([^\'"]+)[\'"]/', $html, $m2)) {
        return ['name' => $m1[1], 'hash' => $m2[1]];
    }
    return ['name' => 'csrf_test_name', 'hash' => ''];
}

function login($baseUrl, $username, $password, $cookieFile) {
    $loginPage = httpGet($baseUrl . '/login', $cookieFile);
    $csrf = extractCsrf($loginPage['body']);
    
    $postData = [
        'username' => $username,
        'password' => $password,
        $csrf['name'] => $csrf['hash'],
    ];
    $loginResp = httpPost($baseUrl . '/auth/attemptLogin', $postData, $cookieFile);
    
    // Ambil halaman /dashboard untuk mendapatkan CSRF token sesi aktif
    $dash = httpGet($baseUrl . '/dashboard', $cookieFile);
    $dashCsrf = extractCsrf($dash['body']);
    return $dashCsrf;
}

function askCopilot($baseUrl, $query, $cookieFile, &$csrf, $clientMessageId = null) {
    if (!$clientMessageId) {
        $clientMessageId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    $payload = [
        'query' => $query,
        'client_message_id' => $clientMessageId,
        $csrf['name'] => $csrf['hash'],
    ];
    
    $headers = [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest',
        'X-CSRF-TOKEN: ' . $csrf['hash'],
        'X-Client-Message-Id: ' . $clientMessageId,
    ];
    
    $resp = httpPost($baseUrl . '/api/ai-copilot/ask', json_encode($payload), $cookieFile, $headers);
    $json = json_decode($resp['body'], true);
    
    if (isset($json['csrf_hash']) && !empty($json['csrf_hash'])) {
        $csrf['hash'] = $json['csrf_hash'];
    }
    
    return [
        'http_code' => $resp['code'],
        'raw' => $resp['body'],
        'json' => $json,
        'client_message_id' => $clientMessageId,
    ];
}

echo "=== MEMULAI TEST SUITE EAO FASE 1D.1 CONVERSATIONAL CORE ===\n\n";

// 1. Login Admin
echo "[AUTH] Login Administrator (admin / admin123)...\n";
$adminCsrf = login($baseUrl, 'admin', 'admin123', $cookieFileAdmin);
echo "       CSRF Token: {$adminCsrf['name']} = " . substr($adminCsrf['hash'], 0, 16) . "...\n\n";

$results = [];

// SKENARIO 1: Sapaan Sederhana
echo "[TEST 1] Sapaan Sederhana ('Halo, selamat pagi Asis AI!')\n";
$s1 = askCopilot($baseUrl, 'Halo, selamat pagi Asis AI!', $cookieFileAdmin, $adminCsrf);
$s1_pass = ($s1['http_code'] === 200 && 
            ($s1['json']['intent_type'] ?? '') === 'GENERAL_CONVERSATION' && 
            ($s1['json']['matched_action'] ?? null) === null && 
            !empty($s1['json']['explanation']));
echo "         HTTP: {$s1['http_code']} | Intent: " . ($s1['json']['intent_type'] ?? 'null') . "\n";
echo "         Matched Action: " . json_encode($s1['json']['matched_action'] ?? null) . "\n";
echo "         Explanation: " . substr($s1['json']['explanation'] ?? '', 0, 100) . "...\n";
echo "         Status: " . ($s1_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_1'] = $s1_pass;

// SKENARIO 2: Pertanyaan Umum Pedagogik
sleep(2);
echo "[TEST 2] Pertanyaan Umum Pedagogik ('Jelaskan perbedaan asesmen formatif dan sumatif')\n";
$s2 = askCopilot($baseUrl, 'Jelaskan perbedaan asesmen formatif dan sumatif', $cookieFileAdmin, $adminCsrf);
$s2_pass = ($s2['http_code'] === 200 && 
            in_array($s2['json']['intent_type'] ?? '', ['KNOWLEDGE_RETRIEVAL', 'APPLICATION_QUESTION']) && 
            ($s2['json']['matched_action'] ?? null) === null && 
            !empty($s2['json']['explanation']));
echo "         HTTP: {$s2['http_code']} | Intent: " . ($s2['json']['intent_type'] ?? 'null') . "\n";
echo "         Matched Action: " . json_encode($s2['json']['matched_action'] ?? null) . "\n";
echo "         Explanation: " . substr($s2['json']['explanation'] ?? '', 0, 120) . "...\n";
echo "         Status: " . ($s2_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_2'] = $s2_pass;

// SKENARIO 3: Pertanyaan Dasar ESPATA
sleep(2);
echo "[TEST 3] Pertanyaan Dasar ESPATA ('Apa saja fitur yang ada di ESPATA?')\n";
$s3 = askCopilot($baseUrl, 'Apa saja fitur yang ada di ESPATA?', $cookieFileAdmin, $adminCsrf);
$s3_pass = ($s3['http_code'] === 200 && 
            !empty($s3['json']['explanation']) && 
            ($s3['json']['widget_type'] ?? '') !== 'guide_card'); // Memastikan TIDAK di-hijack guide_presensi_siswa
echo "         HTTP: {$s3['http_code']} | Intent: " . ($s3['json']['intent_type'] ?? 'null') . " | Source: " . ($s3['json']['source'] ?? 'null') . "\n";
echo "         Explanation: " . substr($s3['json']['explanation'] ?? '', 0, 120) . "...\n";
echo "         Status: " . ($s3_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_3'] = $s3_pass;

// SKENARIO 4: Permintaan Navigasi Tersedia
sleep(2);
echo "[TEST 4] Permintaan Navigasi Tersedia ('Buka menu penilaian')\n";
$s4 = askCopilot($baseUrl, 'Buka menu penilaian', $cookieFileAdmin, $adminCsrf);
$s4_action = $s4['json']['matched_action'] ?? null;
$s4_pass = ($s4['http_code'] === 200 && 
            is_array($s4_action) && 
            (strpos($s4_action['url'] ?? '', 'admin/grades') !== false || strpos($s4_action['url'] ?? '', 'admin/assessments') !== false));
echo "         HTTP: {$s4['http_code']} | Matched Action: " . ($s4_action['title'] ?? 'null') . " (" . ($s4_action['url'] ?? '') . ")\n";
echo "         Status: " . ($s4_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_4'] = $s4_pass;

// SKENARIO 5: Permintaan Navigasi Tidak Tersedia
sleep(2);
echo "[TEST 5] Permintaan Navigasi Tidak Tersedia ('Buka menu helikopter')\n";
$s5 = askCopilot($baseUrl, 'Buka menu helikopter', $cookieFileAdmin, $adminCsrf);
$s5_pass = ($s5['http_code'] === 200 && 
            ($s5['json']['matched_action'] ?? null) === null && 
            !empty($s5['json']['explanation']));
echo "         HTTP: {$s5['http_code']} | Matched Action: " . json_encode($s5['json']['matched_action'] ?? null) . "\n";
echo "         Explanation: " . substr($s5['json']['explanation'] ?? '', 0, 120) . "...\n";
echo "         Status: " . ($s5_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_5'] = $s5_pass;

// SKENARIO 6: Permintaan Bantuan Tugas
sleep(2);
echo "[TEST 6] Permintaan Bantuan Tugas ('Bantu buat 3 butir soal HOTS materi aljabar untuk kelas 7')\n";
$s6 = askCopilot($baseUrl, 'Bantu buat 3 butir soal HOTS materi aljabar untuk kelas 7', $cookieFileAdmin, $adminCsrf);
$s6_has_content = !empty($s6['json']['ai_content']) || !empty($s6['json']['explanation']);
$s6_pass = ($s6['http_code'] === 200 && 
            $s6_has_content && 
            ($s6['json']['intent_type'] ?? '') === 'TASK_ASSISTANCE');
echo "         HTTP: {$s6['http_code']} | Intent: " . ($s6['json']['intent_type'] ?? 'null') . "\n";
echo "         AI Content Length: " . strlen($s6['json']['ai_content'] ?? '') . " chars\n";
if (!empty($s6['json']['ai_content'])) {
    echo "         Snippet: " . substr(str_replace("\n", " ", $s6['json']['ai_content']), 0, 140) . "...\n";
}
echo "         Status: " . ($s6_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_6'] = $s6_pass;

// SKENARIO 7: Pertanyaan Ambigu (Klarifikasi)
sleep(2);
echo "[TEST 7] Pertanyaan Ambigu ('Buat lebih sederhana')\n";
$s7 = askCopilot($baseUrl, 'Buat lebih sederhana', $cookieFileAdmin, $adminCsrf);
$s7_pass = ($s7['http_code'] === 200 && 
            ($s7['json']['intent_type'] ?? '') === 'CLARIFICATION' && 
            !empty($s7['json']['explanation']));
echo "         HTTP: {$s7['http_code']} | Intent: " . ($s7['json']['intent_type'] ?? 'null') . "\n";
echo "         Explanation: " . substr($s7['json']['explanation'] ?? '', 0, 120) . "...\n";
echo "         Status: " . ($s7_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_7'] = $s7_pass;

// SKENARIO 8: Permintaan di Luar Kemampuan
sleep(2);
echo "[TEST 8] Permintaan di Luar Kemampuan ('Hitung pajak penghasilan saya bulan ini')\n";
$s8 = askCopilot($baseUrl, 'Hitung pajak penghasilan saya bulan ini', $cookieFileAdmin, $adminCsrf);
$s8_pass = ($s8['http_code'] === 200 && 
            ($s8['json']['intent_type'] ?? '') === 'UNSUPPORTED_REQUEST' && 
            !empty($s8['json']['explanation']));
echo "         HTTP: {$s8['http_code']} | Intent: " . ($s8['json']['intent_type'] ?? 'null') . "\n";
echo "         Explanation: " . substr($s8['json']['explanation'] ?? '', 0, 120) . "...\n";
echo "         Status: " . ($s8_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_8'] = $s8_pass;

// SKENARIO 9: Eliminasi Respons Terbuang
echo "[TEST 9] Eliminasi Pembuangan Respon AI saat matched_action = null\n";
$s9_pass = (($s1['json']['matched_action'] ?? null) === null && 
            strpos($s1['json']['explanation'] ?? '', 'Tidak ditemukan tindakan yang cocok') === false &&
            ($s2['json']['matched_action'] ?? null) === null && 
            strpos($s2['json']['explanation'] ?? '', 'Tidak ditemukan tindakan yang cocok') === false);
echo "         S1 Respon bukan fallback beku: " . ($s9_pass ? "YES" : "NO") . "\n";
echo "         Status: " . ($s9_pass ? "PASS" : "FAIL") . "\n\n";
$results['SKENARIO_9'] = $s9_pass;

// SKENARIO 10: REGRESI FASE 1C (Safe Replay, Anti-Enumeration, BYOK Bypass)
echo "[TEST 10A] Safe Replay (Idempotensi replay respon instan tanpa call ulang)...\n";
$s10a = askCopilot($baseUrl, 'Halo, selamat pagi Asis AI!', $cookieFileAdmin, $adminCsrf, $s1['client_message_id']);
$s10a_pass = ($s10a['http_code'] === 200 && ($s10a['json']['replayed'] ?? false) === true);
echo "           HTTP: {$s10a['http_code']} | Replayed: " . (($s10a['json']['replayed'] ?? false) ? "TRUE" : "FALSE") . "\n";
echo "           Status: " . ($s10a_pass ? "PASS" : "FAIL") . "\n";

echo "[TEST 10B] Anti-Enumeration 404 (Akses client_message_id milik user lain)...\n";
// Login as Guru Fallback
$guruCsrf = login($baseUrl, 'guruagamaislam', '12345678', $cookieFileGuruFallback);
// Guru mencoba mengirimkan client_message_id milik Admin
$s10b = askCopilot($baseUrl, 'Halo, selamat pagi Asis AI!', $cookieFileGuruFallback, $guruCsrf, $s1['client_message_id']);
$s10b_pass = ($s10b['http_code'] === 404 && ($s10b['json']['code'] ?? '') === 'RESOURCE_NOT_FOUND');
echo "           HTTP: {$s10b['http_code']} | Code: " . ($s10b['json']['code'] ?? 'null') . "\n";
echo "           Status: " . ($s10b_pass ? "PASS" : "FAIL") . "\n";

echo "[TEST 10C] BYOK Bypass (Guru BYOK memanggil AI tanpa menambah kuota Admin)...\n";
sleep(2);
// Ambil kuota Admin sebelum BYOK call
$db = new mysqli('127.0.0.1', 'root', '', 'siakad');
$today = date('Y-m-d');
$qBefore = 0;
$resQ = $db->query("SELECT used_count FROM ai_admin_quota_daily WHERE quota_date = '{$today}'");
if ($row = $resQ->fetch_assoc()) {
    $qBefore = (int) $row['used_count'];
}
$byokCsrf = login($baseUrl, '199212032022211010', '12345678', $cookieFileGuruByok);
$s10c = askCopilot($baseUrl, 'Halo Asis AI, apa kabar hari ini?', $cookieFileGuruByok, $byokCsrf);
$qAfter = 0;
$resQ2 = $db->query("SELECT used_count FROM ai_admin_quota_daily WHERE quota_date = '{$today}'");
if ($row = $resQ2->fetch_assoc()) {
    $qAfter = (int) $row['used_count'];
}
$s10c_pass = ($s10c['http_code'] === 200 && $qBefore === $qAfter);
echo "           Quota Before: {$qBefore} | Quota After: {$qAfter} (Diff: " . ($qAfter - $qBefore) . ")\n";
echo "           Status: " . ($s10c_pass ? "PASS (BYOK Bypassed Quota)" : "FAIL") . "\n\n";

$results['SKENARIO_10A'] = $s10a_pass;
$results['SKENARIO_10B'] = $s10b_pass;
$results['SKENARIO_10C'] = $s10c_pass;

echo "=== RINGKASAN HASIL PENGUJIAN FASE 1D.1 ===\n";
$allPass = true;
foreach ($results as $k => $v) {
    echo sprintf("%-20s : %s\n", $k, $v ? "[PASS]" : "[FAIL]");
    if (!$v) $allPass = false;
}
echo "KESIMPULAN: " . ($allPass ? "SEMUA 10 SKENARIO LULUS 100%!" : "ADA SKENARIO GAGAL!") . "\n";
exit($allPass ? 0 : 1);
