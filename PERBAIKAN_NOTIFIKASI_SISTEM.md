# Perbaikan Notifikasi Sistem Dashboard Admin

## Masalah
Notifikasi sistem di dashboard admin menampilkan informasi yang tidak akurat (hardcoded/statis):
1. "Backup database berhasil dilakukan hari ini pukul 02:00" - tidak ada sistem backup otomatis
2. "Versi terbaru 2.1.0 tersedia untuk diupdate" - tidak ada update checker
3. "Lisensi akan berakhir dalam 15 hari" - informasi tidak dinamis

## Solusi Implementasi

### 1. Dashboard Controller (`app/Controllers/Dashboard.php`)
Menambahkan logika untuk generate notifikasi sistem yang real dan dinamis:

```php
// Generate real system notifications
$systemNotifications = [];

// Check 1: Users with must_change_password flag
$usersNeedPasswordChange = $db->table('users')
    ->where('must_change_password', 1)
    ->countAllResults();
if ($usersNeedPasswordChange > 0) {
    $systemNotifications[] = [
        'type' => 'warning',
        'icon' => 'exclamation-triangle',
        'title' => 'Pengguna Perlu Ganti Password',
        'message' => $usersNeedPasswordChange . ' pengguna perlu mengganti password mereka',
    ];
}

// Check 2: Classes without homeroom teacher
$classesWithoutTeacher = $db->table('classes')
    ->where('teacher_id IS NULL')
    ->orWhere('teacher_id', 0)
    ->countAllResults();
if ($classesWithoutTeacher > 0) {
    $systemNotifications[] = [
        'type' => 'info',
        'icon' => 'info-circle',
        'title' => 'Kelas Tanpa Wali Kelas',
        'message' => $classesWithoutTeacher . ' kelas belum memiliki wali kelas',
    ];
}

// Check 3: Students without active class
$studentsWithoutClass = $db->table('students s')
    ->select('s.id')
    ->join('student_records sr', 'sr.student_id = s.id AND sr.status = "aktif"', 'left')
    ->where('sr.id IS NULL')
    ->countAllResults();
if ($studentsWithoutClass > 0) {
    $systemNotifications[] = [
        'type' => 'warning',
        'icon' => 'exclamation-triangle',
        'title' => 'Siswa Tanpa Kelas Aktif',
        'message' => $studentsWithoutClass . ' siswa belum memiliki kelas aktif di tahun ajaran ini',
    ];
}

// Check 4: Academic year ending soon (within 30 days)
if ($activeYear) {
    $endDate = strtotime($activeYear['end_date']);
    $todayTimestamp = strtotime($today);
    $daysRemaining = floor(($endDate - $todayTimestamp) / 86400);
    
    if ($daysRemaining > 0 && $daysRemaining <= 30) {
        $systemNotifications[] = [
            'type' => 'info',
            'icon' => 'calendar-event',
            'title' => 'Tahun Ajaran Akan Berakhir',
            'message' => 'Tahun ajaran ' . $activeYear['year'] . ' akan berakhir dalam ' . $daysRemaining . ' hari',
        ];
    }
}

// Check 5: License expiration
$licenseModel = new \App\Models\LicenseModel();
$license = $licenseModel->first();
if ($license && !empty($license['expires_at'])) {
    $expiresAt = strtotime($license['expires_at']);
    $todayTimestamp = strtotime($today);
    $daysRemaining = floor(($expiresAt - $todayTimestamp) / 86400);
    
    if ($daysRemaining > 0 && $daysRemaining <= 30) {
        $systemNotifications[] = [
            'type' => 'warning',
            'icon' => 'shield-exclamation',
            'title' => 'Lisensi Akan Berakhir',
            'message' => 'Lisensi aplikasi akan berakhir dalam ' . $daysRemaining . ' hari',
        ];
    } elseif ($daysRemaining <= 0) {
        $systemNotifications[] = [
            'type' => 'danger',
            'icon' => 'shield-x',
            'title' => 'Lisensi Telah Berakhir',
            'message' => 'Lisensi aplikasi telah berakhir. Silakan perpanjang lisensi Anda',
        ];
    }
}

// Default: If no notifications, show success message
if (empty($systemNotifications)) {
    $systemNotifications[] = [
        'type' => 'success',
        'icon' => 'check-circle',
        'title' => 'Sistem Berjalan Normal',
        'message' => 'Tidak ada masalah yang terdeteksi pada sistem',
    ];
}
```

### 2. Admin Dashboard View (`app/Views/dashboard/admin_modern.php`)
Mengganti hardcoded notifications dengan dynamic loop:

