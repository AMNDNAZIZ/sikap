<?php

namespace App\Models;

use CodeIgniter\Model;

class MaterialSegmentModel extends Model
{
    protected $table         = 'material_segments';
    protected $primaryKey    = 'id';
    protected $allowedFields = [
        'material_id',
        'title',
        'content_type',
        'content',
        'content_html',
        'video_url',
        'external_link',
        'file_path',
        'estimated_minutes',
        'sort_order',
    ];
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Ambil seluruh segmen untuk sebuah sub-materi beserta slide (jika ada)
     */
    public function getSegmentsByMaterial(int $materialId): array
    {
        $segments = $this->where('material_id', $materialId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        if (empty($segments)) {
            return [];
        }

        $db = \Config\Database::connect();
        foreach ($segments as &$seg) {
            if ($seg['content_type'] === 'video' && !empty($seg['video_url'])) {
                $seg['embed_url'] = SubjectMaterialModel::toEmbedUrl($seg['video_url']);
            }

            if ($seg['content_type'] === 'slide') {
                $seg['slides'] = $db->table('material_segment_slides')
                    ->where('segment_id', $seg['id'])
                    ->orderBy('sort_order', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->get()->getResultArray();
            } else {
                $seg['slides'] = [];
            }
        }
        unset($seg);

        return $segments;
    }

    /**
     * Hapus segmen beserta file fisiknya
     */
    public function deleteWithFiles(int $id): bool
    {
        $seg = $this->find($id);
        if (!$seg) {
            return false;
        }

        // Hapus file PDF jika ada
        if (!empty($seg['file_path'])) {
            $pdfPath = FCPATH . 'uploads/materials/' . $seg['file_path'];
            if (file_exists($pdfPath)) {
                @unlink($pdfPath);
            }
        }

        // Hapus slide images jika ada
        $db = \Config\Database::connect();
        $slides = $db->table('material_segment_slides')->where('segment_id', $id)->get()->getResultArray();
        foreach ($slides as $sl) {
            $imgPath = FCPATH . 'uploads/materials/slides/' . $sl['image_path'];
            if (file_exists($imgPath)) {
                @unlink($imgPath);
            }
        }

        return $this->delete($id);
    }
}
