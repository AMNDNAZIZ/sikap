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
        $data = [
            'title' => 'Buat Instrumen Asesmen Baru',
            'active_menu' => 'bk',
        ];
        return view('admin/bk/assessment/create', $data);
    }

    public function storeInstrument()
    {
        $title = $this->request->getPost('title');
        
        $data = [
            'title'              => $title,
            'description'        => $this->request->getPost('description'),
            'assessment_type'    => $this->request->getPost('assessment_type'),
            'academic_year_id'   => session('academic_year_id') ?? 1,
            'target_level'       => $this->request->getPost('target_level'),
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
        $data = [
            'instrument_id' => $this->request->getPost('instrument_id'),
            'domain_id'     => $this->request->getPost('domain_id') ?: null,
            'question_text' => $this->request->getPost('question_text'),
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

    // --- PENUGASAN (ASSIGNMENTS) ---

    public function assignments()
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        
        $data = [
            'title'       => 'Penugasan Asesmen BK',
            'active_menu' => 'bk',
            'assignments' => $assignmentModel->select('bk_assignments.*, bk_instruments.title as instrument_title')
                                             ->join('bk_instruments', 'bk_instruments.id = bk_assignments.instrument_id')
                                             ->orderBy('bk_assignments.created_at', 'DESC')
                                             ->findAll(),
            'instruments' => $this->instrumentModel->where('status', 'published')->findAll(),
            'classes'     => (new \App\Models\ClassModel())->where('is_active', 1)->findAll()
        ];
        
        return view('admin/bk/assessment/assignments', $data);
    }

    public function storeAssignment()
    {
        $assignmentModel = new \App\Models\BkAssignmentModel();
        
        $instrument_id = $this->request->getPost('instrument_id');
        $target_type   = $this->request->getPost('target_type');
        $target_id     = $this->request->getPost('target_id'); // e.g. class_id
        $academic_year_id = session('academic_year_id') ?? 1;

        $assignmentData = [
            'instrument_id'    => $instrument_id,
            'title'            => $this->request->getPost('title') ?: $this->instrumentModel->find($instrument_id)['title'],
            'target_type'      => $target_type,
            'target_id'        => $target_type === 'class' ? $target_id : null,
            'academic_year_id' => $academic_year_id,
            'start_date'       => $this->request->getPost('start_date'),
            'end_date'         => $this->request->getPost('end_date'),
            'status'           => 'active',
            'assigned_by'      => session('user_id'),
        ];
        
        $assignment_id = $assignmentModel->insert($assignmentData);

        // POPULATE STUDENTS
        $assignmentStudentModel = new \App\Models\BkAssignmentStudentModel();
        $db = \Config\Database::connect();
        $builder = $db->table('student_records sr')
                      ->select('sr.student_id')
                      ->where('sr.academic_year_id', $academic_year_id)
                      ->where('sr.status', 'aktif');
                      
        if ($target_type === 'class') {
            $builder->where('sr.class_id', $target_id);
        }

        $students = $builder->get()->getResultArray();
        
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

        return redirect()->to('admin/bk/pemetaan/penugasan')->with('success', 'Penugasan berhasil dibuat dan didistribusikan ke ' . count($insertData) . ' siswa.');
    }

    // --- AJAX RESULT DETAIL ---
    
    public function studentResult($result_id)
    {
        $db = \Config\Database::connect();
        
        $result = $db->table('bk_student_results bsr')
            ->select('bsr.*, s.name as student_name, s.nisn, cl.name as class_name, bi.title')
            ->join('students s', 's.id = bsr.student_id')
            ->join('student_records sr', 'sr.student_id = s.id AND sr.status = \'aktif\' AND sr.academic_year_id = ' . (int)(session('academic_year_id') ?? 1))
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
        $academic_year_id = session('academic_year_id') ?? 1;

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
    
    public function deleteInstrument($id)
    {
        $this->instrumentModel->delete($id);
        return redirect()->back()->with('success', 'Instrumen berhasil dihapus.');
    }
    
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
        return redirect()->back()->with('error', 'Fitur preview belum diimplementasikan.');
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
        return redirect()->back()->with('error', 'Fitur lihat progress belum diimplementasikan.');
    }
}
