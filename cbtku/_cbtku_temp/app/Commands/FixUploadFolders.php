<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixUploadFolders extends BaseCommand
{
    protected $group       = 'Maintenance';
    protected $name        = 'fix:uploadfolders';
    protected $description = 'Fix upload folders structure and move files to correct location';

    public function run(array $params)
    {
        CLI::write('=== Fix Upload Folders Structure ===', 'yellow');
        CLI::newLine();
        
        // Define folders to check and fix
        $folders = [
            'soal_images' => 'Question images',
            'audio' => 'Audio files',
            'logo' => 'School logos',
            'carousels' => 'Carousel images',
            'gallery' => 'Gallery images',
            'articles' => 'Article images',
        ];
        
        $totalMoved = 0;
        $totalFailed = 0;
        
        foreach ($folders as $folder => $description) {
            CLI::write("Processing: {$description} ({$folder})", 'cyan');
            CLI::write(str_repeat('-', 50), 'white');
            
            $publicPath = FCPATH . 'uploads/' . $folder . '/';
            $rootPath = ROOTPATH . 'uploads/' . $folder . '/';
            
            // 1. Ensure public folder exists
            if (!is_dir($publicPath)) {
                CLI::write('  Creating public folder...', 'yellow');
                if (mkdir($publicPath, 0775, true)) {
                    CLI::write('  ✓ Folder created', 'green');
                } else {
                    CLI::write('  ✗ Failed to create folder', 'red');
                    continue;
                }
            } else {
                CLI::write('  ✓ Public folder exists', 'green');
            }
            
            // 2. Check if writable
            if (!is_writable($publicPath)) {
                CLI::write('  ⚠ Warning: Folder is not writable!', 'red');
                CLI::write('  Fix: chmod 775 ' . $publicPath, 'yellow');
            } else {
                CLI::write('  ✓ Folder is writable', 'green');
            }
            
            // 3. Check root folder and move files
            if (is_dir($rootPath)) {
                $files = glob($rootPath . '*');
                $fileCount = count(array_filter($files, 'is_file'));
                
                if ($fileCount > 0) {
                    CLI::write("  Found {$fileCount} files in root/{$folder}", 'yellow');
                    CLI::write('  Moving files...', 'yellow');
                    
                    $moved = 0;
                    $failed = 0;
                    
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            $filename = basename($file);
                            $destination = $publicPath . $filename;
                            
                            // Skip if already exists
                            if (file_exists($destination)) {
                                CLI::write("    - Skipped (exists): {$filename}", 'cyan');
                                continue;
                            }
                            
                            if (copy($file, $destination)) {
                                CLI::write("    ✓ Moved: {$filename}", 'green');
                                $moved++;
                                // Optionally delete original after successful copy
                                // unlink($file);
                            } else {
                                CLI::write("    ✗ Failed: {$filename}", 'red');
                                $failed++;
                            }
                        }
                    }
                    
                    CLI::write("  Moved: {$moved} files", $moved > 0 ? 'green' : 'white');
                    if ($failed > 0) {
                        CLI::write("  Failed: {$failed} files", 'red');
                    }
                    
                    $totalMoved += $moved;
                    $totalFailed += $failed;
                } else {
                    CLI::write('  No files in root folder', 'cyan');
                }
            } else {
                CLI::write('  ✓ No root folder (correct)', 'green');
            }
            
            // 4. Count files in public folder
            $publicFiles = glob($publicPath . '*');
            $publicFileCount = count(array_filter($publicFiles, 'is_file'));
            CLI::write("  Total files in public/{$folder}: {$publicFileCount}", 'cyan');
            
            CLI::newLine();
        }
        
        // Summary
        CLI::write('=== Summary ===', 'yellow');
        CLI::write("Total files moved: {$totalMoved}", $totalMoved > 0 ? 'green' : 'white');
        if ($totalFailed > 0) {
            CLI::write("Total files failed: {$totalFailed}", 'red');
        }
        CLI::newLine();
        
        CLI::write('✓ All uploads should be in: ' . FCPATH . 'uploads/', 'green');
        CLI::write('✓ Files accessible at: ' . base_url('uploads/'), 'green');
        CLI::newLine();
        CLI::write('Done!', 'green');
    }
}
