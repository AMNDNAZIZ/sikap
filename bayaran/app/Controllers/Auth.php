<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends Controller
{
    public function doLogin()
    {
        $session = session();
        $model = new \App\Models\UserModel();

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        $user = $model->where('username', $username)
                      ->where('aktif', 1)
                      ->first();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return redirect()->to('/login')->with('error', 'Username atau password salah');
        }

        $session->set([
            'user_id'   => $user['id'],
            'username'  => $user['username'],
            'role'      => $user['role'],
            'logged_in' => true,
        ]);

        return redirect()->to('/admin/dashboard');
    }

    public function login()
    {
        $setting = model('SettingModel')->first();

        return view('auth/login', [
            'logo'          => $setting['logo'] ?? 'default-logo.png',
            'app_name'      => $setting['nama_aplikasi'] ?? 'Aplikasi',
            'school_name'   => $setting['nama_sekolah'] ?? 'Sekolah',
        ]);
    }



    public function logout()
    {
        session()->destroy();
        return redirect()->to('/login');
    }
}
