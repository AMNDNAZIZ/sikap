<?php

/**
 * Authorization Helper
 * 
 * Helper functions untuk role-based access control
 */

if (!function_exists('has_permission')) {
    /**
     * Check if current user has permission
     * 
     * @param string $permission Permission name (e.g., 'students.update', 'cbt.delete')
     * @return bool
     */
    function has_permission(string $permission): bool
    {
        $user = session()->get('user');
        if (!$user) {
            return false;
        }

        $roleId = $user['role_id'] ?? null;
        
        // Permission mapping
        $permissions = [
            // Admin (role_id = 1) - Full access
            1 => [
                'students.*',
                'teachers.*',
                'classes.*',
                'subjects.*',
                'schedules.*',
                'attendance.*',
                'grades.*',
                'cbt.*',
                'users.*',
                'school.*',
                'academic_year.*',
            ],
            
            // Kepala Sekolah (role_id = 2) - Read-only access
            2 => [
                'students.view',
                'teachers.view',
                'classes.view',
                'subjects.view',
                'schedules.view',
                'attendance.view',
                'grades.view',
                'cbt.view',
                'reports.*',
            ],
            
            // Guru (role_id = 3) - Limited access
            3 => [
                'students.view',
                'attendance.create',
                'attendance.update',
                'grades.create',
                'grades.update',
                'schedules.view',
                'cbt.view',
                'cbt.create',
                'cbt.update',
                'materials.create',
                'materials.update',
                'materials.delete',
            ],
            
            // Orang Tua (role_id = 4) - View only their child
            4 => [
                'students.view_own',
                'grades.view_own',
                'attendance.view_own',
            ],
            
            // Siswa (role_id = 5) - View only their own data
            5 => [
                'grades.view_own',
                'attendance.view_own',
                'cbt.take',
            ],
            
            // Staf / Tata Usaha (role_id = 7) - Full access to student & teacher management
            7 => [
                'students.*',
                'teachers.*',
                'classes.*',
                'subjects.*',
                'schedules.view',
                'attendance.view',
                'grades.view',
                'cbt.view',
                'school.*',
                'academic_year.*',
                'reports.*',
            ],
        ];

        $rolePermissions = $permissions[$roleId] ?? [];
        
        // Check exact match
        if (in_array($permission, $rolePermissions)) {
            return true;
        }
        
        // Check wildcard match (e.g., 'students.*' matches 'students.update')
        foreach ($rolePermissions as $rolePermission) {
            if (str_ends_with($rolePermission, '.*')) {
                $prefix = substr($rolePermission, 0, -2);
                if (str_starts_with($permission, $prefix . '.')) {
                    return true;
                }
            }
        }
        
        return false;
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
