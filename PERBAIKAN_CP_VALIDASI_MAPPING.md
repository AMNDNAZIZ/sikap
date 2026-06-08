# Perbaikan Halaman CP - Validasi Mapping Kurikulum

## Masalah
Halaman Capaian Pembelajaran (`/admin/administrasi-guru/cp`) tidak memberikan informasi yang jelas ketika mata pelajaran lokal belum di-mapping dengan Mapel Master. User hanya melihat pesan "Belum ada data CP" tanpa tahu bahwa penyebabnya adalah mapping yang belum dilakukan.

**Masalah Tambahan**: Ketika level sekolah diubah (misal dari SD ke SMP), mapping lama yang mengarah ke Mapel Master SD masih aktif, sehingga CP dengan fase SD (A-C) masih ditampilkan padahal sekolah sudah SMP. Ini menyebabkan data yang tidak konsisten.

## Konsep Mapping Kurikulum

### Struktur Data
1. **Mapel Master** (`mapel_master`): Master data mata pelajaran berdasarkan kurikulum nasional
   - Terhubung dengan `jenjang_master` (SD/SMP/SMA)
   - Berisi kode dan nama mapel standar

2. **Subjects** (`subjects`): Mata pelajaran lokal sekolah
   - Mata pelajaran yang digunakan di sekolah
   - Memiliki kolom `mapel_master_id` untuk mapping

3. **CP Master** (`cp_master`): Capaian Pembelajaran
   - Terhubung dengan `mapel_master_id`
   - Berisi deskripsi CP per fase

### Alur Kerja
```
Mapel Lokal (subjects) 
    ↓ (mapping via mapel_master_id)
Mapel Master (mapel_master)
    ↓ (has many)
CP Master (cp_master)
```

Jika `subjects.mapel_master_id = NULL`, maka tidak ada CP yang bisa ditampilkan karena tidak ada hubungan dengan Mapel Master.

## Solusi Implementasi

### 1. Controller (`app/Controllers/Admin/AdministrasiGuru.php`)

#### Method `cp()` - Validasi Mapping

```php
public function cp()
{
    $data['title'] = 'Capaian Pembelajaran (CP)';
    
    $filters = $this->getAvailableFilters();
    $data['classes'] = $filters['classes'];
    $data['subjects'] = $filters['subjects'];
    $data['readonly'] = $filters['readonly'];
    $data['auto_class'] = $filters['auto_class'];
    
    $classId = $filters['selected_class'];
    $subjectId = $this->request->getGet('subject_id');
    $data['selected_class'] = $classId;
    $data['selected_subject'] = $subjectId;
    $data['subject_not_mapped'] = false;
    $data['subject_name'] = '';
    $data['mapping_mismatch'] = false;
    
    // Get current school level
    $school = $this->db->table('school_profile')->get()->getRowArray();
    $schoolLevel = $school['level'] ?? 1;
    
    if ($subjectId) {
        $subject = $this->subjectModel->find($subjectId);
        $data['subject_name'] = $subject['name'] ?? '';
        
        // Check if subject is mapped to mapel_master
        if (empty($subject['mapel_master_id'])) {
            $data['subject_not_mapped'] = true;
            $data['cp_list'] = [];
        } else {
            // Validate that mapel_master belongs to current school level
            $mapelMaster = $this->mapelMasterModel->find($subject['mapel_master_id']);
            
            if (!$mapelMaster || (int)$mapelMaster['jenjang_id'] !== (int)$schoolLevel) {
                // Mapping exists but doesn't match school level
                $data['mapping_mismatch'] = true;
                $data['old_jenjang'] = $mapelMaster ? match((int)$mapelMaster['jenjang_id']) {
                    1 => 'SD / Sederajat',
                    2 => 'SMP / Sederajat',
                    3 => 'SMA / Sederajat',
                    default => 'Unknown'
                } : 'Unknown';
                $data['current_jenjang'] = match((int)$schoolLevel) {
                    1 => 'SD / Sederajat',
                    2 => 'SMP / Sederajat',
                    3 => 'SMA / Sederajat',
                    default => 'Unknown'
                };
                $data['cp_list'] = [];
            } else {
                $query = $this->cpMasterModel->where('mapel_master_id', $subject['mapel_master_id']);
            
            // ... filter fase logic ...
            
            $data['cp_list'] = $query->findAll();
        }
    } else {
        $data['cp_list'] = [];
    }
    
    return view('admin/administrasi_guru/cp', $data);
}
```

