# Solusi: License Minta Aktivasi di Browser Berbeda

## Masalah

- Chrome: Bisa login dan normal ✓
- Edge: Minta aktivasi ulang meskipun masa berlaku masih lama ✗

## Root Cause

**Hardware signature di database masih NULL**, menyebabkan:
1. Chrome pertama kali akses → Machine ID ter-bind, tapi hardware signature masih NULL
2. Edge akses → Validasi hardware signature gagal karena NULL di database
3. Sistem minta aktivasi ulang

## Penjelasan Teknis

### Hardware Signature vs Machine ID

**Machine ID:**
- Generated dari: hostname, OS, architecture, user, document root
- Bisa berbeda antar browser jika menggunakan data browser-specific

**Hardware Signature:**
- Generated dari: CPU serial, motherboard serial, hard drive serial, MAC address
- **Server-side data** - seharusnya sama untuk semua browser
- Lebih secure dan konsisten

### Validasi di LicenseGuard

```php
// Auto bind saat instalasi pertama
if (empty($license['machine_id'])) {
    $model->update($license['id'], [
        'machine_id' => $machineId,
        'hardware_signature' => $hardwareSignature
    ]);
    return;
}

// Validate both
$machineMatch = ($license['machine_id'] === $machineId);
$hardwareMatch = empty($license['hardware_signature']) || 
                 ($license['hardware_signature'] === $hardwareSignature);

if (!$machineMatch || !$hardwareMatch) {
    self::lock("INVALID MACHINE - License bound to different hardware", true);
}
```

**Masalah:** Jika `hardware_signature` NULL di database, validasi akan pass untuk browser pertama tapi fail untuk browser kedua.

## Solusi

### Opsi 1: Auto-Fix dengan Script (Recommended)

```bash
php fix_license_binding.php
```

Script akan:
1. Clear `machine_id` dan `hardware_signature` di database
2. Saat akses berikutnya, sistem akan auto-bind ulang
3. Semua browser akan menggunakan hardware signature yang sama

### Opsi 2: Manual SQL

```sql
UPDATE app_license SET 
  machine_id = NULL,
  hardware_signature = NULL
WHERE id = 1;
```

Kemudian akses dari browser manapun untuk auto-bind.

### Opsi 3: Clear Browser Cache (Temporary)

Jika tidak ingin reset binding:
1. Buka Edge
2. Clear cache dan cookies (Ctrl + Shift + Delete)
3. Close dan reopen Edge
4. Akses aplikasi lagi

**Note:** Ini hanya temporary fix, masalah bisa muncul lagi.

## Verification

### Check License Status

```bash
php check_license_simple.php
```

Output yang benar:
```
LICENSE INFO:
  Machine ID: SET
  Hardware Sig: SET

✓ Hardware signature is set
  License should work in all browsers
```

### Test di Berbagai Browser

1. Chrome → Should work ✓
2. Edge → Should work ✓
3. Firefox → Should work ✓

## Prevention

Untuk mencegah masalah ini di masa depan:

### 1. Ensure Hardware Signature Always Set

Di `LicenseGuard::validateMachine()`, pastikan hardware signature selalu di-set:

```php
// Auto bind saat instalasi pertama
if (empty($license['machine_id']) || empty($license['hardware_signature'])) {
    $model->update($license['id'], [
        'machine_id' => $machineId,
        'hardware_signature' => $hardwareSignature
    ]);
    log_message('info', 'License bound to machine');
    return;
}
```

### 2. Migration untuk Existing Licenses

Buat migration untuk update existing licenses yang hardware_signature-nya NULL:

```php
// Update all licenses with NULL hardware_signature
$licenses = $model->where('hardware_signature', null)->findAll();
foreach ($licenses as $license) {
    $hardwareSignature = generate_hardware_signature();
    $model->update($license['id'], [
        'hardware_signature' => $hardwareSignature
    ]);
}
```

## Troubleshooting

### Issue: Masih Minta Aktivasi Setelah Fix

**Check:**
1. Hardware signature di database sudah ter-set?
   ```bash
   php check_license_simple.php
   ```

2. Browser cache sudah di-clear?
   - Ctrl + Shift + Delete
   - Clear all data
   - Restart browser

3. Session masih valid?
   - Logout dan login ulang
   - Check `writable/session/` folder

### Issue: Berbeda Signature Antar Browser

**Possible Cause:**
- Browser menggunakan different user agent
- Browser running in different mode (normal vs incognito)

**Solution:**
Hardware signature seharusnya sama karena server-side. Jika berbeda, ada bug di `generate_hardware_signature()`.

### Issue: Local Environment

Jika di local environment (localhost), validasi di-skip:

```php
if (is_local_environment()) {
    return; // Skip validation
}
```

Check dengan:
```bash
php check_license_simple.php
```

## Summary

✓ **Root cause:** Hardware signature NULL di database
✓ **Solution:** Clear binding dan allow auto-bind ulang
✓ **Prevention:** Ensure hardware signature always set saat binding
✓ **Result:** Semua browser di same machine bisa akses

**Next step:** Run `php fix_license_binding.php` dan test di Edge.
