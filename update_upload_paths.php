<?php

/**
 * Update Upload Paths in Controllers
 * 
 * Script untuk update semua path uploads di controller
 * dari FCPATH . 'uploads/' ke UPLOAD_PATH
 */

echo "=================================================================\n";
echo "UPDATE UPLOAD PATHS IN CONTROLLERS\n";
echo "=================================================================\n\n";

$controllersPath = __DIR__ . '/app/Controllers';
$updated = [];
$errors = [];

// Patterns to replace
$patterns = [
    // FCPATH . 'uploads/ -> UPLOAD_PATH
    [
        'search' => "FCPATH . 'uploads/",
        'replace' => "UPLOAD_PATH . '",
        'description' => "FCPATH . 'uploads/' to UPLOAD_PATH . '"
    ],
    // 'uploads/ -> UPLOAD_PATH
    [
        'search' => "'uploads/",
        'replace' => "UPLOAD_PATH . '",
        'description' => "'uploads/' to UPLOAD_PATH . '"
    ],
];

// Scan all PHP files in Controllers
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($controllersPath)
);

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') {
        $filePath = $file->getPathname();
        $relativePath = str_replace(__DIR__ . '/', '', $filePath);
        
        $content = file_get_contents($filePath);
        $originalContent = $content;
        $fileUpdated = false;
        
        foreach ($patterns as $pattern) {
            if (strpos($content, $pattern['search']) !== false) {
                $count = 0;
                $content = str_replace($pattern['search'], $pattern['replace'], $content, $count);
                
                if ($count > 0) {
                    $fileUpdated = true;
                    if (!isset($updated[$relativePath])) {
                        $updated[$relativePath] = [];
                    }
                    $updated[$relativePath][] = [
                        'pattern' => $pattern['description'],
                        'count' => $count
                    ];
                }
            }
        }
        
        if ($fileUpdated) {
            if (file_put_contents($filePath, $content)) {
                echo "✓ Updated: $relativePath\n";
            } else {
                echo "✗ Failed: $relativePath\n";
                $errors[] = $relativePath;
            }
        }
    }
}

echo "\n";
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

if (count($updated) > 0) {
    echo "Files Updated: " . count($updated) . "\n\n";
    
    foreach ($updated as $file => $changes) {
        echo "✓ $file\n";
        foreach ($changes as $change) {
            echo "   - {$change['pattern']}: {$change['count']} replacement(s)\n";
        }
    }
} else {
    echo "No files needed updating.\n";
}

if (count($errors) > 0) {
    echo "\n❌ Errors: " . count($errors) . "\n";
    foreach ($errors as $error) {
        echo "   - $error\n";
    }
}

echo "\n";
echo "=================================================================\n";
echo "NEXT STEPS\n";
echo "=================================================================\n\n";

echo "1. Run migration script:\n";
echo "   php migrate_uploads_to_public.php\n";
echo "\n";

echo "2. Test aplikasi:\n";
echo "   - Upload file baru\n";
echo "   - Lihat file yang sudah ada\n";
echo "\n";

echo "3. Regenerate security hash:\n";
echo "   php regenerate_hash_now.php\n";
echo "\n";

echo "=================================================================\n";
echo "UPDATE COMPLETE!\n";
echo "=================================================================\n";
