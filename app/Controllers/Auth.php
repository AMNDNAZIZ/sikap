<?php

namespace App\Controllers;

use App\Models\UserModel;
use App\Filters\RateLimitFilter;
use CodeIgniter\Controller;

class Auth extends BaseController
{
    public function login()
    {
        $schoolModel = new \App\Models\SchoolModel();
        $data = [
            'school' => $schoolModel->first() ?: [],
            'title' => 'Login Sistem'
        ];
        
        // Use modern login view
        return view('auth/login_modern', $data);
    }

    public function attemptLogin()
    {
        $session = session();
        $model = new UserModel();

        $login = (string)$this->request->getPost('username');
        $password = (string)$this->request->getPost('password');
        $ip = $this->request->getIPAddress();

        $user = $model->groupStart()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->groupEnd()
            ->first();

        // 🔒 Tier 1 Check: Jika akun ditemukan, periksa counter ID Akun sebelum verifikasi password
        if ($user && RateLimitFilter::isAccountBlocked($ip, (int)$user['id'])) {
            log_message('warning', "RateLimitFilter: Controller blocked user ID {$user['id']} from IP: {$ip}");
            return RateLimitFilter::createBlockedResponse($this->request);
        }

        // Simpan pemetaan alias di cache untuk optimasi request berikutnya
        if ($user) {
            RateLimitFilter::setMappedUserId($ip, strtolower(trim($login)), (int)$user['id']);
        }

        if ($user && password_verify($password, $user['password'])) {
            // Check if account is deactivated
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                // Perlakukan sebagai kegagalan autentikasi: tambah counter, jangan pulihkan, jangan bocorkan status
                RateLimitFilter::recordFailure($ip, (int)$user['id'], $login);
                log_message('notice', "Login attempt for deactivated user ID {$user['id']} from IP {$ip}");
                return redirect()->back()->with('error', 'Username atau password salah. Silakan coba lagi.');
            }

            // Single-Device Login Enforcement for Students (role_id = 5)
            $userRoleId = (int) ($user['role_id'] ?? 0);
            if ($userRoleId === 5 && !empty($user['active_session_id'])) {
                $clientDeviceToken = $session->get('device_token');
                if ($clientDeviceToken !== $user['active_session_id']) {
                    $lastActTs = (int) ($user['last_activity'] ?? 0);
                    $isRecentlyActive = ($lastActTs > 0) && ((time() - $lastActTs) < 1800);
                    $hasActiveCbt = false;
                    if (!empty($user['related_id'])) {
                        $hasActiveCbt = (bool) db_connect()->table('cbt_sessions')
                            ->where('student_id', (int) $user['related_id'])
                            ->where('status', 'active')
                            ->countAllResults();
                    }

                    if ($isRecentlyActive || $hasActiveCbt) {
                        log_message('warning', "[SingleDevice] Blocked login for student user ID {$user['id']} from IP {$ip}");
                        return redirect()->back()->with('error', 'Akun Anda sedang aktif di perangkat lain. Silakan logout dari perangkat sebelumnya atau minta pengawas/admin melakukan Reset Login.');
                    }
                }
            }

            // Regenerate session ID to prevent session fixation
            $session->regenerate();

            $deviceToken = bin2hex(random_bytes(24));
            $nowTs = time();

            $model->update($user['id'], [
                'active_session_id' => $deviceToken,
                'last_activity'     => $nowTs,
            ]);
            
            $sessionData = [
                'id' => $user['id'],
                'username' => $user['username'],
                'role_id' => $user['role_id'],
                'related_id' => $user['related_id'],
                'must_change_password' => $user['must_change_password'] ?? 0,
            ];

            // 🔹 Jika Guru
            if ($user['role_id'] == 3) {
                $teacher = db_connect()
                    ->table('teachers')
                    ->where('user_id', $user['id'])
                    ->get()
                    ->getRowArray();

                if ($teacher) {
                    $sessionData['teacher_id'] = $teacher['id'];

                    // cari class berdasarkan teacher_id
                    $class = db_connect()
                        ->table('classes')
                        ->where('teacher_id', $teacher['id'])
                        ->get()
                        ->getRowArray();

                    if ($class) {
                        $sessionData['class_id'] = $class['id'];
                    }
                }
            }

            // 🔹 Jika Siswa atau Orang Tua
            if ($user['role_id'] == 5 || $user['role_id'] == 4) {
                $sessionData['student_id'] = $user['related_id'];
            }

            $session->set('user', $sessionData);
            $session->set('logged_in', true);
            $session->set('last_activity', $nowTs);
            $session->set('device_token', $deviceToken);

            // 📝 Catat audit trail login
            helper('audit');
            audit_log('login', 'users', $user['id']);

            // 🟢 PEMULIHAN COUNTER TIER 1 EKSPLISIT PADA KEBERHASILAN AUTENTIKASI AKTUAL
            RateLimitFilter::recordSuccess($ip, (int)$user['id'], $login);

            // 🔒 Check if user must change password
            if ($user['must_change_password'] == 1) {
                return redirect()->to('/auth/change-password-required')
                    ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
            }

            return redirect()->to('/dashboard');
        }

