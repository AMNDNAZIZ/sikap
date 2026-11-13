<?php
// 🎯 Standar Skill Diterapkan: high-end-visual-design (UI), api-design-quality-review (PDF Layout)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md
/**
 * CSS Bersama untuk semua template PDF surat keluar
 * Pola SAMA seperti daftar_hadir_template.php:
 * - Tidak ada KOP hardcode di sini — KOP di-inject oleh PdfGenerator::generateAndSavePdf()
 * - Body diawali langsung dengan konten surat setelah <body>
 */
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($letter['letter_number'] ?? 'Surat') ?></title>
    <style>
        body        { font-family: Arial, sans-serif; font-size: 14px; color: #000; margin: 0; }
        .page       { padding: 0 30px 30px 30px; }

        /* JUDUL SURAT */
        .surat-judul        { text-align: center; margin-bottom: 12px; }
        .surat-judul .judul { font-size: 18px; font-weight: bold; text-transform: uppercase; text-decoration: underline; }
        .surat-judul .nomor { font-size: 16px; }

        /* DATA TABEL (style kiri-titik dua-kanan) */
        table.data-tabel    { border-collapse: collapse; width: 100%; font-size: 14px; }
        table.data-tabel td { padding: 2px 0; vertical-align: top; }
        table.data-tabel td.label  { width: 160px; }
        table.data-tabel td.colon  { width: 14px; text-align: center; }

        /* TABEL BORDERED (untuk daftar siswa lomba, dll) */
        table.tbl-bordered             { border-collapse: collapse; width: 100%; font-size: 13px; }
        table.tbl-bordered th,
        table.tbl-bordered td          { border: 1px solid #000; padding: 4px 5px; }
        table.tbl-bordered th          { background: #f0f0f0; font-weight: bold; text-align: center; }

        /* TEKS KONTEN */
        .isi, .pembuka, .penutup { font-size: 14px; line-height: 1.8; margin: 8px 0; }

        /* TANDA TANGAN & CAP */
        .ttd-wrapper  { margin-top: 20px; width: 230px; float: right; text-align: center; page-break-inside: avoid; font-size: 14px; position: relative; }
        .ttd-wrapper .signature-space { height: 65px; }
        .ttd-wrapper .ttd-line { border-top: 1px solid #000; width: 100%; display: block; margin-top: 2px; }
        .ttd-wrapper .seal-space { position: absolute; left: -25px; top: 12px; z-index: 10; pointer-events: none; }
        .signature-block { position: relative; z-index: 1; margin: 2px 0; }
        .signature-img { width: 180px; height: auto; max-height: 70px; display: block; margin: 0 auto; }
        .seal-block { position: absolute; left: -25px; top: 12px; z-index: 10; pointer-events: none; }
        .seal-img { width: 3.8cm; height: 3.8cm; max-width: 3.8cm; max-height: 3.8cm; display: block; }
        .ttd-clearfix { clear: both; }

        /* QR CODE */
        .qr-wrap { margin-top: 12px; float: left; text-align: center; }
        .qr-wrap img { width: 65px; height: 65px; }
        .qr-wrap p   { font-size: 9px; margin: 2px 0 0 0; }
    </style>
</head>
<body>
<div class="page">
