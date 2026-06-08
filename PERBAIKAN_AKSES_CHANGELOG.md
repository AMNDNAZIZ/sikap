# Perbaikan Akses Changelog dari Localhost dengan Port

## 🐛 Masalah

Saat mengakses `http://localhost:8080/siakad/admin/changelogs` muncul error:
```
Access Denied: This feature is only available in the development environment (localhost).
```

## 🔍 Root Cause

Controller `Changelogs` melakukan pengecekan:
```php
if ($_SERVER['HTTP_HOST'] !== 'localhost') {
    // Access Denied
}
```

Masalahnya:
- `$_SERVER['HTTP_HOST']` mengembalikan `localhost:8080` (dengan port)
- Kondisi mengecek strict equality dengan `localhost` (tanpa port)
- Hasilnya: `localhost:8080` !== `localhost` → Access Denied

## ✅ Solusi

Mengubah logika pengecekan untuk mendukung:
- `localhost` (tanpa port)
- `localhost:8080` (dengan port)
- `127.0.0.1` (IP loopback)
- `127.0.0.1:8080` (IP dengan port)

### Kode Baru

```php
public function __construct()
{
    // Restriction: Only accessible from localhost (Developer privilege)
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $isLocalhost = (
        $host === 'localhost' || 
        strpos($host, 'localhost:') === 0 || 
        $host === '127.0.0.1' || 
        strpos($host, '127.0.0.1:') === 0
    );
    
    if (!$isLocalhost) {
        header('HTTP/1.1 403 Forbidden');
        exit('Access Denied: This feature is only available in the development environment (localhost).');
    }
    $this->changelogModel = new ChangelogModel();
}
```

## 🧪 Test Cases

| URL | HTTP_HOST | Result |
|-----|-----------|--------|
| `http://localhost/siakad/admin/changelogs` | `localhost` | ✅ Allowed |
| `http://localhost:8080/siakad/admin/changelogs` | `localhost:8080` | ✅ Allowed |
| `http://localhost:3000/siakad/admin/changelogs` | `localhost:3000` | ✅ Allowed |
| `http://127.0.0.1/siakad/admin/changelogs` | `127.0.0.1` | ✅ Allowed |
| `http://127.0.0.1:8080/siakad/admin/changelogs` | `127.0.0.1:8080` | ✅ Allowed |
| `http://siakad-anda.com/admin/changelogs` | `siakad-anda.com` | ❌ Denied |
| `http://192.168.1.100/admin/changelogs` | `192.168.1.100` | ❌ Denied |

## 📂 File yang Dimodifikasi

1. `app/Controllers/Admin/Changelogs.php` - Logika pengecekan localhost
2. `PANDUAN_UPDATE_VERSI_DAN_CHANGELOG.md` - Update dokumentasi
3. `SISTEM_VERSIONING.md` - Update dokumentasi

## 🎯 Impact

- ✅ Developer bisa akses dari `localhost:8080` (port development umum)
- ✅ Developer bisa akses dari `127.0.0.1` (IP loopback)
- ✅ Tetap aman: Production tidak bisa akses
- ✅ Backward compatible: `localhost` tanpa port tetap bisa

## 🔒 Security

Proteksi tetap kuat karena:
- Hanya localhost/127.0.0.1 yang diizinkan
- Domain production tetap diblokir
- IP LAN (192.168.x.x) tetap diblokir
- Tidak ada bypass mechanism

## ✅ Verification

Setelah perbaikan, coba akses:
```
http://localhost:8080/siakad/admin/changelogs
```

Seharusnya sekarang bisa masuk ke halaman Manajemen Changelog.

---

**Fixed:** 2026-04-04  
**Issue:** Access Denied saat akses dari localhost dengan port  
**Solution:** Update localhost detection logic
