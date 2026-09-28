<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1a1a2e; }
  .kop { display: table; width: 100%; border-bottom: 2.5pt solid #8b0000; padding-bottom: 6pt; margin-bottom: 8pt; }
  .kop-logo { display: table-cell; width: 60pt; vertical-align: middle; }
  .kop-logo img { max-width: 52pt; max-height: 52pt; }
  .kop-text { display: table-cell; vertical-align: middle; padding-left: 8pt; }
  .kop-text .sekolah { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #8b0000; }
  .kop-text .alamat  { font-size: 7.5pt; color: #555; margin-top: 2pt; }
  .judul-blok { text-align: center; margin-bottom: 8pt; }
  .judul-blok h1 { font-size: 11pt; font-weight: bold; text-transform: uppercase; }
  .judul-blok h2 { font-size: 9.5pt; font-weight: bold; text-transform: uppercase; }
  .judul-blok .periode { font-size: 9pt; color: #444; margin-top: 3pt; }
  .garis-judul { border-top: 1pt solid #8b0000; border-bottom: .5pt solid #8b0000; height: 3pt; margin: 5pt 0 8pt; }
  .sec-label { font-size: 9pt; font-weight: bold; color: #8b0000; border-left: 3pt solid #8b0000; padding-left: 5pt; margin: 8pt 0 4pt; }
  .privacy-note { background: #fff8e1; border: .5pt solid #ffc107; border-radius:3pt; padding: 4pt 7pt; font-size: 7.5pt; color: #555; margin-bottom: 8pt; }
  .stat-row { display: table; width: 100%; margin-bottom: 8pt; border-collapse: separate; border-spacing: 4pt; }
  .stat-cell { display: table-cell; border: .5pt solid #ccc; border-radius: 3pt; text-align: center; padding: 5pt 3pt; background: #fff8f8; }
  .stat-cell .num { font-size: 14pt; font-weight: bold; }
  .stat-cell .lbl { font-size: 6.5pt; color: #555; margin-top: 1pt; }
  .num-red    { color: #8b0000; } .num-warn { color: #b05000; } .num-ok { color: #155724; }
  .num-info   { color: #004085; } .num-dark  { color: #1a1a2e; }
  table.rekap { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
  table.rekap th { background: #8b0000; color: #fff; font-size: 8pt; padding: 4pt 5pt; text-align: left; }
  table.rekap td { padding: 3pt 5pt; font-size: 8pt; border-bottom: .3pt solid #dde; }
  table.rekap tr:nth-child(even) td { background: #fff5f5; }
  table.rekap .nc { text-align: center; font-weight: bold; }
  table.detail { width: 100%; border-collapse: collapse; font-size: 7.5pt; margin-bottom: 6pt; }
  table.detail th { background: #8b0000; color: #fff; padding: 3.5pt 4pt; text-align: left; }
  table.detail td { padding: 3pt 4pt; border-bottom: .3pt solid #dde; vertical-align: top; }
  table.detail tr:nth-child(even) td { background: #fff8f8; }
  .badge { display: inline-block; padding: 1pt 4pt; border-radius: 3pt; font-size: 6.5pt; font-weight: bold; }
  .b-s  { background: #d4edda; color: #155724; } .b-w  { background: #fff3cd; color: #856404; }
  .b-d  { background: #f8d7da; color: #721c24; } .b-i  { background: #d1ecf1; color: #0c5460; }
  .b-p  { background: #cce5ff; color: #004085; } .b-sec{ background: #e2e3e5; color: #383d41; }
  .info-filter { background: #fff5f5; border: .5pt solid #f5c2c7; border-radius: 3pt; padding: 5pt 8pt; font-size: 7.5pt; color: #333; margin-bottom: 8pt; }
  .footer { position: fixed; bottom: 10pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888; border-top: .5pt solid #ccc; padding-top: 3pt; }
  .dicetak-oleh { font-size: 7.5pt; color: #555; margin-top: 14pt; border-top: .5pt solid #ddd; padding-top: 4pt; }
  .ttd-row { display: table; width: 100%; margin-top: 28pt; }
  .ttd-cell { display: table-cell; width: 33%; text-align: center; font-size: 8pt; padding: 0 8pt; }
  .ttd-name { border-top: .8pt solid #333; margin-top: 40pt; padding-top: 3pt; font-weight: bold; }
  .ttd-nip  { font-size: 7pt; color: #555; }
  .page-break { page-break-after: always; }
  @page { size: A4 portrait; margin: 1.5cm; }
</style>
</head>
<body>

<div class="footer">
  Laporan Penanganan Kasus BK &nbsp;|&nbsp; RAHASIA — Hanya untuk Keperluan Internal &nbsp;|&nbsp;
  Dicetak: <?= esc($printAt) ?>
  <span style="float:right; margin-right:15pt;">Hal. <span class="page"></span> / <span class="pagecount"></span></span>
</div>

<?php
function badgeSeverity(string $s): string {
    return match($s) { 'Ringan'=>'b-s', 'Sedang'=>'b-w', 'Berat'=>'b-d', default=>'b-sec' };
}
function badgeKasus(string $s): string {
    return match($s) {
        'RESOLVED','CLOSED' => 'b-s',
        'IN_PROGRESS','MONITORING' => 'b-p',
        'REFERRED' => 'b-d',
        'DRAFT','REPORTED' => 'b-w',
        default => 'b-sec',
    };
}
$statusLabels = [
    'DRAFT'=>'Draft','REPORTED'=>'Dilaporkan','VERIFIED'=>'Terverifikasi',
    'IN_ASSESSMENT'=>'Asesmen','IN_PROGRESS'=>'Penanganan',
    'MONITORING'=>'Monitoring','REFERRED'=>'Dirujuk',
    'RESOLVED'=>'Selesai','CLOSED'=>'Ditutup',
];
$total = count($rows);
?>

<!-- Kop -->
<div class="kop">
  <div class="kop-logo"><?php if (!empty($logoB64)): ?><img src="<?= $logoB64 ?>" alt="Logo"><?php endif; ?></div>
  <div class="kop-text">
    <div class="sekolah"><?= esc($school['name'] ?? 'NAMA SEKOLAH') ?></div>
    <div class="alamat"><?= esc($school['address'] ?? '') ?><?= !empty($school['city_regency']) ? ', ' . esc($school['city_regency']) : '' ?></div>
    <div class="alamat">Telp: <?= esc($school['phone'] ?? '-') ?> | Email: <?= esc($school['email'] ?? '-') ?></div>
  </div>
</div>

<div class="judul-blok">
  <h1>Laporan Rekapitulasi Penanganan Kasus</h1>
  <h2>Bimbingan dan Konseling</h2>
  <div class="periode">Tahun Ajaran <?= esc($year['year'] ?? '—') ?><?php if (!empty($filter['date_from'])): ?> &nbsp;|&nbsp; Periode <?= esc($filter['date_from']) ?> s.d. <?= esc($filter['date_to'] ?? '—') ?><?php endif; ?></div>
</div>
<div class="garis-judul"></div>

<div class="privacy-note">
  ⚠ Dokumen ini bersifat <b>RAHASIA INTERNAL</b>. Catatan konseling, isi sesi, dan informasi sensitif siswa tidak disertakan.
  Data yang ditampilkan hanya untuk keperluan rekapitulasi dan pelaporan manajemen sekolah.
</div>

<!-- Ringkasan -->
<div class="sec-label">A. Ringkasan Penanganan Kasus</div>
<div class="stat-row">
  <?php
  $sts = [
    ['Total Kasus', $total, 'num-dark'],
    ['Baru/Aktif', $rekap['totalActive'], 'num-warn'],
    ['Penanganan', $rekap['totalProses'], 'num-info'],
    ['Monitoring', $rekap['totalMonitor'], 'num-info'],
    ['Selesai', $rekap['totalSelesai'], 'num-ok'],
    ['Dirujuk', $rekap['totalReferred'], 'num-red'],
  ];
  foreach ($sts as [$lbl,$val,$cls]): ?>
  <div class="stat-cell">
    <div class="num <?= $cls ?>"><?= $val ?></div>
    <div class="lbl"><?= $lbl ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Rekap tabel -->
<table width="100%"><tr valign="top">
  <td width="36%" style="padding-right:6pt;">
    <div class="sec-label">B. Rekap per Kategori</div>
    <table class="rekap">
      <thead><tr><th>Kategori</th><th style="width:45pt;" class="nc">Jml</th><th style="width:40pt;" class="nc">%</th></tr></thead>
      <tbody>
      <?php foreach ($rekap['byCategory'] as $cat => $j): ?>
      <tr><td><?= esc($cat) ?></td><td class="nc"><?= $j ?></td><td class="nc"><?= $total > 0 ? round($j/$total*100,1) : 0 ?>%</td></tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td>Total</td><td class="nc"><?= $total ?></td><td class="nc">100%</td></tr></tfoot>
    </table>
  </td>
  <td width="32%" style="padding-right:6pt;">
    <div class="sec-label">C. Rekap per Status</div>
    <table class="rekap">
      <thead><tr><th>Status</th><th style="width:45pt;" class="nc">Jml</th></tr></thead>
      <tbody>
      <?php foreach ($rekap['byStatus'] as $st => $j): ?>
      <tr><td><?= esc($statusLabels[$st] ?? $st) ?></td><td class="nc"><?= $j ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </td>
  <td width="32%">
    <div class="sec-label">D. Rekap per Bobot</div>
    <table class="rekap">
      <thead><tr><th>Bobot</th><th style="width:45pt;" class="nc">Jml</th></tr></thead>
      <tbody>
      <?php foreach ($rekap['bySeverity'] as $sv => $j): ?>
      <tr><td><span class="badge <?= badgeSeverity($sv) ?>"><?= esc($sv) ?></span></td><td class="nc"><?= $j ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </td>
</tr></table>

<!-- Tabel detail kasus -->
<div class="page-break"></div>
<div style="text-align:center; font-size:9pt; font-weight:bold; margin-bottom:6pt; border-bottom:.5pt solid #8b0000; padding-bottom:4pt;">
  LAPORAN PENANGANAN KASUS BK &nbsp;|&nbsp; <?= esc($year['year'] ?? '') ?>
</div>

<div class="sec-label">E. Rekap Penanganan Kasus (<?= $total ?> Kasus)</div>
<p style="font-size:7pt; color:#777; margin-bottom:4pt;">
  * Untuk menjaga privasi, siswa diidentifikasi dengan NIS/Kode Kasus. Nama lengkap hanya diketahui oleh petugas BK yang berwenang.
</p>

<table class="detail">
  <thead>
    <tr>
      <th style="width:20pt;">No</th>
      <th style="width:48pt;">Tgl Kejadian</th>
      <th style="width:60pt;">No Kasus</th>
      <th style="width:65pt;">Kategori</th>
      <th style="width:60pt;">Kode Siswa</th>
      <th style="width:50pt;">Kelas</th>
      <th style="width:38pt;">Bobot</th>
      <th style="width:55pt;">Status</th>
      <th style="width:48pt;">Tgl Selesai</th>
      <th style="width:28pt; text-align:center;">T.L.</th>
      <th style="width:28pt; text-align:center;">Ref.</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
  <tr>
    <td style="text-align:center; color:#888;"><?= $i + 1 ?></td>
    <td><?= $r['incident_date'] ? date('d/m/Y', strtotime($r['incident_date'])) : '—' ?></td>
    <td><span style="font-size:6.5pt; font-family:monospace;"><?= esc($r['case_code'] ?: 'N/A') ?></span></td>
    <td><?= esc($r['category']) ?></td>
    <td style="color:#555;"><?= esc($r['student_nis'] ?: '(NIS —)') ?></td>
    <td><?= esc($r['class_name'] ?: '—') ?></td>
    <td><span class="badge <?= badgeSeverity($r['severity']) ?>"><?= esc($r['severity']) ?></span></td>
    <td><span class="badge <?= badgeKasus($r['status']) ?>"><?= esc($statusLabels[$r['status']] ?? $r['status']) ?></span></td>
    <td><?= $r['closed_at'] ? date('d/m/Y', strtotime($r['closed_at'])) : '—' ?></td>
    <td style="text-align:center;"><?= (int)$r['total_actions'] ?></td>
    <td style="text-align:center;"><?= (int)$r['total_referrals'] ?></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<div class="ttd-row">
  <div class="ttd-cell"></div>
  <div class="ttd-cell"></div>
  <div class="ttd-cell">
    <div style="font-size:8pt;"><?= esc($school['city_regency'] ?? 'Kota') ?>, <?= date('d F Y') ?></div>
    <div style="font-size:8pt; margin-top:3pt;">Guru BK / Koordinator BK,</div>
    <div class="ttd-name">______________________</div>
    <div class="ttd-nip">NIP. ___________________</div>
  </div>
</div>

<div class="dicetak-oleh">
  Dicetak oleh: <?= esc($user['fullname'] ?? $user['name'] ?? 'Sistem') ?> &nbsp;|&nbsp; <?= esc($printAt) ?> &nbsp;|&nbsp; DOKUMEN RAHASIA INTERNAL
</div>

</body>
</html>
