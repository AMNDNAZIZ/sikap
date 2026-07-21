<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ScheduleModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\TeacherModel;
use App\Models\AcademicYearModel;
use App\Models\TeachingAssignmentModel;
use App\Models\SchoolModel;

class ScheduleController extends BaseController
{
    protected $scheduleModel;
    protected $classModel;
    protected $subjectModel;
    protected $teacherModel;
    protected $academicYearModel;
    protected $assignmentModel;
    protected $schoolModel;
    protected $db;

    public function __construct()
    {
        $this->scheduleModel = new ScheduleModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->teacherModel = new TeacherModel();
        $this->academicYearModel = new AcademicYearModel();
        $this->assignmentModel = new TeachingAssignmentModel();
        $this->schoolModel = new SchoolModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $data['title'] = 'Jadwal Pelajaran';
        $this->validateAccess();

        $activeYear = $this->academicYearModel->getActiveYear();
        $user = session()->get('user');
        $school = $this->schoolModel->getProfile();

        if (in_array($user['role_id'], [1, 2])) {
            // Admin / Kepsek: semua kelas aktif
            $data['classes'] = $this->classModel->where('is_active', 1)->orderBy('name', 'ASC')->findAll();
        } elseif ($user['role_id'] == 3) {
            // Guru: hanya kelas yang diampu di tahun ajaran aktif
            $teacherId = $user['related_id'];
            $activeYearId = $activeYear['id'] ?? 0;

            if ($school['level'] == 'SD') {
                // SD: guru kelas hanya lihat kelas perwaliannya
                $data['classes'] = $this->classModel
                    ->where('teacher_id', $teacherId)
                    ->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
            } else {
                // SMP/SMA: gabung kelas wali + kelas mapel (tahun ajaran aktif)
                $waliClasses = $this->classModel
                    ->where('teacher_id', $teacherId)
                    ->where('is_active', 1)
                    ->findAll();

                $mapelClasses = $this->db->table('teaching_assignments ta')
                    ->select('c.id, c.name, c.level, c.is_active, c.teacher_id')
                    ->join('classes c', 'c.id = ta.class_id')
                    ->where('ta.teacher_id', $teacherId)
                    ->where('ta.academic_year_id', $activeYearId)
                    ->groupBy('c.id')
                    ->orderBy('c.name', 'ASC')
                    ->get()->getResultArray();

                $merged = [];
                foreach ($waliClasses as $c) $merged[$c['id']] = $c;
                foreach ($mapelClasses as $c) $merged[$c['id']] = $c;
                $data['classes'] = array_values($merged);
            }
        } else {
            $data['classes'] = [];
        }

        $data['activeYear'] = $activeYear;
        
        $selectedClassId = $this->request->getGet('class_id');
        $data['selectedClassId'] = $selectedClassId;
        
        if ($selectedClassId) {
            $data['schedules'] = $this->scheduleModel->getSchedulesByClass($selectedClassId, $activeYear['id']);
        } else {
            $data['schedules'] = [];
        }

        $data['days'] = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        // Check if user can manage the selected class
        $data['canManage'] = false;
        if ($selectedClassId) {
            $data['canManage'] = $this->isAuthorizedToManage($selectedClassId);
        }

        // Untuk guru: kumpulkan subject_id yang diampu agar view bisa disable link mapel lain
        $data['teacherSubjectIds'] = [];
        if ($user['role_id'] == 3 && $selectedClassId && !empty($activeYear['id'])) {
            $mySubjects = $this->db->table('teaching_assignments')
                ->select('subject_id')
                ->where('teacher_id', $user['related_id'])
                ->where('class_id', $selectedClassId)
                ->where('academic_year_id', $activeYear['id'])
                ->get()->getResultArray();
            $data['teacherSubjectIds'] = array_column($mySubjects, 'subject_id');
        }

        return view('admin/schedule/index', $data);
    }

    public function manage($classId)
    {
        $this->validateAccess($classId);

        $class = $this->classModel->find($classId);
        if (!$class) return redirect()->to(base_url('admin/schedules'))->with('error', 'Kelas tidak ditemukan.');

        $activeYear = $this->academicYearModel->getActiveYear();
        $data['title'] = 'Kelola Jadwal - ' . $class['name'];
        $data['class'] = $class;
        $data['activeYear'] = $activeYear;
        
        // Fetch existing schedules
        $data['schedules'] = $this->scheduleModel->where('class_id', $classId)
            ->where('academic_year_id', $activeYear['id'])
            ->orderBy('day_of_week', 'ASC')
            ->orderBy('start_time', 'ASC')
            ->findAll();

        // Fetch teaching assignments for this class to make selection easier
        $data['assignments'] = $this->assignmentModel
            ->select('teaching_assignments.*, subjects.name as subject_name, teachers.name as teacher_name')
            ->join('subjects', 'subjects.id = teaching_assignments.subject_id')
            ->join('teachers', 'teachers.id = teaching_assignments.teacher_id')
            ->where('teaching_assignments.class_id', $classId)
            ->where('teaching_assignments.academic_year_id', $activeYear['id'])
            ->findAll();

        $data['days'] = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        return view('admin/schedule/manage', $data);
    }

