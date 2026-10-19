<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkInstrumentModel;
use App\Models\BkInstrumentDomainModel;
use App\Models\BkInstrumentScaleModel;
use App\Models\BkInstrumentQuestionModel;
use App\Models\BkQuestionOptionModel;
use App\Models\BkInstrumentInterpretationModel;

class BkAssessment extends BaseController
{
    protected $instrumentModel;
    protected $domainModel;
    protected $scaleModel;
    protected $questionModel;
    protected $optionModel;
    protected $interpretationModel;

    public function __construct()
    {
        helper(['text']);
        $this->instrumentModel = new BkInstrumentModel();
        $this->domainModel = new BkInstrumentDomainModel();
        $this->scaleModel = new BkInstrumentScaleModel();
        $this->questionModel = new BkInstrumentQuestionModel();
        $this->optionModel = new BkQuestionOptionModel();
        $this->interpretationModel = new BkInstrumentInterpretationModel();
    }

    // --- INSTRUMENT BUILDER ---

    public function createInstrument()
    {
        $schoolLevelCode = 'All';
        $schoolLevelLabel = 'Semua Jenjang';
        if (function_exists('is_school_sd') && is_school_sd()) {
            $schoolLevelCode = 'SD';
            $schoolLevelLabel = 'SD / Sederajat';
        } elseif (function_exists('is_school_smp') && is_school_smp()) {
            $schoolLevelCode = 'SMP';
            $schoolLevelLabel = 'SMP / Sederajat';
        } elseif (function_exists('is_school_sma') && is_school_sma()) {
            $schoolLevelCode = 'SMA';
            $schoolLevelLabel = 'SMA / SMK / Sederajat';
        }

        $data = [
            'title'            => 'Buat Instrumen Asesmen Baru',
            'active_menu'      => 'bk',
            'schoolLevelCode'  => $schoolLevelCode,
            'schoolLevelLabel' => $schoolLevelLabel,
        ];
        return view('admin/bk/assessment/create', $data);
    }

    public function storeInstrument()
    {
        $title = $this->request->getPost('title');
        
        $targetLevel = $this->request->getPost('target_level');
        if (empty($targetLevel)) {
            if (function_exists('is_school_sd') && is_school_sd()) {
                $targetLevel = 'SD';
            } elseif (function_exists('is_school_smp') && is_school_smp()) {
                $targetLevel = 'SMP';
            } elseif (function_exists('is_school_sma') && is_school_sma()) {
                $targetLevel = 'SMA';
            } else {
                $targetLevel = 'All';
            }
        }

        $data = [
            'title'              => $title,
            'description'        => $this->request->getPost('description'),
            'assessment_type'    => $this->request->getPost('assessment_type'),
            'academic_year_id'   => $this->getActiveAcademicYearId(),
            'target_level'       => $targetLevel,
            'purpose'            => $this->request->getPost('purpose'),
            'default_scale_type' => $this->request->getPost('default_scale_type') ?? 'likert',
            'status'             => 'draft',
            'created_by'         => session('user_id'),
        ];
        
        $id = $this->instrumentModel->insert($data);
        
        return redirect()->to("admin/bk/pemetaan/instrumen/edit/$id")->with('success', 'Instrumen berhasil dibuat. Silakan lanjutkan konfigurasi.');
    }

    public function editInstrument($id)
    {
        $instrument = $this->instrumentModel->find($id);
        if (!$instrument) return redirect()->back()->with('error', 'Instrumen tidak ditemukan.');

        $data = [
            'title' => 'Edit Instrumen: ' . $instrument['title'],
            'active_menu' => 'bk',
            'instrument' => $instrument,
            'domains' => $this->domainModel->where('instrument_id', $id)->orderBy('sort_order', 'ASC')->findAll(),
            'scales' => $this->scaleModel->where('instrument_id', $id)->orderBy('sort_order', 'ASC')->findAll(),
            'questions' => $this->questionModel->where('instrument_id', $id)->orderBy('sort_order', 'ASC')->findAll(),
            'interpretations' => $this->interpretationModel->where('instrument_id', $id)->findAll(),
        ];
        return view('admin/bk/assessment/edit', $data);
    }
    
