<?php

/**
 * Production Readiness Verification Script
 * 
 * Verifies that the application is ready for production deployment
 */

define('ROOTPATH', __DIR__ . '/');
define('APPPATH', ROOTPATH . 'app/');
define('FCPATH', ROOTPATH . 'public/');

echo "=================================================================\n";
echo "PRODUCTION READINESS VERIFICATION\n";
echo "=================================================================\n\n";

$checks = [];
$warnings = [];
$errors = [];

// 1. PHP Version
echo "1. Checking PHP Version...\n";
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.1.0', '>=')) {
    echo "   ✓ PHP $phpVersion (OK)\n";
    $checks['PHP Version'] = true;
} else {
    echo "   ✗ PHP $phpVersion (Minimum 8.1.0 required)\n";
    $checks['PHP Version'] = false;
    $errors[] = "PHP version too old: $phpVersion";
}
echo "\n";

// 2. PHP Extensions
echo "2. Checking PHP Extensions...\n";
$requiredExtensions = ['intl', 'mbstring', 'json', 'mysqlnd', 'xml', 'curl', 'gd', 'zip', 'fileinfo'];
$missingExtensions = [];

foreach ($requiredExtensions as $ext) {
    if (extension_loaded($ext)) {
        echo "   ✓ $ext\n";
    } else {
        echo "   ✗ $ext (MISSING)\n";
        $missingExtensions[] = $ext;
    }
}

if (empty($missingExtensions)) {
    $checks['PHP Extensions'] = true;
} else {
    $checks['PHP Extensions'] = false;
    $errors[] = "Missing extensions: " . implode(', ', $missingExtensions);
}
echo "\n";

// 3. Environment File
echo "3. Checking Environment Configuration...\n";
$envFile = FCPATH . '.env';

if (file_exists($envFile)) {
    echo "   ✓ .env file exists\n";
    
    $envContent = file_get_contents($envFile);
    
    // Check CI_ENVIRONMENT
    if (preg_match('/CI_ENVIRONMENT\s*=\s*production/', $envContent)) {
        echo "   ✓ CI_ENVIRONMENT = production\n";
    } else {
        echo "   ⚠️  CI_ENVIRONMENT not set to production\n";
        $warnings[] = "CI_ENVIRONMENT should be 'production'";
    }
    
    // Check HTTPS enforcement
    if (preg_match('/forceGlobalSecureRequests\s*=\s*true/', $envContent)) {
        echo "   ✓ HTTPS enforcement enabled\n";
    } else {
        echo "   ⚠️  HTTPS enforcement not enabled\n";
        $warnings[] = "forceGlobalSecureRequests should be true";
    }
    
    // Check CSP
    if (preg_match('/CSPEnabled\s*=\s*true/', $envContent)) {
        echo "   ✓ CSP enabled\n";
    } else {
        echo "   ⚠️  CSP not enabled\n";
        $warnings[] = "CSPEnabled should be true";
    }
    
    // Check database password
    if (preg_match('/database\.default\.password\s*=\s*$/', $envContent)) {
        echo "   ✗ Database password is empty\n";
        $errors[] = "Database password must be set";
    } else {
        echo "   ✓ Database password set\n";
    }
    
    // Check encryption key
    if (preg_match('/encryption\.key\s*=\s*$/', $envContent) || 
        preg_match('/encryption\.key\s*=\s*hex2bin:/', $envContent) === false) {
        echo "   ⚠️  Encryption key not set or invalid\n";
        $warnings[] = "Run: php spark key:generate";
    } else {
        echo "   ✓ Encryption key set\n";
    }
    
    $checks['Environment'] = true;
} else {
    echo "   ✗ .env file not found\n";
    $checks['Environment'] = false;
    $errors[] = ".env file missing";
}
echo "\n";

// 4. Security Files
echo "4. Checking Security Files...\n";

