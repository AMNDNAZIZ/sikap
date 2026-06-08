# Perbaikan Live Indicator untuk Multi Kelas di Dashboard Admin

## Masalah
Pada dashboard admin bagian "Jadwal Pelajaran Hari Ini", tanda "LIVE" hanya muncul pada satu kelas saja, padahal ada beberapa kelas lain yang juga sedang berlangsung di waktu yang sama. Ini menyebabkan informasi yang tidak akurat dan membingungkan.

## Penyebab
Logika penentuan sesi yang sedang berlangsung menggunakan variabel `$hasCurrentSession` yang hanya menyimpan **satu** jadwal pertama yang ditemukan, kemudian menggunakan `break` untuk menghentikan loop. Akibatnya, hanya satu sesi yang ditandai sebagai "current".

### Kode Lama (Bermasalah):
```php
$hasCurrentSession = false;

if (($rekapDate ?? date('Y-m-d')) == date('Y-m-d')) {
  foreach ($todaySchedule as $item) {
    if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
      $hasCurrentSession = $item; // Hanya simpan satu
      break; // ❌ Berhenti setelah menemukan yang pertama
    }
  }
}

// Kemudian di loop jadwal:
$isCurrent = ($hasCurrentSession && $item['id'] == $hasCurrentSession['id']);
// ❌ Hanya satu jadwal yang akan match
```

**Masalah**:
1. `break` menghentikan loop setelah menemukan sesi pertama
2. Hanya satu sesi yang disimpan di `$hasCurrentSession`
3. Sesi lain yang berlangsung di waktu yang sama tidak terdeteksi

## Solusi
Mengubah logika untuk menyimpan **semua** sesi yang sedang berlangsung dalam array, bukan hanya satu sesi.

### Kode Baru (Diperbaiki):
```php
$currentSessions = []; // ✅ Array untuk menyimpan semua sesi

if (($rekapDate ?? date('Y-m-d')) == date('Y-m-d')) {
  foreach ($todaySchedule as $item) {
    if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
      $currentSessions[] = $item; // ✅ Simpan semua sesi yang berlangsung
      // ✅ Tidak ada break, loop terus sampai selesai
    }
  }
}

// Kemudian di loop jadwal:
$isCurrent = false;
foreach ($currentSessions as $currentSession) {
  if ($item['id'] == $currentSession['id']) {
    $isCurrent = true; // ✅ Semua sesi yang berlangsung akan ditandai
    break;
  }
}
```

**Perbaikan**:
1. Menggunakan array `$currentSessions[]` untuk menyimpan semua sesi
2. Tidak ada `break` di loop pertama, sehingga semua sesi terdeteksi
3. Loop kedua mengecek apakah jadwal ada di array `$currentSessions`

## Perubahan yang Dilakukan

### 1. Mengubah Variabel dari Single ke Array

**Sebelum**:
```php
$hasCurrentSession = false; // Boolean atau single object
```

**Sesudah**:
```php
$currentSessions = []; // Array untuk multiple objects
```

### 2. Menghapus Break Statement

**Sebelum**:
```php
foreach ($todaySchedule as $item) {
  if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
    $hasCurrentSession = $item;
    break; // ❌ Berhenti di sesi pertama
  }
}
```

**Sesudah**:
```php
foreach ($todaySchedule as $item) {
  if ($currentTime >= $item['start_time'] && $currentTime <= $item['end_time']) {
    $currentSessions[] = $item; // ✅ Simpan semua
    // ✅ Tidak ada break
  }
}
```

### 3. Mengubah Logika Pengecekan Current Session

**Sebelum**:
```php
$isCurrent = ($hasCurrentSession && $item['id'] == $hasCurrentSession['id']);
// ❌ Hanya match dengan satu ID
```

**Sesudah**:
```php
$isCurrent = false;
foreach ($currentSessions as $currentSession) {
  if ($item['id'] == $currentSession['id']) {
    $isCurrent = true;
    break; // ✅ Break di sini OK karena sudah ketemu
  }
}
// ✅ Bisa match dengan multiple IDs
```

### 4. Update Alert Banner

**Sebelum**:
```php
<?php if ($hasCurrentSession && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
  <div class="alert alert-info">
    <strong>Sesi Berlangsung:</strong> <?= esc($hasCurrentSession['subject_name']) ?>
    (<?= substr($hasCurrentSession['start_time'], 0, 5) ?> - <?= substr($hasCurrentSession['end_time'], 0, 5) ?>)
  </div>
<?php endif; ?>
```

