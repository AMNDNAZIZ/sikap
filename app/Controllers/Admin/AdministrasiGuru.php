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
use App\Models\AcademicYearModel;
use App\Models\HolidayModel;
use App\Services\AiContextBuilder;

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
    protected $yearModel;
    protected $holidayModel;
    protected $db;
    protected $aiContextBuilder;

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
        $this->yearModel = new AcademicYearModel();
        $this->holidayModel = new HolidayModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Helper: Get AiContextBuilder instance
     */
    protected function getAiContextBuilder(): AiContextBuilder
    {
        return $this->aiContextBuilder ??= new AiContextBuilder();
    }

    /**
     * Helper untuk injeksi AiContextBuilder pada pengujian
     */
    public function setAiContextBuilder(AiContextBuilder $builder): self
    {
        $this->aiContextBuilder = $builder;
        return $this;
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
        $classModel = new \App\Models\ClassModel();
        $data['classes']  = $classModel->getClassesByAcademicYear((int)$activeYearId);
        $data['subjects'] = $this->subjectModel->where('mapel_master_id !=', null)->orderBy('name', 'ASC')->findAll();

        if (!$activeYearId) {
            $data['assignments'] = [];
            $data['pager'] = null;
            $data['atp_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['promes_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['kktp_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['modul_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            return view('admin/administrasi_guru/monitoring', $data);
        }

        // 5. Query ALL assignments (plotting pengajaran) for active academic year
        // Kecualikan mapel BP/BK (is_bk=1) dari monitoring administrasi
        $query = $this->teachingAssignmentModel
            ->select('teaching_assignments.id, teaching_assignments.teacher_id, teaching_assignments.class_id, teaching_assignments.subject_id, teachers.name as teacher_name, classes.name as class_name, classes.level as class_level, subjects.name as subject_name')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->join('classes', 'classes.id = teaching_assignments.class_id')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->where('teaching_assignments.academic_year_id', $activeYearId)
            ->where('subjects.is_bk', 0);  // Kecualikan mapel BP/BK

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
        $kktpStats = ['complete' => 0, 'partial' => 0, 'empty' => 0];
        $modulStats = ['complete' => 0, 'partial' => 0, 'empty' => 0];

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

            // KKTP Status Logic
            $kktpQuery = $this->db->table('kktp');
            if ($isMultiGuru) {
                $kktpQuery->where('class_id', $classId);
            } else {
                $classIdsAtLevel = array_column(
                    $this->db->table('classes')->select('id')->where('level', $classLevel)->get()->getResultArray(),
                    'id'
                ) ?: [0];
                $kktpQuery->whereIn('class_id', $classIdsAtLevel);
            }
            $kktpCount = $kktpQuery->where('subject_id', $subjectId)->countAllResults();

            if ($kktpCount == 0) {
                $row['kktp_status'] = 'empty';
                $row['kktp_info'] = 'Kosong';
                $kktpStats['empty']++;
            } elseif ($kktpCount >= 3) {
                $row['kktp_status'] = 'complete';
                $row['kktp_info'] = "Selesai ({$kktpCount} TP)";
                $kktpStats['complete']++;
            } else {
                $row['kktp_status'] = 'partial';
                $row['kktp_info'] = "Sebagian ({$kktpCount} TP)";
                $kktpStats['partial']++;
            }

            // Modul Ajar Status Logic
            // Single-guru: cari modul dari kelas manapun se-level (lintas tahun)
            $modulQuery = $this->db->table('modul_ajar')
                ->where('subject_id', $subjectId);

            if ($isMultiGuru) {
                $modulQuery->where('class_id', $classId);
            } else {
                $modulQuery->whereIn('class_id', $classIds);
            }

            $modulCount = $modulQuery->countAllResults();

            if ($modulCount == 0) {
                $row['modul_status'] = 'empty';
                $row['modul_info'] = 'Kosong';
                $modulStats['empty']++;
            } elseif ($modulCount >= $atpCount && $atpCount > 0) {
                $row['modul_status'] = 'complete';
                $row['modul_info'] = "Selesai ({$modulCount} modul)";
                $modulStats['complete']++;
            } else {
                $row['modul_status'] = 'partial';
                $row['modul_info'] = "Sebagian ({$modulCount}" . ($atpCount > 0 ? "/{$atpCount}" : "") . " modul)";
                $modulStats['partial']++;
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
        $data['kktp_stats'] = $kktpStats;
        $data['modul_stats'] = $modulStats;

        // 9. Ringkasan per guru: hitung status keseluruhan administrasi
        $teacherSummary = [];
        foreach ($allAssignments as $row) {
            $tid = $row['teacher_id'];
            if (!isset($teacherSummary[$tid])) {
                $teacherSummary[$tid] = [
                    'name'     => $row['teacher_name'],
                    'total'    => 0,
                    'complete' => 0,
                    'partial'  => 0,
                    'empty'    => 0,
                ];
            }
            $teacherSummary[$tid]['total']++;

            // Hitung overall status per assignment (semua 4 komponen harus selesai)
            $allComplete = ($row['atp_status'] === 'complete' && $row['kktp_status'] === 'complete' && $row['promes_status'] === 'complete' && $row['modul_status'] === 'complete');
            $allEmpty = ($row['atp_status'] === 'empty' && $row['kktp_status'] === 'empty' && $row['promes_status'] === 'empty' && $row['modul_status'] === 'empty');

            if ($allComplete) {
                $teacherSummary[$tid]['complete']++;
            } elseif ($allEmpty) {
                $teacherSummary[$tid]['empty']++;
            } else {
                $teacherSummary[$tid]['partial']++;
            }
        }
        // Urutkan berdasarkan persentase selesai (descending)
        uasort($teacherSummary, function($a, $b) {
            $pctA = $a['total'] > 0 ? ($a['complete'] / $a['total']) : 0;
            $pctB = $b['total'] > 0 ? ($b['complete'] / $b['total']) : 0;
            return $pctB <=> $pctA;
        });
        $data['teacher_summary'] = $teacherSummary;

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
        
        $data['school_level'] = $levelId;
        $data['school_level_name'] = match((int)$levelId) {
            1 => 'SD / Sederajat',
            2 => 'SMP / Sederajat',
            3 => 'SMA / Sederajat',
            default => 'Unknown'
        };
        $data['mapel_master'] = $this->mapelMasterModel->where('jenjang_id', $levelId)->orderBy('nama', 'ASC')->findAll();
        
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
                    if (!empty($filters['auto_fase'])) {
                        $queryCp->where('fase', $filters['auto_fase']);
                    } elseif ($filters['role_id'] == 3) {
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
                    $data['cp_list'] = $queryCp->findAll();

                    // Filter TP berdasarkan kelas yang dipilih
                    $tpList = [];
                    if ($classId) {
                        $classInfo  = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
                        $classLevel = (int)($classInfo['level'] ?? 0);
                        $className  = $classInfo['name'] ?? '';

                        // 1. Prioritaskan TP yang terhubung ke ATP milik kelas ini secara langsung
                        $tpList = $this->db->table('tujuan_pembelajaran tp')
                            ->select('tp.*')
                            ->join('alur_tujuan_pembelajaran atp', 'atp.id = tp.atp_id')
                            ->where('tp.subject_id', $subjectId)
                            ->where('atp.class_id', $classId)
                            ->groupBy('tp.id')
                            ->orderBy('tp.elemen', 'ASC')
                            ->orderBy('tp.kode_tp', 'ASC')
                            ->get()->getResultArray();

                        // 2. Fallback: TP mandiri yang tersimpan dengan kolom kelas sesuai level / nama kelas
                        if (empty($tpList) && $classLevel > 0) {
                            $tpList = $this->db->table('tujuan_pembelajaran tp')
                                ->select('tp.*')
                                ->where('tp.subject_id', $subjectId)
                                ->groupStart()
                                    ->where('tp.kelas', (string)$classLevel)
                                    ->orWhere('tp.kelas', $className)
                                ->groupEnd()
                                ->groupBy('tp.id')
                                ->orderBy('tp.elemen', 'ASC')
                                ->orderBy('tp.kode_tp', 'ASC')
                                ->get()->getResultArray();
                        }

                        // 3. Fallback jika single-guru: ambil TP dari kelas lain se-level jika kelas ini belum punya TP
                        if (empty($tpList) && $classLevel > 0 && !$this->isMultiGuruForLevel((int)$subjectId, $classLevel)) {
                            $classIdsAtLevel = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
                            if (!empty($classIdsAtLevel)) {
                                $tpList = $this->db->table('tujuan_pembelajaran tp')
                                    ->select('tp.*')
                                    ->join('alur_tujuan_pembelajaran atp', 'atp.id = tp.atp_id')
                                    ->where('tp.subject_id', $subjectId)
                                    ->whereIn('atp.class_id', $classIdsAtLevel)
                                    ->groupBy('tp.id')
                                    ->orderBy('tp.elemen', 'ASC')
                                    ->orderBy('tp.kode_tp', 'ASC')
                                    ->get()->getResultArray();
                            }
                        }
                    }

                    // Deduplikasi TP berdasarkan kode_tp + deskripsi
                    $uniqueTpList = [];
                    $seenTp = [];
                    foreach ($tpList as $tp) {
                        $key = trim(($tp['kode_tp'] ?? '') . '|' . ($tp['deskripsi'] ?? ''));
                        if (!isset($seenTp[$key])) {
                            $seenTp[$key] = true;
                            $uniqueTpList[] = $tp;
                        }
                    }
                    $data['tp_list'] = $uniqueTpList;
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
                    $isMultiGuru  = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

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

        // ═══ PENCEGAHAN DUPLIKASI ═══
        // Jika bukan edit (id kosong), cek apakah ATP dengan lingkup_materi + subject_id 
        // sudah ada di level yang sama (oleh guru yang sama)
        if (empty($id)) {
            $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            $classLevel = (int) ($classInfo['level'] ?? 0);

            // Cek ATP yang sudah ada di level ini untuk mapel + lingkup materi yang sama
            $existingAtp = $this->db->table('alur_tujuan_pembelajaran atp')
                ->select('atp.id, atp.class_id, c.name as class_name')
                ->join('classes c', 'c.id = atp.class_id')
                ->where('atp.subject_id', $subjectId)
                ->where('atp.lingkup_materi', $lingkupMateri)
                ->where('atp.semester', $semester)
                ->where('c.level', $classLevel)
                ->get()->getRowArray();

            if ($existingAtp) {
                // Cek apakah guru yang sama mengajar di kelas sumber
                $sameTeacher = true;
                if ($user['role_id'] == 3) {
                    $sameTeacher = $this->db->table('teaching_assignments')
                        ->where('teacher_id', $user['related_id'])
                        ->where('class_id', $existingAtp['class_id'])
                        ->where('subject_id', $subjectId)
                        ->countAllResults() > 0;
                }

                if ($sameTeacher) {
                    // Guru yang sama — ATP sudah ada, tidak perlu buat baru
                    return redirect()->back()->with('error', 
                        'ATP dengan lingkup materi "' . esc($lingkupMateri) . '" semester ' . $semester . 
                        ' sudah ada di ' . esc($existingAtp['class_name']) . 
                        '. Karena Anda mengajar mapel ini di kelas se-level, ATP tersebut otomatis berlaku untuk semua kelas yang Anda ampu.');
                }
                // Guru berbeda — boleh buat ATP sendiri (salin manual atau buat baru)
            }
        }

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
            // Hapus elemen lama beserta TP-nya (termasuk legacy TP tanpa atp_elemen_id)
            $oldElemens = $this->atpElemenModel->where('atp_id', $atpId)->findAll();
            foreach ($oldElemens as $oe) {
                $this->tpModel->where('atp_elemen_id', $oe['id'])->delete();
            }
            $this->atpElemenModel->where('atp_id', $atpId)->delete();
            $this->tpModel->where('atp_id', $atpId)->delete();
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
     * Hapus satu Tujuan Pembelajaran (TP) dari ATP
     */
    public function atpDeleteTp($id)
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
        }

        $tp = $this->tpModel->find($id);
        if ($tp) {
            $atpId = $tp['atp_id'] ?? null;
            if ($user['role_id'] == 3 && $atpId) {
                $atp = $this->atpModel->find($atpId);
                if ($atp) {
                    $isOwner = $this->db->table('teaching_assignments')
                        ->where('teacher_id', $user['related_id'])
                        ->where('class_id', $atp['class_id'])
                        ->where('subject_id', $atp['subject_id'])
                        ->countAllResults();
                    if (!$isOwner) {
                        return redirect()->back()->with('error', 'Anda tidak memiliki akses untuk menghapus TP ini.');
                    }
                }
            }

            $this->tpModel->delete($id);

            // Jika TP yang dihapus adalah tp_id utama di header ATP, update ke TP lain jika ada
            if ($atpId) {
                $atp = $this->atpModel->find($atpId);
                if ($atp && (int)($atp['tp_id'] ?? 0) === (int)$id) {
                    $nextTp = $this->tpModel->where('atp_id', $atpId)->first();
                    $this->atpModel->update($atpId, ['tp_id' => $nextTp['id'] ?? 0]);
                }
            }

            return redirect()->back()->with('success', 'Tujuan Pembelajaran (TP) berhasil dihapus.');
        }

        return redirect()->back()->with('error', 'Tujuan Pembelajaran tidak ditemukan.');
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

                    // Cek multi-guru di level yang sama (sama seperti logika ATP)
                    $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

                    // Untuk single-guru: kumpulkan semua class_id se-level (lintas tahun ajaran)
                    $classIdsAtLevel = [];
                    if (!$isMultiGuru) {
                        $classIdsAtLevel = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
                    }

                    // Filter ATP:
                    // - multi-guru  → kirim $classId spesifik agar hanya ATP milik kelas yang dipilih yang diambil (tidak bercampur dengan kelas/guru lain).
                    // - single-guru → getTeacherClassFilter akan return null (se-level), lalu kita lakukan deduplikasi materi agar materi yang sama tidak dobel.
                    $atpClassFilter = $this->getTeacherClassFilter((int)$subjectId, $classLevel, (int)$classId);
                    $atpResult = $this->loadAtpWithElemen((int)$subjectId, $classLevel, null, $atpClassFilter);
                    $atpList   = $atpResult['list'];

                    $modulModel = new \App\Models\ModulAjarModel();
                    foreach ($atpList as &$atp) {
                        $atp['modul_source'] = null;

                        if ($isMultiGuru) {
                            // Multi-guru: modul spesifik milik kelas ini
                            $modul = $modulModel
                                ->where('atp_id', $atp['id'])
                                ->where('class_id', $classId)
                                ->first();
                            $atp['modul'] = $modul;

                            // Jika belum punya modul, cari dari kelas lain se-level (opsi salin)
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
                        } else {
                            // Single-guru: modul berlaku lintas kelas se-level
                            // Cari modul dari kelas manapun se-level untuk ATP ini
                            $modul = null;
                            if (!empty($classIdsAtLevel)) {
                                $modul = $modulModel
                                    ->where('atp_id', $atp['id'])
                                    ->whereIn('class_id', $classIdsAtLevel)
                                    ->first();
                            }
                            // Fallback: cari spesifik kelas ini
                            if (!$modul) {
                                $modul = $modulModel
                                    ->where('atp_id', $atp['id'])
                                    ->where('class_id', $classId)
                                    ->first();
                            }
                            $atp['modul'] = $modul;
                            // Tidak ada opsi salin untuk single-guru
                        }
                    }
                    unset($atp);

                    // ═══ DEDUPLIKASI UNTUK SINGLE GURU (PENCEGAHAN TAMPILAN DOBEL) ═══
                    // Jika untuk single guru terdapat lebih dari 1 record ATP dengan lingkup materi & semester yang sama
                    // (misal guru pernah membuat ATP di 6A dan 6B dengan materi yang sama persis),
                    // tampilkan hanya 1 baris materi (utamakan record yang punya modul atau class_id cocok).
                    if (!$isMultiGuru && !empty($atpList)) {
                        $deduped = [];
                        foreach ($atpList as $item) {
                            $materiKey = ($item['semester'] ?? 1) . '_' . strtolower(trim($item['lingkup_materi'] ?? ''));
                            if (!isset($deduped[$materiKey])) {
                                $deduped[$materiKey] = $item;
                            } else {
                                $existing = $deduped[$materiKey];
                                // Prioritaskan record yang sudah ada modulnya
                                if (empty($existing['modul']) && !empty($item['modul'])) {
                                    $deduped[$materiKey] = $item;
                                } elseif ((empty($existing['modul']) == empty($item['modul'])) && (int)($item['class_id'] ?? 0) === (int)$classId) {
                                    $deduped[$materiKey] = $item;
                                }
                            }
                        }
                        $atpList = array_values($deduped);
                    }
                    $data['atp_list'] = $atpList;
                    $data['is_multi_guru'] = $isMultiGuru;
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
        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        $redirectUrl = ($classId && $subjectId)
            ? base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}")
            : base_url("admin/administrasi-guru/modul-ajar");

        $user = session()->get('user');
        if ($user['role_id'] == 3) {
            $this->teacherModel->update($user['related_id'], ['gemini_api_key' => ltrim(rtrim($key)), 'ai_provider' => $provider]);
            return redirect()->to($redirectUrl)->with('success', 'Konfigurasi API AI Guru berhasil disimpan.');
        } elseif ($user['role_id'] == 1) {
            $this->db->table('users')->where('id', $user['id'])->update(['gemini_api_key' => ltrim(rtrim($key)), 'ai_provider' => $provider]);
            return redirect()->to($redirectUrl)->with('success', 'Konfigurasi API AI Admin berhasil disimpan.');
        }
        return redirect()->to($redirectUrl)->with('error', 'Anda tidak diizinkan menyimpan API Key.');
    }

    /**
     * Generate prompt saja (tanpa panggil API) — user copy prompt ke AI langsung
     */
    public function getModulPrompt()
    {
        $atpId = $this->request->getPost('atp_id');
        $subjectId = $this->request->getPost('subject_id');

        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return $this->response->setJSON(['error' => 'Tidak diizinkan.']);
        }

        $activeYear = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear ? (int)$activeYear['id'] : null;

        // Validasi sumber class_id yang otoritatif
        $postClassId = $this->request->getPost('class_id');
        $classId = null;
        if (!empty($postClassId)) {
            $classExists = $this->db->table('classes')->where('id', $postClassId)->get()->getRowArray();
            if ($classExists) {
                if ($user['role_id'] == 3) {
                    $authQuery = $this->db->table('teaching_assignments')
                        ->where('teacher_id', $user['related_id'])
                        ->where('class_id', $postClassId)
                        ->where('subject_id', $subjectId);
                    if ($activeYearId) {
                        $authQuery->where('academic_year_id', $activeYearId);
                    }
                    $hasAccess = $authQuery->countAllResults();
                    if ($hasAccess === 0) {
                        $hasAccess = $this->db->table('teaching_assignments')
                            ->where('teacher_id', $user['related_id'])
                            ->where('class_id', $postClassId)
                            ->where('subject_id', $subjectId)
                            ->countAllResults();
                    }
                    if ($hasAccess > 0) {
                        $classId = (int)$postClassId;
                    }
                } else {
                    $classId = (int)$postClassId;
                }
            }
        }

        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return $this->response->setJSON(['error' => 'Dokumen ATP tidak ditemukan.']);
        }

        if (empty($classId) && !empty($atpRaw['class_id'])) {
            $classId = (int)$atpRaw['class_id'];
        }

        // Resolve teacher
        $teacherName = '-';
        $teacherId = null;
        if ($user['role_id'] == 3) {
            $teacher = $this->teacherModel->find($user['related_id']);
            $teacherName = !empty($teacher['name']) ? $teacher['name'] : '-';
            $teacherId = (int)$user['related_id'];
        } else {
            $assignQuery = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId);
            if ($activeYearId) {
                $assignQuery->where('academic_year_id', $activeYearId);
            }
            $assignment = $assignQuery->get()->getRowArray();
            if (!$assignment) {
                $assignment = $this->db->table('teaching_assignments')
                    ->where('subject_id', $subjectId)
                    ->where('class_id', $classId)
                    ->get()->getRowArray();
            }
            if ($assignment) {
                $teacher = $this->teacherModel->find($assignment['teacher_id']);
                $teacherName = !empty($teacher['name']) ? $teacher['name'] : '-';
                $teacherId = (int)$assignment['teacher_id'];
            }
        }

        $contextBuilder = $this->getAiContextBuilder();

        // Kondisi 3: Cek apakah kueri sarana/fasilitas mengalami kesalahan teknis (QUERY_ERROR)
        $facilityCheck = $contextBuilder->getConfirmedFacilities();
        if (isset($facilityCheck['status']) && $facilityCheck['status'] === 'QUERY_ERROR') {
            return $this->response->setJSON(['error' => 'Gagal menyiapkan konteks AI: Terjadi kesalahan kueri database pada fasilitas sekolah.']);
        }

        // Kondisi 2: Validasi integritas relasi via AiContextBuilder
        $contextResult = $contextBuilder->buildModulAjarContext((int)$atpId, $classId, (int)$subjectId, $teacherId, $activeYearId);
        if (!$contextResult['success']) {
            return $this->response->setJSON(['error' => $contextResult['message']]);
        }

        $context = $contextResult['data'];
        $prompt = $this->buildModulPromptFromContext($context, $teacherName);

        $kurikulum = $context['kurikulum'] ?? [];
        $jadwal    = $context['jadwal'] ?? [];

        return $this->response->setJSON([
            'success' => true,
            'prompt'  => $prompt,
            'info'    => [
                'materi'              => $kurikulum['materi_pokok'] ?? '-',
                'total_jp'            => $kurikulum['total_jp'] ?? 4,
                'pertemuan'           => $jadwal['jumlah_pertemuan'] ?? 1,
                'jp_per_pertemuan'    => $jadwal['jp_per_pertemuan'] ?? 2,
                'menit_per_pertemuan' => $jadwal['menit_per_pertemuan'] ?? 80,
                'is_uniform'          => $jadwal['is_uniform'] ?? true,
                'jadwal_info'         => $jadwal['jadwal_info'] ?? '-',
            ],
        ]);
    }

    /**
     * Menyusun teks prompt AI Modul Ajar dari konteks faktual terstruktur
     *
     * @param array $context Konteks terstruktur dari AiContextBuilder::buildModulAjarContext()['data']
     * @param string|null $teacherName Nama guru pengampu
     * @return string
     */
    protected function buildModulPromptFromContext(array $context, ?string $teacherName = null): string
    {
        $sekolah    = $context['sekolah'] ?? [];
        $kelas      = $context['kelas'] ?? [];
        $akademik   = $context['akademik'] ?? [];
        $kurikulum  = $context['kurikulum'] ?? [];
        $jadwal     = $context['jadwal'] ?? [];
        $guidelines = $context['panduan_kontekstual'] ?? [];

        $schoolName   = $sekolah['nama'] ?? 'Satuan Pendidikan';
        $jenjang      = $sekolah['jenjang'] ?? 'SMP';
        $schoolCity   = $sekolah['wilayah'] ?? '';
        $tingkatKelas = !empty($kelas['nama']) && $kelas['nama'] !== '-' ? $kelas['nama'] : ($kelas['tingkat'] ?? '-');
        $subjectName  = $akademik['mapel_nama'] ?? '-';
        $penyusun     = $teacherName ?? ($akademik['guru_nama'] ?? '-');
        $materiPokok  = $kurikulum['materi_pokok'] ?? '-';
        $totalJp      = (int)($kurikulum['total_jp'] ?? 4);

        // CP List
        $cpDeskripsiAll = "";
        foreach ($kurikulum['cp_list'] ?? [] as $el) {
            $cpDeskripsiAll .= ($el['elemen'] ?? '') . ": " . ($el['cp_deskripsi'] ?? '') . "\n";
        }

        // TP List
        $tpDeskripsi = "";
        foreach ($kurikulum['tp_list'] ?? [] as $tp) {
            $kodeTp = !empty($tp['kode_tp']) ? $tp['kode_tp'] . '. ' : '';
            $tpDeskripsi .= $kodeTp . ($tp['deskripsi'] ?? '') . "\n";
        }

        // Alokasi Jadwal & Pertemuan
        $jumlahPertemuan   = max(1, (int)($jadwal['jumlah_pertemuan'] ?? 1));
        $jpPerPertemuan    = max(1, (int)($jadwal['jp_per_pertemuan'] ?? 2));
        $menitPerPertemuan = max(25, (int)($jadwal['menit_per_pertemuan'] ?? 80));
        $isUniform         = $jadwal['is_uniform'] ?? true;
        $pertemuanList     = $jadwal['pertemuan_list'] ?? [];
        $jadwalKonteksInfo = $jadwal['jadwal_info'] ?? 'Alokasi waktu dihitung dari standar kurikulum jenjang.';

        // Format label dan petunjuk langkah waktu adaptif
        if ($isUniform) {
            $alokasiWaktuLabel = "{$totalJp} JP ({$jumlahPertemuan} Pertemuan × {$jpPerPertemuan} JP)";
            $alokasiWaktuInfo  = "{$totalJp} JP → {$jumlahPertemuan} Pertemuan (@ {$jpPerPertemuan} JP / {$menitPerPertemuan} menit per pertemuan)";

            $pedomanWaktuSesi = ($menitPerPertemuan < 35)
                ? "DURASI SESI SINGKAT ({$menitPerPertemuan} MENIT): Pilih model atau strategi ringkas (misal: Explicit Instruction ringkas atau Mini-Discovery 3 langkah). Fokuskan pada aktivitas inti yang realistis dan kurangi kegiatan tambahan yang tidak esensial agar total waktu seluruh baris tepat {$menitPerPertemuan} menit."
                : "DURASI SESI STANDAR ({$menitPerPertemuan} MENIT): Terapkan sintaks model pembelajaran terpilih secara proporsional (pembukaan bermakna, fase-fase eksplorasi inti yang mendalam, dan penutup reflektif) dengan total alokasi waktu seluruh baris tepat {$menitPerPertemuan} menit.";

            $langkahWaktuText = "PETUNJUK STRUKTUR PERTEMUAN ({$menitPerPertemuan} MENIT PER PERTEMUAN):\n"
                . "* {$pedomanWaktuSesi}\n"
                . "* Header setiap pertemuan WAJIB berformat: <h4>Pertemuan ke-X — [Nama Model Utama] ({$menitPerPertemuan} menit)</h4> (Contoh: <h4>Pertemuan ke-1 — Discovery Learning ({$menitPerPertemuan} menit)</h4>).\n"
                . "* Jumlah baris tahap dalam tabel fleksibel mengikuti sintaks resmi model yang dipilih (tidak dibatasi kaku 3 baris).\n"
                . "* Total waktu seluruh baris dalam tabel setiap pertemuan WAJIB TEPAT {$menitPerPertemuan} MENIT.\n";
        } else {
            $alokasiWaktuLabel = "{$totalJp} JP ({$jumlahPertemuan} Pertemuan)";
            $ringkasList = array_map(function($p) {
                return "P{$p['pertemuan']}: {$p['jp']}JP/{$p['menit']}m";
            }, $pertemuanList);
            $alokasiWaktuInfo  = "{$totalJp} JP → {$jumlahPertemuan} Pertemuan (" . implode(', ', $ringkasList) . ")";

            $rincianWaktuPertemuan = "";
            foreach ($pertemuanList as $p) {
                $pMenit = (int)($p['menit'] ?? 80);
                $rincianWaktuPertemuan .= "  * Pertemuan ke-{$p['pertemuan']} ({$p['jp']} JP / {$pMenit} menit): Header: <h4>Pertemuan ke-{$p['pertemuan']} — [Nama Model] ({$pMenit} menit)</h4> | Total alokasi waktu tabel: TEPAT {$pMenit} menit.\n";
            }
            $langkahWaktuText = "PETUNJUK STRUKTUR PERTEMUAN (JADWAL BERAGAM):\n"
                . "* Perhatikan durasi aktual masing-masing pertemuan:\n"
                . $rincianWaktuPertemuan
                . "* Header setiap pertemuan WAJIB mencantumkan nama model dan durasi aktualnya.\n"
                . "* Jumlah baris tahap dalam tabel fleksibel mengikuti sintaks resmi model yang dipilih.\n"
                . "* Total alokasi waktu seluruh baris dalam tabel masing-masing pertemuan WAJIB TEPAT sama dengan menit sesi pertemuan tersebut.\n";
        }

        // Informasi Sarana Terkonfirmasi
        $fasilitasInfo = !empty($sekolah['has_fasilitas']) && !empty($sekolah['fasilitas'])
            ? implode(', ', $sekolah['fasilitas'])
            : 'Fasilitas ruang kelas standar dan area lingkungan sekolah (tidak tercatat fasilitas khusus tambahan)';

        // Rombel & Skala Kelas
        $infoRombel = !empty($kelas['has_jumlah_siswa']) && !empty($kelas['jumlah_siswa'])
            ? "Jumlah Peserta Didik Aktif: {$kelas['jumlah_siswa']} siswa."
            : "Kapasitas kelas standar jenjang {$jenjang}.";

        // KKTP Context
        $kktpContextText = "";
        if (!empty($kurikulum['has_kktp']) && !empty($kurikulum['kktp_list'])) {
            $kktpRows = [];
            foreach ($kurikulum['kktp_list'] as $kktpItem) {
                $critStrings = [];
                foreach ($kktpItem['kriteria'] ?? [] as $crit) {
                    $critStrings[] = "Interval {$crit['interval']}: {$crit['label']} (Intervensi: {$crit['intervensi']})";
                }
                $kktpRows[] = "- TP: {$kktpItem['tujuan_pembelajaran']} | Kriteria: " . implode('; ', $critStrings);
            }
            $kktpContextText = "Kriteria Ketercapaian Tujuan Pembelajaran (KKTP) Resmi Terdaftar:\n" . implode("\n", $kktpRows) . "\n\n";
        }

        $prompt = "Buatkan Modul Ajar Kurikulum Merdeka DEEP LEARNING (MJM) 2025 dengan STRUKTUR PERSIS mengikuti rujukan 'Guru Karier' (LENGKAP POIN A SAMPAI K BERURUTAN, DILARANG MELOMPATI BAGIAN APAPUN):\n\n"
            . "WAJIB TULISKAN SELURUH 11 BAGIAN SECARA LENGKAP & BERURUTAN:\n"
            . "<h3>A. Identitas Modul</h3>\n"
            . "Tabel 2-kolom (Label: text-align: left; width: 170px; font-weight: bold;). Isi Alokasi Waktu: '{$alokasiWaktuLabel}'.\n\n"
            . "<h3>B. Kompetensi Awal</h3>\n"
            . "Tuliskan narasi Capaian Pembelajaran (CP) dan kompetensi awal peserta didik.\n\n"
            . "<h3>C. Tujuan Pembelajaran</h3>\n"
            . "Masukkan Tujuan Pembelajaran (TP) persis seperti input data.\n\n"
            . "<h3>D. Materi Pembelajaran</h3>\n"
            . "Tuliskan ringkasan pokok materi esensial yang dipelajari siswa terkait materi " . $materiPokok . ".\n\n"
            . "<h3>E. Model dan Metode Pembelajaran</h3>\n"
            . "Tuliskan secara terstruktur 4 komponen:\n"
            . "1. Pendekatan Pembelajaran: Pembelajaran Mendalam (Deep Learning) dengan trilogi pengalaman Mindful (kesadaran penuh), Meaningful (kebermaknaan konsep), dan Joyful (kegembiraan/refleksi belajar).\n"
            . "2. Model Pembelajaran Utama (Rekomendasi AI): Pilih model pembelajaran yang paling relevan dan kontekstual dari kandidat resmi: Problem-Based Learning (PBL), Project-Based Learning (PjBL), Discovery Learning, Inquiry Learning, Cooperative Learning (termasuk Think-Pair-Share), Explicit/Direct Instruction, Contextual Teaching & Learning (CTL), atau pembelajaran berdiferensiasi sebagai strategi pendukung. Anda diperbolehkan mengombinasikan model dan strategi yang bervariasi antarpertemuan bila memang meningkatkan kualitas pembelajaran, namun TIDAK BOLEH mengganti model hanya demi variasi acak (penggunaan model yang sama pada beberapa pertemuan berturut-turut diperbolehkan jika secara pedagogis tepat).\n"
            . "3. Alasan Pedagogis Pemilihan Model: Jelaskan secara singkat dan spesifik mengapa model tersebut paling tepat untuk TP, materi " . $materiPokok . ", ketersediaan sarana (" . $fasilitasInfo . "), rombel (" . $infoRombel . "), dan alokasi waktu yang tersedia.\n"
            . "4. Metode dan Strategi Pendukung: Uraikan strategi operasional pendukung (misal: diskusi kelompok Think-Pair-Share, eksperimen sederhana, demonstrasi terbimbing, observasi terarah, pengerjaan LKPD berjenjang, dan presentasi apresiatif).\n\n"
            . "<h3>F. Langkah Pembelajaran</h3>\n"
            . "Buat TEPAT {$jumlahPertemuan} tabel pertemuan (dari 'Pertemuan ke-1' sampai 'Pertemuan ke-{$jumlahPertemuan}').\n"
            . "Setiap pertemuan diawali dengan judul: <h4>Pertemuan ke-X — [Nama Model Utama] ([Alokasi] menit)</h4> (Contoh: <h4>Pertemuan ke-1 — Discovery Learning (100 menit)</h4>).\n"
            . "Setiap tabel pertemuan memiliki 4 kolom persis: (Tahap | Sintaks Model | Deskripsi Aktivitas Guru dan Siswa | Waktu) dengan Header Cream (#fff9c4).\n"
            . $langkahWaktuText . "\n"
            . "* PEDOMAN SINTAKS MODEL & PRINSIP DEEP LEARNING (SANGAT PENTING):\n"
            . "  1. SINTAKS OTENTIK & JUMLAH BARIS FLEKSIBEL: Jumlah baris tahap dalam tabel fleksibel mengikuti sintaks resmi model yang dipilih (jangan memaksakan tepat 3 baris jika model membutuhkan 4, 5, atau langkah sintaks lain). Kolom 1 memuat nama Tahap Pembelajaran (misal: Pendahuluan/Orientasi, Inti, Penutup/Refleksi) dan Kolom 2 memuat langkah resmi (Sintaks Model, misal pada PBL: Orientasi Masalah, Organisasi Belajar, Penyelidikan Kelompok, Penyajian Hasil Karya, Evaluasi Pemecahan Masalah).\n"
            . "  2. PENJIWAAN DEEP LEARNING (MJM): Prinsip Mindful, Meaningful, dan Joyful diwujudkan secara kontekstual ke dalam pengalaman belajar, BUKAN sebagai pengganti sintaks model atau pembagian waktu kaku. Wujudkan Mindful pada apersepsi fokus kesadaran belajar di awal, Meaningful pada keterlibatan berpikir mendalam saat menjalankan sintaks inti, dan Joyful pada kepuasan refleksi serta apresiasi keberhasilan belajar di penutup.\n"
            . "  3. TINDAKAN NYATA SISWA & GURU: Deskripsi setiap tahap HARUS menjabarkan aktivitas operasional konkret siswa dan peran guru (apa yang diamati, dihitung, ditulis, didiskusikan, atau dihasilkan), BUKAN instruksi retoris pasif.\n"
            . "  4. KEPATUHAN SARANA FISIK: " . ($guidelines['sarana'] ?? "Gunakan sarana yang nyata di kelas jenjang {$jenjang}.") . "\n"
            . "  5. MANAJEMEN KELAS & SKALA ROMBEL: " . ($guidelines['skala_kelas'] ?? "Atur kelompok belajar proporsional sesuai rombel.") . "\n"
            . "  6. ALTERNATIF KENDALA SARANA: Jika ada media tayang/video guru yang direkomendasikan, WAJIB sertakan alternatif konvensional (misal: 'Jika proyektor/internet tidak tersedia, guru menggunakan carta dinding, gambar cetak, atau demonstrasi manual di papan tulis').\n"
            . "  7. ARTEFAK TERUKUR & PENUTUP TUNTAS: Tahap inti harus menghasilkan bukti belajar nyata (LKPD, peta konsep, laporan solusi kelompok) yang selaras dengan asesmen di Bagian K. Setiap pertemuan WAJIB ditutup tuntas dengan refleksi bermakna dan apresiasi (Joyful) sesuai alokasi waktu yang dijadwalkan.\n"
            . "  8. TOTAL WAKTU TEPAT: Jumlah seluruh alokasi waktu setiap pertemuan HARUS SAMA PERSIS dengan durasi sesi yang ditetapkan.\n"
            . ($jumlahPertemuan > 6
                ? "  9. KEPADATAN DESKRIPSI (MODUL BESAR {$jumlahPertemuan} PERTEMUAN): Karena modul ini mencakup {$jumlahPertemuan} pertemuan, batasi deskripsi aktivitas maksimal sekitar 2 kalimat per sel tabel. Tetap pertahankan sintaks model pembelajaran yang benar dan aktivitas operasional siswa yang konkret, agar seluruh bagian A sampai K selesai tuntas sempurna tanpa terpotong batas token.\n\n"
                : "\n")
            . "<h3>G. Sumber Belajar</h3>\n"
            . "Sebutkan buku pegangan siswa/guru, media pembelajaran fisik/digital yang realistis, LKPD, dan sumber belajar lingkungan nyata yang relevan.\n\n"
            . "<h3>H. Keterampilan</h3>\n"
            . "Uraikan keterampilan abad-21 dan dimensi profil kelulusan yang dilatih secara konkret (bernalar kritis, kreativitas, gotong royong/kolaborasi, komunikasi).\n\n"
            . "<h3>I. Penilaian Sikap</h3>\n"
            . "Uraikan teknik observasi sikap (keaktifan, kedisiplinan, kejujuran, dan tanggung jawab selama proses pembelajaran).\n\n"
            . "<h3>J. Kriteria Kelulusan</h3>\n"
            . (!empty($kktpContextText)
                ? "Selaraskan indikator ketuntasan dengan kriteria ketercapaian resmi berikut:\n{$kktpContextText}"
                : "Uraikan kriteria ketercapaian tujuan pembelajaran peserta didik pada materi ini secara terukur berdasarkan rubrik observasi proses kerja kelompok dan bukti karya formatif.\n\n")
            . "<h3>K. Penilaian / Asesmen</h3>\n"
            . "Uraikan secara spesifik dan selaras dengan kegiatan di Bagian F serta Tujuan Pembelajaran (TP):\n"
            . "1. Penilaian Proses (Formatif): Observasi keaktifan diskusi, partisipasi kelompok, dan lembar cek pengerjaan LKPD dengan menyebutkan kode/nomor TP yang diobservasi.\n"
            . "2. Penilaian Hasil (Sumatif): Tes tertulis, lembar kerja mandiri, atau rubrik penilaian produk/karya nyata. WAJIB sertakan tabel kisi-kisi atau pemetaan yang menghubungkan setiap bentuk instrumen/soal secara eksplisit dengan nomor/kode Tujuan Pembelajaran (TP) yang diukur (hanya mengacu pada TP yang terdaftar di Bagian C/Data Input, dilarang mengarang kode TP baru).\n\n"
            . "ATURAN TAMBAHAN (KETAT & MUTLAK):\n"
            . "1. FORMAT JUDUL BAGIAN: Gunakan tag <h3> dengan format LANGSUNG huruf dan judul saja, TANPA kata 'Bagian'. Contoh: <h3>B. Kompetensi Awal</h3>.\n"
            . "2. JANGAN MENGHASILKAN bagian Tanda Tangan (L, M, dst) karena sistem pencetak otomatis telah menyediakan kolom tanda tangan resmi di bawah modul.\n"
            . "3. KEMBALIKAN HANYA PURE HTML (tanpa markdown, tanpa code block ```html).\n"
            . "4. DILARANG MENGGUNAKAN TANDA BACKSLASH (\\) DALAM BENTUK APAPUN: dilarang rumus LaTeX (\\( \\), \\div, \\times, \\%), dilarang escape penomoran (\\d., \\e.), dilarang backslash di akhir kalimat/baris/sel tabel (Bobot\\, Nilai\\).\n\n"
            . "DATA INPUT PEMBELAJARAN:\n"
            . "Sekolah: {$schoolName} ({$jenjang}" . (!empty($schoolCity) ? " di {$schoolCity}" : "") . ")\n"
            . "Penyusun: {$penyusun}\n"
            . "Mapel: {$subjectName}\n"
            . "Materi: {$materiPokok}\n"
            . "Kelas: {$tingkatKelas} (Jenjang {$jenjang})\n"
            . "Karakteristik Rombel: {$infoRombel}\n"
            . "Sarana Terdaftar: {$fasilitasInfo}\n"
            . "Alokasi Waktu: {$alokasiWaktuInfo}\n"
            . "Rujukan Jadwal & Sesi: {$jadwalKonteksInfo}\n"
            . "CP:\n{$cpDeskripsiAll}"
            . "TP:\n{$tpDeskripsi}\n\n"
            . "KEMBALIKAN HANYA PURE HTML (TANPA MARKDOWN). Pastikan seluruh bagian A sampai K dan seluruh pertemuan selesai tuntas sempurna.";

        return $prompt;
    }

    /**
     * Simpan modul hasil copy-paste dari AI (mode manual tanpa API)
     */
    public function saveManualModul()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return redirect()->back()->with('error', 'Tidak diizinkan.');
        }

        $atpId = $this->request->getPost('atp_id');
        $subjectId = $this->request->getPost('subject_id');
        $content = $this->request->getPost('modul_content');

        if (empty($content) || strlen(strip_tags($content)) < 50) {
            return redirect()->back()->with('error', 'Konten modul terlalu pendek. Pastikan Anda paste hasil dari AI dengan benar.');
        }

        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return redirect()->back()->with('error', 'ATP tidak ditemukan.');
        }

        $classId = $atpRaw['class_id'];

        $activeYear = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear ? (int)$activeYear['id'] : null;

        // Resolve teacher
        if ($user['role_id'] == 3) {
            $targetTeacherId = $user['related_id'];
        } else {
            $assignQuery = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId);
            if ($activeYearId) {
                $assignQuery->where('academic_year_id', $activeYearId);
            }
            $assignment = $assignQuery->get()->getRowArray();
            if (!$assignment) {
                $assignment = $this->db->table('teaching_assignments')
                    ->where('subject_id', $subjectId)
                    ->where('class_id', $classId)
                    ->get()->getRowArray();
            }
            $targetTeacherId = $assignment['teacher_id'] ?? null;
        }

        if (!$targetTeacherId) {
            return redirect()->back()->with('error', 'Tidak dapat menentukan guru pengampu.');
        }

        // Ambil konteks jadwal riil untuk menentukan durasi pertemuan aktual (P2-1)
        $durationContext = null;
        try {
            $contextBuilder = $this->getAiContextBuilder();
            $ctxResult = $contextBuilder->buildModulAjarContext(
                (int)$atpId,
                $classId ? (int)$classId : null,
                (int)$subjectId,
                (int)$targetTeacherId,
                $activeYearId
            );
            if (!empty($ctxResult['success']) && !empty($ctxResult['data']['jadwal'])) {
                $jadwalData = $ctxResult['data']['jadwal'];
                $durationContext = !empty($jadwalData['pertemuan_list'])
                    ? $jadwalData['pertemuan_list']
                    : ($jadwalData['menit_per_pertemuan'] ?? null);
            }
        } catch (\Throwable $e) {
            log_message('error', 'saveManualModul schedule resolution error: ' . $e->getMessage());
        }

        // Jalankan sanitasi dan auto-healer dengan konteks durasi aktual dan data TP (P1 Tahap 10)
        $tps = !empty($ctxResult['data']['kurikulum']['tps']) ? $ctxResult['data']['kurikulum']['tps'] : [];
        $content = $this->sanitizeAndHealModulContent($content, $durationContext ?? 0, $tps);

        $modulModel = new \App\Models\ModulAjarModel();

        // Cek duplikat
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();
        if ($existing) {
            if (!empty($existing['file_pdf'])) {
                $oldPath = FCPATH . 'uploads/modul_ajar/' . $existing['file_pdf'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $modulModel->update($existing['id'], [
                'content'  => $content,
                'tipe'     => 'editor',
                'file_pdf' => null,
            ]);
            $msg = 'Modul berhasil diperbarui dari input manual.';
        } else {
            $modulModel->insert([
                'atp_id'     => $atpId,
                'subject_id' => $subjectId,
                'class_id'   => $classId,
                'teacher_id' => $targetTeacherId,
                'content'    => $content,
                'tipe'       => 'editor',
                'file_pdf'   => null,
            ]);
            $msg = 'Modul berhasil disimpan dari input manual.';
        }

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}"))
            ->with('success', $msg);
    }

    /**
     * Halaman form untuk guru menyusun modul ajar mandiri / copas dari Word
     */
    public function createModulAjar($atpId, $classId)
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'] ?? 0, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan membuat Modul Ajar.');
        }

        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return redirect()->back()->with('error', 'ATP tidak ditemukan.');
        }

        $subject = $this->subjectModel->find($atpRaw['subject_id']);
        $class = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        $atpResult = $this->loadAtpWithElemen((int)$atpRaw['subject_id']);
        $atpFull = null;
        foreach ($atpResult['list'] as $a) {
            if ($a['id'] == $atpId) { $atpFull = $a; break; }
        }
        $atp = $atpFull ?? $atpRaw;

        // Cek jika modul teks sudah ada, langsung arahkan ke edit
        $modulModel = new \App\Models\ModulAjarModel();
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();
        if ($existing && ($existing['tipe'] ?? 'editor') === 'editor') {
            return redirect()->to(base_url("admin/administrasi-guru/modul-ajar/edit/{$existing['id']}/{$classId}"));
        }

        $data['title'] = 'Buat Modul Ajar Mandiri / Copas Word';
        $data['atp'] = $atp;
        $data['class_id'] = $classId;
        $data['class'] = $class;
        $data['subject'] = $subject;
        $data['existing'] = $existing;

        return view('admin/administrasi_guru/modul_ajar_create', $data);
    }

    /**
     * Simpan modul ajar hasil ketik mandiri / copas dari Word
     */
    public function saveModulAjar()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'] ?? 0, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan membuat Modul Ajar.');
        }

        $atpId     = $this->request->getPost('atp_id');
        $classId   = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        $content   = $this->request->getPost('content');

        if (empty(trim(strip_tags($content)))) {
            return redirect()->back()->withInput()->with('error', 'Konten modul tidak boleh kosong.');
        }

        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return redirect()->back()->with('error', 'ATP tidak ditemukan.');
        }

        if ($user['role_id'] == 3) {
            $targetTeacherId = $user['related_id'];
        } else {
            $assignment = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->get()->getRowArray();
            $targetTeacherId = $assignment['teacher_id'] ?? null;
        }

        if (!$targetTeacherId) {
            return redirect()->back()->with('error', 'Tidak dapat menentukan guru pengampu.');
        }

        $modulModel = new \App\Models\ModulAjarModel();
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();

        if ($existing) {
            if (!empty($existing['file_pdf'])) {
                $oldPath = FCPATH . 'uploads/modul_ajar/' . $existing['file_pdf'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $modulModel->update($existing['id'], [
                'content'  => $content,
                'tipe'     => 'editor',
                'file_pdf' => null,
            ]);
            $msg = 'Modul Ajar mandiri berhasil diperbarui.';
        } else {
            $modulModel->insert([
                'atp_id'     => $atpId,
                'subject_id' => $subjectId,
                'class_id'   => $classId,
                'teacher_id' => $targetTeacherId,
                'content'    => $content,
                'tipe'       => 'editor',
                'file_pdf'   => null,
            ]);
            $msg = 'Modul Ajar mandiri berhasil disimpan.';
        }

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}"))
            ->with('success', $msg);
    }

    /**
     * Upload dokumen PDF Modul Ajar
     */
    public function uploadPdfModul()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'] ?? 0, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan mengunggah file Modul Ajar.');
        }

        $atpId     = $this->request->getPost('atp_id');
        $classId   = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');

        $file = $this->request->getFile('pdf_file');

        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'Silakan pilih file PDF yang valid.');
        }

        $ext = strtolower($file->getClientExtension());
        if ($ext !== 'pdf') {
            return redirect()->back()->with('error', 'Format file harus berupa dokumen PDF (.pdf).');
        }

        if ($file->getSizeByUnit('mb') > 15) {
            return redirect()->back()->with('error', 'Ukuran file PDF maksimal 15 MB.');
        }

        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return redirect()->back()->with('error', 'ATP tidak ditemukan.');
        }

        if ($user['role_id'] == 3) {
            $targetTeacherId = $user['related_id'];
        } else {
            $assignment = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->get()->getRowArray();
            $targetTeacherId = $assignment['teacher_id'] ?? null;
        }

        if (!$targetTeacherId) {
            return redirect()->back()->with('error', 'Tidak dapat menentukan guru pengampu.');
        }

        $uploadDir = FCPATH . 'uploads/modul_ajar/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $newName = 'modul_atp_' . $atpId . '_class_' . $classId . '_' . time() . '.' . $ext;
        $file->move($uploadDir, $newName);

        $modulModel = new \App\Models\ModulAjarModel();
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();

        if ($existing) {
            if (!empty($existing['file_pdf'])) {
                $oldPath = $uploadDir . $existing['file_pdf'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $modulModel->update($existing['id'], [
                'tipe'     => 'pdf',
                'file_pdf' => $newName,
            ]);
            $msg = 'File PDF Modul Ajar berhasil diunggah dan menggantikan modul sebelumnya.';
        } else {
            $modulModel->insert([
                'atp_id'     => $atpId,
                'subject_id' => $subjectId,
                'class_id'   => $classId,
                'teacher_id' => $targetTeacherId,
                'content'    => '',
                'tipe'       => 'pdf',
                'file_pdf'   => $newName,
            ]);
            $msg = 'File PDF Modul Ajar berhasil diunggah.';
        }

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}"))
            ->with('success', $msg);
    }

    /**
     * Tampilkan atau streaming file PDF Modul Ajar
     */
    public function viewPdfModul($id)
    {
        $modulModel = new \App\Models\ModulAjarModel();
        $modul = $modulModel->find($id);

        if (!$modul || empty($modul['file_pdf'])) {
            return redirect()->back()->with('error', 'File PDF Modul tidak ditemukan.');
        }

        $filePath = FCPATH . 'uploads/modul_ajar/' . $modul['file_pdf'];
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File PDF fisik tidak ditemukan di server.');
        }

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . basename($modul['file_pdf']) . '"')
            ->setBody(file_get_contents($filePath));
    }

    /**
     * Hitung struktur dan alokasi pertemuan pembelajaran berdasarkan Jadwal Riil (Tier 1),
     * Program Semester (Tier 2), atau Standar Jenjang Kurikulum (Tier 3).
     *
     * @param int|string|null $classId
     * @param int|string|null $subjectId
     * @param int|string|null $atpId
     * @param int $totalJp
     * @param int $schoolLevel 1=SD, 2=SMP, 3=SMA
     * @return array
     */
    private function hitungAlokasiPertemuan($classId, $subjectId, $atpId, int $totalJp, int $schoolLevel = 2): array
    {
        if (function_exists('helper')) {
            helper('jp');
        }

        $jenjang = ($schoolLevel == 1) ? 'SD' : (($schoolLevel == 2) ? 'SMP' : 'SMA/SMK');
        $menitPerJpStandard = ($schoolLevel == 1) ? 35 : (($schoolLevel == 2) ? 40 : 45);

        $activeYear = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : null;

        $namaHariIndo = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        // TIER 1: Cek Jadwal Riil di Tabel schedules (Paling Akurat)
        $schedules = [];
        if (!empty($classId) && !empty($subjectId)) {
            $builder = $this->db->table('schedules')
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId);
            if ($activeYearId) {
                $builder->where('academic_year_id', $activeYearId);
            }
            $schedules = $builder
                ->orderBy('day_of_week', 'ASC')
                ->orderBy('start_time', 'ASC')
                ->get()->getResultArray();
        }

        $jadwalSlots = [];
        foreach ($schedules as $sc) {
            $slotMenit = 0;
            if (!empty($sc['start_time']) && !empty($sc['end_time'])) {
                $slotMenit = max(0, (strtotime($sc['end_time']) - strtotime($sc['start_time'])) / 60);
            }

            if (function_exists('hitung_jp_dari_waktu') && !empty($sc['start_time']) && !empty($sc['end_time'])) {
                $slotJp = hitung_jp_dari_waktu($sc['start_time'], $sc['end_time'], $schoolLevel);
            } elseif ($slotMenit > 0) {
                $slotJp = max(1, (int) round($slotMenit / $menitPerJpStandard));
            } else {
                $slotJp = 2;
            }

            if ($slotMenit <= 0) {
                $slotMenit = $slotJp * $menitPerJpStandard;
            }

            $hariNama = $namaHariIndo[(int)($sc['day_of_week'] ?? 1)] ?? 'Hari Belajar';
            $jadwalSlots[] = [
                'hari'  => $hariNama,
                'jp'    => $slotJp,
                'menit' => (int) $slotMenit,
                'start' => substr((string)$sc['start_time'], 0, 5),
                'end'   => substr((string)$sc['end_time'], 0, 5),
            ];
        }

        $pertemuanList = [];
        $tierDigunakan = 'standar_jenjang';

        if (!empty($jadwalSlots)) {
            // TIER 1: Jadwal Riil ditemukan
            $tierDigunakan = 'jadwal_riil';
            $sisaJp = $totalJp;
            $slotIndex = 0;
            $slotCount = count($jadwalSlots);
            $pNo = 1;

            while ($sisaJp > 0) {
                if ($pNo > 40) break;
                $currentSlot = $jadwalSlots[$slotIndex % $slotCount];
                if (($currentSlot['jp'] ?? 0) <= 0) break; // Defensive guard: cegah loop jika data JP korup

                $jpThis = min($currentSlot['jp'], $sisaJp);
                $menitThis = (int) round(($jpThis / max(1, $currentSlot['jp'])) * $currentSlot['menit']);
                if ($menitThis < 25) {
                    $menitThis = $jpThis * $menitPerJpStandard;
                }

                $pertemuanList[] = [
                    'pertemuan' => $pNo,
                    'hari'      => $currentSlot['hari'],
                    'jp'        => $jpThis,
                    'menit'     => $menitThis,
                ];

                $sisaJp -= $jpThis;
                $slotIndex++;
                $pNo++;
            }

            $jumlahPertemuan = count($pertemuanList);
            $jpPerPertemuan = (int) round(array_sum(array_column($pertemuanList, 'jp')) / max(1, $jumlahPertemuan));
            if ($jpPerPertemuan < 1) $jpPerPertemuan = 1;

            $slotDesc = implode(', ', array_map(function($s) {
                return "{$s['hari']} ({$s['jp']} JP / {$s['menit']} menit, {$s['start']}-{$s['end']})";
            }, $jadwalSlots));
            $jadwalKonteksInfo = "Dihitung dari Jadwal Pelajaran Riil Kelas ({$slotCount} sesi tatap muka per pekan: {$slotDesc}). Total materi {$totalJp} JP diselesaikan dalam {$jumlahPertemuan} Pertemuan.";
        } else {
            // TIER 2: Cek Distribusi Program Semester (Promes) jika jadwal belum diisi
            $prosemRows = $this->db->table('prosem_distributions')
                ->where('atp_id', $atpId)
                ->where('jp >', 0)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();

            if (!empty($prosemRows)) {
                $tierDigunakan = 'promes';
                $sumJp = (int) array_sum(array_column($prosemRows, 'jp'));
                $totalWeeks = count($prosemRows);
                $jpPerMinggu = (int) round($sumJp / max(1, $totalWeeks));
                if ($jpPerMinggu <= 0) $jpPerMinggu = 2;

                // Tentukan jumlah pertemuan per minggu dari Promes
                $pertemuanPerMinggu = ($jpPerMinggu <= 3) ? 1 : 2;
                $jpPerPertemuan = max(1, (int) round($jpPerMinggu / $pertemuanPerMinggu));

                $sisaJp = $totalJp;
                $pNo = 1;
                while ($sisaJp > 0) {
                    if ($pNo > 40) break;
                    $jpThis = min($jpPerPertemuan, $sisaJp);
                    $menitThis = $jpThis * $menitPerJpStandard;
                    $pertemuanList[] = [
                        'pertemuan' => $pNo,
                        'hari'      => 'Pertemuan ke-' . $pNo,
                        'jp'        => $jpThis,
                        'menit'     => $menitThis,
                    ];
                    $sisaJp -= $jpThis;
                    $pNo++;
                }
                $jumlahPertemuan = count($pertemuanList);

                $jadwalKonteksInfo = "Dihitung dari Program Semester ({$totalWeeks} pekan efektif, rata-rata {$jpPerMinggu} JP/minggu). Materi {$totalJp} JP dialokasikan ke {$jumlahPertemuan} Pertemuan.";
            } else {
                // TIER 3: Standar Kurikulum berdasarkan Jenjang {$jenjang}
                $tierDigunakan = 'standar_jenjang';
                if ($totalJp <= 3) {
                    $jpPerPertemuan = $totalJp;
                } elseif ($totalJp % 2 === 0) {
                    $jpPerPertemuan = 2;
                } elseif ($totalJp % 3 === 0) {
                    $jpPerPertemuan = 3;
                } else {
                    $jpPerPertemuan = 2;
                }

                $sisaJp = $totalJp;
                $pNo = 1;
                while ($sisaJp > 0) {
                    if ($pNo > 40) break;
                    $jpThis = min($jpPerPertemuan, $sisaJp);
                    $menitThis = $jpThis * $menitPerJpStandard;
                    $pertemuanList[] = [
                        'pertemuan' => $pNo,
                        'hari'      => 'Pertemuan ke-' . $pNo,
                        'jp'        => $jpThis,
                        'menit'     => $menitThis,
                    ];
                    $sisaJp -= $jpThis;
                    $pNo++;
                }
                $jumlahPertemuan = count($pertemuanList);

                $jadwalKonteksInfo = "Dihitung dari Standar Kurikulum Jenjang {$jenjang} (1 JP = {$menitPerJpStandard} menit). Materi {$totalJp} JP dialokasikan ke {$jumlahPertemuan} Pertemuan.";
            }
        }

        // Hitung waktuMeaningful per pertemuan (tiap pertemuan: Mindful 10 mnt, Joyful 15 mnt, sisa = Meaningful)
        foreach ($pertemuanList as &$pItem) {
            $pMenit = (int)$pItem['menit'];
            if ($pMenit < 35) {
                $pItem['meaningfulMenit'] = max(5, $pMenit - 10);
            } else {
                $pItem['meaningfulMenit'] = max(10, $pMenit - 25);
            }
        }
        unset($pItem);

        // Periksa keseragaman durasi pertemuan
        $isUniform = true;
        $firstMenit = $pertemuanList[0]['menit'] ?? ($menitPerJpStandard * 2);
        foreach ($pertemuanList as $p) {
            if ($p['menit'] !== $firstMenit) {
                $isUniform = false;
                break;
            }
        }

        // Gunakan durasi slot tipikal/pertama (bukan mean pembulatan yang menyesatkan)
        $menitPerPertemuan = $firstMenit;
        if ($menitPerPertemuan < 35) {
            $waktuMindful    = 5;
            $waktuJoyful     = 5;
            $waktuMeaningful = max(5, $menitPerPertemuan - 10);
        } else {
            $waktuMindful    = 10;
            $waktuJoyful     = 15;
            $waktuMeaningful = max(10, $menitPerPertemuan - 25);
        }

        if ($isUniform) {
            $alokasiWaktuLabel = "{$totalJp} JP ({$jumlahPertemuan} Pertemuan × {$jpPerPertemuan} JP)";
            $alokasiWaktuInfo  = "{$totalJp} JP → {$jumlahPertemuan} Pertemuan (@ {$jpPerPertemuan} JP / {$menitPerPertemuan} menit per pertemuan)";
            $langkahWaktuText  = "SETIAP tabel pertemuan (TERMASUK PERTEMUAN TERAKHIR KE-{$jumlahPertemuan}) WAJIB memiliki TEPAT 3 baris lengkap:\n"
                . "  Baris 1 → Tahap: Mindful    | Waktu: " . $waktuMindful . " menit\n"
                . "  Baris 2 → Tahap: Meaningful | Waktu: " . $waktuMeaningful . " menit\n"
                . "  Baris 3 → Tahap: Joyful     | Waktu: " . $waktuJoyful . " menit\n";
        } else {
            $alokasiWaktuLabel = "{$totalJp} JP ({$jumlahPertemuan} Pertemuan)";
            $ringkasList = array_map(function($p) {
                return "P{$p['pertemuan']}: {$p['jp']}JP/{$p['menit']}m";
            }, $pertemuanList);
            $alokasiWaktuInfo  = "{$totalJp} JP → {$jumlahPertemuan} Pertemuan (" . implode(', ', $ringkasList) . ")";

            $rincianWaktuPertemuan = "";
            foreach ($pertemuanList as $p) {
                $pMenit = (int)($p['menit'] ?? 80);
                if ($pMenit < 35) {
                    $pMindful = 5;
                    $pJoyful = 5;
                    $pMeaningful = max(5, $pMenit - 10);
                } else {
                    $pMindful = 10;
                    $pJoyful = 15;
                    $pMeaningful = max(10, $pMenit - 25);
                }
                $rincianWaktuPertemuan .= "  * Tabel Pertemuan ke-{$p['pertemuan']} ({$p['jp']} JP / {$pMenit} menit): Baris 1 Mindful ({$pMindful} menit) | Baris 2 Meaningful ({$pMeaningful} menit) | Baris 3 Joyful ({$pJoyful} menit)\n";
            }
            $langkahWaktuText  = "SETIAP tabel pertemuan (TERMASUK PERTEMUAN TERAKHIR KE-{$jumlahPertemuan}) WAJIB memiliki TEPAT 3 baris lengkap (Tahap: Mindful, Meaningful, Joyful). Perhatikan alokasi waktu aktual masing-masing pertemuan:\n"
                . $rincianWaktuPertemuan;
        }

        return [
            'jumlahPertemuan'    => $jumlahPertemuan,
            'jpPerPertemuan'     => $jpPerPertemuan,
            'menitPerPertemuan'  => $menitPerPertemuan,
            'waktuMeaningful'    => $waktuMeaningful,
            'pertemuanList'      => $pertemuanList,
            'isUniform'          => $isUniform,
            'alokasiWaktuLabel'  => $alokasiWaktuLabel,
            'alokasiWaktuInfo'   => $alokasiWaktuInfo,
            'langkahWaktuText'   => $langkahWaktuText,
            'jadwalKonteksInfo'  => $jadwalKonteksInfo,
            'tierDigunakan'      => $tierDigunakan,
        ];
    }

    public function generateModulAjar()
    {
        try {
            @set_time_limit(180);

            $atpId       = $this->request->getPost('atp_id');
            $subjectId   = $this->request->getPost('subject_id');
            $postClassId = $this->request->getPost('class_id');
            
            $user = session()->get('user');
            if (!in_array($user['role_id'], [1, 3])) {
                return redirect()->to(base_url('admin/administrasi-guru/modul-ajar'))->with('error', 'Hanya guru atau admin yang dapat meng-generate Modul.');
            }

            // Load ATP beserta elemen CP dan TP (struktur baru)
            $atpRaw = $this->atpModel->find($atpId);
            if (!$atpRaw) {
                $fallbackUrl = ($postClassId && $subjectId)
                    ? base_url("admin/administrasi-guru/modul-ajar?class_id={$postClassId}&subject_id={$subjectId}")
                    : base_url('admin/administrasi-guru/modul-ajar');
                return redirect()->to($fallbackUrl)->with('error', 'ATP tidak ditemukan.');
            }

            $activeYear = $this->yearModel->getActiveYear();
            $activeYearId = $activeYear ? (int)$activeYear['id'] : null;

            // Validasi class_id otoritatif
            $classId = null;
            if (!empty($postClassId)) {
                $classExists = $this->db->table('classes')->where('id', $postClassId)->get()->getRowArray();
                if ($classExists) {
                    if ($user['role_id'] == 3) {
                        $authQuery = $this->db->table('teaching_assignments')
                            ->where('teacher_id', $user['related_id'])
                            ->where('class_id', $postClassId)
                            ->where('subject_id', $subjectId);
                        if ($activeYearId) {
                            $authQuery->where('academic_year_id', $activeYearId);
                        }
                        $hasAccess = $authQuery->countAllResults();
                        if ($hasAccess === 0) {
                            $hasAccess = $this->db->table('teaching_assignments')
                                ->where('teacher_id', $user['related_id'])
                                ->where('class_id', $postClassId)
                                ->where('subject_id', $subjectId)
                                ->countAllResults();
                        }
                        if ($hasAccess > 0) {
                            $classId = (int)$postClassId;
                        }
                    } else {
                        $classId = (int)$postClassId;
                    }
                }
            }
            if (empty($classId) && !empty($atpRaw['class_id'])) {
                $classId = (int)$atpRaw['class_id'];
            }

            $redirectUrl = base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}");
            
            $teacherName = '-';
            if ($user['role_id'] == 3) {
                $teacher = $this->teacherModel->find($user['related_id']);
                $teacherName = !empty($teacher['name']) ? $teacher['name'] : '-';
                $apiKey = $teacher['gemini_api_key'] ?? '';
                $aiProvider = $teacher['ai_provider'] ?? 'gemini';
                $targetTeacherId = (int)$user['related_id'];
            } else {
                // Admin
                $userData = $this->db->table('users')->where('id', $user['id'])->get()->getRowArray();
                $apiKey = $userData['gemini_api_key'] ?? '';
                $aiProvider = $userData['ai_provider'] ?? 'gemini';
                
                // Resolve Teacher based on subject & class for active year
                $assignQuery = $this->db->table('teaching_assignments')
                    ->where('subject_id', $subjectId)
                    ->where('class_id', $classId);
                if ($activeYearId) {
                    $assignQuery->where('academic_year_id', $activeYearId);
                }
                $assignment = $assignQuery->get()->getRowArray();
                if (!$assignment) {
                    $assignment = $this->db->table('teaching_assignments')
                        ->where('subject_id', $subjectId)
                        ->where('class_id', $classId)
                        ->get()->getRowArray();
                }
                
                if (!$assignment) {
                    return redirect()->to($redirectUrl)->with('error', 'Tidak ada guru yang diplot untuk Kelas dan Mapel ini. Modul tidak dapat digenerate karena membutuhkan identitas Guru Pengampu.');
                }
                $targetTeacherId = (int)$assignment['teacher_id'];
                $teacher = $this->teacherModel->find($targetTeacherId);
                $teacherName = !empty($teacher['name']) ? $teacher['name'] : '-';
            }

            if (empty($apiKey)) {
                return redirect()->to($redirectUrl)->with('error', 'Anda belum mengatur Gemini API Key.');
            }

            $contextBuilder = $this->getAiContextBuilder();

            // Kondisi 3: Cek apakah kueri fasilitas mengalami kesalahan teknis (QUERY_ERROR)
            $facilityCheck = $contextBuilder->getConfirmedFacilities();
            if (isset($facilityCheck['status']) && $facilityCheck['status'] === 'QUERY_ERROR') {
                return redirect()->to($redirectUrl)->with('error', 'Gagal menyiapkan konteks AI: Terjadi kesalahan kueri database pada fasilitas sekolah.');
            }

            // Kondisi 2: Validasi integritas relasi via AiContextBuilder
            $contextResult = $contextBuilder->buildModulAjarContext((int)$atpId, $classId, (int)$subjectId, $targetTeacherId, $activeYearId);
            if (!$contextResult['success']) {
                return redirect()->to($redirectUrl)->with('error', $contextResult['message']);
            }

            $context = $contextResult['data'];
            $prompt = $this->buildModulPromptFromContext($context, $teacherName);
            $pertemuanList = $context['jadwal']['pertemuan_list'] ?? [];

            $response = null;
            $httpCode = 0;
            $curlError = '';

        if ($aiProvider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $headers = [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ];
            // Model rekomendasi resmi Groq (Llama 3.3 70B sudah didekomisi per 16 Agustus 2026)
            $groqModels = [
                'openai/gpt-oss-120b',
                'qwen/qwen3.6-27b',
                'openai/gpt-oss-20b',
                'qwen-2.5-32b',
                'llama-3.1-8b-instant'
            ];

            foreach ($groqModels as $model) {
                $payload = json_encode([
                    "model" => $model,
                    "messages" => [
                        ["role" => "user", "content" => $prompt]
                    ],
                    "temperature" => 0.7,
                    "max_tokens" => 8192,
                    "reasoning_format" => "parsed"
                ]);

                $attempt = 0;
                while ($attempt < 2) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode === 200) {
                        break 2; // Sukses, keluar dari perulangan model
                    }

                    // Jika 400 atau 404 (model tidak didukung/tidak ada akses di akun ini), coba model berikutnya
                    if (in_array($httpCode, [400, 404])) {
                        break;
                    }

                    if ($httpCode === 429 || $httpCode === 503) {
                        sleep(2);
                    } else {
                        break;
                    }
                }
            }
        } else {
            // Gemini (dengan fallback model)
            $geminiModels = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
            $headers = ['Content-Type: application/json'];
            $payload = json_encode([
                "contents" => [
                    ["parts" => [["text" => $prompt]]]
                ],
                "generationConfig" => [
                    "maxOutputTokens" => 8192,
                    "temperature" => 0.7
                ]
            ]);

            foreach ($geminiModels as $gModel) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$gModel}:generateContent?key=" . $apiKey;
                $attempt = 0;
                while ($attempt < 2) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $curlError = curl_error($ch);
                    curl_close($ch);

                    if ($httpCode === 200) {
                        break 2;
                    }

                    if (in_array($httpCode, [400, 404])) {
                        break;
                    }

                    if ($httpCode === 429 || $httpCode === 503) {
                        sleep(2);
                    } else {
                        break;
                    }
                }
            }
        }

        if ($httpCode != 200) {
            log_message('error', "Modul Ajar AI ($aiProvider) Failed. HTTP: $httpCode. Curl Error: $curlError. Response: $response");
            $errorMsg = "Gagal memanggil API $aiProvider (HTTP $httpCode). ";
            if ($response) {
                $errRes = json_decode($response, true);
                if (isset($errRes['error']['message'])) {
                    $errorMsg .= "Error API: " . $errRes['error']['message'];
                } else {
                    $errorMsg .= "Pastikan API Key valid atau cek batas kuota.";
                }
            } elseif ($curlError) {
                $errorMsg .= "cURL: $curlError";
            }
            if ($httpCode == 503) {
                $errorMsg .= " (Server $aiProvider sedang padat/antre. Anda juga dapat beralih ke provider Groq di menu API Key yang lebih cepat & stabil).";
            }
            return redirect()->to($redirectUrl)->with('error', $errorMsg);
        }

        $resData = json_decode($response, true);
        if ($aiProvider === 'groq') {
            $content = $resData['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $content);
            $blockedReason = $resData['choices'][0]['finish_reason'] ?? 'Unknown';
        } else {
            $content = '';
            if (!empty($resData['candidates'][0]['content']['parts'])) {
                foreach ($resData['candidates'][0]['content']['parts'] as $part) {
                    if (!empty($part['text'])) {
                        $content .= $part['text'];
                    }
                }
            }
            $blockedReason = $resData['candidates'][0]['finishReason'] ?? ($resData['promptFeedback']['blockReason'] ?? 'Unknown');
        }
        $content = preg_replace('/```(?:html)?(.*?)```/s', '$1', $content);

        if (empty(trim($content))) {
            return redirect()->to($redirectUrl)->with('error', "AI tidak mengembalikan teks modul ajar (Status: {$blockedReason}). Silakan coba generate ulang atau gunakan provider lain.");
        }

        // Jalankan auto-healer untuk menyempurnakan setiap pertemuan dan melengkapi asesmen Bagian K jika terpotong (P1 Tahap 10)
        $tps = !empty($context['kurikulum']['tps']) ? $context['kurikulum']['tps'] : [];
        $content = $this->sanitizeAndHealModulContent($content, $pertemuanList, $tps);

        $modulModel = new \App\Models\ModulAjarModel();
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();
        if ($existing) {
            if (!empty($existing['file_pdf'])) {
                $oldPath = FCPATH . 'uploads/modul_ajar/' . $existing['file_pdf'];
                if (file_exists($oldPath)) {
                    @unlink($oldPath);
                }
            }
            $modulModel->update($existing['id'], [
                'content'    => trim($content),
                'tipe'       => 'editor',
                'file_pdf'   => null,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } else {
            $modulModel->insert([
                'atp_id'     => $atpId,
                'subject_id' => $subjectId,
                'class_id'   => $classId,
                'teacher_id' => $targetTeacherId,
                'content'    => trim($content),
                'tipe'       => 'editor',
                'file_pdf'   => null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', 'Modul Ajar berhasil digenerate.');
    } catch (\Throwable $e) {
        log_message('error', 'generateModulAjar error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        $fallbackUrl = isset($redirectUrl) ? $redirectUrl : base_url('admin/administrasi-guru/modul-ajar');
        return redirect()->to($fallbackUrl)->with('error', 'Terjadi kesalahan sistem (500): ' . $e->getMessage());
    }
}

    public function editModulAjar($id, $classId)
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'] ?? 0, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan mengubah Modul Ajar.');
        }

        $modulModel = new \App\Models\ModulAjarModel();
        $modul = $modulModel->find($id);
        if (!$modul) return redirect()->back()->with('error', 'Modul tidak ditemukan.');
        
        // Auto-heal konten modul jika ada tabel pertemuan yang terpotong / belum memiliki baris Joyful
        $healed = $this->sanitizeAndHealModulContent($modul['content']);
        if ($healed !== $modul['content']) {
            $modulModel->update($id, ['content' => $healed]);
            $modul['content'] = $healed;
        }

        $data['title'] = 'Edit Modul Ajar';
        $data['modul'] = $modul;
        $data['class_id'] = $classId;
        $data['subject'] = $this->subjectModel->find($modul['subject_id']);
        
        return view('admin/administrasi_guru/modul_ajar_edit', $data);
    }

    public function updateModulAjar()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'] ?? 0, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak diizinkan mengubah Modul Ajar.');
        }

        $id = $this->request->getPost('id');
        $content = $this->request->getPost('content');
        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');
        
        $content = $this->sanitizeAndHealModulContent($content);

        $modulModel = new \App\Models\ModulAjarModel();
        $modulModel->update($id, [
            'content'  => $content,
            'tipe'     => 'editor',
        ]);
        
        return redirect()->to(base_url('admin/administrasi-guru/modul-ajar?class_id=' . $classId . '&subject_id=' . $subjectId))->with('success', 'Modul Ajar berhasil diperbarui.');
    }

    public function printModulAjar($id, $classId)
    {
        $modulModel = new \App\Models\ModulAjarModel();
        $modul = $modulModel->find($id);
        if (!$modul) return redirect()->back();

        if (($modul['tipe'] ?? 'editor') === 'pdf') {
            return redirect()->to(base_url('admin/administrasi-guru/modul-ajar/pdf/' . $id));
        }

        // Auto-heal konten modul jika ada tabel pertemuan yang terpotong / belum memiliki baris Joyful
        $healed = $this->sanitizeAndHealModulContent($modul['content']);
        if ($healed !== $modul['content']) {
            $modulModel->update($id, ['content' => $healed]);
            $modul['content'] = $healed;
        }

        $data['modul'] = $modul;
        $data['class_id'] = $classId;
        $data['subject'] = $this->subjectModel->find($modul['subject_id']);
        $data['school'] = $this->schoolModel->getProfile();

        // Ambil tahun ajaran aktif untuk memastikan data guru sesuai tahun ajaran yang berlaku
        $activeYear = $this->yearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : null;

        // Query assignment dengan filter tahun ajaran aktif, kelas, dan mata pelajaran
        $assignment = $this->teachingAssignmentModel
            ->where('class_id', $classId)
            ->where('subject_id', $modul['subject_id'])
            ->where('academic_year_id', $activeYearId)
            ->first();

        // Fallback: jika tidak ditemukan di tahun ajaran aktif, cari tanpa filter tahun ajaran
        if (!$assignment) {
            $assignment = $this->teachingAssignmentModel
                ->where('class_id', $classId)
                ->where('subject_id', $modul['subject_id'])
                ->first();
        }

        $data['teacher'] = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;
        
        return view('admin/administrasi_guru/modul_ajar_print', $data);
    }

    /**
     * Memastikan konten Modul Ajar selalu sempurna:
     * 1. Menjaga setiap tabel pertemuan memiliki 3 baris lengkap: Mindful, Meaningful, dan Joyful.
     * 2. Mengisi alokasi waktu jika kolom waktu kosong (Meaningful sisa waktu, Joyful 15 menit).
     * 3. Menyempurnakan kalimat yang terpotong jika output AI terkena batas token.
     * 4. Melengkapi Poin Penilaian (K) jika terpotong sebelum selesai.
     */
    protected function sanitizeAndHealModulContent(string $content, $menitPerPertemuan = 90, array $validTps = []): string
    {
        if (trim($content) === '') {
            return $content;
        }

        $content = preg_replace('/```(?:html)?(.*?)```/s', '$1', $content);
        $content = $this->cleanBackslashesAndLatex($content);

        if (class_exists('DOMDocument')) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><div id="__root_wrapper">' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();

            if ($loaded && ($wrapper = $dom->getElementById('__root_wrapper'))) {
                $tables = $wrapper->getElementsByTagName('table');
                $stepTableIndex = 0;
                foreach ($tables as $table) {
                    $tableHtml = $dom->saveHTML($table);
                    // Periksa apakah tabel ini adalah tabel langkah pembelajaran pertemuan
                    if (stripos($tableHtml, 'Sintaks') === false && stripos($tableHtml, 'Tahap') === false && stripos($tableHtml, 'Mindful') === false && stripos($tableHtml, 'Meaningful') === false) {
                        continue;
                    }

                    // Tentukan durasi untuk pertemuan ini (bisa array per-pertemuan atau durasi flat)
                    if (is_array($menitPerPertemuan)) {
                        $pInfo = $menitPerPertemuan[$stepTableIndex] ?? null;
                        if (is_array($pInfo)) {
                            $menitThisTable = (int)($pInfo['menit'] ?? 0);
                        } else {
                            $menitThisTable = (int)($pInfo ?? 0);
                        }
                    } else {
                        $menitThisTable = (int)$menitPerPertemuan;
                    }

                    if ($menitThisTable <= 0) {
                        // Deteksi durasi dari heading atau teks sebelum tabel jika tersedia di HTML
                        $prevNode = $table->previousSibling;
                        while ($prevNode) {
                            if ($prevNode->nodeType === XML_ELEMENT_NODE) {
                                $prevText = $prevNode->textContent;
                                if (preg_match('/(?:Pertemuan|\bKe-)\s*\d+.*?\(\s*(\d+)\s*(?:x\s*(\d+)\s*)?menit\s*\)/i', $prevText, $m)) {
                                    $menitThisTable = !empty($m[2]) ? ((int)$m[1] * (int)$m[2]) : (int)$m[1];
                                    break;
                                } elseif (preg_match('/(\d+)\s*menit/i', $prevText, $m)) {
                                    $menitThisTable = (int)$m[1];
                                    break;
                                }
                            }
                            $prevNode = $prevNode->previousSibling;
                        }
                    }

                    if ($menitThisTable <= 0) {
                        // Fallback aman default standar sekolah jika durasi benar-benar tidak diketahui
                        $menitThisTable = 80;
                    }

                    if ($menitThisTable < 35) {
                        $defaultClosingMenit = "5 menit";
                    } else {
                        $defaultClosingMenit = "15 menit";
                    }

                    $rows = $table->getElementsByTagName('tr');
                    $hasClosing = false;
                    $hasContentRows = false;

                    foreach ($rows as $row) {
                        // Lewati baris header <th>
                        $thCells = $row->getElementsByTagName('th');
                        if ($thCells->length > 0) {
                            continue;
                        }

                        $cells = $row->getElementsByTagName('td');
                        if ($cells->length === 0) {
                            continue;
                        }

                        $hasContentRows = true;
                        $rowText = $row->textContent;

                        // Deteksi apakah baris ini adalah baris penutup / refleksi / joyful
                        if (
                            stripos($rowText, 'Joyful') !== false ||
                            stripos($rowText, 'Penutup') !== false ||
                            stripos($rowText, 'Refleksi') !== false ||
                            stripos($rowText, 'Apresiasi') !== false
                        ) {
                            $hasClosing = true;
                        }

                        // Jika kolom waktu (kolom ke-4) kurang dari 4 tapi ada 3 kolom, lengkapi tanpa merusak baris
                        if ($cells->length === 3) {
                            $fallbackWaktu = $hasClosing ? $defaultClosingMenit : '15 menit';
                            $newTd = $dom->createElement('td', $fallbackWaktu);
                            $row->appendChild($newTd);
                        } elseif ($cells->length >= 4) {
                            // Jika ada kolom ke-4 namun kosong / hanya spasi, isi dengan durasi yang sesuai tanpa menimpa yang sudah ada
                            $timeCell = $cells->item(3);
                            if (trim($timeCell->textContent) === '') {
                                $fallbackWaktu = $hasClosing ? $defaultClosingMenit : '15 menit';
                                $timeCell->textContent = $fallbackWaktu;
                            }
                        }

                        // Rapikan deskripsi jika terpotong di tengah kalimat tanpa tanda baca akhir
                        if ($cells->length >= 3) {
                            $descCell = $cells->item(2);
                            $descText = trim($descCell->textContent);
                            if ($descText !== '' && !preg_match('/[.!?:]$/', $descText)) {
                                $descCell->textContent = $descText . '.';
                            }
                        }
                    }

                    // Hanya jika tabel sama sekali tidak memiliki baris penutup/refleksi (misal LLM terpotong pada batas token),
                    // tambahkan baris penutup yang proporsional agar tabel tuntas
                    if ($hasContentRows && !$hasClosing) {
                        $trClosing = $dom->createElement('tr');
                        $tdTahap   = $dom->createElement('td', 'Penutup (Joyful)');
                        $tdSintaks = $dom->createElement('td', 'Refleksi & Apresiasi');
                        $tdDesc    = $dom->createElement('td', 'Peserta didik berpartisipasi dalam kuis interaktif singkat / refleksi apresiatif yang menyenangkan untuk menguatkan pemahaman konsep materi.');
                        $tdWaktu   = $dom->createElement('td', $defaultClosingMenit);

                        $trClosing->appendChild($tdTahap);
                        $trClosing->appendChild($tdSintaks);
                        $trClosing->appendChild($tdDesc);
                        $trClosing->appendChild($tdWaktu);

                        $table->appendChild($trClosing);
                    }

                    $stepTableIndex++;
                }

                $fixedHtml = '';
                foreach ($wrapper->childNodes as $child) {
                    $fixedHtml .= $dom->saveHTML($child);
                }
                $content = $fixedHtml;
            }
        }

        // Cek dan lengkapi bagian-bagian modul yang mungkin terlewat (D, E, G, H, I, J, K)
        $hasD = (preg_match('/<h3>\s*D\./i', $content) || stripos($content, 'Materi Pembelajaran') !== false);
        $hasE = (preg_match('/<h3>\s*E\./i', $content) || stripos($content, 'Metode Pembelajaran') !== false);

        if (!$hasD || !$hasE) {
            $insertDE = "";
            if (!$hasD) {
                $insertDE .= "<h3>D. Materi Pembelajaran</h3>\n<p>Materi pokok dan konsep esensial yang dipelajari peserta didik secara kontekstual dan bermakna.</p>\n\n";
            }
            if (!$hasE) {
                $insertDE .= "<h3>E. Metode Pembelajaran</h3>\n<p>Pendekatan Pembelajaran Mendalam (Deep Learning MJM: Mindful, Meaningful, Joyful), diskusi kolaboratif, eksplorasi konsep, dan presentasi.</p>\n\n";
            }

            if (preg_match('/<h3>\s*F\./i', $content)) {
                $content = preg_replace('/(<h3>\s*F\.)/i', $insertDE . '$1', $content, 1);
            }
        }

        $hasG = (preg_match('/<h3>\s*G\./i', $content) || stripos($content, 'Sumber Belajar') !== false);
        $hasH = (preg_match('/<h3>\s*H\./i', $content) || stripos($content, 'Keterampilan') !== false);
        $hasI = (preg_match('/<h3>\s*I\./i', $content) || stripos($content, 'Penilaian Sikap') !== false);
        $hasJ = (preg_match('/<h3>\s*J\./i', $content) || stripos($content, 'Kriteria Kelulusan') !== false);

        $missingGHIJ = "";
        if (!$hasG) {
            $missingGHIJ .= "<h3>G. Sumber Belajar</h3>\n<p>Buku pegangan siswa dan guru, media digital/video interaktif, LKPD, serta sumber belajar lingkungan nyata yang relevan.</p>\n\n";
        }
        if (!$hasH) {
            $missingGHIJ .= "<h3>H. Keterampilan</h3>\n<p>Keterampilan abad-21 dan dimensi profil kelulusan yang dilatihkan (bernalar kritis, kreativitas, gotong royong/kolaborasi, dan komunikasi hasil belajar).</p>\n\n";
        }
        if (!$hasI) {
            $missingGHIJ .= "<h3>I. Penilaian Sikap</h3>\n<p>Penilaian sikap dilakukan melalui observasi terhadap kedisiplinan, rasa ingin tahu, keaktifan diskusi, dan tanggung jawab selama proses pembelajaran.</p>\n\n";
        }
        if (!$hasJ) {
            $missingGHIJ .= "<h3>J. Kriteria Kelulusan</h3>\n<p>Peserta didik dinyatakan tuntas apabila telah mencapai kriteria ketercapaian tujuan pembelajaran yang ditetapkan satuan pendidikan pada materi ini.</p>\n\n";
        }

        if ($missingGHIJ !== "") {
            if (preg_match('/<h3>\s*K\./i', $content)) {
                $content = preg_replace('/(<h3>\s*K\.)/i', $missingGHIJ . '$1', $content, 1);
            } else {
                $content .= "\n\n" . $missingGHIJ;
            }
        }

        // Cek dan lengkapi bagian asesmen (K. Penilaian / Asesmen) jika belum ada atau terpotong
        $hasHeadingK = false;
        $posHeadingK = -1;
        $contentAfterK = '';

        // Deteksi posisi tag heading K
        if (preg_match('/<h3>\s*K\.[^<]*<\/h3>/i', $content, $matchK, PREG_OFFSET_CAPTURE)) {
            $hasHeadingK = true;
            $posHeadingK = $matchK[0][1];
            $lenHeadingK = strlen($matchK[0][0]);
            $contentAfterK = substr($content, $posHeadingK + $lenHeadingK);
        }

        // Evaluasi substansi konten setelah heading K
        $isSubstantiveK = false;
        if ($hasHeadingK) {
            // Hapus tag HTML dan decode entitas
            $strippedAfterK = trim(strip_tags($contentAfterK));
            $decodedAfterK = html_entity_decode($strippedAfterK, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // Hapus whitespace, non-breaking space, dan karakter dekoratif/pemisah (seperti ---, ***, ___, dsb)
            $cleanedAfterK = preg_replace('/[\s\x{00A0}\x{200B}-\x{200D}]+/u', ' ', $decodedAfterK);
            $substantiveAfterK = preg_replace('/^[\s\-\_\*\#\:\.\,\;\|\~\=\+\/\(\)\[\]]+$/u', '', $cleanedAfterK);

            // Konten dianggap substantif jika memiliki minimal 25 karakter bermakna
            if ($substantiveAfterK !== '' && mb_strlen($substantiveAfterK) >= 25) {
                $isSubstantiveK = true;
            }
        }

        if (!$hasHeadingK || !$isSubstantiveK) {
            // 1. Ekstraksi kode/nomor TP valid dari array parameter atau Bagian C dokumen
            $discoveredTpCodes = [];
            foreach ($validTps as $tpItem) {
                if (is_array($tpItem)) {
                    $code = !empty($tpItem['kode_tp']) ? trim($tpItem['kode_tp']) : '';
                    if ($code === '' && !empty($tpItem['deskripsi'])) {
                        if (preg_match('/^([0-9]+(?:\.[0-9]+)+|[A-Z]+-[0-9]+)/', trim($tpItem['deskripsi']), $m)) {
                            $code = $m[1];
                        }
                    }
                    if ($code !== '') {
                        $discoveredTpCodes[] = rtrim($code, '.');
                    }
                } elseif (is_string($tpItem) && trim($tpItem) !== '') {
                    if (preg_match('/^([0-9]+(?:\.[0-9]+)+|[A-Z]+-[0-9]+)/', trim($tpItem), $m)) {
                        $discoveredTpCodes[] = rtrim($m[1], '.');
                    } else {
                        $discoveredTpCodes[] = trim($tpItem);
                    }
                }
            }

            // Jika $validTps kosong, coba ekstrak kode TP dari Bagian C pada dokumen
            if (empty($discoveredTpCodes) && preg_match('/<h3>\s*C\.[^<]*<\/h3>(.*?)(?=<h3>|$)/is', $content, $matchC)) {
                $cHtml = $matchC[1];
                if (preg_match_all('/(?:<li>|<p>)\s*(?:(?:Kode\s*TP|TP)\s*[:.]?\s*)?([0-9]+(?:\.[0-9]+)+|[A-Z]+-[0-9]+)[.:\s]/i', $cHtml, $mCodes)) {
                    foreach ($mCodes[1] as $c) {
                        $discoveredTpCodes[] = rtrim($c, '.');
                    }
                }
            }
            $discoveredTpCodes = array_values(array_unique(array_filter($discoveredTpCodes)));

            // 2. Susun konten Bagian K fallback yang proporsional
            if (!empty($discoveredTpCodes)) {
                $tpListStr = implode(', ', $discoveredTpCodes);
                $tpRows = '';
                foreach ($discoveredTpCodes as $idx => $code) {
                    $no = $idx + 1;
                    $tpRows .= "    <tr>\n"
                        . "      <td style=\"text-align:center;\">{$no}</td>\n"
                        . "      <td>Tujuan Pembelajaran {$code}</td>\n"
                        . "      <td>Observasi keaktifan diskusi, partisipasi kelompok, dan lembar kerja peserta didik (LKPD)</td>\n"
                        . "      <td>Tes tertulis pemahaman konsep serta penilaian unjuk kerja / produk hasil pembelajaran</td>\n"
                        . "    </tr>\n";
                }

                $bodyK = "<p>Penilaian dilaksanakan secara komprehensif mencakup penilaian proses (formatif) dan penilaian hasil (sumatif) yang terpetakan secara langsung pada Tujuan Pembelajaran (TP: {$tpListStr}):</p>\n"
                    . "<ol>\n"
                    . "  <li><strong>Penilaian Proses (Formatif):</strong> Observasi keaktifan belajar, keterlibatan diskusi, dan pengerjaan LKPD terfokus pada penguasaan materi sesuai indikator ketercapaian tujuan pembelajaran.</li>\n"
                    . "  <li><strong>Penilaian Hasil (Sumatif):</strong> Tes tertulis (uraian/pilihan objektif) dan tugas mandiri/kelompok untuk mengukur ketercapaian tujuan pembelajaran secara terukur.</li>\n"
                    . "</ol>\n"
                    . "<p><strong>Tabel Pemetaan Asesmen terhadap Tujuan Pembelajaran:</strong></p>\n"
                    . "<table border=\"1\" cellspacing=\"0\" cellpadding=\"5\" width=\"100%\">\n"
                    . "  <tr style=\"background:#fff9c4;font-weight:bold;text-align:center;\">\n"
                    . "    <th style=\"width:8%;\">No</th>\n"
                    . "    <th style=\"width:22%;\">Kode TP</th>\n"
                    . "    <th style=\"width:35%;\">Bentuk Asesmen Formatif</th>\n"
                    . "    <th style=\"width:35%;\">Bentuk Asesmen Sumatif</th>\n"
                    . "  </tr>\n"
                    . $tpRows
                    . "</table>";
            } else {
                $bodyK = "<p>Penilaian proses (formatif) dilaksanakan melalui observasi keaktifan diskusi, partisipasi kerja kelompok, dan lembar cek pengerjaan tugas harian peserta didik. Penilaian hasil (sumatif) dilaksanakan melalui tes tertulis pemahaman konsep serta penilaian unjuk kerja atau portofolio karya nyata untuk mengukur ketercapaian tujuan pembelajaran pada materi ini.</p>";
            }

            $fullSectionK = "<h3>K. Penilaian / Asesmen</h3>\n" . $bodyK;

            if ($hasHeadingK) {
                // Potong dari posisi <h3>K. lalu gantikan dengan $fullSectionK lengkap (cegah duplikasi heading)
                $content = substr($content, 0, $posHeadingK) . $fullSectionK;
            } else {
                $content .= "\n\n" . $fullSectionK;
            }
        }

        return trim($this->cleanBackslashesAndLatex($content));
    }

    /**
     * Membersihkan tanda backslash (\), LaTeX math artifacts, dan escape markdown yang tidak diinginkan
     */
    protected function cleanBackslashesAndLatex(string $content): string
    {
        if (trim($content) === '') {
            return $content;
        }

        // 1. Lindungi blok <style> dan <script>
        $placeholders = [];
        $content = preg_replace_callback('/<(style|script)[^>]*>.*?<\/\\1>/is', function ($matches) use (&$placeholders) {
            $key = '___PROTECTED_BLOCK_' . count($placeholders) . '___';
            $placeholders[$key] = $matches[0];
            return $key;
        }, $content);

        // 2. Konversi simbol matematika LaTeX umum menjadi simbol Unicode murni
        $content = str_replace(
            ['\div', '\times', '\pm', '\le', '\leq', '\ge', '\geq', '\cdot', '\approx', '\neq', '\ne', '\infty', '\degree', '^\circ'],
            ['÷',    '×',      '±',   '≤',   '≤',    '≥',   '≥',    '·',     '≈',       '≠',    '≠',   '∞',      '°',       '°'],
            $content
        );

        // 3. Konversi kurung dan pembatas LaTeX
        $content = str_replace(
            ['\(', '\)', '\[', '\]', '\{', '\}', '\%'],
            ['(', ')', '[', ']', '{', '}', '%'],
            $content
        );

        // 4. Hapus elemen/baris yang hanya berisi backslash atau rentetan backslash (seperti \ \\\\\\\\ \ \\\\\\ \ \)
        $content = preg_replace('/<(p|div|span|h[1-6]|li)[^>]*>\s*(?:\\\\[\s\\\\]*)+\s*<\/\1>/iu', '', $content);
        $content = preg_replace('/^[ \t\r\n]*(?:\\\\[ \t\\\\]*)+[ \t\r\n]*$/m', '', $content);

        // 5. Bersihkan backslash di awal penomoran atau huruf list: \d., \e., \1., \A.
        $content = preg_replace('/(^|[\s>])\\\+([a-zA-Z0-9]+)\./um', '$1$2.', $content);

        // 6. Bersihkan backslash sebelum atau sesudah tanda baca umum: \., \:, \,, \(, \)
        $content = preg_replace('/\\\+([.,:;!?%])/u', '$1', $content);
        $content = preg_replace('/([.,:;!?%])\\\+/u', '$1', $content);

        // 7. Bersihkan backslash di akhir tag, sel tabel, heading, atau akhir baris
        $content = preg_replace('/\\\+\s*(?=<\/(?:p|h[1-6]|td|th|li|div|span|strong|b|em|i)>|$|\r|\n)/iu', '', $content);

        // 8. Bersihkan backslash sebelum atau sesudah tag HTML
        $content = preg_replace('/(<br\s*\/?>)\s*\\\+/iu', '$1', $content);
        $content = preg_replace('/\\\+\s*(<br\s*\/?>)/iu', '$1', $content);
        $content = preg_replace('/(<\/?(?:b|strong|em|i|span|p|div|td|th)>)\s*\\\+/iu', '$1', $content);
        $content = preg_replace('/\\\+\s*(<\/?(?:b|strong|em|i|span|p|div|td|th)>)/iu', '$1', $content);

        // 9. Bersihkan sisa backslash di luar tag HTML (teks biasa)
        $parts = preg_split('/(<[^>]+>)/u', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        for ($i = 0; $i < count($parts); $i++) {
            if (!empty($parts[$i]) && $parts[$i][0] !== '<') {
                $parts[$i] = str_replace('\\', '', $parts[$i]);
            }
        }
        $content = implode('', $parts);

        // 10. Kembalikan blok yang dilindungi (<style>, <script>)
        if (!empty($placeholders)) {
            $content = strtr($content, $placeholders);
        }

        // Rapikan baris kosong berlebih
        $content = preg_replace('/(?:\r?\n){3,}/', "\n\n", $content);

        return trim($content);
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
        $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $level);

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

        // ═══ Hitung status pekan per bulan ═══
        $activeYear = $this->yearModel->getActiveYear() ?: [];
        $yearStart = $activeYear['start_date'] ?? null;
        $yearEnd   = $activeYear['end_date'] ?? null;
        $schoolDays = (int)($activeYear['school_days'] ?? 5);

        // Ambil semua hari libur dalam rentang semester
        $allHolidays = $this->holidayModel->findAll();
        $holidayDates = array_flip(array_column($allHolidays, 'date'));

        // Tentukan tahun
        $yearForMonth = function(int $month) use ($activeYear): int {
            $startYear = (int)date('Y', strtotime($activeYear['start_date'] ?? date('Y-01-01')));
            // Semester 1: Juli-Des = tahun start, Semester 2: Jan-Jun = tahun start+1
            return ($month >= 7) ? $startYear : $startYear + 1;
        };

        // weekStatus[month][week] = 'full_holiday' | 'has_holiday' | 'partial' | 'out_range' | 'normal'
        $weekStatus = [];

        foreach ($data['months'] as $mCode => $mName) {
            $year = $yearForMonth($mCode);
            $weekStatus[$mCode] = [];

            // Cari minggu-minggu (Senin–Minggu) yang beririsan dengan bulan ini
            // Mulai dari Senin pertama sebelum atau pada tanggal 1 bulan
            $firstDayOfMonth  = mktime(0, 0, 0, $mCode, 1, $year);
            $lastDayOfMonth   = mktime(0, 0, 0, $mCode, (int)date('t', $firstDayOfMonth), $year);
            $daysInMonth      = (int)date('t', $firstDayOfMonth);

            // Cari Minggu awal pekan pertama yang bersinggungan dengan bulan ini
            // (kalender Minggu–Sabtu)
            $dayOfWeekFirst = (int)date('w', $firstDayOfMonth); // 0=Minggu, 1=Sen, ..., 6=Sab
            // Mundur ke Minggu sebelumnya (atau tetap jika tgl 1 sudah Minggu)
            $sundayOfFirstWeek = mktime(0, 0, 0, $mCode, 1 - $dayOfWeekFirst, $year);

            for ($w = 1; $w <= 5; $w++) {
                $weekSunday  = strtotime('+' . ($w - 1) . ' weeks', $sundayOfFirstWeek);
                $weekSaturday = strtotime('+6 days', $weekSunday);

                // Pekan ini tidak beririsan dengan bulan sama sekali
                if ($weekSunday > $lastDayOfMonth || $weekSaturday < $firstDayOfMonth) {
                    $weekStatus[$mCode][$w] = 'out_range';
                    continue;
                }

                $effectiveDays = 0;
                $holidayDaysInWeek = 0;

                // Loop hari Senin–Jumat (atau Sabtu jika 6 hari sekolah) dalam minggu ini
                // Senin = hari ke-1 dari Minggu, Jumat = hari ke-5, Sabtu = hari ke-6
                $startOffset = 1; // Senin
                $endOffset   = ($schoolDays == 6) ? 6 : 5; // Jumat atau Sabtu

                for ($d = $startOffset; $d <= $endOffset; $d++) {
                    $dayTs   = strtotime('+' . $d . ' days', $weekSunday);
                    $dateStr = date('Y-m-d', $dayTs);

                    // Harus berada dalam bulan ini
                    if ((int)date('n', $dayTs) !== $mCode) continue;

                    // Cek out of academic year range
                    if ($yearStart && $dateStr < $yearStart) continue;
                    if ($yearEnd   && $dateStr > $yearEnd)   continue;

                    $effectiveDays++;

                    if (isset($holidayDates[$dateStr])) {
                        $holidayDaysInWeek++;
                    }
                }

                if ($effectiveDays === 0) {
                    $weekStatus[$mCode][$w] = 'full_holiday';
                } elseif ($holidayDaysInWeek > 0 && $holidayDaysInWeek >= $effectiveDays) {
                    $weekStatus[$mCode][$w] = 'full_holiday';
                } elseif ($effectiveDays <= 2) {
                    // Pekan pendek — awal/akhir TA atau sebagian besar di luar bulan
                    $weekStatus[$mCode][$w] = 'partial';
                } elseif ($holidayDaysInWeek > 0) {
                    $weekStatus[$mCode][$w] = 'has_holiday';
                } else {
                    $weekStatus[$mCode][$w] = 'normal';
                }
            }
        }

        $data['week_status'] = $weekStatus;

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
        $activeYearId = $this->yearModel->getActiveYear()['id'] ?? 0;

        $teacherCount = $this->db->table('teaching_assignments ta')
            ->distinct()->select('ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id', $subjectId)
            ->where('c.level', $classLevel)
            ->where('ta.academic_year_id', $activeYearId)
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
                ->where('academic_year_id', $activeYearId)
                ->get()->getResultArray();
            return array_column($myClassIds, 'class_id') ?: [0];
        }

        // Admin/Kepsek tanpa override → tampilkan semua
        return null;
    }

    /**
     * Helper terpusat: cek apakah ada >1 guru untuk mapel+level di tahun ajaran aktif
     */
    private function isMultiGuruForLevel(int $subjectId, int $classLevel): bool
    {
        $activeYearId = $this->yearModel->getActiveYear()['id'] ?? 0;

        $count = $this->db->table('teaching_assignments ta')
            ->distinct()->select('ta.teacher_id')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.subject_id', $subjectId)
            ->where('c.level', $classLevel)
            ->where('ta.academic_year_id', $activeYearId)
            ->countAllResults();

        return $count > 1;
    }

    /**
     * Cari dokumen (ATP/Modul/KKTP) yang sudah ada di level yang sama
     * lintas semua tahun ajaran — agar perangkat lama tetap bisa dipakai
     * meski gurunya berganti di tahun ajaran berikutnya.
     * 
     * Prioritas: kelas yang sama dulu, lalu kelas lain se-level manapun.
     */
    private function getClassIdsForDocumentSearch(int $classLevel, ?int $preferClassId = null): array
    {
        $allClassIds = array_column(
            $this->db->table('classes')->select('id')->where('level', $classLevel)->get()->getResultArray(),
            'id'
        );

        if (!empty($preferClassId) && !in_array($preferClassId, $allClassIds)) {
            array_unshift($allClassIds, $preferClassId);
        }

        return $allClassIds ?: [0];
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

    // =========================================================================
    // PEMBUATAN SOAL AI
    // =========================================================================

    /**
     * Halaman Pembuatan Soal AI
     */
    public function soalAi()
    {
        $data['title'] = 'Pembuatan Soal AI';
        
        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];
        
        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;

        // AI config
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

        // ATP list + Bank Soal
        $data['atp_list'] = [];
        $data['bank_soal'] = [];

        if ($subjectId && $classId) {
            $school = $this->db->table('school_profile')->get()->getRowArray();
            $schoolLevel = $school['level'] ?? 1;
            $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            $classLevel = (int)($classInfo['level'] ?? 0);

            $atpResult = $this->loadAtpWithElemen((int)$subjectId, $classLevel, null, [(int)$classId]);
            $data['atp_list'] = $atpResult['list'];

            // Bank soal milik guru ini untuk mapel ini
            $context = get_cbt_user_context();
            $bankQuery = $this->db->table('cbt_question_banks')
                ->select('id, code, total_questions')
                ->where('subject_id', $subjectId);
            if (!empty($context['teacher_id'])) {
                $bankQuery->where('teacher_id', $context['teacher_id']);
            }
            $data['bank_soal'] = $bankQuery->get()->getResultArray();
        }

        return view('admin/administrasi_guru/soal_ai', $data);
    }

    /**
     * Generate soal menggunakan AI (multi-tipe sekaligus)
     */
    public function generateSoalAi()
    {
        try {
            @set_time_limit(180);

            $user = session()->get('user');
            if (!in_array($user['role_id'], [1, 3])) {
                return $this->response->setJSON(['success' => false, 'error' => 'Akses ditolak.']);
            }

        // Get AI config
        if ($user['role_id'] == 3) {
            $teacher = $this->teacherModel->find($user['related_id']);
            $apiKey = $teacher['gemini_api_key'] ?? '';
            $aiProvider = $teacher['ai_provider'] ?? 'gemini';
        } else {
            $userData = $this->db->table('users')->where('id', $user['id'])->get()->getRowArray();
            $apiKey = $userData['gemini_api_key'] ?? '';
            $aiProvider = $userData['ai_provider'] ?? 'gemini';
        }

        if (empty($apiKey)) {
            return $this->response->setJSON(['success' => false, 'error' => 'API Key AI belum dikonfigurasi.']);
        }

        $atpId = $this->request->getPost('atp_id');
        $subjectId = $this->request->getPost('subject_id');
        $classId = $this->request->getPost('class_id');
        $tipeListJson = $this->request->getPost('tipe_list');
        $selectedTps = $this->request->getPost('tp_ids');
        $jenisAsesmen = $this->request->getPost('jenis_asesmen') ?? 'formatif';
        $atpIds = $this->request->getPost('atp_ids'); // untuk sumatif
        $semester = $this->request->getPost('semester');

        $tipeList = json_decode($tipeListJson, true);
        if (empty($tipeList)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Pilih minimal satu tipe soal.']);
        }

        // Load data ATP - formatif (1) atau sumatif (banyak)
        $materiList = '';
        if ($jenisAsesmen === 'sumatif' && !empty($atpIds)) {
            $atps = $this->atpModel->whereIn('id', $atpIds)->findAll();
            foreach ($atps as $a) {
                $materiList .= "- " . $a['lingkup_materi'] . "\n";
            }
        } else {
            $atp = $this->atpModel->find($atpId);
            if (!$atp) {
                return $this->response->setJSON(['success' => false, 'error' => 'ATP tidak ditemukan.']);
            }
            $materiList = $atp['lingkup_materi'] ?? '-';
        }

        $subject = $this->subjectModel->find($subjectId);
        $school = $this->db->table('school_profile')->get()->getRowArray();
        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();

        // Get TP descriptions
        $tpDeskripsi = '';
        if (!empty($selectedTps)) {
            $tps = $this->db->table('tujuan_pembelajaran')
                ->whereIn('id', $selectedTps)
                ->get()->getResultArray();
            foreach ($tps as $tp) {
                $tpDeskripsi .= "- " . ($tp['kode_tp'] ? $tp['kode_tp'] . ': ' : '') . $tp['deskripsi'] . "\n";
            }
        }

        $prompt = $this->buildSoalAiPrompt(
            $school ?? [],
            $classInfo ?? [],
            $subject ?? [],
            $materiList,
            $tpDeskripsi,
            $tipeList,
            $jenisAsesmen,
            $semester
        );

        // Call AI
        $response = null;
        $httpCode = 0;

        if ($aiProvider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $headers = ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'];
            // Model rekomendasi resmi Groq (Llama 3.3 70B sudah didekomisi per 16 Agustus 2026)
            $groqModels = [
                'openai/gpt-oss-120b',
                'qwen/qwen3.6-27b',
                'openai/gpt-oss-20b',
                'qwen-2.5-32b',
                'llama-3.1-8b-instant'
            ];

            foreach ($groqModels as $model) {
                $payload = json_encode([
                    "model" => $model,
                    "messages" => [["role" => "user", "content" => $prompt]],
                    "temperature" => 0.7,
                    "max_tokens" => 8192,
                    "reasoning_format" => "parsed"
                ]);

                $attempt = 0;
                while ($attempt < 2) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 200) {
                        break 2;
                    }

                    if (in_array($httpCode, [400, 401, 403, 404])) {
                        break 2; // Jika API key tidak valid (401/403) atau model salah, tidak perlu retry loop
                    }

                    if ($httpCode === 429 || $httpCode === 503) {
                        sleep(2);
                    } else {
                        break;
                    }
                }
            }
        } else {
            $geminiModels = ['gemini-2.0-flash', 'gemini-1.5-flash', 'gemini-1.5-pro'];
            $headers = ['Content-Type: application/json'];
            $payload = json_encode([
                "contents" => [["parts" => [["text" => $prompt]]]],
                "generationConfig" => [
                    "temperature" => 0.7,
                    "maxOutputTokens" => 8192
                ]
            ]);

            foreach ($geminiModels as $gModel) {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$gModel}:generateContent?key=" . $apiKey;
                $attempt = 0;
                while ($attempt < 2) {
                    $attempt++;
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 120);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);

                    if ($httpCode === 200) {
                        break 2;
                    }

                    if (in_array($httpCode, [400, 401, 403, 404])) {
                        break 2;
                    }

                    if ($httpCode === 429 || $httpCode === 503) {
                        sleep(2);
                    } else {
                        break;
                    }
                }
            }
        }

        if ($httpCode != 200) {
            $errRes = json_decode($response, true);
            $errMsg = $errRes['error']['message'] ?? 'HTTP ' . $httpCode;
            if ($httpCode == 401 || $httpCode == 403) {
                $errMsg .= " (API Key tidak valid atau kedaluwarsa. Silakan periksa kembali konfigurasi API Key di menu Modul Ajar).";
            } elseif ($httpCode == 503) {
                $errMsg .= " (Server sedang padat. Coba lagi atau gunakan provider lain).";
            }
            return $this->response->setJSON(['success' => false, 'error' => "AI error: {$errMsg}"]);
        }

        $resData = json_decode($response, true);
        if ($aiProvider === 'groq') {
            $content = $resData['choices'][0]['message']['content'] ?? '';
            $content = preg_replace('/<think>[\s\S]*?<\/think>/i', '', $content);
            $finishReason = $resData['choices'][0]['finish_reason'] ?? 'Empty';
        } else {
            $content = '';
            if (!empty($resData['candidates'][0]['content']['parts'])) {
                foreach ($resData['candidates'][0]['content']['parts'] as $part) {
                    if (!empty($part['text'])) {
                        $content .= $part['text'];
                    }
                }
            }
            $finishReason = $resData['candidates'][0]['finishReason'] ?? ($resData['promptFeedback']['blockReason'] ?? 'Empty');
        }

        if (empty(trim($content))) {
            return $this->response->setJSON(['success' => false, 'error' => "AI tidak menghasilkan konten soal ({$finishReason}). Coba generate ulang."]);
        }

        $content = preg_replace('/```(?:json)?(.*?)```/s', '$1', $content);
        $content = trim($content);

        $soalList = $this->parseSoalAiJson($content);
        if (!is_array($soalList) || empty($soalList)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Gagal memproses format JSON dari AI. Coba generate ulang.']);
        }

        foreach ($soalList as &$s) {
            if (isset($s['question'])) {
                $s['question'] = str_replace(["\\r\\n", "\\r", "\\n"], ["\r\n", "\r", "\n"], (string)$s['question']);
            }
            if (isset($s['options']) && is_array($s['options'])) {
                foreach ($s['options'] as $ok => $ov) {
                    $s['options'][$ok] = str_replace(["\\r\\n", "\\r", "\\n"], ["\r\n", "\r", "\n"], (string)$ov);
                }
            }
            if (isset($s['explanation'])) {
                $s['explanation'] = str_replace(["\\r\\n", "\\r", "\\n"], ["\r\n", "\r", "\n"], (string)$s['explanation']);
            }
        }
        unset($s);

        return $this->response->setJSON([
            'success' => true,
            'soal' => $soalList,
            'jumlah' => count($soalList),
        ]);
    } catch (\Throwable $e) {
        log_message('error', 'generateSoalAi error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        return $this->response->setJSON(['success' => false, 'error' => 'Terjadi kesalahan server (500): ' . $e->getMessage()]);
    }
}

    /**
     * Generate prompt soal saja (tanpa panggil API) — user copy ke AI langsung
     */
    public function getSoalPrompt()
    {
        try {
            $user = session()->get('user');
            if (empty($user) || !in_array($user['role_id'] ?? null, [1, 3])) {
                return $this->response->setJSON(['success' => false, 'error' => 'Akses ditolak atau sesi login telah berakhir.']);
            }

            $atpId = $this->request->getPost('atp_id');
            $subjectId = $this->request->getPost('subject_id');
            $classId = $this->request->getPost('class_id');
            $tipeListJson = $this->request->getPost('tipe_list');
            $selectedTps = $this->request->getPost('tp_ids');
            $jenisAsesmen = $this->request->getPost('jenis_asesmen') ?? 'formatif';
            $atpIds = $this->request->getPost('atp_ids');
            $semester = $this->request->getPost('semester');

            $tipeList = json_decode($tipeListJson, true);
            if (empty($tipeList)) {
                return $this->response->setJSON(['success' => false, 'error' => 'Pilih minimal satu tipe soal.']);
            }

            // Load data
            $materiList = '';
            if ($jenisAsesmen === 'sumatif' && !empty($atpIds)) {
                $atps = $this->atpModel->whereIn('id', (array) $atpIds)->findAll();
                foreach ($atps as $a) {
                    $materiList .= "- " . ($a['lingkup_materi'] ?? '-') . "\n";
                }
            } else {
                $atp = $this->atpModel->find($atpId);
                if (!$atp) {
                    return $this->response->setJSON(['success' => false, 'error' => 'Materi ATP tidak ditemukan. Silakan pilih materi terlebih dahulu.']);
                }
                $materiList = $atp['lingkup_materi'] ?? '-';
            }

            $subject = $this->subjectModel->find($subjectId) ?? [];
            $school = $this->db->table('school_profile')->get()->getRowArray() ?? [];
            $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray() ?? [];

            $tpDeskripsi = '';
            if (!empty($selectedTps)) {
                $tps = $this->db->table('tujuan_pembelajaran')
                    ->whereIn('id', (array) $selectedTps)->get()->getResultArray();
                foreach ($tps as $tp) {
                    $tpDeskripsi .= "- " . (!empty($tp['kode_tp']) ? $tp['kode_tp'] . ': ' : '') . ($tp['deskripsi'] ?? '') . "\n";
                }
            }

            $prompt = $this->buildSoalAiPrompt(
                $school,
                $classInfo,
                $subject,
                (string) $materiList,
                (string) $tpDeskripsi,
                $tipeList,
                (string) $jenisAsesmen,
                $semester
            );

            return $this->response->setJSON([
                'success' => true,
                'prompt'  => $prompt,
                'csrf'    => csrf_hash(),
            ]);
        } catch (\Throwable $e) {
            log_message('error', '[getSoalPrompt] Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Terjadi kesalahan sistem: ' . $e->getMessage(),
                'csrf'    => csrf_hash(),
            ]);
        }
    }

    /**
     * Parse & bersihkan output JSON dari AI secara komprehensif
     * Menangani markdown codeblocks, unescaped quotes, trailing comma, placeholder berpetik, dan karakter kontrol.
     */
    private function parseSoalAiJson(string $content): ?array
    {
        $content = preg_replace('/```(?:json)?([\s\S]*?)```/s', '$1', $content);
        $content = trim($content);

        // Ambil substring antara [ pertama dan ] terakhir
        $start = strpos($content, '[');
        $end = strrpos($content, ']');
        if ($start !== false && $end !== false && $end > $start) {
            $content = substr($content, $start, $end - $start + 1);
        }

        // 1. Coba decode langsung jika sudah valid
        $decoded = json_decode($content, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }

        // 2. Pembersihan karakter tak kasat mata
        $clean = preg_replace('/[\x{FEFF}\x{200B}\x{200C}\x{200D}\x{00AD}]/u', '', $content);

        // 3. Ubah tanda kutip ganda yang di-escape (\" atau \\") di dalam teks menjadi petik tunggal (')
        // Ini memastikan kutipan ayat, hadits, atau terjemahan tidak merusak delimiter token JSON
        $clean = preg_replace('/\\\\+"/u', "'", $clean);

        // 4. Bersihkan jika masih ada pola kurung siku berpetik
        $clean = preg_replace('/\\\\?"\[(.*?)\]\\\\?"/s', "'[$1]'", $clean);

        // 5. Hapus trailing comma sebelum } atau ]
        $clean = preg_replace('/,\s*([\}\]])/s', '$1', $clean);

        // 6. Normalisasi backslash LaTeX agar tidak merusak decode JSON
        $clean = preg_replace('/\\\\(?!["\\\\\/bfnrtu]|u[0-9a-fA-F]{4})([a-zA-Z{}_^])/s', '\\\\\\\\$1', $clean);

        $decoded = json_decode($clean, true);
        if (is_array($decoded) && !empty($decoded)) {
            return $decoded;
        }

        return null;
    }

    /**
     * Membangun prompt profesional berstandar nasional untuk AI
     * Menegakkan kaidah penulisan butir soal: kesetaraan panjang opsi, distraktor berkualitas & homogen, paralelisme gramatikal, dan pencegahan clue verbal.
     */
    private function buildSoalAiPrompt(
        $school,
        $classInfo,
        $subject,
        string $materiList,
        string $tpDeskripsi,
        array $tipeList,
        string $jenisAsesmen,
        $semester
    ): string {
        $school = is_array($school) ? $school : [];
        $classInfo = is_array($classInfo) ? $classInfo : [];
        $subject = is_array($subject) ? $subject : [];

        $schoolLevel = (int)($school['level'] ?? 1);
        $jenjang = ($schoolLevel == 1) ? 'SD' : (($schoolLevel == 2) ? 'SMP' : 'SMA');
        $kelasLevel = (int)($classInfo['level'] ?? 7);

        // Deteksi Mata Pelajaran Pendidikan Agama Islam (PAI) atau keagamaan Islam
        $subjectName = $subject['name'] ?? '';
        $isPai = (bool) preg_match('/(islam|pai|al-qur\'?an|qur\'?an|hadis|hadits|fiqih|fikih|akidah|akhlak|tarikh|ski|sejarah kebudayaan islam|agama)/i', $subjectName);
        if (!$isPai && preg_match('/(al-qur\'?an|qur\'?an|surah|ayat|hadis|hadits|fiqih|fikih|zakat|puasa|shalat|salat|haji|akidah|akhlak|tauhid|tajwid)/i', $materiList)) {
            $isPai = true;
        }

        // SD/SMP: 4 opsi (A-D), SMA: 5 opsi (A-E)
        $opsiCount = ($schoolLevel == 3) ? 5 : 4;
        $opsiLabel = ($opsiCount == 5) ? '5 opsi: A,B,C,D,E' : '4 opsi: A,B,C,D';

        $soalRequest = '';
        foreach ($tipeList as $t) {
            $label = match($t['tipe']) {
                'pg' => "Pilihan Ganda ({$opsiLabel} — pilih 1 jawaban benar)",
                'pg_kompleks' => "Pilihan Ganda Kompleks ({$opsiLabel}, bisa lebih dari 1 jawaban benar)",
                'benar_salah' => 'Benar/Salah (1 stimulus + 4 PERNYATAAN yang masing-masing dinilai Benar atau Salah secara INDEPENDEN)',
                'esai' => 'Esai/Uraian singkat',
                default => $t['tipe']
            };
            $soalRequest .= "- {$t['jumlah']} soal tipe {$label}\n";
        }

        $jenisLabel = ($jenisAsesmen === 'sumatif')
            ? "SUMATIF SEMESTER {$semester} (mencakup seluruh materi semester)"
            : "FORMATIF (fokus pada materi spesifik yang dipilih)";

        $karakteristikLevel = '';
        if ($jenjang === 'SD') {
            if ($kelasLevel <= 3) {
                $karakteristikLevel = "Kelas rendah (1-3 SD): gunakan kalimat sederhana, konkret, dekat kehidupan sehari-hari anak.";
            } else {
                $karakteristikLevel = "Kelas tinggi (4-6 SD): boleh abstrak ringan, hubungkan dengan pengalaman nyata, mulai penalaran sederhana.";
            }
        } elseif ($jenjang === 'SMP') {
            if ($kelasLevel <= 7) {
                $karakteristikLevel = "Kelas 7 SMP: transisi dari SD, mulai analisis sederhana, kontekstual dengan kehidupan remaja awal.";
            } elseif ($kelasLevel == 8) {
                $karakteristikLevel = "Kelas 8 SMP: penalaran lebih dalam, bisa bandingkan konsep, analisis teks/data sederhana.";
            } else {
                $karakteristikLevel = "Kelas 9 SMP: persiapan ujian akhir, penalaran tinggi, evaluasi, dan sintesis antar konsep.";
            }
        } else {
            $karakteristikLevel = "Jenjang SMA: penalaran tinggi, analisis mendalam, evaluasi kritis, dan kreativitas berpikir.";
        }

        $samplePgOptions = ($opsiCount == 5)
            ? '{"A":"...","B":"...","C":"...","D":"...","E":"..."}'
            : '{"A":"...","B":"...","C":"...","D":"..."}';

        $prompt = "Kamu adalah pembuat soal ujian profesional dan pakar evaluasi pendidikan berpengalaman untuk jenjang {$jenjang}.\n"
            . "Buatkan paket butir soal ujian BERKUALITAS TINGGI, STANDAR NASIONAL, dan BEBAS DARI CLUE JAWABAN dengan rincian:\n{$soalRequest}\n"
            . "JENIS ASESMEN: {$jenisLabel}\n\n"
            . "KONTEKS PEMBELAJARAN:\n"
            . "- Jenjang: {$jenjang}\n"
            . "- Kelas: " . ($classInfo['name'] ?? '-') . " (Level {$kelasLevel})\n"
            . "- Mata Pelajaran: " . ($subject['name'] ?? '-') . "\n"
            . "- Materi/Lingkup:\n" . $materiList . "\n"
            . "- Karakteristik Siswa: {$karakteristikLevel}\n"
            . (!empty($tpDeskripsi) ? "- Tujuan Pembelajaran (TP):\n{$tpDeskripsi}\n" : '')
            . "\n========================================================================\n"
            . "PANDUAN KUALITAS BUTIR SOAL & KAIDAH PENULISAN OPSI (WAJIB DIPATUHI):\n"
            . "========================================================================\n"
            . "1. ATURAN KRUSIAL OPSI JAWABAN & PENGECOH (MENCEGAH TEBAKAN CEPAT SISWA):\n"
            . "   a. KESETARAAN PANJANG KALIMAT (WAJIB SERAGAM):\n"
            . "      - Semua pilihan jawaban (A, B, C, D" . ($opsiCount == 5 ? ", E" : "") . ") HARUS memiliki panjang kalimat dan jumlah kata yang RELATIF SAMA (seimbang).\n"
            . "      - DILARANG KERAS membuat opsi jawaban yang benar jauh lebih panjang, lebih lengkap, lebih rinci, atau lebih ilmiah daripada opsi pengecoh.\n"
            . "      - Siswa TIDAK BOLEH bisa menebak jawaban hanya dengan melihat opsi mana yang paling panjang atau paling lengkap penjelasannya!\n"
            . "      - Jika kunci jawaban membutuhkan penjelasan 1 kalimat (misal 10-16 kata), maka SELURUH opsi pengecoh (distraktor) juga HARUS dirancang dengan 10-16 kata dengan bobot yang setara.\n"
            . "   b. KUALITAS PENGECOH (DISTRAKTOR) HARUS SANGAT MEYAKINKAN & HOMOGEN:\n"
            . "      - Opsi pengecoh (jawaban salah) HARUS MASUK AKAL (highly plausible), logis, homogen, dan tampak benar bagi siswa yang belum benar-benar paham materi.\n"
            . "      - Buat pengecoh dari:\n"
            . "        * Miskonsepsi umum siswa yang sering terjadi pada topik tersebut.\n"
            . "        * Konsep atau istilah lain yang mirip/relevan tetapi keliru konteks atau keliru syarat.\n"
            . "        * Kesalahan nalar atau kekeliruan langkah perhitungan yang paling sering dialami siswa.\n"
            . "      - DILARANG membuat opsi pengecoh yang konyol, absurd, asal-asalan, sepele, atau jelas-jelas salah saat dibaca sekilas.\n"
            . "   c. PARALELISME / KESELARASAN STRUKTUR TATA BAHASA:\n"
            . "      - Semua opsi dalam satu butir soal HARUS memiliki struktur gramatikal yang setara/paralel.\n"
            . "      - Jika opsi A diawali predikat aktif (misal 'Mengidentifikasi...'), maka opsi B, C, D" . ($opsiCount == 5 ? ', E' : '') . " juga WAJIB diawali predikat aktif ('Menganalisis...', 'Membandingkan...', dst).\n"
            . "      - Jika berupa frasa benda, semua opsi harus frasa benda. Jangan mencampur kalimat utuh dengan frasa gantung.\n"
            . "   d. HINDARI PETUNJUK VERBAL / CLUE JAWABAN:\n"
            . "      - DILARANG menggunakan kata absolut pembuat jebakan seperti 'selalu', 'pasti', 'hanya', 'tidak pernah', atau 'semua' pada opsi pengecoh semata-mata agar menjadi salah.\n"
            . "      - DILARANG mengulang kata teknis khusus dari pertanyaan/stimulus HANYA di opsi jawaban benar sementara pengecoh tidak memakainya (hindari stem-option clue).\n"
            . "      - Tingkat formalitas, nada bahasa, dan keanggunan kalimat pada seluruh opsi harus setara (jangan buat opsi benar bernada paling bijak/ideal sementara opsi salah bernada negatif/kasar).\n"
            . "   e. DISTRIBUSI KUNCI JAWABAN MERATA & ACAK:\n"
            . "      - Sebar kunci jawaban benar secara acak dan seimbang di antara huruf opsi (A, B, C, D" . ($opsiCount == 5 ? ', E' : '') . "). Jangan menumpuk pada huruf tertentu (misal selalu A atau selalu C).\n\n"
            . "CONTOH KONTRASTIF (PERHATIKAN DENGAN SANGAT TELITI):\n"
            . "--- CONTOH BURUK / SALAH (DILARANG DIBUAT) ---\n"
            . "Soal: Apa peranan utama klorofil pada daun dalam fotosintesis?\n"
            . "A. Memberi warna hijau pada daun. (Terlalu pendek & seadanya)\n"
            . "B. Tumbuhan tidak memerlukan klorofil. (Absurd & konyol)\n"
            . "C. Menyerap energi cahaya matahari untuk memecah molekul air dalam reaksi terang guna menghasilkan energi kimia. (KUNCI: Terlalu panjang, 16 kata, sangat ilmiah & rinci!)\n"
            . "D. Menghangatkan daun saat musim dingin. (Konyol)\n"
            . "=> Masalah: Siswa langsung tahu kunci jawabannya adalah C dalam sepintas pandang tanpa perlu belajar!\n\n"
            . "--- CONTOH BAIK / BENAR (WAJIB DIIKUTI) ---\n"
            . "Soal: Apa peranan utama klorofil pada daun dalam fotosintesis?\n"
            . "A. Mengikat gas karbon dioksida dari udara bebas untuk diubah langsung menjadi senyawa amilum.\n"
            . "B. Menyerap energi cahaya matahari untuk memecah molekul air pada tahap reaksi terang fotosintesis. (KUNCI BENAR)\n"
            . "C. Mengalirkan nutrisi organik hasil fotosintesis dari daun menuju ke seluruh jaringan tanaman.\n"
            . "D. Mengendalikan penguapan cairan sel melalui pori-pori daun saat intensitas cahaya matahari tinggi.\n"
            . "=> Hasil: Semua opsi memiliki 13-16 kata, pola kalimat sejajar ('Mengikat...', 'Menyerap...', 'Mengalirkan...', 'Mengendalikan...'), seluruh istilah ilmiah relevan dan homogen, sehingga siswa benar-benar harus berpikir kritis!\n\n"
            . "2. DISTRIBUSI LEVEL KOGNITIF (Taksonomi Bloom Revisi):\n"
            . "   - 20% soal C1-C2 (Mengingat & Memahami): fakta, definisi, konsep dasar\n"
            . "   - 30% soal C3 (Menerapkan): penerapan konsep dalam situasi baru / kontekstual\n"
            . "   - 50% soal C4-C6 (HOTS - Menganalisis, Mengevaluasi, Mencipta): analisis data/teks, pemecahan masalah, penarikan kesimpulan\n\n"
            . "3. STIMULUS SOAL HOTS & SOAL MANDIRI:\n"
            . "   - Soal HOTS wajib menyertakan stimulus (wacana singkat, data, tabel, kasus kehidupan nyata) di awal field 'question'.\n"
            . "   - PENTING (Ujian CBT Digital): Soal diacak oleh sistem. Setiap butir soal HARUS MANDIRI (self-contained). DILARANG membuat satu stimulus untuk beberapa nomor (misal 'Perhatikan teks berikut untuk soal 1-3'). Bila ada soal merujuk teks yang sama, tulis ulang teks secara utuh di masing-masing nomor soal.\n\n"
            . "4. UNTUK MAPEL BAHASA (Indonesia/Inggris/Daerah):\n"
            . "   - Wajib sertakan kutipan teks wacana bacaan yang relevan di field 'question' dengan label 'Bacalah teks berikut!' kemudian buat baris baru.\n\n"
            . "5. UNTUK SOAL YANG MEMBUTUHKAN GAMBAR:\n"
            . "   - Tulis deskripsinya dalam kurung siku: [Gambar: deskripsi visual yang jelas]\n"
            . "   - Kurung siku HANYA untuk deskripsi gambar. DILARANG menggunakan kurung siku untuk ayat/hadits (tulis dalil nyata)!\n\n"
            . "6. ATURAN PENULISAN MATEMATIKA / NOTASI ILMIAH:\n"
            . '   - Gunakan format LaTeX: $\frac{a}{b}$, $\sqrt{x}$, $x^2$, dst.' . "\n\n"
            . ($isPai ? (
                "7. ATURAN KHUSUS MATA PELAJARAN PENDIDIKAN AGAMA ISLAM (PAI) & KEAGAMAAN (WAJIB DIPATUHI):\n"
                . "   a. WAJIB MENYERTAKAN STIMULUS DALIL RELEVAN (AYAT AL-QUR'AN / HADITS / DALIL NAQLI):\n"
                . "      - Sebagian besar butir soal (khususnya pemahaman nilai, analisis hukum/fiqih, hukum bacaan tajwid, tafsir makna, dan akhlak) WAJIB diawali kutipan ayat Al-Qur'an atau Hadits Nabi yang relevan dan sahih di awal field 'question'.\n"
                . "      - TULISKAN TEKS AYAT/HADITS SECARA UTUH DAN NYATA: cantumkan lafadz Arab (dengan harakat jelas) dan/atau terjemahan artinya dalam bahasa Indonesia, beserta rujukan surah dan nomor ayat yang jelas (contoh: QS. Al-Baqarah [2]: 183 atau HR. Bukhari no. 13).\n"
                . "   b. DILARANG KERAS MENGGUNAKAN PLACEHOLDER '[ayat]' ATAU '[hadits]':\n"
                . "      - DILARANG HANYA menulis teks placeholder seperti '[ayat]', '[hadits]', '[kutipan ayat]', atau '[dalil]'!\n"
                . "      - Soal akan langsung tampil di sistem ujian CBT siswa, sehingga teks dalil dan artinya HARUS TERTULIS LENGKAP DAN NYATA agar siswa dapat membaca dan menganalisisnya.\n"
                . "   c. LARANGAN PENGGUNAAN TANDA PETIK GANDA (\") DALAM TEKS SOAL (AGAR STRUKTUR JSON VALID):\n"
                . "      - DILARANG KERAS menggunakan tanda petik ganda (\") di dalam teks pertanyaan, kutipan dalil, teks terjemahan, maupun pilihan jawaban!\n"
                . "      - Selalu gunakan tanda petik tunggal ('...') atau tanda kurung biasa (...) untuk mengutip arti ayat, hadits, atau istilah.\n"
                . "      - DILARANG mengapit nama surah/ayat dengan kurung siku berpetik ganda seperti \\\"[ayat]\\\" atau \"[ayat]\". Gunakan format biasa: (QS. Al-Baqarah [2]: 183).\n\n"
            ) : '')
            . "FORMAT OUTPUT WAJIB JSON ARRAY (tanpa teks pengantar, tanpa backtick, tanpa markdown):\n"
            . "ATURAN TANDA PETIK: Tanda petik ganda (\") HANYA untuk membungkus properti dan nilai JSON. Di dalam teks pertanyaan (question) dan opsi (options), DILARANG menggunakan tanda petik ganda (\"), gunakan tanda petik tunggal (') atau tanda kurung ().\n"
            . "Setiap item memiliki field:\n"
            . "- type: 'pg' | 'pg_kompleks' | 'benar_salah' | 'esai'\n"
            . "- question: teks soal lengkap (beserta stimulus jika ada)\n"
            . "- options: object opsi jawaban A, B, C, D" . ($opsiCount == 5 ? ", E" : "") . " (untuk tipe pg, pg_kompleks, benar_salah)\n"
            . "- answer: kunci jawaban\n"
            . "  * Untuk 'pg': huruf opsi tunggal, contoh: 'B'\n"
            . "  * Untuk 'pg_kompleks': huruf opsi dipisah koma, contoh: 'A,C'\n"
            . "  * Untuk 'benar_salah': question=stimulus utama, options={A:'pernyataan 1',B:'pernyataan 2',C:'pernyataan 3',D:'pernyataan 4'}, answer='B,S,B,S' (dinilai B/S independen per pernyataan, campurkan B dan S)\n"
            . "  * Untuk 'esai': tanpa field options, answer=rubrik/kunci jawaban lengkap\n\n"
            . "Contoh JSON Array:\n"
            . "[\n"
            . "  {\"type\":\"pg\",\"question\":\"...\",\"options\":{$samplePgOptions},\"answer\":\"B\"},\n"
            . "  {\"type\":\"pg_kompleks\",\"question\":\"...\",\"options\":{$samplePgOptions},\"answer\":\"A,C\"},\n"
            . "  {\"type\":\"benar_salah\",\"question\":\"Perhatikan pernyataan berikut!\",\"options\":{\"A\":\"pernyataan 1\",\"B\":\"pernyataan 2\",\"C\":\"pernyataan 3\",\"D\":\"pernyataan 4\"},\"answer\":\"B,S,B,S\"},\n"
            . "  {\"type\":\"esai\",\"question\":\"...\",\"answer\":\"kunci jawaban lengkap\"}\n"
            . "]\n\n"
            . ($jenisAsesmen === 'sumatif'
                ? "- SUMATIF: soal harus MERATA mencakup SEMUA lingkup materi yang tercantum di atas.\n"
                : "- FORMATIF: soal fokus pada materi yang dipilih secara mendalam.\n")
            . "- KEMBALIKAN HANYA JSON ARRAY MURNI!";

        return $prompt;
    }

    /**
     * Simpan soal hasil AI ke bank soal yang dipilih (multi-tipe)
     */
    public function saveSoalToBank()
    {
        $bankId = $this->request->getPost('bank_id');
        $soalJson = $this->request->getPost('soal_json');

        if (!$bankId || !$soalJson) {
            return $this->response->setJSON(['success' => false, 'error' => 'Data tidak lengkap.']);
        }

        helper('cbt');
        if (!can_access_cbt_bank($bankId)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Akses ditolak ke bank soal ini.']);
        }

        $soalList = json_decode($soalJson, true);
        if (!is_array($soalList) || empty($soalList)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Data soal kosong atau tidak valid.']);
        }

        $questionModel = new \App\Models\CbtQuestionModel();
        $bankModel = new \App\Models\CbtBankSoalModel();
        $inserted = 0;
        $failed = 0;

        foreach ($soalList as $soal) {
            $tipe = $soal['type'] ?? 'pg';

            // Normalisasi tipe dari berbagai variasi output AI
            $tipe = match(strtolower(trim($tipe))) {
                'pg', 'pilihan_ganda', 'pilgan', 'multiple_choice' => 'pg',
                'pg_kompleks', 'pgk', 'pilihan_ganda_kompleks', 'multiple_choice_complex', 'pg kompleks' => 'pg_kompleks',
                'benar_salah', 'bs', 'true_false', 'betul_salah' => 'benar_salah',
                'esai', 'essay', 'uraian' => 'esai',
                default => $tipe,
            };

            $ans = $soal['answer'] ?? null;
            if (is_array($ans)) {
                $ans = implode(',', $ans);
            }
            
            // Normalisasi format jawaban agar sesuai dengan parser CbtBankSoal & detail view
            if (is_string($ans)) {
                if ($tipe === 'pg_kompleks') {
                    preg_match_all('/[A-E]/i', $ans, $matches);
                    if (!empty($matches[0])) {
                        $uniqueKeys = array_unique(array_map('strtoupper', $matches[0]));
                        sort($uniqueKeys);
                        $ans = implode(',', $uniqueKeys);
                    }
                } elseif ($tipe === 'benar_salah') {
                    $rawKey = strtoupper(preg_replace('/\s+/', '', $ans));
                    if (strpos($rawKey, ',') !== false) {
                        $ans = $rawKey;
                    } else {
                        $chars = str_split($rawKey);
                        $ans = implode(',', $chars);
                    }
                } else {
                    $ans = trim($ans);
                }
            }

            // Normalisasi literal '\n' dan '\r\n' jika masih berbentuk teks literal
            $cleanQuestionText = str_replace(["\\r\\n", "\\r", "\\n"], ["\r\n", "\r", "\n"], (string)($soal['question'] ?? ''));
            $qHtml = nl2br(trim($cleanQuestionText), false);

            // Build raw_text for editor compatibility
            $rawText = '<p>' . $qHtml . '</p>';
            if ($tipe === 'benar_salah') {
                $rawText .= '<p>Tipe: BS</p>';
            } elseif ($tipe === 'pg_kompleks') {
                $rawText .= '<p>Tipe: PGK</p>';
            }
            
            $cleanOptions = [];
            if (!empty($soal['options']) && is_array($soal['options'])) {
                foreach ($soal['options'] as $key => $val) {
                    $cleanVal = str_replace(["\\r\\n", "\\r", "\\n"], ["\r\n", "\r", "\n"], (string) $val);
                    $optKey = strtoupper(trim((string)$key));
                    $cleanOptions[$optKey] = $cleanVal;
                    $rawText .= '<p>' . $optKey . '. ' . nl2br(trim($cleanVal), false) . '</p>';
                }
            }
            
            if ($tipe !== 'esai') {
                $rawText .= '<p>Kunci: ' . $ans . '</p>';
            } else {
                $rawText .= '<p>Kunci: esai</p>';
                $rawText .= '<p>Jawaban: ' . $ans . '</p>';
            }

            $data = [
                'bank_id' => $bankId,
                'question_text' => $cleanQuestionText,
                'question_type' => $tipe,
                'score' => ($tipe === 'esai') ? 0 : 1,
                'option_a' => $cleanOptions['A'] ?? null,
                'option_b' => $cleanOptions['B'] ?? null,
                'option_c' => $cleanOptions['C'] ?? null,
                'option_d' => $cleanOptions['D'] ?? null,
                'option_e' => $cleanOptions['E'] ?? null,
                'correct_option' => ($tipe !== 'esai') ? $ans : null,
                'essay_answer' => ($tipe === 'esai') ? $ans : null,
                'raw_text' => $rawText,
            ];

            // Lewati soal tanpa teks (mustahil dirender)
            if (trim((string) $data['question_text']) === '') {
                $failed++;
                continue;
            }

            try {
                if ($questionModel->insert($data)) {
                    $inserted++;
                } else {
                    $failed++;
                    log_message('error', '[SoalAI] Insert gagal: ' . json_encode($questionModel->errors()));
                }
            } catch (\Throwable $e) {
                $failed++;
                log_message('error', '[SoalAI] Insert exception: ' . $e->getMessage());
            }
        }

        // Bila tidak ada satu pun soal yang berhasil masuk → laporkan gagal
        if ($inserted === 0) {
            return $this->response->setJSON([
                'success' => false,
                'error'   => 'Tidak ada soal yang berhasil disimpan.'
                    . ($failed > 0 ? " {$failed} soal dilewati/gagal — cek format soal." : ''),
                'csrf'    => csrf_hash(),
            ]);
        }

        // Update totals
        $total = $questionModel->where('bank_id', $bankId)->countAllResults();
        $totalPg = $questionModel->where('bank_id', $bankId)->where('question_type', 'pg')->countAllResults();
        $totalPgk = $questionModel->where('bank_id', $bankId)->where('question_type', 'pg_kompleks')->countAllResults();
        $totalBs = $questionModel->where('bank_id', $bankId)->where('question_type', 'benar_salah')->countAllResults();
        $totalEsai = $questionModel->where('bank_id', $bankId)->where('question_type', 'esai')->countAllResults();

        $bankModel->update($bankId, [
            'total_questions' => $total,
            'total_pg' => $totalPg,
            'total_pg_kompleks' => $totalPgk,
            'total_bs' => $totalBs,
            'total_esai' => $totalEsai,
        ]);

        $message = "{$inserted} soal berhasil ditambahkan ke bank soal.";
        if ($failed > 0) {
            $message .= " {$failed} soal dilewati (teks kosong / format tidak valid).";
        }

        return $this->response->setJSON([
            'success'  => true,
            'message'  => $message,
            'inserted' => $inserted,
            'failed'   => $failed,
            'csrf'     => csrf_hash(),
        ]);
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
            // Admin or Kepsek: kelas aktif di tahun ajaran aktif
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $activeYear['id'] ?? 0;
            $classModel = new \App\Models\ClassModel();
            $classes = $classModel->getClassesByAcademicYear($activeYearId);
            
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
        $modul = $modulModel->find($id);
        if ($modul) {
            if (!empty($modul['file_pdf'])) {
                $filePath = FCPATH . 'uploads/modul_ajar/' . $modul['file_pdf'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                }
            }
            $modulModel->delete($id);
        }

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id=$classId&subject_id=$subjectId"))->with('success', 'Modul Ajar berhasil dihapus.');
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

        $newPdfName = null;
        if (($sourceModul['tipe'] ?? 'editor') === 'pdf' && !empty($sourceModul['file_pdf'])) {
            $srcPath = FCPATH . 'uploads/modul_ajar/' . $sourceModul['file_pdf'];
            if (file_exists($srcPath)) {
                $ext = pathinfo($sourceModul['file_pdf'], PATHINFO_EXTENSION) ?: 'pdf';
                $newPdfName = 'modul_atp_' . $atpId . '_class_' . $targetClassId . '_' . time() . '.' . $ext;
                @copy($srcPath, FCPATH . 'uploads/modul_ajar/' . $newPdfName);
            }
        }

        $modulModel->insert([
            'atp_id'     => $atpId,
            'subject_id' => $subjectId,
            'class_id'   => $targetClassId,
            'teacher_id' => $targetTeacherId,
            'content'    => $sourceModul['content'] ?? '',
            'tipe'       => $sourceModul['tipe'] ?? 'editor',
            'file_pdf'   => $newPdfName ?? ($sourceModul['file_pdf'] ?? null),
        ]);

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id=$targetClassId&subject_id=$subjectId"))
            ->with('success', 'Modul berhasil disalin. Anda dapat mengeditnya jika diperlukan.');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // KKTP (Kriteria Ketercapaian Tujuan Pembelajaran)
    // ═══════════════════════════════════════════════════════════════════════

    public function kktp()
    {
        $data['title'] = 'Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)';

        $filters = $this->getAvailableFilters();
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['readonly'] = $filters['readonly'];
        $data['auto_class'] = $filters['auto_class'];

        $classId = $filters['selected_class'];
        $subjectId = $this->request->getGet('subject_id');
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['kktp_list'] = [];
        $data['available_tps'] = [];

        if ($classId && $subjectId) {
            $kktpModel = new \App\Models\KktpModel();

            $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
            $classLevel = (int) ($classInfo['level'] ?? 0);
            $className  = $classInfo['name'] ?? '';

            // Cek multi-guru (dengan filter tahun ajaran aktif)
            $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

            // 1. Ambil TP yang terhubung ke ATP milik kelas ini secara langsung
            $tpList = $this->db->table('tujuan_pembelajaran tp')
                ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.atp_id')
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = tp.atp_id')
                ->where('tp.subject_id', $subjectId)
                ->where('atp.class_id', $classId)
                ->groupBy('tp.id')
                ->orderBy('tp.kode_tp', 'ASC')
                ->get()->getResultArray();

            // 2. Fallback: TP mandiri dengan kolom kelas sesuai level/nama kelas
            if (empty($tpList) && $classLevel > 0) {
                $tpList = $this->db->table('tujuan_pembelajaran tp')
                    ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.atp_id')
                    ->where('tp.subject_id', $subjectId)
                    ->groupStart()
                        ->where('tp.kelas', (string)$classLevel)
                        ->orWhere('tp.kelas', $className)
                    ->groupEnd()
                    ->groupBy('tp.id')
                    ->orderBy('tp.kode_tp', 'ASC')
                    ->get()->getResultArray();
            }

            // 3. Fallback jika single-guru: jika kelas ini belum punya TP, pinjam dari kelas lain se-level
            if (empty($tpList) && $classLevel > 0 && !$isMultiGuru) {
                $classIdsAtLevel = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
                if (!empty($classIdsAtLevel)) {
                    $tpList = $this->db->table('tujuan_pembelajaran tp')
                        ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.atp_id')
                        ->join('alur_tujuan_pembelajaran atp', 'atp.id = tp.atp_id')
                        ->where('tp.subject_id', $subjectId)
                        ->whereIn('atp.class_id', $classIdsAtLevel)
                        ->groupBy('tp.id')
                        ->orderBy('tp.kode_tp', 'ASC')
                        ->get()->getResultArray();
                }
            }

            // Deduplikasi TP berdasarkan kode_tp + deskripsi
            $uniqueTpList = [];
            $seenTp = [];
            foreach ($tpList as $tp) {
                $key = trim(($tp['kode_tp'] ?? '') . '|' . ($tp['deskripsi'] ?? ''));
                if (!isset($seenTp[$key])) {
                    $seenTp[$key] = true;
                    $uniqueTpList[] = $tp;
                }
            }
            $tpList = $uniqueTpList;
            $data['available_tps'] = $tpList;

            // Auto-generate KKTP untuk TP kelas ini yang belum ada
            if (!$data['readonly'] && !empty($tpList)) {
                foreach ($tpList as $tp) {
                    $kode = trim($tp['kode_tp'] ?? '');
                    $deskripsi = trim($tp['deskripsi'] ?? '');
                    if ($kode !== '') {
                        $pattern = '/^(' . preg_quote($kode, '/') . '|TP\s*' . preg_quote($kode, '/') . ')[\s\.\-:\–]*/i';
                        $cleanDeskripsi = preg_replace($pattern, '', $deskripsi);
                        $tpText = trim($kode . ' - ' . $cleanDeskripsi);
                    } else {
                        $tpText = $deskripsi;
                    }

                    $exists = $kktpModel
                        ->where('subject_id', $subjectId)
                        ->where('class_id', $classId)
                        ->groupStart()
                            ->where('tp_id', $tp['id'])
                            ->orWhere('tujuan_pembelajaran', $tpText)
                            ->orWhere('tujuan_pembelajaran', $deskripsi)
                        ->groupEnd()
                        ->first();

                    if (!$exists) {
                        $kktpModel->insert([
                            'class_id'              => (int)$classId,
                            'subject_id'            => (int)$subjectId,
                            'tp_id'                 => (int)$tp['id'],
                            'tujuan_pembelajaran'   => $tpText,
                            'kriteria_1_interval'   => '0-40%',
                            'kriteria_1_label'      => 'Belum Tercapai',
                            'kriteria_1_intervensi' => 'Remedial di seluruh bagian',
                            'kriteria_2_interval'   => '41-65%',
                            'kriteria_2_label'      => 'Mulai Tercapai',
                            'kriteria_2_intervensi' => 'Remedial di bagian yang diperlukan',
                            'kriteria_3_interval'   => '66-85%',
                            'kriteria_3_label'      => 'Tercapai',
                            'kriteria_3_intervensi' => 'Tidak perlu remedial',
                            'kriteria_4_interval'   => '86-100%',
                            'kriteria_4_label'      => 'Melampaui',
                            'kriteria_4_intervensi' => 'Diberikan pengayaan',
                        ]);
                    }
                }
            }

            // Load KKTP yang spesifik untuk kelas yang dipilih
            $kktpList = $kktpModel
                ->where('subject_id', $subjectId)
                ->where('class_id', $classId)
                ->orderBy('id', 'ASC')
                ->findAll();

            // Jika kelas ini belum punya KKTP dan single-guru, fallback ke kelas lain se-level
            if (empty($kktpList) && $classLevel > 0 && !$isMultiGuru) {
                $classIdsAtLevel = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
                if (!empty($classIdsAtLevel)) {
                    $kktpList = $kktpModel
                        ->where('subject_id', $subjectId)
                        ->whereIn('class_id', $classIdsAtLevel)
                        ->orderBy('id', 'ASC')
                        ->findAll();
                }
            }

            // Deduplikasi daftar KKTP berdasarkan teks tujuan_pembelajaran
            $uniqueKktpList = [];
            $seenKktp = [];
            foreach ($kktpList as $k) {
                // Normalisasi teks untuk perbandingan (abaikan perbedaan kode prefix jika deskripsi sama)
                $normText = preg_replace('/^(\d+(\.\d+)*\s*[\-\.]\s*)/', '', trim($k['tujuan_pembelajaran']));
                $normKey = strtolower($normText ?: $k['tujuan_pembelajaran']);
                if (!isset($seenKktp[$normKey])) {
                    $seenKktp[$normKey] = true;
                    $uniqueKktpList[] = $k;
                }
            }
            $data['kktp_list'] = $uniqueKktpList;
        }

        return view('admin/administrasi_guru/kktp', $data);
    }

    public function kktpStore()
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah data.');
        }

        $classId = $this->request->getPost('class_id');
        $subjectId = $this->request->getPost('subject_id');

        if (empty($classId) || empty($subjectId)) {
            return redirect()->back()->with('error', 'Kelas dan Mata Pelajaran wajib dipilih.');
        }

        $id = $this->request->getPost('id');
        $kktpModel = new \App\Models\KktpModel();

        $data = [
            'class_id'              => $classId,
            'subject_id'            => $subjectId,
            'tp_id'                 => $this->request->getPost('tp_id') ?: null,
            'tujuan_pembelajaran'   => $this->request->getPost('tujuan_pembelajaran'),
            'kriteria_1_interval'   => $this->request->getPost('kriteria_1_interval') ?: '0-40%',
            'kriteria_1_label'      => $this->request->getPost('kriteria_1_label') ?: 'Belum Tercapai',
            'kriteria_1_intervensi' => $this->request->getPost('kriteria_1_intervensi') ?: 'Remedial di seluruh bagian',
            'kriteria_2_interval'   => $this->request->getPost('kriteria_2_interval') ?: '41-65%',
            'kriteria_2_label'      => $this->request->getPost('kriteria_2_label') ?: 'Mulai Tercapai',
            'kriteria_2_intervensi' => $this->request->getPost('kriteria_2_intervensi') ?: 'Remedial di bagian yang diperlukan',
            'kriteria_3_interval'   => $this->request->getPost('kriteria_3_interval') ?: '66-85%',
            'kriteria_3_label'      => $this->request->getPost('kriteria_3_label') ?: 'Tercapai',
            'kriteria_3_intervensi' => $this->request->getPost('kriteria_3_intervensi') ?: 'Tidak perlu remedial',
            'kriteria_4_interval'   => $this->request->getPost('kriteria_4_interval') ?: '86-100%',
            'kriteria_4_label'      => $this->request->getPost('kriteria_4_label') ?: 'Melampaui',
            'kriteria_4_intervensi' => $this->request->getPost('kriteria_4_intervensi') ?: 'Diberikan pengayaan',
        ];

        if ($id) {
            $kktpModel->update($id, $data);
            $msg = 'KKTP berhasil diperbarui.';
        } else {
            $kktpModel->insert($data);
            $msg = 'KKTP berhasil disimpan.';
        }

        return redirect()->to(base_url("admin/administrasi-guru/kktp?class_id={$classId}&subject_id={$subjectId}"))
            ->with('success', $msg);
    }

    public function kktpDelete($id)
    {
        $kktpModel = new \App\Models\KktpModel();
        $kktp = $kktpModel->find($id);
        if ($kktp) {
            $kktpModel->delete($id);
            return redirect()->to(base_url("admin/administrasi-guru/kktp?class_id={$kktp['class_id']}&subject_id={$kktp['subject_id']}"))
                ->with('success', 'KKTP berhasil dihapus.');
        }
        return redirect()->back();
    }

    public function kktpPrint($classId, $subjectId)
    {
        $subject = $this->subjectModel->find($subjectId);
        if (!$subject) return redirect()->back();

        $school = $this->schoolModel->getProfile();
        $classInfo = $this->db->table('classes')->where('id', $classId)->get()->getRowArray();
        $classLevel = (int) ($classInfo['level'] ?? 0);

        // Guru pengampu
        $assignment = $this->teachingAssignmentModel
            ->where('subject_id', $subjectId)
            ->where('class_id', $classId)
            ->first();
        $teacher = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;

        // Ambil KKTP untuk kelas yang dipilih (fallback se-level jika kelas ini belum punya)
        $kktpModel = new \App\Models\KktpModel();
        $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

        $kktpList = $kktpModel->getByClassSubject((int)$classId, (int)$subjectId);
        if (empty($kktpList) && $classLevel > 0 && !$isMultiGuru) {
            $classIds = array_column(
                $this->db->table('classes')->select('id')->where('level', $classLevel)->get()->getResultArray(),
                'id'
            );
            $kktpList = !empty($classIds)
                ? $kktpModel->where('subject_id', $subjectId)->whereIn('class_id', $classIds)->orderBy('id')->findAll()
                : [];
        }

        // Deduplikasi daftar KKTP agar tidak ada penulisan TP ganda
        $uniqueKktpList = [];
        $seenKktp = [];
        foreach ($kktpList as $k) {
            $normText = preg_replace('/^(\d+(\.\d+)*\s*[\-\.]\s*)/', '', trim($k['tujuan_pembelajaran']));
            $normKey = strtolower($normText ?: $k['tujuan_pembelajaran']);
            if (!isset($seenKktp[$normKey])) {
                $seenKktp[$normKey] = true;
                $uniqueKktpList[] = $k;
            }
        }
        $kktpList = $uniqueKktpList;

        return view('admin/administrasi_guru/kktp_print', [
            'school'   => $school,
            'subject'  => $subject,
            'teacher'  => $teacher,
            'classInfo'=> $classInfo,
            'kktpList' => $kktpList,
        ]);
    }
}
