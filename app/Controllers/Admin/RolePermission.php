<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\PermissionModel;

class RolePermission extends BaseController
{
    protected $permModel;

    public function __construct()
    {
        $this->permModel = new PermissionModel();
    }

    /**
     * Halaman utama — daftar semua role dengan tombol atur permission
     */
    public function index()
    {
        $db = db_connect();
        $roles = $db->table('roles')->orderBy('id', 'ASC')->get()->getResultArray();

        $data = [
            'roles' => $roles,
        ];

        return view('admin/role_permission/index', $data);
    }

    /**
     * Halaman edit permission untuk role tertentu
     */
    public function edit(int $roleId)
    {
        $db = db_connect();
        $role = $db->table('roles')->where('id', $roleId)->get()->getRowArray();

        if (!$role) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role tidak ditemukan.');
        }

        // Admin tidak bisa diedit — selalu punya semua permission
        if ($roleId === 1) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role Admin memiliki akses penuh dan tidak dapat diubah.');
        }

        $grouped = $this->permModel->getGrouped();
        $assigned = $this->permModel->getByRole($roleId);
        $assignedIds = array_column($assigned, 'permission_id');

        $data = [
            'role'        => $role,
            'grouped'     => $grouped,
            'assignedIds' => $assignedIds,
        ];

        return view('admin/role_permission/edit', $data);
    }

    /**
     * Simpan perubahan permission untuk sebuah role
     */
    public function update(int $roleId)
    {
        $db = db_connect();
        $role = $db->table('roles')->where('id', $roleId)->get()->getRowArray();

        if (!$role || $roleId === 1) {
            return redirect()->to('/admin/role-permission')->with('error', 'Tidak dapat mengubah role ini.');
        }

        $permissionIds = $this->request->getPost('permissions') ?? [];
        $permissionIds = array_map('intval', $permissionIds);

        $this->permModel->syncRolePermissions($roleId, $permissionIds);

        return redirect()->to('/admin/role-permission')->with('success', 'Permission untuk role "' . esc($role['name']) . '" berhasil disimpan.');
    }

    /**
     * Tambah role baru
     */
    public function createRole()
    {
        $name = trim($this->request->getPost('name'));

        if (empty($name)) {
            return redirect()->to('/admin/role-permission')->with('error', 'Nama role tidak boleh kosong.');
        }

        $db = db_connect();

        // Cek duplikat
        $exists = $db->table('roles')->where('name', $name)->get()->getRowArray();
        if ($exists) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role "' . esc($name) . '" sudah ada.');
        }

        $db->table('roles')->insert(['name' => $name]);

        return redirect()->to('/admin/role-permission')->with('success', 'Role "' . esc($name) . '" berhasil ditambahkan.');
    }

    /**
     * Hapus role (hanya jika tidak ada user yang menggunakannya)
     */
    public function deleteRole(int $roleId)
    {
        // Proteksi role bawaan (1-7)
        if ($roleId <= 7) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role bawaan sistem tidak dapat dihapus.');
        }

        $db = db_connect();
        $role = $db->table('roles')->where('id', $roleId)->get()->getRowArray();

        if (!$role) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role tidak ditemukan.');
        }

        // Cek apakah ada user yang menggunakan role ini
        $userCount = $db->table('users')->where('role_id', $roleId)->countAllResults();
        if ($userCount > 0) {
            return redirect()->to('/admin/role-permission')->with('error', 'Role "' . esc($role['name']) . '" masih digunakan oleh ' . $userCount . ' user. Pindahkan user ke role lain terlebih dahulu.');
        }

        // Hapus role_permissions dulu, lalu role
        $db->table('role_permissions')->where('role_id', $roleId)->delete();
        $db->table('roles')->where('id', $roleId)->delete();

        // Clear cache
        cache()->delete('all_role_permissions');

        return redirect()->to('/admin/role-permission')->with('success', 'Role "' . esc($role['name']) . '" berhasil dihapus.');
    }
}
