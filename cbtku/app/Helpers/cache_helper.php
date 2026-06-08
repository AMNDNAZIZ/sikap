<?php

/**
 * Cache Helper untuk Optimasi Performa CBT
 * 
 * Helper ini menyediakan fungsi-fungsi caching yang mudah digunakan
 * untuk meningkatkan performa aplikasi CBT
 */

if (!function_exists('cache_remember')) {
    /**
     * Get item from cache, or execute callback and store result
     * 
     * @param string $key Cache key
     * @param int $ttl Time to live in seconds
     * @param callable $callback Function to execute if cache miss
     * @return mixed
     */
    function cache_remember(string $key, int $ttl, callable $callback)
    {
        $cache = \Config\Services::cache();
        
        $data = $cache->get($key);
        
        if ($data !== null) {
            return $data;
        }
        
        $data = $callback();
        $cache->save($key, $data, $ttl);
        
        return $data;
    }
}

if (!function_exists('cache_forget')) {
    /**
     * Remove item from cache
     * 
     * @param string $key Cache key
     * @return bool
     */
    function cache_forget(string $key): bool
    {
        return \Config\Services::cache()->delete($key);
    }
}

if (!function_exists('cache_flush')) {
    /**
     * Clear all cache
     * 
     * @return bool
     */
    function cache_flush(): bool
    {
        return \Config\Services::cache()->clean();
    }
}

if (!function_exists('cache_tags')) {
    /**
     * Clear cache by tag pattern
     * 
     * @param string $pattern Pattern to match (e.g., 'test_*')
     * @return int Number of items deleted
     */
    function cache_tags(string $pattern): int
    {
        $cache = \Config\Services::cache();
        $cacheInfo = $cache->getCacheInfo();
        
        $deleted = 0;
        if (is_array($cacheInfo)) {
            foreach ($cacheInfo as $key => $value) {
                if (fnmatch($pattern, $key)) {
                    if ($cache->delete($key)) {
                        $deleted++;
                    }
                }
            }
        }
        
        return $deleted;
    }
}

if (!function_exists('cache_test_data')) {
    /**
     * Cache test data (questions, config, etc.)
     * 
     * @param int $testId Test ID
     * @param string $type Data type (questions, config, etc.)
     * @param callable $callback Function to get data
     * @param int $ttl Time to live (default: 1 hour)
     * @return mixed
     */
    function cache_test_data(int $testId, string $type, callable $callback, int $ttl = 3600)
    {
        $key = "test_{$testId}_{$type}";
        return cache_remember($key, $ttl, $callback);
    }
}

if (!function_exists('cache_student_data')) {
    /**
     * Cache student data
     * 
     * @param int $studentId Student ID
     * @param callable $callback Function to get data
     * @param int $ttl Time to live (default: 30 minutes)
     * @return mixed
     */
    function cache_student_data(int $studentId, callable $callback, int $ttl = 1800)
    {
        $key = "student_{$studentId}";
        return cache_remember($key, $ttl, $callback);
    }
}

if (!function_exists('invalidate_test_cache')) {
    /**
     * Invalidate all cache related to a test
     * 
     * @param int $testId Test ID
     * @return int Number of items deleted
     */
    function invalidate_test_cache(int $testId): int
    {
        return cache_tags("test_{$testId}_*");
    }
}

if (!function_exists('invalidate_student_cache')) {
    /**
     * Invalidate all cache related to a student
     * 
     * @param int $studentId Student ID
     * @return int Number of items deleted
     */
    function invalidate_student_cache(int $studentId): int
    {
        return cache_tags("student_{$studentId}*");
    }
}
