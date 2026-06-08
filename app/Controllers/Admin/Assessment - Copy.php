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
        $this->assignmentModel      = new TeachingAssignmentModel();
        $this->materialScoresModel  = new MaterialScoresModel();
        $this->summativeScoresModel = new SummativeScoresModel();
        $this->finalExamScoresModel = new FinalExamScoresModel();
        $this->academicYearModel    = new AcademicYearModel();
        $this->studentModel         = new StudentModel();
        $this->db                   = \Config\Database::connect();
    }

    /**
     * Halaman Index Assessment
     */
    public function index()
    {
        $user   = session()->get('user') ?? [];
        $roleId = $user['role_id'] ?? null;

        if ($roleId == 1) { // admin
            $teacherId = $this->request->getGet('teacher_id');
            $teachers  = $this->db->table('teachers')->get()->getResultArray();

            $query = $this->assignmentModel
                ->select('teaching_assignments.*, 
                          classes.name as class_name, 
                          subjects.name as subject_name, 
                          teachers.name as teacher_name')
                ->join('classes', 'classes.id = teaching_assignments.class_id')
                ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
                ->join('teachers', 'teachers.id = teaching_assignments.teacher_id');

            // filter guru
            if ($teacherId) {
                $query->where('teaching_assignments.teacher_id', $teacherId);
            }

            // filter tahun ajaran aktif
            $activeYear = $this->academicYearModel->getActiveYear();
            if ($activeYear) {
                $query->where('teaching_assignments.academic_year_id', $activeYear['id']);
            }

            $assignments = $query->get()->getResultArray();

            return view('admin/assessments/index', [
                'isAdmin'     => true,
                'teachers'    => $teachers,
                'assignments' => $assignments,
                'selected'    => $teacherId
            ]);
        } elseif ($roleId == 3) { // guru
            $teacherId = $user['related_id'];

            $assignments = $this->assignmentModel
                ->select('teaching_assignments.*, 
                          classes.name as class_name, 
                          subjects.name as subject_name')
                ->join('classes', 'classes.id = teaching_assignments.class_id')
                ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
                ->where('teaching_assignments.teacher_id', $teacherId)
                ->get()
                ->getResultArray();

            return view('admin/assessments/index', [
                'isAdmin'     => false,
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

        // data kelas
        $class = $this->db->table('classes')
            ->where('id', $classId)
            ->get()
            ->getRowArray();

        // data mata pelajaran
        $subject = $this->db->table('subjects')
            ->where('id', $subjectId)
            ->get()
            ->getRowArray();

        // materi
        $materials = $this->db->table('subject_materials sm')
            ->select('sm.id, sm.title, sm.semester,
                     GROUP_CONCAT(DISTINCT ms.type) as metode_terpakai,
                     COUNT(ms.id) as jumlah_nilai')
            ->join('material_scores ms', 'ms.material_id = sm.id', 'left')
            ->where('sm.subject_id', $subjectId)
            ->where('sm.year_id', $activeYear['id'])
            ->groupBy('sm.id')
            ->get()
            ->getResultArray();

        return view('admin/assessments/formatif_list', [
            'class'     => $class,
            'subject'   => $subject,
            'classId'   => $classId,
            'subjectId' => $subjectId,
            'materials' => $materials
        ]);
    }

    public function input($classId, $subjectId, $type)
    {
        helper('form');

        // ambil daftar siswa
        $students = $this->studentModel
            ->select('students.*')
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId)
            ->get()->getResultArray();

        // ambil materi
        $materials = $this->db->table('subject_materials')
            ->where('subject_id', $subjectId)
            ->get()->getResultArray();

        // cek materi & method dari query string
        $materialId = $this->request->getGet('material_id');
        $method     = $this->request->getGet('method');

        // ambil metode yang masih ada nilai untuk materi
        $materialMethodsUsed = [];
        if (!empty($materialId)) {
            $materialMethodsUsed = $this->db->table('material_scores')
                ->select('type')
                ->where('material_id', $materialId)
                ->groupBy('type')
                ->having('COUNT(student_id) >', 0) // hanya metode yg masih ada nilai siswa
                ->get()->getResultArray();

            $materialMethodsUsed = array_column($materialMethodsUsed, 'type');
        }

        // ambil siswa yang BELUM dinilai jika materi+metode dipilih
        if (!empty($materialId) && !empty($method)) {
            $students = $this->studentModel
                ->select('students.*')
                ->join('student_records sr', 'sr.student_id = students.id')
                ->where('sr.class_id', $classId)
                ->where("students.id NOT IN (
                    SELECT student_id FROM material_scores 
                    WHERE material_id = {$materialId} AND type = " . $this->db->escape($method) . "
                )")
                ->get()->getResultArray();
        }

        // Hitung jumlah siswa di kelas
        $totalStudents = $this->studentModel
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId)
            ->countAllResults();

        // Cek status metode per materi
        $methodStatus = [];
        if (!empty($materialId)) {
            $rows = $this->db->table('material_scores')
                ->select('type, COUNT(student_id) as total')
                ->where('material_id', $materialId)
                ->groupBy('type')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                $methodStatus[$r['type']] = ((int)$r['total'] >= $totalStudents)
                    ? 'full'   // semua siswa sudah dinilai
                    : 'partial'; // baru sebagian
            }
        }

        $redirect_url = site_url('admin/assessments');
        if ($type === 'formatif') {
            $redirect_url = site_url("admin/assessments/formatifList/{$classId}/{$subjectId}");
        }

        return view('admin/assessments/input_form', [
            'classId'             => $classId,
            'subjectId'           => $subjectId,
            'type'                => $type,
            'materials'           => $materials,
            'students'            => $students,
            'materialMethodsUsed' => $materialMethodsUsed,
            'materialId'          => $materialId,
            'method'              => $method,
            'methodStatus'        => $methodStatus,
            'redirect_url'        => $redirect_url,
        ]);
    }



    public function viewScores($type, $id, $p2 = null, $p3 = null)
    {
        // $type: 'formatif' | 'sumatif' | 'final'
        // $id: material_id (formatif) OR subject_id (sumatif/final)
        // $p2, $p3: optional parameters:
        //   - formatif: $p2 => method (e.g. 'tulis' | 'lisan' | 'all')
        //   - sumatif:  $p2 => semester, $p3 => method
        //   - final:    $p2 => semester

        $material = null;
        $subject  = null;
        $class_id = null;
        $types    = [];
        $selected_method = $p2 ?? null;
        $scores   = [];

        // DB shortcut
        $db = $this->db;

        if ($type === 'formatif') {
            $method = $p2 ?? 'all';

            // ambil materi + subject
            $material = $db->table('subject_materials sm')
                ->select('sm.*, subjects.name as subject, subjects.id as subject_id')
                ->join('subjects', 'subjects.id = sm.subject_id')
                ->where('sm.id', $id)
                ->get()
                ->getRowArray();

            if (! $material) {
                return redirect()->back()->with('error', 'Materi tidak ditemukan.');
            }

            // cari satu class_id terkait subject (dipakai untuk tombol kembali)
            $ta = $db->table('teaching_assignments')
                ->select('class_id')
                ->where('subject_id', $material['subject_id'])
                ->limit(1)
                ->get()
                ->getRowArray();
            $class_id = $ta['class_id'] ?? null;

            // jenis metode yang pernah digunakan untuk materi ini
            $rows = $db->table('material_scores')
                ->select('type')
                ->distinct()
                ->where('material_id', $id)
                ->get()
                ->getResultArray();
            $types = array_column($rows, 'type');

            // ambil scores (semua atau per method)
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

            // kirim ke view (include both $material and $subject)
            $subject = [
                'id'   => $material['subject_id'],
                'name' => $material['subject']
            ];

            return view('admin/assessments/view_scores', [
                'type'            => $type,
                'material'        => $material,
                'subject'         => $subject,
                'class_id'        => $class_id,
                'types'           => $types,
                'selected_method' => $selected_method,
                'scores'          => $scores,
            ]);
        }

        if ($type === 'sumatif') {
            // $id = subject_id, $p2 = semester, $p3 = method
            $semester = $p2 ?? null;
            $method   = $p3 ?? null;

            $subject = $db->table('subjects')->where('id', $id)->get()->getRowArray();
            if (! $subject) {
                return redirect()->back()->with('error', 'Mata pelajaran tidak ditemukan.');
            }

            // types available for this subject
            $rows = $db->table('summative_scores')
                ->select('type')
                ->distinct()
                ->where('subject_id', $id)
                ->get()
                ->getResultArray();
            $types = array_column($rows, 'type');

            $builder = $db->table('summative_scores ss')
                ->select('ss.*, students.name as student_name')
                ->join('students', 'students.id = ss.student_id')
                ->where('ss.subject_id', $id);

            if ($semester !== null) {
                $builder->where('ss.semester', $semester);
            }
            if ($method !== null) {
                $builder->where('ss.type', $method);
                $selected_method = $method;
            } else {
                $selected_method = 'all';
            }

            $scores = $builder->orderBy('ss.created_at', 'DESC')->get()->getResultArray();

            return view('admin/assessments/view_scores', [
                'type'            => $type,
                'subject'         => $subject,
                'semester'        => $semester,
                'types'           => $types,
                'selected_method' => $selected_method,
                'scores'          => $scores,
            ]);
        }

        if ($type === 'final') {
            // $id = subject_id, $p2 = semester
            $semester = $p2 ?? null;

            $subject = $db->table('subjects')->where('id', $id)->get()->getRowArray();
            if (! $subject) {
                return redirect()->back()->with('error', 'Mata pelajaran tidak ditemukan.');
            }

            $builder = $db->table('final_exam_scores fs')
                ->select('fs.*, students.name as student_name')
                ->join('students', 'students.id = fs.student_id')
                ->where('fs.subject_id', $id);

            if ($semester !== null) {
                $builder->where('fs.semester', $semester);
            }

            $scores = $builder->orderBy('fs.created_at', 'DESC')->get()->getResultArray();

            return view('admin/assessments/view_scores', [
                'type'     => $type,
                'subject'  => $subject,
                'semester' => $semester,
                'scores'   => $scores,
                'selected_method' => null,
                'types'    => [],
            ]);
        }

        return redirect()->back()->with('error', 'Tipe penilaian belum didukung.');
    }



    public function store()
    {
        $data   = $this->request->getPost();
        $type   = $data['type'] ?? null;
        $userId = session()->get('user')['id'] ?? null;

        if (empty($type)) {
            return redirect()->back()->with('error', 'Tipe penilaian tidak diketahui.');
        }

        $this->db->transStart();

        /** -----------------------------
         *  FORMATIVE
         * ----------------------------- */
        if ($type === 'formatif') {
            $model = new \App\Models\MaterialScoresModel();

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null) continue;

                $existing = $this->db->table('material_scores')
                    ->where('student_id', $studentId)
                    ->where('material_id', $data['material_id'])
                    ->where('type', $data['method'])
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id'  => $studentId,
                    'material_id' => $data['material_id'],
                    'type'        => $data['method'],
                    'score'       => (int)$score,
                    'created_by'  => $userId,
                ];

                if ($existing) {
                    $model->update($existing['id'], $insertData);
                } else {
                    $model->insert($insertData);
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal menyimpan nilai formatif.');
            }

            return redirect()->to(site_url("admin/assessments/viewScores/formatif/{$data['material_id']}/{$data['method']}"))
                 ->with('success', 'Nilai berhasil disimpan.');
        }

        /** -----------------------------
         *  SUMATIVE
         * ----------------------------- */
        if ($type === 'sumatif') {
            $model = new \App\Models\SummativeScoresModel();

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null) continue;

                $existing = $this->db->table('summative_scores')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $data['subject_id'])
                    ->where('semester', $data['semester'])
                    ->where('type', $data['method'])
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id' => $studentId,
                    'subject_id' => $data['subject_id'],
                    'year_id'    => $data['year_id'] ?? null,
                    'semester'   => $data['semester'],
                    'type'       => $data['method'],
                    'score'      => (int)$score,
                    'created_by' => $userId,
                ];

                if ($existing) {
                    $model->update($existing['id'], $insertData);
                } else {
                    $model->insert($insertData);
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal menyimpan nilai sumatif.');
            }

            return redirect()->to(site_url("admin/assessments/viewScores/sumatif/{$data['subject_id']}/{$data['semester']}/{$data['method']}"))
                 ->with('success', 'Nilai sumatif berhasil disimpan.');
        }

        /** -----------------------------
         *  FINAL EXAM
         * ----------------------------- */
        if ($type === 'final') {
            $model = new \App\Models\FinalExamScoresModel();

            foreach ($data['scores'] as $studentId => $score) {
                if ($score === '' || $score === null) continue;

                $existing = $this->db->table('final_exam_scores')
                    ->where('student_id', $studentId)
                    ->where('subject_id', $data['subject_id'])
                    ->where('semester', $data['semester'])
                    ->get()
                    ->getRowArray();

                $insertData = [
                    'student_id' => $studentId,
                    'subject_id' => $data['subject_id'],
                    'year_id'    => $data['year_id'] ?? null,
                    'semester'   => $data['semester'],
                    'score'      => (int)$score,
                    'created_by' => $userId,
                ];

                if ($existing) {
                    $model->update($existing['id'], $insertData);
                } else {
                    $model->insert($insertData);
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal menyimpan nilai ujian akhir.');
            }

            return redirect()->to(site_url("admin/assessments/viewScores/final/{$data['subject_id']}/{$data['semester']}"))
                 ->with('success', 'Nilai ujian akhir berhasil disimpan.');
        }

        $this->db->transComplete();
        return redirect()->to(site_url('admin/assessments'))
             ->with('success', 'Nilai berhasil disimpan.');
    }


    public function success($type, $id = null, $extra = null, $method = null)
    {
        // Perbaiki posisi parameter (jika method masuk ke $extra)
        if ($method === null && $extra !== null && $type === 'formatif') {
            $method = $extra;
            $extra  = null;
        }

        $info   = [];
        $scores = [];

        if ($type === 'formatif') {
            // Ambil data materi
            $material = $this->db->table('subject_materials sm')
                ->select('sm.*, subjects.name as subject')
                ->join('subjects', 'subjects.id = sm.subject_id')
                ->where('sm.id', $id)
                ->get()->getRowArray();

            $info = [
                'id'       => $material['id'],
                'subject'  => $material['subject'],
                'title'    => $material['title'],
                'semester' => $material['semester'],
                'method'   => $method,
            ];

            $scores = $this->db->table('material_scores ms')
                ->select('ms.*, students.name as student_name, sm.id as material_id')
                ->join('students', 'students.id = ms.student_id')
                ->join('subject_materials sm', 'sm.id = ms.material_id')
                ->where('ms.material_id', $id)
                ->where('ms.type', $method)
                ->get()->getResultArray();

        } elseif ($type === 'sumatif') {
            $subject = $this->db->table('subjects')->where('id', $id)->get()->getRowArray();

            $info = [
                'id'       => $subject['id'],
                'subject'  => $subject['name'],
                'title'    => 'Sumatif',
                'semester' => $extra,
                'method'   => $method,
            ];

            $scores = $this->db->table('summative_scores ss')
                ->select('ss.*, students.name as student_name')
                ->join('students', 'students.id = ss.student_id')
                ->where('ss.subject_id', $id)
                ->where('ss.semester', $extra)
                ->where('ss.type', $method)
                ->get()->getResultArray();

        } elseif ($type === 'final') {
            $subject = $this->db->table('subjects')->where('id', $id)->get()->getRowArray();

            $info = [
                'id'       => $subject['id'],
                'subject'  => $subject['name'],
                'title'    => 'Ujian Akhir',
                'semester' => $extra,
                'method'   => '-',
            ];

            $scores = $this->db->table('final_exam_scores fs')
                ->select('fs.*, students.name as student_name')
                ->join('students', 'students.id = fs.student_id')
                ->where('fs.subject_id', $id)
                ->where('fs.semester', $extra)
                ->get()->getResultArray();
        }

        return view('admin/assessments/success', [
            'info'   => $info,
            'scores' => $scores,
            'type'   => $type,
        ]);
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
            $material = $this->db->table('subject_materials')
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
            'score'        => $score,
            'student'      => $student,
            'type'         => $type,
            'material'     => $material,
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
            'score'      => $score,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to($validRedirect)->with('success', 'Nilai berhasil diperbarui.');
    }

    public function deleteBatch($type, $id, $extra = null, $method = null)
    {
        if ($type === 'formatif') {
            $this->db->table('material_scores')
                ->where('material_id', $id)
                ->where('type', $extra)
                ->delete();

        } elseif ($type === 'sumatif') {
            $this->db->table('summative_scores')
                ->where('subject_id', $id)
                ->where('semester', $extra)
                ->where('type', $method)
                ->delete();

        } elseif ($type === 'final') {
            $this->db->table('final_exam_scores')
                ->where('subject_id', $id)
                ->where('semester', $extra)
                ->delete();

        } else {
            return redirect()->back()->with('error', 'Tipe penilaian tidak dikenali.');
        }

        return redirect()->back()->with('success', 'Semua nilai berhasil dihapus.');
    }

    public function deleteOne($id, $type)
    {
        $model = ($type === 'formatif') ? new MaterialScoresModel()
                : (($type === 'sumatif') ? new SummativeScoresModel()
                : new FinalExamScoresModel());

        $score = $model->find($id);
        if (! $score) {
            return redirect()->back()->with('error', 'Data nilai tidak ditemukan.');
        }

        $model->delete($id);

        return redirect()->back()->with('success', 'Nilai berhasil dihapus.');
    }





}
