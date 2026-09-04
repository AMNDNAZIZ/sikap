<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Grid Absensi Guru <?= date('F Y', strtotime($month . '-01')) ?></title>
  <style>
    @page { size: A4 landscape; margin: 10mm 8mm; }
    body  { font-family: DejaVu Sans, sans-serif; font-size: 9px; margin: 0; }
    h3    { text-align: center; margin: 0 0 4px 0; font-size: 12px; }
    p.sub { text-align: center; margin: 0 0 8px 0; font-size: 9px; color: #555; }
    table { border-collapse: collapse; width: 100%; table-layout: fixed; }
    th, td { border: 1px solid #555; padding: 2px 1px; text-align: center; overflow: hidden; }
    thead th { background: #343a40; color: #fff; font-size: 8px; }
    .col-no   { width: 14px; }
    .col-name { width: 140px; text-align: left; padding-left: 3px; white-space: nowrap; overflow: hidden; }
    .col-day  { font-size: 7px; }
    .col-sum  { width: 18px; font-weight: bold; }
    td.holiday { background-color: #f8d7da; color: #842029; }
    td.weekend { background-color: #e2e3e5; color: #666; }
    td.no-sched { background-color: #dc3545; color: #fff; }
    td.not-rec  { background-color: #fff3cd; color: #664d03; }
    td.hadir-full  { color: #198754; font-weight: bold; }
    td.hadir-part  { color: #856404; font-weight: bold; }
    td.hadir-none  { color: #dc3545; font-weight: bold; }
    .persen-ok  { color: #198754; }
    .persen-med { color: #856404; }
    .persen-bad { color: #dc3545; }
    tfoot td { background: #f0f0f0; font-weight: bold; font-size: 8px; }
    .keterangan { margin-top: 8px; font-size: 8px; }
    .keterangan span { margin-right: 8px; }
  </style>
</head>
<body>
  <h3>Rekapitulasi Absensi Guru — <?= date('F Y', strtotime($month . '-01')) ?></h3>
  <?php if (!empty($schoolName)): ?>
  <p class="sub"><?= esc($schoolName) ?></p>
  <?php endif; ?>

  <?php
    $namaHariSingkat = [1=>'Sn',2=>'Sl',3=>'Rb',4=>'Km',5=>'Jm',6=>'Sb',7=>'Mg'];
    $today = date('Y-m-d');
  ?>

  <table>
    <thead>
      <tr>
        <th class="col-no">No</th>
        <th class="col-name">Nama Guru</th>
        <?php foreach ($dates as $d):
          $dow = (int) date('N', strtotime($d));
          $isWe = ($dow >= 6);
        ?>
        <th class="col-day <?= $isWe ? 'weekend' : '' ?>">
          <?= date('d', strtotime($d)) ?><br>
          <span style="font-size:7px"><?= $namaHariSingkat[$dow] ?></span>
        </th>
        <?php endforeach; ?>
        <th class="col-sum">JP</th>
        <th class="col-sum">H</th>
        <th class="col-sum">TH</th>
        <th class="col-sum">%</th>
      </tr>
    </thead>
    <tbody>
    <?php $no = 1; foreach ($rekap as $r): ?>
      <tr>
        <td class="col-no"><?= $no++ ?></td>
        <td class="col-name"><?= esc($r['teacher_name']) ?></td>

        <?php foreach ($dates as $d):
          $cell = $r['daily'][$d] ?? ['status' => 'not_recorded', 'hadir' => 0, 'total' => 0];
          $st   = $cell['status'];
          $h    = $cell['hadir'];
          $tot  = $cell['total'];

          if ($st === 'holiday') {
            $cls  = 'holiday';
            $text = '—';
          } elseif ($st === 'weekend') {
            $cls  = 'weekend';
            $text = '';
          } elseif ($st === 'no_schedule') {
            $cls  = 'no-sched';
            $text = '—';
          } elseif ($st === 'not_recorded') {
            if ($tot > 0) {
              $cls  = 'not-rec';
              $text = '?/'.$tot;
            } else {
              $cls  = 'no-sched';
              $text = '—';
            }
          } else {
            if ($h === 0) {
              $cls  = 'hadir-none';
            } elseif ($h < $tot) {
              $cls  = 'hadir-part';
            } else {
              $cls  = 'hadir-full';
            }
            $text = $h;
          }
        ?>
        <td class="col-day <?= $cls ?>"><?= $text ?></td>
        <?php endforeach; ?>

        <?php
          $pClass = $r['persen'] >= 90 ? 'persen-ok' : ($r['persen'] >= 75 ? 'persen-med' : 'persen-bad');
        ?>
        <td class="col-sum"><?= $r['total_jp'] ?></td>
        <td class="col-sum hadir-full"><?= $r['jp_hadir'] ?></td>
        <td class="col-sum <?= $r['jp_th'] > 0 ? 'hadir-none' : '' ?>"><?= $r['jp_th'] ?></td>
        <td class="col-sum <?= $pClass ?>"><?= $r['persen'] ?>%</td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <div class="keterangan">
    <strong>Keterangan:</strong>
    <span style="background:#dc3545;color:#fff;padding:1px 3px;">—</span> = Tdk ada jam
    &nbsp;
    <span style="background:#fff3cd;color:#664d03;padding:1px 3px;">?/n</span> = Ada jam, blm diisi
    &nbsp;
    <span style="color:#198754">■</span> = Hadir penuh
    &nbsp;
    <span style="color:#856404">■</span> = Hadir sebagian
    &nbsp;
    <span style="color:#dc3545">0</span> = Tdk hadir
  </div>

  <p style="font-size:8px; color:#888; margin-top:6px;">Dicetak: <?= date('d M Y H:i') ?></p>
</body>
</html>
