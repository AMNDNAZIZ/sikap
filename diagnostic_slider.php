<?php
/**
 * Diagnostic Slider Upload
 * 
 * Script sederhana untuk diagnosa masalah upload slider
 * Akses via browser: http://localhost/siakad/diagnostic_slider.php
 */

// Security: Only allow localhost
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    die('Access denied. This script can only be run from localhost.');
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnostic Slider Upload</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .check { margin: 15px 0; padding: 15px; background: #f8f9fa; border-left: 4px solid #28a745; }
        .check.error { border-left-color: #dc3545; background: #fff5f5; }
        .check.warning { border-left-color: #ffc107; background: #fffbf0; }
        .icon { font-weight: bold; margin-right: 10px; }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #0056b3; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        table th, table td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        table th { background: #f8f9fa; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnostic Slider Upload</h1>
        <p>Script ini akan memeriksa semua komponen yang diperlukan untuk upload slider.</p>

        <?php
        $errors = [];
        $warnings = [];
        $success = [];

        // 1. Check PHP version
        echo "<h2>1. PHP Configuration</h2>";
        $phpVersion = phpversion();
        if (version_compare($phpVersion, '7.4.0', '>=')) {
            echo "<div class='check'><span class='icon success'>✓</span> PHP Version: $phpVersion</div>";
            $success[] = "PHP version OK";
        } else {
            echo "<div class='check error'><span class='icon error'>✗</span> PHP Version: $phpVersion (minimum 7.4 required)</div>";
            $errors[] = "PHP version too old";
        }

        // 2. Check upload settings
        echo "<h2>2. Upload Settings</h2>";
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        $maxFileUploads = ini_get('max_file_uploads');
        
        echo "<table>";
        echo "<tr><th>Setting</th><th>Value</th><th>Status</th></tr>";
        echo "<tr><td>upload_max_filesize</td><td>$uploadMaxFilesize</td><td>" . (ini_get('upload_max_filesize') ? '✓' : '✗') . "</td></tr>";
        echo "<tr><td>post_max_size</td><td>$postMaxSize</td><td>" . (ini_get('post_max_size') ? '✓' : '✗') . "</td></tr>";
        echo "<tr><td>max_file_uploads</td><td>$maxFileUploads</td><td>" . (ini_get('max_file_uploads') ? '✓' : '✗') . "</td></tr>";
        echo "</table>";

        // 3. Check GD library
        echo "<h2>3. Image Processing</h2>";
        if (extension_loaded('gd')) {
            $gdInfo = gd_info();
            echo "<div class='check'><span class='icon success'>✓</span> GD Library: Installed</div>";
            echo "<pre>" . print_r($gdInfo, true) . "</pre>";
            $success[] = "GD library available";
        } else {
            echo "<div class='check error'><span class='icon error'>✗</span> GD Library: Not installed</div>";
            $errors[] = "GD library missing";
        }

        // 4. Check uploads folder
        echo "<h2>4. Upload Directory</h2>";
        $uploadPath = __DIR__ . '/uploads/sliders';
        
        if (is_dir($uploadPath)) {
            echo "<div class='check'><span class='icon success'>✓</span> Folder exists: $uploadPath</div>";
            $success[] = "Upload folder exists";
            
            // Check permissions
            if (is_writable($uploadPath)) {
                echo "<div class='check'><span class='icon success'>✓</span> Folder is writable</div>";
                $success[] = "Upload folder writable";
            } else {
                echo "<div class='check error'><span class='icon error'>✗</span> Folder is NOT writable</div>";
                echo "<div class='check warning'><span class='icon warning'>⚠</span> Fix: chmod 755 $uploadPath</div>";
                $errors[] = "Upload folder not writable";
            }
            
            // List files
            $files = scandir($uploadPath);
            $imageFiles = array_filter($files, function($file) use ($uploadPath) {
                return is_file($uploadPath . '/' . $file) && preg_match('/\.(jpg|jpeg|png|webp)$/i', $file);
            });
            
            echo "<div class='check'><span class='icon info'>ℹ</span> Files in folder: " . count($imageFiles) . " images</div>";
            
        } else {
            echo "<div class='check error'><span class='icon error'>✗</span> Folder does not exist: $uploadPath</div>";
            echo "<div class='check warning'><span class='icon warning'>⚠</span> Creating folder...</div>";
            
            if (mkdir($uploadPath, 0755, true)) {
                echo "<div class='check'><span class='icon success'>✓</span> Folder created successfully</div>";
                $success[] = "Upload folder created";
            } else {
                echo "<div class='check error'><span class='icon error'>✗</span> Failed to create folder</div>";
                $errors[] = "Cannot create upload folder";
            }
        }

        // 5. Check database
        echo "<h2>5. Database Connection</h2>";
        require_once __DIR__ . '/app/Config/Database.php';
        $dbConfig = new Config\Database();
        $config = $dbConfig->default;
        
        try {
            $mysqli = new mysqli(
                $config['hostname'],
                $config['username'],
                $config['password'],
                $config['database']
            );
            
            if ($mysqli->connect_error) {
                throw new Exception($mysqli->connect_error);
            }
            
            echo "<div class='check'><span class='icon success'>✓</span> Database connected</div>";
            echo "<div class='check'><span class='icon info'>ℹ</span> Host: {$config['hostname']}, Database: {$config['database']}</div>";
            $success[] = "Database connection OK";
            
            // Check table
            $result = $mysqli->query("SHOW TABLES LIKE 'landing_sliders'");
            if ($result && $result->num_rows > 0) {
                echo "<div class='check'><span class='icon success'>✓</span> Table 'landing_sliders' exists</div>";
                $success[] = "Table exists";
                
                // Check table structure
                $result = $mysqli->query("DESCRIBE landing_sliders");
                echo "<table>";
                echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr><td>{$row['Field']}</td><td>{$row['Type']}</td><td>{$row['Null']}</td><td>{$row['Key']}</td></tr>";
                }
                echo "</table>";
                
                // Count records
                $result = $mysqli->query("SELECT COUNT(*) as count FROM landing_sliders");
                $row = $result->fetch_assoc();
                echo "<div class='check'><span class='icon info'>ℹ</span> Current sliders: {$row['count']}</div>";
                
            } else {
                echo "<div class='check error'><span class='icon error'>✗</span> Table 'landing_sliders' does not exist</div>";
                echo "<div class='check warning'><span class='icon warning'>⚠</span> Run: php spark migrate</div>";
                $errors[] = "Table missing";
            }
            
            $mysqli->close();
            
        } catch (Exception $e) {
            echo "<div class='check error'><span class='icon error'>✗</span> Database error: " . $e->getMessage() . "</div>";
            $errors[] = "Database connection failed";
        }

        // 6. Check controller
        echo "<h2>6. Controller & Routes</h2>";
        $controllerPath = __DIR__ . '/app/Controllers/Admin/Cms/Sliders.php';
        if (file_exists($controllerPath)) {
            echo "<div class='check'><span class='icon success'>✓</span> Controller exists</div>";
            $success[] = "Controller file exists";
            
            $content = file_get_contents($controllerPath);
            if (strpos($content, 'validate') !== false) {
                echo "<div class='check'><span class='icon success'>✓</span> Controller has validation</div>";
            } else {
                echo "<div class='check warning'><span class='icon warning'>⚠</span> Controller might be missing validation</div>";
                $warnings[] = "No validation found";
            }
            
            if (strpos($content, 'try') !== false && strpos($content, 'catch') !== false) {
                echo "<div class='check'><span class='icon success'>✓</span> Controller has error handling</div>";
            } else {
                echo "<div class='check warning'><span class='icon warning'>⚠</span> Controller might be missing error handling</div>";
                $warnings[] = "No error handling found";
            }
        } else {
            echo "<div class='check error'><span class='icon error'>✗</span> Controller not found</div>";
            $errors[] = "Controller file missing";
        }

        // 7. Check views
        echo "<h2>7. View Files</h2>";
        $views = [
            'index' => __DIR__ . '/app/Views/admin/cms/sliders/index.php',
            'form' => __DIR__ . '/app/Views/admin/cms/sliders/form.php',
            'create' => __DIR__ . '/app/Views/admin/cms/sliders/create.php',
            'edit' => __DIR__ . '/app/Views/admin/cms/sliders/edit.php',
        ];
        
        foreach ($views as $name => $path) {
            if (file_exists($path)) {
                echo "<div class='check'><span class='icon success'>✓</span> View '$name' exists</div>";
            } else {
                echo "<div class='check error'><span class='icon error'>✗</span> View '$name' not found</div>";
                $errors[] = "View $name missing";
            }
        }

        // 8. Check model
        echo "<h2>8. Model</h2>";
        $modelPath = __DIR__ . '/app/Models/LandingSliderModel.php';
        if (file_exists($modelPath)) {
            echo "<div class='check'><span class='icon success'>✓</span> Model exists</div>";
            $success[] = "Model file exists";
        } else {
            echo "<div class='check error'><span class='icon error'>✗</span> Model not found</div>";
            $errors[] = "Model file missing";
        }

        // Summary
        echo "<h2>📊 Summary</h2>";
        echo "<div class='check'><span class='icon success'>✓</span> Success: " . count($success) . "</div>";
        echo "<div class='check warning'><span class='icon warning'>⚠</span> Warnings: " . count($warnings) . "</div>";
        echo "<div class='check error'><span class='icon error'>✗</span> Errors: " . count($errors) . "</div>";

        if (count($errors) === 0) {
            echo "<div class='check' style='border-left-color: #28a745; background: #d4edda; margin-top: 20px;'>";
            echo "<h3 style='color: #155724; margin: 0;'>✅ All checks passed!</h3>";
            echo "<p style='margin: 10px 0 0 0;'>Sistem siap untuk upload slider. Silakan test melalui browser.</p>";
            echo "</div>";
        } else {
            echo "<div class='check error' style='margin-top: 20px;'>";
            echo "<h3 style='margin: 0;'>❌ Issues found</h3>";
            echo "<p style='margin: 10px 0 0 0;'>Perbaiki error di atas sebelum melanjutkan.</p>";
            echo "</div>";
        }
        ?>

        <h2>🔗 Quick Links</h2>
        <a href="http://localhost/siakad/admin/cms/sliders" class="btn">Open Sliders Page</a>
        <a href="http://localhost/siakad/admin/cms/sliders/create" class="btn">Create New Slider</a>
        <a href="http://localhost/siakad/regenerate_hash_after_update.php" class="btn">Regenerate Hash</a>

        <h2>📝 Common Issues & Solutions</h2>
        <table>
            <tr>
                <th>Error</th>
                <th>Solution</th>
            </tr>
            <tr>
                <td>uploaded[image]</td>
                <td>File tidak diupload atau field name salah. Pastikan form memiliki enctype="multipart/form-data"</td>
            </tr>
            <tr>
                <td>max_size</td>
                <td>File terlalu besar (> 2MB). Kompres gambar atau tingkatkan upload_max_filesize di php.ini</td>
            </tr>
            <tr>
                <td>is_image</td>
                <td>File bukan gambar. Pastikan upload file JPG, PNG, atau WEBP</td>
            </tr>
            <tr>
                <td>Gagal memindahkan file</td>
                <td>Folder tidak writable. Jalankan: chmod 755 uploads/sliders</td>
            </tr>
            <tr>
                <td>Table not found</td>
                <td>Jalankan migrasi: php spark migrate</td>
            </tr>
            <tr>
                <td>Anti-tampering</td>
                <td>Regenerate hash: php regenerate_hash_after_update.php</td>
            </tr>
        </table>

        <p style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 14px;">
            <strong>Note:</strong> Setelah selesai diagnostic, hapus file ini untuk keamanan.
        </p>
    </div>
</body>
</html>
