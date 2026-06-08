# Debug: Bank Soal Tidak Muncul Setelah Restore

## Masalah
Bank soal berhasil di-restore (terlihat di log), tapi tidak muncul di halaman `/admin/cbt/banksoal`

## Kemungkinan Penyebab

### 1. Teacher ID Tidak Cocok
**Penyebab**: Bank soal ter-restore dengan teacher_id yang berbeda dari user yang login

**Cara Cek**:
```sql
-- Cek teacher_id dari user yang login
SELECT 
    u.id as user_id,
    u.username,
    u.role_id,
    u.related_id as teacher_id,
    u.related_type
FROM users u
WHERE u.id = (SELECT id FROM users WHERE username = 'USERNAME_ANDA');

-- Cek teacher_id dari bank soal yang di-restore
SELECT id, code, teacher_id, is_active
FROM cbt_question_banks
WHERE id = 11;
```

**Solusi**: Jika teacher_id tidak cocok, update manual:
```sql
UPDATE cbt_question_banks
SET teacher_id = TEACHER_ID_ANDA
WHERE id = 11;
```

### 2. Cache Browser
**Penyebab**: Browser masih menampilkan data lama

**Solusi**:
- Tekan `Ctrl + F5` (Windows) atau `Cmd + Shift + R` (Mac) untuk hard refresh
- Atau buka halaman di incognito/private mode

### 3. Session Issue
**Penyebab**: Session user tidak ter-update dengan benar

**Solusi**:
- Logout dan login kembali
- Clear session di database:
```sql
DELETE FROM ci_sessions WHERE id = 'SESSION_ID_ANDA';
```

### 4. Role Bukan Guru
**Penyebab**: User yang login bukan role guru (role_id != 3)

**Cara Cek**:
```sql
SELECT id, username, role_id, related_type, related_id
FROM users
WHERE username = 'USERNAME_ANDA';
```

**Expected**:
- `role_id` = 3 (Guru)
- `related_type` = 'teacher'
- `related_id` = ID guru di tabel teachers

### 5. Filter di Controller
**Penyebab**: Controller memfilter berdasarkan teacher_id

**Cara Cek**: Lihat di log apakah ada query yang dijalankan saat buka halaman bank soal

## Langkah Debugging

### Step 1: Cek Data di Database
```sql
-- 1. Cek bank soal hasil restore
SELECT * FROM cbt_question_banks WHERE id = 11;

-- 2. Cek user yang login
SELECT u.*, t.name as teacher_name
FROM users u
LEFT JOIN teachers t ON t.id = u.related_id
WHERE u.username = 'USERNAME_ANDA';

-- 3. Cek apakah teacher_id cocok
SELECT 
    b.id,
    b.code,
    b.teacher_id,
    u.related_id as user_teacher_id,
    CASE 
        WHEN b.teacher_id = u.related_id THEN 'MATCH'
        WHEN b.teacher_id IS NULL THEN 'NULL (Admin)'
        ELSE 'NOT MATCH'
    END as status
FROM cbt_question_banks b
CROSS JOIN users u
WHERE b.id = 11
AND u.username = 'USERNAME_ANDA';
```

### Step 2: Cek Log Aplikasi
Buka `writable/logs/log-2026-04-07.php` dan cari:
```
DEBUG - 2026-04-07 XX:XX:XX --> --- BASE CONTROLLER INIT: /siakad/index.php/admin/cbt/banksoal ---
```

Lihat apakah ada error atau query yang mencurigakan setelah baris tersebut.

### Step 3: Test dengan Admin
1. Login sebagai Admin
2. Buka `/admin/cbt/banksoal`
3. Apakah bank soal ID 11 muncul?

**Jika muncul di Admin tapi tidak di Guru**: Masalahnya adalah teacher_id tidak cocok

### Step 4: Cek Context Helper
Tambahkan debug di controller `CbtBankSoal.php` method `index()`:

```php
public function index()
{
    $context = get_cbt_user_context();
    
    // DEBUG: Tampilkan context
    log_message('debug', 'CBT Context: ' . json_encode($context));
    
    if ($context['is_admin']) {
        $banks = $this->bankModel->getListWithCounts();
    } elseif ($context['is_teacher'] && $context['teacher_id']) {
        $banks = $this->bankModel->getListWithCounts('teacher', $context['teacher_id']);
        
        // DEBUG: Tampilkan jumlah bank
        log_message('debug', 'Total banks for teacher ' . $context['teacher_id'] . ': ' . count($banks));
    } else {
        $banks = [];
    }
    
    // ... rest of code
}
```

Kemudian buka halaman dan cek log.

## Solusi Cepat

### Jika Teacher ID Tidak Cocok:

**Option 1: Update Manual di Database**
```sql
-- Ganti TEACHER_ID_ANDA dengan teacher_id dari user yang login
UPDATE cbt_question_banks
SET teacher_id = TEACHER_ID_ANDA
WHERE id = 11;
```

**Option 2: Aktifkan Bank Soal**
```sql
-- Aktifkan bank soal agar lebih mudah ditemukan
UPDATE cbt_question_banks
SET is_active = 1
WHERE id = 11;
```

**Option 3: Login sebagai Admin**
- Admin bisa melihat semua bank soal
- Kemudian edit bank soal dan assign ke guru yang benar

## Informasi dari Log

Dari log yang Anda berikan:
```
INFO - 2026-04-07 15:01:24 --> Restore success: Bank ID=11, Code=TKA6-BINA_RESTORED_4, Questions=25
```

Bank soal berhasil dibuat dengan:
- **ID**: 11
- **Code**: TKA6-BINA_RESTORED_4
- **Questions**: 25 soal
- **is_active**: 0 (non-aktif)
- **teacher_id**: Sesuai dengan user yang restore

## Checklist

- [ ] Cek teacher_id di database cocok dengan user yang login
- [ ] Hard refresh browser (Ctrl + F5)
- [ ] Logout dan login kembali
- [ ] Cek dengan akun Admin
- [ ] Cek log aplikasi untuk error
- [ ] Aktifkan bank soal (is_active = 1)

## Jika Masih Tidak Muncul

Jalankan query ini dan kirimkan hasilnya:

```sql
-- Query lengkap untuk debugging
SELECT 
    'Bank Soal' as info,
    b.id,
    b.code,
    b.teacher_id,
    b.is_active,
    t.name as teacher_name
FROM cbt_question_banks b
LEFT JOIN teachers t ON t.id = b.teacher_id
WHERE b.id = 11

UNION ALL

SELECT 
    'User Login' as info,
    u.id,
    u.username,
    u.related_id as teacher_id,
    u.role_id as is_active,
    t.name as teacher_name
FROM users u
LEFT JOIN teachers t ON t.id = u.related_id
WHERE u.username = 'USERNAME_ANDA';  -- GANTI dengan username yang login
```

Hasil query ini akan menunjukkan apakah teacher_id cocok atau tidak.