**Sesudah**:
```php
<?php if (!empty($currentSessions) && ($rekapDate ?? date('Y-m-d')) == date('Y-m-d')): ?>
  <div class="alert alert-info">
    <strong>Sesi Berlangsung:</strong> 
    <?php if (count($currentSessions) == 1): ?>
      <?= esc($currentSessions[0]['subject_name']) ?> - <?= esc($currentSessions[0]['class_name']) ?>
      (<?= substr($currentSessions[0]['start_time'], 0, 5) ?> - <?= substr($currentSessions[0]['end_time'], 0, 5) ?>)
    <?php else: ?>
      <?= count($currentSessions) ?> sesi sedang berlangsung
    <?php endif; ?>
  </div>
<?php endif; ?>
```

**Fitur Baru**:
- Jika hanya 1 sesi: Tampilkan detail lengkap (mata pelajaran, kelas, waktu)
- Jika lebih dari 1 sesi: Tampilkan jumlah sesi yang berlangsung

### 5. Update Summary Footer

**Sebelum**:
```php
<div class="h5 mb-0 fw-bold text-info">
  <?= $hasCurrentSession ? '1' : '0' ?>
</div>
<small class="text-muted">Sesi Berlangsung</small>
```

**Sesudah**:
```php
<div class="h5 mb-0 fw-bold text-info">
  <?= count($currentSessions) ?>
</div>
<small class="text-muted">Sesi Berlangsung</small>
```

**Perbaikan**: Menampilkan jumlah sesi yang sebenarnya sedang berlangsung, bukan hanya 0 atau 1.

## Skenario Penggunaan

### Skenario 1: Tidak Ada Sesi Berlangsung
**Kondisi**: Waktu sekarang 07:00, jadwal dimulai 08:00

**Hasil**:
- Alert banner: Tidak muncul
- Badge LIVE: Tidak ada
- Summary: "0 Sesi Berlangsung"

### Skenario 2: Satu Sesi Berlangsung
**Kondisi**: Waktu sekarang 08:30, hanya Kelas 7A yang ada jadwal 08:00-09:00

**Hasil**:
- Alert banner: "Sesi Berlangsung: Matematika - Kelas 7A (08:00 - 09:00)"
- Badge LIVE: Muncul di Kelas 7A
- Summary: "1 Sesi Berlangsung"

### Skenario 3: Multiple Sesi Berlangsung (FIXED!)
**Kondisi**: Waktu sekarang 08:30, ada 3 kelas dengan jadwal 08:00-09:00
- Kelas 7A: Matematika
- Kelas 7B: Bahasa Indonesia
- Kelas 8A: IPA

**Hasil Sebelum Perbaikan** ❌:
- Alert banner: "Sesi Berlangsung: Matematika (08:00 - 09:00)"
- Badge LIVE: Hanya muncul di Kelas 7A
- Summary: "1 Sesi Berlangsung"

**Hasil Setelah Perbaikan** ✅:
- Alert banner: "Sesi Berlangsung: 3 sesi sedang berlangsung"
- Badge LIVE: Muncul di Kelas 7A, 7B, dan 8A
- Summary: "3 Sesi Berlangsung"

### Skenario 4: Sesi Bergantian
**Kondisi**: Waktu sekarang 09:30
- Kelas 7A: 08:00-09:00 (selesai)
- Kelas 7B: 09:00-10:00 (berlangsung)
- Kelas 8A: 10:00-11:00 (belum mulai)

**Hasil**:
- Alert banner: "Sesi Berlangsung: Bahasa Indonesia - Kelas 7B (09:00 - 10:00)"
- Badge LIVE: Hanya di Kelas 7B
- Badge "Selesai": Di Kelas 7A
- Badge "Dimulai dalam X menit": Di Kelas 8A (jika < 30 menit)
- Summary: "1 Sesi Berlangsung"

## Keuntungan

### 1. Akurasi Informasi
- Semua sesi yang sedang berlangsung ditandai dengan benar
- Tidak ada informasi yang hilang atau terlewat
- Admin bisa melihat semua aktivitas real-time

### 2. Monitoring yang Lebih Baik
- Admin bisa melihat berapa banyak kelas yang sedang belajar
- Memudahkan koordinasi dan pengawasan
- Lebih mudah identifikasi jadwal yang bentrok

### 3. User Experience
- Informasi lebih jelas dan tidak membingungkan
- Visual indicator yang konsisten
- Summary yang akurat

### 4. Scalability
- Bisa handle banyak kelas yang berlangsung bersamaan
- Tidak ada batasan jumlah sesi
- Performance tetap optimal

## Testing

### Test Case 1: Tidak Ada Sesi Berlangsung
1. Buka dashboard admin di luar jam pelajaran (misal pagi sebelum sekolah)
2. **Expected**: 
   - Tidak ada badge LIVE
   - Alert banner tidak muncul
   - Summary: "0 Sesi Berlangsung"

