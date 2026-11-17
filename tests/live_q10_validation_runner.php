<?php

/**
 * Controlled Live Validation Runner for Q10 Prompt Anchor
 * Mandat EAO-HO-2026-035
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Controllers\Api\AiCopilot;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\OperationalContextDTO;
use App\Services\Ai\Intent\IntentClassifier;
use App\Services\Ai\OperationalContextProvider;
use App\Services\Ai\UnifiedAiService;
use Config\AiConfig;
use Config\Database;

echo "====================================================================\n";
echo "   CONTROLLED LIVE VALIDATION: Q10 PROMPT ANCHOR (EAO-HO-2026-035)\n";
echo "====================================================================\n\n";

$db = Database::connect('default');
$config = new AiConfig();
$resolver = new AiKeyResolver($config, $db);

// -------------------------------------------------------------------------
// CP0 — PREFLIGHT CHECKS
// -------------------------------------------------------------------------
echo "[PREFLIGHT 1] Verifikasi Lisensi (SecurityGuard)...\n";
require_once __DIR__ . '/../app/Libraries/SecurityGuard.php';
$secPass = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "              SEC_PASS = " . ($secPass ? '1' : '0') . "\n";
if (!$secPass) {
    echo "[ABORT] SEC_PASS bukan 1. Pengujian dibatalkan demi keamanan lisensi.\n";
    exit(1);
}

echo "[PREFLIGHT 2] Verifikasi Identitas Akun Guru ID 2...\n";
$user = $db->table('users')->where('id', 2)->get()->getRowArray();
$teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);
echo "              User ID: {$user['id']} | Username: {$user['username']} | Role: {$user['role_id']} | Teacher ID: {$teacherId}\n";

$teacher = $db->table('teachers')->where('id', $teacherId)->get()->getRowArray();
$teacherName = $teacher['nama'] ?? $teacher['nama_guru'] ?? 'Guru';
echo "              Nama Guru: {$teacherName}\n";

echo "[PREFLIGHT 3] Verifikasi Kunci BYOK Guru ID 2 via Resolver...\n";
$resolved = $resolver->resolve(2, (int) $user['role_id'], $teacherId, 'groq');
$telemetryTag = $resolved->getTelemetrySourceTag();
echo "              Telemetry Tag: {$telemetryTag}\n";
echo "              Key Source: {$resolved->keySource} | Owner ID: " . ($resolved->keyOwnerId ?? 'null') . "\n";
echo "              Is Fallback: " . ($resolved->isFallback ? 'YES' : 'NO') . "\n";

// Syarat Mutlak Mandat: Jika resolver tidak menghasilkan groq:self:2, SEGERA HENTIKAN PENGUJIAN!
if ($telemetryTag !== 'groq:self:2' || $resolved->keySource !== 'self' || $resolved->keyOwnerId !== 2 || $resolved->isFallback) {
    echo "[ABORT] Resolver TIDAK menghasilkan groq:self:2. Pengujian dibatalkan.\n";
    exit(1);
}
echo "              -> Kepatuhan Identitas BYOK: SAH (groq:self:2)\n";

echo "[PREFLIGHT 4] Catat Kuota Admin Sebelum Pengujian...\n";
$today = date('Y-m-d');
$adminQuotaBefore = $db->table('ai_admin_quota_daily')->where('quota_date', $today)->get()->getRowArray();
$usedBefore = (int) ($adminQuotaBefore['used_count'] ?? 0);
$limitCount = (int) ($adminQuotaBefore['limit_count'] ?? 20);
echo "              Kuota Admin ({$today}): {$usedBefore} / {$limitCount}\n";

echo "[PREFLIGHT 5] Identifikasi Percakapan Benchmark Sebelumnya...\n";
$conv = $db->table('ai_conversations')
    ->where('id', 80)
    ->where('user_id', 2)
    ->get()
    ->getRowArray();

if (!$conv) {
    echo "[ABORT] Percakapan benchmark ID 80 milik User ID 2 tidak ditemukan.\n";
    exit(1);
}
$conversationUuid = $conv['conversation_uuid'];
$maskedUuid = substr($conversationUuid, 0, 8) . '-****-****-****-' . substr($conversationUuid, -12);
echo "              Conversation ID 80 ditemukan | UUID: {$maskedUuid}\n";
echo "              Title: {$conv['title']}\n";

// Ambil riwayat percakapan 6 putaran terakhir (12 pesan)
$messages = $db->table('ai_conversation_messages')
    ->where('conversation_id', 80)
    ->orderBy('id', 'ASC')
    ->get()
    ->getResultArray();

$assembledHistory = [];
// Ambil maksimal 6 pesan terakhir sebelum Q10 untuk konteks
$recentMsgs = array_slice($messages, -6);
foreach ($recentMsgs as $m) {
    $assembledHistory[] = [
        'role'    => ($m['sender_role'] === 'user') ? 'user' : 'assistant',
        'content' => $m['message_text'],
    ];
}
echo "              Riwayat Terakit: " . count($assembledHistory) . " pesan\n\n";

// -------------------------------------------------------------------------
// SETUP TEST RUNNER WRAPPER (TELEMETRY CAPTURE TANPA MODIFIKASI KODE PRODUKSI)
// -------------------------------------------------------------------------
class TelemetryCapturingAiService extends UnifiedAiService {
    public ?AiResponse $lastExecutionResponse = null;
    public ?AiRequest $lastExecutionRequest = null;

    public function executeJson(AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        $this->lastExecutionRequest = $request;
        $response = parent::executeJson($request, $preferredProvider, $userContext);
        $this->lastExecutionResponse = $response;
        return $response;
    }
}

$capturingAiService = new TelemetryCapturingAiService($config, null, null, $db, $resolver);
$copilot = new AiCopilot();
$copilot->setAiService($capturingAiService);

$ref = new ReflectionClass(AiCopilot::class);
$callMethod = $ref->getMethod('callUnifiedAiNlu');
$callMethod->setAccessible(true);

$contextProvider = $copilot->getContextProvider();

// Sediakan daftar aksi standar guru
$allowedActions = [
    [
        'id'          => 'menu_dashboard',
        'title'       => 'Dashboard Guru',
        'category'    => 'Menu & Navigasi',
        'url'         => 'http://localhost:8080/siakad/dashboard',
        'icon'        => 'bi bi-speedometer2',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_assessments',
        'title'       => 'Asesmen & Penilaian',
        'category'    => 'Menu & Navigasi',
        'url'         => 'http://localhost:8080/siakad/admin/assessments',
        'icon'        => 'bi bi-journal-check',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_modul_ajar',
        'title'       => 'Modul Ajar',
        'category'    => 'Menu & Navigasi',
        'url'         => 'http://localhost:8080/siakad/admin/modul-ajar',
        'icon'        => 'bi bi-file-earmark-text',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_presensi',
        'title'       => 'Presensi Siswa',
        'category'    => 'Menu & Navigasi',
        'url'         => 'http://localhost:8080/siakad/admin/attendance',
        'icon'        => 'bi bi-calendar-check',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_jadwal',
        'title'       => 'Jadwal Mengajar',
        'category'    => 'Menu & Navigasi',
        'url'         => 'http://localhost:8080/siakad/admin/schedule',
        'icon'        => 'bi bi-clock',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
];

$q10Query = "Apa yang sedang saya ajarkan?";

// Resolusi Konteks Operasional Guru ID 2
$opContext = $contextProvider->resolveContext($user, $q10Query, $assembledHistory);
echo "Konteks Operasional Guru:\n";
echo "  Is Teacher: " . ($opContext->isTeacher ? 'YES' : 'NO') . "\n";
echo "  Active Assignment: " . json_encode($opContext->activeAssignment) . "\n";
echo "  Is Ambiguous: " . ($opContext->isAmbiguous ? 'YES' : 'NO') . "\n\n";

// -------------------------------------------------------------------------
// CP1 & CP2 — 3 CONTROLLED LIVE TRIALS
// -------------------------------------------------------------------------
$results = [];

for ($trial = 1; $trial <= 3; $trial++) {
    echo "=====================================================================\n";
    $timestamp = date('Y-m-d H:i:s');
    echo "[PERCOBAAN {$trial}/3] Waktu: {$timestamp}\n";
    echo "                      Kueri: \"{$q10Query}\"\n";
    echo "                      Provider: groq | Model: openai/gpt-oss-20b\n";
    echo "                      Tag Kunci: groq:self:2\n";
    echo "---------------------------------------------------------------------\n";

    $clientMessageId = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $userContext = [
        'user_id'           => 2,
        'role_id'           => 3,
        'teacher_id'        => 1,
        'school_name'       => 'SD SAKASALIKA',
        'client_message_id' => $clientMessageId,
    ];

    $startTime = microtime(true);

    try {
        $nluResult = $callMethod->invoke(
            $copilot,
            $q10Query,
            $allowedActions,
            $user,
            $userContext,
            IntentClassifier::INTENT_GENERAL_CONVERSATION,
            $assembledHistory,
            $conversationUuid,
            $opContext
        );
    } catch (\Throwable $e) {
        $nluResult = ['success' => false, 'error' => $e->getMessage()];
    }

    $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
    $capturedResp = $capturingAiService->lastExecutionResponse;

    // Klasifikasi Hasil
    $classification = 'UNKNOWN';
    $isSuccess = $nluResult['success'] ?? false;
    $rawErrorCode = $capturedResp ? $capturedResp->errorCode : null;
    $rawErrorMsg = $capturedResp ? $capturedResp->errorMessage : null;

    if ($isSuccess && !empty($capturedResp) && $capturedResp->success && !empty($capturedResp->jsonData)) {
        $classification = 'LIVE_LLM_SUCCESS';
    } elseif ($rawErrorCode === 'API_ERROR' && strpos((string)$rawErrorMsg, 'Failed to validate JSON') !== false) {
        $classification = 'PROVIDER_HTTP_400';
    } elseif ($rawErrorCode === 'TIMEOUT') {
        $classification = 'TIMEOUT';
    } elseif ($rawErrorCode !== null) {
        $classification = 'PROVIDER_ERROR';
    } else {
        $classification = 'LOCAL_FALLBACK';
    }

    $promptTokens = $capturedResp ? $capturedResp->promptTokens : 0;
    $completionTokens = $capturedResp ? $capturedResp->completionTokens : 0;
    $hasJson = !empty($capturedResp->jsonData);

    // Periksa 6 Properti Kontrak
    $data = $capturedResp ? ($capturedResp->jsonData ?? []) : [];
    $hasIntent = isset($data['intent']);
    $hasExplanation = !empty($data['explanation']);
    $hasAiContentKey = array_key_exists('ai_content', $data);
    $hasMatchedIdKey = array_key_exists('matched_id', $data);
    $hasSecondaryIdsKey = array_key_exists('secondary_ids', $data) && is_array($data['secondary_ids']);
    $hasSuggestionsKey = array_key_exists('suggestions', $data) && is_array($data['suggestions']);

    $contractComplete = ($hasIntent && $hasExplanation && $hasAiContentKey && $hasMatchedIdKey && $hasSecondaryIdsKey && $hasSuggestionsKey);

    echo "Status Klasifikasi: [{$classification}]\n";
    echo "Latensi: {$latencyMs} ms\n";
    echo "Tokens: Prompt={$promptTokens} | Completion={$completionTokens}\n";
    echo "Parsed as JSON: " . ($hasJson ? 'YES' : 'NO') . "\n";
    echo "6 Kontrak Properti Terpenuhi: " . ($contractComplete ? 'YES' : 'NO') . "\n";
    if ($capturedResp && !empty($capturedResp->errorMessage)) {
        echo "Error Message: {$capturedResp->errorMessage}\n";
    }
    if (!empty($nluResult['explanation'])) {
        echo "Penjelasan/Jawaban:\n";
        echo "  " . substr($nluResult['explanation'], 0, 250) . "...\n";
    }
    if (!empty($nluResult['suggestions'])) {
        echo "Saran Tindak Lanjut: " . json_encode($nluResult['suggestions']) . "\n";
    }

    $trialData = [
        'trial'             => $trial,
        'timestamp'         => $timestamp,
        'masked_conv_id'    => $maskedUuid,
        'client_message_id' => $clientMessageId,
        'key_source'        => 'groq:self:2',
        'key_owner_id'      => 2,
        'provider'          => 'groq',
        'model'             => 'openai/gpt-oss-20b',
        'latency_ms'        => $latencyMs,
        'prompt_tokens'     => $promptTokens,
        'completion_tokens' => $completionTokens,
        'classification'    => $classification,
        'has_json'          => $hasJson,
        'contract_complete' => $contractComplete,
        'explanation'       => $nluResult['explanation'] ?? '',
        'intent'            => $nluResult['intent_type'] ?? '',
        'suggestions'       => $nluResult['suggestions'] ?? [],
        'error_code'        => $rawErrorCode,
        'error_msg'         => $rawErrorMsg,
    ];
    $results[] = $trialData;

    // Jeda minimal 30 detik sebelum percobaan berikutnya (sesuai mandat)
    if ($trial < 3) {
        echo "\n[JEDA WAJIB] Menunggu 32 detik sebelum percobaan berikutnya demi stabilitas TPM vendor...\n";
        sleep(32);
    }
}

// -------------------------------------------------------------------------
// CP3 — POST-TEST QUOTA & SECURITY AUDIT
// -------------------------------------------------------------------------
echo "\n=====================================================================\n";
echo "   PEMERIKSAAN PASCA-PENGUJIAN (KUOTA & KEAMANAN)\n";
echo "=====================================================================\n";
$adminQuotaAfter = $db->table('ai_admin_quota_daily')->where('quota_date', $today)->get()->getRowArray();
$usedAfter = (int) ($adminQuotaAfter['used_count'] ?? 0);
echo "Kuota Admin Sebelum: {$usedBefore} | Sesudah: {$usedAfter} | Selisih: " . ($usedAfter - $usedBefore) . "\n";

$quotaPass = ($usedBefore === $usedAfter);
echo "Status Kuota Admin: " . ($quotaPass ? "TIDAK BERKURANG (PASS - BYOK Terbukti Murni)" : "BERKURANG (FAIL)") . "\n";

$secPassAfter = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "SecurityGuard SEC_PASS: " . ($secPassAfter ? "1 (PASS)" : "0 (FAIL)") . "\n";

// Simpan ringkasan ke file JSON sementara untuk penyusunan laporan resmi
file_put_contents(__DIR__ . '/live_q10_results.json', json_encode([
    'preflight' => [
        'sec_pass_before' => $secPass,
        'sec_pass_after'  => $secPassAfter,
        'user_id'         => 2,
        'telemetry_tag'   => $telemetryTag,
        'admin_used_before' => $usedBefore,
        'admin_used_after'  => $usedAfter,
    ],
    'trials' => $results,
], JSON_PRETTY_PRINT));

echo "\nPengujian selesai. Hasil tersimpan ke tests/live_q10_results.json.\n";
