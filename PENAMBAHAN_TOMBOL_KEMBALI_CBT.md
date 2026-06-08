# Penambahan Tombol Kembali ke Dashboard di Layout CBT

## 📌 Overview

Menambahkan tombol "Kembali ke Dashboard" di navbar layout CBT agar siswa dapat dengan mudah kembali ke dashboard tanpa harus menggunakan tombol back browser.

## 🎯 Masalah

Ketika siswa mengakses menu `/siswa/cbt`, halaman menggunakan layout mode CBT yang minimalis. Di layout ini tidak ada tombol untuk kembali ke dashboard, sehingga siswa harus:
- Menggunakan tombol back browser
- Mengetik URL dashboard secara manual
- Atau menutup tab dan membuka dashboard lagi

Ini tidak user-friendly dan bisa membingungkan siswa.

## ✅ Solusi

Menambahkan tombol "Dashboard" di navbar layout CBT yang mengarah ke halaman dashboard.

### Perubahan di Layout CBT

**File:** `app/Views/layouts/cbt.php`

**Before:**
```html
<nav class="navbar navbar-light bg-light sticky-top shadow-sm" style="height: 56px;">
  <div class="container-fluid">
    <a class="navbar-brand fw-bold">MODE CBT</a>
  </div>
</nav>
```

**After:**
```html
<nav class="navbar navbar-light bg-light sticky-top shadow-sm" style="height: 56px;">
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-3">
      <a href="<?= base_url('dashboard') ?>" class="btn btn-sm btn-outline-primary" title="Kembali ke Dashboard">
        <i class="bi bi-arrow-left"></i> Dashboard
      </a>
      <span class="navbar-brand fw-bold mb-0">MODE CBT</span>
    </div>
    
    <div class="d-flex align-items-center gap-2">
      <span class="badge bg-primary">
        <i class="bi bi-person-circle"></i> <?= esc(session()->get('user')['fullname'] ?? 'Siswa') ?>
      </span>
    </div>
  </div>
</nav>
```

## 🎨 Fitur yang Ditambahkan

### 1. Tombol Kembali ke Dashboard
- **Icon:** Arrow left (`bi-arrow-left`)
- **Text:** "Dashboard"
- **Style:** `btn btn-sm btn-outline-primary`
- **Link:** Mengarah ke `base_url('dashboard')`
- **Tooltip:** "Kembali ke Dashboard"

### 2. Badge User Info
- **Icon:** Person circle (`bi-person-circle`)
- **Text:** Nama lengkap user dari session
- **Style:** `badge bg-primary`
- **Fallback:** "Siswa" jika fullname tidak ada

### 3. Layout Improvement
- Navbar sekarang menggunakan flexbox dengan `justify-content-between`
- Tombol dan brand di kiri
- User info di kanan
- Responsive dan clean

## 📊 UI Layout

```
┌─────────────────────────────────────────────────────────────┐
│  [← Dashboard]  MODE CBT              [👤 Nama Siswa]       │
└─────────────────────────────────────────────────────────────┘
```

### Desktop View:
```
┌──────────────────────────────────────────────────────────────────┐
│  [← Dashboard]  MODE CBT                    [👤 ADRIAN AKBAR]    │
└──────────────────────────────────────────────────────────────────┘
```

### Mobile View:
```
┌────────────────────────────────┐
│  [←]  MODE CBT      [👤 ADRIAN] │
└────────────────────────────────┘
```

## 🎯 Affected Pages

Layout CBT digunakan di halaman-halaman berikut:

1. ✅ `/siswa/cbt` - Daftar ujian
2. ✅ `/siswa/cbt/peraturan/:id` - Halaman peraturan ujian
3. ✅ `/siswa/cbt/mulai/:id` - Halaman mengerjakan ujian
4. ✅ `/siswa/cbt/selesai/:id` - Halaman selesai ujian
5. ✅ `/siswa/cbt/hasil/:id` - Halaman hasil ujian

Semua halaman di atas sekarang memiliki tombol kembali ke dashboard.

## 🧪 Testing

### Test Case 1: Klik Tombol Dashboard dari Daftar Ujian

**Steps:**
1. Login sebagai siswa
2. Akses `/siswa/cbt`
3. Klik tombol "Dashboard" di navbar

**Expected Result:**
- ✅ Redirect ke halaman dashboard
- ✅ Tidak ada error
- ✅ User tetap login

### Test Case 2: Klik Tombol Dashboard dari Halaman Ujian

