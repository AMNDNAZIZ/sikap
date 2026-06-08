# Fitur Simpan Jadwal Sekaligus (Bulk Schedule Save)

## Deskripsi
Fitur ini memungkinkan admin untuk membuat dan menyimpan jadwal pelajaran untuk semua hari (Senin-Sabtu) sekaligus dalam sekali klik, tanpa perlu menyimpan satu per satu untuk setiap hari.

## Masalah Sebelumnya
- User harus menyimpan jadwal per hari (satu hari sekali simpan)
- Proses pembuatan jadwal menjadi repetitif dan memakan waktu
- Harus klik tab hari → isi jadwal → simpan → ulangi untuk hari berikutnya

## Solusi
Mengubah UI dan backend agar:
1. Semua tab hari dapat diisi sekaligus
2. Satu tombol "Simpan Semua Jadwal" untuk menyimpan semua hari
3. Backend memproses data multi-hari dalam satu transaksi

## Perubahan yang Dilakukan

### 1. View (`app/Views/admin/schedule/manage.php`)

**A. Mengubah Struktur Form**
- **Sebelum**: Setiap tab hari memiliki form terpisah
- **Sesudah**: Satu form wrapper untuk semua tab hari

```php
<!-- Form wrapper untuk semua hari -->
<form action="<?= base_url('admin/schedules/store-bulk') ?>" method="post" id="bulkScheduleForm">
    <?= csrf_field() ?>
    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
    <input type="hidden" name="academic_year_id" value="<?= $activeYear['id'] ?>">

    <div class="tab-content" id="scheduleTabsContent">
        <!-- Semua tab hari di dalam satu form -->
    </div>

    <!-- Tombol Simpan Semua di bawah -->
    <div class="mt-4 text-center">
        <button type="submit" class="btn btn-success btn-lg">
            Simpan Semua Jadwal
        </button>
    </div>
</form>
```

**B. Mengubah Naming Convention Input**
- **Sebelum**: `name="start_time[]"`, `name="end_time[]"`, `name="assignment_id[]"`
- **Sesudah**: `name="day_1_start_time[]"`, `name="day_1_end_time[]"`, `name="day_1_assignment_id[]"`

Setiap hari memiliki prefix `day_{num}_` untuk membedakan data antar hari.

**C. Update Template Row**
Template untuk menambah baris baru menggunakan placeholder `{DAY}` yang akan diganti dengan nomor hari saat runtime:

```html
<template id="row-template">
    <tr>
        <td>
            <input type="time" name="day_{DAY}_start_time[]" class="form-control" required>
        </td>
        <td>
            <input type="time" name="day_{DAY}_end_time[]" class="form-control" required>
        </td>
        <td>
            <select name="day_{DAY}_assignment_id[]" class="form-select" required>
                <!-- Options -->
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row">
                <i class="bi bi-x-lg"></i>
            </button>
        </td>
    </tr>
</template>
```

**D. Update JavaScript**
Menambahkan logic untuk replace placeholder `{DAY}` saat menambah row baru:

```javascript
// Clone template and replace {DAY} placeholder with actual day number
const newRow = template.content.cloneNode(true);
const tempDiv = document.createElement('div');
tempDiv.appendChild(newRow);
tempDiv.innerHTML = tempDiv.innerHTML.replace(/{DAY}/g, day);

container.appendChild(tempDiv.firstChild);
```

**E. Form Validation**
Menambahkan validasi sebelum submit untuk memastikan minimal ada satu jadwal:

```javascript
document.getElementById('bulkScheduleForm').addEventListener('submit', function(e) {
    const hasSchedule = document.querySelectorAll('.row-container tr:not(.empty-row)').length > 0;
    
    if (!hasSchedule) {
        e.preventDefault();
        alert('Silakan tambahkan minimal satu jadwal sebelum menyimpan.');
        return false;
    }
    
    return true;
});
```

### 2. Controller (`app/Controllers/Admin/ScheduleController.php`)