    public function store()
    {
        $classId = $this->request->getPost('class_id');
        $this->validateAccess($classId);
        $academicYearId = $this->request->getPost('academic_year_id');
        $dayOfWeek = $this->request->getPost('day_of_week');
        
        $startTimes = $this->request->getPost('start_time');
        $endTimes = $this->request->getPost('end_time');
        $assignments = $this->request->getPost('assignment_id'); // Format: subject_id|teacher_id

        if (!$dayOfWeek || !$classId || !$academicYearId) {
            return redirect()->back()->with('error', 'Data tidak lengkap.');
        }

        $this->db->transStart();

        // Optional: Clear existing for this specific day?
        // Actually, better to just clear ALL for the class/day combination to support mass update
        $this->scheduleModel->where([
            'class_id' => $classId,
            'academic_year_id' => $academicYearId,
            'day_of_week' => $dayOfWeek
        ])->delete();

        if ($startTimes && is_array($startTimes)) {
            foreach ($startTimes as $index => $startTime) {
                if (empty($startTime) || empty($endTimes[$index]) || empty($assignments[$index])) continue;

                list($subjectId, $teacherId) = explode('|', $assignments[$index]);

                $this->scheduleModel->insert([
                    'day_of_week'      => $dayOfWeek,
                    'class_id'         => $classId,
                    'subject_id'       => $subjectId,
                    'teacher_id'       => $teacherId,
                    'start_time'       => $startTime,
                    'end_time'         => $endTimes[$index],
                    'academic_year_id' => $academicYearId
                ]);
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan jadwal.');
        }

        return redirect()->to(base_url('admin/schedules/manage/' . $classId))->with('success', 'Jadwal hari tersebut berhasil diperbarui.');
    }

    /**
     * Store schedules for multiple days at once
     */
    public function storeBulk()
    {
        $classId = $this->request->getPost('class_id');
        $this->validateAccess($classId);
        $academicYearId = $this->request->getPost('academic_year_id');

        if (!$classId || !$academicYearId) {
            return redirect()->back()->with('error', 'Data tidak lengkap.');
        }

        $this->db->transStart();

        // Clear all existing schedules for this class and academic year
        $this->scheduleModel->where([
            'class_id' => $classId,
            'academic_year_id' => $academicYearId
        ])->delete();

        $totalInserted = 0;
        $days = [1, 2, 3, 4, 5, 6]; // Senin - Sabtu

        // Process each day
        foreach ($days as $dayNum) {
            $startTimes = $this->request->getPost("day_{$dayNum}_start_time");
            $endTimes = $this->request->getPost("day_{$dayNum}_end_time");
            $assignments = $this->request->getPost("day_{$dayNum}_assignment_id");

            if ($startTimes && is_array($startTimes)) {
                foreach ($startTimes as $index => $startTime) {
                    if (empty($startTime) || empty($endTimes[$index]) || empty($assignments[$index])) continue;

                    list($subjectId, $teacherId) = explode('|', $assignments[$index]);

                    $this->scheduleModel->insert([
                        'day_of_week'      => $dayNum,
                        'class_id'         => $classId,
                        'subject_id'       => $subjectId,
                        'teacher_id'       => $teacherId,
                        'start_time'       => $startTime,
                        'end_time'         => $endTimes[$index],
                        'academic_year_id' => $academicYearId
                    ]);

                    $totalInserted++;
                }
            }
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return redirect()->back()->with('error', 'Gagal menyimpan jadwal.');
        }

        if ($totalInserted === 0) {
            return redirect()->back()->with('warning', 'Tidak ada jadwal yang disimpan. Silakan tambahkan minimal satu jadwal.');
        }

        return redirect()->to(base_url('admin/schedules/manage/' . $classId))
            ->with('success', "Berhasil menyimpan {$totalInserted} jadwal untuk semua hari.");
    }

    private function isAuthorizedToManage($classId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;

        // Admin dan Staf bisa kelola semua kelas
        if (in_array($roleId, [1, 7])) return true;

        // Guru kelas (role 3): hanya untuk level SD, dan hanya kelas yang diampu
        if ($roleId == 3 && $classId) {
            $school = $this->schoolModel->getProfile();
            if (($school['level'] ?? '') == 1 || strtoupper($school['level'] ?? '') == 'SD') {
                $class = $this->classModel->find($classId);
                return ($class && $class['teacher_id'] == $user['related_id']);
            }
        }

        return false;
    }

    public function delete($id)
    {
        $schedule = $this->scheduleModel->find($id);
        if (!$schedule) return redirect()->back()->with('error', 'Jadwal tidak ditemukan.');
        
        $this->validateAccess($schedule['class_id']);

        $this->scheduleModel->delete($id);
        return redirect()->back()->with('success', 'Item jadwal berhasil dihapus.');
    }

    /**
     * Validate if current user has access to manage schedule
     */
    private function validateAccess($classId = null)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;

        // 1. Admin dan Staf always allowed
        if (in_array($roleId, [1, 7])) return true;

        // 2. Kepsek (role 2) — view only, tidak boleh POST/delete
        if ($roleId == 2) {
            if ($this->request->getMethod() == 'post' || strpos($this->request->getUri()->getPath(), 'delete') !== false) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Kepala Sekolah hanya dapat melihat jadwal.");
            }
            return true;
        }

        // 3. Guru kelas (role 3) — hanya untuk level SD, hanya kelas yang diampu
        if ($roleId == 3) {
            $school = $this->schoolModel->getProfile();
            $isSD = (($school['level'] ?? '') == 1 || strtoupper($school['level'] ?? '') == 'SD');

            if ($isSD && $classId) {
                $class = $this->classModel->find($classId);
                if (!$class || $class['teacher_id'] != $user['related_id']) {
                    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Anda hanya dapat mengelola jadwal untuk kelas Anda sendiri.");
                }
                return true;
            }

            // Guru non-SD atau tanpa classId: view only
            if ($this->request->getMethod() == 'post' || strpos($this->request->getUri()->getPath(), 'delete') !== false) {
                throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Guru tidak memiliki akses untuk mengelola jadwal.");
            }
            return true;
        }

        // Default: deny POST/delete for unknown roles
        if ($this->request->getMethod() == 'post' || strpos($this->request->getUri()->getPath(), 'delete') !== false) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound("Akses ditolak.");
        }

        return true;
    }
}
