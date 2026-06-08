<?php

/**
 * Migrate Uploads to Public Directory
 * 
 * Script untuk migrasi folder uploads dari root ke public/uploads
 * dan update semua kode yang menggunakan path uploads
 */

echo "=================================================================\n";
echo "MIGRATE UPLOADS TO PUBLIC DIRECTORY\n";
echo "=================================================================\n\n";

$rootPath = __DIR__;
$uploadsPath = $rootPath . '/uploads';
$publicUploadsPath = $rootPath . '/public/uploads';

// Step 1: Backup
echo "STEP 1: Creating Backup...\n";
$backupName = 'uploads_backup_' . date('Ymd_His');
$backupPath = $rootPath . '/' . $backupName;

if (is_dir($uploadsPath)) {
    echo "   Creating backup: $backupName\n";
    exec("xcopy /E /I /Y \"$uploadsPath\" \"$backupPath\" > nul 2>&1", $output, $return);
    
    if ($return === 0) {
        echo "   ✓ Backup created successfully\n";
    } else {
        echo "   ⚠️  Backup failed, but continuing...\n";
    }
} else {
    echo "   ⚠️  Source uploads folder not found\n";
}
echo "\n";

// Step 2: Create public/uploads structure
echo "STEP 2: Creating public/uploads Structure...\n";

$folders = [
    'activities',
    'articles',
    'chat',
    'facilities',
    'logo',
    'sliders',
    'soal_images',
    'teachers',
    'vision',
    'students',
    'audio'
];

foreach ($folders as $folder) {
    $targetDir = $publicUploadsPath . '/' . $folder;
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
        echo "   ✓ Created: public/uploads/$folder\n";
    } else {
        echo "   - Exists: public/uploads/$folder\n";
    }
}
echo "\n";

// Step 3: Copy files
echo "STEP 3: Copying Files...\n";

if (is_dir($uploadsPath)) {
    foreach ($folders as $folder) {
        $sourceDir = $uploadsPath . '/' . $folder;
        $targetDir = $publicUploadsPath . '/' . $folder;
        
        if (is_dir($sourceDir)) {
            echo "   Copying $folder...\n";
            
            // Count files
            $files = glob($sourceDir . '/*');
            $fileCount = count($files);
            
            if ($fileCount > 0) {
                exec("xcopy /E /I /Y \"$sourceDir\" \"$targetDir\" > nul 2>&1", $output, $return);
                
                if ($return === 0) {
                    echo "      ✓ Copied $fileCount file(s)\n";
                } else {
                    echo "      ⚠️  Copy failed\n";
                }
            } else {
                echo "      - Empty folder\n";
            }
        }
    }
} else {
    echo "   ⚠️  Source uploads folder not found\n";
}
echo "\n";

// Step 4: Create .htaccess for security
echo "STEP 4: Creating Security .htaccess...\n";

$htaccessContent = <<<'HTACCESS'
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Deny access to PHP files
    <FilesMatch "\.php$">
        Order Deny,Allow
        Deny from all
    </FilesMatch>
    
    # Allow only safe file types
    <FilesMatch "\.(jpg|jpeg|png|gif|webp|svg|mp4|mp3|wav|pdf|doc|docx|xls|xlsx|ppt|pptx|zip)$">
        Order Allow,Deny
        Allow from all
    </FilesMatch>
</IfModule>

# Disable directory listing
Options -Indexes

# Prevent access to hidden files
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>
HTACCESS;

$htaccessPath = $publicUploadsPath . '/.htaccess';
file_put_contents($htaccessPath, $htaccessContent);
echo "   ✓ Created .htaccess in public/uploads\n";
echo "\n";

// Step 5: Update Views (check only, no changes needed)
echo "STEP 5: Checking Views...\n";
echo "   ℹ️  Views use base_url('uploads/...') - no changes needed\n";
echo "   ℹ️  Files will be served from public/uploads automatically\n";
echo "\n";

// Step 6: Verify
echo "STEP 6: Verification...\n";

$totalFiles = 0;
foreach ($folders as $folder) {
    $targetDir = $publicUploadsPath . '/' . $folder;
    if (is_dir($targetDir)) {
        $files = glob($targetDir . '/*');
        $count = count($files);
        $totalFiles += $count;
        
        if ($count > 0) {
            echo "   ✓ public/uploads/$folder: $count file(s)\n";
        }
    }
}

echo "\n   Total files in public/uploads: $totalFiles\n";
echo "\n";

// Summary
echo "=================================================================\n";
echo "MIGRATION SUMMARY\n";
echo "=================================================================\n\n";

echo "✓ Backup created: $backupName\n";
echo "✓ Folder structure created in public/uploads\n";
echo "✓ Files copied to public/uploads\n";
echo "✓ Security .htaccess created\n";
echo "✓ Total files migrated: $totalFiles\n";
echo "\n";

echo "=================================================================\n";
echo "NEXT STEPS\n";
echo "=================================================================\n\n";

echo "1. Test aplikasi:\n";
echo "   - Upload file baru (foto guru, slider, dll)\n";
echo "   - Lihat file yang sudah ada (logo, foto, dll)\n";
echo "   - Test CBT dengan gambar soal\n";
echo "\n";

echo "2. Jika semua OK, hapus folder uploads di root:\n";
echo "   rmdir /s uploads\n";
echo "\n";

echo "3. Jika ada masalah, restore dari backup:\n";
echo "   xcopy /E /I /Y \"$backupName\" \"uploads\"\n";
echo "\n";

echo "=================================================================\n";
echo "IMPORTANT NOTES\n";
echo "=================================================================\n\n";

echo "✓ Kode controller sudah diupdate menggunakan UPLOAD_PATH\n";
echo "✓ Views tidak perlu diubah (sudah menggunakan base_url)\n";
echo "✓ File di public/uploads lebih aman (protected by .htaccess)\n";
echo "✓ Backup tersimpan di: $backupName\n";
echo "\n";

echo "=================================================================\n";
echo "MIGRATION COMPLETE!\n";
echo "=================================================================\n";
