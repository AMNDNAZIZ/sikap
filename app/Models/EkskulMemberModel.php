<?php

namespace App\Models;

use CodeIgniter\Model;

class EkskulMemberModel extends Model
{
    protected $table            = 'ekskul_members';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['ekskul_id', 'student_id', 'academic_year_id', 'status'];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get students enrolled in a specific ekskul
     */
    public function getMembers($ekskulId, $academicYearId = null, $status = null)
    {
        $builder = $this->select('ekskul_members.*, students.name as student_name, students.nis, classes.name as class_name')
            ->join('students', 'students.id = ekskul_members.student_id')
            ->join('student_records', 'student_records.student_id = ekskul_members.student_id AND student_records.academic_year_id = ekskul_members.academic_year_id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('ekskul_members.ekskul_id', $ekskulId);

        if ($academicYearId) {
            $builder->where('ekskul_members.academic_year_id', $academicYearId);
        }

        if ($status) {
            $builder->where('ekskul_members.status', $status);
        }

        return $builder->orderBy('classes.name', 'ASC')->orderBy('students.name', 'ASC')->findAll();
    }

    /**
     * Otomatis mendaftarkan siswa aktif ke ekskul wajib
     *
     * @param int $academicYearId
     * @param int|null $ekskulId Jika dispesifikasikan, sinkronisasi hanya untuk ekskul ini
     * @param int|null $studentId Jika dispesifikasikan, sinkronisasi hanya untuk siswa ini
     * @return int Jumlah anggota baru yang ditambahkan
     */
    public function syncWajibMembers($academicYearId, $ekskulId = null, $studentId = null)
    {
        if (empty($academicYearId)) {
            return 0;
        }

        $db = \Config\Database::connect();

        // 1. Ambil ekskul wajib yang aktif
        $ekskulBuilder = $db->table('ekskul_master')
            ->where('is_active', 1)
            ->where('category', 'wajib');

        if ($ekskulId) {
            $ekskulBuilder->where('id', $ekskulId);
        }

        $wajibEkskuls = $ekskulBuilder->get()->getResultArray();
        if (empty($wajibEkskuls)) {
            return 0;
        }

        // 2. Ambil siswa aktif di tahun ajaran ini
        $studentQuery = $db->table('student_records')
            ->select('student_records.student_id')
            ->join('students', 'students.id = student_records.student_id')
            ->where('student_records.academic_year_id', $academicYearId)
            ->where('student_records.status', 'aktif');

        if ($studentId) {
            $studentQuery->where('student_records.student_id', $studentId);
        }

        $activeStudents = $studentQuery->get()->getResultArray();
        if (empty($activeStudents)) {
            return 0;
        }

        $activeStudentIds = array_column($activeStudents, 'student_id');
        $insertedCount = 0;
        $now = date('Y-m-d H:i:s');

        foreach ($wajibEkskuls as $ek) {
            $eid = (int) $ek['id'];

            // Ambil siswa yang sudah terdaftar di ekskul ini
            $existingMembers = $db->table('ekskul_members')
                ->where('ekskul_id', $eid)
                ->where('academic_year_id', $academicYearId)
                ->get()->getResultArray();

            $existingMap = [];
            foreach ($existingMembers as $em) {
                $existingMap[(int) $em['student_id']] = $em;
            }

            $batchInsert = [];
            foreach ($activeStudentIds as $sid) {
                $sid = (int) $sid;
                if (!isset($existingMap[$sid])) {
                    $batchInsert[] = [
                        'ekskul_id'        => $eid,
                        'student_id'       => $sid,
                        'academic_year_id' => $academicYearId,
                        'status'           => 'aktif',
                        'created_at'       => $now,
                        'updated_at'       => $now
                    ];
                } elseif ($existingMap[$sid]['status'] !== 'aktif') {
                    // Update yang statusnya pending/nonaktif menjadi aktif
                    $db->table('ekskul_members')
                        ->where('id', $existingMap[$sid]['id'])
                        ->update(['status' => 'aktif', 'updated_at' => $now]);
                }
            }

            if (!empty($batchInsert)) {
                $db->table('ekskul_members')->insertBatch($batchInsert);
                $insertedCount += count($batchInsert);
            }
        }

        return $insertedCount;
    }
}
