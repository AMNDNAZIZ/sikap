<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\LicenseModel;

class LicenseHashUpdate extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:hash';
    protected $description = 'Update license hash file (requires valid online license)';

    public function run(array $params)
    {
        CLI::write('License Hash Update Tool', 'yellow');
        CLI::write('========================', 'yellow');
        CLI::newLine();

        // 1. Check if license exists
        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            CLI::error('No license found! Please activate your license first.');
            return;
        }

        CLI::write('License Key: ' . substr($license['license_key'], 0, 20) . '...', 'green');
        CLI::write('Domain: ' . $license['domain'], 'green');
        CLI::newLine();

        // 2. Validate license online
        CLI::write('Validating license with server...', 'yellow');
        
        if (!$this->validateLicenseOnline($license)) {
            CLI::error('License validation failed! Cannot generate hash.');
            CLI::error('Please ensure:');
            CLI::write('  1. License is active and not expired');
            CLI::write('  2. Internet connection is available');
            CLI::write('  3. License server is accessible');
            return;
        }

        CLI::write('✓ License validated successfully', 'green');
        CLI::newLine();

        // 3. Generate hashes
        CLI::write('Generating hashes for protected files...', 'yellow');
        CLI::newLine();

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
        $errors = [];

        foreach ($protectedFiles as $file) {
            $fullPath = APPPATH . $file;
            
            if (file_exists($fullPath)) {
                $hash = sha1_file($fullPath);
                $hashes[$file] = $hash;
                CLI::write('  ✓ ' . $file, 'green');
                CLI::write('    Hash: ' . $hash, 'dark_gray');
            } else {
                $errors[] = $file;
                CLI::write('  ✗ ' . $file . ' (NOT FOUND)', 'red');
            }
        }

        CLI::newLine();

        if (!empty($errors)) {
            CLI::error('Some files were not found:');
            foreach ($errors as $error) {
                CLI::write('  - ' . $error, 'red');
            }
            CLI::newLine();
            CLI::write('Application may not function properly!', 'red');
            return;
        }

        // 4. Save to file
        $hashFile = APPPATH . 'Config/.lic_hash';
        $json = json_encode($hashes, JSON_PRETTY_PRINT);
        
        $result = @file_put_contents($hashFile, $json);

        if ($result === false) {
            CLI::error('Failed to write hash file!');
            CLI::error('Please check file permissions for: ' . $hashFile);
            CLI::write('Try: chmod 666 ' . $hashFile);
            return;
        }

        CLI::write('✓ Hash file updated successfully!', 'green');
        CLI::write('File: ' . $hashFile, 'dark_gray');
        CLI::write('Total files: ' . count($hashes), 'dark_gray');
        CLI::newLine();

        CLI::write('Hash update completed!', 'green');
    }

    /**
     * Validate license with online server
     */
    private function validateLicenseOnline($license)
    {
        try {
            // Skip online check for localhost
            helper('license');
            if (is_local_environment()) {
                CLI::write('  (Localhost detected, skipping online validation)', 'dark_gray');
                return true;
            }

            $lsc = config('LService');
            $serverUrl = $lsc->getUrl();
            $domain = $_SERVER['HTTP_HOST'] ?? $license['domain'];
            $machineId = generate_machine_id();

            $client = \Config\Services::curlrequest();
            
            $response = $client->post($serverUrl, [
                'form_params' => [
                    'license_key' => $license['license_key'],
                    'domain' => $domain,
                    'machine_id' => $machineId,
                    'action' => 'verify'
                ],
                'timeout' => 10,
                'http_errors' => false
            ]);

            $result = json_decode($response->getBody(), true);

            if (isset($result['status']) && $result['status'] === 'success') {
                return true;
            }

            CLI::write('  Server response: ' . ($result['message'] ?? 'Unknown error'), 'red');
            return false;

        } catch (\Exception $e) {
            CLI::error('  Exception: ' . $e->getMessage());
            return false;
        }
    }
}
