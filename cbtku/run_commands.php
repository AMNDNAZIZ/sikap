<?php
/**
 * Command Runner for Subdomain Deployment
 * 
 * SECURITY WARNING:
 * - Change the password below before uploading!
 * - Delete this file after use!
 * - Do not leave this file on production server!
 */

// ============================================================================
// CONFIGURATION
// ============================================================================

// Change this password!
define('ACCESS_PASSWORD', 'change_this_password_123');

// Set timezone
date_default_timezone_set('Asia/Jakarta');

// ============================================================================
// SECURITY CHECK
// ============================================================================

$input_password = $_GET['pass'] ?? $_POST['pass'] ?? '';

if ($input_password !== ACCESS_PASSWORD) {
    http_response_code(403);
    die('
    <!DOCTYPE html>
    <html>
    <head>
        <title>Access Denied</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 50px; text-align: center; }
            .error { color: red; font-size: 24px; }
        </style>
    </head>
    <body>
        <div class="error">🔒 Access Denied</div>
        <p>Invalid password</p>
    </body>
    </html>
    ');
}

// ============================================================================
// HTML HEADER
// ============================================================================
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Command Runner - CBT Deployment</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .warning {
            background: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #856404;
        }
        .warning strong { display: block; margin-bottom: 5px; font-size: 16px; }
        .commands {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .cmd-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
            text-decoration: none;
            display: block;
            text-align: center;
        }
        .cmd-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .output {
            background: #1e1e1e;
            color: #d4d4d4;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            line-height: 1.6;
            max-height: 500px;
            overflow-y: auto;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .output .success { color: #4ec9b0; }
        .output .error { color: #f48771; }
        .output .warning { color: #dcdcaa; }
        .output .info { color: #9cdcfe; }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #dee2e6;
        }
        .delete-warning {
            background: #f8d7da;
            border: 2px solid #dc3545;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-weight: bold;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚀 Command Runner</h1>
            <p>CBT Deployment Helper</p>
        </div>
        
        <div class="content">
            <div class="warning">
                <strong>⚠️ PERINGATAN KEAMANAN</strong>
                Hapus file ini (run_commands.php) setelah selesai digunakan!
            </div>

<?php
// ============================================================================
// COMMAND EXECUTION
// ============================================================================

$command = $_GET['cmd'] ?? '';

if (empty($command)) {
    // Show command menu
    ?>
    <h3 style="margin-bottom: 15px;">Pilih Command:</h3>
    <div class="commands">
        <a href="?pass=<?= urlencode(ACCESS_PASSWORD) ?>&cmd=check:uploads" class="cmd-btn">
            📋 Check Uploads
        </a>
        <a href="?pass=<?= urlencode(ACCESS_PASSWORD) ?>&cmd=fix:uploadfolders" class="cmd-btn">
            📁 Fix Upload Folders
        </a>
        <a href="?pass=<?= urlencode(ACCESS_PASSWORD) ?>&cmd=fix:imagepaths" class="cmd-btn">
            🖼️ Fix Image Paths
        </a>
        <a href="?pass=<?= urlencode(ACCESS_PASSWORD) ?>&cmd=cache:clear" class="cmd-btn">
            🗑️ Clear Cache
        </a>
    </div>
    
    <h3 style="margin: 30px 0 15px 0;">Informasi Server:</h3>
    <div class="output">
<span class="info">PHP Version:</span> <?= PHP_VERSION . "\n" ?>
<span class="info">Current Directory:</span> <?= __DIR__ . "\n" ?>
<span class="info">Document Root:</span> <?= $_SERVER['DOCUMENT_ROOT'] ?? 'N/A' . "\n" ?>
<span class="info">Server Software:</span> <?= $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' . "\n" ?>
<span class="info">Spark File:</span> <?= file_exists(__DIR__ . '/spark') ? '✓ Found' : '✗ Not Found' . "\n" ?>
<span class="info">Uploads Folder:</span> <?= is_dir(__DIR__ . '/public/uploads') ? '✓ Exists' : '✗ Not Found' . "\n" ?>
    </div>
    <?php
} else {
    // Execute command
    echo '<h3 style="margin-bottom: 15px;">Executing: <code>' . htmlspecialchars($command) . '</code></h3>';
    echo '<div class="output">';
    
    // Capture output
    ob_start();
    
    try {
        // Set environment
        putenv('CI_ENVIRONMENT=production');
        
        // Change to app directory
        chdir(__DIR__);
        
        echo "<span class='info'>Working Directory:</span> " . getcwd() . "\n";
        echo "<span class='info'>Timestamp:</span> " . date('Y-m-d H:i:s') . "\n";
        echo str_repeat("=", 70) . "\n\n";
        
        // Load CodeIgniter
        if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
            throw new Exception('Composer autoload not found. Run: composer install');
        }
        
        require __DIR__ . '/vendor/autoload.php';
        
        // Execute command
        switch ($command) {
            case 'check:uploads':
                if (!class_exists('\App\Commands\CheckUploads')) {
                    throw new Exception('CheckUploads command not found');
                }
                $cmd = new \App\Commands\CheckUploads();
                $cmd->run([]);
                echo "\n<span class='success'>✓ Command completed successfully!</span>\n";
                break;
                
            case 'fix:uploadfolders':
                if (!class_exists('\App\Commands\FixUploadFolders')) {
                    throw new Exception('FixUploadFolders command not found');
                }
                $cmd = new \App\Commands\FixUploadFolders();
                $cmd->run([]);
                echo "\n<span class='success'>✓ Command completed successfully!</span>\n";
                break;
                
            case 'fix:imagepaths':
                if (!class_exists('\App\Commands\FixImagePaths')) {
                    throw new Exception('FixImagePaths command not found');
                }
                $cmd = new \App\Commands\FixImagePaths();
                $cmd->run([]);
                echo "\n<span class='success'>✓ Command completed successfully!</span>\n";
                break;
                
            case 'cache:clear':
                // Clear cache manually
                $cacheDir = __DIR__ . '/writable/cache';
                if (is_dir($cacheDir)) {
                    $files = glob($cacheDir . '/*');
                    $count = 0;
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                            $count++;
                        }
                    }
                    echo "<span class='success'>✓ Cleared {$count} cache files</span>\n";
                } else {
                    echo "<span class='warning'>⚠ Cache directory not found</span>\n";
                }
                
                // Clear session
                $sessionDir = __DIR__ . '/writable/session';
                if (is_dir($sessionDir)) {
                    $files = glob($sessionDir . '/ci_session*');
                    $count = 0;
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                            $count++;
                        }
                    }
                    echo "<span class='success'>✓ Cleared {$count} session files</span>\n";
                }
                
                echo "\n<span class='success'>✓ Cache cleared successfully!</span>\n";
                break;
                
            default:
                throw new Exception("Unknown command: {$command}");
        }
        
    } catch (Exception $e) {
        echo "\n<span class='error'>✗ Error: " . $e->getMessage() . "</span>\n";
        echo "<span class='error'>File: " . $e->getFile() . ":" . $e->getLine() . "</span>\n";
    }
    
    $output = ob_get_clean();
    echo $output;
    
    echo "\n" . str_repeat("=", 70) . "\n";
    echo "<span class='info'>Finished at:</span> " . date('Y-m-d H:i:s') . "\n";
    
    echo '</div>';
    
    echo '<div style="margin-top: 20px; text-align: center;">';
    echo '<a href="?pass=' . urlencode(ACCESS_PASSWORD) . '" class="cmd-btn" style="display: inline-block; max-width: 200px;">← Back to Menu</a>';
    echo '</div>';
}
?>

            <div class="delete-warning">
                🔥 PENTING: Hapus file ini setelah selesai!<br>
                File: <code><?= basename(__FILE__) ?></code>
            </div>
        </div>
        
        <div class="footer">
            <small>CBT Deployment Helper v1.0 | <?= date('Y') ?></small>
        </div>
    </div>
</body>
</html>
