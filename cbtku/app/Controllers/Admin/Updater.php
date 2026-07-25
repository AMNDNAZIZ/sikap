<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Updater extends BaseController
{
    /**
     * GitHub raw URL for update manifest
     */
    private string $githubRepo = 'Sakajaya/update-cbtku';
    private string $githubBranch = 'main';

    private function getGithubRawUrl(string $path = ''): string
    {
        return "https://raw.githubusercontent.com/{$this->githubRepo}/{$this->githubBranch}/{$path}";
    }

    /**
     * Halaman utama updater
     */
    public function index()
    {
        $data = [
            'title'      => 'System Updater',
            'migrations' => $this->getMigrationHistory(),
        ];
        return view('admin/updater/index', $data);
    }

    // ═══════════════════════════════════════════════════════════════════
    // GENERATE MANIFEST + AUTO PUSH (LOCALHOST / DEVELOPER ONLY)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Generate update_manifest.json dan push ke GitHub (orphan commit)
     * Hanya tersedia di instance developer (IS_DEVELOPER = true)
     */
    public function generateManifest()
    {
        if (!defined('IS_DEVELOPER') || IS_DEVELOPER !== true) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        $newVersion = trim($this->request->getGet('new_version') ?? '');
        if (empty($newVersion) || !preg_match('/^\d+\.\d+(\.\d+)?$/', $newVersion)) {
            return redirect()->back()->with('error', 'Nomor versi tidak valid. Format: X.Y atau X.Y.Z');
        }

        // Sync changelogs to file sebelum generate
        try {
            $this->syncChangelogsToFile();
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to sync changelogs: ' . $e->getMessage());
        }

        // Update APP_VERSION & LAST_UPDATE di Constants.php
        $constantsPath = APPPATH . 'Config/Constants.php';
        if (is_file($constantsPath)) {
            $content = file_get_contents($constantsPath);
            $newDate = date('Y-m-d H:i');
            $content = preg_replace(
                "/define\('APP_VERSION',\s*'[^']+'\);/",
                "define('APP_VERSION', '{$newVersion}');",
                $content
            );
            $content = preg_replace(
                "/define\('LAST_UPDATE',\s*'[^']*'\);/",
                "define('LAST_UPDATE', '{$newDate}');",
                $content
            );
            file_put_contents($constantsPath, $content);
        }

        // Generate manifest: scan app/ dan public/
        $manifest = [
            'version'      => $newVersion,
            'generated_at' => date('Y-m-d H:i:s'),
            'files'        => [],
        ];

        $folders = ['app', 'public'];
        foreach ($folders as $folder) {
            $basePath = ROOTPATH . $folder;
            if (!is_dir($basePath)) continue;
            $this->scanForManifest($basePath, ROOTPATH, $manifest['files']);
        }

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $manifestPath = ROOTPATH . 'update_manifest.json';
        file_put_contents($manifestPath, $manifestJson);

        log_message('info', "[Manifest] Generated with " . count($manifest['files']) . " files, version {$newVersion}");

        // Auto push ke GitHub (orphan commit)
        $pushResult = $this->pushToGitHub($newVersion);

        if ($pushResult['success']) {
            return redirect()->back()->with('success',
                "Manifest v{$newVersion} berhasil digenerate (" . count($manifest['files']) . " files) dan dipush ke GitHub.");
        } else {
            return redirect()->back()->with('warning',
                "Manifest berhasil digenerate, tapi push ke GitHub gagal: {$pushResult['error']}. File manifest tersimpan di root project.");
        }
    }

    /**
     * Scan folder untuk manifest (exclude sensitive files)
     */
    private function scanForManifest(string $dir, string $rootPath, array &$files): void
    {
        $excludeFolders = ['.git', 'node_modules', 'vendor', '.vscode', '.idea', 'tests'];
        $excludeSubPaths = ['public/uploads/'];
        $excludeFiles = ['.env', '.htaccess', '.lic_hash', '.lic_checksum', '.lic_backup_hidden', '.lic_marker'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) continue;

            $fullPath = str_replace('\\', '/', $item->getPathname());
            $relativePath = str_replace('\\', '/', substr($fullPath, strlen(str_replace('\\', '/', $rootPath))));

            // Skip excluded folders
            $skip = false;
            foreach ($excludeFolders as $ef) {
                if (strpos($relativePath, $ef . '/') !== false) { $skip = true; break; }
            }
            if ($skip) continue;

            // Skip excluded sub-paths
            foreach ($excludeSubPaths as $esp) {
                if (strpos($relativePath, $esp) === 0) { $skip = true; break; }
            }
            if ($skip) continue;

            // Skip excluded files by basename
            $basename = basename($relativePath);
            if (in_array($basename, $excludeFiles)) continue;
            if (strpos($basename, '.lic_') === 0) continue;

            // Normalize line ending ke LF sebelum hash (agar cocok dengan server Linux)
            $content = file_get_contents($item->getPathname());
            $content = str_replace("\r\n", "\n", $content);
            $content = str_replace("\r", "\n", $content);

            $files[$relativePath] = md5($content);
        }
    }

    /**
     * Push ke GitHub repo distribusi (orphan commit, force push)
     */
    private function pushToGitHub(string $version): array
    {
        $repoDir = WRITEPATH . 'update_repo/';
        $folders = ['app', 'public'];

        // Pastikan git tersedia
        exec('git --version 2>&1', $output, $returnCode);
        if ($returnCode !== 0) {
            return ['success' => false, 'error' => 'Git tidak terinstall atau tidak tersedia di PATH'];
        }

        // Cleanup & prepare temp repo
        if (is_dir($repoDir)) {
            $this->deleteDirectory($repoDir);
        }
        mkdir($repoDir, 0755, true);

        // Copy folders ke temp repo
        foreach ($folders as $folder) {
            $src = ROOTPATH . $folder;
            if (is_dir($src)) {
                $this->copyDirectory($src, $repoDir . $folder);
            }
        }

        // Copy manifest
        copy(ROOTPATH . 'update_manifest.json', $repoDir . 'update_manifest.json');

        // Create .gitattributes
        $gitattributes = "* text=auto eol=lf\n*.php text eol=lf\n*.js text eol=lf\n*.css text eol=lf\n*.json text eol=lf\n*.png binary\n*.jpg binary\n*.jpeg binary\n*.gif binary\n*.ico binary\n*.pdf binary\n*.woff binary\n*.woff2 binary\n*.ttf binary\n*.eot binary\n";
        file_put_contents($repoDir . '.gitattributes', $gitattributes);

        // Create README
        $readme = "# Update CBT-KU\n\nDistribusi update otomatis untuk aplikasi CBT-KU.\n\n**Versi:** {$version}\n**Generated:** " . date('Y-m-d H:i:s') . "\n\n> File ini digenerate otomatis. Jangan edit manual.\n";
        file_put_contents($repoDir . 'README.md', $readme);

        // Git init, orphan commit, force push
        $remoteUrl = "https://github.com/{$this->githubRepo}.git";
        $commands = [
            'git init',
            'git checkout --orphan main',
            'git add -A',
            "git commit -m \"Update v{$version} - " . date('Y-m-d H:i') . "\"",
            "git remote add origin {$remoteUrl}",
            'git push -f origin main',
        ];

        $allOutput = [];
        foreach ($commands as $cmd) {
            exec("cd \"{$repoDir}\" && {$cmd} 2>&1", $cmdOutput, $cmdReturn);
            $allOutput[] = $cmd . ' => ' . implode(' | ', $cmdOutput);
            $cmdOutput = [];
            if ($cmdReturn !== 0 && strpos($cmd, 'push') !== false) {
                log_message('error', '[GitPush] Failed: ' . implode("\n", $allOutput));
                return ['success' => false, 'error' => 'Git push gagal. Pastikan credentials GitHub sudah dikonfigurasi.'];
            }
        }

        // Cleanup temp repo
        $this->deleteDirectory($repoDir);

        log_message('info', "[GitPush] Successfully pushed v{$version} to {$this->githubRepo}");
        return ['success' => true, 'error' => ''];
    }

    // ═══════════════════════════════════════════════════════════════════
    // CHECK ONLINE UPDATE (CLIENT / PRODUCTION)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Cek update dari GitHub: download remote manifest, bandingkan hash
     */
    public function checkOnlineUpdate()
    {
        try {
            $remoteManifestUrl = $this->getGithubRawUrl('update_manifest.json');

            // Download remote manifest via curl langsung (ringan)
            $remoteJson = $this->curlGet($remoteManifestUrl);
            if ($remoteJson === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghubungi server update (GitHub). Periksa koneksi internet.'
                ]);
            }

            $remoteManifest = json_decode($remoteJson, true);
            if (!$remoteManifest || !isset($remoteManifest['files'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Manifest remote tidak valid atau kosong.'
                ]);
            }

            $localVersion = defined('APP_VERSION') ? APP_VERSION : '0.0.0';
            $remoteVersion = $remoteManifest['version'] ?? '0.0.0';

            // Bandingkan hash tiap file
            $changedFiles = [];
            $newFiles = [];

            foreach ($remoteManifest['files'] as $path => $remoteHash) {
                $localPath = ROOTPATH . $path;
                if (!file_exists($localPath)) {
                    $newFiles[] = $path;
                } else {
                    // Normalize line ending lokal sebelum hash (sama seperti saat generate)
                    $localContent = file_get_contents($localPath);
                    $localContent = str_replace("\r\n", "\n", $localContent);
                    $localContent = str_replace("\r", "\n", $localContent);
                    $localHash = md5($localContent);

                    if ($localHash !== $remoteHash) {
                        $changedFiles[] = $path;
                    }
                }
            }

            $totalDiff = count($changedFiles) + count($newFiles);

            return $this->response->setJSON([
                'success'        => true,
                'has_update'     => $totalDiff > 0,
                'local_version'  => $localVersion,
                'remote_version' => $remoteVersion,
                'generated_at'   => $remoteManifest['generated_at'] ?? '-',
                'changed_files'  => $changedFiles,
                'new_files'      => $newFiles,
                'total_diff'     => $totalDiff,
                'total_remote'   => count($remoteManifest['files']),
            ]);

        } catch (\Throwable $e) {
            log_message('error', '[OnlineUpdate] Check failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // APPLY ONLINE UPDATE (DELTA UPDATE DENGAN STAGING)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Download file yang berubah ke staging, lalu copy ke ROOTPATH
     * Catatan penting:
     * - Tidak redirect setelah selesai (mencegah session invalidation)
     * - Pakai curl_init langsung (bukan CI CURLRequest) — lebih ringan
     * - Reconnect DB setelah download lama
     * - Session clear TERAKHIR
     */
    public function applyOnlineUpdate()
    {
        set_time_limit(0);
        ignore_user_abort(true);

        // Ambil data dari POST (dikirim via JS)
        $input = $this->request->getJSON(true) ?? $this->request->getPost();
        $changedFiles = $input['changed_files'] ?? [];
        $newFiles = $input['new_files'] ?? [];
        $remoteVersion = $input['remote_version'] ?? '';

        $allFiles = array_merge($changedFiles, $newFiles);

        if (empty($allFiles)) {
            echo $this->buildUpdateResultPage(false, 'Tidak ada file yang perlu diupdate.', []);
            return;
        }

        // STEP 1: Enable maintenance mode
        $this->enableMaintenanceMode();

        // STEP 2: Backup critical files
        $backupDir = $this->backupCriticalFiles($allFiles);

        // STEP 3: Buat staging folder
        $stagingId = 'update_staging_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4));
        $stagingDir = WRITEPATH . $stagingId . '/';
        mkdir($stagingDir, 0755, true);

        // STEP 4: Download semua file ke staging
        $downloadErrors = [];
        $downloadedCount = 0;

        foreach ($allFiles as $filePath) {
            $rawUrl = $this->getGithubRawUrl($filePath);
            $localStagingPath = $stagingDir . $filePath;
            $localStagingDir = dirname($localStagingPath);

            if (!is_dir($localStagingDir)) {
                mkdir($localStagingDir, 0755, true);
            }

            $content = $this->curlGet($rawUrl);
            if ($content === false) {
                $downloadErrors[] = $filePath;
                continue;
            }

            if (file_put_contents($localStagingPath, $content) === false) {
                $downloadErrors[] = $filePath . ' (write failed)';
                continue;
            }

            $downloadedCount++;
        }

        // Jika ada error download > 10%, batalkan
        if (count($downloadErrors) > 0 && count($downloadErrors) > count($allFiles) * 0.1) {
            $this->cleanupStaging($stagingDir);
            $this->disableMaintenanceMode();
            echo $this->buildUpdateResultPage(false,
                'Terlalu banyak file gagal didownload (' . count($downloadErrors) . '/' . count($allFiles) . '). Update dibatalkan.',
                ['errors' => $downloadErrors]);
            return;
        }

        // STEP 5: Reconnect database (mencegah "MySQL server has gone away")
        $db = \Config\Database::connect();
        $db->close();
        $db = \Config\Database::connect();
        $db->initialize();

        // STEP 6: Copy dari staging ke ROOTPATH
        $copyResult = $this->moveStagingToRoot($stagingDir);

        // STEP 7: Run migrations
        $migrationResult = $this->autoMigrateAfterUpdate();

        // STEP 8: Sync changelogs
        $this->syncChangelogsFromData();

        // STEP 9: Regenerate security hashes (jika file lisensi berubah)
        $licenseUpdated = $this->checkLicenseFilesUpdated($allFiles);
        if ($licenseUpdated) {
            $this->regenerateSecurityHashes();
        }

        // STEP 10: Update version constant
        if (!empty($remoteVersion)) {
            $this->updateVersionConstant($remoteVersion);
        }

        // STEP 11: Clear sessions (TERAKHIR!)
        $this->clearSessions();

        // STEP 12: Cleanup
        $this->cleanupStaging($stagingDir);
        $this->disableMaintenanceMode();
        $this->cleanupOldBackups(7);

        // STEP 13: Render halaman hasil LANGSUNG (tanpa redirect)
        $details = [
            'downloaded'       => $downloadedCount,
            'download_errors'  => $downloadErrors,
            'copy_result'      => $copyResult,
            'migration_result' => $migrationResult,
            'license_updated'  => $licenseUpdated,
            'backup_dir'       => basename($backupDir),
            'version'          => $remoteVersion,
        ];

        echo $this->buildUpdateResultPage(true,
            "Update ke v{$remoteVersion} berhasil! {$downloadedCount} file diperbarui.",
            $details);
    }

    // ═══════════════════════════════════════════════════════════════════
    // HELPER PRIVATE METHODS
    // ═══════════════════════════════════════════════════════════════════

    private function enableMaintenanceMode(): void
    {
        $flag = ROOTPATH . '.maintenance';
        file_put_contents($flag, json_encode([
            'started_at' => date('Y-m-d H:i:s'),
            'reason'     => 'online_update',
        ]));
    }

    private function disableMaintenanceMode(): void
    {
        $flag = ROOTPATH . '.maintenance';
        if (file_exists($flag)) {
            @unlink($flag);
        }
    }

    private function backupCriticalFiles(array $filesToUpdate): string
    {
        $backupDir = WRITEPATH . 'backups/pre_update_' . date('Ymd_His') . '/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        foreach ($filesToUpdate as $file) {
            $source = ROOTPATH . $file;
            if (file_exists($source)) {
                $dest = $backupDir . $file;
                $destDir = dirname($dest);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                @copy($source, $dest);
            }
        }

        log_message('info', '[Update] Backup created: ' . $backupDir);
        return $backupDir;
    }

    private function autoMigrateAfterUpdate(): array
    {
        try {
            $migrate = \Config\Services::migrations();
            $executed = $migrate->latest();
            return [
                'success' => true,
                'message' => $executed ? 'Migrasi database berhasil.' : 'Database sudah terbaru.',
            ];
        } catch (\Throwable $e) {
            log_message('error', '[Update] Migration failed: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function syncChangelogsFromData(): void
    {
        $syncFile = APPPATH . 'Database/changelogs_sync.php';
        if (!is_file($syncFile)) return;

        $logs = include $syncFile;
        if (is_array($logs)) {
            $db = \Config\Database::connect();
            $db->table('changelogs')->emptyTable();
            if (!empty($logs)) {
                $db->table('changelogs')->insertBatch($logs);
            }
        }
    }

    private function syncChangelogsToFile(): void
    {
        $db = \Config\Database::connect();
        $changelogs = $db->table('changelogs')->orderBy('release_date', 'DESC')->get()->getResultArray();

        if (empty($changelogs)) return;

        $output = "<?php\n\n";
        $output .= "// Auto-generated for sync. Created: " . date('Y-m-d H:i:s') . "\n";
        $output .= "// Total changelogs: " . count($changelogs) . "\n\n";
        $output .= "return " . var_export($changelogs, true) . ";\n";

        file_put_contents(APPPATH . 'Database/changelogs_sync.php', $output);
    }

    private function regenerateSecurityHashes(): void
    {
        $protectedFiles = [
            'Filters/LicenseFilter.php',
            'Models/LicenseModel.php',
            'Controllers/Activate.php',
            'Controllers/BaseController.php',
            'Controllers/Admin/License.php',
            'Libraries/LicenseGuard.php',
            'Libraries/LicenseProtector.php',
            'Helpers/license_helper.php',
            'Config/Filters.php',
            'Config/LService.php',
            'Config/Events.php',
        ];

        $hashes = [];
        foreach ($protectedFiles as $file) {
            $fullPath = APPPATH . $file;
            if (file_exists($fullPath)) {
                $hashes[$file] = sha1_file($fullPath);
            }
        }

        @file_put_contents(APPPATH . 'Config/.lic_hash', json_encode($hashes, JSON_PRETTY_PRINT));
        log_message('info', '[Update] Security hashes regenerated');
    }

    private function clearSessions(): void
    {
        try {
            $db = \Config\Database::connect();
            $db->query('TRUNCATE TABLE ci_sessions');
            log_message('info', '[Update] Sessions cleared');
        } catch (\Throwable $e) {
            log_message('warning', '[Update] Failed to clear sessions: ' . $e->getMessage());
        }
    }

    private function moveStagingToRoot(string $stagingDir): array
    {
        $copied = 0;
        $failed = [];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($stagingDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) continue;

            $relativePath = str_replace('\\', '/', substr($item->getPathname(), strlen($stagingDir)));
            $targetPath = ROOTPATH . $relativePath;
            $targetDir = dirname($targetPath);

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (@copy($item->getPathname(), $targetPath)) {
                $copied++;
            } else {
                $failed[] = $relativePath;
            }
        }

        return ['copied' => $copied, 'failed' => $failed];
    }

    private function cleanupStaging(string $stagingDir): void
    {
        if (is_dir($stagingDir)) {
            $this->deleteDirectory($stagingDir);
        }
    }

    private function buildUpdateResultPage(bool $success, string $message, array $details): string
    {
        $status = $success ? 'success' : 'danger';
        $icon = $success ? 'check-circle' : 'times-circle';
        $title = $success ? 'Update Berhasil!' : 'Update Gagal';

        $html = '<!DOCTYPE html><html lang="id"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        $html .= '<title>' . $title . ' - CBT Updater</title>';
        $html .= '<link href="' . base_url('assets/css/bootstrap.min.css') . '" rel="stylesheet">';
        $html .= '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">';
        $html .= '</head><body class="bg-light"><div class="container py-5"><div class="row justify-content-center"><div class="col-lg-8">';

        $html .= '<div class="card shadow"><div class="card-body text-center py-5">';
        $html .= '<i class="fas fa-' . $icon . ' text-' . $status . ' fa-4x mb-3"></i>';
        $html .= '<h3 class="text-' . $status . '">' . $title . '</h3>';
        $html .= '<p class="lead">' . esc($message) . '</p>';

        if ($success && !empty($details)) {
            $html .= '<div class="text-start mt-4"><div class="bg-light rounded p-3 small">';
            $html .= '<strong>Detail:</strong><ul class="mb-0 mt-2">';
            if (isset($details['downloaded'])) {
                $html .= '<li>File didownload: ' . $details['downloaded'] . '</li>';
            }
            if (isset($details['copy_result']['copied'])) {
                $html .= '<li>File dicopy ke root: ' . $details['copy_result']['copied'] . '</li>';
            }
            if (isset($details['migration_result']['message'])) {
                $html .= '<li>Migrasi: ' . esc($details['migration_result']['message']) . '</li>';
            }
            if (!empty($details['backup_dir'])) {
                $html .= '<li>Backup: <code>' . esc($details['backup_dir']) . '</code></li>';
            }
            if (!empty($details['version'])) {
                $html .= '<li>Versi baru: <strong>v' . esc($details['version']) . '</strong></li>';
            }
            if (!empty($details['download_errors'])) {
                $html .= '<li class="text-warning">File gagal download: ' . count($details['download_errors']) . '</li>';
            }
            $html .= '</ul></div></div>';
        }

        $html .= '<div class="mt-4">';
        $html .= '<a href="' . site_url('admin/updater') . '" class="btn btn-primary"><i class="fas fa-arrow-left me-2"></i>Kembali ke Updater</a> ';
        $html .= '<a href="' . site_url('login') . '" class="btn btn-outline-secondary"><i class="fas fa-sign-in-alt me-2"></i>Login Ulang</a>';
        $html .= '</div>';
        $html .= '</div></div></div></div></div></body></html>';

        return $html;
    }

    // ═══════════════════════════════════════════════════════════════════
    // CURL HELPER (langsung, bukan CI CURLRequest — lebih ringan)
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Simple curl GET request
     * @return string|false
     */
    private function curlGet(string $url)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_USERAGENT      => 'CBTKU-Updater/1.0',
        ]);
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return false;
        }
        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════
    // DATABASE BACKUP & RESTORE
    // ═══════════════════════════════════════════════════════════════════

    public function backupDatabase()
    {
        try {
            $backupDir = WRITEPATH . 'backups/';
            if (!is_dir($backupDir)) mkdir($backupDir, 0755, true);

            $filename = 'backup_' . date('Ymd_His') . '.sql';
            $filepath = $backupDir . $filename;
            $db = \Config\Database::connect();
            $tables = $db->listTables();

            $sql = "-- Database Backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n-- Database: {$db->database}\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

            foreach ($tables as $table) {
                $createRow = $db->query("SHOW CREATE TABLE `{$table}`")->getRowArray();
                $createSql = $createRow['Create Table'] ?? $createRow['Create View'] ?? '';
                if (empty($createSql)) continue;

                $sql .= "-- Table: {$table}\nDROP TABLE IF EXISTS `{$table}`;\n{$createSql};\n\n";
                $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();

                if (!empty($rows)) {
                    $sql .= "-- Data for table: {$table}\n";
                    foreach ($rows as $row) {
                        $values = array_map(function ($v) use ($db) {
                            return $v === null ? 'NULL' : "'" . $db->escapeString($v) . "'";
                        }, array_values($row));
                        $cols = array_map(fn($c) => "`{$c}`", array_keys($row));
                        $sql .= "INSERT INTO `{$table}` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sql .= "\n";
                }
            }
            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
            file_put_contents($filepath, $sql);
            return $this->response->download($filepath, null)->setFileName($filename);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal backup: ' . $e->getMessage());
        }
    }

    public function restoreDatabase()
    {
        $file = $this->request->getFile('sql_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        $ext = strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if ($ext !== 'sql') {
            return redirect()->back()->with('error', 'Hanya file .sql yang diterima.');
        }

        try {
            $content = file_get_contents($file->getTempName());
            if (strpos($content, '-- Database Backup') === false || strpos($content, 'SET FOREIGN_KEY_CHECKS=0') === false) {
                return redirect()->back()->with('error', 'File backup tidak valid atau bukan dari aplikasi ini.');
            }

            $db = \Config\Database::connect();
            $db->query('SET FOREIGN_KEY_CHECKS=0');
            $queries = $this->parseSQLFile($content);
            $ok = 0; $fail = 0; $lastErr = '';

            foreach ($queries as $q) {
                if (empty(trim($q))) continue;
                try { $db->query($q); $ok++; }
                catch (\Throwable $e) { $fail++; $lastErr = $e->getMessage(); }
            }
            $db->query('SET FOREIGN_KEY_CHECKS=1');

            if ($fail > 0) {
                return redirect()->back()->with('warning', "Restore selesai. Berhasil: {$ok}, Gagal: {$fail}. Error: " . substr($lastErr, 0, 100));
            }
            return redirect()->back()->with('success', "Database berhasil direstore! {$ok} query dijalankan.");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // DATABASE MIGRATIONS
    // ═══════════════════════════════════════════════════════════════════

    public function runMigrations()
    {
        try {
            $migrate = \Config\Services::migrations();
            $executed = $migrate->latest();
            $this->syncChangelogsFromData();

            if ($executed) {
                return redirect()->back()->with('success', 'Database berhasil dimigrasi ke versi terbaru.');
            }
            return redirect()->back()->with('info', 'Database sudah dalam versi terbaru.');
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal migrasi: ' . substr($e->getMessage(), 0, 200));
        }
    }

    // ═══════════════════════════════════════════════════════════════════
    // LEGACY: GENERATE PATCH ZIP + PATCH FILES (FALLBACK)
    // ═══════════════════════════════════════════════════════════════════

    public function generatePatch()
    {
        if (!defined('IS_DEVELOPER') || IS_DEVELOPER !== true) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }
        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'Extension PHP Zip tidak aktif.');
        }

        $newVersion = trim($this->request->getGet('new_version') ?? '');
        if (empty($newVersion) || !preg_match('/^\d+\.\d+(\.\d+)?$/', $newVersion)) {
            return redirect()->back()->with('error', 'Nomor versi tidak valid. Format: X.Y atau X.Y.Z');
        }

        // Sync changelogs
        try { $this->syncChangelogsToFile(); } catch (\Throwable $e) {}

        // Update version
        $constantsPath = APPPATH . 'Config/Constants.php';
        if (is_file($constantsPath)) {
            $content = file_get_contents($constantsPath);
            $newDate = date('Y-m-d H:i');
            $content = preg_replace("/define\('APP_VERSION',\s*'[^']+'\);/", "define('APP_VERSION', '{$newVersion}');", $content);
            $content = preg_replace("/define\('LAST_UPDATE',\s*'[^']*'\);/", "define('LAST_UPDATE', '{$newDate}');", $content);
            file_put_contents($constantsPath, $content);
        }

        $zipName = 'update_patch_' . $newVersion . '_' . date('Ymd_His') . '.zip';
        $zipPath = WRITEPATH . 'uploads/' . $zipName;
        if (!is_dir(WRITEPATH . 'uploads')) mkdir(WRITEPATH . 'uploads', 0755, true);

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuat file zip.');
        }

        $stats = ['total_files' => 0, 'excluded_files' => 0, 'total_size' => 0];
        foreach (['app', 'public'] as $folder) {
            $path = ROOTPATH . $folder;
            if (is_dir($path)) $this->addFolderToZip($path, $zip, strlen(ROOTPATH), $stats);
        }
        $zip->close();

        return $this->response->download($zipPath, null)->setFileName($zipName);
    }

    public function patchFiles()
    {
        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 'Extension PHP Zip tidak aktif.');
        }

        $file = $this->request->getFile('patch_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File upload tidak valid.');
        }

        $ext = strtolower(pathinfo($file->getClientName(), PATHINFO_EXTENSION));
        if ($ext !== 'zip') {
            return redirect()->back()->with('error', 'Hanya file .zip yang diterima.');
        }

        // Validate ZIP contents
        $validation = $this->validateZipContents($file->getTempName());
        if (!$validation['valid']) {
            return redirect()->back()->with('error', 'Keamanan: ' . $validation['error']);
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getTempName()) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuka file zip.');
        }

        // Get file list
        $filesToUpdate = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fn = $zip->getNameIndex($i);
            if (substr($fn, -1) !== '/') $filesToUpdate[] = $fn;
        }

        // Backup
        $backupDir = $this->backupCriticalFiles($filesToUpdate);
        $hasMigrations = $this->checkZipForMigrations($zip);

        // Extract
        $extractResult = $this->extractZipSafely($zip, ROOTPATH);
        $zip->close();

        if (!$extractResult['success']) {
            return redirect()->back()->with('warning', 'Update selesai dengan error. Gagal: ' . implode(', ', array_slice($extractResult['failed'], 0, 3)));
        }

        // License hash
        if ($this->checkLicenseFilesUpdated($extractResult['extracted'])) {
            $this->regenerateSecurityHashes();
        }

        // Migrations
        if ($hasMigrations) {
            $migResult = $this->autoMigrateAfterUpdate();
            if (!$migResult['success']) {
                return redirect()->back()->with('warning',
                    'File diperbarui (' . count($extractResult['extracted']) . '), tapi migrasi gagal: ' . $migResult['message']);
            }
        }

        $this->syncChangelogsFromData();
        $this->cleanupOldBackups(7);

        return redirect()->back()->with('success',
            'File berhasil diperbarui (' . count($extractResult['extracted']) . ' files). Backup: ' . basename($backupDir));
    }

    // ═══════════════════════════════════════════════════════════════════
    // ZIP & FILE UTILITIES
    // ═══════════════════════════════════════════════════════════════════

    private function addFolderToZip($dir, $zip, $exclusiveLength, &$stats = [])
    {
        $excludeByName = ['.htaccess', '.env', '.lic_hash', '.lic_checksum', '.lic_backup_hidden', '.lic_marker'];
        $excludeByPath = [
            'app/Filters/LicenseFilter.php', 'app/Models/LicenseModel.php',
            'app/Controllers/Activate.php', 'app/Controllers/BaseController.php',
            'app/Controllers/Admin/License.php', 'app/Libraries/LicenseGuard.php',
            'app/Libraries/LicenseProtector.php', 'app/Helpers/license_helper.php',
            'app/Config/Filters.php', 'app/Config/LService.php', 'app/Config/Events.php',
            'app/Controllers/FixLicense.php', 'app/Controllers/FixLicenseSecure.php',
        ];
        $excludeFolders = ['.git', 'node_modules', 'vendor', 'tests', '.vscode', '.idea'];

        $handle = opendir($dir);
        while (false !== ($f = readdir($handle))) {
            if ($f === '.' || $f === '..') continue;
            $filePath = "$dir/$f";
            $localPath = str_replace('\\', '/', substr($filePath, $exclusiveLength));

            // Skip folders
            $skip = false;
            foreach ($excludeFolders as $ef) {
                if (strpos($localPath, $ef . '/') !== false || basename($localPath) === $ef) { $skip = true; break; }
            }
            if ($skip) { $stats['excluded_files']++; continue; }

            if (is_file($filePath)) {
                $bn = basename($filePath);
                if (in_array($bn, $excludeByName) || strpos($bn, '.lic_') === 0) { $stats['excluded_files']++; continue; }
                if (in_array($localPath, $excludeByPath)) { $stats['excluded_files']++; continue; }

                $zip->addFile($filePath, $localPath);
                $stats['total_files']++;
                $stats['total_size'] += filesize($filePath);
            } elseif (is_dir($filePath)) {
                $zip->addEmptyDir($localPath);
                $this->addFolderToZip($filePath, $zip, $exclusiveLength, $stats);
            }
        }
        closedir($handle);
    }

    private function validateZipContents($zipPath): array
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== TRUE) return ['valid' => false, 'error' => 'Gagal membuka ZIP'];

        $allowed = ['app/', 'public/', 'modules/'];
        $blacklist = ['.env', '.htaccess', 'composer.json', 'spark'];
        $licFiles = ['.lic_hash', '.lic_checksum', '.lic_backup_hidden', '.lic_marker'];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fn = $zip->getNameIndex($i);
            if (substr($fn, -1) === '/') continue;

            $inAllowed = false;
            foreach ($allowed as $a) { if (strpos($fn, $a) === 0) { $inAllowed = true; break; } }
            if (!$inAllowed) { $zip->close(); return ['valid' => false, 'error' => "File di luar folder yang diizinkan: $fn"]; }

            $bn = basename($fn);
            $parts = explode('/', $fn);
            if (count($parts) == 2 && in_array($bn, $blacklist)) { $zip->close(); return ['valid' => false, 'error' => "File sensitif: $bn"]; }
            foreach ($licFiles as $lf) { if ($bn === $lf || strpos($bn, '.lic_') === 0) { $zip->close(); return ['valid' => false, 'error' => "File lisensi: $bn"]; } }
        }

        $zip->close();
        return ['valid' => true];
    }

    private function extractZipSafely($zip, $targetPath): array
    {
        $extracted = []; $failed = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fn = $zip->getNameIndex($i);
            if (substr($fn, -1) === '/') continue;

            $target = $targetPath . $fn;
            $dir = dirname($target);
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            $content = $zip->getFromIndex($i);
            if ($content === false || file_put_contents($target, $content) === false) {
                $failed[] = $fn;
            } else {
                $extracted[] = $fn;
            }
        }
        return ['success' => empty($failed), 'extracted' => $extracted, 'failed' => $failed];
    }

    private function checkZipForMigrations($zip): bool
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $fn = $zip->getNameIndex($i);
            if (strpos($fn, 'app/Database/Migrations/') === 0 && pathinfo($fn, PATHINFO_EXTENSION) === 'php') return true;
        }
        return false;
    }

    private function checkLicenseFilesUpdated(array $files): bool
    {
        $protected = [
            'app/Filters/LicenseFilter.php', 'app/Models/LicenseModel.php',
            'app/Controllers/Activate.php', 'app/Controllers/BaseController.php',
            'app/Controllers/Admin/License.php', 'app/Libraries/LicenseGuard.php',
            'app/Libraries/LicenseProtector.php', 'app/Helpers/license_helper.php',
            'app/Config/Filters.php', 'app/Config/LService.php', 'app/Config/Events.php',
        ];
        foreach ($files as $f) { if (in_array($f, $protected)) return true; }
        return false;
    }

    private function updateVersionConstant(string $newVersion): void
    {
        $path = APPPATH . 'Config/Constants.php';
        if (!file_exists($path)) return;

        $content = file_get_contents($path);
        $content = preg_replace("/define\('APP_VERSION',\s*'[^']+'\);/", "define('APP_VERSION', '{$newVersion}');", $content);
        $content = preg_replace("/define\('LAST_UPDATE',\s*'[^']*'\);/", "define('LAST_UPDATE', '" . date('Y-m-d H:i') . "');", $content);
        file_put_contents($path, $content);
    }

    private function getMigrationHistory(): array
    {
        try {
            return \Config\Services::migrations()->getHistory();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function getCurrentVersion(): string
    {
        return defined('APP_VERSION') ? APP_VERSION : '1.0.0';
    }

    private function parseSQLFile(string $sql): array
    {
        $queries = []; $current = '';
        foreach (explode("\n", $sql) as $line) {
            $line = trim($line);
            if ($line === '' || preg_match('/^(--)|(\/\*)|(\*)/', $line)) continue;
            $current .= $line . ' ';
            if (preg_match('/;$/', $line)) { $queries[] = trim($current); $current = ''; }
        }
        if (!empty(trim($current))) $queries[] = trim($current);
        return $queries;
    }

    private function cleanupOldBackups(int $keepDays = 7): void
    {
        $dir = WRITEPATH . 'backups/';
        if (!is_dir($dir)) return;
        $cutoff = time() - ($keepDays * 86400);
        foreach (glob($dir . 'pre_update_*', GLOB_ONLYDIR) as $folder) {
            if (filemtime($folder) < $cutoff) $this->deleteDirectory($folder);
        }
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->deleteDirectory($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($src)) return;
        if (!is_dir($dst)) mkdir($dst, 0755, true);

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $dst . '/' . substr($item->getPathname(), strlen($src) + 1);
            if ($item->isDir()) { if (!is_dir($target)) mkdir($target, 0755, true); }
            else { copy($item->getPathname(), $target); }
        }
    }
}
