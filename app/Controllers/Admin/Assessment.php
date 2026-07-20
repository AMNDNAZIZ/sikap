<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeachingAssignmentModel;
use App\Models\MaterialScoresModel;
use App\Models\SummativeScoresModel;
use App\Models\FinalExamScoresModel;
use App\Models\AcademicYearModel;
use App\Models\StudentModel;

class Assessment extends BaseController
{
    protected $assignmentModel;
    protected $materialScoresModel;
    protected $summativeScoresModel;
    protected $finalExamScoresModel;
    protected $academicYearModel;
    protected $studentModel;
    protected $db;

    public function __construct()
    {
        $this->assignmentModel = new TeachingAssignmentModel();
        $this->materialScoresModel = new MaterialScoresModel();
        $this->summativeScoresModel = new SummativeScoresModel();
        $this->finalExamScoresModel = new FinalExamScoresModel();
        $this->academicYearModel = new AcademicYearModel();
        $this->studentModel = new StudentModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Halaman Index Assessment
     */
    public function index()
    {
        $user = session()->get('user') ?? [];
        $roleId = $user['role_id'] ?? null;

        if ($roleId == 1 || $roleId == 2) { // admin atau kepala sekolah
            $teacherId = $this->request->getGet('teacher_id');
            $teachers = $this->db->table('teachers')->get()->getResultArray();

            $query = $this->assignmentModel
                ->select('teaching_assignments.*, 
                          classes.name as class_name, classes.level as class_level,
                          subjects.name as subject_name, 
                          teachers.name as teacher_name')
                ->join('classes', 'classes.id = teaching_assignments.class_id')
                ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
                ->join('teachers', 'teachers.id = teaching_assignments.teacher_id');

            if ($teacherId) {
                $query->where('teaching_assignments.teacher_id', $teacherId);
            }

            $activeYear = $this->academicYearModel->getActiveYear();
            if ($activeYear) {
                $query->where('teaching_assignments.academic_year_id', $activeYear['id']);
            }

            $assignments = $query->get()->getResultArray();

            return view('admin/assessments/index', [
                'isAdmin' => true,
                'teachers' => $teachers,
                'assignments' => $assignments,
                'selected' => $teacherId
            ]);
        } elseif ($roleId == 3) { // guru
            $teacherId = $user['related_id'];

            $activeYear = $this->academicYearModel->getActiveYear();

            $query = $this->assignmentModel
                ->select('teaching_assignments.*, 
                          classes.name as class_name, classes.level as class_level,
                          subjects.name as subject_name')
                ->join('classes', 'classes.id = teaching_assignments.class_id')
                ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
                ->where('teaching_assignments.teacher_id', $teacherId);

            if ($activeYear) {
                $query->where('teaching_assignments.academic_year_id', $activeYear['id']);
            }

            $assignments = $query->get()->getResultArray();

            return view('admin/assessments/index', [
                'isAdmin' => false,
                'assignments' => $assignments,
            ]);
        }

        return redirect()->to('/dashboard')->with('error', 'Tidak ada akses.');
    }


    /**
     * Halaman daftar materi untuk penilaian formatif
     */
    public function formatifList($classId, $subjectId)
    {
        $activeYear = $this->academicYearModel->getActiveYear();

        if (!$activeYear) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        $materials = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi as title, atp.semester,
                     GROUP_CONCAT(DISTINCT ms.type) as metode_terpakai,
                     COUNT(ms.id) as jumlah_nilai')
            ->join('material_scores ms', 'ms.material_id = atp.id', 'left')
            ->where('atp.subject_id', $subjectId)
            ->where('atp.class_id', $classId)
            ->groupBy('atp.id')
            ->get()
            ->getResultArray();

        return view('admin/assessments/formatif_list', [
            'class' => $class,
            'subject' => $subject,
            'classId' => $classId,
            'subjectId' => $subjectId,
            'materials' => $materials
        ]);
    }

    public function input($classId, $subjectId, $type)
    {
        helper('form');

        // Validasi
        if (!is_numeric($classId) || !is_numeric($subjectId) || empty($type)) {
            return redirect()->to('admin/assessments')->with('error', 'Parameter tidak valid.');
        }

        // Tahun ajaran aktif
        $activeYear = $this->academicYearModel->getActiveYear();
        if (!$activeYear) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        // Default: semua siswa kelas
        $studentsQuery = $this->studentModel
            ->select('students.*')
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId);

        // --- LOGIC AGAMA ---
        $subjectInfo = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        if ($subjectInfo && stripos($subjectInfo['name'], 'Agama') !== false) {
            // Deteksi agama dari nama mapel
            $religionFilter = null;
            if (stripos($subjectInfo['name'], 'Islam') !== false)
                $religionFilter = 'Islam';
            elseif (stripos($subjectInfo['name'], 'Kristen') !== false)
                $religionFilter = 'Kristen';
            elseif (stripos($subjectInfo['name'], 'Katholik') !== false)
                $religionFilter = 'Katholik';
            elseif (stripos($subjectInfo['name'], 'Hindu') !== false)
                $religionFilter = 'Hindu';
            elseif (stripos($subjectInfo['name'], 'Budha') !== false)
                $religionFilter = 'Budha';
            elseif (stripos($subjectInfo['name'], 'Khonghucu') !== false)
                $religionFilter = 'Khonghucu';

            if ($religionFilter) {
                $studentsQuery->where('students.religion', $religionFilter);
            }
        }
        // -------------------

        $students = $studentsQuery->findAll();

        $materials = [];
        $materialId = $this->request->getGet('material_id');
        $method = $this->request->getGet('method');
        $methodStatus = [];

        if ($type === 'formatif') {
            // Ambil materi dari ATP
            $materials = $this->db->table('alur_tujuan_pembelajaran')
                ->select('id, lingkup_materi as title')
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->orderBy('urutan', 'ASC')
                ->get()
                ->getResultArray();

            if (!empty($materialId) && !empty($method)) {
                // Ambil siswa yg BELUM punya nilai valid (score IS NULL → tetap ditampilkan)
                $students = $this->studentModel
                    ->select('students.*')
                    ->join('student_records sr', 'sr.student_id = students.id')
                    ->where('sr.class_id', $classId)
                    ->where("students.id NOT IN (
                        SELECT student_id FROM material_scores 
                        WHERE material_id = " . (int) $materialId . " 
                        AND type = " . $this->db->escape($method) . " 
                        AND score IS NOT NULL
                    )")
                    ->findAll();

                // Status metode
                $rows = $this->db->table('material_scores')
                    ->select('type, COUNT(student_id) as total')
                    ->where('material_id', $materialId)
                    ->groupBy('type')
                    ->get()
                    ->getResultArray();

                $totalStudents = count($students);
                foreach ($rows as $r) {
                    $methodStatus[$r['type']] = ((int) $r['total'] >= $totalStudents) ? 'full' : 'partial';
                }
            }

        } elseif ($type === 'sumatif') {
            $semester = $this->request->getGet('semester');
            $method = $this->request->getGet('method');

            if (!empty($semester) && !empty($method)) {
                $students = $this->studentModel
                    ->select('students.*')
                    ->join('student_records sr', 'sr.student_id = students.id')
                    ->where('sr.class_id', $classId)
                    ->where("students.id NOT IN (
                        SELECT student_id FROM summative_scores 
                        WHERE subject_id = {$subjectId} 
                          AND semester = " . $this->db->escape($semester) . " 
                          AND type = " . $this->db->escape($method) . "
                          AND year_id = " . (int) $activeYear['id'] . "
                          AND score IS NOT NULL
                    )")
                    ->findAll();
            }

        } elseif ($type === 'final') {
            $semester = $this->request->getGet('semester');

            if (!empty($semester)) {
                $students = $this->studentModel
                    ->select('students.*')
                    ->join('student_records sr', 'sr.student_id = students.id')
                    ->where('sr.class_id', $classId)
                    ->where("students.id NOT IN (
                        SELECT student_id FROM final_exam_scores 
                        WHERE subject_id = {$subjectId} 
                          AND semester = " . $this->db->escape($semester) . "
                          AND year_id = " . (int) $activeYear['id'] . "
                          AND score IS NOT NULL
                    )")
                    ->findAll();
            }
        }

        return view('admin/assessments/input_form', [
            'classId' => $classId,
            'subjectId' => $subjectId,
            'type' => $type,
            'materials' => $materials,
            'students' => $students,
            'materialId' => $materialId,
            'method' => $method,
            'methodStatus' => $methodStatus,
            'activeYear' => $activeYear,
            'semester' => $semester ?? null,
            'redirect_url' => site_url("admin/assessments/{$type}List/{$classId}/{$subjectId}"),
        ]);
    }



    public function viewScores($type, $id, $p2 = null, $p3 = null)
    {
        // Validasi type
        if (!in_array($type, ['formatif', 'sumatif', 'final'])) {
            return redirect()->back()->with('error', 'Tipe penilaian tidak valid.');
        }

        $material = null;
        $subject = null;
        $class_id = null;
        $types = [];
        $selected_method = $p2 ?? null;
        $scores = [];
        $activeYear = null;  // Init
        $semester = null;

        // Ambil active year (KRITIS untuk sumatif/final)
        $activeYear = $this->academicYearModel->getActiveYear();
        if (!$activeYear) {
            // Fallback: Ambil terbaru
            $activeYear = $this->academicYearModel->orderBy('id', 'DESC')->first();
            if ($activeYear) {
                log_message('warning', 'No active year, using latest: ' . $activeYear['id']);
                // Optional: Set as active
                $this->academicYearModel->update($activeYear['id'], ['status' => 'active']);
            } else {
                log_message('error', 'No academic year available in viewScores.');
                if (in_array($type, ['sumatif', 'final'])) {
                    return redirect()->to('admin/academic-years/create')->with('error', 'Belum ada tahun ajaran. Silakan buat terlebih dahulu.');
                }
            }
        }

        log_message('debug', 'Active Year in ViewScores: ' . json_encode($activeYear));

        // DB shortcut
        $db = $this->db;

        if ($type === 'formatif') {
            // ... kode formatif existing (tidak berubah, no year needed) ...
            $method = $p2 ?? 'all';

            $material = $db->table('alur_tujuan_pembelajaran atp')
                ->select('atp.id, atp.lingkup_materi as title, atp.class_id, atp.semester, subjects.name as subject, subjects.id as subject_id')
                ->join('subjects', 'subjects.id = atp.subject_id')
                ->where('atp.id', $id)
                ->get()
                ->getRowArray();

            if (!$material) {
                return redirect()->back()->with('error', 'Lingkup Materi (ATP) tidak ditemukan.');
            }

            $class_id = $material['class_id'] ?? null;

            $rows = $db->table('material_scores')
                ->select('type')
                ->distinct()
                ->where('material_id', $id)
                ->get()
                ->getResultArray();
            $types = array_column($rows, 'type');

            $builder = $db->table('material_scores ms')
                ->select('ms.*, students.name as student_name')
                ->join('students', 'students.id = ms.student_id')
                ->where('ms.material_id', $id);

            if ($method && $method !== 'all') {
                $builder->where('ms.type', $method);
                $selected_method = $method;
            } else {
                $selected_method = 'all';
            }

            $scores = $builder->orderBy('ms.type', 'ASC')->get()->getResultArray();

            log_message('debug', 'Formatif Scores Count: ' . count($scores));

            $subject = [
                'id' => $material['subject_id'],
                'name' => $material['subject']
            ];

            return view('admin/assessments/view_scores', [
                'type' => $type,
                'material' => $material,
                'subject' => $subject,
                'class_id' => $class_id,
                'types' => $types,
                'selected_method' => $selected_method,
                'scores' => $scores,
                'activeYear' => $activeYear,  // Pass ke view (meski formatif no need)
                'semester' => $semester,
            ]);
        }

        if ($type === 'sumatif') {
            $semester = $p2 ?? null;
            $method = $p3 ?? null;

            $subject = $db->table('subjects')->where('id', $id)->get()->getRowArray();

            $ta = $db->table('teaching_assignments')
                ->select('class_id')
                ->where('subject_id', $id)
                ->limit(1)
                ->get()
                ->getRowArray();
            $class_id = $ta['class_id'] ?? null;

            $typesQuery = $db->table('summative_scores')
                ->select('type')
                ->distinct()
                ->where('subject_id', $id)
                ->where('year_id', $activeYear['id'] ?? 0);

            if ($semester !== null) {
                $typesQuery->where('semester', $semester);
            }

            $types = array_column($typesQuery->get()->getResultArray(), 'type');

            $builder = $db->table('summative_scores ss')
                ->select('ss.id, ss.student_id, ss.subject_id, ss.year_id, ss.semester, ss.type, ss.score, students.name as student_name')
                ->join('students', 'students.id = ss.student_id', 'left')
                ->where('ss.subject_id', $id)
                ->where('ss.year_id', $activeYear['id']);

            if ($semester !== null) {
                $builder->where('ss.semester', $semester);
            }

            if (!empty($method) && $method !== 'all') {
                $builder->where('ss.type', $method);
                $selected_method = $method;
            } else {
                $selected_method = 'all';
            }

            $scores = $builder->orderBy('students.name', 'ASC')->get()->getResultArray();

            log_message('debug', 'Sumatif Scores Data: ' . json_encode($scores));
            log_message('debug', 'Active Year: ' . json_encode($activeYear));
            log_message('debug', 'Semester: ' . $semester);
            log_message('debug', 'Method: ' . $method);
            log_message('debug', 'Scores Found: ' . count($scores));
            log_message('debug', 'Scores Data: ' . json_encode($scores));

            return view('admin/assessments/view_scores', [
                'type' => $type,
                'subject' => $subject,
                'class_id' => $class_id,
                'semester' => $semester,
                'types' => $types,
                'selected_method' => $selected_method,
                'scores' => $scores,
                'activeYear' => $activeYear,
            ]);
        }


        if ($type === 'final') {
            $subject = $db->table('subjects')->where('id', $id)->get()->getRowArray();
            if (!$subject) {
                return redirect()->back()->with('error', 'Mata pelajaran tidak ditemukan.');
            }

            // Cari class_id berdasarkan teaching_assignments
            $assignment = $db->table('teaching_assignments ta')
                ->select('ta.class_id')
                ->where('ta.subject_id', $id)
                ->where('ta.academic_year_id', $activeYear['id'])
                ->get()
                ->getRowArray();

            $classId = $assignment['class_id'] ?? null;

            $scores = $db->table('final_exam_scores fs')
                ->select('fs.id, fs.student_id, fs.subject_id, fs.year_id, fs.score, fs.created_at, fs.updated_at, students.name as student_name')
                ->join('students', 'students.id = fs.student_id', 'left')
                ->where('fs.subject_id', $id)
                ->where('fs.year_id', $activeYear['id'])
                ->orderBy('students.name', 'ASC')
                ->get()
                ->getResultArray();

            return view('admin/assessments/view_scores', [
                'type' => $type,
                'subject' => $subject,
                'scores' => $scores,
                'selected_method' => null,
                'types' => [],
                'activeYear' => $activeYear,
                'class_id' => $classId, // kirim class_id yang valid
            ]);
        }





        return redirect()->back()->with('error', 'Tipe penilaian belum didukung.');
    }

    public function store()
    {
        $data = $this->request->getPost();
        $type = $data['type'] ?? null;
        $userId = session()->get('user')['id'] ?? null;

        if (empty($type)) {
            return redirect()->back()->with('error', 'Tipe penilaian tidak diketahui.');
        }

        $this->db->transStart();

        // =================== FORMATIF ===================
        if ($type === 'formatif') {
            $model = new MaterialScoresModel();

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null)
                    continue;

                $existing = $this->db->table('material_scores')
                    ->where('student_id', $studentId)
                    ->where('material_id', $data['material_id'])
                    ->where('type', $data['method'])
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id' => $studentId,
                    'material_id' => $data['material_id'],
                    'type' => $data['method'],
                    'score' => (int) $score,
                    'created_by' => $userId,
                ];

                if ($existing) {
                    $model->update($existing['id'], $insertData);
                } else {
                    $model->insert($insertData);
                }
            }

            $redirect = site_url("admin/assessments/viewScores/formatif/{$data['material_id']}/{$data['method']}");
        }

