# Implementasi Isolasi Data CBT untuk Guru

## Langkah-Langkah Implementasi

### Step 1: Jalankan Migration
```bash
php spark migrate
```

Ini akan menambahkan kolom `created_by` ke tabel `cbt_exam_names`.

### Step 2: Load Helper CBT di Autoload
Edit `app/Config/Autoload.php`:

```php
public $helpers = ['authorization', 'cbt']; // Tambahkan 'cbt'
```

### Step 3: Update Controller CbtBankSoal.php

Buka file `app/Controllers/Admin/CbtBankSoal.php` dan lakukan perubahan berikut:

#### 3.1. Update Constructor
```php
public function __construct()
{
    $this->bankModel = new CbtBankSoalModel();
    $this->questionModel = new CbtQuestionModel();
    helper('cbt'); // Tambahkan ini
}
```

#### 3.2. Update Method index()
Ganti baris 22-42 dengan:
```php
public function index()
{
    $context = get_cbt_user_context();

    if ($context['is_admin']) {
        $banks = $this->bankModel->getListWithCounts();
    } elseif ($context['is_teacher'] && $context['teacher_id']) {
        $banks = $this->bankModel->getListWithCounts('teacher', $context['teacher_id']);
    } else {
        $banks = [];
    }

    foreach ($banks as &$bank) {
        $bank['creator_name'] = !empty($bank['teacher_name'])
            ? $bank['teacher_name']
            : 'Admin';
    }

    return view('admin/cbt/banksoal/index', [
        'title' => 'Daftar Bank Soal',
        'banks' => $banks,
    ]);
}
```

#### 3.3. Update Method storeAjax()
Ganti baris 76-82 dengan:
```php
$context = get_cbt_user_context();
$teacherId = $context['teacher_id'];

$subjectId = $this->request->getPost('subject_id');

// Ambil nama pembuat
$creatorName = session()->get('user')['fullname'] ?? 'Admin';
if ($teacherId) {
    $creator = $db->table('teachers')->select('name')->where('id', $teacherId)->get()->getRow();
    if ($creator) {
        $creatorName = $creator->name;
    }
}
```

#### 3.4. Tambahkan Validasi di Awal Setiap Method

Tambahkan di awal method `detail($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `copy($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `print($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `edit_Soal($bankId, $questionId)`:
```php
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `updateSoal($bankId, $questionId)`:
```php
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `delete($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke bank soal ini.'
    ]);
}
```

Tambahkan di awal method `toggle($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return $this->response->setJSON([
        'success' => false,
        'message' => 'Anda tidak memiliki akses ke bank soal ini.'
    ]);
}
```

Tambahkan di awal method `tambahSoal($bankId)`:
```php
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di method `parseSoal()` setelah get bank_id:
```php
$bankId = $this->request->getPost('bank_id');
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `saveParsedSoal($bankId)`:
```php
if (!can_access_cbt_bank($bankId)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

Tambahkan di awal method `backup($id)`:
```php
if (!can_access_cbt_bank($id)) {
    return redirect()->back()->with('error', 'Anda tidak memiliki akses ke bank soal ini.');
}
```

### Step 4: Update Controller CbtExamName.php

File ini perlu dibuat atau diupdate untuk menambahkan isolasi data.

### Step 5: Update Controller CbtTestStatus.php

Perlu menambahkan:
1. Filter bank soal berdasarkan teacher_id
2. Filter kelas berdasarkan teaching assignment
3. Set created_by saat create
4. Validasi ownership saat update/delete

### Step 6: Update Controller CbtAktivitas.php

Perlu menambahkan:
1. Filter aktivitas berdasarkan created_by dari test_status

### Step 7: Update Controller CbtConvertNilai.php

Perlu menambahkan:
1. Filter berdasarkan teaching assignment

## Prioritas Implementasi

### Priority 1 (CRITICAL) - Selesaikan Hari Ini
- [x] Buat migration untuk cbt_exam_names
- [x] Buat helper cbt_helper.php
- [x] Update CbtExamNameModel
- [ ] Update CbtBankSoal controller (index, storeAjax, detail, delete)

### Priority 2 (HIGH) - Besok
- [ ] Tambahkan validasi ownership di semua method CbtBankSoal
- [ ] Update CbtTestStatus controller
- [ ] Update CbtExamName controller

### Priority 3 (MEDIUM) - Lusa
- [ ] Update CbtAktivitas controller
- [ ] Update CbtConvertNilai controller
- [ ] Testing menyeluruh

## Testing Checklist

### Bank Soal
- [ ] Admin login → bisa lihat semua bank soal
- [ ] Guru A login → hanya lihat bank soal milik Guru A
- [ ] Guru A coba akses detail bank soal Guru B → ditolak
- [ ] Guru A coba edit bank soal Guru B → ditolak
- [ ] Guru A coba delete bank soal Guru B → ditolak
- [ ] Guru A coba copy bank soal Guru B → ditolak

### Status Tes
- [ ] Guru A buat status tes → hanya bisa pilih bank soal milik Guru A
- [ ] Guru A buat status tes → hanya bisa pilih kelas yang diampu
- [ ] Guru A coba edit status tes Guru B → ditolak

### Aktivitas
- [ ] Guru A lihat aktivitas → hanya ujian yang dijadwalkan Guru A
- [ ] Admin lihat aktivitas → semua ujian

### Konversi Nilai
- [ ] Guru A konversi nilai → hanya mata pelajaran yang diampu
- [ ] Admin konversi nilai → semua mata pelajaran

## Catatan Penting

1. Backup database sebelum menjalankan migration
2. Test di development environment dulu
3. Pastikan semua guru sudah memiliki teaching assignment
4. Data existing yang tidak memiliki teacher_id akan tetap accessible oleh semua guru
5. Admin selalu memiliki akses penuh ke semua data
