<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Laporan Nilai</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background: #f2f2f2; }
  </style>
</head>
<body>
  <h3>Laporan Nilai - Semester <?= esc($semester) ?></h3>
  <p>Tahun Ajaran: <?= esc($activeYear['year']) ?></p>

  <table>
    <thead>
      <tr>
        <th rowspan="2">#</th>
        <th rowspan="2">Nama Siswa</th>

        <?php if (!empty($allFormatifCols[$semester])): ?>
          <th colspan="<?= count($allFormatifCols[$semester]) ?>">Nilai Formatif</th>
        <?php endif; ?>

        <?php if (!empty($allSumatifCols[$semester])): ?>
          <th colspan="<?= count($allSumatifCols[$semester]) ?>">Nilai Sumatif</th>
        <?php endif; ?>

        <?php if ($semester == 2 && $hasFinal): ?>
          <th rowspan="2">Final</th>
        <?php endif; ?>
      </tr>
      <tr>
        <?php if (!empty($allFormatifCols[$semester])): ?>
          <?php foreach ($allFormatifCols[$semester] as $label): ?>
            <th><?= esc($label) ?></th>
          <?php endforeach ?>
        <?php endif; ?>

        <?php if (!empty($allSumatifCols[$semester])): ?>
          <?php foreach ($allSumatifCols[$semester] as $label): ?>
            <th><?= esc($label) ?></th>
          <?php endforeach ?>
        <?php endif; ?>
      </tr>
    </thead>
    <tbody>
      <?php $no=1; foreach ($students as $st): ?>
        <tr>
          <td><?= $no++ ?></td>
          <td style="text-align:left"><?= esc($st['name']) ?></td>

          <?php if (!empty($allFormatifCols[$semester])): ?>
            <?php foreach ($allFormatifCols[$semester] as $colKey => $label): ?>
              <td><?= esc($grades[$semester][$st['id']]['formatif'][$colKey] ?? '-') ?></td>
            <?php endforeach ?>
          <?php endif; ?>

          <?php if (!empty($allSumatifCols[$semester])): ?>
            <?php foreach ($allSumatifCols[$semester] as $colKey => $label): ?>
              <td><?= esc($grades[$semester][$st['id']]['sumatif'][$colKey] ?? '-') ?></td>
            <?php endforeach ?>
          <?php endif; ?>

          <?php if ($semester == 2 && $hasFinal): ?>
            <td><?= esc($grades[$semester][$st['id']]['final'] ?? '-') ?></td>
          <?php endif; ?>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</body>
</html>
