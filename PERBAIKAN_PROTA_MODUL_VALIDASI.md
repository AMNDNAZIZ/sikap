# Perbaikan Validasi Prota/Prosem dan Modul Ajar

## Ringkasan
Menambahkan validasi mapping mata pelajaran ke mapel_master pada halaman Program Tahunan/Semester dan Modul Ajar, konsisten dengan halaman TP/ATP.

## Perubahan yang Dilakukan

### 1. Controller: AdministrasiGuru.php

#### Method protaProsem()
Menambahkan validasi:
- Pengecekan apakah subject sudah di-mapping ke mapel_master
- Validasi bahwa mapel_master sesuai dengan level sekolah saat ini
- Menampilkan notifikasi yang sesuai jika ada masalah mapping

```php
// Tambahan variabel untuk notifikasi
$data['subject_not_mapped'] = false;
$data['subject_name'] = '';
$data['mapping_mismatch'] = false;

// Get current school level
$school = $this->db->table('school_profile')->get()->getRowArray();
$schoolLevel = $school['level'] ?? 1;

// Validasi mapping
if (empty($subject['mapel_master_id'])) {
    $data['subject_not_mapped'] = true;
    // Set data kosong
} else {
    $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
    
    if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
        $data['mapping_mismatch'] = true;
        // Set data kosong dan info jenjang
    } else {
        // Proses normal
    }
}
```

#### Method modulAjar()
Menambahkan validasi yang sama seperti protaProsem():
- Pengecekan mapping ke mapel_master
- Validasi level sekolah
- Notifikasi error yang informatif

### 2. View: prota_prosem.php

Menambahkan notifikasi sebelum konten utama:

```php
<?php if ($selected_subject): ?>
    <?php if ($subject_not_mapped): ?>
        <div class="alert alert-warning">
            Mata pelajaran belum di-mapping ke Mapel Master
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger">
            Mapping tidak sesuai dengan level sekolah
        </div>
    <?php else: ?>
        <!-- Konten normal -->
    <?php endif; ?>
<?php else: ?>
    <!-- Pesan pilih mata pelajaran -->
<?php endif; ?>
```

### 3. View: modul_ajar.php

Menambahkan struktur notifikasi yang sama seperti prota_prosem.php untuk konsistensi UX.

## Manfaat

1. **Konsistensi**: Semua halaman administrasi guru (TP/ATP, Prota/Prosem, Modul Ajar) memiliki validasi yang sama
2. **User Experience**: Pengguna mendapat notifikasi yang jelas tentang masalah mapping
3. **Data Integrity**: Mencegah penggunaan data yang tidak valid atau tidak sesuai level sekolah
4. **Guidance**: Memberikan link langsung ke halaman Mata Pelajaran untuk memperbaiki mapping

## Testing

Skenario yang perlu ditest:
1. ✅ Subject belum di-mapping → Tampil notifikasi warning
2. ✅ Subject di-mapping ke jenjang berbeda → Tampil notifikasi error
3. ✅ Subject di-mapping dengan benar → Tampil konten normal
4. ✅ Belum pilih subject → Tampil pesan pilih mata pelajaran

## File yang Diubah

1. `app/Controllers/Admin/AdministrasiGuru.php`
   - Method `protaProsem()`
   - Method `modulAjar()`

2. `app/Views/admin/administrasi_guru/prota_prosem.php`
   - Tambah notifikasi validasi

3. `app/Views/admin/administrasi_guru/modul_ajar.php`
   - Tambah notifikasi validasi

## Catatan

- Validasi ini konsisten dengan implementasi di halaman TP/ATP
- Notifikasi menggunakan Bootstrap alert dengan icon yang sesuai
- Link ke halaman Mata Pelajaran disediakan untuk memudahkan perbaikan
