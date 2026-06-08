<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Prosem - <?= esc($subject['name']) ?> - Semester <?= $semester ?></title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; line-height: 1.1; color: #000; margin: 0; padding: 15px; }
        p { margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mb-0 { margin-bottom: 0; }
        .mb-4 { margin-bottom: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-bordered { border: 1px solid #000; }
        .table-bordered th, .table-bordered td { border: 1px solid #000; padding: 2px 2px; }
        .table-borderless th, .table-borderless td { border: none !important; padding: 1px 4px; }
        
        .bg-light { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        .small { font-size: 8pt; }
        .text-uppercase { text-transform: uppercase; }
        .text-decoration-underline { text-decoration: underline; }
        
        @media print {
            @page { size: landscape; margin: 0.5cm; }
            .no-print { display: none; }
            body { padding: 0; }
        }

        .header-section { margin-bottom: 15px; text-align: center; }
        .header-section h3 { margin: 0; font-size: 14pt; }
        
        .signature-row { display: flex; justify-content: space-between; margin-top: 30px; }
        .signature-box { width: 35%; text-align: center; }
        
        .jp-cell { width: 18px; text-align:center; font-size: 8pt; }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background: #ffc; padding: 10px; border: 1px solid #cc0; margin-bottom: 10px; text-align: center;">
        <button onclick="window.print()" style="padding: 5px 15px; cursor: pointer;">Print Sekarang</button>
        <p style="margin: 5px 0 0 0; font-size: 0.8em;">Gunakan pengaturan <b>Landscape</b> pada jendela print.</p>
    </div>

    <!-- Document Header -->
    <div class="header-section">
        <h3>PROGRAM SEMESTER (PROSEM)</h3>
        <div class="fw-bold text-uppercase">TAHUN PELAJARAN <?= date('Y') ?>/<?= date('Y') + 1 ?></div>
    </div>

    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
        <div style="width: 40%;">
            <table class="table-borderless small">
                <tr>
                    <td width="120">Satuan Pendidikan</td>
                    <td width="10">:</td>
                    <td class="fw-bold"><?= esc($school['name'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td>Mata Pelajaran</td>
                    <td>:</td>
                    <td class="fw-bold"><?= esc($subject['name']) ?></td>
                </tr>
            </table>
        </div>
        <div style="width: 30%;">
            <table class="table-borderless small">
                <tr>
                    <td width="100">Kelas / Fase</td>
                    <td width="10">:</td>
                    <td class="fw-bold"><?= esc($kelas) ?> / <?= esc($fase) ?></td>
                </tr>
                <tr>
                    <td>Semester</td>
                    <td>:</td>
                    <td class="fw-bold"><?= $semester ?> (<?= $semester == 1 ? 'GANJIL' : 'GENAP' ?>)</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="table-bordered">
        <thead class="bg-light text-center small fw-bold">
            <tr>
                <th rowspan="2" width="30">NO</th>
                <th rowspan="2">LINGKUP MATERI / TUJUAN PEMBELAJARAN</th>
                <th rowspan="2" width="40">JML JP</th>
                <?php foreach ($months as $mCode => $mName): ?>
                    <th colspan="5"><?= $mName ?></th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($months as $mCode => $mName): ?>
                    <?php for($w=1; $w<=5; $w++): ?><th class="jp-cell"><?= $w ?></th><?php endfor; ?>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($atp_list as $atp): ?>
                <?php $tps = $atp['tps'] ?? []; ?>
                <tr>
                    <td class="text-center" valign="top"><?= $no++ ?></td>
                    <td style="padding: 2px 5px;">
                        <div class="fw-bold"><?= esc($atp['lingkup_materi']) ?></div>
                        <div style="font-size: 8pt; margin-top: 2px;">
                            <?php foreach ($tps as $tp): ?>
                                • <?= esc($tp['kode_tp']) ?> <?= esc($tp['deskripsi']) ?><br>
                            <?php endforeach; ?>
                        </div>
                    </td>
                    <td class="text-center fw-bold"><?= $atp['alokasi_waktu'] ?></td>
                    <?php foreach ($months as $mCode => $mName): ?>
                        <?php for ($w = 1; $w <= 5; $w++): ?>
                            <td class="jp-cell fw-bold">
                                <?= $atp['distributions'][$mCode][$w] ?? '' ?>
                            </td>
                        <?php endfor; ?>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="signature-row">
        <div class="signature-box">
            <p class="small">Mengetahui,</p>
            <p class="small">Kepala Sekolah</p>
            <br><br>
            <p class="fw-bold text-decoration-underline"><?= esc($school['headmaster'] ?? '-') ?></p>
            <p class="small">NIP. <?= esc($school['principal_nip'] ?? '-') ?></p>
        </div>
        <div class="signature-box">
            <p class="small"><?= esc($school['city_regency'] ?? 'Indramayu') ?>, <?= date('d F Y') ?></p>
            <p class="small">Guru Mata Pelajaran</p>
            <br><br>
            <p class="fw-bold text-decoration-underline"><?= esc($teacher['name'] ?? '-') ?></p>
            <p class="small">NIP. <?= esc($teacher['nip'] ?? '-') ?></p>
        </div>
    </div>

</body>
</html>
