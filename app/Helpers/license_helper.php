<?php

function is_local_environment()
{
    // Check environment variable first
    if (env('CI_ENVIRONMENT') === 'production') {
        return false; // Force production mode
    }

    // Check production marker file
    $markerFile = WRITEPATH . '.production_mode';
    if (file_exists($markerFile)) {
        return false; // Marked as production
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $ip = $_SERVER['SERVER_ADDR'] ?? '';

    // Only allow true localhost, not private networks
    $localIPs = ['127.0.0.1', '::1'];
    $isLocalIP = in_array($ip, $localIPs);

    // Check if domain is localhost
    $isLocalHost = (
        strpos($host, 'localhost') !== false ||
        strpos($host, '127.0.0.1') !== false
    );

    return $isLocalIP && $isLocalHost;
}

function generate_machine_id()
{
    $data = [];

    // 1. Installation-specific salt (most important)
    $installId = \App\Libraries\LicenseGuard::getInstallationId();
    $data[] = $installId;

    // 2. Hostname (stable)
    $data[] = php_uname('n');

    // 3. OS type
    $data[] = php_uname('s');

    // 4. Machine architecture
    $data[] = php_uname('m');

    // 5. System user
    $data[] = get_current_user();

    // 6. PHP version (major.minor only for stability)
    $data[] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

    // 7. Try to get hardware-based identifiers (if available and shell_exec is enabled)
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {
        if (PHP_OS_FAMILY === 'Linux') {
            // Try to get CPU info
            $cpuinfo = @shell_exec('cat /proc/cpuinfo | grep "model name" | head -1');
            if ($cpuinfo) {
                $data[] = md5($cpuinfo);
            }

            // Try to get MAC address
            $mac = @shell_exec('cat /sys/class/net/*/address 2>/dev/null | head -1');
            if ($mac) {
                $data[] = trim($mac);
            }
        } elseif (PHP_OS_FAMILY === 'Windows') {
            // Try to get Windows machine GUID
            $guid = @shell_exec('wmic csproduct get uuid 2>nul');
            if ($guid) {
                $data[] = md5($guid);
            }
        }
    } else {
        // Fallback: Use server-specific identifiers when shell_exec is disabled
        // This ensures machine_id is still unique per installation
        if (isset($_SERVER['SERVER_SOFTWARE'])) {
            $data[] = md5($_SERVER['SERVER_SOFTWARE']);
        }
        if (isset($_SERVER['SERVER_SIGNATURE'])) {
            $data[] = md5($_SERVER['SERVER_SIGNATURE']);
        }
    }

    // 8. Document root (helps identify different installations on same server)
    $data[] = md5($_SERVER['DOCUMENT_ROOT'] ?? '');

    return hash('sha256', implode('|', $data));
}

/**
 * Generate hardware-based signature (more secure than machine_id)
 */
function generate_hardware_signature()
{
    $signatures = [];

    // Try to get hardware-specific identifiers
    if (function_exists('shell_exec') && !in_array('shell_exec', explode(',', ini_get('disable_functions')))) {

        if (PHP_OS_FAMILY === 'Linux') {
            // CPU Serial
            $cpuSerial = @shell_exec("cat /proc/cpuinfo | grep Serial | cut -d ' ' -f 2");
            if ($cpuSerial) {
                $signatures[] = 'cpu:' . trim($cpuSerial);
            }

            // Motherboard Serial
            $mbSerial = @shell_exec("sudo dmidecode -s baseboard-serial-number 2>/dev/null");
            if ($mbSerial && !empty(trim($mbSerial))) {
                $signatures[] = 'mb:' . trim($mbSerial);
            }

            // Hard Drive Serial (first drive)
            $hdSerial = @shell_exec("lsblk -d -o SERIAL | grep -v SERIAL | head -1");
            if ($hdSerial) {
                $signatures[] = 'hd:' . trim($hdSerial);
            }

            // MAC Address (first interface)
            $mac = @shell_exec("cat /sys/class/net/*/address 2>/dev/null | head -1");
            if ($mac) {
                $signatures[] = 'mac:' . trim($mac);
            }

        } elseif (PHP_OS_FAMILY === 'Windows') {
            // BIOS Serial
            $biosSerial = @shell_exec("wmic bios get serialnumber 2>nul");
            if ($biosSerial) {
                $signatures[] = 'bios:' . md5($biosSerial);
            }

            // Motherboard Serial
            $mbSerial = @shell_exec("wmic baseboard get serialnumber 2>nul");
            if ($mbSerial) {
                $signatures[] = 'mb:' . md5($mbSerial);
            }

            // Hard Drive Serial
            $hdSerial = @shell_exec("wmic diskdrive get serialnumber 2>nul");
            if ($hdSerial) {
                $signatures[] = 'hd:' . md5($hdSerial);
            }
        }
    }

    // Fallback: Use software-based identifiers
    if (empty($signatures)) {
        $signatures[] = 'host:' . php_uname('n');
        $signatures[] = 'os:' . php_uname('s');
        $signatures[] = 'arch:' . php_uname('m');
        $signatures[] = 'user:' . get_current_user();
        $signatures[] = 'root:' . md5($_SERVER['DOCUMENT_ROOT'] ?? '');
    }

    // Add installation ID
    $signatures[] = 'install:' . \App\Libraries\LicenseGuard::getInstallationId();

    return hash('sha256', implode('|', $signatures));
}

/**
 * Mark installation as production mode
 */
function mark_as_production()
{
    $markerFile = WRITEPATH . '.production_mode';
    return file_put_contents($markerFile, date('Y-m-d H:i:s'));
}

/**
 * Check if installation is marked as production
 */
function is_production_mode()
{
    $markerFile = WRITEPATH . '.production_mode';
    return file_exists($markerFile);
}