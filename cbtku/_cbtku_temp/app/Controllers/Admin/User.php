<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\RoleModel;

class User extends BaseController
{
    protected $userModel;
    protected $roleModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->roleModel = new RoleModel();
    }

    public function index()
    {
        $keyword = $this->request->getGet('keyword');
        $page    = (int) ($this->request->getGet('page') ?? 1);

        $builder = $this->userModel
            ->select('users.*, roles.name as role_name')
            ->join('roles', 'roles.id = users.role_id', 'left');

        if (!empty($keyword)) {
            $builder->groupStart()
                ->like('users.username', $keyword)
                ->orLike('users.fullname', $keyword)
                ->orLike('roles.name', $keyword)
            ->groupEnd();
        }

        $data['users'] = $builder
            ->orderBy('users.id', 'DESC')
            ->paginate(20, 'users'); // 20 per halaman

        $data['pager']   = $this->userModel->pager;
        $data['keyword'] = $keyword;

        return view('admin/users/index', $data);
    }


    public function create()
    {
        $roles = $this->roleModel->findAll();
        $roleOptions = [];
        foreach ($roles as $r) {
            $roleOptions[$r['id']] = $r['name'];
        }

        return view('admin/users/create', [
            'roles' => $roleOptions
        ]);
    }

    public function store()
    {
        $data = $this->request->getPost();

        $this->userModel->save([
            'username' => $data['username'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'fullname' => $data['fullname'],
            'role_id'  => $data['role_id'],
        ]);

        return redirect()->to('/admin/users')->with('success', 'User berhasil ditambahkan');
    }

    public function edit($id)
    {
        $user = $this->userModel->find($id);
        if (!$user) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("User dengan ID $id tidak ditemukan");
        }

        $roles = $this->roleModel->findAll();
        $roleOptions = [];
        foreach ($roles as $r) {
            $roleOptions[$r['id']] = $r['name'];
        }

        return view('admin/users/edit', [
            'user'  => $user,
            'roles' => $roleOptions
        ]);
    }

    public function update($id)
    {
        $data = $this->request->getPost();
        $updateData = [
            'username' => $data['username'],
            'fullname' => $data['fullname'],
            'role_id'  => $data['role_id'],
        ];

        if (!empty($data['password'])) {
            $updateData['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $this->userModel->update($id, $updateData);

        return redirect()->to('/admin/users')->with('success', 'User berhasil diperbarui');
    }

    public function delete($id)
    {
        $this->userModel->delete($id);

        return redirect()->to('/admin/users')->with('success', 'User berhasil dihapus');
    }

    public function resetPassword($id)
    {
        $this->userModel->update($id, [
            'password' => password_hash('123456', PASSWORD_BCRYPT)
        ]);

        return redirect()->to('/admin/users')->with('success', 'Password user berhasil direset ke 123456');
    }

}
