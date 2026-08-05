<?php

namespace App\Controllers;

use CodeIgniter\Controller;

/**
 * License Hash Update Controller
 * 
 * Akses via: http://your-domain.com/index.php/fixlicense
 * atau: http://your-domain.com/fixlicense (jika mod_rewrite aktif)
 */
class FixLicense extends Controller
{
    /**
     * File-file yang perlu di-hash
     */
    private $protectedFiles = [
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

    public function index()
    {
        // Disable license check for this controller
        $hashFile = APPPATH . 'Config/.lic_hash';
        $hashes = [];
        $errors = [];
        $success = true;

        $data = [
            'hashFile' => $hashFile,
            'hashes' => [],
            'errors' => [],
            'success' => true,
            'result' => null
        ];

        // Generate hashes
        foreach ($this->protectedFiles as $file) {
            $fullPath = APPPATH . $file;
            
            if (file_exists($fullPath)) {
                $hash = sha1_file($fullPath);
                $hashes[$file] = $hash;
                $data['hashes'][$file] = $hash;
            } else {
                $errors[] = $file;
                $data['errors'][] = $file;
                $data['success'] = false;
            }
        }

        // Save to file
        if (!empty($hashes)) {
            $json = json_encode($hashes, JSON_PRETTY_PRINT);
            
            // Check if file is writable
            if (file_exists($hashFile) && !is_writable($hashFile)) {
                $data['success'] = false;
                $data['error_message'] = 'File tidak bisa ditulis! Permission denied.';
            } else {
                $result = @file_put_contents($hashFile, $json);
                
                if ($result !== false) {
                    $data['result'] = $result;
                    $data['success'] = true;
                } else {
                    $data['success'] = false;
                    $data['error_message'] = 'Gagal menulis hash file!';
                }
            }
        }

        return view('fix_license_view', $data);
    }

    public function delete()
    {
        // Delete this controller file
        $controllerFile = __FILE__;
        $viewFile = APPPATH . 'Views/fix_license_view.php';
        
        $deleted = [];
        $failed = [];
        
        if (@unlink($controllerFile)) {
            $deleted[] = 'Controller: ' . basename($controllerFile);
        } else {
            $failed[] = 'Controller: ' . basename($controllerFile);
        }
        
        if (file_exists($viewFile) && @unlink($viewFile)) {
            $deleted[] = 'View: ' . basename($viewFile);
        } else if (file_exists($viewFile)) {
            $failed[] = 'View: ' . basename($viewFile);
        }
        
        return view('fix_license_delete', [
            'deleted' => $deleted,
            'failed' => $failed
        ]);
    }
}