        // 🔴 AUTENTIKASI GAGAL: Catat kegagalan ke RateLimitFilter
        RateLimitFilter::recordFailure($ip, $user ? (int)$user['id'] : null, $login);

        return redirect()->back()->with('error', 'Username atau password salah. Silakan coba lagi.');
    }

    public function logout()
    {
        helper('audit');
        $user = session()->get('user');
        if ($user) {
            audit_log('logout');
            if (!empty($user['id'])) {
                try {
                    (new UserModel())->update((int) $user['id'], [
                        'active_session_id' => null,
                        'last_activity'     => null,
                    ]);
                } catch (\Throwable $e) {
                    log_message('error', '[Auth::logout] Clear device token failed: ' . $e->getMessage());
                }
            }
        }
        session()->destroy();
        return redirect()->to('/login');
    }

    /**
     * Ping session to keep it alive
     * Called by JavaScript keep-alive script
     */
    public function pingSession()
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Not authenticated'
            ]);
        }

        $nowTs = time();
        // Update last activity timestamp
        session()->set('last_activity', $nowTs);
        $user = session()->get('user');
        if (!empty($user['id'])) {
            try {
                db_connect()->table('users')
                    ->where('id', (int) $user['id'])
                    ->update(['last_activity' => $nowTs]);
            } catch (\Throwable $e) {
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'timestamp' => $nowTs
        ]);
    }

    public function changePasswordRequired()
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return redirect()->to('/login');
        }

        // Check if user must change password
        $user = session()->get('user');
        if (!$user || $user['must_change_password'] != 1) {
            return redirect()->to('/dashboard');
        }

        $schoolModel = new \App\Models\SchoolModel();
        $data = [
            'school' => $schoolModel->first() ?: [],
            'title' => 'Ganti Password Wajib',
            'user' => $user
        ];

        return view('auth/change_password_required', $data);
    }

    public function updatePasswordRequired()
    {
        // Check if user is logged in
        if (!session()->get('logged_in')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sesi tidak valid'
            ]);
        }

        $user = session()->get('user');
        if (!$user || $user['must_change_password'] != 1) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak'
            ]);
        }

        $rules = [
            'current_password' => 'required',
            'new_password' => 'required|min_length[6]',
            'confirm_password' => 'required|matches[new_password]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $model = new UserModel();
        $userData = $model->find($user['id']);

        // Verify current password
        if (!password_verify($this->request->getPost('current_password'), $userData['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password lama tidak sesuai'
            ]);
        }

        // Check if new password is same as old password
        if (password_verify($this->request->getPost('new_password'), $userData['password'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Password baru tidak boleh sama dengan password lama'
            ]);
        }

        // Update password
        $newPassword = password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT);
        $model->update($user['id'], [
            'password' => $newPassword,
            'must_change_password' => 0,
            'password_changed_at' => date('Y-m-d H:i:s')
        ]);

        // Update session
        $user['must_change_password'] = 0;
        session()->set('user', $user);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Password berhasil diubah. Anda akan diarahkan ke dashboard...'
        ]);
    }
}
