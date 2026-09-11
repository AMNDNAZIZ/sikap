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

        // Ambil SEMUA siswa aktif di kelas tersebut, beserta nilai CBT-nya (bila ada).
        // Siswa yang tidak mengikuti ujian (tanpa session) tetap muncul dengan nilai 0
        // agar bisa disimpan (dikosongkan/0) dan tidak mengganggu penyimpanan siswa lain.
        $sessions = $this->db->table('student_records sr')
            ->select('st.id as student_id, st.name as student_name, st.nis,
                      cs.score, cs.total_score')
            ->join('students st', 'st.id = sr.student_id')
            ->join('cbt_sessions cs', "cs.student_id = st.id AND cs.test_id = {$test['id']}", 'left')
            ->where('sr.class_id', $classId)
            ->where('sr.status', 'aktif')
            ->orderBy('st.name', 'ASC')
            ->get()->getResultArray();

        if (empty($sessions)) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif di kelas ini.');
        }

        // Cari XA (Max Asli) dan XB (Min Asli) HANYA dari siswa yang benar-benar ikut ujian
        // (punya nilai), agar rentang konversi tidak terdistorsi oleh nilai 0 siswa absen.
        $participantScores = [];
        foreach ($sessions as $s) {
            if ($s['total_score'] !== null || $s['score'] !== null) {
                $participantScores[] = (float) ($s['total_score'] ?? $s['score'] ?? 0);
            }
        }

        if (empty($participantScores)) {
            return redirect()->back()->with('error', 'Belum ada siswa yang mengikuti ujian ini.');
        }

        $xa = max($participantScores);
        $xb = min($participantScores);

        // Jika XA == XB, hindari pembagian nol
        $denominator = ($xa - $xb) ?: 1;

        $results = [];
        foreach ($sessions as $s) {
            // Siswa tidak ikut ujian (tanpa session) → nilai 0, tidak dikonversi
            $ikutUjian = ($s['total_score'] !== null || $s['score'] !== null);

            if (!$ikutUjian) {
                $results[] = [
                    'student_id' => $s['student_id'],
                    'student_name' => $s['student_name'],
                    'nis' => $s['nis'],
                    'raw_score' => null,
                    'converted_score' => 0,
                    'ikut_ujian' => false,
                ];
                continue;
            }

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
                'converted_score' => round($converted, 2),
                'ikut_ujian' => true,
            ];
        }

        // Ambil data materi dari ATP (Lingkup Materi).
        // Konsisten dgn Assessment::formatifList: ATP dikaitkan per-level, dan bila
        // mapel single-guru, ATP bisa tersimpan di kelas lain se-level. Ambil ATP
        // se-level agar material_id yang dipilih PASTI muncul di daftar formatif.
        $activeYear = (new AcademicYearModel())->getActiveYear() ?: [];

        $classRow   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $classLevel = (int) ($classRow['level'] ?? 0);

        // Deteksi multi-guru untuk mapel+level ini
        $guruCount = $this->db->table('teaching_assignments ta')
            ->distinct()->select('ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id', $test['subject_id'])
            ->where('c.level', $classLevel)
            ->where('ta.academic_year_id', $activeYear['id'] ?? 0)
            ->countAllResults();
        $isMultiGuru = $guruCount > 1;

        $matBuilder = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi as title, atp.semester')
            ->join('classes c_atp', 'c_atp.id = atp.class_id', 'left')
            ->where('atp.subject_id', $test['subject_id']);

        if ($isMultiGuru) {
            $matBuilder->where('atp.class_id', $classId);
        } else {
            $matBuilder->where('c_atp.level', $classLevel);
        }

        $materials = $matBuilder
            ->orderBy('atp.semester', 'ASC')
            ->orderBy('atp.id', 'ASC')
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

        if (empty($studentScores) || !is_array($studentScores)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Tidak ada data untuk disimpan.');
        }

        // Validasi akses - guru hanya bisa konversi mata pelajaran yang ia ampu
        if (!can_convert_subject_score($subjectId)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Anda tidak memiliki akses untuk mengkonversi nilai mata pelajaran ini.');
        }

        // Validasi tipe tujuan
        if (!in_array($type, ['formatif', 'sumatif', 'final'], true)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Tujuan penyimpanan nilai tidak valid.');
        }

        // Validasi field wajib per tipe (agar tidak gagal di tengah transaksi)
        if ($type === 'formatif' && empty($this->request->getPost('material_id'))) {
            return redirect()->back()->with('error', 'Pilih Lingkup Materi (ATP) terlebih dahulu.');
        }
        if (in_array($type, ['sumatif', 'final'], true) && empty($semester)) {
            // final tidak menyimpan semester, tapi UI tetap meminta; abaikan bila kosong utk final
            if ($type === 'sumatif') {
                return redirect()->back()->with('error', 'Pilih semester terlebih dahulu.');
            }
        }

        // ── Normalisasi metode agar cocok dengan ENUM kolom `type` ───────────
        // material_scores.type  : tulis|lisan|projek|observasi
        // summative_scores.type : tulis|penugasan
        $normFormatif = function (?string $m): string {
            $m = strtolower(trim((string) $m));
            $map = [
                'tulis' => 'tulis', 'tertulis' => 'tulis', 'tes' => 'tulis',
                'lisan' => 'lisan',
                'projek' => 'projek', 'proyek' => 'projek', 'praktek' => 'projek', 'praktik' => 'projek',
                'observasi' => 'observasi', 'pengamatan' => 'observasi',
            ];
            return $map[$m] ?? 'tulis';
        };
        $normSumatif = function (?string $m): string {
            $m = strtolower(trim((string) $m));
            // STS/tengah → tulis; SAS/akhir/penugasan → penugasan (default tulis)
            if (in_array($m, ['penugasan', 'sas', 'akhir'], true)) return 'penugasan';
            return 'tulis';
        };

        $this->db->transStart();

        $updateCount = 0;
        $ignoreCount = 0;

        foreach ($studentScores as $studentId => $newScore) {
            // Siswa tanpa nilai (tidak ikut ujian / input kosong) → anggap 0
            // agar tidak menggagalkan penyimpanan nilai siswa lain.
            if ($newScore === '' || $newScore === null || !is_numeric($newScore)) {
                $newScore = 0.0;
            }
            $newScore = (float) $newScore;
            // Batasi rentang wajar 0–100
            if ($newScore < 0)   $newScore = 0.0;
            if ($newScore > 100) $newScore = 100.0;

            $studentId = (int) $studentId;
            if ($studentId <= 0) { $ignoreCount++; continue; }

            $table = '';
            $row = null;                 // baris nilai lama (jika ada)
            $matchWhere = [];            // where untuk update baris yang tepat
            $data = [];                  // data insert

            if ($type === 'formatif') {
                $materialId = $this->request->getPost('material_id');
                $method = $normFormatif($this->request->getPost('material_method'));
                $table = 'material_scores';

                if (empty($materialId)) { $ignoreCount++; continue; }

                $matchWhere = [
                    'student_id'  => $studentId,
                    'material_id' => $materialId,
                    'type'        => $method,
                ];
                $row = $this->db->table($table)->where($matchWhere)->get()->getRowArray();

                $data = $matchWhere + [
                    'score'      => $newScore,
                    'created_by' => session()->get('user')['id'],
                ];

            } elseif ($type === 'sumatif') {
                $method = $normSumatif($this->request->getPost('sumatif_method'));
                $table = 'summative_scores';

                $matchWhere = [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'year_id'    => $yearId,
                    'semester'   => $semester,
                    'type'       => $method,
                ];
                $row = $this->db->table($table)->where($matchWhere)->get()->getRowArray();

                $data = $matchWhere + ['score' => $newScore];

            } elseif ($type === 'final') {
                $table = 'final_exam_scores';
                // final_exam_scores TIDAK punya kolom semester — hanya student/subject/year
                $matchWhere = [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'year_id'    => $yearId,
                ];
                $row = $this->db->table($table)->where($matchWhere)->get()->getRowArray();

                $data = $matchWhere + ['score' => $newScore];
            }

            // Belum ada nilai → INSERT. Sudah ada → UPDATE hanya jika nilai baru lebih besar
            // (agar nilai bagus tidak tertimpa nilai konversi yang lebih rendah).
            if (!$row) {
                $this->db->table($table)->insert($data);
                $updateCount++;
            } else {
                $existingScore = (float) $row['score'];
                if ($newScore > $existingScore) {
                    $this->db->table($table)->where($matchWhere)->update(['score' => $newScore]);
                    $updateCount++;
                } else {
                    $ignoreCount++;
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Gagal menyimpan nilai.');
        }

        return redirect()->to('admin/cbt/convertnilai')->with('success', "Berhasil memproses nilai. $updateCount diperbarui/ditambah, $ignoreCount diabaikan (nilai lama lebih besar).");
    }
}
