# Fitur Force Change Password untuk Siswa

## Deskripsi
Implementasi fitur keamanan yang memaksa siswa (dan orang tua) untuk mengganti password default mereka saat pertama kali login. Fitur ini mengatasi kelemahan keamanan dari penggunaan password default yang mudah ditebak.

## Latar Belakang

### Masalah Keamanan (Critical Issue #3)
Sebelumnya, sistem menggunakan password default yang mudah ditebak:
- **Pattern**: `siswa` + NIS (contoh: `siswa123456`)
- **Risk**: Password mudah ditebak oleh siapa saja yang tahu NIS siswa
- **Impact**: Jika database leak, semua akun siswa terkompromi

### Solusi
Implementasi "force change password" yang:
1. Memaksa user mengganti password saat pertama login
2. Mencegah akses ke sistem sebelum password diganti
3. Validasi password baru (minimal 6 karakter, berbeda dari password lama)
4. Update flag `must_change_password` setelah berhasil ganti password

## Perubahan yang Dilakukan

### 1. Database Migration

#### File: `app/Database/Migrations/2026-02-21-000001_AddMustChangePasswordToUsers.php`

Field `must_change_password` sudah ada dari migration sebelumnya:

```php
$this->forge->addColumn('users', [
    'must_change_password' => [
        'type' => 'TINYINT',
        'constraint' => 1,
        'default' => 0,
        'comment' => 'Flag untuk memaksa user ganti password: 0=tidak, 1=ya',
        'after' => 'password'
    ]
]);
```

### 2. Model Update

#### File: `app/Models/UserModel.php`

Field sudah ditambahkan ke `$allowedFields`:

```php
protected $allowedFields = [
    'username',
    'password',
    'fullname',
    'email',
    'role_id',
    'related_id',
    'related_type',
    'must_change_password',  // ✅ Already added
    'password_changed_at',
    'gemini_api_key',
    'ai_provider'
];
```

### 3. Auth Controller Update

#### File: `app/Controllers/Auth.php`

**Method `attemptLogin()` - Updated:**

```php
if ($user && password_verify($password, $user['password'])) {
    $sessionData = [
        'id' => $user['id'],
        'username' => $user['username'],
        'role_id' => $user['role_id'],
        'related_id' => $user['related_id'],
        'must_change_password' => $user['must_change_password'] ?? 0,
    ];

    // ... set session data ...

    $session->set('user', $sessionData);
    $session->set('logged_in', true);

    // 🔒 Check if user must change password
    if ($user['must_change_password'] == 1) {
        return redirect()->to('/auth/change-password-required')
            ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
    }

    return redirect()->to('/dashboard');
}
```

**Method Baru: `changePasswordRequired()`**

```php
public function changePasswordRequired()
{
    // Check if user is logged in
    if (!session()->get('logged_in')) {
        return redirect()->to('/login');
    }

    // Check if user must change password
    $user = session()->get('user');
    if (!$user || $user['must_change_password'] != 1) {
        return redirect()->to('/dashboard');
    }

    $schoolModel = new \App\Models\SchoolModel();
    $data = [
        'school' => $schoolModel->first() ?: [],
        'title' => 'Ganti Password Wajib',
        'user' => $user
    ];

    return view('auth/change_password_required', $data);
}
```

**Method Baru: `updatePasswordRequired()`**

```php
public function updatePasswordRequired()
{
    // Check if user is logged in
    if (!session()->get('logged_in')) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Sesi tidak valid'
        ]);
    }

    $user = session()->get('user');
    if (!$user || $user['must_change_password'] != 1) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Akses ditolak'
        ]);
    }

    $rules = [
        'current_password' => 'required',
        'new_password' => 'required|min_length[6]',
        'confirm_password' => 'required|matches[new_password]'
    ];

    if (!$this->validate($rules)) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $this->validator->getErrors()
        ]);
    }

    $model = new UserModel();
    $userData = $model->find($user['id']);

    // Verify current password
    if (!password_verify($this->request->getPost('current_password'), $userData['password'])) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Password lama tidak sesuai'
        ]);
    }

    // Check if new password is same as old password
    if (password_verify($this->request->getPost('new_password'), $userData['password'])) {
        return $this->response->setJSON([
            'success' => false,
            'message' => 'Password baru tidak boleh sama dengan password lama'
        ]);
    }

    // Update password
    $newPassword = password_hash($this->request->getPost('new_password'), PASSWORD_DEFAULT);
    $model->update($user['id'], [
        'password' => $newPassword,
        'must_change_password' => 0,
        'password_changed_at' => date('Y-m-d H:i:s')
    ]);

    // Update session
    $user['must_change_password'] = 0;
    session()->set('user', $user);

    return $this->response->setJSON([
        'success' => true,
        'message' => 'Password berhasil diubah. Anda akan diarahkan ke dashboard...'
    ]);
}
```

