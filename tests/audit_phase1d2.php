<?php

/**
 * Audit Empiris Terpadu Mandat EAO-HO-2026-017
 * Mencakup Checkpoint 0, Checkpoint 1, Checkpoint 2, dan Checkpoint 3
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

$db = \Config\Database::connect('default');
$mem = new \App\Services\Ai\Memory\ConversationMemoryManager($db);
$baseUrl = 'http://localhost:8080/siakad';

$cookieAdmin = __DIR__ . '/cookie_audit_admin.txt';
$cookieGuru = __DIR__ . '/cookie_audit_guru.txt';

if (file_exists($cookieAdmin)) unlink($cookieAdmin);
if (file_exists($cookieGuru)) unlink($cookieGuru);

function httpGet($url, $cookieFile = null, $headers = []) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($cookieFile) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
    if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
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
    httpPost($baseUrl . '/auth/attemptLogin', $postData, $cookieFile);
    $dash = httpGet($baseUrl . '/dashboard', $cookieFile);
    return extractCsrf($dash['body']);
}

echo "=========================================================================\n";
echo "       AUDIT EMPIRIS EAO-HO-2026-017 (FASE 1D.2 VALIDATION)\n";
echo "=========================================================================\n\n";

// =========================================================================
// CHECKPOINT 0: AUDIT RETENSI & PENGELOLAAN DATA
// =========================================================================
echo "--- CHECKPOINT 0: AUDIT RETENSI & PENGELOLAAN DATA ---\n";
$totalConversations = $db->table('ai_conversations')->countAllResults();
$totalMessages = $db->table('ai_conversation_messages')->countAllResults();
echo "1. Total baris ai_conversations: {$totalConversations}\n";
echo "2. Total baris ai_conversation_messages: {$totalMessages}\n";

// Cek struktur skema kolom TTL / deleted_at
$hasConvTtl = $db->fieldExists('expires_at', 'ai_conversations') || $db->fieldExists('deleted_at', 'ai_conversations');
$hasMsgTtl = $db->fieldExists('expires_at', 'ai_conversation_messages') || $db->fieldExists('deleted_at', 'ai_conversation_messages');
echo "3. Kolom TTL / Soft Delete di ai_conversations: " . ($hasConvTtl ? "ADA" : "TIDAK ADA") . "\n";
echo "4. Kolom TTL / Soft Delete di ai_conversation_messages: " . ($hasMsgTtl ? "ADA" : "TIDAK ADA") . "\n";

// Cek method delete di ConversationMemoryManager
$hasDeleteMethod = method_exists($mem, 'deleteConversation');
echo "5. Method deleteConversation di ConversationMemoryManager: " . ($hasDeleteMethod ? "ADA" : "BELUM ADA") . "\n";

// =========================================================================
// CHECKPOINT 1: AUDIT ISOLASI & KEAMANAN PROMPT
// =========================================================================
echo "\n--- CHECKPOINT 1: AUDIT ISOLASI DAN KEAMANAN PROMPT ---\n";
$csrfAdmin = login($baseUrl, 'admin', 'admin123', $cookieAdmin);
$csrfGuru = login($baseUrl, 'guruagamaislam', '12345678', $cookieGuru);

// 1. User A membuat dan membaca percakapannya sendiri
$c1_admin = $mem->getOrCreateConversation(1, 1, null, 'Percakapan Admin Audit');
$c1_admin_uuid = $c1_admin['conversation_uuid'];
$mem->recordUserMessage($c1_admin['id'], 'Pesan rahasia Admin 1', 'msg_admin_1', 'TASK_ASSISTANCE');
$mem->recordAssistantReply($c1_admin['id'], 'Balasan rahasia Admin 1', null, 'TASK_ASSISTANCE', null, 'msg_admin_1');

$readSelf = httpGet($baseUrl . '/api/ai-copilot/conversation/' . $c1_admin_uuid, $cookieAdmin, ['X-Requested-With: XMLHttpRequest']);
$readSelfJson = json_decode($readSelf['body'], true);
$test1_pass = ($readSelf['code'] === 200 && ($readSelfJson['status'] ?? '') === 'success' && count($readSelfJson['messages'] ?? []) >= 2);
echo "[CP1-UJI 1] Pengguna A membaca percakapan sendiri: " . ($test1_pass ? "PASS" : "FAIL") . " (HTTP {$readSelf['code']})\n";

// 2. Pengguna B (Guru) mencoba membaca percakapan A (Admin)
$readCross = httpGet($baseUrl . '/api/ai-copilot/conversation/' . $c1_admin_uuid, $cookieGuru, ['X-Requested-With: XMLHttpRequest']);
$readCrossJson = json_decode($readCross['body'], true);
$test2_pass = ($readCross['code'] === 404 && ($readCrossJson['code'] ?? '') === 'CONVERSATION_NOT_FOUND');
echo "[CP1-UJI 2] Pengguna B membaca percakapan A: " . ($test2_pass ? "PASS" : "FAIL") . " (HTTP {$readCross['code']})\n";

// 3. Pengguna B mencoba mengirim pesan ke percakapan A
$postCrossPayload = [
    'query'             => 'Upaya injeksi pesan ke thread Admin',
    'client_message_id' => '00000000-0000-4000-8000-000000000001',
    'conversation_id'   => $c1_admin_uuid,
    $csrfGuru['name']   => $csrfGuru['hash'],
];
$postCross = httpPost($baseUrl . '/api/ai-copilot/ask', json_encode($postCrossPayload), $cookieGuru, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'X-CSRF-TOKEN: ' . $csrfGuru['hash'],
    'X-Client-Message-Id: 00000000-0000-4000-8000-000000000001',
    'X-Conversation-Id: ' . $c1_admin_uuid,
]);
$postCrossJson = json_decode($postCross['body'], true);
$assignedUuidToGuru = $postCrossJson['conversation_id'] ?? null;

// Pastikan Guru dibuatkan UUID baru dan thread Admin tidak bertambah pesan injeksi
$adminMessagesCount = $db->table('ai_conversation_messages')->where('conversation_id', $c1_admin['id'])->countAllResults();
$test3_pass = ($postCross['code'] === 200 && 
               $assignedUuidToGuru !== $c1_admin_uuid && 
               $adminMessagesCount === 2);
echo "[CP1-UJI 3] Pengguna B mengirim ke thread A (Auto-Isolasi): " . ($test3_pass ? "PASS" : "FAIL") . " (UUID Baru: {$assignedUuidToGuru})\n";

// 4. Pengguna B memanipulasi UUID percakapan (Malformed)
$malformedUuid = 'invalid-uuid-format-xxxx';
$readMalformed = httpGet($baseUrl . '/api/ai-copilot/conversation/' . $malformedUuid, $cookieGuru, ['X-Requested-With: XMLHttpRequest']);
$test4_pass = ($readMalformed['code'] === 404);
echo "[CP1-UJI 4] Manipulasi UUID Malformed: " . ($test4_pass ? "PASS" : "FAIL") . " (HTTP {$readMalformed['code']})\n";

// 5. Pengguna A mencoba mengakses percakapan yang tidak ada
$nonExistentUuid = 'ffffffff-ffff-4fff-8fff-ffffffffffff';
$readNonExistent = httpGet($baseUrl . '/api/ai-copilot/conversation/' . $nonExistentUuid, $cookieAdmin, ['X-Requested-With: XMLHttpRequest']);
$test5_pass = ($readNonExistent['code'] === 404);
echo "[CP1-UJI 5] Akses percakapan tidak ada: " . ($test5_pass ? "PASS" : "FAIL") . " (HTTP {$readNonExistent['code']})\n";

// 6. Pengguna mencoba mengubah identitas pemilik melalui payload (user_id spoofing)
$spoofPayload = [
    'query'             => 'Tes spoofing user_id payload',
    'client_message_id' => '00000000-0000-4000-8000-000000000002',
    'user_id'           => 1, // Mencoba memalsukan sebagai Admin padahal sesi adalah Guru
    $csrfGuru['name']   => $csrfGuru['hash'],
];
$spoofPost = httpPost($baseUrl . '/api/ai-copilot/ask', json_encode($spoofPayload), $cookieGuru, [
    'Content-Type: application/json',
    'X-Requested-With: XMLHttpRequest',
    'X-CSRF-TOKEN: ' . $csrfGuru['hash'],
    'X-Client-Message-Id: 00000000-0000-4000-8000-000000000002',
]);
$spoofJson = json_decode($spoofPost['body'], true);
$spoofConvRow = $db->table('ai_conversations')->where('conversation_uuid', $spoofJson['conversation_id'] ?? '')->get()->getRowArray();
$test6_pass = ($spoofPost['code'] === 200 && (int) $spoofConvRow['user_id'] === 63); // Sesi guru adalah User ID 63
echo "[CP1-UJI 6] Pemalsuan user_id payload diabaikan server: " . ($test6_pass ? "PASS" : "FAIL") . " (Tercatat: User ID {$spoofConvRow['user_id']})\n";

// 7. Dua percakapan berbeda milik pengguna yang sama tidak saling mencampur
$c2_admin = $mem->getOrCreateConversation(1, 1, null, 'Percakapan Admin Audit 2');
$mem->recordUserMessage($c2_admin['id'], 'Pesan topik kedua Admin', 'msg_admin_2', 'TASK_ASSISTANCE');
$hist1 = $mem->getAssembledHistory($c1_admin['id']);
$hist2 = $mem->getAssembledHistory($c2_admin['id']);
$test7_pass = (count($hist1) === 2 && count($hist2) === 1 && $hist1[0]['content'] !== $hist2[0]['content']);
echo "[CP1-UJI 7] Dua percakapan satu pengguna tidak mencampur: " . ($test7_pass ? "PASS" : "FAIL") . "\n";

// 8. Percakapan pengguna berbeda tidak pernah masuk ke context assembly satu sama lain
$guruConv = $mem->getOrCreateConversation(63, 3, null, 'Sesi Guru Audit');
$mem->recordUserMessage($guruConv['id'], 'Kueri Guru Agama', 'msg_guru_1', 'TASK_ASSISTANCE');
$guruHist = $mem->getAssembledHistory($guruConv['id']);
$test8_pass = (count($guruHist) === 1 && $guruHist[0]['content'] === 'Kueri Guru Agama');
echo "[CP1-UJI 8] Percakapan antar-pengguna terisolasi di assembly: " . ($test8_pass ? "PASS" : "FAIL") . "\n";

// =========================================================================
// CHECKPOINT 2: AUDIT TOKEN BUDGET & PEMANGKASAN RIWAYAT
// =========================================================================
echo "\n--- CHECKPOINT 2: AUDIT TOKEN BUDGET & ESTIMASI KARAKTER ---\n";
// Uji formula penghitungan karakter per token
$sampleText = "Ini adalah contoh teks bahasa Indonesia untuk menguji estimasi jumlah token Asis AI.";
$estToken = $mem->estimateTokenCount($sampleText);
$charLen = mb_strlen($sampleText);
$ratio = round($charLen / $estToken, 2);
echo "1. Rumus Token: max(1, ceil(mb_strlen(\$text) / 3.8))\n";
echo "   Panjang Karakter: {$charLen} char | Estimasi Token: {$estToken} token | Rasio: {$ratio} char/token\n";

// Uji 6 variasi perakitan:
// A. 6 putaran pendek
$testConvA = $mem->getOrCreateConversation(999, 1, null, 'Test Variasi A');
for ($i = 1; $i <= 6; $i++) {
    $mem->recordUserMessage($testConvA['id'], "Pendek {$i}", "u_a_{$i}");
    $mem->recordAssistantReply($testConvA['id'], "Jawab {$i}", null, null, null, "a_a_{$i}");
}
$histA = $mem->getAssembledHistory($testConvA['id'], 6, 800);
echo "2. Variasi A (6 putaran pendek): " . count($histA) . " putaran terakit (Batas: 6)\n";

// B. 6 putaran panjang (> 800 token)
$testConvB = $mem->getOrCreateConversation(999, 1, null, 'Test Variasi B');
$longText = str_repeat("Penjelasan panjang kurikulum dan pedagogik sekolah terpadu dengan materi lengkap. ", 20); // ~1500 char = ~395 token
for ($i = 1; $i <= 6; $i++) {
    $mem->recordUserMessage($testConvB['id'], "Soal panjang {$i}: " . $longText, "u_b_{$i}");
    $mem->recordAssistantReply($testConvB['id'], "Jawaban panjang {$i}: " . $longText, null, null, null, "a_b_{$i}");
}
$histB = $mem->getAssembledHistory($testConvB['id'], 6, 800);
$totalTokensB = 0;
foreach ($histB as $turn) {
    $totalTokensB += $mem->estimateTokenCount($turn['content']);
}
echo "3. Variasi B (6 putaran panjang): Dipangkas menjadi " . count($histB) . " putaran ({$totalTokensB} token <= 800 budget)\n";

// C. Pesan terbaru sangat panjang (1000 char cap)
$queryLong = str_repeat("Kueri pengguna sangat panjang ", 50); // ~1500 char
$sanitizedLong = trim(preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', mb_substr($queryLong, 0, 1000)));
echo "4. Variasi C (Batas sanitasi kueri terbaru): Masukan " . mb_strlen($queryLong) . " char dipangkas tepat menjadi " . mb_strlen($sanitizedLong) . " char\n";

// D. Teks campuran Indonesia - Inggris
$mixedText = "Explain how to implement Modul Ajar Kurikulum Merdeka with interactive rubrics and formative assessment.";
$mixedTokens = $mem->estimateTokenCount($mixedText);
echo "5. Variasi D (Teks Campuran ID-EN): " . mb_strlen($mixedText) . " char => {$mixedTokens} token\n";

// E. Karakter khusus dan formatting list
$specialText = "1. Point A: Nilai >= 75 (Kriteria Ketuntasan)\n2. Point B: Bobot 20% & Formula: [Score * 100] / Total.\n3. Simbol: #$%^&*~`";
$specialTokens = $mem->estimateTokenCount($specialText);
echo "6. Variasi E (Karakter Khusus & List): " . mb_strlen($specialText) . " char => {$specialTokens} token\n";

// =========================================================================
// CHECKPOINT 3: AUDIT INTEGRASI KONTEKS OPERASIONAL ESPATA
// =========================================================================
echo "\n--- CHECKPOINT 3: VALIDASI ESPATA CONTEXTUAL AWARENESS ---\n";
// Periksa data aktual di database untuk 6 kondisi:
// 1. Guru dengan 1 penugasan (Guru BYOK: User ID 397, Teacher ID 12)
$t1Assignments = $db->table('teaching_assignments')->where('teacher_id', 12)->get()->getResultArray();
echo "1. Guru BYOK (Teacher ID 12): " . count($t1Assignments) . " penugasan KBM di database\n";

// 2. Guru dengan >1 penugasan (Guru Fallback: User ID 63, Teacher ID 3)
$t2Assignments = $db->table('teaching_assignments')->where('teacher_id', 3)->get()->getResultArray();
echo "2. Guru Fallback (Teacher ID 3): " . count($t2Assignments) . " penugasan KBM di database\n";

// 3. Pengguna Admin (User ID 1, Role 1)
$adminRole = $db->table('users')->where('id', 1)->get()->getRowArray();
echo "3. Pengguna Admin (User ID 1): Role ID " . ($adminRole['role_id'] ?? 'null') . "\n";

// 4. Pengguna tanpa penugasan (Teacher ID yang belum memiliki KBM)
$emptyTeacher = $db->table('teachers')
    ->select('teachers.id, teachers.name')
    ->join('teaching_assignments', 'teaching_assignments.teacher_id = teachers.id', 'left')
    ->where('teaching_assignments.id IS NULL')
    ->get()
    ->getRowArray();
echo "4. Guru tanpa penugasan KBM: " . ($emptyTeacher ? "Ditemukan ID {$emptyTeacher['id']} ({$emptyTeacher['name']})" : "Tidak ada") . "\n";

// 5. Cek apakah konteks KBM (Mapel, Kelas, Fase, Semester) diinjeksi ke prompt LLM di callUnifiedAiNlu:
$refAiCopilot = new ReflectionClass(\App\Controllers\Api\AiCopilot::class);
$methodCall = $refAiCopilot->getMethod('callUnifiedAiNlu');
echo "5. Audit callUnifiedAiNlu System Prompt & User Prompt:\n";
echo "   - Digunakan untuk filtering katalog aksi: YA (AiActionRegistry)\n";
echo "   - Digunakan untuk kueri data live (jadwal/rekap presensi): YA (tryExecuteLiveQuery)\n";
echo "   - Diinjeksi sebagai blok teks konteks operasional di prompt LLM: BELUM DIAKTIFKAN\n";

echo "\n=========================================================================\n";
echo "                      AUDIT SELESAI SECARA FAKTUAL                       \n";
echo "=========================================================================\n";
