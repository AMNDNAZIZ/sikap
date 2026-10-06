<?php

namespace App\Models;

use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table            = 'audit_logs';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id', 
        'action', 
        'table_name', 
        'record_id', 
        'old_data', 
        'new_data', 
        'ip_address', 
        'user_agent', 
        'created_at'
    ];

    protected $useTimestamps = false; // We use created_at managed manually or by DB default

    /**
     * Helper method to insert log quickly
     */
    public function logAction($action, $tableName = null, $recordId = null, $oldData = null, $newData = null)
    {
        $session = session();
        $user = $session->get('user');
        
        $request = \Config\Services::request();

        $data = [
            'user_id'    => $user['id'] ?? null,
            'action'     => $action,
            'table_name' => $tableName,
            'record_id'  => $recordId,
            'old_data'   => $oldData ? json_encode($oldData) : null,
            'new_data'   => $newData ? json_encode($newData) : null,
            'ip_address' => $request->getIPAddress(),
            'user_agent' => $request->getUserAgent()->getAgentString(),
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->insert($data);
    }
}
