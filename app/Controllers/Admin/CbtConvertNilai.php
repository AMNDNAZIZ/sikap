<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\CbtSessionModel;
use App\Models\CbtTestStatusModel;
use App\Models\StudentModel;
use App\Models\MaterialScoresModel;
use App\Models\SummativeScoresModel;
use App\Models\FinalExamScoresModel;
use App\Models\AcademicYearModel;

class CbtConvertNilai extends BaseController
{
    protected $sessionModel;
    protected $testModel;
    protected $studentModel;
    protected $db;

    public function __construct()
    {
        $this->sessionModel = new CbtSessionModel();
        $this->testModel = new CbtTestStatusModel();
        $this->studentModel = new StudentModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * 1. Halaman pemilihan Bank Soal & Kelas
     */
    public function index()
    {
        helper('cbt');
        $context = get_cbt_user_context();

        // Filter bank soal berdasarkan mata pelajaran yang diampu guru
        $query = $this->db->table('cbt_question_banks qb')
            ->select('qb.id, qb.code, s.name as subject_name, s.id as subject_id')
            ->join('subjects s', 's.id = qb.subject_id');

        // Guru hanya bisa konversi nilai untuk mata pelajaran yang ia ampu
        if ($context['is_teacher'] && $context['teacher_id']) {
            $teacherSubjects = get_teacher_subjects($context['teacher_id']);
            $subjectIds = array_column($teacherSubjects, 'id');
            
            if (empty($subjectIds)) {
                $banks = [];
            } else {
                $query->whereIn('s.id', $subjectIds);
                $banks = $query->get()->getResultArray();
            }
        } else {
            // Admin bisa lihat semua
            $banks = $query->get()->getResultArray();
        }

        // Filter kelas berdasarkan kelas yang diampu guru
        if ($context['is_admin']) {
            $classes = $this->db->table('classes')->orderBy('level', 'ASC')->orderBy('name', 'ASC')->get()->getResultArray();
        } elseif ($context['is_teacher'] && $context['teacher_id']) {
            $classes = get_teacher_classes($context['teacher_id']);
        } else {
            $classes = [];
        }

        return view('admin/cbt/convert/index', [
            'banks' => $banks,
            'classes' => $classes
        ]);
    }

    /**
     * 2. Preview Hasil Konversi
     */
    public function preview()
    {
        helper('cbt');
        $context = get_cbt_user_context();

        $bankId = $this->request->getPost('bank_id');
        $classId = $this->request->getPost('class_id');
        $ya = (float) $this->request->getPost('ya'); // Target Max
        $yb = (float) $this->request->getPost('yb'); // Target Min

        if (!$bankId || !$classId) {
            return redirect()->back()->with('error', 'Bank Soal dan Kelas harus dipilih.');
        }

        // Ambil data ujian terkait bank soal ini
        $test = $this->db->table('cbt_test_status ts')
            ->select('ts.id, qb.code as bank_code, s.name as subject_name, s.id as subject_id')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id')
            ->join('subjects s', 's.id = qb.subject_id')
            ->where('ts.bank_id', $bankId)
            ->get()->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Tidak ada jadwal ujian untuk Bank Soal ini.');
        }

        // Validasi akses - guru hanya bisa konversi mata pelajaran yang ia ampu
        if (!can_convert_subject_score($test['subject_id'])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengkonversi nilai mata pelajaran ini.');
        }

        // Ambil nilai siswa di kelas tersebut
        $sessions = $this->db->table('cbt_sessions cs')
            ->select('cs.student_id, st.name as student_name, st.nis, cs.score, cs.total_score')
            ->join('students st', 'st.id = cs.student_id')
            ->join('student_records sr', 'sr.student_id = st.id')
            ->where('cs.test_id', $test['id'])
            ->where('sr.class_id', $classId)
            ->get()->getResultArray();

        if (empty($sessions)) {
            return redirect()->back()->with('error', 'Tidak ada data nilai untuk kelas ini pada ujian tersebut.');
        }

        // Cari XA (Max Asli) dan XB (Min Asli)
        $rawScores = array_map(function ($s) {
            return (float) ($s['total_score'] ?? $s['score'] ?? 0);
        }, $sessions);

        $xa = max($rawScores);
        $xb = min($rawScores);

        // Jika XA == XB, hindari pembagian nol
        $denominator = ($xa - $xb) ?: 1;

        $results = [];
        foreach ($sessions as $s) {
            $nx = (float) ($s['total_score'] ?? $s['score'] ?? 0);

            // Rumus: ((YA-YB)/(XA-XB)) x (NX-XB) + YB
            if ($xa == $xb) {
                // Jika semua nilai sama, set ke YA (atau YB, sama saja)
                $converted = $ya;
            } else {
                $converted = (($ya - $yb) / $denominator) * ($nx - $xb) + $yb;
            }

            $results[] = [
                'student_id' => $s['student_id'],
                'student_name' => $s['student_name'],
                'nis' => $s['nis'],
                'raw_score' => $nx,
                'converted_score' => round($converted, 2)
            ];
        }

        // Ambil data materi dari ATP (Lingkup Materi)
        $activeYear = (new AcademicYearModel())->getActiveYear() ?: [];
        $materials = $this->db->table('alur_tujuan_pembelajaran')
            ->select('id, lingkup_materi as title, semester')
            ->where('subject_id', $test['subject_id'])
            ->where('class_id', $classId)
            ->get()->getResultArray();

        return view('admin/cbt/convert/preview', [
            'test' => $test,
            'class_id' => $classId,
            'class_name' => $this->db->table('classes')->where('id', $classId)->get()->getRowArray()['name'] ?? '-',
            'ya' => $ya,
            'yb' => $yb,
            'xa' => $xa,
            'xb' => $xb,
            'results' => $results,
            'materials' => $materials,
            'activeYear' => $activeYear
        ]);
    }

