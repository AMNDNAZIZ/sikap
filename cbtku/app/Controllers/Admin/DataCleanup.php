<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\CbtTestStatusModel;
use App\Models\CbtSessionModel;
use App\Models\CbtStudentSessionModel;
use App\Models\CbtAnswerModel;

class DataCleanup extends BaseController
{
    protected $studentModel;
    protected $testStatusModel;
    protected $sessionModel;
    protected $studentSessionModel;
    protected $answerModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->testStatusModel = new CbtTestStatusModel();
        $this->sessionModel = new CbtSessionModel();
        $this->studentSessionModel = new CbtStudentSessionModel();
        $this->answerModel = new CbtAnswerModel();
    }

    public function index()
    {
        // Check if user is admin (role_id = 1)
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
        }

        // Get statistics (with error handling for missing tables)
        $stats = [
            'students' => 0,
            'test_status' => 0,
            'sessions' => 0,
            'answers' => 0,
            'cheat_logs' => 0
        ];

        $db = \Config\Database::connect();

        try {
            $stats['students'] = $this->studentModel->countAll();
        } catch (\Exception $e) {
            log_message('warning', 'Failed to count students: ' . $e->getMessage());
        }

        try {
            $stats['test_status'] = $this->testStatusModel->countAll();
        } catch (\Exception $e) {
            log_message('warning', 'Failed to count test status: ' . $e->getMessage());
        }

        try {
            $stats['sessions'] = $this->sessionModel->countAll();
        } catch (\Exception $e) {
            log_message('warning', 'Failed to count sessions: ' . $e->getMessage());
        }

        try {
            $stats['answers'] = $this->answerModel->countAll();
        } catch (\Exception $e) {
            log_message('warning', 'Failed to count answers: ' . $e->getMessage());
        }

        // Check if cbt_cheat_logs exists and count it
        try {
            $query = $db->query("SHOW TABLES LIKE 'cbt_cheat_logs'");
            if ($query->getRow()) {
                $query = $db->query("SELECT COUNT(*) as total FROM cbt_cheat_logs");
                $stats['cheat_logs'] = $query->getRow()->total;
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to count cheat logs: ' . $e->getMessage());
        }

        $data = [
            'title' => 'Pembersihan Data',
            'stats' => $stats
        ];

        return view('admin/data_cleanup/index', $data);
    }

    /**
     * Clean student data
     */
    public function cleanStudents()
    {
        // Check if user is admin (role_id = 1)
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat melakukan operasi ini.'
            ]);
        }

        // Verify confirmation
        $confirmation = $this->request->getPost('confirmation');
        if ($confirmation !== 'HAPUS SEMUA DATA SISWA') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Konfirmasi tidak sesuai. Ketik: HAPUS SEMUA DATA SISWA'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get count before delete
            $studentCount = $this->studentModel->countAll();

            // Delete related data first
            // 1. Delete student answers
            $db->query("DELETE FROM cbt_answers WHERE student_id IN (SELECT id FROM students)");
            
            // 2. Delete student sessions and cheat logs
            $db->query("DELETE FROM cbt_sessions WHERE student_id IN (SELECT id FROM students)");
            
            try {
                $query = $db->query("SHOW TABLES LIKE 'cbt_cheat_logs'");
                if ($query->getRow()) {
                     $db->query("DELETE FROM cbt_cheat_logs WHERE student_id IN (SELECT id FROM students)");
                }
            } catch (\Exception $e) {
                log_message('warning', 'cbt_cheat_logs cleanup failed for students: ' . $e->getMessage());
            }
            
            // 3. Delete associated user accounts for students
            // role_id = 3 is for students, or related_type = 'student'
            $db->query("DELETE FROM users WHERE role_id = 3 OR related_type IN ('student', 'siswa')");
            
            // 4. Delete students
            $this->studentModel->truncate();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus data siswa. Terjadi kesalahan database.'
                ]);
            }

            // Log activity
            log_message('info', 'Admin ' . session()->get('username') . ' cleaned student data. Total: ' . $studentCount);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Berhasil menghapus ' . $studentCount . ' data siswa beserta data terkait (sesi dan jawaban).',
                'deleted_count' => $studentCount
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to clean student data: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus data siswa: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Clean test status data (including related sessions and answers)
     */
    public function cleanTestStatus()
    {
        // Check if user is admin (role_id = 1)
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat melakukan operasi ini.'
            ]);
        }

        // Verify confirmation
        $confirmation = $this->request->getPost('confirmation');
        if ($confirmation !== 'HAPUS SEMUA TES STATUS') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Konfirmasi tidak sesuai. Ketik: HAPUS SEMUA TES STATUS'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get counts before delete
            $testStatusCount = $this->testStatusModel->countAll();
            $sessionCount = $this->sessionModel->countAll();
            $answerCount = $this->answerModel->countAll();



            // Delete in correct order (child tables first)
            // 1. Delete answers and cheat logs
            $db->query("DELETE FROM cbt_answers WHERE test_id IN (SELECT id FROM cbt_test_status)");
            
            try {
                $query = $db->query("SHOW TABLES LIKE 'cbt_cheat_logs'");
                if ($query->getRow()) {
                    $db->query("DELETE FROM cbt_cheat_logs WHERE test_id IN (SELECT id FROM cbt_test_status)");
                }
            } catch (\Exception $e) {
                log_message('warning', 'cbt_cheat_logs cleanup failed: ' . $e->getMessage());
            }
            
            // 3. Delete sessions
            $db->query("DELETE FROM cbt_sessions WHERE test_id IN (SELECT id FROM cbt_test_status)");
            
            // 4. Delete test status
            $this->testStatusModel->truncate();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus data tes status. Terjadi kesalahan database.'
                ]);
            }

            // Log activity
            log_message('info', 'Admin ' . session()->get('username') . ' cleaned test status data. Tests: ' . $testStatusCount . ', Sessions: ' . $sessionCount . ', Answers: ' . $answerCount);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Berhasil menghapus data tes status beserta data terkait.',
                'deleted' => [
                    'test_status' => $testStatusCount,
                    'sessions' => $sessionCount,
                    'answers' => $answerCount
                ]
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to clean test status data: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus data tes status: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Clean all data (students + test status)
     */
    public function cleanAll()
    {
        // Check if user is admin (role_id = 1)
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak. Hanya admin yang dapat melakukan operasi ini.'
            ]);
        }

        // Verify confirmation
        $confirmation = $this->request->getPost('confirmation');
        if ($confirmation !== 'HAPUS SEMUA DATA') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Konfirmasi tidak sesuai. Ketik: HAPUS SEMUA DATA'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Get counts before delete
            $stats = [
                'students' => $this->studentModel->countAll(),
                'test_status' => $this->testStatusModel->countAll(),
                'sessions' => $this->sessionModel->countAll(),
                'answers' => $this->answerModel->countAll()
            ];



            // Delete in correct order
            // 1. Delete answers, cheat logs, sessions
            $this->answerModel->truncate();
            
            try {
                $query = $db->query("SHOW TABLES LIKE 'cbt_cheat_logs'");
                if ($query->getRow()) {
                    $db->query("TRUNCATE TABLE cbt_cheat_logs");
                }
            } catch (\Exception $e) {
                log_message('warning', 'cbt_cheat_logs truncate failed: ' . $e->getMessage());
            }
            
            // 2. Delete sessions
            $this->sessionModel->truncate();
            
            // 4. Delete test status
            $this->testStatusModel->truncate();
            
            // 4.5. Delete associated user accounts for students
            $db->query("DELETE FROM users WHERE role_id = 3 OR related_type IN ('student', 'siswa')");

            // 5. Delete students
            $this->studentModel->truncate();

            $db->transComplete();

            if ($db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus semua data. Terjadi kesalahan database.'
                ]);
            }

            // Log activity
            log_message('info', 'Admin ' . session()->get('username') . ' cleaned all data. Stats: ' . json_encode($stats));

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Berhasil menghapus semua data.',
                'deleted' => $stats
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Failed to clean all data: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus semua data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get current statistics
     */
    public function getStats()
    {
        // Check if user is admin (role_id = 1)
        $user = session()->get('user');
        if (!$user || $user['role_id'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak.'
            ]);
        }

        $stats = [
            'students' => 0,
            'test_status' => 0,
            'sessions' => 0,
            'answers' => 0,
            'cheat_logs' => 0
        ];

        $db = \Config\Database::connect();

        try {
            $stats['students'] = $this->studentModel->countAll();
        } catch (\Exception $e) {}

        try {
            $stats['test_status'] = $this->testStatusModel->countAll();
        } catch (\Exception $e) {}

        try {
            $stats['sessions'] = $this->sessionModel->countAll();
        } catch (\Exception $e) {}

        try {
            $stats['answers'] = $this->answerModel->countAll();
        } catch (\Exception $e) {}

        // Check if cbt_cheat_logs exists and count it
        try {
            $query = $db->query("SHOW TABLES LIKE 'cbt_cheat_logs'");
            if ($query->getRow()) {
                $query = $db->query("SELECT COUNT(*) as total FROM cbt_cheat_logs");
                $stats['cheat_logs'] = $query->getRow()->total;
            }
        } catch (\Exception $e) {}

        return $this->response->setJSON([
            'success' => true,
            'stats' => $stats
        ]);
    }
}
