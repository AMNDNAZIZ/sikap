# Checklist: CSRF Token di CMS Forms

## 📋 Overview

Semua form dengan method POST/PUT/DELETE harus memiliki CSRF token untuk security.

Error jika tidak ada: **"The action you requested is not allowed"**

## ✅ Status Forms

### CMS Sliders
- [x] **FIXED** - `app/Views/admin/cms/sliders/form.php`
- [x] CSRF token added: `<?= csrf_field() ?>`
- [x] Tested and working

### CMS Links
- [x] **FIXED** - `app/Views/admin/cms/links/create.php`
- [x] **FIXED** - `app/Views/admin/cms/links/edit.php`
- [x] **FIXED** - `app/Views/admin/cms/links/index.php` (delete form)
- [x] CSRF token added to all forms
- [ ] Test form submission

### CMS Facilities
- [x] **FIXED** - `app/Views/admin/cms/facilities/form.php`
- [x] CSRF token added: `<?= csrf_field() ?>`
- [ ] Test form submission

### CMS Articles
- [x] **FIXED** - `app/Views/admin/cms/articles/form.php`
- [x] CSRF token added: `<?= csrf_field() ?>`
- [ ] Test form submission

### CMS Activities
- [x] **FIXED** - `app/Views/admin/cms/activities/form.php`
- [x] CSRF token added: `<?= csrf_field() ?>`
- [ ] Test form submission

## 🔍 Cara Check

### Step 1: Buka File View
```bash
# Example untuk Links
app/Views/admin/cms/links/form.php
```

### Step 2: Cari Form Tag
```php
<form method="post" ...>
```

### Step 3: Check CSRF Token
Harus ada salah satu dari:
```php
<?= csrf_field() ?>
```
atau
```php
<input type="hidden" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
```

### Step 4: Jika Tidak Ada, Tambahkan
```php
<form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>  <!-- ADD THIS -->
    <!-- form fields -->
</form>
```

## 🛠️ Quick Fix Script

Untuk check semua CMS forms sekaligus:

```bash
# Search for forms without CSRF token
grep -r "<form" app/Views/admin/cms/ | grep -v "csrf_field"
```

## 📝 Template Fix

Untuk setiap form yang tidak punya CSRF token:

**Before:**
```php
<form action="..." method="post">
    <div class="form-group">
```

**After:**
```php
<form action="..." method="post">
    <?= csrf_field() ?>
    <div class="form-group">
```

## 🧪 Testing Checklist

Untuk setiap form yang sudah di-fix:

- [ ] Refresh halaman form (Ctrl+F5)
- [ ] View source, verify CSRF token exists
- [ ] Submit form dengan data valid
- [ ] Verify tidak ada error "action not allowed"
- [ ] Verify data tersimpan di database
- [ ] Test update form (jika ada)
- [ ] Test delete action (jika ada)

## 🔐 Security Notes

### Why CSRF Token Important?

1. **Prevent CSRF Attacks**
   - Attacker tidak bisa submit form dari website lain
   - Token hanya valid untuk session yang generate form

2. **Validate Request Origin**
   - Server bisa verify request berasal dari form yang legitimate
   - Bukan dari script atau bot

3. **Session Security**
   - Token tied to user session
   - Expired setelah logout atau timeout

### CSRF Token Lifecycle

1. **Generate**: Saat render form
2. **Embed**: Di form sebagai hidden field
3. **Submit**: Dikirim bersama form data
4. **Validate**: Server compare dengan session
5. **Regenerate**: Setelah submit (jika config regenerate = true)

## 📊 Priority

### High Priority (User-facing forms)
1. ✅ Sliders (FIXED)
2. Links
3. Articles
4. Activities
5. Facilities

### Medium Priority (Admin forms)
- User management
- School settings
- Academic year
- Teachers
- Students

### Low Priority (Rarely used)
- Changelogs
- Holidays
- Subjects

## 🚀 Bulk Fix Strategy

### Option 1: Manual Fix (Recommended)
- Check setiap form satu per satu
- Test setelah fix
- Dokumentasi changes

### Option 2: Search & Replace
```bash
# Find all forms
find app/Views -name "*.php" -exec grep -l "<form" {} \;

# For each file, add csrf_field() after <form> tag
# (Manual review required)
```

### Option 3: Create Base Form Component
```php
// app/Views/components/form_start.php
<form <?= $attributes ?>>
    <?= csrf_field() ?>
```

Then use:
```php
<?= view('components/form_start', ['attributes' => 'method="post"']) ?>
```

## 📚 Resources

- **CodeIgniter 4 CSRF Docs**: https://codeigniter.com/user_guide/libraries/security.html
- **OWASP CSRF Guide**: https://owasp.org/www-community/attacks/csrf
- **Our Documentation**: SOLUSI_CSRF_ERROR.md

## ✅ Completion Checklist

- [x] Sliders form fixed
- [x] Links forms fixed (create, edit, delete)
- [x] Facilities form fixed
- [x] Articles form fixed
- [x] Activities form fixed
- [ ] All forms tested
- [ ] Documentation updated
- [ ] Team notified

## 🎯 Next Steps

1. **Check Links Form**
   ```bash
   app/Views/admin/cms/links/form.php
   ```

2. **Check Facilities Form**
   ```bash
   app/Views/admin/cms/facilities/form.php
   ```

3. **Check Articles Form**
   ```bash
   app/Views/admin/cms/articles/form.php
   ```

4. **Check Activities Form**
   ```bash
   app/Views/admin/cms/activities/form.php
   ```

5. **Test All Forms**
   - Create new record
   - Update existing record
   - Delete record
   - Verify no CSRF errors

## 📞 Support

Jika menemukan form lain yang error "action not allowed":

1. Check apakah form punya CSRF token
2. Tambahkan `<?= csrf_field() ?>` setelah `<form>` tag
3. Refresh halaman dan test ulang
4. Jika masih error, check SOLUSI_CSRF_ERROR.md

---

**Last Updated:** 2026-02-25
**Status:** ✅ ALL CMS FORMS FIXED
**Next Action:** Testing all forms
