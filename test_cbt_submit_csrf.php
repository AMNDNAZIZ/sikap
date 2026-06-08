<?php
/**
 * Test Script: CBT Submit CSRF Token
 * 
 * Script ini untuk testing apakah CSRF token dikirim dengan benar
 * saat submit ujian CBT dan apakah auto-retry mechanism bekerja.
 * 
 * CARA PAKAI:
 * 1. Akses via browser: http://localhost/siakad/test_cbt_submit_csrf.php
 * 2. Atau jalankan via CLI: php test_cbt_submit_csrf.php
 */

// Define constants if not defined
if (!defined('APPPATH')) {
    define('APPPATH', __DIR__ . '/app/');
}

echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║         TEST: CBT Submit CSRF Token Verification            ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

// Test 1: Check Security Config
echo "TEST 1: Security Configuration\n";
echo str_repeat("-", 60) . "\n";

$securityConfigPath = APPPATH . 'Config/Security.php';
if (file_exists($securityConfigPath)) {
    $content = file_get_contents($securityConfigPath);
    
    // Extract expires value
    if (preg_match('/public\s+int\s+\$expires\s*=\s*(\d+)/', $content, $matches)) {
        $expires = (int)$matches[1];
        echo "✓ CSRF Expires: $expires seconds (" . ($expires / 3600) . " hours)\n";
        
        if ($expires < 21600) {
            echo "⚠️  WARNING: CSRF expires < 6 hours, may cause issues for long exams\n";
        } else {
            echo "✅ CSRF expires is sufficient for long exams\n";
        }
    }
    
    // Extract regenerate value
    if (preg_match('/public\s+bool\s+\$regenerate\s*=\s*(true|false)/', $content, $matches)) {
        $regenerate = $matches[1] === 'true' ? 'YES' : 'NO';
        echo "✓ CSRF Regenerate: $regenerate\n";
    }
    
    // Extract protection method
    if (preg_match('/public\s+string\s+\$csrfProtection\s*=\s*[\'"](\w+)[\'"]/', $content, $matches)) {
        echo "✓ CSRF Protection: " . $matches[1] . "\n";
    }
} else {
    echo "❌ Security.php not found\n";
}
echo "\n";

// Test 2: Check if refreshCsrf endpoint exists
echo "TEST 2: RefreshCsrf Endpoint\n";
echo str_repeat("-", 60) . "\n";

$controllerPath = APPPATH . 'Controllers/Siswa/Cbt.php';
if (file_exists($controllerPath)) {
    $content = file_get_contents($controllerPath);
    
    if (strpos($content, 'function refreshCsrf') !== false) {
        echo "✅ refreshCsrf() method exists in Cbt controller\n";
        
        // Check if it returns token and hash
        if (strpos($content, "'token' => \$newToken") !== false && 
            strpos($content, "'hash' => \$newHash") !== false) {
            echo "✅ refreshCsrf() returns token and hash\n";
        } else {
            echo "❌ refreshCsrf() does not return proper token/hash\n";
        }
    } else {
        echo "❌ refreshCsrf() method NOT FOUND in Cbt controller\n";
    }
} else {
    echo "❌ Cbt controller file not found\n";
}
echo "\n";

// Test 3: Check ujian.php view for CSRF token in submit
echo "TEST 3: CSRF Token in Submit Function\n";
echo str_repeat("-", 60) . "\n";

