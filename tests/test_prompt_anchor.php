<?php

/**
 * Test Suite for Prompt Anchor Implementation Verification
 * Mandat EAO-HO-2026-033
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Controllers\Api\AiCopilot;
use App\Services\Ai\Intent\IntentClassifier;
use App\Services\Ai\DTO\OperationalContextDTO;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\UnifiedAiService;

echo "====================================================================\n";
echo "   PROMPT ANCHOR VERIFICATION SUITE (EAO-HO-2026-033)\n";
echo "====================================================================\n\n";

$totalTests = 0;
$passedTests = 0;

function assertAnchor(bool $condition, string $testId, string $testName, string $detail = '') {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] [{$testId}] {$testName}" . ($detail ? " | {$detail}" : "") . "\n";
    } else {
        echo "[FAIL] [{$testId}] {$testName}" . ($detail ? " | {$detail}" : "") . "\n";
    }
}

// 1. Mock UnifiedAiService to inspect captured AiRequest
$capturedRequest = null;
$mockAiService = new class($capturedRequest) extends UnifiedAiService {
    public ?AiRequest $lastRequest = null;
    public function __construct(&$ref) {
        $this->lastRequest = &$ref;
    }
    public function executeJson(AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        $this->lastRequest = $request;
        $mockJson = [
            'intent' => IntentClassifier::INTENT_GENERAL_CONVERSATION,
            'explanation' => 'Anda sedang mengajar mata pelajaran Pendidikan Agama Islam di Kelas 6A.',
            'ai_content' => null,
            'matched_id' => null,
            'secondary_ids' => [],
            'suggestions' => ['Jadwal Mengajar', 'Daftar Nilai']
        ];
        return new AiResponse(true, json_encode($mockJson), $mockJson, 'groq', 'openai/gpt-oss-20b');
    }
};

$copilot = new AiCopilot();
$copilot->setAiService($mockAiService);

$ref = new ReflectionClass(AiCopilot::class);
$method = $ref->getMethod('callUnifiedAiNlu');
$method->setAccessible(true);

$user = ['id' => 2, 'role_id' => 3, 'name' => 'Guru Salma'];
$userContext = ['teacher_id' => 1, 'school_name' => 'SD SAKASALIKA'];
$allowedActions = [];
$assembledHistory = [];

$opContext = new OperationalContextDTO(2, 'salma', 'Guru Salma', 3, 'Guru');
$opContext->isTeacher = true;
$opContext->isAmbiguous = false;
$opContext->activeAssignment = ['assignment_id' => 10, 'subject_name' => 'Pendidikan Agama Islam', 'class_name' => 'Kelas 6A', 'fase' => 'C'];
$opContext->assignments = [$opContext->activeAssignment];
$opContext->assignedClasses = ['Kelas 6A'];
$opContext->status = 'COMPLETE';

// Invoke callUnifiedAiNlu with Q10 query
$q10Query = 'Apa yang sedang saya ajarkan?';
$result = $method->invoke($copilot, $q10Query, $allowedActions, $user, $userContext, IntentClassifier::INTENT_GENERAL_CONVERSATION, $assembledHistory, 'test-uuid-q10', $opContext);

// Verification 1: Prompt Anchor is present in userPrompt
$req = $mockAiService->lastRequest;
assertAnchor(
    $req !== null && strpos($req->prompt, 'INSTRUKSI FORMAT KELUARAN (PROMPT ANCHOR):') !== false,
    'T-PA-01', 'Prompt Anchor is present in captured AiRequest userPrompt',
    'Anchor heading detected'
);

// Verification 2: Explicit anchor mandates starting with '{' and ending with '}'
assertAnchor(
    $req !== null && 
    strpos($req->prompt, "Keluaran WAJIB dimulai langsung dengan karakter '{'") !== false &&
    strpos($req->prompt, "diakhiri dengan karakter '}'") !== false,
    'T-PA-02', 'Explicit constraint: starts directly with { and ends with }',
    'Direct JSON delimiter constraint present'
);

// Verification 3: Strict prohibition of markdown fences and conversational preamble
assertAnchor(
    $req !== null && 
    strpos($req->prompt, 'Dilarang keras menambahkan salam, kalimat pembuka, pengantar') !== false &&
    strpos($req->prompt, 'markdown code fence') !== false,
    'T-PA-03', 'Prohibition of preamble and markdown code fences',
    'No-preamble rule present'
);

// Verification 4: JSON Mode is enabled on AiRequest
assertAnchor(
    $req !== null && $req->jsonMode === true,
    'T-PA-04', 'jsonMode remains true on AiRequest',
    'jsonMode = ' . ($req->jsonMode ? 'true' : 'false')
);

// Verification 5: Response contract validation functions correctly with Prompt Anchor
assertAnchor(
    $result['success'] === true &&
    $result['intent_type'] === IntentClassifier::INTENT_GENERAL_CONVERSATION &&
    !empty($result['explanation']) &&
    is_array($result['suggestions']),
    'T-PA-05', 'NLU pipeline accepts and validates response according to contract',
    'Explanation: ' . substr($result['explanation'], 0, 40)
);

echo "\n====================================================================\n";
echo "REKAPITULASI PENGUJIAN PROMPT ANCHOR:\n";
echo "Total Uji: {$totalTests} | Lulus: {$passedTests} | Gagal: " . ($totalTests - $passedTests) . "\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo "STATUS: SEMUA 5 PENGUJIAN PROMPT ANCHOR LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
