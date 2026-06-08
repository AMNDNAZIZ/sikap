<?php
/**
 * Simple Script to Apply Database Indexes
 * Run: php apply_indexes_simple.php
 */

// Load .env file
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            if ($key === 'database.default.hostname') $hostname = $value;
            if ($key === 'database.default.database') $database = $value;
            if ($key === 'database.default.username') $username = $value;
            if ($key === 'database.default.password') $password = $value;
        }
    }
}

// Fallback to defaults
$hostname = $hostname ?? 'localhost';
$database = $database ?? 'siakad';
$username = $username ?? 'root';
$password = $password ?? '';

echo "═══════════════════════════════════════════════════════════════\n";
echo "  APPLY DATABASE INDEXES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Connecting to database...\n";
echo "Host: $hostname\n";
echo "Database: $database\n";
echo "Username: $username\n\n";

// Connect
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = new mysqli($hostname, $username, $password, $database);
    if ($mysqli->connect_error) {
        throw new Exception($mysqli->connect_error);
    }
    echo "✓ Connected successfully\n\n";
} catch (Exception $e) {
    echo "✗ Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Define indexes to add
$indexes = [
    // Students
    ['table' => 'students', 'name' => 'idx_nis', 'columns' => 'nis'],
    ['table' => 'students', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    ['table' => 'students', 'name' => 'idx_status', 'columns' => 'status'],
    
    // Users
    ['table' => 'users', 'name' => 'idx_username', 'columns' => 'username'],
    ['table' => 'users', 'name' => 'idx_email', 'columns' => 'email'],
    ['table' => 'users', 'name' => 'idx_role_id', 'columns' => 'role_id'],
    
    // Teachers
    ['table' => 'teachers', 'name' => 'idx_nip', 'columns' => 'nip'],
    ['table' => 'teachers', 'name' => 'idx_status', 'columns' => 'status'],
    
    // Attendance
    ['table' => 'attendance', 'name' => 'idx_date', 'columns' => 'date'],
    ['table' => 'attendance', 'name' => 'idx_student_id', 'columns' => 'student_id'],
    ['table' => 'attendance', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    
    // Teaching Assignments
    ['table' => 'teaching_assignments', 'name' => 'idx_teacher_id', 'columns' => 'teacher_id'],
    ['table' => 'teaching_assignments', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    ['table' => 'teaching_assignments', 'name' => 'idx_subject_id', 'columns' => 'subject_id'],
    
    // Grades
    ['table' => 'grades', 'name' => 'idx_student_id', 'columns' => 'student_id'],
    ['table' => 'grades', 'name' => 'idx_subject_id', 'columns' => 'subject_id'],
    ['table' => 'grades', 'name' => 'idx_semester', 'columns' => 'semester'],
    
    // Assessments
    ['table' => 'assessments', 'name' => 'idx_student_id', 'columns' => 'student_id'],
    ['table' => 'assessments', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    ['table' => 'assessments', 'name' => 'idx_subject_id', 'columns' => 'subject_id'],
    
    // CBT Sessions
    ['table' => 'cbt_sessions', 'name' => 'idx_student_id', 'columns' => 'student_id'],
    ['table' => 'cbt_sessions', 'name' => 'idx_test_id', 'columns' => 'test_id'],
    ['table' => 'cbt_sessions', 'name' => 'idx_status', 'columns' => 'status'],
    
    // Agendas
    ['table' => 'agendas', 'name' => 'idx_date', 'columns' => 'date'],
    ['table' => 'agendas', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    ['table' => 'agendas', 'name' => 'idx_teacher_id', 'columns' => 'teacher_id'],
    
    // Materials
    ['table' => 'materials', 'name' => 'idx_class_id', 'columns' => 'class_id'],
    ['table' => 'materials', 'name' => 'idx_subject_id', 'columns' => 'subject_id'],
    
    // Student Notes
    ['table' => 'student_notes', 'name' => 'idx_student_id', 'columns' => 'student_id'],
    ['table' => 'student_notes', 'name' => 'idx_teacher_id', 'columns' => 'teacher_id'],
];

echo "═══════════════════════════════════════════════════════════════\n";
echo "  ADDING INDEXES\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$success = 0;
$skipped = 0;
$errors = 0;

foreach ($indexes as $index) {
    $table = $index['table'];
    $name = $index['name'];
    $columns = $index['columns'];
    
    echo "Adding {$name} to {$table}... ";
    
    // Check if table exists
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows === 0) {
        echo "⚠ Table not found\n";
        $skipped++;
        continue;
    }
    
    // Check if index already exists
    $result = $mysqli->query("SHOW INDEX FROM $table WHERE Key_name = '$name'");
    if ($result->num_rows > 0) {
        echo "⚠ Already exists\n";
        $skipped++;
        continue;
    }
    
    // Add index
    $sql = "ALTER TABLE $table ADD INDEX $name ($columns)";
    try {
        if ($mysqli->query($sql)) {
            echo "✓ Success\n";
            $success++;
        } else {
            echo "✗ Error: " . $mysqli->error . "\n";
            $errors++;
        }
    } catch (Exception $e) {
        echo "✗ Error: " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "✓ Success: $success\n";
echo "⚠ Skipped: $skipped\n";
echo "✗ Errors: $errors\n\n";

// Verify
echo "═══════════════════════════════════════════════════════════════\n";
echo "  VERIFICATION\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$tables = ['students', 'users', 'attendance', 'teaching_assignments', 'grades'];
foreach ($tables as $table) {
    // Check if table exists first
    $checkTable = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($checkTable && $checkTable->num_rows > 0) {
        $result = $mysqli->query("SHOW INDEX FROM $table WHERE Key_name LIKE 'idx_%'");
        if ($result) {
            echo "✓ $table: " . $result->num_rows . " indexes\n";
        }
    } else {
        echo "⚠ $table: Table not found\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "  COMPLETED\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "Expected improvement: 30-50% faster queries\n";
echo "Test your application to see the difference!\n\n";

$mysqli->close();
