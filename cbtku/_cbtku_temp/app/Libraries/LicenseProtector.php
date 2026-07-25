<?php

namespace App\Libraries;

use App\Models\LicenseModel;

/**
 * ============================================
 * LICENSE PROTECTOR - Multi-Layer Protection
 * ============================================
 * 
 * Proteksi berlapis untuk mencegah:
 * 1. Penghapusan data lisensi dari database
 * 2. Modifikasi file sistem lisensi
 * 3. Bypass validasi lisensi
 * 4. Truncate/drop table lisensi
 */
class LicenseProtector
{
    private static $backupPath = WRITEPATH . 'license_backup/';
    private static $checksumFile = APPPATH . 'Config/.lic_checksum';

    /**
     * Initialize protector - dipanggil saat bootstrap
     */
    public static function init()
    {
        // Pastikan folder backup ada
        if (!is_dir(self::$backupPath)) {
            mkdir(self::$backupPath, 0755, true);
        }

        // Create backup jika belum ada
        self::createBackup();

        // Verify integrity
        self::verifyIntegrity();

        // Setup database trigger (jika belum ada)
        self::setupDatabaseTrigger();
    }

    /**
     * Create encrypted backup of license data
     */
    public static function createBackup()
    {
        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            return false;
        }

        // Encrypt license data
        $encrypted = self::encrypt(json_encode($license));

        // Save to multiple locations
        $backupFiles = [
            self::$backupPath . '.lic_backup',
            self::$backupPath . '.lic_backup_' . date('Ymd'),
            APPPATH . 'Config/.lic_backup_hidden'
        ];

        $successCount = 0;
        foreach ($backupFiles as $file) {
            // 🔒 SECURITY: Temporarily remove hidden attribute on Windows to avoid 'Permission Denied'
            if (DIRECTORY_SEPARATOR === '\\' && file_exists($file)) {
                @exec("attrib -h " . escapeshellarg($file));
            }

            // 🔒 SECURITY: Use @ to suppress errors and check result
            $result = @file_put_contents($file, $encrypted);

            if ($result === false) {
                log_message('warning', 'Failed to create backup file: ' . $file);
                continue;
            }

            $successCount++;

            // Hide file (Windows)
            if (DIRECTORY_SEPARATOR === '\\') {
                @exec("attrib +h " . escapeshellarg($file));
            }
        }

        // Create checksum
        self::createChecksum($license);

