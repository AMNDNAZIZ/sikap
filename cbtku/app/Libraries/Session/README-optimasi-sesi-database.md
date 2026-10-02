# Optimasi Sesi & Cache Mode Database untuk Ujian Konkurensi Tinggi

Dokumen ini merangkum perubahan kode dan konfigurasi server yang diperlukan agar
aplikasi CBT tetap stabil (tanpa logout massal) saat **session & cache disimpan di
database** dan peserta ujian mencapai **100+ orang bersamaan**.

> Catatan: Redis tetap pilihan terbaik untuk konkurensi sangat tinggi. Perubahan di
> sini membuat mode **database** jauh lebih tahan, dari sekitar ~80 peserta menjadi
> 100+ peserta, tetapi bukan pengganti Redis untuk skala yang lebih besar lagi.

## Ringkasan akar masalah

Pada driver session `database`, CodeIgniter mengunci baris `ci_sessions` memakai
advisory lock MySQL `GET_LOCK(key, 300)` di **setiap** request yang menyentuh sesi.
Saat 80-100+ peserta menembak endpoint ujian (ping tiap 30 detik, simpan jawaban,
heartbeat) secara bersamaan:

1. Banyak request berebut lock dan menahan koneksi hingga 300 detik.
2. `pConnect` (koneksi persisten) menahan koneksi + lock lintas request.
3. Filter Auth menulis ulang sesi (tempdata `exam_active`) di setiap request.
4. Koneksi MySQL (`max_connections`) dan worker web cepat habis.
5. Request gagal membaca sesi → CI memulai sesi kosong → peserta ter-logout.

## Perubahan di kode (sudah diterapkan)

| File | Perubahan |
|------|-----------|
| `app/Libraries/Session/FastMySQLiHandler.php` | Handler sesi MySQLi kustom: `GET_LOCK` timeout dipangkas 300 → 10 detik. Koneksi tidak tertahan lama. |
| `app/Config/Session.php` | Saat driver `database` + MySQL, memakai `FastMySQLiHandler`. |
| `app/Config/Database.php` | `pConnect` diubah `true` → `false`. Koneksi & lock dilepas di akhir tiap request. |
| `app/Filters/Auth.php` | Tempdata `exam_active` hanya ditulis pada path berat (mulai/ujian/peraturan/submit) dan hanya bila belum ada; tidak lagi ditulis di setiap ping/save. `markAsFlashdata` dihapus dari jalur ujian. Untuk endpoint high-frequency (ping/save/heartbeat), sesi ditutup dini via `session()->close()` agar lock cepat dilepas, dan update `last_activity` dilewati. Throttle `last_activity` dilonggarkan 30 → 60 detik. |

Endpoint high-frequency (`ping`, `saveAnswer`, `saveAnswersBulk`, `heartbeat`) sudah
diverifikasi **tidak menulis PHP session** (hanya membaca identitas siswa dan menulis
ke tabel `cbt_sessions`/`cbt_answers`), sehingga penutupan sesi dini aman.

## Konfigurasi server MySQL/MariaDB (WAJIB untuk 100+ peserta)

Tambahkan/ubah pada `my.cnf` / `my.ini` (bagian `[mysqld]`), lalu restart MySQL:

```ini
[mysqld]
# Naikkan batas koneksi. Rumus kasar: (jumlah peserta puncak x 1.5) + cadangan admin.
# Untuk 100-150 peserta, 300 adalah titik awal yang aman.
max_connections = 300

# Jangan biarkan transaksi InnoDB menunggu lock terlalu lama.
innodb_lock_wait_timeout = 15

# Batas menunggu metadata lock (DDL) juga dipendekkan.
lock_wait_timeout = 30

# Beri InnoDB memori memadai (sesuaikan dengan RAM server, mis. 50-70% RAM).
innodb_buffer_pool_size = 1G

# Flushing lebih longgar → tulis lebih cepat (aman untuk data sesi/aktivitas).
innodb_flush_log_at_trx_commit = 2

# Timeout koneksi menganggur agar koneksi tidak menumpuk.
wait_timeout = 120
interactive_timeout = 120
```

### Verifikasi cepat di server

```sql
SHOW VARIABLES LIKE 'max_connections';
SHOW STATUS LIKE 'Threads_connected';
SHOW STATUS LIKE 'Max_used_connections';
```

`Max_used_connections` yang mendekati `max_connections` saat ujian = tanda perlu
menaikkan `max_connections` atau beralih ke Redis.

## Konfigurasi PHP (opsional tapi disarankan)

- Pastikan `max_execution_time` cukup (mis. 60-120 dtk) tetapi tidak berlebihan.
- Gunakan PHP-FPM dengan jumlah `pm.max_children` yang selaras dengan
  `max_connections` MySQL agar tidak ada worker yang menunggu koneksi.

## Housekeeping tabel ci_sessions

Baris sesi kedaluwarsa perlu dibersihkan agar tabel tidak membengkak:

```sql
DELETE FROM ci_sessions WHERE timestamp < (UNIX_TIMESTAMP() - 14400);
```

Jalankan berkala (mis. cron tiap jam) saat tidak ada ujian berlangsung.

## Catatan lisensi

`app/Config/Filters.php` termasuk file yang diverifikasi checksum oleh sistem lisensi.
File yang diubah pada optimasi ini (`Session.php`, `Database.php`, `Auth.php`,
`FastMySQLiHandler.php`) **tidak** termasuk daftar yang dilindungi, sehingga tidak
memicu masalah verifikasi lisensi.
