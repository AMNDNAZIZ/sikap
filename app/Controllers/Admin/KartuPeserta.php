<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ExamScheduleModel;
use App\Models\CbtExamNameModel;
use App\Models\SchoolModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class KartuPeserta extends BaseController
{

    public function index()
    {
        $db = db_connect();
        $classes = $db->table('classes')->orderBy('name', 'ASC')->get()->getResultArray();
        $exams   = $db->table('cbt_exam_names')->orderBy('name', 'ASC')->get()->getResultArray();
    
        return view('admin/cbt/kartu_peserta_index', [
            'classes' => $classes,
            'exams'   => $exams
        ]);
    }
    
    public function cetakMassal($examId = null, $classId = null)
    {
        $studentModel  = new StudentModel();
        $scheduleModel = new ExamScheduleModel();
        $schoolModel   = new SchoolModel();
        $examModel     = new CbtExamNameModel();
    
        // Validasi parameter
        if (!$examId || !$classId) {
            return redirect()->back()->with('error', 'Nama ujian dan kelas harus dipilih.');
        }
    
        // Ambil data sekolah, siswa, jadwal, dan ujian
        $school    = $schoolModel->first();
        $students  = $studentModel->getByClass($classId);
        $schedules = $scheduleModel->getByClass($classId);
        $exam      = $examModel->find($examId);
    
        // Jika data tidak ditemukan
        if (empty($students)) {
            return redirect()->back()->with('error', 'Tidak ada siswa di kelas ini.');
        }
    
        $examName = $exam['name'] ?? 'UJIAN SEKOLAH';
    
        return view('admin/cbt/kartu_peserta_massal', [
            'school'    => $school,
            'students'  => $students,
            'schedules' => $schedules,
            'examName'  => $examName
        ]);
    }


    /**
     * ðŸ”¹ Cetak langsung ke PDF (pratinjau di browser)
     */
    public function cetakPdfMassal($examParam = null, $classId = null)
    {
        $studentModel   = new StudentModel();
        $scheduleModel  = new ExamScheduleModel();
        $schoolModel    = new SchoolModel();
        $examNameModel  = new CbtExamNameModel();
    
        // 🔎 Validasi parameter
        if (!$examParam || !$classId) {
            return redirect()->back()->with('error', 'Nama ujian dan kelas harus dipilih terlebih dahulu.');
        }
    
        // 🔎 Cari ujian berdasarkan ID atau nama (case-insensitive)
        $exam = $examNameModel
            ->where('id', $examParam)
            ->orWhere('LOWER(name)', strtolower(urldecode($examParam)))
            ->first();
    
        // Ambil data sekolah, siswa, dan jadwal
        $school    = $schoolModel->first();
        $students  = $studentModel->getByClass($classId);
        $schedules = $scheduleModel->getByClass($classId);
    
        // 🔎 Validasi data siswa
        if (empty($students)) {
            return redirect()->back()->with('error', 'Tidak ada siswa di kelas ini.');
        }
    
        // 🔎 Nama ujian fallback jika tidak ditemukan
        $examName = $exam['name'] ?? urldecode($examParam) ?? 'UJIAN SEKOLAH';
    
        // 🔧 Render view ke HTML
        $html = view('admin/cbt/kartu_peserta_massal_pdf', [
            'school'    => $school,
            'students'  => $students,
            'schedules' => $schedules,
            'examName'  => $examName
        ]);
    
        // 🧩 Konfigurasi Dompdf
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times New Roman');
    
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
    
        // 💾 Nama file aman
        $safeExamName = preg_replace('/[^a-zA-Z0-9_]/', '_', strtoupper($examName));
    
        // 📄 Tampilkan langsung di browser
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream("Kartu_Peserta_{$safeExamName}_Kelas_{$classId}.pdf", ["Attachment" => false]);
        exit;
    }

}
