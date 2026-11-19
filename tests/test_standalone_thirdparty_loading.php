<?php
// Isolated test to verify that HtmlSanitizer works 100% via ThirdParty bundle
// even if vendor/autoload.php is completely bypassed or absent.

define('APPPATH', dirname(__DIR__) . '/app/');
define('SYSTEMPATH', dirname(__DIR__) . '/system/');
define('ROOTPATH', dirname(__DIR__) . '/tests/non_existent_vendor_dir/');
define('WRITEPATH', dirname(__DIR__) . '/writable/');

require_once SYSTEMPATH . 'Common.php';
require_once APPPATH . 'Common.php';
require_once APPPATH . 'Libraries/HtmlSanitizer.php';

$out = \App\Libraries\HtmlSanitizer::clean('<p>ThirdParty Standalone Test</p><script>alert(1)</script>');
$source = \App\Libraries\HtmlSanitizer::getLoadedSource();

echo "SOURCE:" . $source . "|OUT:" . $out;
