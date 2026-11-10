<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class SnbpController extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Dashboard & Kalkulator Pemeringkatan Siswa Eligible SNBP (SMA Kelas 12)
     */
    public function index()
    {
        // 1. Dapatkan Profil Sekolah & Level
        $school = $this->db->table('school_profile')->get()->getRowArray() ?: [];
        $schoolLevel = (int)($school['level'] ?? 1);

        // 2. Tahun Ajaran Aktif
        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        // 3. Kuota Akreditasi (default A = 40%)
        $akreditasi = $this->request->getGet('akreditasi') ?? 'A';
        $kuotaPersen = match (strtoupper($akreditasi)) {
            'A' => 40,
            'B' => 25,
            'C' => 5,
            default => 40,
        };

        // 4. Cari kelas tingkat akhir SMA (Level 12)
        $classesLevel12 = $this->db->table('classes')
            ->where('level', 12)
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->get()->getResultArray();

        $classIds = array_column($classesLevel12, 'id');
        $studentsRanking = [];
        $totalSiswaTingkat12 = 0;
        $kuotaEligible = 0;

        if (!empty($classIds)) {
            // Ambil seluruh siswa aktif di kelas 12 pada tahun ajaran aktif
            $students = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.nis, s.nisn, s.gender, c.id as class_id, c.name as class_name')
                ->join('students s', 's.id = sr.student_id')
                ->join('classes c', 'c.id = sr.class_id')
                ->whereIn('sr.class_id', $classIds)
                ->where('sr.academic_year_id', $activeYearId)
                ->where('sr.status', 'aktif')
                ->orderBy('s.name', 'ASC')
                ->get()->getResultArray();

            $totalSiswaTingkat12 = count($students);
            $kuotaEligible = (int) ceil(($kuotaPersen / 100) * $totalSiswaTingkat12);

            // Hitung rata-rata nilai semester dari riwayat nilai (tabel grades)
            foreach ($students as $stu) {
                $sid = (int) $stu['id'];

                // Ambil rata-rata nilai rapor semester
                $gradeAvgRow = $this->db->table('grades')
                    ->select('AVG(COALESCE(erapor_score, final_score)) as avg_score, COUNT(id) as total_subjects')
                    ->where('student_id', $sid)
                    ->where('is_published', 1)
                    ->get()->getRowArray();

                $avgScore = $gradeAvgRow && $gradeAvgRow['avg_score'] !== null ? round((float)$gradeAvgRow['avg_score'], 2) : 0.00;
                $totalSubj = (int)($gradeAvgRow['total_subjects'] ?? 0);

                // Alternatif fallback jika belum ada grades terpublikasi, cek dari sumatif / pts
                if ($avgScore <= 0) {
                    $matAvgRow = $this->db->table('material_scores')
                        ->select('AVG(score) as avg_score')
                        ->where('student_id', $sid)
                        ->get()->getRowArray();
                    $avgScore = $matAvgRow && $matAvgRow['avg_score'] !== null ? round((float)$matAvgRow['avg_score'], 2) : 0.00;
                }

                // Ambil data aspirasi karir/kuliah jika ada
                $career = $this->db->table('bk_career_profiles')
                    ->where('student_id', $sid)
                    ->get()->getRowArray();

                $studentsRanking[] = [
                    'student_id'        => $sid,
                    'name'              => $stu['name'],
                    'nis'               => $stu['nis'],
                    'nisn'              => $stu['nisn'],
                    'gender'            => $stu['gender'],
                    'class_name'        => $stu['class_name'],
                    'avg_score'         => $avgScore,
                    'total_subjects'    => $totalSubj,
                    'target_ptn'        => $career['university_target'] ?? '-',
                    'major_interest'    => $career['major_interest'] ?? '-',
                    'post_graduate_plan'=> $career['post_graduate_plan'] ?? '-',
                ];
            }

            // Urutkan ranking berdasarkan nilai rata-rata tertinggi (DESC)
            usort($studentsRanking, function ($a, $b) {
                if ($a['avg_score'] == $b['avg_score']) {
                    return strcmp($a['name'], $b['name']);
                }
                return ($a['avg_score'] > $b['avg_score']) ? -1 : 1;
            });

            // Beri label ranking dan status eligible
            $rank = 1;
            foreach ($studentsRanking as &$sRank) {
                $sRank['rank'] = $rank;
                $sRank['is_eligible'] = ($rank <= $kuotaEligible && $sRank['avg_score'] > 0);
                $rank++;
            }
            unset($sRank);
        }

        $data = [
            'title'               => 'Pemeringkatan Siswa Eligible SNBP',
            'school'              => $school,
            'schoolLevel'         => $schoolLevel,
            'activeYear'          => $activeYear,
            'akreditasi'          => $akreditasi,
            'kuotaPersen'         => $kuotaPersen,
            'totalSiswaTingkat12' => $totalSiswaTingkat12,
            'kuotaEligible'       => $kuotaEligible,
            'studentsRanking'     => $studentsRanking,
        ];

        return view('admin/grades/snbp_ranking', $data);
    }

    /**
     * Ekspor Data Siswa Eligible SNBP dalam format Excel Siap PDSS / SNPMB
     */
    public function exportExcel()
    {
        $akreditasi = $this->request->getGet('akreditasi') ?? 'A';
        $kuotaPersen = match (strtoupper($akreditasi)) {
            'A' => 40,
            'B' => 25,
            'C' => 5,
            default => 40,
        };

        $academicYearModel = new \App\Models\AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear['id'] ?? 0;

        $classesLevel12 = $this->db->table('classes')
            ->where('level', 12)
            ->where('is_active', 1)
            ->get()->getResultArray();
        $classIds = array_column($classesLevel12, 'id');

        $studentsRanking = [];
        if (!empty($classIds)) {
            $students = $this->db->table('student_records sr')
                ->select('s.id, s.name, s.nis, s.nisn, s.gender, c.name as class_name')
                ->join('students s', 's.id = sr.student_id')
                ->join('classes c', 'c.id = sr.class_id')
                ->whereIn('sr.class_id', $classIds)
                ->where('sr.academic_year_id', $activeYearId)
                ->where('sr.status', 'aktif')
                ->get()->getResultArray();

            $kuotaEligible = (int) ceil(($kuotaPersen / 100) * count($students));

            foreach ($students as $stu) {
                $sid = (int) $stu['id'];
                $gradeAvgRow = $this->db->table('grades')
                    ->select('AVG(COALESCE(erapor_score, final_score)) as avg_score')
                    ->where('student_id', $sid)
                    ->where('is_published', 1)
                    ->get()->getRowArray();

                $avgScore = $gradeAvgRow && $gradeAvgRow['avg_score'] !== null ? round((float)$gradeAvgRow['avg_score'], 2) : 0.00;

                $studentsRanking[] = [
                    'name'       => $stu['name'],
                    'nis'        => $stu['nis'],
                    'nisn'       => $stu['nisn'],
                    'gender'     => $stu['gender'] === 'L' ? 'Laki-laki' : 'Perempuan',
                    'class_name' => $stu['class_name'],
                    'avg_score'  => $avgScore,
                ];
            }

            usort($studentsRanking, function ($a, $b) {
                return ($a['avg_score'] > $b['avg_score']) ? -1 : 1;
            });

            $rank = 1;
            foreach ($studentsRanking as &$sRank) {
                $sRank['rank'] = $rank;
                $sRank['status'] = ($rank <= $kuotaEligible && $sRank['avg_score'] > 0) ? 'ELIGIBLE' : 'TIDAK ELIGIBLE';
                $rank++;
            }
            unset($sRank);
        }

        // Generate Spreadsheet
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('SNBP Eligible');

        // Header Dokumen
        $sheet->setCellValue('A1', 'DAFTAR PEMERINGKATAN SISWA ELIGIBLE SNBP');
        $sheet->setCellValue('A2', 'Tahun Ajaran: ' . ($activeYear['year'] ?? '-') . ' | Akreditasi: ' . $akreditasi . ' (Kuota ' . $kuotaPersen . '%)');
        $sheet->getStyle('A1:A2')->getFont()->setBold(true);

        // Header Kolom
        $headers = ['Peringkat', 'NISN', 'NIS', 'Nama Siswa', 'L/P', 'Rombel', 'Rata-rata Nilai', 'Status SNBP'];
        $colLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];
        foreach ($headers as $idx => $hdr) {
            $sheet->setCellValue($colLetters[$idx] . '4', $hdr);
        }

        $sheet->getStyle('A4:H4')->getFont()->setBold(true);
        $sheet->getStyle('A4:H4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle('A4:H4')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');

        $rowNum = 5;
        foreach ($studentsRanking as $row) {
            $sheet->setCellValue('A' . $rowNum, $row['rank']);
            $sheet->setCellValueExplicit('B' . $rowNum, $row['nisn'] ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C' . $rowNum, $row['nis'] ?: '-', \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue('D' . $rowNum, $row['name']);
            $sheet->setCellValue('E' . $rowNum, $row['gender']);
            $sheet->setCellValue('F' . $rowNum, $row['class_name']);
            $sheet->setCellValue('G' . $rowNum, number_format($row['avg_score'], 2));
            $sheet->setCellValue('H' . $rowNum, $row['status']);

            if ($row['status'] === 'ELIGIBLE') {
                $sheet->getStyle('H' . $rowNum)->getFont()->setBold(true)->getColor()->setARGB('FF15803D');
            }

            $rowNum++;
        }

        // Auto width
        foreach ($colLetters as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Pemeringkatan_SNBP_' . date('Ymd_His') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }
}

