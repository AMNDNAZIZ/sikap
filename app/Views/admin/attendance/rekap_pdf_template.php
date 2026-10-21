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
    .signature-section { margin-top: 25px; page-break-inside: avoid; }
    table.signature-table { width: 100%; border: none !important; font-size: 11px; margin-top: 10px; }
    table.signature-table td { border: none !important; width: 50%; text-align: center; vertical-align: top; padding: 0 15px; }
    .signature-space { height: 50px; }
    .signature-img { max-height: 48px; max-width: 130px; display: block; margin: 0 auto; }
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
    <?php elseif (preg_match('/^\d{4}-\d{2}$/', $periode)): ?>
      <?php
        $bulanList = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $mNum = (int) date('n', strtotime($periode . '-01'));
      ?>
      Bulan <?= ($bulanList[$mNum] ?? date('F', strtotime($periode . '-01'))) . ' ' . date('Y', strtotime($periode . '-01')) ?>
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
          <strong style="text-decoration: underline;"><?= esc($kepsekNama) ?></strong><br>
          NIP. <?= esc($kepsekNip) ?>
        </td>
        <td>
          <?= esc($kota) ?>, <?= esc($titimangsaDate ?? date('d F Y')) ?><br>
          <strong>Wali Kelas <?= esc($class['name'] ?? '') ?></strong>
          <div class="signature-space">
            <?php if (!empty($waliSigBase64)): ?>
              <img src="<?= $waliSigBase64 ?>" alt="TTD Wali Kelas" class="signature-img">
            <?php endif; ?>
          </div>
          <strong style="text-decoration: underline;"><?= esc($waliNama) ?></strong><br>
          NIP. <?= esc($waliNip) ?>
        </td>
      </tr>
    </table>
  </div>
</body>
</html>