        // =================== SUMATIF ===================
        elseif ($type === 'sumatif') {
            $semester = (int) ($data['semester'] ?? 0);
            $method = $data['method'] ?? null;
            $yearId = (int) ($data['year_id'] ?? 0);

            if (!$semester || !$method || !$yearId) {
                return redirect()->back()->with('error', 'Semester, metode, atau tahun ajaran tidak valid.');
            }

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null)
                    continue;

                $existing = $this->db->table('summative_scores')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $data['subject_id'])
                    ->where('semester', $semester)
                    ->where('year_id', $yearId)
                    ->where('type', $method)
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id' => $studentId,
                    'subject_id' => $data['subject_id'],
                    'semester' => $semester,
                    'year_id' => $yearId,
                    'type' => $method,
                    'score' => (int) $score,
                ];

                if ($existing) {
                    $this->db->table('summative_scores')->update($insertData, ['id' => $existing['id']]);
                } else {
                    $this->db->table('summative_scores')->insert($insertData);
                }
            }

            $redirect = site_url("admin/assessments/viewScores/sumatif/{$data['subject_id']}/{$semester}/{$method}");
        }

        // =================== FINAL ===================
        elseif ($type === 'final') {
            $yearId = (int) ($data['year_id'] ?? 0);

            if (!$yearId) {
                return redirect()->back()->with('error', 'Tahun ajaran tidak valid.');
            }

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null)
                    continue;

                $existing = $this->db->table('final_exam_scores')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $data['subject_id'])
                    ->where('year_id', $yearId)
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id' => $studentId,
                    'subject_id' => $data['subject_id'],
                    'year_id' => $yearId,
                    'score' => (int) $score,
                ];

                if ($existing) {
                    $this->db->table('final_exam_scores')
                        ->update($insertData, ['id' => $existing['id']]);
                } else {
                    $this->db->table('final_exam_scores')
                        ->insert($insertData);
                }
            }

            $redirect = site_url("admin/assessments/viewScores/final/{$data['subject_id']}");
        } else {
            return redirect()->back()->with('error', 'Tipe penilaian tidak didukung.');
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Terjadi kesalahan saat menyimpan nilai.');
        }

        return redirect()->to($redirect)->with('success', 'Nilai berhasil disimpan.');
    }



    /**
     * Helper method untuk build redirect URL (opsional, bisa inline jika tidak mau)
     */
    private function buildRedirectUrl($type, $data)
    {
        switch ($type) {
            case 'formatif':
                return site_url("admin/assessments/viewScores/formatif/{$data['material_id']}/{$data['method']}");
            case 'sumatif':
                return site_url("admin/assessments/viewScores/sumatif/{$data['subject_id']}/{$data['semester']}/{$data['method']}");
            case 'final':
                return site_url("admin/assessments/viewScores/final/{$data['subject_id']}/{$data['semester']}");
            default:
                return site_url('admin/assessments');
        }
    }


    public function edit($id, $type)
    {
        // Tentukan model sesuai tipe
        if ($type === 'formatif') {
            $model = new MaterialScoresModel();
        } elseif ($type === 'sumatif') {
            $model = new SummativeScoresModel();
        } elseif ($type === 'final') {
            $model = new FinalExamScoresModel();
        } else {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Tipe penilaian tidak dikenali");
        }

        // Ambil score
        $score = $model->find($id);
        if (!$score) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Data nilai tidak ditemukan");
        }

        // Ambil data siswa
        $student = $this->studentModel->find($score['student_id']);

        // Ambil materi/mapel sesuai tipe
        if ($type === 'formatif') {
            $material = $this->db->table('alur_tujuan_pembelajaran')
                ->select('id, lingkup_materi as title')
                ->where('id', $score['material_id'])
                ->get()->getRowArray();
        } else {
            // Untuk sumatif & final → pakai data mapel
            $material = $this->db->table('subjects')
                ->where('id', $score['subject_id'])
                ->get()->getRowArray();
            $material['title'] = ($type === 'sumatif') ? 'Sumatif' : 'Ujian Akhir';
        }

        // Ambil redirect_url dari query (konsisten)
        $redirectUrl = $this->request->getGet('redirect_url');
        if (empty($redirectUrl)) {
            // fallback: kembalikan ke daftar penilaian jika kosong
            if ($type === 'formatif') {
                $redirectUrl = site_url("admin/assessments/viewScores/formatif/{$score['material_id']}/{$score['type']}");
            } elseif ($type === 'sumatif') {
                $redirectUrl = site_url("admin/assessments/viewScores/sumatif/{$score['subject_id']}/{$score['semester']}/{$score['type']}");
            } else {
                $redirectUrl = site_url("admin/assessments/viewScores/final/{$score['subject_id']}/{$score['semester']}");
            }
        }

        return view('admin/assessments/edit_form', [
            'score' => $score,
            'student' => $student,
            'type' => $type,
            'material' => $material,
            'redirect_url' => $redirectUrl
        ]);
    }


    public function update($id, $type)
    {
        $post = $this->request->getPost();
        $redirectUrlRaw = $post['redirect_url'] ?? '';

        // decode jika dikirim urlencode
        $redirectUrl = $redirectUrlRaw ? urldecode($redirectUrlRaw) : '';

        // Sanitasi: terima hanya URL internal (site_url prefix) atau path yang diawali '/'
        $siteBase = rtrim(site_url(), '/'); // e.g. https://example.com
        $validRedirect = '';

        if (!empty($redirectUrl)) {
            // jika full URL dan berawalan site base -> valid
            if (stripos($redirectUrl, $siteBase) === 0) {
                $validRedirect = $redirectUrl;
            } elseif (strpos($redirectUrl, '/') === 0) {
                // jika path relatif ("/admin/assessments/..")
                $validRedirect = $siteBase . $redirectUrl;
            } else {
                // jika redirectUrl nampak bukan internal, ignore
                $validRedirect = '';
            }
        }

        // fallback jika tidak valid
        if (empty($validRedirect)) {
            // jika ingin kembali ke halaman viewScores default, coba bangun dari data post
            if ($type === 'formatif' && !empty($post['material_id']) && !empty($post['method'])) {
                $validRedirect = site_url("admin/assessments/viewScores/formatif/{$post['material_id']}/{$post['method']}");
            } else {
                // default fallback ke daftar penilaian
                $validRedirect = site_url('admin/assessments');
            }
        }

        // Pilih model sesuai tipe
        if ($type === 'formatif') {
            $model = new MaterialScoresModel();
        } elseif ($type === 'sumatif') {
            $model = new SummativeScoresModel();
        } elseif ($type === 'final') {
            $model = new FinalExamScoresModel();
        } else {
            return redirect()->to($validRedirect)->with('error', 'Tipe penilaian tidak valid.');
        }

        // Update record (pastikan field 'score' ada di form)
        $score = (int) ($post['score'] ?? 0);
        $model->update($id, [
            'score' => $score,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to($validRedirect)->with('success', 'Nilai berhasil diperbarui.');
    }

    public function deleteBatch($type, $id, $p2 = null, $p3 = null)
    {
        $year = $this->academicYearModel->getActiveYear();

        if ($type === 'formatif') {
            $materialId = $id;
            $method = $p2;

            $this->db->table('material_scores')
                ->where('material_id', $materialId)
                ->when($method !== 'all', function ($builder) use ($method) {
                    return $builder->where('type', $method);
                })
                ->delete();

            return redirect()->to("admin/assessments/viewScores/formatif/{$materialId}/{$method}")
                ->with('success', 'Semua nilai formatif berhasil dihapus.');

        } elseif ($type === 'sumatif') {
            $subjectId = $id;
            $semester = $p2;
            $method = $p3;

            $this->db->table('summative_scores')
                ->where('subject_id', $subjectId)
                ->where('year_id', $year['id'] ?? 0)
                ->where('semester', $semester)
                ->where('type', $method)
                ->delete();

            return redirect()->to("admin/assessments/viewScores/sumatif/{$subjectId}/{$semester}/{$method}")
                ->with('success', 'Semua nilai sumatif berhasil dihapus.');

        } elseif ($type === 'final') {
            $subjectId = $id;
            $semester = $p2;

            $this->db->table('final_exam_scores')
                ->where('subject_id', $subjectId)
                ->where('year_id', $year['id'] ?? 0)
                ->where('semester', $semester)
                ->delete();

            return redirect()->to("admin/assessments/viewScores/final/{$subjectId}/{$semester}")
                ->with('success', 'Semua nilai ujian akhir berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Jenis penilaian tidak valid.');
    }


    public function deleteOne($id, $type)
    {
        if ($type === 'formatif') {
            // Soft delete → kosongkan score saja
            $this->db->table('material_scores')
                ->where('id', $id)
                ->update(['score' => null]);

        } elseif ($type === 'sumatif') {
            $this->db->table('summative_scores')
                ->where('id', $id)
                ->update(['score' => null]);

        } elseif ($type === 'final') {
            $this->db->table('final_exam_scores')
                ->where('id', $id)
                ->update(['score' => null]);
        } else {
            return redirect()->back()->with('error', 'Jenis penilaian tidak valid.');
        }

        return redirect()->back()->with('success', 'Nilai siswa berhasil dihapus.');
    }


    public function sumatifList($classId, $subjectId)
    {
        // Pastikan tahun ajaran aktif
        $activeYear = $this->academicYearModel->getActiveYear();
        if (!$activeYear) {
            $activeYear = $this->academicYearModel->orderBy('id', 'DESC')->first();
            if ($activeYear) {
                $this->academicYearModel->update($activeYear['id'], ['is_active' => 1]);
            } else {
                return redirect()->to('admin/academic-years/create')
                    ->with('error', 'Belum ada tahun ajaran. Silakan buat terlebih dahulu.');
            }
        }

        // Ambil info kelas & mapel
        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        if (!$class || !$subject) {
            return redirect()->to('admin/assessments')->with('error', 'Kelas atau Mata Pelajaran tidak ditemukan.');
        }

        // Hitung status nilai per semester dan metode
        $status = [];
        foreach ([1, 2] as $sem) {
            foreach (['tulis', 'penugasan'] as $method) {
                $jumlah = $this->db->table('summative_scores')
                    ->where('subject_id', $subjectId)
                    ->where('year_id', $activeYear['id'])
                    ->where('semester', $sem)
                    ->where('type', $method)
                    ->countAllResults();

                $status[$sem][$method] = $jumlah;
            }
        }

        return view('admin/assessments/sumatif_list', [
            'class' => $class,
            'subject' => $subject,
            'status' => $status,
            'classId' => $classId,
            'subjectId' => $subjectId,
            'activeYear' => $activeYear,
        ]);
    }

    public function finalList($classId, $subjectId)
    {
        $activeYear = $this->academicYearModel->getActiveYear();

        if (!$activeYear) {
            return redirect()->to('admin/academic-years')->with('error', 'Belum ada tahun ajaran aktif.');
        }

        // Ambil data kelas
        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        if (!$class) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        // Ambil level tertinggi dari tabel classes
        $maxLevel = $this->db->table('classes')->selectMax('level')->get()->getRowArray();
        $finalLevel = $maxLevel ? ($maxLevel['level'] ?? null) : null;

        // Validasi: hanya kelas dengan level tertinggi
        if ($class['level'] != $finalLevel) {
            return redirect()->to('admin/assessments')
                ->with('error', 'Penilaian final hanya untuk kelas akhir (level ' . $finalLevel . ').');
        }

        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        $info = $this->db->table('final_exam_scores fs')
            ->select('COUNT(fs.id) as jumlah, COUNT(DISTINCT fs.student_id) as siswa_terisi')
            ->where('fs.subject_id', $subjectId)
            ->where('fs.year_id', $activeYear['id'])
            ->get()
            ->getRowArray();

        return view('admin/assessments/final_list', [
            'class' => $class,
            'subject' => $subject,
            'classId' => $classId,
            'subjectId' => $subjectId,
            'status' => $info,
        ]);
    }





}
