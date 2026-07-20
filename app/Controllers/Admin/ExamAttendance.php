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

        // Ambil daftar ujian dari tabel cbt_exam_names
        $examNameModel = new CbtExamNameModel();
        $exams = $examNameModel->select('id, name')->orderBy('name', 'ASC')->findAll();

        return view('admin/cbt/attendance/index', [
            'classes' => $classes,
            'exams' => $exams
        ]);
    }


    public function printByClass($examId, $classId)
    {
        $studentModel = new StudentModel();
        $examModel = new CbtExamNameModel();
        $academicModel = new AcademicYearModel();
        $schoolModel = new SchoolModel();
        $classModel = new ClassModel();

        // Data siswa per kelas (gunakan getByClass yang sudah diperbaiki)
        $students = $studentModel->getByClass($classId);

        // Data kelas
        $classData = $classModel->find($classId);
        $className = $classData['name'] ?? '-';

        // Data ujian
        $exam = $examModel->find($examId);
        $examName = $exam['name'] ?? 'UJIAN SEKOLAH';

        // Tahun akademik & sekolah
        $academicYear = $academicModel->where('is_active', 1)->first();
        $school = $schoolModel->first();

        $exam_date = date('Y-m-d');
        $subject = $exam['subject'] ?? '...................................................';

        return view('admin/cbt/attendance/print', [
            'students' => $students,
            'room' => $className, // Gunakan nama kelas sebagai pengganti ruang
            'class_id' => $classId,   // Tambahkan class_id untuk link PDF
            'examId' => $examId,    // Tambahkan examId untuk link PDF yang benar
            'examName' => $examName,
            'academicYear' => $academicYear['years'] ?? date('Y') . '/' . (date('Y') + 1),
            'subject' => $subject,
            'exam_date' => $exam_date,
            'school' => $school,
        ]);
    }

    /**
     * Cetak PDF daftar hadir berdasarkan ujian & kelas
     */
    public function printPdf($examParam, $classId)
    {
        // Tetap set memory limit agar aman, tapi optimasi gambar adalah kuncinya
        ini_set('memory_limit', '1024M');

        $studentModel = new StudentModel();
        $examModel = new CbtExamNameModel();
        $academicModel = new AcademicYearModel();
        $schoolModel = new SchoolModel();
        $classModel = new ClassModel();

        // Data siswa per kelas
        $students = $studentModel->getByClass($classId);

        // Data kelas
        $classData = $classModel->find($classId);
        $className = $classData['name'] ?? '-';

        // Data ujian
        $exam = $examModel
            ->where('id', $examParam)
            ->orWhere('name', urldecode($examParam))
            ->first();
        $examName = $exam['name'] ?? 'UJIAN SEKOLAH';

        // Tahun akademik & sekolah
        $academicYear = $academicModel->where('is_active', 1)->first();
        $school = $schoolModel->first();

        // Optimasi Logo
        $logoBase64 = null;
        if (!empty($school['logo'])) {
            $logoPath = UPLOAD_PATH . 'logo/' . $school['logo'];
            // Resize ke lebar 100px agar ringan
            $logoBase64 = $this->getResizedLogoBase64($logoPath, 100);
        }

        // Render view ke HTML
        $html = view('admin/cbt/attendance/pdf', [
            'students' => $students,
            'room' => $className, // Gunakan nama kelas
            'examName' => $examName,
            'academicYear' => $academicYear['years'] ?? date('Y') . '/' . (date('Y') + 1),
            'school' => $school,
            'logoBase64' => $logoBase64, // Kirim logo yang sudah di-resize
        ]);

        // Konfigurasi Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times-Roman');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Output ke browser
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream('Daftar_Hadir_' . $examName . '_' . $className . '.pdf', ["Attachment" => false]);
        exit();
    }

    /**
     * Helper untuk resize gambar dan ubah ke base64 agar hemat memori di Dompdf
     */
    private function getResizedLogoBase64($path, $targetWidth = 100)
    {
        if (!file_exists($path)) {
            return null;
        }

        $info = @getimagesize($path);
        if (!$info) {
            return null;
        }

        $mime = $info['mime'];
        $width = $info[0];
        $height = $info[1];

        // Hitung tinggi proporsional
        $ratio = $width / $height;
        $targetHeight = $targetWidth / $ratio;

        // Load image berdasarkan tipe
        switch ($mime) {
            case 'image/jpeg':
                $src = @imagecreatefromjpeg($path);
                break;
            case 'image/png':
                $src = @imagecreatefrompng($path);
                break;
            case 'image/gif':
                $src = @imagecreatefromgif($path);
                break;
            default:
                return null;
        }

        if (!$src)
            return null;

        // Buat canvas baru
        $dst = imagecreatetruecolor((int) $targetWidth, (int) $targetHeight);

        // Pertahankan transparansi (untuk PNG/GIF)
        if ($mime == 'image/png' || $mime == 'image/gif') {
            imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
        }

        // Resize
        imagecopyresampled($dst, $src, 0, 0, 0, 0, (int) $targetWidth, (int) $targetHeight, $width, $height);

        // Output ke buffer
        ob_start();
        if ($mime == 'image/jpeg') {
            imagejpeg($dst, null, 75); // Quality 75%
        } elseif ($mime == 'image/png') {
            imagepng($dst, null, 6); // Compression level 6
        } else {
            imagegif($dst);
        }
        $data = ob_get_clean();

        // Bersihkan memori GD
        imagedestroy($src);
        imagedestroy($dst);

        return 'data:' . $mime . ';base64,' . base64_encode($data);
    }
}
