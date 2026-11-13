<?php

namespace App\Libraries;

/**
 * EspataFeatureKnowledge
 * 
 * Basis Pengetahuan Bawaan (Built-in Knowledge Base) untuk panduan penggunaan
 * fitur-fitur aplikasi ESPATA, offline-ready, ramah shared hosting, dan sesuai peran (RBAC).
 */
class EspataFeatureKnowledge
{
    /**
     * Dapatkan daftar seluruh panduan fitur yang tersedia
     */
    public static function getAllGuides(): array
    {
        return [
            [
                'id'            => 'guide_modul_ajar',
                'title'         => 'Panduan Membuat Modul Ajar (Kurikulum Merdeka)',
                'category'      => 'Kurikulum & Pembelajaran',
                'allowed_roles' => [1, 3],
                'keywords'      => ['cara membuat modul', 'cara buat modul', 'panduan modul ajar', 'bikin rpp', 'perangkat ajar', 'generate modul ajar', 'modul ajar'],
                'summary'       => 'Langkah-langkah menyusun Modul Ajar dan memanfaatkan Generator AI:',
                'steps'         => [
                    'Buka menu <strong>Kurikulum & Pembelajaran</strong> &rarr; <strong>Perangkat Ajar (Modul Ajar)</strong>.',
                    'Pilih <strong>Mata Pelajaran</strong> dan <strong>Kelas</strong> yang Anda ampu pada dropdown filter.',
                    'Cari Alur Tujuan Pembelajaran (ATP) yang diinginkan, lalu klik tombol <strong>Buat Modul Ajar</strong>.',
                    'Anda dapat menyusun komponen modul secara manual atau klik tombol <strong>Generate AI</strong> untuk membuat draf materi, pertanyaan pemantik, dan asesmen secara otomatis.',
                    'Setelah selesai diperiksa, klik <strong>Simpan</strong>. Anda juga dapat mengunduh dokumen siap cetak melalui tombol <strong>Cetak PDF</strong>.'
                ],
                'action_url'    => site_url('admin/administrasi-guru/modul-ajar'),
                'action_label'  => 'Buka Modul Ajar',
                'action_icon'   => 'bi bi-journal-bookmark-fill',
            ],
            [
                'id'            => 'guide_input_nilai',
                'title'         => 'Panduan Penginputan Nilai Siswa & Asesmen',
                'category'      => 'Penilaian & Rapor',
                'allowed_roles' => [1, 3],
                'keywords'      => ['cara input nilai', 'cara memasukkan nilai', 'panduan nilai', 'nilai tugas', 'nilai rapor', 'input nilai siswa'],
                'summary'       => 'Langkah-langkah menginput nilai harian, formatif, sumatif, dan rapor:',
                'steps'         => [
                    'Masuk ke menu <strong>Penilaian & Rapor</strong> &rarr; <strong>Input Nilai</strong>.',
                    'Pilih <strong>Kelas</strong> dan <strong>Mata Pelajaran</strong> yang sedang Anda ampu.',
                    'Pilih jenis penilaian: <strong>Formatif</strong> (tugas, kuis, harian) atau <strong>Sumatif</strong> (PTS / PAS).',
                    'Ketikkan nilai angka pada kolom siswa. Sistem secara otomatis menghitung nilai akhir dan capaian kompetensi.',
                    'Klik tombol <strong>Simpan Nilai</strong> di bagian bawah untuk menyimpan perubahan.'
                ],
                'action_url'    => site_url('admin/grades'),
                'action_label'  => 'Buka Halaman Penilaian',
                'action_icon'   => 'bi bi-calculator-fill',
            ],
            [
                'id'            => 'guide_presensi_siswa',
                'title'         => 'Panduan Pencatatan Presensi Kehadiran Siswa',
                'category'      => 'Akademik & KBM',
                'allowed_roles' => [1, 3],
                'keywords'      => ['cara absen siswa', 'cara presensi siswa', 'panduan kehadiran siswa', 'absen harian', 'presensi kelas'],
                'summary'       => 'Langkah-langkah mencatat kehadiran harian siswa di kelas:',
                'steps'         => [
                    'Buka menu <strong>Akademik & KBM</strong> &rarr; <strong>Presensi Siswa</strong>.',
                    'Pilih <strong>Kelas</strong> yang ingin diabsen serta tentukan tanggal presensi.',
                    'Tentukan status masing-masing siswa: <strong>H</strong> (Hadir), <strong>S</strong> (Sakit), <strong>I</strong> (Izin), atau <strong>A</strong> (Alpa).',
                    'Gunakan tombol pintas <em>"Set Semua Hadir"</em> untuk mempercepat pengisian jika sebagian besar siswa hadir.',
                    'Klik tombol <strong>Simpan Presensi</strong> untuk merekam data kehadiran ke rekapitulasi sekolah.'
                ],
                'action_url'    => site_url('admin/attendance'),
                'action_label'  => 'Buka Presensi Siswa',
                'action_icon'   => 'bi bi-calendar-check-fill',
            ],
            [
                'id'            => 'guide_bank_soal_cbt',
                'title'         => 'Panduan Mengelola Bank Soal Ujian CBT',
                'category'      => 'Ujian Online CBT',
                'allowed_roles' => [1, 3],
                'keywords'      => ['cara buat soal cbt', 'tambah soal cbt', 'bank soal ujian', 'buat soal ujian', 'upload soal cbt'],
                'summary'       => 'Langkah-langkah menyusun bank soal untuk pelaksanaan ujian online:',
                'steps'         => [
                    'Buka menu <strong>Ujian CBT</strong> &rarr; <strong>Bank Soal</strong>.',
                    'Klik tombol <strong>Tambah Bank Soal</strong> baru, lalu pilih mata pelajaran dan tingkat kelas sasaran.',
                    'Klik ikon <strong>Kelola Soal</strong> pada paket yang telah dibuat, lalu pilih <strong>Tambah Butir Soal</strong>.',
                    'Pilih tipe soal yang didukung: <em>Pilihan Ganda (PG), PG Kompleks, Menjodohkan, Isian Singkat, atau Esai</em>.',
                    'Tuliskan stimulus/pertanyaan soal, opsi jawaban, tentukan kunci jawaban yang benar, dan bobot nilainya.',
                    'Anda juga dapat memanfaatkan fitur <strong>Import Excel</strong> untuk mengunggah puluhan soal sekaligus secara instan.'
                ],
                'action_url'    => site_url('admin/cbt/bank-soal'),
                'action_label'  => 'Buka Bank Soal CBT',
                'action_icon'   => 'bi bi-card-checklist',
            ],
            [
                'id'            => 'guide_reset_ujian_cbt',
                'title'         => 'Panduan Reset Sesi Ujian Siswa Terkunci di CBT',
                'category'      => 'Ujian Online CBT',
                'allowed_roles' => [1, 2, 3],
                'keywords'      => ['cara reset ujian', 'siswa terkunci', 'reset login cbt', 'ujian tab lain', 'buka sesi cbt', 'ujian terkunci', 'reset cbt'],
                'summary'       => 'Langkah-langkah membuka sesi ujian siswa yang terkunci atau pindah perangkat:',
                'steps'         => [
                    'Buka menu <strong>Ujian CBT</strong> &rarr; <strong>Aktivitas & Monitoring</strong>.',
                    'Pilih sesi ujian yang sedang berjalan pada daftar ujian aktif.',
                    'Cari nama atau username siswa yang mengalami kendala pada tabel pengawas.',
                    'Klik tombol aksi <strong>Reset Login / Buka Kunci</strong> pada baris siswa tersebut.',
                    'Konfirmasi dialog reset. Sistem akan membersihkan token kunci sesi sebelumnya.',
                    'Minta siswa untuk me-refresh halaman ujian di peramban (browser) mereka untuk melanjutkan ujian tanpa kehilangan jawaban yang telah tersimpan.'
                ],
                'action_url'    => site_url('admin/cbt/aktivitas'),
                'action_label'  => 'Buka Monitoring CBT',
                'action_icon'   => 'bi bi-speedometer2',
            ],
            [
                'id'            => 'guide_rekap_absensi_guru',
                'title'         => 'Panduan Melihat Rekapitulasi Presensi Guru',
                'category'      => 'Kepegawaian',
                'allowed_roles' => [1, 2],
                'keywords'      => ['cara melihat absen guru', 'rekap kehadiran guru', 'laporan absensi guru', 'kehadiran pegawai', 'pantau guru'],
                'summary'       => 'Langkah-langkah monitoring kehadiran guru dan tenaga pendidik:',
                'steps'         => [
                    'Masuk ke menu <strong>Kepegawaian</strong> &rarr; <strong>Laporan Absensi Guru</strong>.',
                    'Pilih periode <strong>Bulan</strong> dan <strong>Tahun Pelajaran</strong> yang ingin ditinjau.',
                    'Sistem akan menampilkan rekapitulasi kehadiran per guru, persentase kehadiran, serta rincian jam pelajaran yang terlaksana.',
                    'Gunakan tombol <strong>Cetak / Export Excel</strong> untuk mencetak berkas laporan resmi bagi pimpinan atau dinas pendidikan.'
                ],
                'action_url'    => site_url('admin/teacher-attendance/report'),
                'action_label'  => 'Buka Laporan Presensi Guru',
                'action_icon'   => 'bi bi-person-check-fill',
            ],
            [
                'id'            => 'guide_rapor_semester',
                'title'         => 'Panduan Pengolahan & Cetak Rapor Semester',
                'category'      => 'Penilaian & Rapor',
                'allowed_roles' => [1, 2, 3],
                'keywords'      => ['cara cetak rapor', 'rapor semester', 'cetak nilai rapor', 'rapor kurikulum merdeka', 'kelulusan'],
                'summary'       => 'Langkah-langkah finalisasi dan pencetakan buku rapor siswa:',
                'steps'         => [
                    'Buka menu <strong>Penilaian & Rapor</strong> &rarr; <strong>Rapor Semester</strong>.',
                    'Pastikan seluruh guru pengampu mata pelajaran telah merampungkan penginputan nilai akhir.',
                    'Bagi Wali Kelas, lengkapi bagian <strong>Catatan Wali Kelas</strong>, rekapitulasi kehadiran semester (S/I/A), dan kegiatan ekstrakurikuler.',
                    'Klik tombol <strong>Hitung Peringkat & Kelulusan/Kenaikan</strong> jika diperlukan.',
                    'Klik tombol <strong>Cetak Rapor PDF</strong> untuk mencetak lembar rapor lengkap dengan identitas sekolah dan tanda tangan.'
                ],
                'action_url'    => site_url('admin/rapor-semester'),
                'action_label'  => 'Buka Rapor Semester',
                'action_icon'   => 'bi bi-award-fill',
            ],
            [
                'id'            => 'guide_mutasi_siswa',
                'title'         => 'Panduan Pencatatan Mutasi Siswa (Masuk / Keluar)',
                'category'      => 'Administrasi Siswa',
                'allowed_roles' => [1],
                'keywords'      => ['cara mutasi siswa', 'siswa pindah', 'buku mutasi', 'siswa keluar', 'siswa masuk', 'pindah sekolah'],
                'summary'       => 'Langkah-langkah mengelola buku mutasi siswa:',
                'steps'         => [
                    'Buka menu <strong>Administrasi Siswa</strong> &rarr; <strong>Buku Mutasi Siswa</strong>.',
                    'Klik tombol <strong>Catat Mutasi Baru</strong>.',
                    'Pilih jenis mutasi: <em>Mutasi Masuk</em> (pindahan dari sekolah lain) atau <em>Mutasi Keluar</em> (pindah ke sekolah lain).',
                    'Isikan nomor surat mutasi, tanggal kepindahan, alasan, dan sekolah tujuan/asal.',
                    'Klik <strong>Simpan Mutasi</strong>. Status siswa akan diperbarui secara otomatis tanpa merusak arsip nilai lama.'
                ],
                'action_url'    => site_url('admin/student-mutation'),
                'action_label'  => 'Buka Buku Mutasi Siswa',
                'action_icon'   => 'bi bi-arrow-left-right',
            ],
            [
                'id'            => 'guide_surat_tu',
                'title'         => 'Panduan Pengelolaan Surat Masuk & Keluar',
                'category'      => 'Tata Usaha & Surat',
                'allowed_roles' => [1, 2],
                'keywords'      => ['cara buat surat', 'surat masuk', 'surat keluar', 'agenda surat', 'tata usaha', 'nomor surat'],
                'summary'       => 'Langkah-langkah pencatatan surat dan arsip dinas:',
                'steps'         => [
                    'Buka menu <strong>Tata Usaha & Surat</strong> &rarr; pilih <strong>Surat Masuk</strong> atau <strong>Surat Keluar</strong>.',
                    'Klik tombol <strong>Tambah Surat</strong>.',
                    'Isikan nomor agenda, nomor surat, tanggal surat, instansi pengirim/tujuan, dan perihal.',
                    'Unggah pindaian (scan) berkas surat dalam format PDF jika tersedia.',
                    'Klik <strong>Simpan</strong>. Surat akan tercatat rapi pada agenda buku ekspedisi digital sekolah.'
                ],
                'action_url'    => site_url('admin/surat-masuk'),
                'action_label'  => 'Buka Agenda Surat',
                'action_icon'   => 'bi bi-inbox',
            ],
        ];
    }

