<?php

/**
 * Controlled Live Full 10-Query Benchmark Runner
 * Mandat EAO-HO-2026-039: FINAL ACCEPTANCE BENCHMARK — ASIS AI FASE 1D.3
 * 
 * Target: Akun Guru ID 2 (User ID 2 / Teacher ID 1)
 * Provider: Groq
 * Model: openai/gpt-oss-20b
 * Key Tag: groq:self:2
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
echo "   MANDAT EAO-HO-2026-039: FINAL ACCEPTANCE BENCHMARK RUNNER\n";
echo "   Controlled Live LLM Benchmark (Asis AI ESPATA - Fase 1D.3)\n";
echo "====================================================================\n\n";

$db = Database::connect('default');
$config = new AiConfig();
$resolver = new AiKeyResolver($config, $db);

// -------------------------------------------------------------------------
// PREFLIGHT CHECKS
// -------------------------------------------------------------------------
echo "--- [PREFLIGHT AUDIT & ENVIRONMENT VERIFICATION] ---\n";

// 1. Verifikasi Lisensi (SecurityGuard)
echo "[PREFLIGHT 1] Verifikasi Lisensi (SecurityGuard)...\n";
require_once __DIR__ . '/../app/Libraries/SecurityGuard.php';
$secPass = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "              SEC_PASS = " . ($secPass ? '1' : '0') . "\n";
if (!$secPass) {
    echo "[ABORT] SEC_PASS bukan 1. Pengujian dibatalkan demi keamanan lisensi.\n";
    exit(1);
}

// 2. Verifikasi Identitas Akun Guru ID 2
echo "[PREFLIGHT 2] Verifikasi Identitas Akun Guru ID 2...\n";
$user = $db->table('users')->where('id', 2)->get()->getRowArray();
$teacherId = (int) ($user['teacher_id'] ?? $user['related_id'] ?? 0);
$user['teacher_id'] = $teacherId;
echo "              User ID: {$user['id']} | Username: {$user['username']} | Role: {$user['role_id']} | Teacher ID: {$teacherId}\n";

$teacher = $db->table('teachers')->where('id', $teacherId)->get()->getRowArray();
$teacherName = $teacher['nama'] ?? $teacher['nama_guru'] ?? 'Guru';
echo "              Nama Guru: {$teacherName}\n";

// 3. Verifikasi Kunci BYOK Guru ID 2 via Resolver
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

// 4. Catat Kuota Admin Sebelum Pengujian (Asia/Jakarta)
echo "[PREFLIGHT 4] Catat Kuota Admin Sebelum Pengujian...\n";
$tzJakarta = new DateTimeZone('Asia/Jakarta');
$nowJakarta = new DateTime('now', $tzJakarta);
$today = $nowJakarta->format('Y-m-d');
$adminQuotaBefore = $db->table('ai_admin_quota_daily')->where('quota_date', $today)->get()->getRowArray();
$usedBefore = (int) ($adminQuotaBefore['used_count'] ?? 0);
$limitCount = (int) ($adminQuotaBefore['limit_count'] ?? 20);
echo "              Kuota Admin ({$today} Asia/Jakarta): {$usedBefore} / {$limitCount}\n";

// 5. Inisialisasi Sesi Percakapan Baru Bersih
echo "[PREFLIGHT 5] Inisialisasi Sesi Percakapan Bersih untuk Benchmark EAO-039...\n";
$memoryManager = new ConversationMemoryManager($db);
$newConv = $memoryManager->getOrCreateConversation(2, 3, null, "Buatkan 10 soal pilihan ganda tentang pecahan.");
$conversationId = (int) $newConv['id'];
$conversationUuid = $newConv['conversation_uuid'];
$maskedUuid = substr($conversationUuid, 0, 8) . '-****-****-****-' . substr($conversationUuid, -12);
echo "              Conversation Baru Dibuat | ID: {$conversationId} | UUID: {$maskedUuid}\n";
echo "              Tujuan: Menjamin kesinambungan multi-turn bersih dan murni Q1-Q10.\n\n";

// -------------------------------------------------------------------------
// TELEMETRY CAPTURING SETUP
// -------------------------------------------------------------------------
class TelemetryCapturingAiServiceEao039 extends UnifiedAiService {
    public ?AiResponse $lastExecutionResponse = null;
    public ?AiRequest $lastExecutionRequest = null;

    public function executeJson(AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        $this->lastExecutionRequest = $request;
        $response = parent::executeJson($request, $preferredProvider, $userContext);
        $this->lastExecutionResponse = $response;
        return $response;
    }
}

$capturingAiService = new TelemetryCapturingAiServiceEao039($config, null, null, $db, $resolver);
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
        'id'          => 'menu_grades',
        'title'       => 'Nilai Siswa',
        'category'    => 'Penilaian & Rapor',
        'url'         => 'http://localhost:8080/siakad/admin/grades',
        'icon'        => 'bi bi-award',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_presensi',
        'title'       => 'Presensi Siswa',
        'category'    => 'Kesiswaan',
        'url'         => 'http://localhost:8080/siakad/admin/attendance',
        'icon'        => 'bi bi-calendar-check',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_jadwal',
        'title'       => 'Jadwal Pelajaran',
        'category'    => 'Akademik',
        'url'         => 'http://localhost:8080/siakad/admin/schedule',
        'icon'        => 'bi bi-clock',
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
    [
        'id'          => 'menu_kokurikuler',
        'title'       => 'Kokurikuler',
        'category'    => 'Kurikulum',
        'url'         => 'http://localhost:8080/siakad/admin/kokurikuler',
        'icon'        => 'bi bi-diagram-3',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
    [
        'id'          => 'menu_students',
        'title'       => 'Data Siswa',
        'category'    => 'Kesiswaan',
        'url'         => 'http://localhost:8080/siakad/admin/students',
        'icon'        => 'bi bi-people',
        'badge'       => 'Menu',
        'action_type' => 'navigate',
    ],
];

$benchmarkQuestions = [
    1  => "Buatkan 10 soal pilihan ganda tentang pecahan.",
    2  => "Buatkan soal untuk kelas saya.",
    3  => "Ubah menjadi lebih sulit.",
    4  => "Sekarang buat materi Bahasa Indonesia.",
    5  => "Buatkan tujuan pembelajaran untuk materi tersebut.",
    6  => "Saya ingin membuat asesmen formatif.",
    7  => "Apa fase kurikulum untuk kelas yang saya ajar?",
    8  => "Bantu saya membuat modul ajar.",
    9  => "Buatkan kegiatan pembelajaran yang sesuai dengan kelas saya.",
    10 => "Apa yang sedang saya ajarkan?",
];

$results = [];

echo "=====================================================================\n";
echo "   MEMULAI EKSEKUSI BENCHMARK FINAL ACCEPTANCE ASIS AI (10 KUERI)    \n";
echo "   (Jeda Wajib: 32 Detik Sekuensial Tanpa Retry)                     \n";
echo "=====================================================================\n\n";

foreach ($benchmarkQuestions as $qNum => $queryText) {
    $nowJakarta = new DateTime('now', $tzJakarta);
    $timestamp = $nowJakarta->format('Y-m-d H:i:s');
    echo "---------------------------------------------------------------------\n";
    echo "[Q{$qNum}/10] WAKTU: {$timestamp} WIB\n";
    echo "       KUERI   : \"{$queryText}\"\n";
    echo "       PROVIDER: groq | MODEL: openai/gpt-oss-20b | TAG: groq:self:2\n";

    $clientMessageId = sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $assembledHistory = $memoryManager->getAssembledHistory($conversationId, 6, 800);
    echo "       MULTI-TURN CONTEXT: " . count($assembledHistory) . " pesan riwayat terpasang\n";

    $intentClassification = IntentClassifier::classify($queryText);
    $intentType = $intentClassification['intent'];

    $memoryManager->recordUserMessage($conversationId, $queryText, $clientMessageId, $intentType);

    $opContext = $contextProvider->resolveContext($user, $queryText, $assembledHistory);
    $activeSubj = $opContext->activeAssignment['subject_name'] ?? 'None';
    $activeCls = $opContext->activeAssignment['class_name'] ?? 'None';
    $activePhase = $opContext->activeAssignment['phase'] ?? 'None';
    echo "       OPERATIONAL CONTEXT: Sekolah=" . ($opContext->schoolName ?? 'None') 
        . " | Mapel=" . $activeSubj 
        . " | Kelas=" . $activeCls 
        . " | Fase=" . $activePhase 
        . " | Ambiguous=" . ($opContext->isAmbiguous ? 'YES' : 'NO') . "\n";

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

    echo "       HASIL EVALUASI LIVE:\n";
    echo "       - Status Klasifikasi : [{$classification}]\n";
    echo "       - HTTP Status Code   : {$httpCode}\n";
    echo "       - Latensi            : {$latencyMs} ms (" . round($latencyMs / 1000, 3) . " s)\n";
    echo "       - Token Penggunaan   : Prompt={$promptTokens} | Completion={$completionTokens} | Total={$totalTokens}\n";
    echo "       - Validitas JSON     : " . ($hasJson ? 'VALID' : 'INVALID') . "\n";
    echo "       - Kontrak 6 Properti : " . ($contractComplete ? 'LENGKAP' : 'TIDAK LENGKAP') . "\n";
    echo "       - Final Intent       : {$finalIntent}\n";
    echo "       - Matched Action     : " . ($matchedAction ? $matchedAction['title'] : 'None') . "\n";
    echo "       - Eksplanasi         : " . mb_substr(strip_tags((string)$explanation), 0, 160) . "...\n";
    if (!empty($aiContent)) {
        echo "       - AI Content         : YES (" . strlen((string)$aiContent) . " karakter)\n";
    } else {
        echo "       - AI Content         : NONE\n";
    }

    $results[$qNum] = [
        'q_num'             => $qNum,
        'timestamp'         => $timestamp,
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
        'matched_action'    => $matchedAction ? $matchedAction['title'] : null,
        'explanation'       => $explanation,
        'ai_content'        => $aiContent,
        'suggestions'       => $data['suggestions'] ?? [],
        'error_msg'         => $rawErrorMsg,
        'client_message_id' => $clientMessageId,
        'context_snapshot'  => [
            'school'    => $opContext->schoolName,
            'subject'   => $activeSubj,
            'class'     => $activeCls,
            'phase'     => $activePhase,
            'ambiguous' => $opContext->isAmbiguous,
        ],
    ];

    if ($qNum < 10) {
        echo "       -> Menunggu 32 detik sesuai mandat untuk kepatuhan Groq rate-limit...\n";
        sleep(32);
    }
}

// -------------------------------------------------------------------------
// POST-BENCHMARK INTEGRITY VERIFICATION
// -------------------------------------------------------------------------
echo "\n=====================================================================\n";
echo "--- [POST-BENCHMARK AUDIT & INTEGRITY CHECK] ---\n";
echo "=====================================================================\n";

$adminQuotaAfter = $db->table('ai_admin_quota_daily')->where('quota_date', $today)->get()->getRowArray();
$usedAfter = (int) ($adminQuotaAfter['used_count'] ?? 0);
$quotaDelta = $usedAfter - $usedBefore;

echo "[INTEGRITAS 1] Kuota Admin Sebelum : {$usedBefore} / {$limitCount}\n";
echo "              Kuota Admin Sesudah : {$usedAfter} / {$limitCount}\n";
echo "              Delta Pemotongan    : {$quotaDelta} (Wajib 0)\n";
$quotaIntegrityPass = ($quotaDelta === 0);
echo "              Status Isolasi Kuota: " . ($quotaIntegrityPass ? 'PATUH (ZERO DECREMENT)' : 'LEAK DETECTED!') . "\n";

$secPassPost = App\Libraries\SecurityGuard::verifyCriticalFiles();
echo "[INTEGRITAS 2] Lisensi SEC_PASS   : " . ($secPassPost ? '1 (PASS)' : '0 (FAIL)') . "\n";

$totalLiveSuccess = count(array_filter($results, fn($r) => $r['classification'] === 'LIVE_LLM_SUCCESS'));
$totalValidJson = count(array_filter($results, fn($r) => $r['is_json_valid'] === true));
$totalContractComplete = count(array_filter($results, fn($r) => $r['contract_complete'] === true));

echo "\n=====================================================================\n";
echo "                 REKAPITULASI BENCHMARK EAO-HO-2026-039              \n";
echo "=====================================================================\n";
echo "Total Pertanyaan Dieksekusi : " . count($results) . " / 10\n";
echo "Total LIVE_LLM_SUCCESS      : {$totalLiveSuccess} / 10 (" . ($totalLiveSuccess * 10) . "%)\n";
echo "Total JSON Valid            : {$totalValidJson} / 10\n";
echo "Total Kontrak 6 Properti    : {$totalContractComplete} / 10\n";
echo "Isolasi Kuota Admin (Delta) : {$quotaDelta}\n";
echo "Status Lisensi SEC_PASS     : " . ($secPassPost ? '1' : '0') . "\n";

$isAccepted = ($totalLiveSuccess === 10 && $totalValidJson === 10 && $totalContractComplete === 10 && $quotaIntegrityPass && $secPassPost);
echo "Status Rekomendasi Mandat   : " . ($isAccepted ? 'BENCHMARK ACCEPTED (10/10 SUCCESS)' : 'BENCHMARK INCOMPLETE') . "\n";
echo "=====================================================================\n";

$outputData = [
    'metadata' => [
        'mandate'              => 'EAO-HO-2026-039',
        'benchmark_date'       => $today,
        'user_id'              => 2,
        'teacher_id'           => 1,
        'teacher_name'         => $teacherName,
        'provider'             => 'groq',
        'model'                => 'openai/gpt-oss-20b',
        'telemetry_tag'        => $telemetryTag,
        'conversation_id'      => $conversationId,
        'conversation_uuid'    => $conversationUuid,
        'admin_quota_before'   => $usedBefore,
        'admin_quota_after'    => $usedAfter,
        'admin_quota_delta'    => $quotaDelta,
        'sec_pass_before'      => $secPass ? 1 : 0,
        'sec_pass_after'       => $secPassPost ? 1 : 0,
        'total_live_success'   => $totalLiveSuccess,
        'recommendation_status'=> $isAccepted ? 'ACCEPTED' : 'INCOMPLETE',
    ],
    'results'  => $results,
];

file_put_contents(__DIR__ . '/live_benchmark_10_results_eao039.json', json_encode($outputData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Detail telemetri tersimpan pada: tests/live_benchmark_10_results_eao039.json\n";
