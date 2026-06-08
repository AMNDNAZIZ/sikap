<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Profile extends BaseController
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    public function index()
    {
        $userSession = session()->get('user');
        $user = $this->userModel->find($userSession['id']);
        return view('profile/index', ['user' => $user]);
    }

    public function updatePassword()
    {
        $user = session()->get('user');
        $id   = $user['id'];

        $newPassword     = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');

        if ($newPassword !== $confirmPassword) {
            return redirect()->back()->with('error', 'Password baru dan konfirmasi tidak cocok.');
        }

        $this->userModel->update($id, [
            'password' => password_hash($newPassword, PASSWORD_BCRYPT)
        ]);

        return redirect()->to('/profile')->with('success', 'Password berhasil diperbarui.');
    }
}
