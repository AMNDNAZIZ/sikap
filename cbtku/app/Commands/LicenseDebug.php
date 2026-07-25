<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\LicenseModel;

/**
 * Command untuk debug koneksi dan renewal lisensi
 * 
 * Usage:
 * php spark license:debug
 */
class LicenseDebug extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:debug';
    protected $description = 'Debug license renewal connection and response';

    public function run(array $params)
    {
        CLI::write('License Renewal Debug Tool', 'yellow');
        CLI::write('==========================', 'yellow');
        CLI::newLine();

        // 1. Check license exists
        CLI::write('1. Checking local license...', 'blue');
        $model = new LicenseModel();
        $license = $model->first();

        if (!$license) {
            CLI::write('   ✗ No license found in database', 'red');
            return;
        }

        CLI::write('   ✓ License found', 'green');
        CLI::write('   - Key: ' . $license['license_key'], 'white');
        CLI::write('   - Domain: ' . ($license['domain'] ?? 'Not set'), 'white');
        CLI::write('   - Expires: ' . ($license['expires_at'] ?? 'Lifetime'), 'white');
        CLI::write('   - Status: ' . $license['status'], 'white');
        CLI::newLine();

        // 2. Get server config
        CLI::write('2. Checking server configuration...', 'blue');
        $lsc = config('LService');
        $serverUrl = $lsc->getUrl();
        CLI::write('   - Server URL: ' . $serverUrl, 'white');
        CLI::newLine();

        // 3. Prepare request data
        CLI::write('3. Preparing request data...', 'blue');
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        helper('license');
        $machineId = generate_machine_id();

        $requestData = [
            'license_key' => $license['license_key'],
            'domain' => $domain,
            'machine_id' => $machineId,
            'action' => 'check_renewal'
        ];

        CLI::write('   - License Key: ' . $requestData['license_key'], 'white');
        CLI::write('   - Domain: ' . $requestData['domain'], 'white');
        CLI::write('   - Machine ID: ' . substr($requestData['machine_id'], 0, 32) . '...', 'white');
        CLI::write('   - Action: ' . $requestData['action'], 'white');
        CLI::newLine();

        // 4. Test connection
        CLI::write('4. Testing connection to server...', 'blue');
        $client = \Config\Services::curlrequest();

        try {
            CLI::write('   Sending POST request...', 'white');
            
            $response = $client->post($serverUrl, [
                'form_params' => $requestData,
                'timeout' => 10,
                'http_errors' => false,
                'debug' => false
            ]);

            $statusCode = $response->getStatusCode();
            $body = $response->getBody();

            CLI::newLine();
            CLI::write('   ✓ Connection successful', 'green');
            CLI::write('   - Status Code: ' . $statusCode, 'white');
            CLI::newLine();

            // 5. Parse response
            CLI::write('5. Parsing server response...', 'blue');
            CLI::write('   Raw Response:', 'white');
            CLI::write('   ' . str_repeat('-', 70), 'dark_gray');
            
            // Pretty print JSON if possible
            $result = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                CLI::write('   ' . json_encode($result, JSON_PRETTY_PRINT), 'white');
            } else {
                CLI::write('   ' . $body, 'white');
            }
            CLI::write('   ' . str_repeat('-', 70), 'dark_gray');
            CLI::newLine();

            // 6. Analyze response
            CLI::write('6. Analyzing response...', 'blue');
            
            if (!$result) {
                CLI::write('   ✗ Invalid JSON response', 'red');
                CLI::write('   Response is not valid JSON format', 'yellow');
                return;
            }

            if (!isset($result['status'])) {
                CLI::write('   ✗ Missing "status" field', 'red');
                CLI::write('   Server response must include "status" field', 'yellow');
                return;
            }

            CLI::write('   - Status: ' . $result['status'], 'white');

            if ($result['status'] === 'success') {
                CLI::write('   ✓ Status is SUCCESS', 'green');

                if (isset($result['data']['expiry'])) {
                    $newExpiry = $result['data']['expiry'];
                    $oldExpiry = $license['expires_at'];

                    CLI::write('   - Old Expiry: ' . $oldExpiry, 'white');
                    CLI::write('   - New Expiry: ' . $newExpiry, 'white');

                    if (strtotime($newExpiry) > strtotime($oldExpiry)) {
                        CLI::write('   ✓ License RENEWED (new expiry is later)', 'green');
                        
                        $daysAdded = (strtotime($newExpiry) - strtotime($oldExpiry)) / 86400;
                        CLI::write('   - Days added: ' . round($daysAdded), 'white');
                    } else if (strtotime($newExpiry) === strtotime($oldExpiry)) {
                        CLI::write('   ⚠ No renewal (expiry date unchanged)', 'yellow');
                    } else {
                        CLI::write('   ⚠ New expiry is EARLIER than old expiry', 'yellow');
                    }

                    // Check if expired
                    if (strtotime($newExpiry) < time()) {
                        CLI::write('   ✗ License is EXPIRED', 'red');
                    } else {
                        $daysRemaining = ceil((strtotime($newExpiry) - time()) / 86400);
                        CLI::write('   ✓ License is ACTIVE (' . $daysRemaining . ' days remaining)', 'green');
                    }
                } else {
                    CLI::write('   ✗ Missing "data.expiry" field', 'red');
                    CLI::write('   Server response must include expiry date in data.expiry', 'yellow');
                }
            } else {
                CLI::write('   ✗ Status is ERROR', 'red');
                if (isset($result['message'])) {
                    CLI::write('   - Message: ' . $result['message'], 'white');
                }
            }

            CLI::newLine();

            // 7. Recommendation
            CLI::write('7. Recommendations:', 'blue');
            
            if ($result['status'] === 'success' && isset($result['data']['expiry'])) {
                if (strtotime($result['data']['expiry']) > strtotime($license['expires_at'])) {
                    CLI::write('   ✓ Server response is VALID', 'green');
                    CLI::write('   ✓ You can proceed with renewal', 'green');
                    CLI::newLine();
                    
                    $confirm = CLI::prompt('Do you want to update local license now?', ['y', 'n']);
                    if ($confirm === 'y') {
                        $this->updateLicense($model, $license, $result['data']['expiry'], $machineId);
                    }
                } else {
                    CLI::write('   ⚠ License not renewed on server', 'yellow');
                    CLI::write('   → Update expiry date on server first', 'yellow');
                    CLI::write('   → Then run this command again', 'yellow');
                }
            } else {
                CLI::write('   ✗ Server response is INVALID', 'red');
                CLI::write('   → Check server API implementation', 'yellow');
                CLI::write('   → Ensure it returns correct JSON format', 'yellow');
                CLI::write('   → See LICENSE_SERVER_EXAMPLE.php for reference', 'yellow');
            }

        } catch (\Exception $e) {
            CLI::newLine();
            CLI::write('   ✗ Connection failed', 'red');
            CLI::write('   Error: ' . $e->getMessage(), 'red');
            CLI::newLine();
            CLI::write('Troubleshooting:', 'yellow');
            CLI::write('   1. Check if server URL is correct', 'white');
            CLI::write('   2. Check if server is online', 'white');
            CLI::write('   3. Check firewall/network settings', 'white');
            CLI::write('   4. Try: curl -X POST ' . $serverUrl, 'white');
        }
    }

    private function updateLicense($model, $license, $newExpiry, $machineId)
    {
        CLI::newLine();
        CLI::write('Updating local license...', 'blue');

        try {
            $lsc = config('LService');
            $hashSecret = $lsc->getSecret();
            $newHash = hash('sha256', $license['license_key'] . $newExpiry . $machineId . $hashSecret);

            $model->update($license['id'], [
                'expires_at' => $newExpiry,
                'last_check' => date('Y-m-d H:i:s'),
                'status' => 'active',
                'hash' => $newHash
            ]);

            // Update backup
            \App\Libraries\LicenseProtector::createBackup();

            CLI::write('✓ License updated successfully!', 'green');
            CLI::write('  - New expiry: ' . $newExpiry, 'white');
            CLI::write('  - Backup created', 'white');
        } catch (\Exception $e) {
            CLI::write('✗ Failed to update license', 'red');
            CLI::write('  Error: ' . $e->getMessage(), 'red');
        }
    }
}
