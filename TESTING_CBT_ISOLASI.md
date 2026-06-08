# Testing Guide - CBT Data Isolation

## Prerequisites

1. Buat minimal 2 akun guru dengan data berbeda:
   - **Guru A**: Mengampu Matematika, Kelas 10A
   - **Guru B**: Mengampu Bahasa Indonesia, Kelas 10B
2. Buat 1 akun admin
3. Pastikan teaching_assignments sudah terisi untuk kedua guru

## Test Scenarios

### 1. Bank Soal Isolation

#### Test 1.1: Guru hanya melihat bank soal sendiri
1. Login sebagai **Guru A**
2. Buka `/admin/cbt/banksoal`
3. ✅ **Expected**: Hanya melihat bank soal yang dibuat oleh Guru A
4. ❌ **Should NOT see**: Bank soal dari Guru B

#### Test 1.2: Guru tidak bisa edit bank soal guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/banksoal/edit/{id_bank_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 1.3: Guru tidak bisa hapus bank soal guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/banksoal/delete/{id_bank_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 1.4: Admin melihat semua bank soal
1. Login sebagai **Admin**
2. Buka `/admin/cbt/banksoal`
3. ✅ **Expected**: Melihat bank soal dari Guru A dan Guru B

#### Test 1.5: Restore bank soal dari backup guru lain
1. Login sebagai **Guru A**
2. Upload backup bank soal dari **Guru B**
3. ✅ **Expected**: Berhasil restore
4. ✅ **Expected**: Bank soal ter-restore dengan teacher_id = Guru A
5. ✅ **Expected**: Kode bank soal ditambah suffix "_RESTORED_n" jika collision
6. ✅ **Expected**: Success message menampilkan jumlah soal yang ter-restore

#### Test 1.6: Admin restore mempertahankan teacher_id asli
1. Login sebagai **Admin**
2. Upload backup bank soal dari **Guru A**
3. ✅ **Expected**: Berhasil restore
4. ✅ **Expected**: Bank soal ter-restore dengan teacher_id = Guru A (tetap)

#### Test 1.7: Restore dengan media files
1. Login sebagai **Guru A**
2. Upload backup yang berisi gambar dan audio
3. ✅ **Expected**: File media ter-copy ke writable/uploads/
4. ✅ **Expected**: URL di soal sudah ter-update ke base_url baru

### 2. Status Tes Isolation

#### Test 2.1: Guru hanya melihat jadwal ujian sendiri
1. Login sebagai **Guru A**
2. Buka `/admin/cbt/test-status`
3. ✅ **Expected**: Hanya melihat jadwal ujian yang dibuat oleh Guru A
4. ❌ **Should NOT see**: Jadwal ujian dari Guru B

#### Test 2.2: Guru hanya bisa jadwalkan dari bank soal sendiri
1. Login sebagai **Guru A**
2. Buka `/admin/cbt/test-status/create`
3. ✅ **Expected**: Dropdown bank soal hanya menampilkan bank soal Guru A
4. ❌ **Should NOT see**: Bank soal dari Guru B

#### Test 2.3: Guru hanya bisa pilih kelas yang diampu
1. Login sebagai **Guru A** (mengampu Kelas 10A)
2. Buka `/admin/cbt/test-status/create`
3. ✅ **Expected**: Dropdown kelas hanya menampilkan Kelas 10A
4. ❌ **Should NOT see**: Kelas 10B

#### Test 2.4: Guru tidak bisa edit jadwal ujian guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/test-status/edit/{id_jadwal_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 2.5: Guru tidak bisa hapus jadwal ujian guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/test-status/delete/{id_jadwal_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 2.6: Guru tidak bisa pause/unpause jadwal ujian guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/test-status/toggle-pause/{id_jadwal_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 2.7: Admin melihat semua jadwal ujian
1. Login sebagai **Admin**
2. Buka `/admin/cbt/test-status`
3. ✅ **Expected**: Melihat jadwal ujian dari Guru A dan Guru B

### 3. Aktivitas Isolation

#### Test 3.1: Guru hanya melihat aktivitas ujian sendiri
1. Login sebagai **Guru A**
2. Buka `/admin/cbt/aktivitas`
3. ✅ **Expected**: Hanya melihat aktivitas ujian yang dijadwalkan oleh Guru A
4. ❌ **Should NOT see**: Aktivitas ujian dari Guru B

