<?php

/**
 * Notification Helper
 *
 * Menyediakan fungsi global yang bisa dipanggil dari controller manapun
 * untuk mengirim notifikasi ke satu atau banyak user.
 *
 * Contoh pemakaian:
 *   notify_user(5, 'tugas_baru', 'Tugas Baru: Matematika', 'Deadline 3 hari lagi', '/siswa/tugas/12');
 *   notify_users([3, 7, 12], 'pengumuman_baru', 'Pengumuman', 'Libur besok', '/siswa/announcements');
 *   notify_class(2, 'tugas_baru', 'Tugas Fisika', 'Kerjakan sebelum Jumat', '/siswa/tugas/8');
 */

if (!function_exists('notify_user')) {
    /**
     * Kirim notifikasi ke satu user.
     *
     * @param int         $userId   ID user penerima
     * @param string      $type     Tipe notifikasi (lihat ENUM di migration)
     * @param string      $title    Judul singkat
     * @param string      $message  Pesan detail (opsional)
     * @param string|null $url      URL tujuan saat diklik (opsional)
     * @param string|null $relType  Jenis resource (e.g. 'tugas', 'announcement')
     * @param int|null    $relId    ID resource
     */
    function notify_user(
        int    $userId,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        try {
            $model = new \App\Models\NotificationModel();
            $model->send($userId, $type, $title, $message, $url, $relType, $relId);
        } catch (\Throwable $e) {
            log_message('error', '[notify_user] Gagal kirim notifikasi ke user ' . $userId . ': ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_users')) {
    /**
     * Kirim notifikasi ke banyak user sekaligus (bulk insert).
     *
     * @param int[]       $userIds  Array ID user penerima
     * @param string      $type     Tipe notifikasi
     * @param string      $title    Judul singkat
     * @param string      $message  Pesan detail (opsional)
     * @param string|null $url      URL tujuan
     * @param string|null $relType  Jenis resource
     * @param int|null    $relId    ID resource
     */
    function notify_users(
        array  $userIds,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        if (empty($userIds)) {
            return;
        }
        try {
            $model = new \App\Models\NotificationModel();
            $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
        } catch (\Throwable $e) {
            log_message('error', '[notify_users] Gagal kirim notifikasi bulk: ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_class')) {
    /**
     * Kirim notifikasi ke semua siswa di sebuah kelas.
     *
     * @param int         $classId  ID kelas
     * @param string      $type     Tipe notifikasi
     * @param string      $title    Judul singkat
     * @param string      $message  Pesan detail
     * @param string|null $url      URL tujuan
     * @param string|null $relType  Jenis resource
     * @param int|null    $relId    ID resource
     */
    function notify_class(
        int    $classId,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        try {
            $db = \Config\Database::connect();

            // Ambil user_id semua siswa aktif di kelas ini
            $rows = $db->table('student_records sr')
                ->select('u.id as user_id')
                ->join('students st', 'st.id = sr.student_id')
                ->join('users u', 'u.id = st.user_id', 'left')
                ->where('sr.class_id', $classId)
                ->where('sr.status', 'aktif')
                ->where('u.id IS NOT NULL')
                ->where('u.is_active', 1)
                ->get()->getResultArray();

            $userIds = array_column($rows, 'user_id');

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_class] Gagal kirim notifikasi ke kelas ' . $classId . ': ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_classes')) {
    /**
     * Kirim notifikasi ke semua siswa di beberapa kelas.
     *
     * @param int[]       $classIds Array ID kelas
     * @param string      $type     Tipe notifikasi
     * @param string      $title    Judul singkat
     * @param string      $message  Pesan detail
     * @param string|null $url      URL tujuan
     * @param string|null $relType  Jenis resource
     * @param int|null    $relId    ID resource
     */
    function notify_classes(
        array  $classIds,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        if (empty($classIds)) {
            return;
        }
        try {
            $db = \Config\Database::connect();

            $rows = $db->table('student_records sr')
                ->select('u.id as user_id')
                ->join('students st', 'st.id = sr.student_id')
                ->join('users u', 'u.id = st.user_id', 'left')
                ->whereIn('sr.class_id', $classIds)
                ->where('sr.status', 'aktif')
                ->where('u.id IS NOT NULL')
                ->where('u.is_active', 1)
                ->groupBy('u.id')
                ->get()->getResultArray();

            $userIds = array_column($rows, 'user_id');

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_classes] Gagal kirim notifikasi ke kelas: ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_by_role')) {
    /**
     * Kirim notifikasi ke semua user dengan role tertentu.
     *
     * @param int|int[]   $roleIds  Role ID atau array role ID
     * @param string      $type     Tipe notifikasi
     * @param string      $title    Judul singkat
     * @param string      $message  Pesan detail
     * @param string|null $url      URL tujuan
     */
    function notify_by_role(
        int|array $roleIds,
        string    $type,
        string    $title,
        string    $message = '',
        ?string   $url     = null
    ): void {
        if (!is_array($roleIds)) {
            $roleIds = [$roleIds];
        }
        try {
            $db = \Config\Database::connect();

            $rows = $db->table('users')
                ->select('id')
                ->whereIn('role_id', $roleIds)
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_column($rows, 'id');

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_by_role] Gagal kirim notifikasi ke role: ' . $e->getMessage());
        }
    }
}
