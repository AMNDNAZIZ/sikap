<?php

namespace App\Controllers;

use App\Models\UserModel;

class User extends BaseController
{
    public function index()
    {
        $users = (new UserModel())->findAll();

        return view('admin/user/index', [
            'title' => 'Manajemen User',
            'users' => $users
        ]);
    }

    public function save()
    {
        $rules = [
            'username' => 'required|min_length[3]|is_unique[users.username]',
            'password_hash' => 'required|min_length[4]',
            'nama' => 'required',
            'role' => 'required|in_list[superadmin,admin,operator,staf]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        (new UserModel())->save($this->request->getPost());

        return redirect()->to('/admin/user')->with('msg', 'User berhasil ditambahkan.');
    }

    public function edit($id)
    {
        $user = (new UserModel())->find($id);
        if (!$user) return redirect()->back()->with('msg', 'User tidak ditemukan.');

        return view('admin/user/edit', [
            'title' => 'Edit User',
            'user' => $user
        ]);
    }

    public function update($id)
    {
        $data = $this->request->getPost();

        $rules = [
            'nama' => 'required',
            'role' => 'required|in_list[superadmin,admin,operator,staf]',
        ];

        if (!empty($data['password_hash'])) {
            $rules['password_hash'] = 'min_length[4]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $data['id'] = $id;

        (new UserModel())->save($data);

        return redirect()->to('/admin/user')->with('msg', 'User berhasil diperbarui.');
    }

    public function delete($id)
    {
        (new UserModel())->delete($id);
        return redirect()->back()->with('msg', 'User berhasil dihapus.');
    }
}
