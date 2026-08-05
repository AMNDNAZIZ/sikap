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
            $data['kktp_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
            $data['modul_stats'] = ['complete' => 0, 'partial' => 0, 'empty' => 0];
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

                    // Cek multi-guru di level yang sama (sama seperti logika ATP)
                    $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

                    // Untuk single-guru: kumpulkan semua class_id se-level (lintas tahun ajaran)
                    $classIdsAtLevel = [];
                    if (!$isMultiGuru) {
                        $classIdsAtLevel = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
                    }

                    $atpResult = $this->loadAtpWithElemen((int)$subjectId, $classLevel);
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

        // Load data ATP
        $atpRaw = $this->atpModel->find($atpId);
        if (!$atpRaw) {
            return $this->response->setJSON(['error' => 'ATP tidak ditemukan.']);
        }

        $atpResult = $this->loadAtpWithElemen((int)$atpRaw['subject_id']);
        $atpFull = null;
        foreach ($atpResult['list'] as $a) {
            if ($a['id'] == $atpId) { $atpFull = $a; break; }
        }
        $atp = $atpFull ?? $atpRaw;

        $tps = $atp['tps'] ?? [];
        $tpDeskripsi = "";
        foreach ($tps as $tp) {
            $kodeTp = !empty($tp['kode_tp']) ? $tp['kode_tp'] . '. ' : '';
            $tpDeskripsi .= $kodeTp . $tp['deskripsi'] . "\n";
        }

        $cpDeskripsiAll = "";
        foreach ($atp['elemen_list'] ?? [] as $el) {
            $cpDeskripsiAll .= ($el['elemen'] ?? '') . ": " . ($el['cp_deskripsi'] ?? '') . "\n";
        }

        $subject = $this->subjectModel->find($subjectId);
        $school = $this->schoolModel->getProfile();

        // Resolve teacher
        if ($user['role_id'] == 3) {
            $teacher = $this->teacherModel->find($user['related_id']);
        } else {
            $assignment = $this->db->table('teaching_assignments')
                ->where('subject_id', $subjectId)
                ->where('class_id', $atpRaw['class_id'])
                ->get()->getRowArray();
            $teacher = $assignment ? $this->teacherModel->find($assignment['teacher_id']) : null;
        }

        $totalJp = (int) ($atp['alokasi_waktu'] ?? 4);

        // JP per minggu dari promes
        $prosemJpPerWeek = $this->db->table('prosem_distributions')
            ->where('atp_id', $atpId)->where('jp >', 0)
            ->orderBy('id', 'ASC')->limit(1)->get()->getRowArray();
        $jpPerMinggu = (int) ($prosemJpPerWeek['jp'] ?? 0);

        if ($jpPerMinggu <= 0) {
            $totalDistributed = $this->db->table('prosem_distributions')
                ->where('atp_id', $atpId)->where('jp >', 0)->countAllResults();
            if ($totalDistributed > 0) {
                $sumJp = $this->db->table('prosem_distributions')
                    ->selectSum('jp')->where('atp_id', $atpId)->where('jp >', 0)->get()->getRowArray();
                $jpPerMinggu = (int) round(($sumJp['jp'] ?? $totalJp) / $totalDistributed);
            } else {
                $jpPerMinggu = min($totalJp, 4);
            }
        }

        $pertemuanPerMinggu = ($jpPerMinggu <= 3) ? 1 : 2;
        $jpPerPertemuan = max(1, (int) round($jpPerMinggu / $pertemuanPerMinggu));
        $jumlahPertemuan = max(1, (int) ceil($totalJp / $jpPerPertemuan));
        $menitPerPertemuan = $jpPerPertemuan * 45;

        $prompt = "Buatkan Modul Ajar Kurikulum Merdeka DEEP LEARNING (MJM) 2025 dengan STRUKTUR PERSIS mengikuti rujukan 'Guru Karier' (Poin A sampai K).\n\n"
            . "ATURAN:\n"
            . "0. FORMAT JUDUL BAGIAN: Gunakan <h3> dengan format LANGSUNG 'A. Identitas Modul', 'B. Kompetensi Awal', dst. JANGAN tulis kata 'Bagian'. Contoh BENAR: <h3>B. Kompetensi Awal</h3>.\n"
            . "1. A. Identitas Modul: Tabel 2-kolom. Alokasi Waktu: '{$totalJp} JP ({$jumlahPertemuan} Pertemuan × {$jpPerPertemuan} JP)'.\n"
            . "2. B. Kompetensi Awal: Tulis HANYA CP.\n"
            . "3. C. Tujuan Pembelajaran: Masukkan TP persis seperti input.\n"
            . "4. Bagian F (Langkah Pembelajaran): Buat TEPAT {$jumlahPertemuan} tabel pertemuan. Kolom: (Tahap | Sintaks | Deskripsi | Waktu). TOTAL waktu setiap pertemuan = {$menitPerPertemuan} menit.\n"
            . "5. Setiap pertemuan WAJIB mengandung MJM: Mindful (~10 menit), Meaningful (~" . max(10, $menitPerPertemuan - 25) . " menit), Joyful (~15 menit).\n"
            . "6. Bagian K (Penilaian): Buat spesifik (Penilaian Proses + Penilaian Hasil).\n"
            . "7. JANGAN buat bagian Tanda Tangan.\n\n"
            . "DATA INPUT:\n"
            . "Sekolah: " . ($school['name'] ?? '-') . "\n"
            . "Penyusun: " . ($teacher['name'] ?? '-') . "\n"
            . "Mapel: " . ($subject['name'] ?? '-') . "\n"
            . "Materi: " . ($atp['lingkup_materi'] ?? '-') . "\n"
            . "Kelas: " . ($tps[0]['kelas'] ?? '-') . "\n"
            . "Alokasi: {$totalJp} JP → {$jumlahPertemuan} Pertemuan × {$jpPerPertemuan} JP ({$menitPerPertemuan} menit)\n"
            . "CP:\n" . $cpDeskripsiAll . "\n"
            . "TP:\n" . $tpDeskripsi . "\n\n"
            . "KEMBALIKAN HANYA PURE HTML (tanpa markdown, tanpa code block).";

        return $this->response->setJSON([
            'success' => true,
            'prompt' => $prompt,
            'info' => [
                'materi' => $atp['lingkup_materi'] ?? '-',
                'total_jp' => $totalJp,
                'pertemuan' => $jumlahPertemuan,
                'jp_per_pertemuan' => $jpPerPertemuan,
            ],
        ]);
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

        // Resolve teacher
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

        // Cek duplikat
        $existing = $modulModel->where('atp_id', $atpId)->where('class_id', $classId)->first();
        if ($existing) {
            $modulModel->update($existing['id'], ['content' => $content]);
            $msg = 'Modul berhasil diperbarui dari input manual.';
        } else {
            $modulModel->insert([
                'atp_id'     => $atpId,
                'subject_id' => $subjectId,
                'class_id'   => $classId,
                'teacher_id' => $targetTeacherId,
                'content'    => $content,
            ]);
            $msg = 'Modul berhasil disimpan dari input manual.';
        }

        return redirect()->to(base_url("admin/administrasi-guru/modul-ajar?class_id={$classId}&subject_id={$subjectId}"))
            ->with('success', $msg);
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

        // Ambil JP per minggu dari Program Semester (Promes)
        // untuk menentukan jumlah pertemuan yang akurat
        $prosemJpPerWeek = $this->db->table('prosem_distributions')
            ->where('atp_id', $atpId)
            ->where('jp >', 0)
            ->orderBy('id', 'ASC')
            ->limit(1)
            ->get()->getRowArray();

        $jpPerMinggu = (int) ($prosemJpPerWeek['jp'] ?? 0);

        // Fallback: jika promes belum diisi, hitung dari total JP
        if ($jpPerMinggu <= 0) {
            // Estimasi dari total JP / jumlah minggu efektif (asumsi)
            $totalDistributed = $this->db->table('prosem_distributions')
                ->where('atp_id', $atpId)
                ->where('jp >', 0)
                ->countAllResults();
            if ($totalDistributed > 0) {
                $sumJp = $this->db->table('prosem_distributions')
                    ->selectSum('jp')
                    ->where('atp_id', $atpId)
                    ->where('jp >', 0)
                    ->get()->getRowArray();
                $jpPerMinggu = (int) round(($sumJp['jp'] ?? $totalJp) / $totalDistributed);
            } else {
                // Tidak ada promes sama sekali, fallback ke alokasi_waktu
                $jpPerMinggu = min($totalJp, 4);
            }
        }

        // Hitung jumlah pertemuan per minggu berdasarkan JP per minggu
        // Aturan: 1-3 JP = 1 pertemuan, 4-8 JP = 2 pertemuan
        if ($jpPerMinggu <= 3) {
            $pertemuanPerMinggu = 1;
        } else {
            $pertemuanPerMinggu = 2;
        }

        // JP per pertemuan
        $jpPerPertemuan = max(1, (int) round($jpPerMinggu / $pertemuanPerMinggu));

        // Total pertemuan untuk seluruh alokasi waktu ATP ini
        $jumlahPertemuan = max(1, (int) ceil($totalJp / $jpPerPertemuan));

        // Menit per pertemuan
        $menitPerPertemuan = $jpPerPertemuan * 45;

        $prompt = "Buatkan Modul Ajar Kurikulum Merdeka DEEP LEARNING (MJM) 2025 dengan STRUKTUR PERSIS mengikuti rujukan 'Guru Karier' (Poin A sampai K).\n\n"
                . "ATURAN TATA LETAK (WAJIB & KETAT):\n"
                . "1. FORMAT JUDUL BAGIAN: Gunakan tag <h3> dengan format LANGSUNG huruf dan judul saja, TANPA kata 'Bagian'. Contoh yang BENAR: <h3>B. Kompetensi Awal</h3>. Contoh yang SALAH: <h3>Bagian B (Kompetensi Awal)</h3>.\n"
                . "2. Bagian A (Identitas): Tabel 2-kolom. Kolom kiri (Label) WAJIB 'text-align: left; width: 170px; font-weight: bold;'. Isi baris Alokasi Waktu dengan '{$totalJp} JP ({$jumlahPertemuan} Pertemuan × {$jpPerPertemuan} JP)'.\n"
                . "3. Bagian B (Kompetensi Awal): Tuliskan HANYA Capaian Pembelajaran (CP) saja, jangan sertakan Tujuan Pembelajaran (TP).\n"
                . "4. Bagian C (Tujuan Pembelajaran): Masukkan Tujuan Pembelajaran persis seperti input HTML, JANGAN buatkan penomoran list otomatis (1, 2) lagi karena sudah dimuat di teks.\n"
                . "5. Bagian F (Langkah Pembelajaran): Pindahkan dan buat TEPAT {$jumlahPertemuan} tabel pertemuan di sini, masing-masing berlabel 'Pertemuan ke-1', 'Pertemuan ke-2', dst.\n"
                . "   Setiap tabel pertemuan memiliki 4 kolom: (Tahap | Sintaks | Deskripsi | Waktu) dengan Header Cream (#fff9c4).\n"
                . "   TOTAL waktu setiap pertemuan WAJIB {$menitPerPertemuan} menit ({$jpPerPertemuan} JP × 45 menit).\n"
                . "6. Bagian K (Penilaian / Asesmen): Buat SPESIFIK. Jelaskan 'Penilaian Proses' (bentuknya) dan 'Penilaian Hasil' (instrumennya) yang benar-benar dapat mengukur Tujuan Pembelajaran tersebut.\n"
                . "7. JANGAN MENGHASILKAN bagian Tanda Tangan (L, M, dst) sama sekali karena sistem pencetak otomatis telah menyediakan kolom tanda tangan di bawah modul.\n\n"
                . "PANDUAN DEEP LEARNING MJM (SANGAT PENTING — WAJIB ADA DI SETIAP PERTEMUAN):\n"
                . "Setiap pertemuan wajib mengandung ketiga elemen MJM dalam kolom 'Deskripsi' dengan proporsi waktu berikut:\n"
                . "  a. MINDFUL (~10 menit): Tuliskan SCRIPT kata-kata guru untuk teknik STOP / mindfulness singkat.\n"
                . "     Contoh: 'Guru mengajak siswa memejamkan mata 30 detik, fokus pada napas, lalu bertanya: Apa yang ingin kamu capai hari ini?'\n"
                . "  b. MEANINGFUL (~" . max(10, $menitPerPertemuan - 25) . " menit): Eksplorasi → Diskusi Kolaboratif → Koneksi ke dunia nyata yang SPESIFIK untuk materi " . $atp['lingkup_materi'] . ".\n"
                . "     Gunakan model: Motivasi → Eksplorasi Konsep → Diskusi Kelompok → Presentasi/Sintesis.\n"
                . "     JANGAN gunakan kalimat normatif 'Guru menjelaskan'. Gunakan deskripsi aksi nyata.\n"
                . "  c. JOYFUL (~15 menit): Rincikan AKTIVITAS/GAME/PROYEK MINI yang memancing emosi positif terkait materi.\n\n"
                . "DATA INPUT:\n"
                . "Sekolah: " . ($school['name'] ?? '-') . "\n"
                . "Penyusun: " . ($teacher['name'] ?? '-') . "\n"
                . "Mapel: " . $subject['name'] . "\n"
                . "Materi: " . $atp['lingkup_materi'] . "\n"
                . "Kelas: " . ($tps[0]['kelas'] ?? '-') . "\n"
                . "Alokasi Waktu: {$totalJp} JP → {$jumlahPertemuan} Pertemuan (masing-masing {$jpPerPertemuan} JP / {$menitPerPertemuan} menit)\n"
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

            $atpResult = $this->loadAtpWithElemen((int)$subjectId, $classLevel);
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

        // Build soal request per tipe
        $soalRequest = '';
        // SD/SMP: PGK tetap 4 opsi (A-D), SMA: 5 opsi (A-E)
        $pgkOpsiCount = ($school['level'] == 3) ? 5 : 4;
        $pgkOpsiLabel = ($pgkOpsiCount == 5) ? '5 opsi: A,B,C,D,E' : '4 opsi: A,B,C,D';

        foreach ($tipeList as $t) {
            $label = match($t['tipe']) {
                'pg' => 'Pilihan Ganda (4 opsi: A,B,C,D — pilih 1 jawaban benar)',
                'pg_kompleks' => "Pilihan Ganda Kompleks ({$pgkOpsiLabel}, bisa lebih dari 1 jawaban benar)",
                'benar_salah' => 'Benar/Salah (1 stimulus + 4 PERNYATAAN yang masing-masing dinilai Benar atau Salah secara INDEPENDEN)',
                'esai' => 'Esai/Uraian singkat',
                default => $t['tipe']
            };
            $soalRequest .= "- {$t['jumlah']} soal tipe {$label}\n";
        }

        $jenjang = ($school['level'] == 1) ? 'SD' : (($school['level'] == 2) ? 'SMP' : 'SMA');
        $kelasLevel = (int)($classInfo['level'] ?? 7);

        // Label jenis asesmen untuk prompt
        $jenisLabel = ($jenisAsesmen === 'sumatif')
            ? "SUMATIF SEMESTER {$semester} (mencakup seluruh materi semester)"
            : "FORMATIF (fokus pada satu materi spesifik)";

        // Tentukan karakteristik soal berdasarkan jenjang dan level kelas
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

        $prompt = "Kamu adalah pembuat soal ujian profesional berpengalaman untuk jenjang {$jenjang}.\n"
            . "Buatkan soal ujian BERKUALITAS TINGGI dengan komposisi:\n{$soalRequest}\n"
            . "JENIS ASESMEN: {$jenisLabel}\n\n"
            . "KONTEKS PEMBELAJARAN:\n"
            . "- Jenjang: {$jenjang}\n"
            . "- Kelas: " . ($classInfo['name'] ?? '-') . " (Level {$kelasLevel})\n"
            . "- Mata Pelajaran: " . ($subject['name'] ?? '-') . "\n"
            . "- Materi/Lingkup:\n" . $materiList . "\n"
            . "- Karakteristik: {$karakteristikLevel}\n"
            . (!empty($tpDeskripsi) ? "- Tujuan Pembelajaran:\n{$tpDeskripsi}\n" : '')
            . "\nPANDUAN KUALITAS SOAL (WAJIB DIPATUHI):\n"
            . "1. DISTRIBUSI LEVEL KOGNITIF (Taksonomi Bloom Revisi):\n"
            . "   - 20% soal C1-C2 (Mengingat & Memahami): definisi, fakta, konsep dasar\n"
            . "   - 30% soal C3 (Menerapkan): aplikasi konsep dalam situasi baru\n"
            . "   - 50% soal C4-C6 (HOTS - Menganalisis, Mengevaluasi, Mencipta): analisis data/teks, membandingkan, menyimpulkan, menilai argumen\n"
            . "2. SOAL HOTS harus mengandung STIMULUS (teks bacaan, data, tabel, situasi, kasus) yang harus dianalisis siswa sebelum menjawab.\n"
            . "3. Untuk mapel BAHASA (Indonesia/Inggris/Daerah):\n"
            . "   - WAJIB sertakan WACANA/TEKS BACAAN yang relevan sebelum soal (dalam field 'question')\n"
            . "   - Format: tuliskan wacana dulu dengan label 'Bacalah teks berikut!' lalu soalnya\n"
            . "   - Teks harus sesuai panjang dan kompleksitas level kelas\n"
            . "4. Untuk soal yang IDEALNYA memerlukan GAMBAR/ILUSTRASI:\n"
            . "   - Sertakan deskripsi dalam kurung siku: [Gambar: deskripsi gambar yang diperlukan]\n"
            . "   - Contoh: [Gambar: Peta Indonesia dengan panah menunjukkan arah angin muson barat]\n"
            . "5. DISTRAKTOR (opsi salah) harus MASUK AKAL dan homogen — jangan opsi yang jelas salah\n"
            . "6. Soal TIDAK BOLEH mengandung petunjuk jawabannya di soal lain\n"
            . "7. Stem soal harus JELAS dan tidak ambigu\n"
            . "8. Hindari soal yang jawabannya 'semua benar' atau 'tidak ada yang benar'\n\n"
            . "FORMAT OUTPUT WAJIB JSON ARRAY (tanpa markdown, tanpa backtick):\n"
            . "Setiap item memiliki field: type, question, options (jika PG/PGK), answer\n"
            . "Contoh:\n"
            . '[{"type":"pg","question":"...","options":{"A":"...","B":"...","C":"...","D":"..."},"answer":"A"},'
            . '{"type":"pg_kompleks","question":"...","options":{"A":"...","B":"...","C":"...","D":"..."' . ($pgkOpsiCount == 5 ? ',"E":"..."' : '') . '},"answer":"A,C"},'
            . '{"type":"benar_salah","question":"Pernyataan utama/stimulus soal","options":{"A":"pernyataan 1","B":"pernyataan 2","C":"pernyataan 3","D":"pernyataan 4"},"answer":"B,S,B,S"},'
            . '{"type":"esai","question":"...","answer":"kunci jawaban lengkap"}]' . "\n\n"
            . "ATURAN TAMBAHAN:\n"
            . "- Untuk tipe benar_salah: question=stimulus utama, options=4 pernyataan (A-D), answer=status B atau S per opsi dipisah koma (contoh: 'B,S,B,B')\n"
            . "- PENTING SOAL BS: Opsi A-D adalah PERNYATAAN BERBEDA yang masing-masing dinilai Benar/Salah secara INDEPENDEN. INI BUKAN pilihan ganda! Setiap pernyataan harus berdiri sendiri dan bisa dinilai kebenarannya. Campurkan pernyataan benar dan salah (jangan semua benar atau semua salah).\n"
            . "- Contoh BS BENAR: question='Perhatikan pernyataan tentang Pancasila berikut!', options: A='Pancasila terdiri dari 5 sila' (B), B='Sila pertama tentang kemanusiaan' (S), C='Pancasila adalah dasar negara' (B), D='Pancasila ditetapkan tanggal 1 Juni 1945' (S)\n"
            . "- Kelompokkan berurutan: PG dulu, lalu PGK, BS, terakhir Esai\n"
            . "- Untuk Esai: kunci jawaban harus lengkap (rubrik/poin yang diharapkan)\n"
            . "- Untuk BS: pernyataan harus spesifik, bukan opini\n"
            . ($jenisAsesmen === 'sumatif'
                ? "- SUMATIF: soal harus MERATA mencakup SEMUA materi yang tercantum, jangan fokus 1 materi saja\n"
                  . "- SUMATIF: soal boleh menghubungkan antar-materi (integrasi konsep)\n"
                : "- FORMATIF: soal fokus pada materi spesifik yang dipilih, eksplorasi mendalam\n")
            . "- KEMBALIKAN HANYA JSON ARRAY MURNI, tanpa backtick, tanpa markdown";

        // Call AI
        if ($aiProvider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $payload = json_encode([
                "model" => "llama-3.3-70b-versatile",
                "messages" => [["role" => "user", "content" => $prompt]],
                "temperature" => 0.7
            ]);
            $headers = ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'];
        } else {
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=' . $apiKey;
            $payload = json_encode(["contents" => [["parts" => [["text" => $prompt]]]]]);
            $headers = ['Content-Type: application/json'];
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode != 200) {
            $errRes = json_decode($response, true);
            $errMsg = $errRes['error']['message'] ?? 'HTTP ' . $httpCode;
            return $this->response->setJSON(['success' => false, 'error' => "AI error: {$errMsg}"]);
        }

        $resData = json_decode($response, true);
        if ($aiProvider === 'groq') {
            $content = $resData['choices'][0]['message']['content'] ?? '';
        } else {
            $content = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        $content = preg_replace('/```(?:json)?(.*?)```/s', '$1', $content);
        $content = trim($content);

        $soalList = json_decode($content, true);
        if (!is_array($soalList)) {
            return $this->response->setJSON(['success' => false, 'error' => 'Gagal parse response AI. Coba generate ulang.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'soal' => $soalList,
            'jumlah' => count($soalList),
        ]);
    }

    /**
     * Generate prompt soal saja (tanpa panggil API) — user copy ke AI langsung
     */
    public function getSoalPrompt()
    {
        $user = session()->get('user');
        if (!in_array($user['role_id'], [1, 3])) {
            return $this->response->setJSON(['success' => false, 'error' => 'Akses ditolak.']);
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

        $tpDeskripsi = '';
        if (!empty($selectedTps)) {
            $tps = $this->db->table('tujuan_pembelajaran')
                ->whereIn('id', $selectedTps)->get()->getResultArray();
            foreach ($tps as $tp) {
                $tpDeskripsi .= "- " . ($tp['kode_tp'] ? $tp['kode_tp'] . ': ' : '') . $tp['deskripsi'] . "\n";
            }
        }

        $soalRequest = '';
        $pgkOpsiCount = ($school['level'] == 3) ? 5 : 4;
        $pgkOpsiLabel = ($pgkOpsiCount == 5) ? '5 opsi: A,B,C,D,E' : '4 opsi: A,B,C,D';

        foreach ($tipeList as $t) {
            $label = match($t['tipe']) {
                'pg' => 'Pilihan Ganda (4 opsi: A,B,C,D — pilih 1 jawaban benar)',
                'pg_kompleks' => "Pilihan Ganda Kompleks ({$pgkOpsiLabel}, bisa lebih dari 1 jawaban benar)",
                'benar_salah' => 'Benar/Salah (1 stimulus + 4 PERNYATAAN independen, masing-masing dinilai B/S)',
                'esai' => 'Esai/Uraian singkat',
                default => $t['tipe']
            };
            $soalRequest .= "- {$t['jumlah']} soal tipe {$label}\n";
        }

        $jenjang = ($school['level'] == 1) ? 'SD' : (($school['level'] == 2) ? 'SMP' : 'SMA');
        $kelasLevel = (int)($classInfo['level'] ?? 7);
        $jenisLabel = ($jenisAsesmen === 'sumatif')
            ? "SUMATIF SEMESTER {$semester}"
            : "FORMATIF";

        $prompt = "Buatkan soal ujian {$jenisLabel} untuk:\n"
            . "- Jenjang: {$jenjang}, Kelas: " . ($classInfo['name'] ?? '-') . "\n"
            . "- Mapel: " . ($subject['name'] ?? '-') . "\n"
            . "- Materi:\n" . $materiList . "\n"
            . (!empty($tpDeskripsi) ? "- Tujuan Pembelajaran:\n{$tpDeskripsi}\n" : '')
            . "\nKomposisi soal:\n{$soalRequest}\n"
            . "PANDUAN:\n"
            . "- 20% soal C1-C2 (Mengingat & Memahami)\n"
            . "- 30% soal C3 (Menerapkan)\n"
            . "- 50% soal C4-C6 (HOTS: Menganalisis, Mengevaluasi, Mencipta)\n"
            . "- Soal HOTS harus ada stimulus (teks/data/kasus)\n"
            . "- Distraktor harus masuk akal dan homogen\n"
            . "- Untuk mapel Bahasa: sertakan teks bacaan sebelum soal\n\n"
            . "FORMAT OUTPUT: JSON ARRAY (tanpa markdown/backtick)\n"
            . "Field per item: type, question, options (jika PG/PGK/BS), answer\n"
            . "PENTING untuk tipe benar_salah: question=stimulus utama, options={A:'pernyataan 1',B:'pernyataan 2',C:'pernyataan 3',D:'pernyataan 4'}, answer='B,S,B,S' (B=Benar, S=Salah per opsi)\n"
            . "Soal BS BUKAN pilihan ganda! Setiap opsi adalah PERNYATAAN INDEPENDEN yang dinilai B/S. Campurkan pernyataan benar dan salah.\n"
            . "Contoh:\n"
            . '[{"type":"pg","question":"...","options":{"A":"...","B":"...","C":"...","D":"..."},"answer":"A"},'
            . '{"type":"benar_salah","question":"Stimulus soal","options":{"A":"pernyataan 1","B":"pernyataan 2","C":"pernyataan 3","D":"pernyataan 4"},"answer":"B,S,B,B"},'
            . '{"type":"esai","question":"...","answer":"kunci jawaban"}]';

        return $this->response->setJSON([
            'success' => true,
            'prompt' => $prompt,
        ]);
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

            $data = [
                'bank_id' => $bankId,
                'question_text' => $soal['question'] ?? '',
                'question_type' => $tipe,
                'score' => ($tipe === 'esai') ? 0 : 1,
                'option_a' => $soal['options']['A'] ?? null,
                'option_b' => $soal['options']['B'] ?? null,
                'option_c' => $soal['options']['C'] ?? null,
                'option_d' => $soal['options']['D'] ?? null,
                'option_e' => $soal['options']['E'] ?? null,
                'correct_option' => ($tipe !== 'esai') ? ($soal['answer'] ?? null) : null,
                'essay_answer' => ($tipe === 'esai') ? ($soal['answer'] ?? '') : null,
            ];
            $questionModel->insert($data);
            $inserted++;
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

        return $this->response->setJSON([
            'success' => true,
            'message' => "{$inserted} soal berhasil ditambahkan ke bank soal.",
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

            // Cek multi-guru (dengan filter tahun ajaran aktif)
            $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

            // Tentukan class_ids untuk query (single-guru: lintas semua tahun ajaran)
            if ($isMultiGuru) {
                $effectiveClassIds = [(int)$classId];
            } else {
                $effectiveClassIds = $this->getClassIdsForDocumentSearch($classLevel, (int)$classId);
            }

            // Ambil semua TP dari ATP yang sudah ada di level ini
            $tpList = $this->db->table('tujuan_pembelajaran tp')
                ->select('tp.id, tp.kode_tp, tp.deskripsi, tp.lingkup_materi, tp.atp_id')
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = tp.atp_id')
                ->where('tp.subject_id', $subjectId)
                ->whereIn('atp.class_id', $effectiveClassIds)
                ->groupBy('tp.id')
                ->orderBy('tp.kode_tp', 'ASC')
                ->get()->getResultArray();

            $data['available_tps'] = $tpList;

            // Auto-generate KKTP untuk TP yang belum ada
            if (!$data['readonly'] && !empty($tpList)) {
                foreach ($tpList as $tp) {
                    $exists = $kktpModel
                        ->where('subject_id', $subjectId)
                        ->where('tp_id', $tp['id'])
                        ->whereIn('class_id', $effectiveClassIds)
                        ->first();

                    if (!$exists) {
                        $kktpModel->insert([
                            'class_id'              => (int)$classId,
                            'subject_id'            => (int)$subjectId,
                            'tp_id'                 => (int)$tp['id'],
                            'tujuan_pembelajaran'   => $tp['kode_tp'] . ' - ' . $tp['deskripsi'],
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

            // Load KKTP yang sudah ada
            $data['kktp_list'] = $kktpModel
                ->where('subject_id', $subjectId)
                ->whereIn('class_id', $effectiveClassIds)
                ->orderBy('id', 'ASC')
                ->findAll();
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

        // Ambil KKTP (single guru → lintas kelas se-level)
        $kktpModel = new \App\Models\KktpModel();
        $isMultiGuru = $this->isMultiGuruForLevel((int)$subjectId, $classLevel);

        if ($isMultiGuru) {
            $kktpList = $kktpModel->getByClassSubject((int)$classId, (int)$subjectId);
        } else {
            $classIds = array_column(
                $this->db->table('classes')->select('id')->where('level', $classLevel)->get()->getResultArray(),
                'id'
            );
            $kktpList = !empty($classIds)
                ? $kktpModel->where('subject_id', $subjectId)->whereIn('class_id', $classIds)->orderBy('id')->findAll()
                : [];
        }

        return view('admin/administrasi_guru/kktp_print', [
            'school'   => $school,
            'subject'  => $subject,
            'teacher'  => $teacher,
            'classInfo'=> $classInfo,
            'kktpList' => $kktpList,
        ]);
    }
}