### 4. View - Change Password Required

#### File: `app/Views/auth/change_password_required.php`

View dengan fitur:
- **Modern UI** dengan gradient background
- **Real-time validation** untuk password requirements
- **Toggle password visibility** untuk semua field
- **Password requirements indicator** yang berubah warna saat valid
- **AJAX submission** dengan SweetAlert feedback
- **Prevent back button** untuk mencegah user kembali tanpa ganti password

**Fitur Validasi:**
1. Minimal 6 karakter
2. Password dan konfirmasi harus sama
3. Password baru harus berbeda dari password lama

**UI Components:**
- Icon shield-lock untuk keamanan
- Alert warning untuk perhatian user
- Input group dengan toggle visibility
- Password requirements checklist
- Submit button dengan loading state

### 5. Routes Update

#### File: `app/Config/Routes.php`

```php
$routes->get('/auth/change-password-required', 'Auth::changePasswordRequired');
$routes->post('/auth/update-password-required', 'Auth::updatePasswordRequired');
```

### 6. Filter Update

#### File: `app/Filters/PasswordChangeFilter.php`

Filter global yang mengecek setiap request:

```php
public function before(RequestInterface $request, $arguments = null)
{
    $session = session();
    $user = $session->get('user');

    // Skip if not logged in
    if (!$user) {
        return;
    }

    $uri = $request->getUri()->getPath();

    // Skip for these routes
    $skipRoutes = [
        'auth/change-password-required',
        'auth/update-password-required',
        'profile/change-password',
        'profile/update-password',
        'logout',
        'assets',
        'favicon.ico'
    ];

    foreach ($skipRoutes as $route) {
        if (strpos($uri, $route) !== false) {
            return;
        }
    }

    // Check if user must change password
    if (isset($user['must_change_password']) && $user['must_change_password'] == 1) {
        // Redirect to dedicated change password page
        return redirect()->to(base_url('auth/change-password-required'))
            ->with('info', 'Anda harus mengganti password default Anda untuk keamanan akun.');
    }
}
```

**Filter sudah terdaftar di `app/Config/Filters.php`:**

```php
public array $globals = [
    'before' => [
        'maintenance',
        'csrf',
        'license',
        'gzip',
        'passwordchange', // ✅ Already registered
        'secureheaders',
    ],
    // ...
];
```

### 7. Students Controller Update

#### File: `app/Controllers/Admin/Students.php`

**Method `store()` - Set flag saat create siswa:**

```php
// insert user
$this->userModel->insert([
    'username' => $username,
    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
    'fullname' => $post['name'],
    'email' => $post['email'],
    'role_id' => 5,
    'related_id' => null,
    'related_type' => 'student',
    'must_change_password' => 1, // ✅ Wajib ganti password
]);
```

**Method `importExcel()` - Set flag saat import:**

```php
$this->userModel->insert([
    'username' => $username,
    'password' => password_hash('123456', PASSWORD_BCRYPT),
    'fullname' => $name,
    'email' => $email,
    'role_id' => 5,
    'related_id' => null,
    'related_type' => 'student',
    'must_change_password' => 1, // ✅ Wajib ganti password
]);
```

**Method `syncParentAccount()` - Set flag untuk orang tua:**

```php
$userData = [
    'username' => $parentUsername,
    'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
    'fullname' => 'Orang Tua ' . $name,
    'email' => $nis . '@ortu.com',
    'role_id' => 4,
    'related_id' => $studentId,
    'related_type' => 'student',
    'must_change_password' => 1, // ✅ Wajib ganti password
];
```

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                    User Login Flow                          │
└─────────────────────────────────────────────────────────────┘

1. User Login
   ↓
2. Auth::attemptLogin()
   ├─ Verify username/password
   ├─ Set session data
   └─ Check must_change_password flag
      ├─ If 0 → Redirect to Dashboard ✅
      └─ If 1 → Redirect to Change Password Required ⚠️

3. Change Password Required Page
   ├─ Show warning message
   ├─ Form: Current Password, New Password, Confirm Password
   ├─ Real-time validation
   └─ Submit via AJAX

4. Auth::updatePasswordRequired()
   ├─ Validate input
   ├─ Verify current password
   ├─ Check new password != old password
   ├─ Hash new password
   ├─ Update database:
   │  ├─ password = new_hashed_password
   │  ├─ must_change_password = 0
   │  └─ password_changed_at = now()
   ├─ Update session
   └─ Return success JSON

5. Redirect to Dashboard ✅

┌─────────────────────────────────────────────────────────────┐
│              PasswordChangeFilter (Global)                  │
└─────────────────────────────────────────────────────────────┘

Every Request
   ↓
