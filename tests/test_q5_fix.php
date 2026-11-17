<?php

/**
 * Regression Test Suite for Q5 Bug Fix
 * Validating $finalIntent hoisting and variable access order in AiCopilot::callUnifiedAiNlu()
 * Mandat EAO-HO-2026-031
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Controllers\Api\AiCopilot;
use App\Services\Ai\Intent\IntentClassifier;
use App\Services\Ai\DTO\OperationalContextDTO;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\UnifiedAiService;

echo "====================================================================\n";
echo "   REGRESSION TEST SUITE: Q5 FIX VERIFICATION (EAO-HO-2026-031)\n";
echo "====================================================================\n\n";

$totalTests = 0;
$passedTests = 0;

function assertQ5(bool $condition, string $testId, string $testName, string $detail = '') {
    global $totalTests, $passedTests;
    $totalTests++;
    if ($condition) {
        $passedTests++;
        echo "[PASS] [{$testId}] {$testName}" . ($detail ? " | {$detail}" : "") . "\n";
    } else {
        echo "[FAIL] [{$testId}] {$testName}" . ($detail ? " | {$detail}" : "") . "\n";
    }
}

// Setup reflection on AiCopilot::callUnifiedAiNlu
$copilot = new AiCopilot();
$ref = new ReflectionClass(AiCopilot::class);
$method = $ref->getMethod('callUnifiedAiNlu');
$method->setAccessible(true);

// -------------------------------------------------------------------------
// TEST 1: Exact Q5 scenario - LLM returns ai_content with specific intent
// -------------------------------------------------------------------------
// Mock UnifiedAiService to return a simulated response where data has ai_content
$mockAiService = new class extends UnifiedAiService {
    public ?AiResponse $predefinedResponse = null;
    public function __construct() {}
    public function executeJson(App\Services\Ai\DTO\AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        return $this->predefinedResponse ?? new AiResponse(true, '', null, 'mock');
    }
};

$copilot->setAiService($mockAiService);

// Test 1A: Non-ambiguous context with ai_content (The exact Q5 benchmark trigger)
$mockArrayA = [
    'intent' => IntentClassifier::INTENT_TASK_ASSISTANCE,
    'explanation' => 'Berikut adalah draf materi pembelajaran yang diminta.',
    'ai_content' => [
        'title' => 'Materi IPAS Kelas 6',
        'sections' => ['Tujuan', 'Materi Inti', 'Latihan']
    ],
    'matched_id' => null,
    'suggestions' => []
];
$mockAiService->predefinedResponse = new AiResponse(true, json_encode($mockArrayA), $mockArrayA, 'groq', 'openai/gpt-oss-20b');

$user = ['id' => 2, 'role_id' => 3, 'name' => 'Guru Salma'];
$userContext = ['teacher_id' => 1, 'school_name' => 'SD SAKASALIKA'];
$allowedActions = [];
$assembledHistory = [];

$opContextNonAmbiguous = new OperationalContextDTO(2, 'salma', 'Guru Salma', 3, 'Guru');
$opContextNonAmbiguous->isTeacher = true;
$opContextNonAmbiguous->isAmbiguous = false;
$opContextNonAmbiguous->activeAssignment = ['assignment_id' => 10, 'subject_name' => 'IPAS', 'class_name' => 'Kelas 6A', 'fase' => 'C'];
$opContextNonAmbiguous->assignments = [$opContextNonAmbiguous->activeAssignment];
$opContextNonAmbiguous->assignedClasses = ['Kelas 6A'];
$opContextNonAmbiguous->status = 'COMPLETE';

// Invoke callUnifiedAiNlu
$result1A = $method->invoke($copilot, 'buatkan materi ipas', $allowedActions, $user, $userContext, IntentClassifier::INTENT_TASK_ASSISTANCE, $assembledHistory, 'test-uuid-1', $opContextNonAmbiguous);

assertQ5(
    $result1A['success'] === true &&
    $result1A['intent_type'] === IntentClassifier::INTENT_TASK_ASSISTANCE &&
    !empty($result1A['ai_content']) &&
    strpos($result1A['ai_content'], 'Materi IPAS Kelas 6') !== false,
    'T-Q5-01', 'Q5 Exact Scenario: ai_content populated without Undefined Variable error',
    'ai_content preserved and intent_type = ' . ($result1A['intent_type'] ?? 'NONE')
);

// Test 1B: Ambiguous context where intent is CLARIFICATION (ai_content must be nullified to prevent confusion)
$opContextAmbiguous = new OperationalContextDTO(2, 'salma', 'Guru Salma', 3, 'Guru');
$opContextAmbiguous->isTeacher = true;
$opContextAmbiguous->isAmbiguous = true;
$opContextAmbiguous->activeAssignment = null;
$opContextAmbiguous->assignments = [
    ['assignment_id' => 10, 'subject_name' => 'IPAS', 'class_name' => 'Kelas 6A', 'fase' => 'C'],
    ['assignment_id' => 11, 'subject_name' => 'Matematika', 'class_name' => 'Kelas 6A', 'fase' => 'C']
];
$opContextAmbiguous->assignedClasses = ['Kelas 6A'];
$opContextAmbiguous->status = 'AMBIGUOUS';

$mockArrayB = [
    'intent' => IntentClassifier::INTENT_CLARIFICATION,
    'explanation' => 'Anda mengajar beberapa mata pelajaran di Kelas 6A. Mohon tentukan mata pelajaran yang dimaksud.',
    'ai_content' => 'Data materi draft parsial yang seharusnya disembunyikan.',
    'matched_id' => null,
    'suggestions' => ['IPAS Kelas 6A', 'Matematika Kelas 6A']
];
$mockAiService->predefinedResponse = new AiResponse(true, json_encode($mockArrayB), $mockArrayB, 'groq', 'openai/gpt-oss-20b');

$result1B = $method->invoke($copilot, 'buatkan soal', $allowedActions, $user, $userContext, IntentClassifier::INTENT_CLARIFICATION, $assembledHistory, 'test-uuid-2', $opContextAmbiguous);

assertQ5(
    $result1B['success'] === true &&
    $result1B['intent_type'] === IntentClassifier::INTENT_CLARIFICATION &&
    $result1B['ai_content'] === null,
    'T-Q5-02', 'Ambiguous Context with CLARIFICATION: ai_content is correctly nullified',
    'ai_content is null and clarification prompt is clean'
);

// Test 1C: Normal response without ai_content (regression baseline)
$mockArrayC = [
    'intent' => IntentClassifier::INTENT_APPLICATION_QUESTION,
    'explanation' => 'Menu input nilai berada pada menu Penilaian & Rapor -> Input Nilai.',
    'ai_content' => null,
    'matched_id' => null,
    'suggestions' => []
];
$mockAiService->predefinedResponse = new AiResponse(true, json_encode($mockArrayC), $mockArrayC, 'groq', 'openai/gpt-oss-20b');

$result1C = $method->invoke($copilot, 'cara input nilai', $allowedActions, $user, $userContext, IntentClassifier::INTENT_APPLICATION_QUESTION, $assembledHistory, 'test-uuid-3', $opContextNonAmbiguous);

assertQ5(
    $result1C['success'] === true &&
    $result1C['intent_type'] === IntentClassifier::INTENT_APPLICATION_QUESTION &&
    $result1C['ai_content'] === null &&
    strpos($result1C['explanation'], 'Input Nilai') !== false,
    'T-Q5-03', 'Normal Response without ai_content operates normally',
    'Explanation: ' . substr($result1C['explanation'], 0, 50)
);

// Test 1D: Fallback when intent in JSON is empty (defaults to $intentType parameter)
$mockArrayD = [
    'intent' => '',
    'explanation' => 'Penjelasan umum.',
    'ai_content' => 'Konten teks biasa.',
    'matched_id' => null,
    'suggestions' => []
];
$mockAiService->predefinedResponse = new AiResponse(true, json_encode($mockArrayD), $mockArrayD, 'groq', 'openai/gpt-oss-20b');

$result1D = $method->invoke($copilot, 'pertanyaan umum', $allowedActions, $user, $userContext, IntentClassifier::INTENT_GENERAL_CONVERSATION, $assembledHistory, 'test-uuid-4', $opContextNonAmbiguous);

assertQ5(
    $result1D['success'] === true &&
    $result1D['intent_type'] === IntentClassifier::INTENT_GENERAL_CONVERSATION &&
    !empty($result1D['ai_content']),
    'T-Q5-04', 'Empty intent in data falls back correctly to $intentType parameter',
    'intent_type = ' . $result1D['intent_type']
);

echo "\n====================================================================\n";
echo "REKAPITULASI PENGUJIAN REGRESI Q5:\n";
echo "Total Uji: {$totalTests} | Lulus: {$passedTests} | Gagal: " . ($totalTests - $passedTests) . "\n";
echo "====================================================================\n";

if ($passedTests === $totalTests) {
    echo "STATUS: SEMUA PENGUJIAN REGRESI Q5 LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT PENGUJIAN YANG GAGAL!\n";
    exit(1);
}
