<?php
// Mocking necessary constants
define('ROOTPATH', 'c:/xampp/htdocs/siakad/');
define('WRITEPATH', 'c:/xampp/htdocs/siakad/writable/');
define('FCPATH', 'c:/xampp/htdocs/siakad/public/');
// Our corrected logic
$zipName = 'test_patch_v1.zip';
$zipPath = WRITEPATH . 'uploads/' . $zipName;

echo "Target ZIP Path: " . $zipPath . PHP_EOL;

if (!is_dir(WRITEPATH . 'uploads')) {
    echo "Creating uploads directory..." . PHP_EOL;
    mkdir(WRITEPATH . 'uploads', 0755, true);
}

if (!class_exists('ZipArchive')) {
    die("ZipArchive extension not enabled.");
}

$zip = new ZipArchive();
if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
    $zip->addFromString('test.txt', 'This is a test file.');
    $zip->close();

    if (file_exists($zipPath)) {
        echo "SUCCESS: ZIP file created successfully at " . $zipPath . PHP_EOL;
        unlink($zipPath); // Cleanup
    } else {
        echo "FAILURE: ZIP file was not created." . PHP_EOL;
    }
} else {
    echo "FAILURE: Could not open ZIP archive at " . $zipPath . PHP_EOL;
}
