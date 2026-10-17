<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class LicensePackage extends BaseCommand
{
    protected $group       = 'License';
    protected $name        = 'license:package';
    protected $description = 'Siapkan dan kemas file Core Lisensi ke folder build/ioncube/ untuk di-encode dengan ionCube Encoder';
    protected $usage       = 'license:package';

    /**
     * Daftar file inti lisensi yang harus di-encode dengan ionCube
     */
    protected array $targetFiles = [
        'app/Libraries/LicenseGuard.php',
        'app/Libraries/SecurityGuard.php',
        'app/Libraries/SecureConfig.php',
        'app/Helpers/license_helper.php',
        'app/Filters/LicenseFilter.php',
        'app/Models/LicenseModel.php',
    ];

    public function run(array $params)
    {
        CLI::write("=======================================================", 'cyan');
        CLI::write("  ESPATA - Paket Persiapan Encoding ionCube Lisensi   ", 'yellow');
        CLI::write("=======================================================", 'cyan');
        CLI::newLine();

        $buildDir  = ROOTPATH . 'build/ioncube/';
        $sourceDir = $buildDir . 'source/';
        $outDir    = $buildDir . 'encoded/';

        // Buat direktori build
        if (!is_dir($sourceDir)) {
            mkdir($sourceDir, 0755, true);
        }
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        CLI::write("📦 Menyalin file-file Core Lisensi ke: build/ioncube/source/", 'yellow');

        $copiedCount = 0;
        foreach ($this->targetFiles as $relPath) {
            $srcPath  = ROOTPATH . $relPath;
            $destPath = $sourceDir . $relPath;
            $destDir  = dirname($destPath);

            if (!is_file($srcPath)) {
                CLI::write("  ⚠️ File tidak ditemukan: {$relPath}", 'red');
                continue;
            }

            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

            copy($srcPath, $destPath);
            CLI::write("  ✅ Disalin: {$relPath}", 'green');
            $copiedCount++;
        }

        // Tulis file panduan encoding
        $guideContent = $this->generateGuideContent();
        file_put_contents($buildDir . 'PANDUAN_ENCODING.md', $guideContent);

        CLI::newLine();
        CLI::write("🎉 Berhasil menyalin {$copiedCount} file core lisensi!", 'green');
        CLI::write("📄 Panduan encoding dibuat di: build/ioncube/PANDUAN_ENCODING.md", 'cyan');
        CLI::newLine();

        CLI::write("-------------------------------------------------------", 'dark_gray');
        CLI::write("Langkah Selanjutnya:", 'yellow');
        CLI::write("1. Buka ionCube Encoder (GUI atau CLI).", 'white');
        CLI::write("2. Set Source folder : " . $sourceDir, 'white');
        CLI::write("3. Set Target folder : " . $outDir, 'white');
        CLI::write("4. Set Target PHP    : PHP 8.1 atau 8.2 (sesuai PHP server klien).", 'white');
        CLI::write("5. Klik Encode. Hasil file ter-encode akan ada di folder: build/ioncube/encoded/", 'white');
        CLI::write("6. Salin file ter-encode tersebut ke server klien / master paket rilis.", 'white');
        CLI::write("-------------------------------------------------------", 'dark_gray');
    }

    private function generateGuideContent(): string
    {
        return "# PANDUAN ENCODING CORE LISENSI DENGAN IONCUBE\n\n" .
            "File-file di dalam folder `source/` adalah komponen inti sistem lisensi ESPATA.\n" .
            "File ini cukup di-encode **1 KALI** (atau jika ada perubahan algoritma lisensi).\n" .
            "Seluruh controller, view, dan fitur lainnya tetap dalam format Plain PHP agar Anda bebas merilis update tanpa perlu encoding ulang.\n\n" .
            "## 1. File yang Di-encode:\n" .
            "- `app/Libraries/LicenseGuard.php` : Inti logika verifikasi mesin, domain, expired, dan server lisensi.\n" .
            "- `app/Libraries/SecurityGuard.php` : Pemeriksa integritas & security hash anti-tamper.\n" .
            "- `app/Libraries/SecureConfig.php` : Penyimpan rahasia & decryptor kunci konfigurasi.\n" .
            "- `app/Helpers/license_helper.php` : Helper penentu signature hardware & machine ID.\n" .
            "- `app/Filters/LicenseFilter.php` : Filter global penegak lisensi tiap request route.\n" .
            "- `app/Models/LicenseModel.php` : Model database lisensi dengan enkripsi field sensitif.\n\n" .
            "## 2. Pengaturan di ionCube Encoder:\n" .
            "1. **Input / Source Directory**: Path ke folder `build/ioncube/source/`\n" .
            "2. **Output / Target Directory**: Path ke folder `build/ioncube/encoded/`\n" .
            "3. **PHP Version Target**: Pilih versi PHP server klien (misal **PHP 8.1** atau **PHP 8.2**).\n" .
            "4. **File Restrictions (Opsional)**:\n" .
            "   - Anda bisa menambahkan domain lock atau expiry date bawaan ionCube jika diinginkan.\n\n" .
            "## 3. Contoh Perintah ionCube Encoder CLI:\n" .
            "```bash\n" .
            "ioncube_encoder.exe --php 8.1 source -o encoded\n" .
            "```\n\n" .
            "## 4. Cara Deploy ke Server Klien:\n" .
            "1. Pastikan ekstensi **ionCube Loader** aktif di cPanel / php.ini server klien.\n" .
            "2. Salin isi folder `build/ioncube/encoded/app/` ke dalam folder `app/` di server klien (menimpa 6 file plain PHP).\n" .
            "3. Sistem Auto-Update (`espata-update`) sudah dikonfigurasi untuk **MENGECEUALIKAN** 6 file ini,\n" .
            "   sehingga setiap update fitur baru yang dirilis via GitHub **tidak akan pernah menimpa** file ionCube klien!\n";
    }
}
