<?php

namespace App\Services\Cbt\Analysis;

use CodeIgniter\Database\BaseConnection;

/**
 * CbtTpMappingService
 *
 * Layanan Penyelarasan Materi & Pemetaan Butir Soal ke TP Berbasis AI
 * untuk Asesmen Formatif (1 Pokok Materi) dan Sumatif (Multi Pokok Materi).
 */
class CbtTpMappingService
{
    protected ?BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db;
    }

    protected function getDb(): ?BaseConnection
    {
        if ($this->db === null && class_exists('\Config\Database') && function_exists('config')) {
            $this->db = \Config\Database::connect();
        }
        return $this->db;
    }

    /**
     * Dapatkan konteks lengkap ujian, materi ATP, dan status pemetaan TP saat ini.
     */
    public function getTestContext(int $testId): array
    {
        $db = $this->getDb();

        // 1. Data Ujian
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, qb.level AS bank_level, qb.teacher_id, s.name AS subject_name, s.id AS subject_id, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()->getRowArray();

        if (!$test) {
            throw new \RuntimeException("Ujian CBT #{$testId} tidak ditemukan.");
        }

        $subjectId = (int)($test['subject_id'] ?? 0);
        $bankId = (int)($test['bank_id'] ?? 0);

        // 2. Ekstrak data penjadwalan ujian (Level Kelas & Semester dari cbt_test_status)
        $scheduling = $this->resolveTestScheduling($test);
        $targetLevels = $scheduling['target_levels'];
        $targetClassIds = $scheduling['target_class_ids'];
        $testSemester = $scheduling['semester'];

        // 3. Muat Daftar ATP yang sesuai dengan Level Kelas dan Semester Terjadwal
        $atpList = [];
        $isFilteredBySchedule = false;

        if ($db->tableExists('alur_tujuan_pembelajaran') && $subjectId > 0) {
            $atpQuery = $db->table('alur_tujuan_pembelajaran')
                ->where('alur_tujuan_pembelajaran.subject_id', $subjectId);

            $hasLevelFilter = false;
            if (!empty($targetLevels) || !empty($targetClassIds)) {
                $hasLevelFilter = true;
                $atpQuery->join('classes c_atp', 'c_atp.id = alur_tujuan_pembelajaran.class_id', 'left');
                $atpQuery->groupStart();
                if (!empty($targetLevels)) {
                    $atpQuery->whereIn('c_atp.level', $targetLevels);
                }
                if (!empty($targetClassIds)) {
                    if (!empty($targetLevels)) {
                        $atpQuery->orWhereIn('alur_tujuan_pembelajaran.class_id', $targetClassIds);
                    } else {
                        $atpQuery->whereIn('alur_tujuan_pembelajaran.class_id', $targetClassIds);
                    }
                }
                $atpQuery->groupEnd();
            }

            if ($testSemester !== null && in_array($testSemester, [1, 2])) {
                $atpQuery->where('alur_tujuan_pembelajaran.semester', $testSemester);
            }

            $rawAtp = $atpQuery->orderBy('alur_tujuan_pembelajaran.semester', 'ASC')
                ->orderBy('alur_tujuan_pembelajaran.urutan', 'ASC')
                ->get()->getResultArray();

            if (!empty($rawAtp)) {
                $isFilteredBySchedule = true;
            } else {
                // Fallback 1: Jika dengan filter semester kosong, coba filter level kelas saja
                if ($testSemester !== null && $hasLevelFilter) {
                    $fallbackQuery = $db->table('alur_tujuan_pembelajaran')
                        ->where('alur_tujuan_pembelajaran.subject_id', $subjectId)
                        ->join('classes c_atp', 'c_atp.id = alur_tujuan_pembelajaran.class_id', 'left');

                    $fallbackQuery->groupStart();
                    if (!empty($targetLevels)) {
                        $fallbackQuery->whereIn('c_atp.level', $targetLevels);
                    }
                    if (!empty($targetClassIds)) {
                        $fallbackQuery->orWhereIn('alur_tujuan_pembelajaran.class_id', $targetClassIds);
                    }
                    $fallbackQuery->groupEnd();

                    $rawAtp = $fallbackQuery->orderBy('alur_tujuan_pembelajaran.semester', 'ASC')
                        ->orderBy('alur_tujuan_pembelajaran.urutan', 'ASC')
                        ->get()->getResultArray();

                    if (!empty($rawAtp)) {
                        $isFilteredBySchedule = true;
                    }
                }

                // Fallback 2: Jika masih kosong, muat seluruh materi mapel
                if (empty($rawAtp)) {
                    $rawAtp = $db->table('alur_tujuan_pembelajaran')
                        ->where('subject_id', $subjectId)
                        ->orderBy('semester', 'ASC')
                        ->orderBy('urutan', 'ASC')
                        ->get()->getResultArray();
                }
            }

            foreach ($rawAtp as $atp) {
                $atpId = (int)$atp['id'];
                $tps = [];

                // Ambil TP via atp_elemen jika ada
                if ($db->tableExists('atp_elemen')) {
                    $elemenRows = $db->table('atp_elemen ae')
                        ->select('ae.id as atp_elemen_id, cp.elemen')
                        ->join('cp_master cp', 'cp.id = ae.cp_master_id', 'left')
                        ->where('ae.atp_id', $atpId)
                        ->get()->getResultArray();

                    $elemenIds = array_column($elemenRows, 'atp_elemen_id');
                    if (!empty($elemenIds)) {
                        $tps = $db->table('tujuan_pembelajaran')
                            ->whereIn('atp_elemen_id', $elemenIds)
                            ->orderBy('kode_tp', 'ASC')
                            ->get()->getResultArray();
                    }
                }

                // Fallback: ambil TP via atp_id langsung
                if (empty($tps) && $db->fieldExists('atp_id', 'tujuan_pembelajaran')) {
                    $tps = $db->table('tujuan_pembelajaran')
                        ->where('atp_id', $atpId)
                        ->orderBy('kode_tp', 'ASC')
                        ->get()->getResultArray();
                }

                $formattedTps = [];
                foreach ($tps as $tp) {
                    $formattedTps[] = [
                        'id'             => (int)$tp['id'],
                        'kode_tp'        => $tp['kode_tp'] ?? ('TP #' . $tp['id']),
                        'deskripsi'      => $tp['deskripsi'] ?? '',
                        'lingkup_materi' => $atp['lingkup_materi'] ?? '-',
                        'elemen'         => $tp['elemen'] ?? '-',
                    ];
                }

                $atpList[] = [
                    'id'             => $atpId,
                    'lingkup_materi' => $atp['lingkup_materi'] ?? 'Pokok Bahasan',
                    'semester'       => $atp['semester'] ?? 1,
                    'urutan'         => $atp['urutan'] ?? 1,
                    'tps'            => $formattedTps,
                ];
            }
        }

        // Jika ATP belum dibuat sama sekali, muat TP langsung dari tabel tujuan_pembelajaran
        if (empty($atpList) && $db->tableExists('tujuan_pembelajaran') && $subjectId > 0) {
            $rawTps = $db->table('tujuan_pembelajaran')
                ->where('subject_id', $subjectId)
                ->orderBy('kode_tp', 'ASC')
                ->get()->getResultArray();

            $formattedTps = [];
            foreach ($rawTps as $tp) {
                $formattedTps[] = [
                    'id'             => (int)$tp['id'],
                    'kode_tp'        => $tp['kode_tp'] ?? ('TP #' . $tp['id']),
                    'deskripsi'      => $tp['deskripsi'] ?? '',
                    'lingkup_materi' => $tp['lingkup_materi'] ?? 'Umum',
                    'elemen'         => $tp['elemen'] ?? '-',
                ];
            }
            $atpList[] = [
                'id'             => 0,
                'lingkup_materi' => 'Semua Materi Terdaftar',
                'semester'       => 1,
                'urutan'         => 1,
                'tps'            => $formattedTps,
            ];
        }

        // 3. Muat Konfigurasi Pemetaan Tersimpan
        $config = null;
        if ($db->tableExists('cbt_test_tp_configs')) {
            $config = $db->table('cbt_test_tp_configs')
                ->where('test_id', $testId)
                ->get()->getRowArray();
        }

        $selectedAtpIds = [];
        $selectedTpIds = [];
        $assessmentType = 'formatif';

        if ($config) {
            $assessmentType = $config['assessment_type'] ?? 'formatif';
            $selectedAtpIds = json_decode($config['selected_atp_ids'] ?? '[]', true) ?: [];
            $selectedTpIds = json_decode($config['selected_tp_ids'] ?? '[]', true) ?: [];
        }

        // 4. Muat Butir Soal Ujian
        $questions = [];
        if ($bankId > 0 && $db->tableExists('cbt_questions')) {
            $questions = $db->table('cbt_questions')
                ->where('bank_id', $bankId)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
        }

        // 5. Muat Pemetaan Soal ke TP yang Tersimpan
        $savedMappings = [];
        if ($db->tableExists('cbt_test_tp_mappings')) {
            $mappingRows = $db->table('cbt_test_tp_mappings')
                ->where('test_id', $testId)
                ->get()->getResultArray();
            foreach ($mappingRows as $m) {
                $savedMappings[(int)$m['question_id']] = [
                    'tp_id'          => (int)$m['tp_id'],
                    'confidence'     => (float)($m['confidence'] ?? 100),
                    'reasoning'      => $m['reasoning'] ?? '',
                    'mapping_source' => $m['mapping_source'] ?? 'manual',
                ];
            }
        }

        return [
            'test'                   => $test,
            'scheduling'             => $scheduling,
            'is_filtered_by_schedule'=> $isFilteredBySchedule,
            'atp_list'               => $atpList,
            'config'                 => $config,
            'assessment_type'        => $assessmentType,
            'selected_atp_ids'       => $selectedAtpIds,
            'selected_tp_ids'        => $selectedTpIds,
            'questions'              => $questions,
            'saved_mappings'         => $savedMappings,
        ];
    }

    /**
     * Ekstrak data penjadwalan ujian (Semester dan Tingkat Kelas sasaran).
     */
    public function resolveTestScheduling(array $test): array
    {
        $db = $this->getDb();

        // 1. Ekstrak semester dari penjadwalan
        $rawSemester = $test['semester'] ?? null;
        $semester = null;
        if (!empty($rawSemester)) {
            if (is_numeric($rawSemester)) {
                $semester = (int)$rawSemester;
            } elseif (stripos((string)$rawSemester, 'ganjil') !== false) {
                $semester = 1;
            } elseif (stripos((string)$rawSemester, 'genap') !== false) {
                $semester = 2;
            }
        }

        // 2. Ekstrak target kelas dari class_codes penjadwalan
        $rawCodes = $test['class_codes'] ?? '';
        $targetClassTokens = [];
        if (is_string($rawCodes)) {
            $decoded = json_decode($rawCodes, true);
            if (is_array($decoded)) {
                $targetClassTokens = $decoded;
            } elseif (trim($rawCodes) !== '') {
                $targetClassTokens = array_map('trim', explode(',', $rawCodes));
            }
        } elseif (is_array($rawCodes)) {
            $targetClassTokens = $rawCodes;
        }
        $targetClassTokens = array_values(array_filter(array_map('trim', (array)$targetClassTokens)));

        $matchedClasses = [];
        $targetClassIds = [];
        $targetLevels = [];
        $targetClassNames = [];

        if (!empty($targetClassTokens) && $db !== null && $db->tableExists('classes')) {
            $matchedClasses = $db->table('classes')
                ->groupStart()
                    ->whereIn('name', $targetClassTokens)
                    ->orWhereIn('id', array_filter($targetClassTokens, 'is_numeric'))
                ->groupEnd()
                ->get()->getResultArray();

            foreach ($matchedClasses as $c) {
                $targetClassIds[] = (int)$c['id'];
                $targetClassNames[] = $c['name'];
                if (!empty($c['level'])) {
                    $targetLevels[] = (int)$c['level'];
                }
            }
        }

        // Jika tidak cocok via query exact, ekstrak level angka / romawi dari teks class_codes
        if (empty($targetLevels)) {
            foreach ($targetClassTokens as $str) {
                if (preg_match('/\b(VII|VIII|IX|X|XI|XII)\b/i', $str, $m)) {
                    $romanMap = ['VII' => 7, 'VIII' => 8, 'IX' => 9, 'X' => 10, 'XI' => 11, 'XII' => 12];
                    $targetLevels[] = $romanMap[strtoupper($m[1])];
                } elseif (preg_match('/(?:kelas\s*|\b)([1-9]|1[0-2])(?:\s*[a-zA-Z]|\b)/i', $str, $m)) {
                    $targetLevels[] = (int)$m[1];
                }
            }
        }

        // Cek juga dari bank_level jika berupa angka
        if (empty($targetLevels) && !empty($test['bank_level']) && is_numeric($test['bank_level'])) {
            $targetLevels[] = (int)$test['bank_level'];
        }

        $targetLevels = array_values(array_unique(array_filter($targetLevels)));
        $targetClassIds = array_values(array_unique(array_filter($targetClassIds)));

        $levelLabel = !empty($targetLevels) 
            ? 'Kelas ' . implode(', ', $targetLevels) 
            : (!empty($test['bank_level']) ? 'Level ' . $test['bank_level'] : (!empty($targetClassNames) ? implode(', ', $targetClassNames) : 'Semua Level'));
        
        $semesterLabel = $semester ? 'Semester ' . $semester : 'Semua Semester';

        return [
            'semester'           => $semester,
            'semester_label'     => $semesterLabel,
            'target_class_ids'   => $targetClassIds,
            'target_levels'      => $targetLevels,
            'target_class_names' => $targetClassNames,
            'level_label'        => $levelLabel,
            'class_codes_raw'    => $rawCodes,
        ];
    }

    /**
     * Jalankan pencocokan soal ke TP menggunakan AI (dengan fallback semantik cerdas).
     */
    public function matchQuestionsWithAi(
        int $testId,
        string $assessmentType,
        array $selectedAtpIds,
        array $selectedTpIds,
        ?string $apiKey = null,
        ?string $provider = 'gemini'
    ): array {
        $context = $this->getTestContext($testId);
        $questions = $context['questions'];
        $atpList = $context['atp_list'];

        // Kumpulkan kandidat TP
        $candidateTps = [];
        foreach ($atpList as $atp) {
            if (empty($selectedAtpIds) || in_array($atp['id'], $selectedAtpIds) || $atp['id'] === 0) {
                foreach ($atp['tps'] as $tp) {
                    if (empty($selectedTpIds) || in_array($tp['id'], $selectedTpIds)) {
                        $candidateTps[$tp['id']] = $tp;
                    }
                }
            }
        }

        // Jika tidak ada TP kandidat yang dipilih, ambil seluruh TP dari ATP yang ada
        if (empty($candidateTps)) {
            foreach ($atpList as $atp) {
                foreach ($atp['tps'] as $tp) {
                    $candidateTps[$tp['id']] = $tp;
                }
            }
        }

        if (empty($candidateTps) || empty($questions)) {
            return [
                'success'  => false,
                'message'  => 'Daftar butir soal atau Tujuan Pembelajaran (TP) belum tersedia.',
                'mappings' => [],
            ];
        }

        // Coba pemetaan via LLM jika API key tersedia
        $apiKey = $apiKey ?: (getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? null));

        if (!empty($apiKey) && strtolower($provider) === 'gemini') {
            $llmResult = $this->callGeminiForMapping($questions, $candidateTps, $apiKey, $assessmentType);
            if (!empty($llmResult)) {
                return [
                    'success'  => true,
                    'source'   => 'ai_gemini',
                    'message'  => 'Pemetaan butir soal berhasil dianalisis dengan AI Gemini.',
                    'mappings' => $llmResult,
                ];
            }
        }

        // Fallback: Analisis semantik kata kunci & taksonomi materi (100% offline & deterministik)
        $semanticResult = $this->fallbackSemanticMatch($questions, $candidateTps);
        return [
            'success'  => true,
            'source'   => 'semantic_engine',
            'message'  => 'Pemetaan butir soal berhasil dianalisis menggunakan mesin relevansi kata kunci & taksonomi TP.',
            'mappings' => $semanticResult,
        ];
    }

    /**
     * Panggil Gemini API untuk memetakan soal ke TP.
     */
    protected function callGeminiForMapping(array $questions, array $candidateTps, string $apiKey, string $assessmentType): ?array
    {
        $tpPromptList = [];
        foreach ($candidateTps as $id => $tp) {
            $tpPromptList[] = sprintf(
                "- ID_TP: %d | Kode: %s | Materi: %s | Deskripsi: %s",
                $id,
                $tp['kode_tp'],
                $tp['lingkup_materi'],
                $tp['deskripsi']
            );
        }

        $questionPromptList = [];
        foreach ($questions as $idx => $q) {
            $cleanText = strip_tags($q['raw_text'] ?? ($q['question_text'] ?? ''));
            $cleanText = mb_substr(preg_replace('/\s+/', ' ', $cleanText), 0, 250);
            $questionPromptList[] = sprintf(
                "[No. %d] ID_SOAL: %d | Tipe: %s | Teks: %s",
                $idx + 1,
                $q['id'],
                $q['question_type'] ?? 'pg',
                $cleanText
            );
        }

        $prompt = "Anda adalah pakar penelaah asesmen Kurikulum Merdeka.\n"
            . "Tugas Anda: Memetakan setiap butir soal ujian CBT berikut ke salah satu Tujuan Pembelajaran (TP) yang paling relevan.\n"
            . "Jenis Asesmen: " . strtoupper($assessmentType) . "\n\n"
            . "DAFTAR TUJUAN PEMBELAJARAN (TP) KANDIDAT:\n"
            . implode("\n", $tpPromptList) . "\n\n"
            . "DAFTAR BUTIR SOAL UJIAN:\n"
            . implode("\n", $questionPromptList) . "\n\n"
            . "ATURAN:\n"
            . "1. Analisis konsep, kata kerja operasional, dan indikator pada teks soal.\n"
            . "2. Pilih tepat SATU ID_TP yang paling sesuai untuk setiap ID_SOAL.\n"
            . "3. Kembalikan HANYA format JSON valid tanpa tanda markdown (```json), berupa array objek dengan struktur:\n"
            . "[{\"question_id\": <INT>, \"tp_id\": <INT>, \"confidence\": <FLOAT 50-100>, \"reasoning\": \"<RINGKASAN_ALASAN_SINGKAT>\"}]";

        $models = ['gemini-2.0-flash', 'gemini-1.5-flash'];
        foreach ($models as $model) {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $apiKey;
            $payload = json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => [
                    'temperature'     => 0.2,
                    'maxOutputTokens' => 8192,
                ]
            ]);

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($response)) {
                $decoded = json_decode($response, true);
                $rawText = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? '';
                $cleanJson = trim(preg_replace('/^```(?:json)?|```$/m', '', $rawText));
                $parsed = json_decode($cleanJson, true);

                if (is_array($parsed) && !empty($parsed)) {
                    $resultMap = [];
                    foreach ($parsed as $item) {
                        $qid = (int)($item['question_id'] ?? 0);
                        $tpid = (int)($item['tp_id'] ?? 0);
                        if ($qid > 0 && isset($candidateTps[$tpid])) {
                            $resultMap[$qid] = [
                                'tp_id'          => $tpid,
                                'confidence'     => (float)($item['confidence'] ?? 85.0),
                                'reasoning'      => (string)($item['reasoning'] ?? 'Sesuai dengan indikator TP'),
                                'mapping_source' => 'ai_gemini',
                            ];
                        }
                    }
                    if (!empty($resultMap)) {
                        return $resultMap;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Fallback Semantik Cerdas (Offline): Menghitung skor kemiripan teks soal terhadap deskripsi TP.
     */
    public function fallbackSemanticMatch(array $questions, array $candidateTps): array
    {
        $resultMap = [];
        $tpKeys = array_keys($candidateTps);
        $totalCandidates = count($tpKeys);

        // Pre-proses token kata untuk setiap TP
        $tpTokens = [];
        foreach ($candidateTps as $id => $tp) {
            $text = strtolower($tp['kode_tp'] . ' ' . $tp['lingkup_materi'] . ' ' . $tp['deskripsi']);
            $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
            // Hilangkan stopwords umum bahasa Indonesia
            $stopwords = ['dan', 'atau', 'pada', 'untuk', 'yang', 'dengan', 'dalam', 'di', 'ke', 'dari', 'dapat', 'peserta', 'didik', 'mampu', 'siswa'];
            $filtered = array_diff($words, $stopwords);
            $tpTokens[$id] = array_unique($filtered);
        }

        foreach ($questions as $idx => $q) {
            $qid = (int)$q['id'];
            $qText = strtolower(strip_tags($q['raw_text'] ?? ($q['question_text'] ?? '')));
            $qWords = preg_split('/[^\p{L}\p{N}]+/u', $qText, -1, PREG_SPLIT_NO_EMPTY);

            $bestTpId = $tpKeys[$idx % $totalCandidates]; // Default seimbang jika tidak ada kecocokan
            $bestScore = 0;
            $bestMatchedWords = [];

            foreach ($tpTokens as $tpId => $tokens) {
                $matched = array_intersect($qWords, $tokens);
                $score = count($matched);
                if ($score > $bestScore) {
                    $bestScore = $score;
                    $bestTpId = $tpId;
                    $bestMatchedWords = $matched;
                }
            }

            if ($bestScore > 0) {
                $confidence = min(95.0, 65.0 + ($bestScore * 8.0));
                $reasoning = 'Kecocokan kata kunci: ' . implode(', ', array_slice($bestMatchedWords, 0, 3));
            } else {
                $confidence = 70.0;
                $reasoning = 'Distribusi proporsional materi TP';
            }

            $resultMap[$qid] = [
                'tp_id'          => $bestTpId,
                'confidence'     => round($confidence, 1),
                'reasoning'      => $reasoning,
                'mapping_source' => 'semantic_engine',
            ];
        }

        return $resultMap;
    }

    /**
     * Simpan konfigurasi asesmen dan pemetaan butir soal ke database.
     */
    public function saveMapping(
        int $testId,
        string $assessmentType,
        array $selectedAtpIds,
        array $selectedTpIds,
        array $mappings,
        ?int $userId = null
    ): array {
        $db = $this->getDb();
        $now = date('Y-m-d H:i:s');

        // 1. Simpan / Update cbt_test_tp_configs
        if ($db->tableExists('cbt_test_tp_configs')) {
            $existing = $db->table('cbt_test_tp_configs')->where('test_id', $testId)->get()->getRowArray();
            $configData = [
                'test_id'          => $testId,
                'assessment_type'  => in_array($assessmentType, ['formatif', 'sumatif']) ? $assessmentType : 'formatif',
                'selected_atp_ids' => json_encode(array_values($selectedAtpIds)),
                'selected_tp_ids'  => json_encode(array_values($selectedTpIds)),
                'created_by'       => $userId,
                'updated_at'       => $now,
            ];

            if ($existing) {
                $db->table('cbt_test_tp_configs')->where('id', $existing['id'])->update($configData);
            } else {
                $configData['created_at'] = $now;
                $db->table('cbt_test_tp_configs')->insert($configData);
            }
        }

        // 2. Simpan / Replace cbt_test_tp_mappings
        if ($db->tableExists('cbt_test_tp_mappings')) {
            // Hapus pemetaan lama untuk test_id ini
            $db->table('cbt_test_tp_mappings')->where('test_id', $testId)->delete();

            $batchData = [];
            foreach ($mappings as $qid => $map) {
                $batchData[] = [
                    'test_id'        => $testId,
                    'question_id'    => (int)$qid,
                    'tp_id'          => (int)($map['tp_id'] ?? 0),
                    'confidence'     => !empty($map['confidence']) ? (float)$map['confidence'] : 100.0,
                    'reasoning'      => mb_substr((string)($map['reasoning'] ?? ''), 0, 250),
                    'mapping_source' => (string)($map['mapping_source'] ?? 'manual'),
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ];
            }

            if (!empty($batchData)) {
                $db->table('cbt_test_tp_mappings')->insertBatch($batchData);
            }
        }

        // 3. Sinkronisasi dengan cbt_test_item_versions jika ada (Assessment Intelligence V3)
        if ($db->tableExists('cbt_test_items') && $db->tableExists('cbt_test_item_versions')) {
            $items = $db->table('cbt_test_items ti')
                ->select('ti.id as test_item_id, ti.bank_question_id, tiv.id as version_id, tiv.content_payload_json')
                ->join('cbt_test_item_versions tiv', 'tiv.test_item_id = ti.id AND tiv.test_id = ti.test_id', 'inner')
                ->where('ti.test_id', $testId)
                ->get()->getResultArray();

            foreach ($items as $item) {
                $qid = (int)$item['bank_question_id'];
                if (isset($mappings[$qid])) {
                    $payload = !empty($item['content_payload_json']) ? json_decode($item['content_payload_json'], true) : [];
                    if (!is_array($payload)) {
                        $payload = [];
                    }
                    $payload['tp_id'] = (int)$mappings[$qid]['tp_id'];
                    $payload['mapping_source'] = $mappings[$qid]['mapping_source'] ?? 'ai';

                    $db->table('cbt_test_item_versions')
                        ->where('id', $item['version_id'])
                        ->update(['content_payload_json' => json_encode($payload)]);
                }
            }
        }

        return [
            'success'      => true,
            'test_id'      => $testId,
            'total_mapped' => count($mappings),
            'message'      => 'Konfigurasi materi asesmen dan pemetaan butir soal berhasil disimpan.',
        ];
    }
}
