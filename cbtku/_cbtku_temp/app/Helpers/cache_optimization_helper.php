<?php

/**
 * Cache Optimization Helper
 * 
 * Helper functions untuk caching data statis yang sering diakses
 * Mengurangi query database dan meningkatkan performa
 */

if (!function_exists('get_cached_test')) {
    /**
     * Get test data with caching
     * 
     * @param int $testId Test ID
     * @param int $ttl Cache TTL in seconds (default: 1 hour)
     * @return array|null Test data or null
     */
    function get_cached_test(int $testId, int $ttl = 3600): ?array
    {
        $cache = \Config\Services::cache();
        $cacheKey = "test_data_{$testId}";
        
        $test = $cache->get($cacheKey);
        
        if ($test === null) {
            $testModel = new \App\Models\CbtTestStatusModel();
            
            $test = $testModel
                ->select('
                    cbt_test_status.*,
                    cbt_question_banks.code AS bank_code,
                    subjects.name AS subject_name,
                    cbt_exam_names.name AS exam_name
                ')
                ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
                ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
                ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                ->find($testId);
            
            if ($test) {
                $cache->save($cacheKey, $test, $ttl);
                log_message('debug', "[CacheHelper] Cached test data for test_id={$testId}");
            }
        } else {
            log_message('debug', "[CacheHelper] Retrieved test data from cache for test_id={$testId}");
        }
        
        return $test;
    }
}

if (!function_exists('clear_cached_test')) {
    /**
     * Clear cached test data
     * Call this when test data is updated
     * 
     * @param int $testId Test ID
     * @return bool Success status
     */
    function clear_cached_test(int $testId): bool
    {
        $cache = \Config\Services::cache();
        $cacheKey = "test_data_{$testId}";
        
        $result = $cache->delete($cacheKey);
        log_message('info', "[CacheHelper] Cleared cache for test_id={$testId}");
        
        return $result;
    }
}

if (!function_exists('get_cached_student')) {
    /**
     * Get student data with caching
     * 
     * @param int $studentId Student ID
     * @param int $ttl Cache TTL in seconds (default: 30 minutes)
     * @return array|null Student data or null
     */
    function get_cached_student(int $studentId, int $ttl = 1800): ?array
    {
        $cache = \Config\Services::cache();
        $cacheKey = "student_data_{$studentId}";
        
        $student = $cache->get($cacheKey);
        
        if ($student === null) {
            $studentModel = new \App\Models\StudentModel();
            
            $student = $studentModel
                ->select('students.*, classes.name AS class_name')
                ->join('classes', 'classes.id = students.class_id', 'left')
                ->find($studentId);
            
            if ($student) {
                $cache->save($cacheKey, $student, $ttl);
                log_message('debug', "[CacheHelper] Cached student data for student_id={$studentId}");
            }
        } else {
            log_message('debug', "[CacheHelper] Retrieved student data from cache for student_id={$studentId}");
        }
        
        return $student;
    }
}

if (!function_exists('clear_cached_student')) {
    /**
     * Clear cached student data
     * Call this when student data is updated
     * 
     * @param int $studentId Student ID
     * @return bool Success status
     */
    function clear_cached_student(int $studentId): bool
    {
        $cache = \Config\Services::cache();
        $cacheKey = "student_data_{$studentId}";
        
        $result = $cache->delete($cacheKey);
        log_message('info', "[CacheHelper] Cleared cache for student_id={$studentId}");
        
        return $result;
    }
}

if (!function_exists('get_cached_questions')) {
    /**
     * Get questions for a bank with caching
     * 
     * @param int $bankId Bank ID
     * @param int $ttl Cache TTL in seconds (default: 2 hours)
     * @return array Questions array
     */
    function get_cached_questions(int $bankId, int $ttl = 7200): array
    {
        $cache = \Config\Services::cache();
        $cacheKey = "questions_bank_{$bankId}";
        
        $questions = $cache->get($cacheKey);
        
        if ($questions === null) {
            $questionModel = new \App\Models\CbtQuestionModel();
            
            $questions = $questionModel
                ->where('bank_id', $bankId)
                ->orderBy('id', 'ASC')
                ->findAll();
            
            $cache->save($cacheKey, $questions, $ttl);
            log_message('debug', "[CacheHelper] Cached questions for bank_id={$bankId}, count=" . count($questions));
        } else {
            log_message('debug', "[CacheHelper] Retrieved questions from cache for bank_id={$bankId}");
        }
        
        return $questions;
    }
}

if (!function_exists('clear_cached_questions')) {
    /**
     * Clear cached questions for a bank
     * Call this when questions are updated
     * 
     * @param int $bankId Bank ID
     * @return bool Success status
     */
    function clear_cached_questions(int $bankId): bool
    {
        $cache = \Config\Services::cache();
        $cacheKey = "questions_bank_{$bankId}";
        
        $result = $cache->delete($cacheKey);
        log_message('info', "[CacheHelper] Cleared cache for bank_id={$bankId}");
        
        return $result;
    }
}

if (!function_exists('warm_cache_for_test')) {
    /**
     * Warm up cache for a test (preload data)
     * Call this before test starts to ensure fast access
     * 
     * @param int $testId Test ID
     * @return array Status of cache warming
     */
    function warm_cache_for_test(int $testId): array
    {
        $result = [
            'test' => false,
            'questions' => false,
            'message' => ''
        ];
        
        try {
            // Cache test data
            $test = get_cached_test($testId);
            if ($test) {
                $result['test'] = true;
                
                // Cache questions
                $bankId = $test['bank_id'] ?? null;
                if ($bankId) {
                    $questions = get_cached_questions($bankId);
                    $result['questions'] = !empty($questions);
                }
                
                $result['message'] = 'Cache warmed successfully';
            } else {
                $result['message'] = 'Test not found';
            }
        } catch (\Throwable $e) {
            $result['message'] = 'Error: ' . $e->getMessage();
            log_message('error', "[CacheHelper] Cache warming failed: " . $e->getMessage());
        }
        
        return $result;
    }
}

if (!function_exists('clear_all_test_cache')) {
    /**
     * Clear all test-related cache
     * Use with caution - only when necessary
     * 
     * @return bool Success status
     */
    function clear_all_test_cache(): bool
    {
        $cache = \Config\Services::cache();
        
        // Clear all cache with test_ prefix
        $result = $cache->clean();
        
        log_message('info', "[CacheHelper] Cleared all test cache");
        
        return $result;
    }
}
