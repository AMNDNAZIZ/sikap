<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use CodeIgniter\Database\BaseConnection;
use Dompdf\Dompdf;
use Dompdf\Options;

class Grades extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    /** ============================================================
     *  VIEW INDEX
     *  ============================================================*/
    public function index()
    {
        $user = session()->get('user');
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $data = $this->buildGradesData($user['student_id']);
        return view('siswa/grades/index', $data);
    }


    /** ============================================================
     *  PDF EXPORT
     *  ============================================================*/
    public function pdf($studentId = null)
    {
        $user = session()->get('user');
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $semester = $this->request->getGet('semester');

        $data = $this->buildGradesData($user['student_id']);

        if (!in_array($semester, $data['semesters'])) {
            return redirect()->back()->with('error', 'Semester tidak valid.');
        }

        $html = view('siswa/grades/pdf', array_merge($data, [
            'semester' => $semester
        ]));

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = "Nilai_Semester_{$semester}.pdf";
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream($filename, ["Attachment" => true]);
        exit;
    }


    /** ============================================================
     *  FUNGSI UTAMA PEMBENTUK DATA NILAI
     *  ============================================================*/
    private function buildGradesData($studentId)
    {
        /** --- Ambil data siswa --- */
        $student = $this->db->table('students s')
            ->select('s.*, c.id as class_id, c.name as class_name, c.level as class_level, sr.academic_year_id')
            ->join('student_records sr', 'sr.student_id = s.id', 'left')
            ->join('classes c', 'c.id = sr.class_id', 'left')
            ->where('s.id', $studentId)
            ->orderBy('sr.id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$student) {
            throw new \RuntimeException('Data siswa tidak ditemukan.');
        }

        $classId = (int) ($student['class_id'] ?? 0);

        /** --- Tahun ajaran aktif --- */
        $activeYear = $this->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$activeYear) {
            throw new \RuntimeException('Tahun ajaran aktif tidak ditemukan.');
        }

        /** --- Mata pelajaran siswa --- */
        $subjectsRaw = $this->db->table('teaching_assignments ta')
            ->select('ta.subject_id, s.name as subject_name, s.religion, s.sort_order')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $student['class_id'])
            ->where('ta.academic_year_id', $activeYear['id'])
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()
            ->getResultArray();

        // Filter subjects based on student's religion
        $subjects = [];
        foreach ($subjectsRaw as $subject) {
            // If subject has a religion specified, only show if it matches student's religion
            if (!empty($subject['religion']) && $subject['religion'] !== $student['religion']) {
                continue;
            }
            $subjects[] = $subject;
        }

        $semesters = [1, 2];

        $grades      = [];
        $maxFormatif = [];
        $maxSumatif  = [];
        $hasFinal    = false;

        /** ============================================================
         *  LOOP SETIAP SEMESTER
         *  ============================================================*/
        foreach ($semesters as $semester) {
            $maxFormatif[$semester] = 0;
            $maxSumatif[$semester]  = 0;

            // 1. Ambil semua asesmen formatif yang AKTUAL ADA di database untuk kelas & tahun ajaran & semester ini
            // (a) Melalui subject_materials
            $smBuilder = $this->db->table('material_scores ms')
                ->select('sm.subject_id, ms.material_id, ms.type, sm.title, sm.sort_order')
                ->distinct()
                ->join('subject_materials sm', 'sm.id = ms.material_id')
                ->where('sm.year_id', $activeYear['id'])
                ->where('sm.semester', $semester)
                ->where('ms.score IS NOT NULL', null, false);

            if ($classId > 0) {
                $smBuilder->join('student_records sr', 'sr.student_id = ms.student_id AND sr.academic_year_id = ' . (int)$activeYear['id'])
                          ->where('sr.class_id', $classId);
            }
            $smAssessments = $smBuilder->orderBy('sm.subject_id', 'ASC')
                ->orderBy('sm.sort_order', 'ASC')
                ->orderBy('sm.id', 'ASC')
                ->get()->getResultArray();

            // (b) Melalui alur_tujuan_pembelajaran (ATP)
            $atpBuilder = $this->db->table('material_scores ms')
                ->select('atp.subject_id, ms.material_id, ms.type, atp.lingkup_materi as title, atp.urutan as sort_order')
                ->distinct()
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
                ->where('atp.semester', $semester)
                ->where('ms.score IS NOT NULL', null, false);

            if ($classId > 0) {
                $atpBuilder->where('atp.class_id', $classId);
            }
            $atpAssessments = $atpBuilder->orderBy('atp.subject_id', 'ASC')
                ->orderBy('atp.urutan', 'ASC')
                ->orderBy('atp.id', 'ASC')
                ->get()->getResultArray();

            // Gabungkan asesmen yang ada per mata pelajaran
            $assessmentsBySub = [];
            foreach ($smAssessments as $a) {
                $key = $a['material_id'] . ':' . $a['type'];
                $assessmentsBySub[$a['subject_id']][$key] = $a;
            }
            foreach ($atpAssessments as $a) {
                $key = $a['material_id'] . ':' . $a['type'];
                if (!isset($assessmentsBySub[$a['subject_id']][$key])) {
                    $assessmentsBySub[$a['subject_id']][$key] = $a;
                }
            }

            // 2. Ambil seluruh skor formatif milik siswa ini
            $studentFormatifScores = $this->db->table('material_scores')
                ->where('student_id', $student['id'])
                ->where('score IS NOT NULL', null, false)
                ->get()->getResultArray();

            $scoresMap = [];
            foreach ($studentFormatifScores as $sc) {
                $scoresMap[(int)$sc['material_id']][$sc['type']] = $sc;

                // Pastikan jika ada asesmen milik siswa ini yang belum masuk daftar kelas, tetap masuk
                $mid   = (int)$sc['material_id'];
                $mType = $sc['type'];
                $key   = $mid . ':' . $mType;

                $smRow = $this->db->table('subject_materials')
                    ->where('id', $mid)
                    ->where('year_id', $activeYear['id'])
                    ->where('semester', $semester)
                    ->get()->getRowArray();
                if ($smRow) {
                    $subId = $smRow['subject_id'];
                    if (!isset($assessmentsBySub[$subId][$key])) {
                        $assessmentsBySub[$subId][$key] = [
                            'subject_id'  => $subId,
                            'material_id' => $mid,
                            'type'        => $mType,
                            'title'       => $smRow['title'] ?: 'Materi',
                            'sort_order'  => $smRow['sort_order'],
                        ];
                    }
                } else {
                    $atpRow = $this->db->table('alur_tujuan_pembelajaran')
                        ->where('id', $mid)
                        ->where('semester', $semester)
                        ->get()->getRowArray();
                    if ($atpRow) {
                        $subId = $atpRow['subject_id'];
                        if (!isset($assessmentsBySub[$subId][$key])) {
                            $assessmentsBySub[$subId][$key] = [
                                'subject_id'  => $subId,
                                'material_id' => $mid,
                                'type'        => $mType,
                                'title'       => $atpRow['lingkup_materi'] ?: 'Materi',
                                'sort_order'  => $atpRow['urutan'],
                            ];
                        }
                    }
                }
            }

            // 3. Ambil asesmen sumatif yang AKTUAL ADA di database
            $sumBuilder = $this->db->table('summative_scores ss')
                ->select('ss.subject_id, ss.type')
                ->distinct()
                ->where('ss.year_id', $activeYear['id'])
                ->where('ss.semester', $semester)
                ->where('ss.score IS NOT NULL', null, false);

            if ($classId > 0) {
                $sumBuilder->join('student_records sr', 'sr.student_id = ss.student_id AND sr.academic_year_id = ' . (int)$activeYear['id'])
                           ->where('sr.class_id', $classId);
            }
            $sumAssessments = $sumBuilder->orderBy('ss.id', 'ASC')->get()->getResultArray();

            $sumatifBySub = [];
            foreach ($sumAssessments as $sa) {
                $sumatifBySub[$sa['subject_id']][$sa['type']] = $sa['type'];
            }

            // Skor sumatif siswa ini
            $studentSumatifScores = $this->db->table('summative_scores')
                ->where('student_id', $student['id'])
                ->where('year_id', $activeYear['id'])
                ->where('semester', $semester)
                ->where('score IS NOT NULL', null, false)
                ->get()->getResultArray();

            $sumScoresMap = [];
            foreach ($studentSumatifScores as $ssc) {
                $sumScoresMap[$ssc['subject_id']][$ssc['type']] = $ssc;
                if (!isset($sumatifBySub[$ssc['subject_id']][$ssc['type']])) {
                    $sumatifBySub[$ssc['subject_id']][$ssc['type']] = $ssc['type'];
                }
            }

            // 4. Ambil nilai erapor untuk semester ini (prerogatif guru)
            $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
            $eraporScores = $this->db->table('grades')
                ->select('subject_id, erapor_score')
                ->where('student_id', $student['id'])
                ->where('year_id', $activeYear['id'])
                ->where('semester', $semesterLabel)
                ->where('erapor_score IS NOT NULL', null, false)
                ->get()->getResultArray();

            $eraporBySub = [];
            foreach ($eraporScores as $row) {
                $eraporBySub[$row['subject_id']] = $row['erapor_score'];
            }

            // 5. Cek nilai final (semester 2 untuk tingkat akhir)
            $finalScores = [];
            if ($semester == 2) {
                $maxLevel = $this->db->table('classes')->selectMax('level')->get()->getRowArray()['level'];
                if ($student['class_level'] == $maxLevel) {
                    $hasFinal = true;
                    $finalRows = $this->db->table('final_exam_scores')
                        ->select('subject_id, score')
                        ->where('student_id', $student['id'])
                        ->where('year_id', $activeYear['id'])
                        ->get()->getResultArray();

                    foreach ($finalRows as $row) {
                        $finalScores[$row['subject_id']] = $row['score'];
                    }
                }
            }

            // 6. Bentuk baris nilai per mata pelajaran
            foreach ($subjects as $sub) {
                $sid   = $sub['subject_id'];
                $sname = $sub['subject_name'];

                $fItems = array_values($assessmentsBySub[$sid] ?? []);
                $sItems = array_values($sumatifBySub[$sid] ?? []);

                // Susun nilai formatif berurutan
                $fList = [];
                foreach ($fItems as $item) {
                    $sc = $scoresMap[(int)$item['material_id']][$item['type']] ?? null;
                    $fList[] = [
                        'material_id'    => $item['material_id'],
                        'material_title' => $item['title'] ?: 'Materi',
                        'type'           => $item['type'],
                        'score'          => $sc ? $sc['score'] : null,
                    ];
                }

                // Susun nilai sumatif berurutan
                $sList = [];
                foreach ($sItems as $stype) {
                    $sc = $sumScoresMap[$sid][$stype] ?? null;
                    $sList[] = [
                        'type'  => $stype,
                        'score' => $sc ? $sc['score'] : null,
                    ];
                }

                // Hitung jumlah penilaian maksimal yang ada
                if (count($fList) > $maxFormatif[$semester]) {
                    $maxFormatif[$semester] = count($fList);
                }
                if (count($sList) > $maxSumatif[$semester]) {
                    $maxSumatif[$semester] = count($sList);
                }

                // Rata-rata Formatif (hanya dari yang sudah dinilai)
                $avgFormatif = null;
                $fScored = array_filter($fList, fn($item) => $item['score'] !== null && $item['score'] !== '');
                if (!empty($fScored)) {
                    $fVals = array_map(fn($item) => (float) $item['score'], $fScored);
                    $avgFormatif = round(array_sum($fVals) / count($fVals), 2);
                }

                // Rata-rata Sumatif (hanya dari yang sudah dinilai)
                $avgSumatif = null;
                $sScored = array_filter($sList, fn($item) => $item['score'] !== null && $item['score'] !== '');
                if (!empty($sScored)) {
                    $sVals = array_map(fn($item) => (float) $item['score'], $sScored);
                    $avgSumatif = round(array_sum($sVals) / count($sVals), 2);
                }

                // Nilai akhir sistem (% formatif + sumatif)
                $nilaiSistem = null;
                if ($avgFormatif !== null || $avgSumatif !== null) {
                    $fWeight = ($activeYear['formatif_weight'] ?? 60) / 100;
                    $sWeight = ($activeYear['sumatif_weight'] ?? 40) / 100;
                    $nilaiSistem = round((($avgFormatif ?? 0) * $fWeight) + (($avgSumatif ?? 0) * $sWeight), 2);
                }

                $erapor = $eraporBySub[$sid] ?? null;

                $grades[$semester][$sid] = [
                    'subject'      => $sname,
                    'formatif'     => $fList,
                    'sumatif'      => $sList,
                    'avg_formatif' => $avgFormatif,
                    'avg_sumatif'  => $avgSumatif,
                    'nilai_akhir'  => $erapor ?? $nilaiSistem,
                    'erapor'       => $erapor,
                    'nilai_sistem' => $nilaiSistem,
                ];

                if ($semester == 2 && $hasFinal) {
                    $grades[$semester][$sid]['final'] = $finalScores[$sid] ?? null;
                }
            }
        }

        return [
            'student'     => $student,
            'activeYear'  => $activeYear,
            'semesters'   => $semesters,
            'grades'      => $grades,
            'maxFormatif' => $maxFormatif,
            'maxSumatif'  => $maxSumatif,
            'hasFinal'    => $hasFinal,
        ];
    }

}
