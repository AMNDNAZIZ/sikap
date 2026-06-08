# Perbaikan Error 403 pada Peta (Map)

## Masalah

Aplikasi menampilkan banyak error **403 Access Blocked** pada peta karena menggunakan icon marker dari sumber eksternal yang sering diblokir:
- `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/` - Sering diblokir oleh firewall/ISP
- `https://cdnjs.cloudflare.com/ajax/libs/leaflet/` - Kadang lambat atau diblokir

## Solusi

Mengganti icon marker eksternal dengan **custom HTML marker** menggunakan `L.divIcon()` yang tidak memerlukan resource eksternal.

## File yang Diubah

### 1. `app/Views/admin/student_map/index.php`

**Sebelum:**
```javascript
const schoolIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    // ...
});

const studentIcon = L.icon({
    iconUrl: `https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-${iconColor}.png`,
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    // ...
});
```

**Sesudah:**
```javascript
// School marker dengan emoji 🏫
const schoolIcon = L.divIcon({
    className: 'custom-school-marker',
    html: '<div style="background-color: #dc3545; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><div style="transform: rotate(45deg); margin-top: 3px; text-align: center; color: white; font-size: 16px;">🏫</div></div>',
    iconSize: [30, 42],
    iconAnchor: [15, 42],
    popupAnchor: [0, -42]
});

// Student marker dengan emoji 👦/👧
const iconColor = student.gender === 'L' ? '#0d6efd' : '#d63384';
const iconEmoji = student.gender === 'L' ? '👦' : '👧';

const studentIcon = L.divIcon({
    className: 'custom-student-marker',
    html: `<div style="background-color: ${iconColor}; width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><div style="transform: rotate(45deg); margin-top: 2px; text-align: center; font-size: 14px;">${iconEmoji}</div></div>`,
    iconSize: [28, 40],
    iconAnchor: [14, 40],
    popupAnchor: [0, -40]
});
```

### 2. `app/Views/admin/school/index.php`

**Sebelum:**
```javascript
const schoolIcon = L.icon({
    iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
    shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
    // ...
});
```

**Sesudah:**
```javascript
const schoolIcon = L.divIcon({
    className: 'custom-school-marker',
    html: '<div style="background-color: #dc3545; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 3px 6px rgba(0,0,0,0.4);"><div style="transform: rotate(45deg); margin-top: 4px; text-align: center; color: white; font-size: 18px;">🏫</div></div>',
    iconSize: [32, 44],
    iconAnchor: [16, 44],
    popupAnchor: [0, -44]
});
```

## Keuntungan Solusi Ini

1. ✅ **Tidak ada dependency eksternal** - Semua resource lokal
2. ✅ **Tidak ada error 403** - Tidak ada request ke server eksternal yang bisa diblokir
3. ✅ **Lebih cepat** - Tidak perlu download image dari server luar
4. ✅ **Lebih mudah dikustomisasi** - Bisa ubah warna, ukuran, emoji dengan mudah
5. ✅ **Responsive** - Marker tetap terlihat jelas di berbagai ukuran layar
6. ✅ **Modern** - Menggunakan emoji yang universal dan mudah dipahami

## Tampilan Marker

- **Sekolah**: Pin merah dengan emoji 🏫
- **Siswa Laki-laki**: Pin biru dengan emoji 👦
- **Siswa Perempuan**: Pin pink dengan emoji 👧

## Testing

Setelah perubahan ini, peta akan:
1. Load lebih cepat
2. Tidak ada error 403 di console browser
3. Marker tetap terlihat dengan jelas
4. Semua fungsi peta (zoom, drag, cluster) tetap berfungsi normal

## Catatan

File `app/Views/siswa/location/index.php` tidak diubah karena sudah menggunakan marker default Leaflet yang tidak memerlukan resource eksternal.
