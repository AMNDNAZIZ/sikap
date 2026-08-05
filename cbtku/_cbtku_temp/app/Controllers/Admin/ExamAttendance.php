<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\CbtExamNameModel;
use App\Models\CbtTestStatusModel;
use App\Models\SchoolModel;
use App\Models\AcademicYearModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExamAttendance extends BaseController
{
    public function index()
    {
        $classModel = new ClassModel();
        $classes = $classModel->orderBy('name', 'ASC')->findAll();
    
        $studentModel = new StudentModel();
        $rooms = $studentModel
            ->select('room')
            ->distinct()
            ->orderBy('room', 'ASC')
            ->findAll();
    
        // Ambil daftar ujian dari tabel cbt_exam_names
        $examNameModel = new CbtExamNameModel();
        $exams = $examNameModel->select('id, name')->orderBy('name', 'ASC')->findAll();
    
        return view('admin/cbt/attendance/index', [
            'classes' => $classes,
            'rooms'   => $rooms,
            'exams'   => $exams
        ]);
    }

    
    public function printByRoom($examId, $room)
    {
        $studentModel  = new StudentModel();
        $examModel     = new CbtExamNameModel();
        $academicModel = new AcademicYearModel();
        $schoolModel   = new SchoolModel();

        // Data siswa per ruang
        $students = $studentModel
            ->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.room', urldecode($room))
            ->orderBy('students.name', 'ASC')
            ->findAll();

        // Data ujian
        $exam = $examModel->find($examId);
        $examName = $exam['name'] ?? 'UJIAN SEKOLAH';

        // Tahun akademik & sekolah
        $academicYear = $academicModel->where('is_active', 1)->first();
        $school       = $schoolModel->first();

        $exam_date = date('Y-m-d');
        $subject   = $exam['subject'] ?? '...................................................';

        return view('admin/cbt/attendance/print', [
            'students'     => $students,
            'room'         => urldecode($room),
            'examName'     => $examName,
            'academicYear' => $academicYear['years'] ?? '2025/2026',
            'subject'      => $subject,
            'exam_date'    => $exam_date,
            'school'       => $school,
        ]);
    }

    /**
     * Cetak PDF daftar hadir berdasarkan ujian & ruang
     */
    public function printPdf($examParam, $room)
    {
        $studentModel  = new StudentModel();
        $examModel     = new CbtExamNameModel();
        $academicModel = new AcademicYearModel();
        $schoolModel   = new SchoolModel();
  
        // Data siswa per ruang
        $students = $studentModel
            ->select('students.*, classes.name AS class_name')
            ->join('classes', 'classes.id = students.class_id', 'left')
            ->where('students.room', urldecode($room))
            ->orderBy('students.name', 'ASC')
            ->findAll();

        // Data ujian
        $exam = $examModel
        ->where('id', $examParam)
        ->orWhere('name', urldecode($examParam))
        ->first();
        $examName = $exam['name'] ?? 'UJIAN SEKOLAH';

        // Tahun akademik & sekolah
        $academicYear = $academicModel->where('is_active', 1)->first();
        $school       = $schoolModel->first();

        // Render view ke HTML
        $html = view('admin/cbt/attendance/pdf', [
            'students'     => $students,
            'room'         => urldecode($room),
            'examName'     => $examName,
            'academicYear' => $academicYear['years'] ?? '2025/2026',
            'school'       => $school,
        ]);

        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output ke browser
        $dompdf->stream('Daftar_Hadir_' . $examName . '_' . $room . '.pdf', ["Attachment" => false]);
        exit();
    }
}
