# Troubleshooting: "The action you requested is not allowed"

## Error Description

Error message: **"The action you requested is not allowed"**

This is a CSRF (Cross-Site Request Forgery) protection error from CodeIgniter 4.

## Root Cause

After implementing security fixes, CSRF configuration was changed:
- Changed from `cookie` to `session` based
- Enabled `tokenRandomize`
- Enabled `regenerate` (initially)

The `regenerate = true` setting causes CSRF token to change after every POST request, which can cause issues with:
- Form submissions
- Back button usage
- Multiple form submissions
- AJAX requests

## Solution Applied

### 1. Disable CSRF Regenerate

**File:** `app/Config/Security.php`

**Changed:**
```php
// ❌ BEFORE - Causes issues
public bool $regenerate = true;

// ✅ AFTER - Fixed
public bool $regenerate = false;
```

**Reason:** While `regenerate = true` is more secure, it causes usability issues. The token still expires after 2 hours, which provides adequate security.

### 2. Clear Browser Cache & Session

After changing CSRF configuration:

```bash
# Clear browser cache
Ctrl + Shift + Delete (Chrome/Firefox)

# Or use incognito/private mode
Ctrl + Shift + N (Chrome)
Ctrl + Shift + P (Firefox)
```

### 3. Clear Application Cache

```bash
# Clear CodeIgniter cache
rm -rf writable/cache/*

# Or via browser
http://localhost:8080/siakad/index.php/clear-cache
```

## Alternative Solutions

### Option 1: Keep Regenerate but Add Exceptions

If you want to keep `regenerate = true` for better security:

**File:** `app/Config/Filters.php`

```php
public array $globals = [
    'before' => [
        'csrf' => [
            'except' => [
                'siswa/cbt/refreshCsrf',
                'siswa/cbt/save-answer',  // Add exceptions for problematic endpoints
                'admin/students/store',
                'admin/teachers/store',
            ]
        ],
    ],
];
```

### Option 2: Use AJAX with Token Refresh

For long-running operations:

```javascript
// Refresh CSRF token periodically
setInterval(function() {
    fetch('/siswa/cbt/refreshCsrf', {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update all CSRF tokens in forms
            document.querySelectorAll('input[name="' + data.csrf_token_name + '"]')
                .forEach(input => {
                    input.value = data.csrf_token_hash;
                });
        }
    });
}, 1800000); // 30 minutes
```

### Option 3: Increase CSRF Expiration

If forms are taking too long to submit:

**File:** `app/Config/Security.php`

```php
// Increase from 2 hours to 4 hours
public int $expires = 14400; // 4 hours
```

## Current Configuration (Recommended)

**File:** `app/Config/Security.php`

```php
public string $csrfProtection = 'session';  // Session-based (secure)
public bool $tokenRandomize = true;         // Randomization (secure)
public int $expires = 7200;                 // 2 hours (balanced)
public bool $regenerate = false;            // Disabled (usability)
public bool $redirect = (ENVIRONMENT === 'production'); // Redirect on error
```

**Balance:** This configuration provides good security while maintaining usability.

## Testing After Fix

### 1. Test Form Submission

```
1. Login to application
2. Navigate to Students page
3. Click "Add Student"
4. Fill form
5. Submit
6. Should work without CSRF error
```

### 2. Test Back Button

```
1. Submit a form
2. Click browser back button
3. Submit form again
4. Should work (with regenerate = false)
```

### 3. Test Multiple Tabs

```
1. Open application in 2 tabs
2. Submit form in tab 1
3. Submit form in tab 2
4. Both should work (with regenerate = false)
```

## Prevention

### For Developers

1. **Always include CSRF token in forms:**
```php
<?= csrf_field() ?>
```

2. **For AJAX requests:**
```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

3. **Add meta tag in layout:**
```html
<meta name="csrf-token" content="<?= csrf_hash() ?>">
```

### For Users

1. Don't use browser back button after form submission
2. Don't open multiple tabs for same form
3. Submit forms within 2 hours of page load
4. Clear cache if experiencing issues

## Error Messages

### "The action you requested is not allowed"
- **Cause:** CSRF token invalid or expired
- **Solution:** Refresh page and try again

### "403 Forbidden"
- **Cause:** CSRF token missing
- **Solution:** Ensure `<?= csrf_field() ?>` in form

### Token expired
- **Cause:** Form open for more than 2 hours
- **Solution:** Refresh page to get new token

## Monitoring

### Check CSRF Logs

**File:** `writable/logs/log-YYYY-MM-DD.php`

Look for:
```
CRITICAL - CSRF token mismatch
ERROR - Invalid CSRF token
```

### Debug Mode

Enable debug mode to see detailed CSRF errors:

**File:** `.env`
```
CI_ENVIRONMENT = development
```

## Summary

✅ **Fixed:** Changed `regenerate = false` in Security.php  
✅ **Impact:** Forms now work without CSRF errors  
✅ **Security:** Still protected with 2-hour token expiration  
✅ **Usability:** Back button and multiple submissions work  

**Status:** RESOLVED  
**Date:** 2026-04-05  
**Solution:** Disabled CSRF regenerate for better usability  

---

**Note:** If you need maximum security with `regenerate = true`, implement AJAX token refresh for long-running operations.
