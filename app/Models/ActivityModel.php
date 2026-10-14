<?php
namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table          = 'landing_activities';
    protected $primaryKey     = 'id';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['title', 'description', 'image', 'date', 'created_by'];

    /** Ekstensi gambar yang diizinkan untuk ditampilkan di halaman publik */
    private const SAFE_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function getActivitiesWithAuthor()
    {
        return $this->select('landing_activities.*, users.fullname as uploader_name, 
            (SELECT COUNT(*) FROM landing_activity_photos WHERE landing_activity_photos.activity_id = landing_activities.id) as photo_count')
            ->join('users', 'users.id = landing_activities.created_by', 'left')
            ->orderBy('landing_activities.date', 'DESC')
            ->findAll();
    }

    /**
     * Ambil aktivitas sebagai album untuk halaman publik.
     * Mengembalikan daftar kegiatan lengkap dengan foto-foto di dalamnya beserta deskripsi per foto.
     */
    public function getPublicActivities(): array
    {
        $rows = $this->orderBy('date', 'DESC')->findAll();
        $photoModel = new ActivityPhotoModel();

        $result = [];
        foreach ($rows as $row) {
            $photos = $photoModel->getValidPhotosByActivity((int) $row['id']);

            // Fallback jika belum ada di landing_activity_photos tapi ada di landing_activities.image
            if (empty($photos) && !empty($row['image'])) {
                $ext      = strtolower(pathinfo($row['image'], PATHINFO_EXTENSION));
                $isSafe   = in_array($ext, self::SAFE_IMAGE_EXT, true);
                $filePath = UPLOAD_PATH . 'activities/' . basename($row['image']);

                if ($isSafe && is_file($filePath)) {
                    $photos = [
                        [
                            'id'          => 0,
                            'activity_id' => $row['id'],
                            'image'       => $row['image'],
                            'caption'     => $row['description'] ?? '',
                            'is_cover'    => 1,
                        ]
                    ];
                }
            }

            if (!empty($photos)) {
                // Tentukan cover
                $coverPhoto = null;
                foreach ($photos as $p) {
                    if (!empty($p['is_cover'])) {
                        $coverPhoto = $p;
                        break;
                    }
                }
                if (!$coverPhoto) {
                    $coverPhoto = $photos[0];
                }

                $row['cover_image'] = $coverPhoto['image'];
                $row['image']       = $coverPhoto['image']; // kompatibilitas
                $row['photos']      = $photos;
                $row['photo_count'] = count($photos);

                $result[] = $row;
            }
        }

        return $result;
    }
}
