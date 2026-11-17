<?php

/**
 * Test Suite CP2: Bounded Content Generation Verification
 * Mandat EAO-HO-2026-038
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Controllers\Api\AiCopilot;
use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\OperationalContextDTO;
use App\Services\Ai\Intent\IntentClassifier;
use App\Services\Ai\UnifiedAiService;
use Config\AiConfig;
use Config\Database;

echo "====================================================================\n";
echo "   CP2 TEST SUITE: BOUNDED CONTENT GENERATION (EAO-HO-2026-038)\n";
echo "====================================================================\n\n";

class CapturingMockAiService extends UnifiedAiService {
    public ?AiRequest $capturedRequest = null;
    public ?string $mockResponseText = null;

    public function executeJson(AiRequest $request, ?string $preferredProvider = null, ?array $userContext = null): AiResponse {
        $this->capturedRequest = $request;

        $text = $this->mockResponseText ?: json_encode([
            'intent'        => 'TASK_ASSISTANCE',
            'explanation'   => 'Berikut draf materi Bahasa Indonesia yang padat dan terstruktur.',
            'ai_content'    => "Materi Pokok:\n1. Gagasan utama teks deskripsi.\n2. Kalimat efektif dan ejaan baku.\n3. Contoh paragraf deskripsi sederhana.\n4. Latihan identifikasi ide pokok.\n5. Refleksi pembelajaran.",
            'matched_id'    => null,
            'secondary_ids' => [],
            'suggestions'   => ['Ingin latihan soal untuk materi ini?', 'Mau modul ajar lengkap?']
        ]);

        $jsonData = json_decode($text, true);

        return AiResponse::createSuccess(
            $text,
            'groq',
            'openai/gpt-oss-20b',
            120,
            180,
            120.5,
            $jsonData,
            []
        );
    }
}

$db = Database::connect('default');
$config = new AiConfig();
$mockService = new CapturingMockAiService($config, null, null, $db);
$copilot = new AiCopilot();
$copilot->setAiService($mockService);

$ref = new ReflectionClass(AiCopilot::class);
$callMethod = $ref->getMethod('callUnifiedAiNlu');
$callMethod->setAccessible(true);

$user = ['id' => 2, 'role_id' => 3, 'username' => '198501102022211005', 'related_id' => 1, 'teacher_id' => 1];
$userContext = ['user_id' => 2, 'role_id' => 3, 'teacher_id' => 1, 'client_message_id' => 'test-id'];

$allowedActions = [
    ['id' => 'menu_modul', 'title' => 'Modul Ajar', 'category' => 'Administrasi', 'url' => 'http://localhost/siakad/admin/modul-ajar']
];

$testScenarios = [
    // 1. Pembuatan materi Bahasa Indonesia kelas 6
    [
        'id'       => 'T-CP2-01',
        'query'    => 'Sekarang buat materi Bahasa Indonesia.',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Pembuatan materi Bahasa Indonesia kelas 6 memuat instruksi bounded length',
    ],
    // 2. Pembuatan materi Matematika kelas 6
    [
        'id'       => 'T-CP2-02',
        'query'    => 'Buat materi Matematika pecahan desimal untuk kelas 6',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Pembuatan materi Matematika kelas 6 memuat instruksi bounded length',
    ],
    // 3. Pembuatan materi IPA/IPAS
    [
        'id'       => 'T-CP2-03',
        'query'    => 'Susun materi IPAS sistem tata surya',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Pembuatan materi IPAS memuat instruksi bounded length',
    ],
    // 4. Permintaan materi yang lebih lengkap
    [
        'id'       => 'T-CP2-04',
        'query'    => 'Buatkan materi yang lebih lengkap tentang fotosintesis',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Permintaan materi lebih lengkap tetap dibatasi bounded length pada prompt',
    ],
    // 5. Permintaan ringkasan materi
    [
        'id'       => 'T-CP2-05',
        'query'    => 'Buatkan ringkasan materi norma dan aturan di masyarakat',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Permintaan ringkasan materi memuat instruksi bounded length',
    ],
    // 6. Percakapan lanjutan meminta pengembangan materi
    [
        'id'       => 'T-CP2-06',
        'query'    => 'Tolong buatkan draf pengembangan materi tadi',
        'intent'   => IntentClassifier::INTENT_TASK_ASSISTANCE,
        'has_bound'=> true,
        'desc'     => 'Percakapan lanjutan bantuan tugas memuat instruksi bounded length',
    ],
    // 7. Pertanyaan aplikasi ESPATA yang tidak meminta pembuatan konten
    [
        'id'       => 'T-CP2-07',
        'query'    => 'Bagaimana cara input nilai di ESPATA?',
        'intent'   => IntentClassifier::INTENT_APPLICATION_QUESTION,
        'has_bound'=> false,
        'desc'     => 'Pertanyaan aplikasi ESPATA TIDAK memuat instruksi bounded length',
    ],
];

$passed = 0;
$total = count($testScenarios);

foreach ($testScenarios as $sc) {
    $opCtx = new OperationalContextDTO(2, '198501102022211005', 'Salma Wijaya', 3, 'Guru');
    $res = $callMethod->invoke(
        $copilot,
        $sc['query'],
        $allowedActions,
        $user,
        $userContext,
        $sc['intent'],
        [],
        'conv-uuid-1',
        $opCtx
    );

    $req = $mockService->capturedRequest;
    $prompt = $req->prompt;

    $hasBoundingHeader = (strpos($prompt, 'PEDOMAN PEMBUATAN DRAF KONTEN (BOUNDED LENGTH)') !== false);
    $hasAnchor = (strpos($prompt, 'INSTRUKSI FORMAT KELUARAN (PROMPT ANCHOR)') !== false);
    $has3Paragraf = (strpos($prompt, 'maksimal 3 paragraf atau maksimal 5 poin utama') !== false);

    $condition = false;
    if ($sc['has_bound']) {
        $condition = $hasBoundingHeader && $hasAnchor && $has3Paragraf && ($res['success'] ?? false);
    } else {
        $condition = !$hasBoundingHeader && $hasAnchor && ($res['success'] ?? false);
    }

    if ($condition) {
        $passed++;
        echo "[PASS] [{$sc['id']}] {$sc['desc']}\n";
    } else {
        echo "[FAIL] [{$sc['id']}] {$sc['desc']} (hasBounding: " . ($hasBoundingHeader ? 'YES' : 'NO') . ")\n";
    }
}

echo "\n====================================================================\n";
echo "REKAPITULASI PENGUJIAN CP2 BOUNDED CONTENT:\n";
echo "Total Uji: {$total} | Lulus: {$passed} | Gagal: " . ($total - $passed) . "\n";
echo "====================================================================\n";

if ($passed === $total) {
    echo "STATUS: SEMUA PENGUJIAN CP2 BOUNDED CONTENT LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT KEGAGALAN PENGUJIAN CP2!\n";
    exit(1);
}
