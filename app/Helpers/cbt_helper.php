<?php

if (!function_exists('get_cbt_user_context')) {
    /**
     * Get user context for CBT access control
     * 
     * @return array ['role_id' => int, 'user_id' => int, 'teacher_id' => int|null, 'is_admin' => bool, 'is_teacher' => bool]
     */
    function get_cbt_user_context(): array
    {
        $user = session()->get('user');
        
        if (!$user) {
            return [
                'role_id' => 0,
                'user_id' => 0,
                'teacher_id' => null,
                'is_admin' => false,
                'is_teacher' => false,
                'is_headmaster' => false
            ];
        }
        
        $roleId = (int)($user['role_id'] ?? 0);
        $userId = (int)($user['id'] ?? 0);
        $teacherId = null;
        
        // Coba ambil teacher_id dari berbagai kemungkinan field
        if ($roleId === 3) {
            // Prioritas 1: related_id dengan related_type = 'teacher'
            if (!empty($user['related_id']) && ($user['related_type'] ?? '') === 'teacher') {
                $teacherId = (int)$user['related_id'];
                log_message('debug', "CBT Context: teacher_id from related_id = {$teacherId}");
            }
            // Prioritas 2: field teacher_id langsung
            elseif (!empty($user['teacher_id'])) {
                $teacherId = (int)$user['teacher_id'];
                log_message('debug', "CBT Context: teacher_id from teacher_id field = {$teacherId}");
            }
            // Prioritas 3: query database berdasarkan user_id
            else {
                log_message('debug', "CBT Context: teacher_id not in session, querying database for user_id = {$userId}");
                $db = \Config\Database::connect();
                $userRow = $db->table('users')
                    ->select('related_id, related_type, teacher_id')
                    ->where('id', $userId)
                    ->get()
                    ->getRowArray();
                
                if ($userRow) {
                    log_message('debug', "CBT Context: DB result = " . json_encode($userRow));
                    if (!empty($userRow['related_id']) && ($userRow['related_type'] ?? '') === 'teacher') {
                        $teacherId = (int)$userRow['related_id'];
                        log_message('debug', "CBT Context: teacher_id from DB related_id = {$teacherId}");
                    } elseif (!empty($userRow['teacher_id'])) {
                        $teacherId = (int)$userRow['teacher_id'];
                        log_message('debug', "CBT Context: teacher_id from DB teacher_id field = {$teacherId}");
                    }
                } else {
                    log_message('warning', "CBT Context: No user found in DB for user_id = {$userId}");
                }
            }
        }
        
        return [
            'role_id' => $roleId,
            'user_id' => $userId,
            'teacher_id' => $teacherId,
            'is_admin' => $roleId === 1,
            'is_teacher' => $roleId === 3,
            'is_headmaster' => $roleId === 2
        ];
    }
}

if (!function_exists('can_access_cbt_bank')) {
    /**
     * Check if user can access a specific bank soal
     * 
     * @param int $bankId
     * @return bool
     */
    function can_access_cbt_bank(int $bankId): bool
    {
        $context = get_cbt_user_context();
        
        // Admin can access all
        if ($context['is_admin']) {
            return true;
        }
        
        // Teacher can only access their own
        if ($context['is_teacher'] && $context['teacher_id']) {
            $db = \Config\Database::connect();
            $bank = $db->table('cbt_question_banks')
                ->select('teacher_id')
                ->where('id', $bankId)
                ->get()
                ->getRow();
            
            return $bank && (int)$bank->teacher_id === $context['teacher_id'];
        }
        
        return false;
    }
}

if (!function_exists('can_access_cbt_test_status')) {
    /**
     * Check if user can access a specific test status
     * 
     * @param int $testStatusId
     * @return bool
     */
    function can_access_cbt_test_status(int $testStatusId): bool
    {
        $context = get_cbt_user_context();
        
        // Admin can access all
        if ($context['is_admin']) {
            return true;
        }
        
        // Teacher can only access their own
        if ($context['is_teacher'] && $context['user_id']) {
            $db = \Config\Database::connect();
            $testStatus = $db->table('cbt_test_status')
                ->select('created_by')
                ->where('id', $testStatusId)
                ->get()
                ->getRow();
            
            return $testStatus && (int)$testStatus->created_by === $context['user_id'];
        }
        
        return false;
    }
}

if (!function_exists('can_access_cbt_exam_name')) {
    /**
     * Check if user can access a specific exam name
     * 
     * @param int $examNameId
     * @return bool
     */
    function can_access_cbt_exam_name(int $examNameId): bool
    {
        $context = get_cbt_user_context();
        
        // Admin can access all
        if ($context['is_admin']) {
            return true;
        }
        
        // Teacher can only access their own
        if ($context['is_teacher'] && $context['user_id']) {
            $db = \Config\Database::connect();
            $examName = $db->table('cbt_exam_names')
                ->select('created_by')
                ->where('id', $examNameId)
                ->get()
                ->getRow();
            
            // If created_by is null, it's accessible by all (legacy data)
            if (!$examName || $examName->created_by === null) {
                return true;
            }
            
            return (int)$examName->created_by === $context['user_id'];
        }
        
        return false;
    }
}

if (!function_exists('get_teacher_classes')) {
    /**
     * Get classes taught by a teacher
     * 
     * @param int $teacherId
     * @return array
     */
    function get_teacher_classes(int $teacherId): array
    {
        $db = \Config\Database::connect();
        
        $classes = $db->table('teaching_assignments ta')
            ->select('c.id, c.name, c.level')
            ->join('classes c', 'c.id = ta.class_id')
            ->where('ta.teacher_id', $teacherId)
            ->groupBy('c.id')
            ->get()
            ->getResultArray();
        
        return $classes;
    }
}

if (!function_exists('get_teacher_subjects')) {
    /**
     * Get subjects taught by a teacher
     * 
     * @param int $teacherId
     * @return array
     */
    function get_teacher_subjects(int $teacherId): array
    {
        $db = \Config\Database::connect();
        
        $subjects = $db->table('teaching_assignments ta')
            ->select('s.id, s.name')
            ->join('subjects s', 's.id = ta.subject_id')
            ->where('ta.teacher_id', $teacherId)
            ->groupBy('s.id')
            ->get()
            ->getResultArray();
        
        return $subjects;
    }
}

if (!function_exists('can_convert_subject_score')) {
    /**
     * Check if user can convert score for a specific subject
     * 
     * @param int $subjectId
     * @return bool
     */
    function can_convert_subject_score(int $subjectId): bool
    {
        $context = get_cbt_user_context();
        
        // Admin can convert all
        if ($context['is_admin']) {
            return true;
        }
        
        // Teacher can only convert their subjects
        if ($context['is_teacher'] && $context['teacher_id']) {
            $subjects = get_teacher_subjects($context['teacher_id']);
            $subjectIds = array_column($subjects, 'id');
            
            return in_array($subjectId, $subjectIds);
        }
        
        return false;
    }
}
