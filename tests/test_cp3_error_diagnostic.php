<?php

/**
 * Test Suite CP3: Provider Error Diagnostic Sanitization Verification
 * Mandat EAO-HO-2026-038
 */

require_once __DIR__ . '/../system/Test/bootstrap.php';

use App\Services\Ai\DTO\AiRequest;
use App\Services\Ai\Providers\GroqAdapter;
use Config\AiConfig;

echo "====================================================================\n";
echo "   CP3 TEST SUITE: PROVIDER ERROR DIAGNOSTIC (EAO-HO-2026-038)\n";
echo "====================================================================\n\n";

$config = new AiConfig();
$adapter = new GroqAdapter($config);

$passed = 0;
$total = 0;

function assertTest(bool $condition, string $id, string $desc) {
    global $passed, $total;
    $total++;
    if ($condition) {
        $passed++;
        echo "[PASS] [{$id}] {$desc}\n";
    } else {
        echo "[FAIL] [{$id}] {$desc}\n";
    }
}

// 1. Uji HTTP 400 JSON Validate Failed
$adapter->setHttpExecutor(function($endpoint, $headers, $payload, $timeout, $config) {
    return [
        'httpCode'  => 400,
        'curlErrno' => 0,
        'curlError' => '',
        'rawBody'   => json_encode([
            'error' => [
                'message'           => "Failed to validate JSON. Please adjust your prompt. See 'failed_generation' for more details.",
                'type'              => 'invalid_request_error',
                'code'              => 'json_validate_failed',
                'failed_generation' => '{"intent": "TASK_ASSISTANCE", "ai_content": "Secret text...', // should not be kept in rawResponse
            ]
        ])
    ];
});

$req = new AiRequest('test prompt', '', [], 'openai/gpt-oss-20b', 0.3, 650, 15, true, 'gsk_secretkey1234567890abcdef');
$resp1 = $adapter->generateText($req);

assertTest(!$resp1->success, "T-CP3-01", "HTTP 400 menghasilkan success = false");
assertTest($resp1->errorCode === 'API_ERROR', "T-CP3-02", "Error code diset API_ERROR");
assertTest($resp1->rawResponse['http_code'] === 400, "T-CP3-03", "Diagnostic memuat http_code = 400");
assertTest($resp1->rawResponse['provider_code'] === 'json_validate_failed', "T-CP3-04", "Diagnostic memuat provider_code = json_validate_failed");
assertTest($resp1->rawResponse['json_mode'] === true, "T-CP3-05", "Diagnostic memuat json_mode = true");
assertTest(!isset($resp1->rawResponse['failed_generation']), "T-CP3-06", "Raw failed_generation TIDAK disimpan pada diagnostic (kebijakan privasi CP3)");
assertTest(strpos(json_encode($resp1->rawResponse), 'gsk_secretkey') === false, "T-CP3-07", "Zero Secret Leakage: API key tidak bocor ke diagnostic");

// 2. Uji HTTP 429 Rate Limit
$adapter->setHttpExecutor(function($endpoint, $headers, $payload, $timeout, $config) {
    return [
        'httpCode'  => 429,
        'curlErrno' => 0,
        'curlError' => '',
        'rawBody'   => json_encode([
            'error' => [
                'message' => 'Rate limit reached for model',
                'type'    => 'rate_limit_exceeded',
                'code'    => 'rate_limit_reached',
            ]
        ])
    ];
});

$resp2 = $adapter->generateText($req);
assertTest($resp2->errorCode === 'RATE_LIMIT_429', "T-CP3-08", "HTTP 429 menghasilkan errorCode = RATE_LIMIT_429");
assertTest($resp2->rawResponse['failure_class'] === 'RATE_LIMIT_429', "T-CP3-09", "Diagnostic failure_class diset RATE_LIMIT_429");

// 3. Uji cURL Timeout
$adapter->setHttpExecutor(function($endpoint, $headers, $payload, $timeout, $config) {
    return [
        'httpCode'  => 0,
        'curlErrno' => 28, // CURLE_OPERATION_TIMEDOUT
        'curlError' => 'Operation timed out after 15000 milliseconds',
        'rawBody'   => ''
    ];
});

$resp3 = $adapter->generateText($req);
assertTest($resp3->errorCode === 'TIMEOUT', "T-CP3-10", "cURL 28 menghasilkan errorCode = TIMEOUT");
assertTest($resp3->rawResponse['curl_errno'] === 28, "T-CP3-11", "Diagnostic memuat curl_errno = 28");

echo "\n====================================================================\n";
echo "REKAPITULASI PENGUJIAN CP3 ERROR DIAGNOSTIC:\n";
echo "Total Uji: {$total} | Lulus: {$passed} | Gagal: " . ($total - $passed) . "\n";
echo "====================================================================\n";

if ($passed === $total) {
    echo "STATUS: SEMUA PENGUJIAN CP3 ERROR DIAGNOSTIC LULUS 100%!\n";
    exit(0);
} else {
    echo "STATUS: TERDAPAT KEGAGALAN PENGUJIAN CP3!\n";
    exit(1);
}
