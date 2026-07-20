<?php

namespace App\Models;

use CodeIgniter\Model;

class KokurikulerRubrikModel extends Model
{
    protected $table = 'kokurikuler_rubrik';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'document_id',
        'dimensi_profil',
        'sub_dimensi',
        'aspek_dinilai',
        'urutan',
        'created_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = false;
    protected $dateFormat = 'datetime';

    /**
     * Get rubrik by document ID
     */
    public function getRubrikByDocument($documentId)
    {
        return $this->where('document_id', $documentId)
            ->orderBy('urutan', 'ASC')
            ->findAll();
    }

    /**
     * Generate rubrik from document (auto-generate)
     */
    public function generateRubrikFromDocument($documentId)
    {
        $documentModel = new \App\Models\KokurikulerDocumentModel();
        $document = $documentModel->find($documentId);

        if (!$document) {
            log_message('error', 'GenerateRubrik - Document not found: ' . $documentId);
            return false;
        }

        log_message('info', 'GenerateRubrik - Document found: ' . $documentId . ', jenis_kokurikuler: ' . ($document['jenis_kokurikuler'] ?? $document['bentuk_kegiatan'] ?? 'unknown'));

        // Check if rubrik already exists
        $existing = $this->where('document_id', $documentId)->countAllResults();
        if ($existing > 0) {
            log_message('info', 'GenerateRubrik - Rubrik already exists (' . $existing . ' items)');
            return true; // Already generated
        }

        $rubrikData = [];
        $urutan = 1;
        $now = date('Y-m-d H:i:s');

        // Get dimensi profil
        $dimensiList = json_decode($document['dimensi_profil'], true);
        log_message('info', 'GenerateRubrik - Dimensi profil: ' . json_encode($dimensiList));

        $jenisKokurikuler = $document['jenis_kokurikuler'] ?? $document['bentuk_kegiatan'] ?? '';
        
        if ($jenisKokurikuler === 'lintas_disiplin') {
            // Lintas Disiplin: Generate rubrik per sub dimensi
            $kegiatanDetail = json_decode($document['kegiatan_detail'], true);
            log_message('info', 'GenerateRubrik - Kegiatan detail: ' . json_encode($kegiatanDetail));
            
            if (isset($kegiatanDetail['items']) && is_array($kegiatanDetail['items'])) {
                // Group by dimensi and sub_dimensi to avoid duplicates
                $grouped = [];
                
                foreach ($kegiatanDetail['items'] as $item) {
                    $dimensi = $item['dimensi_profil'] ?? null;
                    $subDimensi = $item['sub_dimensi'] ?? null;
                    
                    if ($dimensi && $subDimensi) {
                        $key = $dimensi . '|' . $subDimensi;
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'dimensi' => $dimensi,
                                'sub_dimensi' => $subDimensi,
                            ];
                        }
                    }
                }
                
                // Create rubrik for each unique dimensi-subdimensi pair
                foreach ($grouped as $item) {
                    $rubrikData[] = [
                        'document_id' => $documentId,
                        'dimensi_profil' => $item['dimensi'],
                        'sub_dimensi' => $item['sub_dimensi'],
                        'aspek_dinilai' => $item['sub_dimensi'], // Sub dimensi sebagai aspek
                        'urutan' => $urutan++,
                        'created_at' => $now,
                    ];
                }
                
                log_message('info', 'GenerateRubrik - Lintas disiplin items processed: ' . count($rubrikData));
            }
        } elseif ($jenisKokurikuler === '7kaih') {
            // 7 KAIH: Generate rubrik per sub dimensi
            $kegiatanDetail = json_decode($document['kegiatan_detail'], true);
            log_message('info', 'GenerateRubrik - Kegiatan detail: ' . json_encode($kegiatanDetail));
            
            if (isset($kegiatanDetail['items']) && is_array($kegiatanDetail['items'])) {
                // Group by dimensi and sub_dimensi to avoid duplicates
                $grouped = [];
                
                foreach ($kegiatanDetail['items'] as $item) {
                    $dimensi = $item['dimensi_profil'] ?? null;
                    $subDimensi = $item['sub_dimensi'] ?? null;
                    
                    if ($dimensi && $subDimensi) {
                        $key = $dimensi . '|' . $subDimensi;
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'dimensi' => $dimensi,
                                'sub_dimensi' => $subDimensi,
                            ];
                        }
                    }
                }
                
                // Create rubrik for each unique dimensi-subdimensi pair
                foreach ($grouped as $item) {
                    $rubrikData[] = [
                        'document_id' => $documentId,
                        'dimensi_profil' => $item['dimensi'],
                        'sub_dimensi' => $item['sub_dimensi'],
                        'aspek_dinilai' => $item['sub_dimensi'], // Sub dimensi sebagai aspek
                        'urutan' => $urutan++,
                        'created_at' => $now,
                    ];
                }
                
                log_message('info', 'GenerateRubrik - 7 KAIH items processed: ' . count($rubrikData));
            }
        } else {
            // Lainnya: Check if it's JSON and has items (selected sub-dimensions)
            $kegiatanDetail = json_decode($document['kegiatan_detail'], true);
            if ($kegiatanDetail && isset($kegiatanDetail['items']) && is_array($kegiatanDetail['items'])) {
                // Group by dimensi and sub_dimensi to avoid duplicates
                $grouped = [];
                foreach ($kegiatanDetail['items'] as $item) {
                    $dimensi = $item['dimensi_profil'] ?? null;
                    $subDimensi = $item['sub_dimensi'] ?? null;
                    if ($dimensi && $subDimensi) {
                        $key = $dimensi . '|' . $subDimensi;
                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'dimensi' => $dimensi,
                                'sub_dimensi' => $subDimensi,
                            ];
                        }
                    }
                }
                
                // Create rubrik for each unique dimensi-subdimensi pair
                foreach ($grouped as $item) {
                    $rubrikData[] = [
                        'document_id' => $documentId,
                        'dimensi_profil' => $item['dimensi'],
                        'sub_dimensi' => $item['sub_dimensi'],
                        'aspek_dinilai' => $item['sub_dimensi'], // Sub dimensi sebagai aspek
                        'urutan' => $urutan++,
                        'created_at' => $now,
                    ];
                }
                log_message('info', 'GenerateRubrik - Lainnya items processed: ' . count($rubrikData));
            } else {
                // Fallback: Generate per dimensi profil (tanpa sub dimensi detail)
                log_message('info', 'GenerateRubrik - Using default rubrik (Lainnya) without sub-dimensions');
                foreach ($dimensiList as $dimensi) {
                    $rubrikData[] = [
                        'document_id' => $documentId,
                        'dimensi_profil' => $dimensi,
                        'sub_dimensi' => null,
                        'aspek_dinilai' => 'Menunjukkan pemahaman dan penerapan dimensi ' . $dimensi,
                        'urutan' => $urutan++,
                        'created_at' => $now,
                    ];
                }
            }
        }

        log_message('info', 'GenerateRubrik - Generated ' . count($rubrikData) . ' rubrik items');

        if (!empty($rubrikData)) {
            try {
                // Use query builder directly to avoid timestamp issues
                $db = \Config\Database::connect();
                $builder = $db->table('kokurikuler_rubrik');
                
                // Log the data before insert
                log_message('info', 'GenerateRubrik - Data to insert: ' . json_encode($rubrikData));
                
                $result = $builder->insertBatch($rubrikData);
                
                log_message('info', 'GenerateRubrik - Insert batch result: ' . ($result ? 'success (' . $result . ' rows)' : 'failed'));
                
                if (!$result) {
                    log_message('error', 'GenerateRubrik - Insert failed. Last query: ' . $db->getLastQuery());
                }
                
                return $result;
            } catch (\Exception $e) {
                log_message('error', 'Generate rubrik error: ' . $e->getMessage());
                log_message('error', 'Stack trace: ' . $e->getTraceAsString());
                
                // Try to get last query if available
                try {
                    $db = \Config\Database::connect();
                    log_message('error', 'Last query: ' . $db->getLastQuery());
                } catch (\Exception $e2) {
                    log_message('error', 'Could not get last query: ' . $e2->getMessage());
                }
                
                return false;
            }
        }

        log_message('warning', 'GenerateRubrik - No rubrik data generated');
        return false;
    }

    /**
     * Delete rubrik by document
     */
    public function deleteByDocument($documentId)
    {
        return $this->where('document_id', $documentId)->delete();
    }
}
