<?php

namespace App\Libraries;

/**
 * EAKB Core Retrieval Engine & Indexer
 * Mesin Pencarian Inti ESPATA Application Knowledge Base (Tahap 2B)
 * 
 * Standar Mandat: EAO-HO-2026-047
 * Arsitektur: Pure In-Memory Indexing & Deterministic Multi-Field Retrieval
 * Lingkungan: PHP 8.1+ / CodeIgniter 4 (Shared Hosting Friendly, Sub-Millisecond Latency)
 */
class EakbRetrievalEngine
{
    /**
     * Role Pengguna yang Diizinkan Mengakses Asis AI (Mandat Final Human Owner)
     */
    public const ASIS_AI_ALLOWED_ROLES = [1, 2, 3, 7];

    /**
     * Ambang Batas Skor Relevansi Minimum untuk Menganggap Fitur Cocok
     */
    public const MINIMUM_RELEVANCE_SCORE = 1.0;

    /**
     * Indeks Pencarian Statis dalam Memori (Inverted Index Bytecode)
     *
     * @var array|null
     */
    protected static ?array $index = null;

    /**
     * Daftar Kata Henti (Stopwords) Bahasa Indonesia
     */
    protected const INDONESIAN_STOPWORDS = [
        'yang', 'di', 'ke', 'dari', 'pada', 'untuk', 'dengan', 'dan', 'atau',
        'ini', 'itu', 'adalah', 'saya', 'anda', 'kami', 'kita', 'ia', 'dia',
        'mereka', 'akan', 'telah', 'sudah', 'sedang', 'bisa', 'dapat', 'tolong',
        'mohon', 'silakan', 'apa', 'apakah', 'siapa', 'kapan', 'dimana', 'bagaimana',
        'kenapa', 'mengapa', 'adakah', 'bukan', 'tidak', 'tak', 'ada', 'pun',
        'karena', 'oleh', 'seperti', 'sebagai', 'secara', 'harus', 'ingin', 'mau'
    ];

    /**
     * Pembobotan Relevansi Field Katalog
     */
    protected const FIELD_WEIGHTS = [
        'feature_id'       => 20.0,
        'exact_title'      => 15.0,
        'historical_alias' => 15.0,
        'title_token'      => 4.0,
        'category_token'   => 2.5,
        'workflow_step'    => 1.5,
        'faq_token'        => 1.2,
        'validation_token' => 1.0,
        'limitations_token'=> 0.8,
    ];

    // =========================================================================
    // 0. ADVANCED RBAC ENFORCEMENT (MANDAT EAO-HO-2026-049 CHECKPOINT 1)
    // =========================================================================

    /**
     * Memeriksa Apakah Role ID Pengguna Berada dalam Allowlist Resmi Asis AI [1, 2, 3, 7]
     *
     * @param int|null $roleId
     * @return bool
     */
    public static function isAllowedRole(?int $roleId): bool
    {
        return $roleId !== null && in_array($roleId, self::ASIS_AI_ALLOWED_ROLES, true);
    }

    /**
     * Memeriksa Apakah Role Pengguna Berhak Mengakses Fitur Tertentu
     * Aturan: Fail-closed jika role tidak ada di allowlist atau fitur tidak mengizinkan role.
     *
     * @param array|string $feature DTO Fitur atau Feature ID
     * @param int|null $roleId Role ID pengguna
     * @return bool
     */
    public static function canAccessFeature(array|string $feature, ?int $roleId): bool
    {
        if (!self::isAllowedRole($roleId)) {
            return false;
        }

        if (is_string($feature)) {
            $feature = EspataApplicationKnowledge::getFeature($feature);
        }

        if (empty($feature) || !is_array($feature)) {
            return false;
        }

        $allowedRoles = $feature['allowed_roles'] ?? [];
        if (empty($allowedRoles) || !is_array($allowedRoles)) {
            return false; // Fail closed jika allowed_roles kosong atau tidak valid
        }

        return in_array($roleId, $allowedRoles, true);
    }

