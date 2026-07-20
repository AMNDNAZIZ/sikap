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

        $grades = [];
        $allFormatifCols = [];
        $allSumatifCols = [];
        $hasFinal = false;

        /** ============================================================
         *  LOOP SETIAP SEMESTER
         *  ============================================================*/
        foreach ($semesters as $semester) {

            /** ------------------------------------------------------------
             * 1) Bentuk daftar kolom FORMATIF global
             * ------------------------------------------------------------*/
            $materials = $this->db->table('subject_materials')
                ->select('id, subject_id, semester, year_id')
                ->where('year_id', $activeYear['id'])
                ->where('semester', $semester)
                ->orderBy('id')
                ->get()
                ->getResultArray();

            // Kelompokkan material per mapel
            $materialsBySubject = [];
            $maxMaterials = 0;

            foreach ($subjects as $sub) {
                $sid = $sub['subject_id'];
                $materialsBySubject[$sid] = array_values(array_filter(
                    $materials,
                    fn($m) => $m['subject_id'] == $sid
                ));
                $maxMaterials = max($maxMaterials, count($materialsBySubject[$sid]));
            }

            /** --- Ambil semua tipe formatif global berdasarkan posisi materi --- */
            $formatifTypes = [];
            for ($i = 0; $i < $maxMaterials; $i++) {
                $foundTypes = [];

                foreach ($subjects as $sub) {
                    $sid = $sub['subject_id'];
                    if (!isset($materialsBySubject[$sid][$i]))
                        continue;

                    $matId = $materialsBySubject[$sid][$i]['id'];
                    $types = $this->db->table('material_scores')
                        ->distinct()
                        ->select('type')
                        ->where('material_id', $matId)
                        ->get()
                        ->getResultArray();

                    foreach ($types as $t) {
                        $foundTypes[$t['type']] = true;
                    }
                }
                $formatifTypes[$i] = array_keys($foundTypes);
            }

            /** --- Bentuk metadata kolom formatif --- */
            $allFormatifCols[$semester] = [];
            for ($i = 0; $i < $maxMaterials; $i++) {
                foreach ($formatifTypes[$i] as $type) {
                    $pos = $i + 1;
                    $key = "mat:{$pos}:{$type}";
                    $allFormatifCols[$semester][$key] = [
                        'label' => "M{$pos} ({$type})",
                        'tooltip' => "Materi ke-{$pos}"
                    ];
                }
            }

            /** ------------------------------------------------------------
             * 2) SUMATIF — disatukan seluruh mapel, hanya tipe yang berbeda
             * ------------------------------------------------------------*/
            $sumTypes = $this->db->table('summative_scores')
                ->distinct()
                ->select('type')
                ->where('year_id', $activeYear['id'])
                ->where('semester', $semester)
                ->get()
                ->getResultArray();

            $allSumatifCols[$semester] = [];
            foreach ($sumTypes as $t) {
                $type = $t['type'];
                $key = "sum:{$type}";

                $allSumatifCols[$semester][$key] = [
                    'label' => "Sumatif ({$type})",
                    'tooltip' => "Semua mapel"
                ];
            }

            /** ------------------------------------------------------------
             * 3) ISI NILAI PER MAPEL
             * ------------------------------------------------------------*/
            foreach ($subjects as $sub) {

                $sid = $sub['subject_id'];
                $sname = $sub['subject_name'];

                $grades[$semester][$sid] = [
                    'subject' => $sname,
                    'formatif' => [],
                    'sumatif' => [],
                    'avg_formatif' => null,
                    'avg_sumatif' => null,
                    'nilai_akhir' => null,
                ];

                /* FORMATIF */
                $formatifValues = [];

                for ($i = 0; $i < $maxMaterials; $i++) {
                    $pos = $i + 1;
                    if (!isset($materialsBySubject[$sid][$i])) {
                        foreach ($formatifTypes[$i] as $type) {
                            $key = "mat:{$pos}:{$type}";
                            $grades[$semester][$sid]['formatif'][$key] = null;
                        }
                        continue;
                    }

                    $matId = $materialsBySubject[$sid][$i]['id'];

                    foreach ($formatifTypes[$i] as $type) {
                        $key = "mat:{$pos}:{$type}";

                        $score = $this->db->table('material_scores')
                            ->select('score')
                            ->where('material_id', $matId)
                            ->where('student_id', $student['id'])
                            ->where('type', $type)
                            ->get()
                            ->getRowArray()['score'] ?? null;

                        $grades[$semester][$sid]['formatif'][$key] = $score;

                        if ($score !== null) {
                            $formatifValues[] = (float) $score;
                        }
                    }
                }

                if (!empty($formatifValues)) {
                    $grades[$semester][$sid]['avg_formatif'] =
                        round(array_sum($formatifValues) / count($formatifValues), 2);
                }

                /* SUMATIF (global type) */
                $sumatifValues = [];

                foreach ($sumTypes as $t) {
                    $type = $t['type'];
                    $key = "sum:{$type}";

                    $score = $this->db->table('summative_scores')
                        ->select('score')
                        ->where('student_id', $student['id'])
                        ->where('subject_id', $sid)
                        ->where('type', $type)
                        ->where('year_id', $activeYear['id'])
                        ->where('semester', $semester)
                        ->get()
                        ->getRowArray()['score'] ?? null;

                    $grades[$semester][$sid]['sumatif'][$key] = $score;

                    if ($score !== null) {
                        $sumatifValues[] = (float) $score;
                    }
                }

                if (!empty($sumatifValues)) {
                    $grades[$semester][$sid]['avg_sumatif'] =
                        round(array_sum($sumatifValues) / count($sumatifValues), 2);
                }

                /** Nilai akhir sistem (% formatif + sumatif) */
                $af = $grades[$semester][$sid]['avg_formatif'];
                $as = $grades[$semester][$sid]['avg_sumatif'];

                $nilaiSistem = null;
                if ($af !== null || $as !== null) {
                    $fWeight = ($activeYear['formatif_weight'] ?? 60) / 100;
                    $sWeight = ($activeYear['sumatif_weight'] ?? 40) / 100;
                    $nilaiSistem = round((($af ?? 0) * $fWeight) + (($as ?? 0) * $sWeight), 2);
                }

                // Cek apakah guru sudah mengisi nilai erapor
                $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
                $eraporRow = $this->db->table('grades')
                    ->select('erapor_score')
                    ->where('student_id', $student['id'])
                    ->where('subject_id', $sid)
                    ->where('year_id', $activeYear['id'])
                    ->where('semester', $semesterLabel)
                    ->where('erapor_score IS NOT NULL', null, false)
                    ->get()->getRowArray();
                $erapor = $eraporRow['erapor_score'] ?? null;

                // Nilai akhir = erapor (prerogratif guru) jika sudah diisi,
                // fallback ke nilai sistem (% formatif + sumatif)
                $grades[$semester][$sid]['nilai_akhir']  = $erapor ?? $nilaiSistem;
                $grades[$semester][$sid]['erapor']       = $erapor;
                $grades[$semester][$sid]['nilai_sistem']  = $nilaiSistem;

                /** FINAL (semester 2) */
                if ($semester == 2) {
                    $maxLevel = $this->db->table('classes')->selectMax('level')->get()->getRowArray()['level'];

                    if ($student['class_level'] == $maxLevel) {
                        $hasFinal = true;
                        $grades[$semester][$sid]['final'] =
                            $this->db->table('final_exam_scores')
                                ->select('score')
                                ->where('student_id', $student['id'])
                                ->where('subject_id', $sid)
                                ->where('year_id', $activeYear['id'])
                                ->get()
                                ->getRowArray()['score'] ?? null;
                    }
                }
            }
        }

        return [
            'student' => $student,
            'activeYear' => $activeYear,
            'semesters' => $semesters,
            'grades' => $grades,
            'allFormatifCols' => $allFormatifCols,
            'allSumatifCols' => $allSumatifCols,
            'hasFinal' => $hasFinal,
        ];
    }

}