$securityHash = APPPATH . 'Config/.security_hash';
if (file_exists($securityHash)) {
    echo "   ✓ .security_hash exists\n";
    $checks['Security Hash'] = true;
} else {
    echo "   ✗ .security_hash not found\n";
    $checks['Security Hash'] = false;
    $errors[] = "Run: php generate_security_hash.php";
}

$licenseHash = APPPATH . 'Config/.lic_hash';
if (file_exists($licenseHash)) {
    echo "   ✓ .lic_hash exists\n";
    $checks['License Hash'] = true;
} else {
    echo "   ✗ .lic_hash not found\n";
    $checks['License Hash'] = false;
    $errors[] = "Run: php generate_license_hash.php";
}
echo "\n";

// 5. File Permissions
echo "5. Checking File Permissions...\n";

$writableDirs = [
    'writable/cache',
    'writable/logs',
    'writable/session',
    'writable/uploads',
    'public/uploads'
];

$permissionIssues = [];
foreach ($writableDirs as $dir) {
    $path = FCPATH . $dir;
    if (is_dir($path)) {
        if (is_writable($path)) {
            echo "   ✓ $dir is writable\n";
        } else {
            echo "   ✗ $dir is NOT writable\n";
            $permissionIssues[] = $dir;
        }
    } else {
        echo "   ⚠️  $dir does not exist\n";
        $warnings[] = "Directory missing: $dir";
    }
}

if (empty($permissionIssues)) {
    $checks['File Permissions'] = true;
} else {
    $checks['File Permissions'] = false;
    $errors[] = "Fix permissions: " . implode(', ', $permissionIssues);
}
echo "\n";

// 6. Critical Files
echo "6. Checking Critical Files...\n";

$criticalFiles = [
    'app/Config/Filters.php',
    'app/Config/App.php',
    'app/Libraries/SecurityGuard.php',
    'app/Libraries/LicenseGuard.php',
    'app/Filters/RateLimitFilter.php',
    'app/Helpers/password_helper.php',
    'app/Helpers/asset_helper.php'
];

$missingFiles = [];
foreach ($criticalFiles as $file) {
    $path = FCPATH . $file;
    if (file_exists($path)) {
        echo "   ✓ $file\n";
    } else {
        echo "   ✗ $file (MISSING)\n";
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    $checks['Critical Files'] = true;
} else {
    $checks['Critical Files'] = false;
    $errors[] = "Missing files: " . implode(', ', $missingFiles);
}
echo "\n";

// 7. Database Connection (if .env exists)
echo "7. Checking Database Connection...\n";
if (file_exists($envFile)) {
    try {
        // Parse .env for database credentials
        $envLines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $dbConfig = [];
        
        foreach ($envLines as $line) {
            if (strpos($line, 'database.default.') === 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim(str_replace('database.default.', '', $key));
                $value = trim($value);
                $dbConfig[$key] = $value;
            }
        }
        
        if (!empty($dbConfig['hostname']) && !empty($dbConfig['database'])) {
            $host = $dbConfig['hostname'];
            $db = $dbConfig['database'];
            $user = $dbConfig['username'] ?? 'root';
            $pass = $dbConfig['password'] ?? '';
            
            $mysqli = @new mysqli($host, $user, $pass, $db);
            
            if ($mysqli->connect_error) {
                echo "   ✗ Database connection failed: " . $mysqli->connect_error . "\n";
                $checks['Database'] = false;
                $errors[] = "Database connection failed";
            } else {
                echo "   ✓ Database connection successful\n";
                echo "   ✓ Database: $db\n";
                
                // Check if tables exist
                $result = $mysqli->query("SHOW TABLES");
                $tableCount = $result->num_rows;
                
                if ($tableCount > 0) {
                    echo "   ✓ Tables found: $tableCount\n";
                } else {
                    echo "   ⚠️  No tables found (run migrations)\n";
                    $warnings[] = "Run: php spark migrate";
                }
                
                $mysqli->close();
                $checks['Database'] = true;
            }
        } else {
            echo "   ⚠️  Database configuration incomplete\n";
            $warnings[] = "Configure database in .env";
            $checks['Database'] = false;
        }
    } catch (Exception $e) {
        echo "   ✗ Error checking database: " . $e->getMessage() . "\n";
        $checks['Database'] = false;
    }
} else {
    echo "   ⚠️  Skipped (no .env file)\n";
    $checks['Database'] = false;
}
echo "\n";

// 8. Web Server Check
echo "8. Checking Web Server Configuration...\n";

// Check if running via web server
if (php_sapi_name() === 'cli') {
    echo "   ⚠️  Running via CLI (cannot check web server)\n";
    echo "   ℹ️  Verify manually:\n";
    echo "      - Apache/Nginx configured\n";
    echo "      - SSL certificate installed\n";
    echo "      - Virtual host configured\n";
    echo "      - mod_rewrite enabled (Apache)\n";
    $warnings[] = "Verify web server configuration manually";
} else {
    echo "   ✓ Running via web server\n";
    
    // Check HTTPS
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        echo "   ✓ HTTPS enabled\n";
    } else {
        echo "   ⚠️  HTTPS not detected\n";
        $warnings[] = "Enable HTTPS for production";
    }
}
echo "\n";

