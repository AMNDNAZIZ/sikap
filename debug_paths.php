<?php
require 'public/index.php'; // This might not work as it executes the app
// Alternative: just define what we need or check CI4 bootstrap
echo "ROOTPATH: " . ROOTPATH . PHP_EOL;
echo "WRITEPATH: " . WRITEPATH . PHP_EOL;
echo "FCPATH: " . FCPATH . PHP_EOL;
echo "APPPATH: " . APPPATH . PHP_EOL;
include 'app/Config/Constants.php';
echo "UPLOAD_PATH: " . UPLOAD_PATH . PHP_EOL;
$zipName = 'test.zip';
$zipPath = WRITEPATH . UPLOAD_PATH . '' . $zipName;
echo "Resulting Zip Path: " . $zipPath . PHP_EOL;
