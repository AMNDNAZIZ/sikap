# SIAKAD Bridge - Sinkronisasi Dapodik ke Online

Bridge ini digunakan untuk mengirim data dari Dapodik lokal (di PC/Laptop sekolah) ke SIAKAD yang terpasang di hosting online.

## 📋 Prasyarat

1. **PC/Laptop dengan Dapodik terinstal**
   - Aplikasi Dapodik harus berjalan
   - Web Service Dapodik harus aktif (biasanya di port 5774)

2. **PowerShell** (sudah terinstal di Windows)

3. **Koneksi Internet** untuk mengirim data ke server online

## 🔧 Cara Setup

### Langkah 1: Konfigurasi Script

Edit file `siakad_sync_agent.ps1` dan sesuaikan konfigurasi berikut:

```powershell
# URL SIAKAD Online Anda
$SIAKAD_URL = "https://siakad-anda.com/api/dapodik/receive"

# Token keamanan (harus sama dengan di .env server)
$SIAKAD_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"

# URL Dapodik Lokal (biasanya tidak perlu diubah)
$DAPODIK_URL = "http://127.0.0.1:5774/WebService"

# Key Web Service Dapodik (ambil dari aplikasi Dapodik)
$DAPODIK_KEY = "Zikf0c0WK7snRmf"

# NPSN Sekolah Anda
$DAPODIK_NPSN = "20105399"
```

### Langkah 2: Konfigurasi Server Online

Di server SIAKAD online, edit file `.env` dan tambahkan:

```env
SIAKAD_SYNC_TOKEN = "siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j"
```

**PENTING:** Token harus sama dengan yang ada di script PowerShell!

### Langkah 3: Mendapatkan Key Web Service Dapodik

1. Buka aplikasi Dapodik
2. Masuk ke menu **Pengaturan** > **Web Service**
3. Salin **Key** yang ditampilkan
4. Paste ke variabel `$DAPODIK_KEY` di script

### Langkah 4: Menjalankan Sync

1. Buka PowerShell sebagai Administrator
2. Navigate ke folder bridge:
   ```powershell
   cd C:\path\to\siakad\bridge
   ```

3. Jalankan script:
   ```powershell
   .\siakad_sync_agent.ps1
   ```

4. Script akan:
   - Mengambil data siswa dari Dapodik lokal
   - Mengirim ke server online
   - Mengambil data guru dari Dapodik lokal
   - Mengirim ke server online

## 📊 Output yang Diharapkan

Jika berhasil, Anda akan melihat:

```
--- SIAKAD SYNC AGENT STARTING ---
Penarikan data SISWA...
Ditemukan 150 siswa. Mengirim ke Online...
SUCCESS: Berhasil sinkronisasi 145 siswa via API. Melompati 5 siswa (sudah ada).

Penarikan data GURU...
Ditemukan 25 guru. Mengirim ke Online...
SUCCESS: Berhasil sinkronisasi 23 guru via API. Melompati 2 guru (sudah ada).

--- SYNC PROCESS COMPLETED ---
```

## ⚠️ Troubleshooting

### Error: "FAILED to fetch from Dapodik"

**Penyebab:**
- Aplikasi Dapodik tidak berjalan
- Web Service Dapodik tidak aktif
- Key Web Service salah

**Solusi:**
1. Pastikan aplikasi Dapodik terbuka
2. Cek menu Pengaturan > Web Service di Dapodik
3. Pastikan Key sudah benar

### Error: "FAILED to push to Siakad: Unauthorized"

**Penyebab:**
- Token tidak sama antara script dan server
- Token salah

**Solusi:**
1. Cek `$SIAKAD_TOKEN` di script
2. Cek `SIAKAD_SYNC_TOKEN` di file `.env` server
3. Pastikan keduanya sama persis

### Error: "FAILED to push to Siakad: 404 Not Found"

**Penyebab:**
- URL salah
- Route API belum terdaftar di server

**Solusi:**
1. Pastikan URL benar: `https://domain-anda.com/api/dapodik/receive`
2. Pastikan route API sudah ditambahkan di `app/Config/Routes.php`

## 🔄 Sinkronisasi Berkala

Untuk sinkronisasi otomatis berkala, Anda bisa:

### Opsi 1: Task Scheduler Windows

1. Buka **Task Scheduler**
2. Klik **Create Basic Task**
3. Nama: "SIAKAD Dapodik Sync"
4. Trigger: Daily atau Weekly
5. Action: Start a program
   - Program: `powershell.exe`
   - Arguments: `-ExecutionPolicy Bypass -File "C:\path\to\siakad\bridge\siakad_sync_agent.ps1"`

### Opsi 2: Manual Sesuai Kebutuhan

Jalankan script setiap kali ada perubahan data di Dapodik yang perlu disinkronkan.

## 📝 Catatan Penting

1. **Data yang Sudah Ada:** Script akan melewati (skip) data yang sudah ada berdasarkan NISN (siswa) atau NUPTK/NIP (guru)

2. **Password Default:** 
   - Siswa: `siswa[NISN]` (contoh: `siswa29192919`)
   - Guru: `guru[NUPTK/NIP]` (contoh: `guru1234567890`)
   - User harus mengganti password saat login pertama

3. **Kelas Otomatis:** Sistem akan mencoba mencocokkan nama rombel dari Dapodik dengan nama kelas di SIAKAD secara otomatis

4. **Keamanan:** Jangan share token sync ke pihak lain. Token ini memberikan akses untuk menambah data ke sistem.

## 🆘 Bantuan Lebih Lanjut

Jika mengalami masalah:
1. Cek log error di output PowerShell
2. Cek log server di `writable/logs/`
3. Hubungi administrator sistem
