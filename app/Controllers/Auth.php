<?php

namespace App\Controllers;

use App\Models\UserModel;
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

        $login = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // ✅ Rate limiting - Check login attempts
        $ip = $this->request->getIPAddress();
        // Sanitize IP address for cache key (remove invalid characters)
        $sanitizedIp = str_replace([':', '.', '/'], '_', $ip);
        $cacheKey = "login_attempts_{$sanitizedIp}";
        $lockoutKey = "login_lockout_{$sanitizedIp}";
        
        $attempts = cache()->get($cacheKey) ?? 0;
        $lockoutTime = cache()->get($lockoutKey);
        
        // Check if IP is locked out
        if ($lockoutTime && time() < $lockoutTime) {
            $remainingTime = ceil(($lockoutTime - time()) / 60);
            return redirect()->back()->with('error', "Terlalu banyak percobaan login. Silakan coba lagi dalam {$remainingTime} menit.");
        }

        $user = $model->groupStart()
            ->where('username', $login)
            ->orWhere('email', $login)
            ->groupEnd()
            ->first();

        if ($user && password_verify($password, $user['password'])) {
            // ✅ Check if account is deactivated
            if (isset($user['is_active']) && $user['is_active'] == 0) {
                return redirect()->back()->with('error', 'Akun Anda telah dinonaktifkan. Hubungi administrator sekolah.');
            }

            // ✅ Clear login attempts on successful login
            cache()->delete($cacheKey);
            cache()->delete($lockoutKey);
            
            // ✅ Regenerate session ID to prevent session fixation
            $session->regenerate();
            
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
                        $sessionData['class_id'] = $class['id']; // simpan ke session
                    }
                }
            }

            // 🔹 Jika Siswa atau Orang Tua
            if ($user['role_id'] == 5 || $user['role_id'] == 4) {
                $sessionData['student_id'] = $user['related_id'];
            }

            $session->set('user', $sessionData);
            $session->set('logged_in', true);
            // ✅ Set last activity timestamp for session timeout
            $session->set('last_activity', time());

            // 🔒 Check if user must change password
            if ($user['must_change_password'] == 1) {
                return redirect()->to('/auth/change-password-required')
                    ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
            }

            return redirect()->to('/dashboard');
        }

        // ✅ Increment login attempts on failed login
        $attempts++;
        cache()->save($cacheKey, $attempts, 900); // 15 minutes
        
        // ✅ Lock out after 5 failed attempts
        if ($attempts >= 5) {
            cache()->save($lockoutKey, time() + 900, 900); // Lock for 15 minutes
            return redirect()->back()->with('error', 'Terlalu banyak percobaan login gagal. Akun Anda dikunci selama 15 menit.');
        }
        
        $remainingAttempts = 5 - $attempts;
        return redirect()->back()->with('error', "Username atau password salah. Sisa percobaan: {$remainingAttempts}");
    }

    public function logout()
    {
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

        // Update last activity timestamp
        session()->set('last_activity', time());

        return $this->response->setJSON([
            'success' => true,
            'timestamp' => time()
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
