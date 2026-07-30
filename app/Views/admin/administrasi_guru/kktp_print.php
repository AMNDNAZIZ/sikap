<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>KKTP - <?= esc($subject['name']) ?></title>
    <style>
        @page { size: landscape; margin: 1.5cm; }
        body { font-family: Arial, sans-serif; font-size: 10.5pt; line-height: 1.4; color: #000; }
        .no-print { background: #f8f9fa; padding: 10px; border-bottom: 1px solid #ddd; margin-bottom: 20px; }
        .title { text-align: center; font-weight: bold; font-size: 14pt; text-decoration: underline; margin-bottom: 16px; }
        .meta-table { width: auto; margin-bottom: 16px; border-collapse: collapse; }
        .meta-table td { padding: 2px 4px; vertical-align: top; }
        .meta-table td:first-child { width: 140px; }
        .meta-table td:nth-child(2) { width: 10px; }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 6px 8px; vertical-align: middle; }
        .main-table thead th { background-color: #dde8f0; text-align: center; font-size: 10pt; }
        .text-center { text-align: center; }
        .fw-bold { font-weight: bold; }
        .small { font-size: 9pt; }
        .footer-table { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .footer-table td { padding: 4px; vertical-align: top; width: 50%; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Cetak</button>
        <button onclick="window.close()" class="btn btn-secondary">✖ Tutup</button>
    </div>

    <div class="title">KRITERIA KETERCAPAIAN TUJUAN PEMBELAJARAN (KKTP)</div>

    <table class="meta-table">
        <tr><td>Satuan Pendidikan</td><td>:</td><td><?= esc($school['name'] ?? '-') ?></td></tr>
        <tr><td>Mata Pelajaran</td><td>:</td><td><?= esc($subject['name'] ?? '-') ?></td></tr>
        <tr><td>Kelas / Semester</td><td>:</td><td><?= esc($classInfo['name'] ?? '-') ?></td></tr>
        <tr><td>Guru Pengampu</td><td>:</td><td><?= esc($teacher['name'] ?? '-') ?></td></tr>
        <tr><td>Tahun Pelajaran</td><td>:</td><td><?= date('Y') ?>/<?= date('Y') + 1 ?></td></tr>
    </table>

    <?php if (!empty($kktpList)): ?>
    <table class="main-table">
        <thead>
            <tr>
                <th width="40">No</th>
                <th width="30%">Tujuan Pembelajaran (TP)</th>
                <th colspan="3">Kriteria Ketercapaian</th>
            </tr>
            <tr>
                <th></th>
                <th></th>
                <th width="15%">Interval Nilai</th>
                <th width="15%">Kriteria</th>
                <th width="25%">Intervensi</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kktpList as $i => $k): ?>
            <tr>
                <td class="text-center" rowspan="4"><?= $i + 1 ?></td>
                <td rowspan="4"><?= esc($k['tujuan_pembelajaran']) ?></td>
                <td class="text-center"><?= esc($k['kriteria_1_interval']) ?></td>
                <td><?= esc($k['kriteria_1_label']) ?></td>
                <td><?= esc($k['kriteria_1_intervensi']) ?></td>
            </tr>
            <tr>
                <td class="text-center"><?= esc($k['kriteria_2_interval']) ?></td>
                <td><?= esc($k['kriteria_2_label']) ?></td>
                <td><?= esc($k['kriteria_2_intervensi']) ?></td>
            </tr>
            <tr>
                <td class="text-center"><?= esc($k['kriteria_3_interval']) ?></td>
                <td><?= esc($k['kriteria_3_label']) ?></td>
                <td><?= esc($k['kriteria_3_intervensi']) ?></td>
            </tr>
            <tr>
                <td class="text-center"><?= esc($k['kriteria_4_interval']) ?></td>
                <td><?= esc($k['kriteria_4_label']) ?></td>
                <td><?= esc($k['kriteria_4_intervensi']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <p><em>Belum ada data KKTP.</em></p>
    <?php endif; ?>

    <!-- Tanda Tangan -->
    <table class="footer-table">
        <tr>
            <td>
                Mengetahui,<br>
                Kepala Sekolah<br><br><br><br>
                <u><?= esc($school['headmaster'] ?? '............................') ?></u><br>
                NIP. <?= esc($school['principal_nip'] ?? '............................' ) ?>
            </td>
            <td style="text-align: right;">
                <?= esc($school['city_regency'] ?? '............') ?>, .........................<br>
                Guru Mata Pelajaran<br><br><br><br>
                <u><?= esc($teacher['name'] ?? '............................') ?></u><br>
                NIP. <?= esc($teacher['nip'] ?? '............................' ) ?>
            </td>
        </tr>
    </table>
</body>
</html>
