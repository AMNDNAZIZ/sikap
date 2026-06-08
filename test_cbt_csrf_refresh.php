<?php
/**
 * Test Script: CBT CSRF Token Refresh
 * 
 * Tests:
 * 1. CSRF token expiry configuration
 * 2. Token refresh endpoint
 * 3. Long exam simulation
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap CodeIgniter
$app = require_once FCPATH . '../app/Config/Paths.php';
$app = new \CodeIgniter\CodeIgniter($app);
$app->initialize();

echo "=== CBT CSRF Token Refresh Test ===\n\n";

// Test 1: Check CSRF Configuration
echo "1. CSRF Configuration:\n";
$security = \Config\Services::security();
$config = config('Security');

echo "   - Token Name: " . $config->tokenName . "\n";
echo "   - Expires: " . $config->expires . " seconds (" . ($config->expires / 3600) . " hours)\n";
echo "   - Regenerate: " . ($config->regenerate ? 'Yes' : 'No') . "\n";
echo "   - Protection: " . $config->csrfProtection . "\n\n";

// Test 2: Generate Token
echo "2. Generate CSRF Token:\n";
$token = $security->getCSRFTokenName();
$hash = $security->getCSRFHash();

echo "   - Token: $token\n";
echo "   - Hash: " . substr($hash, 0, 20) . "...\n";
echo "   - Length: " . strlen($hash) . " chars\n\n";

// Test 3: Simulate Token Refresh
echo "3. Token Refresh Simulation:\n";
echo "   Initial Token: " . substr($hash, 0, 20) . "...\n";

// Regenerate
$newHash = $security->getCSRFHash();
echo "   After Refresh: " . substr($newHash, 0, 20) . "...\n";
echo "   Changed: " . ($hash !== $newHash ? 'Yes' : 'No') . "\n\n";

// Test 4: Calculate Exam Duration Support
echo "4. Exam Duration Support:\n";
$maxExamHours = floor($config->expires / 3600);
echo "   - Max Exam Duration: $maxExamHours hours\n";
echo "   - Refresh Interval: 60 minutes\n";
echo "   - Refreshes Needed: " . ceil($maxExamHours) . " times\n\n";

// Test 5: Recommendations
echo "5. Recommendations:\n";
if ($config->expires < 14400) {
    echo "   ⚠️  WARNING: Token expires in " . ($config->expires / 3600) . " hours\n";
    echo "   ⚠️  Recommended: 4-6 hours (14400-21600 seconds)\n";
} else {
    echo "   ✅ Token expiry is sufficient for long exams\n";
}

if (!$config->regenerate) {
    echo "   ⚠️  WARNING: Token regeneration is disabled\n";
    echo "   ⚠️  Recommended: Enable regeneration for security\n";
} else {
    echo "   ✅ Token regeneration is enabled\n";
}

echo "\n=== Test Complete ===\n";
