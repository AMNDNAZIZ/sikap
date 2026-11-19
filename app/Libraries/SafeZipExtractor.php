<?php

namespace App\Libraries;

use RuntimeException;
use ZipArchive;

/**
 * SafeZipExtractionException
 * Exception khusus untuk pelanggaran keamanan ekstraksi ZIP.
 */
class SafeZipExtractionException extends RuntimeException {}

/**
 * SafeZipExtractor
 * 
 * Modul terpusat untuk inspeksi, validasi entri, staging, dan penerapan patch ZIP
 * dengan perlindungan terhadap:
 * - Zip Slip / Path Traversal (Unix & Windows)
 * - Penimpaan berkas terlindungi lisensi & konfigurasi rahasia
 * - ZIP Bomb (Ukuran, kuantitas file, rasio kompresi, kedalaman direktori)
 * - Symlink, device files, dan entri abnormal
 * - Tabrakan nama file / case-insensitive collision
 * - Rollback otomatis pada kegagalan parsial
 */
class SafeZipExtractor
{
    // Konfigurasi Batas Keamanan (Empiris Berbasis Paket ESPATA)
    public const MAX_ZIP_SIZE = 104857600;        // 100 MB
    public const MAX_FILES = 3000;                // 3.000 file
    public const MAX_TOTAL_UNCOMPRESSED = 157286400; // 150 MB
    public const MAX_FILE_SIZE = 15728640;        // 15 MB per file
    public const MAX_RATIO = 100;                 // 100:1 rasio kompresi maksimal
    public const MAX_DEPTH = 12;                  // 12 level kedalaman direktori

    /**
     * Whitelist direktori dan berkas akar yang diizinkan untuk patch
     */
    protected static array $allowedPrefixes = [
        'app/',
        'public/',
        'modules/',
    ];

    protected static array $allowedRootFiles = [
        'index.php',
        '.htaccess',
        'spark',
        'preload.php',
        'UPDATE_MANIFEST.json',
    ];

    /**
     * Blacklist mutlak: berkas terlindungi lisensi dan konfigurasi rahasia
     */
    protected static array $protectedFiles = [
        'app/helpers/license_helper.php',
        'app/libraries/licenseguard.php',
        'app/libraries/securityguard.php',
        'app/libraries/secureconfig.php',
        'app/filters/licensefilter.php',
        'app/models/licensemodel.php',
        'app/config/.security_hash',
        'app/config/.lic_hash',
        '.security_hash',
        '.lic_hash',
        '.env',
        'app/config/database.php',
        'app/config/encryption.php',
        'writable/.install_id',
    ];

