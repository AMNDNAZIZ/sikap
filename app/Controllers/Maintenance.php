<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class Maintenance extends BaseController
{
    /**
     * Remote Hash Reset
     * URL: /maintenance/reset-hashes/[token]
     */
    public function resetHashes($token = '')
    {
        helper('license');

        // 1. Generate expected token
        $secret = \App\Libraries\LicenseGuard::getHashSecret();
        $salt = 'SakaSalikaMaintenanceSalt2026';
        $expectedToken = hash('sha256', $secret . $salt);

        // 2. Verify token
        if (empty($token) || $token !== $expectedToken) {
            log_message('critical', 'Unauthorized remote hash reset attempt with token: ' . $token);
            return $this->response->setStatusCode(403)->setBody('<h1>403 Forbidden</h1><p>Invalid maintenance token.</p>');
        }

        // 3. Define hash files
        $licHash = APPPATH . 'Config/.lic_hash';
        $secHash = APPPATH . 'Config/.security_hash';

        $results = [];

        // 4. Delete files if they exist
        if (file_exists($licHash)) {
            if (unlink($licHash)) {
                $results[] = '.lic_hash deleted';
                log_message('info', 'Remote Reset: .lic_hash deleted');
            } else {
                $results[] = 'Failed to delete .lic_hash';
            }
        } else {
            $results[] = '.lic_hash not found (already reset?)';
        }

        if (file_exists($secHash)) {
            if (unlink($secHash)) {
                $results[] = '.security_hash deleted';
                log_message('info', 'Remote Reset: .security_hash deleted');
            } else {
                $results[] = 'Failed to delete .security_hash';
            }
        } else {
            $results[] = '.security_hash not found (already reset?)';
        }

        // 5. Output response
        $output = "<h1>Maintenance: Remote Hash Reset</h1>";
        $output .= "<ul><li>" . implode("</li><li>", $results) . "</li></ul>";
        $output .= "<hr><p><b>Success!</b> Silakan buka dashboard aplikasi untuk meregenerasi hash secara otomatis.</p>";
        $output .= "<p><a href='" . base_url('dashboard') . "'>Ke Dashboard</a></p>";

        return $this->response->setBody($output);
    }

    /**
     * Tool to see the current valid token (For Developer only)
     * You might want to delete this method or keep it for internal use
     */
    public function debugToken()
    {
        helper('license');

        // Only allow from localhost or if explicitly enabled
        if (!is_local_environment()) {
            return $this->response->setStatusCode(404);
        }

        $secret = \App\Libraries\LicenseGuard::getHashSecret();
        $salt = 'SakaSalikaMaintenanceSalt2026';
        $token = hash('sha256', $secret . $salt);

        return "Current valid maintenance token: <b>$token</b>";
    }
}
