<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\LicenseProtector;
use App\Models\LicenseModel;

/**
 * Command untuk verify integritas lisensi
 * 
 * Usage:
 * php spark license:verify
 */
class LicenseVerify extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:verify';
    protected $description = 'Verify license integrity and security';

    public function run(array $params)
    {
        CLI::write('License Integrity Verification', 'yellow');
        CLI::write('==============================', 'yellow');
        CLI::newLine();

        $allPassed = true;

        // 1. Check if license exists
        CLI::write('1. Checking license existence...', 'blue');
        $model = new LicenseModel();
        $license = $model->first();
        
        if ($license) {
            CLI::write('   ✓ License found in database', 'green');
        } else {
            CLI::write('   ✗ License NOT found in database', 'red');
            $allPassed = false;
        }

        // 2. Check backup files
        CLI::newLine();
        CLI::write('2. Checking backup files...', 'blue');
        $backupPath = WRITEPATH . 'license_backup/';
        $backupFiles = [
            $backupPath . '.lic_backup',
            $backupPath . '.lic_backup_' . date('Ymd'),
            APPPATH . 'Config/.lic_backup_hidden'
        ];

        $backupCount = 0;
        foreach ($backupFiles as $file) {
            if (file_exists($file)) {
                CLI::write('   ✓ Backup found: ' . basename($file), 'green');
                $backupCount++;
            }
        }

        if ($backupCount === 0) {
            CLI::write('   ✗ No backup files found', 'red');
            $allPassed = false;
        } else {
            CLI::write('   ✓ ' . $backupCount . ' backup file(s) found', 'green');
        }

        // 3. Check marker files
        CLI::newLine();
        CLI::write('3. Checking marker files...', 'blue');
        $markerValid = LicenseProtector::verifyMarkers();
        
        if ($markerValid) {
            CLI::write('   ✓ All marker files valid', 'green');
        } else {
            CLI::write('   ✗ Marker files missing or tampered', 'red');
            $allPassed = false;
        }

        // 4. Check integrity
        CLI::newLine();
        CLI::write('4. Checking data integrity...', 'blue');
        $integrityValid = LicenseProtector::verifyIntegrity();
        
        if ($integrityValid) {
            CLI::write('   ✓ License data integrity valid', 'green');
        } else {
            CLI::write('   ✗ License data integrity check failed', 'red');
            $allPassed = false;
        }

        // 5. Check database trigger
        CLI::newLine();
        CLI::write('5. Checking database protection...', 'blue');
        try {
            $db = \Config\Database::connect();
            $query = $db->query("SHOW TRIGGERS LIKE 'app_license'");
            $triggers = $query->getResultArray();
            
            $hasTrigger = false;
            foreach ($triggers as $trigger) {
                if ($trigger['Trigger'] === 'prevent_license_delete') {
                    $hasTrigger = true;
                    break;
                }
            }

            if ($hasTrigger) {
                CLI::write('   ✓ Database trigger active', 'green');
            } else {
                CLI::write('   ⚠ Database trigger not found (will be created on next request)', 'yellow');
            }
        } catch (\Exception $e) {
            CLI::write('   ✗ Error checking trigger: ' . $e->getMessage(), 'red');
            $allPassed = false;
        }

        // Summary
        CLI::newLine();
        CLI::write('==============================', 'yellow');
        if ($allPassed) {
            CLI::write('✓ All security checks PASSED', 'green');
        } else {
            CLI::write('✗ Some security checks FAILED', 'red');
            CLI::write('Run "php spark license:restore" to restore from backup', 'yellow');
        }

        // Show license info if exists
        if ($license) {
            CLI::newLine();
            CLI::write('License Information:', 'yellow');
            CLI::write('- Key: ' . $license['license_key']);
            CLI::write('- Domain: ' . ($license['domain'] ?? 'Not set'));
            CLI::write('- Machine ID: ' . substr($license['machine_id'] ?? '', 0, 16) . '...');
            CLI::write('- Expires: ' . ($license['expires_at'] ?? 'Lifetime'));
            CLI::write('- Status: ' . $license['status']);
            CLI::write('- Last Check: ' . ($license['last_check'] ?? 'Never'));
        }
    }
}
