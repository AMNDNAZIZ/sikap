<?php
$t0 = microtime(true);
$constantsContent = file_get_contents(__DIR__ . '/app/Config/Constants.php');
preg_match("/define\('APP_VERSION',\s*'([^']+)'\)/", $constantsContent, $vMatch);
$appVersion = $vMatch[1] ?? '2.0.4';
$root = rtrim(str_replace('\\', '/', __DIR__), '/') . '/';
$dirs = ['app', 'public'];
$files = [];

$textExts = ['php', 'js', 'css', 'html', 'htm', 'json', 'xml', 'md', 'txt', 'yml', 'yaml', 'tpl', 'sql', 'htaccess', 'env'];

function scanDirFast($dir, $root, &$files, $textExts) {
    if (!is_dir($dir)) return;
    $handle = opendir($dir);
    if (!$handle) return;
    while (false !== ($f = readdir($handle))) {
        if ($f === '.' || $f === '..') continue;
        $full = str_replace('\\', '/', $dir . '/' . $f);
        $rel = substr($full, strlen($root));
        if (strpos($rel, '.git') !== false) continue;
        if (strpos($rel, 'node_modules') !== false) continue;
        if (strpos($rel, 'public/uploads') !== false) continue;
        if (strpos($rel, 'app/Config/.lic_hash') !== false) continue;
        if (strpos($rel, 'app/Config/.security_hash') !== false) continue;
        
        if (is_file($full)) {
            $ext = strtolower(pathinfo($full, PATHINFO_EXTENSION));
            if (in_array($ext, $textExts) || $ext === '') {
                $c = file_get_contents($full);
                $c = str_replace("\r\n", "\n", $c);
                $files[$rel] = md5($c);
            } else {
                $files[$rel] = md5_file($full);
            }
        } elseif (is_dir($full)) {
            scanDirFast($full, $root, $files, $textExts);
        }
    }
    closedir($handle);
}

foreach ($dirs as $d) {
    scanDirFast(__DIR__ . '/' . $d, $root, $files, $textExts);
}

ksort($files);
$manifest = [
    'version'     => $appVersion,
    'generated'   => date('Y-m-d H:i:s'),
    'last_update' => date('Y-m-d H:i'),
    'total_files' => count($files),
    'files'       => $files
];
file_put_contents(__DIR__ . '/update_manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Manifest v{$appVersion}: " . count($files) . " files generated in " . round(microtime(true) - $t0, 2) . "s\n";