    /**
     * 3. Simpan Nilai ke Raport
     */
    public function save()
    {
        helper('cbt');
        
        $type = $this->request->getPost('dest_type'); // formatif, sumatif, final
        $subjectId = $this->request->getPost('subject_id');
        $yearId = $this->request->getPost('year_id');
        $semester = $this->request->getPost('semester');
        $studentScores = $this->request->getPost('student_scores'); // [student_id => score]

        if (empty($studentScores)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Tidak ada data untuk disimpan.');
        }

        // Validasi akses - guru hanya bisa konversi mata pelajaran yang ia ampu
        if (!can_convert_subject_score($subjectId)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Anda tidak memiliki akses untuk mengkonversi nilai mata pelajaran ini.');
        }

        $this->db->transStart();

        $updateCount = 0;
        $ignoreCount = 0;

        foreach ($studentScores as $studentId => $newScore) {
            $newScore = (float) $newScore;
            $existingScore = null;
            $table = '';
            $where = [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'year_id' => $yearId
            ];

            if ($type === 'formatif') {
                $materialId = $this->request->getPost('material_id');
                $method = $this->request->getPost('material_method'); // "tulis" as default requested
                $table = 'material_scores';

                $row = $this->db->table($table)
                    ->where('student_id', $studentId)
                    ->where('material_id', $materialId)
                    ->where('type', $method)
                    ->get()->getRowArray();

                $existingScore = $row ? (float) $row['score'] : -1;

                $data = [
                    'student_id' => $studentId,
                    'material_id' => $materialId,
                    'type' => $method,
                    'score' => $newScore,
                    'created_by' => session()->get('user')['id']
                ];

            } elseif ($type === 'sumatif') {
                $method = $this->request->getPost('sumatif_method');
                $table = 'summative_scores';
                $where['semester'] = $semester;
                $where['type'] = $method;

                $row = $this->db->table($table)->where($where)->get()->getRowArray();
                $existingScore = $row ? (float) $row['score'] : -1;

                $data = array_merge($where, ['score' => $newScore]);

            } elseif ($type === 'final') {
                $table = 'final_exam_scores';
                $where['semester'] = $semester;

                $row = $this->db->table($table)->where($where)->get()->getRowArray();
                $existingScore = $row ? (float) $row['score'] : -1;

                $data = array_merge($where, ['score' => $newScore]);
            }

            // Simpan nilai bersifat update jika pada materi yang dipilih sudah ada nilai 
            // maka rubah jika nilai lebih besar dan abaikan jika nilai lebih kecil nilai sebelumnya
            if ($newScore > $existingScore) {
                if ($existingScore === -1.0) {
                    $this->db->table($table)->insert($data);
                } else {
                    // Update needs specific where
                    if ($type === 'formatif') {
                        $this->db->table($table)
                            ->where('student_id', $studentId)
                            ->where('material_id', $materialId)
                            ->where('type', $method)
                            ->update(['score' => $newScore]);
                    } else {
                        $this->db->table($table)->where($where)->update(['score' => $newScore]);
                    }
                }
                $updateCount++;
            } else {
                $ignoreCount++;
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Gagal menyimpan nilai.');
        }

        return redirect()->to('admin/cbt/convertnilai')->with('success', "Berhasil memproses nilai. $updateCount diperbarui/ditambah, $ignoreCount diabaikan (nilai lama lebih besar).");
    }
}
