<?php

function is_local_environment()
{
    $host = $_SERVER['HTTP_HOST'] ?? '';

    return (
        strpos($host, 'localhost') !== false ||
        strpos($host, '127.0.0.1') !== false ||
        strpos($host, '192.168.') !== false ||
        strpos($host, '10.') !== false
    );
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