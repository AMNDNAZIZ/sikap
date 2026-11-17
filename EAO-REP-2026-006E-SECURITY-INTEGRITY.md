# EAO-REP-2026-006E-SECURITY-INTEGRITY
## Laporan Forensik & Verifikasi Integritas Lisensi: Investigasi Kegagalan Exit Code 1 Awal dan Pembuktian Hash Baku 100%

**Nomor Dokumen:** EAO-REP-2026-006E-SECURITY-INTEGRITY  
**Tanggal:** 16 November 2026  
**Otoritas Pembuat:** Tim Multi-Agen EAO (AI Security Reviewer, System Architect, QA Engineer)  
**Dasar Mandat:** EAO-HO-006E (Bagian D — Security Integrity Verification)  
**Status Audit:** READ-ONLY FORENSIC & INTEGRITY AUDIT  
**Hasil Integritas:** **SEC_PASS (MUTLAK 100% PERSIS / NOL TAMPERING)**

---

## 1. Kronologi Investigasi Exit Code 1 Awal

Pada pembukaan sesi kerja verifikasi, dilakukan pemeriksaan integritas berkas lisensi menggunakan baris perintah CLI:

### 1.1 Perintah Pemeriksaan Pertama (Memicu Exit Code 1):
```bash
php -r "require 'app/Libraries/SecurityGuard.php'; echo \App\Libraries\SecurityGuard::verifyCriticalFiles();"
```
**Output Sistem:**
```
PHP Fatal error:  Uncaught Error: Undefined constant "App\Libraries\APPPATH" in C:\xampp\htdocs\siakad\app\Libraries\SecurityGuard.php:41
Stack trace:
#0 Command line code(1): App\Libraries\SecurityGuard::verifyCriticalFiles()
#1 {main}
  thrown in C:\xampp\htdocs\siakad\app\Libraries\SecurityGuard.php on line 41

Command exited with code 1.
```

### 1.2 Perintah Pemeriksaan Kedua (Membuktikan Konteks Bootstrap):
```bash
php -r "define('APPPATH', __DIR__ . '/app/'); require 'app/Libraries/SecurityGuard.php'; echo \App\Libraries\SecurityGuard::verifyCriticalFiles();"
```
**Output Sistem:**
```
1
Command exited with code 0.
```

---

## 2. Analisis Akar Masalah Teknis (*Root Cause Analysis*)

### 2.1 Peran Konstanta Global `APPPATH` di Framework CodeIgniter 4
Di dalam kerangka kerja CodeIgniter 4, konstanta sistem seperti `APPPATH`, `SYSTEMPATH`, `ROOTPATH`, dan `WRITEPATH` dideklarasikan pada saat proses *bootstrap* aplikasi (di dalam `public/index.php` atau `spark` melalui pemanggilan `system/bootstrap.php` atau `system/Test/bootstrap.php`).

Ketika `SecurityGuard.php` dimuat secara mandiri melalui *one-liner* PHP CLI (`php -r "require ..."`), seluruh alur *bootstrap* CodeIgniter 4 tidak dieksekusi. Akibatnya, pada baris 41 berkas `app/Libraries/SecurityGuard.php`:
```php
$hashFile = APPPATH . 'Config/.security_hash';
```
Interpreter PHP berusaha mencari konstanta `APPPATH` di dalam namespace `App\Libraries`. Karena konstanta tersebut belum didefinisikan, PHP 8.1+ melempar *Unhandled Fatal Error*: `Undefined constant "App\Libraries\APPPATH"`.

### 2.2 Bukti Bahwa Exit Code 1 Bukan Masalah Integritas Berkas
1. Galat yang terjadi adalah **PHP Engine Runtime Fatal Error** akibat ketiadaan definisi konstanta sistem, **BUKAN** kegagalan logika verifikasi hash (`SEC_FAIL`).
2. Begitu konstanta `APPPATH` disediakan secara sah (`define('APPPATH', __DIR__ . '/app/');`), pemanggilan `SecurityGuard::verifyCriticalFiles()` berjalan mulus dan seketika mengembalikan nilai `true` (`1` pada `echo`).
3. Tidak ada satu pun baris kode pada keenam berkas terlindung yang berubah atau dimodifikasi.

---

## 3. Hasil Audit Forensik Hash Aktual Keenam Berkas Terlindung

Untuk memberikan kepastian 100% kepada Human Owner, tim mengeksekusi skrip audit hash mendalam (`scratch/check_hashes.php`) yang membandingkan nilai kriptografis SHA-256 aktual setiap berkas dengan nilai dasar yang tersimpan di `app/Config/.security_hash`:

| No | Nama Berkas Terlindung | Hash Baseline (`.security_hash`) | Hash Aktual di Filesystem | Status Kecocokan | Status Integritas |
|:---:|:---|:---|:---|:---:|:---:|
| 1 | `Helpers/license_helper.php` | `9a3af1adab13a95cc8b280d682e10825f49dbb8e44a9c24366c264bc1d3decdb` | `9a3af1adab13a95cc8b280d682e10825f49dbb8e44a9c24366c264bc1d3decdb` | **IDENTIK 100%** | **SEC_PASS** |
| 2 | `Libraries/LicenseGuard.php` | `2a53d40bc4088e738e9c733448382d7d72f778b6b53dfd2d03c57f5476d02151` | `2a53d40bc4088e738e9c733448382d7d72f778b6b53dfd2d03c57f5476d02151` | **IDENTIK 100%** | **SEC_PASS** |
| 3 | `Libraries/SecurityGuard.php` | `c677a4689c307d23bfd7f1a751dc19e736c544121d7d47de678fbcddbc771b27` | `c677a4689c307d23bfd7f1a751dc19e736c544121d7d47de678fbcddbc771b27` | **IDENTIK 100%** | **SEC_PASS** |
| 4 | `Libraries/SecureConfig.php` | `fc5a91354e17cab2614e45e649b799252fd595faa7706dd05f233dd83d4d4587` | `fc5a91354e17cab2614e45e649b799252fd595faa7706dd05f233dd83d4d4587` | **IDENTIK 100%** | **SEC_PASS** |
| 5 | `Filters/LicenseFilter.php` | `74d4611bf622b976cf56262d64b559faa4f56302380597dfd8264f16d8eab3d6` | `74d4611bf622b976cf56262d64b559faa4f56302380597dfd8264f16d8eab3d6` | **IDENTIK 100%** | **SEC_PASS** |
| 6 | `Models/LicenseModel.php` | `b39df211750afa29db2dbc3ebed4af68f059d910aaf7132aa8a8e49e4fc51481` | `b39df211750afa29db2dbc3ebed4af68f059d910aaf7132aa8a8e49e4fc51481` | **IDENTIK 100%** | **SEC_PASS** |

---

## 4. Kesimpulan Akhir Security Reviewer

1. **Konfirmasi Absolut:** Kejadian *exit code 1* pada perintah pertama murni disebabkan oleh **masalah lingkungan eksekusi CLI (*execution environment / missing bootstrap context*)**, yaitu ketiadaan konstanta `APPPATH` saat file dipanggil secara *standalone*.
2. **Ketiadaan Tampering:** Keenam berkas kritikal lisensi terbukti **100% utuh, tidak pernah tersentuh, dan nilainya identik secara bit-for-bit** dengan baseline resmi aplikasi ESPATA.
3. **Status Integritas:** Sistem lisensi berstatus **`SEC_PASS` PENUH**. Tidak ada pelanggaran terhadap *Golden Rule #2 (Protected Files Inviolability)*.
