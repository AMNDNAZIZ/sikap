# Perbaikan: Tombol Hapus Masal Tidak Muncul di Admin

## Masalah

Tombol "Hapus Terpilih" (bulk delete) muncul di halaman guru tapi tidak muncul di halaman admin.

## Penyebab

Kondisi di view salah:
```php
<?php if (!empty($bank['teacher_name'])) { ?>
  <button type="submit" class="btn btn-danger btn-sm">
    <i class="bi bi-trash"></i> Hapus Terpilih
  </button>
<?php } ?>
```

**Masalah**:
1. Variabel `$bank` tidak tersedia di footer (sudah keluar dari loop)
2. Kondisi `!empty($bank['teacher_name'])` salah karena:
   - Admin tidak punya teacher_name → tombol tidak muncul
   - Guru punya teacher_name → tombol muncul

## Solusi

### 1. Hapus Kondisi yang Salah

**File**: `app/Views/admin/cbt/banksoal/index.php`

**Sebelum**:
```php
<div class="card-footer">
  <?php if (!empty($bank['teacher_name'])) { ?>
    <button type="submit" class="btn btn-danger btn-sm"
      onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
      <i class="bi bi-trash"></i> Hapus Terpilih
    </button>
  <?php } else { } ?>
</div>
```

**Sesudah**:
```php
<div class="card-footer">
  <?php 
  // Tampilkan tombol hapus masal untuk semua user (admin dan guru)
  // Admin bisa hapus semua, guru hanya bisa hapus miliknya (sudah difilter di controller)
  ?>
  <button type="submit" class="btn btn-danger btn-sm"
    onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
    <i class="bi bi-trash"></i> Hapus Terpilih
  </button>
</div>
```

### 2. Tambah Context ke View (Optional)

**File**: `app/Controllers/Admin/CbtBankSoal.php`

```php
return view('admin/cbt/banksoal/index', [
    'title' => 'Daftar Bank Soal',
    'banks' => $banks,
    'subjects' => $subjects,
    'context' => $context,  // ← Tambahan (untuk keperluan future)
]);
```

## Keamanan

Tombol hapus masal sekarang muncul untuk semua user, tapi tetap aman karena:

1. **Validasi di Controller**: Method `bulkDelete()` sudah memiliki validasi ownership
```php
public function bulkDelete()
{
    $ids = $this->request->getPost('ids');
    
    // Validasi ownership untuk setiap ID
    foreach ($ids as $id) {
        if (!can_access_cbt_bank($id)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses...');
        }
    }
    
    // Hapus hanya jika semua ID valid
    foreach ($ids as $id) {
        $this->questionModel->where('bank_id', $id)->delete();
        $this->bankModel->delete($id);
    }
    
    return redirect()->back()->with('success', 'Data terpilih berhasil dihapus.');
}
```

2. **Filter di Index**: Data yang ditampilkan sudah difilter berdasarkan role
   - Admin: Melihat semua bank soal
   - Guru: Hanya melihat bank soal miliknya

## Hasil

| User | Tombol Muncul | Bisa Hapus |
|------|---------------|------------|
| Admin | ✅ Ya | Semua bank soal |
| Guru | ✅ Ya | Hanya bank soal miliknya |

## Testing

### Test 1: Admin Hapus Masal
1. Login sebagai Admin
2. Buka `/admin/cbt/banksoal`
3. ✅ Tombol "Hapus Terpilih" muncul
4. Pilih beberapa bank soal (milik guru berbeda)
5. Klik "Hapus Terpilih"
6. ✅ Berhasil dihapus

### Test 2: Guru Hapus Masal Bank Soal Sendiri
1. Login sebagai Guru A
2. Buka `/admin/cbt/banksoal`
3. ✅ Tombol "Hapus Terpilih" muncul
4. Pilih beberapa bank soal milik Guru A
5. Klik "Hapus Terpilih"
6. ✅ Berhasil dihapus

### Test 3: Guru Coba Hapus Bank Soal Guru Lain (Security Test)
1. Login sebagai Guru A
2. Buka `/admin/cbt/banksoal` (hanya melihat bank soal miliknya)
3. Guru A tidak bisa melihat bank soal Guru B
4. ✅ Tidak bisa hapus bank soal guru lain (tidak terlihat)

### Test 4: Guru Coba Bypass dengan POST Manual (Advanced Security Test)
1. Login sebagai Guru A
2. Coba POST manual dengan ID bank soal Guru B:
```bash
curl -X POST http://localhost:8080/siakad/admin/cbt/banksoal/bulkDelete \
  -d "ids[]=ID_BANK_GURU_B" \
  -H "Cookie: ci_session=..."
```
3. ✅ Error: "Anda tidak memiliki akses ke salah satu bank soal yang dipilih"

## File yang Dimodifikasi

1. `app/Views/admin/cbt/banksoal/index.php` - Hapus kondisi yang salah
2. `app/Controllers/Admin/CbtBankSoal.php` - Tambah context ke view (optional)

---

**Status**: ✅ FIXED
**Date**: April 7, 2026
**Security**: ✅ Validated (ownership check di controller)
