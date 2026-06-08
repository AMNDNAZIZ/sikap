<?php

namespace App\Services;

use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtStudentSessionModel;

/**
 * CBT Session Service
 * 
 * Handles all business logic related to exam sessions
 */
class CbtSessionService extends BaseService
{
    protected $sessionModel;
    protected $testModel;
    protected $db;
    protected $studentSessionModel;

    public function __construct()
    {
        parent::__construct();
        $this->db = db_connect();
        $this->sessionModel = new CbtSessionModel();
        $this->testModel = new CbtTestStatusModel();
        $this->studentSessionModel = new CbtStudentSessionModel();
    }

    /**
     * Start exam session
     * 
     * Creates a new exam session or resumes an existing one.
     * 
     * Behavior:
     * - If session exists and finished: Returns error
     * - If session exists and active: Resumes session
     * - If no session exists: Creates new session
     * 
     * Session Data:
     * - student_id: Student taking the exam
     * - test_id: Test being taken
     * - status: 'active' or 'finished'
     * - started_at: Unix timestamp when session started
     * - question_order: JSON array of question IDs in display order
     * - extra_time: Additional minutes granted (default: 0)
     * 
     * @param int $studentId Student ID taking the exam
     * @param int $testId Test ID being taken
     * @param array $questionOrder Array of question IDs in display order
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     *               - 'data' (array): Session data including:
     *                 - 'session_id' (int): Session ID
     *                 - 'status' (string): 'started' or 'resumed'
     *                 - 'started_at' (int): Unix timestamp
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtSessionService();
     * $result = $service->startSession(1, 10, [1, 2, 3, 4, 5]);
     * if ($result['success']) {
     *     echo "Session ID: " . $result['data']['session_id'];
     * }
     */
    public function startSession(int $studentId, int $testId, array $questionOrder): array
    {
        try {
            // Check if session already exists
            $existing = $this->sessionModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->first();

            if ($existing) {
                if ($existing['status'] === 'finished') {
                    return $this->errorResponse('Ujian sudah selesai');
                }

                // Resume existing session
                return $this->successResponse([
                    'session_id' => $existing['id'],
                    'status' => 'resumed',
                    'started_at' => $existing['started_at']
                ], 'Melanjutkan sesi ujian');
            }

            // Create new session
            $now = time();
            $sessionData = [
                'student_id' => $studentId,
                'test_id' => $testId,
                'status' => 'active',
                'started_at' => $now,
                'question_order' => json_encode($questionOrder),
                'extra_time' => 0
            ];

            $sessionId = $this->sessionModel->insert($sessionData);

            if (!$sessionId) {
                return $this->errorResponse('Gagal membuat sesi ujian');
            }

            $this->logDebug('CbtSessionService::startSession', "Started session {$sessionId} for student {$studentId}");

            return $this->successResponse([
                'session_id' => $sessionId,
                'status' => 'started',
                'started_at' => $now
            ], 'Sesi ujian dimulai');
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::startSession', 'Failed to start session', $e);
            return $this->errorResponse('Terjadi kesalahan saat memulai ujian');
        }
    }