### Test Case 2: Satu Sesi Berlangsung
1. Buka dashboard admin saat hanya satu kelas yang sedang belajar
2. **Expected**: 
   - Badge LIVE muncul di satu kelas
   - Alert banner: "Sesi Berlangsung: [Mata Pelajaran] - [Kelas] ([Waktu])"
   - Summary: "1 Sesi Berlangsung"

### Test Case 3: Multiple Sesi Berlangsung (CRITICAL TEST)
1. Buat jadwal untuk 3+ kelas di waktu yang sama (misal: 08:00-09:00)
2. Buka dashboard admin di waktu tersebut (08:00-09:00)
3. **Expected**: 
   - Badge LIVE muncul di **SEMUA** kelas yang sedang berlangsung
   - Alert banner: "Sesi Berlangsung: X sesi sedang berlangsung"
   - Summary: "X Sesi Berlangsung" (X = jumlah kelas yang berlangsung)

### Test Case 4: Transisi Waktu
1. Buka dashboard admin tepat saat sesi berakhir (misal 09:00)
2. Refresh halaman
3. **Expected**: 
   - Badge LIVE hilang dari sesi yang baru selesai
   - Badge LIVE muncul di sesi yang baru dimulai
   - Summary update otomatis

### Test Case 5: Filter Kelas
1. Buka dashboard admin saat multiple sesi berlangsung
2. Filter hanya satu kelas
3. **Expected**: 
   - Hanya jadwal kelas yang dipilih yang muncul
   - Badge LIVE tetap muncul jika kelas tersebut sedang berlangsung
   - Summary hanya menghitung kelas yang difilter

## Performance Consideration

### Kompleksitas Algoritma

**Sebelum**:
- Loop pertama: O(n) dengan early break → O(1) best case
- Pengecekan current: O(1)
- **Total**: O(n) worst case, O(1) best case

**Sesudah**:
- Loop pertama: O(n) tanpa break → O(n) always
- Loop kedua (nested): O(n × m) dimana m = jumlah current sessions
- **Total**: O(n × m) worst case

**Analisis**:
- Worst case: Jika semua jadwal berlangsung bersamaan, m = n → O(n²)
- Typical case: m << n (biasanya hanya 1-5 sesi berlangsung) → O(n)
- Best case: m = 0 (tidak ada sesi berlangsung) → O(n)

**Kesimpulan**: 
- Performance impact minimal karena:
  - n (total jadwal per hari) biasanya < 50
  - m (sesi berlangsung) biasanya < 10
  - Operasi hanya comparison, sangat cepat
  - Tidak ada database query tambahan

### Memory Usage

**Sebelum**: 
- 1 object atau boolean → ~1KB

**Sesudah**: 
- Array of objects → ~1KB × jumlah sesi berlangsung
- Typical: 5 sesi × 1KB = 5KB
- Worst case: 50 sesi × 1KB = 50KB

**Kesimpulan**: Memory impact negligible (< 100KB worst case)

## File yang Dimodifikasi

1. `app/Views/dashboard/admin_modern.php` - Logika penentuan sesi berlangsung

## Catatan Teknis

### Mengapa Tidak Menggunakan array_filter()?

**Alternatif dengan array_filter**:
```php
$currentSessions = array_filter($todaySchedule, function($item) use ($currentTime) {
  return $currentTime >= $item['start_time'] && $currentTime <= $item['end_time'];
});
```

**Alasan menggunakan foreach**:
- Lebih readable dan mudah dipahami
- Performance sama (array_filter juga O(n))
- Lebih fleksibel untuk modifikasi di masa depan

### Mengapa Break di Loop Kedua?

Di loop kedua (pengecekan `$isCurrent`), kita menggunakan `break` setelah menemukan match:

```php
foreach ($currentSessions as $currentSession) {
  if ($item['id'] == $currentSession['id']) {
    $isCurrent = true;
    break; // ✅ OK karena sudah ketemu
  }
}
```

**Alasan**:
- Setelah menemukan match, tidak perlu cek lagi
- Menghemat iterasi yang tidak perlu
- ID unik, tidak mungkin ada duplicate

### Alternatif: in_array()

**Bisa juga menggunakan**:
```php
$currentSessionIds = array_column($currentSessions, 'id');
$isCurrent = in_array($item['id'], $currentSessionIds);
```

**Trade-off**:
- Lebih concise
- Tapi perlu extract IDs dulu (overhead)
- Untuk jumlah kecil, foreach lebih efisien

## Referensi
- PHP Arrays: https://www.php.net/manual/en/language.types.array.php
- PHP foreach: https://www.php.net/manual/en/control-structures.foreach.php
- Algorithm Complexity: https://www.bigocheatsheet.com/