// 9. Security Headers Check
echo "9. Checking Security Configuration...\n";

$filtersConfig = APPPATH . 'Config/Filters.php';
if (file_exists($filtersConfig)) {
    $content = file_get_contents($filtersConfig);
    
    if (strpos($content, "'secureheaders'") !== false) {
        echo "   ✓ Security headers filter registered\n";
    } else {
        echo "   ⚠️  Security headers not registered\n";
        $warnings[] = "Enable secureheaders filter";
    }
    
    if (strpos($content, "'ratelimit'") !== false) {
        echo "   ✓ Rate limiting filter registered\n";
    } else {
        echo "   ⚠️  Rate limiting not registered\n";
        $warnings[] = "Enable ratelimit filter";
    }
    
    if (strpos($content, "'csrf'") !== false) {
        echo "   ✓ CSRF protection enabled\n";
    } else {
        echo "   ✗ CSRF protection not enabled\n";
        $errors[] = "Enable CSRF protection";
    }
}
echo "\n";

// Summary
echo "=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n";

$passedChecks = array_filter($checks);
$totalChecks = count($checks);
$passedCount = count($passedChecks);

echo "\nChecks Passed: $passedCount / $totalChecks\n\n";

foreach ($checks as $name => $status) {
    echo ($status ? '✓' : '✗') . " $name\n";
}

echo "\n";

// Errors
if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $i => $error) {
        echo "   " . ($i + 1) . ". $error\n";
    }
    echo "\n";
}

// Warnings
if (!empty($warnings)) {
    echo "⚠️  WARNINGS (" . count($warnings) . "):\n";
    foreach ($warnings as $i => $warning) {
        echo "   " . ($i + 1) . ". $warning\n";
    }
    echo "\n";
}

// Final verdict
echo "=================================================================\n";
if (empty($errors) && empty($warnings)) {
    echo "✅ PRODUCTION READY!\n";
    echo "\nAll checks passed. Application is ready for production deployment.\n";
} elseif (empty($errors)) {
    echo "⚠️  READY WITH WARNINGS\n";
    echo "\nApplication can be deployed but address warnings for optimal security.\n";
} else {
    echo "❌ NOT READY FOR PRODUCTION\n";
    echo "\nFix all errors before deploying to production.\n";
}
echo "=================================================================\n";

// Next steps
if (!empty($errors) || !empty($warnings)) {
    echo "\nNEXT STEPS:\n";
    
    if (!empty($errors)) {
        echo "\n1. Fix Critical Errors:\n";
        foreach ($errors as $error) {
            echo "   - $error\n";
        }
    }
    
    if (!empty($warnings)) {
        echo "\n2. Address Warnings:\n";
        foreach ($warnings as $warning) {
            echo "   - $warning\n";
        }
    }
    
    echo "\n3. Run this script again to verify\n";
    echo "\n";
}

echo "=================================================================\n";