    /**
     * Validate session is active and not expired
     * 
     * Performs comprehensive session validation:
     * 1. Checks if session exists
     * 2. Checks if session is finished
     * 3. Checks if test exists
     * 4. Checks if session has expired (time limit exceeded)
     * 
     * Validation Reasons:
     * - 'session_not_found': Session doesn't exist in database
     * - 'session_finished': Session status is 'finished'
     * - 'test_not_found': Test doesn't exist in database
     * - 'session_expired': Time limit exceeded
     * - 'error': Unexpected error occurred
     * 
     * @param int $studentId Student ID taking the exam
     * @param int $testId Test ID being validated
     * @return array Validation result with keys:
     *               - 'valid' (bool): True if session is valid and active
     *               - 'reason' (string): Reason for invalid session (if invalid)
     *               - 'message' (string): User-friendly message
     *               - 'session' (array): Session data (if valid)
     *               - 'test' (array): Test data (if valid)
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtSessionService();
     * $validation = $service->validateSession(1, 10);
     * if (!$validation['valid']) {
     *     echo "Error: " . $validation['message'];
     *     echo "Reason: " . $validation['reason'];
     * }
     */
    public function validateSession(int $studentId, int $testId): array
    {
        try {
            // Get session
            $session = $this->sessionModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->first();

            if (!$session) {
                return [
                    'valid' => false,
                    'reason' => 'session_not_found',
                    'message' => 'Sesi ujian tidak ditemukan'
                ];
            }

            if ($session['cheat_locked'] ?? false) {
                return [
                    'valid' => false,
                    'reason' => 'cheat_locked',
                    'message' => 'Ujian telah ditutup oleh pengawas (Pelanggaran Anti-Cheat).'
                ];
            }

            if ($session['status'] === 'finished') {
                return [
                    'valid' => false,
                    'reason' => 'session_finished',
                    'message' => 'Ujian sudah selesai'
                ];
            }

            // Get test info
            $test = $this->testModel->find($testId);

            if (!$test) {
                return [
                    'valid' => false,
                    'reason' => 'test_not_found',
                    'message' => 'Ujian tidak ditemukan'
                ];
            }

            // Check if expired
            if ($this->isSessionExpired($session, $test)) {
                return [
                    'valid' => false,
                    'reason' => 'session_expired',
                    'message' => 'Waktu ujian telah habis'
                ];
            }

            return [
                'valid' => true,
                'session' => $session,
                'test' => $test
            ];
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::validateSession', 'Validation failed', $e);
            return [
                'valid' => false,
                'reason' => 'error',
                'message' => 'Terjadi kesalahan validasi'
            ];
        }
    }

    /**
     * Check if session is expired
     * 
     * Determines if a session has exceeded its time limit.
     * 
     * Time Calculation:
     * - Base duration: Test duration in minutes (from test config)
     * - Extra time: Additional minutes granted (from session data)
     * - Grace period: Additional seconds to prevent premature expiration
     * - Total allowed: (duration + extra_time) * 60 + grace_seconds
     * 
     * Formula:
     * elapsed = current_time - started_at
     * expired = elapsed > (duration + extra_time) * 60 + grace_seconds
     * 
     * @param array $session Session data with keys:
     *                       - 'started_at' (int): Unix timestamp when session started
     *                       - 'extra_time' (int): Additional minutes granted
     * @param array $test Test data with keys:
     *                    - 'duration' (int): Test duration in minutes
     * @param int $graceSeconds Grace period in seconds (default: 30)
     *                          Prevents premature expiration due to network delays
     * @return bool True if session has expired, false otherwise
     * 
     * @example
     * $service = new CbtSessionService();
     * $session = ['started_at' => 1708700000, 'extra_time' => 5];
     * $test = ['duration' => 60];
     * 
     * if ($service->isSessionExpired($session, $test)) {
     *     echo "Time's up!";
     * }
     */
    public function isSessionExpired(array $session, array $test, int $graceSeconds = 30): bool
    {
        $startTs = (int) $session['started_at'];
        $durationMin = (int) ($test['duration'] ?? 0);
        $extraMin = (int) ($session['extra_time'] ?? 0);

        $totalDurationSec = ($durationMin + $extraMin) * 60;
        $elapsed = time() - $startTs;

        return $elapsed > ($totalDurationSec + $graceSeconds);
    }

