<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityPhotoModel extends Model
{
    protected $table            = 'landing_activity_photos';
    protected $primaryKey       = 'id';
    protected $useTimestamps    = true;
    protected $allowedFields    = [
        'activity_id',
        'image',
        'caption',
        'is_cover',
        'sort_order',
    ];

    /** Ekstensi gambar yang diizinkan */
    public const SAFE_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    /**
     * Ambil foto-foto untuk aktivitas tertentu yang filenya valid di disk.
     */
    public function getValidPhotosByActivity(int $activityId): array
    {
        $photos = $this->where('activity_id', $activityId)
            ->orderBy('is_cover', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        return array_values(array_filter($photos, function ($p) {
            if (empty($p['image'])) {
                return false;
            }
            $ext      = strtolower(pathinfo($p['image'], PATHINFO_EXTENSION));
            $isSafe   = in_array($ext, self::SAFE_IMAGE_EXT, true);
            $filePath = UPLOAD_PATH . 'activities/' . basename($p['image']);
            return $isSafe && is_file($filePath);
        }));
    }
}
