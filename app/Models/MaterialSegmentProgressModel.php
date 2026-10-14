<?php

namespace App\Models;

use CodeIgniter\Model;

class MaterialSegmentProgressModel extends Model
{
    protected $table         = 'material_segment_progress';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'student_id',
        'material_id',
        'segment_id',
        'status',
        'completed_at',
    ];
    public $timestamps = false;

    /**
     * Ambil map progres siswa per segmen dalam 1 sub materi: [segment_id => ['status' => ..., 'completed_at' => ...]]
     */
    public function getProgressMap(int $studentId, int $materialId): array
    {
        $rows = $this->where('student_id', $studentId)
            ->where('material_id', $materialId)
            ->findAll();

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['segment_id']] = [
                'status'       => $r['status'],
                'completed_at' => $r['completed_at'],
            ];
        }

        return $map;
    }

    /**
     * Tandai satu segmen selesai.
     * Jika seluruh segmen pada materi tersebut sudah selesai, otomatis tandai sub materi selesai di material_progress!
     */
    public function markCompleted(int $studentId, int $materialId, int $segmentId): array
    {
        $existing = $this->where('student_id', $studentId)
            ->where('segment_id', $segmentId)
            ->first();

        $now = date('Y-m-d H:i:s');
        if ($existing) {
            $this->update($existing['id'], [
                'status'       => 'completed',
                'completed_at' => $now,
            ]);
        } else {
            $this->insert([
                'student_id'   => $studentId,
                'material_id'  => $materialId,
                'segment_id'   => $segmentId,
                'status'       => 'completed',
                'completed_at' => $now,
            ]);
        }

        // Cek total segmen materi ini
        $segModel = new MaterialSegmentModel();
        $allSegments = $segModel->where('material_id', $materialId)->findAll();
        $totalSegments = count($allSegments);

        $doneCount = $this->where('student_id', $studentId)
            ->where('material_id', $materialId)
            ->where('status', 'completed')
            ->countAllResults();

        $isAllDone = ($totalSegments > 0 && $doneCount >= $totalSegments);

        if ($isAllDone) {
            // Update tabel induk material_progress
            $progModel = new MaterialProgressModel();
            $progModel->markCompleted($studentId, $materialId);
        }

        return [
            'success'        => true,
            'done_count'     => $doneCount,
            'total_segments' => $totalSegments,
            'is_all_done'    => $isAllDone,
        ];
    }
}
