# Perbaikan Controller CbtBankSoal untuk Isolasi Data Guru

## Perubahan yang Perlu Dilakukan

### 1. Load Helper di Constructor
```php
public function __construct()
{
    $this->bankModel = new CbtBankSoalModel();
    $this->questionModel = new CbtQuestionModel();
    helper('cbt'); // ← Tambahkan ini
}
```

### 2. Fix Method index()
**Masalah**: Role ID salah (2 seharusnya 3)

```php
// SEBELUM (SALAH)
if ($roleId == 1) {
    $banks = $this->bankModel->getListWithCounts();
} elseif ($roleId == 2 && $teacherId) {  // ← SALAH!
    $banks = $this->bankModel->getListWithCounts('teacher', (int) $teacherId);
}

// SESUDAH (BENAR)
$context = get_cbt_user_context();

if ($context['is_admin']) {
    $banks = $this->bankModel->getListWithCounts();
} elseif ($context['is_teacher'] && $context['teacher_id']) {
    $banks = $this->bankModel->getListWithCounts('teacher', $context['teacher_id']);
} else {
    $banks = [];
}
```

### 3. Fix Method storeAjax()
**Masalah**: Role ID salah, session key salah

```php
// SEBELUM
$user = session()->get('user');
$teacherId = ($user['role_id'] ?? 0) == 2
    ? ($user['teacher_id'] ?? null)  // ← SALAH! Seharusnya related_id
    : null;

// SESUDAH
$context = get_cbt_user_context();
$teacherId = $context['teacher_id'];
```

### 4. Tambah Validasi Ownership di detail()
```php
public function detail($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 5. Tambah Validasi Ownership di copy()
```php
public function copy($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 6. Tambah Validasi Ownership di print()
```php
public function print($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 7. Tambah Validasi Ownership di edit_Soal()
```php
public function edit_Soal($bankId, $questionId)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($bankId)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 8. Tambah Validasi Ownership di updateSoal()
```php
public function updateSoal($bankId, $questionId)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($bankId)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 9. Tambah Validasi Ownership di delete()
```php
public function delete($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke bank soal ini.'
        ]);
    }
    
    // ... kode existing
}
```

### 10. Tambah Validasi Ownership di bulkDelete()
```php
public function bulkDelete()
{
    $ids = $this->request->getPost('ids');
    
    // Validasi setiap ID
    foreach ($ids as $id) {
        if (!can_access_cbt_bank($id)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke salah satu bank soal yang dipilih.'
            ]);
        }
    }
    
    // ... kode existing
}
```

### 11. Tambah Validasi Ownership di toggle()
```php
public function toggle($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Anda tidak memiliki akses ke bank soal ini.'
        ]);
    }
    
    // ... kode existing
}
```

### 12. Tambah Validasi Ownership di tambahSoal()
```php
public function tambahSoal($bankId)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($bankId)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 13. Tambah Validasi Ownership di parseSoal()
```php
public function parseSoal()
{
    $bankId = $this->request->getPost('bank_id');
    
    // Tambahkan validasi
    if (!can_access_cbt_bank($bankId)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 14. Tambah Validasi Ownership di saveParsedSoal()
```php
public function saveParsedSoal($bankId)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($bankId)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

### 15. Tambah Validasi Ownership di backup()
```php
public function backup($id)
{
    // Tambahkan validasi
    if (!can_access_cbt_bank($id)) {
        return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
    }
    
    // ... kode existing
}
```

## Summary Perubahan
- Total method yang perlu diperbaiki: 15 methods
- Tambah helper load di constructor
- Fix role_id check (2 → 3)
- Fix session key (teacher_id → related_id)
- Tambah validasi ownership di semua method CRUD
- Gunakan helper function `get_cbt_user_context()` dan `can_access_cbt_bank()`

## Testing
Setelah perbaikan, test:
1. Admin bisa akses semua bank soal
2. Guru hanya bisa akses bank soal miliknya
3. Guru tidak bisa edit/delete/copy bank soal guru lain
4. Guru tidak bisa tambah soal ke bank soal guru lain
5. Error message muncul jika akses ditolak
