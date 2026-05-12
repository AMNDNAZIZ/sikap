<?php
namespace App\Controllers\Siswa;

use App\Controllers\BaseController;

class AttendanceController extends BaseController
{
    protected $attendanceModel;
    protected $db;

    public function __construct()
    {
        $this->attendanceModel = new \App\Models\AttendanceModel();
        $this->db = db_connect();
    }

    public function index()
    {
        // auth
        $user = session()->get('user');
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $studentId = $user['student_id'];

        // ambil class_id dari student_records (kelas aktif terbaru)
        $record = $this->db->table('student_records')
            ->select('class_id')
            ->where('student_id', $studentId)
            ->where('status', 'aktif')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray();

        if (!$record) {
            return redirect()->back()->with('error', 'Data kelas siswa tidak ditemukan.');
        }
        $classId = $record['class_id'];

        // Ambil tahun ajaran aktif untuk batas min/max bulan
        $year = $this->db->table('academic_years')->where('is_active', 1)->get()->getRowArray();
        if (!$year) {
            return redirect()->back()->with('error', 'Tahun ajaran aktif tidak ditemukan.');
        }
        $startDate = $year['start_date']; // Y-m-d
        $endDate = $year['end_date'];

        $minMonth = date('Y-m', strtotime($startDate));
        $maxMonth = date('Y-m', strtotime($endDate));

        // params dari UI
        $month = $this->request->getGet('month') ?? date('Y-m');
        // clamp ke rentang tahun ajaran
        if ($month < $minMonth)
            $month = $minMonth;
        if ($month > $maxMonth)
            $month = $maxMonth;

        $activeOnly = $this->request->getGet('activeOnly') === '1';

        // prev/next month (dalam rentang)
        $prevMonth = date('Y-m', strtotime('-1 month', strtotime($month . '-01')));
        $nextMonth = date('Y-m', strtotime('+1 month', strtotime($month . '-01')));
        if ($prevMonth < $minMonth)
            $prevMonth = null;
        if ($nextMonth > $maxMonth)
            $nextMonth = null;

        // range tanggal untuk bulan yang dipilih (maks hingga hari ini)
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01', strtotime($month . '-01'));
        $monthEndRaw = date('Y-m-t', strtotime($month . '-01')); // akhir bulan
        $monthEnd = ($monthEndRaw > $today) ? $today : $monthEndRaw;
        // juga pastikan tidak keluar dari tahun ajaran
        if ($monthStart < $startDate)
            $monthStart = $startDate;
        if ($monthEnd > $endDate)
            $monthEnd = $endDate;

        // buat daftar tanggal dari monthStart .. monthEnd
        $dates = [];
        for ($d = strtotime($monthStart); $d <= strtotime($monthEnd); $d = strtotime('+1 day', $d)) {
            $dates[] = date('Y-m-d', $d);
        }

        // ambil attendances bulan ini untuk siswa
        $attendances = $this->attendanceModel
            ->where('student_id', $studentId)
            ->where('class_id', $classId)
            ->where('date >=', $monthStart)
            ->where('date <=', $monthEnd)
            ->findAll();

        $map = [];
        foreach ($attendances as $a) {
            $map[$a['date']] = $a['status']; // H/I/S/A, namun model simpan hanya non-H biasanya
        }

        // ambil holidays untuk bulan yang relevan (atau pakai model getCustomHolidays)
        $holidaysArr = $this->attendanceModel->getCustomHolidays(date('Y-m', strtotime($month . '-01')));
        $holidayMap = [];
        foreach ($holidaysArr as $h) {
            $holidayMap[$h['date']] = $h['description'];
        }

        // Susun records (tanggal -> status + flags)
        $records = [];
        foreach ($dates as $dateStr) {
            $dayNum = (int) date('N', strtotime($dateStr)); // 1..7 (1=Mon,6=Sat,7=Sun)
            $isWeekend = ($dayNum >= 6);
            $isHoliday = isset($holidayMap[$dateStr]);

            if ($activeOnly && ($isWeekend || $isHoliday)) {
                // jika user memilih hanya hari aktif, skip baris
                continue;
            }

            if ($isWeekend) {
                $statusKey = '-';
                $note = ($dayNum == 6) ? 'Sabtu' : 'Minggu';
            } elseif ($isHoliday) {
                $statusKey = '-';
                $note = $holidayMap[$dateStr];
            } else {
                // hari aktif: kalau ada record gunakan, jika tidak anggap 'H'
                $statusKey = $map[$dateStr] ?? 'H';
                $note = null;
            }

            $statusLabel = [
                'H' => 'Hadir',
                'I' => 'Izin',
                'S' => 'Sakit',
                'A' => 'Alpa',
                '-' => '-'
            ][$statusKey] ?? $statusKey;

            $records[] = [
                'date' => $dateStr,
                'status_key' => $statusKey,
                'status' => $statusLabel,
                'note' => $note,
                'isWeekend' => $isWeekend,
                'isHoliday' => $isHoliday,
            ];
        }

        // REKAP BULAN (hanya hitung H/I/S/A pada hari aktif)
        $summary = [
            'Hadir' => 0,
            'Izin' => 0,
            'Sakit' => 0,
            'Alpa' => 0,
        ];
        foreach ($records as $r) {
            // hanya hitung jika status termasuk kategori resmi
            if (in_array($r['status_key'], ['H', 'I', 'S', 'A'], true)) {
                $label = [
                    'H' => 'Hadir',
                    'I' => 'Izin',
                    'S' => 'Sakit',
                    'A' => 'Alpa'
                ][$r['status_key']];
                $summary[$label]++;
            }
        }

        // --- REKAP SEMESTER BERJALAN ---
        // Tentukan semester berjalan berdasarkan tanggal hari ini (bukan $month)
        $todayMonthNum = (int) date('n'); // 1..12
        $todayYear = (int) date('Y');

        if ($todayMonthNum >= 7) {
            // Semester 1: Juli - Desember (tahun sama)
            $semStart = sprintf('%04d-07-01', $todayYear);
            $semEnd = sprintf('%04d-12-31', $todayYear);
            $semesterName = "Semester 1 (Juli - Desember $todayYear)";
        } else {
            // Semester 2: Januari - Juni (tahun sama)
            $semStart = sprintf('%04d-01-01', $todayYear);
            $semEnd = sprintf('%04d-06-30', $todayYear);
            $semesterName = "Semester 2 (Januari - Juni $todayYear)";
        }

        // Clamp semester range ke tahun ajaran aktif
        if ($semStart < $startDate)
            $semStart = $startDate;
        if ($semEnd > $endDate)
            $semEnd = $endDate;

        // Karena semester berjalan, batas akhir hitungan = hari ini (jika semester belum selesai)
        if ($semEnd > $today)
            $semEnd = $today;

        // Jika semStart > semEnd (mis. semester belum mulai), set default empty
        $semesterSummary = [
            'Hadir' => 0,
            'Izin' => 0,
            'Sakit' => 0,
            'Alpa' => 0,
        ];
        $totalHariAktifSemester = 0;
        if (strtotime($semStart) <= strtotime($semEnd)) {
            // ambil attendance untuk rentang semester (sampai hari ini)
            $semAttendances = $this->attendanceModel
                ->where('student_id', $studentId)
                ->where('class_id', $classId)
                ->where('date >=', $semStart)
                ->where('date <=', $semEnd)
                ->findAll();

            $semMap = [];
            foreach ($semAttendances as $a) {
                $semMap[$a['date']] = $a['status'];
            }

            // ambil holidays semester
            $semHolidays = $this->db->table('holidays')
                ->where('date >=', $semStart)
                ->where('date <=', $semEnd)
                ->get()->getResultArray();

            $semHolidayMap = [];
            foreach ($semHolidays as $h)
                $semHolidayMap[$h['date']] = $h['description'];

            // iterasi setiap tanggal semester, hitung hanya hari aktif (skip weekend+holiday)
            for ($d = strtotime($semStart); $d <= strtotime($semEnd); $d = strtotime('+1 day', $d)) {
                $dateStr = date('Y-m-d', $d);
                $dayNum = (int) date('N', $d);
                $isWeekend = ($dayNum >= 6);
                $isHoliday = isset($semHolidayMap[$dateStr]);

                if ($isWeekend || $isHoliday)
                    continue; // skip dari rekap semester

                $totalHariAktifSemester++;
                $statusKey = $semMap[$dateStr] ?? 'H';
                $label = [
                    'H' => 'Hadir',
                    'I' => 'Izin',
                    'S' => 'Sakit',
                    'A' => 'Alpa'
                ][$statusKey] ?? 'Hadir';

                $semesterSummary[$label]++;
            }
        }

        $attendancePercentage = $totalHariAktifSemester > 0
            ? round(($semesterSummary['Hadir'] / $totalHariAktifSemester) * 100, 2)
            : 0;

        // kirim ke view
        return view('siswa/attendance/index', [
            'records' => $records,
            'month' => $month,
            'activeOnly' => $activeOnly,
            'summary' => $summary,
            'minMonth' => $minMonth,
            'maxMonth' => $maxMonth,
            'prevMonth' => $prevMonth,
            'nextMonth' => $nextMonth,
            'semesterName' => $semesterName,
            'semesterSummary' => $semesterSummary,
            'attendancePercentage' => $attendancePercentage,
            'totalHariAktifSemester' => $totalHariAktifSemester,
        ]);
    }
}
