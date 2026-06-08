# Perbaikan Masalah Logout Tiba-tiba

## Masalah
Pengguna sering mengalami logout tiba-tiba saat sedang mengisi data (contoh: mengisi Master CP), yang menyebabkan data yang sedang diisi hilang dan harus login ulang.

## Penyebab Masalah
1. **Session Timeout Terlalu Pendek**: Session timeout hanya 2 jam (7200 detik)
2. **Session Match IP Terlalu Ketat**: Pencocokan IP address menyebabkan logout saat IP berubah (mobile network, VPN, dll)
3. **Session Regeneration Terlalu Sering**: Session ID di-regenerate setiap 5 menit, bisa menyebabkan konflik saat submit form
4. **Tidak Ada Keep-Alive Mechanism**: Tidak ada mekanisme untuk menjaga session tetap aktif saat user sedang bekerja

## Solusi yang Diterapkan

### 1. Perpanjangan Session Timeout
**File**: `app/Config/Session.php`
- Session expiration diperpanjang dari 2 jam menjadi **4 jam** (14400 detik)
- Session timeout filter juga disesuaikan menjadi 4 jam

```php
public int $expiration = 14400; // 4 hours (sebelumnya 7200)
```

### 2. Nonaktifkan Session Match IP
**File**: `app/Config/Session.php`
- `matchIP` diubah dari `true` menjadi `false`
- Mencegah logout saat IP address berubah (mobile network, VPN, proxy)

```php
public bool $matchIP = false; // Sebelumnya true
```

**Catatan Keamanan**: Meskipun ini mengurangi sedikit keamanan, trade-off ini acceptable karena:
- Aplikasi masih menggunakan CSRF protection
- Session ID tetap di-regenerate secara berkala
- User experience lebih baik (tidak logout tiba-tiba)

### 3. Kurangi Frekuensi Session Regeneration
**File**: `app/Config/Session.php`
- `timeToUpdate` diperpanjang dari 5 menit menjadi **10 menit** (600 detik)
- Mengurangi kemungkinan konflik session saat submit form

```php
public int $timeToUpdate = 600; // 10 minutes (sebelumnya 300)
```

### 4. Perbaikan Session Timeout Filter
**File**: `app/Filters/SessionTimeoutFilter.php`

Perubahan:
- Skip timeout check untuk AJAX requests (mencegah logout saat submit form)
- Inisialisasi `last_activity` jika belum ada (untuk session yang sudah ada)
- Pesan error lebih informatif

```php
// Skip for AJAX requests to prevent disrupting form submissions
if ($request->isAJAX()) {
    $session->set('last_activity', time());
    return;
}

// If last_activity is not set, initialize it
if (!$lastActivity) {
    $session->set('last_activity', time());
    return;
}
```

### 5. Session Keep-Alive Mechanism
**File Baru**: `public/assets/js/session-keepalive.js`

Fitur:
- Otomatis ping server setiap 5 menit untuk menjaga session tetap aktif
- Hanya aktif saat user sedang bekerja (ada aktivitas mouse/keyboard)
- Berhenti otomatis setelah 10 menit idle (tidak ada aktivitas)
- Tidak mengganggu performa karena menggunakan background ping

**File**: `app/Controllers/Auth.php`
- Menambahkan method `pingSession()` untuk menerima ping dari JavaScript

**File**: `app/Config/Routes.php`
- Menambahkan route `/auth/ping-session` untuk endpoint keep-alive

**File**: `app/Views/layouts/app.php`
- Menambahkan script keep-alive untuk user yang sudah login

## Cara Kerja Keep-Alive

1. **User Login**: Script keep-alive dimuat otomatis
2. **User Aktif**: Script mendeteksi aktivitas (mouse, keyboard, scroll, touch)
3. **Ping Berkala**: Setiap 5 menit, script mengirim ping ke server untuk update `last_activity`
4. **User Idle**: Jika tidak ada aktivitas selama 10 menit, ping dihentikan untuk menghemat resource
5. **User Aktif Kembali**: Ping dimulai lagi saat user kembali aktif

## Konfigurasi yang Dapat Disesuaikan

### Session Timeout
Jika ingin mengubah durasi session:

```php
// app/Config/Session.php
public int $expiration = 14400; // Ubah sesuai kebutuhan (dalam detik)

// app/Filters/SessionTimeoutFilter.php
const SESSION_TIMEOUT = 14400; // Harus sama dengan expiration
```

### Keep-Alive Interval
Jika ingin mengubah frekuensi ping:

```javascript
// public/assets/js/session-keepalive.js
const PING_INTERVAL = 5 * 60 * 1000; // 5 menit (dalam milliseconds)
const IDLE_TIMEOUT = 10 * 60 * 1000; // 10 menit idle
```

## Testing

### Test 1: Session Tidak Logout Saat Mengisi Form
1. Login ke aplikasi
2. Buka halaman Master CP
3. Biarkan halaman terbuka selama 30 menit
4. Isi form dan submit
5. **Expected**: Data tersimpan tanpa logout

### Test 2: Keep-Alive Berfungsi
1. Login ke aplikasi
2. Buka browser console (F12)
3. Lihat log "Session keep-alive started"
4. Tunggu 5 menit
5. **Expected**: Muncul request ke `/auth/ping-session` di Network tab

### Test 3: Idle Detection
1. Login ke aplikasi
2. Biarkan idle (tidak ada aktivitas) selama 10 menit
3. Lihat console
4. **Expected**: Muncul log "Session keep-alive stopped (user idle)"

### Test 4: Session Masih Expire Setelah Timeout
1. Login ke aplikasi
2. Biarkan idle selama lebih dari 4 jam
3. Coba akses halaman lain
4. **Expected**: Redirect ke login dengan pesan "Sesi Anda telah berakhir"

## Monitoring

### Cek Session di Database
```sql
SELECT * FROM ci_sessions ORDER BY timestamp DESC LIMIT 10;
```

### Cek Log Session Timeout
```bash
tail -f writable/logs/log-*.log | grep "Session"
```

## Rollback (Jika Diperlukan)

Jika ingin kembali ke konfigurasi lama:

1. **Session.php**: Ubah kembali `expiration` ke 7200, `matchIP` ke true, `timeToUpdate` ke 300
2. **SessionTimeoutFilter.php**: Ubah `SESSION_TIMEOUT` ke 7200
3. **app.php**: Hapus baris `<script src="<?= base_url('assets/js/session-keepalive.js') ?>"></script>`
4. **Routes.php**: Hapus route `/auth/ping-session`

## Rekomendasi Tambahan

1. **Backup Data Otomatis**: Pertimbangkan menambahkan auto-save untuk form panjang
2. **Session Storage**: Gunakan Redis untuk session storage jika traffic tinggi
3. **Monitoring**: Setup monitoring untuk track session timeout issues
4. **User Education**: Informasikan user tentang durasi session dan pentingnya save berkala

## Changelog

- **2026-04-21**: Implementasi perbaikan session timeout dan keep-alive mechanism
  - Perpanjang session timeout dari 2 jam ke 4 jam
  - Nonaktifkan session match IP
  - Tambahkan session keep-alive script
  - Perbaiki session timeout filter untuk AJAX requests
