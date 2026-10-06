<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;

class AuditLog extends BaseController
{
    protected $auditModel;

    public function __construct()
    {
        $this->auditModel = new AuditLogModel();
    }

    public function index()
    {
        // Simple pagination for audit logs
        $logs = $this->auditModel
            ->select('audit_logs.*, users.username, users.fullname, users.role_id')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->orderBy('audit_logs.created_at', 'DESC')
            ->paginate(50);

        $data = [
            'title' => 'Log Aktivitas (Audit Trail)',
            'logs'  => $logs,
            'pager' => $this->auditModel->pager
        ];

        return view('admin/audit_logs/index', $data);
    }
    
    public function show($id)
    {
        $log = $this->auditModel
            ->select('audit_logs.*, users.username, users.fullname')
            ->join('users', 'users.id = audit_logs.user_id', 'left')
            ->where('audit_logs.id', $id)
            ->first();

        if (!$log) {
            return redirect()->to('/admin/audit-logs')->with('error', 'Log tidak ditemukan.');
        }

        $data = [
            'title' => 'Detail Log Aktivitas',
            'log'   => $log
        ];

        return view('admin/audit_logs/show', $data);
    }
}
