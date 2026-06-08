<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
    h3 { text-align: center; margin-bottom: 10px; }
    table { border-collapse: collapse; width: 100%; font-size: 10px; }
    th, td { border: 1px solid #000; padding: 3px; text-align: center; }
    th { background-color: #f0f0f0; }
    td.status-H { color: green; }
    td.status-I, td.status-S { color: orange; }
    td.status-A { color: red; }
    tfoot td { font-weight: bold; background-color: #e0e0e0; }
    .holiday { background-color: #dc3545; color: white; }
  </style>
</head>
<body>
  <h3>Absensi <?= esc($class['name']) ?> Bulan <?= date('F Y', strtotime($month . '-01')) ?></h3>

  <table>
    <thead>
      <tr>
        <th>No</th>
        <th>NIS</th>
        <th>Nama Siswa</th>
        <th>JK</th>
        <?php
          $holidayCols = [];
          $totalWorkDays = 0;
          $today = date('Y-m-d');
          foreach ($dates as $idx => $d) {
              // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
              $isWeekend = is_weekend($d, $schoolDays);
              $isHoliday = $isWeekend || isset($holidays[$d]);
              $holidayCols[$idx] = $isHoliday;
              if (!$isHoliday && $d <= $today) {
                  $totalWorkDays++;
              }
          }
        ?>
        <?php foreach ($dates as $idx => $d): ?>
          <th class="<?= $holidayCols[$idx] ? 'holiday' : '' ?>"><?= date('d', strtotime($d)) ?></th>
        <?php endforeach; ?>
        <th>H</th>
        <th>I</th>
        <th>S</th>
        <th>A</th>
        <th>%</th>
      </tr>
    </thead>
    <tbody>
      <?php
        $totalH = $totalI = $totalS = $totalA = 0;
      ?>
      <?php foreach ($students as $i => $s): ?>
        <?php $countH = $countI = $countS = $countA = 0; ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= esc($s['nis'] ?? '-') ?></td>
          <td style="text-align:left; white-space:nowrap;"><?= esc($s['name']) ?></td>
          <td><?= esc($s['gender']) ?></td>
          <?php foreach ($dates as $idx => $d):
            $isHoliday = $holidayCols[$idx];
            $val = $attMap[$s['id']][$d] ?? null;
            $disp = '-';
            $class = '';

            if ($isHoliday) {
              $disp = '-';
            } elseif ($d > $today) {
              $disp = '-';
            } elseif ($val) {
              $disp = $val;
              if ($val === 'H') { $class = 'status-H'; $countH++; }
              elseif ($val === 'I') { $class = 'status-I'; $countI++; }
              elseif ($val === 'S') { $class = 'status-S'; $countS++; }
              elseif ($val === 'A') { $class = 'status-A'; $countA++; }
            } else {
              // default hadir jika hari kerja, tanggal <= hari ini, tidak ada data
              $disp = 'H';
              $class = 'status-H';
              $countH++;
            }
          ?>
            <td class="<?= ($isHoliday ? 'holiday ' : '') . $class ?>"><?= $disp ?></td>
          <?php endforeach; ?>
          <td class="status-H"><?= $countH ?></td>
          <td class="status-I"><?= $countI ?></td>
          <td class="status-S"><?= $countS ?></td>
          <td class="status-A"><?= $countA ?></td>
          <td>
            <?php
              $percent = $totalWorkDays ? round(($countH / $totalWorkDays) * 100, 1) : 0;
              echo $percent . '%';
            ?>
          </td>
        </tr>
        <?php
          $totalH += $countH;
          $totalI += $countI;
          $totalS += $countS;
          $totalA += $countA;
        ?>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <?php
        $totalStudents = count($students);
        $totalPossibleAttendances = $totalWorkDays * $totalStudents;
        $percentClass = $totalPossibleAttendances ? round(($totalH / $totalPossibleAttendances) * 100, 1) : 0;
      ?>
      <tr>
        <td colspan="<?= 4 + count($dates) ?>" style="text-align:right">Total Kelas:</td>
        <td class="status-H"><?= $totalH ?></td>
        <td class="status-I"><?= $totalI ?></td>
        <td class="status-S"><?= $totalS ?></td>
        <td class="status-A"><?= $totalA ?></td>
        <td><?= $percentClass ?>%</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
