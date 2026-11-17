<?php

namespace App\Libraries;

/**
 * EAKB Disambiguation Resolver
 * Modul Resolusi Ambiguitas & Klarifikasi Fitur ESPATA Application Knowledge Base (Tahap 2C)
 * 
 * Standar Mandat: EAO-HO-2026-049
 * Arsitektur: Deterministic Multi-Candidate Scoring & Ambiguity Decision Engine
 * Lingkungan: PHP 8.1+ / CodeIgniter 4 (Shared Hosting Friendly, Zero LLM Calls)
 */
class EakbDisambiguationResolver
{
    /**
     * Status Kontrak Hasil Resolusi
     */
    public const STATUS_RESOLVED             = 'RESOLVED';
    public const STATUS_NEEDS_CLARIFICATION  = 'NEEDS_CLARIFICATION';
    public const STATUS_NO_MATCH             = 'NO_MATCH';
    public const STATUS_ROLE_NOT_ALLOWED     = 'ROLE_NOT_ALLOWED';

    /**
     * Parameter Ambang Batas Resolusi Deterministik
     */
    public const MINIMUM_ABSOLUTE_SCORE      = 1.0;
    public const MAX_CLARIFICATION_ITEMS     = 3;
    public const SCORE_DIFF_THRESHOLD        = 2.5;
    public const SCORE_RATIO_THRESHOLD       = 0.75;
    public const DOMINANT_SCORE_THRESHOLD    = 25.0;

    // =========================================================================
    // 1. DISAMBIGUATION EVALUATOR (CHECKPOINT 3)
    // =========================================================================

