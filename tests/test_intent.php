<?php

require_once __DIR__ . '/../app/Services/Ai/Intent/IntentClassifier.php';

use App\Services\Ai\Intent\IntentClassifier;

$testCases = [
    'Halo, selamat pagi Asis AI!' => IntentClassifier::INTENT_GENERAL_CONVERSATION,
    'Jelaskan perbedaan asesmen formatif dan sumatif' => IntentClassifier::INTENT_KNOWLEDGE_RETRIEVAL,
    'Apa saja fitur yang ada di ESPATA?' => IntentClassifier::INTENT_APPLICATION_QUESTION,
    'Buka menu penilaian' => IntentClassifier::INTENT_NAVIGATION,
    'Buka menu helikopter' => IntentClassifier::INTENT_NAVIGATION,
    'Bantu buat 3 butir soal HOTS materi aljabar untuk kelas 7' => IntentClassifier::INTENT_TASK_ASSISTANCE,
    'Buat lebih sederhana' => IntentClassifier::INTENT_CLARIFICATION,
    'Hitung pajak penghasilan saya bulan ini' => IntentClassifier::INTENT_UNSUPPORTED_REQUEST,
];

$allPass = true;
foreach ($testCases as $query => $expected) {
    $res = IntentClassifier::classify($query);
    $pass = $res['intent'] === $expected;
    echo ($pass ? '[PASS] ' : '[FAIL] ') . "'{$query}' => " . $res['intent'] . " (expected: {$expected})\n";
    if (!$pass) {
        $allPass = false;
    }
}

echo $allPass ? "ALL INTENT TESTS PASS\n" : "FAILURES DETECTED\n";
exit($allPass ? 0 : 1);
