<?php
// Test script untuk debug viewDocument route

echo "<h2>Test ViewDocument Route</h2>";

// Test 1: Check if route exists
echo "<h3>1. Test Route</h3>";
echo "URL: <a href='http://localhost:8080/siakad/admin/teachers/viewDocument/1' target='_blank'>http://localhost:8080/siakad/admin/teachers/viewDocument/1</a><br>";

// Test 2: Check if file exists
define('FCPATH', __DIR__ . '/public/');
define('UPLOAD_PATH', FCPATH . 'uploads/');

$uploadPath = UPLOAD_PATH . 'teacher_documents/';
echo "<h3>2. Check Upload Path</h3>";
echo "Upload Path: " . $uploadPath . "<br>";
echo "Exists: " . (is_dir($uploadPath) ? 'YES' : 'NO') . "<br>";

if (is_dir($uploadPath)) {
    $files = scandir($uploadPath);
    echo "Files: <pre>" . print_r($files, true) . "</pre>";
}

// Test 3: Check database
echo "<h3>3. Check Database</h3>";
try {
    require __DIR__ . '/vendor/autoload.php';
    
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    $db = new mysqli(
        $_ENV['database_default_hostname'] ?? 'localhost',
        $_ENV['database_default_username'] ?? 'root',
        $_ENV['database_default_password'] ?? '',
        $_ENV['database_default_database'] ?? 'siakad'
    );
    
    if ($db->connect_error) {
        echo "Connection failed: " . $db->connect_error;
    } else {
        echo "Database connected!<br>";
        
        $result = $db->query("SELECT * FROM teacher_documents LIMIT 5");
        if ($result) {
            echo "Documents in DB:<br>";
            while ($row = $result->fetch_assoc()) {
                echo "ID: {$row['id']}, Title: {$row['title']}, Filename: {$row['filename']}<br>";
            }
        } else {
            echo "No documents found or table doesn't exist<br>";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
