<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SubjectModel;
use App\Models\MapelMasterModel;
use App\Models\CpMasterModel;
use App\Models\JenjangMasterModel;
use App\Models\TujuanPembelajaranModel;
use App\Models\AlurTujuanPembelajaranModel;
use App\Models\AtpElemenModel;
use App\Models\SchoolModel;
use App\Models\ProsemDistributionModel;
use App\Models\TeacherModel;
use App\Models\TeachingAssignmentModel;

class AdministrasiGuru extends BaseController
{
    protected $subjectModel;
    protected $mapelMasterModel;
    protected $cpMasterModel;
    protected $jenjangMasterModel;
    protected $tpModel;
    protected $atpModel;
    protected $atpElemenModel;
    protected $schoolModel;
    protected $prosemModel;
    protected $teacherModel;
    protected $teachingAssignmentModel;
    protected $db;

    public function __construct()
    {
        $this->subjectModel = new SubjectModel();
        $this->mapelMasterModel = new MapelMasterModel();
        $this->cpMasterModel = new CpMasterModel();
        $this->jenjangMasterModel = new JenjangMasterModel();
        $this->tpModel = new TujuanPembelajaranModel();
        $this->atpModel = new AlurTujuanPembelajaranModel();
        $this->atpElemenModel = new AtpElemenModel();
        $this->schoolModel = new SchoolModel();
        $this->prosemModel = new ProsemDistributionModel();
        $this->teacherModel = new TeacherModel();
        $this->teachingAssignmentModel = new TeachingAssignmentModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $data['title'] = 'Administrasi Guru';
        // Get school level from profile
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $data['school_level'] = $school['level'] ?? 1; // 1: SD, 2: SMP
        
        return view('admin/administrasi_guru/index', $data);
    }

    public function monitoring()
    {
        // 1. Check permission - Only Admin (1) and Kepsek (2)
        $user = session()->get('user');
        if (!$user || !in_array($user['role_id'], [1, 2])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        $data['title'] = 'Monitoring Administrasi Guru';

        // 2. Get active academic year
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : null;
        $data['active_year'] = $activeYear;

        // 3. Filter inputs
        $filterTeacher = $this->request->getGet('teacher_id');
        $filterClass   = $this->request->getGet('class_id');
        $filterSubject = $this->request->getGet('subject_id');

        $data['filter_teacher'] = $filterTeacher;
        $data['filter_class']   = $filterClass;
        $data['filter_subject'] = $filterSubject;

        // 4. Dropdowns data (all teachers, classes, and mapped subjects)
        $data['teachers'] = $this->teacherModel->orderBy('name', 'ASC')->findAll();
        $data['classes']  = $this->db->table('classes')->orderBy('name', 'ASC')->get()->getResultArray();
        $data['subjects'] = $this->subjectModel->where('mapel_master_id !=', null)->orderBy('name', 'ASC')->findAll();

        if (!$activeYearId) {
            $data['assignments'] = [];
            $data['pager'] = null;
            $data['atp_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['promes_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            return view('admin/administrasi_guru/monitoring', $data);
        }

        // 5. Query ALL assignments (plotting pengajaran) for active academic year
        $query = $this->teachingAssignmentModel
            ->select('teaching_assignments.id, teaching_assignments.teacher_id, teaching_assignments.class_id, teaching_assignments.subject_id, teachers.name as teacher_name, classes.name as class_name, classes.level as class_level, subjects.name as subject_name')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->where('teaching_assignments.academic_year_id', $activeYearId);

        if ($filterTeacher) {
            $query->where('teaching_assignments.teacher_id', $filterTeacher);
        }
        if ($filterClass) {
            $query->where('teaching_assignments.class_id', $filterClass);
        }
        if ($filterSubject) {
            $query->where('teaching_assignments.subject_id', $filterSubject);
        }

        $allAssignments = $query->orderBy('teachers.name', 'ASC')
            ->orderBy('classes.name', 'ASC')
            ->orderBy('subjects.name', 'ASC')
            ->findAll(); // Get all matching results to calculate statistics

        // 6. Pre-fetch multi-guru map for fast lookup
        $multiGuruCounts = $this->db->table('teaching_assignments ta')
            ->select('ta.subject_id, c.level as class_level, COUNT(DISTINCT ta.teacher_id) as teacher_count')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.academic_year_id', $activeYearId)
            ->groupBy('ta.subject_id, c.level')
            ->get()->getResultArray();

        $multiGuruMap = [];
        foreach ($multiGuruCounts as $mg) {
            $key = $mg['subject_id'] . '_' . $mg['class_level'];
            $multiGuruMap[$key] = ($mg['teacher_count'] > 1);
        }

        // Statistics counters
        $atpStats = ['complete' => 0, 'partial' => 0, 'empty' => 0];
        $promesStats = ['complete' => 0, 'partial' => 0, 'empty' => 0];

        // 7. Calculate status for all assignments
        foreach ($allAssignments as &$row) {
            $classId = $row['class_id'];
            $subjectId = $row['subject_id'];
            $classLevel = $row['class_level'];

            $key = $subjectId . '_' . $classLevel;
            $isMultiGuru = $multiGuruMap[$key] ?? false;

            // Fetch ATPs
            $atpQuery = $this->db->table('alur_tujuan_pembelajaran');
            if ($isMultiGuru) {
                $atpQuery->where('class_id', $classId);
            } else {
                $classIdsAtLevelQuery = $this->db->table('classes')
                    ->select('id')
                    ->where('level', $classLevel)
                    ->get()->getResultArray();
                $classIds = array_column($classIdsAtLevelQuery, 'id') ?: [0];
                $atpQuery->whereIn('class_id', $classIds);
            }

            $atpRecords = $atpQuery->where('subject_id', $subjectId)
                ->get()->getResultArray();

            $atpCount = count($atpRecords);
            $sem1Count = 0;
            $sem2Count = 0;
            $totalAllocatedJp = 0;
            $atpIds = [];
            
            $sem1Allocated = 0;
            $sem2Allocated = 0;
            $atpIdsSem1 = [];
            $atpIdsSem2 = [];

            foreach ($atpRecords as $atp) {
                $atpIds[] = $atp['id'];
                $totalAllocatedJp += (int)($atp['alokasi_waktu'] ?? 0);
                if ($atp['semester'] == 1) {
                    $sem1Count++;
                    $sem1Allocated += (int)($atp['alokasi_waktu'] ?? 0);
                    $atpIdsSem1[] = $atp['id'];
                } elseif ($atp['semester'] == 2) {
                    $sem2Count++;
                    $sem2Allocated += (int)($atp['alokasi_waktu'] ?? 0);
                    $atpIdsSem2[] = $atp['id'];
                }
            }

            // ATP Status Logic
            if ($atpCount == 0) {
                $row['atp_status'] = 'empty';
                $row['atp_info'] = 'Kosong';
                $atpStats['empty']++;
            } elseif ($sem1Count >= 2 && $sem2Count >= 2) {
                $row['atp_status'] = 'complete';
                $row['atp_info'] = "Selesai (Sem1: {$sem1Count}, Sem2: {$sem2Count})";
                $atpStats['complete']++;
            } else {
                $row['atp_status'] = 'partial';
                $row['atp_info'] = "Selesai sebagian (Sem1: {$sem1Count}, Sem2: {$sem2Count})";
                $atpStats['partial']++;
            }

            // Promes Status Logic
            $sem1Distributed = 0;
            $sem2Distributed = 0;

            if (!empty($atpIdsSem1)) {
                $distQuery1 = $this->db->table('prosem_distributions')
                    ->selectSum('jp')
                    ->whereIn('atp_id', $atpIdsSem1)
                    ->get()->getRowArray();
                $sem1Distributed = (int)($distQuery1['jp'] ?? 0);
            }
            if (!empty($atpIdsSem2)) {
                $distQuery2 = $this->db->table('prosem_distributions')
                    ->selectSum('jp')
                    ->whereIn('atp_id', $atpIdsSem2)
                    ->get()->getRowArray();
                $sem2Distributed = (int)($distQuery2['jp'] ?? 0);
            }

            $totalDistributedJp = $sem1Distributed + $sem2Distributed;

            $isSem1Complete = ($sem1Allocated > 0 && $sem1Distributed >= $sem1Allocated);
            $isSem2Complete = ($sem2Allocated > 0 && $sem2Distributed >= $sem2Allocated);

            if ($atpCount == 0) {
                $row['promes_status'] = 'empty';
                $row['promes_info'] = 'Kosong';
                $promesStats['empty']++;
            } elseif ($totalDistributedJp == 0) {
                $row['promes_status'] = 'empty';
                $row['promes_info'] = "Belum terdistribusi (0/{$totalAllocatedJp} JP)";
                $promesStats['empty']++;
            } elseif ($isSem1Complete && $isSem2Complete) {
                $row['promes_status'] = 'complete';
                $row['promes_info'] = "Selesai ({$totalDistributedJp}/{$totalAllocatedJp} JP)";
                $promesStats['complete']++;
            } else {
                $row['promes_status'] = 'partial';
                $row['promes_info'] = "Selesai sebagian (Sem1: {$sem1Distributed}/{$sem1Allocated}, Sem2: {$sem2Distributed}/{$sem2Allocated} JP)";
                $promesStats['partial']++;
            }
        }
        unset($row);

        // 8. Manual Pagination
        $totalItems = count($allAssignments);
        $perPage = 50;
        $currentPage = (int)($this->request->getGet('page') ?? 1);
        if ($currentPage < 1) $currentPage = 1;
        $offset = ($currentPage - 1) * $perPage;

        $pagedAssignments = array_slice($allAssignments, $offset, $perPage);

        // Load pager service
        $pager = \Config\Services::pager();
        $pager->setPath('admin/administrasi-guru/monitoring');

        $data['assignments'] = $pagedAssignments;
        $data['pager'] = $pager;
        $data['page'] = $currentPage;
        $data['perPage'] = $perPage;
        $data['total'] = $totalItems;
        $data['atp_stats'] = $atpStats;
        $data['promes_stats'] = $promesStats;

        return view('admin/administrasi_guru/monitoring', $data);
    }

    public function mapping()
    {
        // ✅ Check permission - Kepsek can view
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $isReadOnly = ($roleId == 2); // Kepsek is read-only
        
        $data['title'] = 'Mapping Mata Pelajaran';
        $data['isReadOnly'] = $isReadOnly;
        $data['subjects'] = $this->subjectModel->findAll();
        
        // Get school level to filter mapel master
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $levelId = $school['level'] ?? 1;
        
        $data['mapel_master'] = $this->mapelMasterModel->where('jenjang_id', $levelId)->findAll();
        
        return view('admin/administrasi_guru/mapping', $data);
    }

    public function updateMapping()
    {
        // ✅ Check permission - Kepsek cannot update
        $user = session()->get('user');
        if (($user['role_id'] ?? null) == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk mengubah data.');
        }
        
        $mappings = $this->request->getPost('mapping');
        
        if ($mappings) {
            foreach ($mappings as $subjectId => $masterId) {
                if ($masterId === '') $masterId = null;
                $this->subjectModel->update($subjectId, ['mapel_master_id' => $masterId]);
            }
        }
        
        return redirect()->to(base_url('admin/administrasi-guru/mapping'))->with('success', 'Mapping mata pelajaran berhasil diperbarui.');
    }

    public function cp()
    {
        $data['title'] = 'Capaian Pembelajaran (CP)';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['subject_not_mapped'] = false;
        $data['subject_name'] = '';
        $data['mapping_mismatch'] = false;
        
        // Get current school level
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;
        
        if ($subjectId) {
            $subject = $this->subjectModel->find($subjectId);
            $data['subject_name'] = $subject['name'] ?? '';
            
            // Check if subject is mapped to mapel_master
            if (empty($subject['mapel_master_id'])) {
                $data['subject_not_mapped'] = true;
                $data['cp_list'] = [];
            } else {
                // Validate that mapel_master belongs to current school level
                $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
                
                if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                    // Mapping exists but doesn't match school level
                    $data['mapping_mismatch'] = true;
                    $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    } : 'Unknown';
                    $data['current_jenjang'] = match((int)$schoolLevel) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    };
                    $data['cp_list'] = [];
                } else {
                    $query = $this->cpMasterModel->where('mapel_master_id', $subject['mapel_master_id']);
                    
                    // Filter Fase untuk selain Admin dan Kepsek (Role Guru = 3)
                    if ($filters['role_id'] == 3) {
                        if (!empty($filters['auto_fase'])) {
                            $query->where('fase', $filters['auto_fase']);
                        } else {
                            // Jika belum pilih kelas, tampilkan berdasarkan semua fase dari kelas-kelas yang diampu
                            $assignedPhases = [];
                            foreach ($filters['classes'] as $c) {
                                $lvl = (int)$c['level'];
                                if ($lvl >= 1 && $lvl <= 2) $assignedPhases[] = 'A';
                                elseif ($lvl >= 3 && $lvl <= 4) $assignedPhases[] = 'B';
                                elseif ($lvl >= 5 && $lvl <= 6) $assignedPhases[] = 'C';
                                elseif ($lvl >= 7 && $lvl <= 9) $assignedPhases[] = 'D';
                                elseif ($lvl == 10) $assignedPhases[] = 'E';
                                elseif ($lvl >= 11 && $lvl <= 12) $assignedPhases[] = 'F';
                            }
                            if (!empty($assignedPhases)) {
                                $query->whereIn('fase', array_unique($assignedPhases));
                            }
                        }
                    }
                    
                    $data['cp_list'] = $query->findAll();
                }
            }
        } else {
            $data['cp_list'] = [];
        }
        
