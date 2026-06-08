<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Nilai Siswa</title>
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
    h3 { margin-bottom: 5px; }
    table.info td { padding: 3px 8px; }
    table.grades { border-collapse: collapse; width: 100%; margin-bottom: 25px; }
    table.grades th, table.grades td {
      border: 1px solid #000;
      padding: 4px;
      text-align: center;
    }
    table.grades th {
      background: #f0f0f0;
    }
    table.grades td.left {
      text-align: left;
    }
  </style>
</head>
<body>

<?php
  // Jika user memilih semester tertentu → tampilkan hanya 1 semester
  $showSem = isset($semester) ? [$semester] : $semesters;
?>

<?php foreach ($showSem as $s): ?>
<h3>DAFTAR NILAI – SEMESTER <?= $s ?></h3>

<table class="info">
  <tr>
    <td><strong>Nama</strong></td>
    <td>: <?= esc($student['name']) ?></td>
  </tr>
  <tr>
    <td><strong>Kelas</strong></td>
    <td>: <?= esc($student['class_name']) ?></td>
  </tr>
  <tr>
    <td><strong>Tahun Ajaran</strong></td>
    <td>: <?= esc($activeYear['year']) ?></td>
  </tr>
</table>

<table class="grades">
  <thead>
    <tr>
      <th rowspan="2" style="width:30px;">No</th>
      <th rowspan="2" class="left" style="width:150px;">Mata Pelajaran</th>

      <!-- FORMATIF -->
      <?php if (!empty($allFormatifCols[$s])): ?>
        <th colspan="<?= count($allFormatifCols[$s]) ?>">Nilai Formatif</th>
      <?php endif; ?>
      <th rowspan="2">Rerata<br>Formatif</th>

      <!-- SUMATIF -->
      <?php if (!empty($allSumatifCols[$s])): ?>
        <th colspan="<?= count($allSumatifCols[$s]) ?>">Nilai Sumatif</th>
      <?php endif; ?>
      <th rowspan="2">Rerata<br>Sumatif</th>

      <th rowspan="2">Nilai<br>Akhir</th>

      <?php if ($s == 2 && $hasFinal): ?>
        <th rowspan="2">Final</th>
      <?php endif; ?>
    </tr>

    <tr>
      <?php if (!empty($allFormatifCols[$s])): ?>
        <?php foreach ($allFormatifCols[$s] as $meta): ?>
          <th><?= esc($meta['label']) ?></th>
        <?php endforeach; ?>
      <?php endif; ?>

      <?php if (!empty($allSumatifCols[$s])): ?>
        <?php foreach ($allSumatifCols[$s] as $meta): ?>
          <th><?= esc($meta['label']) ?></th>
        <?php endforeach; ?>
      <?php endif; ?>
    </tr>
  </thead>

  <tbody>
    <?php $no = 1; foreach ($grades[$s] as $row): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td class="left"><?= esc($row['subject']) ?></td>

        <!-- FORMATIF -->
        <?php if (!empty($allFormatifCols[$s])): ?>
          <?php foreach ($allFormatifCols[$s] as $key => $meta): ?>
            <td><?= esc($row['formatif'][$key] ?? '-') ?></td>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- RERATA FORMATIF -->
        <td><?= $row['avg_formatif'] !== null ? esc($row['avg_formatif']) : '-' ?></td>

        <!-- SUMATIF -->
        <?php if (!empty($allSumatifCols[$s])): ?>
          <?php foreach ($allSumatifCols[$s] as $key => $meta): ?>
            <td><?= esc($row['sumatif'][$key] ?? '-') ?></td>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- RERATA SUMATIF -->
        <td><?= $row['avg_sumatif'] !== null ? esc($row['avg_sumatif']) : '-' ?></td>

        <!-- NILAI AKHIR — erapor jika sudah diisi guru, fallback ke nilai sistem -->
        <td style="text-align:center; font-weight:bold;">
          <?php if ($row['nilai_akhir'] !== null): ?>
            <?= number_format((float)$row['nilai_akhir'], 2, ',', '.') ?>
          <?php else: ?>
            -
          <?php endif; ?>
        </td>

        <!-- FINAL -->
        <?php if ($s == 2 && $hasFinal): ?>
          <td><?= esc($row['final'] ?? '-') ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endforeach; ?>

</body>
</html>