    /**
     * Mengevaluasi Hasil Pencarian dan Menentukan Apakah Kueri Cukup Jelas atau Butuh Klarifikasi
     *
     * @param array $searchResult Kontrak hasil dari EakbRetrievalEngine::search()
     * @param array $options Opsi tambahan [min_score => float, diff_threshold => float, max_items => int]
     * @return array Kontrak hasil resolusi ambiguitas
     */
    public static function resolve(array $searchResult, array $options = []): array
    {
        $startTime = microtime(true);
        $query = $searchResult['query'] ?? '';
        $roleId = $searchResult['role_id'] ?? null;
        $searchStatus = $searchResult['status'] ?? 'NO_MATCH';

        // 1. Pemeriksaan RBAC Lapis Pertama
        if ($searchStatus === 'ROLE_NOT_ALLOWED' || ($roleId !== null && !EakbRetrievalEngine::isAllowedRole($roleId))) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_ROLE_NOT_ALLOWED,
                'is_ambiguous'             => false,
                'reason'                   => 'Role ID pengguna tidak berwenang mengakses basis pengetahuan EAKB.',
                'primary_candidate'        => null,
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        $candidates = $searchResult['results'] ?? [];
        $totalFound = count($candidates);

        $minScore = $options['min_score'] ?? self::MINIMUM_ABSOLUTE_SCORE;
        $diffThreshold = $options['diff_threshold'] ?? self::SCORE_DIFF_THRESHOLD;
        $ratioThreshold = $options['ratio_threshold'] ?? self::SCORE_RATIO_THRESHOLD;
        $maxItems = $options['max_items'] ?? self::MAX_CLARIFICATION_ITEMS;

        // 2. Kueri Kosong atau Tanpa Hasil Relevan
        if ($totalFound === 0 || empty($candidates)) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_NO_MATCH,
                'is_ambiguous'             => false,
                'reason'                   => 'Tidak ditemukan fitur ESPATA yang cocok dengan pertanyaan ini.',
                'primary_candidate'        => null,
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        $top = $candidates[0];
        $topScore = (float)($top['relevance_score'] ?? 0.0);

        // 3. Skor Tertinggi Terlalu Rendah (< min_score)
        if ($topScore < $minScore) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_NO_MATCH,
                'is_ambiguous'             => false,
                'reason'                   => "Skor kandidat tertinggi ({$topScore}) di bawah ambang batas minimum relevansi ({$minScore}).",
                'primary_candidate'        => null,
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // 4. Hanya Ditemukan 1 Kandidat Lolos RBAC -> Jelas / RESOLVED
        if ($totalFound === 1) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_RESOLVED,
                'is_ambiguous'             => false,
                'reason'                   => 'Tepat satu fitur relevan yang cocok dan terverifikasi untuk role ini.',
                'primary_candidate'        => $top,
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // 5. Evaluasi 2 Kandidat Teratas (Ambiguity vs Dominance)
        $runnerUp = $candidates[1];
        $runnerScore = (float)($runnerUp['relevance_score'] ?? 0.0);
        $scoreDiff = round($topScore - $runnerScore, 4);
        $scoreRatio = ($topScore > 0) ? round($runnerScore / $topScore, 4) : 0.0;

        // Cek Tag Dominan pada Kandidat Pertama
        $matchedFields = $top['matched_fields'] ?? [];
        $hasExactTag = in_array('feature_id:exact', $matchedFields, true) ||
                       in_array('title:exact', $matchedFields, true) ||
                       in_array('historical_alias:p5', $matchedFields, true);

        // Jika kandidat pertama memiliki skor dominan masif (>= 25) atau tag exact dengan selisih memadai (>= 5.0)
        if (($topScore >= self::DOMINANT_SCORE_THRESHOLD && $scoreDiff >= 4.0) || ($hasExactTag && $scoreDiff >= 5.0)) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_RESOLVED,
                'is_ambiguous'             => false,
                'reason'                   => "Kandidat pertama dominan secara definitif (skor: {$topScore}, selisih: {$scoreDiff}).",
                'primary_candidate'        => $top,
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // Kriteria Kebutuhan Klarifikasi (NEEDS_CLARIFICATION):
        // A. Selisih skor sangat tipis (scoreDiff <= diffThreshold) DAN rasio kedekatan >= 0.70
        // B. ATAU kueri umum dengan skor moderat (topScore <= 12.0) dan runner up memiliki rasio >= ratioThreshold
        $isVeryCloseScore = ($scoreDiff <= $diffThreshold && $scoreRatio >= 0.70);
        $isModerateScoreTie = ($topScore <= 12.0 && $scoreRatio >= $ratioThreshold);

        if ($isVeryCloseScore || $isModerateScoreTie) {
            // Bentuk daftar kandidat klarifikasi (maksimal $maxItems yang lolos RBAC)
            $clarificationItems = [];
            $slicedCandidates = array_slice($candidates, 0, $maxItems);

            foreach ($slicedCandidates as $cand) {
                $clarificationItems[] = [
                    'feature_id'         => $cand['feature_id'],
                    'title'              => $cand['title'] ?? '',
                    'category'           => $cand['category'] ?? '',
                    'feature_status'     => $cand['feature_status'] ?? 'ACTIVE',
                    'relevance_score'    => $cand['relevance_score'] ?? 0.0,
                    'clarification_hint' => self::generateHint($cand),
                ];
            }

            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => self::STATUS_NEEDS_CLARIFICATION,
                'is_ambiguous'             => true,
                'reason'                   => "Pertanyaan memiliki beberapa kemungkinan fitur dengan tingkat relevansi yang berdekatan (selisih: {$scoreDiff}, rasio: " . round($scoreRatio * 100) . "%).",
                'primary_candidate'        => null,
                'clarification_candidates' => $clarificationItems,
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // 6. Jika Margin Keunggulan Cukup Jelas -> RESOLVED
        return [
            'query'                    => $query,
            'role_id'                  => $roleId,
            'status'                   => self::STATUS_RESOLVED,
            'is_ambiguous'             => false,
            'reason'                   => "Kandidat pertama memiliki margin keunggulan yang memadai (skor: {$topScore}, selisih: {$scoreDiff}).",
            'primary_candidate'        => $top,
            'clarification_candidates' => [],
            'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
        ];
    }

    /**
     * Membentuk Petunjuk Klarifikasi Singkat dalam Bahasa Indonesia
     *
     * @param array $feature
     * @return string
     */
    protected static function generateHint(array $feature): string
    {
        $cat = $feature['category'] ?? 'Umum';
        $title = $feature['title'] ?? '';
        $status = $feature['feature_status'] ?? 'ACTIVE';

        $statusNote = ($status !== 'ACTIVE') ? " ({$status})" : "";
        return "Modul {$cat} > {$title}{$statusNote}";
    }
}
