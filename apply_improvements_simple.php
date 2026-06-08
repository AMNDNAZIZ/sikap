<?php

/**
 * Simple Script to Verify Additional Improvements
 */

define('APPPATH', __DIR__ . '/app/');

echo "=================================================================\n";
echo "Verify Additional Performance & Security Improvements\n";
echo "=================================================================\n\n";

$improvements = [];

// 1. Check Rate Limiting Filter
echo "1. Checking Rate Limiting Filter...\n";
$rateLimit = APPPATH . 'Filters/RateLimitFilter.php';
if (file_exists($rateLimit)) {
    echo "   ✓ RateLimitFilter.php exists\n";
    $improvements['Rate Limiting'] = true;
} else {
    echo "   ✗ RateLimitFilter.php not found\n";
    $improvements['Rate Limiting'] = false;
}
echo "\n";

// 2. Check Password Helper
echo "2. Checking Password Helper...\n";
$passwordHelper = APPPATH . 'Helpers/password_helper.php';
if (file_exists($passwordHelper)) {
    echo "   ✓ password_helper.php exists\n";
    $improvements['Password Helper'] = true;
} else {
    echo "   ✗ password_helper.php not found\n";
    $improvements['Password Helper'] = false;
}
echo "\n";

// 3. Check Asset Helper
echo "3. Checking Asset Helper...\n";
$assetHelper = APPPATH . 'Helpers/asset_helper.php';
if (file_exists($assetHelper)) {
    echo "   ✓ asset_helper.php exists\n";
    $improvements['Asset Helper'] = true;
} else {
    echo "   ✗ asset_helper.php not found\n";
    $improvements['Asset Helper'] = false;
}
echo "\n";

// 4. Check Migration
echo "4. Checking Password History Migration...\n";
$migration = APPPATH . 'Database/Migrations/2026-02-25-000002_CreatePasswordHistory.php';
if (file_exists($migration)) {
    echo "   ✓ CreatePasswordHistory migration exists\n";
    $improvements['Password History Migration'] = true;
} else {
    echo "   ✗ CreatePasswordHistory migration not found\n";
    $improvements['Password History Migration'] = false;
}
echo "\n";

// 5. Check Filters Config
echo "5. Checking Filters Configuration...\n";
$filtersConfig = APPPATH . 'Config/Filters.php';
$content = file_get_contents($filtersConfig);

if (strpos($content, 'RateLimitFilter') !== false) {
    echo "   ✓ Rate limit filter registered\n";
    $improvements['Filters Config'] = true;
} else {
    echo "   ✗ Rate limit filter not registered\n";
    $improvements['Filters Config'] = false;
}
echo "\n";

// Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";

$successCount = 0;
foreach ($improvements as $name => $status) {
    echo ($status ? '✓' : '✗') . " {$name}\n";
    if ($status) $successCount++;
}

echo "\n";
echo "Success: {$successCount}/" . count($improvements) . " improvements verified\n";
echo "\n";

if ($successCount === count($improvements)) {
    echo "✓ All improvements are in place!\n";
    echo "\n";
    echo "NEXT STEPS:\n";
    echo "1. Run migration to create password_history table:\n";
    echo "   php spark migrate\n";
    echo "\n";
    echo "2. Regenerate security hash (Filters.php was modified):\n";
    echo "   php regenerate_hash_now.php\n";
    echo "\n";
    echo "3. Test rate limiting:\n";
    echo "   - Try login 6 times with wrong password\n";
    echo "   - Should block after 5 attempts\n";
    echo "\n";
    echo "4. Update your code to use new helpers:\n";
    echo "   - helper('password'); for password validation\n";
    echo "   - helper('asset'); for asset optimization\n";
} else {
    echo "⚠️  Some improvements are missing\n";
    echo "   Check error messages above\n";
}

echo "=================================================================\n";
