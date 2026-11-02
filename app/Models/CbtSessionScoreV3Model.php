<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CbtSessionScoreV3Model
 *
 * Enforces DEC-ESS-01:
 * When pending_essay_count > 0:
 *   final_score = NULL, report_score = NULL, publication_eligibility = 0
 * When pending_essay_count == 0:
 *   final_score = sum(components), report_score = final_score, publication_eligibility = 1
 */
class CbtSessionScoreV3Model extends Model
{
    protected $table            = 'cbt_session_scores_v3';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'session_id',
        'score_pg',
        'score_esai',
        'score_pg_kompleks',
        'score_bs',
        'final_score',
        'report_score',
        'raw_legacy_score',
        'rounding_delta',
        'pending_essay_count',
        'publication_eligibility',
        'calculated_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'calculated_at';
    protected $updatedField  = 'updated_at';
    protected $dateFormat    = 'datetime';

    /**
     * Consolidate and recalculate session scores.
     * Strictly enforces DEC-ESS-01 publication gating.
     */
    public function recalculateSessionScore(
        int $sessionId,
        float $scorePg,
        ?float $scoreEsai,
        float $scorePgKompleks,
        float $scoreBs,
        int $pendingEssayCount,
        ?string $rawLegacyScore = null,
        float $roundingDelta = 0.0000
    ): array {
        $existing = $this->where('session_id', $sessionId)->first();

        $isEligible = ($pendingEssayCount === 0);
        $finalScore = null;
        $reportScore = null;

        if ($isEligible) {
            $sum = $scorePg + ($scoreEsai ?? 0.0) + $scorePgKompleks + $scoreBs;
            $finalScore = round($sum, 3);
            $reportScore = $finalScore;
        }

        $payload = [
            'session_id'              => $sessionId,
            'score_pg'                => $scorePg,
            'score_esai'              => $scoreEsai,
            'score_pg_kompleks'       => $scorePgKompleks,
            'score_bs'                => $scoreBs,
            'final_score'             => $finalScore,
            'report_score'            => $reportScore,
            'raw_legacy_score'        => $rawLegacyScore,
            'rounding_delta'          => $roundingDelta,
            'pending_essay_count'     => $pendingEssayCount,
            'publication_eligibility' => $isEligible ? 1 : 0,
            'updated_at'              => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->where('session_id', $sessionId)->set($payload)->update();
            $payload['id'] = $existing['id'];
        } else {
            $payload['calculated_at'] = date('Y-m-d H:i:s');
            $id = $this->insert($payload, true);
            $payload['id'] = $id;
        }

        return $payload;
    }
}

