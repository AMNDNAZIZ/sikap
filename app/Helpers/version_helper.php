<?php

/**
 * Version Helper
 * 
 * Helper untuk mengambil versi aplikasi dari database changelogs
 */

if (!function_exists('get_app_version')) {
    /**
     * Get latest application version from changelogs table
     * 
     * @return string Version string (e.g., "1.1.1-stable")
     */
    function get_app_version(): string
    {
        static $version = null;

        // Cache version untuk menghindari query berulang
        if ($version !== null) {
            return $version;
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('changelogs');
            
            // Get latest version ordered by release_date DESC
            $changelog = $builder
                ->select('version, is_stable')
                ->orderBy('release_date', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($changelog) {
                $version = $changelog['version'];
                
                // Add stability suffix if marked as stable
                if (!empty($changelog['is_stable']) && $changelog['is_stable'] == 1) {
                    // Check if version already has -stable suffix
                    if (strpos($version, '-stable') === false) {
                        $version .= '-stable';
                    }
                }
            } else {
                // Fallback to constant if no changelog found
                $version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
            }
        } catch (\Exception $e) {
            // Fallback to constant on error
            $version = defined('APP_VERSION') ? APP_VERSION : '1.0.0';
            log_message('error', 'Failed to get app version from database: ' . $e->getMessage());
        }

        return $version;
    }
}

if (!function_exists('get_last_update')) {
    /**
     * Get last update date from latest changelog
     * 
     * @return string Formatted date (e.g., "2026-02-19 22:10")
     */
    function get_last_update(): string
    {
        static $lastUpdate = null;

        // Cache last update untuk menghindari query berulang
        if ($lastUpdate !== null) {
            return $lastUpdate;
        }

        try {
            $db = \Config\Database::connect();
            $builder = $db->table('changelogs');
            
            // Get latest release_date
            $changelog = $builder
                ->select('release_date')
                ->orderBy('release_date', 'DESC')
                ->orderBy('id', 'DESC')
                ->limit(1)
                ->get()
                ->getRowArray();

            if ($changelog && !empty($changelog['release_date'])) {
                // Format date to Y-m-d H:i
                $date = new \DateTime($changelog['release_date']);
                $lastUpdate = $date->format('Y-m-d H:i');
            } else {
                // Fallback to constant if no changelog found
                $lastUpdate = defined('LAST_UPDATE') ? LAST_UPDATE : date('Y-m-d H:i');
            }
        } catch (\Exception $e) {
            // Fallback to constant on error
            $lastUpdate = defined('LAST_UPDATE') ? LAST_UPDATE : date('Y-m-d H:i');
            log_message('error', 'Failed to get last update from database: ' . $e->getMessage());
        }

        return $lastUpdate;
    }
}

if (!function_exists('get_version_badge_class')) {
    /**
     * Get Bootstrap badge class based on version stability
     * 
     * @param string $version Version string
     * @return string Bootstrap badge class
     */
    function get_version_badge_class(string $version): string
    {
        if (strpos($version, '-stable') !== false) {
            return 'badge bg-success';
        } elseif (strpos($version, '-beta') !== false) {
            return 'badge bg-warning text-dark';
        } elseif (strpos($version, '-alpha') !== false) {
            return 'badge bg-danger';
        } elseif (strpos($version, '-dev') !== false) {
            return 'badge bg-secondary';
        }
        
        return 'badge bg-primary';
    }
}
