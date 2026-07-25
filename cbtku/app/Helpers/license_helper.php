<?php

function is_local_environment()
{
    // 1. Check CI_ENVIRONMENT
    if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
        return true;
    }

    $host = $_SERVER['HTTP_HOST'] ?? '';
    $addr = $_SERVER['REMOTE_ADDR'] ?? '';

    // 2. Check common local hostnames/TLDs
    $isLocalHost = (
        $host === 'localhost' ||
        $host === '127.0.0.1' ||
        $host === '[::1]' ||
        strpos($host, '.local') !== false ||
        strpos($host, '.test') !== false ||
        strpos($host, '.dev') !== false
    );

    if ($isLocalHost)
        return true;

    // 3. Check private IP ranges
    // 10.0.0.0 - 10.255.255.255
    // 172.16.0.0 - 172.31.255.255
    // 192.168.0.0 - 192.168.255.255
    if (strpos($addr, '192.168.') === 0 || strpos($addr, '10.') === 0) {
        return true;
    }

    if (preg_match('/^172\.(1[6-9]|2[0-9]|3[0-1])\./', $addr)) {
        return true;
    }

    // Also check host for these IPs (in case REMOTE_ADDR is weird)
    if (strpos($host, '192.168.') === 0 || strpos($host, '10.') === 0) {
        return true;
    }

    return false;
}

function generate_machine_id()
{
    $data = [];

    // hostname server (sangat stabil)
    $data[] = php_uname('n');

    // tipe OS
    $data[] = php_uname('s');

    // arsitektur mesin
    $data[] = php_uname('m');

    // user system
    $data[] = get_current_user();

    // versi PHP major (opsional stabilizer)
    $data[] = PHP_MAJOR_VERSION;

    return hash('sha256', implode('|', $data));
}