#### Test 3.2: Guru tidak bisa lihat detail aktivitas ujian guru lain
1. Login sebagai **Guru A**
2. Coba akses langsung `/admin/cbt/aktivitas/detail/{id_test_guru_b}`
3. ✅ **Expected**: Redirect dengan error "Anda tidak memiliki akses"

#### Test 3.3: Admin melihat semua aktivitas
1. Login sebagai **Admin**
2. Buka `/admin/cbt/aktivitas`
3. ✅ **Expected**: Melihat aktivitas ujian dari Guru A dan Guru B

### 4. Konversi Nilai Isolation

#### Test 4.1: Guru hanya melihat bank soal dari mata pelajaran yang diampu
1. Login sebagai **Guru A** (mengampu Matematika)
2. Buka `/admin/cbt/convertnilai`
3. ✅ **Expected**: Dropdown bank soal hanya menampilkan bank soal Matematika
4. ❌ **Should NOT see**: Bank soal Bahasa Indonesia

#### Test 4.2: Guru hanya melihat kelas yang diampu
1. Login sebagai **Guru A** (mengampu Kelas 10A)
2. Buka `/admin/cbt/convertnilai`
3. ✅ **Expected**: Dropdown kelas hanya menampilkan Kelas 10A
4. ❌ **Should NOT see**: Kelas 10B

#### Test 4.3: Guru tidak bisa konversi nilai mata pelajaran yang tidak diampu
1. Login sebagai **Guru A** (mengampu Matematika)
2. Coba submit form dengan bank_id dari Bahasa Indonesia
3. ✅ **Expected**: Error "Anda tidak memiliki akses untuk mengkonversi nilai mata pelajaran ini"

#### Test 4.4: Admin bisa konversi semua mata pelajaran
1. Login sebagai **Admin**
2. Buka `/admin/cbt/convertnilai`
3. ✅ **Expected**: Melihat semua bank soal dan semua kelas

### 5. Nama Ujian (Shared Resource)

#### Test 5.1: Semua guru bisa gunakan semua nama ujian
1. Login sebagai **Guru A**
2. Buka `/admin/cbt/test-status/create`
3. ✅ **Expected**: Dropdown nama ujian menampilkan semua nama ujian (UTS, UAS, dll)
4. Login sebagai **Guru B**
5. ✅ **Expected**: Dropdown nama ujian menampilkan nama ujian yang sama

## Common Issues & Solutions

### Issue 1: Guru melihat bank soal guru lain
**Cause**: Helper function tidak dipanggil atau filter tidak diterapkan
**Solution**: Pastikan `helper('cbt')` dipanggil dan `get_cbt_user_context()` digunakan

### Issue 2: Error "Call to undefined function"
**Cause**: Helper belum di-autoload
**Solution**: Pastikan `app/Config/Autoload.php` sudah include `'cbt'` di array helpers

### Issue 3: Guru bisa edit data guru lain
**Cause**: Validasi ownership tidak diterapkan
**Solution**: Pastikan setiap method edit/update/delete memanggil `can_access_cbt_*()` function

### Issue 4: Admin tidak bisa akses data
**Cause**: Filter terlalu ketat
**Solution**: Pastikan ada check `if ($context['is_admin'])` sebelum filter

## Performance Testing

1. **Load Test**: Buat 100+ bank soal dengan 10 guru berbeda
2. **Query Test**: Pastikan query menggunakan index (teacher_id, created_by)
3. **Cache Test**: Pastikan tidak ada cache yang menyebabkan data bocor antar guru

## Security Testing

1. **Direct URL Access**: Coba akses langsung URL dengan ID data guru lain
2. **Parameter Tampering**: Coba ubah parameter POST/GET untuk bypass validation
3. **Session Hijacking**: Pastikan session tidak bisa digunakan untuk akses data guru lain

## Regression Testing

Pastikan fitur yang sudah ada tetap berfungsi:
- [ ] Admin masih bisa akses semua data
- [ ] Siswa masih bisa mengerjakan ujian
- [ ] Nilai masih bisa dihitung dengan benar
- [ ] Export/Import masih berfungsi

---

**Testing Date**: _____________
**Tested By**: _____________
**Status**: [ ] PASS [ ] FAIL
**Notes**: _____________
