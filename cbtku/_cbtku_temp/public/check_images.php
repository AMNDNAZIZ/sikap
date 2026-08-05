<?php
/**
 * Image Diagnostic Tool
 * Cek URL gambar di database vs file fisik di server
 * Akses: https://cbtsmpistora.sch.id/check_images.php?key=cbt_img_2026
 * HAPUS FILE INI SETELAH SELESAI!
 */

if (($_GET['key'] ?? '') !== 'cbt_img_2026') {
    http_response_code(403);
    die('Forbidden');
}

echo '<pre style="font-family:monospace;font-size:13px;background:#1e1e1e;color:#d4d4d4;padding:20px;">';
echo "=== IMAGE DIAGNOSTIC TOOL ===\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

$appRoot = dirname(__DIR__);
$publicPath = __DIR__;

// ─── 1. Cek folder uploads ────────────────────────────────────
echo "=== UPLOAD FOLDERS ===\n";
$folders = [
    $publicPath . '/uploads',
    $publicPath . '/uploads/soal_images',
    $publicPath . '/uploads/logo',
];
foreach ($folders as $f) {
    $exists   = is_dir($f);
    $writable = $exists && is_writable($f);
    $count    = $exists ? count(glob($f . '/*')) : 0;
    echo ($exists ? "✅" : "❌") . " $f";
    echo " | writable=" . ($writable ? "yes" : "NO");
    echo " | files=$count\n";
}
echo "\n";

// ─── 2. Sample gambar di soal_images ─────────────────────────
echo "=== SAMPLE FILES IN soal_images ===\n";
$imgDir = $publicPath . '/uploads/soal_images/';
if (is_dir($imgDir)) {
    $files = array_slice(glob($imgDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE), 0, 5);
    if (empty($files)) {
        echo "⚠️  No image files found in soal_images/\n";
    } else {
        foreach ($files as $f) {
            echo "✅ " . basename($f) . " (" . number_format(filesize($f)) . " bytes)\n";
        }
    }
} else {
    echo "❌ soal_images folder does not exist!\n";
}
echo "\n";

// ─── 3. Cek URL gambar di database ───────────────────────────
echo "=== IMAGE URLs IN DATABASE ===\n";
$envFile = $appRoot . '/.env';
$dbHost = 'localhost'; $dbName = ''; $dbUser = ''; $dbPass = '';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES) as $line) {
        if (strpos($line, '#') === 0) continue;
        if (strpos($line, 'database.default.hostname') !== false) $dbHost = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.database') !== false) $dbName = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.username') !== false) $dbUser = trim(explode('=', $line, 2)[1]);
        if (strpos($line, 'database.default.password') !== false) $dbPass = trim(explode('=', $line, 2)[1]);
    }
}

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_TIMEOUT => 3,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Cari soal yang mengandung gambar
    $stmt = $pdo->query("
        SELECT id, 
               SUBSTRING(question_text, LOCATE('src=\"', question_text) + 5, 
                         LOCATE('\"', question_text, LOCATE('src=\"', question_text) + 5) - LOCATE('src=\"', question_text) - 5
               ) as img_url
        FROM cbt_questions 
        WHERE question_text LIKE '%<img%' OR raw_text LIKE '%<img%'
        LIMIT 10
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo "ℹ️  No questions with images found\n";
    } else {
        foreach ($rows as $row) {
            $url = trim($row['img_url'] ?? '');
            if (empty($url)) continue;

            echo "Question ID " . $row['id'] . ":\n";
            echo "  URL: $url\n";

            // Cek apakah URL mengandung localhost
            if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
                echo "  ❌ MASALAH: URL masih pakai localhost!\n";
            } elseif (strpos($url, '/public/uploads/') !== false) {
                echo "  ❌ MASALAH: URL mengandung /public/ yang tidak perlu!\n";
            } elseif (strpos($url, 'uploads/soal_images/') !== false) {
                // Cek file fisik
                $filename = basename(parse_url($url, PHP_URL_PATH));
                $physicalPath = $imgDir . $filename;
                if (file_exists($physicalPath)) {
                    echo "  ✅ File fisik ada: $physicalPath\n";
                } else {
                    echo "  ❌ File fisik TIDAK ADA: $physicalPath\n";
                }
            } else {
                echo "  ⚠️  URL format tidak dikenali\n";
            }
            echo "\n";
        }
    }

    // Cek juga di raw_text
    $stmt2 = $pdo->query("
        SELECT id, raw_text 
        FROM cbt_questions 
        WHERE raw_text LIKE '%<img%' 
        LIMIT 5
    ");
    $rawRows = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rawRows)) {
        echo "\n=== IMAGE URLs IN raw_text ===\n";
        foreach ($rawRows as $row) {
            preg_match_all('/src="([^"]+)"/i', $row['raw_text'], $matches);
            foreach ($matches[1] as $url) {
                if (strpos($url, 'data:image') === 0) {
                    echo "Question " . $row['id'] . ": ❌ BASE64 masih tersimpan di DB (belum diproses)!\n";
                    continue;
                }
                echo "Question " . $row['id'] . ": $url\n";
                if (strpos($url, 'localhost') !== false) {
                    echo "  ❌ MASALAH: URL masih pakai localhost!\n";
                } elseif (strpos($url, 'uploads/soal_images/') !== false) {
                    $filename = basename(parse_url($url, PHP_URL_PATH));
                    $physicalPath = $imgDir . $filename;
                    echo "  " . (file_exists($physicalPath) ? "✅ File ada" : "❌ File TIDAK ADA") . ": $physicalPath\n";
                }
            }
        }
    }

} catch (Exception $e) {
    echo "❌ DB Error: " . $e->getMessage() . "\n";
}
echo "\n";

