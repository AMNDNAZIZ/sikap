<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use Dompdf\Dompdf;
use Dompdf\Options;

class Grades extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = db_connect();
    }

    // --- Halaman select kelas + mapel ---
    public function select()
    {
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to('/login');
        }

        // admin & kepala sekolah → semua kelas
        if (in_array($user['role_id'], [1,2])) {
            $classes = $this->db->table('classes')->get()->getResultArray();
            $subjects = $this->db->table('subjects')->get()->getResultArray();
        }
        // guru → kelas & mapel sesuai teaching_assignments
        elseif ($user['role_id'] == 3) {
            $assignments = $this->db->table('teaching_assignments ta')
                ->select('c.id as class_id, c.name as class_name, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $user['related_id'])
                ->get()->getResultArray();

            $classes = array_unique(array_map(function($a){ 
                return ['id'=>$a['class_id'], 'name'=>$a['class_name']]; 
            }, $assignments), SORT_REGULAR);

            $subjects = array_unique(array_map(function($a){ 
                return ['id'=>$a['subject_id'], 'name'=>$a['subject_name']]; 
            }, $assignments), SORT_REGULAR);
        } else {
            return redirect()->back()->with('error', 'Role tidak diizinkan.');
        }

        return view('admin/grades/select', [
            'classes' => $classes,
            'subjects' => $subjects,
        ]);
    }

    // --- Lihat nilai siswa ---
    public function view()
    {
        $classId   = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');

        $students = $this->db->table('students s')
            ->select('s.id, s.name')
            ->join('student_records sr', 'sr.student_id = s.id')
            ->where('sr.class_id', $classId)
            ->get()->getResultArray();

        $activeYear = $this->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        $semesters  = [1,2];
        $grades     = [];
        $allFormatifCols = [];
        $allSumatifCols  = [];
        $hasFinal   = false;

        foreach ($semesters as $semester) {
            foreach ($students as $student) {
                $studentId = $student['id'];

                // formatif
                $materials = $this->db->table('subject_materials')
                    ->where('subject_id', $subjectId)
                    ->where('year_id', $activeYear['id'])
                    ->where('semester', $semester)
                    ->get()->getResultArray();

                foreach ($materials as $mIndex => $material) {
                    $methods = $this->db->table('material_scores')
                        ->distinct()->select('type')
                        ->where('material_id', $material['id'])
                        ->get()->getResultArray();

                    foreach ($methods as $method) {
                        $colKey = "mat:{$material['id']}:{$method['type']}";
                        $label  = "M" . ($mIndex+1) . " ({$method['type']})";
                        $allFormatifCols[$semester][$colKey] = [
                            'label'=>$label, 'tooltip'=>$material['title'] ?? ''
                        ];
                        $score = $this->db->table('material_scores')
                            ->select('score')
                            ->where('material_id', $material['id'])
                            ->where('student_id', $studentId)
                            ->where('type', $method['type'])
                            ->get()->getRowArray();
                        $grades[$semester][$studentId]['formatif'][$colKey] = $score['score'] ?? null;
                    }
                }

                // sumatif
                $methods = $this->db->table('summative_scores')
                    ->distinct()->select('type')
                    ->where('subject_id', $subjectId)
                    ->where('year_id', $activeYear['id'])
                    ->where('semester', $semester)
                    ->get()->getResultArray();

                foreach ($methods as $method) {
                    $colKey = "sum:{$subjectId}:{$method['type']}";
                    $label  = "Sumatif ({$method['type']})";
                    $allSumatifCols[$semester][$colKey] = [
                        'label'=>$label, 'tooltip'=>"Mapel"
                    ];
                    $score = $this->db->table('summative_scores')
                        ->select('score')
                        ->where('subject_id', $subjectId)
                        ->where('student_id', $studentId)
                        ->where('year_id', $activeYear['id'])
                        ->where('semester', $semester)
                        ->where('type', $method['type'])
                        ->get()->getRowArray();
                    $grades[$semester][$studentId]['sumatif'][$colKey] = $score['score'] ?? null;
                }

                // final
                if ($semester == 2) {
                    $maxLevel = $this->db->table('classes')->selectMax('level')->get()->getRowArray()['level'];
                    $studentRecord = $this->db->table('student_records sr')
                        ->join('classes c','c.id=sr.class_id')
                        ->where('sr.student_id',$studentId)->orderBy('sr.id','DESC')
                        ->get()->getRowArray();
                    if ($studentRecord['level'] == $maxLevel) {
                        $hasFinal = true;
                        $score = $this->db->table('final_exam_scores')
                            ->select('score')
                            ->where('subject_id',$subjectId)
                            ->where('student_id',$studentId)
                            ->where('year_id',$activeYear['id'])
                            ->get()->getRowArray();
                        $grades[$semester][$studentId]['final'] = $score['score'] ?? null;
                    }
                }
            }
        }

        return view('admin/grades/view', [
            'students' => $students,
            'semesters'=> $semesters,
            'grades'   => $grades,
            'allFormatifCols'=>$allFormatifCols,
            'allSumatifCols' =>$allSumatifCols,
            'hasFinal' =>$hasFinal,
        ]);
    }

    // --- PDF ---
    public function pdf()
    {
        // mirip view(), tapi render ke view pdf
    }
}
