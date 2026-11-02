<?php

namespace App\Services\Cbt\Analysis;

use App\Services\Cbt\Assessment\AssessmentEngineService;
use App\Services\Cbt\Assessment\AssessmentStatusResolver;
use App\Services\Cbt\Assessment\MasteryRateCalculator;
use App\Services\Cbt\Assessment\TpEvidenceEvaluator;
use App\Services\Cbt\Assessment\QuestionWeightResolver;
use CodeIgniter\Database\BaseConnection;

/**
 * CbtTpAnalysisService
 *
 * ESPATA Assessment Intelligence V3 — Analisis Hasil Ujian Berbasis TP
 * Menyederhanakan analisis hasil ujian menjadi dua fokus utama:
 * 1. Analisis Penguasaan TP Per Siswa (Student TP Mastery)
 * 2. Analisis Penguasaan TP Per Kelas (Cohort/Class TP Mastery)
 *
 * Aturan Perhitungan & Kebijakan Akademik:
 * - Dasar analisis: Pemetaan soal objektif terhadap Tujuan Pembelajaran (TP) dan hasil jawaban siswa.
 * - Penguasaan TP Siswa = (Skor diperoleh pada soal TP / Skor maksimal soal TP) * 100%.
 * - Penguasaan TP Kelas = CR_murni = (Jumlah Siswa Dikuasai / Jumlah Siswa Dinilai Lengkap) * 100%.
 * - Soal yang dibatalkan (is_void) tidak dihitung dalam bobot maupun skor.
 * - Siswa yang belum mengikuti ujian (not attempted) tidak dihitung sebagai nol.
 * - Ujian dengan koreksi esai yang belum selesai ditandai "Menunggu Koreksi Esai".
 * - Isolasi OD-PATH-B: Nilai esai tidak dialokasikan secara sembarang ke TP.
 * - Aturan konjungtif kecukupan bukti: Smax >= 10.00 dan N_unique >= 2.
 *   Jika belum terpenuhi: "Belum cukup data untuk dianalisis".
 */
class CbtTpAnalysisService
{
    private ?BaseConnection $db;
    private TpEvidenceEvaluator $tpEvaluator;
    private MasteryRateCalculator $masteryCalculator;
    private QuestionWeightResolver $weightResolver;

    public const THRESHOLD_REMEDIAL = 75.0;
    public const THRESHOLD_ENRICHMENT = 85.0;

    public function __construct(
        ?BaseConnection $db = null,
        ?TpEvidenceEvaluator $tpEvaluator = null,
        ?MasteryRateCalculator $masteryCalculator = null,
        ?QuestionWeightResolver $weightResolver = null
    ) {
        $this->db = $db;
        $this->tpEvaluator = $tpEvaluator ?? new TpEvidenceEvaluator();
        $this->masteryCalculator = $masteryCalculator ?? new MasteryRateCalculator();
        $this->weightResolver = $weightResolver ?? new QuestionWeightResolver();
    }

    /**
     * Dapatkan koneksi database secara aman.
     */
    protected function getDb(): BaseConnection
    {
        if ($this->db === null) {
            $this->db = \Config\Database::connect();
        }
        return $this->db;
    }

