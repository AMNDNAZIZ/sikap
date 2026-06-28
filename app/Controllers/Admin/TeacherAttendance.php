<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeacherAttendanceModel;
use App\Models\ScheduleModel;
use App\Models\TeacherModel;
use App\Models\AcademicYearModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;

class TeacherAttendance extends BaseController
{
    protected TeacherAttendanceModel $attendanceModel;
    protected ScheduleModel          $scheduleModel;
    protected TeacherModel           $teacherModel;
    protected AcademicYearModel      $yearModel;

    public function __construct()
    {
        $this->attendanceModel = new TeacherAttendanceModel();
        $this->scheduleModel   = new ScheduleModel();
        $this->teacherModel    = new TeacherModel();
        $this->yearModel       = new AcademicYearModel();
        helper('license'); // loads is_weekend() helper
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Hitung JP dari start_time & end_time.
     * 1 JP = 45 menit, dibulatkan ke atas.
     */
    private function hitungJP(string $start, string $end): int
    {
        $diffSec = strtotime($end) - strtotime($start);
        if ($diffSec <= 0) {
            return 0;
        }
        return (int) ceil($diffSec / (45 * 60));
    }

    /**
     * Nama hari Indonesia dari nomor (1=Senin…7=Minggu).
     */
    private function namaHari(int $dayOfWeek): string
    {
        $map = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
        return $map[$dayOfWeek] ?? '-';
    }

    /**
     * Ambil tahun ajaran aktif (throw jika tidak ada).
     */
    private function getActiveYear(): array
    {
        $year = $this->yearModel->getActiveYear();
        if (!$year) {
            return [];
        }
        return $year;
    }

    /**
     * Hitung rekap JP per guru untuk bulan tertentu.
     * Mengembalikan array [teacher_id => [teacher_data, totalSlot, totalJP, jpTH, jpHadir, persen]]
     */
    private function hitungRekapBulan(string $yearMonth, ?int $filterTeacherId = null): array
    {
        $activeYear = $this->getActiveYear();
        if (empty($activeYear)) {
            return [];
        }

        $monthStart = $yearMonth . '-01';
        $monthEnd   = date('Y-m-t', strtotime($monthStart));

        // Ambil semua jadwal di tahun ajaran aktif (filter teacher jika ada)
        $db          = \Config\Database::connect();
        $schedBuilder = $db->table('schedules s')
            ->select('s.id, s.day_of_week, s.start_time, s.end_time, s.teacher_id, t.name AS teacher_name, t.nip')
            ->join('teachers t', 't.id = s.teacher_id')
            ->where('s.academic_year_id', $activeYear['id']);

        if ($filterTeacherId) {
            $schedBuilder->where('s.teacher_id', $filterTeacherId);
        }

        $schedules = $schedBuilder->get()->getResultArray();

        if (empty($schedules)) {
            return [];
        }

        // Kelompokkan jadwal per guru
        $byTeacher = [];
        foreach ($schedules as $s) {
            $tid = $s['teacher_id'];
            if (!isset($byTeacher[$tid])) {
                $byTeacher[$tid] = [
                    'teacher_id'   => $tid,
                    'teacher_name' => $s['teacher_name'],
                    'nip'          => $s['nip'],
                    'schedules'    => [],
                ];
            }
            $byTeacher[$tid]['schedules'][] = $s;
        }

        // Iterasi hari-hari dalam bulan, hitung slot & JP terjadwal per guru
        $start = strtotime($monthStart);
        $end   = strtotime($monthEnd);

        // Ambil semua record TH untuk bulan ini (semua guru)
        $thRecords = $db->table('teacher_attendances ta')
            ->select('ta.schedule_id, ta.date')
            ->join('schedules s', 's.id = ta.schedule_id')
            ->where('ta.status', 'TH')
            ->where('ta.date >=', $monthStart)
            ->where('ta.date <=', $monthEnd)
            ->get()
            ->getResultArray();

        // Map: schedule_id + date => TH
        $thMap = [];
        foreach ($thRecords as $r) {
            $thMap[$r['schedule_id'] . '_' . $r['date']] = true;
        }

        // Holidays dalam bulan
        $holidays = $db->table('holidays')
            ->where('date >=', $monthStart)
            ->where('date <=', $monthEnd)
            ->get()
            ->getResultArray();
        $holidayDates = [];
        foreach ($holidays as $h) {
            $holidayDates[$h['date']] = true;
        }

        $result = [];

        foreach ($byTeacher as $tid => $tData) {
            $totalJP  = 0;
            $jpTH     = 0;
            $totalSlot = 0;

            for ($d = $start; $d <= $end; $d = strtotime('+1 day', $d)) {
                $dateStr   = date('Y-m-d', $d);
                $dayOfWeek = (int) date('N', $d); // 1=Mon..7=Sun

                if (isset($holidayDates[$dateStr])) {
                    continue;
                }
                if (is_weekend($dateStr)) {
                    continue;
                }

                foreach ($tData['schedules'] as $s) {
                    if ((int) $s['day_of_week'] !== $dayOfWeek) {
                        continue;
                    }

                    $jp = $this->hitungJP($s['start_time'], $s['end_time']);
                    $totalJP  += $jp;
                    $totalSlot++;

                    $key = $s['id'] . '_' . $dateStr;
                    if (isset($thMap[$key])) {
                        $jpTH += $jp;
                    }
                }
            }

            $jpHadir = $totalJP - $jpTH;
            $persen  = $totalJP > 0 ? round(($jpHadir / $totalJP) * 100, 1) : 0;

            $result[$tid] = [
                'teacher_id'   => $tid,
                'teacher_name' => $tData['teacher_name'],
                'nip'          => $tData['nip'],
                'total_slot'   => $totalSlot,
                'total_jp'     => $totalJP,
                'jp_th'        => $jpTH,
                'jp_hadir'     => $jpHadir,
                'persen'       => $persen,
            ];
        }

        // Urutkan by nama
        uasort($result, fn($a, $b) => strcmp($a['teacher_name'], $b['teacher_name']));

        return $result;
    }

    // -----------------------------------------------------------------------
    // 1. INDEX — Input absensi harian (Admin / Staf)
    // -----------------------------------------------------------------------

    public function index()
    {
        $date = $this->request->getGet('date') ?? date('Y-m-d');

        // Validasi format tanggal
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $activeYear = $this->getActiveYear();
        if (empty($activeYear)) {
            return view('admin/teacher_attendance/index', [
                'title'       => 'Absensi Guru',
                'date'        => $date,
                'schedules'   => [],
                'attendances' => [],
                'activeYear'  => null,
                'error'       => 'Tidak ada tahun ajaran aktif.',
            ]);
        }

        $dayOfWeek = (int) date('N', strtotime($date)); // 1=Senin..7=Minggu

        // Ambil semua jadwal hari ini di tahun ajaran aktif
        $schedules = $this->scheduleModel->getScheduleForDay($dayOfWeek, [], $activeYear['id']);

        // Tambahkan JP ke tiap jadwal
        foreach ($schedules as &$s) {
            $s['jp'] = $this->hitungJP($s['start_time'], $s['end_time']);
        }
        unset($s);

        // Ambil record absensi untuk tanggal ini
        $rawAttendances = $this->attendanceModel
            ->where('date', $date)
            ->findAll();

        // Map: schedule_id => record
        $attendances = [];
        foreach ($rawAttendances as $a) {
            $attendances[$a['schedule_id']] = $a;
        }

        return view('admin/teacher_attendance/index', [
            'title'       => 'Absensi Guru Harian',
            'date'        => $date,
            'schedules'   => $schedules,
            'attendances' => $attendances,
            'activeYear'  => $activeYear,
            'namaHari'    => $this->namaHari($dayOfWeek),
        ]);
    }

    // -----------------------------------------------------------------------
    // 2. SAVE — AJAX POST (Admin / Staf)
    // -----------------------------------------------------------------------

    public function save()
    {
        $user       = session()->get('user');
        $date       = $this->request->getPost('date');
        $scheduleId = (int) $this->request->getPost('schedule_id');
        $status     = $this->request->getPost('status'); // 'H' atau 'TH'
        $keterangan = $this->request->getPost('keterangan') ?? '';

        if (!$date || !$scheduleId || !in_array($status, ['H', 'TH'])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Data tidak lengkap.']);
        }

        if ($status === 'H') {
            // Hapus record jika ada (default = Hadir, tidak perlu disimpan)
            $this->attendanceModel
                ->where('schedule_id', $scheduleId)
                ->where('date', $date)
                ->delete();
        } else {
            // Upsert record TH
            $existing = $this->attendanceModel
                ->where('schedule_id', $scheduleId)
                ->where('date', $date)
                ->first();

            $payload = [
                'schedule_id' => $scheduleId,
                'date'        => $date,
                'status'      => 'TH',
                'keterangan'  => $keterangan,
                'recorded_by' => $user['id'] ?? null,
            ];

            if ($existing) {
                $this->attendanceModel->update($existing['id'], $payload);
            } else {
                $this->attendanceModel->insert($payload);
            }
        }

        return $this->response->setJSON(['success' => true]);
    }

    // -----------------------------------------------------------------------
    // 3. REPORT — Rekap bulanan (Admin / Staf / Kepsek)
    // -----------------------------------------------------------------------

    public function report()
    {
        $month      = $this->request->getGet('month') ?? date('Y-m');
        $teacherId  = (int) ($this->request->getGet('teacher_id') ?? 0);

        $rekap    = $this->hitungRekapBulan($month, $teacherId ?: null);
        $teachers = $this->teacherModel->orderBy('name', 'ASC')->findAll();

        return view('admin/teacher_attendance/report', [
            'title'    => 'Laporan Absensi Guru',
            'month'    => $month,
            'rekap'    => $rekap,
            'teachers' => $teachers,
            'filterTeacherId' => $teacherId,
        ]);
    }

    // -----------------------------------------------------------------------
    // 4. REPORT DETAIL — Rincian ketidakhadiran satu guru
    // -----------------------------------------------------------------------

    public function reportDetail(int $teacherId)
    {
        $month   = $this->request->getGet('month') ?? date('Y-m');
        $teacher = $this->teacherModel->find($teacherId);

        if (!$teacher) {
            return redirect()->to(base_url('admin/teacher-attendance/report'))->with('error', 'Guru tidak ditemukan.');
        }

        $details = $this->attendanceModel->getAbsensiGuruByMonth($teacherId, $month);

        // Tambahkan JP & nama hari ke tiap record
        foreach ($details as &$d) {
            $d['jp']       = $this->hitungJP($d['start_time'], $d['end_time']);
            $d['nama_hari'] = $this->namaHari((int) $d['day_of_week']);
        }
        unset($d);

        return view('admin/teacher_attendance/detail', [
            'title'   => 'Detail Absensi Guru',
            'month'   => $month,
            'teacher' => $teacher,
            'details' => $details,
        ]);
    }

    // -----------------------------------------------------------------------
    // 5. EXPORT EXCEL
    // -----------------------------------------------------------------------

    public function exportExcel()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');
        $rekap = $this->hitungRekapBulan($month);

        if (ob_get_length()) {
            @ob_end_clean();
        }

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        // Judul
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', 'Laporan Absensi Guru — ' . date('F Y', strtotime($month . '-01')));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);

        // Header tabel
        $headers = ['No', 'Nama Guru', 'NIP', 'Total JP Terjadwal', 'JP Hadir', 'JP Tidak Hadir', '% Kehadiran'];
        $col     = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '3', $h);
            $sheet->getStyle($col . '3')->getFont()->setBold(true);
            $col++;
        }

        $row = 4;
        $no  = 1;
        foreach ($rekap as $r) {
            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $r['teacher_name']);
            $sheet->setCellValue('C' . $row, $r['nip'] ?? '-');
            $sheet->setCellValue('D' . $row, $r['total_jp']);
            $sheet->setCellValue('E' . $row, $r['jp_hadir']);
            $sheet->setCellValue('F' . $row, $r['jp_th']);
            $sheet->setCellValue('G' . $row, $r['persen'] . '%');
            $row++;
        }

        // Auto-size kolom
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'Absensi_Guru_' . $month;
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '.xlsx"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    // -----------------------------------------------------------------------
    // 6. EXPORT PDF
    // -----------------------------------------------------------------------

    public function exportPdf()
    {
        $month = $this->request->getGet('month') ?? date('Y-m');
        $rekap = $this->hitungRekapBulan($month);

        $html = view('admin/teacher_attendance/pdf_report', [
            'title' => 'Laporan Absensi Guru',
            'month' => $month,
            'rekap' => $rekap,
        ]);

        if (ob_get_length()) @ob_end_clean();

        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('Absensi_Guru_' . $month . '.pdf', ['Attachment' => true]);
        exit;
    }

    // -----------------------------------------------------------------------
    // 7. MY ATTENDANCE — Guru melihat kehadiran sendiri (role 3)
    // -----------------------------------------------------------------------

    public function myAttendance()
    {
        $user      = session()->get('user');
        $teacherId = $user['related_id'] ?? null;

        if (!$teacherId) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Data guru tidak ditemukan.');
        }

        $date = $this->request->getGet('date') ?? date('Y-m-d');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $activeYear = $this->getActiveYear();
        if (empty($activeYear)) {
            return view('admin/teacher_attendance/my_attendance', [
                'title'       => 'Kehadiran Saya',
                'date'        => $date,
                'teacher'     => null,
                'schedules'   => [],
                'attendances' => [],
                'error'       => 'Tidak ada tahun ajaran aktif.',
            ]);
        }

        $dayOfWeek = (int) date('N', strtotime($date));

        // Jadwal guru ini di hari tersebut
        $schedules = $this->scheduleModel->getScheduleForDay($dayOfWeek, ['teacher_id' => $teacherId], $activeYear['id']);

        foreach ($schedules as &$s) {
            $s['jp'] = $this->hitungJP($s['start_time'], $s['end_time']);
        }
        unset($s);

        // Absensi tanggal tersebut untuk guru ini
        $db = \Config\Database::connect();
        $rawAtt = $db->table('teacher_attendances ta')
            ->select('ta.*')
            ->join('schedules s', 's.id = ta.schedule_id')
            ->where('ta.date', $date)
            ->where('s.teacher_id', $teacherId)
            ->get()
            ->getResultArray();

        $attendances = [];
        foreach ($rawAtt as $a) {
            $attendances[$a['schedule_id']] = $a;
        }

        $teacher = $this->teacherModel->find($teacherId);

        return view('admin/teacher_attendance/my_attendance', [
            'title'       => 'Kehadiran Saya',
            'date'        => $date,
            'teacher'     => $teacher,
            'schedules'   => $schedules,
            'attendances' => $attendances,
            'namaHari'    => $this->namaHari($dayOfWeek),
        ]);
    }
}