    /**
     * Menyaring Kumpulan Fitur Sehingga Hanya Fitur yang Sah untuk Role Pengguna yang Lolos
     *
     * @param array $features
     * @param int|null $roleId
     * @return array
     */
    public static function filterAllowedFeatures(array $features, ?int $roleId): array
    {
        if (!self::isAllowedRole($roleId)) {
            return [];
        }

        $filtered = [];
        foreach ($features as $f) {
            if (self::canAccessFeature($f, $roleId)) {
                $filtered[] = $f;
            }
        }

        return $filtered;
    }

    // =========================================================================
    // 1. INDEXER & BUILD MECHANISM (CHECKPOINT 2)
    // =========================================================================

    /**
     * Membangun atau Mengambil Indeks Pencarian EAKB
     * Indeks dibangun murni dari data katalog aktual (EspataApplicationKnowledge).
     *
     * @param bool $forceRebuild Paksa bangun ulang indeks
     * @return array
     */
    public static function getIndex(bool $forceRebuild = false): array
    {
        if (self::$index !== null && !$forceRebuild) {
            return self::$index;
        }

        $features = EspataApplicationKnowledge::getAllFeatures();
        $aliases = EspataApplicationKnowledge::getHistoricalAliases();

        $tokenIndex = [];
        $featureStore = [];

        foreach ($features as $featureId => $feature) {
            $featureStore[$featureId] = $feature;

            // 1. Indeks Feature ID (e.g. 'cbt', 'bank', 'soal')
            $fidTokens = explode('_', strtolower($featureId));
            foreach ($fidTokens as $tok) {
                if (strlen($tok) >= 2 && $tok !== 'feat') {
                    $tokenIndex[$tok][$featureId]['feature_id_token'] = true;
                }
            }

            // 2. Indeks Title
            $titleTokens = self::tokenize($feature['title'] ?? '');
            foreach ($titleTokens as $tok) {
                $tokenIndex[$tok][$featureId]['title_token'] = true;
            }

            // 3. Indeks Category
            $categoryTokens = self::tokenize($feature['category'] ?? '');
            foreach ($categoryTokens as $tok) {
                $tokenIndex[$tok][$featureId]['category_token'] = true;
            }

            // 4. Indeks Workflow Steps
            foreach ($feature['workflow_steps'] ?? [] as $step) {
                $stepTokens = self::tokenize($step);
                foreach ($stepTokens as $tok) {
                    $tokenIndex[$tok][$featureId]['workflow_step'] = true;
                }
            }

            // 5. Indeks Troubleshooting FAQ
            foreach ($feature['troubleshooting_faq'] ?? [] as $faq) {
                $faqTokens = self::tokenize($faq);
                foreach ($faqTokens as $tok) {
                    $tokenIndex[$tok][$featureId]['faq_token'] = true;
                }
            }

            // 6. Indeks Validation Rules & Errors
            foreach ($feature['validation_rules_and_errors'] ?? [] as $val) {
                $valTokens = self::tokenize($val);
                foreach ($valTokens as $tok) {
                    $tokenIndex[$tok][$featureId]['validation_token'] = true;
                }
            }

            // 7. Indeks Known Limitations
            foreach ($feature['known_limitations'] ?? [] as $lim) {
                $limTokens = self::tokenize($lim);
                foreach ($limTokens as $tok) {
                    $tokenIndex[$tok][$featureId]['limitations_token'] = true;
                }
            }
        }

        // 8. Indeks Historical Aliases & Sinonim
        $aliasIndex = [];
        foreach ($aliases as $aliasKey => $aliasData) {
            $normKey = self::normalize($aliasKey);
            $targetFid = $aliasData['target_feature'] ?? null;
            if ($targetFid && isset($featureStore[$targetFid])) {
                $aliasIndex[$normKey] = [
                    'target_feature' => $targetFid,
                    'official_term'  => $aliasData['official_term'] ?? '',
                    'guidance'       => $aliasData['guidance'] ?? '',
                    'type'           => $aliasData['type'] ?? 'SYNONYM',
                ];
            }
        }

        self::$index = [
            'features'       => $featureStore,
            'token_index'    => $tokenIndex,
            'alias_index'    => $aliasIndex,
            'total_features' => count($featureStore),
            'built_at'       => microtime(true),
        ];

        return self::$index;
    }