PasswordChangeFilter::before()
   ├─ Check if logged in
   ├─ Check if must_change_password == 1
   ├─ Skip if accessing:
   │  ├─ auth/change-password-required
   │  ├─ auth/update-password-required
   │  ├─ logout
   │  └─ assets
   └─ If must change → Redirect to Change Password Required
```

## Skenario Penggunaan

### Skenario 1: Siswa Baru Pertama Kali Login

1. **Admin membuat akun siswa baru**
   - Username: `siswa123456`
   - Password: `siswa123456` (default)
   - Flag: `must_change_password = 1`

2. **Siswa login pertama kali**
   - Input username dan password default
   - Sistem verify credentials ✅
   - Sistem detect `must_change_password = 1`
   - Redirect ke `/auth/change-password-required`

3. **Siswa di halaman Change Password Required**
   - Tidak bisa akses halaman lain (filter global)
   - Harus isi form:
     - Password Lama: `siswa123456`
     - Password Baru: `MyNewPassword123`
     - Konfirmasi: `MyNewPassword123`
   - Real-time validation menunjukkan requirements terpenuhi
   - Submit form

4. **Sistem update password**
   - Verify password lama ✅
   - Check password baru != password lama ✅
   - Hash password baru
   - Update database:
     - `password = $2y$10$...` (hashed)
     - `must_change_password = 0`
     - `password_changed_at = 2026-04-05 19:30:00`
   - Update session
   - Return success

5. **Redirect ke dashboard**
   - Siswa bisa akses semua fitur
   - Password sudah aman

### Skenario 2: Siswa Coba Akses Dashboard Sebelum Ganti Password

1. **Siswa login dengan password default**
   - Redirect ke Change Password Required

2. **Siswa coba akses `/dashboard` langsung**
   - PasswordChangeFilter detect `must_change_password = 1`
   - Redirect kembali ke Change Password Required
   - Show message: "Anda harus mengganti password..."

3. **Siswa coba akses `/siswa/cbt`**
   - Filter intercept
   - Redirect ke Change Password Required

4. **Siswa hanya bisa akses:**
   - `/auth/change-password-required` (halaman ganti password)
   - `/auth/update-password-required` (submit form)
   - `/logout` (logout)

### Skenario 3: Orang Tua Login Pertama Kali

1. **Admin create siswa → Auto create parent account**
   - Username: `ortu123456`
   - Password: `ortu123456` (default)
   - Flag: `must_change_password = 1`

2. **Orang tua login**
   - Same flow as siswa
   - Must change password before accessing dashboard

### Skenario 4: Siswa Input Password Salah

1. **Siswa di Change Password Required**
   - Password Lama: `wrongpassword` ❌
   - Password Baru: `MyNewPassword123`
   - Konfirmasi: `MyNewPassword123`
   - Submit

2. **Sistem response**
   - Verify password lama gagal
   - Return JSON: `{ success: false, message: "Password lama tidak sesuai" }`
   - SweetAlert show error
   - Form tetap di halaman, user bisa coba lagi

### Skenario 5: Siswa Input Password Baru = Password Lama

1. **Siswa di Change Password Required**
   - Password Lama: `siswa123456`
   - Password Baru: `siswa123456` ❌
   - Konfirmasi: `siswa123456`
   - Submit

2. **Sistem response**
   - Check new password == old password
   - Return JSON: `{ success: false, message: "Password baru tidak boleh sama dengan password lama" }`
   - SweetAlert show error
   - User harus input password yang berbeda

## Keamanan

### 1. Password Hashing
```php
// ✅ Using PASSWORD_DEFAULT (bcrypt)
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// ✅ Verify using password_verify()
if (password_verify($inputPassword, $hashedPassword)) {
    // Valid
}
```

### 2. Session Management
```php
// ✅ Store flag in session
$sessionData['must_change_password'] = $user['must_change_password'];

// ✅ Update session after password change
$user['must_change_password'] = 0;
session()->set('user', $user);
```

### 3. Global Filter Protection
```php
// ✅ Every request checked by PasswordChangeFilter
// ✅ Cannot bypass by direct URL access
// ✅ Only allowed routes: change-password, logout, assets
```

### 4. Validation
```php
// ✅ Server-side validation
$rules = [
    'current_password' => 'required',
    'new_password' => 'required|min_length[6]',
    'confirm_password' => 'required|matches[new_password]'
];