**Perubahan:**
- Menambahkan flag `subject_not_mapped` untuk cek apakah mapel sudah di-mapping
- Menambahkan flag `mapping_mismatch` untuk cek apakah mapping sesuai dengan level sekolah
- Menambahkan `subject_name` untuk ditampilkan di pesan error
- Menambahkan validasi jenjang: cek apakah `mapel_master.jenjang_id` sama dengan `school.level`
- Validasi: jika `mapel_master_id` kosong, set flag `subject_not_mapped`
- Validasi: jika jenjang tidak sesuai, set flag `mapping_mismatch` dan kosongkan cp_list
- Jika sudah di-mapping dan jenjang sesuai, lanjutkan query CP seperti biasa

### 2. View (`app/Views/admin/administrasi_guru/cp.php`)

#### A. Alert untuk Mapel Belum Di-mapping

```php
<?php if ($subject_not_mapped): ?>
    <div class="alert alert-warning border-left-warning shadow">
        <!-- ... existing warning alert ... -->
    </div>
<?php endif; ?>
```

#### B. Alert untuk Mapping Tidak Sesuai Level Sekolah (BARU)

```php
<?php elseif ($mapping_mismatch): ?>
    <div class="alert alert-danger border-left-danger shadow">
        <div class="d-flex align-items-start">
            <i class="bi bi-x-circle-fill me-3" style="font-size: 2rem;"></i>
            <div>
                <h5 class="alert-heading mb-2">Mapping Tidak Sesuai dengan Level Sekolah</h5>
                <p class="mb-2">
                    Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> di-mapping ke Mapel Master dengan jenjang 
                    <strong><?= esc($old_jenjang) ?></strong>, tetapi level sekolah saat ini adalah 
                    <strong><?= esc($current_jenjang) ?></strong>.
                </p>
                <p class="mb-2">
                    Hal ini terjadi karena level sekolah telah diubah, sehingga mapping lama tidak valid lagi.
                    Capaian Pembelajaran (CP) tidak dapat ditampilkan karena ketidaksesuaian jenjang.
                </p>
                <hr>
                <p class="mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Silakan lakukan <strong>Pemetaan Ulang</strong> untuk menghubungkan mata pelajaran dengan 
                    Mapel Master yang sesuai dengan level sekolah saat ini (<strong><?= esc($current_jenjang) ?></strong>).
                </p>
                <div class="mt-3">
                    <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-danger">
                        <i class="bi bi-arrow-repeat me-1"></i> Pemetaan Ulang
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
```

**Fitur:**
- Alert danger (merah) untuk kasus serius (mapping tidak valid)
- Menampilkan jenjang lama vs jenjang sekarang
- Penjelasan bahwa level sekolah telah diubah
- Tombol "Pemetaan Ulang" untuk solusi

#### C. Alert untuk Mapel Sudah Di-mapping tapi Belum Ada CP

