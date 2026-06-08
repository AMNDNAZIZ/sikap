<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class Updater extends BaseController
{
    public function index()
    {
        $data = [
            'title' => 'System Updater',
            'migrations' => $this->getMigrationHistory()
        ];
        return view('admin/updater/index', $data);
    }

    /**
     * Handle File Patch (ZIP) with Auto-Migration
     */
    public function patchFiles()
    {
        // 🔒 SECURITY: Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with('error', 
                'Extension PHP Zip tidak aktif. Aktifkan di php.ini dengan menghapus tanda ; pada baris: extension=zip');
        }

        $file = $this->request->getFile('patch_file');

        // Enhanced validation with detailed error messages
        if (!$file) {
            return redirect()->back()->with('error', 'Tidak ada file yang diupload. Pastikan Anda memilih file ZIP.');
        }

        if (!$file->isValid()) {
            $error = $file->getErrorString() . ' (Error Code: ' . $file->getError() . ')';
            
            // Check common upload errors
            if ($file->getError() == UPLOAD_ERR_INI_SIZE || $file->getError() == UPLOAD_ERR_FORM_SIZE) {
                $maxSize = ini_get('upload_max_filesize');
                $error .= " File terlalu besar. Maksimal: {$maxSize}";
            }
            
            return redirect()->back()->with('error', 'File upload error: ' . $error);
        }

        // Get original filename for better validation
        $originalName = $file->getClientName();
        $clientExtension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $detectedExtension = $file->getExtension();
        
        // Check extension (both client and detected)
        if ($clientExtension !== 'zip' && $detectedExtension !== 'zip') {
            return redirect()->back()->with('error', 
                'File tidak valid. Harap upload file .zip (File Anda: ' . $originalName . ')');
        }
        
        // Additional check: Verify it's actually a ZIP file by checking magic bytes
        $tempPath = $file->getTempName();
        if (!$this->isValidZipFile($tempPath)) {
            return redirect()->back()->with('error', 
                'File bukan ZIP yang valid. File mungkin corrupt atau bukan file ZIP asli.');
        }

        // 🔒 SECURITY: Validate ZIP contents before extraction
        $validation = $this->validateZipContents($file->getTempName());
        if (!$validation['valid']) {
            return redirect()->back()->with('error', 'Keamanan: ' . $validation['error']);
        }

        // ✅ NEW: Check write permissions
        $permCheck = $this->checkWritePermissions();
        if (!$permCheck['writable']) {
            $folders = implode(', ', $permCheck['folders']);
            return redirect()->back()->with('error', 
                'Folder tidak writable: ' . $folders . '. Jalankan: chmod -R 775 app/ public/ writable/');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getTempName()) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuka file zip.');
        }

        // 🔹 STEP 1: Get list of files to update
        $filesToUpdate = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            if (substr($filename, -1) !== '/') {
                $filesToUpdate[] = $filename;
            }
        }

        // 🔹 STEP 2: Backup existing files
        try {
            $backupDir = $this->backupBeforeUpdate($filesToUpdate);
            log_message('info', 'Backup created at: ' . $backupDir);
        } catch (\Exception $e) {
            $zip->close();
            return redirect()->back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }

        // 🔹 STEP 3: Check if patch contains migrations
        $hasMigrations = $this->checkZipForMigrations($zip);
        
        // 🔹 STEP 4: Extract files with validation
        try {
            $extractResult = $this->extractZipSafely($zip, ROOTPATH);
            $zip->close();
            
            if (!$extractResult['success']) {
                $failedList = implode(', ', array_slice($extractResult['failed'], 0, 5));
                log_message('error', 'Some files failed to extract: ' . implode(', ', $extractResult['failed']));
                
                return redirect()->back()->with('warning', 
                    'Update selesai dengan beberapa error. File gagal: ' . $failedList . 
                    '. Backup tersimpan di: ' . basename($backupDir));
            }
            
            log_message('info', 'System files updated successfully. ' . count($extractResult['extracted']) . ' files extracted.');
            
            // 🔹 STEP 5: Check if license-protected files were updated
            $licenseFilesUpdated = $this->checkLicenseFilesUpdated($extractResult['extracted']);
            
            if ($licenseFilesUpdated) {
                log_message('info', 'License-protected files were updated, regenerating hash...');
                $hashRegenerated = $this->regenerateLicenseHash();
                
                if (!$hashRegenerated) {
                    log_message('warning', 'Failed to regenerate license hash. Run: php spark license:hashupdate');
                }
            }
            
            // 🔹 STEP 6: Auto-run migrations if patch contains them
            if ($hasMigrations) {
                $migrationResult = $this->autoRunMigrations();
                
                if ($migrationResult['success']) {
                    $message = 'File sistem berhasil diperbarui (' . count($extractResult['extracted']) . ' files). ' . $migrationResult['message'];
                    
                    if ($licenseFilesUpdated && $hashRegenerated) {
                        $message .= ' License hash telah di-regenerate otomatis.';
                    }
                    
                    return redirect()->back()->with('success', $message);
                } else {
                    $message = 'File sistem berhasil diperbarui, tetapi migrasi database gagal: ' . $migrationResult['error'];
                    $message .= ' Silakan jalankan migrasi manual dari menu "Jalankan Migrasi Database".';
                    $message .= ' Backup tersimpan di: ' . basename($backupDir);
                    return redirect()->back()->with('warning', $message);
                }
            }
            
            // 🔹 STEP 7: Cleanup old backups (keep last 7 days)
            $this->cleanupOldBackups(7);
            
            $message = 'File sistem berhasil diperbarui (' . count($extractResult['extracted']) . ' files). ' .
                'Backup tersimpan di: ' . basename($backupDir);
            
            if ($licenseFilesUpdated && $hashRegenerated) {
                $message .= ' License hash telah di-regenerate otomatis.';
            } elseif ($licenseFilesUpdated && !$hashRegenerated) {
                $message .= ' PERHATIAN: License hash gagal di-regenerate. Jalankan: php spark license:hashupdate';
            }
            
            return redirect()->back()->with('success', $message);
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to extract patch: ' . $e->getMessage());
            return redirect()->back()->with('error', 
                'Gagal mengekstrak: ' . $e->getMessage() . 
                '. Backup tersimpan di: ' . basename($backupDir ?? 'unknown'));
        }
    }

    /**
     * ✅ NEW: Check write permissions before update
     */
    private function checkWritePermissions()
    {
        $criticalFolders = [
            ROOTPATH . 'app/',
            ROOTPATH . 'public/',
            ROOTPATH . 'writable/',
        ];
        
        $notWritable = [];
        
        foreach ($criticalFolders as $folder) {
            if (!is_writable($folder)) {
                $notWritable[] = $folder;
            }
        }
        
        return [
            'writable' => empty($notWritable),
            'folders' => $notWritable
        ];
    }

    /**
     * ✅ NEW: Backup files before update
     */
    private function backupBeforeUpdate($filesToUpdate)
    {
        $backupDir = WRITEPATH . 'backups/pre_update_' . date('Ymd_His') . '/';
        
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        $backedUpCount = 0;
        
        foreach ($filesToUpdate as $file) {
            $sourcePath = ROOTPATH . $file;
            
            if (file_exists($sourcePath)) {
                $backupPath = $backupDir . $file;
                $backupFolder = dirname($backupPath);
                
                if (!is_dir($backupFolder)) {
                    mkdir($backupFolder, 0755, true);
                }
                
                if (copy($sourcePath, $backupPath)) {
                    $backedUpCount++;
                }
            }
        }
        
        log_message('info', "Backed up {$backedUpCount} files to: {$backupDir}");
        
        return $backupDir;
    }

    /**
     * ✅ NEW: Extract ZIP with validation and error handling
     */
    private function extractZipSafely($zip, $targetPath)
    {
        $extractedFiles = [];
        $failedFiles = [];
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip directories
            if (substr($filename, -1) === '/') {
                continue;
            }
            
            $targetFile = $targetPath . $filename;
            $targetDir = dirname($targetFile);
            
            // Create directory if not exists
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0755, true)) {
                    $failedFiles[] = $filename . ' (failed to create directory)';
                    continue;
                }
            }
            
            // Extract file
            $content = $zip->getFromIndex($i);
            
            if ($content === false) {
                $failedFiles[] = $filename . ' (failed to read from ZIP)';
                continue;
            }
            
            if (file_put_contents($targetFile, $content) === false) {
                $failedFiles[] = $filename . ' (failed to write)';
                continue;
            }
            
            $extractedFiles[] = $filename;
        }
        
        return [
            'success' => empty($failedFiles),
            'extracted' => $extractedFiles,
            'failed' => $failedFiles
        ];
    }

    /**
     * ✅ NEW: Validate ZIP file by checking magic bytes
     */
    private function isValidZipFile($filePath)
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return false;
        }
        
        // Read first 4 bytes
        $handle = fopen($filePath, 'rb');
        if (!$handle) {
            return false;
        }
        
        $bytes = fread($handle, 4);
        fclose($handle);
        
        if (strlen($bytes) < 4) {
            return false;
        }
        
        // ZIP file magic bytes: PK\x03\x04 (50 4B 03 04)
        // or PK\x05\x06 (empty ZIP)
        // or PK\x07\x08 (spanned ZIP)
        $magicBytes = unpack('C4', $bytes);
        
        // Check for standard ZIP signature
        if ($magicBytes[1] == 0x50 && $magicBytes[2] == 0x4B) {
            // Valid ZIP signature (PK)
            if (($magicBytes[3] == 0x03 && $magicBytes[4] == 0x04) ||  // Standard ZIP
                ($magicBytes[3] == 0x05 && $magicBytes[4] == 0x06) ||  // Empty ZIP
                ($magicBytes[3] == 0x07 && $magicBytes[4] == 0x08)) {  // Spanned ZIP
                return true;
            }
        }
        
        return false;
    }

    /**
     * ✅ NEW: Check if license-protected files were updated
     */
    private function checkLicenseFilesUpdated($extractedFiles)
    {
        $protectedFiles = [
            'app/Filters/LicenseFilter.php',
            'app/Models/LicenseModel.php',
            'app/Controllers/Activate.php',
            'app/Controllers/BaseController.php',
            'app/Controllers/Admin/License.php',
            'app/Libraries/LicenseGuard.php',
            'app/Libraries/LicenseProtector.php',
            'app/Helpers/license_helper.php',
            'app/Config/Filters.php',
            'app/Config/LService.php',
            'app/Config/Events.php'
        ];
        
        foreach ($extractedFiles as $file) {
            if (in_array($file, $protectedFiles)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * ✅ NEW: Regenerate license hash after updating protected files
     */
    private function regenerateLicenseHash()
    {
        try {
            // Check if LicenseGuard exists
            if (!class_exists('\App\Libraries\LicenseGuard')) {
                log_message('error', 'LicenseGuard class not found');
                return false;
            }
            
            // Get protected files list
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
                'Config/Events.php'
            ];
            
            $hashes = [];
            foreach ($protectedFiles as $file) {
                $fullPath = APPPATH . $file;
                
                if (file_exists($fullPath)) {
                    $hashes[$file] = sha1_file($fullPath);
                } else {
                    log_message('error', 'Protected file not found during hash regeneration: ' . $file);
                    return false;
                }
            }
            
            $hashFile = APPPATH . 'Config/.lic_hash';
            $json = json_encode($hashes, JSON_PRETTY_PRINT);
            
            // Write hash file
            $result = @file_put_contents($hashFile, $json);
            
            if ($result === false) {
                log_message('error', 'Failed to write hash file: permission denied');
                return false;
            }
            
            log_message('info', 'License hash regenerated successfully with ' . count($hashes) . ' files');
            
            // Also update checksum if LicenseProtector is available
            if (class_exists('\App\Libraries\LicenseProtector')) {
                try {
                    \App\Libraries\LicenseProtector::createBackup();
                    log_message('info', 'License backup updated after hash regeneration');
                } catch (\Exception $e) {
                    log_message('warning', 'Failed to update license backup: ' . $e->getMessage());
                }
            }
            
            return true;
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to regenerate license hash: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if ZIP contains migration files
     */
    private function checkZipForMigrations($zip)
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Check if file is in migrations directory
            if (strpos($filename, 'app/Database/Migrations/') === 0 && 
                pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Auto-run migrations with safe error handling
     */
    private function autoRunMigrations()
    {
        $migrate = \Config\Services::migrations();

        try {
            // Get current migration version before running
            $currentVersion = $this->getCurrentMigrationVersion();
            
            // Run migrations
            $executed = $migrate->latest();

            // Auto-sync changelogs if sync file exists
            $this->syncChangelogsFromData();

            if ($executed) {
                log_message('info', 'Database migrations auto-executed successfully by user: ' . session()->get('user_id'));
                return [
                    'success' => true,
                    'message' => 'Database berhasil dimigrasi ke versi terbaru.'
                ];
            } else {
                return [
                    'success' => true,
                    'message' => 'Database sudah dalam versi terbaru.'
                ];
            }
        } catch (\Throwable $e) {
            // Log error but don't fail the entire update
            $msg = $e->getMessage();
            log_message('error', 'Auto-migration failed: ' . $msg);
            
            // User-friendly error message
            $userMsg = $this->getMigrationErrorMessage($msg);
            
            return [
                'success' => false,
                'error' => $userMsg
            ];
        }
    }

    /**
     * Get current migration version
     */
    private function getCurrentMigrationVersion()
    {
        try {
            $db = \Config\Database::connect();
            $query = $db->query("SELECT version FROM migrations ORDER BY version DESC LIMIT 1");
            $result = $query->getRow();
            return $result ? $result->version : 0;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * Get user-friendly migration error message
     */
    private function getMigrationErrorMessage($msg)
    {
        if (stripos($msg, 'Duplicate column') !== false) {
            return 'Kolom sudah ada di database. Migrasi mungkin sudah pernah dijalankan sebelumnya.';
        } elseif (stripos($msg, 'Unknown column') !== false) {
            return 'Kolom tidak ditemukan. Pastikan urutan migrasi benar.';
        } elseif (stripos($msg, 'Table') !== false && stripos($msg, 'already exists') !== false) {
            return 'Tabel sudah ada. Migrasi mungkin sudah pernah dijalankan.';
        } elseif (stripos($msg, 'Syntax error') !== false) {
            return 'Kesalahan syntax SQL. Hubungi developer.';
        } elseif (stripos($msg, 'Unknown table') !== false) {
            return 'Tabel tidak ditemukan. Mungkin ada migrasi yang terlewat.';
        } else {
            return substr($msg, 0, 200);
        }
    }

    /**
     * Run Database Migrations
     */
    public function runMigrations()
    {
        $migrate = \Config\Services::migrations();

        try {
            $executed = $migrate->latest();

            // Auto-sync changelogs if sync file exists (carried from development)
            $this->syncChangelogsFromData();

            if ($executed) {
                log_message('info', 'Database migrations executed successfully by user: ' . session()->get('user_id'));
                return redirect()->back()->with('success', 'Database berhasil dimigrasi ke versi terbaru.');
            } else {
                return redirect()->back()->with('info', 'Database sudah dalam versi terbaru.');
            }
        } catch (\Throwable $e) {
            // Provide more context in error message
            $msg = $e->getMessage();
            $file = method_exists($e, 'getFile') ? basename($e->getFile()) : '';
            
            // User-friendly error messages based on common issues
            $userMsg = 'Gagal menjalankan migrasi database. ';
            
            if (stripos($msg, 'Duplicate column') !== false) {
                $userMsg .= 'Kolom sudah ada di database. Migrasi mungkin sudah pernah dijalankan sebelumnya.';
            } elseif (stripos($msg, 'Unknown column') !== false) {
                $userMsg .= 'Kolom tidak ditemukan. Pastikan urutan migrasi benar atau jalankan migrasi sebelumnya terlebih dahulu.';
            } elseif (stripos($msg, 'Table') !== false && stripos($msg, 'already exists') !== false) {
                $userMsg .= 'Tabel sudah ada. Migrasi mungkin sudah pernah dijalankan.';
            } elseif (stripos($msg, 'Syntax error') !== false) {
                $userMsg .= 'Kesalahan syntax SQL. Hubungi developer.';
            } else {
                $userMsg .= 'Error: ' . substr($msg, 0, 150);
            }
            
            if ($file) {
                $userMsg .= " (File: $file)";
            }
            
            // Log technical details
            log_message('error', 'Migration failed: ' . $msg . ' | File: ' . ($file ?: 'unknown'));
            
            return redirect()->back()->with('error', $userMsg);
        }
    }

    /**
     * Backup Database
     */
    public function backupDatabase()
    {
        try {
            // Ensure backup directory exists
            $backupDir = WRITEPATH . 'backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generate filename with timestamp
            $filename = 'backup_' . date('Ymd_His') . '.sql';
            $filepath = $backupDir . $filename;

            // Get database connection
            $db = \Config\Database::connect();
            $dbName = $db->database;

            // Get all tables
            $tables = $db->listTables();

            // Start building SQL content
            $sqlContent = "-- Database Backup\n";
            $sqlContent .= "-- Generated: " . date('Y-m-d H:i:s') . "\n";
            $sqlContent .= "-- Database: {$dbName}\n\n";
            $sqlContent .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Loop through each table
            foreach ($tables as $table) {
                // Get CREATE TABLE statement
                $createTableQuery = $db->query("SHOW CREATE TABLE `{$table}`")->getRow();
                $sqlContent .= "-- Table: {$table}\n";
                $sqlContent .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sqlContent .= $createTableQuery->{'Create Table'} . ";\n\n";

                // Get table data
                $rows = $db->query("SELECT * FROM `{$table}`")->getResultArray();

                if (!empty($rows)) {
                    $sqlContent .= "-- Data for table: {$table}\n";

                    foreach ($rows as $row) {
                        $values = array_map(function ($value) use ($db) {
                            if ($value === null) {
                                return 'NULL';
                            }
                            return "'" . $db->escapeString($value) . "'";
                        }, array_values($row));

                        $columns = array_map(function ($col) {
                            return "`{$col}`";
                        }, array_keys($row));

                        $sqlContent .= "INSERT INTO `{$table}` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
                    }
                    $sqlContent .= "\n";
                }
            }

            $sqlContent .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Write backup to file
            file_put_contents($filepath, $sqlContent);

            // Download the file
            return $this->response->download($filepath, null)->setFileName($filename);

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Gagal membuat backup: ' . $e->getMessage());
        }
    }

    /**
     * Restore Database from SQL file
     */
    public function restoreDatabase()
    {
        $file = $this->request->getFile('sql_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid. Harap upload file .sql');
        }

        // Get original filename and check extension
        $originalName = $file->getClientName();
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if ($extension !== 'sql') {
            return redirect()->back()->with('error', 'Hanya file .sql yang diperbolehkan. File Anda: ' . $originalName);
        }

        try {
            // Read SQL file content
            $sqlContent = file_get_contents($file->getTempName());

            if (empty($sqlContent)) {
                return redirect()->back()->with('error', 'File SQL kosong');
            }

            // 🔒 SECURITY: Verify backup signature
            if (!$this->verifyBackupSignature($sqlContent)) {
                return redirect()->back()->with('error', 
                    'File backup tidak valid atau bukan dari aplikasi ini. Pastikan file backup dibuat dari menu Backup Database.');
            }

            // Get database connection
            $db = \Config\Database::connect();

            // Disable foreign key checks
            $db->query('SET FOREIGN_KEY_CHECKS=0');

            // Parse SQL file properly
            $queries = $this->parseSQLFile($sqlContent);

            // Execute each query
            $successCount = 0;
            $errorCount = 0;
            $lastError = '';

            foreach ($queries as $query) {
                if (!empty(trim($query))) {
                    try {
                        $db->query($query);
                        $successCount++;
                    } catch (\Throwable $e) {
                        $errorCount++;
                        $lastError = $e->getMessage();
                    }
                }
            }

            // Re-enable foreign key checks
            $db->query('SET FOREIGN_KEY_CHECKS=1');

            log_message('info', "Database restored: {$successCount} queries executed, {$errorCount} failed");

            if ($errorCount > 0) {
                return redirect()->back()->with(
                    'warning',
                    "Database direstore dengan peringatan. Berhasil: {$successCount}, Gagal: {$errorCount} query. Error terakhir: " . substr($lastError, 0, 100)
                );
            }

            return redirect()->back()->with(
                'success',
                "Database berhasil direstore! {$successCount} query dijalankan."
            );

        } catch (\Throwable $e) {
            log_message('error', 'Database restore failed: ' . $e->getMessage());
            return redirect()->back()->with(
                'error',
                'Gagal restore database: ' . $e->getMessage()
            );
        }
    }

    /**
     * 🔒 SECURITY: Verify backup file signature
     */
    private function verifyBackupSignature($content)
    {
        // Check for our signature in comments
        $hasSignature = strpos($content, '-- Database Backup') !== false 
                     && strpos($content, '-- Database: ') !== false;
        
        return $hasSignature;
    }

    /**
     * 🔒 SECURITY: Validate ZIP contents before extraction
     */
    private function validateZipContents($zipPath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== TRUE) {
            return ['valid' => false, 'error' => 'Gagal membuka file ZIP'];
        }
        
        $allowedFolders = ['app/', 'public/', 'modules/'];
        
        // 🔒 SECURITY: Blacklist sensitive files (only at root level)
        $rootBlacklistFiles = [
            '.env', 
            '.env.production', 
            '.htaccess', 
            'composer.json', 
            'composer.lock',
            'spark'
        ];
        
        // 🔒 SECURITY: Blacklist license-related files (prevent tampering)
        $licenseFiles = [
            '.lic_hash',
            '.lic_checksum', 
            '.lic_backup_hidden',
            '.lic_marker',
            '.lic_backup'
        ];
        
        $suspiciousCount = 0;
        
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            // Skip directories
            if (substr($filename, -1) === '/') {
                continue;
            }
            
            // Check if in allowed folder
            $inAllowedFolder = false;
            foreach ($allowedFolders as $folder) {
                if (strpos($filename, $folder) === 0) {
                    $inAllowedFolder = true;
                    break;
                }
            }
            
            if (!$inAllowedFolder) {
                $zip->close();
                return ['valid' => false, 'error' => "File di luar folder yang diizinkan: $filename. Hanya app/, public/, modules/ yang diperbolehkan."];
            }
            
            $basename = basename($filename);
            
            // 🔒 SECURITY: Check root-level blacklist (only for files directly in allowed folders)
            // Allow index.php in subfolders like public/subfolder/index.php
            $pathParts = explode('/', $filename);
            $isRootLevel = (count($pathParts) == 2); // e.g., "public/index.php" or "app/index.php"
            
            if ($isRootLevel && in_array($basename, $rootBlacklistFiles)) {
                $zip->close();
                return ['valid' => false, 'error' => "File sensitif terdeteksi di root folder: $basename. File ini tidak boleh ada dalam patch."];
            }
            
            // 🔒 SECURITY: Check license files (anywhere)
            foreach ($licenseFiles as $licFile) {
                if ($basename === $licFile || strpos($basename, $licFile) !== false) {
                    $zip->close();
                    return ['valid' => false, 'error' => "File lisensi terdeteksi: $basename. File ini tidak boleh ada dalam patch untuk mencegah tampering."];
                }
            }
            
            // Check for PHP backdoor patterns (only for PHP files)
            if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                $content = $zip->getFromIndex($i);
                if ($content && $this->detectBackdoor($content)) {
                    $suspiciousCount++;
                    log_message('warning', "Suspicious code detected in: $filename");
                    
                    // Allow some suspicious patterns but warn
                    if ($suspiciousCount > 3) {
                        $zip->close();
                        return ['valid' => false, 'error' => "Terlalu banyak kode mencurigakan terdeteksi. File mungkin berbahaya."];
                    }
                }
            }
        }
        
        $zip->close();
        return ['valid' => true];
    }

    /**
     * 🔒 SECURITY: Detect potential backdoor patterns
     */
    private function detectBackdoor($content)
    {
        // Common backdoor patterns
        $dangerousPatterns = [
            '/eval\s*\(\s*\$_(GET|POST|REQUEST|COOKIE)/i',  // eval($_GET[...])
            '/system\s*\(\s*\$_(GET|POST|REQUEST)/i',       // system($_GET[...])
            '/exec\s*\(\s*\$_(GET|POST|REQUEST)/i',         // exec($_GET[...])
            '/shell_exec\s*\(\s*\$_(GET|POST|REQUEST)/i',   // shell_exec($_GET[...])
            '/passthru\s*\(\s*\$_(GET|POST|REQUEST)/i',     // passthru($_GET[...])
            '/base64_decode\s*\(\s*\$_(GET|POST|REQUEST)/i', // base64_decode($_GET[...])
            '/assert\s*\(\s*\$_(GET|POST|REQUEST)/i',       // assert($_GET[...])
            '/preg_replace.*\/e["\'].*\$_(GET|POST)/i',     // preg_replace with /e modifier
            '/create_function\s*\(.*\$_(GET|POST)/i',       // create_function with user input
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }
        
        // Check for obfuscated code (too many base64_decode, eval, etc.)
        $suspiciousCount = 0;
        $suspiciousCount += substr_count(strtolower($content), 'base64_decode');
        $suspiciousCount += substr_count(strtolower($content), 'eval(');
        $suspiciousCount += substr_count(strtolower($content), 'gzinflate');
        $suspiciousCount += substr_count(strtolower($content), 'str_rot13');
        
        // If too many suspicious functions, likely obfuscated
        if ($suspiciousCount > 5) {
            return true;
        }
        
        return false;
    }

    /**
     * Parse SQL file into individual queries
     */
    private function parseSQLFile($sql)
    {
        $queries = [];
        $currentQuery = '';
        $lines = explode("\n", $sql);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines
            if (empty($line)) {
                continue;
            }

            // Skip comment lines
            if (preg_match('/^--/', $line) || preg_match('/^\/\*/', $line) || preg_match('/^\*/', $line)) {
                continue;
            }

            // Add line to current query
            $currentQuery .= $line . ' ';

            // If line ends with semicolon, it's the end of a query
            if (preg_match('/;$/', $line)) {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            }
        }

        // Add last query if exists
        if (!empty(trim($currentQuery))) {
            $queries[] = trim($currentQuery);
        }

        return $queries;
    }

    /**
     * Generate Patch ZIP with automatic exclusion of sensitive files
     */
    public function generatePatch()
    {
        // Check if ZipArchive is available
        if (!class_exists('ZipArchive')) {
            return redirect()->back()->with(
                'error',
                'Extension PHP Zip tidak aktif. Aktifkan di php.ini dengan menghapus tanda ; pada baris: extension=zip'
            );
        }

        // Auto-sync changelogs to file before generating patch
        try {
            $this->syncChangelogsToFile();
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to sync changelogs to file: ' . $e->getMessage());
            // Continue anyway, don't block patch generation
        }

        $zipName = 'update_patch_' . date('Ymd_His') . '.zip';
        $zipPath = WRITEPATH . 'uploads/' . $zipName;

        // Ensure uploads directory exists
        if (!is_dir(WRITEPATH . 'uploads')) {
            mkdir(WRITEPATH . 'uploads', 0755, true);
        }

        // Automatically update LAST_UPDATE in Constants.php
        $constantsPath = APPPATH . 'Config/Constants.php';
        if (is_file($constantsPath)) {
            $content = file_get_contents($constantsPath);
            $newDate = date('Y-m-d H:i');
            $pattern = "/define\('LAST_UPDATE', '.*'\);/";
            $replacement = "define('LAST_UPDATE', '{$newDate}');";

            if (preg_match($pattern, $content)) {
                $newContent = preg_replace($pattern, $replacement, $content);
                file_put_contents($constantsPath, $newContent);
            }
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return redirect()->back()->with('error', 'Gagal membuat file zip di ' . $zipPath);
        }

        // Directories to include
        $folders = ['app', 'public', 'modules']; // Add 'modules' if you use HMVC
        
        // Statistics
        $stats = [
            'total_files' => 0,
            'excluded_files' => 0,
            'total_size' => 0
        ];

        foreach ($folders as $folder) {
            $path = ROOTPATH . $folder;
            if (is_dir($path)) {
                $this->addFolderToZip($path, $zip, strlen(ROOTPATH), $stats);
            }
        }

        $zip->close();
        
        // Log statistics
        log_message('info', sprintf(
            'Patch generated: %s | Files: %d | Excluded: %d | Size: %.2f MB',
            $zipName,
            $stats['total_files'],
            $stats['excluded_files'],
            $stats['total_size'] / 1024 / 1024
        ));

        return $this->response->download($zipPath, null)->setFileName($zipName);
    }

    /**
     * Add folder to ZIP with automatic exclusion of sensitive files
     * 
     * @param string $dir Directory path
     * @param \ZipArchive $zipArchive ZIP archive object
     * @param int $exclusiveLength Length to exclude from path
     * @param array &$stats Statistics array (passed by reference)
     */
    private function addFolderToZip($dir, $zipArchive, $exclusiveLength, &$stats = [])
    {
        // 🔒 SECURITY: Files to exclude (root-level sensitive files)
        $excludeFiles = [
            '.htaccess',
            '.env',
            '.env.production',
            '.env.local',
            '.env.development',
            '.env.testing',
            'composer.json',
            'composer.lock',
            'spark',
            'phpunit.xml',
            '.editorconfig',
            '.gitignore',
            '.gitattributes',
            'README.md',
            'LICENSE',
            'CHANGELOG.md',
            // 🔒 SECURITY: Exclude license files (prevent tampering)
            '.lic_hash',
            '.lic_checksum',
            '.lic_backup_hidden',
            '.lic_marker',
            '.lic_backup'
        ];
        
        // Folders to exclude
        $excludeFolders = [
            '.git',
            '.github',
            'node_modules',
            'vendor',
            'tests',
            '.vscode',
            '.idea'
        ];
        
        // Writable subfolders to exclude (keep structure but not content)
        $excludeWritableFolders = [
            'writable/cache',
            'writable/logs',
            'writable/session',
            'writable/uploads',
            'writable/debugbar',
            'writable/license_backup'  // 🔒 SECURITY: Exclude license backup folder
        ];

        $handle = opendir($dir);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..') {
                $filePath = "$dir/$f";
                $localPath = substr($filePath, $exclusiveLength);
                
                // Check if folder should be excluded
                $shouldExcludeFolder = false;
                foreach ($excludeFolders as $excludeFolder) {
                    if (strpos($localPath, $excludeFolder) !== false) {
                        $shouldExcludeFolder = true;
                        break;
                    }
                }
                
                // Check if writable subfolder should be excluded
                foreach ($excludeWritableFolders as $excludeWritable) {
                    if (strpos($localPath, $excludeWritable) !== false) {
                        $shouldExcludeFolder = true;
                        break;
                    }
                }
                
                if ($shouldExcludeFolder) {
                    if (isset($stats['excluded_files'])) {
                        $stats['excluded_files']++;
                    }
                    continue;
                }

                if (is_file($filePath)) {
                    $fileName = basename($filePath);
                    
                    // Check if file should be excluded
                    if (in_array($fileName, $excludeFiles)) {
                        if (isset($stats['excluded_files'])) {
                            $stats['excluded_files']++;
                        }
                        log_message('debug', "Excluded sensitive file: {$localPath}");
                        continue;
                    }
                    
                    // Add file to ZIP
                    $zipArchive->addFile($filePath, $localPath);
                    
                    // Update statistics
                    if (isset($stats['total_files'])) {
                        $stats['total_files']++;
                        $stats['total_size'] += filesize($filePath);
                    }
                    
                } elseif (is_dir($filePath)) {
                    // Add subfolder
                    $zipArchive->addEmptyDir($localPath);
                    $this->addFolderToZip($filePath, $zipArchive, $exclusiveLength, $stats);
                }
            }
        }
        closedir($handle);
    }

    private function getMigrationHistory()
    {
        $migrate = \Config\Services::migrations();
        try {
            return $migrate->getHistory();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Synchronize changelogs table from local data file (app/Database/changelogs_sync.php)
     */
    private function syncChangelogsFromData()
    {
        $syncFile = APPPATH . 'Database/changelogs_sync.php';

        if (!is_file($syncFile)) {
            return;
        }

        $logs = include $syncFile;

        if (is_array($logs)) {
            $db = \Config\Database::connect();
            $db->table('changelogs')->emptyTable(); // Clear existing
            if (!empty($logs)) {
                $db->table('changelogs')->insertBatch($logs); // Insert all
            }
        }
    }

    /**
     * Synchronize changelogs from database to file (for patch generation)
     * This ensures the sync file is always up-to-date before creating patch
     */
    private function syncChangelogsToFile()
    {
        $db = \Config\Database::connect();
        
        // Get all changelogs, ordered by release_date DESC
        $changelogs = $db->table('changelogs')
            ->orderBy('release_date', 'DESC')
            ->get()
            ->getResultArray();
        
        if (empty($changelogs)) {
            log_message('warning', 'No changelogs found in database to sync');
            return;
        }
        
        // Generate PHP array code
        $output = "<?php\n\n";
        $output .= "// This file is automatically generated for synchronization between development and production.\n";
        $output .= "// Created: " . date('Y-m-d H:i:s') . "\n";
        $output .= "// Total changelogs: " . count($changelogs) . "\n\n";
        $output .= "return " . var_export($changelogs, true) . ";\n";
        
        // Write to file
        $syncFile = APPPATH . 'Database/changelogs_sync.php';
        $result = file_put_contents($syncFile, $output);
        
        if ($result === false) {
            throw new \Exception("Failed to write changelogs sync file: {$syncFile}");
        }
        
        log_message('info', 'Changelogs synced to file successfully. Total: ' . count($changelogs));
    }

    /**
     * ✅ NEW: Cleanup old backups (keep only recent ones)
     */
    private function cleanupOldBackups($keepDays = 7)
    {
        $backupDir = WRITEPATH . 'backups/';
        
        if (!is_dir($backupDir)) {
            return;
        }
        
        $folders = glob($backupDir . 'pre_update_*', GLOB_ONLYDIR);
        $cutoffTime = time() - ($keepDays * 24 * 60 * 60);
        $deletedCount = 0;
        
        foreach ($folders as $folder) {
            if (filemtime($folder) < $cutoffTime) {
                $this->deleteDirectory($folder);
                log_message('info', 'Cleaned up old backup: ' . basename($folder));
                $deletedCount++;
            }
        }
        
        if ($deletedCount > 0) {
            log_message('info', "Cleaned up {$deletedCount} old backup(s)");
        }
    }

    /**
     * ✅ NEW: Delete directory recursively
     */
    private function deleteDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        
        rmdir($dir);
    }

    /**
     * ✅ NEW: Rollback to previous backup
     */
    public function rollback($backupFolder)
    {
        $backupPath = WRITEPATH . 'backups/' . $backupFolder . '/';
        
        if (!is_dir($backupPath)) {
            return redirect()->back()->with('error', 'Backup tidak ditemukan: ' . $backupFolder);
        }
        
        try {
            $this->restoreFromBackup($backupPath, ROOTPATH);
            
            log_message('info', 'System rolled back from: ' . $backupFolder . ' by user: ' . session()->get('user_id'));
            return redirect()->back()->with('success', 'Sistem berhasil di-rollback ke versi sebelumnya dari backup: ' . $backupFolder);
            
        } catch (\Exception $e) {
            log_message('error', 'Rollback failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal rollback: ' . $e->getMessage());
        }
    }

    /**
     * ✅ NEW: Restore files from backup
     */
    private function restoreFromBackup($backupPath, $targetPath)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        $restoredCount = 0;
        
        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($backupPath));
            $targetFile = $targetPath . $relativePath;
            
            if ($item->isDir()) {
                if (!is_dir($targetFile)) {
                    mkdir($targetFile, 0755, true);
                }
            } else {
                if (copy($item->getPathname(), $targetFile)) {
                    $restoredCount++;
                }
            }
        }
        
        log_message('info', "Restored {$restoredCount} files from backup");
    }

    /**
     * ✅ NEW: List available backups
     */
    public function listBackups()
    {
        $backupDir = WRITEPATH . 'backups/';
        
        if (!is_dir($backupDir)) {
            return $this->response->setJSON([
                'success' => true,
                'backups' => []
            ]);
        }
        
        $folders = glob($backupDir . 'pre_update_*', GLOB_ONLYDIR);
        $backups = [];
        
        foreach ($folders as $folder) {
            $backups[] = [
                'name' => basename($folder),
                'path' => $folder,
                'size' => $this->getDirectorySize($folder),
                'created' => date('Y-m-d H:i:s', filemtime($folder)),
                'age_days' => floor((time() - filemtime($folder)) / 86400)
            ];
        }
        
        // Sort by created date (newest first)
        usort($backups, function($a, $b) {
            return strcmp($b['created'], $a['created']);
        });
        
        return $this->response->setJSON([
            'success' => true,
            'backups' => $backups
        ]);
    }

    /**
     * ✅ NEW: Get directory size
     */
    private function getDirectorySize($dir)
    {
        $size = 0;
        
        if (!is_dir($dir)) {
            return $size;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        
        return $size;
    }
}
