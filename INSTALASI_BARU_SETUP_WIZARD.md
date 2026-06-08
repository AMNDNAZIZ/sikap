# Setup Wizard untuk Instalasi Baru

## Overview

Untuk instalasi baru, sistem akan otomatis mendeteksi dan memandu admin melalui setup wizard untuk konfigurasi awal.

## Setup Flow untuk Admin

### 1. Login Pertama Kali

Setelah login sebagai admin, sistem akan mengecek:

```
1. Apakah data sekolah sudah diisi?
   ├─ Tidak → Redirect ke /admin/school
   └─ Ya → Lanjut ke step 2

2. Apakah ada tahun ajaran?
   ├─ Tidak → Redirect ke /admin/academic-year/create
   └─ Ya → Lanjut ke step 3

3. Apakah ada tahun ajaran yang aktif?
   ├─ Tidak → Redirect ke /admin/academic-year (untuk aktivasi)
   └─ Ya → Tampilkan dashboard
```

### 2. Setup Wizard Steps

#### Step 1: Data Sekolah

**URL:** `/admin/school`

**Message:** "Selamat datang! Silakan lengkapi data sekolah terlebih dahulu."

**Data yang perlu diisi:**
- Nama Sekolah
- NPSN
- Alamat
- Kepala Sekolah
- Logo (optional)
- Kontak

**Action:** Setelah save, otomatis lanjut ke dashboard yang akan redirect ke Step 2

#### Step 2: Buat Tahun Ajaran

**URL:** `/admin/academic-year/create`

**Message:** "Silakan buat tahun ajaran terlebih dahulu untuk memulai menggunakan sistem."

**Data yang perlu diisi:**
- Tahun Ajaran (contoh: 2024/2025)
- Tanggal Mulai
- Tanggal Selesai
- Semester
- Status Aktif (centang untuk mengaktifkan)
- Hari Sekolah (5 atau 6 hari)

**Action:** Setelah save, otomatis lanjut ke dashboard

#### Step 3: Aktivasi Tahun Ajaran (jika belum aktif)

**URL:** `/admin/academic-year`

**Message:** "Tidak ada tahun ajaran yang aktif. Silakan aktifkan salah satu tahun ajaran yang sudah ada."

**Action:** 
- Klik tombol "Aktifkan" pada tahun ajaran yang diinginkan
- Setelah aktivasi, refresh dashboard

### 3. Setup Complete

Setelah semua step selesai, admin akan melihat dashboard normal dengan:
- Statistik siswa, guru, kelas
- Jadwal hari ini
- Rekap ketidakhadiran
- Dan fitur lainnya

## Setup Flow untuk User Lain (Guru, Siswa, Orang Tua)

### Jika Sistem Belum Dikonfigurasi

**Tampilan:** Custom error page

**Message:** 
- Heading: "Sistem Belum Dikonfigurasi"
- Message: "Sistem sedang dalam proses konfigurasi awal. Administrator sedang mengatur tahun ajaran. Silakan hubungi administrator atau coba lagi nanti."

**Action:**
- Button "Logout" untuk keluar
- Tidak bisa akses dashboard sampai admin selesai setup

## Implementation Details

### Dashboard Controller Logic

**File:** `app/Controllers/Dashboard.php`

```php
public function index()
{
    $user = session()->get('user');
    $roleId = $user['role_id'] ?? null;
    
    // Check if this is a fresh installation (for admin only)
    if ($roleId == 1) {
        // Step 1: Check school data
        $school = $schoolModel->first();
        if (!$school || empty($school['name'])) {
            return redirect()->to('/admin/school')
                ->with('info', 'Selamat datang! Silakan lengkapi data sekolah terlebih dahulu.');
        }
        
        // Step 2: Check if academic year exists
        $yearCount = $yearModel->countAll();
        if ($yearCount == 0) {
            return redirect()->to('/admin/academic-year/create')
                ->with('info', 'Silakan buat tahun ajaran terlebih dahulu untuk memulai menggunakan sistem.');
        }
    }
    
    // Step 3: Check if there's an active year
    $activeYear = $yearModel->getActiveYear();
    if (!$activeYear) {
        if ($roleId == 1) {
            return redirect()->to('/admin/academic-year')
                ->with('warning', 'Tidak ada tahun ajaran yang aktif. Silakan aktifkan salah satu tahun ajaran yang sudah ada.');
        }
        
        // For other users, show error
        return view('errors/custom_error', [
            'title' => 'Sistem Belum Siap',
            'heading' => 'Sistem Belum Dikonfigurasi',
            'message' => 'Sistem sedang dalam proses konfigurasi awal...'
        ]);
    }
    
    // Normal dashboard display
    // ...
}
```