    /**
     * Extend session time
     * 
     * Adds extra minutes to a session's time limit.
     * Useful for accommodating students with special needs or technical issues.
     * 
     * Process:
     * 1. Finds session by student_id and test_id
     * 2. Adds extra minutes to existing extra_time
     * 3. Updates session in database
     * 
     * Note: Extra time is cumulative (adds to existing extra_time)
     * 
     * @param int $studentId Student ID whose session to extend
     * @param int $testId Test ID being extended
     * @param int $extraMinutes Additional minutes to add (positive integer)
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     *               - 'data' (array): Updated session data including:
     *                 - 'extra_time' (int): Total extra minutes granted
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtSessionService();
     * $result = $service->extendTime(1, 10, 15);
     * if ($result['success']) {
     *     echo "Added 15 minutes. Total extra: " . $result['data']['extra_time'];
     * }
     */
    public function extendTime(int $studentId, int $testId, int $extraMinutes): array
    {
        try {
            $session = $this->sessionModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->first();

            if (!$session) {
                return $this->errorResponse('Sesi tidak ditemukan');
            }

            $currentExtra = (int) ($session['extra_time'] ?? 0);
            $newExtra = $currentExtra + $extraMinutes;

            $updated = $this->sessionModel->update($session['id'], [
                'extra_time' => $newExtra
            ]);

            if (!$updated) {
                return $this->errorResponse('Gagal menambah waktu');
            }

            $this->logDebug('CbtSessionService::extendTime', "Extended time by {$extraMinutes} minutes for session {$session['id']}");

            return $this->successResponse([
                'extra_time' => $newExtra
            ], "Waktu ditambah {$extraMinutes} menit");
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::extendTime', 'Failed to extend time', $e);
            return $this->errorResponse('Terjadi kesalahan');
        }
    }

    /**
     * End session
     * 
     * Marks a session as finished and records the finish timestamp.
     * 
     * Process:
     * 1. Finds session by student_id and test_id
     * 2. Checks if already finished (idempotent operation)
     * 3. Updates status to 'finished'
     * 4. Records finished_at timestamp
     * 
     * Note: This is idempotent - calling multiple times is safe
     * 
     * @param int $studentId Student ID whose session to end
     * @param int $testId Test ID being ended
     * @return array Response with keys:
     *               - 'success' (bool): Operation success status
     *               - 'message' (string): Status message
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * $service = new CbtSessionService();
     * $result = $service->endSession(1, 10);
     * if ($result['success']) {
     *     echo "Session ended successfully";
     * }
     */
    public function endSession(int $studentId, int $testId): array
    {
        try {
            $session = $this->sessionModel->where([
                'student_id' => $studentId,
                'test_id' => $testId
            ])->first();

            if (!$session) {
                return $this->errorResponse('Sesi tidak ditemukan');
            }

            if ($session['status'] === 'finished') {
                return $this->successResponse(null, 'Sesi sudah selesai');
            }

            $updated = $this->sessionModel->update($session['id'], [
                'status' => 'finished',
                'finished_at' => time()
            ]);

            if (!$updated) {
                return $this->errorResponse('Gagal mengakhiri sesi');
            }

            $this->logDebug('CbtSessionService::endSession', "Ended session {$session['id']}");

            return $this->successResponse(null, 'Sesi ujian selesai');
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::endSession', 'Failed to end session', $e);
            return $this->errorResponse('Terjadi kesalahan');
        }
    }

    /**
     * Get remaining time for session
     * 
     * Calculates how much time is left in a session.
     * 
     * Calculation:
     * - Total duration = (test.duration + session.extra_time) * 60 seconds
     * - Elapsed = current_time - session.started_at
     * - Remaining = max(0, total_duration - elapsed)
     * 
     * Returns detailed time information for UI display and logic.
     * 
     * @param int $studentId Student ID taking the exam
     * @param int $testId Test ID being taken
     * @return array Time information with keys:
     *               - 'remaining_seconds' (int): Seconds remaining (0 if expired)
     *               - 'remaining_minutes' (int): Minutes remaining (rounded down)
     *               - 'expired' (bool): True if time has run out
     *               - 'total_duration' (int): Total allowed seconds
     *               - 'elapsed' (int): Seconds elapsed since start
     * 
     * @throws \Throwable If database query fails
     * 
     * @example
     * $service = new CbtSessionService();
     * $time = $service->getRemainingTime(1, 10);
     * 
     * if ($time['expired']) {
     *     echo "Time's up!";
     * } else {
     *     echo "Time left: " . $time['remaining_minutes'] . " minutes";
     * }
     */
    public function getRemainingTime(int $studentId, int $testId): array
    {
        try {
            $validation = $this->validateSession($studentId, $testId);

            if (!$validation['valid']) {
                return [
                    'remaining_seconds' => 0,
                    'expired' => true
                ];
            }

            $session = $validation['session'];
            $test = $validation['test'];

            $startTs = (int) $session['started_at'];
            $durationMin = (int) ($test['duration'] ?? 0);
            $extraMin = (int) ($session['extra_time'] ?? 0);

            $totalDurationSec = ($durationMin + $extraMin) * 60;
            $elapsed = time() - $startTs;
            $remaining = max(0, $totalDurationSec - $elapsed);

            return [
                'remaining_seconds' => $remaining,
                'remaining_minutes' => floor($remaining / 60),
                'expired' => $remaining <= 0,
                'total_duration' => $totalDurationSec,
                'elapsed' => $elapsed
            ];
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::getRemainingTime', 'Failed to get time', $e);
            return [
                'remaining_seconds' => 0,
                'expired' => true
            ];
        }
    }