    /**
     * Validasi dan sanitasi seluruh entri di dalam arsip ZIP sebelum ekstraksi.
     * Mengembalikan daftar entri yang telah divalidasi.
     *
     * @param ZipArchive $zip
     * @return array Daftar entri terverifikasi ['name' => ..., 'size' => ..., 'is_dir' => ...]
     * @throws SafeZipExtractionException
     */
    public static function sanitizeZipEntries(ZipArchive $zip): array
    {
        $numFiles = $zip->numFiles;
        if ($numFiles <= 0) {
            throw new SafeZipExtractionException('Arsip ZIP kosong atau tidak memiliki entri yang valid.');
        }

        if ($numFiles > self::MAX_FILES) {
            throw new SafeZipExtractionException(
                "Jumlah entri dalam ZIP ({$numFiles}) melebihi batas keamanan maksimum (" . self::MAX_FILES . " file)."
            );
        }

        $validatedEntries = [];
        $seenNormalizedPaths = [];
        $totalUncompressed = 0;

        for ($i = 0; $i < $numFiles; $i++) {
            $stat = $zip->statIndex($i);
            if ($stat === false || !isset($stat['name'])) {
                throw new SafeZipExtractionException("Gagal membaca metadata entri index #{$i}.");
            }

            $rawName = $stat['name'];
            $size = (int)($stat['size'] ?? 0);
            $compSize = (int)($stat['comp_size'] ?? 0);

            // 1. Cek Ukuran File & Akumulasi ZIP Bomb
            if ($size < 0) {
                throw new SafeZipExtractionException("Entri '{$rawName}' memiliki ukuran tidak valid.");
            }

            if ($size > self::MAX_FILE_SIZE) {
                throw new SafeZipExtractionException(
                    "Ukuran entri '{$rawName}' (" . round($size / 1048576, 2) . " MB) melebihi batas maksimum per file (" . (self::MAX_FILE_SIZE / 1048576) . " MB)."
                );
            }

            $totalUncompressed += $size;
            if ($totalUncompressed > self::MAX_TOTAL_UNCOMPRESSED) {
                throw new SafeZipExtractionException(
                    "Total ukuran dekompresi arsip melebihi batas maksimum (" . (self::MAX_TOTAL_UNCOMPRESSED / 1048576) . " MB)."
                );
            }

            // 2. Cek Rasio Kompresi (ZIP Bomb Ratio Check)
            if ($compSize > 0 && $size > 1024) {
                $ratio = $size / $compSize;
                if ($ratio > self::MAX_RATIO) {
                    throw new SafeZipExtractionException(
                        "Entri '{$rawName}' memiliki rasio kompresi mencurigakan ({$ratio}:1) yang melebihi batas aman (" . self::MAX_RATIO . ":1)."
                    );
                }
            }

            // 3. Deteksi Symlink (Unix external attributes)
            if (isset($stat['opsys']) && $stat['opsys'] === ZipArchive::OPSYS_UNIX && isset($stat['external_attributes'])) {
                $attr = ($stat['external_attributes'] >> 16) & 0177777;
                if (($attr & 0120000) === 0120000) {
                    throw new SafeZipExtractionException("Entri '{$rawName}' terdeteksi sebagai symbolic link yang dilarang.");
                }
            }

            // 4. Validasi Karakter & Path Traversal
            $normalized = self::validateAndNormalizePath($rawName);

            // 5. Cek Duplikasi / Tabrakan Nama (Case-insensitive)
            $lowerKey = strtolower($normalized);
            if (isset($seenNormalizedPaths[$lowerKey])) {
                throw new SafeZipExtractionException(
                    "Ditemukan entri duplikat atau tabrakan nama file: '{$rawName}' dengan '{$seenNormalizedPaths[$lowerKey]}'."
                );
            }
            $seenNormalizedPaths[$lowerKey] = $rawName;

            $isDir = str_ends_with($rawName, '/') || str_ends_with($rawName, '\\');

            // 6. Blacklist File Terlindungi Lisensi & Kredensial (Prioritas Keamanan Tertinggi)
            if (self::isProtectedFile($normalized)) {
                throw new SafeZipExtractionException(
                    "PELANGGARAN KEAMANAN KRITIS: Entri '{$rawName}' mencoba menimpa berkas terlindungi lisensi/sistem."
                );
            }

            // 7. Whitelist Direktori / Root Files
            if (!self::isAllowedPath($normalized, $isDir)) {
                throw new SafeZipExtractionException(
                    "Entri '{$rawName}' berada di luar direktori aman yang diizinkan (hanya app/, public/, modules/, dan root files rilis)."
                );
            }

            $validatedEntries[] = [
                'index'      => $i,
                'raw_name'   => $rawName,
                'clean_path' => $normalized,
                'size'       => $size,
                'is_dir'     => $isDir,
            ];
        }

        return $validatedEntries;
    }

