<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Models\RoleModel;
use App\Models\SchoolModel;
use App\Models\StudentModel;
use App\Models\CbtBankSoalModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtSessionModel;
use App\Models\TeacherModel;

use App\Models\LicenseModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? null;

        switch ($roleId) {
            case 1: // 🧑‍💼 Admin
                $roleModel = new RoleModel();
                $schoolModel = new SchoolModel();
                $studentModel = new StudentModel();
                $bankModel = new CbtBankSoalModel();
                $testStatusModel = new CbtTestStatusModel();
                $sessionModel = new CbtSessionModel();
                $licenseModel = new LicenseModel();

                // Cache static data for 5 minutes
                $role = cache_remember('dashboard_role_' . $roleId, 300, function () use ($roleModel, $roleId) {
                    return $roleModel->find($roleId);
                });

                $school = cache_remember('dashboard_school', 300, function () use ($schoolModel) {
                    return $schoolModel->first();
                });

                // Cache counts for 1 minute (they change less frequently)
                $students = cache_remember('dashboard_students_count', 60, function () use ($studentModel) {
                    return $studentModel->countAllResults();
                });

                $banks = cache_remember('dashboard_banks_count', 60, function () use ($bankModel) {
                    return $bankModel->countAllResults();
                });

                $tests = cache_remember('dashboard_tests_count', 60, function () use ($testStatusModel) {
                    return $testStatusModel->countAllResults();
                });

                // Active users should be real-time, no cache
                $activeUsers = $sessionModel->where('status', 'active')->countAllResults();

                // 1. License Info - cache for 5 minutes
                $license = cache_remember('dashboard_license', 300, function () use ($licenseModel) {
                    return $licenseModel->getActiveLicense();
                });

                // 2. Active/Upcoming Exams (Next 7 days) - cache for 2 minutes
                $now = date('Y-m-d H:i:s');
                $upcomingExams = cache_remember('dashboard_upcoming_exams', 120, function () use ($testStatusModel, $now) {
                    return $testStatusModel->select('cbt_test_status.*, cbt_exam_names.name as exam_name, subjects.name as subject_name')
                        ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
                        ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
                        ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                        ->where('cbt_test_status.is_active', 1)
                        ->where('cbt_test_status.end_time >=', $now)
                        ->orderBy('cbt_test_status.start_time', 'ASC')
                        ->limit(5)
                        ->find();
                });

                // 3. Recent Activity (Active Sessions - Students Currently Taking Exam)
                // No cache - needs to be real-time
                // Filter: Only show students who are ACTUALLY taking exam right now
                $recentActivity = $sessionModel->select('cbt_sessions.*, students.name as student_name, cbt_exam_names.name as exam_name, cbt_test_status.duration, cbt_test_status.start_time, cbt_test_status.end_time')
                    ->join('students', 'students.id = cbt_sessions.student_id', 'left')
                    ->join('cbt_test_status', 'cbt_test_status.id = cbt_sessions.test_id', 'left')
                    ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                    ->where('cbt_sessions.status', 'active')
                    ->where('cbt_test_status.is_active', 1) // Exam must be active
                    ->where('cbt_test_status.start_time <=', $now) // Exam has started
                    ->where('cbt_test_status.end_time >=', $now) // Exam not ended yet
                    ->orderBy('cbt_sessions.started_at', 'DESC')
                    ->findAll(); // Get all active sessions, will limit in view with scroll

                // Get database connection for progress calculation
                $db = \Config\Database::connect();

                // Optimize: Batch query for answer counts instead of N+1 queries
                $sessionIds = array_column($recentActivity, 'id');
                $studentIds = array_column($recentActivity, 'student_id');
                $testIds = array_column($recentActivity, 'test_id');

                // Build answer count map in single query
                $answerCounts = [];
                if (!empty($studentIds) && !empty($testIds)) {
                    $answerResults = $db->table('cbt_answers')
                        ->select('student_id, test_id, COUNT(*) as count')
                        ->whereIn('student_id', $studentIds)
                        ->whereIn('test_id', $testIds)
                        ->groupBy(['student_id', 'test_id'])
                        ->get()
                        ->getResultArray();

                    foreach ($answerResults as $row) {
                        $key = $row['student_id'] . '_' . $row['test_id'];
                        $answerCounts[$key] = (int) $row['count'];
                    }
                }

                // Calculate progress for each active session
                foreach ($recentActivity as &$activity) {
                    // Get question count and answered count
                    $questionOrder = json_decode($activity['question_order'] ?? '[]', true) ?? [];
                    $totalQuestions = count($questionOrder);

                    $key = $activity['student_id'] . '_' . $activity['test_id'];
                    $answeredCount = $answerCounts[$key] ?? 0;

                    if ($totalQuestions > 0) {
                        $activity['total_questions'] = $totalQuestions;
                        $activity['answered_questions'] = $answeredCount;
                        $activity['progress_percent'] = round(($answeredCount / $totalQuestions) * 100);
                    } else {
                        $activity['total_questions'] = 0;
                        $activity['answered_questions'] = 0;
                        $activity['progress_percent'] = 0;
                    }

                    // Calculate remaining time
                    $startedAt = is_numeric($activity['started_at']) ? $activity['started_at'] : strtotime($activity['started_at']);
                    $duration = (int) ($activity['duration'] ?? 0);
                    $extraTime = (int) ($activity['extra_time'] ?? 0);
                    $totalDuration = ($duration + $extraTime) * 60; // in seconds
                    $elapsed = time() - $startedAt;
                    $remaining = max(0, $totalDuration - $elapsed);

                    $activity['remaining_minutes'] = floor($remaining / 60);
                    $activity['remaining_seconds'] = $remaining % 60;
                    $activity['time_percent'] = $totalDuration > 0 ? round((($totalDuration - $remaining) / $totalDuration) * 100) : 0;
                }

                // 4. System Info - cache for 1 hour (rarely changes)
                $systemInfo = cache_remember('dashboard_system_info', 3600, function () use ($db) {
                    return [
                        'php' => phpversion(),
                        'ci' => \CodeIgniter\CodeIgniter::CI_VERSION,
                        'db' => $db->getVersion(),
                        'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                    ];
                });

                return view('dashboard/admin', [
                    'user' => $user,
                    'role' => $role['name'] ?? 'Administrator',
                    'school' => $school,
                    'students' => $students,
                    'banks' => $banks,
                    'tests' => $tests,
                    'activeUsers' => $activeUsers,
                    'license' => $license,
                    'upcomingExams' => $upcomingExams,
                    'recentActivity' => $recentActivity,
                    'systemInfo' => $systemInfo
                ]);

            case 2: // 👨‍🏫 Guru
                $teacherModel = new TeacherModel();
                $bankModel = new CbtBankSoalModel();
                $testStatusModel = new CbtTestStatusModel();
                $sessionModel = new CbtSessionModel();

                $teacher = $teacherModel->where('user_id', $user['id'])->first();
                $teacherId = $teacher['id'] ?? null;

                $licenseModel = new LicenseModel();
                $license = cache_remember('dashboard_license', 300, function () use ($licenseModel) {
                    return $licenseModel->getActiveLicense();
                });

                // 🌐 Global Stats (Harmonization with Admin)
                $studentModel = new StudentModel();
                $globalStudents = cache_remember('dashboard_students_count', 60, function () use ($studentModel) {
                    return $studentModel->countAllResults();
                });
                $globalActiveUsers = $sessionModel->where('status', 'active')->countAllResults();

                $banks = 0;
                $tests = 0;
                $activeSessions = 0;
                $finishedSessions = 0;

                $upcomingExams = [];
                $recentActivity = [];

                if ($teacherId) {
                    $banks = $bankModel->where('teacher_id', $teacherId)->countAllResults();

                    // Join to get tests associated with teacher's banks
                    $tests = $testStatusModel->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id')
                        ->where('cbt_question_banks.teacher_id', $teacherId)
                        ->countAllResults();

                    // Join to get sessions associated with teacher's banks
                    $activeSessions = $sessionModel->join('cbt_test_status', 'cbt_test_status.id = cbt_sessions.test_id')
                        ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id')
                        ->where('cbt_question_banks.teacher_id', $teacherId)
                        ->where('cbt_sessions.status', 'active')
                        ->countAllResults();

                    $finishedSessions = $sessionModel->join('cbt_test_status', 'cbt_test_status.id = cbt_sessions.test_id')
                        ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id')
                        ->where('cbt_question_banks.teacher_id', $teacherId)
                        ->where('cbt_sessions.status', 'finished')
                        ->countAllResults();

                    // 1. Upcoming Exams (Teacher's subjects only)
                    $now = date('Y-m-d H:i:s');
                    $upcomingExams = $testStatusModel->select('cbt_test_status.*, cbt_exam_names.name as exam_name, subjects.name as subject_name')
                        ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id')
                        ->join('subjects', 'subjects.id = cbt_question_banks.subject_id', 'left')
                        ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                        ->where('cbt_question_banks.teacher_id', $teacherId)
                        ->where('cbt_test_status.is_active', 1)
                        ->where('cbt_test_status.end_time >=', $now)
                        ->orderBy('cbt_test_status.start_time', 'ASC')
                        ->limit(5)
                        ->find();

                    // 2. Recent Activity (Active Sessions - Students Currently Taking Exam)
                    // Filter: Only show students who are ACTUALLY taking exam right now
                    $recentActivity = $sessionModel->select('cbt_sessions.*, students.name as student_name, cbt_exam_names.name as exam_name, cbt_test_status.duration, cbt_test_status.start_time, cbt_test_status.end_time')
                        ->join('students', 'students.id = cbt_sessions.student_id', 'left')
                        ->join('cbt_test_status', 'cbt_test_status.id = cbt_sessions.test_id', 'left')
                        ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_test_status.bank_id', 'left')
                        ->join('cbt_exam_names', 'cbt_exam_names.id = cbt_test_status.exam_name_id', 'left')
                        ->where('cbt_question_banks.teacher_id', $teacherId)
                        ->where('cbt_sessions.status', 'active')
                        ->where('cbt_test_status.is_active', 1) // Exam must be active
                        ->where('cbt_test_status.start_time <=', $now) // Exam has started
                        ->where('cbt_test_status.end_time >=', $now) // Exam not ended yet
                        ->orderBy('cbt_sessions.started_at', 'DESC')
                        ->findAll(); // Get all active sessions, will limit in view with scroll

                    // Calculate progress for each active session
                    $db = \Config\Database::connect();

                    // Optimize: Batch query for answer counts instead of N+1 queries
                    $studentIds = array_column($recentActivity, 'student_id');
                    $testIds = array_column($recentActivity, 'test_id');

                    // Build answer count map in single query
                    $answerCounts = [];
                    if (!empty($studentIds) && !empty($testIds)) {
                        $answerResults = $db->table('cbt_answers')
                            ->select('student_id, test_id, COUNT(*) as count')
                            ->whereIn('student_id', $studentIds)
                            ->whereIn('test_id', $testIds)
                            ->groupBy(['student_id', 'test_id'])
                            ->get()
                            ->getResultArray();

                        foreach ($answerResults as $row) {
                            $key = $row['student_id'] . '_' . $row['test_id'];
                            $answerCounts[$key] = (int) $row['count'];
                        }
                    }

                    foreach ($recentActivity as &$activity) {
                        // Get question count and answered count
                        $questionOrder = json_decode($activity['question_order'] ?? '[]', true) ?? [];
                        $totalQuestions = count($questionOrder);

                        $key = $activity['student_id'] . '_' . $activity['test_id'];
                        $answeredCount = $answerCounts[$key] ?? 0;

                        if ($totalQuestions > 0) {
                            $activity['total_questions'] = $totalQuestions;
                            $activity['answered_questions'] = $answeredCount;
                            $activity['progress_percent'] = round(($answeredCount / $totalQuestions) * 100);
                        } else {
                            $activity['total_questions'] = 0;
                            $activity['answered_questions'] = 0;
                            $activity['progress_percent'] = 0;
                        }

                        // Calculate remaining time
                        $startedAt = is_numeric($activity['started_at']) ? $activity['started_at'] : strtotime($activity['started_at']);
                        $duration = (int) ($activity['duration'] ?? 0);
                        $extraTime = (int) ($activity['extra_time'] ?? 0);
                        $totalDuration = ($duration + $extraTime) * 60; // in seconds
                        $elapsed = time() - $startedAt;
                        $remaining = max(0, $totalDuration - $elapsed);

                        $activity['remaining_minutes'] = floor($remaining / 60);
                        $activity['remaining_seconds'] = $remaining % 60;
                        $activity['time_percent'] = $totalDuration > 0 ? round((($totalDuration - $remaining) / $totalDuration) * 100) : 0;
                    }
                }

                // System Info (same as admin)
                $db = \Config\Database::connect();
                $systemInfo = [
                    'php' => phpversion(),
                    'ci' => \CodeIgniter\CodeIgniter::CI_VERSION,
                    'db' => $db->getVersion(),
                    'server' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
                ];

                return view('dashboard/guru', [
                    'user' => $user,
                    'teacher' => $teacher,
                    'role' => 'Guru Mata Pelajaran',
                    'school' => (new SchoolModel())->first(), // Fetch school info
                    'banks' => $banks,
                    'tests' => $tests,
                    'activeSessions' => $activeSessions,
                    'finishedSessions' => $finishedSessions,
                    'globalStudents' => $globalStudents,
                    'globalActiveUsers' => $globalActiveUsers,
                    'upcomingExams' => $upcomingExams,
                    'recentActivity' => $recentActivity,
                    'systemInfo' => $systemInfo,
                    'license' => $license
                ]);

            case 3: // 👨‍🎓 Siswa
                return redirect()->to(base_url('siswa/cbt'));

            default:
                return redirect()->to(base_url('login'));
        }
    }
}
