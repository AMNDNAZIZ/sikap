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
<?php
  $fCount = $maxFormatif[$s] ?? 0;
  $sCount = $maxSumatif[$s] ?? 0;
?>
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
      <th rowspan="2" class="left" style="width:160px;">Mata Pelajaran</th>

      <!-- FORMATIF -->
      <?php if ($fCount > 0): ?>
        <th colspan="<?= $fCount ?>">Nilai Formatif</th>
        <th rowspan="2">Rerata<br>Formatif</th>
      <?php endif; ?>

      <!-- SUMATIF -->
      <?php if ($sCount > 0): ?>
        <th colspan="<?= $sCount ?>">Nilai Sumatif</th>
        <th rowspan="2">Rerata<br>Sumatif</th>
      <?php endif; ?>

      <th rowspan="2">Nilai<br>Akhir</th>

      <?php if ($s == 2 && $hasFinal): ?>
        <th rowspan="2">Final</th>
      <?php endif; ?>
    </tr>

    <tr>
      <?php for ($i = 1; $i <= $fCount; $i++): ?>
        <th style="width:28px;"><?= $i ?></th>
      <?php endfor; ?>

      <?php for ($i = 1; $i <= $sCount; $i++): ?>
        <th style="width:28px;"><?= $i ?></th>
      <?php endfor; ?>
    </tr>
  </thead>

  <tbody>
    <?php $no = 1; foreach ($grades[$s] ?? [] as $row): ?>
      <tr>
        <td><?= $no++ ?></td>
        <td class="left"><?= esc($row['subject']) ?></td>

        <!-- FORMATIF -->
        <?php for ($i = 0; $i < $fCount; $i++): ?>
          <?php $fItem = $row['formatif'][$i] ?? null; ?>
          <td>
            <?= ($fItem !== null && $fItem['score'] !== null && $fItem['score'] !== '') ? (is_numeric($fItem['score']) ? ((float)$fItem['score'] == (int)$fItem['score'] ? (int)$fItem['score'] : number_format((float)$fItem['score'], 1, ',', '.')) : esc($fItem['score'])) : '-' ?>
          </td>
        <?php endfor; ?>

        <!-- RERATA FORMATIF -->
        <?php if ($fCount > 0): ?>
          <td><?= $row['avg_formatif'] !== null ? number_format((float)$row['avg_formatif'], 2, ',', '.') : '-' ?></td>
        <?php endif; ?>

        <!-- SUMATIF -->
        <?php for ($i = 0; $i < $sCount; $i++): ?>
          <?php $sItem = $row['sumatif'][$i] ?? null; ?>
          <td>
            <?= ($sItem !== null && $sItem['score'] !== null && $sItem['score'] !== '') ? (is_numeric($sItem['score']) ? ((float)$sItem['score'] == (int)$sItem['score'] ? (int)$sItem['score'] : number_format((float)$sItem['score'], 1, ',', '.')) : esc($sItem['score'])) : '-' ?>
          </td>
        <?php endfor; ?>

        <!-- RERATA SUMATIF -->
        <?php if ($sCount > 0): ?>
          <td><?= $row['avg_sumatif'] !== null ? number_format((float)$row['avg_sumatif'], 2, ',', '.') : '-' ?></td>
        <?php endif; ?>

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
          <td><?= $row['final'] !== null ? number_format((float)$row['final'], 2, ',', '.') : '-' ?></td>
        <?php endif; ?>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php endforeach; ?>

</body>
</html>
