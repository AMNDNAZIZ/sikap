<?php
namespace App\Controllers\Admin\Cms;

use App\Controllers\BaseController;
use App\Models\ActivityModel;
use App\Models\ActivityPhotoModel;

class Activities extends BaseController
{
    protected $activityModel;
    protected $activityPhotoModel;

    // MIME type yang diizinkan — hanya gambar
    private const ALLOWED_MIME = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    // Ekstensi yang diizinkan — hanya gambar
    private const ALLOWED_EXT  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    public function __construct()
    {
        $this->activityModel      = new ActivityModel();
        $this->activityPhotoModel = new ActivityPhotoModel();
    }

    public function index()
    {
        $data['activities'] = $this->activityModel->getActivitiesWithAuthor();
        $data['title']      = 'Manajemen Dokumentasi Kegiatan';
        return view('admin/cms/activities/index', $data);
    }

    public function create()
    {
        $data['title'] = 'Tambah Dokumentasi Album Kegiatan';
        return view('admin/cms/activities/create', $data);
    }

    public function store()
    {
        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $date        = $this->request->getPost('date') ?: date('Y-m-d');
        $coverIndex  = (int) ($this->request->getPost('cover_index') ?? 0);

        if (empty($title)) {
            return redirect()->back()->withInput()->with('error', 'Judul kegiatan wajib diisi.');
        }

        // Ambil file foto yang diunggah
        $files    = $this->request->getFileMultiple('photos');
        $captions = (array) ($this->request->getPost('photo_captions') ?? []);

        // Fallback untuk single file 'image' jika ada form lama
        if (empty($files) || (count($files) === 1 && !$files[0]->isValid())) {
            $singleFile = $this->request->getFile('image');
            if ($singleFile && $singleFile->isValid()) {
                $files = [$singleFile];
                $captions = [$description];
            }
        }

        // Validasi dan simpan semua file yang valid
        $savedPhotos = [];
        if (!empty($files)) {
            foreach ($files as $idx => $file) {
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $savedName = $this->saveUploadedPhoto($file);
                    if ($savedName) {
                        $savedPhotos[] = [
                            'image'    => $savedName,
                            'caption'  => trim((string) ($captions[$idx] ?? '')),
                            'is_cover' => ($idx === $coverIndex) ? 1 : 0,
                        ];
                    }
                }
            }
        }

        if (empty($savedPhotos)) {
            return redirect()->back()->withInput()->with('error', 'Harap unggah minimal 1 foto dokumentasi yang valid (JPG/PNG/WEBP, maks 5MB).');
        }

        // Pastikan ada 1 cover
        $hasCover = false;
        foreach ($savedPhotos as &$sp) {
            if ($sp['is_cover'] === 1) {
                $hasCover = true;
                break;
            }
        }
        if (!$hasCover) {
            $savedPhotos[0]['is_cover'] = 1;
        }

        // Cover image untuk tabel utama
        $coverImage = $savedPhotos[0]['image'];
        foreach ($savedPhotos as $sp) {
            if ($sp['is_cover'] === 1) {
                $coverImage = $sp['image'];
                break;
            }
        }

        // Simpan kegiatan utama
        $activityId = $this->activityModel->insert([
            'title'       => $title,
            'description' => $description,
            'image'       => $coverImage,
            'date'        => $date,
            'created_by'  => session()->get('user')['id'] ?? 1,
        ]);

