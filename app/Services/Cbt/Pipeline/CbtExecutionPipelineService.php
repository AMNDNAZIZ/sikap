<?php

namespace App\Services\Cbt\Pipeline;

use App\Models\CbtAnswerV3Model;
use App\Models\CbtAuditTrailV3Model;
use App\Models\CbtEssayGradeV3Model;
use App\Models\CbtSessionItemModel;
use App\Models\CbtSessionScoreV3Model;

/**
 * CbtExecutionPipelineService
 *
 * Pragmatic end-to-end execution pipeline for ESPATA CBT V3.
 * Coordinates:
 * - Session creation & item allocation (cbt_sessions, cbt_session_items)
 * - Real-time student answer submission (cbt_answers_v3)
 * - Automated objective scoring (PG, BS, Kompleks)
 * - Session submission & DEC-ESS-01 publication gating (cbt_session_scores_v3)
 * - Teacher essay grading & append-only revision history (cbt_essay_grades_v3)
 * - Tamper-evident HMAC hash chain audit logging (cbt_audit_trail_v3)
 */
class CbtExecutionPipelineService
{
    private CbtAnswerV3Model $answerModel;
    private CbtSessionItemModel $sessionItemModel;
    private CbtEssayGradeV3Model $essayGradeModel;
    private CbtSessionScoreV3Model $sessionScoreModel;
    private CbtAuditTrailV3Model $auditTrailModel;

    public function __construct(
        ?CbtAnswerV3Model $answerModel = null,
        ?CbtSessionItemModel $sessionItemModel = null,
        ?CbtEssayGradeV3Model $essayGradeModel = null,
        ?CbtSessionScoreV3Model $sessionScoreModel = null,
        ?CbtAuditTrailV3Model $auditTrailModel = null
    ) {
        $this->answerModel = $answerModel ?? new CbtAnswerV3Model();
        $this->sessionItemModel = $sessionItemModel ?? new CbtSessionItemModel();
        $this->essayGradeModel = $essayGradeModel ?? new CbtEssayGradeV3Model();
        $this->sessionScoreModel = $sessionScoreModel ?? new CbtSessionScoreV3Model();
        $this->auditTrailModel = $auditTrailModel ?? new CbtAuditTrailV3Model();
    }

    /**
     * Submit or update a student answer.
     * Instantly evaluates objective items (PG, BS) and leaves essays pending.
     */
    public function submitAnswer(
        int $sessionId,
        int $itemVersionId,
        int $studentId,
        string $questionType,
        mixed $answerPayload,
        ?string $correctKey = null,
        float $itemWeight = 1.0,
        ?int $expectedVersion = null
    ): array {
        $questionType = strtolower($questionType);
        $allocatedScore = null;
        $isDoubtful = 0;

        // Automated objective grading
        if (in_array($questionType, ['pg', 'benar_salah', 'bs'], true)) {
            $isCorrect = (string)$answerPayload === (string)$correctKey;
            $allocatedScore = $isCorrect ? $itemWeight : 0.0;
        } elseif ($questionType === 'esai' || $questionType === 'essay') {
            // DEC-ESS-01: Essay is left unallocated until manual grading
            $allocatedScore = null;
        }

        $canonicalHash = hash('sha256', "{$sessionId}|{$itemVersionId}|{$studentId}|" . (is_string($answerPayload) ? $answerPayload : json_encode($answerPayload)));

        // Save answer via optimistic concurrency fencing
        $answer = $this->answerModel->saveAnswerAtomically(
            $sessionId,
            $itemVersionId,
            $studentId,
            is_string($answerPayload) ? $answerPayload : json_encode($answerPayload),
            $isDoubtful,
            $canonicalHash,
            $expectedVersion,
            $allocatedScore
        );

        // Record audit trail event only if answer was actually created or modified
        if (!isset($answer['version_incremented']) || $answer['version_incremented'] !== false) {
            $this->auditTrailModel->appendLog(
                'ANSWER_SUBMITTED',
                'cbt_answers_v3',
                (string)$answer['id'],
                $studentId,
                'STUDENT',
                [
                    'session_id'      => $sessionId,
                    'item_version_id' => $itemVersionId,
                    'question_type'   => $questionType,
                    'allocated_score' => $allocatedScore,
                    'answer_version'  => $answer['answer_version'],
                ]
            );
        }

        return $answer;
    }