// ─── 4. Test akses HTTP ke gambar ────────────────────────────
echo "=== HTTP ACCESS TEST ===\n";
$testFiles = array_slice(glob($imgDir . '*.{jpg,jpeg,png,gif,webp}', GLOB_BRACE), 0, 2);
if (!empty($testFiles)) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    foreach ($testFiles as $f) {
        $url = $protocol . '://' . $host . '/uploads/soal_images/' . basename($f);
        echo "Testing: $url\n";

        $headers = @get_headers($url, 1);
        if ($headers) {
            $status = $headers[0] ?? 'unknown';
            echo "  Response: $status\n";
            if (strpos($status, '200') !== false) {
                echo "  ✅ File dapat diakses via HTTP\n";
            } elseif (strpos($status, '403') !== false) {
                echo "  ❌ 403 Forbidden - Nginx/Apache memblokir akses!\n";
            } elseif (strpos($status, '404') !== false) {
                echo "  ❌ 404 Not Found - File tidak ditemukan atau path salah\n";
            } else {
                echo "  ⚠️  Status tidak dikenali\n";
            }
        } else {
            echo "  ❌ Tidak bisa connect (curl/get_headers disabled?)\n";
        }
        echo "\n";
    }
} else {
    echo "⚠️  Tidak ada file gambar untuk ditest\n";
}

// ─── 5. Nginx config hint ─────────────────────────────────────
echo "=== NGINX CONFIG CHECK ===\n";
$nginxConf = '/www/server/panel/vhost/nginx/' . $_SERVER['HTTP_HOST'] . '.conf';
if (file_exists($nginxConf)) {
    $conf = file_get_contents($nginxConf);
    echo "Nginx config found: $nginxConf\n\n";
    // Cek apakah ada location untuk uploads
    if (strpos($conf, 'uploads') !== false) {
        echo "✅ Config mengandung rule untuk 'uploads'\n";
    } else {
        echo "⚠️  Config TIDAK mengandung rule khusus untuk 'uploads'\n";
        echo "   Gambar mungkin di-forward ke PHP dan gagal!\n";
    }
    // Tampilkan bagian location
    preg_match_all('/location[^{]+\{[^}]+\}/s', $conf, $locations);
    echo "\nLocation blocks:\n";
    foreach ($locations[0] as $loc) {
        echo "  " . trim(preg_replace('/\s+/', ' ', $loc)) . "\n";
    }
} else {
    echo "⚠️  Nginx config tidak ditemukan di: $nginxConf\n";
    echo "   Cek manual di aaPanel → Website → " . $_SERVER['HTTP_HOST'] . " → Config\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
echo '</pre>';
echo '<p style="color:red;font-weight:bold;font-family:monospace;padding:10px;">⚠️ HAPUS FILE INI: public/check_images.php</p>';