    /**
     * Cleanup expired sessions
     * 
     * Automatically marks expired active sessions as finished.
     * Useful for scheduled cleanup tasks (cron jobs).
     * 
     * Process:
     * 1. Finds all sessions with status='active'
     * 2. Checks each session for expiration (with 5-minute grace period)
     * 3. Marks expired sessions as 'finished'
     * 4. Records finished_at timestamp
     * 
     * Grace Period: 5 minutes (300 seconds)
     * - Prevents premature cleanup during network issues
     * - Allows students to submit even if slightly over time
     * 
     * @return int Number of sessions cleaned up
     * 
     * @throws \Throwable If database operation fails
     * 
     * @example
     * // In a scheduled task (cron job)
     * $service = new CbtSessionService();
     * $cleaned = $service->cleanupExpiredSessions();
     * echo "Cleaned up {$cleaned} expired sessions";
     */
    public function cleanupExpiredSessions(): int
    {
        try {
            $activeSessions = $this->sessionModel->where('status', 'active')->findAll();
            $cleaned = 0;

            foreach ($activeSessions as $session) {
                $test = $this->testModel->find($session['test_id']);

                if ($test && $this->isSessionExpired($session, $test, 300)) { // 5 min grace
                    $this->sessionModel->update($session['id'], [
                        'status' => 'finished',
                        'finished_at' => time()
                    ]);
                    $cleaned++;
                }
            }

            if ($cleaned > 0) {
                $this->logDebug('CbtSessionService::cleanupExpiredSessions', "Cleaned up {$cleaned} expired sessions");
            }

            return $cleaned;
        } catch (\Throwable $e) {
            $this->logError('CbtSessionService::cleanupExpiredSessions', 'Cleanup failed', $e);
            return 0;
        }
    }

    /**
     * Get violation count from logs for a student and test
     * 
     * @param int $studentId
     * @param int $testId
     * @return int
     */
    public function getViolationCount(int $studentId, int $testId): int
    {
        return (int) $this->db->table('cbt_cheat_logs')
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->countAllResults();
    }

    /**
     * Get numeric violation limit based on anti-cheat setting
     * 
     * @param string $antiCheatSetting 'tidak', 'kuat', or 'sangat_kuat'
     * @return int
     */
    public function getViolationLimit(string $antiCheatSetting): int
    {
        if ($antiCheatSetting === 'sangat_kuat')
            return 2;
        if ($antiCheatSetting === 'kuat')
            return 4;
        return 999;
    }

    /**
     * Check and enforce anti-cheat violation limit
     * 
     * @param int $studentId
     * @param int $testId
     * @param string $antiCheatSetting
     * @return array ['is_locked' => bool, 'violation_count' => int, 'violation_limit' => int]
     */
    public function checkAndEnforceCheatLimit(int $studentId, int $testId, string $antiCheatSetting): array
    {
        $count = $this->getViolationCount($studentId, $testId);
        $limit = $this->getViolationLimit($antiCheatSetting);
        $isLocked = ($count >= $limit);

        if ($isLocked) {
            $this->sessionModel->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->set(['cheat_locked' => 'violation_limit'])
                ->update();
        }

        return [
            'is_locked' => $isLocked,
            'violation_count' => $count,
            'violation_limit' => $limit
        ];
    }
}