    /**
     * Cari panduan fitur yang paling relevan dengan kueri pengguna berdasarkan role pengguna
     */
    public static function findGuide(string $query, int $roleId): ?array
    {
        $qClean = strtolower(preg_replace('/[^a-z0-9\s]/', ' ', $query));
        $tokens = array_filter(explode(' ', $qClean), function ($t) {
            return strlen(trim($t)) >= 2;
        });

        if (empty($tokens)) {
            return null;
        }

        $allGuides = self::getAllGuides();
        $bestMatch = null;
        $highestScore = 0;

        foreach ($allGuides as $guide) {
            // Cek role izin
            if (!in_array($roleId, $guide['allowed_roles']) && $roleId !== 1) {
                continue;
            }

            $score = 0;
            $titleLower = strtolower($guide['title']);
            $keywords = $guide['keywords'] ?? [];

            // Exact match pada frase
            if (strpos($titleLower, $qClean) !== false) {
                $score += 60;
            }

            // Cek kecocokan kata kunci
            foreach ($keywords as $kw) {
                if (strpos($qClean, strtolower($kw)) !== false) {
                    $score += 40;
                }
            }

            // Cek kecocokan per kata (token)
            foreach ($tokens as $token) {
                if (strpos($titleLower, $token) !== false) {
                    $score += 15;
                }
                foreach ($keywords as $kw) {
                    if (strpos(strtolower($kw), $token) !== false) {
                        $score += 10;
                    }
                }
            }

            if ($score > $highestScore && $score >= 30) {
                $highestScore = $score;
                $bestMatch = $guide;
            }
        }

        return $bestMatch;
    }
}

