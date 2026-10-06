<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rekap Absensi Pembina Ekskul - Bulan <?= esc($monthName) ?> <?= $year ?></title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11pt;
            color: #000;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 8px;
            margin-bottom: 16px;
        }
        .header h2 {
            margin: 0;
            font-size: 14pt;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 4px 0;
            font-size: 12pt;
        }
        .header p {
            margin: 2px 0;
            font-size: 9pt;
            color: #333;
        }
        .title-doc {
            text-align: center;
            margin-bottom: 15px;
        }
        .title-doc h4 {
            margin: 0;
            font-size: 12pt;
            text-transform: uppercase;
            text-decoration: underline;
        }
        .title-doc span {
            font-size: 10pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 10pt;
        }
        th {
            background-color: #f2f2f2;
            text-align: center;
            font-weight: bold;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .ttd-section {
            margin-top: 35px;
            display: table;
            width: 100%;
            page-break-inside: avoid;
        }
        .ttd-col {
            display: table-cell;
            width: 50%;
            text-align: center;
        }
        .ttd-space {
            height: 60px;
        }
        @media print {
            body { margin: 10mm; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak Manual jika tidak auto -->
    <div class="no-print" style="margin-bottom: 15px;">
        <button onclick="window.print()" style="padding: 8px 16px; font-size: 11pt; cursor: pointer; background: #007bff; color: #fff; border: none; border-radius: 4px;">
            🖨️ Cetak Dokumen
        </button>
        <button onclick="window.close()" style="padding: 8px 16px; font-size: 11pt; cursor: pointer; background: #6c757d; color: #fff; border: none; border-radius: 4px; margin-left: 5px;">
            Tutup
        </button>
    </div>

    <!-- Kop Sekolah -->
    <div class="header">
        <h2><?= esc(strtoupper($school['name'] ?? 'SEKOLAH')) ?></h2>
        <p><?= esc($school['address'] ?? '') ?> | Telp: <?= esc($school['phone'] ?? '-') ?> | Email: <?= esc($school['email'] ?? '-') ?></p>
    </div>

    <!-- Judul Dokumen -->
    <div class="title-doc">
        <h4>REKAPITULASI KEHADIRAN PEMBINA & PELATIH EKSTRAKURIKULER</h4>
        <span>Periode: <strong>Bulan <?= esc($monthName) ?> <?= $year ?></strong> — Tahun Ajaran <strong><?= esc($activeYear['year'] ?? '-') ?></strong></span>
    </div>

    <!-- Tabel Rekap -->
    <table>
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="24%">Nama Pembina / Pelatih</th>
                <th width="15%">NIP / Identitas</th>
                <th width="20%">Cabang Ekstrakurikuler</th>
                <th width="15%">Kategori</th>
                <th width="12%">Kehadiran Sah (Kali)</th>
                <th width="10%">Paraf</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($rekapPembina)): ?>
                <tr>
                    <td colspan="7" class="text-center" style="padding: 15px;">Tidak ada data kehadiran pembina di periode ini.</td>
                </tr>
            <?php else: ?>
                <?php $totalHonor = 0; foreach ($rekapPembina as $idx => $r): 
                    $totalHonor += $r['verified_count'];
                ?>
                    <tr>
                        <td class="text-center"><?= $idx + 1 ?></td>
                        <td><strong><?= esc($r['pembina_name']) ?></strong></td>
                        <td class="text-center"><?= esc($r['nip']) ?></td>
                        <td><?= esc($r['ekskul_name']) ?></td>
                        <td class="text-center"><?= esc($r['is_external']) ?></td>
                        <td class="text-center font-weight-bold" style="font-weight: bold; font-size: 11pt;">
                            <?= $r['verified_count'] ?> kali
                        </td>
                        <td></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <th colspan="5" class="text-right" style="padding: 8px;">TOTAL PERTEMUAN TERVERIFIKASI:</th>
                    <th class="text-center" style="font-size: 11pt;"><?= $totalHonor ?> Pertemuan</th>
                    <th></th>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="font-size: 9pt; margin-top: 10px; font-style: italic;">
        * Rekapitulasi kehadiran di atas telah diverifikasi secara administratif berdasarkan jurnal kegiatan riil dan presensi siswa yang diselenggarakan.
    </p>

    <!-- Tanda Tangan -->
    <div class="ttd-section">
        <div class="ttd-col">
            <div>Mengetahui,</div>
            <div style="font-weight: bold;">Kepala Sekolah</div>
            <div class="ttd-space"></div>
            <div style="font-weight: bold; text-decoration: underline;"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></div>
            <div>NIP. <?= esc($school['principal_nip'] ?? '-') ?></div>
        </div>
        <div class="ttd-col">
            <div><?= esc($school['city_regency'] ?? 'Kota') ?>, <?= date('d F Y') ?></div>
            <div style="font-weight: bold;">Koordinator Ekstrakurikuler / Bendahara</div>
            <div class="ttd-space"></div>
            <div style="font-weight: bold; text-decoration: underline;">( ...................................................... )</div>
            <div>NIP. -</div>
        </div>
    </div>

    <script>
        window.onload = function() {
            // Auto open print dialog
            window.print();
        };
    </script>
</body>
</html>
