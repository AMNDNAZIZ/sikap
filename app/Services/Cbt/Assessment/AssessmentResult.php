<?php

namespace App\Services\Cbt\Assessment;

/**
 * AssessmentResult
 *
 * ESPATA Assessment Intelligence V3 - Stage D / Stage E
 * Immutable Value Object representing the complete assessment evaluation for a student.
 *
 * V3.26 Update:
 * Supports nullable finalScore and reportScore to strictly differentiate between:
 * - A valid earned score of 0.00 (student attempted questions and got 0)
 * - An unavailable score (null) due to ENTIRE_TEST_VOID / NO_VALID_QUESTIONS.
 */
class AssessmentResult
{
    private string|int $studentId;
    private string|int $testId;
    private string|int|null $sessionId;

    private array $statuses;

    private ?float $finalScore;
    private ?float $reportScore;
    private float $objectiveScore;
    private float $essayScore;

    private array $weightDetails;
    private array $itemsBreakdown;
    private array $tpEvaluations;
    private bool $isFullyGraded;
    private array $metadata;

    public function __construct(
        string|int $studentId,
        string|int $testId,
        string|int|null $sessionId,
        array $statuses,
        ?float $finalScore,
        ?float $reportScore,
        float $objectiveScore,
        float $essayScore,
        array $weightDetails,
        array $itemsBreakdown,
        array $tpEvaluations,
        bool $isFullyGraded,
        array $metadata = []
    ) {
        $this->studentId = $studentId;
        $this->testId = $testId;
        $this->sessionId = $sessionId;
        $this->statuses = $statuses;
        $this->finalScore = $finalScore !== null ? round(min(100.0, max(0.0, $finalScore)), 2) : null;
        $this->reportScore = $reportScore !== null ? round(min(100.0, max(0.0, $reportScore)), 2) : null;
        $this->objectiveScore = round($objectiveScore, 2);
        $this->essayScore = round($essayScore, 2);
        $this->weightDetails = $weightDetails;
        $this->itemsBreakdown = $itemsBreakdown;
        $this->tpEvaluations = $tpEvaluations;
        $this->isFullyGraded = $isFullyGraded;
        $this->metadata = $metadata;
    }

    public function getStudentId(): string|int
    {
        return $this->studentId;
    }

    public function getTestId(): string|int
    {
        return $this->testId;
    }

    public function getSessionId(): string|int|null
    {
        return $this->sessionId;
    }

    public function getStatuses(): array
    {
        return $this->statuses;
    }

    public function getStatus(string $domain): ?string
    {
        return $this->statuses[$domain] ?? null;
    }

    public function getFinalScore(): ?float
    {
        return $this->finalScore;
    }

    public function getReportScore(): ?float
    {
        return $this->reportScore;
    }

    public function hasScore(): bool
    {
        return $this->finalScore !== null;
    }

    public function getObjectiveScore(): float
    {
        return $this->objectiveScore;
    }

    public function getEssayScore(): float
    {
        return $this->essayScore;
    }

    public function getWeightDetails(): array
    {
        return $this->weightDetails;
    }

    public function getItemsBreakdown(): array
    {
        return $this->itemsBreakdown;
    }

    public function getTpEvaluations(): array
    {
        return $this->tpEvaluations;
    }

    public function isFullyGraded(): bool
    {
        return $this->isFullyGraded;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * Check if student passed a specified passing grade.
     */
    public function isPassing(float $passingGrade): bool
    {
        if ($this->finalScore === null) {
            return false;
        }
        return $this->finalScore >= $passingGrade;
    }

    /**
     * Check if result is eligible to be synchronized to the gradebook.
     * Guaranteed false if finalScore is null or test has no valid questions.
     */
    public function isEligibleForGradebook(): bool
    {
        if ($this->finalScore === null) {
            return false;
        }
        return AssessmentStatusResolver::isEligibleForGradebook($this->statuses);
    }

    /**
     * Serialize to array.
     */
    public function toArray(): array
    {
        return [
            'student_id' => $this->studentId,
            'test_id' => $this->testId,
            'session_id' => $this->sessionId,
            'statuses' => $this->statuses,
            'final_score' => $this->finalScore,
            'report_score' => $this->reportScore,
            'has_score' => $this->hasScore(),
            'objective_score' => $this->objectiveScore,
            'essay_score' => $this->essayScore,
            'is_fully_graded' => $this->isFullyGraded,
            'is_eligible_for_gradebook' => $this->isEligibleForGradebook(),
            'weight_details' => $this->weightDetails,
            'items_breakdown' => $this->itemsBreakdown,
            'tp_evaluations' => $this->tpEvaluations,
            'metadata' => $this->metadata,
        ];
    }

    /**
     * Serialize to JSON.
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
