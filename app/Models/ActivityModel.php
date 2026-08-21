<?php
namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table      = 'landing_activities';
    protected $primaryKey = 'id';
    protected $useTimestamps  = true;
    protected $allowedFields  = ['title', 'description', 'image', 'date', 'created_by'];

    /** Ekstensi gambar yang diizinkan untuk ditampilkan di halaman publik */
    private const SAFE_IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];

    public function getActivitiesWithAuthor()
    {
        return $this->select('landing_activities.*, users.fullname as uploader_name')
            ->join('users', 'users.id = landing_activities.created_by', 'left')
            ->orderBy('landing_activities.date', 'DESC')
            ->findAll();
    }

    /**
     * Ambil aktivitas untuk halaman publik — hanya yang punya gambar aman dan ada di disk.
     */
    public function getPublicActivities(): array
    {
        $rows = $this->orderBy('date', 'DESC')->findAll();

        return array_filter($rows, function ($row) {
            if (empty($row['image'])) return false;

            $ext      = strtolower(pathinfo($row['image'], PATHINFO_EXTENSION));
            $isSafe   = in_array($ext, self::SAFE_IMAGE_EXT, true);
            $filePath = UPLOAD_PATH . 'activities/' . basename($row['image']);
            $exists   = is_file($filePath);

            return $isSafe && $exists;
        });
    }
}