$viewPath = APPPATH . 'Views/siswa/cbt/ujian.php';
if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    
    // Check if submitExam sends CSRF token
    if (strpos($content, 'function submitExam') !== false) {
        echo "✅ submitExam() function exists\n";
        
        // Check if CSRF token is sent in body
        if (preg_match('/body:\s*JSON\.stringify\(\{[^}]*\[csrfToken\]:\s*csrfHash/s', $content)) {
            echo "✅ CSRF token is sent in submit request body\n";
        } else {
            echo "❌ CSRF token NOT sent in submit request body\n";
        }
        
        // Check for auto-retry on 403
        if (strpos($content, 'r.status === 403') !== false && 
            strpos($content, 'refreshCsrf') !== false) {
            echo "✅ Auto-retry mechanism exists for 403 errors\n";
        } else {
            echo "❌ Auto-retry mechanism NOT FOUND for 403 errors\n";
        }
        
        // Check for window.csrfToken usage
        if (strpos($content, 'window.csrfToken') !== false && 
            strpos($content, 'window.csrfHash') !== false) {
            echo "✅ Uses window.csrfToken and window.csrfHash\n";
        } else {
            echo "❌ Does not use window.csrfToken/csrfHash\n";
        }
    } else {
        echo "❌ submitExam() function NOT FOUND\n";
    }
} else {
    echo "❌ ujian.php view file not found\n";
}
echo "\n";

// Test 4: Check saveAnswer for comparison
echo "TEST 4: SaveAnswer CSRF Implementation (for comparison)\n";
echo str_repeat("-", 60) . "\n";

if (file_exists($viewPath)) {
    $content = file_get_contents($viewPath);
    
    if (strpos($content, 'function commitAnswer') !== false || 
        strpos($content, 'const commitAnswer') !== false) {
        echo "✅ commitAnswer() function exists\n";
        
        // Check if it uses FormData with CSRF
        if (strpos($content, 'fd.append(csrfToken, csrfHash)') !== false) {
            echo "✅ commitAnswer() sends CSRF token via FormData\n";
        }
        
        // Check for auto-retry on 403
        if (strpos($content, 'response.status === 403') !== false) {
            echo "✅ commitAnswer() has auto-retry for 403 errors\n";
        }
    }
}
echo "\n";

// Test 5: Routes check
echo "TEST 5: Required Routes\n";
echo str_repeat("-", 60) . "\n";

$routesPath = APPPATH . 'Config/Routes.php';
if (file_exists($routesPath)) {
    $content = file_get_contents($routesPath);
    
    $requiredRoutes = [
        'siswa/cbt/submit',
        'siswa/cbt/saveAnswer',
        'siswa/cbt/refreshCsrf'
    ];
    
    foreach ($requiredRoutes as $route) {
        // Routes might be defined implicitly via controller
        echo "ℹ️  Route: $route (check manually if needed)\n";
    }
} else {
    echo "⚠️  Routes.php not found\n";
}
echo "\n";

// Summary
echo "╔══════════════════════════════════════════════════════════════╗\n";
echo "║                         SUMMARY                              ║\n";
echo "╚══════════════════════════════════════════════════════════════╝\n\n";

echo "CHECKLIST:\n";
echo "□ CSRF expires >= 6 hours (21600 seconds)\n";
echo "□ refreshCsrf() method exists and returns token/hash\n";
echo "□ submitExam() sends CSRF token in request body\n";
echo "□ submitExam() has auto-retry mechanism for 403\n";
echo "□ Uses window.csrfToken and window.csrfHash\n";
echo "□ commitAnswer() also has CSRF protection (for comparison)\n\n";

echo "NEXT STEPS:\n";
echo "1. Test manually dengan login sebagai siswa\n";
echo "2. Mulai ujian CBT\n";
echo "3. Buka Developer Console (F12)\n";
echo "4. Jawab semua soal\n";
echo "5. Klik 'Selesaikan Ujian'\n";
echo "6. Check console logs untuk:\n";
echo "   - [Submit] CSRF token may be expired, refreshing...\n";
echo "   - [CSRF] Token refreshed, retrying submit...\n";
echo "7. Verify redirect ke halaman hasil\n\n";

echo "MONITORING:\n";
echo "- Browser Console: Check for CSRF-related logs\n";
echo "- Network Tab: Check POST requests to submit/refreshCsrf\n";
echo "- Server Logs: writable/logs/log-*.log\n\n";

echo "═══════════════════════════════════════════════════════════════\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "═══════════════════════════════════════════════════════════════\n";
