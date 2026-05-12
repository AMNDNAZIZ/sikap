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
     * Handle File Patch (ZIP) with Auto-Migrate
     * 
     * CRITICAL: This method uses maintenance mode to prevent race conditions
     * between file extraction and database migration.
     */
    public function patchFiles()
    {
        $file = $this->request->getFile('patch_file');

        if (!$file || !$file->isValid() || $file->getExtension() !== 'zip') {
            return redirect()->back()->with('error', 'File tidak valid. Harap upload file .zip');
        }

        $zip = new \ZipArchive();
        if ($zip->open($file->getTempName()) === TRUE) {
            try {
                // Step 0: Enable maintenance mode to prevent race conditions
                $this->enableMaintenanceMode();

                // Step 1: Read manifest (if exists)
                $manifest = $this->readUpdateManifest($zip);

                // Step 2: Backup critical files before extract
                $backupPath = $this->backupCriticalFiles();

                // Step 3: Extract files
                $zip->extractTo(ROOTPATH);
                $zip->close();

                // Step 4: IMMEDIATELY auto-migrate database (before any request can access new code)
                $migrateResult = $this->autoMigrateAfterUpdate();

                // Step 5: Regenerate security hashes
                $this->regenerateSecurityHashes();

                // Step 6: Disable maintenance mode
                $this->disableMaintenanceMode();

                // Build success message
                $message = 'File sistem berhasil diperbarui.';

                if ($manifest && isset($manifest['version'])) {
                    $message .= ' Version: ' . $manifest['version'] . '.';
                }

                if ($migrateResult['executed']) {
                    $message .= ' Database berhasil dimigrasi ke versi terbaru.';
                } elseif ($migrateResult['already_latest']) {
                    $message .= ' Database sudah dalam versi terbaru.';
                }

                if ($migrateResult['error']) {
                    $message .= ' PERINGATAN: ' . $migrateResult['error'];
                }

                log_message('info', 'System updated successfully. Backup: ' . $backupPath);

                return redirect()->back()->with('success', $message);

            } catch (\Exception $e) {
                // Disable maintenance mode on error
                $this->disableMaintenanceMode();

                log_message('error', 'Update failed: ' . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal mengekstrak: ' . $e->getMessage());
            }
        } else {
            return redirect()->back()->with('error', 'Gagal membuka file zip.');
        }
    }

    /**
     * Enable maintenance mode
     * Creates a marker file that triggers maintenance page for all users except admin
     */
    private function enableMaintenanceMode()
    {
        $maintenanceFile = WRITEPATH . '.maintenance';
        $data = [
            'enabled_at' => date('Y-m-d H:i:s'),
            'reason' => 'System update in progress',
            'admin_session' => session()->get('user_id'), // Allow current admin to continue
        ];
        file_put_contents($maintenanceFile, json_encode($data));
        log_message('info', 'Maintenance mode enabled for system update');
    }

    /**
     * Disable maintenance mode
     */
    private function disableMaintenanceMode()
    {
        $maintenanceFile = WRITEPATH . '.maintenance';
        if (is_file($maintenanceFile)) {
            unlink($maintenanceFile);
            log_message('info', 'Maintenance mode disabled');
        }
    }

    /**
     * Read update manifest from zip
     */
    private function readUpdateManifest($zipArchive)
    {
        try {
            $manifestContent = $zipArchive->getFromName('UPDATE_MANIFEST.json');
            if ($manifestContent) {
                return json_decode($manifestContent, true);
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Failed to read update manifest: ' . $e->getMessage());
        }
        return null;
    }

    /**
     * Backup critical files before update
     */
    private function backupCriticalFiles()
    {
        $backupDir = WRITEPATH . 'backups/pre_update_' . date('Ymd_His') . '/';

        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Files to backup
        $criticalFiles = [
            '.env',
            'app/Config/Database.php',
            'app/Config/App.php',
            'app/Config/.lic_hash',
            'app/Config/.security_hash',
            'writable/.install_id',
        ];

        foreach ($criticalFiles as $file) {
            $source = ROOTPATH . $file;
            if (is_file($source)) {
                $dest = $backupDir . $file;
                $destDir = dirname($dest);

                if (!is_dir($destDir)) {
                    mkdir($destDir, 0755, true);
                }

                copy($source, $dest);
            }
        }

        log_message('info', 'Critical files backed up to: ' . $backupDir);
        return $backupDir;
    }

    /**
     * Auto-migrate database after file update
     */
    private function autoMigrateAfterUpdate()
    {
        $result = [
            'executed' => false,
            'already_latest' => false,
            'error' => null
        ];

        try {
            $migrate = \Config\Services::migrations();
            $executed = $migrate->latest();

            if ($executed) {
                $result['executed'] = true;

                // Auto-sync changelogs if sync file exists
                $this->syncChangelogsFromData();

                log_message('info', 'Database auto-migrated successfully after file update');
            } else {
                $result['already_latest'] = true;
                log_message('info', 'Database already at latest version');
            }

        } catch (\Throwable $e) {
            $result['error'] = 'Migrasi gagal: ' . $e->getMessage();
            log_message('error', 'Auto-migration failed: ' . $e->getMessage());
        }

        return $result;
    }

    /**
     * Regenerate security hashes after file update
     */
    private function regenerateSecurityHashes()
    {
        try {
            // Regenerate .security_hash
            if (class_exists('\App\Libraries\SecurityGuard')) {
                \App\Libraries\SecurityGuard::regenerateHashes();
                log_message('info', 'Security hash regenerated after update');
            }

        } catch (\Throwable $e) {
            log_message('warning', 'Failed to regenerate hashes: ' . $e->getMessage());
            // Don't block update, just log warning
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
                return redirect()->back()->with('success', 'Database berhasil dimigrasi ke versi terbaru.');
            } else {
                return redirect()->back()->with('info', 'Database sudah dalam versi terbaru.');
            }
        } catch (\Throwable $e) {
            // Provide more context in error message
            $msg = $e->getMessage();
            $file = method_exists($e, 'getFile') ? basename($e->getFile()) : '';
            $errorMsg = "Gagal menjalankan migrasi: " . $msg;
            if ($file) {
                $errorMsg .= " (File: $file)";
            }

            return redirect()->back()->with('error', $errorMsg . '. Pastikan kolom database tidak ada yang duplikat atau buat migrasi yang defensif.');
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
                // Validate table name for security
                if (!$this->isValidTable($table)) {
                    log_message('warning', "Skipping invalid/unknown table: {$table}");
                    continue;
                }

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
            return redirect()->back()->with(
                'error',
                'Gagal restore database: ' . $e->getMessage()
            );
        }
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
     * Generate Patch ZIP (Localhost Only usage recommended)
     */
    /**
     * Get list of allowed tables for backup/restore operations
     * 
     * @return array
     */
    private function getAllowedTables(): array
    {
        return [
            'users',
            'students',
            'teachers',
            'classes',
            'subjects',
            'attendance',
            'grades',
            'assessments',
            'teaching_assignments',
            'academic_years',
            'holidays',
            'agendas',
            'announcements',
            'materials',
            'student_notes',
            'behaviors',
            'cbt_bank_soal',
            'cbt_questions',
            'cbt_test_status',
            'cbt_sessions',
            'cbt_answers',
            'exam_schedules',
            'app_license',
            'landing_sliders',
            'landing_links',
            'landing_facilities',
            'landing_articles',
            'landing_activities',
            'changelogs',
            'subject_scores',
            'announcement_targets',
            'ci_sessions',
            'student_academic_records',
            'promotions',
            'placements',
            'exam_attendance',
            'cbt_exam_names',
            'cbt_convert_nilai',
            'chat_rooms',
            'chat_messages',
            'student_locations',
        ];
    }

    /**
     * Validate if table name is allowed
     * 
     * @param string $table
     * @return bool
     */
    private function isValidTable(string $table): bool
    {
        return in_array($table, $this->getAllowedTables(), true);
    }

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
        $zipPath = FCPATH . 'uploads/backups/' . $zipName;

        // Ensure backups directory exists
        if (!is_dir(FCPATH . 'uploads/backups')) {
            mkdir(FCPATH . 'uploads/backups', 0755, true);
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

        // Step 1: Add folders (app, public, modules)
        $folders = ['app', 'public', 'modules'];
        foreach ($folders as $folder) {
            $path = ROOTPATH . $folder;
            if (is_dir($path)) {
                $this->addFolderToZip($path, $zip, strlen(ROOTPATH));
            }
        }

        // Step 2: Add root files (for root installation support)
        $rootFiles = [
            'index.php',      // For root installation
            '.htaccess',      // For root installation
            'spark',          // CLI tool
            'preload.php',    // PHP preload (if exists)
        ];

        foreach ($rootFiles as $file) {
            $filePath = ROOTPATH . $file;
            if (is_file($filePath)) {
                $zip->addFile($filePath, $file);
                log_message('info', "Added root file to patch: {$file}");
            }
        }

        // Step 3: Add update manifest
        $this->addUpdateManifest($zip);

        $zip->close();

        return $this->response->download($zipPath, null)->setFileName($zipName);
    }

    /**
     * Add update manifest to zip
     */
    private function addUpdateManifest($zipArchive)
    {
        $manifest = [
            'version' => defined('APP_VERSION') ? APP_VERSION : '1.0.0',
            'build_date' => date('Y-m-d H:i:s'),
            'build_by' => 'System Updater',
            'php_min_version' => '8.1',
            'includes' => [
                'folders' => ['app', 'public', 'modules'],
                'root_files' => ['index.php', '.htaccess', 'spark'],
            ],
            'migrations' => $this->getNewMigrations(),
            'features' => [
                'auto_migrate' => true,
                'auto_regenerate_hashes' => true,
                'backup_before_update' => true,
            ],
            'notes' => 'Auto-migrate enabled. Database will be updated automatically after file extraction.',
        ];

        $manifestJson = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        $zipArchive->addFromString('UPDATE_MANIFEST.json', $manifestJson);

        log_message('info', 'Update manifest added to patch. Migrations: ' . count($manifest['migrations']));
    }

    /**
     * Get list of new migrations (not yet run on current system)
     */
    private function getNewMigrations()
    {
        try {
            $migrate = \Config\Services::migrations();
            $history = $migrate->getHistory();

            // Get all migration files
            $migrationPath = APPPATH . 'Database/Migrations/';
            if (!is_dir($migrationPath)) {
                return [];
            }

            $files = glob($migrationPath . '*.php');

            $newMigrations = [];
            foreach ($files as $file) {
                $filename = basename($file);
                // Check if this migration is not in history
                $found = false;
                foreach ($history as $h) {
                    if (strpos($h->class, pathinfo($filename, PATHINFO_FILENAME)) !== false) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newMigrations[] = $filename;
                }
            }

            return $newMigrations;

        } catch (\Throwable $e) {
            log_message('warning', 'Failed to get new migrations: ' . $e->getMessage());
            return [];
        }
    }

    private function addFolderToZip($dir, $zipArchive, $exclusiveLength)
    {
        $handle = opendir($dir);
        while (false !== ($f = readdir($handle))) {
            if ($f != '.' && $f != '..') {
                $filePath = "$dir/$f";
                // Remove prefix from file path before adding to zip.
                $localPath = substr($filePath, $exclusiveLength);

                // Skip sensitive/heavy files
                if (strpos($filePath, '.git') !== false || strpos($filePath, 'node_modules') !== false) {
                    continue;
                }

                if (is_file($filePath)) {
                    $zipArchive->addFile($filePath, $localPath);
                } elseif (is_dir($filePath)) {
                    // Add subfolder
                    $zipArchive->addEmptyDir($localPath);
                    $this->addFolderToZip($filePath, $zipArchive, $exclusiveLength);
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
}
