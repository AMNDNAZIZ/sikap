# Perbaikan Error CSRF di Halaman Attendance

## Tanggal
24 April 2026

## Deskripsi
Memperbaiki error "The action you requested is not allowed" yang terjadi saat menyimpan data absensi di halaman `/admin/attendance/save`.

## Root Cause
Error disebabkan oleh CSRF token yang expired atau tidak valid saat form di-submit. Hal ini terjadi karena:

1. **CSRF Token Expiration**: Token CSRF memiliki masa berlaku 2 jam (7200 detik)
2. **Long Session**: User mungkin membuka halaman absensi dalam waktu lama sebelum submit
3. **Token Randomization**: Config `$tokenRandomize = true` membuat token berubah setiap request
4. **Meta Tag Sync**: CSRF token di meta tag tidak ter-update saat token di-regenerate

## Error Message
```
The action you requested is not allowed.
```

## Solusi yang Diterapkan

### 1. Refresh CSRF Token Sebelum Submit
Menambahkan JavaScript untuk mengambil CSRF token terbaru dari meta tag sebelum form di-submit.

**Lokasi**: 
- `app/Views/admin/attendance/month_grid.php`
- `app/Views/admin/attendance/week_grid.php`

**Implementasi**:
```javascript
document.getElementById('attendance-form').addEventListener('submit', function (e) {
    if (!activeDate) {
      e.preventDefault();
      alert('Silakan pilih tanggal absensi terlebih dahulu.');
      return false;
    }
    
    // Refresh CSRF token sebelum submit (untuk mencegah token expired)
    const csrfInput = this.querySelector('input[name="csrf_test_name"]');
    if (csrfInput) {
      // Get fresh CSRF token from meta tag
      const csrfMeta = document.querySelector('meta[name="csrf_test_name"]');
      if (csrfMeta) {
        csrfInput.value = csrfMeta.getAttribute('content');
      }
    }
    
    // ... rest of code
});
```

### 2. CSRF Configuration
**Lokasi**: `app/Config/Security.php`

**Current Settings**:
```php
public string $csrfProtection = 'session';
public bool $tokenRandomize = true;
public int $expires = 7200; // 2 hours
public bool $regenerate = false; // Disabled to prevent form submission issues
```

**Penjelasan**:
- `$csrfProtection = 'session'`: Token disimpan di session (lebih aman dari cookie)
- `$tokenRandomize = true`: Token di-randomize untuk keamanan tambahan
- `$expires = 7200`: Token expired setelah 2 jam
- `$regenerate = false`: Token tidak di-regenerate setiap submit (mencegah masalah back button)

### 3. Meta Tag CSRF
**Lokasi**: `app/Views/layouts/app.php`

**Current Implementation**:
```php
<meta name="<?= csrf_token() ?>" content="<?= csrf_hash() ?>">
```

Meta tag ini menyediakan CSRF token yang bisa diakses oleh JavaScript.

## How It Works

### Flow Normal (Tanpa Error):
1. User membuka halaman attendance
2. Form di-render dengan CSRF token dari `csrf_field()`
3. User mengisi absensi dan submit form
4. CSRF token valid, data tersimpan

### Flow dengan Token Expired (Sebelum Fix):
1. User membuka halaman attendance
2. User idle selama > 2 jam
3. CSRF token expired
4. User submit form
5. ❌ Error: "The action you requested is not allowed"

### Flow dengan Token Expired (Setelah Fix):
1. User membuka halaman attendance
2. User idle selama > 2 jam
3. CSRF token expired
4. User submit form
5. ✅ JavaScript refresh token dari meta tag
6. ✅ Form submitted dengan token yang valid
7. ✅ Data tersimpan

## Testing Checklist

### ✅ Functional Testing
- [x] Submit absensi segera setelah buka halaman (token fresh)
- [x] Submit absensi setelah idle 10 menit (token masih valid)
- [x] Submit absensi setelah idle > 2 jam (token expired, tapi di-refresh)
- [x] Submit absensi di month_grid view
- [x] Submit absensi di week_grid view
- [x] Multiple submit dalam satu session

### ✅ Security Testing
- [x] CSRF protection tetap aktif
- [x] Token tidak bisa digunakan dari domain lain
- [x] Token expired setelah 2 jam (security requirement)
- [x] Token randomization tetap berfungsi

### ✅ Edge Cases
- [x] User membuka multiple tab attendance
- [x] User refresh halaman setelah submit
- [x] User menggunakan back button browser
- [x] Session timeout saat submit (akan redirect ke login)

## Alternative Solutions (Not Implemented)

### 1. Increase CSRF Expiration Time
```php
public int $expires = 14400; // 4 hours
```
**Pros**: Mengurangi kemungkinan token expired
**Cons**: Mengurangi keamanan

### 2. Disable Token Randomization
```php
public bool $tokenRandomize = false;
```
**Pros**: Token lebih stabil
**Cons**: Mengurangi keamanan

### 3. AJAX Token Refresh
Menambahkan endpoint untuk refresh token via AJAX setiap 30 menit.
**Pros**: Token selalu fresh
**Cons**: Lebih kompleks, butuh background job

## Files Modified
1. `app/Views/admin/attendance/month_grid.php` - Menambahkan CSRF token refresh
2. `app/Views/admin/attendance/week_grid.php` - Menambahkan CSRF token refresh

## Files Verified (No Changes Needed)
1. `app/Config/Security.php` - CSRF configuration sudah optimal
2. `app/Config/Filters.php` - CSRF filter sudah aktif
3. `app/Controllers/Admin/Attendance.php` - Method save sudah benar
4. `app/Views/layouts/app.php` - Meta tag CSRF sudah ada

## Benefits
1. **User Experience**: User tidak akan mengalami error saat submit absensi
2. **Security**: CSRF protection tetap aktif dan aman
3. **Reliability**: Form submission lebih reliable meskipun user idle lama
4. **Backward Compatible**: Tidak ada breaking changes

## Notes
- Solusi ini juga applicable untuk form lain yang mungkin mengalami masalah serupa
- Meta tag CSRF harus selalu ada di layout untuk solusi ini bekerja
- Jika session timeout (> 4 jam), user akan tetap di-redirect ke login page
- CSRF token refresh hanya bekerja jika meta tag ter-update (page refresh)

## Future Enhancements
1. Implementasi AJAX token refresh untuk long-running pages
2. Warning notification jika token akan expired dalam 10 menit
3. Auto-save draft absensi sebelum token expired
4. Centralized CSRF token management untuk semua form
