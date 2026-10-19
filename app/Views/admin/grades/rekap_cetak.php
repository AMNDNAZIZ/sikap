<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= esc($title) ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; font-size: 9pt; }

        .header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #000; padding-bottom: 8px; }
        .header h2 { font-size: 13pt; font-weight: bold; }
        .header p  { font-size: 9pt; margin-top: 3px; }

        table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        th, td { border: 1px solid #000; padding: 3px 4px; vertical-align: middle; }
        thead th { background: #e8e8e8; text-align: center; font-weight: bold; }
        td.center { text-align: center; }
        td.score  { text-align: center; }
        tfoot td  { background: #fff8dc; font-weight: bold; }

        .legend { margin-top: 8px; font-size: 8pt; }
        .no-print { padding: 10px; }
        .btn-print { padding: 8px 18px; background: #0d6efd; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 10pt; }
        .btn-back  { padding: 8px 18px; background: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 10pt; text-decoration: none; margin-right: 6px; }

        .signature-section { margin-top: 25px; page-break-inside: avoid; break-inside: avoid; }
        table.signature-table { width: 100%; border: none !important; font-size: 9pt; margin-top: 10px; }
        table.signature-table td { border: none !important; width: 50%; text-align: center; vertical-align: top; padding: 0 20px; }
        .signature-space { height: 55px; }
        .signature-img { max-height: 50px; max-width: 140px; display: block; margin: 0 auto; }
        .signature-name { font-weight: bold; text-decoration: underline; font-size: 9.5pt; }
        .signature-nip { font-size: 8.5pt; margin-top: 2px; }

        @media print {
            .no-print { display: none; }
            body { margin: 8mm; }
        }
    </style>
</head>
<body>

<?php
function fmt($val) {
    return $val !== null && $val !== '' ? number_format((float)$val, 2) : null;
}
?>

<div class="no-print">
    <a href="javascript:history.back()" class="btn-back">← Kembali</a>
    <button class="btn-print" onclick="window.print()">🖨️ Cetak</button>
</div>

<?php $rekap = $rekap; ?>

<div class="header">
    <h2>REKAP NILAI SISWA</h2>
    <p>
        Kelas <?= esc($rekap['class']['name']) ?> &bull;
        Tahun Ajaran <?= esc($rekap['year']['year']) ?> &bull;
        Semester <?= $semester ?> &bull;
        <?= esc($rekap['score_type_label']) ?>
    </p>
</div>

<?php if (!empty($rekap['students']) && !empty($rekap['subjects'])): ?>
<table>
    <thead>
        <tr>
            <th width="3%">No</th>
            <th width="9%">NIS</th>
            <th width="20%">Nama Siswa</th>
            <?php foreach ($rekap['subjects'] as $subj): ?>
                <th title="<?= esc($subj['name']) ?>"><?= esc($subj['code']) ?></th>
            <?php endforeach; ?>
            <th>Jml</th>
            <th>Rata</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($rekap['students'] as $i => $student): ?>
        <tr>
            <td class="center"><?= $i + 1 ?></td>
            <td class="center"><?= esc($student['nis']) ?></td>
            <td><?= esc($student['name']) ?></td>
            <?php foreach ($rekap['subjects'] as $subj): ?>
                <?php $val = $rekap['scores'][$student['id']][$subj['id']] ?? null; ?>
                <td class="score"><?= $val !== null ? number_format((float)$val, 2) : '-' ?></td>
            <?php endforeach; ?>
            <td class="score"><?= $student['row_total'] !== null ? number_format((float)$student['row_total'], 2) : '-' ?></td>
            <td class="score"><?= $student['row_avg'] !== null ? number_format((float)$student['row_avg'], 2) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right;">Rata-rata Kelas</td>
            <?php
            $grandSum = 0; $grandCount = 0;
            foreach ($rekap['subjects'] as $subj):
                $avg = $rekap['col_avg'][$subj['id']] ?? null;
                if ($avg !== null) { $grandSum += $avg; $grandCount++; }
            ?>
                <td class="score"><?= $avg !== null ? number_format((float)$avg, 2) : '-' ?></td>
            <?php endforeach; ?>
            <td class="score"><?= $grandCount > 0 ? number_format($grandSum, 2) : '-' ?></td>
            <td class="score"><?= $grandCount > 0 ? number_format($grandSum / $grandCount, 2) : '-' ?></td>
        </tr>
    </tfoot>
</table>

<div class="legend">
    <strong>Keterangan:</strong>
    <?php foreach ($rekap['subjects'] as $i => $subj): ?>
        <?= esc($subj['code']) ?> = <?= esc($subj['name']) ?><?= $i < count($rekap['subjects']) - 1 ? ' &bull; ' : '' ?>
    <?php endforeach; ?>
</div>

<!-- TANDA TANGAN -->
<?php
    $kota = !empty($school['city_regency']) ? $school['city_regency'] : (!empty($school['city']) ? $school['city'] : 'Kota/Kab.');
    $kepsekNama = !empty($school['headmaster']) ? $school['headmaster'] : 'Kepala Sekolah';
    $kepsekNip  = !empty($school['principal_nip']) ? $school['principal_nip'] : '-';
    $waliNama   = !empty($waliKelas['name']) ? $waliKelas['name'] : 'Wali Kelas';
    $waliNip    = !empty($waliKelas['nip']) ? $waliKelas['nip'] : '-';
?>
<div class="signature-section">
    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Kepala Sekolah</strong>
                <div class="signature-space">
                    <?php if (!empty($principalSigBase64)): ?>
                        <img src="<?= $principalSigBase64 ?>" alt="TTD Kepala Sekolah" class="signature-img">
                    <?php endif; ?>
                </div>
                <div class="signature-name"><?= esc($kepsekNama) ?></div>
                <div class="signature-nip">NIP. <?= esc($kepsekNip) ?></div>
            </td>
            <td>
                <?= esc($kota) ?>, <?= esc($titimangsaDate ?? date('d F Y')) ?><br>
                <strong>Wali Kelas <?= esc($rekap['class']['name'] ?? '') ?></strong>
                <div class="signature-space">
                    <?php if (!empty($waliSigBase64)): ?>
                        <img src="<?= $waliSigBase64 ?>" alt="TTD Wali Kelas" class="signature-img">
                    <?php endif; ?>
                </div>
                <div class="signature-name"><?= esc($waliNama) ?></div>
                <div class="signature-nip">NIP. <?= esc($waliNip) ?></div>
            </td>
        </tr>
    </table>
</div>

<div style="margin-top: 20px; text-align: right; font-size: 8pt;">
    Dicetak: <?= date('d/m/Y H:i') ?>
</div>

<?php else: ?>
<p>Tidak ada data.</p>
<?php endif; ?>

</body>
</html>
