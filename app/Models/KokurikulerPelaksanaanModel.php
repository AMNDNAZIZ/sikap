<?php

namespace App\Models;

use CodeIgniter\Model;

class KokurikulerPelaksanaanModel extends Model
{
    protected $table = 'kokurikuler_pelaksanaan';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'pertemuan_ke',
        'status',
        'tanggal_pelaksanaan',
        'catatan_pelaksanaan',
        'dokumentasi',
        'alasan_tidak_terlaksana',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Get pelaksanaan by document ID
     */
    public function getPelaksanaanByDocument($documentId)
    {
        return $this->where('document_id', $documentId)
            ->orderBy('pertemuan_ke', 'ASC')
            ->findAll();
    }

    /**
     * Get pelaksanaan detail with creator info
     */
    public function getPelaksanaanWithCreator($documentId)
    {
        return $this->select('kokurikuler_pelaksanaan.*, users.fullname as creator_name')
            ->join('users', 'users.id = kokurikuler_pelaksanaan.created_by', 'left')
            ->where('kokurikuler_pelaksanaan.document_id', $documentId)
            ->orderBy('kokurikuler_pelaksanaan.pertemuan_ke', 'ASC')
            ->findAll();
    }

    /**
     * Initialize pelaksanaan records for a document
     */
    public function initializePelaksanaan($documentId, $jumlahPertemuan, $userId)
    {
        $data = [];
        for ($i = 1; $i <= $jumlahPertemuan; $i++) {
            // Check if already exists
            $existing = $this->where('document_id', $documentId)
                ->where('pertemuan_ke', $i)
                ->first();
            
            if (!$existing) {
                $data[] = [
                    'document_id' => $documentId,
                    'pertemuan_ke' => $i,
                    'status' => 'belum_dilaksanakan',
                    'created_by' => $userId,
                ];
            }
        }

        if (!empty($data)) {
            return $this->insertBatch($data);
        }

        return true;
    }

    /**
     * Get summary statistics
     */
    public function getSummary($documentId)
    {
        $pelaksanaan = $this->where('document_id', $documentId)->findAll();
        
        $summary = [
            'total' => count($pelaksanaan),
            'terlaksana' => 0,
            'tidak_terlaksana' => 0,
            'belum_dilaksanakan' => 0,
            'persentase_terlaksana' => 0,
        ];

        foreach ($pelaksanaan as $p) {
            if ($p['status'] === 'terlaksana') {
                $summary['terlaksana']++;
            } elseif ($p['status'] === 'tidak_terlaksana') {
                $summary['tidak_terlaksana']++;
            } else {
                $summary['belum_dilaksanakan']++;
            }
        }

        if ($summary['total'] > 0) {
            $summary['persentase_terlaksana'] = round(($summary['terlaksana'] / $summary['total']) * 100, 2);
        }

        return $summary;
    }
}
