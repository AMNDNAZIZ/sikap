<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\LicenseModel;

/**
 * Command untuk regenerate checksum file
 * 
 * Usage:
 * php spark license:fix-checksum
 */
class LicenseFixChecksum extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:fix-checksum';
    protected $description = 'Regenerate license checksum file (fix integrity check)';

    public function run(array $params)
    {
        CLI::write('License Checksum Fix', 'yellow');
        CLI::write('====================', 'yellow');
        CLI::newLine();

        // Get license from database
        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            CLI::error('License not found in database!');
            CLI::write('Please activate the application first.', 'red');
            return;
        }

        CLI::write('License found:', 'green');
        CLI::write('- Key: ' . $license['license_key']);
        CLI::write('- Domain: ' . ($license['domain'] ?? 'Not set'));
        CLI::write('- Expires: ' . ($license['expires_at'] ?? 'Lifetime'));
        CLI::newLine();

        // Regenerate checksum
        CLI::write('Regenerating checksum...', 'blue');
        
        try {
            $checksumFile = APPPATH . 'Config/.lic_checksum';
            
            // Check if file exists and is writable
            if (file_exists($checksumFile) && !is_writable($checksumFile)) {
                CLI::error('Checksum file is not writable!');
                CLI::write('File: ' . $checksumFile, 'yellow');
                CLI::write('Please set write permission for this file.', 'yellow');
                CLI::newLine();
                CLI::write('Windows: Right-click file → Properties → Security → Edit → Allow "Full control"', 'cyan');
                CLI::write('Or run command prompt as Administrator', 'cyan');
                return;
            }
            
            // Check if directory is writable
            $dir = dirname($checksumFile);
            if (!is_writable($dir)) {
                CLI::error('Config directory is not writable!');
                CLI::write('Directory: ' . $dir, 'yellow');
                CLI::write('Please set write permission for app/Config directory.', 'yellow');
                return;
            }
            
            $data = [
                'license_key' => $license['license_key'],
                'machine_id' => $license['machine_id'],
                'expires_at' => $license['expires_at'],
                'created_at' => $license['created_at']
            ];
            
            $lsc = config('LService');
            $secret = $lsc->getSecret();
            $checksum = hash('sha256', json_encode($data) . $secret);
            
            $result = @file_put_contents($checksumFile, $checksum);
            
            if ($result === false) {
                CLI::error('Failed to write checksum file!');
                CLI::write('This might be a permission issue.', 'yellow');
                CLI::write('Try running command prompt as Administrator.', 'yellow');
                return;
            }
            
            CLI::write('✓ Checksum file updated: ' . $checksumFile, 'green');
            CLI::newLine();
            
            // Also update backup
            CLI::write('Updating backup...', 'blue');
            \App\Libraries\LicenseProtector::createBackup();
            CLI::write('✓ Backup updated', 'green');
            CLI::newLine();
            
            // Verify
            CLI::write('Verifying integrity...', 'blue');
            $valid = \App\Libraries\LicenseProtector::verifyIntegrity();
            
            if ($valid) {
                CLI::write('✓ Integrity check PASSED', 'green');
                CLI::newLine();
                CLI::write('SUCCESS! License checksum fixed.', 'green');
            } else {
                CLI::error('Integrity check still FAILED');
                CLI::write('Please check the license data manually.', 'yellow');
            }
            
        } catch (\Exception $e) {
            CLI::error('Failed to fix checksum: ' . $e->getMessage());
        }
    }
}