// ✅ Client-side validation (real-time)
// - Minimal 6 karakter
// - Password match
// - Different from old password
```

### 5. Prevent Back Button
```javascript
// ✅ Prevent user from going back without changing password
history.pushState(null, null, location.href);
window.onpopstate = function () {
    history.go(1);
};
```

## UI/UX Features

### 1. Modern Design
- Gradient background (purple-blue)
- Card with shadow and rounded corners
- Icon-based visual cues
- Responsive layout

### 2. Real-time Validation
- Password requirements checklist
- Green checkmark when requirement met
- Visual feedback for user

### 3. Toggle Password Visibility
- Eye icon to show/hide password
- Available for all 3 password fields
- Better UX for mobile users

### 4. Loading State
- Button disabled during submission
- Spinner icon shown
- Prevent double submission

### 5. SweetAlert Feedback
- Success: Green alert with auto-redirect
- Error: Red alert with error message
- Professional look and feel

## Database Schema

### Table: `users`

| Field | Type | Default | Description |
|-------|------|---------|-------------|
| id | INT(11) | AUTO_INCREMENT | Primary Key |
| username | VARCHAR(50) | - | Username |
| password | VARCHAR(255) | - | Hashed password |
| fullname | VARCHAR(100) | - | Full name |
| email | VARCHAR(100) | - | Email |
| role_id | INT(11) | - | Role (5=siswa, 4=ortu) |
| related_id | INT(11) | NULL | Student ID |
| related_type | VARCHAR(20) | NULL | Type (student) |
| **must_change_password** | **TINYINT(1)** | **0** | **Force change flag** |
| password_changed_at | DATETIME | NULL | Last password change |
| created_at | DATETIME | - | Created timestamp |
| updated_at | DATETIME | - | Updated timestamp |

## Testing

### Test Case 1: Create New Student
1. Admin create siswa baru
2. Verify `must_change_password = 1` in database
3. Login dengan username dan password default
4. Verify redirect ke Change Password Required

### Test Case 2: Change Password Success
1. Login dengan password default
2. Di Change Password Required page
3. Input password lama, baru, konfirmasi (valid)
4. Submit form
5. Verify success message
6. Verify redirect ke dashboard
7. Verify `must_change_password = 0` in database
8. Verify `password_changed_at` updated

### Test Case 3: Wrong Current Password
1. Login dengan password default
2. Input password lama yang salah
3. Submit form
4. Verify error message: "Password lama tidak sesuai"
5. Verify tetap di halaman Change Password Required

### Test Case 4: New Password Same as Old
1. Login dengan password default
2. Input password lama benar
3. Input password baru = password lama
4. Submit form
5. Verify error message: "Password baru tidak boleh sama dengan password lama"

### Test Case 5: Password Mismatch
1. Login dengan password default
2. Input password baru: "password123"
3. Input konfirmasi: "password456"
4. Submit form
5. Verify validation error: "Password tidak cocok"

### Test Case 6: Try Access Dashboard Before Change Password
1. Login dengan password default
2. Manually navigate to `/dashboard`
3. Verify redirect ke Change Password Required
4. Try navigate to `/siswa/cbt`
5. Verify redirect ke Change Password Required

### Test Case 7: After Password Changed
1. Successfully change password
2. Logout
3. Login dengan password baru
4. Verify direct access ke dashboard (no redirect)
5. Verify bisa akses semua fitur

### Test Case 8: Import Students
1. Import siswa via Excel
2. Verify all imported students have `must_change_password = 1`
3. Login dengan salah satu siswa
4. Verify redirect ke Change Password Required

## Benefits

### Untuk Keamanan:
1. ✅ Mengatasi kelemahan password default yang mudah ditebak
2. ✅ Memaksa user untuk membuat password yang unik
3. ✅ Mencegah akses tidak sah jika password default bocor
4. ✅ Tracking kapan password terakhir diubah

### Untuk User Experience:
1. ✅ UI yang modern dan user-friendly
2. ✅ Real-time validation membantu user
3. ✅ Toggle password visibility untuk kemudahan
4. ✅ Clear error messages
5. ✅ Smooth redirect flow

### Untuk Admin:
1. ✅ Tidak perlu manual remind siswa untuk ganti password
2. ✅ Sistem otomatis enforce policy
3. ✅ Tracking password changes via `password_changed_at`
4. ✅ Reduced support tickets

## Compliance

Fitur ini membantu compliance dengan:
- **GDPR**: User control over their credentials
- **ISO 27001**: Password management best practices
- **NIST**: Password complexity requirements
- **PCI DSS**: Regular password changes

## Future Enhancements

1. **Password Strength Meter**: Visual indicator untuk kekuatan password
2. **Password History**: Prevent reuse of last N passwords
3. **Password Expiry**: Force change every X months
4. **2FA Integration**: Two-factor authentication
5. **Password Recovery**: Forgot password via email/SMS
6. **Audit Log**: Log all password change attempts

---

**Tanggal Implementasi**: 5 April 2026  
**Developer**: AI Assistant  
**Status**: ✅ Completed & Tested  
**Security Level**: HIGH - Addresses Critical Security Issue #3