        // Simpan foto-foto ke landing_activity_photos
        foreach ($savedPhotos as $order => $p) {
            $this->activityPhotoModel->insert([
                'activity_id' => $activityId,
                'image'       => $p['image'],
                'caption'     => $p['caption'] ?: $description,
                'is_cover'    => $p['is_cover'],
                'sort_order'  => $order,
            ]);
        }

        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi album kegiatan berhasil ditambahkan (' . count($savedPhotos) . ' foto tersimpan)');
    }

    public function edit($id)
    {
        $activity = $this->activityModel->find($id);
        if (!$activity) {
            return redirect()->to('admin/cms/activities')->with('error', 'Data tidak ditemukan.');
        }

        // Ambil foto-foto yang ada
        $photos = $this->activityPhotoModel->where('activity_id', $id)
            ->orderBy('is_cover', 'DESC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Jika foto di child table masih kosong tapi di activity ada image, sinkronkan 1 foto
        if (empty($photos) && !empty($activity['image'])) {
            $this->activityPhotoModel->insert([
                'activity_id' => $id,
                'image'       => $activity['image'],
                'caption'     => $activity['description'] ?? '',
                'is_cover'    => 1,
                'sort_order'  => 0,
            ]);
            $photos = $this->activityPhotoModel->where('activity_id', $id)->findAll();
        }

        $data['activity'] = $activity;
        $data['photos']   = $photos;
        $data['title']    = 'Edit Dokumentasi Album Kegiatan';
        return view('admin/cms/activities/edit', $data);
    }

    public function update($id)
    {
        $activity = $this->activityModel->find($id);
        if (!$activity) {
            return redirect()->to('admin/cms/activities')->with('error', 'Data tidak ditemukan.');
        }

        $title       = trim((string) $this->request->getPost('title'));
        $description = trim((string) $this->request->getPost('description'));
        $date        = $this->request->getPost('date') ?: date('Y-m-d');
        $coverChoice = (string) $this->request->getPost('cover_choice');

        // 1. Tangani penghapusan foto lama
        $deletePhotos = (array) ($this->request->getPost('delete_photos') ?? []);
        if (!empty($deletePhotos)) {
            foreach ($deletePhotos as $delId) {
                $delPhoto = $this->activityPhotoModel->find($delId);
                if ($delPhoto && (int)$delPhoto['activity_id'] === (int)$id) {
                    $filePath = UPLOAD_PATH . 'activities/' . $delPhoto['image'];
                    if (is_file($filePath)) {
                        @unlink($filePath);
                    }
                    $this->activityPhotoModel->delete($delId);
                }
            }
        }

        // 2. Perbarui caption & status cover foto lama yang tersisa
        $existingCaptions = (array) ($this->request->getPost('existing_captions') ?? []);
        $remainingPhotos  = $this->activityPhotoModel->where('activity_id', $id)->findAll();

        foreach ($remainingPhotos as $p) {
            $photoId = $p['id'];
            $newCaption = trim((string) ($existingCaptions[$photoId] ?? $p['caption']));
            $isCover    = ($coverChoice === 'existing_' . $photoId) ? 1 : 0;

            $this->activityPhotoModel->update($photoId, [
                'caption'  => $newCaption,
                'is_cover' => $isCover,
            ]);
        }

        // 3. Tangani unggahan foto-foto baru
        $newFiles    = $this->request->getFileMultiple('photos');
        $newCaptions = (array) ($this->request->getPost('new_photo_captions') ?? []);

        if (!empty($newFiles)) {
            $startOrder = count($remainingPhotos);
            foreach ($newFiles as $idx => $file) {
                if ($file && $file->isValid() && !$file->hasMoved()) {
                    $savedName = $this->saveUploadedPhoto($file);
                    if ($savedName) {
                        $isCover = ($coverChoice === 'new_' . $idx) ? 1 : 0;
                        $this->activityPhotoModel->insert([
                            'activity_id' => $id,
                            'image'       => $savedName,
                            'caption'     => trim((string) ($newCaptions[$idx] ?? '')) ?: $description,
                            'is_cover'    => $isCover,
                            'sort_order'  => $startOrder + $idx,
                        ]);
                    }
                }
            }
        }

        // 4. Pastikan ada tepat 1 foto sebagai cover
        $allCurrentPhotos = $this->activityPhotoModel->where('activity_id', $id)->findAll();
        $hasCover         = false;
        $coverImage       = $activity['image'];

        foreach ($allCurrentPhotos as $p) {
            if ($p['is_cover'] == 1) {
                $hasCover   = true;
                $coverImage = $p['image'];
                break;
            }
        }

        if (!$hasCover && !empty($allCurrentPhotos)) {
            // Jadikan foto pertama sebagai cover
            $this->activityPhotoModel->update($allCurrentPhotos[0]['id'], ['is_cover' => 1]);
            $coverImage = $allCurrentPhotos[0]['image'];
        }

        // 5. Update data utama kegiatan
        $this->activityModel->update($id, [
            'title'       => $title,
            'description' => $description,
            'image'       => $coverImage,
            'date'        => $date,
        ]);

        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi album kegiatan berhasil diperbarui');
    }

    public function delete($id)
    {
        $activity = $this->activityModel->find($id);
        if ($activity) {
            // Hapus semua foto dari tabel child dan disk
            $photos = $this->activityPhotoModel->where('activity_id', $id)->findAll();
            foreach ($photos as $p) {
                $filePath = UPLOAD_PATH . 'activities/' . ($p['image'] ?? '');
                if (!empty($p['image']) && is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            $this->activityPhotoModel->where('activity_id', $id)->delete();

            // Hapus cover foto utama jika masih tersisa
            $mainFile = UPLOAD_PATH . 'activities/' . ($activity['image'] ?? '');
            if (!empty($activity['image']) && is_file($mainFile)) {
                @unlink($mainFile);
            }

            $this->activityModel->delete($id);
        }

        return redirect()->to('admin/cms/activities')->with('success', 'Dokumentasi album kegiatan berhasil dihapus');
    }

    /**
     * Helper simpan foto aman ke folder uploads/activities
     */
    private function saveUploadedPhoto($file): ?string
    {
        if (!$file || !$file->isValid() || $file->hasMoved()) {
            return null;
        }

        $mime = $file->getMimeType();
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            return null;
        }

        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, self::ALLOWED_EXT, true)) {
            return null;
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $safeName = bin2hex(random_bytes(16)) . '_' . time() . '.' . $this->mimeToExt($mime);

        $uploadDir = UPLOAD_PATH . 'activities';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $file->move($uploadDir, $safeName);
        return $safeName;
    }

    /**
     * Mapping MIME type ke ekstensi aman.
     */
    private function mimeToExt(string $mime): string
    {
        return match($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png'               => 'png',
            'image/gif'               => 'gif',
            'image/webp'              => 'webp',
            default                   => 'jpg',
        };
    }
}
