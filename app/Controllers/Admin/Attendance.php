<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\{AttendanceModel, StudentModel, ClassModel, HolidayModel, AcademicYearModel};
use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class Attendance extends BaseController
{
    protected $attendanceModel;
    protected $studentModel;
    protected $classModel;
    protected $holidayModel;
    protected $yearModel;

    public function __construct()
    {
        $this->attendanceModel = new AttendanceModel();
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->holidayModel = new HolidayModel();
        $this->yearModel = new AcademicYearModel();
    }

    public function index()
    {
        log_message('error', '--- ATTENDANCE INDEX START ---');
        $session = session();
        $user = $session->get('user');
        $roleId = $user['role_id'] ?? null;

        if ($roleId == 1 || $roleId == 2) { // Admin atau Kepala Sekolah
            $classes = $this->classModel->orderBy('name', 'ASC')->findAll();
            return view('admin/attendance/select_class', [
                'title' => 'Absensi Siswa',
                'classes' => $classes,
                'isAdmin' => true,
            ]);
        }

        if ($roleId == 3) { // Guru
            $teacherId = $user['related_id'] ?? null;
            $db = \Config\Database::connect();

            // Ambil tahun ajaran aktif
            $activeYear = $this->yearModel->getActiveYear() ?: [];
            $activeYearId = $activeYear['id'] ?? 0;

            // Get classes where teacher is Wali Kelas
            $waliClasses = $this->classModel->where('teacher_id', $teacherId)->findAll();

            // Get classes where teacher is Guru Mapel (tahun ajaran aktif)
            $mapelClasses = $db->table('teaching_assignments ta')
                ->select('c.id, c.name')
                ->join('classes c', 'c.id = ta.class_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYearId)
                ->groupBy('c.id')
                ->get()
                ->getResultArray();

            // Merge and remove duplicates
            $classesMap = [];
            foreach ($waliClasses as $c)
                $classesMap[$c['id']] = $c;
            foreach ($mapelClasses as $c)
                $classesMap[$c['id']] = $c;

            $classes = array_values($classesMap);

            if (empty($classes)) {
                return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses absensi untuk kelas mana pun.');
            }

            return view('admin/attendance/select_class', [
                'title' => 'Absensi Siswa',
                'classes' => $classes,
                'isAdmin' => false,
            ]);
        }

        return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
    }


    /**
     * Bulanan (grid)
     */
    public function view()
    {
        $classId = $this->request->getGet('class_id');
        $jenis = $this->request->getGet('jenis_laporan') ?? 'bulan';
        $month = $this->request->getGet('month') ?? date('Y-m');
        $week = $this->request->getGet('week');
        $today = date('Y-m-d');

        if (!$classId) {
            return redirect()->to('/admin/attendance')->with('error', 'Pilih kelas terlebih dahulu.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak ke kelas ini.');
        }

        // Data kelas dan siswa
        $class = $this->classModel->find($classId);
        $students = $this->getStudentsByClass((int)$classId);

        // Tahun ajaran aktif
        $activeYear = $this->yearModel->getActiveYear() ?: [];
        $yearStart = date('Y-m-d', strtotime($activeYear['start_date']));
        $yearEnd = date('Y-m-d', strtotime($activeYear['end_date']));

        // =========================
        // Tentukan rentang tanggal
        // =========================
        if ($jenis === 'bulan') {
            $monthStart = date('Y-m-01', strtotime($month));
            $monthEnd   = date('Y-m-t',  strtotime($month));

            // Bulan yang sama sekali di luar tahun ajaran → tolak
            // (monthEnd < yearStart) atau (monthStart > yearEnd)
            if ($monthEnd < $yearStart || $monthStart > $yearEnd) {
                return redirect()->to("/admin/attendance?class_id={$classId}")
                    ->with('error', 'Bulan yang dipilih di luar tahun ajaran aktif ('
                        . date('d M Y', strtotime($yearStart)) . ' - '
                        . date('d M Y', strtotime($yearEnd)) . ').');
            }

            $start = strtotime($monthStart);
            $end   = strtotime($monthEnd);

        } elseif ($jenis === 'minggu') {
            if (!$week) {
                $week = date('o-\WW');
            }

            [$yearWeek, $weekNumber] = explode('-W', $week);
            $dto = new \DateTime();
            $dto->setISODate((int) $yearWeek, (int) $weekNumber);
            $weekStart = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $weekEnd = $dto->format('Y-m-d');

            if ($weekEnd < $yearStart || $weekStart > $yearEnd) {
                return redirect()->to("/admin/attendance?class_id={$classId}")
                    ->with('error', 'Minggu yang dipilih di luar tahun ajaran aktif ('
                        . date('d M Y', strtotime($yearStart)) . ' - '
                        . date('d M Y', strtotime($yearEnd)) . ').');
            }

            $start = strtotime($weekStart);
            $end = strtotime($weekEnd);

        } elseif ($jenis === 'semester1') {
            $start = strtotime(date('Y', strtotime($yearStart)) . '-07-01');
            $end = strtotime(date('Y', strtotime($yearStart)) . '-12-31');
        } elseif ($jenis === 'semester2') {
            $start = strtotime((date('Y', strtotime($yearStart)) + 1) . '-01-01');
            $end = strtotime((date('Y', strtotime($yearStart)) + 1) . '-06-30');
        } else { // tahunan
            $start = strtotime($yearStart);
            $end = strtotime($yearEnd);
        }

        // =========================
        // Buat daftar tanggal
        // =========================
        $dates = [];
        for ($d = $start; $d <= $end; $d = strtotime('+1 day', $d)) {
            $dates[] = date('Y-m-d', $d);
        }

        // Ambil absensi dari DB (H tidak disimpan, hanya S/I/A)
        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', date('Y-m-d', $start))
            ->where('date <=', date('Y-m-d', $end))
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        // Ambil hari libur custom
        $holidays = [];
        $customHolidays = $this->holidayModel
            ->where('date >=', date('Y-m-d', $start))
            ->where('date <=', date('Y-m-d', $end))
            ->findAll();
        foreach ($customHolidays as $h) {
            $holidays[$h['date']] = $h['description'];
        }

        // Tandai tanggal di luar periode tahun ajaran sebagai "libur periode"
        // Berlaku untuk bulan awal (sebelum start_date) dan bulan akhir (setelah end_date)
        foreach ($dates as $d) {
            if ($d < $yearStart || $d > $yearEnd) {
                $holidays[$d] = 'Di luar periode tahun ajaran';
            }
        }

        // Ambil pengaturan hari pembelajaran (5 atau 6 hari)
        $schoolDays = $activeYear['school_days'] ?? 5;

        // =========================
        // Hitung rekap kelas
        // =========================
        $totalH = $totalI = $totalS = $totalA = 0;
        foreach ($students as $s) {
            foreach ($dates as $d) {
                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);
                $isHoliday = $isWeekend || isset($holidays[$d]);

                if ($isHoliday || $d > $today)
                    continue;

                $val = $attMap[$s['id']][$d] ?? null;
                if ($val === 'I')
                    $totalI++;
                elseif ($val === 'S')
                    $totalS++;
                elseif ($val === 'A')
                    $totalA++;
                else
                    $totalH++; // default hadir
            }
        }
        $totalAll = $totalH + $totalI + $totalS + $totalA;
        $percentClass = $totalAll ? round(($totalH / $totalAll) * 100, 1) : 0;

        // =========================
        // Hitung rekap per siswa
        // =========================
        $rekapSiswa = [];
        foreach ($students as $s) {
            $countH = $countI = $countS = $countA = 0;
            foreach ($dates as $d) {
                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);
                $isHoliday = $isWeekend || isset($holidays[$d]);

                if ($isHoliday || $d > $today)
                    continue;

                $val = $attMap[$s['id']][$d] ?? null;
                if ($val === 'I')
                    $countI++;
                elseif ($val === 'S')
                    $countS++;
                elseif ($val === 'A')
                    $countA++;
                else
                    $countH++; // default hadir
            }
            $totalHari = $countH + $countI + $countS + $countA;
            $percent = $totalHari ? round(($countH / $totalHari) * 100, 1) : 0;

            $rekapSiswa[$s['id']] = [
                'student' => $s,
                'H' => $countH,
                'I' => $countI,
                'S' => $countS,
                'A' => $countA,
                'percent' => $percent
            ];
        }

        // =========================
        // Hitung Statistik Siswa (L/P)
        // =========================
        $stats = ['total' => count($students), 'L' => 0, 'P' => 0];
        foreach ($students as $s) {
            if ($s['gender'] == 'L')
                $stats['L']++;
            elseif ($s['gender'] == 'P')
                $stats['P']++;
        }

        // =========================
        // Tentukan view
        // =========================
        if ($jenis === 'bulan') {
            return view('admin/attendance/month_grid', [
                'title' => 'Absensi Bulanan',
                'class' => $class,
                'students' => $students,
                'dates' => $dates,
                'attMap' => $attMap,
                'holidays' => $holidays,
                'month' => $month,
                'rekap' => [
                    'H' => $totalH,
                    'I' => $totalI,
                    'S' => $totalS,
                    'A' => $totalA,
                    'percent' => $percentClass
                ],
                'activeYear' => $activeYear,
                'studentStats' => $stats,
                'schoolDays' => $schoolDays,
            ]);
        } elseif ($jenis === 'minggu') {
            return view('admin/attendance/week_grid', [
                'title' => 'Absensi Mingguan',
                'class' => $class,
                'students' => $students,
                'dates' => $dates,
                'attMap' => $attMap,
                'holidays' => $holidays,
                'week' => $week,
                'rekap' => [
                    'H' => $totalH,
                    'I' => $totalI,
                    'S' => $totalS,
                    'A' => $totalA,
                    'percent' => $percentClass
                ],
                'activeYear' => $activeYear,
                'schoolDays' => $schoolDays,
            ]);
        } else {
            return view('admin/attendance/rekap_view', [
                'title' => 'Rekapitulasi Absensi',
                'class' => $class,
                'students' => $students,
                'dates' => $dates,
                'attMap' => $attMap,
                'holidays' => $holidays,
                'jenis' => $jenis,
                'periode' => $jenis,
                'activeYear' => $activeYear,
                'rekap' => [
                    'H' => $totalH,
                    'I' => $totalI,
                    'S' => $totalS,
                    'A' => $totalA,
                    'percent' => $percentClass
                ],
                'rekapSiswa' => $rekapSiswa
            ]);
        }
    }



    /**
     * Simpan absensi per tanggal (hemat storage):
     * - jika status = 'H' -> hapus record (anggap default hadir)
     * - jika status != 'H' -> insert / update
     */
    public function save()
    {
        $classId = $this->request->getPost('class_id');
        $date = $this->request->getPost('date');
        $status = $this->request->getPost('status'); // [student_id => status]

        if (!$classId || !$date || $status === null) {
            return redirect()->back()->with('error', 'Data tidak lengkap.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        foreach ($status as $studentId => $st) {
            // jika H -> hapus record (karena default hadir)
            if ($st === 'H') {
                $this->attendanceModel
                    ->where('class_id', $classId)
                    ->where('student_id', $studentId)
                    ->where('date', $date)
                    ->delete();
                continue;
            }

            $existing = $this->attendanceModel
                ->where('class_id', $classId)
                ->where('student_id', $studentId)
                ->where('date', $date)
                ->first();

            if ($existing) {
                $this->attendanceModel->update($existing['id'], ['status' => $st]);
            } else {
                $this->attendanceModel->insert([
                    'class_id' => $classId,
                    'student_id' => $studentId,
                    'date' => $date,
                    'status' => $st,
                ]);
            }
        }

        return redirect()->back()->with('success', 'Absensi berhasil disimpan.');
    }


    /**
     * Tampilkan absensi mingguan
     */
    public function week()
    {
        $classId = $this->request->getGet('class_id');
        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }
        $weekStr = $this->request->getGet('week') ?? date('Y-\WW');

        if (!preg_match('/^(\d{4})-W(\d{2})$/', $weekStr, $m)) {
            $weekStr = date('Y-\WW');
            preg_match('/^(\d{4})-W(\d{2})$/', $weekStr, $m);
        }
        $year = (int) $m[1];
        $week = (int) $m[2];

        $dto = new \DateTime();
        $dto->setISODate($year, $week);
        $weekDates = [];
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $dto->format('Y-m-d');
            $dto->modify('+1 day');
        }

        $start = $weekDates[0];
        $end   = $weekDates[6];

        $class = $this->classModel->find($classId);

        // Ambil activeYear DULU sebelum dipakai di query siswa
        $activeYear = $this->yearModel->getActiveYear() ?: [];
        $schoolDays = $activeYear['school_days'] ?? 5;

        $students = $this->getStudentsByClass((int) $classId, $activeYear);

        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        $holidays = [];
        $holidayRows = $this->holidayModel
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();
        foreach ($holidayRows as $h) {
            $holidays[$h['date']] = $h['description'] ?? 'Libur';
        }

        // Ambil pengaturan hari pembelajaran (5 atau 6 hari) — sudah diambil di atas

        // Tandai tanggal di luar periode tahun ajaran sebagai "libur periode"
        $yearStart = date('Y-m-d', strtotime($activeYear['start_date']));
        $yearEnd   = date('Y-m-d', strtotime($activeYear['end_date']));
        foreach ($weekDates as $d) {
            if ($d < $yearStart || $d > $yearEnd) {
                $holidays[$d] = 'Di luar periode tahun ajaran';
            }
        }

        return view('admin/attendance/week_grid', [
            'title' => 'Absensi Mingguan',
            'class' => $class,
            'students' => $students,
            'weekDates' => $weekDates,
            'attMap' => $attMap,
            'holidays' => $holidays,
            'week' => $weekStr,
            'schoolDays' => $schoolDays,
        ]);
    }


    /**
     * Cetak PDF (menggunakan view admin/attendance/pdf_grid yang sudah menampilkan '-' jika tidak ada data)
     */
    public function pdf()
    {
        $classId = $this->request->getGet('class_id');
        $month = $this->request->getGet('month') ?? date('Y-m');

        if (!$classId) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $class = $this->classModel->find($classId);
        if (!$class) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        // Ambil activeYear DULU sebelum dipakai query siswa
        $activeYear = $this->yearModel->getActiveYear() ?: [];

        // siswa via join student_records (jangan pakai where('class_id', ...) di students)
        $students = $this->getStudentsByClass((int) $classId, $activeYear);

        // daftar tanggal bulan
        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $dates = [];
        for ($d = strtotime($start); $d <= strtotime($end); $d = strtotime('+1 day', $d)) {
            $dates[] = date('Y-m-d', $d);
        }

        // ambil absensi (hanya yang tersimpan: biasanya I/S/A)
        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        // libur
        $holidayRows = $this->holidayModel
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();
        $holidays = [];
        foreach ($holidayRows as $h) {
            $holidays[$h['date']] = $h['description'] ?? '';
        }

        // Ambil pengaturan hari pembelajaran ($activeYear sudah diambil di atas)
        $schoolDays = $activeYear['school_days'] ?? 5;

        // Tandai tanggal di luar periode tahun ajaran sebagai "libur periode"
        $yearStart = date('Y-m-d', strtotime($activeYear['start_date']));
        $yearEnd   = date('Y-m-d', strtotime($activeYear['end_date']));
        foreach ($dates as $d) {
            if ($d < $yearStart || $d > $yearEnd) {
                $holidays[$d] = 'Di luar periode tahun ajaran';
            }
        }

        $data = [
            'class' => $class,
            'month' => $month,
            'students' => $students,
            'dates' => $dates,
            'attMap' => $attMap,
            'holidays' => $holidays,
            'schoolDays' => $schoolDays,
        ];

        $html = view('admin/attendance/pdf_grid', $data);

        // bersihkan output buffer agar PDF tidak corrupt
        if (ob_get_length()) {
            @ob_end_clean();
        }

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        // orientasi landscape agar tabel bulanan muat A4
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        $filename = 'Absensi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $class['name']) . "_{$month}.pdf";
        $dompdf->stream($filename, ['Attachment' => false]);
        exit;
    }

    /**
     * Export Excel (bulanan)
     */
    public function excel()
    {
        $classId = $this->request->getGet('class_id');
        $month = $this->request->getGet('month') ?? date('Y-m');

        if (!$classId) {
            return redirect()->back()->with('error', 'Pilih kelas terlebih dahulu.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        $class = $this->classModel->find($classId);
        if (!$class) {
            return redirect()->back()->with('error', 'Kelas tidak ditemukan.');
        }

        // Ambil activeYear DULU sebelum dipakai query siswa
        $activeYear = $this->yearModel->getActiveYear() ?: [];

        $students = $this->getStudentsByClass((int) $classId, $activeYear);

        $start = $month . '-01';
        $end = date('Y-m-t', strtotime($start));
        $dates = [];
        for ($d = strtotime($start); $d <= strtotime($end); $d = strtotime('+1 day', $d)) {
            $dates[] = date('Y-m-d', $d);
        }

        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        // holidays
        $holidayRows = $this->holidayModel
            ->where('date >=', $start)
            ->where('date <=', $end)
            ->findAll();
        $holidaySet = [];
        foreach ($holidayRows as $h)
            $holidaySet[$h['date']] = true;

        // Ambil pengaturan hari pembelajaran ($activeYear sudah diambil di atas)
        $schoolDays = $activeYear['school_days'] ?? 5;

        // Tandai tanggal di luar periode tahun ajaran sebagai "libur periode"
        $yearStart = date('Y-m-d', strtotime($activeYear['start_date']));
        $yearEnd   = date('Y-m-d', strtotime($activeYear['end_date']));
        foreach ($dates as $d) {
            if ($d < $yearStart || $d > $yearEnd) {
                $holidaySet[$d] = true;
            }
        }

        // buat excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // header
        $sheet->setCellValue('A1', 'No');
        $sheet->setCellValue('B1', 'NIS');
        $sheet->setCellValue('C1', 'Nama');
        $sheet->setCellValue('D1', 'JK');

        $firstDataCol = 5; // E
        foreach ($dates as $i => $d) {
            $colIndex = $firstDataCol + $i;
            $colLetter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue($colLetter . '1', date('d', strtotime($d)));
        }

        // rekap cols
        $lastDateColIndex = $firstDataCol + count($dates) - 1;
        $rekapCols = ['H', 'I', 'S', 'A', '%'];
        foreach ($rekapCols as $j => $label) {
            $colLetter = Coordinate::stringFromColumnIndex($lastDateColIndex + 1 + $j);
            $sheet->setCellValue($colLetter . '1', $label);
        }

        $row = 2;
        foreach ($students as $index => $s) {
            $sheet->setCellValue('A' . $row, $index + 1);
            $sheet->setCellValue('B' . $row, $s['nis'] ?? '-');
            $sheet->setCellValue('C' . $row, $s['name']);
            $sheet->setCellValue('D' . $row, $s['gender']);

            $H = $I = $S = $A = 0;

            foreach ($dates as $i => $d) {
                $colIndex = $firstDataCol + $i;
                $colLetter = Coordinate::stringFromColumnIndex($colIndex);
                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);
                $isHoliday = $isWeekend || !empty($holidaySet[$d]);
                $val = $attMap[$s['id']][$d] ?? null;

                if ($isHoliday) {
                    $display = '-';
                } elseif ($d <= date('Y-m-d')) {
                    $display = $val ?? 'H';
                } else {
                    $display = '-';
                }

                $sheet->setCellValue($colLetter . $row, $display);

                if ($display === 'H')
                    $H++;
                elseif ($display === 'I')
                    $I++;
                elseif ($display === 'S')
                    $S++;
                elseif ($display === 'A')
                    $A++;
            }

            $workDays = 0;
            foreach ($dates as $d) {
                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);
                $isHoliday = $isWeekend || !empty($holidaySet[$d]);
                if ($d <= date('Y-m-d') && !$isHoliday)
                    $workDays++;
            }

            $percent = $workDays ? round(($H / $workDays) * 100, 1) : 0;

            $sheet->setCellValue(Coordinate::stringFromColumnIndex($lastDateColIndex + 1) . $row, $H);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($lastDateColIndex + 2) . $row, $I);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($lastDateColIndex + 3) . $row, $S);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($lastDateColIndex + 4) . $row, $A);
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($lastDateColIndex + 5) . $row, $percent . '%');

            $row++;
        }

        // kirim file
        $filename = 'Absensi_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $class['name']) . "_{$month}.xlsx";
        if (ob_get_length())
            @ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    public function rekap()
    {
        $session = session();
        $user = $session->get('user');
        $role = $user['role_id'] ?? null;

        $classId = $this->request->getGet('class_id');
        $periode = $this->request->getGet('periode');
        $activeYear = $this->yearModel->getActiveYear() ?: [];

        // =================================
        // VALIDASI WAJIB (sebelum cek akses)
        // =================================
        if (!$classId || !$periode) {
            return redirect()->to('/admin/attendance')->with('error', 'Pilih kelas dan periode rekap.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        // Dapatkan detail kelas
        $class = $this->classModel->find($classId);
        if (!$class) {
            return redirect()->to('/admin/attendance')->with('error', 'Kelas tidak ditemukan.');
        }

        // =================================
        // AMBIL SISWA DI KELAS
        // =================================
        $students = $this->studentModel
            ->select('students.*')
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
            ->where('sr.status', 'aktif')
            ->findAll();

        // =================================
        // TENTUKAN RANGE TANGGAL
        // =================================
        [$startDate, $endDate] = $this->getRekapDateRange($activeYear, $periode);
        $today = date('Y-m-d');

        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );

        // daftar tanggal
        $dates = [];
        foreach ($period as $d) {
            $dates[] = $d->format('Y-m-d');
        }

        // ambil data absensi dalam range
        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        // ambil hari libur
        $holidayList = $this->holidayModel
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        $holidays = [];
        foreach ($holidayList as $h) {
            $holidays[$h['date']] = true;
        }

        // Ambil pengaturan hari pembelajaran
        $schoolDays = $activeYear['school_days'] ?? 5;

        // =================================
        // HITUNG REKAP
        // =================================
        $rekapSiswa = [];

        foreach ($students as $s) {
            $H = $I = $S = $A = 0;

            foreach ($dates as $d) {
                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);

                // skip weekend, hari libur, atau tanggal yang belum terjadi
                if ($isWeekend || isset($holidays[$d]) || $d > $today)
                    continue;

                // default kehadiran = 'H'
                $status = $attMap[$s['id']][$d] ?? 'H';

                if ($status === 'H')
                    $H++;
                elseif ($status === 'I')
                    $I++;
                elseif ($status === 'S')
                    $S++;
                elseif ($status === 'A')
                    $A++;
            }

            $total = $H + $I + $S + $A;
            $percent = $total > 0 ? round(($H / $total) * 100, 1) : 0;

            $rekapSiswa[] = [
                'student' => $s,
                'H' => $H,
                'I' => $I,
                'S' => $S,
                'A' => $A,
                'percent' => $percent
            ];
        }

        // =================================
        // TAMPILKAN VIEW
        // =================================
        return view('admin/attendance/rekap_view', [
            'title' => 'Rekap Absensi',
            'class' => $class,
            'rekapSiswa' => $rekapSiswa,
            'periode' => $periode,
            'activeYear' => $activeYear
        ]);
    }

    public function rekapPdf()
    {
        $session = session();
        $user = $session->get('user') ?? [];
        $role = $user['role_id'] ?? null;
        $periode = $this->request->getGet('periode');
        $classId = $this->request->getGet('class_id');

        // =========================
        // 4. Validasi parameter
        // =========================
        if (!$classId || !$periode) {
            return redirect()->back()->with('error', 'Parameter tidak lengkap.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        // =========================
        // 5. Ambil data kelas dan rekap
        // =========================
        $class = $this->classModel->find($classId);
        $rekap = $this->generateRekap((int) $classId, $periode);

        $html = view('admin/attendance/rekap_pdf_template', [
            'class' => $class,
            'activeYear' => $rekap['activeYear'],
            'periode' => $periode,
            'rekapSiswa' => $rekap['rekapSiswa'],
        ]);

        // =========================
        // 6. Generate PDF
        // =========================
        if (ob_get_length())
            @ob_end_clean();

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        $dompdf->stream("Rekap_{$class['name']}_{$periode}.pdf", ['Attachment' => true]);
        exit;
    }

    public function rekapExcel()
    {
        $session = session();
        $user = $session->get('user') ?? [];
        $role = $user['role_id'] ?? null;
        $periode = $this->request->getGet('periode');
        $classId = $this->request->getGet('class_id');

        // =========================
        // 4. Validasi
        // =========================
        if (!$classId || !$periode) {
            return redirect()->back()->with('error', 'Parameter tidak lengkap.');
        }

        if (!$this->canAccessAttendance($classId)) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak.');
        }

        // =========================
        // 5. Ambil data rekap
        // =========================
        $class = $this->classModel->find($classId);
        $rekap = $this->generateRekap((int) $classId, $periode);
        $rekapSiswa = $rekap['rekapSiswa'];

        // =========================
        // 6. Generate Excel
        // =========================
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['No', 'NIS', 'Nama', 'JK', 'H', 'I', 'S', 'A', '%'], null, 'A1');

        $row = 2;
        foreach ($rekapSiswa as $i => $r) {
            $s = $r['student'];
            $sheet->fromArray([
                $i + 1,
                $s['nis'] ?? '-',
                $s['name'] ?? '-',
                $s['gender'] ?? '-',
                $r['H'],
                $r['I'],
                $r['S'],
                $r['A'],
                $r['percent'] . '%'
            ], null, "A{$row}");
            $row++;
        }

        $filename = "Rekap_{$class['name']}_{$periode}.xlsx";

        if (ob_get_length())
            @ob_end_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header("Content-Disposition: attachment; filename=\"{$filename}\"");
        header('Cache-Control: max-age=0');

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }


    /**
     * Helper periode (dipakai rekap)
     */
    private function getPeriodeRange(array $activeYear, string $periode): array
    {
        $yearStart = date('Y-m-d', strtotime($activeYear['start_date']));
        $yearEnd = date('Y-m-d', strtotime($activeYear['end_date']));

        $startYear = date('Y', strtotime($yearStart));
        $endYear = date('Y', strtotime($yearEnd));

        switch ($periode) {

            case 'semester1':
                // HARUS identik dengan fungsi view()
                $start = "{$startYear}-07-01";
                $end = "{$startYear}-12-31";
                break;

            case 'semester2':
                // identik dengan fungsi view()
                $start = ($startYear + 1) . "-01-01";
                $end = ($startYear + 1) . "-06-30";
                break;

            case 'tahunan':
            case 'satu_tahun':
                $start = $yearStart;
                $end = $yearEnd;
                break;

            default:
                // fallback aman
                $start = $yearStart;
                $end = $yearEnd;
        }

        return [$start, $end];
    }



    private function generateRekap(int $classId, string $periode): array
    {
        $today = date('Y-m-d');
        $activeYear = $this->yearModel->getActiveYear();

        // ================================
        // Ambil rentang periode mentah
        // ================================
        [$startDate, $endDate] = $this->getPeriodeRange($activeYear, $periode);

        // ================================
        // Samakan dengan fungsi view()
        // ================================
        // Clamp ke tahun ajaran aktif
        if ($startDate < $activeYear['start_date']) {
            $startDate = $activeYear['start_date'];
        }
        if ($endDate > $activeYear['end_date']) {
            $endDate = $activeYear['end_date'];
        }

        // ================================
        // Buat daftar tanggal
        // ================================
        $period = new \DatePeriod(
            new \DateTime($startDate),
            new \DateInterval('P1D'),
            (new \DateTime($endDate))->modify('+1 day')
        );
        $dates = array_map(fn($d) => $d->format('Y-m-d'), iterator_to_array($period));

        // ================================
        // Ambil siswa
        // ================================
        $students = $this->studentModel
            ->select('students.*')
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
            ->where('sr.status', 'aktif')
            ->orderBy('students.name', 'ASC')
            ->findAll();

        // ================================
        // Ambil absensi
        // ================================
        $attendances = $this->attendanceModel
            ->where('class_id', $classId)
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        $attMap = [];
        foreach ($attendances as $a) {
            $attMap[$a['student_id']][$a['date']] = $a['status'];
        }

        // ================================
        // Ambil hari libur custom
        // ================================
        $holidayRows = $this->holidayModel
            ->where('date >=', $startDate)
            ->where('date <=', $endDate)
            ->findAll();

        $holidays = [];
        foreach ($holidayRows as $h) {
            $holidays[$h['date']] = true;
        }

        // ================================
        // Hitung rekap per siswa
        // (identik dengan fungsi view)
        // ================================
        $rekapSiswa = [];

        // Ambil pengaturan hari pembelajaran
        $schoolDays = $activeYear['school_days'] ?? 5;

        foreach ($students as $s) {
            $H = $I = $S = $A = 0;

            foreach ($dates as $d) {

                // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
                $isWeekend = is_weekend($d, $schoolDays);
                $isHoliday = $isWeekend || isset($holidays[$d]);
                if ($isHoliday || $d > $today)
                    continue;

                // Ambil status, default H
                $val = $attMap[$s['id']][$d] ?? 'H';

                if ($val === 'I')
                    $I++;
                elseif ($val === 'S')
                    $S++;
                elseif ($val === 'A')
                    $A++;
                else
                    $H++;
            }

            $total = $H + $I + $S + $A;
            $percent = $total ? round(($H / $total) * 100, 1) : 0;

            $rekapSiswa[] = [
                'student' => $s,
                'H' => $H,
                'I' => $I,
                'S' => $S,
                'A' => $A,
                'percent' => $percent
            ];
        }

        return [
            'activeYear' => $activeYear,
            'dates' => $dates,
            'holidays' => $holidays,
            'rekapSiswa' => $rekapSiswa
        ];
    }


    private function getRekapDateRange($year, $periode)
    {
        $yearStart = $year['start_date']; // e.g., '2023-08-01'
        $yearEnd = $year['end_date'];   // e.g., '2024-07-31'

        $startYear = date('Y', strtotime($yearStart)); // e.g., 2023

        if ($periode === 'semester1') {
            // Identik dengan view(): Juli - Desember tahun start
            return [
                date('Y-m-d', strtotime("{$startYear}-07-01")),
                date('Y-m-d', strtotime("{$startYear}-12-31"))
            ];
        }

        if ($periode === 'semester2') {
            // Identik dengan view(): Januari - Juni tahun berikutnya
            $nextYear = $startYear + 1;
            return [
                date('Y-m-d', strtotime("{$nextYear}-01-01")),
                date('Y-m-d', strtotime("{$nextYear}-06-30"))
            ];
        }

        // Tahunan: Tetap clamp ke tahun ajaran
        return [$yearStart, $yearEnd];
    }

    /**
     * Ambil siswa aktif di kelas berdasarkan tahun ajaran aktif.
     * Filter academic_year_id dan status='aktif' mencegah siswa tahun lalu ikut tampil.
     */
    private function getStudentsByClass(int $classId, ?array $activeYear = null): array
    {
        if (!$activeYear) {
            $activeYear = $this->yearModel->getActiveYear() ?: [];
        }
        return $this->studentModel
            ->select('students.*')
            ->join('student_records sr', 'sr.student_id = students.id')
            ->where('sr.class_id', $classId)
            ->where('sr.academic_year_id', $activeYear['id'] ?? 0)
            ->where('sr.status', 'aktif')
            ->orderBy('students.name', 'ASC')
            ->findAll();
    }

    private function canAccessAttendance($classId)
    {
        if (!$classId)
            return false;
        $session = session();
        $user = $session->get('user');

        if (!$user)
            return false;
        if ($user['role_id'] == 1 || $user['role_id'] == 2)
            return true; // Admin atau Kepala Sekolah

        if ($user['role_id'] == 3) {
            $teacherId = $user['related_id'] ?? null;
            if (!$teacherId)
                return false;

            // Wali Kelas?
            $isWali = $this->classModel->where('id', $classId)->where('teacher_id', $teacherId)->countAllResults();
            if ($isWali > 0)
                return true;

            // Guru Mapel?
            $db = \Config\Database::connect();
            $isMapel = $db->table('teaching_assignments')
                ->where('class_id', $classId)
                ->where('teacher_id', $teacherId)
                ->countAllResults();
            if ($isMapel > 0)
                return true;
        }

        return false;
    }

}
