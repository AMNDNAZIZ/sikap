<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Dompdf\Dompdf;
use Dompdf\Options;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class GradesManageController extends BaseController
{
    public function index()
    {
        $session = session();
        $db = db_connect();
        $user = $session->get('user');

        $role = '';
        $teachers = [];
        $class = null;
        $subjects = [];
        $assignments = [];

        // Fetch all academic years for admin
        $academicYears = $db->table('academic_years')
            ->orderBy('year', 'DESC')
            ->get()->getResultArray();

        if ($user['role_id'] == 1 || $user['role_id'] == 2) { // admin atau kepala sekolah
            $role = 'admin';
            $teachers = $db->table('teachers')
                ->select('teachers.id, teachers.name, users.username')
                ->join('users', 'users.id = teachers.user_id')
                ->get()->getResultArray();
        } elseif ($user['role_id'] == 3) { // guru
            // Ambil tahun ajaran aktif
            $activeYear = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            $activeYearId = $activeYear['id'] ?? 0;

            // cek apakah guru ini wali kelas
            $class = $db->table('classes')
                ->where('teacher_id', $user['teacher_id'])
                ->get()->getRowArray();

            if ($class) {
                // guru kelas
                $role = 'homeroom';
                $subjects = $db->table('teaching_assignments ta')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.teacher_id', $user['teacher_id'])
                    ->where('ta.class_id', $class['id'])
                    ->where('ta.academic_year_id', $activeYearId)
                    ->select('s.id, s.name')
                    ->get()->getResultArray();
            } else {
                // guru mapel
                $role = 'subject_teacher';

                $tas = $db->table('teaching_assignments ta')
                    ->join('classes c', 'c.id = ta.class_id')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.teacher_id', $user['teacher_id'])
                    ->where('ta.academic_year_id', $activeYearId)
                    ->select('c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
                    ->get()->getResultArray();

                foreach ($tas as $row) {
                    $assignments[$row['class_id']]['class'] = [
                        'id' => $row['class_id'],
                        'name' => $row['class_name'],
                    ];
                    $assignments[$row['class_id']]['subjects'][] = [
                        'id' => $row['subject_id'],
                        'name' => $row['subject_name'],
                    ];
                }
            }
        }

        return view('admin/grades/manage_index', [
            'role' => $role,
            'teachers' => $teachers,
            'class' => $class,
            'subjects' => $subjects,
            'assignments' => $assignments,
            'academicYears' => $academicYears,
        ]);
    }

    public function selectTeacher()
    {
        $teacherId = $this->request->getGet('teacher_id');
        $db = db_connect();

        $teacher = $db->table('teachers')->where('id', $teacherId)->get()->getRowArray();

        // cek apakah guru ini homeroom
        $class = $db->table('classes')->where('teacher_id', $teacherId)->get()->getRowArray();

        if ($class) {
            // redirect ke pilih subject seperti homeroom
            return redirect()->to('admin/grades/select-subject/' . $class['id'] . '?teacher_id=' . $teacherId);
        } else {
            // subject teacher → pilih class & subject
            return redirect()->to('admin/grades/select-class-subject?teacher_id=' . $teacherId);
        }
    }

    public function selectSubject($classId)
    {
        $subjectId = $this->request->getGet('subject_id');
        $teacherId = $this->request->getGet('teacher_id');

        if ($subjectId) {
            return redirect()->to("admin/grades/show/$classId/$subjectId?teacher_id=$teacherId");
        }

        return redirect()->back()->with('error', 'Silakan pilih mapel');
    }

    public function selectClassSubject()
    {
        $classId = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');
        $teacherId = $this->request->getGet('teacher_id');

        if ($classId && $subjectId) {
            return redirect()->to("admin/grades/show/$classId/$subjectId?teacher_id=$teacherId");
        }

        return redirect()->back()->with('error', 'Silakan pilih kelas dan mapel');
    }

    private function showData($classId, $subjectId, $semester = 1)
    {
        $db = db_connect();

        // Get year_id from query parameter if provided
        $requestedYearId = $this->request->getGet('year_id');

        // Kelas
        $class = $db->table('classes')->where('id', $classId)->get()->getRowArray();
        if (!$class)
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Kelas tidak ditemukan");

        // Mapel
        $subject = $db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if (!$subject)
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Mata pelajaran tidak ditemukan");

        // Tahun ajaran (prioritas: parameter, lalu dari student_records)
        if ($requestedYearId) {
            $yearRow = $db->table('academic_years')
                ->where('id', $requestedYearId)
                ->get()->getRowArray();
            $academicYearId = $yearRow['id'] ?? null;
            $yearName = $yearRow['year'] ?? '-';
        } else {
            $yearRow = $db->table('student_records sr')
                ->select('sr.academic_year_id, ay.year')
                ->join('academic_years ay', 'ay.id = sr.academic_year_id')
                ->where('sr.class_id', $classId)
                ->orderBy('sr.academic_year_id', 'DESC')
                ->get()
                ->getRowArray();
            $academicYearId = $yearRow['academic_year_id'] ?? null;
            $yearName = $yearRow['year'] ?? '-';
        }

        // Daftar siswa
        $students = $db->table('student_records sr')
            ->select('s.id, s.name')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $academicYearId)
            ->orderBy('s.name', 'ASC')
            ->get()
            ->getResultArray();

        // Materi sesuai semester (Ambil dari ATP)
        $materials = $db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi as title, atp.semester')
            ->where('atp.subject_id', $subjectId)
            ->where('atp.class_id', $classId)
            ->where('atp.semester', $semester)
            ->orderBy('atp.urutan', 'ASC')
            ->get()
            ->getResultArray();

        // Nilai formatif
        $materialScores = $db->table('material_scores ms')
            ->select('ms.student_id, ms.material_id, ms.type, ms.score')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
            ->join('student_records sr', 'sr.student_id = ms.student_id')
            ->where('atp.subject_id', $subjectId)
            ->where('atp.class_id', $classId)
            ->where('atp.semester', $semester)
            ->where('sr.academic_year_id', $academicYearId)
            ->get()
            ->getResultArray();

        // Nilai sumatif
        $summativeScores = $db->table('summative_scores ss')
            ->select('ss.student_id, ss.type, ss.score')
            ->join('student_records sr', 'sr.student_id = ss.student_id')
            ->where('ss.subject_id', $subjectId)
            ->where('ss.semester', $semester)
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $academicYearId)
            ->get()
            ->getResultArray();

        // Nilai final (khusus semester 2)
        $finalScores = [];
        if ($semester == 2) {
            $finalScores = $db->table('final_exam_scores fe')
                ->select('fe.student_id, fe.score')
                ->join('student_records sr', 'sr.student_id = fe.student_id')
                ->where('fe.subject_id', $subjectId)
                ->where('sr.class_id', $classId)
                ->where('sr.academic_year_id', $academicYearId)
                ->get()
                ->getResultArray();
        }

        // Struktur nilai per siswa
        $scores = [];
        foreach ($students as $stu) {
            $scores[$stu['id']][$semester] = [
                'formatif' => [],
                'formatif_avg' => null,
                'sumatif' => [],
                'sumatif_avg' => null,
                'rapor' => null,
                'final' => null,
                'erapor' => null,
            ];
        }

        // Metode formatif
        $formatifMethods = [];
        foreach ($materialScores as $ms) {
            $mid = (int) $ms['material_id'];
            $type = (string) $ms['type'];

            if (!isset($formatifMethods[$mid]))
                $formatifMethods[$mid] = [];
            if (!in_array($type, $formatifMethods[$mid], true)) {
                $formatifMethods[$mid][] = $type;
            }

            if (isset($scores[$ms['student_id']])) {
                $scores[$ms['student_id']][$semester]['formatif'][$mid][$type] = $ms['score'];
            }
        }

        // Hanya materi yang ada nilainya
        $visibleMaterials = [];
        foreach ($materials as $m) {
            if (!empty($formatifMethods[$m['id']])) {
                $visibleMaterials[] = $m;
            }
        }

        // Urutkan metode formatif
        $methodOrder = ['tulis', 'lisan', 'projek', 'observasi'];
        foreach ($formatifMethods as $mid => &$types) {
            $ordered = [];
            foreach ($methodOrder as $mo) {
                if (in_array($mo, $types, true))
                    $ordered[] = $mo;
            }
            foreach ($types as $t) {
                if (!in_array($t, $ordered, true))
                    $ordered[] = $t;
            }
            $types = $ordered;
        }
        unset($types);

        // Metode sumatif
        $sumatifMethods = [];
        foreach ($summativeScores as $ss) {
            $type = (string) $ss['type'];
            if (!in_array($type, $sumatifMethods, true))
                $sumatifMethods[] = $type;

            if (isset($scores[$ss['student_id']])) {
                $scores[$ss['student_id']][$semester]['sumatif'][ucfirst($type)] = $ss['score'];
            }
        }

        // Isi final
        foreach ($finalScores as $fs) {
            if (isset($scores[$fs['student_id']])) {
                $scores[$fs['student_id']][$semester]['final'] = $fs['score'];
            }
        }

        // Ambil nilai erapor dari tabel grades
        $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
        $studentIds    = array_column($students, 'id');
        $eraporRows    = $db->table('grades')
            ->select('student_id, erapor_score')
            ->whereIn('student_id', $studentIds)
            ->where('subject_id', $subjectId)
            ->where('year_id', $academicYearId)
            ->where('semester', $semesterLabel)
            ->where('erapor_score IS NOT NULL', null, false)
            ->get()->getResultArray();
        foreach ($eraporRows as $er) {
            if (isset($scores[$er['student_id']])) {
                $scores[$er['student_id']][$semester]['erapor'] = $er['erapor_score'];
            }
        }

        // Hitung rata2
        foreach ($students as $stu) {
            $sid = $stu['id'];

            $fvals = [];
            foreach ($scores[$sid][$semester]['formatif'] as $types) {
                foreach ($types as $v) {
                    if ($v !== null && $v !== '')
                        $fvals[] = $v;
                }
            }
            if (!empty($fvals)) {
                $scores[$sid][$semester]['formatif_avg'] = round(array_sum($fvals) / count($fvals), 2);
            }

            $svals = array_filter(array_values($scores[$sid][$semester]['sumatif'] ?? []), fn($v) => $v !== null && $v !== '');
            if (!empty($svals)) {
                $scores[$sid][$semester]['sumatif_avg'] = round(array_sum($svals) / count($svals), 2);
            }

            $favg = $scores[$sid][$semester]['formatif_avg'] ?? 0;
            $savg = $scores[$sid][$semester]['sumatif_avg'] ?? 0;
            if ($favg || $savg) {
                // Ambil bobot dari tahun ajaran (academicYearId is available in scope)
                $yearRecord = $db->table('academic_years')->where('id', $academicYearId)->get()->getRowArray();
                $fWeight = ($yearRecord['formatif_weight'] ?? 60) / 100;
                $sWeight = ($yearRecord['sumatif_weight'] ?? 40) / 100;

                $scores[$sid][$semester]['rapor'] = round(($favg * $fWeight) + ($savg * $sWeight), 2);
            }
        }

        $hasFinal = ($semester == 2 && !empty($finalScores));

        return [
            'class' => $class,
            'subject' => $subject,
            'yearName' => $yearName,
            'yearId' => $academicYearId,
            'students' => $students,
            'materials' => $materials,
            'visibleMaterials' => $visibleMaterials,
            'formatifMethods' => $formatifMethods,
            'sumatifMethods' => $sumatifMethods,
            'scores' => $scores,
            'semester' => $semester,
            'hasFinal' => $hasFinal,
        ];
    }

    /**
     * Show view browser
     */
    public function show($classId, $subjectId, $semester = 1)
    {
        $data = $this->showData($classId, $subjectId, $semester);
        return view('admin/grades/manage_show', $data);
    }

    /**
     * Export PDF
     */
    public function pdf($classId, $subjectId, $semester = 1)
    {
        $data = $this->showData($classId, $subjectId, $semester);

        $options = new Options();
        $options->set('isRemoteEnabled', true);

        $dompdf = new Dompdf($options);
        $html = view('admin/grades/pdf_template', $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream("Nilai_Semester{$semester}.pdf", ["Attachment" => true]);
        exit;
    }

    /**
     * Export Excel
     */
    public function excel($classId, $subjectId, $semester = 1)
    {
        $data = $this->showData($classId, $subjectId, $semester);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'DAFTAR NILAI');
        $sheet->setCellValue('A2', 'Tahun Ajaran: ' . $data['yearName']);
        $sheet->setCellValue('A3', 'Kelas: ' . $data['class']['name']);
        $sheet->setCellValue('A4', 'Mata Pelajaran: ' . $data['subject']['name']);
        $sheet->setCellValue('A5', 'Semester: ' . $semester);

        // Header tabel
        $col = 'A';
        $sheet->setCellValue($col++ . '6', 'No');
        $sheet->setCellValue($col++ . '6', 'Nama Siswa');

        foreach ($data['visibleMaterials'] as $mat) {
            foreach ($data['formatifMethods'][$mat['id']] as $method) {
                $sheet->setCellValue($col . '6', $mat['title'] . ' (' . $method . ')');
                $col++;
            }
        }

        foreach ($data['sumatifMethods'] as $method) {
            $sheet->setCellValue($col . '6', 'Sumatif (' . $method . ')');
            $col++;
        }

        if ($data['hasFinal']) {
            $sheet->setCellValue($col++ . '6', 'Final');
        }

        $sheet->setCellValue($col . '6', 'Rapor');

        // Data siswa
        $row = 7;
        $no = 1;
        foreach ($data['students'] as $stu) {
            $col = 'A';
            $sheet->setCellValue($col++ . $row, $no++);
            $sheet->setCellValue($col++ . $row, $stu['name']);

            foreach ($data['visibleMaterials'] as $mat) {
                foreach ($data['formatifMethods'][$mat['id']] as $method) {
                    $val = $data['scores'][$stu['id']][$semester]['formatif'][$mat['id']][$method] ?? '';
                    $sheet->setCellValue($col++ . $row, $val);
                }
            }

            foreach ($data['sumatifMethods'] as $method) {
                $val = $data['scores'][$stu['id']][$semester]['sumatif'][ucfirst($method)] ?? '';
                $sheet->setCellValue($col++ . $row, $val);
            }

            if ($data['hasFinal']) {
                $val = $data['scores'][$stu['id']][$semester]['final'] ?? '';
                $sheet->setCellValue($col++ . $row, $val);
            }

            $sheet->setCellValue($col . $row, $data['scores'][$stu['id']][$semester]['rapor'] ?? '');
            $row++;
        }

        // Download file
        $writer = new Xlsx($spreadsheet);
        $filename = "Nilai_Semester{$semester}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment;filename=\"$filename\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    public function exportExcel($classId, $subjectId, $semester = 1)
    {
        $data = $this->showData($classId, $subjectId, $semester);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->setCellValue('A1', 'DAFTAR NILAI');
        $sheet->setCellValue('A2', 'Tahun Ajaran: ' . $data['yearName']);
        $sheet->setCellValue('A3', 'Kelas: ' . $data['class']['name']);
        $sheet->setCellValue('A4', 'Mata Pelajaran: ' . $data['subject']['name']);
        $sheet->setCellValue('A5', 'Semester: ' . $semester);

        $rowStart = 7;
        $col = 1;

        // Header baris pertama
        $sheet->setCellValue([$col++, $rowStart], 'No');
        $sheet->setCellValue([$col++, $rowStart], 'Nama Siswa');

        $formCols = 0;
        foreach ($data['visibleMaterials'] as $m) {
            $formCols += count($data['formatifMethods'][$m['id']] ?? []);
        }
        if ($formCols > 0) {
            $sheet->setCellValue([$col, $rowStart], 'Formatif');
            $sheet->mergeCells([$col, $rowStart, $col + $formCols, $rowStart]);
            $col += $formCols + 1;
        }
        $sumCols = count($data['sumatifMethods']);
        if ($sumCols > 0) {
            $sheet->setCellValue([$col, $rowStart], 'Sumatif');
            $sheet->mergeCells([$col, $rowStart, $col + $sumCols, $rowStart]);
            $col += $sumCols + 1;
        }

        $sheet->setCellValue([$col++, $rowStart], 'Nilai Rapor');
        if ($semester == 2 && $data['hasFinal']) {
            $sheet->setCellValue([$col++, $rowStart], 'Nilai Final');
        }

        // Header baris kedua
        $row2 = $rowStart + 1;
        $col = 1;
        $sheet->setCellValue([$col++, $row2], '');
        $sheet->setCellValue([$col++, $row2], '');

        foreach ($data['visibleMaterials'] as $idx => $m) {
            $mIndex = $idx + 1;
            foreach ($data['formatifMethods'][$m['id']] as $method) {
                $sheet->setCellValue([$col++, $row2], "M{$mIndex} ($method)");
            }
        }
        if ($formCols > 0) {
            $sheet->setCellValue([$col++, $row2], 'Rerata');
        }

        foreach ($data['sumatifMethods'] as $method) {
            $sheet->setCellValue([$col++, $row2], ucfirst($method));
        }
        if ($sumCols > 0) {
            $sheet->setCellValue([$col++, $row2], 'Rerata');
        }

        $sheet->setCellValue([$col++, $row2], '');
        if ($semester == 2 && $data['hasFinal']) {
            $sheet->setCellValue([$col++, $row2], '');
        }

        // Isi data siswa
        $row = $rowStart + 2;
        foreach ($data['students'] as $i => $stu) {
            $col = 1;
            $sheet->setCellValue([$col++, $row], $i + 1);
            $sheet->setCellValue([$col++, $row], $stu['name']);
            $sid = $stu['id'];

            foreach ($data['visibleMaterials'] as $m) {
                foreach ($data['formatifMethods'][$m['id']] as $method) {
                    $val = $data['scores'][$sid][$semester]['formatif'][$m['id']][$method] ?? '';
                    $sheet->setCellValue([$col++, $row], $val);
                }
            }
            if ($formCols > 0) {
                $favg = $data['scores'][$sid][$semester]['formatif_avg'] ?? '';
                $sheet->setCellValue([$col++, $row], $favg);
            }

            foreach ($data['sumatifMethods'] as $method) {
                $val = $data['scores'][$sid][$semester]['sumatif'][ucfirst($method)] ?? '';
                $sheet->setCellValue([$col++, $row], $val);
            }
            if ($sumCols > 0) {
                $savg = $data['scores'][$sid][$semester]['sumatif_avg'] ?? '';
                $sheet->setCellValue([$col++, $row], $savg);
            }

            $rapor = $data['scores'][$sid][$semester]['rapor'] ?? '';
            $sheet->setCellValue([$col++, $row], $rapor);

            if ($semester == 2 && $data['hasFinal']) {
                $final = $data['scores'][$sid][$semester]['final'] ?? '';
                $sheet->setCellValue([$col++, $row], $final);
            }

            $row++;
        }

        // Styling border
        $highestCol = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();
        $sheet->getStyle("A{$rowStart}:{$highestCol}{$highestRow}")
            ->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Styling header background
        $sheet->getStyle("A{$rowStart}:{$highestCol}{$row2}")
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFEFEFEF');
        $sheet->getStyle("A{$rowStart}:{$highestCol}{$row2}")
            ->getFont()->setBold(true);

        // Download
        $writer = new Xlsx($spreadsheet);
        $filename = "Nilai_Semester{$semester}.xlsx";
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }


    private function getFormatifCols(): array
    {
        return [
            1 => [
                'tugas1' => ['label' => 'T1', 'tooltip' => 'Tugas 1'],
                'tugas2' => ['label' => 'T2', 'tooltip' => 'Tugas 2'],
            ],
            2 => [
                'tugas1' => ['label' => 'T1', 'tooltip' => 'Tugas 1'],
            ],
        ];
    }

    private function getSumatifCols(): array
    {
        return [
            1 => [
                'uts' => ['label' => 'UTS', 'tooltip' => 'Ujian Tengah Semester'],
            ],
            2 => [
                'uas' => ['label' => 'UAS', 'tooltip' => 'Ujian Akhir Semester'],
            ],
        ];
    }

    public function getTeacherInfo($teacherId)
    {
        $db = db_connect();
        $yearId = $this->request->getGet('year_id');

        $teacher = $db->table('teachers t')
            ->join('users u', 'u.id = t.user_id')
            ->select('t.id, u.username, u.role_id')
            ->where('t.id', $teacherId)
            ->get()->getRowArray();

        if (!$teacher) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Guru tidak ditemukan']);
        }

        // cek apakah wali kelas
        $class = $db->table('classes')
            ->where('teacher_id', $teacher['id'])
            ->get()->getRowArray();

        if ($class) {
            // wali kelas
            $query = $db->table('teaching_assignments ta')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $class['id']);

            if ($yearId) {
                $query->where('ta.academic_year_id', $yearId);
            }

            $subjects = $query->select('s.id, s.name')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'status' => 'ok',
                'type' => 'homeroom',
                'class' => $class,
                'subjects' => $subjects
            ]);
        }

        // guru mapel
        $query = $db->table('teaching_assignments ta')
            ->join('classes c', 'c.id = ta.class_id')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.teacher_id', $teacher['id']);

        if ($yearId) {
            $query->where('ta.academic_year_id', $yearId);
        }

        $assignments = $query->select('c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'ok',
            'type' => 'subject_teacher',
            'assignments' => $assignments
        ]);
    }

    public function studentTracking()
    {
        $studentId = $this->request->getGet('student_id');
        $data = [
            'student' => null,
            'records' => [],
            'grades' => [],
        ];

        if ($studentId) {
            $data = $this->getStudentTrackingData($studentId);
        }

        return view('admin/grades/student_tracking', $data);
    }

    private function getStudentTrackingData($studentId)
    {
        $db = db_connect();
        $student = $db->table('students')->where('id', $studentId)->get()->getRowArray();

        if (!$student) {
            return ['student' => null, 'records' => [], 'grades' => [], 'matrix' => [], 'subjects' => []];
        }

        $records = $db->table('student_records sr')
            ->select('sr.*, ay.year as year_name, c.name as class_name')
            ->join('academic_years ay', 'ay.id = sr.academic_year_id')
            ->join('classes c', 'c.id = sr.class_id')
            ->where('sr.student_id', $studentId)
            ->where('sr.status !=', 'lulus')
            ->orderBy('ay.year', 'ASC') // Order ascending for the matrix columns
            ->get()->getResultArray();

        // Timeline view data (old format)
        $grades = [];
        // Matrix view data (new format)
        $matrix = [];
        $allSubjects = [];

        foreach ($records as $record) {
            $yearId = $record['academic_year_id'];
            $classId = $record['class_id'];

            // Get all subjects for this class/year
            $subjects = $db->table('teaching_assignments ta')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $classId)
                ->where('ta.academic_year_id', $yearId)
                ->select('s.id, s.name, s.religion') // Fetch religion
                ->get()->getResultArray();

            foreach ($subjects as $subject) {
                // Filter by religion
                if (!empty($subject['religion']) && $subject['religion'] !== $student['religion']) {
                    continue;
                }

                // Collect all unique subjects
                if (!isset($allSubjects[$subject['name']])) {
                    $allSubjects[$subject['name']] = $subject['name'];
                }

                foreach ([1, 2] as $semester) {
                    $scores = $this->calculateStudentGrade($studentId, $classId, $subject['id'], $yearId, $semester);

                    // Populate timeline data — tampilkan jika ada nilai apapun
                    if ($scores['nilai_akhir'] !== null || $scores['rapor'] !== null) {
                        $grades[$record['id']][$semester][] = [
                            'subject_name' => $subject['name'],
                            'scores' => $scores
                        ];
                    }

                    // Populate matrix data — gunakan nilai_akhir (erapor ?? rapor)
                    $matrix[$subject['name']][$yearId][$semester] = $scores['nilai_akhir'];
                }
            }
        }



        // Sort subjects by custom sort_order
        // Need to fetch sort_order for collected subjects to sort properly if not already available
        // But simpler: fetch all subjects with sort_order first to map names to order
        $subjectOrders = $db->table('subjects')->select('name, sort_order')->get()->getResultArray();
        $orderMap = [];
        foreach ($subjectOrders as $s) {
            $orderMap[$s['name']] = $s['sort_order'];
        }

        // Custom sort function
        uksort($allSubjects, function ($a, $b) use ($orderMap) {
            $orderA = $orderMap[$a] ?? 999;
            $orderB = $orderMap[$b] ?? 999;

            if ($orderA == $orderB) {
                return strcmp($a, $b); // Fallback to alphabetical
            }
            return $orderA <=> $orderB;
        });

        // Fetch School Profile
        $school = $db->table('school_profile')->get()->getRowArray();

        return [
            'student' => $student,
            'records' => $records,
            'grades' => $grades,
            'matrix' => $matrix,
            'subjects' => array_values($allSubjects),
            'school' => $school
        ];
    }

    public function studentTrackingPdf()
    {
        $studentId = $this->request->getGet('student_id');
        if (!$studentId)
            return redirect()->back();

        $data = $this->getStudentTrackingData($studentId);
        if (!$data['student'])
            return redirect()->back();

        // Determine orientation
        $orientation = count($data['records']) > 3 ? 'landscape' : 'portrait';

        $dompdf = new Dompdf();
        $html = view('admin/grades/student_tracking_pdf', $data);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream("Riwayat_Nilai_" . $data['student']['nis'] . ".pdf", ["Attachment" => false]);
        exit;
    }

    public function studentTrackingExcel()
    {
        $studentId = $this->request->getGet('student_id');
        if (!$studentId)
            return redirect()->back();

        $data = $this->getStudentTrackingData($studentId);
        if (!$data['student'])
            return redirect()->back();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header Info
        $sheet->setCellValue('A1', 'DATA RIWAYAT NILAI');
        $sheet->setCellValue('A3', 'Nama');
        $sheet->setCellValue('C3', ': ' . $data['student']['name']);
        $sheet->setCellValue('A4', 'NIS/NISN');
        $sheet->setCellValue('C4', ': ' . $data['student']['nis']);
        $sheet->setCellValue('A5', 'Tempat, Tanggal Lahir');
        //$sheet->setCellValue('C5', ': ' . $data['student']['birth_place'] . ', ' . $data['student']['birth_date']); // Adjust fields if needed

        // Table Header
        // Row 7: Years
        // Row 8: Classes
        // Row 9: Semesters

        $startCol = 3; // C
        $colIndex = $startCol;

        // Draw Headers
        foreach ($data['records'] as $rec) {
            $yearStr = $rec['year_name'];
            $classStr = $rec['class_name'];

            // Year Header (Merged for 2 cols)
            $sheet->setCellValue([$colIndex, 7], $yearStr);
            $sheet->mergeCells([$colIndex, 7, $colIndex + 1, 7]);

            // Class Header (Merged for 2 cols)
            $sheet->setCellValue([$colIndex, 8], $classStr);
            $sheet->mergeCells([$colIndex, 8, $colIndex + 1, 8]);

            // Semester Headers
            $sheet->setCellValue([$colIndex, 9], '1');
            $sheet->setCellValue([$colIndex + 1, 9], '2');

            $colIndex += 2;
        }

        $sheet->setCellValue('A7', 'No');
        $sheet->mergeCells('A7:A9');
        $sheet->setCellValue('B7', 'Mata Pelajaran');
        $sheet->mergeCells('B7:B9');

        // Style borders for header
        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER, 'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle([1, 7, $colIndex - 1, 9])->applyFromArray($headerStyle);

        // Data Rows
        $row = 10;
        $no = 1;
        foreach ($data['subjects'] as $subjectName) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $subjectName);

            $col = $startCol;
            foreach ($data['records'] as $rec) {
                $yid = $rec['academic_year_id'];

                // Semester 1
                $val1 = $data['matrix'][$subjectName][$yid][1] ?? '';
                $sheet->setCellValue([$col, $row], $val1);

                // Semester 2
                $val2 = $data['matrix'][$subjectName][$yid][2] ?? '';
                $sheet->setCellValue([$col + 1, $row], $val2);

                $col += 2;
            }
            $row++;
        }

        // Apply borders to data
        $dataStyle = [
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
        ];
        $sheet->getStyle([1, 10, $colIndex - 1, $row - 1])->applyFromArray($dataStyle);

        // Footer with Signature
        $lastRow = $row + 2;

        $sheet->setCellValue('C' . $lastRow, 'Jakarta, ' . date('d F Y'));
        $sheet->mergeCells('C' . $lastRow . ':E' . $lastRow); // Merge for slightly wider text area if needed, though C is auto-width

        $sheet->setCellValue('C' . ($lastRow + 1), 'Kepala ' . ($data['school']['name'] ?? 'Sekolah'));
        $sheet->mergeCells('C' . ($lastRow + 1) . ':E' . ($lastRow + 1));

        $sheet->setCellValue('C' . ($lastRow + 5), $data['school']['headmaster'] ?? '..........................');
        $sheet->mergeCells('C' . ($lastRow + 5) . ':E' . ($lastRow + 5));

        $sheet->setCellValue('C' . ($lastRow + 6), 'NIP: ' . ($data['school']['principal_nip'] ?? '-'));
        $sheet->mergeCells('C' . ($lastRow + 6) . ':E' . ($lastRow + 6));

        // Align signature center relative to column C (or right side)
        // Adjust column index for signature block placement if it needs to be on the right
        // The PDF puts it on the right. In Excel, let's put it on the right-most columns used.
        $sigCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIndex - 2);

        // Let's protect against small column count
        if ($colIndex < 4)
            $sigCol = 'C';

        $sheet->setCellValue($sigCol . $lastRow, 'Jakarta, ' . date('d F Y'));
        $sheet->setCellValue($sigCol . ($lastRow + 1), 'Kepala ' . ($data['school']['name'] ?? 'Sekolah'));
        $sheet->setCellValue($sigCol . ($lastRow + 5), $data['school']['headmaster'] ?? '..........................');
        $sheet->setCellValue($sigCol . ($lastRow + 6), 'NIP: ' . ($data['school']['principal_nip'] ?? '-'));

        // Clear the previous C column set if we moved it
        if ($sigCol !== 'C') {
            $sheet->setCellValue('C' . $lastRow, '');
            $sheet->setCellValue('C' . ($lastRow + 1), '');
            $sheet->setCellValue('C' . ($lastRow + 5), '');
            $sheet->setCellValue('C' . ($lastRow + 6), '');
        }

        // Auto size columns
        foreach (range(0, $colIndex) as $i) {
            $columnID = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="Riwayat_Nilai_' . $data['student']['nis'] . '.xlsx"');
        $writer->save('php://output');
        exit;
    }

    private function calculateStudentGrade($studentId, $classId, $subjectId, $yearId, $semester)
    {
        $db = db_connect();

        // Formatif — gunakan ATP (alur_tujuan_pembelajaran) bukan subject_materials
        $fscores = $db->table('material_scores ms')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
            ->where('ms.student_id', $studentId)
            ->where('atp.subject_id', $subjectId)
            ->where('atp.class_id', $classId)
            ->where('atp.semester', $semester)
            ->select('ms.score')
            ->get()->getResultArray();

        $fvals = array_filter(array_column($fscores, 'score'), fn($v) => $v !== null && $v !== '');
        $favg = !empty($fvals) ? round(array_sum($fvals) / count($fvals), 2) : null;

        // Sumatif
        $sscores = $db->table('summative_scores')
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('semester', $semester)
            ->where('year_id', $yearId)
            ->select('score')
            ->get()->getResultArray();

        $svals = array_filter(array_column($sscores, 'score'), fn($v) => $v !== null && $v !== '');
        $savg = !empty($svals) ? round(array_sum($svals) / count($svals), 2) : null;

        // Final (only sem 2)
        $final = null;
        if ($semester == 2) {
            $fscore = $db->table('final_exam_scores')
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('year_id', $yearId)
                ->get()->getRowArray();
            $final = $fscore['score'] ?? null;
        }

        // Nilai rapor acuan sistem (perhitungan % formatif + sumatif)
        $rapor = null;
        if ($favg !== null || $savg !== null) {
            $yearRecord = $db->table('academic_years')->where('id', $yearId)->get()->getRowArray();
            $fWeight = ($yearRecord['formatif_weight'] ?? 60) / 100;
            $sWeight = ($yearRecord['sumatif_weight'] ?? 40) / 100;
            $rapor = round((($favg ?? 0) * $fWeight) + (($savg ?? 0) * $sWeight), 2);
        }

        // Nilai erapor — prerogratif guru, diambil dari tabel grades
        $semesterLabel = $semester == 1 ? 'ganjil' : 'genap';
        $gradeRow = $db->table('grades')
            ->select('erapor_score')
            ->where('student_id', $studentId)
            ->where('subject_id', $subjectId)
            ->where('year_id', $yearId)
            ->where('semester', $semesterLabel)
            ->where('erapor_score IS NOT NULL', null, false)
            ->get()->getRowArray();
        $erapor = $gradeRow['erapor_score'] ?? null;

        // Nilai akhir = erapor jika sudah diisi, fallback ke rapor acuan sistem
        $nilaiAkhir = $erapor ?? $rapor;

        return [
            'formatif_avg' => $favg,
            'sumatif_avg'  => $savg,
            'final'        => $final,
            'rapor'        => $rapor,        // acuan sistem
            'erapor'       => $erapor,       // input guru (null jika belum diisi)
            'nilai_akhir'  => $nilaiAkhir,   // nilai final: erapor ?? rapor
        ];
    }

    public function searchStudent()
    {
        $q = $this->request->getGet('q');
        $db = db_connect();

        $students = $db->table('students')
            ->select('id, name, nis')
            ->groupStart()
            ->like('name', $q)
            ->orLike('nis', $q)
            ->groupEnd()
            ->limit(10)
            ->get()->getResultArray();

        return $this->response->setJSON($students);
    }

    /**
     * Rekap Nilai - semua siswa, semua mapel, satu halaman
     * Filter: class_id, semester, score_type, year_id
     */
    public function rekap()
    {
        $db = db_connect();
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;

        // Get filter params
        $classId   = $this->request->getGet('class_id');
        $semester  = $this->request->getGet('semester') ?: '1';
        $scoreType = $this->request->getGet('score_type') ?: 'rapor';
        $yearId    = $this->request->getGet('year_id');

        // Get academic years
        $years = $db->table('academic_years')->orderBy('year', 'DESC')->get()->getResultArray();

        // Default to active year
        if (!$yearId) {
            $activeYear = $db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
            $yearId = $activeYear['id'] ?? ($years[0]['id'] ?? null);
        }

        // Get classes based on role
        if (in_array($roleId, [1, 2])) {
            // Admin/Kepsek: all classes
            $classes = $db->table('classes')->orderBy('level', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
        } else {
            // Guru: hanya kelas yang diwalikan
            $teacherId = $user['related_id'] ?? 0;
            $classes = $db->table('classes')->where('teacher_id', $teacherId)->get()->getResultArray();

            if (empty($classes)) {
                // Guru bukan wali kelas — tidak punya akses ke rekap
                return redirect()->to('admin/grades')->with('error', 'Fitur Rekap Nilai Kelas hanya tersedia untuk wali kelas.');
            }

            // Pastikan class_id yang diminta adalah kelas yang diwalikan guru ini
            if ($classId && !in_array($classId, array_column($classes, 'id'))) {
                return redirect()->to('admin/grades/rekap')->with('error', 'Anda hanya dapat melihat rekap kelas yang Anda walikan.');
            }

            if (!$classId && !empty($classes)) {
                $classId = $classes[0]['id'];
            }
        }

        $rekapData = null;
        if ($classId && $yearId) {
            $rekapData = $this->_buildRekapData($classId, $semester, $scoreType, $yearId, $db);
        }

        return view('admin/grades/rekap', [
            'title'     => 'Rekap Nilai Kelas',
            'classes'   => $classes,
            'years'     => $years,
            'classId'   => $classId,
            'semester'  => $semester,
            'scoreType' => $scoreType,
            'yearId'    => $yearId,
            'rekap'     => $rekapData,
        ]);
    }

    /**
     * Cetak rekap nilai (print view)
     */
    public function rekapCetak()
    {
        $db = db_connect();
        $classId   = $this->request->getGet('class_id');
        $semester  = $this->request->getGet('semester') ?: '1';
        $scoreType = $this->request->getGet('score_type') ?: 'rapor';
        $yearId    = $this->request->getGet('year_id');

        if (!$classId || !$yearId) {
            return redirect()->to('admin/grades/rekap')->with('error', 'Parameter tidak lengkap.');
        }

        $rekapData = $this->_buildRekapData($classId, $semester, $scoreType, $yearId, $db);

        return view('admin/grades/rekap_cetak', [
            'title'     => 'Cetak Rekap Nilai',
            'semester'  => $semester,
            'scoreType' => $scoreType,
            'rekap'     => $rekapData,
        ]);
    }

    /**
     * Export Excel rekap nilai — versi lengkap dengan merge cells dan auto size
     */
    public function rekapExcel()
    {
        $db = db_connect();
        $classId   = $this->request->getGet('class_id');
        $semester  = $this->request->getGet('semester') ?: '1';
        $scoreType = $this->request->getGet('score_type') ?: 'rapor';
        $yearId    = $this->request->getGet('year_id');

        if (!$classId || !$yearId) {
            return redirect()->to('admin/grades/rekap')->with('error', 'Parameter tidak lengkap.');
        }

        $rekap = $this->_buildRekapData($classId, $semester, $scoreType, $yearId, $db);

        if (empty($rekap['students']) || empty($rekap['subjects'])) {
            return redirect()->to('admin/grades/rekap')->with('error', 'Tidak ada data untuk diekspor.');
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Nilai');

        // ── Header info ──────────────────────────────────────────
        $sheet->setCellValue('A1', 'REKAP NILAI KELAS');
        $sheet->setCellValue('A2', 'Kelas       : ' . ($rekap['class']['name'] ?? '-'));
        $sheet->setCellValue('A3', 'Tahun Ajaran: ' . ($rekap['year']['year'] ?? '-'));
        $sheet->setCellValue('A4', 'Semester    : ' . $semester);
        $sheet->setCellValue('A5', 'Jenis Nilai : ' . ($rekap['score_type_label'] ?? $scoreType));

        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        // ── Header tabel (baris 7) ────────────────────────────────
        $headerRow = 7;
        $col = 1;
        $sheet->setCellValue([$col++, $headerRow], 'No');
        $sheet->setCellValue([$col++, $headerRow], 'NIS');
        $sheet->setCellValue([$col++, $headerRow], 'Nama Siswa');

        $subjectStartCol = $col;
        foreach ($rekap['subjects'] as $subj) {
            $sheet->setCellValue([$col, $headerRow], $subj['code']);
            // Tooltip nama lengkap di baris 8
            $sheet->setCellValue([$col, $headerRow + 1], $subj['name']);
            $col++;
        }
        $sheet->setCellValue([$col++, $headerRow], 'Jumlah');
        $sheet->setCellValue([$col,   $headerRow], 'Rata-rata');
        $lastCol = $col;

        // Style header
        $headerRange = [1, $headerRow, $lastCol, $headerRow + 1];
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $headerRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . ($headerRow + 1))
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFD0E4FF');
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $headerRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . ($headerRow + 1))
            ->getFont()->setBold(true);
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $headerRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . ($headerRow + 1))
            ->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER)
            ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // Merge header kolom No, NIS, Nama (rowspan 2)
        foreach ([1, 2, 3] as $c) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->mergeCells($colLetter . $headerRow . ':' . $colLetter . ($headerRow + 1));
        }
        // Merge Jumlah dan Rata-rata
        foreach ([$lastCol - 1, $lastCol] as $c) {
            $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($c);
            $sheet->mergeCells($colLetter . $headerRow . ':' . $colLetter . ($headerRow + 1));
        }

        // ── Data siswa (mulai baris 9) ────────────────────────────
        $dataRow = $headerRow + 2;
        foreach ($rekap['students'] as $i => $student) {
            $col = 1;
            $sheet->setCellValue([$col++, $dataRow], $i + 1);
            $sheet->setCellValue([$col++, $dataRow], $student['nis']);
            $sheet->setCellValue([$col++, $dataRow], $student['name']);

            foreach ($rekap['subjects'] as $subj) {
                $val = $rekap['scores'][$student['id']][$subj['id']] ?? null;
                $sheet->setCellValue([$col++, $dataRow], $val !== null ? (float)$val : '');
            }

            $sheet->setCellValue([$col++, $dataRow], $student['row_total'] !== null ? (float)$student['row_total'] : '');
            $sheet->setCellValue([$col,   $dataRow], $student['row_avg']   !== null ? (float)$student['row_avg']   : '');
            $dataRow++;
        }

        // ── Baris rata-rata kelas ─────────────────────────────────
        $sheet->setCellValue([1, $dataRow], '');
        $sheet->setCellValue([2, $dataRow], '');
        $sheet->setCellValue([3, $dataRow], 'Rata-rata Kelas');
        $sheet->getStyle('C' . $dataRow)->getFont()->setBold(true);

        $col = 4;
        $grandSum = 0; $grandCount = 0;
        foreach ($rekap['subjects'] as $subj) {
            $avg = $rekap['col_avg'][$subj['id']] ?? null;
            $sheet->setCellValue([$col++, $dataRow], $avg !== null ? (float)$avg : '');
            if ($avg !== null) { $grandSum += $avg; $grandCount++; }
        }
        $sheet->setCellValue([$col++, $dataRow], $grandCount > 0 ? round($grandSum, 2) : '');
        $sheet->setCellValue([$col,   $dataRow], $grandCount > 0 ? round($grandSum / $grandCount, 2) : '');

        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $dataRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . $dataRow)
            ->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFF8E1');
        $sheet->getStyle(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $dataRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . $dataRow)
            ->getFont()->setBold(true);

        // ── Border seluruh tabel ──────────────────────────────────
        $tableRange = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(1) . $headerRow . ':' .
            \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($lastCol) . $dataRow;
        $sheet->getStyle($tableRange)->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        // ── Auto width kolom ──────────────────────────────────────
        foreach (range(1, $lastCol) as $c) {
            $sheet->getColumnDimensionByColumn($c)->setAutoSize(true);
        }
        // Kolom nama siswa sedikit lebih lebar
        $sheet->getColumnDimensionByColumn(3)->setWidth(30);

        // ── Download ──────────────────────────────────────────────
        $className  = preg_replace('/[^a-zA-Z0-9]/', '_', $rekap['class']['name'] ?? 'Kelas');
        $yearName   = preg_replace('/[^a-zA-Z0-9]/', '_', $rekap['year']['year'] ?? '');
        $filename   = "Rekap_{$scoreType}_{$className}_Smt{$semester}_{$yearName}.xlsx";

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        $writer->save('php://output');
        exit;
    }

    /**
     * Build rekap data: students × subjects matrix
     */
    private function _buildRekapData($classId, $semester, $scoreType, $yearId, $db)
    {
        // Get class info
        $class = $db->table('classes')->where('id', $classId)->get()->getRowArray();
        $year  = $db->table('academic_years')->where('id', $yearId)->get()->getRowArray();

        // Get students in class for this year
        $students = $db->table('student_records sr')
            ->select('s.id, s.nis, s.name, s.religion')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $yearId)
            ->where('sr.status', 'aktif')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        if (empty($students)) {
            return ['class' => $class, 'year' => $year, 'students' => [], 'subjects' => [], 'scores' => [], 'col_avg' => [], 'score_type_label' => ''];
        }

        // Get subjects taught in this class/year via teaching_assignments
        $subjects = $db->table('teaching_assignments ta')
            ->select('s.id, s.code, s.name, s.religion, s.sort_order')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.class_id', $classId)
            ->where('ta.academic_year_id', $yearId)
            ->where('s.is_active', 1)
            ->orderBy('s.sort_order', 'ASC')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Remove duplicate subjects
        $uniqueSubjects = [];
        $seenIds = [];
        foreach ($subjects as $subj) {
            if (!in_array($subj['id'], $seenIds)) {
                $uniqueSubjects[] = $subj;
                $seenIds[] = $subj['id'];
            }
        }
        $subjects = $uniqueSubjects;

        // Guard: jika tidak ada mata pelajaran, kembalikan data kosong
        // Ini mencegah SQL error "IN ()" ketika whereIn dipanggil dengan array kosong
        if (empty($subjects)) {
            return ['class' => $class, 'year' => $year, 'students' => $students, 'subjects' => [], 'scores' => [], 'col_avg' => [], 'score_type_label' => 'Belum ada mata pelajaran yang di-assign ke kelas ini.'];
        }

        // Get weights for report score calculation
        $fWeight = ($year['formatif_weight'] ?? 60) / 100;
        $sWeight = ($year['sumatif_weight'] ?? 40) / 100;

        // Build score matrix
        $scores = [];
        $studentIds = array_column($students, 'id');
        $subjectIds = array_column($subjects, 'id');

        // Pre-fetch all relevant scores in bulk
        // Formatif averages
        $formatifAvg = [];
        if (in_array($scoreType, ['formatif', 'rapor']) && !empty($studentIds) && !empty($subjectIds)) {
            $fRows = $db->table('material_scores ms')
                ->select('ms.student_id, atp.subject_id, AVG(ms.score) as avg_score')
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = ms.material_id')
                ->whereIn('ms.student_id', $studentIds)
                ->whereIn('atp.subject_id', $subjectIds)
                ->where('atp.class_id', $classId)
                ->where('atp.semester', $semester)
                ->groupBy('ms.student_id, atp.subject_id')
                ->get()->getResultArray();
            foreach ($fRows as $r) {
                $formatifAvg[$r['student_id']][$r['subject_id']] = round($r['avg_score'], 2);
            }
        }

        // Sumatif averages
        $sumatifAvg = [];
        if (in_array($scoreType, ['sumatif', 'rapor']) && !empty($studentIds) && !empty($subjectIds)) {
            $sRows = $db->table('summative_scores')
                ->select('student_id, subject_id, AVG(score) as avg_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semester)
                ->groupBy('student_id, subject_id')
                ->get()->getResultArray();
            foreach ($sRows as $r) {
                $sumatifAvg[$r['student_id']][$r['subject_id']] = round($r['avg_score'], 2);
            }
        }

        // Final exam scores (semester 2 only)
        $finalScores = [];
        if (($scoreType === 'final' || ($scoreType === 'rapor' && $semester == 2)) && !empty($studentIds) && !empty($subjectIds)) {
            $fExRows = $db->table('final_exam_scores')
                ->select('student_id, subject_id, score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->get()->getResultArray();
            foreach ($fExRows as $r) {
                $finalScores[$r['student_id']][$r['subject_id']] = $r['score'];
            }
        }

        // Rapor scores from grades table
        $raporScores = [];
        if ($scoreType === 'rapor' && !empty($studentIds) && !empty($subjectIds)) {
            $rRows = $db->table('grades')
                ->select('student_id, subject_id, report_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semester == 1 ? 'ganjil' : 'genap')
                ->get()->getResultArray();
            foreach ($rRows as $r) {
                $raporScores[$r['student_id']][$r['subject_id']] = $r['report_score'];
            }
        }

        // Erapor scores — nilai erapor yang diinput guru (prerogratif guru)
        $eraporScores = [];
        if ($scoreType === 'erapor' && !empty($studentIds) && !empty($subjectIds)) {
            $eRows = $db->table('grades')
                ->select('student_id, subject_id, erapor_score')
                ->whereIn('student_id', $studentIds)
                ->whereIn('subject_id', $subjectIds)
                ->where('year_id', $yearId)
                ->where('semester', $semester == 1 ? 'ganjil' : 'genap')
                ->where('erapor_score IS NOT NULL', null, false)
                ->get()->getResultArray();
            foreach ($eRows as $r) {
                $eraporScores[$r['student_id']][$r['subject_id']] = $r['erapor_score'];
            }
        }

        // Build matrix
        $colSums = array_fill_keys($subjectIds, 0);
        $colCounts = array_fill_keys($subjectIds, 0);

        foreach ($students as &$student) {
            $sid = $student['id'];
            $rowSum = 0;
            $rowCount = 0;

            foreach ($subjects as $subj) {
                $subjId = $subj['id'];

                // Filter by religion
                if (!empty($subj['religion']) && $subj['religion'] !== $student['religion']) {
                    $scores[$sid][$subjId] = null;
                    continue;
                }

                $val = null;
                switch ($scoreType) {
                    case 'formatif':
                        $val = $formatifAvg[$sid][$subjId] ?? null;
                        break;
                    case 'sumatif':
                        $val = $sumatifAvg[$sid][$subjId] ?? null;
                        break;
                    case 'final':
                        $val = $finalScores[$sid][$subjId] ?? null;
                        break;
                    case 'rapor':
                        // Use grades table if available, else calculate
                        if (isset($raporScores[$sid][$subjId])) {
                            $val = $raporScores[$sid][$subjId];
                        } else {
                            $f = $formatifAvg[$sid][$subjId] ?? 0;
                            $s = $sumatifAvg[$sid][$subjId] ?? 0;
                            if ($f > 0 || $s > 0) {
                                $val = round(($f * $fWeight) + ($s * $sWeight), 2);
                            }
                        }
                        break;
                    case 'erapor':
                        // Nilai erapor yang diinput guru — langsung dari tabel grades
                        $val = $eraporScores[$sid][$subjId] ?? null;
                        break;
                }

                $scores[$sid][$subjId] = $val;

                if ($val !== null) {
                    $rowSum += $val;
                    $rowCount++;
                    $colSums[$subjId] += $val;
                    $colCounts[$subjId]++;
                }
            }

            $student['row_total'] = $rowCount > 0 ? round($rowSum, 2) : null;
            $student['row_avg']   = $rowCount > 0 ? round($rowSum / $rowCount, 2) : null;
        }
        unset($student);

        // Column averages
        $colAvg = [];
        foreach ($subjectIds as $subjId) {
            $colAvg[$subjId] = $colCounts[$subjId] > 0 ? round($colSums[$subjId] / $colCounts[$subjId], 2) : null;
        }

        $scoreTypeLabels = [
            'formatif' => 'Formatif',
            'sumatif'  => 'Sumatif',
            'final'    => 'Final',
            'rapor'    => 'Nilai Rapor',
            'erapor'   => 'Nilai Erapor',
        ];

        return [
            'class'            => $class,
            'year'             => $year,
            'students'         => $students,
            'subjects'         => $subjects,
            'scores'           => $scores,
            'col_avg'          => $colAvg,
            'score_type_label' => $scoreTypeLabels[$scoreType] ?? $scoreType,
        ];
    }
}
