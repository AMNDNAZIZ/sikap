<?php
require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Services\Ai\Intent\IntentClassifier;

$testCases = [
    // 1. Pertanyaan konseptual asesmen formatif -> KNOWLEDGE_RETRIEVAL
    "Apa itu asesmen formatif?" => IntentClassifier::INTENT_KNOWLEDGE_RETRIEVAL,
    "Mengapa asesmen formatif diperlukan?" => IntentClassifier::INTENT_KNOWLEDGE_RETRIEVAL,
    
    // 2. Permintaan membuat asesmen formatif -> TASK_ASSISTANCE
    "Saya ingin membuat asesmen formatif." => IntentClassifier::INTENT_TASK_ASSISTANCE,
    "Buatkan asesmen formatif untuk kelas saya." => IntentClassifier::INTENT_TASK_ASSISTANCE,
    
    // 3. Permintaan membuat asesmen sumatif -> TASK_ASSISTANCE
    "Saya ingin membuat asesmen sumatif." => IntentClassifier::INTENT_TASK_ASSISTANCE,
    "Rancang asesmen sumatif mata pelajaran IPA" => IntentClassifier::INTENT_TASK_ASSISTANCE,
    
    // 4. Pertanyaan cara menggunakan fitur asesmen ESPATA -> APPLICATION_QUESTION
    "Bagaimana cara membuat asesmen formatif di ESPATA?" => IntentClassifier::INTENT_APPLICATION_QUESTION,
    "Bagaimana cara input nilai asesmen di sistem?" => IntentClassifier::INTENT_APPLICATION_QUESTION,
    
    // 5. Pertanyaan navigasi menu asesmen -> NAVIGATION
    "Buka menu asesmen" => IntentClassifier::INTENT_NAVIGATION,
    "Arahkan ke menu penilaian" => IntentClassifier::INTENT_NAVIGATION,
    
    // 6. Pertanyaan umum tidak berkaitan dengan asesmen -> GENERAL_CONVERSATION / UNSUPPORTED
    "Halo, selamat pagi Asis AI!" => IntentClassifier::INTENT_GENERAL_CONVERSATION,
    "Hitung pajak penghasilan saya bulan ini" => IntentClassifier::INTENT_UNSUPPORTED_REQUEST,
    
    // 7. Permintaan pembuatan soal -> TASK_ASSISTANCE
    "Bantu buat 3 butir soal HOTS materi aljabar untuk kelas 7" => IntentClassifier::INTENT_TASK_ASSISTANCE,
    "Buatkan 10 soal pilihan ganda tentang pecahan." => IntentClassifier::INTENT_TASK_ASSISTANCE,
    
    // 8. Percakapan lanjutan mempertahankan konteks / klarifikasi -> CLARIFICATION
    "Buat lebih sederhana" => IntentClassifier::INTENT_CLARIFICATION,
];

echo "=== TESTING CURRENT INTENT CLASSIFIER ===\n";
$fails = 0;
foreach ($testCases as $query => $expected) {
    $res = IntentClassifier::classify($query);
    $actual = $res['intent'];
    $ok = ($actual === $expected);
    if (!$ok) $fails++;
    echo ($ok ? "[PASS]" : "[FAIL]") . " \"{$query}\" => Actual: {$actual} | Expected: {$expected}\n";
}
echo "Total Failures on Current Code: {$fails}\n";
