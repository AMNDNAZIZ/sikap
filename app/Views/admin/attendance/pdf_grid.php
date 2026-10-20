<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Absensi <?= esc($class['name']) ?> - <?= esc($monthNameIndo ?? $month) ?></title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 10px;
      color: #111;
      margin: 0;
      padding: 0;
    }
    .header-box {
      text-align: center;
      margin-bottom: 12px;
    }
    .header-school {
      font-size: 13px;
      font-weight: bold;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 2px;
    }
    .header-title {
      font-size: 11.5px;
      font-weight: bold;
      margin-bottom: 3px;
    }
    .header-meta {
      font-size: 9.5px;
      color: #333;
    }

    table.data-table {
      border-collapse: collapse;
      width: 100%;
      font-size: 9px;
    }
    table.data-table th, table.data-table td {
      border: 1px solid #000;
      padding: 2.5px 1.5px;
      text-align: center;
    }
    table.data-table th {
      background-color: #f0f0f0;
      font-weight: bold;
    }
    td.status-H { color: #15803d; font-weight: bold; }
    td.status-I { color: #d97706; font-weight: bold; }
    td.status-S { color: #2563eb; font-weight: bold; }
    td.status-A { color: #dc2626; font-weight: bold; }
    tfoot td {
      font-weight: bold;
      background-color: #e5e7eb;
    }
    .holiday {
      background-color: #ef4444;
      color: #ffffff !important;
      font-weight: bold;
    }

    .legend-box {
      width: 100%;
      margin-top: 6px;
      font-size: 8.5px;
      color: #444;
      border: none;
    }

    table.ttd-table {
      border-collapse: collapse;
      width: 100%;
      border: none !important;
      margin-top: 20px;
      page-break-inside: avoid;
      font-size: 10px;
    }
    table.ttd-table td {
      border: none !important;
      padding: 0;
      line-height: 1.35;
      text-align: center;
      vertical-align: top;
    }
    .ttd-space {
      height: 50px;
      margin: 4px 0;
    }
    .ttd-img {
      max-height: 50px;
      max-width: 140px;
    }
>>>>>>> bbe84885 (Update & sync v2.0.4 - 2026-10-19 13:25)
  </style>
</head>
<body>
  <div class="header-box">
    <?php if (!empty($school['name'])): ?>
      <div class="header-school"><?= esc($school['name']) ?></div>
    <?php endif; ?>
    <div class="header-title">DAFTAR HADIR SISWA (PRESENSI BULANAN)</div>
    <div class="header-meta">
      Kelas: <strong><?= esc($class['name']) ?></strong> &nbsp;|&nbsp; 
      Bulan: <strong><?= esc($monthNameIndo ?? date('F Y', strtotime($month . '-01'))) ?></strong>
      <?php if (!empty($activeYear['year'])): ?>
        &nbsp;|&nbsp; Tahun Ajaran: <strong><?= esc($activeYear['year']) ?></strong>
      <?php endif; ?>
    </div>
  </div>

  <table class="data-table">
    <thead>
      <tr>
        <th style="width: 22px;">No</th>
        <th style="width: 55px;">NIS</th>
        <th style="text-align: left; padding-left: 4px; min-width: 130px;">Nama Siswa</th>
        <th style="width: 22px;">JK</th>
        <?php
          $holidayCols = [];
          $totalWorkDays = 0;
          $today = date('Y-m-d');
          foreach ($dates as $idx => $d) {
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
        <th style="width: 20px;">H</th>
        <th style="width: 20px;">I</th>
        <th style="width: 20px;">S</th>
        <th style="width: 20px;">A</th>
        <th style="width: 35px;">%</th>
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
          <td style="text-align: left; padding-left: 4px; white-space: nowrap;"><?= esc($s['name']) ?></td>
          <td><?= esc($s['gender']) ?></td>
          <?php foreach ($dates as $idx => $d):
            $isHoliday = $holidayCols[$idx];
            $val = $attMap[$s['id']][$d] ?? null;
            $disp = '-';
            $classStatus = '';

            if ($isHoliday) {
              $disp = '-';
            } elseif ($d > $today) {
              $disp = '-';
            } elseif ($val) {
              $disp = $val;
              if ($val === 'H') { $classStatus = 'status-H'; $countH++; }
              elseif ($val === 'I') { $classStatus = 'status-I'; $countI++; }
              elseif ($val === 'S') { $classStatus = 'status-S'; $countS++; }
              elseif ($val === 'A') { $classStatus = 'status-A'; $countA++; }
            } else {
              $disp = 'H';
              $classStatus = 'status-H';
              $countH++;
            }
          ?>
            <td class="<?= ($isHoliday ? 'holiday ' : '') . $classStatus ?>"><?= $disp ?></td>
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
        <td colspan="<?= 4 + count($dates) ?>" style="text-align: right; padding-right: 8px;">Total Akumulasi Kelas:</td>
        <td class="status-H"><?= $totalH ?></td>
        <td class="status-I"><?= $totalI ?></td>
        <td class="status-S"><?= $totalS ?></td>
        <td class="status-A"><?= $totalA ?></td>
        <td><?= $percentClass ?>%</td>
      </tr>
    </tfoot>
  </table>

  <!-- Keterangan & Ringkasan -->
  <table class="legend-box" style="width: 100%; border: none;">
    <tr>
      <td style="border: none; text-align: left; vertical-align: top;">
        <strong>Keterangan:</strong> 
        <span style="color: #15803d; font-weight: bold;">H</span> = Hadir &nbsp;|&nbsp; 
        <span style="color: #2563eb; font-weight: bold;">S</span> = Sakit &nbsp;|&nbsp; 
        <span style="color: #d97706; font-weight: bold;">I</span> = Izin &nbsp;|&nbsp; 
        <span style="color: #dc2626; font-weight: bold;">A</span> = Alpa &nbsp;|&nbsp; 
        <span style="background-color: #ef4444; color: white; padding: 0 3px; border-radius: 2px;">Merah</span> = Libur / Akhir Pekan
      </td>
      <td style="border: none; text-align: right; vertical-align: top;">
        Total Siswa: <strong><?= count($students) ?></strong> 
        (L: <?= count(array_filter($students, fn($st) => ($st['gender'] ?? '') === 'L')) ?>, 
        P: <?= count(array_filter($students, fn($st) => ($st['gender'] ?? '') === 'P')) ?>)
      </td>
    </tr>
  </table>

  <!-- Tanda Tangan Kepala Sekolah & Guru / Wali Kelas -->
  <table class="ttd-table">
    <tr>
      <td style="width: 50%;">
        Mengetahui,<br>
        Kepala Sekolah <?= esc($school['name'] ?? '') ?>
        <div class="ttd-space">
          <?php if (!empty($principalSigBase64)): ?>
            <img src="<?= $principalSigBase64 ?>" class="ttd-img" alt="TTD Kepala Sekolah">
          <?php endif; ?>
        </div>
        <strong><u><?= esc(!empty($school['headmaster']) ? $school['headmaster'] : '......................................................') ?></u></strong><br>
        NIP. <?= esc(!empty($school['principal_nip']) ? $school['principal_nip'] : '-') ?>
      </td>
      <td style="width: 50%;">
        <?= esc($titimangsaCity) ?>, <?= esc($titimangsaDate) ?><br>
        Guru Kelas / Wali Kelas <?= esc($class['name']) ?>
        <div class="ttd-space">
          <?php if (!empty($waliSigBase64)): ?>
            <img src="<?= $waliSigBase64 ?>" class="ttd-img" alt="TTD Wali Kelas">
          <?php endif; ?>
        </div>
        <strong><u><?= esc(!empty($waliKelas['name']) ? $waliKelas['name'] : '......................................................') ?></u></strong><br>
        NIP. <?= esc(!empty($waliKelas['nip']) ? $waliKelas['nip'] : '-') ?>
      </td>
    </tr>
  </table>
</body>
</html>
