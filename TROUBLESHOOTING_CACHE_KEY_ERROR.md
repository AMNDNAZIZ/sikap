# Troubleshooting: Cache Key Contains Reserved Characters

## Error Description

Error message: **"Cache key contains reserved characters {}()/\@:"**

This error occurs during login when the rate limiting feature tries to use IP address as cache key.

## Root Cause

The rate limiting implementation uses IP address directly in cache key:

```php
// ❌ BEFORE - Causes error
$ip = $this->request->getIPAddress();
$cacheKey = "login_attempts_{$ip}";
```

**Problem:** IP addresses can contain reserved characters:
- IPv4: Contains dots (`.`) - Example: `192.168.1.1`
- IPv6: Contains colons (`:`) - Example: `::1` or `2001:db8::1`

CodeIgniter cache keys don't allow these characters: `{}()/\@:`

## Solution Applied

### Sanitize IP Address for Cache Key

**File:** `app/Controllers/Auth.php`

**Changed:**
```php
// ✅ AFTER - Fixed
$ip = $this->request->getIPAddress();
// Sanitize IP address for cache key (remove invalid characters)
$sanitizedIp = str_replace([':', '.', '/'], '_', $ip);
$cacheKey = "login_attempts_{$sanitizedIp}";
$lockoutKey = "login_lockout_{$sanitizedIp}";
```

**Examples:**
- IPv4: `192.168.1.1` → `192_168_1_1`
- IPv6: `::1` → `__1`
- IPv6: `2001:db8::1` → `2001_db8__1`

## Impact

### Before Fix
- ❌ Login fails with cache error
- ❌ Rate limiting doesn't work
- ❌ Users cannot login

### After Fix
- ✅ Login works normally
- ✅ Rate limiting works correctly
- ✅ IP-based lockout functions properly
- ✅ Supports both IPv4 and IPv6

## Testing

### Test IPv4 (Localhost)
```
IP: 127.0.0.1
Cache Key: login_attempts_127_0_0_1
Result: ✅ Works
```

### Test IPv6 (Localhost)
```
IP: ::1
Cache Key: login_attempts___1
Result: ✅ Works
```

### Test Public IPv4
```
IP: 192.168.1.100
Cache Key: login_attempts_192_168_1_100
Result: ✅ Works
```

### Test Public IPv6
```
IP: 2001:db8::1
Cache Key: login_attempts_2001_db8__1
Result: ✅ Works
```

## Related Code

### Rate Limiting Implementation

**File:** `app/Controllers/Auth.php`

```php
public function attemptLogin()
{
    // Get and sanitize IP address
    $ip = $this->request->getIPAddress();
    $sanitizedIp = str_replace([':', '.', '/'], '_', $ip);
    
    // Create cache keys
    $cacheKey = "login_attempts_{$sanitizedIp}";
    $lockoutKey = "login_lockout_{$sanitizedIp}";
    
    // Check attempts
    $attempts = cache()->get($cacheKey) ?? 0;
    $lockoutTime = cache()->get($lockoutKey);
    
    // ... rate limiting logic ...
    
    // Save attempts
    cache()->save($cacheKey, $attempts, 900);
    
    // Lock out after 5 attempts
    if ($attempts >= 5) {
        cache()->save($lockoutKey, time() + 900, 900);
    }
}
```

## Cache Key Best Practices

### Valid Characters
- Alphanumeric: `a-z`, `A-Z`, `0-9`
- Underscore: `_`
- Hyphen: `-`

### Invalid Characters (Must Sanitize)
- Colon: `:`
- Dot: `.`
- Slash: `/`
- Backslash: `\`
- At sign: `@`
- Braces: `{}`
- Parentheses: `()`

### Sanitization Function

```php
/**
 * Sanitize string for use as cache key
 * 
 * @param string $key Raw key
 * @return string Sanitized key
 */
function sanitize_cache_key(string $key): string
{
    // Replace invalid characters with underscore
    return str_replace([':', '.', '/', '\\', '@', '{', '}', '(', ')'], '_', $key);
}

// Usage
$cacheKey = sanitize_cache_key("user_{$email}");
```

## Prevention

### For Future Development

1. **Always sanitize user input for cache keys:**
```php
// ❌ BAD
$key = "user_{$email}";

// ✅ GOOD
$sanitizedEmail = str_replace(['@', '.'], '_', $email);
$key = "user_{$sanitizedEmail}";
```

2. **Use hash for complex identifiers:**
```php
// ✅ BETTER
$key = "user_" . md5($email);
```

3. **Create helper function:**
```php
// app/Helpers/cache_helper.php
function make_cache_key(string $prefix, string $identifier): string
{
    $sanitized = str_replace([':', '.', '/', '\\', '@', '{', '}', '(', ')'], '_', $identifier);
    return "{$prefix}_{$sanitized}";
}

// Usage
$key = make_cache_key('login_attempts', $ip);
```

## Error Messages

### "Cache key contains reserved characters"
- **Cause:** Cache key contains invalid characters
- **Solution:** Sanitize the key before using

### "Invalid cache key"
- **Cause:** Cache key is empty or too long
- **Solution:** Validate key length and content

## Monitoring

### Check Cache Logs

**File:** `writable/logs/log-YYYY-MM-DD.php`

Look for:
```
ERROR - Cache key contains reserved characters
CRITICAL - Invalid cache key
```

### Debug Cache Keys

Add logging to see actual cache keys:
```php
log_message('debug', "Cache key: {$cacheKey}");
```

## Summary

✅ **Fixed:** Sanitized IP address for cache key  
✅ **Impact:** Login now works without cache errors  
✅ **Security:** Rate limiting still functions correctly  
✅ **Compatibility:** Supports both IPv4 and IPv6  

**Status:** RESOLVED  
**Date:** 2026-04-05  
**Solution:** Replace invalid characters with underscore  

---

**Note:** This fix maintains rate limiting functionality while ensuring cache keys are valid for all IP address formats.
