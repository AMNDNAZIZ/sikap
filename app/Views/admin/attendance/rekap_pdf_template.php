<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Rekap Absensi <?= esc($class['name']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; font-size: 12px; }
    h3, p { margin: 0 0 10px 0; }
    table { border-collapse: collapse; width: 100%; font-size: 11px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background-color: #f0f0f0; }
    .text-left { text-align: left; }
    tfoot td { font-weight: bold; background-color: #e0e0e0; }
  </style>
</head>
<body>
  <h3>Rekap Absensi Kelas <?= esc($class['name']) ?></h3>
  <p>
    Tahun Ajaran: <b><?= esc($activeYear['year']) ?></b><br>
    Periode:
    <?php if ($periode === 'semester1'): ?>
      Semester 1 (Juli – Desember <?= date('Y', strtotime($activeYear['start_date'])) ?>)
    <?php elseif ($periode === 'semester2'): ?>
      Semester 2 (Januari – Juni <?= date('Y', strtotime($activeYear['end_date'])) ?>)
    <?php else: ?>
      Satu Tahun (<?= date('d M Y', strtotime($activeYear['start_date'])) ?> – <?= date('d M Y', strtotime($activeYear['end_date'])) ?>)
    <?php endif; ?>
  </p>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>NIS</th>
        <th>Nama</th>
        <th>JK</th>
        <th>H</th>
        <th>I</th>
        <th>S</th>
        <th>A</th>
        <th>% Hadir</th>
      </tr>
    </thead>
    <tbody>
      <?php 
        $totalH = $totalI = $totalS = $totalA = 0;
        $totalPercent = 0;
      ?>
      <?php foreach ($rekapSiswa as $i => $r): ?>
        <?php
          $totalH += $r['H'];
          $totalI += $r['I'];
          $totalS += $r['S'];
          $totalA += $r['A'];
          $totalPercent += $r['percent'];
        ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td><?= esc($r['student']['nis'] ?? '-') ?></td>
          <td class="text-left"><?= esc($r['student']['name']) ?></td>
          <td><?= esc($r['student']['gender']) ?></td>
          <td><?= $r['H'] ?></td>
          <td><?= $r['I'] ?></td>
          <td><?= $r['S'] ?></td>
          <td><?= $r['A'] ?></td>
          <td><?= $r['percent'] ?>%</td>
        </tr>
      <?php endforeach ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="4">Total Kelas</td>
        <td><?= $totalH ?></td>
        <td><?= $totalI ?></td>
        <td><?= $totalS ?></td>
        <td><?= $totalA ?></td>
        <td><?= count($rekapSiswa) ? round($totalPercent / count($rekapSiswa), 1) : 0 ?>%</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