```php
<?php elseif (empty($cp_list)): ?>
    <div class="alert alert-info border-left-info shadow">
        <div class="d-flex align-items-start">
            <i class="bi bi-info-circle-fill me-3" style="font-size: 2rem;"></i>
            <div>
                <h5 class="alert-heading mb-2">Belum Ada Data CP</h5>
                <p class="mb-2">
                    Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> sudah di-mapping, 
                    tetapi belum ada Capaian Pembelajaran (CP) yang tersedia di Master CP.
                </p>
                <hr>
                <p class="mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    Silakan tambahkan data CP di <strong>Master CP</strong> untuk Mapel Master yang terkait.
                </p>
                <div class="mt-3">
                    <a href="<?= base_url('admin/administrasi-guru/cp-master') ?>" class="btn btn-info text-white">
                        <i class="bi bi-plus-lg me-1"></i> Ke Halaman Master CP
                    </a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
```

**Fitur:**
- Alert info untuk kasus sudah di-mapping tapi belum ada CP
- Membedakan dengan kasus belum di-mapping
- Tombol langsung ke halaman Master CP untuk menambahkan data

## Skenario Penggunaan

### Skenario 1: Mapel Belum Di-mapping
1. Admin/Guru pilih mata pelajaran "Matematika Kelas 7"
2. Mapel ini belum di-mapping (`mapel_master_id = NULL`)
3. Sistem menampilkan alert warning:
   - "Mata Pelajaran Belum Di-mapping"
   - Penjelasan bahwa CP tidak bisa ditampilkan
   - Tombol "Ke Halaman Mapping"
4. Admin klik tombol, redirect ke halaman mapping
5. Admin mapping "Matematika Kelas 7" dengan "Matematika (MTK)" dari Mapel Master
6. Kembali ke halaman CP, sekarang bisa melihat CP

### Skenario 2: Level Sekolah Diubah (Mapping Mismatch) - BARU
1. Sekolah awalnya SD (level = 1), semua mapel di-mapping ke Mapel Master SD
2. Admin ubah level sekolah menjadi SMP (level = 2)
3. Mapel lokal masih memiliki `mapel_master_id` yang mengarah ke Mapel Master SD
4. Admin/Guru pilih mata pelajaran "Matematika"
5. Sistem deteksi: `mapel_master.jenjang_id = 1` (SD) ≠ `school.level = 2` (SMP)
6. Sistem menampilkan alert danger:
   - "Mapping Tidak Sesuai dengan Level Sekolah"
   - Penjelasan: di-mapping ke SD tapi sekolah sekarang SMP
   - Tombol "Pemetaan Ulang"
7. Admin klik tombol, redirect ke halaman mapping
8. Admin mapping ulang dengan Mapel Master SMP yang sesuai
9. Kembali ke halaman CP, sekarang bisa melihat CP SMP (Fase D)

### Skenario 3: Mapel Sudah Di-mapping, Belum Ada CP
1. Admin/Guru pilih mata pelajaran "IPA Kelas 7"
2. Mapel sudah di-mapping dengan Mapel Master "IPA"
3. Tapi belum ada CP di Master CP untuk Mapel Master "IPA"
4. Sistem menampilkan alert info:
   - "Belum Ada Data CP"
   - Penjelasan bahwa mapping sudah ada tapi CP belum
   - Tombol "Ke Halaman Master CP"
5. Admin klik tombol, redirect ke halaman Master CP
6. Admin tambahkan CP untuk Mapel Master "IPA"
7. Kembali ke halaman CP, sekarang bisa melihat CP

### Skenario 4: Mapel Sudah Di-mapping dan Ada CP
1. Admin/Guru pilih mata pelajaran "Bahasa Indonesia Kelas 7"
2. Mapel sudah di-mapping dengan Mapel Master "Bahasa Indonesia"
3. Sudah ada CP di Master CP untuk Mapel Master "Bahasa Indonesia"
4. Sistem menampilkan tabel CP dengan data lengkap

## Keuntungan

1. **Informasi Jelas**: User tahu persis kenapa CP tidak muncul
2. **Guided Solution**: Tombol langsung ke halaman yang tepat untuk solusi
3. **Membedakan Kasus**: Alert berbeda untuk 3 kasus berbeda:
   - Belum mapping (warning kuning)
   - Mapping tidak sesuai level (danger merah)
   - Belum ada CP (info biru)
