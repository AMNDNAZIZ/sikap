<?php

namespace App\Libraries;

/**
 * EAKB Catalog Validator
 * Validator Integritas dan Kepatuhan Katalog Pengetahuan Aplikasi ESPATA
 * 
 * Standar Mandat: EAO-HO-2026-043 (CP4)
 */
class EakbCatalogValidator
{
    public const VALID_FEATURE_STATUSES = [
        'ACTIVE',
        'LIMITED',
        'INACTIVE',
        'PLANNED',
        'UNVERIFIED',
        'OUT_OF_SCOPE',
    ];

    public const VALID_ROLES = [1, 2, 3, 4, 5, 6, 7, 8];

    public const REQUIRED_ATTRIBUTES = [
        'feature_id',
        'title',
        'category',
        'allowed_roles',
        'prerequisites',
        'workflow_steps',
        'validation_rules_and_errors',
        'troubleshooting_faq',
        'feature_status',
        'source_reference',
        'source_version',
        'last_verified_at',
        'confidence',
        'related_features',
        'known_limitations',
    ];

    /**
     * Jalankan validasi lengkap atas katalog EAKB
     *
     * @return array{
     *     is_valid: bool,
     *     total_features: int,
     *     active_count: int,
     *     limited_count: int,
     *     inactive_count: int,
     *     errors: string[],
     *     warnings: string[],
     *     details: array<string, array>
     * }
     */
    public static function validate(): array
    {
        $features = EspataApplicationKnowledge::getAllFeatures();
        $totalFeatures = count($features);
        $errors = [];
        $warnings = [];
        $details = [];

        $allFeatureIds = array_keys($features);
        $activeCount = 0;
        $limitedCount = 0;
        $inactiveCount = 0;

        // 1. Verifikasi Jumlah Total Fitur (Wajib Tepat 70 Fitur)
        if ($totalFeatures !== 70) {
            $errors[] = "Jumlah total fitur dalam katalog adalah {$totalFeatures}, seharusnya tepat 70 fitur.";
        }

        // 2. Pemeriksaan Keunikan Feature ID (Zero Duplicate)
        $idCounts = array_count_values($allFeatureIds);
        foreach ($idCounts as $id => $count) {
            if ($count > 1) {
                $errors[] = "Duplikasi Feature ID terdeteksi: '{$id}' muncul sebanyak {$count} kali.";
            }
        }

        // 3. Iterasi Verifikasi Setiap Fitur
        foreach ($features as $featureId => $feature) {
            $featureErrors = [];

            // A. Verifikasi Kecocokan Key dan feature_id
            if (($feature['feature_id'] ?? null) !== $featureId) {
                $featureErrors[] = "Key katalog '{$featureId}' tidak cocok dengan nilai feature_id '{$feature['feature_id']}'.";
            }

            // B. Kelengkapan 15 Atribut Wajib
            foreach (self::REQUIRED_ATTRIBUTES as $attr) {
                if (!array_key_exists($attr, $feature)) {
                    $featureErrors[] = "Atribut wajib '{$attr}' hilang pada fitur '{$featureId}'.";
                } elseif (empty($feature[$attr]) && !in_array($attr, ['known_limitations', 'prerequisites', 'related_features'], true)) {
                    $featureErrors[] = "Atribut wajib '{$attr}' bernilai kosong pada fitur '{$featureId}'.";
                }
            }

            // C. Validitas Status Fitur
            $status = $feature['feature_status'] ?? 'UNKNOWN';
            if (!in_array($status, self::VALID_FEATURE_STATUSES, true)) {
                $featureErrors[] = "Status fitur tidak valid: '{$status}' pada fitur '{$featureId}'.";
            } else {
                if ($status === 'ACTIVE') {
                    $activeCount++;
                } elseif ($status === 'LIMITED') {
                    $limitedCount++;
                } elseif ($status === 'INACTIVE') {
                    $inactiveCount++;
                }
            }

            // D. Perlindungan Out-of-Scope (bayaran & cbtku dilarang aktif)
            if (preg_match('/bayaran|cbtku/i', $featureId) && $status !== 'OUT_OF_SCOPE') {
                $featureErrors[] = "Fitur '{$featureId}' merupakan subaplikasi yang telah dipisahkan dan dilarang berstatus selain OUT_OF_SCOPE.";
            }

            // E. Validitas Allowed Roles
            $roles = $feature['allowed_roles'] ?? [];
            if (!is_array($roles) || empty($roles)) {
                $featureErrors[] = "allowed_roles harus berupa array tidak kosong pada fitur '{$featureId}'.";
            } else {
                foreach ($roles as $r) {
                    if (!in_array($r, self::VALID_ROLES, true)) {
                        $featureErrors[] = "Role ID tidak sah ({$r}) ditemukan pada fitur '{$featureId}'.";
                    }
                }
            }

            // F. Validitas Referensi Fitur Terkait (related_features)
            $related = $feature['related_features'] ?? [];
            if (is_array($related)) {
                foreach ($related as $relId) {
                    if (!in_array($relId, $allFeatureIds, true)) {
                        $featureErrors[] = "related_features merujuk ke feature_id yang tidak ada: '{$relId}' pada fitur '{$featureId}'.";
                    }
                }
            }

            // G. Validitas Nilai Confidence (Rentang 1..5)
            $confidence = $feature['confidence'] ?? 0;
            if (!is_int($confidence) || $confidence < 1 || $confidence > 5) {
                $featureErrors[] = "Nilai confidence tidak valid ({$confidence}) pada fitur '{$featureId}'. Wajib integer antara 1 sampai 5.";
            }

            // H. Validitas Format last_verified_at (ISO-8601)
            $verifiedAt = $feature['last_verified_at'] ?? '';
            if (empty($verifiedAt) || !preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', $verifiedAt)) {
                $featureErrors[] = "Format last_verified_at tidak valid ('{$verifiedAt}') pada fitur '{$featureId}'.";
            }

            // I. Validitas source_reference
            $srcRef = $feature['source_reference'] ?? '';
            if (empty($srcRef) || strlen($srcRef) < 5) {
                $featureErrors[] = "source_reference wajib menunjuk berkas kode konkret pada fitur '{$featureId}'.";
            }

            // J. Deteksi Kebocoran Rahasia & Data Pribadi (Zero Secret & Zero Student PII)
            $rawJson = json_encode($feature);
            if (preg_match('/(sk-[a-zA-Z0-9]+|gsk_[a-zA-Z0-9]+|api_key|password_hash|\$2y\$10\$)/i', $rawJson)) {
                $featureErrors[] = "POTENSI KEBOCORAN KREDENSIAL/SECRET terdeteksi pada fitur '{$featureId}'.";
            }
            if (preg_match('/(317[0-9]{13}|00[0-9]{8})/i', $rawJson)) {
                // Deteksi format NIK 16 digit atau NISN riil
                $warnings[] = "Peringatan data numerik mirip identitas kependudukan pada fitur '{$featureId}'.";
            }

            // K. Pemeriksaan Keterbatasan Fitur LIMITED dan INACTIVE
            if ($status === 'LIMITED' && empty($feature['known_limitations'])) {
                $featureErrors[] = "Fitur LIMITED '{$featureId}' wajib menyertakan known_limitations.";
            }
            if ($status === 'INACTIVE' && empty($feature['known_limitations'])) {
                $featureErrors[] = "Fitur INACTIVE '{$featureId}' wajib menyertakan known_limitations.";
            }

            $details[$featureId] = [
                'status'    => empty($featureErrors) ? 'VALID' : 'INVALID',
                'errors'    => $featureErrors,
                'role_count'=> count($roles),
                'confidence'=> $confidence,
            ];

            if (!empty($featureErrors)) {
                $errors = array_merge($errors, $featureErrors);
            }
        }

        // 4. Verifikasi Kebijakan Asis AI Allowed Roles
        $asisAllowed = EspataApplicationKnowledge::ASIS_AI_ALLOWED_ROLES;
        $asisDenied = EspataApplicationKnowledge::ASIS_AI_DENIED_ROLES;
        if ($asisAllowed !== [1, 2, 3, 7]) {
            $errors[] = "Kebijakan ASIS_AI_ALLOWED_ROLES tidak sesuai mandat Human Owner [1, 2, 3, 7].";
        }
        if ($asisDenied !== [4, 5, 6, 8]) {
            $errors[] = "Kebijakan ASIS_AI_DENIED_ROLES tidak sesuai mandat Human Owner [4, 5, 6, 8].";
        }

        // 5. Verifikasi Historical Alias P5
        $aliases = EspataApplicationKnowledge::getHistoricalAliases();
        if (!isset($aliases['p5']) || ($aliases['p5']['target_feature'] ?? '') !== 'feat_kok_planning') {
            $errors[] = "Historical alias 'p5' wajib diarahkan ke 'feat_kok_planning'.";
        }

        return [
            'is_valid'       => empty($errors),
            'total_features' => $totalFeatures,
            'active_count'   => $activeCount,
            'limited_count'  => $limitedCount,
            'inactive_count' => $inactiveCount,
            'errors'         => $errors,
            'warnings'       => $warnings,
            'details'        => $details,
        ];
    }
}