**Steps:**
1. Login sebagai siswa
2. Akses `/siswa/cbt`
3. Klik "Mulai Ujian"
4. Klik tombol "Dashboard" di navbar

**Expected Result:**
- ✅ Redirect ke halaman dashboard
- ✅ Session ujian tetap tersimpan (bisa dilanjutkan)
- ✅ Tidak ada data yang hilang

### Test Case 3: User Info Badge

**Steps:**
1. Login sebagai siswa dengan nama lengkap
2. Akses `/siswa/cbt`
3. Lihat badge di kanan navbar

**Expected Result:**
- ✅ Menampilkan nama lengkap siswa
- ✅ Icon person-circle muncul
- ✅ Badge berwarna biru (primary)

### Test Case 4: Responsive Mobile

**Steps:**
1. Login sebagai siswa
2. Akses `/siswa/cbt`
3. Resize browser ke ukuran mobile (< 768px)

**Expected Result:**
- ✅ Tombol dan badge tetap terlihat
- ✅ Layout tidak berantakan
- ✅ Tombol masih bisa diklik

## 🎨 Styling Details

### Button Styling
```css
.btn-sm.btn-outline-primary {
  padding: 0.25rem 0.75rem;
  font-size: 0.875rem;
  border-radius: 0.25rem;
  border: 1px solid #0d6efd;
  color: #0d6efd;
}

.btn-sm.btn-outline-primary:hover {
  background-color: #0d6efd;
  color: white;
}
```

### Badge Styling
```css
.badge.bg-primary {
  background-color: #0d6efd;
  color: white;
  padding: 0.5rem 1rem;
  border-radius: 0.375rem;
}
```

### Navbar Height
- Fixed height: `56px`
- Sticky top: Tetap di atas saat scroll
- Shadow: `shadow-sm` untuk depth

## 💡 User Experience Improvements

### Before:
- ❌ Tidak ada cara mudah kembali ke dashboard
- ❌ User harus pakai tombol back browser
- ❌ Tidak ada info user yang login
- ❌ Layout terasa "terjebak" di mode CBT

### After:
- ✅ Tombol kembali yang jelas dan mudah diakses
- ✅ User bisa langsung klik untuk kembali
- ✅ Ada info user yang sedang login
- ✅ Layout lebih informatif dan user-friendly

## 🔒 Security Notes

- Tombol menggunakan `base_url()` untuk URL yang aman
- Session user di-escape dengan `esc()` untuk XSS protection
- Tidak ada data sensitive yang ditampilkan
- Link hanya mengarah ke dashboard internal

## 📝 Notes

### Kenapa Tidak Pakai Logout Button?

- Layout CBT fokus pada ujian
- Logout button ada di dashboard
- Menghindari logout tidak sengaja saat ujian
- Siswa bisa logout dari dashboard setelah kembali

### Kenapa Tampilkan Nama User?

- Konfirmasi user yang sedang login
- Membantu jika ada multiple accounts
- User experience yang lebih personal
- Konsisten dengan layout lain

### Alternatif Navigation

Selain tombol Dashboard, siswa juga bisa:
1. Klik logo/brand (jika ditambahkan link)
2. Gunakan browser back button
3. Ketik URL dashboard manual

Tapi tombol Dashboard adalah cara paling intuitif dan user-friendly.

## ✅ Verification Checklist

- [x] Tombol Dashboard added to navbar
- [x] Link mengarah ke `base_url('dashboard')`
- [x] Icon arrow-left ditampilkan
- [x] User info badge ditampilkan
- [x] Responsive di mobile
- [x] No syntax errors
- [x] Works on all CBT pages
- [x] Session preserved after navigation

## 🔄 Future Enhancements

Fitur yang bisa ditambahkan di masa depan:

1. **Breadcrumb Navigation**
   - Dashboard > CBT > Ujian Matematika
   - Memudahkan navigasi multi-level

2. **Quick Menu Dropdown**
   - Dropdown dengan link ke menu lain
   - Nilai, Jadwal, Profil, dll

3. **Notification Badge**
   - Badge notifikasi di navbar
   - Alert untuk pengumuman penting

4. **Timer Display**
   - Tampilkan sisa waktu ujian di navbar
   - Countdown real-time

5. **Dark Mode Toggle**
   - Switch untuk dark/light mode
   - Simpan preferensi user

---

**Added:** 2026-04-04  
**Feature:** Back to Dashboard button in CBT layout  
**Impact:** Improved navigation and user experience
