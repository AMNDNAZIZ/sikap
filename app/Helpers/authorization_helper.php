<?php

/**
 * Authorization Helper
 * 
 * Helper functions untuk role-based access control (Dynamic RBAC)
 * Permission disimpan di tabel `permissions` dan `role_permissions`.
 * Admin bisa mengatur permission setiap role melalui halaman pengaturan.
 */

if (!function_exists('has_permission')) {
    /**
     * Check if current user has permission
     * 
     * @param string $permission Permission name (e.g., 'students.view', 'cbt.manage')
     * @return bool
     */
    function has_permission(string $permission): bool
    {
        $user = session()->get('user');
        if (!$user) {
            return false;
        }

        $roleId = (int) ($user['role_id'] ?? 0);
        if ($roleId === 0) {
            return false;
        }

        // Admin (role 1) always has full access as a safety net
        if ($roleId === 1) {
            return true;
        }

        try {
            $rolePerms = \App\Models\PermissionModel::getCachedRolePermissions();
            $permsForRole = $rolePerms[$roleId] ?? [];

            // Exact match
            if (in_array($permission, $permsForRole)) {
                return true;
            }

            // Check if role has 'module.manage' which grants all actions in that module
            $parts = explode('.', $permission, 2);
            if (count($parts) === 2) {
                $module = $parts[0];
                if (in_array($module . '.manage', $permsForRole)) {
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            // Fallback jika tabel belum ada (misal saat migration belum jalan)
            log_message('warning', 'has_permission fallback: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('require_permission')) {
    /**
     * Require permission or redirect with error
     * 
     * @param string $permission Permission name
     * @param string $redirectUrl Redirect URL if no permission (default: back)
     * @return void
     */
    function require_permission(string $permission, string $redirectUrl = null): void
    {
        if (!has_permission($permission)) {
            $message = 'Anda tidak memiliki akses untuk melakukan aksi ini.';
            
            if ($redirectUrl) {
                redirect()->to($redirectUrl)->with('error', $message)->send();
            } else {
                redirect()->back()->with('error', $message)->send();
            }
            exit;
        }
    }
}

if (!function_exists('is_admin')) {
    /**
     * Check if current user is admin
     * 
     * @return bool
     */
    function is_admin(): bool
    {
        $user = session()->get('user');
        return ($user['role_id'] ?? null) == 1;
    }
}

if (!function_exists('is_teacher')) {
    /**
     * Check if current user is teacher
     * 
     * @return bool
     */
    function is_teacher(): bool
    {
        $user = session()->get('user');
        return ($user['role_id'] ?? null) == 3;
    }
}

if (!function_exists('is_student')) {
    /**
     * Check if current user is student
     * 
     * @return bool
     */
    function is_student(): bool
    {
        $user = session()->get('user');
        return ($user['role_id'] ?? null) == 5;
    }
}

if (!function_exists('is_parent')) {
    /**
     * Check if current user is parent
     * 
     * @return bool
     */
    function is_parent(): bool
    {
        $user = session()->get('user');
        return ($user['role_id'] ?? null) == 4;
    }
}

if (!function_exists('is_kepsek')) {
    /**
     * Check if current user is kepala sekolah
     * 
     * @return bool
     */
    function is_kepsek(): bool
    {
        $user = session()->get('user');
        return ($user['role_id'] ?? null) == 2;
    }
}

if (!function_exists('can_access_student')) {
    /**
     * Check if current user can access specific student data
     * 
     * @param int $studentId Student ID to check
     * @return bool
     */
    function can_access_student(int $studentId): bool
    {
        $user = session()->get('user');
        if (!$user) {
            return false;
        }

        $roleId = $user['role_id'] ?? null;
        
        // Admin and Kepsek can access all students
        if ($roleId == 1 || $roleId == 2) {
            return true;
        }
        
        // Teacher can access students in their class
        if ($roleId == 3) {
            $teacherId = $user['teacher_id'] ?? $user['related_id'] ?? null;
            if (!$teacherId) {
                return false;
            }
            
            // Check if student is in teacher's class
            $db = \Config\Database::connect();
            $class = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRowArray();
            
            if (!$class) {
                return false;
            }
            
            $studentRecord = $db->table('student_records')
                ->where('student_id', $studentId)
                ->where('class_id', $class['id'])
                ->where('status', 'aktif')
                ->get()
                ->getRowArray();
            
            return !empty($studentRecord);
        }
        
        // Parent can only access their own child
        if ($roleId == 4) {
            return ($user['related_id'] ?? null) == $studentId;
        }
        
        // Student can only access their own data
        if ($roleId == 5) {
            return ($user['related_id'] ?? null) == $studentId;
        }
        
        return false;
    }
}

if (!function_exists('is_school_swasta')) {
    /**
     * Check if the school status is "swasta" (private)
     * Useful to conditionally enable/disable modules exclusive to private schools.
     * 
     * @return bool
     */
    function is_school_swasta(): bool
    {
        $cache = cache();
        $cached = $cache->get('school_status');
        
        if ($cached !== null) {
            return $cached === 'swasta';
        }

        $db = \Config\Database::connect();
        $school = $db->table('school_profile')
            ->select('status')
            ->get()
            ->getRowArray();

        $status = $school['status'] ?? null;
        $cache->save('school_status', $status, 3600); // cache 1 hour

        return $status === 'swasta';
    }
}

if (!function_exists('is_school_negeri')) {
    /**
     * Check if the school status is "negeri" (public)
     * 
     * @return bool
     */
    function is_school_negeri(): bool
    {
        $cache = cache();
        $cached = $cache->get('school_status');
        
        if ($cached !== null) {
            return $cached === 'negeri';
        }

        $db = \Config\Database::connect();
        $school = $db->table('school_profile')
            ->select('status')
            ->get()
            ->getRowArray();

        $status = $school['status'] ?? null;
        $cache->save('school_status', $status, 3600);

        return $status === 'negeri';
    }
}

if (!function_exists('can_access_class')) {
    /**
     * Check if current user can access specific class data
     * 
     * @param int $classId Class ID to check
     * @return bool
     */
    function can_access_class(int $classId): bool
    {
        $user = session()->get('user');
        if (!$user) {
            return false;
        }

        $roleId = $user['role_id'] ?? null;
        
        // Admin and Kepsek can access all classes
        if ($roleId == 1 || $roleId == 2) {
            return true;
        }
        
        // Teacher can access their own class
        if ($roleId == 3) {
            $teacherId = $user['teacher_id'] ?? $user['related_id'] ?? null;
            if (!$teacherId) {
                return false;
            }
            
            $db = \Config\Database::connect();
            $class = $db->table('classes')
                ->where('id', $classId)
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRowArray();
            
            return !empty($class);
        }
        
        return false;
    }
}
