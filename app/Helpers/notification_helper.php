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
     * Kirim notifikasi ke semua siswa (dan opsional orang tua) di sebuah kelas.
     *
     * @param int         $classId        ID kelas
     * @param string      $type           Tipe notifikasi
     * @param string      $title          Judul singkat
     * @param string      $message        Pesan detail
     * @param string|null $url            URL tujuan
     * @param string|null $relType        Jenis resource
     * @param int|null    $relId          ID resource
     * @param bool        $includeParents Apakah orang tua juga dikirimkan notifikasi
     */
    function notify_class(
        int    $classId,
        string $type,
        string $title,
        string $message        = '',
        ?string $url           = null,
        ?string $relType       = null,
        ?int   $relId          = null,
        bool   $includeParents = false
    ): void {
        try {
            $db = \Config\Database::connect();

            // Dapatkan tahun ajaran aktif untuk memastikan hanya siswa aktif tahun berjalan
            $activeYearId = null;
            try {
                $ay = (new \App\Models\AcademicYearModel())->getActiveYear();
                if (!empty($ay['id'])) {
                    $activeYearId = (int) $ay['id'];
                }
            } catch (\Throwable $e) {}

            $builder = $db->table('student_records sr')
                ->select('sr.student_id, st.user_id as student_user_id')
                ->join('students st', 'st.id = sr.student_id')
                ->where('sr.class_id', $classId)
                ->where('sr.status', 'aktif');

            if ($activeYearId) {
                $builder->where('sr.academic_year_id', $activeYearId);
            }

            $rows = $builder->get()->getResultArray();
            if (empty($rows)) {
                return;
            }

            $studentIds = array_unique(array_filter(array_column($rows, 'student_id')));
            $directUserIds = array_unique(array_filter(array_column($rows, 'student_user_id')));

            $targetRoles = [5]; // Siswa
            if ($includeParents) {
                $targetRoles[] = 4; // Orang Tua
            }

            $userRows = $db->table('users')
                ->select('id')
                ->groupStart()
                    ->whereIn('id', !empty($directUserIds) ? $directUserIds : [0])
                    ->orGroupStart()
                        ->where('related_type', 'student')
                        ->whereIn('related_id', $studentIds)
                        ->whereIn('role_id', $targetRoles)
                    ->groupEnd()
                ->groupEnd()
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_unique(array_column($userRows, 'id'));

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
     * Kirim notifikasi ke semua siswa (dan opsional orang tua) di beberapa kelas.
     *
     * @param int[]       $classIds       Array ID kelas
     * @param string      $type           Tipe notifikasi
     * @param string      $title          Judul singkat
     * @param string      $message        Pesan detail
     * @param string|null $url            URL tujuan
     * @param string|null $relType        Jenis resource
     * @param int|null    $relId          ID resource
     * @param bool        $includeParents Apakah orang tua juga dikirimkan notifikasi
     */
    function notify_classes(
        array  $classIds,
        string $type,
        string $title,
        string $message        = '',
        ?string $url           = null,
        ?string $relType       = null,
        ?int   $relId          = null,
        bool   $includeParents = false
    ): void {
        if (empty($classIds)) {
            return;
        }
        try {
            $db = \Config\Database::connect();

            $activeYearId = null;
            try {
                $ay = (new \App\Models\AcademicYearModel())->getActiveYear();
                if (!empty($ay['id'])) {
                    $activeYearId = (int) $ay['id'];
                }
            } catch (\Throwable $e) {}

            $builder = $db->table('student_records sr')
                ->select('sr.student_id, st.user_id as student_user_id')
                ->join('students st', 'st.id = sr.student_id')
                ->whereIn('sr.class_id', $classIds)
                ->where('sr.status', 'aktif');

            if ($activeYearId) {
                $builder->where('sr.academic_year_id', $activeYearId);
            }

            $rows = $builder->get()->getResultArray();
            if (empty($rows)) {
                return;
            }

            $studentIds = array_unique(array_filter(array_column($rows, 'student_id')));
            $directUserIds = array_unique(array_filter(array_column($rows, 'student_user_id')));

            $targetRoles = [5];
            if ($includeParents) {
                $targetRoles[] = 4;
            }

            $userRows = $db->table('users')
                ->select('id')
                ->groupStart()
                    ->whereIn('id', !empty($directUserIds) ? $directUserIds : [0])
                    ->orGroupStart()
                        ->where('related_type', 'student')
                        ->whereIn('related_id', $studentIds)
                        ->whereIn('role_id', $targetRoles)
                    ->groupEnd()
                ->groupEnd()
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_unique(array_column($userRows, 'id'));

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_classes] Gagal kirim notifikasi ke kelas: ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_student_and_parents')) {
    /**
     * Kirim notifikasi ke Siswa dan Orang Tua sekaligus berdasarkan student_id.
     * Sangat berguna untuk panggilan BK, nilai rapor, tugas dinilai, presensi dll.
     *
     * @param int         $studentId ID siswa (students.id)
     * @param string      $type      Tipe notifikasi
     * @param string      $title     Judul singkat
     * @param string      $message   Pesan detail
     * @param string|null $url       URL tujuan
     * @param string|null $relType   Jenis resource
     * @param int|null    $relId     ID resource
     */
    function notify_student_and_parents(
        int    $studentId,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        try {
            $db = \Config\Database::connect();

            // 1. Ambil direct user_id dari tabel students (role 5)
            $student = $db->table('students')->select('user_id')->where('id', $studentId)->get()->getRowArray();
            $directUserId = !empty($student['user_id']) ? (int) $student['user_id'] : 0;

            // 2. Ambil user siswa (role 5) dan orang tua (role 4) dari users via related_id
            $users = $db->table('users')
                ->select('id')
                ->groupStart()
                    ->where('id', $directUserId)
                    ->orGroupStart()
                        ->where('related_type', 'student')
                        ->where('related_id', $studentId)
                        ->whereIn('role_id', [4, 5])
                    ->groupEnd()
                ->groupEnd()
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_unique(array_column($users, 'id'));

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_student_and_parents] Gagal kirim notifikasi ke student ' . $studentId . ': ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_parents_of_student')) {
    /**
     * Kirim notifikasi khusus ke Orang Tua dari siswa tertentu.
     */
    function notify_parents_of_student(
        int    $studentId,
        string $type,
        string $title,
        string $message  = '',
        ?string $url     = null,
        ?string $relType = null,
        ?int   $relId    = null
    ): void {
        try {
            $db = \Config\Database::connect();

            $users = $db->table('users')
                ->select('id')
                ->where('role_id', 4) // Orang Tua
                ->where('related_type', 'student')
                ->where('related_id', $studentId)
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_unique(array_column($users, 'id'));

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_parents_of_student] Gagal: ' . $e->getMessage());
        }
    }
}

if (!function_exists('notify_parents_of_class')) {
    /**
     * Kirim notifikasi khusus ke seluruh orang tua siswa di kelas tertentu.
     */
    function notify_parents_of_class(
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

            $activeYearId = null;
            try {
                $ay = (new \App\Models\AcademicYearModel())->getActiveYear();
                if (!empty($ay['id'])) {
                    $activeYearId = (int) $ay['id'];
                }
            } catch (\Throwable $e) {}

            $builder = $db->table('student_records sr')
                ->select('sr.student_id')
                ->where('sr.class_id', $classId)
                ->where('sr.status', 'aktif');

            if ($activeYearId) {
                $builder->where('sr.academic_year_id', $activeYearId);
            }

            $rows = $builder->get()->getResultArray();
            if (empty($rows)) {
                return;
            }

            $studentIds = array_unique(array_filter(array_column($rows, 'student_id')));

            $users = $db->table('users')
                ->select('id')
                ->where('role_id', 4)
                ->where('related_type', 'student')
                ->whereIn('related_id', $studentIds)
                ->where('is_active', 1)
                ->get()->getResultArray();

            $userIds = array_unique(array_column($users, 'id'));

            if (!empty($userIds)) {
                $model = new \App\Models\NotificationModel();
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_parents_of_class] Gagal: ' . $e->getMessage());
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
     * @param string|null $relType  Jenis resource
     * @param int|null    $relId    ID resource
     */
    function notify_by_role(
        int|array $roleIds,
        string    $type,
        string    $title,
        string    $message = '',
        ?string   $url     = null,
        ?string   $relType = null,
        ?int      $relId   = null
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
                $model->sendBulk($userIds, $type, $title, $message, $url, $relType, $relId);
            }
        } catch (\Throwable $e) {
            log_message('error', '[notify_by_role] Gagal kirim notifikasi ke role: ' . $e->getMessage());
        }
    }
}
