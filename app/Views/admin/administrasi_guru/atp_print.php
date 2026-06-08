<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cetak ATP - <?= esc($subject['name']) ?></title>
    <style>
        @page {
            size: landscape;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.4;
            color: #000;
        }
        .header-table {
            width: 100%;
            margin-bottom: 20px;
        }
        .header-table td {
            vertical-align: top;
        }
        .title {
            text-align: center;
            font-weight: bold;
            font-size: 14pt;
            text-decoration: underline;
            margin-bottom: 20px;
        }
        .meta-table {
            width: 50%;
        }
        .meta-table td {
            padding: 2px 0;
        }
        .main-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .main-table th, .main-table td {
            border: 1px solid #000;
            padding: 6px;
        }
        .main-table th {
            background-color: #f2f2f2;
            text-align: center;
        }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .align-middle { vertical-align: middle; }
        
        .footer-table {
            width: 100%;
            margin-top: 40px;
        }
        .footer-table td {
            width: 50%;
            text-align: center;
        }
        .signature-space {
            height: 80px;
        }
        
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd; margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer;">🖨️ Cetak Sekarang</button>
        <button onclick="window.close()" style="padding: 5px 15px; cursor: pointer; margin-left: 10px;">❌ Tutup</button>
    </div>

    <div class="title">ALUR TUJUAN PEMBELAJARAN (ATP)</div>

    <table class="header-table">
        <tr>
            <td width="100%">
                <table class="meta-table">
                    <tr>
                        <td width="120">Nama Sekolah</td>
                        <td width="10">:</td>
                        <td class="fw-bold"><?= esc($school['name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td>Mata Pelajaran</td>
                        <td>:</td>
                        <td><?= esc($subject['name']) ?></td>
                    </tr>
                    <tr>
                        <td>Fase</td>
                        <td>:</td>
                        <td>Fase <?= esc($fase) ?></td>
                    </tr>
                    <tr>
                        <td>Kelas</td>
                        <td>:</td>
                        <td>Kelas <?= esc($kelas) ?></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th width="30">No</th>
                <th width="120">Elemen CP</th>
                <th width="250">Capaian Pembelajaran (CP)</th>
                <th width="150">Lingkup Materi</th>
                <th>Tujuan Pembelajaran (TP)</th>
                <th width="80">Alokasi Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($atp_list)): ?>
                <tr><td colspan="6" class="text-center">Data tidak tersedia.</td></tr>
            <?php else: ?>
                <?php $no = 1; foreach ($atp_list as $atp): ?>
                    <?php 
                    $tps = $atp['tps'] ?? [];
                    $rowCount = count($tps) ?: 1;
                    ?>
                    <tr>
                        <td rowspan="<?= $rowCount ?>" class="text-center align-middle"><?= $no++ ?></td>
                        <td rowspan="<?= $rowCount ?>" class="align-middle" style="font-size: 10pt;">
                            <strong><?= esc($atp['elemen']) ?></strong>
                        </td>
                        <td rowspan="<?= $rowCount ?>" class="align-middle">
                            <div style="font-size: 9pt; text-align: justify;">
                                <?= nl2br(esc($atp['cp_deskripsi'])) ?>
                            </div>
                        </td>
                        <td rowspan="<?= $rowCount ?>" class="text-center align-middle" style="font-size: 10pt;"><?= esc($atp['lingkup_materi']) ?></td>
                        
                        <?php if (!empty($tps)): ?>
                            <td class="align-middle" style="font-size: 10pt;">
                                <strong><?= esc($tps[0]['kode_tp']) ?></strong> <?= esc($tps[0]['deskripsi']) ?>
                            </td>
                        <?php else: ?>
                            <td class="text-center">-</td>
                        <?php endif; ?>

                        <td rowspan="<?= $rowCount ?>" class="text-center align-middle fw-bold"><?= esc($atp['alokasi_waktu']) ?> JP</td>
                    </tr>
                    
                    <?php for ($i = 1; $i < $rowCount; $i++): ?>
                        <tr>
                            <td class="align-middle" style="font-size: 10pt;">
                                <strong><?= esc($tps[$i]['kode_tp']) ?></strong> <?= esc($tps[$i]['deskripsi']) ?>
                            </td>
                        </tr>
                    <?php endfor; ?>
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
                <?= esc($school['city_regency'] ?? 'Indramayu') ?>, <?= date('d F Y') ?><br>
                Guru Kelas/Mapel
                <div class="signature-space"></div>
                <strong><?= esc($teacher['name'] ?? '-') ?></strong><br>
                NIP. <?= esc($teacher['nip'] ?? '-') ?>
            </td>
        </tr>
    </table>
</body>
</html>