**Menambahkan Method `storeBulk()`**

```php
/**
 * Store schedules for multiple days at once
 */
public function storeBulk()
{
    $classId = $this->request->getPost('class_id');
    $this->validateAccess($classId);
    $academicYearId = $this->request->getPost('academic_year_id');

    if (!$classId || !$academicYearId) {
        return redirect()->back()->with('error', 'Data tidak lengkap.');
    }

    $this->db->transStart();

    // Clear all existing schedules for this class and academic year
    $this->scheduleModel->where([
        'class_id' => $classId,
        'academic_year_id' => $academicYearId
    ])->delete();

    $totalInserted = 0;
    $days = [1, 2, 3, 4, 5, 6]; // Senin - Sabtu

    // Process each day
    foreach ($days as $dayNum) {
        $startTimes = $this->request->getPost("day_{$dayNum}_start_time");
        $endTimes = $this->request->getPost("day_{$dayNum}_end_time");
        $assignments = $this->request->getPost("day_{$dayNum}_assignment_id");

        if ($startTimes && is_array($startTimes)) {
            foreach ($startTimes as $index => $startTime) {
                if (empty($startTime) || empty($endTimes[$index]) || empty($assignments[$index])) continue;

                list($subjectId, $teacherId) = explode('|', $assignments[$index]);

                $this->scheduleModel->insert([
                    'day_of_week'      => $dayNum,
                    'class_id'         => $classId,
                    'subject_id'       => $subjectId,
                    'teacher_id'       => $teacherId,
                    'start_time'       => $startTime,
                    'end_time'         => $endTimes[$index],
                    'academic_year_id' => $academicYearId
                ]);

                $totalInserted++;
            }
        }
    }

    $this->db->transComplete();

    if ($this->db->transStatus() === false) {
        return redirect()->back()->with('error', 'Gagal menyimpan jadwal.');
    }

    if ($totalInserted === 0) {
        return redirect()->back()->with('warning', 'Tidak ada jadwal yang disimpan. Silakan tambahkan minimal satu jadwal.');
    }

    return redirect()->to(base_url('admin/schedules/manage/' . $classId))
        ->with('success', "Berhasil menyimpan {$totalInserted} jadwal untuk semua hari.");
}
```

**Fitur Method:**
- Menggunakan database transaction untuk memastikan atomicity
- Menghapus semua jadwal lama untuk kelas tersebut sebelum insert yang baru
- Memproses data dari 6 hari (Senin-Sabtu) sekaligus
- Menghitung total jadwal yang berhasil disimpan
- Memberikan feedback yang informatif ke user

### 3. Routes (`app/Config/Routes.php`)

Menambahkan route baru untuk bulk save:

```php
$routes->post('schedules/store-bulk', 'ScheduleController::storeBulk', $auth1);
```

## Cara Penggunaan

### Untuk Admin:

1. **Akses Halaman Kelola Jadwal**
   - Buka menu "Jadwal Pelajaran"
   - Pilih kelas yang ingin dikelola
   - Klik "Kelola Jadwal"

2. **Isi Jadwal untuk Semua Hari**
   - Klik tab hari (Senin, Selasa, dst.)
   - Klik tombol "+ Tambah Slot Jam"
   - Isi jam mulai, jam selesai, dan pilih mata pelajaran & guru
   - Ulangi untuk hari-hari lainnya
   - Tidak perlu menyimpan per hari

3. **Simpan Semua Jadwal Sekaligus**
   - Setelah semua hari terisi
   - Scroll ke bawah
   - Klik tombol "Simpan Semua Jadwal"
   - Sistem akan menyimpan jadwal untuk semua hari dalam sekali proses

4. **Feedback**
   - Jika berhasil: SweetAlert dengan icon success "Berhasil menyimpan X jadwal untuk semua hari"
   - Jika tidak ada jadwal: SweetAlert dengan icon warning "Tidak ada jadwal yang disimpan. Silakan tambahkan minimal satu jadwal"
   - Jika gagal: SweetAlert dengan icon error "Gagal menyimpan jadwal"
   - Saat proses simpan: Loading indicator "Menyimpan... Mohon tunggu"