    /**
     * Normalisasi dan validasi mendalam setiap segmen path
     *
     * @param string $path
     * @return string Path ternormalisasi dengan separator '/'
     * @throws SafeZipExtractionException
     */
    public static function validateAndNormalizePath(string $path): string
    {
        // Tolak null byte
        if (strpos($path, "\0") !== false) {
            throw new SafeZipExtractionException("Nama entri mengandung karakter null byte: {$path}");
        }

        // Tolak karakter kontrol
        if (preg_match('/[\x00-\x1F\x7F]/', $path)) {
            throw new SafeZipExtractionException("Nama entri mengandung karakter kontrol ilegal: {$path}");
        }

        // Seragamkan separator ke '/'
        $clean = str_replace('\\', '/', trim($path));

        // Tolak absolute path Unix atau Windows drive atau UNC
        if (str_starts_with($clean, '/') || preg_match('/^[a-zA-Z]:/', $clean) || str_starts_with($clean, '//')) {
            throw new SafeZipExtractionException("Path absolut / drive letter terdeteksi dilarang: {$path}");
        }

        // Tolak traversal eksplisit
        if (strpos($clean, '../') !== false || strpos($clean, '/..') !== false || $clean === '..') {
            throw new SafeZipExtractionException("Pola path traversal '../' terdeteksi: {$path}");
        }

        $segments = explode('/', $clean);
        $safeSegments = [];

        foreach ($segments as $segment) {
            // Abaikan segmen kosong akibat multiple slashes
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                throw new SafeZipExtractionException("Segmen direktori parent '..' dilarang: {$path}");
            }

            // Tolak nama file reserved di Windows
            if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])(\..*)?$/i', $segment)) {
                throw new SafeZipExtractionException("Nama entri mengandung reserved device name Windows: {$segment}");
            }

            // Tolak karakter terlarang Windows
            if (preg_match('/[<>:"|?*]/', $segment)) {
                throw new SafeZipExtractionException("Nama entri mengandung karakter terlarang sistem file: {$segment}");
            }

            $safeSegments[] = $segment;
        }

        if (empty($safeSegments)) {
            throw new SafeZipExtractionException("Path entri kosong atau tidak valid: {$path}");
        }

        if (count($safeSegments) > self::MAX_DEPTH) {
            throw new SafeZipExtractionException(
                "Kedalaman struktur direktori (" . count($safeSegments) . ") melebihi batas maksimum (" . self::MAX_DEPTH . "): {$path}"
            );
        }

        $normalized = implode('/', $safeSegments);
        if (str_ends_with($clean, '/')) {
            $normalized .= '/';
        }

        return $normalized;
    }

    /**
     * Periksa apakah path berada dalam whitelist direktori atau berkas yang diizinkan
     */
    public static function isAllowedPath(string $normalizedPath, bool $isDir): bool
    {
        $clean = strtolower(ltrim($normalizedPath, '/'));

        foreach (self::$allowedPrefixes as $prefix) {
            if (str_starts_with($clean, strtolower($prefix))) {
                return true;
            }
        }

        // Berkas / direktori akar spesifik
        $rootTarget = rtrim($clean, '/');
        $lowerRootFiles = array_map('strtolower', self::$allowedRootFiles);
        if (in_array($rootTarget, $lowerRootFiles, true)) {
            return true;
        }

        // Direktori akar yang sah (app, public, modules)
        if ($isDir && in_array($rootTarget, ['app', 'public', 'modules'], true)) {
            return true;
        }

        return false;
    }

    /**
     * Periksa apakah path menargetkan file terlindungi lisensi atau rahasia sistem
     */
    public static function isProtectedFile(string $normalizedPath): bool
    {
        $cleanLower = strtolower(rtrim(ltrim($normalizedPath, '/'), '/'));

        return in_array($cleanLower, self::$protectedFiles, true);
    }

    /**
     * Ekstraksi aman bertahap (Staging) dari file ZIP yang telah divalidasi.
     * Menggunakan streaming chunked dan akumulator kumulatif untuk membatasi
     * ukuran file individu serta total dekompresi aktual seluruh arsip.
     * Menggunakan verified write loop untuk menjamin integritas penulisan fisik ke disk.
     *
     * @param ZipArchive $zip
     * @param array $validatedEntries
     * @param string $stagingBaseDir Direktori staging terisolasi di bawah WRITEPATH
     * @param int $maxTotalSize Batas akumulasi ukuran dekompresi riil seluruh arsip (default: 150 MB)
     * @param int $maxFileSize Batas ukuran dekompresi riil per file (default: 15 MB)
     * @param callable|null $chunkWriter Callback custom writer untuk pengujian deterministik (default: null)
     * @return array Daftar file yang berhasil diekstrak ke staging (path relatif)
     * @throws SafeZipExtractionException
     */
    public static function extractToStaging(
        ZipArchive $zip,
        array $validatedEntries,
        string $stagingBaseDir,
        int $maxTotalSize = self::MAX_TOTAL_UNCOMPRESSED,
        int $maxFileSize = self::MAX_FILE_SIZE,
        ?callable $chunkWriter = null
    ): array {
        $stagingReal = realpath($stagingBaseDir);
        if ($stagingReal === false || !is_dir($stagingReal)) {
            throw new SafeZipExtractionException("Direktori staging tidak valid atau tidak ditemukan: {$stagingBaseDir}");
        }

        $extractedFiles = [];
        $totalActualBytesWritten = 0;

        try {
            foreach ($validatedEntries as $entry) {
                $relPath = $entry['clean_path'];
                $destPath = $stagingReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

                if ($entry['is_dir']) {
                    if (!is_dir($destPath) && !mkdir($destPath, 0755, true)) {
                        throw new SafeZipExtractionException("Gagal membuat direktori staging: {$relPath}");
                    }
                    continue;
                }

                $parentDir = dirname($destPath);
                if (!is_dir($parentDir) && !mkdir($parentDir, 0755, true)) {
                    throw new SafeZipExtractionException("Gagal membuat direktori parent: {$parentDir}");
                }

                // Ekstraksi via safe stream writing
                $stream = $zip->getStream($entry['raw_name']);
                if ($stream === false) {
                    throw new SafeZipExtractionException("Gagal membuka stream ZIP untuk entri: {$entry['raw_name']}");
                }

                $outFp = fopen($destPath, 'wb');
                if ($outFp === false) {
                    fclose($stream);
                    throw new SafeZipExtractionException("Gagal menulis berkas ke staging: {$destPath}");
                }

                $bytesWritten = 0;
                while (!feof($stream)) {
                    $chunk = fread($stream, 65536);
                    if ($chunk === false) {
                        fclose($stream);
                        fclose($outFp);
                        @unlink($destPath);
                        throw new SafeZipExtractionException("Gagal membaca chunk stream: {$relPath}");
                    }

                    $chunkLen = strlen($chunk);
                    if ($chunkLen === 0) {
                        continue;
                    }

                    // 1. Cek batas ukuran file tunggal sebelum penulisan ke disk
                    if (($bytesWritten + $chunkLen) > $maxFileSize) {
                        fclose($stream);
                        fclose($outFp);
                        @unlink($destPath);
                        throw new SafeZipExtractionException(
                            "Ukuran riil file melebihi batas maksimum pada saat ekstraksi (" . round($maxFileSize / 1048576, 2) . " MB): {$relPath}"
                        );
                    }

                    // 2. Cek batas dekompresi kumulatif seluruh arsip sebelum penulisan ke disk
                    if (($totalActualBytesWritten + $chunkLen) > $maxTotalSize) {
                        fclose($stream);
                        fclose($outFp);
                        @unlink($destPath);
                        throw new SafeZipExtractionException(
                            "Total ukuran dekompresi aktual arsip melebihi batas maksimum kumulatif (" . round($maxTotalSize / 1048576, 2) . " MB): {$relPath}"
                        );
                    }

                    // 3. Verified Write Loop: tulis seluruh byte chunk dengan verifikasi offset
                    try {
                        self::writeVerifiedChunk($outFp, $chunk, $relPath, $chunkWriter);
                    } catch (\Throwable $writeEx) {
                        fclose($stream);
                        fclose($outFp);
                        @unlink($destPath);
                        throw $writeEx;
                    }

                    // Tambah penghitung hanya setelah chunk berhasil ditulis utuh ke disk
                    $bytesWritten += $chunkLen;
                    $totalActualBytesWritten += $chunkLen;
                }

                fclose($stream);
                fclose($outFp);

                // Verifikasi pasca-tulis: pastikan realpath tetap di dalam staging
                $writtenReal = realpath($destPath);
                if ($writtenReal === false || !str_starts_with($writtenReal, $stagingReal)) {
                    @unlink($destPath);
                    throw new SafeZipExtractionException("Pelanggaran batas staging terdeteksi pada file: {$relPath}");
                }

                $extractedFiles[] = $relPath;
            }
        } catch (\Throwable $e) {
            // Bersihkan seluruh direktori staging pada kegagalan ekstraksi
            self::cleanupDirectory($stagingBaseDir);
            throw ($e instanceof SafeZipExtractionException) ? $e : new SafeZipExtractionException("Kegagalan ekstraksi staging: " . $e->getMessage(), 0, $e);
        }

        return $extractedFiles;
    }

    /**
     * Tulis seluruh byte data chunk ke file handle target secara terverifikasi
     * menggunakan offset loop, mendeteksi partial write, dan menolak false / 0 / nilai tidak valid.
     *
     * @param resource $outFp Handle file tujuan
     * @param string $chunk Data biner yang akan ditulis
     * @param string $relPath Path relatif file untuk konteks exception
     * @param callable|null $customWriter Opsional: callback custom writer untuk pengujian deterministik
     * @return int Jumlah byte yang berhasil ditulis (harus sama dengan strlen($chunk))
     * @throws SafeZipExtractionException
     */
    public static function writeVerifiedChunk($outFp, string $chunk, string $relPath, ?callable $customWriter = null): int
    {
        $chunkLen = strlen($chunk);
        $offset = 0;

        while ($offset < $chunkLen) {
            $remaining = substr($chunk, $offset);

            if ($customWriter !== null) {
                $n = $customWriter($outFp, $remaining, $offset, $chunkLen);
            } else {
                $n = fwrite($outFp, $remaining);
            }

            if ($n === false || $n === 0) {
                throw new SafeZipExtractionException(
                    "Kegagalan penulisan file staging (disk error / zero bytes written): {$relPath}"
                );
            }

            if (!is_int($n) || $n < 1 || $n > ($chunkLen - $offset)) {
                throw new SafeZipExtractionException(
                    "Hasil penulisan file staging tidak valid: {$relPath}"
                );
            }

            $offset += $n;
        }

        return $offset;
    }

    /**
     * Backup file target di ROOTPATH yang akan ditimpa sebelum proses apply.
     *
     * @param array $filesToApply Daftar path relatif file
     * @param string $backupDir Direktori backup
     * @return array Metadata backup ['backed_up' => [...], 'new_files' => [...]]
     */
    public static function backupTargetFiles(array $filesToApply, string $backupDir): array
    {
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $backedUp = [];
        $newFiles = [];

        foreach ($filesToApply as $relPath) {
            $rootTarget = ROOTPATH . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (is_file($rootTarget)) {
                $backupDest = $backupDir . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                $destDir = dirname($backupDest);
                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }
                if (copy($rootTarget, $backupDest)) {
                    $backedUp[] = $relPath;
                }
            } else {
                $newFiles[] = $relPath;
            }
        }

        return [
            'backed_up' => $backedUp,
            'new_files' => $newFiles,
        ];
    }

    /**
     * Terapkan file dari staging ke ROOTPATH secara terverifikasi.
     *
     * @param string $stagingDir
     * @param array $files
     * @param string $backupDir
     * @throws SafeZipExtractionException
     */
    public static function applyStagingToRoot(string $stagingDir, array $files, string $backupDir): array
    {
        $stagingReal = realpath($stagingDir);
        $rootReal = realpath(ROOTPATH);
        $applied = [];

        try {
            foreach ($files as $relPath) {
                // Pastikan tidak ada berkas terlindungi yang lolos
                if (self::isProtectedFile($relPath)) {
                    throw new SafeZipExtractionException("Tolak penulisan: berkas terlindungi {$relPath}");
                }

                $source = $stagingReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
                $dest = ROOTPATH . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

                if (!is_file($source)) {
                    throw new SafeZipExtractionException("Berkas staging tidak ditemukan: {$relPath}");
                }

                $destDir = dirname($dest);
                if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
                    throw new SafeZipExtractionException("Gagal membuat direktori tujuan: {$destDir}");
                }

                if (!copy($source, $dest)) {
                    throw new SafeZipExtractionException("Gagal menyalin berkas ke target: {$relPath}");
                }

                $applied[] = $relPath;
            }

            // Verifikasi Integritas Lisensi Langsung Setelah Terapkan
            if (class_exists(\App\Libraries\SecurityGuard::class)) {
                $secPass = \App\Libraries\SecurityGuard::verifyCriticalFiles();
                if ($secPass !== true && $secPass !== 1) {
                    throw new SafeZipExtractionException("Integritas SecurityGuard gagal pasca-update (SEC_PASS !== 1).");
                }
            }

            return $applied;

        } catch (\Throwable $e) {
            // Picu Rollback Otomatis
            self::rollback($backupDir, $applied);
            throw new SafeZipExtractionException("Penerapan patch gagal, rollback dijalankan: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Rollback file-file yang sempat diubah dari direktori backup.
     *
     * @param string $backupDir
     * @param array $appliedFiles
     */
    public static function rollback(string $backupDir, array $appliedFiles): void
    {
        log_message('critical', '[SafeZipExtractor] Memulai rollback darurat dari: ' . $backupDir);

        foreach ($appliedFiles as $relPath) {
            $backupFile = $backupDir . str_replace('/', DIRECTORY_SEPARATOR, $relPath);
            $rootTarget = ROOTPATH . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

            if (is_file($backupFile)) {
                // Kembalikan file dari backup
                copy($backupFile, $rootTarget);
                log_message('info', "[SafeZipExtractor] Rollback restored: {$relPath}");
            } else {
                // File ini sebelumnya tidak ada, hapus file baru yang sempat dibuat
                if (is_file($rootTarget)) {
                    @unlink($rootTarget);
                    log_message('info', "[SafeZipExtractor] Rollback deleted new file: {$relPath}");
                }
            }
        }
    }

    /**
     * Hapus direktori staging beserta isinya secara rekursif
     */
    public static function cleanupDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                @rmdir($item->getRealPath());
            } else {
                @unlink($item->getRealPath());
            }
        }

        @rmdir($dir);
    }
}