    /**
     * Bangun Ulang Indeks (Rebuild Index)
     */
    public static function rebuildIndex(): array
    {
        return self::getIndex(true);
    }

    // =========================================================================
    // 2. NORMALISASI & TOKENISASI (CHECKPOINT 1)
    // =========================================================================

    /**
     * Normalisasi Teks Bahasa Indonesia
     */
    public static function normalize(string $text): string
    {
        $text = mb_strtolower(trim($text), 'UTF-8');
        // Ganti karakter non-alfanumerik dengan spasi, pertahankan huruf dan angka
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        // Hilangkan spasi berulang
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Tokenisasi Kalimat Menjadi Token Kata Bersih
     *
     * @param string $text
     * @param bool $removeStopwords
     * @return string[]
     */
    public static function tokenize(string $text, bool $removeStopwords = true): array
    {
        $normalized = self::normalize($text);
        if ($normalized === '') {
            return [];
        }

        $rawTokens = explode(' ', $normalized);
        $cleanTokens = [];

        foreach ($rawTokens as $tok) {
            $tok = trim($tok);
            if (strlen($tok) < 2) {
                continue;
            }
            if ($removeStopwords && in_array($tok, self::INDONESIAN_STOPWORDS, true)) {
                continue;
            }
            $cleanTokens[] = $tok;
        }

        return array_values(array_unique($cleanTokens));
    }

    // =========================================================================
    // 3. CORE RETRIEVAL & SCORING (CHECKPOINT 1, 3, 4)
    // =========================================================================

    /**
     * Lakukan Pencarian Fitur EAKB Berdasarkan Pertanyaan Pengguna
     *
     * @param string $query Pertanyaan atau kata kunci pengguna
     * @param int|null $roleId Role ID pengguna (1, 2, 3, 7)
     * @param array $options Opsi tambahan [limit => int, min_score => float, include_related => bool]
     * @return array Kontrak Hasil Pencarian EAKB
     */
    public static function search(string $query, ?int $roleId = null, array $options = []): array
    {
        $startTime = microtime(true);
        $rawQueryTrimmed = trim(strtolower($query));
        $normQuery = self::normalize($query);
        $tokens = self::tokenize($query);

        $limit = $options['limit'] ?? 5;
        $minScore = $options['min_score'] ?? self::MINIMUM_RELEVANCE_SCORE;

        // 1. Pemeriksaan Keamanan RBAC (Checkpoint 1 Mandat EAO-049: Fail-Closed)
        $allowUnrestricted = $options['allow_unrestricted'] ?? false;
        if (!$allowUnrestricted && !self::isAllowedRole($roleId)) {
            return [
                'query'            => $query,
                'role_id'          => $roleId,
                'is_allowed_role'  => false,
                'total_found'      => 0,
                'status'           => 'ROLE_NOT_ALLOWED',
                'message'          => 'Role ID kosong, tidak valid, atau tidak diizinkan mengakses layanan pengetahuan Asis AI.',
                'latency_ms'       => round((microtime(true) - $startTime) * 1000, 3),
                'results'          => [],
            ];
        }

        if (empty($normQuery) || empty($tokens)) {
            return [
                'query'            => $query,
                'role_id'          => $roleId,
                'is_allowed_role'  => true,
                'total_found'      => 0,
                'status'           => 'EMPTY_QUERY',
                'latency_ms'       => round((microtime(true) - $startTime) * 1000, 3),
                'results'          => [],
            ];
        }

        $index = self::getIndex();
        $features = $index['features'];
        $tokenIndex = $index['token_index'];
        $aliasIndex = $index['alias_index'];

        $featureScores = [];
        $matchedFields = [];
        $hasExactTokenMatch = [];

        // 2. Exact Match & Historical Aliases Matching (Checkpoint 1)
        foreach ($aliasIndex as $aliasKey => $aliasInfo) {
            // Pencocokan alias sebagai kata mandiri atau frase di dalam kueri
            if ($normQuery === $aliasKey || preg_match('/\b' . preg_quote($aliasKey, '/') . '\b/i', $normQuery)) {
                $targetFid = $aliasInfo['target_feature'];
                $featureScores[$targetFid] = ($featureScores[$targetFid] ?? 0) + self::FIELD_WEIGHTS['historical_alias'];
                $matchedFields[$targetFid][] = 'historical_alias:' . $aliasKey;
                $hasExactTokenMatch[$targetFid] = true;
            }
        }

        // 3. Exact Feature ID & Exact Title Matching
        foreach ($features as $fid => $feat) {
            $fidLower = strtolower($fid);
            $cleanFid = str_replace('_', ' ', $fidLower);
            $normTitle = self::normalize($feat['title'] ?? '');
            
            // Exact Feature ID match (e.g. 'feat_cbt_bank_soal', 'cbt_bank_soal', 'feat cbt bank soal')
            if ($rawQueryTrimmed === $fidLower || 
                $rawQueryTrimmed === str_replace('feat_', '', $fidLower) ||
                $normQuery === $cleanFid ||
                $normQuery === str_replace('feat ', '', $cleanFid)) {
                $featureScores[$fid] = ($featureScores[$fid] ?? 0) + self::FIELD_WEIGHTS['feature_id'];
                $matchedFields[$fid][] = 'feature_id:exact';
                $hasExactTokenMatch[$fid] = true;
            }

            // Exact Title Match
            if ($normQuery === $normTitle || str_contains($normQuery, $normTitle)) {
                $featureScores[$fid] = ($featureScores[$fid] ?? 0) + self::FIELD_WEIGHTS['exact_title'];
                $matchedFields[$fid][] = 'title:exact';
                $hasExactTokenMatch[$fid] = true;
            }
        }

        // 4. Token-Based Multi-Field Inverted Index Scoring
        foreach ($tokens as $qToken) {
            $tokenMatchedInFeatures = false;

            // A. Exact Token Match
            if (isset($tokenIndex[$qToken])) {
                $tokenMatchedInFeatures = true;
                foreach ($tokenIndex[$qToken] as $targetFid => $fieldOccurrences) {
                    $hasExactTokenMatch[$targetFid] = true;
                    foreach ($fieldOccurrences as $fieldName => $isMatched) {
                        $baseWeight = self::FIELD_WEIGHTS[$fieldName] ?? 1.0;
                        $featureScores[$targetFid] = ($featureScores[$targetFid] ?? 0) + $baseWeight;
                        $matchedFields[$targetFid][] = $fieldName;
                    }
                }
            }

            // B. Toleransi Salah Ketik (Fuzzy Levenshtein)
            // Hanya aktif jika token query >= 5 karakter dan belum ada exact match pada token ini
            if (!$tokenMatchedInFeatures && strlen($qToken) >= 5) {
                foreach (array_keys($tokenIndex) as $indexedTok) {
                    $lenDiff = abs(strlen($indexedTok) - strlen($qToken));
                    if ($lenDiff <= 2) {
                        $dist = levenshtein($qToken, $indexedTok);
                        // Maksimal jarak 1 untuk kata pendek (5-6), maksimal 2 untuk kata panjang (>=7)
                        $maxAllowedDist = (strlen($qToken) <= 6) ? 1 : 2;
                        if ($dist <= $maxAllowedDist) {
                            $similarityWeight = ($dist === 1) ? 0.70 : 0.45;
                            foreach ($tokenIndex[$indexedTok] as $targetFid => $fieldOccurrences) {
                                // Batasi fuzzy match hanya pada field penting (title, category, feature_id)
                                foreach ($fieldOccurrences as $fieldName => $isMatched) {
                                    if (in_array($fieldName, ['title_token', 'category_token', 'feature_id_token'], true)) {
                                        $baseWeight = self::FIELD_WEIGHTS[$fieldName] ?? 1.0;
                                        $featureScores[$targetFid] = ($featureScores[$targetFid] ?? 0) + ($baseWeight * $similarityWeight);
                                        $matchedFields[$targetFid][] = $fieldName . ':fuzzy';
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // 5. Normalisasi Skor & Filter Berdasarkan Role Pengguna (Checkpoint 3 & 4)
        $scoredResults = [];
        foreach ($featureScores as $fid => $rawScore) {
            $feat = $features[$fid] ?? null;
            if (!$feat) {
                continue;
            }

            // Grounding Safety: Jika sebuah fitur sama sekali tidak memiliki exact token match
            // dan hanya mendapatkan poin dari fuzzy match yang rendah, abaikan untuk mencegah halusinasi
            if (empty($hasExactTokenMatch[$fid]) && $rawScore < 3.0) {
                continue;
            }

            // Filter Role Pengguna: Jika roleId diberikan, fitur harus dapat diakses oleh role tersebut
            if ($roleId !== null && !self::canAccessFeature($feat, $roleId)) {
                continue; // Skip fitur yang tidak boleh diakses oleh role ini
            }

            // Kalibrasi Confidence: Fitur INACTIVE/LIMITED dikalibrasi sesuai bukti empiris EAO-045
            $confidence = $feat['confidence'] ?? 5;
            $confidenceFactor = ($feat['feature_status'] === 'INACTIVE') ? 0.70 : (($feat['feature_status'] === 'LIMITED') ? 0.85 : 1.0);
            
            // Skor Akhir Relevansi
            $finalScore = round($rawScore * $confidenceFactor, 4);

            if ($finalScore >= $minScore) {
                // Mitigasi Backlog EAO-045: Koreksi typo penamaan Berita.php -> Articles.php pada source_reference
                $sourceRef = $feat['source_reference'] ?? '';
                if ($fid === 'feat_cms_school_web' && str_contains($sourceRef, 'Berita.php')) {
                    $sourceRef = str_replace('Berita.php', 'Articles.php', $sourceRef);
                }

                $scoredResults[] = [
                    'feature_id'        => $fid,
                    'title'             => $feat['title'] ?? '',
                    'category'          => $feat['category'] ?? '',
                    'feature_status'    => $feat['feature_status'] ?? 'ACTIVE',
                    'relevance_score'   => $finalScore,
                    'matched_fields'    => array_values(array_unique($matchedFields[$fid] ?? [])),
                    'source_reference'  => $sourceRef,
                    'confidence'        => $confidence,
                    'known_limitations' => $feat['known_limitations'] ?? [],
                    'prerequisites'     => $feat['prerequisites'] ?? [],
                    'workflow_steps'    => $feat['workflow_steps'] ?? [],
                    'validation_rules'  => $feat['validation_rules_and_errors'] ?? [],
                    'troubleshooting'   => $feat['troubleshooting_faq'] ?? [],
                    'related_features'  => $feat['related_features'] ?? [],
                ];
            }
        }

        // 6. Pengurutan Hasil Berdasarkan Skor Tertinggi (Descending)
        usort($scoredResults, fn($a, $b) => $b['relevance_score'] <=> $a['relevance_score']);

        // 7. Pembatasan Jumlah Hasil
        $finalResults = array_slice($scoredResults, 0, $limit);

        $endTime = microtime(true);
        $latencyMs = round(($endTime - $startTime) * 1000, 3);

        return [
            'query'            => $query,
            'role_id'          => $roleId,
            'is_allowed_role'  => true,
            'total_found'      => count($finalResults),
            'status'           => !empty($finalResults) ? 'SUCCESS' : 'NO_MATCH',
            'latency_ms'       => $latencyMs,
            'results'          => $finalResults,
        ];
    }

    /**
     * Helper Cepat: Cari Fitur Berdasarkan Feature ID Eksak
     *
     * @param string $featureId
     * @param int|null $roleId
     * @return array|null
     */
    public static function findById(string $featureId, ?int $roleId = null): ?array
    {
        $search = self::search($featureId, $roleId, ['limit' => 1, 'min_score' => 1.0]);
        return $search['results'][0] ?? null;
    }

    // =========================================================================
    // 4. UNIFIED PIPELINE (MANDAT EAO-HO-2026-049 CHECKPOINT 4)
    // =========================================================================

    /**
     * Eksekusi Terpadu: Retrieval -> RBAC -> Disambiguation -> Graph Traversal
     *
     * @param string $query Pertanyaan pengguna
     * @param int|null $roleId Role ID pengguna otoritatif (1, 2, 3, 7)
     * @param array $options Opsi penelusuran [limit => int, max_related => int]
     * @return array Kontrak hasil akhir EAKB Stage 2C
     */
    public static function resolve(string $query, ?int $roleId, array $options = []): array
    {
        $startTime = microtime(true);

        // 1. Validasi Role Pengguna (Fail Closed)
        if (!self::isAllowedRole($roleId)) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => EakbDisambiguationResolver::STATUS_ROLE_NOT_ALLOWED,
                'is_ambiguous'             => false,
                'reason'                   => 'Role ID kosong, tidak valid, atau tidak diizinkan mengakses layanan pengetahuan Asis AI.',
                'primary_feature'          => null,
                'related_features'         => [],
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // 2. Lakukan Retrieval Awal (Sudah Melalui Filter RBAC Fitur)
        $searchResult = self::search($query, $roleId, $options);

        // 3. Evaluasi Ambiguitas
        $disambiguation = EakbDisambiguationResolver::resolve($searchResult, $options);
        $decisionStatus = $disambiguation['status'];

        // Jika Tidak Ada Hasil atau Role Ditolak
        if ($decisionStatus === EakbDisambiguationResolver::STATUS_NO_MATCH ||
            $decisionStatus === EakbDisambiguationResolver::STATUS_ROLE_NOT_ALLOWED) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => $decisionStatus,
                'is_ambiguous'             => false,
                'reason'                   => $disambiguation['reason'],
                'primary_feature'          => null,
                'related_features'         => [],
                'clarification_candidates' => [],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // Jika Butuh Klarifikasi (Ambiguitas Terdeteksi)
        if ($decisionStatus === EakbDisambiguationResolver::STATUS_NEEDS_CLARIFICATION) {
            return [
                'query'                    => $query,
                'role_id'                  => $roleId,
                'status'                   => EakbDisambiguationResolver::STATUS_NEEDS_CLARIFICATION,
                'is_ambiguous'             => true,
                'reason'                   => $disambiguation['reason'],
                'primary_feature'          => null,
                'related_features'         => [],
                'clarification_candidates' => $disambiguation['clarification_candidates'],
                'latency_ms'               => round((microtime(true) - $startTime) * 1000, 3),
            ];
        }

        // 4. Jika Jelas (RESOLVED): Ambil Fitur Utama & Telusuri Graf Fitur Terkait
        $primaryFeature = $disambiguation['primary_candidate'] ?? null;
        $relatedFeatures = [];

        if ($primaryFeature && isset($primaryFeature['feature_id'])) {
            $maxRelated = $options['max_related'] ?? EakbFeatureGraph::DEFAULT_MAX_RELATED_NODES;
            $graphResult = EakbFeatureGraph::traverse($primaryFeature['feature_id'], $roleId, [
                'max_hops'  => 1,
                'max_nodes' => $maxRelated,
            ]);
            $relatedFeatures = $graphResult['nodes'] ?? [];
        }

        $endTime = microtime(true);
        $latencyMs = round(($endTime - $startTime) * 1000, 3);

        return [
            'query'                    => $query,
            'role_id'                  => $roleId,
            'status'                   => EakbDisambiguationResolver::STATUS_RESOLVED,
            'is_ambiguous'             => false,
            'reason'                   => $disambiguation['reason'],
            'primary_feature'          => $primaryFeature,
            'related_features'         => $relatedFeatures,
            'clarification_candidates' => [],
            'latency_ms'               => $latencyMs,
        ];
    }
}
