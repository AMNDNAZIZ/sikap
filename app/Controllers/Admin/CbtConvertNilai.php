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
        if (!in_array($type, ['formatif', 'sumatif', 'final', 'pts'], true)) {
            return redirect()->to('admin/cbt/convertnilai')->with('error', 'Tujuan penyimpanan nilai tidak valid.');
        }

        // Validasi field wajib per tipe (agar tidak gagal di tengah transaksi)
        if ($type === 'formatif' && empty($this->request->getPost('material_id'))) {
            return redirect()->back()->with('error', 'Pilih Lingkup Materi (ATP) terlebih dahulu.');
        }
        if (in_array($type, ['sumatif', 'final', 'pts'], true) && empty($semester)) {
            // final tidak menyimpan semester, tapi UI tetap meminta; abaikan bila kosong utk final
            if ($type === 'sumatif' || $type === 'pts') {
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

            } elseif ($type === 'pts') {
                // PTS disimpan terpisah di pts_scores (tidak masuk perhitungan rapor akhir)
                $table = 'pts_scores';
                $matchWhere = [
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'year_id'    => $yearId,
                    'semester'   => $semester,
                ];
                $row = $this->db->table($table)->where($matchWhere)->get()->getRowArray();

                $data = $matchWhere + [
                    'score'      => $newScore,
                    'source'     => 'cbt_convert',
                    'created_by' => session()->get('user')['id'] ?? null,
                ];
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

    // ============================================================
    // ALUR KHUSUS: IMPOR NILAI CBT LANGSUNG KE PTS (tanpa konversi)
    // URL: /admin/cbt/pts-import?class_id=X&subject_id=Y&semester=Z
    // ============================================================

    /**
     * Pilih bank soal untuk impor ke PTS.
     * Bank soal difilter ke mapel yang sesuai (subject_id dari URL).
     */
    public function indexPtsImport()
    {
        helper('cbt');

        $classId   = (int) $this->request->getGet('class_id');
        $subjectId = (int) $this->request->getGet('subject_id');
        $semester  = $this->request->getGet('semester');

        if (!$classId || !$subjectId || !in_array($semester, ['1', '2'], true)) {
            return redirect()->to('admin/assessments')->with('error', 'Parameter tidak valid.');
        }

        $class   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();

        if (!$class || !$subject) {
            return redirect()->back()->with('error', 'Kelas atau Mata Pelajaran tidak ditemukan.');
        }

        // Daftar bank soal untuk mapel ini saja
        $banks = $this->db->table('cbt_question_banks qb')
            ->select('qb.id, qb.code, s.name as subject_name')
            ->join('subjects s', 's.id = qb.subject_id')
            ->where('qb.subject_id', $subjectId)
            ->where('qb.is_active', 1)
            ->orderBy('qb.id', 'DESC')
            ->get()->getResultArray();

        return view('admin/cbt/convert/pts_import_index', [
            'class'     => $class,
            'subject'   => $subject,
            'classId'   => $classId,
            'subjectId' => $subjectId,
            'semester'  => $semester,
            'banks'     => $banks,
        ]);
    }

    /**
     * Preview nilai CBT apa adanya (tanpa konversi) untuk PTS.
     */
    public function previewPtsImport()
    {
        helper('cbt');

        $bankId    = (int) $this->request->getPost('bank_id');
        $classId   = (int) $this->request->getPost('class_id');
        $subjectId = (int) $this->request->getPost('subject_id');
        $semester  = $this->request->getPost('semester');

        if (!$bankId || !$classId || !$subjectId || !in_array($semester, ['1', '2'], true)) {
            return redirect()->back()->with('error', 'Data tidak lengkap.');
        }

        // Ambil info test untuk bank soal ini
        $test = $this->db->table('cbt_test_status ts')
            ->select('ts.id, qb.code as bank_code, s.name as subject_name, s.id as subject_id')
            ->join('cbt_question_banks qb', 'qb.id = ts.bank_id')
            ->join('subjects s', 's.id = qb.subject_id')
            ->where('ts.bank_id', $bankId)
            ->get()->getRowArray();

        if (!$test) {
            return redirect()->back()->with('error', 'Tidak ada jadwal ujian untuk Bank Soal ini. Pastikan bank soal sudah dijadwalkan.');
        }

        if ((int) $test['subject_id'] !== $subjectId) {
            return redirect()->back()->with('error', 'Bank soal tidak sesuai dengan mata pelajaran yang dipilih.');
        }

        // Ambil semua siswa aktif di kelas + nilai CBT apa adanya
        $sessions = $this->db->table('student_records sr')
            ->select('st.id as student_id, st.name as student_name, st.nis,
                      cs.score, cs.total_score, cs.status as session_status')
            ->join('students st', 'st.id = sr.student_id')
            ->join('cbt_sessions cs', "cs.student_id = st.id AND cs.test_id = {$test['id']}", 'left')
            ->where('sr.class_id', $classId)
            ->where('sr.status', 'aktif')
            ->orderBy('st.name', 'ASC')
            ->get()->getResultArray();

        if (empty($sessions)) {
            return redirect()->back()->with('error', 'Tidak ada siswa aktif di kelas ini.');
        }

        // Hitung nilai raw per siswa (total_score atau score, apa adanya 0–100)
        $results = [];
        foreach ($sessions as $s) {
            $ikutUjian = ($s['total_score'] !== null || $s['score'] !== null);
            $rawScore  = null;
            if ($ikutUjian) {
                $rawScore = (float) ($s['total_score'] ?? $s['score'] ?? 0);
                $rawScore = max(0, min(100, $rawScore));
            }
            $results[] = [
                'student_id'   => $s['student_id'],
                'student_name' => $s['student_name'],
                'nis'          => $s['nis'],
                'raw_score'    => $rawScore,
                'ikut_ujian'   => $ikutUjian,
            ];
        }

        $class   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $subject = $this->db->table('subjects')->where('id', $subjectId)->get()->getRowArray();
        $activeYear = (new AcademicYearModel())->getActiveYear() ?: [];

        return view('admin/cbt/convert/pts_import_preview', [
            'test'       => $test,
            'class'      => $class,
            'subject'    => $subject,
            'classId'    => $classId,
            'subjectId'  => $subjectId,
            'semester'   => $semester,
            'results'    => $results,
            'activeYear' => $activeYear,
        ]);
    }

    /**
     * Simpan hasil CBT apa adanya ke pts_scores.
     * Nilai yang sudah ada diganti jika nilai baru lebih besar.
     */
    public function savePtsImport()
    {
        helper('cbt');

        $subjectId     = (int) $this->request->getPost('subject_id');
        $yearId        = (int) $this->request->getPost('year_id');
        $semester      = $this->request->getPost('semester');
        $classId       = (int) $this->request->getPost('class_id');
        $studentScores = $this->request->getPost('student_scores'); // [student_id => score]

        if (!$subjectId || !$yearId || !in_array($semester, ['1', '2'], true)) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        if (empty($studentScores) || !is_array($studentScores)) {
            return redirect()->to("admin/assessments/ptsList/{$classId}/{$subjectId}")->with('error', 'Tidak ada data untuk disimpan.');
        }

        if (!can_convert_subject_score($subjectId)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mapel ini.');
        }

        $this->db->transStart();
        $saved = 0;
        $skipped = 0;
        $userId = session()->get('user')['id'] ?? null;

        foreach ($studentScores as $studentId => $newScore) {
            if ($newScore === '' || $newScore === null || !is_numeric($newScore)) {
                $skipped++; continue;
            }
            $studentId = (int) $studentId;
            if ($studentId <= 0) { $skipped++; continue; }

            $newScore = (float) $newScore;
            $newScore = max(0, min(100, $newScore));

            $existing = $this->db->table('pts_scores')
                ->where('student_id', $studentId)
                ->where('subject_id', $subjectId)
                ->where('year_id', $yearId)
                ->where('semester', $semester)
                ->get()->getRowArray();

            if (!$existing) {
                $this->db->table('pts_scores')->insert([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'year_id'    => $yearId,
                    'semester'   => $semester,
                    'score'      => $newScore,
                    'source'     => 'cbt',
                    'created_by' => $userId,
                ]);
                $saved++;
            } elseif ($newScore > (float) $existing['score']) {
                $this->db->table('pts_scores')->where([
                    'student_id' => $studentId,
                    'subject_id' => $subjectId,
                    'year_id'    => $yearId,
                    'semester'   => $semester,
                ])->update(['score' => $newScore, 'source' => 'cbt']);
                $saved++;
            } else {
                $skipped++;
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai PTS.');
        }

        return redirect()->to("admin/assessments/ptsList/{$classId}/{$subjectId}")
            ->with('success', "Nilai PTS dari CBT berhasil disimpan: {$saved} siswa diperbarui, {$skipped} diabaikan.");
    }

    // ============================================================
    // IMPORT DARI CBT MANDIRI
    // Flow: upload JSON → parse → cocokkan NIS → konversi → preview
    //       → user pilih mapel + tipe tujuan → simpan
    // ============================================================

    /**
     * Preview: upload file JSON export CBT Mandiri, parse, cocokkan NIS,
     * hitung konversi nilai, tampilkan halaman preview + form mapping mapel.
     *
     * POST admin/cbt/import-mandiri/preview
     */
    public function importCbtMandiri()
    {
        helper('cbt');

        // --- 1. Validasi upload ---
        $file = $this->request->getFile('nilai_file');
        if (!$file || !$file->isValid() || $file->getExtension() !== 'json') {
            return redirect()->to('admin/cbt/convertnilai?tab=mandiri')
                ->with('error', 'File tidak valid. Harap upload file .json dari CBT Mandiri.');
        }

        $json = file_get_contents($file->getTempName());
        $payload = json_decode($json, true);

        if (!$payload || ($payload['format'] ?? '') !== 'espata_cbt_export_v1') {
            return redirect()->to('admin/cbt/convertnilai?tab=mandiri')
                ->with('error', 'Format file tidak dikenali. Pastikan file diexport dari CBT Mandiri versi terbaru.');
        }

        $students = $payload['students'] ?? [];
        if (empty($students)) {
            return redirect()->to('admin/cbt/convertnilai?tab=mandiri')
                ->with('error', 'File tidak mengandung data siswa.');
        }

        // --- 2. Parameter konversi ---
        $ya = (float) ($this->request->getPost('ya') ?? 100);
        $yb = (float) ($this->request->getPost('yb') ?? 0);
        if ($ya < $yb) { $ya = 100; $yb = 0; } // fallback aman

        // --- 3. Cocokkan NIS → student_id di ESPATA ---
        // Ambil semua NIS dari file
        $nisList = array_filter(array_column($students, 'nis'));
        $nisList = array_values(array_unique($nisList));

        $espataStudents = [];
        if (!empty($nisList)) {
            $rows = $this->db->table('students')
                ->select('id, nis, name, class_id')
                ->whereIn('nis', $nisList)
                ->get()->getResultArray();
            foreach ($rows as $r) {
                $espataStudents[$r['nis']] = $r; // NIS → data siswa ESPATA
            }
        }

        // --- 4. Hitung konversi nilai ---
        // Hanya siswa yang ikut ujian (ikut_ujian = true) masuk perhitungan range
        $participantScores = [];
        foreach ($students as $s) {
            if (!empty($s['ikut_ujian'])) {
                $participantScores[] = (float) $s['score'];
            }
        }

        $xa = !empty($participantScores) ? max($participantScores) : 100;
        $xb = !empty($participantScores) ? min($participantScores) : 0;
        $denominator = ($xa - $xb) ?: 1;

        $results = [];
        foreach ($students as $s) {
            $nis           = $s['nis'] ?? '';
            $rawScore      = (float) ($s['score'] ?? 0);
            $ikutUjian     = !empty($s['ikut_ujian']);
            $espata        = $espataStudents[$nis] ?? null;

            // Hitung nilai konversi
            $convertedScore = 0;
            if ($ikutUjian) {
                if ($ya == $yb || ($xa == $xb)) {
                    $convertedScore = $ya;
                } else {
                    $convertedScore = (($ya - $yb) / $denominator) * ($rawScore - $xb) + $yb;
                }
                $convertedScore = round(min(100, max(0, $convertedScore)), 2);
            }

            $results[] = [
                'nis'             => $nis,
                'name'            => $s['name']       ?? '-',
                'class_name'      => $s['class_name'] ?? '-',
                'raw_score'       => $rawScore,
                'converted_score' => $convertedScore,
                'ikut_ujian'      => $ikutUjian,
                'espata_id'       => $espata['id']   ?? null,
                'espata_name'     => $espata['name'] ?? null,
                'matched'         => ($espata !== null),
            ];
        }

        $matchedCount   = count(array_filter($results, fn($r) => $r['matched']));
        $unmatchedCount = count($results) - $matchedCount;

        // --- 5. Daftar mapel ESPATA untuk dropdown mapping ---
        // Filter berdasarkan mapel yang diampu guru (konsisten dengan can_convert_subject_score).
        // Admin (role 1) & Kepsek (role 2) melihat semua; Guru (role 3) hanya mapelnya.
        $context = get_cbt_user_context();

        if ($context['is_admin'] || $context['is_headmaster']) {
            // Admin & Kepsek: semua mapel aktif
            $espataSubjects = $this->db->table('subjects')
                ->select('id, name, code')
                ->where('is_active', 1)
                ->orderBy('sort_order', 'ASC')
                ->orderBy('name', 'ASC')
                ->get()->getResultArray();
        } elseif ($context['is_teacher'] && $context['teacher_id']) {
            // Guru: hanya mapel yang diampu di teaching_assignments
            $espataSubjects = $this->db->table('teaching_assignments ta')
                ->distinct()
                ->select('s.id, s.name, s.code')
                ->join('subjects s', 's.id = ta.subject_id', 'inner')
                ->where('ta.teacher_id', $context['teacher_id'])
                ->where('s.is_active', 1)
                ->orderBy('s.sort_order', 'ASC')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();
        } else {
            $espataSubjects = [];
        }

        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear() ?: [];

        return view('admin/cbt/convert/preview_cbt_mandiri', [
            'payload'        => $payload,
            'results'        => $results,
            'matchedCount'   => $matchedCount,
            'unmatchedCount' => $unmatchedCount,
            'ya'             => $ya,
            'yb'             => $yb,
            'xa'             => $xa,
            'xb'             => $xb,
            'espataSubjects' => $espataSubjects,
            'activeYear'     => $activeYear,
        ]);
    }

    /**
     * Simpan hasil import CBT Mandiri ke tabel nilai ESPATA.
     * Hanya menyimpan siswa yang matched (NIS ditemukan).
     *
     * POST admin/cbt/import-mandiri/save
     */
    public function saveCbtMandiri()
    {
        helper('cbt');

        $subjectId  = (int)   $this->request->getPost('subject_id');
        $yearId     = (int)   $this->request->getPost('year_id');
        $semester   =         $this->request->getPost('semester');
        $destType   =         $this->request->getPost('dest_type');   // formatif|sumatif|pts|final
        $scores     =         $this->request->getPost('student_scores'); // [student_id => score]

        if (!$subjectId || !$yearId || !$destType || empty($scores)) {
            return redirect()->to('admin/cbt/convertnilai?tab=mandiri')
                ->with('error', 'Data tidak lengkap. Harap isi semua field yang wajib.');
        }

        if (!in_array($destType, ['formatif', 'sumatif', 'pts', 'final'], true)) {
            return redirect()->back()->with('error', 'Tipe tujuan tidak valid.');
        }

        if (in_array($destType, ['sumatif', 'pts'], true) && empty($semester)) {
            return redirect()->back()->with('error', 'Pilih semester untuk tipe ' . strtoupper($destType) . '.');
        }

        // Validasi akses mapel
        if (!can_convert_subject_score($subjectId)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mapel ini.');
        }

        // Untuk formatif butuh material_id
        $materialId = $this->request->getPost('material_id');
        if ($destType === 'formatif' && empty($materialId)) {
            return redirect()->back()->with('error', 'Pilih Lingkup Materi (ATP) untuk tipe Formatif.');
        }

        $this->db->transStart();
        $saved   = 0;
        $skipped = 0;
        $userId  = session()->get('user')['id'] ?? null;
        $now     = date('Y-m-d H:i:s');

        foreach ($scores as $studentId => $newScore) {
            $studentId = (int) $studentId;
            if ($studentId <= 0) { $skipped++; continue; }

            $newScore = ($newScore === '' || $newScore === null || !is_numeric($newScore))
                ? 0.0 : (float) $newScore;
            $newScore = max(0, min(100, $newScore));

            // ── Tentukan tabel & where berdasarkan destType ──
            $table      = '';
            $matchWhere = [];
            $insertData = [];

            if ($destType === 'formatif') {
                $normMethod = 'tulis'; // nilai dari CBT = tulis
                $table      = 'material_scores';
                $matchWhere = ['student_id' => $studentId, 'material_id' => $materialId, 'type' => $normMethod];
                $insertData = array_merge($matchWhere, ['score' => $newScore, 'created_by' => $userId]);

            } elseif ($destType === 'sumatif') {
                $table      = 'summative_scores';
                $matchWhere = ['student_id' => $studentId, 'subject_id' => $subjectId,
                               'year_id' => $yearId, 'semester' => $semester, 'type' => 'tulis'];
                $insertData = array_merge($matchWhere, ['score' => $newScore]);

            } elseif ($destType === 'pts') {
                $table      = 'pts_scores';
                $matchWhere = ['student_id' => $studentId, 'subject_id' => $subjectId,
                               'year_id' => $yearId, 'semester' => $semester];
                $insertData = array_merge($matchWhere, ['score' => $newScore,
                               'source' => 'cbt_mandiri', 'created_by' => $userId]);

            } elseif ($destType === 'final') {
                $table      = 'final_exam_scores';
                $matchWhere = ['student_id' => $studentId, 'subject_id' => $subjectId, 'year_id' => $yearId];
                $insertData = array_merge($matchWhere, ['score' => $newScore]);
            }

            if (!$table) { $skipped++; continue; }

            $existing = $this->db->table($table)->where($matchWhere)->get()->getRowArray();
            if (!$existing) {
                $this->db->table($table)->insert($insertData);
                $saved++;
            } elseif ($newScore > (float) $existing['score']) {
                $this->db->table($table)->where($matchWhere)->update(['score' => $newScore]);
                $saved++;
            } else {
                $skipped++;
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan nilai ke database.');
        }

        return redirect()->to('admin/cbt/convertnilai?tab=mandiri')
            ->with('success', "Import CBT Mandiri selesai: {$saved} nilai disimpan, {$skipped} diabaikan (nilai lama lebih besar atau tidak diikuti).");
    }

    /**
     * AJAX: daftar ATP/Lingkup Materi untuk subject_id tertentu.
     * Dipakai oleh view preview_cbt_mandiri untuk dropdown Formatif.
     *
     * GET admin/cbt/import-mandiri/atp/:subjectId
     */
    public function getAtpBySubject(int $subjectId)
    {
        $rows = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi AS title, atp.semester')
            ->join('classes c', 'c.id = atp.class_id', 'left')
            ->where('atp.subject_id', $subjectId)
            ->orderBy('atp.semester', 'ASC')
            ->orderBy('atp.urutan', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON($rows);
    }
}