    /**
     * Finalize session pengerjaan and compute initial scores.
     * Enforces DEC-ESS-01: If essays remain pending, final_score is NULL.
     */
    public function finishSession(int $sessionId, array $itemTypesMap): array
    {
        // 1. Fetch all answers for session
        $answers = $this->answerModel->where('session_id', $sessionId)->findAll();

        $scorePg = 0.0;
        $scoreBs = 0.0;
        $scorePgKompleks = 0.0;
        $scoreEsai = 0.0;
        $pendingEssayCount = 0;

        foreach ($answers as $ans) {
            $itemVerId = (int)$ans['item_version_id'];
            $type = strtolower($itemTypesMap[$itemVerId] ?? 'pg');
            $score = $ans['allocated_score'] !== null ? (float)$ans['allocated_score'] : null;

            if ($type === 'esai' || $type === 'essay') {
                if ($score === null) {
                    $pendingEssayCount++;
                } else {
                    $scoreEsai += $score;
                }
            } elseif ($type === 'benar_salah' || $type === 'bs') {
                $scoreBs += ($score ?? 0.0);
            } elseif ($type === 'pg_kompleks') {
                $scorePgKompleks += ($score ?? 0.0);
            } else {
                $scorePg += ($score ?? 0.0);
            }
        }

        // 2. Recalculate session score record
        $scoreRecord = $this->sessionScoreModel->recalculateSessionScore(
            $sessionId,
            $scorePg,
            $pendingEssayCount > 0 ? null : $scoreEsai,
            $scorePgKompleks,
            $scoreBs,
            $pendingEssayCount
        );

        // 3. Record audit trail event (non-blocking for mission-critical scoring)
        try {
            $this->auditTrailModel->appendLog(
                'SESSION_FINISHED',
                'cbt_session_scores_v3',
                (string)$scoreRecord['id'],
                $sessionId,
                'SYSTEM',
                [
                    'session_id'              => $sessionId,
                    'pending_essay_count'     => $pendingEssayCount,
                    'publication_eligibility' => $scoreRecord['publication_eligibility'],
                    'final_score'             => $scoreRecord['final_score'],
                ]
            );
        } catch (\Throwable $e) {
            log_message('warning', '[CbtExecutionPipelineService::finishSession] Audit trail recording failed non-fatally: ' . $e->getMessage());
        }

        return $scoreRecord;
    }

    /**
     * Grade an essay answer with append-only revision history.
     * Updates answer score and triggers automatic score recalculation.
     */
    public function gradeEssay(
        int $answerId,
        int $sessionId,
        int $graderId,
        float $scoreAwarded,
        ?string $feedback,
        array $itemTypesMap
    ): array {
        // 1. Record append-only revision in cbt_essay_grades_v3
        $gradeRevision = $this->essayGradeModel->recordGradeRevision(
            $answerId,
            $sessionId,
            $graderId,
            $scoreAwarded,
            $feedback
        );

        // 2. Update answer score in cbt_answers_v3
        $this->answerModel->update($answerId, [
            'allocated_score' => $scoreAwarded,
        ]);

        // 3. Recalculate session score
        $scoreRecord = $this->finishSession($sessionId, $itemTypesMap);

        // 4. Record audit trail event
        $this->auditTrailModel->appendLog(
            'ESSAY_GRADED',
            'cbt_essay_grades_v3',
            (string)$gradeRevision['id'],
            $graderId,
            'TEACHER',
            [
                'answer_id'               => $answerId,
                'session_id'              => $sessionId,
                'version_number'          => $gradeRevision['version_number'],
                'score_awarded'           => $scoreAwarded,
                'pending_essay_count'     => $scoreRecord['pending_essay_count'],
                'publication_eligibility' => $scoreRecord['publication_eligibility'],
                'final_score'             => $scoreRecord['final_score'],
            ]
        );

        return [
            'revision'     => $gradeRevision,
            'session_score'=> $scoreRecord,
        ];
    }
}
