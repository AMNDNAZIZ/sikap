<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckUploads extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'check:uploads';
    protected $description = 'Check all upload folders and their permissions';

    public function run(array $params)
    {
        CLI::write('=== Checking Upload Folders ===', 'yellow');
        CLI::newLine();
        
        $config = config('App');
        $baseUrl = rtrim($config->baseURL, '/');
        
        CLI::write('Base URL: ' . $baseUrl, 'cyan');
        CLI::write('FCPATH (public): ' . FCPATH, 'cyan');
        CLI::write('ROOTPATH: ' . ROOTPATH, 'cyan');
        CLI::newLine();
        
        // Define all upload folders that should exist in public/
        $folders = [
            'uploads/' => 'Root uploads folder',
            'uploads/logo/' => 'School logos',
            'uploads/soal_images/' => 'Question images',
            'uploads/audio/' => 'Audio files',
            'uploads/carousels/' => 'Carousel images',
            'uploads/gallery/' => 'Gallery images',
            'uploads/articles/' => 'Article images',
        ];
        
        CLI::write('Checking folders in public/:', 'yellow');
        CLI::newLine();
        
        $allOk = true;
        
        foreach ($folders as $folder => $description) {
            $fullPath = FCPATH . $folder;
            $exists = is_dir($fullPath);
            $writable = $exists ? is_writable($fullPath) : false;
            
            CLI::write("📁 {$description} ({$folder})", 'white');
            CLI::write("   Path: {$fullPath}", 'cyan');
            
            if ($exists) {
                CLI::write("   ✓ Exists", 'green');
                
                if ($writable) {
                    CLI::write("   ✓ Writable", 'green');
                } else {
                    CLI::write("   ✗ NOT Writable", 'red');
                    CLI::write("   Fix: chmod 775 {$fullPath}", 'yellow');
                    $allOk = false;
                }
                
                // Count files
                $files = glob($fullPath . '*');
                $fileCount = count(array_filter($files, 'is_file'));
                CLI::write("   Files: {$fileCount}", 'cyan');
                
            } else {
                CLI::write("   ✗ Does NOT exist", 'red');
                CLI::write("   Creating folder...", 'yellow');
                
                if (mkdir($fullPath, 0775, true)) {
                    CLI::write("   ✓ Created successfully", 'green');
                } else {
                    CLI::write("   ✗ Failed to create", 'red');
                    $allOk = false;
                }
            }
            
            // Test URL accessibility
            $testUrl = $baseUrl . '/' . $folder;
            CLI::write("   URL: {$testUrl}", 'cyan');
            
            CLI::newLine();
        }
        
        // Check if root/uploads exists (should NOT be used)
        $rootUploads = ROOTPATH . 'uploads/';
        if (is_dir($rootUploads)) {
            CLI::write('⚠ WARNING: Found uploads folder in root directory!', 'red');
            CLI::write('   Path: ' . $rootUploads, 'yellow');
            CLI::write('   This folder is NOT accessible from web!', 'yellow');
            CLI::write('   Files should be in: ' . FCPATH . 'uploads/', 'yellow');
            
            $rootFiles = glob($rootUploads . '**/*', GLOB_BRACE);
            $rootFileCount = count(array_filter($rootFiles, 'is_file'));
            
            if ($rootFileCount > 0) {
                CLI::write("   Found {$rootFileCount} files in root/uploads", 'red');
                CLI::write('   Run: php spark fix:uploadfolders to move them', 'yellow');
            }
            
            CLI::newLine();
        }
        
        // Test write permission
        CLI::write('Testing write permission...', 'yellow');
        $testFile = FCPATH . 'uploads/test_write_' . time() . '.txt';
        
        if (@file_put_contents($testFile, 'test')) {
            CLI::write('✓ Write test successful', 'green');
            @unlink($testFile);
        } else {
            CLI::write('✗ Write test FAILED', 'red');
            CLI::write('  Permission issue detected!', 'red');
            $allOk = false;
        }
        
        CLI::newLine();
        
        // Summary
        CLI::write('=== Summary ===', 'yellow');
        
        if ($allOk) {
            CLI::write('✓ All checks passed!', 'green');
            CLI::write('✓ Upload system is properly configured', 'green');
        } else {
            CLI::write('✗ Some issues found', 'red');
            CLI::write('  Please fix the issues above', 'yellow');
        }
        
        CLI::newLine();
        
        // Recommendations
        CLI::write('=== Recommendations ===', 'yellow');
        CLI::write('1. All uploads should go to: ' . FCPATH . 'uploads/', 'cyan');
        CLI::write('2. Files are accessible at: ' . $baseUrl . '/uploads/', 'cyan');
        CLI::write('3. Never use ROOTPATH/uploads/ for web-accessible files', 'cyan');
        CLI::write('4. Folder permissions should be 775 or 777', 'cyan');
        
        CLI::newLine();
        CLI::write('Done!', 'green');
    }
}
