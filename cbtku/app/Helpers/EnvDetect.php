<?php
if (!function_exists('env_detect')) {
    /**
     * Detect if a given PHP extension is loaded and functional.
     * Returns true if the extension exists and a simple test connection succeeds.
     */
    function env_detect(string $extension): bool {
        if (!extension_loaded($extension)) {
            return false;
        }
        // Specific checks for known extensions
        switch (strtolower($extension)) {
            case 'redis':
                try {
                    $client = new Redis();
                    // Attempt a connection to default localhost:6379 with short timeout
                    $client->connect('127.0.0.1', 6379, 0.5);
                    $client->close();
                    return true;
                } catch (Throwable $e) {
                    return false;
                }
            case 'apcu':
                // APCu is considered available if function_exists('apcu_fetch')
                return function_exists('apcu_fetch');
            default:
                return true;
        }
    }
}
?>
