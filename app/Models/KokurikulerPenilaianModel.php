<?php

namespace App\Models;

use CodeIgniter\Model;

class KokurikulerPenilaianModel extends Model
{
    protected $table = 'kokurikuler_penilaian';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'student_id',
        'penilaian_detail',
        'catatan_tambahan',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get penilaian by document
     */
    public function getPenilaianByDocument($documentId)
    {
        return $this->select('kokurikuler_penilaian.*, students.name as student_name, students.nis')
            ->join('students', 'students.id = kokurikuler_penilaian.student_id', 'left')
            ->where('kokurikuler_penilaian.document_id', $documentId)
            ->orderBy('students.name', 'ASC')
            ->findAll();
    }

    /**
     * Get penilaian for specific student
     */
    public function getPenilaianByStudentAndDocument($studentId, $documentId)
    {
        return $this->where('student_id', $studentId)
            ->where('document_id', $documentId)
            ->first();
    }

    /**
     * Get summary statistics
     * Progress = students who have penilaian saved for ALL rubrik in this document
     */
    public function getSummary($documentId)
    {
        $db = \Config\Database::connect();
        
        // Get document details
        $documentModel = new \App\Models\KokurikulerDocumentModel();
        $document = $documentModel->find($documentId);
        
        if (!$document) {
            return [
                'total_students' => 0,
                'sudah_dinilai' => 0,
                'belum_dinilai' => 0,
                'persentase' => 0,
            ];
        }

        // Count total rubrik for this document
        $totalRubrik = $db->table('kokurikuler_rubrik')
            ->where('document_id', $documentId)
            ->countAllResults();

        if ($totalRubrik === 0) {
            return [
                'total_students' => 0,
                'sudah_dinilai' => 0,
                'belum_dinilai' => 0,
                'persentase' => 0,
            ];
        }

        // Get active year
        $yearModel = new \App\Models\AcademicYearModel();
        $activeYear = $yearModel->getActiveYear();
        
        if (!$activeYear) {
            return [
                'total_students' => 0,
                'sudah_dinilai' => 0,
                'belum_dinilai' => 0,
                'persentase' => 0,
            ];
        }

        // Get total students
        $students = 0;
        if (!empty($document['class_id'])) {
            $students = $db->table('student_records')
                ->where('class_id', $document['class_id'])
                ->where('academic_year_id', $activeYear['id'])
                ->where('status', 'aktif')
                ->countAllResults();
        } else {
            $levelKelas = $document['level_kelas'];
            $query = $db->table('student_records')
                ->join('classes', 'classes.id = student_records.class_id', 'left')
                ->where('student_records.academic_year_id', $activeYear['id'])
                ->where('student_records.status', 'aktif');
            if (strpos($levelKelas, ',') !== false) {
                $levels = array_map('trim', explode(',', $levelKelas));
                $query->whereIn('classes.level', $levels);
            } else {
                $query->where('classes.level', $levelKelas);
            }
            $students = $query->countAllResults();
        }

        // Count progress: total rubrik assessments done vs total needed
        $penilaianRows = $this->where('document_id', $documentId)->findAll();
        
        $totalAssessmentsDone = 0;
        foreach ($penilaianRows as $p) {
            $detail = json_decode($p['penilaian_detail'] ?? '{}', true) ?: [];
            $totalAssessmentsDone += count($detail);
        }
        
        // Total needed = students × rubrik
        $totalNeeded = $students * max($totalRubrik, 1);
        $sudahDinilai = count($penilaianRows); // students with at least 1 rubrik assessed
        
        $belumDinilai = $students - $sudahDinilai;
        $persentase = $totalNeeded > 0 ? round(($totalAssessmentsDone / $totalNeeded) * 100, 2) : 0;

        return [
            'total_students' => $students,
            'sudah_dinilai' => $sudahDinilai,
            'belum_dinilai' => $belumDinilai,
            'persentase' => $persentase,
            'total_rubrik' => $totalRubrik,
            'total_assessments_done' => $totalAssessmentsDone,
            'total_assessments_needed' => $totalNeeded,
        ];
    }

    /**
     * Get students list with penilaian status
     */
    public function getStudentsWithPenilaianStatus($documentId)
    {
        $db = \Config\Database::connect();
        
        // Get document details
        $documentModel = new \App\Models\KokurikulerDocumentModel();
        $document = $documentModel->find($documentId);
        
        if (!$document) {
            log_message('error', 'Document not found: ' . $documentId);
            return [];
        }

        log_message('info', 'Getting students for document ' . $documentId . ' - class_id: ' . ($document['class_id'] ?? 'NULL') . ', level_kelas: ' . $document['level_kelas']);

        // Get active year
        $yearModel = new \App\Models\AcademicYearModel();
        $activeYear = $yearModel->getActiveYear();
        
        if (!$activeYear) {
            log_message('error', 'No active academic year found');
            return [];
        }

        // Get students based on class_id or level using student_records
        $studentsQuery = $db->table('students')
            ->select('students.id, students.name, students.nis, classes.name as class_name, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'inner')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.academic_year_id', $activeYear['id']);
        
        if (!empty($document['class_id'])) {
            // Jika ada class_id spesifik (dari wali kelas)
            log_message('info', 'Filtering by class_id: ' . $document['class_id']);
            $studentsQuery->where('student_records.class_id', $document['class_id']);
        } else {
            // Jika tidak ada class_id (template dari admin), gunakan level
            $levelKelas = $document['level_kelas'];
            
            // Handle comma-separated levels or single level
            if (strpos($levelKelas, ',') !== false) {
                $levels = array_map('trim', explode(',', $levelKelas));
                log_message('info', 'Filtering by multiple levels: ' . implode(', ', $levels));
                $studentsQuery->whereIn('classes.level', $levels);
            } else {
                log_message('info', 'Filtering by single level: ' . $levelKelas);
                $studentsQuery->where('classes.level', $levelKelas);
            }
        }
        
        $students = $studentsQuery
            ->groupBy('students.id')
            ->orderBy('classes.name', 'ASC')
            ->orderBy('students.name', 'ASC')
            ->get()
            ->getResultArray();

        log_message('info', 'Found ' . count($students) . ' students');

        // Get penilaian data
        $penilaianData = $this->where('document_id', $documentId)->findAll();
        $penilaianMap = [];
        foreach ($penilaianData as $p) {
            $penilaianMap[$p['student_id']] = $p;
        }

        // Merge data
        foreach ($students as &$student) {
            if (isset($penilaianMap[$student['id']])) {
                $student['penilaian'] = $penilaianMap[$student['id']];
                $student['status'] = 'sudah_dinilai';
            } else {
                $student['penilaian'] = null;
                $student['status'] = 'belum_dinilai';
            }
        }

        return $students;
    }
}
