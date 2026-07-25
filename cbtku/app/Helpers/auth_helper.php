<?php

/**
 * Auth Helper
 * Helper functions for authentication and authorization
 */

if (!function_exists('update_user_last_activity_throttled')) {
    /**
     * Update user last_activity with throttling
     * Used in Auth filter to prevent excessive database updates
     * 
     * @param int $userId User ID
     * @param int $throttleSeconds Throttle interval (default: 30)
     * @return bool True if updated, false if skipped
     */
    function update_user_last_activity_throttled(int $userId, int $throttleSeconds = 30): bool
    {
        $cacheKey = "user_last_activity_{$userId}";
        $cache = \Config\Services::cache();
        $lastUpdate = $cache->get($cacheKey);

        // Check if enough time has passed
        if ($lastUpdate !== null) {
            $elapsed = time() - $lastUpdate;
            if ($elapsed < $throttleSeconds) {
                return false; // Skip update, too soon
            }
        }

        // Update database
        try {
            $model = new \App\Models\UserModel();
            $request = \Config\Services::request();
            $model->update($userId, [
                'last_ip' => $request->getIPAddress(),
                'user_agent' => $request->getUserAgent()->getAgentString(),
                'last_activity' => date('Y-m-d H:i:s')
            ]);

            // Update cache timestamp
            $cache->save($cacheKey, time(), $throttleSeconds + 10);

            return true; // Updated
        } catch (\Throwable $e) {
            log_message('error', '[Auth Filter] Failed to update last_activity: ' . $e->getMessage());
            return false;
        }
    }
}
