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
                ->orLike('users.email', $keyword)
                ->orLike('roles.name', $keyword)
            ->groupEnd();
        }

        $data['users'] = $builder
            ->orderBy('users.id', 'DESC')
            ->paginate(20, 'users'); // 10 per halaman

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
            'email'    => $data['email'],
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
            'email'    => $data['email'],
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

    /**
     * AJAX: Get session stats for dashboard widget
     */
    public function sessionInfo()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $db = db_connect();
        $expiration = config('Session')->expiration ?: 7200;
        $cutoff = time() - $expiration;

        $total    = $db->table('ci_sessions')->countAllResults();
        $expired  = $db->table('ci_sessions')->where('timestamp <', $cutoff)->countAllResults();
        $active   = $total - $expired;

        // Estimate size in KB
        $sizeRow = $db->query("SELECT ROUND(SUM(LENGTH(data)) / 1024, 1) as size_kb FROM ci_sessions")->getRowArray();
        $sizeKb  = $sizeRow['size_kb'] ?? 0;

        return $this->response->setJSON([
            'total'   => $total,
            'active'  => $active,
            'expired' => $expired,
            'size_kb' => $sizeKb,
            'size_label' => $sizeKb >= 1024
                ? round($sizeKb / 1024, 2) . ' MB'
                : $sizeKb . ' KB',
        ]);
    }

    /**
     * AJAX: Clean expired sessions
     */
    public function sessionClean()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(403);
        }

        $db = db_connect();
        $expiration = config('Session')->expiration ?: 7200;
        $cutoff = time() - $expiration;

        $count = $db->table('ci_sessions')->where('timestamp <', $cutoff)->countAllResults();
        $db->table('ci_sessions')->where('timestamp <', $cutoff)->delete();

        log_message('info', "Dashboard sessionClean: {$count} expired sessions deleted by user " . session()->get('user')['id']);

        return $this->response->setJSON([
            'success' => true,
            'deleted' => $count,
            'message' => $count > 0
                ? "{$count} session expired berhasil dihapus."
                : "Tidak ada session expired yang perlu dihapus.",
            'csrf_hash' => csrf_hash(),
        ]);
    }

}
