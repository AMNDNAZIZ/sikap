<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table = 'permissions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['module', 'action', 'label', 'group_name', 'sort_order'];

    /**
     * Get all permissions grouped by group_name
     */
    public function getGrouped(): array
    {
        $all = $this->orderBy('sort_order', 'ASC')->findAll();
        $grouped = [];
        foreach ($all as $perm) {
            $group = $perm['group_name'] ?? 'Lainnya';
            $grouped[$group][] = $perm;
        }
        return $grouped;
    }

    /**
     * Get permission IDs assigned to a role
     */
    public function getByRole(int $roleId): array
    {
        return db_connect()->table('role_permissions')
            ->where('role_id', $roleId)
            ->get()
            ->getResultArray();
    }

    /**
     * Sync permissions for a role (replace all)
     */
    public function syncRolePermissions(int $roleId, array $permissionIds): void
    {
        $db = db_connect();
        $db->table('role_permissions')->where('role_id', $roleId)->delete();

        if (!empty($permissionIds)) {
            $batch = [];
            foreach ($permissionIds as $permId) {
                $batch[] = [
                    'role_id'       => $roleId,
                    'permission_id' => (int) $permId,
                ];
            }
            $db->table('role_permissions')->insertBatch($batch);
        }

        // Clear cache
        cache()->delete('role_permissions_' . $roleId);
        cache()->delete('all_role_permissions');
    }

    /**
     * Get all role permissions as a cached map: [role_id => ['module.action', ...]]
     */
    public static function getCachedRolePermissions(): array
    {
        $cached = cache()->get('all_role_permissions');
        if ($cached !== null) {
            return $cached;
        }

        $db = db_connect();
        $results = $db->table('role_permissions rp')
            ->select('rp.role_id, p.module, p.action')
            ->join('permissions p', 'p.id = rp.permission_id')
            ->get()
            ->getResultArray();

        $map = [];
        foreach ($results as $row) {
            $map[$row['role_id']][] = $row['module'] . '.' . $row['action'];
        }

        cache()->save('all_role_permissions', $map, 3600); // cache 1 jam
        return $map;
    }
}
