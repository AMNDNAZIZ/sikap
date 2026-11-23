<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LKPD Pertemuan <?= esc($pertemuan) ?> - <?= esc($subject['name'] ?? 'Mata Pelajaran') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #111;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        
        /* Toolbar Kontrol Cetak (Hanya tampil di layar) */
        .print-toolbar {
            background: #ffffff;
            border-bottom: 2px solid #0d6efd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 10px 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-toolbar {
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print { background: #0d6efd; color: #fff; }
        .btn-print:hover { background: #0b5ed7; }
        .btn-close-print { background: #6c757d; color: #fff; }
        .btn-close-print:hover { background: #5c636a; }

        /* Container Lembar Kerja */
        .sheet-container {
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 20mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        /* Header LKPD */
        .lkpd-header {
            text-align: center;
            border-bottom: 3px double #222;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .lkpd-school-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .lkpd-main-title {
            font-size: 16pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0b3d91;
            margin: 6px 0 2px 0;
        }
        .lkpd-sub-title {
            font-size: 11pt;
            font-style: italic;
            color: #444;
        }

        /* Box Identitas Siswa */
        .meta-box {
            border: 1px solid #333;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 18px;
            background: #fafafa;
            font-size: 10.5pt;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 6px;
            vertical-align: middle;
        }
        .dotted-line {
            border-bottom: 1px dotted #555;
            display: inline-block;
            width: 90%;
            height: 14px;
        }

        /* Konten Utama LKPD */
        .lkpd-content {
            font-size: 11pt;
            line-height: 1.5;
        }
        .lkpd-content h3, .lkpd-content h4, .lkpd-content h5 {
            color: #0b3d91;
            margin-top: 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid #dcdcdc;
            padding-bottom: 4px;
        }
        .lkpd-content h3 { font-size: 13pt; }
        .lkpd-content h4 { font-size: 12pt; }
        
        /* Styling SVG & Ilustrasi Visual */
        .lkpd-content svg {
            display: block;
            margin: 12px auto;
            max-width: 100% !important;
            height: auto !important;
            border-radius: 6px;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.06));
        }
        .lkpd-content figure {
            text-align: center;
            margin: 14px 0;
        }
        .lkpd-content figcaption {
            font-size: 9.5pt;
            color: #555;
            font-style: italic;
            margin-top: 4px;
        }

        /* Tabel Observasi / Aktivitas */
        .lkpd-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10.5pt;
        }
        .lkpd-content th, .lkpd-content td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .lkpd-content th {
            background-color: #f1f3f5;
            font-weight: bold;
            text-align: center;
        }

        /* Kotak Jawaban / Catatan Siswa */
        .answer-box {
            border: 1px dashed #666;
            background: #fff;
            min-height: 80px;
            border-radius: 4px;
            margin: 8px 0 14px 0;
            padding: 8px;
            position: relative;
        }
        .answer-box::before {
            content: "Area Jawaban Siswa:";
            display: block;
            font-size: 8.5pt;
            color: #888;
            font-style: italic;
            margin-bottom: 4px;
        }
        .answer-lines {
            background-image: repeating-linear-gradient(transparent, transparent 23px, #ccc 24px);
            line-height: 24px;
            min-height: 96px;
            margin: 8px 0 14px 0;
        }

        /* Kotak Refleksi Diri */
        .reflection-card {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 10px 14px;
            margin-top: 20px;
            border-radius: 0 4px 4px 0;
        }

        /* Footer Tanda Tangan */
        .lkpd-footer-signature {
            margin-top: 30px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
            font-size: 10.5pt;
        }
        .sig-block {
            width: 45%;
            text-align: center;
        }

        /* Print Specific Media Rules */
        @media print {
            body {
                background: #ffffff;
            }
            .no-print, .print-toolbar {
                display: none !important;
            }
            .sheet-container {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            .lkpd-main-title, .lkpd-content h3, .lkpd-content h4 {
                color: #000000 !important;
            }
            .meta-box, .reflection-card {
                background: #ffffff !important;
            }
            .lkpd-content table th {
                background-color: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Kontrol Cetak (Tidak tercetak) -->
    <div class="print-toolbar no-print">
        <div class="toolbar-left">
            <button onclick="window.print()" class="btn-toolbar btn-print">
                🖨️ Cetak LKPD (A4)
            </button>
            <button onclick="window.close()" class="btn-toolbar btn-close-print">
                ✖ Tutup
            </button>
            <span style="color:#555; font-size:12px; margin-left:10px;">
                Pertemuan ke-<?= esc($pertemuan) ?> &bull; <strong><?= esc($subject['name'] ?? 'Mata Pelajaran') ?></strong> (Kelas <?= esc($class['name'] ?? '-') ?>)
            </span>
        </div>
        <div class="toolbar-right">
            <small style="color:#6c757d;">
                💡 Tips: Gunakan opsi cetak <em>Destination: Save as PDF</em> atau cetak langsung ke printer kertas A4.
            </small>
        </div>
    </div>

    <!-- Lembar Kerja Peserta Didik (Container A4) -->
    <div class="sheet-container">
        <!-- Header -->
        <div class="lkpd-header">
            <div class="lkpd-school-title"><?= esc($school['name'] ?? 'SEKOLAH') ?></div>
            <div class="lkpd-main-title">LEMBAR KERJA PESERTA DIDIK (LKPD)</div>
            <div class="lkpd-sub-title">Pertemuan ke-<?= esc($pertemuan) ?>: <?= esc($lkpd['title'] ?? ('Topik Pertemuan ' . $pertemuan)) ?></div>
        </div>

        <!-- Box Identitas Siswa -->
        <div class="meta-box">
            <table class="meta-table">
                <tr>
                    <td width="15%"><strong>Nama Siswa/Kel:</strong></td>
                    <td width="45%"><span class="dotted-line"></span></td>
                    <td width="15%"><strong>Mata Pelajaran:</strong></td>
                    <td width="25%"><?= esc($subject['name'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td><strong>Kelas / Fase:</strong></td>
                    <td><?= esc($class['name'] ?? '-') ?> <?= !empty($atp['fase']) ? ' / Fase ' . esc($atp['fase']) : '' ?></td>
                    <td><strong>Hari / Tgl:</strong></td>
                    <td><span class="dotted-line" style="width: 80%;"></span></td>
                </tr>
                <tr>
                    <td><strong>Lingkup Materi:</strong></td>
                    <td colspan="3"><?= esc($atp['lingkup_materi'] ?? '-') ?></td>
                </tr>
            </table>
        </div>

        <!-- Konten Hasil AI / Input Guru -->
        <div class="lkpd-content">
            <?= $lkpd['content'] ?? '' ?>
        </div>

        <!-- Tanda Tangan Feedback Guru & Nilai -->
        <div class="lkpd-footer-signature">
            <div class="sig-block">
                <div>Catatan / Feedback Guru:</div>
                <div style="border: 1px dotted #888; height: 60px; margin-top: 5px; border-radius: 4px;"></div>
            </div>
            <div class="sig-block" style="text-align: right;">
                <div style="text-align: center; display: inline-block;">
                    <div><?= esc($school['city'] ?? 'Kota') ?>, .............................. 20...</div>
                    <div style="margin-top: 4px;">Guru Mata Pelajaran,</div>
                    <div style="height: 50px;"></div>
                    <div style="font-weight: bold; text-decoration: underline;">
                        <?= esc(session()->get('user')['name'] ?? 'Guru Pengampu') ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

</body>
</html>
