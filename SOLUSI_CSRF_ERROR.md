# Solusi: CSRF Error "The action you requested is not allowed"

## 🐛 Masalah

Error muncul saat submit form slider:
```
Error! The action you requested is not allowed.
```

## 🔍 Penyebab

Error ini disebabkan oleh **CSRF (Cross-Site Request Forgery) Protection** di CodeIgniter 4.

Form tidak memiliki **CSRF token** yang diperlukan untuk validasi keamanan.

## ✅ Solusi

### Fix yang Sudah Diterapkan

**File:** `app/Views/admin/cms/sliders/form.php`

**Sebelum:**
```php
<form action="..." method="post" enctype="multipart/form-data">
    <div class="row">
    ...
```

**Sesudah:**
```php
<form action="..." method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="row">
    ...
```

### Penjelasan

`<?= csrf_field() ?>` akan generate hidden input field:
```html
<input type="hidden" name="csrf_test_name" value="random_token_here">
```

Token ini divalidasi oleh CodeIgniter untuk memastikan request berasal dari form yang legitimate.

## 🔐 Tentang CSRF Protection

### Apa itu CSRF?

CSRF (Cross-Site Request Forgery) adalah serangan dimana attacker membuat user melakukan action yang tidak diinginkan di website yang sudah ter-authenticate.

### Contoh Serangan CSRF

1. User login ke website A (bank.com)
2. User membuka website jahat B (evil.com)
3. Website B memiliki form tersembunyi yang submit ke bank.com
4. Form ter-submit otomatis menggunakan session user
5. Bank transfer terjadi tanpa sepengetahuan user

### Cara Kerja CSRF Protection

1. Server generate random token saat render form
2. Token disimpan di session dan di-embed di form
3. Saat form di-submit, server compare token dari form dengan token di session
4. Jika match → Request valid
5. Jika tidak match → Request ditolak (error "action not allowed")

## 📋 Checklist: Form yang Memerlukan CSRF Token

Semua form dengan method POST/PUT/DELETE harus memiliki CSRF token:

- [x] Sliders create/update form
- [ ] Links create/update form
- [ ] Facilities create/update form
- [ ] Articles create/update form
- [ ] Activities create/update form

## 🔧 Cara Menambahkan CSRF Token

### Method 1: csrf_field() Helper (Recommended)
```php
<form method="post">
    <?= csrf_field() ?>
    <!-- form fields -->
</form>
```

### Method 2: Manual Hidden Input
```php
<form method="post">
    <input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
    <!-- form fields -->
</form>
```

### Method 3: AJAX Request
```javascript
fetch('/api/endpoint', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
    },
    body: JSON.stringify(data)
});
```

## ⚙️ CSRF Configuration

**File:** `app/Config/Security.php`

```php
public string $csrfProtection = 'session'; // or 'cookie'
public string $tokenRandomize = false;
public string $tokenName = 'csrf_test_name';
public string $headerName = 'X-CSRF-TOKEN';
public string $cookieName = 'csrf_cookie_name';
public int $expires = 7200; // 2 hours
public bool $regenerate = true;
public string $redirect = false;
public string $samesite = 'Lax';
```

### Key Settings

- **csrfProtection**: 'session' atau 'cookie'
- **tokenRandomize**: Regenerate token setiap request (lebih aman tapi bisa error di multi-tab)
- **regenerate**: Regenerate token setelah submit (recommended: true)
- **expires**: Token expiry time (default: 2 hours)

## 🐛 Common CSRF Issues

### Issue 1: Token Expired
**Error:** "The action you requested is not allowed"
**Penyebab:** Token expired (default: 2 hours)
**Solusi:** Refresh halaman untuk generate token baru

### Issue 2: Multi-Tab Issue
**Error:** Token invalid saat submit dari tab lain
**Penyebab:** `tokenRandomize = true` regenerate token setiap request
**Solusi:** Set `tokenRandomize = false` di config

### Issue 3: AJAX Request Gagal
**Error:** CSRF token missing
**Solusi:** Include token di header atau body:
```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### Issue 4: Form di-cache
**Error:** Token invalid setelah back button
**Penyebab:** Browser cache form dengan token lama
**Solusi:** Disable cache untuk form pages:
```php
$response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
```

## 🧪 Testing CSRF Protection

### Test 1: Valid Token
```bash
# Should succeed
curl -X POST http://localhost/siakad/admin/cms/sliders/store \
  -F "csrf_test_name=valid_token" \
  -F "image=@test.jpg" \
  -F "title=Test"
```

### Test 2: Missing Token
```bash
# Should fail with "action not allowed"
curl -X POST http://localhost/siakad/admin/cms/sliders/store \
  -F "image=@test.jpg" \
  -F "title=Test"
```

### Test 3: Invalid Token
```bash
# Should fail with "action not allowed"
curl -X POST http://localhost/siakad/admin/cms/sliders/store \
  -F "csrf_test_name=invalid_token" \
  -F "image=@test.jpg" \
  -F "title=Test"
```

## 🔍 Debugging CSRF Issues

### Step 1: Check Token in Form
```php
// View source halaman, cari:
<input type="hidden" name="csrf_test_name" value="...">
```

### Step 2: Check Token in Session
```php
// Add di controller untuk debug:
log_message('debug', 'Session CSRF: ' . session()->get('csrf_test_name'));
log_message('debug', 'POST CSRF: ' . $this->request->getPost('csrf_test_name'));
```

### Step 3: Check CSRF Config
```php
// app/Config/Security.php
var_dump($config->csrfProtection);
var_dump($config->tokenName);
```

### Step 4: Disable CSRF (Testing Only!)
```php
// app/Config/Filters.php
public array $globals = [
    'before' => [
        // 'csrf', // Comment out untuk disable
    ],
];
```

**⚠️ WARNING:** Jangan disable CSRF di production!

## 📚 Best Practices

1. **Always Use CSRF Protection**
   - Jangan disable di production
   - Hanya disable untuk testing/debugging

2. **Use csrf_field() Helper**
   - Lebih simple dan maintainable
   - Auto-update jika config berubah

3. **Handle Token Expiry**
   - Show user-friendly message
   - Auto-refresh form jika token expired

4. **AJAX Requests**
   - Include token di header atau body
   - Use meta tag untuk easy access:
   ```html
   <meta name="csrf-token" content="<?= csrf_hash() ?>">
   ```

5. **Multi-Tab Support**
   - Set `tokenRandomize = false`
   - Or implement custom token management

## 🎯 Summary

**Masalah:** Form slider error "action not allowed"
**Penyebab:** Missing CSRF token
**Solusi:** Tambah `<?= csrf_field() ?>` di form

**Status:** ✅ FIXED

Form slider sekarang sudah memiliki CSRF token dan siap digunakan!