    public function updateInstrument($id)
    {
        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
        ];
        $this->instrumentModel->update($id, $data);
        return redirect()->back()->with('success', 'Informasi instrumen berhasil diupdate.');
    }
    
    public function publishInstrument($id)
    {
        $this->instrumentModel->update($id, ['status' => 'published']);
        return redirect()->back()->with('success', 'Instrumen berhasil dipublish dan siap digunakan.');
    }

    public function unpublishInstrument($id)
    {
        $instrument = $this->instrumentModel->find($id);
        if (!$instrument) {
            return redirect()->back()->with('error', 'Instrumen tidak ditemukan.');
        }

        $this->instrumentModel->update($id, ['status' => 'draft']);
        return redirect()->back()->with('success', 'Instrumen berhasil di-unpublish menjadi status Draft.');
    }

    public function deleteInstrument($id)
    {
        $instrument = $this->instrumentModel->find($id);
        if (!$instrument) {
            return redirect()->back()->with('error', 'Instrumen tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        
        // Cek apakah ada jawaban siswa yang terkait dengan instrumen ini
        $hasAnswers = $db->table('bk_assignments ba')
            ->join('bk_assignment_students bas', 'bas.assignment_id = ba.id')
            ->where('ba.instrument_id', $id)
            ->where('bas.status !=', 'ASSIGNED')
            ->countAllResults();

        if ($hasAnswers > 0) {
            return redirect()->back()->with('error', 'Instrumen tidak dapat dihapus karena sudah memiliki hasil atau respon jawaban siswa. Silakan unpublish jika ingin menonaktifkannya.');
        }

        // Hapus penugasan yang belum diisi siswa jika ada
        $assignments = $db->table('bk_assignments')->where('instrument_id', $id)->get()->getResultArray();
        foreach ($assignments as $a) {
            $db->table('bk_assignment_students')->where('assignment_id', $a['id'])->delete();
            $db->table('bk_assignments')->where('id', $a['id'])->delete();
        }

        // Hapus detail konfigurasi instrumen: pertanyaan, opsi, skala, domain, interpretasi
        $questions = $this->questionModel->where('instrument_id', $id)->findAll();
        foreach ($questions as $q) {
            $this->optionModel->where('question_id', $q['id'])->delete();
        }
        $this->questionModel->where('instrument_id', $id)->delete();
        $this->domainModel->where('instrument_id', $id)->delete();
        $this->scaleModel->where('instrument_id', $id)->delete();
        $this->interpretationModel->where('instrument_id', $id)->delete();

        // Hapus instrumen
        $this->instrumentModel->delete($id);

        return redirect()->to('admin/bk/pemetaan')->with('success', 'Instrumen "' . $instrument['title'] . '" berhasil dihapus.');
    }

    // --- AJAX METHODS FOR BUILDER ---

    // Domain CRUD
    public function storeDomain()
    {
        $data = [
            'instrument_id' => $this->request->getPost('instrument_id'),
            'domain_name'   => $this->request->getPost('domain_name'),
            'sub_domain'    => $this->request->getPost('sub_domain'),
            'sort_order'    => $this->request->getPost('sort_order') ?? 0,
        ];
        $id = $this->domainModel->insert($data);
        return $this->response->setJSON(['status' => 'success', 'id' => $id, 'message' => 'Aspek berhasil ditambahkan']);
    }

    public function deleteDomain($id)
    {
        $this->domainModel->delete($id);
        return $this->response->setJSON(['status' => 'success', 'message' => 'Aspek berhasil dihapus']);
    }

    // Question CRUD
    public function storeQuestion()
    {
        $questionText = trim($this->request->getPost('question_text') ?? '');
        $instrumentId = (int)$this->request->getPost('instrument_id');
        $domainId     = $this->request->getPost('domain_id') ?: null;

        if (empty($questionText)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Teks pertanyaan tidak boleh kosong']);
        }

        // Cegah duplikasi pertanyaan akibat double-click/double-submit
        $existing = $this->questionModel
            ->where('instrument_id', $instrumentId)
            ->where('domain_id', $domainId)
            ->where('question_text', $questionText)
            ->first();

        if ($existing) {
            return $this->response->setJSON([
                'status'  => 'success',
                'id'      => $existing['id'],
                'message' => 'Pertanyaan sudah tersimpan (duplikasi dicegah)'
            ]);
        }

        $data = [
            'instrument_id' => $instrumentId,
            'domain_id'     => $domainId,
            'question_text' => $questionText,
            'question_type' => $this->request->getPost('question_type') ?? 'scale',
            'is_reverse'    => $this->request->getPost('is_reverse') ?? 0,
            'sort_order'    => $this->request->getPost('sort_order') ?? 0,
        ];
        $id = $this->questionModel->insert($data);
        
        // Update total questions count in instrument
        $this->_updateTotalQuestions($data['instrument_id']);
        
        return $this->response->setJSON(['status' => 'success', 'id' => $id, 'message' => 'Pertanyaan berhasil ditambahkan']);
    }

    public function deleteQuestion($id)
    {
        $question = $this->questionModel->find($id);
        if ($question) {
            $this->questionModel->delete($id);
            $this->_updateTotalQuestions($question['instrument_id']);
        }
        return $this->response->setJSON(['status' => 'success', 'message' => 'Pertanyaan berhasil dihapus']);
    }

    private function _updateTotalQuestions($instrument_id)
    {
        $total = $this->questionModel->where('instrument_id', $instrument_id)->countAllResults();
        $this->instrumentModel->update($instrument_id, ['total_questions' => $total]);
    }

    private function getActiveAcademicYearId(): int
    {
        $sessionYearId = session('academic_year_id');
        if (!empty($sessionYearId)) {
            return (int)$sessionYearId;
        }
        $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
        return (int)($activeYear['id'] ?? 1);
    }

    /**
     * Dapatkan siswa pada kelas-kelas target dengan toleransi terhadap tahun ajaran & status
     */
    private function getStudentsForClasses($db, array $classIds, int $academicYearId): array
    {
        $classIds = array_values(array_filter(array_map('intval', $classIds)));
        if (empty($classIds)) {
            return [];
        }

        $builder = $db->table('student_records sr')
            ->select('sr.student_id')
            ->whereIn('sr.class_id', $classIds)
            ->where('LOWER(sr.status)', 'aktif');

        if ($academicYearId > 0) {
            $hasYear = (clone $builder)->where('sr.academic_year_id', $academicYearId)->countAllResults();
            if ($hasYear > 0) {
                $builder->where('sr.academic_year_id', $academicYearId);
            }
        }
        $students = $builder->groupBy('sr.student_id')->get()->getResultArray();

        // Fallback jika tidak ditemukan dengan status 'aktif' (misal status null atau format lain)
        if (empty($students)) {
            $students = $db->table('student_records sr')
                ->select('sr.student_id')
                ->whereIn('sr.class_id', $classIds)
                ->groupBy('sr.student_id')
                ->get()->getResultArray();
        }

        return $students;
    }

    // --- PENUGASAN (ASSIGNMENTS) ---

    public function assignments()
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        
        $data = [
            'title'       => 'Penugasan Asesmen BK',
            'active_menu' => 'bk',
            'assignments' => $assignmentModel->select('bk_assignments.*, bk_instruments.title as instrument_title, classes.name as class_name')
                                             ->join('bk_instruments', 'bk_instruments.id = bk_assignments.instrument_id')
                                             ->join('classes', 'classes.id = bk_assignments.target_id', 'left')
                                             ->orderBy('bk_assignments.created_at', 'DESC')
                                             ->findAll(),
            'instruments' => $this->instrumentModel->where('status', 'published')->findAll(),
            'classes'     => (new \App\Models\ClassModel())->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll()
        ];
        
        return view('admin/bk/assessment/assignments', $data);
    }

    public function storeAssignment()
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        
        $instrument_id = $this->request->getPost('instrument_id');
        $target_type   = $this->request->getPost('target_type') ?: 'class';
        $academic_year_id = $this->getActiveAcademicYearId();

        // Mendukung pemilihan banyak kelas (target_ids array) atau single target_id
        $target_ids = $this->request->getPost('target_ids');
        if (!is_array($target_ids)) {
            $singleTarget = $this->request->getPost('target_id');
            $target_ids = $singleTarget ? [$singleTarget] : [];
        }
        $target_ids = array_values(array_filter(array_map('intval', $target_ids)));

        if ($target_type === 'class' && empty($target_ids)) {
            return redirect()->back()->withInput()->with('error', 'Silakan pilih minimal satu kelas sasaran penugasan.');
        }

        $classNames = [];
        $notesData = [];
        $primaryClassId = null;
        if ($target_type === 'class') {
            $classModel = new \App\Models\ClassModel();
            $classes = $classModel->whereIn('id', $target_ids)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll();
            $classNames = array_column($classes, 'name');
            $primaryClassId = $target_ids[0] ?? null;

            $notesData = [
                'class_ids'   => $target_ids,
                'class_names' => $classNames,
            ];
        }

        $instrument = $this->instrumentModel->find($instrument_id);
        $title = trim($this->request->getPost('title') ?? '');
        if (empty($title)) {
            if ($target_type === 'class' && !empty($classNames)) {
                $title = ($instrument['title'] ?? 'Asesmen BK') . ' - ' . implode(', ', $classNames);
            } else {
                $title = $instrument['title'] ?? 'Asesmen BK';
            }
        }

        $assignmentData = [
            'instrument_id'    => $instrument_id,
            'title'            => $title,
            'target_type'      => $target_type,
            'target_id'        => $primaryClassId,
            'academic_year_id' => $academic_year_id,
            'start_date'       => $this->request->getPost('start_date'),
            'end_date'         => $this->request->getPost('end_date'),
            'status'           => 'active',
            'assigned_by'      => session('user_id'),
            'notes'            => !empty($notesData) ? json_encode($notesData) : null,
        ];
        
        $assignment_id = $assignmentModel->insert($assignmentData);

        // POPULATE STUDENTS
        $assignmentStudentModel = new \App\Models\BkAssignmentStudentModel();
        $db = \Config\Database::connect();

        if ($target_type === 'class') {
            $students = $this->getStudentsForClasses($db, $target_ids, $academic_year_id);
        } else {
            $builder = $db->table('student_records sr')
                          ->select('sr.student_id')
                          ->where('LOWER(sr.status)', 'aktif');
            if ($academic_year_id > 0) {
                $hasYear = (clone $builder)->where('sr.academic_year_id', $academic_year_id)->countAllResults();
                if ($hasYear > 0) {
                    $builder->where('sr.academic_year_id', $academic_year_id);
                }
            }
            $students = $builder->groupBy('sr.student_id')->get()->getResultArray();
            if (empty($students)) {
                $students = $db->table('student_records sr')
                               ->select('sr.student_id')
                               ->groupBy('sr.student_id')
                               ->get()->getResultArray();
            }
        }
        
        $insertData = [];
        foreach ($students as $s) {
            $insertData[] = [
                'assignment_id' => $assignment_id,
                'student_id'    => $s['student_id'],
                'status'        => 'ASSIGNED'
            ];
        }
        
        if (!empty($insertData)) {
            $assignmentStudentModel->insertBatch($insertData);
        }

        $targetMsg = $target_type === 'class' 
            ? count($target_ids) . ' kelas (' . implode(', ', $classNames) . ')' 
            : 'semua siswa';

        return redirect()->to('admin/bk/pemetaan/penugasan')->with('success', 'Penugasan berhasil dibuat untuk ' . $targetMsg . ' dan didistribusikan ke ' . count($insertData) . ' siswa.');
    }

    // --- AJAX RESULT DETAIL ---
    
    public function studentResult($result_id)
    {
        $db = \Config\Database::connect();
        
        $result = $db->table('bk_student_results bsr')
            ->select('bsr.*, s.name as student_name, s.nisn, cl.name as class_name, bi.title')
            ->join('students s', 's.id = bsr.student_id')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . $this->getActiveAcademicYearId())
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('bk_instruments bi', 'bi.id = bsr.instrument_id')
            ->where('bsr.id', $result_id)
            ->get()->getRowArray();
            
        if (!$result) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Not found']);
        }
        
        $details = $db->table('bk_result_details brd')
            ->select('brd.*, d.domain_name, d.sub_domain')
            ->join('bk_instrument_domains d', 'd.id = brd.domain_id')
            ->where('brd.result_id', $result_id)
            ->orderBy('d.sort_order', 'ASC')
            ->get()->getResultArray();
            
        return $this->response->setJSON([
            'status' => 'success',
            'result' => $result,
            'details' => $details
        ]);
    }

    // --- PETA KEBUTUHAN KELAS (CLASS MAP) ---

    public function classMapDetail()
    {
        $instrument_id = $this->request->getPost('instrument_id');
        $class_id = $this->request->getPost('class_id');
        $academic_year_id = $this->getActiveAcademicYearId();

        $db = \Config\Database::connect();
        
        // Dapatkan semua siswa di kelas tersebut (tahun ajaran aktif)
        $students = $db->table('student_records sr')
            ->select('s.id, s.name, s.nisn')
            ->join('students s', 's.id = sr.student_id')
            ->where('sr.class_id', $class_id)
            ->where('sr.academic_year_id', $academic_year_id)
            ->where('sr.status', 'aktif')
            ->get()->getResultArray();
            
        if(empty($students)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada siswa aktif di kelas ini.']);
        }
        
        $studentIds = array_column($students, 'id');
        
        // Ambil struktur domain instrumen
        $domains = $db->table('bk_instrument_domains')
            ->where('instrument_id', $instrument_id)
            ->orderBy('sort_order', 'ASC')
            ->get()->getResultArray();
            
        if(empty($domains)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Instrumen tidak memiliki aspek/domain.']);
        }
        
        // Ambil hasil siswa (untuk instrument dan students tersebut)
        $resultsRaw = $db->table('bk_student_results bsr')
            ->select('bsr.id as result_id, bsr.student_id, bsr.percentage as overall_percentage, bsr.overall_level')
            ->where('bsr.instrument_id', $instrument_id)
            ->whereIn('bsr.student_id', $studentIds)
            ->get()->getResultArray();
            
        $resultsMap = []; // student_id => result
        $resultIds = [];
        foreach($resultsRaw as $r) {
            $resultsMap[$r['student_id']] = $r;
            $resultIds[] = $r['result_id'];
        }
        
        // Ambil detail domain
        $detailsMap = []; // result_id => [domain_id => detail]
        if(!empty($resultIds)) {
            $detailsRaw = $db->table('bk_result_details')
                ->whereIn('result_id', $resultIds)
                ->get()->getResultArray();
            foreach($detailsRaw as $d) {
                $detailsMap[$d['result_id']][$d['domain_id']] = $d;
            }
        }
        
        // Susun data map
        $mapData = [];
        $domainAverages = [];
        foreach($domains as $dom) {
            $domainAverages[$dom['id']] = ['total' => 0, 'count' => 0];
        }
        
        foreach($students as $s) {
            $res = $resultsMap[$s['id']] ?? null;
            
            $studentRow = [
                'name' => $s['name'],
                'nisn' => $s['nisn'],
                'has_result' => $res ? true : false,
                'overall_level' => $res ? $res['overall_level'] : '-',
                'domains' => []
            ];
            
            foreach($domains as $dom) {
                if($res && isset($detailsMap[$res['result_id']][$dom['id']])) {
                    $dDetail = $detailsMap[$res['result_id']][$dom['id']];
                    $studentRow['domains'][$dom['id']] = [
                        'percentage' => $dDetail['percentage'],
                        'level_label' => $dDetail['level_label']
                    ];
                    
                    $domainAverages[$dom['id']]['total'] += $dDetail['percentage'];
                    $domainAverages[$dom['id']]['count']++;
                } else {
                    $studentRow['domains'][$dom['id']] = null;
                }
            }
            
            $mapData[] = $studentRow;
        }
        
        // Kalkulasi rata-rata kelas
        $classAverages = [];
        foreach($domains as $dom) {
            $avg = $domainAverages[$dom['id']]['count'] > 0 
                 ? $domainAverages[$dom['id']]['total'] / $domainAverages[$dom['id']]['count'] 
                 : 0;
            $classAverages[] = [
                'domain_name' => $dom['domain_name'],
                'sub_domain' => $dom['sub_domain'],
                'average_percentage' => round($avg, 2)
            ];
        }
        
        // Urutkan kelas berdasarkan need (misal: yang rata-ratanya tertinggi jika AKPD)
        usort($classAverages, function($a, $b) {
            return $b['average_percentage'] <=> $a['average_percentage'];
        });

        return $this->response->setJSON([
            'status' => 'success',
            'domains' => $domains,
            'map' => $mapData,
            'averages' => $classAverages
        ]);
    }
    
    // --- ADDITIONAL METHODS FOR ROUTES ---
    
    public function duplicateInstrument($id)
    {
        return redirect()->back()->with('error', 'Fitur duplikasi belum diimplementasikan.');
    }
    
    // --- SKALA ---
    public function storeScale()
    {
        $data = [
            'instrument_id' => $this->request->getPost('instrument_id'),
            'scale_label' => $this->request->getPost('scale_label'),
            'scale_value' => $this->request->getPost('scale_value'),
            'sort_order' => 0
        ];
        $this->scaleModel->insert($data);
        return redirect()->back()->with('success', 'Skala berhasil ditambahkan.');
    }
    
    public function deleteScale($id)
    {
        $this->scaleModel->delete($id);
        return redirect()->back()->with('success', 'Skala berhasil dihapus.');
    }

    // --- INTERPRETASI ---
    public function storeInterpretation()
    {
        $domainId = $this->request->getPost('domain_id');
        $data = [
            'instrument_id' => $this->request->getPost('instrument_id'),
            'domain_id' => empty($domainId) ? null : $domainId,
            'min_percent' => $this->request->getPost('min_percent'),
            'max_percent' => $this->request->getPost('max_percent'),
            'level_label' => $this->request->getPost('level_label'),
            'description' => $this->request->getPost('description')
        ];
        $this->interpretationModel->insert($data);
        return redirect()->back()->with('success', 'Interpretasi berhasil ditambahkan.');
    }
    
    public function deleteInterpretation($id)
    {
        $this->interpretationModel->delete($id);
        return redirect()->back()->with('success', 'Interpretasi berhasil dihapus.');
    }
    
    public function previewInstrument($id)
    {
        $instrument = $this->instrumentModel->find($id);
        if (!$instrument) {
            return redirect()->back()->with('error', 'Instrumen tidak ditemukan.');
        }

        $domains = $this->domainModel->where('instrument_id', $id)->orderBy('sort_order', 'ASC')->findAll();
        $scales  = $this->scaleModel->where('instrument_id', $id)->orderBy('sort_order', 'ASC')->findAll();
        $questions = $this->questionModel->where('instrument_id', $id)->where('is_active', 1)->orderBy('sort_order', 'ASC')->findAll();
        $interpretations = $this->interpretationModel->where('instrument_id', $id)->findAll();

        // Default scale jika belum disetting (standard 4-skala Likert/AKPD)
        if (empty($scales)) {
            $scales = [
                ['scale_value' => 4, 'scale_label' => 'Sangat Sesuai / Sangat Membutuhkan'],
                ['scale_value' => 3, 'scale_label' => 'Sesuai / Membutuhkan'],
                ['scale_value' => 2, 'scale_label' => 'Kurang Sesuai / Kurang Membutuhkan'],
                ['scale_value' => 1, 'scale_label' => 'Tidak Sesuai / Tidak Membutuhkan'],
            ];
        }

        $data = [
            'title'           => 'Preview Instrumen: ' . $instrument['title'],
            'active_menu'     => 'bk',
            'instrument'      => $instrument,
            'domains'         => $domains,
            'scales'          => $scales,
            'questions'       => $questions,
            'interpretations' => $interpretations,
        ];

        return view('admin/bk/assessment/preview', $data);
    }
    
    public function updateDomain($id)
    {
        return $this->response->setJSON(['status' => 'error', 'message' => 'Belum diimplementasikan']);
    }
    
    public function reorderDomains()
    {
        return $this->response->setJSON(['status' => 'error', 'message' => 'Belum diimplementasikan']);
    }
    
    public function updateQuestion($id)
    {
        return $this->response->setJSON(['status' => 'error', 'message' => 'Belum diimplementasikan']);
    }
    
    public function reorderQuestions()
    {
        return $this->response->setJSON(['status' => 'error', 'message' => 'Belum diimplementasikan']);
    }
    
    public function duplicateQuestion($id)
    {
        return $this->response->setJSON(['status' => 'error', 'message' => 'Belum diimplementasikan']);
    }
    
    public function updateAssignment($id)
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        $assignment = $assignmentModel->find($id);
        if (!$assignment) {
            return redirect()->back()->with('error', 'Penugasan tidak ditemukan.');
        }

        $title = trim($this->request->getPost('title') ?? '');
        $start_date = $this->request->getPost('start_date');
        $end_date = $this->request->getPost('end_date');
        $status = $this->request->getPost('status') ?? $assignment['status'];
        $target_type = $this->request->getPost('target_type') ?: $assignment['target_type'];

        $updateData = [
            'title'      => !empty($title) ? $title : $assignment['title'],
            'start_date' => $start_date,
            'end_date'   => $end_date,
            'status'     => $status,
        ];

        $db = \Config\Database::connect();
        $academicYearId = (int)($assignment['academic_year_id'] ?: $this->getActiveAcademicYearId());

        // Jika tipe sasaran adalah kelas
        if ($target_type === 'class') {
            $target_ids = $this->request->getPost('target_ids');
            if (!is_array($target_ids)) {
                $single = $this->request->getPost('target_id');
                $target_ids = $single ? [$single] : [];
            }
            $target_ids = array_values(array_filter(array_map('intval', $target_ids)));

            if (empty($target_ids)) {
                return redirect()->back()->withInput()->with('error', 'Silakan pilih minimal satu kelas sasaran.');
            }

            $classModel = new \App\Models\ClassModel();
            $classes = $classModel->whereIn('id', $target_ids)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll();
            $classNames = array_column($classes, 'name');

            $updateData['target_type'] = 'class';
            $updateData['target_id']   = $target_ids[0];
            $updateData['notes']       = json_encode([
                'class_ids'   => $target_ids,
                'class_names' => $classNames,
            ]);

            // Sinkronisasi siswa: ambil semua siswa di kelas-kelas yang dipilih
            $students = $this->getStudentsForClasses($db, $target_ids, $academicYearId);
            $targetStudentIds = array_column($students, 'student_id');

            $existingStudents = $db->table('bk_assignment_students')
                ->where('assignment_id', $id)
                ->get()->getResultArray();
            $existingMap = [];
            foreach ($existingStudents as $es) {
                $existingMap[$es['student_id']] = $es;
            }

            // Tambahkan siswa baru dari kelas yang baru dipilih
            $newInserts = [];
            foreach ($targetStudentIds as $sid) {
                if (!isset($existingMap[$sid])) {
                    $newInserts[] = [
                        'assignment_id' => $id,
                        'student_id'    => $sid,
                        'status'        => 'ASSIGNED'
                    ];
                }
            }
            if (!empty($newInserts)) {
                $db->table('bk_assignment_students')->insertBatch($newInserts);
            }

            // Hapus siswa yang kelasnya tidak lagi dipilih, HANYA jika status masih ASSIGNED (belum mulai)
            $toRemove = [];
            foreach ($existingStudents as $es) {
                if (!in_array($es['student_id'], $targetStudentIds) && $es['status'] === 'ASSIGNED') {
                    $toRemove[] = $es['id'];
                }
            }
            if (!empty($toRemove)) {
                $db->table('bk_assignment_students')->whereIn('id', $toRemove)->delete();
            }
        } elseif ($target_type === 'all') {
            $updateData['target_type'] = 'all';
            $updateData['target_id']   = null;
            $updateData['notes']       = null;

            // Sinkronisasi semua siswa di tahun ajaran aktif
            $allStudents = $db->table('student_records sr')
                ->select('sr.student_id')
                ->where('sr.academic_year_id', $academicYearId)
                ->where('sr.status', 'aktif')
                ->get()->getResultArray();

            $existingStudents = $db->table('bk_assignment_students')
                ->where('assignment_id', $id)
                ->get()->getResultArray();
            $existingSids = array_column($existingStudents, 'student_id');

            $newInserts = [];
            foreach ($allStudents as $st) {
                if (!in_array($st['student_id'], $existingSids)) {
                    $newInserts[] = [
                        'assignment_id' => $id,
                        'student_id'    => $st['student_id'],
                        'status'        => 'ASSIGNED'
                    ];
                }
            }
            if (!empty($newInserts)) {
                $db->table('bk_assignment_students')->insertBatch($newInserts);
            }
        }

        $assignmentModel->update($id, $updateData);

        return redirect()->to('admin/bk/pemetaan/penugasan')->with('success', 'Penugasan berhasil diperbarui.');
    }

    public function deleteAssignment($id)
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        $assignment = $assignmentModel->find($id);
        if (!$assignment) {
            return redirect()->back()->with('error', 'Penugasan tidak ditemukan.');
        }

        $assignmentModel->delete($id);
        return redirect()->to('admin/bk/pemetaan/penugasan')->with('success', 'Penugasan "' . ($assignment['title'] ?? '') . '" berhasil dihapus.');
    }

    public function activateAssignment($id)
    {
        (new \App\Models\BkAssignmentModel())->update($id, ['status' => 'active']);
        return redirect()->back()->with('success', 'Penugasan diaktifkan.');
    }
    
    public function closeAssignment($id)
    {
        (new \App\Models\BkAssignmentModel())->update($id, ['status' => 'closed']);
        return redirect()->back()->with('success', 'Penugasan ditutup.');
    }
    
    public function assignmentProgress($id)
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        $db = \Config\Database::connect();

        $assignment = $assignmentModel->select('bk_assignments.*, bk_instruments.title as instrument_title, bk_instruments.assessment_type, bk_instruments.description as instrument_description, classes.name as class_name')
            ->join('bk_instruments', 'bk_instruments.id = bk_assignments.instrument_id')
            ->join('classes', 'classes.id = bk_assignments.target_id', 'left')
            ->where('bk_assignments.id', $id)
            ->first();

        if (!$assignment) {
            return redirect()->to('admin/bk/pemetaan/penugasan')->with('error', 'Data penugasan tidak ditemukan.');
        }

        $academicYearId = (int)($assignment['academic_year_id'] ?: $this->getActiveAcademicYearId());

        // Parse class names jika penugasan mencakup lebih dari satu kelas
        $targetClassNames = [];
        $targetClassIds = [];
        if (!empty($assignment['notes'])) {
            $parsedNotes = json_decode($assignment['notes'], true);
            if (!empty($parsedNotes['class_names']) && is_array($parsedNotes['class_names'])) {
                $targetClassNames = $parsedNotes['class_names'];
            }
            if (!empty($parsedNotes['class_ids']) && is_array($parsedNotes['class_ids'])) {
                $targetClassIds = array_map('intval', $parsedNotes['class_ids']);
            }
        }
        if (empty($targetClassNames) && !empty($assignment['class_name'])) {
            $targetClassNames = [$assignment['class_name']];
        }
        if (empty($targetClassIds) && !empty($assignment['target_id'])) {
            $targetClassIds = [(int)$assignment['target_id']];
        }
        $assignment['target_class_names'] = $targetClassNames;

        // Auto-backfill jika penugasan kelas ini belum memiliki siswa di bk_assignment_students
        $existingCount = $db->table('bk_assignment_students')->where('assignment_id', $id)->countAllResults();
        if ($existingCount === 0 && !empty($targetClassIds)) {
            $studentsToInsert = $this->getStudentsForClasses($db, $targetClassIds, $academicYearId);
            $backfill = [];
            foreach ($studentsToInsert as $st) {
                $backfill[] = [
                    'assignment_id' => $id,
                    'student_id'    => $st['student_id'],
                    'status'        => 'ASSIGNED',
                ];
            }
            if (!empty($backfill)) {
                (new \App\Models\BkAssignmentStudentModel())->insertBatch($backfill);
            }
        }

        // Get assigned students with their status and result
        $students = $db->table('bk_assignment_students bas')
            ->select('bas.id as assignment_student_id, bas.status as student_status, bas.started_at, bas.completed_at, bas.last_saved_at, s.id as student_id, s.nis, s.nisn, s.name as student_name, s.gender, cl.name as current_class_name, bsr.id as result_id, bsr.total_score, bsr.total_max_score, bsr.percentage, bsr.overall_level')
            ->join('students s', 's.id = bas.student_id')
            ->join('student_records sr', 'sr.student_id = s.id AND LOWER(sr.status) = \'aktif\'', 'left')
            ->join('classes cl', 'cl.id = sr.class_id', 'left')
            ->join('bk_student_results bsr', 'bsr.assignment_student_id = bas.id', 'left')
            ->where('bas.assignment_id', $id)
            ->groupBy('bas.id')
            ->orderBy('s.name', 'ASC')
            ->get()->getResultArray();

        // Stats
        $totalStudents = count($students);
        $completedCount = 0;
        $inProgressCount = 0;
        $assignedCount = 0;

        foreach ($students as $st) {
            $stStatus = strtoupper($st['student_status'] ?? '');
            if ($stStatus === 'COMPLETED') {
                $completedCount++;
            } elseif ($stStatus === 'IN_PROGRESS') {
                $inProgressCount++;
            } else {
                $assignedCount++;
            }
        }

        $completionPercentage = $totalStudents > 0 ? round(($completedCount / $totalStudents) * 100, 1) : 0;

        $data = [
            'title'                => 'Progress Penugasan: ' . ($assignment['title'] ?? 'Asesmen BK'),
            'active_menu'          => 'bk',
            'assignment'           => $assignment,
            'students'             => $students,
            'totalStudents'        => $totalStudents,
            'completedCount'       => $completedCount,
            'inProgressCount'      => $inProgressCount,
            'assignedCount'        => $assignedCount,
            'completionPercentage' => $completionPercentage,
        ];

        return view('admin/bk/assessment/progress', $data);
    }
}
