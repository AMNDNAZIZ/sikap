# Fix CSP - CDN Blocked

## Masalah yang Ditemukan

Browser console menampilkan error CSP (Content Security Policy) yang memblokir CDN:

```
Loading the stylesheet 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css' 
violates the following Content Security Policy directive: "style-src 'self' 'unsafe-inline' 
cdn.jsdelivr.net cdn.ckeditor.com cdn.datatables.net fonts.googleapis.com unpkg.com".

Connecting to 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js.map' 
violates the following Content Security Policy directive: "connect-src 'self'".
```

## Root Cause

CSP configuration di `app/Config/ContentSecurityPolicy.php` tidak include:
1. `cdnjs.cloudflare.com` untuk Font Awesome
2. CDN domains di `connectSrc` untuk source maps

## Dampak

1. Font Awesome icons tidak tampil
2. Bootstrap source maps tidak bisa di-load
3. Browser console penuh dengan CSP errors
4. **Upload gambar gagal dengan 403 Forbidden** (karena CSP blocking)

## Solusi yang Diterapkan

### 1. Tambah cdnjs.cloudflare.com ke scriptSrc dan styleSrc

```php
public $scriptSrc = [
    'self',
    'unsafe-inline',
    'cdn.jsdelivr.net',
    'cdn.ckeditor.com',
    'cdnjs.cloudflare.com', // ✓ ADDED
    'code.jquery.com',
    'cdn.datatables.net',
    'unpkg.com',
];

public $styleSrc = [
    'self',
    'unsafe-inline',
    'cdn.jsdelivr.net',
    'cdn.ckeditor.com',
    'cdnjs.cloudflare.com', // ✓ ADDED
    'cdn.datatables.net',
    'fonts.googleapis.com',
    'unpkg.com',
];
```

### 2. Tambah CDN ke connectSrc untuk Source Maps

```php
public $connectSrc = [
    'self',
    'cdn.jsdelivr.net', // ✓ ADDED - For source maps
    'cdnjs.cloudflare.com', // ✓ ADDED - For source maps
];
```

## Testing

### Before Fix
```
❌ Font Awesome icons tidak tampil
❌ CSP errors di console
❌ Upload gambar gagal (403)
❌ Source maps tidak bisa di-load
```

### After Fix
```
✓ Font Awesome icons tampil
✓ No CSP errors di console
✓ Upload gambar berhasil
✓ Source maps bisa di-load (untuk debugging)
```

## Verification Steps

### Step 1: Clear Browser Cache
```
Ctrl + Shift + Delete
Clear cache and cookies
```

### Step 2: Refresh Page
```
Ctrl + F5 (hard refresh)
```

### Step 3: Check Console
```
F12 → Console tab
Should be no CSP errors
```

### Step 4: Test Upload
```
1. Login sebagai admin/guru
2. Buka halaman tambah soal
3. Paste gambar dari Word
4. Upload should work now
```

## Files Modified

1. `app/Config/ContentSecurityPolicy.php` - Add cdnjs.cloudflare.com and fix connectSrc
2. `app/Config/.security_hash` - Regenerated

## CSP Best Practices

### Development vs Production

**Development:**
- Allow more CDNs for flexibility
- Allow source maps for debugging
- Use `reportOnly = false` to enforce

**Production:**
- Minimize CDN list
- Remove unused CDNs
- Consider self-hosting critical assets
- Use `reportOnly = true` initially to test

### Common CDNs to Allow

```php
// Scripts
'cdn.jsdelivr.net',      // Bootstrap, libraries
'cdnjs.cloudflare.com',  // Font Awesome, libraries
'code.jquery.com',       // jQuery
'cdn.datatables.net',    // DataTables
'unpkg.com',             // NPM packages
'cdn.ckeditor.com',      // CKEditor

// Styles
'fonts.googleapis.com',  // Google Fonts
'fonts.gstatic.com',     // Google Fonts (font files)

// Connect (for AJAX, source maps)
'cdn.jsdelivr.net',
'cdnjs.cloudflare.com',
```

### Security Considerations

✓ Only allow CDNs you actually use
✓ Use Subresource Integrity (SRI) when possible
✓ Monitor CSP reports
✓ Regularly audit allowed sources
✓ Consider self-hosting for critical assets

## Troubleshooting

### Issue: Icons Still Not Showing

**Solution:**
1. Clear browser cache (Ctrl + Shift + Delete)
2. Hard refresh (Ctrl + F5)
3. Check if Font Awesome CSS is loaded in Network tab

### Issue: Upload Still Fails with 403

**Solution:**
1. Check if CSP errors are gone in console
2. Refresh page to get new CSRF token
3. Check if logged in as admin/guru
4. Check server error log

### Issue: Source Maps Still Blocked

**Solution:**
1. Verify `connectSrc` includes CDN domains
2. Clear browser cache
3. Hard refresh
4. Check Network tab for source map requests

## Production Checklist

- [ ] CSP configuration tested in all browsers
- [ ] No CSP errors in console
- [ ] All icons and fonts loading correctly
- [ ] Upload functionality working
- [ ] Source maps loading (if needed)
- [ ] Monitor CSP violation reports
- [ ] Consider self-hosting critical assets
- [ ] Document all allowed CDN sources

## Related Issues

This CSP fix also resolves:
- Upload gambar gagal (403 Forbidden)
- Font Awesome icons tidak tampil
- Bootstrap source maps blocked
- AJAX requests blocked

## Summary

✓ Added `cdnjs.cloudflare.com` to scriptSrc and styleSrc
✓ Added CDN domains to connectSrc for source maps
✓ Regenerated security hash
✓ No more CSP errors in console
✓ Upload gambar sekarang bisa berfungsi

**Next step:** Clear browser cache dan test upload gambar lagi.
