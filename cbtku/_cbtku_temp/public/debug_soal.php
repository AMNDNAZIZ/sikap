<?php
/**
 * Debug: Lihat raw HTML soal yang dikirim ke browser siswa
 * Akses: https://cbtsmpistora.sch.id/debug_soal.php?key=debug2026&test_id=1
 * HAPUS SETELAH SELESAI!
 */
if (($_GET['key'] ?? '') !== 'debug2026') { http_response_code(403); die('Forbidden'); }

$appRoot = dirname(__DIR__);
// Load env
$dbHost = 'localhost'; $dbName = ''; $dbUser = ''; $dbPass = '';
foreach (file($appRoot . '/.env', FILE_IGNORE_NEW_LINES) as $line) {
    if (strpos($line, '#') === 0) continue;
    if (strpos($line, 'database.default.hostname') !== false) $dbHost = trim(explode('=', $line, 2)[1]);
    if (strpos($line, 'database.default.database') !== false) $dbName = trim(explode('=', $line, 2)[1]);
    if (strpos($line, 'database.default.username') !== false) $dbUser = trim(explode('=', $line, 2)[1]);
    if (strpos($line, 'database.default.password') !== false) $dbPass = trim(explode('=', $line, 2)[1]);
}

$testId = (int)($_GET['test_id'] ?? 1);
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Ambil bank_id dari test
    $stmt = $pdo->prepare("SELECT bank_id FROM cbt_test_status WHERE id = ?");
    $stmt->execute([$testId]);
    $test = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$test) die("Test ID $testId tidak ditemukan");

    // Ambil soal bergambar
    $stmt = $pdo->prepare("SELECT id, question_text, raw_text, option_a, option_b, option_c, option_d, option_e FROM cbt_questions WHERE bank_id = ? AND (question_text LIKE '%<img%' OR raw_text LIKE '%<img%') LIMIT 3");
    $stmt->execute([$test['bank_id']]);
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($questions)) {
        die("<pre>Tidak ada soal bergambar di bank soal test ID $testId (bank_id={$test['bank_id']})</pre>");
    }
} catch (Exception $e) {
    die("DB Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Debug Soal Gambar</title>
<style>
body { font-family: monospace; font-size: 13px; background: #1e1e1e; color: #d4d4d4; padding: 20px; }
.section { background: #2d2d2d; border: 1px solid #444; padding: 15px; margin: 15px 0; border-radius: 6px; }
.ok { color: #4ec9b0; } .err { color: #f44747; } .warn { color: #dcdcaa; }
img { max-width: 300px; border: 2px solid #4ec9b0; margin: 5px; }
img.broken { border-color: #f44747; }
pre { white-space: pre-wrap; word-break: break-all; }
</style>
</head>
<body>
<h2>🔍 Debug Soal Bergambar — Test ID: <?= $testId ?></h2>
<p>Base URL: <strong><?= $baseUrl ?></strong></p>

<?php foreach ($questions as $i => $q): ?>
<div class="section">
    <h3>Soal ID: <?= $q['id'] ?></h3>

    <?php
    // Tentukan sumber konten (sama seperti di ujian.php)
    $useRawText = !empty($q['raw_text']);
    $rawContent = $useRawText ? $q['raw_text'] : $q['question_text'];

    // Ekstrak semua src dari konten
    preg_match_all('/src="([^"]+)"/i', $rawContent, $matches);
    $imgUrls = $matches[1] ?? [];
    ?>

    <p><strong>Sumber:</strong> <?= $useRawText ? 'raw_text' : 'question_text' ?></p>
    <p><strong>Jumlah gambar ditemukan:</strong> <?= count($imgUrls) ?></p>

    <?php if (!empty($imgUrls)): ?>
    <h4>URL Gambar di Database:</h4>
    <?php foreach ($imgUrls as $url): ?>
        <?php
        $isLocalhost = strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false;
        $hasPublicPath = strpos($url, '/public/uploads/') !== false;
        $isRelative = !preg_match('#^https?://#', $url);
        $isBase64 = strpos($url, 'data:image') === 0;

        // Hitung URL yang seharusnya
        $fixedUrl = $url;
        if ($isLocalhost) {
            $fixedUrl = preg_replace('#https?://(?:localhost|127\.0\.0\.1)(?::\d+)?/(uploads/)#i', $baseUrl . '/$1', $url);
        } elseif ($hasPublicPath) {
            $fixedUrl = preg_replace('#https?://[^/]+/public/(uploads/)#i', $baseUrl . '/$1', $url);
        } elseif ($isRelative) {
            $fixedUrl = $baseUrl . '/' . ltrim($url, '/');
        }

        // Cek file fisik
        $filename = basename(parse_url($fixedUrl, PHP_URL_PATH));
        $physicalPath = __DIR__ . '/uploads/soal_images/' . $filename;
        $fileExists = file_exists($physicalPath);
        ?>
        <div style="margin: 10px 0; padding: 10px; background: #333; border-radius: 4px;">
            <div class="<?= $isLocalhost || $hasPublicPath || $isRelative ? 'err' : 'ok' ?>">
                DB URL: <?= htmlspecialchars($url) ?>
                <?php if ($isLocalhost): ?> ← ❌ LOCALHOST URL<?php endif; ?>
                <?php if ($hasPublicPath): ?> ← ❌ /public/ PATH<?php endif; ?>
                <?php if ($isRelative): ?> ← ⚠️ RELATIVE URL<?php endif; ?>
                <?php if ($isBase64): ?> ← ❌ BASE64 (belum diproses)<?php endif; ?>
            </div>
            <?php if (!$isBase64): ?>
            <div class="<?= $url === $fixedUrl ? 'ok' : 'warn' ?>">
                Fixed URL: <?= htmlspecialchars($fixedUrl) ?>
            </div>
            <div class="<?= $fileExists ? 'ok' : 'err' ?>">
                File fisik: <?= $physicalPath ?> — <?= $fileExists ? '✅ ADA' : '❌ TIDAK ADA' ?>
            </div>
            <div>
                <strong>Test render gambar:</strong><br>
                <img src="<?= htmlspecialchars($fixedUrl) ?>"
                     onerror="this.classList.add('broken'); this.nextSibling.textContent='❌ GAGAL LOAD: ' + this.src;"
                     onload="this.nextSibling.textContent='✅ BERHASIL LOAD';"
                     alt="test">
                <span></span>
            </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>

    <h4>Raw HTML (100 chars sekitar img tag):</h4>
    <pre><?php
    preg_match_all('/.{0,50}<img[^>]+>.{0,50}/s', $rawContent, $ctx);
    foreach ($ctx[0] as $c) echo htmlspecialchars($c) . "\n---\n";
    ?></pre>

    <h4>Simulasi data-raw-content (seperti yang dikirim ke browser):</h4>
    <pre><?= htmlspecialchars(htmlspecialchars($rawContent)) ?></pre>

    <h4>Setelah JavaScript decode (innerHTML yang akan di-set):</h4>
    <div style="background:#fff;color:#000;padding:10px;border-radius:4px;">
        <?= $rawContent ?>
    </div>
</div>
<?php endforeach; ?>

<p style="color:red;font-weight:bold;">⚠️ HAPUS FILE INI: public/debug_soal.php</p>
</body>
</html>
