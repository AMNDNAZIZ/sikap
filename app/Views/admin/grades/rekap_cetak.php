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

<div style="margin-top: 30px; text-align: right; font-size: 8pt;">
    Dicetak: <?= date('d/m/Y H:i') ?>
</div>

<?php else: ?>
<p>Tidak ada data.</p>
<?php endif; ?>

</body>
</html>
