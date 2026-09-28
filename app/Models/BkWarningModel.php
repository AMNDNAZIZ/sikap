<?php

namespace App\Models;

use CodeIgniter\Model;

class BkWarningModel extends Model
{
    public function getAtRiskStudents()
    {
        $db = \Config\Database::connect();
        
        // Dapatkan tahun ajaran aktif
        $academicYearModel = new AcademicYearModel();
        $activeYear = $academicYearModel->getActiveYear();
        $activeYearId = $activeYear ? $activeYear['id'] : 0;

        $sql = "
            SELECT 
                s.id as student_id, 
                s.name as student_name, 
                s.nis, 
                c.name as class_name,
                (
                    SELECT COALESCE(SUM(b.points), 0)
                    FROM student_note_behaviors snb
                    JOIN student_notes sn ON sn.id = snb.note_id
                    JOIN behaviors b ON b.id = snb.behavior_id
                    WHERE sn.student_id = s.id 
                      AND b.type = 'negative'
                      AND sn.academic_year_id = ?
                ) as negative_points,
                (
                    SELECT COUNT(a.id)
                    FROM attendances a
                    WHERE a.student_id = s.id 
                      AND a.status = 'A'
                ) as total_alpha,
                (
                    SELECT COUNT(j.id)
                    FROM bk_journals j
                    WHERE j.student_id = s.id
                ) as total_journals
            FROM students s
            JOIN student_records sr ON sr.student_id = s.id
            JOIN classes c ON c.id = sr.class_id
            WHERE sr.status = 'aktif' 
              AND sr.academic_year_id = ?
            HAVING negative_points >= 20 
                OR total_alpha >= 3 
                OR total_journals >= 2
            ORDER BY negative_points DESC, total_alpha DESC
        ";

        return $db->query($sql, [$activeYearId, $activeYearId])->getResultArray();
    }
}
