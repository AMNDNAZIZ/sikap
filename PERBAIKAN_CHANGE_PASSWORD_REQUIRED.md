# Perbaikan Halaman Ganti Password Wajib (Change Password Required)

## Masalah yang Ditemukan

Halaman ganti password wajib (`/auth/change-password-required`) yang muncul saat pertama kali login dengan password default selalu gagal memproses perubahan password.

### Gejala
- Form submit tidak berhasil
- Tidak ada response atau error yang jelas
- User tidak bisa mengganti password dan terjebak di halaman tersebut

## Analisis Masalah

### 1. CSRF Token Tidak Dikirim
Form tidak memiliki CSRF token field, padahal CodeIgniter 4 memerlukan CSRF token untuk semua POST request.

```php
// SEBELUM (SALAH)
<form id="changePasswordForm">
    <div class="mb-3">
        <label>Password Lama</label>
        ...
    </div>
</form>
```

### 2. Error Handling JavaScript Tidak Benar
Kode JavaScript untuk menampilkan error field tidak benar karena struktur DOM tidak sesuai:

```javascript
// SEBELUM (SALAH)
const input = document.getElementById(field);
if (input) {
    input.classList.add('is-invalid');
    input.nextElementSibling.textContent = message; // ← Salah! nextElementSibling adalah span.input-group-text
}
```

Struktur HTML:
```html
<div class="input-group">
    <input id="current_password" />  ← input
    <span class="input-group-text">  ← nextElementSibling (bukan invalid-feedback!)
        <i class="bi bi-eye"></i>
    </span>
</div>
<div class="invalid-feedback"></div>  ← Ini yang seharusnya diisi
```

### 3. CSS untuk Invalid Feedback Tidak Ada
Class `.invalid-feedback` tidak memiliki styling untuk menampilkan error message.

## Solusi yang Diterapkan

### 1. Menambahkan CSRF Token
```php
<form id="changePasswordForm">
    <?= csrf_field() ?>
    <div class="mb-3">
        ...
    </div>
</form>
```

CSRF token akan otomatis diambil oleh `FormData` dan dikirim ke server.

### 2. Memperbaiki Error Handling JavaScript
```javascript
if (data.errors) {
    for (const [field, message] of Object.entries(data.errors)) {
        const input = document.getElementById(field);
        if (input) {
            input.classList.add('is-invalid');
            // Find the invalid-feedback div after the input-group
            const inputGroup = input.closest('.input-group');
            const feedbackDiv = inputGroup.nextElementSibling;
            if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                feedbackDiv.textContent = message;
                feedbackDiv.style.display = 'block';
            }
        }
    }
}
```

Logika baru:
1. Cari input element
2. Cari parent `.input-group`
3. Ambil `nextElementSibling` dari input-group (bukan dari input)
4. Verifikasi bahwa element tersebut adalah `.invalid-feedback`
5. Set text dan tampilkan

### 3. Memperbaiki Clear Errors
```javascript
// Clear previous errors
document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
document.querySelectorAll('.invalid-feedback').forEach(el => {
    el.textContent = '';
    el.style.display = 'none';  // ← Tambahan untuk menyembunyikan
});
```

### 4. Menambahkan CSS untuk Invalid Feedback
```css
.invalid-feedback {
    display: none;
    color: #dc3545;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.form-control.is-invalid {
    border-color: #dc3545;
}

.form-control.is-invalid:focus {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}
```

## Flow Proses yang Benar

1. User login dengan password default
2. System detect `must_change_password = 1`
3. Redirect ke `/auth/change-password-required`
4. User isi form:
   - Password lama (password default)
   - Password baru
   - Konfirmasi password baru
5. Submit form dengan CSRF token
6. Server validasi:
   - CSRF token valid ✓
   - Password lama cocok ✓
   - Password baru minimal 6 karakter ✓
   - Password baru sama dengan konfirmasi ✓
   - Password baru berbeda dari password lama ✓
7. Update database:
   - Hash password baru
   - Set `must_change_password = 0`
   - Set `password_changed_at`
8. Update session
9. Return JSON success
10. Redirect ke dashboard

## File yang Diubah

1. `app/Views/auth/change_password_required.php`
   - Tambah `<?= csrf_field() ?>`
   - Perbaiki error handling JavaScript
   - Perbaiki clear errors
   - Tambah CSS untuk invalid feedback

## Testing

Skenario yang perlu ditest:
1. ✅ Login dengan password default → Redirect ke change password
2. ✅ Submit tanpa isi form → Tampil error validasi
3. ✅ Password lama salah → Tampil error "Password lama tidak sesuai"
4. ✅ Password baru kurang dari 6 karakter → Tampil error validasi
5. ✅ Password baru tidak cocok dengan konfirmasi → Tampil error "matches"
6. ✅ Password baru sama dengan password lama → Tampil error
7. ✅ Semua validasi benar → Password berhasil diubah, redirect ke dashboard
8. ✅ Setelah ganti password, `must_change_password = 0` di database dan session

## Catatan Keamanan

- CSRF protection aktif untuk mencegah CSRF attack
- Password di-hash menggunakan `PASSWORD_DEFAULT` (bcrypt)
- Password lama diverifikasi sebelum update
- Password baru tidak boleh sama dengan password lama
- Session diupdate setelah password berhasil diubah
- User tidak bisa kembali ke halaman ini setelah password diubah (history.pushState)

## Rekomendasi Tambahan

1. Tambahkan rate limiting untuk mencegah brute force
2. Tambahkan password strength meter
3. Log aktivitas perubahan password
4. Kirim notifikasi email setelah password berhasil diubah
5. Tambahkan opsi "Lupa Password Lama" untuk user yang benar-benar lupa