4. **Validasi Level Sekolah**: Mencegah tampilnya CP dari jenjang yang salah
5. **User-Friendly**: Penjelasan yang mudah dipahami dengan visual yang jelas
6. **Efisiensi**: User tidak perlu bingung mencari halaman mapping/master CP
7. **Data Konsisten**: Memastikan CP yang ditampilkan sesuai dengan level sekolah saat ini

## Alur Perbaikan Lengkap

```
1. Buat Mapel Master
   ↓
2. Mapping Mapel Lokal ke Mapel Master
   ↓
3. Buat CP di Master CP untuk Mapel Master
   ↓
4. CP bisa ditampilkan di halaman CP
```

## Testing

### Test 1: Mapel Belum Di-mapping
```sql
-- Set mapel_master_id to NULL
UPDATE subjects SET mapel_master_id = NULL WHERE id = 1;
```
- Pilih mapel tersebut di halaman CP
- Harus muncul alert warning "Mata Pelajaran Belum Di-mapping"
- Tombol "Ke Halaman Mapping" harus ada

### Test 2: Mapping Mismatch (Level Sekolah Diubah) - BARU
```sql
-- Set school level to SMP
UPDATE school_profile SET level = 2;

-- Subject still mapped to SD mapel_master
UPDATE subjects SET mapel_master_id = 1 WHERE id = 1;

-- Ensure mapel_master is SD (jenjang_id = 1)
SELECT * FROM mapel_master WHERE id = 1 AND jenjang_id = 1;
```
- Pilih mapel tersebut di halaman CP
- Harus muncul alert danger "Mapping Tidak Sesuai dengan Level Sekolah"
- Harus menampilkan jenjang lama (SD) vs jenjang sekarang (SMP)
- Tombol "Pemetaan Ulang" harus ada
- CP tidak boleh ditampilkan

### Test 3: Mapel Sudah Di-mapping, Belum Ada CP
```sql
-- Set mapel_master_id to valid ID
UPDATE subjects SET mapel_master_id = 1 WHERE id = 1;

-- Delete all CP for that mapel_master
DELETE FROM cp_master WHERE mapel_master_id = 1;
```
- Pilih mapel tersebut di halaman CP
- Harus muncul alert info "Belum Ada Data CP"
- Tombol "Ke Halaman Master CP" harus ada

### Test 4: Mapel Sudah Di-mapping dan Ada CP
```sql
-- Ensure mapping exists
UPDATE subjects SET mapel_master_id = 1 WHERE id = 1;

-- Ensure CP exists
INSERT INTO cp_master (mapel_master_id, elemen, fase, deskripsi, nomor_sk, tahun) 
VALUES (1, 'Bilangan', 'D', 'Deskripsi CP', '033/H/KR/2022', 2024);
```
- Pilih mapel tersebut di halaman CP
- Harus muncul tabel CP dengan data

## File yang Dimodifikasi

1. `app/Controllers/Admin/AdministrasiGuru.php` - Added mapping validation in cp() method
2. `app/Views/admin/administrasi_guru/cp.php` - Added informative alerts for unmapped subjects

## Hubungan dengan Fitur Lain

Perbaikan ini juga berlaku untuk halaman lain yang bergantung pada mapping:
- **TP (Tujuan Pembelajaran)**: Sama seperti CP, perlu mapping
- **ATP (Alur Tujuan Pembelajaran)**: Bergantung pada TP yang bergantung pada CP
- **Prota/Prosem**: Bergantung pada ATP

Semua halaman ini sebaiknya memiliki validasi mapping yang sama.

## Status
✅ Selesai - Halaman CP sekarang memberikan informasi yang jelas tentang status mapping, memvalidasi kesesuaian jenjang dengan level sekolah, dan mengarahkan user ke solusi yang tepat
