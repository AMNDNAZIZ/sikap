<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9.5pt; color: #1a1a2e; }
  .kop { display: table; width: 100%; border-bottom: 3pt solid #003580; padding-bottom: 7pt; margin-bottom: 8pt; }
  .kop-logo { display: table-cell; width: 65pt; vertical-align: middle; }
  .kop-logo img { max-width: 55pt; max-height: 55pt; }
  .kop-text { display: table-cell; vertical-align: middle; padding-left: 10pt; }
  .kop-text .sekolah  { font-size: 14pt; font-weight: bold; text-transform: uppercase; color: #003580; }
  .kop-text .alamat   { font-size: 8pt; color: #444; margin-top: 2pt; }
  .judul-blok { text-align: center; padding: 10pt 0 6pt; }
  .judul-blok h1 { font-size: 13pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1pt; color: #003580; }
  .judul-blok h2 { font-size: 10.5pt; font-weight: bold; text-transform: uppercase; color: #003580; margin-top: 3pt; }
  .judul-blok .meta  { font-size: 9pt; color: #444; margin-top: 4pt; }
  .garis { border-top: 2pt solid #003580; border-bottom: .5pt solid #003580; height: 3pt; margin: 6pt 0 10pt; }
  .sec-label { font-size: 10pt; font-weight: bold; color: #003580; border-left: 4pt solid #003580;
               padding-left: 6pt; margin: 12pt 0 5pt; }
  .sec-sub { font-size: 8.5pt; font-weight: bold; color: #333; margin: 8pt 0 3pt; }
  /* Stat boxes */
  .stat-wrap { display: table; width: 100%; border-collapse: separate; border-spacing: 5pt; margin-bottom: 6pt; }
  .stat-cell { display: table-cell; border: .7pt solid #b8c9f0; border-radius: 4pt; text-align: center;
               padding: 7pt 4pt; background: #f5f8ff; }
  .stat-cell .num { font-size: 20pt; font-weight: bold; }
  .stat-cell .lbl { font-size: 7pt; color: #555; margin-top: 2pt; line-height: 1.2; }
  /* Progress bar capaian */
  .prog-wrap { background: #e9ecef; border-radius: 5pt; height: 14pt; overflow: hidden; margin: 4pt 0; }
  .prog-bar  { height: 14pt; border-radius: 5pt; text-align: center; line-height: 14pt; font-size: 8pt;
               color: #fff; font-weight: bold; }
  /* Tables */
  table.t-std { width: 100%; border-collapse: collapse; margin-bottom: 8pt; }
  table.t-std th { background: #003580; color: #fff; font-size: 8pt; padding: 4pt 5pt; text-align: left; }
  table.t-std td { padding: 3.5pt 5pt; font-size: 8pt; border-bottom: .3pt solid #d0d8f0; }
  table.t-std tr:nth-child(even) td { background: #f0f4ff; }
  .nc { text-align: center; font-weight: bold; }
  /* TTD */
  .ttd-row { display: table; width: 100%; margin-top: 30pt; }
  .ttd-cell { display: table-cell; width: 33%; text-align: center; font-size: 8pt; padding: 0 8pt; }
  .ttd-name { border-top: .8pt solid #333; margin-top: 45pt; padding-top: 3pt; font-weight: bold; }
  .ttd-nip  { font-size: 7pt; color: #555; }
  .footer { position: fixed; bottom: 10pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888;
            border-top: .5pt solid #ccc; padding-top: 3pt; }
  .dicetak { font-size: 7.5pt; color: #555; margin-top: 12pt; border-top: .5pt solid #ddd; padding-top: 4pt; }
  .page-break { page-break-after: always; }
  .note-empty { background: #fffbe6; border: .5pt solid #ffc107; border-radius: 3pt; padding: 5pt 8pt;
                font-size: 8pt; color: #555; font-style: italic; margin: 4pt 0; }
  @page { size: A4 portrait; margin: 1.8cm; }
</style>
</head>
<body>

<div class="footer">
  Laporan Eksekutif BK &nbsp;|&nbsp; <?= esc($school['name'] ?? '') ?> &nbsp;|&nbsp; <?= esc($printAt) ?>
  <span style="float:right; margin-right:15pt;">Hal. <span class="page"></span> / <span class="pagecount"></span></span>
</div>

<?php
$semLabel = match((string)($filter['semester'] ?? '')) {
    '1' => 'Semester 1 (Ganjil)', '2' => 'Semester 2 (Genap)', default => 'Tahun Penuh',
};
$totalKasusOlah = $totalKasus ?? 0;
$totalLayananOlah = $totalLayanan ?? 0;
?>

<!-- ══ HALAMAN 1 ═══════════════════════════════════════════════ -->

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
  <h1>Laporan Eksekutif</h1>
  <h2>Pelaksanaan Bimbingan dan Konseling</h2>
  <div class="meta">
    <?= esc($semLabel) ?> &nbsp;|&nbsp; Tahun Ajaran <?= esc($year['year'] ?? '—') ?>
    <?php if (!empty($filter['date_from'])): ?>
      &nbsp;|&nbsp; <?= esc($filter['date_from']) ?> – <?= esc($filter['date_to'] ?? '—') ?>
    <?php endif; ?>
  </div>
</div>
<div class="garis"></div>

<!-- A. Ringkasan Eksekutif -->
<div class="sec-label">A. Ringkasan Eksekutif</div>
<div class="stat-wrap">
  <?php
  $statItems = [
    [$totalProgram, 'Total Program', '#003580'],
    [$totalLayananOlah, 'Total Layanan', '#1565C0'],
    [$totalSiswa, 'Siswa Dilayani', '#4CAF50'],
    [$totalKasusOlah, 'Total Kasus', '#D32F2F'],
    [$kasusSelesai, 'Kasus Selesai', '#2E7D32'],
    [$kasusProses + $totalMonitor, 'Kasus Berjalan', '#E65100'],
    [$totalRujukan, 'Rujukan', '#7B1FA2'],
    [$totalTindakLanjut, 'Tindak Lanjut', '#00695C'],
  ];
  ?>
  <?php foreach ($statItems as [$val, $lbl, $color]): ?>
  <div class="stat-cell" style="border-top: 3pt solid <?= $color ?>;">
    <div class="num" style="color:<?= $color ?>;"><?= $val ?></div>
    <div class="lbl"><?= $lbl ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- B. Pelaksanaan Layanan -->
<div class="sec-label">B. Pelaksanaan Layanan</div>
<?php if (empty($byType)): ?>
  <p class="note-empty">Belum terdapat data layanan BK pada periode yang dipilih.</p>
<?php else: ?>
  <?php $totL = array_sum(array_column($byType, 'jumlah')); ?>
  <table class="t-std">
    <thead><tr><th>Jenis Layanan</th><th style="width:60pt;" class="nc">Jumlah</th><th style="width:200pt;">Proporsi</th></tr></thead>
    <tbody>
    <?php foreach ($byType as $b): ?>
    <?php $pct = $totL > 0 ? round($b['jumlah']/$totL*100) : 0; ?>
    <tr>
      <td><?= esc($b['service_type']) ?></td>
      <td class="nc"><?= $b['jumlah'] ?></td>
      <td>
        <div class="prog-wrap">
          <div class="prog-bar" style="width:<?= $pct ?>%; background:#1565C0;"><?= $pct ?>%</div>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- C. Bidang Layanan -->
<div class="sec-label">C. Layanan per Bidang</div>
<?php if (empty($byField)): ?>
  <p class="note-empty">Belum terdapat data bidang layanan.</p>
<?php else: ?>
  <?php $totF = array_sum(array_column($byField, 'jumlah')); ?>
  <table class="t-std">
    <thead><tr><th>Bidang</th><th style="width:60pt;" class="nc">Jumlah</th><th style="width:60pt;" class="nc">%</th></tr></thead>
    <tbody>
    <?php foreach ($byField as $b): ?>
    <tr>
      <td><?= esc($b['field']) ?></td>
      <td class="nc"><?= $b['jumlah'] ?></td>
      <td class="nc"><?= $totF > 0 ? round($b['jumlah']/$totF*100,1) : 0 ?>%</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- D. Capaian Program -->
<div class="sec-label">D. Capaian Program BK</div>
<?php if ($targetLayanan > 0): ?>
  <table class="t-std">
    <thead><tr><th>Indikator</th><th style="width:80pt;" class="nc">Nilai</th></tr></thead>
    <tbody>
      <tr><td>Target Layanan (dari RPL)</td><td class="nc"><?= $targetLayanan ?></td></tr>
      <tr><td>Realisasi Layanan</td><td class="nc"><?= $totalLayananOlah ?></td></tr>
      <tr><td>Persentase Ketercapaian</td>
        <td class="nc" style="color:<?= $capaian >= 80 ? '#2E7D32' : ($capaian >= 60 ? '#E65100' : '#D32F2F') ?>; font-size:11pt;">
          <?= $capaian ?>%
        </td>
      </tr>
    </tbody>
  </table>
  <div class="prog-wrap" style="margin-bottom:6pt;">
    <?php $capPct = min(100, $capaian); $capColor = $capaian >= 80 ? '#2E7D32' : ($capaian >= 60 ? '#E65100' : '#D32F2F'); ?>
    <div class="prog-bar" style="width:<?= $capPct ?>%; background:<?= $capColor ?>;"><?= $capaian ?>%</div>
  </div>
<?php else: ?>
  <p style="font-size:8.5pt; color:#444;">
    Data target program (RPL) belum tersedia. Hanya realisasi yang dapat ditampilkan:
    <strong><?= $totalLayananOlah ?></strong> layanan terlaksana.
  </p>
<?php endif; ?>

<!-- ══ HALAMAN 2 ═══════════════════════════════════════════════ -->
<div class="page-break"></div>

<div style="text-align:center; font-size:10pt; font-weight:bold; margin-bottom:8pt; color:#003580; border-bottom: .5pt solid #003580; padding-bottom:4pt;">
  LAPORAN EKSEKUTIF BK &nbsp;|&nbsp; <?= esc($year['year'] ?? '') ?> &nbsp;|&nbsp; <?= esc($semLabel) ?> (lanjutan)
</div>

<!-- E. Pemetaan Kebutuhan Siswa -->
<div class="sec-label">E. Pemetaan Kebutuhan Siswa (Berdasarkan Asesmen)</div>
<?php if (empty($asesmenAgg)): ?>
  <p class="note-empty">Belum terdapat data asesmen pada periode yang dipilih. Bagian ini akan terisi setelah siswa mengisi instrumen asesmen BK.</p>
<?php else: ?>
  <table class="t-std">
    <thead><tr><th>Jenis Instrumen</th><th style="width:100pt;" class="nc">Siswa yang Mengisi</th></tr></thead>
    <tbody>
    <?php foreach ($asesmenAgg as $a): ?>
    <tr><td><?= esc($a['type']) ?></td><td class="nc"><?= $a['total_siswa'] ?></td></tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>

<!-- F. Penanganan Kasus -->
<div class="sec-label">F. Penanganan Kasus</div>
<?php if ($totalKasusOlah === 0): ?>
  <p class="note-empty">Tidak terdapat kasus BK pada periode yang dipilih.</p>
<?php else: ?>
  <table class="t-std">
    <thead><tr><th>Indikator</th><th style="width:80pt;" class="nc">Jumlah</th></tr></thead>
    <tbody>
      <tr><td>Total Kasus</td><td class="nc"><?= $totalKasusOlah ?></td></tr>
      <tr><td>Kasus Selesai</td><td class="nc" style="color:#2E7D32;"><?= $kasusSelesai ?></td></tr>
      <tr><td>Dalam Penanganan / Monitoring</td><td class="nc" style="color:#E65100;"><?= $kasusProses + $totalMonitor ?></td></tr>
      <tr><td>Dirujuk</td><td class="nc" style="color:#7B1FA2;"><?= $kasusReferred ?></td></tr>
      <tr><td>Total Tindak Lanjut yang Tercatat</td><td class="nc"><?= $totalTindakLanjut ?></td></tr>
    </tbody>
  </table>

  <?php if (!empty($kasusByKategori)): ?>
  <div class="sec-sub">Distribusi Kasus per Kategori</div>
  <table class="t-std">
    <thead><tr><th>Kategori</th><th style="width:60pt;" class="nc">Jml</th><th style="width:60pt;" class="nc">%</th></tr></thead>
    <tbody>
    <?php foreach ($kasusByKategori as $k): ?>
    <tr>
      <td><?= esc($k['category']) ?></td>
      <td class="nc"><?= $k['jumlah'] ?></td>
      <td class="nc"><?= $totalKasusOlah > 0 ? round($k['jumlah']/$totalKasusOlah*100,1) : 0 ?>%</td>
    </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
<?php endif; ?>

<!-- G. Rujukan -->
<div class="sec-label">G. Rujukan</div>
<?php if ($totalRujukan === 0): ?>
  <p class="note-empty">Tidak terdapat data rujukan pada periode yang dipilih.</p>
<?php else: ?>
  <p style="font-size:8.5pt; color:#333;">Total rujukan eksternal yang tercatat: <strong><?= $totalRujukan ?></strong></p>
<?php endif; ?>

<!-- Tanda Tangan -->
<div class="ttd-row">
  <div class="ttd-cell">
    <div style="font-size:8pt;">Mengetahui,</div>
    <div style="font-size:8pt; margin-top:3pt;">Kepala Sekolah,</div>
    <div class="ttd-name"><?= esc($school['headmaster'] ?? '___________________') ?></div>
    <div class="ttd-nip">NIP. <?= esc($school['principal_nip'] ?? '___________________') ?></div>
  </div>
  <div class="ttd-cell"></div>
  <div class="ttd-cell">
    <div style="font-size:8pt;"><?= esc($school['city_regency'] ?? 'Kota') ?>, <?= date('d F Y') ?></div>
    <div style="font-size:8pt; margin-top:3pt;">Guru BK / Koordinator BK,</div>
    <div class="ttd-name">______________________</div>
    <div class="ttd-nip">NIP. ___________________</div>
  </div>
</div>

<div class="dicetak">
  Dicetak oleh: <?= esc($user['fullname'] ?? $user['name'] ?? 'Sistem') ?> &nbsp;|&nbsp; <?= esc($printAt) ?>
</div>

</body>
</html>
