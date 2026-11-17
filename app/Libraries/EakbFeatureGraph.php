<?php

namespace App\Libraries;

/**
 * EAKB Feature Graph Traversal
 * Mesin Penelusuran Graf Fitur ESPATA Application Knowledge Base (Tahap 2C)
 * 
 * Standar Mandat: EAO-HO-2026-049
 * Arsitektur: In-Memory Directed Graph Traversal dengan Pre-Traversal RBAC Filtering
 * Lingkungan: PHP 8.1+ / CodeIgniter 4 (Shared Hosting Friendly, Sub-Millisecond Latency)
 */
class EakbFeatureGraph
{
    /**
     * Kedalaman Traversal Default (1 Hop / One-Hop Mandated)
     */
    public const DEFAULT_MAX_HOPS = 1;

    /**
     * Jumlah Maksimal Node Terkait yang Dikembalikan
     */
    public const DEFAULT_MAX_RELATED_NODES = 3;

    /**
     * Cache Adjacency List dalam Memori
     *
     * @var array|null
     */
    protected static ?array $adjacencyList = null;

    // =========================================================================
    // 1. GRAF BUILDER & ADJACENCY LIST
    // =========================================================================

    /**
     * Membangun atau Mengambil Daftar Ketetanggaan (Adjacency List) dari Katalog Aktual
     *
     * @param bool $forceRebuild Paksa bangun ulang graf
     * @return array<string, string[]>
     */
    public static function getAdjacencyList(bool $forceRebuild = false): array
    {
        if (self::$adjacencyList !== null && !$forceRebuild) {
            return self::$adjacencyList;
        }

        $features = EspataApplicationKnowledge::getAllFeatures();
        $adj = [];

        foreach ($features as $fid => $feature) {
            $rawRelated = $feature['related_features'] ?? [];
            $validEdges = [];

            foreach ($rawRelated as $targetFid) {
                // Validasi keberadaan node tujuan di katalog aktual (tidak mengarang node)
                if (isset($features[$targetFid]) && $targetFid !== $fid) {
                    $validEdges[] = $targetFid;
                }
            }

            // Hapus duplikasi edge pada level node
            $adj[$fid] = array_values(array_unique($validEdges));
        }

        self::$adjacencyList = $adj;
        return self::$adjacencyList;
    }

    /**
     * Bangun Ulang Adjacency List Graf
     */
    public static function rebuildGraph(): array
    {
        return self::getAdjacencyList(true);
    }

    // =========================================================================
    // 2. NEIGHBOR LOOKUP & RBAC FILTERING
    // =========================================================================

    /**
     * Mengambil Node Tetangga Langsung (Hop-1) dari Sebuah Fitur dengan Filter RBAC
     *
     * @param string $featureId Feature ID sumber
     * @param int|null $roleId Role ID pengguna otoritatif (1, 2, 3, 7)
     * @return array Daftar feature DTO tetangga yang lolos otorisasi
     */
    public static function getNeighbors(string $featureId, ?int $roleId = null): array
    {
        $traversal = self::traverse($featureId, $roleId, [
            'max_hops'  => 1,
            'max_nodes' => self::DEFAULT_MAX_RELATED_NODES,
        ]);

        return $traversal['nodes'] ?? [];
    }

    // =========================================================================
    // 3. GRAPH TRAVERSAL (CHECKPOINT 2)
    // =========================================================================

