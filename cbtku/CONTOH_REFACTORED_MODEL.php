<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * CONTOH: Refactored CbtBankSoalModel
 * 
 * Menunjukkan cara refactor dari manual SQL ke Query Builder + Database Views
 */
class CbtBankSoalModelRefactored extends Model
{
    protected $table = 'cbt_question_banks';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'code', 'name', 'subject_id', 'teacher_id', 'academic_year_id',
        'class_id', 'description', 'created_at', 'updated_at'
    ];

    // ========================================
    // BEFORE: Manual SQL Query (Hard to maintain)
    // ========================================
    
    public function getBanksWithStatsOLD($teacherId = null)
    {
        $db = \Config\Database::connect();
        
        // Manual SQL - prone to errors, hard to maintain
        $sql = "SELECT b.*, 
                (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id) AS total_questions,
                (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id AND question_type = 'pg') AS total_pg,
                (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id AND question_type = 'pg_kompleks') AS total_pg_kompleks,
                (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id AND question_type = 'esai') AS total_esai,
                s.name as subject_name,
                t.name as teacher_name
                FROM cbt_question_banks b
                LEFT JOIN subjects s ON s.id = b.subject_id
                LEFT JOIN teachers t ON t.id = b.teacher_id";
        
        if ($teacherId) {
            $sql .= " WHERE b.teacher_id = ?";
            return $db->query($sql, [$teacherId])->getResultArray();
        }
        
        $sql .= " ORDER BY b.id DESC";
        return $db->query($sql)->getResultArray();
    }

    // ========================================
    // AFTER: Using Database View (Clean & Fast)
    // ========================================
    
    public function getBanksWithStats($teacherId = null)
    {
        // Use database view - much cleaner!
        $builder = $this->db->table('vw_cbt_bank_stats');
        
        if ($teacherId) {
            $builder->where('teacher_id', $teacherId);
        }
        
        return $builder->orderBy('id', 'DESC')->get()->getResultArray();
    }

    // ========================================
    // BEFORE: Multiple Queries (N+1 Problem)
    // ========================================
    
    public function updateBankTotalsOLD($bankId)
    {
        // 5 separate queries - inefficient!
        $total = $this->db->table('cbt_questions')
            ->where('bank_id', $bankId)
            ->countAllResults();

        $totalPg = $this->db->table('cbt_questions')
            ->where('bank_id', $bankId)
            ->where('question_type', 'pg')
            ->countAllResults();

        $totalPgk = $this->db->table('cbt_questions')
            ->where('bank_id', $bankId)
            ->where('question_type', 'pg_kompleks')
            ->countAllResults();

        $totalBs = $this->db->table('cbt_questions')
            ->where('bank_id', $bankId)
            ->where('question_type', 'benar_salah')
            ->countAllResults();

        $totalEsai = $this->db->table('cbt_questions')
            ->where('bank_id', $bankId)
            ->where('question_type', 'esai')
            ->countAllResults();

        $this->update($bankId, [
            'total_questions' => $total,
            'total_pg' => $totalPg,
            'total_pg_kompleks' => $totalPgk,
            'total_bs' => $totalBs,
            'total_esai' => $totalEsai
        ]);
    }

    // ========================================
    // AFTER: Single Query with GROUP BY
    // ========================================
    
    public function updateBankTotals($bankId)
    {
        // Single query with GROUP BY - efficient!
        $counts = $this->db->table('cbt_questions')
            ->select('question_type, COUNT(*) as count')
            ->where('bank_id', $bankId)
            ->groupBy('question_type')
            ->get()
            ->getResultArray();
        
        // Build count map
        $countMap = [];
        $total = 0;
        foreach ($counts as $row) {
            $countMap[$row['question_type']] = (int) $row['count'];
            $total += (int) $row['count'];
        }
        
        $this->update($bankId, [
            'total_questions' => $total,
            'total_pg' => $countMap['pg'] ?? 0,
            'total_pg_kompleks' => $countMap['pg_kompleks'] ?? 0,
            'total_bs' => $countMap['benar_salah'] ?? 0,
            'total_esai' => $countMap['esai'] ?? 0
        ]);
    }

    // ========================================
    // BEFORE: Complex Manual Query
    // ========================================
    
    public function searchBanksOLD($keyword, $subjectId = null, $teacherId = null)
    {
        $db = \Config\Database::connect();
        
        // Complex manual SQL - hard to maintain
        $sql = "SELECT b.*, s.name as subject_name, t.name as teacher_name,
                (SELECT COUNT(*) FROM cbt_questions WHERE bank_id = b.id) as total_questions
                FROM cbt_question_banks b
                LEFT JOIN subjects s ON s.id = b.subject_id
                LEFT JOIN teachers t ON t.id = b.teacher_id
                WHERE (b.name LIKE ? OR b.code LIKE ?)";
        
        $params = ["%{$keyword}%", "%{$keyword}%"];
        
        if ($subjectId) {
            $sql .= " AND b.subject_id = ?";
            $params[] = $subjectId;
        }
        
        if ($teacherId) {
            $sql .= " AND b.teacher_id = ?";
            $params[] = $teacherId;
        }
        
        $sql .= " ORDER BY b.updated_at DESC";
        
        return $db->query($sql, $params)->getResultArray();
    }

    // ========================================
    // AFTER: Query Builder (Clean & Maintainable)
    // ========================================
    
    public function searchBanks($keyword, $subjectId = null, $teacherId = null)
    {
        // Use Query Builder - much cleaner!
        $builder = $this->db->table('vw_cbt_bank_stats');
        
        // Search by keyword
        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('name', $keyword)
                    ->orLike('code', $keyword)
                    ->groupEnd();
        }
        
        // Filter by subject
        if ($subjectId) {
            $builder->where('subject_id', $subjectId);
        }
        
        // Filter by teacher
        if ($teacherId) {
            $builder->where('teacher_id', $teacherId);
        }
        
        return $builder->orderBy('updated_at', 'DESC')->get()->getResultArray();
    }

    // ========================================
    // BONUS: Pagination with Query Builder
    // ========================================
    
    public function getBanksPaginated($perPage = 20, $page = 1, $filters = [])
    {
        $builder = $this->db->table('vw_cbt_bank_stats');
        
        // Apply filters
        if (!empty($filters['subject_id'])) {
            $builder->where('subject_id', $filters['subject_id']);
        }
        
        if (!empty($filters['teacher_id'])) {
            $builder->where('teacher_id', $filters['teacher_id']);
        }
        
        if (!empty($filters['keyword'])) {
            $builder->groupStart()
                    ->like('name', $filters['keyword'])
                    ->orLike('code', $filters['keyword'])
                    ->groupEnd();
        }
        
        // Get total count
        $total = $builder->countAllResults(false); // false = don't reset query
        
        // Get paginated results
        $offset = ($page - 1) * $perPage;
        $results = $builder->orderBy('updated_at', 'DESC')
                          ->limit($perPage, $offset)
                          ->get()
                          ->getResultArray();
        
        return [
            'data' => $results,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    // ========================================
    // BONUS: Batch Operations
    // ========================================
    
    public function deleteBanksWithQuestions(array $bankIds)
    {
        // Use transaction for data integrity
        $this->db->transStart();
        
        try {
            // Delete questions first (foreign key)
            $this->db->table('cbt_questions')
                     ->whereIn('bank_id', $bankIds)
                     ->delete();
            
            // Delete banks
            $this->db->table('cbt_question_banks')
                     ->whereIn('id', $bankIds)
                     ->delete();
            
            $this->db->transComplete();
            
            return $this->db->transStatus();
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Failed to delete banks: ' . $e->getMessage());
            return false;
        }
    }
}

/**
 * KEY BENEFITS OF REFACTORING:
 * 
 * 1. READABILITY
 *    - Query Builder is self-documenting
 *    - Easy to understand query logic
 *    - No string concatenation mess
 * 
 * 2. MAINTAINABILITY
 *    - Easy to modify queries
 *    - Less prone to SQL injection
 *    - IDE autocomplete support
 * 
 * 3. PERFORMANCE
 *    - Database views are optimized by DB engine
 *    - Single query instead of multiple
 *    - Better query planning
 * 
 * 4. TESTABILITY
 *    - Easy to mock Query Builder
 *    - Can test without database
 *    - Clear method boundaries
 * 
 * 5. REUSABILITY
 *    - Views can be used across multiple models
 *    - Query Builder methods are composable
 *    - DRY principle
 */