        return view('admin/administrasi_guru/cp', $data);
    }

    public function tp()
    {
        $data['title'] = 'Tujuan Pembelajaran (TP)';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['subject_not_mapped'] = false;
        $data['subject_name'] = '';
        $data['mapping_mismatch'] = false;
        
        // Get current school level
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;
        
        if ($subjectId) {
            $subject = $this->subjectModel->find($subjectId);
            $data['subject_name'] = $subject['name'] ?? '';
            
            // Check if subject is mapped to mapel_master
            if (empty($subject['mapel_master_id'])) {
                $data['subject_not_mapped'] = true;
                $data['cp_list'] = [];
                $data['tp_list'] = [];
            } else {
                // Validate that mapel_master belongs to current school level
                $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
                
                if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                    // Mapping exists but doesn't match school level
                    $data['mapping_mismatch'] = true;
                    $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    } : 'Unknown';
                    $data['current_jenjang'] = match((int)$schoolLevel) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    };
                    $data['cp_list'] = [];
                    $data['tp_list'] = [];
                } else {
                    $queryCp = $this->cpMasterModel->where('mapel_master_id', $subject['mapel_master_id']);
                    if ($filters['role_id'] == 3) {
                        if (!empty($filters['auto_fase'])) {
                            $queryCp->where('fase', $filters['auto_fase']);
                        } else {
                            $assignedPhases = [];
                            foreach ($filters['classes'] as $c) {
                                $lvl = (int)$c['level'];
                                if ($lvl >= 1 && $lvl <= 2) $assignedPhases[] = 'A';
                                elseif ($lvl >= 3 && $lvl <= 4) $assignedPhases[] = 'B';
                                elseif ($lvl >= 5 && $lvl <= 6) $assignedPhases[] = 'C';
                                elseif ($lvl >= 7 && $lvl <= 9) $assignedPhases[] = 'D';
                                elseif ($lvl == 10) $assignedPhases[] = 'E';
                                elseif ($lvl >= 11 && $lvl <= 12) $assignedPhases[] = 'F';
                            }
                            if (!empty($assignedPhases)) {
                                $queryCp->whereIn('fase', array_unique($assignedPhases));
                            }
                        }
                    }
                    $data['cp_list'] = $queryCp->findAll();
                    $data['tp_list'] = $this->tpModel->where('subject_id', $subjectId)->findAll();
                }
            }
        } else {
            $data['cp_list'] = [];
            $data['tp_list'] = [];
        }
        
        return view('admin/administrasi_guru/tp', $data);
    }

    public function tpStore()
    {
        $data = [
            'subject_id'     => $this->request->getPost('subject_id'),
            'cp_master_id'   => $this->request->getPost('cp_master_id'),
            'elemen'         => $this->request->getPost('elemen'),
            'lingkup_materi' => $this->request->getPost('lingkup_materi'),
            'kode_tp'        => $this->request->getPost('kode_tp'),
            'deskripsi'      => $this->request->getPost('deskripsi'),
            'fase'           => $this->request->getPost('fase'),
            'kelas'          => $this->request->getPost('kelas'),
        ];
        
        $id = $this->request->getPost('id');
        if ($id) {
            $this->tpModel->update($id, $data);
            $msg = 'Tujuan Pembelajaran berhasil diperbarui.';
        } else {
            $this->tpModel->insert($data);
            $msg = 'Tujuan Pembelajaran berhasil ditambahkan.';
        }
        
        return redirect()->to(base_url('admin/administrasi-guru/tp?subject_id=' . $data['subject_id']))->with('success', $msg);
    }

    public function tpDelete($id)
    {
        $tp = $this->tpModel->find($id);
        if ($tp) {
            $this->tpModel->delete($id);
            return redirect()->to(base_url('admin/administrasi-guru/tp?subject_id=' . $tp['subject_id']))->with('success', 'Tujuan Pembelajaran berhasil dihapus.');
        }
        return redirect()->back();
    }

    public function atp()
    {
        $data['title'] = 'Alur Tujuan Pembelajaran (ATP)';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        $data['auto_fase'] = $filters['auto_fase'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['subject_not_mapped'] = false;
        $data['subject_name'] = '';
        $data['mapping_mismatch'] = false;
        
        // Get current school level
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;
        
        if ($subjectId) {
            $subject = $this->subjectModel->find($subjectId);
            $data['subject_name'] = $subject['name'] ?? '';
            
            // Check if subject is mapped to mapel_master
            if (empty($subject['mapel_master_id'])) {
                $data['subject_not_mapped'] = true;
                $data['cp_master_list'] = [];
                $data['available_phases'] = [];
                $data['atp_list'] = [];
            } else {
                // Validate that mapel_master belongs to current school level
                $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
                
                if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                    // Mapping exists but doesn't match school level
                    $data['mapping_mismatch'] = true;
                    $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    } : 'Unknown';
                    $data['current_jenjang'] = match((int)$schoolLevel) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    };
                    $data['cp_master_list'] = [];
                    $data['available_phases'] = [];
                    $data['atp_list'] = [];
                } else {
                    $queryCp = $this->cpMasterModel->where('mapel_master_id', $subject['mapel_master_id']);
                    if ($filters['role_id'] == 3) {
                        if (!empty($filters['auto_fase'])) {
                            $queryCp->where('fase', $filters['auto_fase']);
                        } else {
                            $assignedPhases = [];
                            foreach ($filters['classes'] as $c) {
                                $lvl = (int)$c['level'];
                                if ($lvl >= 1 && $lvl <= 2) $assignedPhases[] = 'A';
                                elseif ($lvl >= 3 && $lvl <= 4) $assignedPhases[] = 'B';
                                elseif ($lvl >= 5 && $lvl <= 6) $assignedPhases[] = 'C';
                                elseif ($lvl >= 7 && $lvl <= 9) $assignedPhases[] = 'D';
                                elseif ($lvl == 10) $assignedPhases[] = 'E';
                                elseif ($lvl >= 11 && $lvl <= 12) $assignedPhases[] = 'F';
                            }
                            if (!empty($assignedPhases)) {
                                $queryCp->whereIn('fase', array_unique($assignedPhases));
                            }
                        }
                    }
                    $cpList = $queryCp->findAll();
                    $data['cp_master_list'] = $cpList;
                    
                    $phases = array_unique(array_column($cpList, 'fase'));
                    sort($phases);
                    $data['available_phases'] = $phases;

                    // Cek apakah ada >1 guru di level ini untuk mapel ini
                    $classInfo    = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
                    $classLevel   = (int)($classInfo['level'] ?? 0);
                    $teacherCount = $this->db->table('teaching_assignments ta')
                        ->distinct()->select('ta.teacher_id')
                        ->join('classes c', 'c.id = ta.class_id')
                        ->where('ta.subject_id', $subjectId)
                        ->where('c.level', $classLevel)
                        ->get()->getResultArray();
                    $isMultiGuru = count($teacherCount) > 1;

                    // Cek apakah kelas ini punya ATP sendiri
                    $ownAtpCount = $this->atpModel
                        ->where('subject_id', $subjectId)
                        ->where('class_id', $classId)
                        ->countAllResults();

                    // Jika multi-guru dan kelas ini belum punya ATP,
                    // cek apakah ada kelas lain se-level yang sudah punya ATP
                    $data['is_multi_guru']     = $isMultiGuru;
                    $data['has_own_atp']       = $ownAtpCount > 0;
                    $data['source_class_id']   = null;
                    $data['source_class_name'] = null;
                    $data['classes_with_atp']  = [];

                    // Parameter: gunakan ATP dari kelas lain
                    $sourceClassId = $this->request->getGet('source_class_id');

                    if ($isMultiGuru) {
                        // Cari kelas se-level yang sudah punya ATP untuk mapel ini
                        $classesWithAtp = $this->db->table('classes c')
                            ->select('c.id, c.name')
                            ->join('alur_tujuan_pembelajaran atp', 'atp.class_id = c.id')
                            ->where('c.level', $classLevel)
                            ->where('atp.subject_id', $subjectId)
                            ->where('c.id !=', $classId)
                            ->groupBy('c.id')
                            ->get()->getResultArray();
                        $data['classes_with_atp'] = $classesWithAtp;

                        if ($sourceClassId && !$ownAtpCount) {
                            // Tampilkan ATP dari kelas sumber, tapi simpan info kelas asli
                            $sourceClass = $this->db->table('classes')->where('id', $sourceClassId)->get()->getRowArray();
                            $data['source_class_id']   = $sourceClassId;
                            $data['source_class_name'] = $sourceClass['name'] ?? '';
                            $effectiveClassId = $sourceClassId;
                        } else {
                            $effectiveClassId = $classId;
                        }
                    } else {
                        $effectiveClassId = $classId;
                    }

                    $atpQuery = $this->atpModel
                        ->select('alur_tujuan_pembelajaran.*')
                        ->where('alur_tujuan_pembelajaran.subject_id', $subjectId);

                    if ($classId) {
                        if ($isMultiGuru) {
                            // Multi-guru: filter spesifik per kelas efektif
                            $atpQuery->join('classes c_atp', 'c_atp.id = alur_tujuan_pembelajaran.class_id', 'left')
                                     ->where('c_atp.level', $classLevel)
                                     ->where('alur_tujuan_pembelajaran.class_id', $effectiveClassId);
                        } else {
                            // 1 guru: tampilkan semua ATP se-level
                            $atpQuery->join('classes c_atp', 'c_atp.id = alur_tujuan_pembelajaran.class_id', 'left')
                                     ->where('c_atp.level', $classLevel);
                        }
                    }

                    $atpList = $atpQuery
                        ->groupBy('alur_tujuan_pembelajaran.id')
                        ->orderBy('alur_tujuan_pembelajaran.semester', 'ASC')
                        ->orderBy('alur_tujuan_pembelajaran.urutan', 'ASC')
                        ->findAll();
                    foreach ($atpList as &$atp) {
                        // Load elemen CP untuk ATP ini
                        $elemenList = $this->db->table('atp_elemen ae')
                            ->select('ae.*, cp.elemen, cp.deskripsi as cp_deskripsi, cp.fase')
                            ->join('cp_master cp', 'cp.id = ae.cp_master_id')
                            ->where('ae.atp_id', $atp['id'])
                            ->orderBy('ae.urutan', 'ASC')
                            ->get()->getResultArray();
                        // Untuk tiap elemen, load TP-nya (PENTING: unset referensi setelah loop)
                        foreach ($elemenList as &$el) {
                            $el['tps'] = $this->tpModel->where('atp_elemen_id', $el['id'])->findAll();
                        }
                        unset($el); // WAJIB: mencegah bug referensi PHP
                        $atp['elemen_list'] = $elemenList;
                        // Backward compat: gabung semua TP dari semua elemen
                        $atp['tps'] = [];
                        foreach ($elemenList as $el) {
                            $atp['tps'] = array_merge($atp['tps'], $el['tps']);
                        }
                        // Fallback untuk data lama (tanpa atp_elemen)
                        if (empty($elemenList)) {
                            $atp['tps'] = $this->tpModel->where('atp_id', $atp['id'])->findAll();
                        }
                    }
                    unset($atp); // WAJIB: mencegah bug referensi PHP pada loop $atpList
                    $data['atp_list'] = $atpList;
                }
            }
        } else {
            $data['cp_master_list'] = [];
            $data['available_phases'] = [];
            $data['atp_list'] = [];
        }
        
        return view('admin/administrasi_guru/atp', $data);
    }

    public function atpStore()
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah/mengubah data.');
        }

        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');

        if (empty($classId) || empty($subjectId)) {
            return redirect()->back()->withInput()->with('error', 'Silakan pilih Kelas dan Mata Pelajaran terlebih dahulu dari filter.');
        }

        // Guru Ownership Check
        if ($user['role_id'] == 3) {
            $isAssigned = $this->db->table('teaching_assignments')
                ->where('teacher_id', $user['related_id'])
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->countAllResults();

            if (!$isAssigned) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk mengelola data pada kelas/mata pelajaran ini.');
            }
        }

        $id = $this->request->getPost('id');
        $lingkupMateri = $this->request->getPost('lingkup_materi');
        $alokasiWaktu  = $this->request->getPost('alokasi_waktu');
        $semester      = $this->request->getPost('semester');
        $urutan        = $this->request->getPost('urutan');
        $kelas         = $this->request->getPost('kelas');

        $this->db->transStart();

        // 1. Simpan/update ATP header (tp_id nullable, diisi setelah TP tersimpan)
        $atpData = [
            'class_id'       => $classId,
            'subject_id'     => $subjectId,
            'lingkup_materi' => $lingkupMateri,
            'urutan'         => $urutan,
            'semester'       => $semester,
            'alokasi_waktu'  => $alokasiWaktu,
        ];

        if ($id) {
            $this->atpModel->update($id, $atpData);
            $atpId = $id;
            // Hapus elemen lama beserta TP-nya
            $oldElemens = $this->atpElemenModel->where('atp_id', $atpId)->findAll();
            foreach ($oldElemens as $oe) {
                $this->tpModel->where('atp_elemen_id', $oe['id'])->delete();
            }
            $this->atpElemenModel->where('atp_id', $atpId)->delete();
            $msg = 'ATP berhasil diperbarui.';
        } else {
            // Insert dengan tp_id = 0 sementara (akan diupdate setelah TP disimpan)
            $this->atpModel->insert(array_merge($atpData, ['tp_id' => 0]));
            $atpId = $this->atpModel->getInsertID();
            $msg = 'ATP berhasil disimpan.';
        }

        // 2. Simpan elemen-elemen CP
        $elemenCps      = $this->request->getPost('elemen_cp') ?? [];
        $tpKodesAll     = $this->request->getPost('tp_kode') ?? [];
        $tpDeskripsiAll = $this->request->getPost('tp_deskripsi') ?? [];
        $firstTpId      = null;

        // Normalisasi index — JS reindexElemen() memakai indeks berurutan
        $elemenCps = array_values($elemenCps);

        foreach ($elemenCps as $idx => $cpMasterId) {
            if (empty($cpMasterId)) continue;

            $cp = $this->cpMasterModel->find($cpMasterId);
            if (!$cp) continue;

            $this->atpElemenModel->insert([
                'atp_id'       => $atpId,
                'cp_master_id' => $cpMasterId,
                'urutan'       => $idx + 1,
            ]);
            $atpElemenId = $this->atpElemenModel->getInsertID();

            // 3. Simpan TP untuk elemen ini
            $tpKodes      = isset($tpKodesAll[$idx]) ? array_values((array)$tpKodesAll[$idx]) : [];
            $tpDeskripsis = isset($tpDeskripsiAll[$idx]) ? array_values((array)$tpDeskripsiAll[$idx]) : [];

            foreach ($tpKodes as $tpIdx => $kode) {
                $kode     = trim($kode ?? '');
                $deskripsi = trim($tpDeskripsis[$tpIdx] ?? '');
                if (empty($kode) || empty($deskripsi)) continue;

                $this->tpModel->insert([
                    'subject_id'     => $subjectId,
                    'cp_master_id'   => $cpMasterId,
                    'atp_id'         => $atpId,
                    'atp_elemen_id'  => $atpElemenId,
                    'elemen'         => $cp['elemen'] ?? '',
                    'lingkup_materi' => $lingkupMateri,
                    'kode_tp'        => $kode,
                    'deskripsi'      => $deskripsi,
                    'fase'           => $cp['fase'] ?? '',
                    'kelas'          => $kelas,
                ]);

                if ($firstTpId === null) {
                    $firstTpId = $this->tpModel->getInsertID();
                }
            }
        }

        // Update tp_id di header ATP (backward compat)
        $this->atpModel->update($atpId, ['tp_id' => $firstTpId ?? 0]);

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            log_message('error', '[atpStore] Transaksi gagal. atpId=' . ($atpId ?? 'null') . ' error=' . $this->db->error()['message']);
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan ATP. Silakan coba lagi.');
        }

        return redirect()->to(base_url('admin/administrasi-guru/atp?class_id=' . $classId . '&subject_id=' . $subjectId))->with('success', $msg);
    }

    public function atpDelete($id)
    {
        $atp = $this->atpModel->find($id);
        if ($atp) {
            $user = session()->get('user');

            // Cegah guru menghapus ATP milik kelas lain
            if ($user['role_id'] == 3) {
                if ((int)$atp['class_id'] !== (int)$atp['class_id']) {
                    return redirect()->back()->with('error', 'Anda tidak dapat menghapus ATP ini.');
                }
                // Pastikan guru hanya hapus ATP milik kelasnya sendiri
                $isOwner = $this->db->table('teaching_assignments')
                    ->where('teacher_id', $user['related_id'])
                    ->where('class_id', $atp['class_id'])
                    ->where('subject_id', $atp['subject_id'])
                    ->countAllResults();
                if (!$isOwner) {
                    return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus ATP ini.');
                }
            }

            // Hapus TP dari tiap elemen lalu hapus elemen
            $oldElemens = $this->atpElemenModel->where('atp_id', $id)->findAll();
            foreach ($oldElemens as $oe) {
                $this->tpModel->where('atp_elemen_id', $oe['id'])->delete();
            }
            $this->atpElemenModel->where('atp_id', $id)->delete();
            // Hapus TP lama (data lama tanpa atp_elemen_id)
            $this->tpModel->where('atp_id', $id)->delete();
            // Hapus ATP
            $this->atpModel->delete($id);
            return redirect()->to(base_url('admin/administrasi-guru/atp?class_id=' . $atp['class_id'] . '&subject_id=' . $atp['subject_id']))->with('success', 'Alur berhasil dihapus.');
        }
        return redirect()->back();
    }

    /**
     * Salin semua ATP dari kelas sumber ke kelas tujuan (deep copy).
     * ATP baru disimpan permanen dengan class_id kelas tujuan.
     * Data asli tidak terpengaruh sama sekali.
     */
    public function atpCopyFromSource()
    {
        $user        = session()->get('user');
        $sourceClassId = (int) $this->request->getPost('source_class_id');
        $targetClassId = (int) $this->request->getPost('target_class_id');
        $subjectId     = (int) $this->request->getPost('subject_id');

        if (!$sourceClassId || !$targetClassId || !$subjectId) {
            return redirect()->back()->with('error', 'Parameter tidak lengkap.');
        }

        // Hak akses: guru hanya bisa menyalin ke kelasnya sendiri
        if ($user['role_id'] == 3) {
            $isAssigned = $this->db->table('teaching_assignments')
                ->where('teacher_id', $user['related_id'])
                ->where('class_id', $targetClassId)
                ->where('subject_id', $subjectId)
                ->countAllResults();
            if (!$isAssigned) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke kelas tujuan.');
            }
        }

        // Cek kelas tujuan sudah punya ATP — cegah duplikasi
        $alreadyHasAtp = $this->atpModel
            ->where('subject_id', $subjectId)
            ->where('class_id', $targetClassId)
            ->countAllResults();
        if ($alreadyHasAtp > 0) {
            return redirect()->back()->with('error', 'Kelas ini sudah memiliki ATP. Hapus ATP yang ada terlebih dahulu jika ingin menyalin ulang.');
        }

        // Ambil semua ATP dari kelas sumber
        $sourceAtps = $this->atpModel
            ->where('subject_id', $subjectId)
            ->where('class_id', $sourceClassId)
            ->orderBy('semester', 'ASC')
            ->orderBy('urutan', 'ASC')
            ->findAll();

        if (empty($sourceAtps)) {
            return redirect()->back()->with('error', 'Tidak ada ATP di kelas sumber untuk disalin.');
        }

        $this->db->transStart();

        foreach ($sourceAtps as $srcAtp) {
            // Salin header ATP dengan class_id baru
            $newAtpData = [
                'subject_id'     => $subjectId,
                'class_id'       => $targetClassId,
                'cp_master_id'   => $srcAtp['cp_master_id'],
                'lingkup_materi' => $srcAtp['lingkup_materi'],
                'tp_id'          => 0,
                'alur_tujuan'    => $srcAtp['alur_tujuan'] ?? null,
                'urutan'         => $srcAtp['urutan'],
                'semester'       => $srcAtp['semester'],
                'alokasi_waktu'  => $srcAtp['alokasi_waktu'],
            ];
            $this->atpModel->insert($newAtpData);
            $newAtpId = $this->atpModel->getInsertID();

            // Salin elemen CP
            $srcElemens = $this->atpElemenModel->where('atp_id', $srcAtp['id'])->orderBy('urutan', 'ASC')->findAll();
            $firstTpId  = null;

            foreach ($srcElemens as $srcEl) {
                $this->atpElemenModel->insert([
                    'atp_id'       => $newAtpId,
                    'cp_master_id' => $srcEl['cp_master_id'],
                    'urutan'       => $srcEl['urutan'],
                ]);
                $newElemenId = $this->atpElemenModel->getInsertID();

                // Salin TP untuk elemen ini
                $srcTps = $this->tpModel->where('atp_elemen_id', $srcEl['id'])->findAll();
                foreach ($srcTps as $srcTp) {
                    $this->tpModel->insert([
                        'subject_id'     => $subjectId,
                        'cp_master_id'   => $srcTp['cp_master_id'],
                        'atp_id'         => $newAtpId,
                        'atp_elemen_id'  => $newElemenId,
                        'elemen'         => $srcTp['elemen'],
                        'lingkup_materi' => $srcTp['lingkup_materi'],
                        'kode_tp'        => $srcTp['kode_tp'],
                        'deskripsi'      => $srcTp['deskripsi'],
                        'fase'           => $srcTp['fase'],
                        'kelas'          => $srcTp['kelas'],
                    ]);
                    if ($firstTpId === null) {
                        $firstTpId = $this->tpModel->getInsertID();
                    }
                }
            }

            // Update tp_id backward compat
            $this->atpModel->update($newAtpId, ['tp_id' => $firstTpId ?? 0]);
        }

        $this->db->transComplete();

        if (!$this->db->transStatus()) {
            return redirect()->back()->with('error', 'Gagal menyalin ATP. Silakan coba lagi.');
        }

        return redirect()->to(base_url("admin/administrasi-guru/atp?class_id={$targetClassId}&subject_id={$subjectId}"))
            ->with('success', 'ATP berhasil disalin secara permanen ke kelas ini. Anda dapat mengedit sesuai kebutuhan.');
    }

    public function cpMasterIndex()
    {
        // ✅ Check permission - Kepsek can view
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;
        $isReadOnly = ($roleId == 2); // Kepsek is read-only
        
        $data['title'] = 'Data Master CP';
        $data['isReadOnly'] = $isReadOnly;
        
        // Get school level to filter mapel
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;

        // Get filter parameters
        $filterMapel = $this->request->getGet('mapel_id');
        $filterFase = $this->request->getGet('fase');
        
        // Get sorting parameters
        $sortBy = $this->request->getGet('sort') ?? 'mapel_nama';
        $sortOrder = $this->request->getGet('order') ?? 'asc';
        
        // Validate sort parameters
        $allowedSort = ['mapel_nama', 'fase', 'elemen', 'tahun'];
        if (!in_array($sortBy, $allowedSort)) {
            $sortBy = 'mapel_nama';
        }
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
            $sortOrder = 'asc';
        }

        // Build query with filters
        $query = $this->cpMasterModel
            ->select('cp_master.*, mapel_master.nama as mapel_nama, jenjang_master.nama as jenjang_nama')
            ->join('mapel_master', 'mapel_master.id = cp_master.mapel_master_id')
            ->join('jenjang_master', 'jenjang_master.id = mapel_master.jenjang_id')
            ->where('mapel_master.jenjang_id', $schoolLevel);
        
        // Apply mapel filter if selected
        if ($filterMapel) {
            $query->where('cp_master.mapel_master_id', $filterMapel);
        }
        
        // Apply fase filter if selected
        if ($filterFase) {
            $query->where('cp_master.fase', $filterFase);
        }
        
        $data['cp_master'] = $query->orderBy($sortBy, $sortOrder)->paginate(20);
        $data['pager'] = $this->cpMasterModel->pager;
        
        // Filter mapel by school level
        $data['mapel_master'] = $this->mapelMasterModel->where('jenjang_id', $schoolLevel)->findAll();
        
        // Add school level info
        $data['school_level'] = $schoolLevel;
        $data['school_level_name'] = match((int)$schoolLevel) {
            1 => 'SD / Sederajat',
            2 => 'SMP / Sederajat',
            3 => 'SMA / Sederajat',
            default => 'Unknown'
        };
        
        // Pass filter and sorting info to view
        $data['filter_mapel'] = $filterMapel;
        $data['filter_fase'] = $filterFase;
        $data['current_sort'] = $sortBy;
        $data['current_order'] = $sortOrder;
        
        return view('admin/administrasi_guru/cp_master', $data);
    }

    public function cpMasterStore()
    {
        // ✅ Check permission - Kepsek cannot create/update
        $user = session()->get('user');
        if (($user['role_id'] ?? null) == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah/mengubah data.');
        }
        
        // Get school level for validation
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;
        
        $id = $this->request->getPost('id');
        $mapelMasterId = $this->request->getPost('mapel_master_id');
        
        // Validate that selected mapel belongs to school level
        $mapel = $this->mapelMasterModel->find($mapelMasterId);
        if (!$mapel || (int)$mapel['jenjang_id'] !== (int)$schoolLevel) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Mata pelajaran tidak sesuai dengan level sekolah. Silakan pilih mapel yang sesuai.');
        }
        
        $data = [
            'mapel_master_id' => $mapelMasterId,
            'elemen'          => $this->request->getPost('elemen'),
            'fase'            => $this->request->getPost('fase'),
            'deskripsi'       => $this->request->getPost('deskripsi'),
            'nomor_sk'        => $this->request->getPost('nomor_sk'),
            'tahun'           => $this->request->getPost('tahun'),
            'is_active'       => $this->request->getPost('is_active') ?? 1,
        ];

        // Check duplication (mapel + fase + elemen)
        $check = $this->cpMasterModel->where([
            'mapel_master_id' => $data['mapel_master_id'],
            'fase'            => $data['fase'],
            'elemen'          => $data['elemen']
        ]);
        if ($id) $check->where('id !=', $id);
        
        if ($check->first()) {
            return redirect()->back()->withInput()->with('error', 'CP dengan Elemen, Mapel, dan Fase ini sudah ada.');
        }

        if ($id) {
            $this->cpMasterModel->update($id, $data);
            $msg = 'CP Master berhasil diperbarui.';
        } else {
            $this->cpMasterModel->insert($data);
            $msg = 'CP Master berhasil ditambahkan.';
        }

        return redirect()->to(base_url('admin/administrasi-guru/cp-master'))->with('success', $msg);
    }

    public function cpMasterDelete($id)
    {
        // ✅ Check permission - Kepsek cannot delete
        $user = session()->get('user');
        if (($user['role_id'] ?? null) == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
        }
        
        $this->cpMasterModel->delete($id);
        return redirect()->to(base_url('admin/administrasi-guru/cp-master'))->with('success', 'CP Master berhasil dihapus.');
    }

    public function protaProsem()
    {
        $data['title'] = 'Program Tahunan & Semester';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['subject_not_mapped'] = false;
        $data['subject_name'] = '';
        $data['mapping_mismatch'] = false;
        
        // Get current school level
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;

        if ($subjectId) {
            $subject = $this->subjectModel->find($subjectId);
            $data['subject'] = $subject;
            $data['subject_name'] = $subject['name'] ?? '';
            $data['school'] = $this->schoolModel->getProfile();
            
            // Check if subject is mapped to mapel_master
            if (empty($subject['mapel_master_id'])) {
                $data['subject_not_mapped'] = true;
                $data['prota'] = [];
                $data['fase'] = '-';
                $data['kelas'] = '-';
                $data['teacher'] = null;
            } else {
                // Validate that mapel_master belongs to current school level
                $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
                
                if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                    // Mapping exists but doesn't match school level
                    $data['mapping_mismatch'] = true;
                    $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    } : 'Unknown';
                    $data['current_jenjang'] = match((int)$schoolLevel) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    };
                    $data['prota'] = [];
                    $data['fase'] = '-';
                    $data['kelas'] = '-';
                    $data['teacher'] = null;
                } else {
                    $assignment = $this->teachingAssignmentModel->where('subject_id', $subjectId);
                    if ($classId) $assignment->where('class_id', $classId);
                    $assignment = $assignment->first();
                    
                    $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;
                    
                    $protaResult = null;
                    if ($classId) {
                        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
                        $level = (int)($classInfo['level'] ?? 0);
                        $teacherFilter = $this->getTeacherClassFilter((int)$subjectId, $level, (int)$classId);
                        $protaResult = $this->loadAtpWithElemen($subjectId, $level, null, $teacherFilter);
                    } else {
                        $protaResult = $this->loadAtpWithElemen($subjectId);
                    }
                    $prota   = $protaResult['list'];
                    $allTps  = $protaResult['all_tps'];

                    $data['fase']  = !empty($allTps) ? ($allTps[0]['fase']  ?? '-') : '-';
                    $data['kelas'] = !empty($allTps) ? ($allTps[0]['kelas'] ?? '-') : '-';
                    $data['prota'] = $prota;
                }
            }
        } else {
            $data['prota'] = [];
            $data['fase'] = '-';
            $data['kelas'] = '-';
        }

        return view('admin/administrasi_guru/prota_prosem', $data);
    }

    public function modulAjar()
    {
        $data['title'] = 'Modul Ajar';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['subject_not_mapped'] = false;
        $data['subject_name'] = '';
        $data['mapping_mismatch'] = false;
        
        // Get current school level
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $schoolLevel = $school['level'] ?? 1;

        $user = session()->get('user');
        $data['gemini_api_key'] = '';
        $data['ai_provider'] = 'gemini';
        if ($user['role_id'] == 3) {
            $teacher = $this->teacherModel->find($user['related_id']);
            $data['gemini_api_key'] = $teacher['gemini_api_key'] ?? '';
            $data['ai_provider'] = $teacher['ai_provider'] ?? 'gemini';
        } elseif ($user['role_id'] == 1) {
            $userData = $this->db->table('users')->where('id', $user['id'])->get()->getRowArray();
            $data['gemini_api_key'] = $userData['gemini_api_key'] ?? '';
            $data['ai_provider'] = $userData['ai_provider'] ?? 'gemini';
        }

        if ($subjectId && $classId) {
            $subject = $this->subjectModel->find($subjectId);
            $data['subject_name'] = $subject['name'] ?? '';
            
            // Check if subject is mapped to mapel_master
            if (empty($subject['mapel_master_id'])) {
                $data['subject_not_mapped'] = true;
                $data['atp_list'] = [];
            } else {
                // Validate that mapel_master belongs to current school level
                $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
                
                if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                    // Mapping exists but doesn't match school level
                    $data['mapping_mismatch'] = true;
                    $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    } : 'Unknown';
                    $data['current_jenjang'] = match((int)$schoolLevel) {
                        1 => 'SD / Sederajat',
                        2 => 'SMP / Sederajat',
                        3 => 'SMA / Sederajat',
                        default => 'Unknown'
                    };
                    $data['atp_list'] = [];
                } else {
                    $classInfo   = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
                    $classLevel  = (int)($classInfo['level'] ?? 0);
                    $atpResult   = $this->loadAtpWithElemen((int)$subjectId, $classLevel);
                    $atpList     = $atpResult['list'];

                    $modulModel = new \App\Models\ModulAjarModel();
                    foreach ($atpList as &$atp) {
                        // Modul milik kelas ini
                        $modul = $modulModel
                            ->where('atp_id', $atp['id'])
                            ->where('class_id', $classId)
                            ->first();
                        $atp['modul'] = $modul;

                        // Jika kelas ini belum punya modul, cari modul dari kelas lain se-level
                        $atp['modul_source'] = null;
                        if (!$modul) {
                            $modulSource = $this->db->table('modul_ajar ma')
                                ->select('ma.id, ma.class_id, c.name as class_name, ma.teacher_id')
                                ->join('classes c', 'c.id = ma.class_id')
                                ->where('ma.atp_id', $atp['id'])
                                ->where('c.level', $classLevel)
                                ->where('ma.class_id !=', $classId)
                                ->get()->getRowArray();
                            $atp['modul_source'] = $modulSource;
                        }
                    }
                    unset($atp);
                    $data['atp_list'] = $atpList;
                }
            }
        } else {
            $data['atp_list'] = [];
        }

        return view('admin/administrasi_guru/modul_ajar', $data);
    }

    public function saveApiKey()
    {
        $key = $this->request->getPost('gemini_api_key');
        $provider = $this->request->getPost('ai_provider') ?? 'gemini';
        $user = session()->get('user');
        if ($user['role_id'] == 3) {
            $this->teacherModel->update($user['related_id'], ['gemini_api_key' => ltrim(rtrim($key)), 'ai_provider' => $provider]);
            return redirect()->back()->with('success', 'Konfigurasi API AI Guru berhasil disimpan.');
        } elseif ($user['role_id'] == 1) {
            $this->db->table('users')->where('id', $user['id'])->update(['gemini_api_key' => ltrim(rtrim($key)), 'ai_provider' => $provider]);
            return redirect()->back()->with('success', 'Konfigurasi API AI Admin berhasil disimpan.');
        }
        return redirect()->back()->with('error', 'Anda tidak diizinkan menyimpan API Key.');
    }

    public function generateModulAjar()
    {
        $atpId = $this->request->getPost('atp_id');
        $subjectId = $this->request->getPost('subject_id');
        
        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return redirect()->back()->with('error', 'Hanya guru atau admin yang dapat meng-generate Modul.');
        }
        
        if ($user['role_id'] == 3) {
            $teacher = $this->teacherModel->find($user['related_id']);
            $apiKey = $teacher['gemini_api_key'];
            $aiProvider = $teacher['ai_provider'] ?? 'gemini';
            $targetTeacherId = $user['related_id'];
        } else {
            // Admin
            $userData = $this->db->table('users')->where('id', $user['id'])->get()->getRowArray();
            $apiKey = $userData['gemini_api_key'];
            $aiProvider = $userData['ai_provider'] ?? 'gemini';
            
            // Resolve Teacher based on subject & class
            $assignment = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $this->atpModel->find($atpId)['class_id'])
                ->get()->getRowArray();
            
            if (!$assignment) {
                return redirect()->back()->with('error', 'Tidak ada guru yang diplot untuk Kelas dan Mapel ini. Modul tidak dapat digenerate karena membutuhkan identitas Guru Pengampu.');
            }
            $targetTeacherId = $assignment['teacher_id'];
        }

        if (empty($apiKey)) {
            return redirect()->back()->with('error', 'Anda belum mengatur Gemini API Key.');
        }

        // Load ATP beserta elemen CP dan TP (struktur baru)
        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return redirect()->back()->with('error', 'ATP tidak ditemukan.');
        }
        $atpResult   = $this->loadAtpWithElemen((int)$atpRaw['subject_id']);
        $atpFull     = null;
        foreach ($atpResult['list'] as $a) {
            if ($a['id'] == $atpId) { $atpFull = $a; break; }
        }
        $atp = $atpFull ?? $atpRaw;

        // Kumpulkan semua TP dari semua elemen CP
        $tps = $atp['tps'] ?? [];
        $tpDeskripsi = "";
        foreach ($tps as $index => $tp) {
            $kodeTp = !empty($tp['kode_tp']) ? $tp['kode_tp'] . '. ' : '';
            $tpDeskripsi .= "<p>" . $kodeTp . $tp['deskripsi'] . "</p>\n";
        }

        // Kumpulkan semua CP deskripsi dari elemen_list
        $cpDeskripsiAll = "";
        foreach ($atp['elemen_list'] ?? [] as $el) {
            $cpDeskripsiAll .= "<p><b>" . ($el['elemen'] ?? '') . ":</b> " . ($el['cp_deskripsi'] ?? '') . "</p>\n";
        }
        if (empty($cpDeskripsiAll) && !empty($atp['cp_deskripsi'])) {
            $cpDeskripsiAll = $atp['cp_deskripsi']; // fallback data lama
        }

        $subject = $this->subjectModel->find($subjectId);
        $teacher = $this->teacherModel->find($targetTeacherId);
        $school = $this->schoolModel->getProfile();

        $totalJp = (int) ($atp['alokasi_waktu'] ?? 4);
        $jumlahPertemuan = max(1, (int) ceil($totalJp / 2));
        $menitPerPertemuan = 2 * 45; // 2 JP × 45 menit = 90 menit

        $prompt = "Buatkan Modul Ajar Kurikulum Merdeka DEEP LEARNING (MJM) 2025 dengan STRUKTUR PERSIS mengikuti rujukan 'Guru Karier' (Poin A sampai K).\n\n"
                . "ATURAN TATA LETAK (WAJIB & KETAT):\n"
                . "1. JANGAN PERNAH menyatukan judul bagian dalam satu baris. Setiap bagian diawali tag <h3>.\n"
                . "2. Bagian A (Identitas): Tabel 2-kolom. Kolom kiri (Label) WAJIB 'text-align: left; width: 170px; font-weight: bold;'. Isi baris Alokasi Waktu dengan '{$totalJp} JP ({$jumlahPertemuan} Pertemuan)'.\n"
                . "3. Bagian B (Kompetensi Awal): Tuliskan HANYA Capaian Pembelajaran (CP) saja, jangan sertakan Tujuan Pembelajaran (TP).\n"
                . "4. Bagian C (Tujuan Pembelajaran): Masukkan Tujuan Pembelajaran persis seperti input HTML, JANGAN buatkan penomoran list otomatis (1, 2) lagi karena sudah dimuat di teks.\n"
                . "5. Bagian F (Langkah Pembelajaran): Pindahkan dan buat TEPAT {$jumlahPertemuan} tabel pertemuan di sini, masing-masing berlabel 'Pertemuan ke-1', 'Pertemuan ke-2', dst.\n"
                . "   Setiap tabel pertemuan memiliki 4 kolom: (Tahap | Sintaks | Deskripsi | Waktu) dengan Header Cream (#fff9c4).\n"
                . "   TOTAL waktu setiap pertemuan WAJIB {$menitPerPertemuan} menit (2 JP × 45 menit).\n"
                . "6. Bagian K (Penilaian / Asesmen): Buat SPESIFIK. Jelaskan 'Penilaian Proses' (bentuknya) dan 'Penilaian Hasil' (instrumennya) yang benar-benar dapat mengukur Tujuan Pembelajaran tersebut.\n"
                . "7. JANGAN MENGHASILKAN bagian Tanda Tangan (L, M, dst) sama sekali karena sistem pencetak otomatis telah menyediakan kolom tanda tangan di bawah modul.\n\n"
                . "PANDUAN DEEP LEARNING MJM (SANGAT PENTING — WAJIB ADA DI SETIAP PERTEMUAN):\n"
                . "Setiap pertemuan wajib mengandung ketiga elemen MJM dalam kolom 'Deskripsi' dengan proporsi waktu berikut:\n"
                . "  a. MINDFUL (~10 menit): Tuliskan SCRIPT kata-kata guru untuk teknik STOP / mindfulness singkat.\n"
                . "     Contoh: 'Guru mengajak siswa memejamkan mata 30 detik, fokus pada napas, lalu bertanya: Apa yang ingin kamu capai hari ini?'\n"
                . "  b. MEANINGFUL (~65 menit): Eksplorasi → Diskusi Kolaboratif → Koneksi ke dunia nyata yang SPESIFIK untuk materi " . $atp['lingkup_materi'] . ".\n"
                . "     Gunakan model: Motivasi → Eksplorasi Konsep → Diskusi Kelompok → Presentasi/Sintesis.\n"
                . "     JANGAN gunakan kalimat normatif 'Guru menjelaskan'. Gunakan deskripsi aksi nyata.\n"
                . "  c. JOYFUL (~15 menit): Rincikan AKTIVITAS/GAME/PROYEK MINI yang memancing emosi positif terkait materi.\n\n"
                . "DATA INPUT:\n"
                . "Sekolah: " . ($school['name'] ?? '-') . "\n"
                . "Penyusun: " . ($teacher['name'] ?? '-') . "\n"
                . "Mapel: " . $subject['name'] . "\n"
                . "Materi: " . $atp['lingkup_materi'] . "\n"
                . "Kelas: " . ($tps[0]['kelas'] ?? '-') . "\n"
                . "Alokasi Waktu: {$totalJp} JP → {$jumlahPertemuan} Pertemuan (masing-masing 2 JP / {$menitPerPertemuan} menit)\n"
                . "CP: " . $cpDeskripsiAll . "\n"
                . "TP:\n" . $tpDeskripsi . "\n\n"
                . "KEMBALIKAN HANYA PURE HTML (TANPA MARKDOWN). Pastikan esensi MJM terlihat kuat dalam deskripsi kegiatan.";

        if ($aiProvider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $ch = curl_init($url);
            $payload = json_encode([
                "model" => "llama-3.3-70b-versatile",
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ],
                "temperature" => 0.7
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
        } else {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=' . $apiKey;
            $ch = curl_init($url);
            $payload = json_encode([
                "contents" => [
                    ["parts" => [["text" => $prompt]]]
                ]
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode != 200) {
            log_message('error', "Modul Ajar AI ($aiProvider) Failed. HTTP: $httpCode. Curl Error: $curlError. Response: $response");
            $errorMsg = "Gagal memanggil API $aiProvider (HTTP $httpCode). ";
            if ($response) {
                // Try parsing the error object
                $errRes = json_decode($response, true);
                if ($aiProvider === 'groq' && isset($errRes['error']['message'])) {
                    $errorMsg .= "Error API: " . $errRes['error']['message'];
                } elseif (isset($errRes['error']['message'])) {
                    $errorMsg .= "Error API: " . $errRes['error']['message'];
                } else {
                    $errorMsg .= "Pastikan API Key valid atau batas kuota.";
                }
            } elseif ($curlError) {
                $errorMsg .= "cURL: $curlError";
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        $resData = json_decode($response, true);
        if ($aiProvider === 'groq') {
            $content = $resData['choices'][0]['message']['content'] ?? '';
        } else {
            $content = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }
        $content = preg_replace('/```(?:html)?(.*?)```/s', '$1', $content);

        $modulModel = new \App\Models\ModulAjarModel();
        
        $modulModel->insert([
            'atp_id' => $atpId,
            'subject_id' => $subjectId,
            'class_id' => $atp['class_id'],
            'teacher_id' => $targetTeacherId,
            'content' => trim($content)
        ]);

        return redirect()->back()->with('success', 'Modul Ajar berhasil digenerate.');
    }

    public function editModulAjar($id, $classId)
    {
        $modulModel = new \App\Models\ModulAjarModel();
        $modul = $modulModel->find($id);
        if (!$modul) return redirect()->back()->with('error', 'Modul tidak ditemukan.');
        
        $data['title'] = 'Edit Modul Ajar';
        $data['modul'] = $modul;
        $data['class_id'] = $classId;
        $data['subject'] = $this->subjectModel->find($modul['subject_id']);
        
        return view('admin/administrasi_guru/modul_ajar_edit', $data);
    }

    public function updateModulAjar()
    {
        $id = $this->request->getPost('id');
        $content = $this->request->getPost('content');
        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        
        $modulModel = new \App\Models\ModulAjarModel();
        $modulModel->update($id, ['content' => $content]);
        
        return redirect()->to(base_url('admin/administrasi-guru/modul-ajar?class_id=' . $classId . '&subject_id=' . $subjectId))->with('success', 'Modul Ajar berhasil diperbarui.');
    }

    public function printModulAjar($id, $classId)
    {
        $modulModel = new \App\Models\ModulAjarModel();
        $modul = $modulModel->find($id);
        if (!$modul) return redirect()->back();

        $data['modul'] = $modul;
        $data['class_id'] = $classId;
        $data['subject'] = $this->subjectModel->find($modul['subject_id']);
        $data['school'] = $this->schoolModel->getProfile();

        $assignment = $this->teachingAssignmentModel->where('class_id', $classId)->where('subject_id', $modul['subject_id'])->first();
        $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;
        
        return view('admin/administrasi_guru/modul_ajar_print', $data);
    }

    public function atpPrint($classId, $subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->back();

        $data['subject'] = $subject;
        $data['school']  = $this->schoolModel->getProfile();

        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $level     = (int)($classInfo['level'] ?? 0);

        // Guru pengampu — spesifik untuk kelas yang dicetak
        $assignment = $this->teachingAssignmentModel
            ->where('subject_id', $subjectId)
            ->where('class_id', $classId)
            ->first();
        if (!$assignment) {
            $assignment = $this->teachingAssignmentModel->where('subject_id', $subjectId)->first();
        }
        $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;

        // Cek apakah ada >1 guru di level yang sama untuk mapel ini
        $teacherCount = $this->db->table('teaching_assignments ta')
            ->distinct()->select('ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id', $subjectId)
            ->where('c.level', $level)
            ->get()->getResultArray();
        $isMultiGuru = count($teacherCount) > 1;

        if ($isMultiGuru) {
            // Cek apakah kelas ini punya ATP sendiri
            $ownAtpCount = $this->atpModel
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->countAllResults();

            if ($ownAtpCount > 0) {
                // Punya ATP sendiri → cetak ATP milik kelas ini
                $atpResult = $this->loadAtpWithElemen((int)$subjectId, $level, null, [(int)$classId]);
            } else {
                // Tidak punya ATP → cari ATP dari kelas lain se-level yang ada
                // (kelas manapun yang punya ATP untuk mapel ini di level yang sama)
                $sourceClass = $this->db->table('classes c')
                    ->select('c.id')
                    ->join('alur_tujuan_pembelajaran atp', 'atp.class_id = c.id')
                    ->where('c.level', $level)
                    ->where('atp.subject_id', $subjectId)
                    ->where('c.id !=', $classId)
                    ->groupBy('c.id')
                    ->get()->getRowArray();

                if ($sourceClass) {
                    $atpResult = $this->loadAtpWithElemen((int)$subjectId, $level, null, [(int)$sourceClass['id']]);
                } else {
                    $atpResult = ['list' => [], 'all_tps' => []];
                }
            }
        } else {
            // 1 guru di level ini: tampilkan semua ATP se-level
            $atpResult = $this->loadAtpWithElemen((int)$subjectId, $level);
        }

        $atpList = $atpResult['list'];
        $allTps  = $atpResult['all_tps'];

        $data['atp_list'] = $atpList;
        $data['fase']  = !empty($allTps) ? ($allTps[0]['fase']  ?? '-') : '-';
        $data['kelas'] = !empty($allTps) ? ($allTps[0]['kelas'] ?? '-') : '-';

        return view('admin/administrasi_guru/atp_print', $data);
    }

    public function prosemInput($classId, $subjectId, $semester)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->back();

        $data['title'] = 'Input Distribusi Prosem';
        $data['class_id'] = $classId;
        $data['subject'] = $subject;
        $data['semester'] = $semester;

        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        // ATPs for this subject and semester
        $level         = (int)($classInfo['level'] ?? 0);
        $teacherFilter = $this->getTeacherClassFilter((int)$subjectId, $level, (int)$classId);
        $atpResult     = $this->loadAtpWithElemen((int)$subjectId, $level, (int)$semester, $teacherFilter);
        $atpList       = $atpResult['list'];

        foreach ($atpList as &$atp) {
            // Get existing distributions
            $dist = $this->prosemModel->where('atp_id', $atp['id'])->findAll();
            $atp['distributions'] = [];
            foreach ($dist as $d) {
                $atp['distributions'][$d['month']][$d['week']] = $d['jp'];
            }
        }
        unset($atp);
        $data['atp_list'] = $atpList;

        // Define months based on semester
        if ($semester == 1) {
            $data['months'] = [
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 
                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
        } else {
            $data['months'] = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
                4 => 'April', 5 => 'Mei', 6 => 'Juni'
            ];
        }

        return view('admin/administrasi_guru/prosem_input', $data);
    }

    public function prosemSave()
    {
        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');

        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses.');
        }
        if ($user['role_id'] == 3) {
            $isAssigned = $this->db->table('teaching_assignments')
                ->where('teacher_id', $user['related_id'])
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId)
                ->countAllResults();

            if (!$isAssigned) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses pada kelas/mata pelajaran ini.');
            }
        }

        $distribution = $this->request->getPost('dist'); // array[atp_id][month][week] = jp
        
        if ($distribution) {
            foreach ($distribution as $atpId => $months) {
                // Clear old distributions for this ATP session
                $this->prosemModel->where('atp_id', $atpId)->delete();
                
                foreach ($months as $month => $weeks) {
                    foreach ($weeks as $week => $jp) {
                        if ($jp > 0) {
                            $this->prosemModel->insert([
                                'atp_id' => $atpId,
                                'month'  => $month,
                                'week'   => $week,
                                'jp'     => $jp
                            ]);
                        }
                    }
                }
            }
        }

        return redirect()->to(base_url('admin/administrasi-guru/prota-prosem?class_id=' . $classId . '&subject_id=' . $subjectId))
                        ->with('success', 'Distribusi Prosem berhasil disimpan.');
    }

    public function protaPrint($classId, $subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->back();

        $data['subject'] = $subject;
        $data['school'] = $this->schoolModel->getProfile();

        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        // Get Teacher — spesifik untuk kelas yang dicetak
        $assignment = $this->teachingAssignmentModel
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
        if (!$assignment) {
            $assignment = $this->teachingAssignmentModel->where('subject_id', $subjectId)->first();
        }
        $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;

        // Get ATPs grouped by semester
        $level         = (int)($classInfo['level'] ?? 0);
        $teacherFilter = $this->getTeacherClassFilter((int)$subjectId, $level, (int)$classId);
        $protaResult   = $this->loadAtpWithElemen((int)$subjectId, $level, null, $teacherFilter);
        $prota         = $protaResult['list'];
        $allTps        = $protaResult['all_tps'];

        $data['fase']  = !empty($allTps) ? ($allTps[0]['fase']  ?? '-') : '-';
        $data['kelas'] = !empty($allTps) ? ($allTps[0]['kelas'] ?? '-') : '-';
        $data['prota'] = $prota;

        return view('admin/administrasi_guru/prota_print', $data);
    }

    public function prosemPrint($classId, $subjectId, $semester)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->back();

        $data['subject'] = $subject;
        $data['semester'] = $semester;
        $data['school'] = $this->schoolModel->getProfile();

        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        // Get Teacher — spesifik untuk kelas yang dicetak
        $assignment = $this->teachingAssignmentModel
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->first();
        if (!$assignment) {
            $assignment = $this->teachingAssignmentModel->where('subject_id', $subjectId)->first();
        }
        $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;

        // ATPs for this subject and semester
        $level         = (int)($classInfo['level'] ?? 0);
        $teacherFilter = $this->getTeacherClassFilter((int)$subjectId, $level, (int)$classId);
        $atpResult     = $this->loadAtpWithElemen((int)$subjectId, $level, (int)$semester, $teacherFilter);
        $atpList       = $atpResult['list'];
        $allTps        = $atpResult['all_tps'];

        foreach ($atpList as &$atp) {
            $dist = $this->prosemModel->where('atp_id', $atp['id'])->findAll();
            $atp['distributions'] = [];
            foreach ($dist as $d) {
                $atp['distributions'][$d['month']][$d['week']] = $d['jp'];
            }
        }
        unset($atp);
        $data['atp_list'] = $atpList;
        $data['fase']  = !empty($allTps) ? ($allTps[0]['fase']  ?? '-') : '-';
        $data['kelas'] = !empty($allTps) ? ($allTps[0]['kelas'] ?? '-') : '-';

        if ($semester == 1) {
            $data['months'] = [
                7 => 'Juli', 8 => 'Agustus', 9 => 'September', 
                10 => 'Oktober', 11 => 'November', 12 => 'Desember'
            ];
        } else {
            $data['months'] = [
                1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 
                4 => 'April', 5 => 'Mei', 6 => 'Juni'
            ];
        }

        return view('admin/administrasi_guru/prosem_print', $data);
    }

    /**
     * Cek apakah ada >1 guru untuk subject+level yang sama.
     * Jika ya, kembalikan class_ids milik guru ini saja.
     * Jika tidak, kembalikan null (tampilkan semua ATP se-level).
     *
     * @param int      $subjectId
     * @param int      $classLevel
     * @param int|null $overrideTeacherId  Paksa filter ke teacher_id tertentu (untuk print by admin)
     */
    private function getTeacherClassFilter(int $subjectId, int $classLevel, ?int $overrideClassId = null): ?array
    {
        $teacherCount = $this->db->table('teaching_assignments ta')
            ->distinct()->select('ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id', $subjectId)
            ->where('c.level', $classLevel)
            ->get()->getResultArray();

        if (count($teacherCount) <= 1) {
            return null; // Hanya 1 guru di level ini → tampilkan semua
        }

        // >1 guru di level ini → filter per class_id spesifik
        if ($overrideClassId !== null) {
            // Explicit class_id (dari admin/cetak) → ambil ATP kelas ini saja
            return [$overrideClassId];
        }

        $user = session()->get('user');
        if ($user && $user['role_id'] == 3) {
            // Guru login → filter ATP milik kelas-kelas yang diajar guru ini
            $myClassIds = $this->db->table('teaching_assignments')
                ->select('class_id')
                ->where('teacher_id', $user['related_id'])
                ->where('subject_id', $subjectId)
                ->get()->getResultArray();
            return array_column($myClassIds, 'class_id') ?: [0];
        }

        // Admin/Kepsek tanpa override → tampilkan semua
        return null;
    }

    /**
     * Helper: Query ATP list tanpa JOIN cp_master di header,
     * lalu load elemen_list (atp_elemen + cp_master + TP) untuk setiap ATP.
     * Backward compatible untuk data lama (tanpa atp_elemen).
     */
    private function loadAtpWithElemen(int $subjectId, ?int $classLevel = null, ?int $semester = null, ?array $teacherClassIds = null): array
    {
        $query = $this->atpModel
            ->select('alur_tujuan_pembelajaran.*')
            ->where('alur_tujuan_pembelajaran.subject_id', $subjectId);

        if ($classLevel !== null) {
            $query->join('classes c_atp', 'c_atp.id = alur_tujuan_pembelajaran.class_id', 'left')
                  ->where('c_atp.level', $classLevel);
        }

        // Filter per kelas guru jika ada >1 guru di level yang sama
        if (!empty($teacherClassIds)) {
            $query->whereIn('alur_tujuan_pembelajaran.class_id', $teacherClassIds);
        }

        if ($semester !== null) {
            $query->where('alur_tujuan_pembelajaran.semester', $semester);
        }

        $atpList = $query
            ->groupBy('alur_tujuan_pembelajaran.id')
            ->orderBy('alur_tujuan_pembelajaran.semester', 'ASC')
            ->orderBy('alur_tujuan_pembelajaran.urutan', 'ASC')
            ->findAll();

        $allTps = [];

        foreach ($atpList as &$atp) {
            // Load elemen CP + TP via struktur baru
            $elemenList = $this->db->table('atp_elemen ae')
                ->select('ae.*, cp.elemen, cp.deskripsi as cp_deskripsi, cp.fase')
                ->join('cp_master cp', 'cp.id = ae.cp_master_id')
                ->where('ae.atp_id', $atp['id'])
                ->orderBy('ae.urutan', 'ASC')
                ->get()->getResultArray();

            foreach ($elemenList as &$el) {
                $el['tps'] = $this->tpModel->where('atp_elemen_id', $el['id'])->findAll();
                $allTps    = array_merge($allTps, $el['tps']);
            }
            unset($el);
            $atp['elemen_list'] = $elemenList;

            // Backward compat: data lama tidak punya atp_elemen
            if (empty($elemenList)) {
                $legacyTps = $this->tpModel->where('atp_id', $atp['id'])->findAll();
                $grouped   = [];
                foreach ($legacyTps as $tp) {
                    $cpId = $tp['cp_master_id'] ?? null;
                    if ($cpId) {
                        $grouped[$cpId][] = $tp;
                    }
                }
                foreach ($grouped as $cpId => $tps) {
                    $cp = $this->cpMasterModel->find($cpId);
                    $atp['elemen_list'][] = [
                        'cp_master_id' => $cpId,
                        'elemen'       => $cp['elemen'] ?? '-',
                        'cp_deskripsi' => $cp['deskripsi'] ?? '-',
                        'fase'         => $cp['fase'] ?? '-',
                        'tps'          => $tps,
                    ];
                    $allTps = array_merge($allTps, $tps);
                }
                // Jika tidak ada cp_master_id sama sekali, buat pseudo elemen
                if (empty($atp['elemen_list']) && !empty($legacyTps)) {
                    $atp['elemen_list'][] = [
                        'cp_master_id' => null,
                        'elemen'       => '-',
                        'cp_deskripsi' => '-',
                        'fase'         => '-',
                        'tps'          => $legacyTps,
                    ];
                    $allTps = array_merge($allTps, $legacyTps);
                }
            }

            // Flat list TP untuk backward compat (prota/prosem cukup pakai $atp['tps'])
            $atp['tps'] = [];
            foreach ($atp['elemen_list'] as $el) {
                $atp['tps'] = array_merge($atp['tps'], $el['tps']);
            }
        }
        unset($atp);

        return ['list' => $atpList, 'all_tps' => $allTps];
    }

    private function getAvailableFilters()
    {
        $user = session()->get('user');
        if (!$user) return ['classes' => [], 'subjects' => [], 'readonly' => true, 'role_id' => null, 'auto_class' => false, 'auto_fase' => null];

        $roleId = $user['role_id'];
        $teacherId = $user['related_id'];
        $readonly = ($roleId == 2); // Kepsek is read-only

        $classes = [];
        $subjects = [];
        $autoClass = false;
        $autoFase = null;

        $classId = $this->request->getGet('class_id');

        if (in_array($roleId, [1, 2])) {
            // Admin or Kepsek: All classes
            $classes = $this->db->table('classes')->get()->getResultArray();
            
            // Admin & Kepsek can see ALL subjects regardless of teaching assignments
            $subjects = $this->subjectModel->where('mapel_master_id !=', null)->findAll();
        } elseif ($roleId == 3) {
            // Guru: only assigned classes and subjects for active academic year
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $assignmentQuery = $this->db->table('teaching_assignments ta')
                ->select('c.id as class_id, c.name as class_name, c.level, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $teacherId);

            if (!empty($activeYear['id'])) {
                $assignmentQuery->where('ta.academic_year_id', $activeYear['id']);
            }
            
            $assignments = $assignmentQuery->get()->getResultArray();

            $tempClasses = [];
            foreach ($assignments as $a) {
                $tempClasses[$a['class_id']] = ['id' => $a['class_id'], 'name' => $a['class_name'], 'level' => $a['level']];
            }
            $classes = array_values($tempClasses);
            
            // Auto-select if only 1 class
            if (count($classes) === 1) {
                $autoClass = true;
                $classId = $classes[0]['id'];
                $_GET['class_id'] = $classId; // Force class_id into GET for consistent logic
            }

            if ($classId) {
                $filteredSubjects = array_filter($assignments, fn($a) => $a['class_id'] == $classId);
                $subjects = array_map(fn($s) => ['id' => $s['subject_id'], 'name' => $s['subject_name']], $filteredSubjects);
            } else {
                // If no class selected, show subjects across all handled classes
                $tempSubjects = [];
                foreach ($assignments as $a) {
                    $tempSubjects[$a['subject_id']] = ['id' => $a['subject_id'], 'name' => $a['subject_name']];
                }
                $subjects = array_values($tempSubjects);
            }
        }

        // Derive Fase from Class Level globally
        if ($classId) {
            $cls = array_values(array_filter($classes, fn($c) => $c['id'] == $classId))[0] ?? null;
            if ($cls) {
                $lvl = (int)$cls['level'];
                if ($lvl >= 1 && $lvl <= 2) $autoFase = 'A';
                elseif ($lvl >= 3 && $lvl <= 4) $autoFase = 'B';
                elseif ($lvl >= 5 && $lvl <= 6) $autoFase = 'C';
                elseif ($lvl >= 7 && $lvl <= 9) $autoFase = 'D';
                elseif ($lvl == 10) $autoFase = 'E';
                elseif ($lvl >= 11 && $lvl <= 12) $autoFase = 'F';
            }
        }

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'readonly' => $readonly,
            'role_id' => $roleId,
            'auto_class' => $autoClass,
            'auto_fase' => $autoFase,
            'selected_class' => $classId
        ];
    }

    public function deleteModulAjar($id, $subjectId, $classId)
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan menghapus Modul Ajar.');
        }

        $modulModel = new \App\Models\ModulAjarModel();
        $modulModel->delete($id);

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id=$classId&subject_id=$subjectId"))->with('success', 'Modul Ajar berhasil dihapus. Silakan generate ulang jika diperlukan.');
    }

    /**
     * Salin modul dari kelas lain se-level ke kelas ini.
     * Konten sama, teacher_id disesuaikan dengan guru kelas tujuan.
     */
    public function copyModulAjar()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan menyalin Modul Ajar.');
        }

        $sourceModulId = $this->request->getPost('source_modul_id');
        $targetClassId = $this->request->getPost('target_class_id');
        $subjectId     = $this->request->getPost('subject_id');
        $atpId         = $this->request->getPost('atp_id');

        $modulModel = new \App\Models\ModulAjarModel();
        $sourceModul = $modulModel->find($sourceModulId);

        if (!$sourceModul) {
            return redirect()->back()->with('error', 'Modul sumber tidak ditemukan.');
        }

        // Cari teacher_id yang mengajar di kelas tujuan
        $assignment = $this->teachingAssignmentModel
            ->where('class_id', $targetClassId)
            ->where('subject_id', $subjectId)
            ->first();

        // Jika guru login (role 3), gunakan teacher_id sendiri
        if ($user['role_id'] == 3) {
            $targetTeacherId = $user['related_id'];
        } elseif ($assignment) {
            $targetTeacherId = $assignment['teacher_id'];
        } else {
            $targetTeacherId = $sourceModul['teacher_id']; // fallback
        }

        // Cek apakah modul sudah ada untuk kelas tujuan + ATP ini
        $existing = $modulModel
            ->where('atp_id', $atpId)
            ->where('class_id', $targetClassId)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Modul untuk kelas ini sudah ada. Hapus dulu jika ingin menyalin ulang.');
        }

        $modulModel->insert([
            'atp_id'     => $atpId,
            'subject_id' => $subjectId,
            'class_id'   => $targetClassId,
            'teacher_id' => $targetTeacherId,
            'content'    => $sourceModul['content'],
        ]);

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id=$targetClassId&subject_id=$subjectId"))
            ->with('success', 'Modul berhasil disalin. Anda dapat mengeditnya jika diperlukan.');
    }
}
