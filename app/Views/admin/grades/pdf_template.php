<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Daftar Nilai</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      font-size: 12px;
    }
    h4 {
      margin-bottom: 5px;
    }
    table {
      border-collapse: collapse;
      width: 100%;
    }
    table.info td {
      border: none;
      padding: 2px 4px;
    }
    .table {
      border: 1px solid #000;
      width: 100%;
      border-collapse: collapse;
    }
    .table th, .table td {
      border: 1px solid #000;
      padding: 4px;
      text-align: center;
    }
    .bg-success-subtle { background: #c7f5c4; }
    .bg-warning-subtle { background: #ffe599; }
    .bg-info-subtle { background: #cfe2f3; }
    .bg-danger-subtle { background: #f9cb9c; }
    .fw-bold { font-weight: bold; }
    .text-start { text-align: left; }
  </style>
</head>
<body>

<h4>DAFTAR NILAI</h4>

<table class="info">
  <tr>
    <td style="width:150px;">Tahun Ajaran</td>
    <td>: <?= esc($yearName) ?></td>
  </tr>
  <tr>
    <td>Kelas</td>
    <td>: <?= esc($class['name']) ?></td>
  </tr>
  <tr>
    <td>Mata Pelajaran</td>
    <td>: <?= esc($subject['name']) ?></td>
  </tr>
</table>

<br>

<table class="table">
  <thead>
    <tr>
      <th rowspan="2">No</th>
      <th rowspan="2">Nama Siswa</th>

      <?php
      $totalFormCols = 0;
      foreach ($visibleMaterials as $m) {
        $totalFormCols += count($formatifMethods[$m['id']] ?? []);
      }
      if ($totalFormCols > 0) {
        echo '<th colspan="' . ($totalFormCols + 1) . '">Formatif</th>';
      }

      $totalSumCols = count($sumatifMethods);
      if ($totalSumCols > 0) {
        echo '<th colspan="' . ($totalSumCols + 1) . '">Sumatif</th>';
      }
      ?>
      <th rowspan="2">Nilai Rapor</th>
      <?php if ($semester == 2 && $hasFinal): ?>
        <th rowspan="2">Nilai Final</th>
      <?php endif; ?>
    </tr>

    <tr>
      <!-- Formatif sub-columns -->
      <?php foreach ($visibleMaterials as $idx => $m): ?>
        <?php foreach ($formatifMethods[$m['id']] as $method): ?>
          <th>M<?= $idx + 1 ?> (<?= esc($method) ?>)</th>
        <?php endforeach; ?>
      <?php endforeach; ?>
      <?php if ($totalFormCols > 0): ?>
        <th>Rerata</th>
      <?php endif; ?>

      <!-- Sumatif sub-columns -->
      <?php foreach ($sumatifMethods as $method): ?>
        <th><?= ucfirst(esc($method)) ?></th>
      <?php endforeach; ?>
      <?php if ($totalSumCols > 0): ?>
        <th>Rerata</th>
      <?php endif; ?>
    </tr>
  </thead>

  <tbody>
    <?php foreach ($students as $i => $stu): ?>
      <?php $sid = $stu['id']; ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td class="text-start" style="text-align:left;"><?= esc($stu['name']) ?></td>

        <!-- Formatif -->
        <?php foreach ($visibleMaterials as $m): ?>
          <?php foreach ($formatifMethods[$m['id']] as $method): ?>
            <?php $val = $scores[$sid][$semester]['formatif'][$m['id']][$method] ?? ''; ?>
            <td><?= $val !== '' ? esc(number_format((float)$val, 2)) : '' ?></td>
          <?php endforeach; ?>
        <?php endforeach; ?>
        <?php if ($totalFormCols > 0): ?>
          <?php $favg = $scores[$sid][$semester]['formatif_avg'] ?? ''; ?>
          <td class="bg-success-subtle fw-bold">
            <?= $favg !== '' ? esc(number_format((float)$favg, 2)) : '' ?>
          </td>
        <?php endif; ?>

        <!-- Sumatif -->
        <?php foreach ($sumatifMethods as $method): ?>
          <?php $val = $scores[$sid][$semester]['sumatif'][ucfirst($method)] ?? ''; ?>
          <td><?= $val !== '' ? esc(number_format((float)$val, 2)) : '' ?></td>
        <?php endforeach; ?>
        <?php if ($totalSumCols > 0): ?>
          <?php $savg = $scores[$sid][$semester]['sumatif_avg'] ?? ''; ?>
          <td class="bg-warning-subtle fw-bold">
            <?= $savg !== '' ? esc(number_format((float)$savg, 2)) : '' ?>
          </td>
        <?php endif; ?>

        <!-- Rapor -->
        <td class="bg-info-subtle fw-bold">
          <?= $scores[$sid][$semester]['rapor'] ?? '' ?>
        </td>

        <!-- Final -->
        <?php if ($semester == 2 && $hasFinal): ?>
          <td class="bg-danger-subtle fw-bold">
            <?= $scores[$sid][$semester]['final'] ?? '' ?>
          </td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

</body>
</html>