    /**
     * Melakukan Penelusuran Graf Fitur Berbasis Relasi Katalog Aktual
     *
     * Aturan Traversal:
     * 1. Kedalaman dibatasi 1 hop (one-hop).
     * 2. Jumlah maksimal node terkait dibatasi (default: 3).
     * 3. Siklus dan duplikasi dicegah menggunakan visited set.
     * 4. RBAC diperiksa ketat pada setiap node target sebelum dimasukkan ke hasil.
     * 5. Seluruh metadata fitur dipertahankan utuh.
     *
     * @param string $startFeatureId Feature ID awal
     * @param int|null $roleId Role ID pengguna (wajib lolos allowlist resmi)
     * @param array $options Opsi penelusuran [max_hops => int, max_nodes => int]
     * @return array Kontrak hasil graph traversal
     */
    public static function traverse(string $startFeatureId, ?int $roleId = null, array $options = []): array
    {
        $startTime = microtime(true);
        $maxHops = $options['max_hops'] ?? self::DEFAULT_MAX_HOPS;
        $maxNodes = $options['max_nodes'] ?? self::DEFAULT_MAX_RELATED_NODES;

        // 1. Validasi Keberadaan Node Awal di Katalog
        $features = EspataApplicationKnowledge::getAllFeatures();
        $startFeature = $features[$startFeatureId] ?? null;

        if (!$startFeature) {
            return [
                'start_feature'   => $startFeatureId,
                'role_id'         => $roleId,
                'status'          => 'START_NODE_NOT_FOUND',
                'message'         => "Fitur awal '{$startFeatureId}' tidak ditemukan dalam katalog EAKB.",
                'total_nodes'     => 0,
                'latency_ms'      => round((microtime(true) - $startTime) * 1000, 3),
                'nodes'           => [],
            ];
        }

        // 2. Validasi RBAC Role Pengguna (Fail Closed)
        if ($roleId !== null && !EakbRetrievalEngine::isAllowedRole($roleId)) {
            return [
                'start_feature'   => $startFeatureId,
                'role_id'         => $roleId,
                'status'          => 'ROLE_NOT_ALLOWED',
                'message'         => 'Role ID tidak diizinkan mengakses layanan graf pengetahuan EAKB.',
                'total_nodes'     => 0,
                'latency_ms'      => round((microtime(true) - $startTime) * 1000, 3),
                'nodes'           => [],
            ];
        }

        // Jika start feature sendiri tidak boleh diakses oleh role ini, tolak traversal
        if ($roleId !== null && !EakbRetrievalEngine::canAccessFeature($startFeature, $roleId)) {
            return [
                'start_feature'   => $startFeatureId,
                'role_id'         => $roleId,
                'status'          => 'FORBIDDEN_START_NODE',
                'message'         => "Role {$roleId} tidak berhak mengakses fitur awal '{$startFeatureId}'.",
                'total_nodes'     => 0,
                'latency_ms'      => round((microtime(true) - $startTime) * 1000, 3),
                'nodes'           => [],
            ];
        }

        $adj = self::getAdjacencyList();
        $visited = [$startFeatureId => true];
        $results = [];

        // Traversal BFS Level 1 (Hop 1)
        $queue = [[
            'fid'  => $startFeatureId,
            'hop'  => 0,
            'from' => null,
        ]];

        while (!empty($queue) && count($results) < $maxNodes) {
            $curr = array_shift($queue);
            $currFid = $curr['fid'];
            $currHop = $curr['hop'];

            if ($currHop >= $maxHops) {
                continue; // Jangan telusuri lebih dalam dari max_hops
            }

            $neighbors = $adj[$currFid] ?? [];
            foreach ($neighbors as $neighborFid) {
                if (count($results) >= $maxNodes) {
                    break;
                }

                // Pencegahan Siklus & Duplikasi Node
                if (isset($visited[$neighborFid])) {
                    continue;
                }
                $visited[$neighborFid] = true;

                $targetFeature = $features[$neighborFid] ?? null;
                if (!$targetFeature) {
                    continue; // Abaikan jika node tidak valid
                }

                // Pemeriksaan Keamanan RBAC pada Node Terkait (Wajib Lolos)
                if ($roleId !== null && !EakbRetrievalEngine::canAccessFeature($targetFeature, $roleId)) {
                    continue; // JANGAN BOCORKAN FITUR TERLARANG MELALUI GRAF!
                }

                // Resolusi mitigasi Berita.php -> Articles.php jika relevan
                $sourceRef = $targetFeature['source_reference'] ?? '';
                if ($neighborFid === 'feat_cms_school_web' && str_contains($sourceRef, 'Berita.php')) {
                    $sourceRef = str_replace('Berita.php', 'Articles.php', $sourceRef);
                }

                $results[] = [
                    'feature_id'        => $neighborFid,
                    'title'             => $targetFeature['title'] ?? '',
                    'category'          => $targetFeature['category'] ?? '',
                    'feature_status'    => $targetFeature['feature_status'] ?? 'ACTIVE',
                    'relation_type'     => 'GRAPH_RELATED',
                    'connected_from'    => $currFid,
                    'hop_distance'      => $currHop + 1,
                    'source_reference'  => $sourceRef,
                    'confidence'        => $targetFeature['confidence'] ?? 5,
                    'known_limitations' => $targetFeature['known_limitations'] ?? [],
                    'prerequisites'     => $targetFeature['prerequisites'] ?? [],
                    'workflow_steps'    => $targetFeature['workflow_steps'] ?? [],
                    'validation_rules'  => $targetFeature['validation_rules_and_errors'] ?? [],
                    'troubleshooting'   => $targetFeature['troubleshooting_faq'] ?? [],
                    'related_features'  => $targetFeature['related_features'] ?? [],
                ];

                // Siapkan untuk hop berikutnya jika maxHops > 1
                if ($currHop + 1 < $maxHops) {
                    $queue[] = [
                        'fid'  => $neighborFid,
                        'hop'  => $currHop + 1,
                        'from' => $currFid,
                    ];
                }
            }
        }

        $endTime = microtime(true);
        $latencyMs = round(($endTime - $startTime) * 1000, 3);

        return [
            'start_feature'   => $startFeatureId,
            'role_id'         => $roleId,
            'status'          => 'SUCCESS',
            'total_nodes'     => count($results),
            'max_hops'        => $maxHops,
            'max_nodes_limit' => $maxNodes,
            'latency_ms'      => $latencyMs,
            'nodes'           => $results,
        ];
    }
}