## Notifikasi

### Jenis Notifikasi dengan SweetAlert2

**1. Success (Berhasil)**
```javascript
Swal.fire({
    icon: 'success',
    title: 'Berhasil!',
    text: 'Berhasil menyimpan X jadwal untuk semua hari',
    showConfirmButton: true,
    confirmButtonColor: '#198754',
    timer: 5000,
    timerProgressBar: true
});
```
- Icon: ✓ (hijau)
- Auto close setelah 5 detik
- Progress bar menunjukkan countdown
- Bisa ditutup manual dengan klik OK

**2. Error (Gagal)**
```javascript
Swal.fire({
    icon: 'error',
    title: 'Gagal!',
    text: 'Gagal menyimpan jadwal',
    showConfirmButton: true,
    confirmButtonColor: '#dc3545'
});
```
- Icon: ✗ (merah)
- Harus ditutup manual
- Tidak auto close

**3. Warning (Peringatan)**
```javascript
Swal.fire({
    icon: 'warning',
    title: 'Perhatian!',
    text: 'Tidak ada jadwal yang disimpan. Silakan tambahkan minimal satu jadwal',
    showConfirmButton: true,
    confirmButtonColor: '#ffc107'
});
```
- Icon: ⚠ (kuning)
- Harus ditutup manual
- Untuk validasi atau peringatan

**4. Loading (Proses)**
```javascript
Swal.fire({
    title: 'Menyimpan...',
    text: 'Mohon tunggu, sedang menyimpan jadwal',
    allowOutsideClick: false,
    allowEscapeKey: false,
    didOpen: () => {
        Swal.showLoading();
    }
});
```
- Spinner loading
- Tidak bisa ditutup (prevent user action)
- Muncul saat form submit
- Auto close saat redirect

## Keuntungan

### 1. Efisiensi Waktu
- **Sebelum**: Harus simpan 6 kali (satu per hari)
- **Sesudah**: Cukup simpan 1 kali untuk semua hari
- Menghemat waktu hingga 80%

### 3. User Experience Lebih Baik
- Tidak perlu repetitif klik simpan
- Bisa melihat dan membandingkan jadwal antar hari sebelum menyimpan
- Lebih intuitif dan natural
- **Notifikasi visual yang jelas** dengan SweetAlert2
- **Loading indicator** saat proses simpan
- **Auto-close** untuk notifikasi sukses (5 detik)

### 4. Data Consistency
- Menggunakan database transaction
- Semua data tersimpan atau tidak sama sekali (atomic)
- Menghindari data jadwal yang tidak konsisten

### 4. Fleksibilitas
- Bisa mengisi hanya hari tertentu saja
- Bisa skip hari yang tidak ada jadwal
- Bisa edit semua hari sekaligus

### 5. Feedback Visual yang Baik
- SweetAlert2 untuk notifikasi yang menarik dan user-friendly
- Loading indicator saat proses simpan
- Validasi real-time dengan notifikasi
- Auto-close untuk success message (tidak mengganggu workflow)

## Backward Compatibility

Method `store()` yang lama tetap dipertahankan untuk:
- Kompatibilitas dengan kode lama (jika ada)
- Fallback jika ada masalah dengan bulk save
- Kemungkinan fitur save per hari di masa depan

## Testing

### Test Case 1: Simpan Jadwal Lengkap (Semua Hari)
1. Isi jadwal untuk Senin-Sabtu
2. Klik "Simpan Semua Jadwal"
3. **Expected**: Semua jadwal tersimpan, muncul pesan sukses dengan jumlah jadwal

### Test Case 2: Simpan Jadwal Sebagian (Beberapa Hari)
1. Isi jadwal hanya untuk Senin, Rabu, Jumat
2. Klik "Simpan Semua Jadwal"
3. **Expected**: Hanya jadwal yang diisi tersimpan, hari lain kosong

