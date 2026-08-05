<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Libraries\LicenseProtector;

/**
 * Command untuk restore lisensi dari backup
 * 
 * Usage:
 * php spark license:restore
 */
class LicenseRestore extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:restore';
    protected $description = 'Restore license from encrypted backup';

    public function run(array $params)
    {
        CLI::write('License Restore Tool', 'yellow');
        CLI::write('====================', 'yellow');
        CLI::newLine();

        // Confirm action
        $confirm = CLI::prompt('This will restore license from backup. Continue?', ['y', 'n']);
        
        if ($confirm !== 'y') {
            CLI::write('Restore cancelled.', 'red');
            return;
        }

        CLI::write('Attempting to restore license...', 'blue');
        
        try {
            $restored = LicenseProtector::restoreFromBackup();
            
            if ($restored) {
                CLI::write('✓ License successfully restored from backup!', 'green');
                
                // Show restored license info
                $model = new \App\Models\LicenseModel();
                $license = $model->first();
                
                if ($license) {
                    CLI::newLine();
                    CLI::write('License Information:', 'yellow');
                    CLI::write('- Key: ' . $license['license_key']);
                    CLI::write('- Domain: ' . ($license['domain'] ?? 'Not set'));
                    CLI::write('- Expires: ' . ($license['expires_at'] ?? 'Lifetime'));
                    CLI::write('- Status: ' . $license['status']);
                }
            } else {
                CLI::write('✗ Failed to restore license. No valid backup found.', 'red');
            }
        } catch (\Exception $e) {
            CLI::write('✗ Error: ' . $e->getMessage(), 'red');
        }
    }
}
