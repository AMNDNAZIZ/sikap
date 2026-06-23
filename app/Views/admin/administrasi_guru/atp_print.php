<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak ATP - <?= esc($subject['name']) ?></title>
    <style>
        @page {
            size: landscape;
            margin: 1.2cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10.5pt;
            line-height: 1.4;
            color: #000;
        }
        .no-print {
            background: #f8f9fa;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            text-decoration: underline;
            margin-bottom: 16px;
        }
        .meta-table {
            width: auto;
            margin-bottom: 16px;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 2px 4px;
            vertical-align: top;
        }
        .meta-table td:first-child { width: 130px; }
        .meta-table td:nth-child(2) { width: 10px; }

        /* Main table */
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .main-table th,
        .main-table td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }
        .main-table thead th {
            background-color: #dde8f0;
            text-align: center;
            font-size: 10pt;
        }
        /* Sub-header row untuk elemen CP */
        .row-elemen td {
            background-color: #f5f9fc;
        }
        .row-elemen td.elemen-label {
            font-weight: bold;
            color: #1a5276;
        }

        .text-center  { text-align: center; }
        .text-justify { text-align: justify; }
        .fw-bold      { font-weight: bold; }
        .align-top    { vertical-align: top; }
        .align-middle { vertical-align: middle; }
        .small        { font-size: 9pt; }

        .footer-table {
            width: 100%;
            margin-top: 40px;
            border-collapse: collapse;
        }
        .footer-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 20px;
        }
        .signature-space { height: 70px; }

        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()" style="padding:5px 15px;cursor:pointer;">🖨️ Cetak Sekarang</button>
        <button onclick="window.close()" style="padding:5px 15px;cursor:pointer;margin-left:10px;">❌ Tutup</button>
    </div>

    <div class="title">ALUR TUJUAN PEMBELAJARAN (ATP)</div>

    <table class="meta-table">
        <tr>
            <td>Nama Sekolah</td><td>:</td>
            <td><strong><?= esc($school['name'] ?? '-') ?></strong></td>
        </tr>
        <tr>
            <td>Mata Pelajaran</td><td>:</td>
            <td><?= esc($subject['name']) ?></td>
        </tr>
        <tr>
            <td>Fase</td><td>:</td>
            <td>Fase <?= esc($fase) ?></td>
        </tr>
        <tr>
            <td>Kelas</td><td>:</td>
            <td>Kelas <?= esc($kelas) ?></td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="28" rowspan="2">No</th>
                <th width="160" rowspan="2">Lingkup Materi</th>
                <th colspan="2">Elemen Capaian Pembelajaran</th>
                <th width="55" rowspan="2">Smtr</th>
                <th width="70" rowspan="2">Alokasi<br>Waktu</th>
            </tr>
            <tr>
                <th width="130">Elemen</th>
                <th>Tujuan Pembelajaran (TP)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($atp_list)): ?>
                <tr><td colspan="6" class="text-center">Data tidak tersedia.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($atp_list as $atp):
                    $elemenList = $atp['elemen_list'] ?? [];
                    $jumlahElemen = count($elemenList) ?: 1;
                ?>

                    <?php if (empty($elemenList)): ?>
                        {{-- Fallback: tidak ada elemen --}}
                        <tr>
                            <td class="text-center align-middle"><?= $no++ ?></td>
                            <td class="align-middle"><?= esc($atp['lingkup_materi']) ?></td>
                            <td class="text-center" colspan="2">-</td>
                            <td class="text-center align-middle"><?= $atp['semester'] ?></td>
                            <td class="text-center align-middle fw-bold"><?= esc($atp['alokasi_waktu']) ?> JP</td>
                        </tr>
                    <?php else: ?>

                        <?php foreach ($elemenList as $ei => $el):
                            $tps = $el['tps'] ?? [];
                            $isFirst = ($ei === 0);
                        ?>
                            <tr>
                                <?php if ($isFirst): ?>
                                    <td rowspan="<?= $jumlahElemen ?>" class="text-center align-middle fw-bold">
                                        <?= $no++ ?>
                                    </td>
                                    <td rowspan="<?= $jumlahElemen ?>" class="align-top" style="font-size:10pt;">
                                        <?= esc($atp['lingkup_materi']) ?>
                                    </td>
                                <?php endif; ?>

                                <!-- Kolom Elemen CP -->
                                <td class="align-top" style="font-size:9.5pt;">
                                    <strong><?= esc($el['elemen']) ?></strong>
                                </td>

                                <!-- Kolom TP -->
                                <td class="align-top" style="font-size:10pt;">
                                    <?php if (!empty($tps)): ?>
                                        <?php foreach ($tps as $tp): ?>
                                            <div style="margin-bottom:3px;">
                                                <strong><?= esc($tp['kode_tp']) ?></strong>
                                                <?= esc($tp['deskripsi']) ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <span style="color:#999;">-</span>
                                    <?php endif; ?>
                                </td>

                                <?php if ($isFirst): ?>
                                    <td rowspan="<?= $jumlahElemen ?>" class="text-center align-middle fw-bold">
                                        <?= $atp['semester'] ?>
                                    </td>
                                    <td rowspan="<?= $jumlahElemen ?>" class="text-center align-middle fw-bold">
                                        <?= esc($atp['alokasi_waktu']) ?> JP
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <table class="footer-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah
                <div class="signature-space"></div>
                <strong><?= esc($school['headmaster'] ?? '-') ?></strong><br>
                NIP. <?= esc($school['principal_nip'] ?? '-') ?>
            </td>
            <td>
                <?= esc($school['city_regency'] ?? '-') ?>, <?= date('d F Y') ?><br>
                Guru Kelas / Mata Pelajaran
                <div class="signature-space"></div>
                <strong><?= esc($teacher['name'] ?? '-') ?></strong><br>
                NIP. <?= esc($teacher['nip'] ?? '-') ?>
            </td>
        </tr>
    </table>

</body>
</html>