    /**
     * Jalankan analisis lengkap untuk sebuah paket ujian (test_id) dari database.
     *
     * @param int $testId ID sesi ujian (cbt_test_status.id)
     * @return array Hasil analisis terstruktur (Per Siswa dan Per Kelas)
     */
    public function analyzeTest(int $testId): array
    {
        $db = $this->getDb();

        // 1. Ambil informasi ujian
        $test = $db->table('cbt_test_status ts')
            ->select('ts.*, qb.code AS bank_code, qb.subject_id, qb.level AS bank_level, s.name AS subject_name, en.name AS exam_name')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id', 'left')
            ->join('subjects s', 's.id = qb.subject_id', 'left')
            ->join('cbt_exam_names en', 'en.id = ts.exam_name_id', 'left')
            ->where('ts.id', $testId)
            ->get()->getRowArray();

        if (!$test) {
            throw new \RuntimeException("Ujian CBT #{$testId} tidak ditemukan.");
        }

        // 2. Ambil seluruh soal di bank soal
        $questions = $db->table('cbt_questions')
            ->where('bank_id', (int)$test['bank_id'])
            ->orderBy('id', 'ASC')
            ->get()->getResultArray();

        // Ambil info pembatalan soal dari cbt_test_item_versions jika tersedia
        $voidMap = [];
        $tpMap = [];
        if ($db->tableExists('cbt_test_items') && $db->tableExists('cbt_test_item_versions')) {
            $versions = $db->table('cbt_test_items ti')
                ->select('ti.bank_question_id, tiv.is_active, tiv.content_payload_json')
                ->join('cbt_test_item_versions tiv', 'tiv.test_item_id = ti.id AND tiv.test_id = ti.test_id', 'inner')
                ->where('ti.test_id', $testId)
                ->get()->getResultArray();

            foreach ($versions as $v) {
                $qid = (int)$v['bank_question_id'];
                if (isset($v['is_active']) && (int)$v['is_active'] === 0) {
                    $voidMap[$qid] = true;
                }
                if (!empty($v['content_payload_json'])) {
                    $payload = json_decode($v['content_payload_json'], true);
                    if (is_array($payload) && !empty($payload['tp_id'])) {
                        $tpMap[$qid] = (int)$payload['tp_id'];
                    }
                }
            }
        }

        // 3. Ambil daftar Tujuan Pembelajaran (TP) untuk mata pelajaran ini
        $subjectId = (int)($test['subject_id'] ?? 0);
        $tps = [];
        if ($db->tableExists('tujuan_pembelajaran') && $subjectId > 0) {
            $tps = $db->table('tujuan_pembelajaran')
                ->where('subject_id', $subjectId)
                ->orderBy('kode_tp', 'ASC')
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
        }

        // Ambil ambang batas KKTP bila ada
        $thresholds = [];
        if ($db->tableExists('kktp') && $subjectId > 0) {
            $kktpRows = $db->table('kktp')
                ->where('subject_id', $subjectId)
                ->get()->getResultArray();
            foreach ($kktpRows as $k) {
                $tpId = (int)($k['tp_id'] ?? 0);
                if ($tpId > 0 && !empty($k['kriteria_3_interval'])) {
                    // Ekstrak angka batas bawah, misal "66-85%" -> 66
                    if (preg_match('/(\d+)/', $k['kriteria_3_interval'], $m)) {
                        $thresholds[$tpId] = (float)$m[1];
                    }
                }
            }
        }

        // 4. Susun TP Definitions dan petakan soal ke TP
        $tpDefinitions = [];
        $tpIdList = [];
        if (!empty($tps)) {
            foreach ($tps as $tp) {
                $id = (int)$tp['id'];
                $tpIdList[] = $id;
                $tpDefinitions[] = [
                    'tp_id'     => $id,
                    'tp_code'   => !empty($tp['kode_tp']) ? 'TP ' . $tp['kode_tp'] : 'TP #' . $id,
                    'deskripsi' => $tp['deskripsi'] ?? '',
                    'threshold' => $thresholds[$id] ?? self::THRESHOLD_REMEDIAL,
                ];
            }
        }

        // Petakan soal ke TP
        $items = [];
        $totalObj = 0;
        foreach ($questions as $idx => $q) {
            $qid = (int)$q['id'];
            $type = strtolower((string)($q['question_type'] ?? 'pg'));
            $isVoid = !empty($voidMap[$qid]);

            // Tentukan tp_id untuk soal ini
            $assignedTpId = $tpMap[$qid] ?? null;
            if ($assignedTpId === null && !empty($tpIdList)) {
                // Jika belum dipetakan eksplisit di payload, distribusikan secara seimbang ke TP yang tersedia
                $assignedTpId = $tpIdList[$idx % count($tpIdList)];
            }

            $items[] = [
                'item_id'       => $qid,
                'question_text' => $q['raw_text'] ?? ($q['question_text'] ?? ''),
                'type'          => $type,
                'tp_id'         => $assignedTpId,
                'key'           => $q['correct_option'] ?? '',
                'score'         => (float)($q['score'] ?? 1.0),
                'is_void'       => $isVoid,
            ];

            if (!$isVoid && $type !== 'esai') {
                $totalObj++;
            }
        }

        // 5. Ambil data sesi pengerjaan siswa
        $sessions = $db->table('cbt_sessions s')
            ->select('s.*, st.name as student_name, st.nisn, st.nis, c.name as class_name,
                      sc.score_pg, sc.score_esai, sc.final_score, sc.pending_essay_count, sc.publication_eligibility')
            ->join('students st', 'st.id = s.student_id', 'left')
            ->join('classes c', 'c.id = st.class_id', 'left')
            ->join('cbt_session_scores_v3 sc', 'sc.session_id = s.id', 'left')
            ->where('s.test_id', $testId)
            ->orderBy('st.name', 'ASC')
            ->get()->getResultArray();

        // 6. Ambil jawaban seluruh siswa untuk test ini
        $answers = [];
        if ($db->tableExists('cbt_answers')) {
            $answers = $db->table('cbt_answers')
                ->where('test_id', $testId)
                ->get()->getResultArray();
        }

        $testConfig = [
            'test_id'        => $testId,
            'name'           => $test['exam_name'] ?? ('Ujian #' . $testId),
            'subject_name'   => $test['subject_name'] ?? 'Mata Pelajaran',
            'bank_code'      => $test['bank_code'] ?? '-',
            'class_codes'    => $test['class_codes'] ?? '',
            'passing_grade'  => self::THRESHOLD_REMEDIAL,
            'tp_definitions' => $tpDefinitions,
        ];

        return $this->analyzeFromData($testConfig, $items, $sessions, $answers);
    }

    /**
     * Mesin kalkulasi murni (dapat diuji secara terisolasi tanpa database operasional).
     *
     * @param array $testConfig Konfigurasi ujian & definisi TP
     * @param array $items Daftar butir soal beserta tipe, bobot, is_void, dan tp_id
     * @param array $sessions Daftar sesi siswa (termasuk status sesi & pending_essay_count)
     * @param array $answers Daftar jawaban siswa (session_id, question_id, answer)
     * @return array Hasil analisis Penguasaan TP Per Siswa dan Per Kelas
     */
    public function analyzeFromData(
        array $testConfig,
        array $items,
        array $sessions,
        array $answers
    ): array {
        $tpDefinitions = $testConfig['tp_definitions'] ?? [];
        $defaultThreshold = (float)($testConfig['passing_grade'] ?? self::THRESHOLD_REMEDIAL);

        // Jika TP definitions kosong, buat entri fallback agar laporan tetap informatif
        if (empty($tpDefinitions)) {
            $tpDefinitions = [
                [
                    'tp_id'     => 1,
                    'tp_code'   => 'TP Umum',
                    'deskripsi' => 'Penguasaan materi umum ' . ($testConfig['subject_name'] ?? 'mata pelajaran'),
                    'threshold' => $defaultThreshold,
                ]
            ];
            // Petakan semua soal ke TP fallback
            foreach ($items as &$it) {
                if (empty($it['tp_id'])) {
                    $it['tp_id'] = 1;
                }
            }
            unset($it);
        }

        // Resolusi bobot soal objektif agar total bobot valid terdistribusi ke 100%
        $activeObjectiveItems = [];
        foreach ($items as $item) {
            $type = strtolower((string)($item['type'] ?? 'pg'));
            if ($type !== 'esai' && empty($item['is_void'])) {
                $activeObjectiveItems[] = $item;
            }
        }

        $totalActiveObj = count($activeObjectiveItems);
        $weightPerItem = $totalActiveObj > 0 ? (100.0 / $totalActiveObj) : 0.0;

        $decoratedItems = [];
        foreach ($items as $item) {
            $type = strtolower((string)($item['type'] ?? 'pg'));
            $isVoid = !empty($item['is_void']);
            $weight = ($type !== 'esai' && !$isVoid) ? $weightPerItem : 0.0;

            $decoratedItems[$item['item_id']] = array_merge($item, [
                'resolved_weight' => $weight,
            ]);
        }

        // Kelompokkan jawaban berdasarkan session_id
        $answersBySession = [];
        foreach ($answers as $ans) {
            $sessId = (int)($ans['session_id'] ?? 0);
            $qid = (int)($ans['question_id'] ?? ($ans['item_id'] ?? 0));
            if (!isset($answersBySession[$sessId])) {
                $answersBySession[$sessId] = [];
            }
            $answersBySession[$sessId][$qid] = $ans['answer'] ?? ($ans['student_answer'] ?? '');
        }

        // =========================================================================
        // 1. ANALISIS PENGUASAAN TP PER SISWA
        // =========================================================================
        $studentReports = [];
        $cohortEvaluations = [];
        $totalEvaluatedStudents = 0;
        $totalRegisteredStudents = count($sessions);
        $pendingEssayStudentsCount = 0;

        foreach ($sessions as $session) {
            $studentId = (int)($session['student_id'] ?? 0);
            $sessionId = (int)($session['id'] ?? ($session['session_id'] ?? 0));
            $studentName = $session['student_name'] ?? ('Siswa #' . $studentId);
            $nisn = $session['nisn'] ?? ($session['nis'] ?? '-');
            $className = $session['class_name'] ?? '-';
            $sessionStatus = strtoupper((string)($session['status'] ?? ($session['session_status'] ?? 'SUBMITTED')));
            $pendingEssayCount = (int)($session['pending_essay_count'] ?? 0);

            // Siswa yang belum ujian atau absen
            $isAbsentOrUnattempted = in_array($sessionStatus, ['ABSENT', 'NOT_ATTEMPTED', 'BELUM_MENGERJAKAN'], true);
            $hasAttempted = !$isAbsentOrUnattempted && !empty($session['started_at']);

            $isEssayPending = ($pendingEssayCount > 0);
            if ($isEssayPending) {
                $pendingEssayStudentsCount++;
            }

            $studentAnswers = $answersBySession[$sessionId] ?? [];

            // Evaluasi per butir soal untuk siswa ini
            $studentItemBreakdown = [];
            foreach ($decoratedItems as $itemId => $item) {
                $type = strtolower((string)($item['type'] ?? 'pg'));
                if ($type === 'esai' || !empty($item['is_void'])) {
                    continue; // OD-PATH-B isolation & abaikan soal void
                }

                $key = (string)($item['key'] ?? '');
                $givenAnswer = (string)($studentAnswers[$itemId] ?? '');
                $isCorrect = false;

                if ($givenAnswer !== '' && $key !== '') {
                    $isCorrect = (trim(strtoupper($givenAnswer)) === trim(strtoupper($key)));
                }

                $earnedWeight = $isCorrect ? $item['resolved_weight'] : 0.0;

                $studentItemBreakdown[] = [
                    'item_id'        => $itemId,
                    'tp_id'          => $item['tp_id'] ?? null,
                    'weight'         => $item['resolved_weight'],
                    'earned_weight'  => $earnedWeight,
                    'weighted_score' => $earnedWeight,
                    'is_void'        => !empty($item['is_void']),
                    'is_correct'     => $isCorrect,
                ];
            }

            // Evaluasi TP menggunakan TpEvidenceEvaluator
            $tpEvals = $this->tpEvaluator->evaluate($studentItemBreakdown, $tpDefinitions, $hasAttempted);

            $masteredTpCodes = [];
            $needsImprovementTpCodes = [];
            $insufficientTpCodes = [];
            $allMastered = true;
            $hasDefinitive = false;
            $hasAnyFail = false;
            $totalScoreSum = 0.0;
            $evalCount = 0;

            foreach ($tpEvals as $tpId => &$eval) {
                $status = $eval['status'] ?? AssessmentStatusResolver::TP_INSUFFICIENT_EVIDENCE;
                $tpCode = $eval['tp_code'] ?? ('TP #' . $tpId);

                // Tambahkan label deskriptif ramah guru
                if ($status === AssessmentStatusResolver::TP_MASTERED) {
                    $eval['status_label'] = 'Dikuasai';
                    $eval['badge_class'] = 'bg-success';
                    $masteredTpCodes[] = $tpCode;
                    $hasDefinitive = true;
                } elseif ($status === AssessmentStatusResolver::TP_NOT_MASTERED) {
                    $eval['status_label'] = 'Perlu Peningkatan';
                    $eval['badge_class'] = 'bg-warning text-dark';
                    $needsImprovementTpCodes[] = $tpCode;
                    $allMastered = false;
                    $hasAnyFail = true;
                    $hasDefinitive = true;
                } elseif ($status === AssessmentStatusResolver::TP_NOT_ATTEMPTED) {
                    $eval['status_label'] = 'Belum Mengerjakan';
                    $eval['badge_class'] = 'bg-light text-muted border';
                    $allMastered = false;
                } else {
                    $eval['status_label'] = 'Belum cukup data untuk dianalisis';
                    $eval['badge_class'] = 'bg-secondary';
                    $insufficientTpCodes[] = $tpCode;
                    $allMastered = false;
                }

                if ($eval['student_percentage'] !== null) {
                    $totalScoreSum += $eval['student_percentage'];
                    $evalCount++;
                }
            }
            unset($eval);

            // Tentukan rekomendasi tindak lanjut siswa
            $avgTpScore = $evalCount > 0 ? round($totalScoreSum / $evalCount, 1) : null;
            $recommendation = '-';
            $recommendationBadge = 'bg-secondary';

            if (!$hasAttempted) {
                $recommendation = 'Ujian Susulan';
                $recommendationBadge = 'bg-light text-muted border';
            } elseif ($isEssayPending) {
                $recommendation = 'Menunggu Koreksi Esai';
                $recommendationBadge = 'bg-info text-dark';
            } elseif ($hasAnyFail) {
                $recommendation = 'Remedial (' . implode(', ', $needsImprovementTpCodes) . ')';
                $recommendationBadge = 'bg-danger';
            } elseif ($hasDefinitive) {
                $recommendation = ($avgTpScore !== null && $avgTpScore >= self::THRESHOLD_ENRICHMENT)
                    ? 'Pengayaan'
                    : 'Tuntas';
                $recommendationBadge = 'bg-success';
            } elseif (!empty($insufficientTpCodes)) {
                $recommendation = 'Belum cukup data untuk dianalisis';
                $recommendationBadge = 'bg-secondary';
            }

            if ($hasAttempted && !$isEssayPending) {
                $totalEvaluatedStudents++;
                // Hanya siswa dengan hasil penilaian sah dan lengkap yang masuk ke perhitungan kohor kelas (Aturan 3 & 7)
                $cohortEvaluations[] = [
                    'student_id'     => $studentId,
                    'tp_evaluations' => $tpEvals,
                ];
            }

            $studentReports[] = [
                'student_id'             => $studentId,
                'session_id'             => $sessionId,
                'nama_siswa'             => $studentName,
                'nisn'                   => $nisn,
                'kelas'                  => $className,
                'has_attempted'          => $hasAttempted,
                'is_essay_pending'       => $isEssayPending,
                'pending_essay_count'    => $pendingEssayCount,
                'tp_evaluations'         => $tpEvals,
                'mastered_tps'           => $masteredTpCodes,
                'needs_improvement_tps'  => $needsImprovementTpCodes,
                'insufficient_tps'       => $insufficientTpCodes,
                'average_percentage'     => $avgTpScore,
                'recommendation'         => $recommendation,
                'recommendation_badge'   => $recommendationBadge,
            ];
        }

        // =========================================================================
        // 2. ANALISIS PENGUASAAN TP PER KELAS
        // =========================================================================
        $cohortMetricsRaw = $this->masteryCalculator->calculateCohort($cohortEvaluations);

        $tpClassMetrics = [];
        $topTp = null;
        $lowestTp = null;
        $maxCr = -1.0;
        $minCr = 101.0;
        $tpsNeedingReinforcement = [];

        foreach ($tpDefinitions as $tpDef) {
            $tpId = $tpDef['tp_id'];
            $metric = $cohortMetricsRaw[$tpId] ?? null;

            // Hitung jumlah soal valid & Smax untuk TP ini
            $validQuestionsForTp = 0;
            $sMaxForTp = 0.0;
            foreach ($decoratedItems as $it) {
                if ((int)($it['tp_id'] ?? 0) === (int)$tpId && empty($it['is_void']) && ($it['type'] ?? '') !== 'esai') {
                    $validQuestionsForTp++;
                    $sMaxForTp += (float)$it['resolved_weight'];
                }
            }

            $threshold = (float)($tpDef['threshold'] ?? $defaultThreshold);
            $crMurni = $metric['cr_murni'] ?? null;
            $masteredCount = (int)($metric['mastered_count'] ?? 0);
            $notMasteredCount = (int)($metric['not_mastered_count'] ?? 0);
            $definitiveCount = (int)($metric['definitive_count'] ?? 0);
            $hasDefinitive = !empty($metric['has_definitive_evidence']);

            // Tentukan status kelas
            $isSufficientEvidence = ($sMaxForTp >= TpEvidenceEvaluator::MIN_SMAX && $validQuestionsForTp >= TpEvidenceEvaluator::MIN_N_UNIQUE);

            if (!$isSufficientEvidence || !$hasDefinitive) {
                $statusKelas = 'BELUM_CUKUP_DATA';
                $statusLabel = 'Belum cukup data untuk dianalisis';
                $badgeClass  = 'bg-secondary';
                $actionNote  = 'Perlu tambahan butir soal valid yang mengukur TP ini.';
            } elseif ($crMurni !== null && $crMurni >= $threshold) {
                $statusKelas = 'TUNTAS';
                $statusLabel = 'Penguasaan Baik (Tuntas)';
                $badgeClass  = 'bg-success';
                $actionNote  = 'Penguasaan kelas sudah baik. Dapat dilanjutkan ke materi pengayaan atau TP berikutnya.';
            } else {
                $statusKelas = 'PERLU_PENGUATAN';
                $statusLabel = 'Perlu Penguatan Pembelajaran';
                $badgeClass  = 'bg-danger';
                $actionNote  = 'TP ini belum dikuasai mayoritas kelas. Perlu mendapat penguatan materi dalam pembelajaran remedial kelas.';
                $tpsNeedingReinforcement[] = [
                    'tp_id'      => $tpId,
                    'tp_code'    => $tpDef['tp_code'],
                    'deskripsi'  => $tpDef['deskripsi'],
                    'percentage' => $crMurni,
                    'threshold'  => $threshold,
                ];
            }

            // Lacak TP tertinggi & terendah (hanya dari yang memiliki data sah)
            if ($crMurni !== null && $isSufficientEvidence) {
                if ($crMurni > $maxCr) {
                    $maxCr = $crMurni;
                    $topTp = [
                        'tp_id'      => $tpId,
                        'tp_code'    => $tpDef['tp_code'],
                        'deskripsi'  => $tpDef['deskripsi'],
                        'percentage' => $crMurni,
                    ];
                }
                if ($crMurni < $minCr) {
                    $minCr = $crMurni;
                    $lowestTp = [
                        'tp_id'      => $tpId,
                        'tp_code'    => $tpDef['tp_code'],
                        'deskripsi'  => $tpDef['deskripsi'],
                        'percentage' => $crMurni,
                    ];
                }
            }

            $tpClassMetrics[$tpId] = [
                'tp_id'                => $tpId,
                'tp_code'              => $tpDef['tp_code'],
                'deskripsi'            => $tpDef['deskripsi'],
                'threshold'            => $threshold,
                'valid_question_count' => $validQuestionsForTp,
                's_max'                => round($sMaxForTp, 2),
                'is_sufficient_evidence' => $isSufficientEvidence,
                'mastered_count'       => $masteredCount,
                'not_mastered_count'   => $notMasteredCount,
                'definitive_count'     => $definitiveCount,
                'cr_murni'             => $crMurni,
                'cr_kohort'            => $metric['cr_kohort'] ?? null,
                'status'               => $statusKelas,
                'status_label'         => $statusLabel,
                'badge_class'          => $badgeClass,
                'action_note'          => $actionNote,
            ];
        }

        $classAnalysis = [
            'total_registered'            => $totalRegisteredStudents,
            'total_evaluated'             => $totalEvaluatedStudents,
            'pending_essay_student_count' => $pendingEssayStudentsCount,
            'has_pending_essays'          => ($pendingEssayStudentsCount > 0),
            'default_threshold'           => $defaultThreshold,
            'tp_metrics'                  => $tpClassMetrics,
            'top_tp'                      => $topTp,
            'lowest_tp'                   => $lowestTp,
            'tps_needing_reinforcement'   => $tpsNeedingReinforcement,
            'calculation_base_note'       => sprintf(
                'Perhitungan didasarkan pada %d siswa dengan hasil penilaian sah dan lengkap (dari %d siswa terdaftar).',
                $totalEvaluatedStudents,
                $totalRegisteredStudents
            ),
        ];

        return [
            'test_config'      => $testConfig,
            'tp_definitions'   => $tpDefinitions,
            'items_count'      => count($items),
            'valid_items_count'=> count($activeObjectiveItems),
            'student_analysis' => $studentReports,
            'class_analysis'   => $classAnalysis,
            'analyzed_at'      => date('Y-m-d H:i:s'),
        ];
    }
}
