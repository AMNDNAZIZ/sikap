<?php

/**
 * Controlled Live Validation Runner for Q4 & Q6 Remediation
 * Mandat EAO-HO-2026-038 (Checkpoint 5)
 * 
 * Target: Akun Guru ID 2 (Salma Wijaya / User ID 2 / Teacher ID 1)
 * Provider: Groq
 * Model: openai/gpt-oss-20b
 * Key Tag: groq:self:2
 * Total Panggilan: Maksimal 2 (Q4 & Q6 masing-masing tepat 1 kali)
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Controllers\Api\AiCopilot;
use App\Services\Ai\AiKeyResolver;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\Intent\IntentClassifier;
use App\Services\Ai\Memory\ConversationMemoryManager;
use App\Services\Ai\OperationalContextProvider;
use App\Services\Ai\UnifiedAiService;
use Config\AiConfig;
use Config\Database;

echo "====================================================================\n";
echo "   CONTROLLED LIVE VALIDATION: REMEDIASI Q4 & Q6 (EAO-HO-2026-038)\n";
echo "   Maksimal 2 Panggilan Live (Jeda Wajib 32 Detik)\n";
echo "====================================================================\n\n";

$db = Database::connect('default');
$config = new AiConfig();
$resolver = new AiKeyResolver($config, $db);

// -------------------------------------------------------------------------
// PREFLIGHT CHECKS & VERIFIKASI IDENTITAS
// -------------------------------------------------------------------------
echo "[PREFLIGHT 1] Verifikasi Lisensi (SecurityGuard)...\n";
require_once __DIR__ . '/../app/Libraries/SecurityGuard.php';
$secPass = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "              SEC_PASS = " . ($secPass ? '1' : '0') . "\n";
if (!$secPass) {
    echo "[ABORT] SEC_PASS bukan 1. Pengujian dibatalkan.\n";
    exit(1);
}

echo "[PREFLIGHT 2] Verifikasi Identitas Akun Guru ID 2...\n";
$user = $db->table('users')->where('id', 2)->get()->getRowArray();
$teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);
$user['teacher_id'] = $teacherId;
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

echo "[PREFLIGHT 5] Inisialisasi Sesi Percakapan Bersih untuk Pengujian Terkontrol...\n";
$memoryManager = new ConversationMemoryManager($db);
$newConv = $memoryManager->getOrCreateConversation(2, 3, null, "Sesi Validasi Remediasi Q4 dan Q6");
$conversationId = (int) $newConv['id'];
$conversationUuid = $newConv['conversation_uuid'];
$maskedUuid = substr($conversationUuid, 0, 8) . '-****-****-****-' . substr($conversationUuid, -12);
echo "              Conversation ID: {$conversationId} | UUID: {$maskedUuid}\n\n";

// -------------------------------------------------------------------------
// TELEMETRY CAPTURING WRAPPER
// -------------------------------------------------------------------------
class LiveValidationAiService extends UnifiedAiService {
    public ?AiResponse $lastExecutionResponse = null;
    public ?AiRequest $lastExecutionRequest = null;

    public function executeJson(AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        $this->lastExecutionRequest = $request;
        $response = parent::executeJson($request, $preferredProvider, $userContext);
        $this->lastExecutionResponse = $response;
        return $response;
    }
}

$capturingAiService = new LiveValidationAiService($config, null, null, $db, $resolver);
$copilot = new AiCopilot();
$copilot->setAiService($capturingAiService);

$ref = new ReflectionClass(AiCopilot::class);
$callMethod = $ref->getMethod('callUnifiedAiNlu');
$callMethod->setAccessible(true);

$contextProvider = $copilot->getContextProvider();

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
        'id'          => 'menu_modul_ajar',
        'title'       => 'Modul Ajar',
        'category'    => 'Administrasi Guru',
        'url'         => 'http://localhost:8080/siakad/admin/administrasi-guru/modul-ajar',
        'icon'        => 'bi bi-file-earmark-text',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_assessments',
        'title'       => 'Asesmen & Penilaian',
        'category'    => 'Penilaian & Rapor',
        'url'         => 'http://localhost:8080/siakad/admin/assessments',
        'icon'        => 'bi bi-journal-check',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_cbt_bank',
        'title'       => 'Bank Soal CBT',
        'category'    => 'Ujian CBT',
        'url'         => 'http://localhost:8080/siakad/admin/cbt/banksoal',
        'icon'        => 'bi bi-collection',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
];

$validationQueries = [
    'Q4' => "Sekarang buat materi Bahasa Indonesia.",
    'Q6' => "Saya ingin membuat asesmen formatif.",
];

$liveResults = [];
$stepCount = 0;

foreach ($validationQueries as $label => $queryText) {
    $stepCount++;
    $timestamp = date('Y-m-d H:i:s');
    echo "=====================================================================\n";
    echo "[{$label}] WAKTU: {$timestamp}\n";
    echo "      KUERI   : \"{$queryText}\"\n";
    echo "      PROVIDER: groq | MODEL: openai/gpt-oss-20b | TAG: groq:self:2\n";

    // Pembangkitan client_message_id unik
    $clientMessageId = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $assembledHistory = $memoryManager->getAssembledHistory($conversationId, 6, 800);
    echo "      MULTI-TURN CONTEXT: " . count($assembledHistory) . " pesan terpasang\n";

    $intentClassification = IntentClassifier::classify($queryText);
    $intentType = $intentClassification['intent'];
    echo "      CLASSIFIER INTENT : {$intentType} (Confidence: {$intentClassification['confidence']})\n";

    $memoryManager->recordUserMessage($conversationId, $queryText, $clientMessageId, $intentType);

    $opContext = $contextProvider->resolveContext($user, $queryText, $assembledHistory);
    $activeSubj = $opContext->activeAssignment['subject_name'] ?? 'None';
    $activeCls = $opContext->activeAssignment['class_name'] ?? 'None';
    echo "      OPERATIONAL CONTEXT: Mapel={$activeSubj} | Kelas={$activeCls} | Ambiguous=" . ($opContext->isAmbiguous ? 'YES' : 'NO') . "\n";

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
            $queryText,
            $allowedActions,
            $user,
            $userContext,
            $intentType,
            $assembledHistory,
            $conversationUuid,
            $opContext
        );
    } catch (\Throwable $e) {
        $nluResult = ['success' => false, 'error' => $e->getMessage()];
    }

    $latencyMs = round((microtime(true) - $startTime) * 1000, 2);
    $capturedResp = $capturingAiService->lastExecutionResponse;

    $classification = 'UNKNOWN';
    $isSuccess = $nluResult['success'] ?? false;
    $rawErrorCode = $capturedResp ? $capturedResp->errorCode : null;
    $rawErrorMsg = $capturedResp ? $capturedResp->errorMessage : null;
    $httpCode = $capturedResp ? ($capturedResp->rawResponse['http_code'] ?? 200) : 500;

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
    $totalTokens = $promptTokens + $completionTokens;
    $hasJson = !empty($capturedResp->jsonData);

    $data = $capturedResp ? ($capturedResp->jsonData ?? []) : [];
    $hasIntent = isset($data['intent']);
    $hasExplanation = !empty($data['explanation']);
    $hasAiContentKey = array_key_exists('ai_content', $data);
    $hasMatchedIdKey = array_key_exists('matched_id', $data);
    $hasSecondaryIdsKey = array_key_exists('secondary_ids', $data) && is_array($data['secondary_ids']);
    $hasSuggestionsKey = array_key_exists('suggestions', $data) && is_array($data['suggestions']);

    $contractComplete = ($hasIntent && $hasExplanation && $hasAiContentKey && $hasMatchedIdKey && $hasSecondaryIdsKey && $hasSuggestionsKey);

    $explanation = $nluResult['explanation'] ?? ($data['explanation'] ?? '');
    $aiContent = $nluResult['ai_content'] ?? ($data['ai_content'] ?? null);
    $finalIntent = $nluResult['intent_type'] ?? ($data['intent'] ?? $intentType);
    $matchedAction = $nluResult['matched_action'] ?? null;
    $matchedId = $matchedAction['id'] ?? null;

    $memoryManager->recordAssistantReply(
        $conversationId,
        $explanation,
        $aiContent,
        $finalIntent,
        $matchedId,
        $clientMessageId
    );

    echo "      HASIL EVALUASI LIVE:\n";
    echo "      - Klasifikasi       : [{$classification}]\n";
    echo "      - HTTP Status Code  : {$httpCode}\n";
    echo "      - Latensi           : {$latencyMs} ms (" . round($latencyMs / 1000, 3) . " s)\n";
    echo "      - Tokens            : Prompt={$promptTokens} | Completion={$completionTokens} | Total={$totalTokens}\n";
    echo "      - Validitas JSON    : " . ($hasJson ? 'VALID' : 'INVALID') . "\n";
    echo "      - Kontrak 6 Properti: " . ($contractComplete ? 'LENGKAP' : 'TIDAK LENGKAP') . "\n";
    echo "      - Final Intent      : {$finalIntent}\n";
    echo "      - Eksplanasi        : " . mb_substr(strip_tags((string)$explanation), 0, 150) . "...\n";
    if (!empty($aiContent)) {
        echo "      - AI Content        : YES (" . strlen((string)$aiContent) . " karakter)\n";
        echo "      - Snippet Konten    : " . mb_substr(str_replace("\n", " ", strip_tags((string)$aiContent)), 0, 120) . "...\n";
    } else {
        echo "      - AI Content        : NONE (null)\n";
    }

    $liveResults[$label] = [
        'label'             => $label,
        'query'             => $queryText,
        'classification'    => $classification,
        'http_code'         => $httpCode,
        'latency_ms'        => $latencyMs,
        'prompt_tokens'     => $promptTokens,
        'completion_tokens' => $completionTokens,
        'total_tokens'      => $totalTokens,
        'is_json_valid'     => $hasJson,
        'contract_complete' => $contractComplete,
        'final_intent'      => $finalIntent,
        'explanation'       => $explanation,
        'ai_content'        => $aiContent,
        'suggestions'       => $data['suggestions'] ?? [],
        'error_msg'         => $rawErrorMsg,
        'client_message_id' => $clientMessageId,
    ];

    if ($stepCount < count($validationQueries)) {
        echo "\n      -> Jeda wajib 32 detik antarpemanggilan untuk kepatuhan Groq TPM/RPM...\n";
        sleep(32);
    }
}

// -------------------------------------------------------------------------
// POST-VALIDATION AUDIT & INTEGRITY CHECK
// -------------------------------------------------------------------------
echo "\n=====================================================================\n";
echo "--- [POST-VALIDATION AUDIT & INTEGRITY CHECK] ---\n";
echo "=====================================================================\n";

$adminQuotaAfter = $db->table('ai_admin_quota_daily')->where('quota_date', $today)->get()->getRowArray();
$usedAfter = (int) ($adminQuotaAfter['used_count'] ?? 0);
$quotaDelta = $usedAfter - $usedBefore;

echo "Kuota Admin Sebelum : {$usedBefore} / {$limitCount}\n";
echo "Kuota Admin Sesudah : {$usedAfter} / {$limitCount}\n";
echo "Delta Pemotongan    : {$quotaDelta} (Wajib 0)\n";
$quotaPass = ($quotaDelta === 0);
echo "Isolasi Kuota       : " . ($quotaPass ? 'PATUH (ZERO DECREMENT)' : 'LEAK DETECTED!') . "\n";

$secPassPost = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "Lisensi SEC_PASS    : " . ($secPassPost ? '1 (PASS)' : '0 (FAIL)') . "\n";

$q4Success = ($liveResults['Q4']['classification'] === 'LIVE_LLM_SUCCESS');
$q6Success = ($liveResults['Q6']['classification'] === 'LIVE_LLM_SUCCESS');
$allSuccess = ($q4Success && $q6Success && $quotaPass && $secPassPost);

echo "\n=====================================================================\n";
echo "HASIL KONTROL VALIDASI LIVE Q4 & Q6:\n";
echo "Q4 Status: [{$liveResults['Q4']['classification']}] | JSON Valid: " . ($liveResults['Q4']['is_json_valid'] ? 'YES' : 'NO') . "\n";
echo "Q6 Status: [{$liveResults['Q6']['classification']}] | JSON Valid: " . ($liveResults['Q6']['is_json_valid'] ? 'YES' : 'NO') . "\n";
echo "Status Akhir: " . ($allSuccess ? 'REMEDIATION SUCCESS (2/2 LIVE_LLM_SUCCESS)' : 'REMEDIATION INCOMPLETE') . "\n";
echo "=====================================================================\n";

file_put_contents(__DIR__ . '/live_q4_q6_remediation_results.json', json_encode([
    'metadata' => [
        'mandate'            => 'EAO-HO-2026-038',
        'validation_date'    => $today,
        'user_id'            => 2,
        'teacher_id'         => 1,
        'provider'           => 'groq',
        'model'              => 'openai/gpt-oss-20b',
        'telemetry_tag'      => $telemetryTag,
        'conversation_id'    => $conversationId,
        'conversation_uuid'  => $conversationUuid,
        'admin_quota_before' => $usedBefore,
        'admin_quota_after'  => $usedAfter,
        'admin_quota_delta'  => $quotaDelta,
        'sec_pass'           => $secPassPost ? 1 : 0,
        'all_success'        => $allSuccess,
    ],
    'results'  => $liveResults,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "Telemetri hasil pengujian tersimpan pada: tests/live_q4_q6_remediation_results.json\n";
