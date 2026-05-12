<!DOCTYPE html>
<html>
<head>
  <style>
    table { border-collapse: collapse; width: 100%; font-size: 12px; }
    th, td { border: 1px solid #000; padding: 4px; text-align: center; }
    th { background-color: #f0f0f0; }
    .text-success { color: green; }
    .text-warning { color: orange; }
    .text-danger { color: red; }
    td.status-H { color: green; }
    td.status-I, td.status-S { color: orange; }
    td.status-A { color: red; }
    tfoot td { font-weight: bold; background-color: #e0e0e0; }
    .holiday { background-color: #dc3545; color: white; } /* merah (bootstrap bg-danger) */
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
          // Tentukan kolom mana yang libur (weekend / holiday)
          $holidayCols = [];
          $totalWorkDays = 0;
          foreach ($dates as $idx => $d) {
              $dow = date('N', strtotime($d));
              $isWeekend = ($dow >= 6);
              $isHoliday = $isWeekend || isset($holidays[$d]);
              $holidayCols[$idx] = $isHoliday;
              if (!$isHoliday) {
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
        $totalH = 0;
        $totalI = 0;
        $totalS = 0;
        $totalA = 0;
      ?>
      <?php foreach ($students as $i => $s): ?>
        <?php
          $countH = 0;
          $countI = 0;
          $countS = 0;
          $countA = 0;
        ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= esc($s['nis'] ?? '-') ?></td>
          <td style="text-align:left"><?= esc($s['name']) ?></td>
          <td><?= esc($s['gender']) ?></td>
          <?php foreach ($dates as $idx => $d):
            $val = $attMap[$s['id']][$d] ?? '-';
            $class = '';
            if ($val === 'H') {
              $class = 'status-H';
              $countH++;
            } elseif ($val === 'I') {
              $class = 'status-I';
              $countI++;
            } elseif ($val === 'S') {
              $class = 'status-S';
              $countS++;
            } elseif ($val === 'A') {
              $class = 'status-A';
              $countA++;
            }
            $tdClass = $holidayCols[$idx] ? 'holiday ' . $class : $class;
          ?>
            <td class="<?= $tdClass ?>"><?= $val ?></td>
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