## User Experience

### Admin Experience

1. **Login** → Redirect ke School Setup
2. **Isi Data Sekolah** → Save → Redirect ke Dashboard
3. **Dashboard** → Redirect ke Create Academic Year
4. **Buat Tahun Ajaran** → Save → Redirect ke Dashboard
5. **Dashboard** → Tampil normal ✅

**Total Time:** ~5-10 menit untuk setup lengkap

### Non-Admin Experience

1. **Login** → Redirect ke Dashboard
2. **Dashboard** → Tampil error "Sistem Belum Dikonfigurasi"
3. **Logout** → Tunggu admin selesai setup
4. **Login lagi** → Dashboard tampil normal ✅

## Benefits

### 1. User-Friendly
- ✅ Guided setup process
- ✅ Clear instructions at each step
- ✅ No confusion about what to do first

### 2. Prevents Errors
- ✅ No "undefined variable" errors
- ✅ No blank dashboard
- ✅ No broken features due to missing data

### 3. Professional
- ✅ Smooth onboarding experience
- ✅ Clear error messages
- ✅ Helpful guidance

### 4. Flexible
- ✅ Admin can setup at their own pace
- ✅ Can skip optional fields
- ✅ Can edit later

## Testing Checklist

### Fresh Installation Test

- [ ] Install aplikasi baru
- [ ] Login sebagai admin
- [ ] Redirect ke school setup ✓
- [ ] Isi data sekolah
- [ ] Save → Redirect ke dashboard
- [ ] Redirect ke create academic year ✓
- [ ] Buat tahun ajaran dengan status aktif
- [ ] Save → Redirect ke dashboard
- [ ] Dashboard tampil normal ✓

### Existing Installation Test

- [ ] Login sebagai admin
- [ ] Dashboard tampil normal (no redirect) ✓
- [ ] Semua fitur berfungsi ✓

### Non-Admin Test

- [ ] Login sebagai guru/siswa saat setup belum selesai
- [ ] Tampil error "Sistem Belum Dikonfigurasi" ✓
- [ ] Logout button berfungsi ✓
- [ ] Login lagi setelah admin selesai setup
- [ ] Dashboard tampil normal ✓

## Troubleshooting

### Admin Stuck in Loop

**Problem:** Admin terus di-redirect ke school setup

**Cause:** Data sekolah tidak tersimpan dengan benar

**Solution:**
1. Check database table `schools`
2. Pastikan ada record dengan `name` tidak kosong
3. Jika tidak ada, insert manual:
```sql
INSERT INTO schools (name, npsn, address, created_at, updated_at) 
VALUES ('Nama Sekolah', '12345678', 'Alamat Sekolah', NOW(), NOW());
```

### Academic Year Not Activating

**Problem:** Tahun ajaran dibuat tapi tidak aktif

**Cause:** Checkbox "Aktif" tidak dicentang saat create

**Solution:**
1. Go to `/admin/academic-year`
2. Click "Aktifkan" button pada tahun ajaran yang diinginkan
3. Refresh dashboard

### Non-Admin Can't Access After Setup

**Problem:** User lain masih lihat error setelah admin selesai setup

**Cause:** Cache atau session issue

**Solution:**
1. User logout dan login lagi
2. Clear browser cache
3. Check database: pastikan ada tahun ajaran dengan `is_active = 1`

## Recommendations

### For Administrators

1. **Complete setup immediately** after installation
2. **Fill all required fields** for school data
3. **Activate academic year** right after creation
4. **Test with different user roles** after setup

### For Developers

1. **Add validation** to ensure required fields are filled
2. **Add progress indicator** to show setup completion
3. **Add skip option** for optional steps
4. **Add setup completion check** to prevent re-setup

## Future Enhancements

### Possible Improvements

1. **Setup Progress Bar**
   - Show "Step 1 of 3" indicator
   - Visual progress tracking

2. **Setup Wizard Page**
   - Dedicated setup wizard UI
   - All steps in one page
   - Save and continue later option

3. **Data Import**
   - Import school data from file
   - Import academic year from template
   - Bulk import students/teachers

4. **Setup Validation**
   - Validate NPSN format
   - Validate date ranges
   - Check for duplicate data

5. **Setup Completion Email**
   - Send email to admin when setup complete
   - Include summary of configured data
   - Next steps recommendations

---

**Last Updated:** 2026-04-06  
**Status:** Implemented  
**Version:** 1.0.0