### Test Case 3: Simpan Tanpa Jadwal
1. Jangan isi jadwal sama sekali
2. Klik "Simpan Semua Jadwal"
3. **Expected**: Muncul alert "Silakan tambahkan minimal satu jadwal sebelum menyimpan"

### Test Case 4: Update Jadwal yang Sudah Ada
1. Buka kelas yang sudah ada jadwalnya
2. Edit beberapa jadwal
3. Klik "Simpan Semua Jadwal"
4. **Expected**: Jadwal lama terhapus, jadwal baru tersimpan

### Test Case 5: Transaction Rollback
1. Simulasi error database saat insert
2. **Expected**: Tidak ada data yang tersimpan (rollback), muncul pesan error

## Catatan Teknis

### Database Transaction
Menggunakan `transStart()` dan `transComplete()` untuk memastikan:
- Semua insert berhasil atau tidak sama sekali
- Data consistency terjaga
- Rollback otomatis jika ada error

### Naming Convention
Format: `day_{dayNumber}_{fieldName}[]`
- `day_1_start_time[]` = Jam mulai untuk Senin
- `day_2_end_time[]` = Jam selesai untuk Selasa
- `day_3_assignment_id[]` = Mata pelajaran untuk Rabu

### Permission
Hanya Admin (role_id = 1) yang dapat menyimpan jadwal (bulk atau single).

## Troubleshooting

### Tombol "Tambah Slot Jam" Tidak Berfungsi

**Gejala**: Klik tombol "+ Tambah Slot Jam" tidak menambahkan baris baru

**Penyebab Umum**:
1. JavaScript belum selesai load
2. Template element tidak ditemukan
3. Conflict dengan library lain (jQuery, Select2, dll)
4. Browser cache

**Solusi**:
1. **Buka Browser Console** (F12) dan lihat error messages
2. **Check Console Logs**:
   - Harus muncul: "Schedule manage script loaded"
   - Harus muncul: "Found add button for day: 1", "Found add button for day: 2", dst.
   - Saat klik tombol, harus muncul: "Add button clicked for day: X"
   
3. **Jika tidak ada log sama sekali**:
   - Clear browser cache (Ctrl+Shift+Delete)
   - Hard refresh (Ctrl+F5)
   - Pastikan JavaScript tidak diblokir oleh browser

4. **Jika ada error "Template not found"**:
   - Pastikan `<template id="row-template">` ada di HTML
   - Pastikan template berada di luar form
   - Check typo di ID template

5. **Jika ada error "Container not found"**:
   - Pastikan setiap tabel memiliki `<tbody class="row-container">`
   - Check typo di class name

**Debug Mode**:
Script sudah dilengkapi dengan console.log untuk debugging. Buka browser console untuk melihat:
- Apakah script loaded
- Apakah button ditemukan
- Apakah template dan container ditemukan
- Apakah row berhasil ditambahkan

### Jadwal Tidak Tersimpan

**Gejala**: Klik "Simpan Semua Jadwal" tapi data tidak tersimpan

**Penyebab Umum**:
1. Validation error (tidak ada jadwal yang diisi)
2. CSRF token expired
3. Database error
4. Permission denied

**Solusi**:
1. Pastikan minimal ada satu jadwal yang diisi
2. Jangan biarkan halaman terbuka terlalu lama (CSRF token expire)
3. Check flash message untuk error detail
4. Pastikan login sebagai Admin (role_id = 1)

## File yang Dimodifikasi

1. `app/Views/admin/schedule/manage.php` - UI untuk bulk input
2. `app/Controllers/Admin/ScheduleController.php` - Method storeBulk()
3. `app/Config/Routes.php` - Route untuk store-bulk

## Referensi
- CodeIgniter 4 Database Transactions: https://codeigniter.com/user_guide/database/transactions.html
- Bootstrap 5 Tabs: https://getbootstrap.com/docs/5.0/components/navs-tabs/
