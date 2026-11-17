<?php

/**
 * Evaluasi Kualitas Jawaban Asis AI Terhadap 10 Pertanyaan Nyata
 * Mandat EAO-HO-2026-020 (Checkpoint 6)
 */

$baseUrl = 'http://localhost:8080/siakad';
$cookieFile = __DIR__ . '/cookie_eval_quality.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

function httpGet($url, $cookieFile = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
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

echo "=== LOGIN GURU (guruagamaislam / 12345678) ===\n";
$loginPage = httpGet($baseUrl . '/login', $cookieFile);
$csrf = extractCsrf($loginPage['body']);
$postData = [
    'username' => 'guruagamaislam',
    'password' => '12345678',
    $csrf['name'] => $csrf['hash'],
];
httpPost($baseUrl . '/auth/attemptLogin', $postData, $cookieFile);
$dash = httpGet($baseUrl . '/dashboard', $cookieFile);
$csrf = extractCsrf($dash['body']);
echo "Login OK. CSRF Token: {$csrf['name']} = " . substr($csrf['hash'], 0, 16) . "...\n\n";

$questions = [
    1 => "Buatkan 10 soal pilihan ganda tentang pecahan.",
    2 => "Buatkan soal untuk kelas saya.",
    3 => "Ubah menjadi lebih sulit.",
    4 => "Sekarang buat materi Bahasa Indonesia.",
    5 => "Buatkan tujuan pembelajaran untuk materi tersebut.",
    6 => "Saya ingin membuat asesmen formatif.",
    7 => "Apa fase kurikulum untuk kelas yang saya ajar?",
    8 => "Bantu saya membuat modul ajar.",
    9 => "Buatkan kegiatan pembelajaran yang sesuai dengan kelas saya.",
    10 => "Apa yang sedang saya ajarkan?",
];

$conversationId = null;
$evaluations = [];

foreach ($questions as $num => $q) {
    echo "---------------------------------------------------------------------\n";
    echo "[PERTANYAAN {$num}] \"{$q}\"\n";
    
    $clientMessageId = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $payload = [
        'query'             => $q,
        'client_message_id' => $clientMessageId,
        'conversation_id'   => $conversationId,
        $csrf['name']       => $csrf['hash'],
    ];

    $headers = [
        'Content-Type: application/json',
        'X-Requested-With: XMLHttpRequest',
        'X-CSRF-TOKEN: ' . $csrf['hash'],
        'X-Client-Message-Id: ' . $clientMessageId,
    ];
    if ($conversationId) {
        $headers[] = 'X-Conversation-Id: ' . $conversationId;
    }

    $resp = httpPost($baseUrl . '/api/ai-copilot/ask', json_encode($payload), $cookieFile, $headers);
    if ($resp['code'] === 429) {
        echo "Rate limit 429 hit, waiting 3s...\n";
        sleep(3);
        $resp = httpPost($baseUrl . '/api/ai-copilot/ask', json_encode($payload), $cookieFile, $headers);
    }

    $json = json_decode($resp['body'], true);
    if (isset($json['csrf_hash'])) {
        $csrf['hash'] = $json['csrf_hash'];
    }
    if (isset($json['conversation_id'])) {
        $conversationId = $json['conversation_id'];
    }

    $explanation = $json['explanation'] ?? ($json['message'] ?? 'Empty response');
    $hasAiContent = !empty($json['ai_content']);
    $aiContentSnippet = $hasAiContent ? mb_substr(strip_tags((string)$json['ai_content']), 0, 150) . '...' : 'null';
    $matchedAction = $json['matched_action']['title'] ?? 'null';
    $intent = $json['intent_type'] ?? 'null';
    $contextStatus = $json['context_status'] ?? 'null';

    echo "HTTP Status      : {$resp['code']}\n";
    echo "Intent Type      : {$intent}\n";
    echo "Context Status   : {$contextStatus}\n";
    echo "Explanation      : " . mb_substr($explanation, 0, 180) . "...\n";
    echo "Has AI Content   : " . ($hasAiContent ? 'YES (' . strlen((string)$json['ai_content']) . ' chars)' : 'NO') . "\n";
    echo "Matched Action   : {$matchedAction}\n";

    $evaluations[$num] = [
        'question'       => $q,
        'http_code'      => $resp['code'],
        'intent'         => $intent,
        'context_status' => $contextStatus,
        'explanation'    => $explanation,
        'has_ai_content' => $hasAiContent,
        'ai_content_len' => strlen((string)($json['ai_content'] ?? '')),
        'matched_action' => $matchedAction,
    ];

    // Jeda 1.5 detik antar-pemanggilan agar tidak menabrak Groq TPM
    sleep(2);
}

if (file_exists($cookieFile)) unlink($cookieFile);

echo "\n=====================================================================\n";
echo "HASIL KESELURUHAN EVALUASI 10 PERTANYAAN NYATA:\n";
echo "Total Pertanyaan: " . count($evaluations) . " | Berhasil Diproses: " . count(array_filter($evaluations, fn($e) => $e['http_code'] === 200)) . "\n";
echo "=====================================================================\n";
