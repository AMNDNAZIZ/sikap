<?php

use App\Models\SubjectModel;
use App\Models\TeacherModel;

if (!function_exists('getSubjects')) {
    function getSubjects(): array
    {
        $model = new SubjectModel();
        return $model->select('id, name')->orderBy('name')->findAll();
    }
}

if (!function_exists('getTeachers')) {
    function getTeachers(): array
    {
        $model = new TeacherModel();
        return $model->select('id, name')->orderBy('name')->findAll();
    }
}

function level_name($val) {
    return $val == 1 ? 'SD' : ($val == 2 ? 'SMP' : '-');
}


/**
 * ========================================
 * Last Activity Throttling Functions
 * ========================================
 * Optimize database updates by throttling last_activity updates
 */

if (!function_exists('should_update_last_activity')) {
    /**
     * Check if last_activity should be updated based on throttle interval
     * 
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @param int $throttleSeconds Throttle interval in seconds (default: 30)
     * @return bool
     */
    function should_update_last_activity(int $studentId, int $testId, int $throttleSeconds = 30): bool
    {
        $cacheKey = "last_activity_update_{$studentId}_{$testId}";
        $cache = \Config\Services::cache();
        $lastUpdate = $cache->get($cacheKey);
        
        if ($lastUpdate === null) {
            // First time, allow update
            $cache->save($cacheKey, time(), $throttleSeconds + 10);
            return true;
        }
        
        $elapsed = time() - $lastUpdate;
        
        if ($elapsed >= $throttleSeconds) {
            // Enough time has passed, allow update
            $cache->save($cacheKey, time(), $throttleSeconds + 10);
            return true;
        }
        
        // Too soon, skip update
        return false;
    }
}

if (!function_exists('update_last_activity_throttled')) {
    /**
     * Update last_activity with throttling
     * 
     * @param int $studentId Student ID
     * @param int $testId Test ID
     * @param int $throttleSeconds Throttle interval (default: 30)
     * @return bool True if updated, false if skipped
     */
    function update_last_activity_throttled(int $studentId, int $testId, int $throttleSeconds = 30): bool
    {
        if (!should_update_last_activity($studentId, $testId, $throttleSeconds)) {
            return false; // Skipped due to throttle
        }
        
        try {
            $sessionModel = new \App\Models\CbtSessionModel();
            $sessionModel->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->set(['last_activity' => time()])
                ->update();
            
            return true; // Updated
        } catch (\Throwable $e) {
            log_message('error', '[Throttle] Failed to update last_activity: ' . $e->getMessage());
            return false;
        }
    }
}
