# Perbaikan Form Ganti Password Admin di Halaman Profile

## Masalah yang Ditemukan

Pada halaman `/profile` untuk user admin (non-teacher), form ganti password tidak memiliki field untuk memasukkan password lama (`current_password`), tetapi controller `Profile::updatePassword()` memerlukan field tersebut untuk validasi.

### Dampak
- Admin tidak bisa mengganti password melalui halaman profile
- Error terjadi karena field `current_password` tidak dikirim dari form
- Validasi di controller gagal karena `$currentPassword` bernilai null

## Analisis

### Controller: Profile::updatePassword()
```php
public function updatePassword()
{
    $user = session()->get('user');
    $id = $user['id'];

    $currentPassword = $this->request->getPost('current_password'); // ← Memerlukan field ini
    $newPassword = $this->request->getPost('new_password');
    $confirmPassword = $this->request->getPost('confirm_password');

    // Get user data
    $userData = $this->userModel->find($id);

    // Verify current password
    if (!password_verify($currentPassword, $userData['password'])) { // ← Validasi password lama
        return redirect()->back()->with('error', 'Password lama tidak sesuai.');
    }
    // ... kode lainnya
}
```

### View Sebelum Perbaikan: profile/index.php
Form hanya memiliki 2 field:
- `new_password` (Password Baru)
- `confirm_password` (Konfirmasi Password Baru)

Field `current_password` tidak ada, menyebabkan validasi gagal.

## Solusi

Menambahkan field `current_password` di form `app/Views/profile/index.php`:

```php
<div class="mb-3">
    <label class="form-label">Password Lama</label>
    <div class="input-group">
        <input type="password" name="current_password" id="current_password" class="form-control" required>
        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('current_password', this)">
            <i class="bi bi-eye"></i>
        </button>
    </div>
</div>
```

## Perbandingan dengan View Lain

### profile/change_password.php
View ini sudah benar dan memiliki field `current_password`. View ini digunakan untuk halaman khusus ganti password (`/profile/change-password`).

### profile/teacher_edit.php
View untuk guru memiliki form password di tab "Akun Login" yang tidak memerlukan password lama karena menggunakan method `updateTeacher()` yang berbeda dan tidak memvalidasi password lama.

## File yang Diubah

1. `app/Views/profile/index.php`
   - Menambahkan field `current_password` dengan toggle visibility
   - Field ditempatkan sebelum `new_password` untuk urutan yang logis

## Testing

Skenario yang perlu ditest:
1. ✅ Admin login dan akses `/profile`
2. ✅ Isi password lama yang salah → Tampil error "Password lama tidak sesuai"
3. ✅ Isi password lama yang benar, password baru tidak cocok → Tampil error "Password baru dan konfirmasi tidak cocok"
4. ✅ Isi semua field dengan benar → Password berhasil diubah
5. ✅ Toggle visibility password berfungsi untuk semua field

## Catatan Keamanan

- Form tetap memerlukan password lama untuk keamanan
- Ini mencegah orang lain mengubah password jika admin meninggalkan komputer dalam keadaan login
- Konsisten dengan best practice untuk form ganti password
- Password di-hash menggunakan `PASSWORD_BCRYPT` sebelum disimpan

## Rekomendasi Tambahan

Untuk konsistensi, pertimbangkan untuk:
1. Menambahkan validasi strength password di client-side
2. Menambahkan indikator kekuatan password
3. Menambahkan konfirmasi sebelum submit
4. Menambahkan rate limiting untuk mencegah brute force
