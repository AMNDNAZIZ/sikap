# Perbaikan Error: Call to undefined function getSubjects()

## Masalah
Halaman bank soal (`/admin/cbt/banksoal`) menampilkan error:
```
Call to undefined function getSubjects()
```

## Penyebab
View `app/Views/admin/cbt/banksoal/index.php` memanggil function `getSubjects()` yang tidak ada di helper manapun. Function ini seharusnya diganti dengan variabel `$subjects` yang dikirim dari controller.

## Solusi

### 1. Update Controller CbtBankSoal.php
Tambahkan data subjects ke method `index()` dengan filter berdasarkan user:

```php
public function index()
{
    $context = get_cbt_user_context();

    // ... kode existing untuk banks ...

    // Get subjects - filter untuk guru
    $subjectModel = new \App\Models\SubjectModel();
    if ($context['is_admin']) {
        $subjects = $subjectModel->orderBy('name', 'ASC')->findAll();
    } elseif ($context['is_teacher'] && $context['teacher_id']) {
        $subjects = get_teacher_subjects($context['teacher_id']);
    } else {
        $subjects = [];
    }

    return view('admin/cbt/banksoal/index', [
        'title' => 'Daftar Bank Soal',
        'banks' => $banks,
        'subjects' => $subjects, // ← Tambahkan ini
    ]);
}
```

### 2. Update View index.php
Ganti `getSubjects()` dengan `$subjects`:

**Sebelum:**
```php
<?php foreach (getSubjects() as $m): ?>
    <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
<?php endforeach; ?>
```

**Sesudah:**
```php
<?php foreach ($subjects as $m): ?>
    <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
<?php endforeach; ?>
```

Perubahan dilakukan di 2 tempat:
1. Modal "Tambah Bank Soal" (baris ~125)
2. Modal "Edit Bank Soal" (baris ~168)

## Manfaat Tambahan

Dengan perubahan ini, sekarang dropdown mata pelajaran juga diisolasi:
- **Admin**: Bisa melihat semua mata pelajaran
- **Guru**: Hanya bisa melihat mata pelajaran yang ia ampu

Ini konsisten dengan isolasi data CBT yang sudah diimplementasikan.

## Files Modified

1. ✅ `app/Controllers/Admin/CbtBankSoal.php` - Method `index()`
2. ✅ `app/Views/admin/cbt/banksoal/index.php` - 2 tempat

## Testing

1. Login sebagai Admin
2. Buka `/admin/cbt/banksoal`
3. Klik "Tambah Bank Soal"
4. ✅ Dropdown "Mata Pelajaran" harus menampilkan semua mata pelajaran
5. Halaman tidak error lagi

6. Login sebagai Guru A
7. Buka `/admin/cbt/banksoal`
8. Klik "Tambah Bank Soal"
9. ✅ Dropdown "Mata Pelajaran" hanya menampilkan mata pelajaran yang diampu Guru A

## Catatan

- File `app/Views/admin/cbt/banksoal/form.php` tidak digunakan, jadi tidak perlu diperbaiki
- Helper function `get_teacher_subjects()` sudah ada di `app/Helpers/cbt_helper.php`
- Isolasi subjects konsisten dengan isolasi bank soal dan status tes