```php
<?php if (!empty($systemNotifications)): ?>
  <?php foreach ($systemNotifications as $notification): ?>
    <?php
      // Map notification type to Bootstrap color
      $colorMap = [
        'success' => 'success',
        'info' => 'info',
        'warning' => 'warning',
        'danger' => 'danger',
      ];
      $color = $colorMap[$notification['type']] ?? 'secondary';
    ?>
    <div class="list-group-item py-3 px-4 border-0">
      <div class="d-flex align-items-center">
        <div class="flex-shrink-0">
          <div class="rounded-circle bg-<?= $color ?> bg-opacity-10 text-<?= $color ?> p-2">
            <i class="bi bi-<?= $notification['icon'] ?>"></i>
          </div>
        </div>
        <div class="flex-grow-1 ms-3">
          <h6 class="mb-1 fw-bold"><?= esc($notification['title']) ?></h6>
          <p class="mb-0 small text-muted"><?= esc($notification['message']) ?></p>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
```

## Jenis Notifikasi yang Ditampilkan

### 1. Pengguna Perlu Ganti Password (Warning)
- **Kondisi**: Ada user dengan flag `must_change_password = 1`
- **Icon**: exclamation-triangle
- **Warna**: Warning (kuning)
- **Contoh**: "3 pengguna perlu mengganti password mereka"

### 2. Kelas Tanpa Wali Kelas (Info)
- **Kondisi**: Ada kelas dengan `teacher_id IS NULL` atau `teacher_id = 0`
- **Icon**: info-circle
- **Warna**: Info (biru)
- **Contoh**: "2 kelas belum memiliki wali kelas"

### 3. Siswa Tanpa Kelas Aktif (Warning)
- **Kondisi**: Ada siswa yang tidak memiliki record aktif di `student_records`
- **Icon**: exclamation-triangle
- **Warna**: Warning (kuning)
- **Contoh**: "5 siswa belum memiliki kelas aktif di tahun ajaran ini"

### 4. Tahun Ajaran Akan Berakhir (Info)
- **Kondisi**: Tahun ajaran aktif akan berakhir dalam 30 hari atau kurang
- **Icon**: calendar-event
- **Warna**: Info (biru)
- **Contoh**: "Tahun ajaran 2025/2026 akan berakhir dalam 15 hari"

### 5. Lisensi Akan Berakhir (Warning)
- **Kondisi**: Lisensi akan berakhir dalam 30 hari atau kurang
- **Icon**: shield-exclamation
- **Warna**: Warning (kuning)
- **Contoh**: "Lisensi aplikasi akan berakhir dalam 10 hari"

### 6. Lisensi Telah Berakhir (Danger)
- **Kondisi**: Lisensi sudah melewati tanggal expired
- **Icon**: shield-x
- **Warna**: Danger (merah)
- **Contoh**: "Lisensi aplikasi telah berakhir. Silakan perpanjang lisensi Anda"

### 7. Sistem Berjalan Normal (Success)
- **Kondisi**: Tidak ada masalah yang terdeteksi
- **Icon**: check-circle
- **Warna**: Success (hijau)
- **Contoh**: "Tidak ada masalah yang terdeteksi pada sistem"

## Keuntungan

1. **Akurat**: Notifikasi berdasarkan data real dari database
2. **Dinamis**: Update otomatis setiap kali dashboard di-refresh
3. **Actionable**: Memberikan informasi yang bisa ditindaklanjuti admin
4. **User-friendly**: Menggunakan warna dan icon yang sesuai dengan tingkat urgency
5. **Extensible**: Mudah menambahkan notifikasi baru di masa depan

## Cara Menambahkan Notifikasi Baru

Untuk menambahkan notifikasi sistem baru, tambahkan logika check di `app/Controllers/Dashboard.php` (case 1: Admin):

```php
// Check for your condition
$yourCondition = $db->table('your_table')
    ->where('your_condition')
    ->countAllResults();
    
if ($yourCondition > 0) {
    $systemNotifications[] = [
        'type' => 'warning', // success, info, warning, danger
        'icon' => 'your-bootstrap-icon', // Bootstrap Icons name
        'title' => 'Your Notification Title',
        'message' => 'Your notification message with ' . $yourCondition . ' items',
    ];
}
```

## Testing

Untuk testing notifikasi:

1. **Test Warning - Users Need Password Change**:
   ```sql
   UPDATE users SET must_change_password = 1 WHERE id = 1;
   ```

2. **Test Info - Classes Without Teacher**:
   ```sql
   UPDATE classes SET teacher_id = NULL WHERE id = 1;
   ```

3. **Test Warning - Students Without Class**:
   ```sql
   DELETE FROM student_records WHERE student_id = 1;
   ```

4. **Test Info - Academic Year Ending**:
   - Set active academic year end_date to within 30 days

5. **Test License Expiration**:
   - Set license expires_at to within 30 days or past date

## File yang Dimodifikasi

1. `app/Controllers/Dashboard.php` - Added system notifications logic
2. `app/Views/dashboard/admin_modern.php` - Replaced hardcoded notifications with dynamic display

## Status
✅ Selesai - Notifikasi sistem sekarang menampilkan informasi yang akurat dan dinamis