        // Return true if at least one backup was created
        return $successCount > 0;
    }

    /**
     * Restore license from backup
     */
    public static function restoreFromBackup()
    {
        $backupFiles = [
            self::$backupPath . '.lic_backup',
            APPPATH . 'Config/.lic_backup_hidden',
            self::$backupPath . '.lic_backup_' . date('Ymd')
        ];

        foreach ($backupFiles as $file) {
            if (file_exists($file)) {
                try {
                    $encrypted = file_get_contents($file);
                    $decrypted = self::decrypt($encrypted);
                    $license = json_decode($decrypted, true);

                    if ($license && isset($license['license_key'])) {
                        $model = new LicenseModel();

                        // Check if license exists
                        $existing = $model->first();

                        if ($existing) {
                            $model->update($existing['id'], $license);
                        } else {
                            $model->insert($license);
                        }

                        log_message('critical', 'License restored from backup: ' . $file);
                        
                        // 🔒 SYNC: Immediately update checksum file after restoration
                        self::createChecksum($license);
                        
                        return true;
                    }
                } catch (\Exception $e) {
                    log_message('error', 'Failed to restore from ' . $file . ': ' . $e->getMessage());
                    continue;
                }
            }
        }

        return false;
    }

    /**
     * Verify license integrity
     */
    public static function verifyIntegrity()
    {
        $model = new LicenseModel();
        $license = $model->first();

        // Jika tidak ada lisensi, coba restore
        if (!$license) {
            log_message('warning', 'License not found in database, attempting restore...');
            $restored = self::restoreFromBackup();

            if (!$restored) {
                log_message('critical', 'License data missing and restore failed!');
                return false;
            }

            $license = $model->first();
        }

        // Verify checksum
        if (!self::verifyChecksum($license)) {
            log_message('critical', 'License checksum mismatch! Possible tampering detected.');

            // Auto-restore jika checksum tidak cocok
            self::restoreFromBackup();
            return false;
        }

        return true;
    }

    /**
     * Create checksum untuk deteksi perubahan
     */
    private static function createChecksum($license)
    {
        $data = [
            'license_key' => (string) ($license['license_key'] ?? ''),
            'machine_id' => (string) ($license['machine_id'] ?? ''),
            'expires_at' => (string) ($license['expires_at'] ?? ''),
            'created_at' => (string) ($license['created_at'] ?? '')
        ];

        // 🔒 FIX: Sort keys alphabetically to ensure consistent JSON string
        ksort($data);

        $checksum = hash('sha256', json_encode($data) . self::getSecret());

        // 🔒 SECURITY: Temporarily remove hidden attribute on Windows to avoid 'Permission Denied'
        if (DIRECTORY_SEPARATOR === '\\' && file_exists(self::$checksumFile)) {
            @exec("attrib -h " . escapeshellarg(self::$checksumFile));
        }

        // 🔒 SECURITY: Use @ to suppress errors and check result
        $result = @file_put_contents(self::$checksumFile, $checksum);

        if ($result === false) {
            log_message('warning', 'Failed to create checksum file: ' . self::$checksumFile);
            return false;
        }

        // Hide file (Windows)
        if (DIRECTORY_SEPARATOR === '\\') {
            @exec("attrib +h " . escapeshellarg(self::$checksumFile));
        }

        return true;
    }

    /**
     * Verify checksum
     */
    private static function verifyChecksum($license)
    {
        if (!file_exists(self::$checksumFile)) {
            self::createChecksum($license);
            return true;
        }

        $storedChecksum = file_get_contents(self::$checksumFile);

        $data = [
            'license_key' => $license['license_key'],
            'machine_id' => $license['machine_id'],
            'expires_at' => $license['expires_at'],
            'created_at' => $license['created_at']
        ];

        // 🔒 FIX: Sort keys alphabetically to ensure consistent JSON string (must match createChecksum)
        ksort($data);

        $currentChecksum = hash('sha256', json_encode($data) . self::getSecret());

        return $storedChecksum === $currentChecksum;
    }

    /**
     * Setup database trigger untuk mencegah DELETE/TRUNCATE
     */
    private static function setupDatabaseTrigger()
    {
        try {
            $db = \Config\Database::connect();

            // Check if trigger exists
            $query = $db->query("SHOW TRIGGERS LIKE 'app_license'");
            $triggers = $query->getResultArray();

            $hasDeleteTrigger = false;
            foreach ($triggers as $trigger) {
                if ($trigger['Trigger'] === 'prevent_license_delete') {
                    $hasDeleteTrigger = true;
                    break;
                }
            }

            // Create trigger jika belum ada
            if (!$hasDeleteTrigger) {
                // Trigger untuk mencegah DELETE
                $db->query("
                    CREATE TRIGGER prevent_license_delete
                    BEFORE DELETE ON app_license
                    FOR EACH ROW
                    BEGIN
                        -- Log attempt
                        INSERT INTO license_audit_log (action, license_key, timestamp)
                        VALUES ('DELETE_ATTEMPT', OLD.license_key, NOW());
                        
                        -- Prevent deletion by signaling error
                        SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'License deletion is not allowed. Contact support.';
                    END
                ");

                log_message('info', 'License protection trigger created successfully');
            }

            // Create audit log table jika belum ada
            if (!$db->tableExists('license_audit_log')) {
                $forge = \Config\Database::forge();
                $forge->addField([
                    'id' => [
                        'type' => 'INT',
                        'constraint' => 11,
                        'unsigned' => true,
                        'auto_increment' => true,
                    ],
                    'action' => [
                        'type' => 'VARCHAR',
                        'constraint' => 50,
                    ],
                    'license_key' => [
                        'type' => 'VARCHAR',
                        'constraint' => 255,
                    ],
                    'timestamp' => [
                        'type' => 'DATETIME',
                    ],
                ]);
                $forge->addKey('id', true);
                $forge->createTable('license_audit_log');
            }

        } catch (\Exception $e) {
            log_message('error', 'Failed to setup database trigger: ' . $e->getMessage());
        }
    }

    /**
     * Encrypt data
     */
    private static function encrypt($data)
    {
        $key = self::getSecret();
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }

    /**
     * Decrypt data
     */
    private static function decrypt($data)
    {
        $key = self::getSecret();
        list($encrypted, $iv) = explode('::', base64_decode($data), 2);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }

    /**
     * Get secret key
     */
    private static function getSecret()
    {
        $lsc = config('LService');
        return $lsc->getSecret();
    }

    /**
     * Monitor license table untuk perubahan mencurigakan
     */
    public static function monitorChanges()
    {
        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            // License hilang! Auto-restore
            log_message('critical', 'LICENSE DELETED! Attempting auto-restore...');

            $restored = self::restoreFromBackup();

            if ($restored) {
                log_message('info', 'License successfully restored from backup');

                // Send alert (optional - bisa kirim email ke pengembang)
                self::sendAlert('License was deleted and restored automatically');
            } else {
                log_message('critical', 'CRITICAL: License restore failed!');
                self::sendAlert('CRITICAL: License deleted and restore failed!');
            }
        }
    }

    /**
     * Send alert to developer (optional)
     */
    private static function sendAlert($message)
    {
        // Log to file
        $alertFile = WRITEPATH . 'logs/license_alerts.log';
        $alert = date('Y-m-d H:i:s') . " | " . $message . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n";
        file_put_contents($alertFile, $alert, FILE_APPEND);

        // Optional: Send email to developer
        // $email = \Config\Services::email();
        // $email->setTo('developer@sakasalika.com');
        // $email->setSubject('License Security Alert');
        // $email->setMessage($message);
        // $email->send();
    }

    /**
     * Create hidden marker files
     */
    public static function createMarkers()
    {
        $markers = [
            APPPATH . 'Config/.lic_marker',
            WRITEPATH . '.lic_marker',
            ROOTPATH . '.lic_marker'
        ];

        $markerData = hash('sha256', 'SakaSalika_License_Marker_' . date('Y-m-d'));

        foreach ($markers as $marker) {
            // 🔒 SECURITY: Temporarily remove hidden attribute on Windows to avoid 'Permission Denied'
            if (DIRECTORY_SEPARATOR === '\\' && file_exists($marker)) {
                @exec("attrib -h " . escapeshellarg($marker));
            }

            // 🔒 SECURITY: Use @ to suppress errors and check result
            $result = @file_put_contents($marker, $markerData);

            if ($result === false) {
                // Log error but don't break application
                log_message('warning', 'Failed to create marker file: ' . $marker);
                continue;
            }

            // Hide file (Windows only)
            if (DIRECTORY_SEPARATOR === '\\') {
                @exec("attrib +h " . escapeshellarg($marker));
            }
        }
    }

    /**
     * Verify marker files
     */
    public static function verifyMarkers()
    {
        $markers = [
            APPPATH . 'Config/.lic_marker',
            WRITEPATH . '.lic_marker',
            ROOTPATH . '.lic_marker'
        ];

        $expectedData = hash('sha256', 'SakaSalika_License_Marker_' . date('Y-m-d'));
        $validCount = 0;

        foreach ($markers as $marker) {
            if (!file_exists($marker)) {
                log_message('debug', 'License marker missing: ' . $marker);
                // Try to create it
                @file_put_contents($marker, $expectedData);

                // If still doesn't exist, skip but don't fail
                if (!file_exists($marker)) {
                    continue;
                }
            }

            $data = @file_get_contents($marker);
            if ($data === $expectedData) {
                $validCount++;
            } else {
                log_message('debug', 'License marker mismatch: ' . $marker);
                // Try to recreate
                @file_put_contents($marker, $expectedData);
            }
        }

        // 🔒 SECURITY: At least 1 valid marker is acceptable
        // This prevents permission issues from breaking the app
        return $validCount >= 1;
    }
}
