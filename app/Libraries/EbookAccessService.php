<?php

namespace App\Libraries;

class EbookAccessService
{
    /**
     * Cek apakah user boleh mengakses (baca/download) sebuah buku
     */
    public function canAccess(array $user, array $book): bool
    {
        $roleId = (int) ($user['role_id'] ?? 0);
        $bookType = $book['book_type'] ?? 'mapel';

        // Admin & Staf TU = full access
        if (in_array($roleId, [1, 7])) {
            return true;
        }

        // Buku Umum: any authenticated user with valid role can access
        if ($bookType === 'umum') {
            // Guru and Siswa can access umum books
            return in_array($roleId, [3, 5]);
        }

        // Guru (Wali Kelas) = check level matches
        if ($roleId === 3) {
            $allowedLevels = $this->getAllowedLevels($user);
            return in_array((int) $book['level'], $allowedLevels);
        }

        // Siswa = check level matches AND religion check
        if ($roleId === 5) {
            $studentData = $this->getStudentData($user);
            if (empty($studentData)) {
                return false;
            }

            // Check level matches
            if ((int) $book['level'] !== (int) $studentData['level']) {
                return false;
            }

            // Check religion: book has no religion OR religion matches student
            if (!empty($book['religion'])) {
                if (($studentData['religion'] ?? '') !== $book['religion']) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Cek apakah pengelola boleh mengelola (create/edit/delete) buku pada level tertentu
     * For umum books, pass level=0 or null
     */
    public function canManage(array $user, ?int $level, string $bookType = 'mapel'): bool
    {
        $roleId = (int) ($user['role_id'] ?? 0);

        // Admin & Staf TU = full access
        if (in_array($roleId, [1, 7])) {
            return true;
        }

        // Guru (Wali Kelas): can only manage 'mapel' type books at their level
        if ($roleId === 3) {
            // Wali Kelas cannot manage umum books
            if ($bookType === 'umum') {
                return false;
            }

            if ($level === null || $level === 0) {
                return false;
            }

            $allowedLevels = $this->getAllowedLevels($user);
            return in_array($level, $allowedLevels);
        }

        return false;
    }

    /**
     * Dapatkan daftar level kelas yang diizinkan untuk user
     */
    public function getAllowedLevels(array $user): array
    {
        $roleId = (int) ($user['role_id'] ?? 0);

        // Admin & Staf TU = all levels berdasarkan tingkat sekolah
        if (in_array($roleId, [1, 7])) {
            return $this->getSchoolLevels();
        }

        // Guru = levels from classes where teacher_id matches
        if ($roleId === 3) {
            $db = \Config\Database::connect();
            $userId = (int) ($user['id'] ?? 0);

            $levels = $db->table('classes')
                ->select('classes.level')
                ->join('teachers', 'teachers.id = classes.teacher_id', 'inner')
                ->where('teachers.user_id', $userId)
                ->groupBy('classes.level')
                ->get()
                ->getResultArray();

            return array_map(function ($row) {
                return (int) $row['level'];
            }, $levels);
        }

        // Siswa = level from active student_record
        if ($roleId === 5) {
            $studentData = $this->getStudentData($user);
            if (!empty($studentData) && !empty($studentData['level'])) {
                return [(int) $studentData['level']];
            }
            return [];
        }

        return [];
    }

    /**
     * Dapatkan daftar level kelas berdasarkan tingkat sekolah di school_profile
     * SD (level=1): 1-6, SMP (level=2): 7-9, SMA (level=3): 10-12
     */
    public function getSchoolLevels(): array
    {
        $db = \Config\Database::connect();
        $school = $db->table('school_profile')->select('level')->get()->getRowArray();
        $schoolLevel = (int) ($school['level'] ?? 1);

        switch ($schoolLevel) {
            case 1: // SD
                return [1, 2, 3, 4, 5, 6];
            case 2: // SMP
                return [7, 8, 9];
            case 3: // SMA
                return [10, 11, 12];
            default:
                return [1, 2, 3, 4, 5, 6];
        }
    }

    /**
     * Dapatkan label kelas berdasarkan level number
     */
    public static function getLevelLabel(int $level): string
    {
        return 'Kelas ' . $level;
    }

    /**
     * Cek apakah user bisa mengelola buku umum (hanya Admin & Staf TU)
     */
    public function canManageUmum(array $user): bool
    {
        $roleId = (int) ($user['role_id'] ?? 0);
        return in_array($roleId, [1, 7]);
    }

    /**
     * Sanitasi nama file untuk download
     */
    public function sanitizeFilename(string $title): string
    {
        // Replace non-alphanumeric/space/hyphen with hyphen
        $sanitized = preg_replace('/[^a-zA-Z0-9\s\-]/', '-', $title);
        // Collapse multiple hyphens
        $sanitized = preg_replace('/-+/', '-', $sanitized);
        // Trim hyphens and spaces
        $sanitized = trim($sanitized, '- ');
        // Max 100 chars
        $sanitized = mb_substr($sanitized, 0, 100);
        // Ensure not empty
        if (empty($sanitized)) {
            $sanitized = 'ebook';
        }
        return $sanitized . '.pdf';
    }

    /**
     * Validasi bahwa filename tidak mengandung path traversal
     */
    public function isValidFilename(string $filename): bool
    {
        // Reject if contains path traversal
        if (strpos($filename, '../') !== false || strpos($filename, '..\\') !== false) {
            return false;
        }
        // Must end with .pdf
        if (substr(strtolower($filename), -4) !== '.pdf') {
            return false;
        }
        return true;
    }

    /**
     * Get student data (level + religion) for access checking
     */
    protected function getStudentData(array $user): array
    {
        $db = \Config\Database::connect();
        $userId = (int) ($user['id'] ?? 0);

        $result = $db->table('students')
            ->select('students.religion, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'inner')
            ->join('classes', 'classes.id = student_records.class_id', 'inner')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'inner')
            ->where('students.user_id', $userId)
            ->where('student_records.status', 'aktif')
            ->where('academic_years.is_active', 1)
            ->get()
            ->getRowArray();

        return $result ?? [];
    }
}
