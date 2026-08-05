<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class FixImagePaths extends BaseCommand
{
    protected $group       = 'Database';
    protected $name        = 'fix:imagepaths';
    protected $description = 'Fix image paths in questions to use correct base URL';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        
        // Get current base URL from config
        $config = config('App');
        $newBaseUrl = rtrim($config->baseURL, '/');
        
        CLI::write("Current Base URL: " . $newBaseUrl, 'yellow');
        CLI::write("Fixing image paths in database...", 'yellow');
        
        // Possible old base URLs to replace
        $oldBaseUrls = [
            'http://localhost',
            'http://localhost:8080',
            'http://127.0.0.1',
            'http://127.0.0.1:8080',
        ];
        
        $totalFixed = 0;
        
        // Fix cbt_questions table
        $questions = $db->table('cbt_questions')->get()->getResultArray();
        
        foreach ($questions as $question) {
            $updated = false;
            $data = [];
            
            // Fix question_text
            if (!empty($question['question_text'])) {
                $originalText = $question['question_text'];
                $fixedText = $originalText;
                
                foreach ($oldBaseUrls as $oldUrl) {
                    // Replace old base URL with new one
                    $fixedText = str_replace($oldUrl . '/uploads/', $newBaseUrl . '/uploads/', $fixedText);
                    $fixedText = str_replace($oldUrl . '/public/uploads/', $newBaseUrl . '/uploads/', $fixedText);
                }
                
                // Also fix relative paths that might be broken
                $fixedText = preg_replace_callback('/<img[^>]+src="([^"]+)"[^>]*>/i', function($matches) use ($newBaseUrl) {
                    $src = $matches[1];
                    
                    // If it's a relative path starting with uploads/
                    if (strpos($src, 'uploads/') === 0) {
                        $src = $newBaseUrl . '/' . $src;
                        return str_replace($matches[1], $src, $matches[0]);
                    }
                    
                    // If it's already a full URL with correct base, leave it
                    if (strpos($src, $newBaseUrl) === 0) {
                        return $matches[0];
                    }
                    
                    // If it's a full URL with wrong base, fix it
                    if (preg_match('#^https?://#', $src)) {
                        // Extract the path after domain
                        if (preg_match('#/uploads/(.+)$#', $src, $pathMatch)) {
                            $src = $newBaseUrl . '/uploads/' . $pathMatch[1];
                            return str_replace($matches[1], $src, $matches[0]);
                        }
                    }
                    
                    return $matches[0];
                }, $fixedText);
                
                if ($fixedText !== $originalText) {
                    $data['question_text'] = $fixedText;
                    $updated = true;
                }
            }
            
            // Fix raw_text
            if (!empty($question['raw_text'])) {
                $originalRaw = $question['raw_text'];
                $fixedRaw = $originalRaw;
                
                foreach ($oldBaseUrls as $oldUrl) {
                    $fixedRaw = str_replace($oldUrl . '/uploads/', $newBaseUrl . '/uploads/', $fixedRaw);
                    $fixedRaw = str_replace($oldUrl . '/public/uploads/', $newBaseUrl . '/uploads/', $fixedRaw);
                }
                
                // Fix relative paths in raw_text
                $fixedRaw = preg_replace_callback('/<img[^>]+src="([^"]+)"[^>]*>/i', function($matches) use ($newBaseUrl) {
                    $src = $matches[1];
                    
                    if (strpos($src, 'uploads/') === 0) {
                        $src = $newBaseUrl . '/' . $src;
                        return str_replace($matches[1], $src, $matches[0]);
                    }
                    
                    if (strpos($src, $newBaseUrl) === 0) {
                        return $matches[0];
                    }
                    
                    if (preg_match('#^https?://#', $src)) {
                        if (preg_match('#/uploads/(.+)$#', $src, $pathMatch)) {
                            $src = $newBaseUrl . '/uploads/' . $pathMatch[1];
                            return str_replace($matches[1], $src, $matches[0]);
                        }
                    }
                    
                    return $matches[0];
                }, $fixedRaw);
                
                if ($fixedRaw !== $originalRaw) {
                    $data['raw_text'] = $fixedRaw;
                    $updated = true;
                }
            }
            
            // Update if changes were made
            if ($updated) {
                $db->table('cbt_questions')
                    ->where('id', $question['id'])
                    ->update($data);
                $totalFixed++;
                CLI::write("Fixed question ID: " . $question['id'], 'green');
            }
        }
        
        CLI::write("", 'white');
        CLI::write("Total questions fixed: " . $totalFixed, 'green');
        CLI::write("Done!", 'green');
    }
}
