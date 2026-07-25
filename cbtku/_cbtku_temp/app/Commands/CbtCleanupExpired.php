<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;

/**
 * Cleanup Expired CBT Sessions
 * 
 * Usage: php spark cbt:cleanup
 * 
 * Add to crontab (every 5 minutes):
 * star-slash-5 star star star star cd /path/to/cbtku && php spark cbt:cleanup
 */
class CbtCleanupExpired extends BaseCommand
{
    protected $group       = 'CBT';
    protected $name        = 'cbt:cleanup';
    protected $description = 'Auto-submit expired CBT sessions';

    public function run(array $params)
    {
        CLI::write('Starting CBT cleanup...', 'yellow');
        
        $sessionModel = new CbtSessionModel();
        $testModel = new CbtTestStatusModel();
        
        // Get all active sessions
        $activeSessions = $sessionModel
            ->where('status', 'active')
            ->findAll();
        
        if (empty($activeSessions)) {
            CLI::write('No active sessions found.', 'green');
            return;
        }
        
        CLI::write('Found ' . count($activeSessions) . ' active sessions', 'yellow');
        
        $expiredCount = 0;
        $errorCount = 0;
        
        foreach ($activeSessions as $session) {
            try {
                $test = $testModel->find($session['test_id']);
                
                if (!$test) {
                    CLI::write('Test not found for session ' . $session['id'], 'red');
                    continue;
                }
                
                // Check if expired
                if ($this->isSessionExpired($session, $test)) {
                    CLI::write('Auto-submitting expired session: Student ' . $session['student_id'] . ', Test ' . $session['test_id'], 'yellow');
                    
                    // Auto-submit
                    $success = $this->autoSubmit($session['student_id'], $session['test_id']);
                    
                    if ($success) {
                        $expiredCount++;
                        CLI::write('✓ Successfully submitted', 'green');
                    } else {
                        $errorCount++;
                        CLI::write('✗ Failed to submit', 'red');
                    }
                }
            } catch (\Throwable $e) {
                $errorCount++;
                CLI::write('Error processing session ' . $session['id'] . ': ' . $e->getMessage(), 'red');
            }
        }
        
        CLI::write('', 'white');
        CLI::write('Cleanup complete!', 'green');
        CLI::write('Expired sessions: ' . $expiredCount, 'yellow');
        CLI::write('Errors: ' . $errorCount, ($errorCount > 0 ? 'red' : 'green'));
        
        log_message('info', "[CBT-CLEANUP] Processed " . count($activeSessions) . " sessions, expired: $expiredCount, errors: $errorCount");
    }
    
    /**
     * Check if session is expired
     */
    private function isSessionExpired($session, $test): bool
    {
        $startTs = (int)$session['started_at'];
        $baseDuration = ((int)$test['duration']) * 60;
        $extraDuration = ((int)($session['extra_time'] ?? 0)) * 60;
        $durationSec = max(10, $baseDuration + $extraDuration);
        $sessionEndTs = $startTs + $durationSec;
        
        $testEndTs = !empty($test['end_time']) ? strtotime($test['end_time']) : PHP_INT_MAX;
        $effectiveEndTs = min($sessionEndTs, $testEndTs);
        
        return time() > $effectiveEndTs;
    }
    
    /**
     * Auto-submit expired session
     */
    private function autoSubmit($studentId, $testId): bool
    {
        try {
            $sessionModel = new CbtSessionModel();
            $session = $sessionModel
                ->where('student_id', $studentId)
                ->where('test_id', $testId)
                ->first();
            
            if (!$session || $session['status'] === 'finished') {
                return true; // Already finished
            }
            
            // Calculate score
            $score = $this->calculateScore($studentId, $testId);
            
            // Update session
            $sessionModel->update($session['id'], [
                'status' => 'finished',
                'finished_at' => time(),
                'score' => $score,
                'total_score' => $score
            ]);
            
            log_message('info', "[CBT-CLEANUP] Auto-submitted: Student $studentId, Test $testId, Score: $score");
            
            return true;
        } catch (\Throwable $e) {
            log_message('error', '[CBT-CLEANUP] Auto-submit failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculate score (simplified version)
     */
    private function calculateScore($studentId, $testId): float
    {
        $testModel = new CbtTestStatusModel();
        $questionModel = new \App\Models\CbtQuestionModel();
        $answerModel = new \App\Models\CbtAnswerModel();
        
        $test = $testModel->find($testId);
        if (!$test) return 0.0;
        
        $questions = $questionModel->where('bank_id', $test['bank_id'])->findAll();
        if (empty($questions)) return 0.0;
        
        $answers = $answerModel
            ->where('student_id', $studentId)
            ->where('test_id', $testId)
            ->findAll();
        
        $answerMap = [];
        foreach ($answers as $a) {
            $answerMap[$a['question_id']] = $a;
        }
        
        $totalPg = $totalPgk = $totalBs = 0;
        $earnedPg = $earnedPgk = $earnedBs = 0;
        
        foreach ($questions as $q) {
            $type = strtolower($q['question_type'] ?? 'pg');
            $qid = $q['id'];
            $correctStr = $q['correct_option'] ?? '';
            $studentAns = isset($answerMap[$qid]) ? ($answerMap[$qid]['answer'] ?? null) : null;
            
            if (in_array($type, ['pg', 'pilihan_ganda', 'multiple_choice'])) {
                $totalPg++;
                if ($correctStr !== '' && $studentAns !== null && (string)$studentAns === (string)$correctStr) {
                    $earnedPg += 1;
                }
            } elseif ($type === 'pg_kompleks' || $type === 'pgk') {
                $totalPgk++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $correctArr = explode(',', $correctStr);
                    $studentArr = explode(',', $studentAns);
                    $correctSelected = count(array_intersect($studentArr, $correctArr));
                    $incorrectSelected = count(array_diff($studentArr, $correctArr));
                    $totalCorrectOptions = count($correctArr);
                    $rawScore = $correctSelected - (0.5 * $incorrectSelected);
                    $qScore = ($totalCorrectOptions > 0) ? (max(0, $rawScore) / $totalCorrectOptions) : 0;
                    $earnedPgk += $qScore;
                }
            } elseif ($type === 'benar_salah' || $type === 'bs') {
                $totalBs++;
                if (!empty($correctStr) && !empty($studentAns)) {
                    $correctArr = explode(',', $correctStr);
                    $studentArr = explode(',', $studentAns);
                    $matches = 0;
                    $totalItems = count($correctArr);
                    for ($i = 0; $i < $totalItems; $i++) {
                        if (isset($studentArr[$i]) && $studentArr[$i] === $correctArr[$i]) {
                            $matches++;
                        }
                    }
                    $qScore = ($totalItems > 0) ? ($matches / $totalItems) : 0;
                    $earnedBs += $qScore;
                }
            }
        }
        
        $bobotPg = (float)($test['bobot_pg'] ?? 0);
        $bobotPgk = (float)($test['bobot_pg_kompleks'] ?? 0);
        $bobotBs = (float)($test['bobot_bs'] ?? 0);
        
        $nilaiPg = ($totalPg > 0) ? ($earnedPg / $totalPg) * 100 : 0;
        $nilaiPgk = ($totalPgk > 0) ? ($earnedPgk / $totalPgk) * 100 : 0;
        $nilaiBs = ($totalBs > 0) ? ($earnedBs / $totalBs) * 100 : 0;
        
        $finalScore = ($nilaiPg * ($bobotPg / 100)) + ($nilaiPgk * ($bobotPgk / 100)) + ($nilaiBs * ($bobotBs / 100));
        
        return round($finalScore, 2);
    }
}
