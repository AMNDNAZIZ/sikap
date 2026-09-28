<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9pt; color: #1a1a2e; background: #fff; }

  /* ── KOP ─────────────────────────────────────────────────────── */
  .kop { display: table; width: 100%; border-bottom: 2.5pt solid #003580; padding-bottom: 6pt; margin-bottom: 8pt; }
  .kop-logo { display: table-cell; width: 60pt; vertical-align: middle; }
  .kop-logo img { max-width: 52pt; max-height: 52pt; }
  .kop-text { display: table-cell; vertical-align: middle; padding-left: 8pt; }
  .kop-text .sekolah { font-size: 13pt; font-weight: bold; text-transform: uppercase; color: #003580; }
  .kop-text .alamat  { font-size: 7.5pt; color: #555; margin-top: 2pt; }
  .kop-text .npsn    { font-size: 7.5pt; color: #555; }

  /* ── JUDUL LAPORAN ────────────────────────────────────────────── */
  .judul-blok { text-align: center; margin-bottom: 10pt; }
  .judul-blok h1 { font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: .5pt; }
  .judul-blok h2 { font-size: 9.5pt; font-weight: bold; text-transform: uppercase; }
  .judul-blok .periode { font-size: 9pt; color: #444; margin-top: 3pt; }
  .garis-judul { border-top: 1pt solid #003580; border-bottom: .5pt solid #003580; height: 3pt; margin: 5pt 0 10pt; }

  /* ── SECTION LABEL ────────────────────────────────────────────── */
  .sec-label { font-size: 9pt; font-weight: bold; color: #003580; border-left: 3pt solid #003580;
               padding-left: 5pt; margin: 8pt 0 4pt; }

  /* ── STAT BOXES ───────────────────────────────────────────────── */
  .stat-row { display: table; width: 100%; margin-bottom: 8pt; border-collapse: separate; border-spacing: 4pt; }
  .stat-cell { display: table-cell; width: 16.6%; border: .5pt solid #ccc; border-radius: 3pt;
               text-align: center; padding: 5pt 3pt; background: #f5f8ff; }
  .stat-cell .num { font-size: 14pt; font-weight: bold; color: #003580; }
  .stat-cell .lbl { font-size: 6.5pt; color: #555; margin-top: 1pt; }

  /* ── TABEL REKAP ──────────────────────────────────────────────── */
  table.rekap { width: 100%; border-collapse: collapse; margin-bottom: 6pt; }
  table.rekap th { background: #003580; color: #fff; font-size: 8pt; padding: 4pt 5pt; text-align: left; }
  table.rekap td { padding: 3pt 5pt; font-size: 8pt; border-bottom: .3pt solid #dde; }
  table.rekap tr:nth-child(even) td { background: #f0f4ff; }
  table.rekap .num-col { text-align: center; font-weight: bold; }
  table.rekap tfoot td { background: #e8eef8; font-weight: bold; border-top: .8pt solid #003580; }

  /* ── TABEL DETAIL LAYANAN (landscape) ───────────────────────── */
  table.detail { width: 100%; border-collapse: collapse; margin-bottom: 6pt; font-size: 7.5pt; }
  table.detail th { background: #003580; color: #fff; padding: 3.5pt 4pt; text-align: left; }
  table.detail td { padding: 3pt 4pt; border-bottom: .3pt solid #dde; vertical-align: top; }
  table.detail tr:nth-child(even) td { background: #f7f9ff; }
  .badge { display: inline-block; padding: 1pt 4pt; border-radius: 3pt; font-size: 6.5pt; font-weight: bold; }
  .badge-s  { background: #d4edda; color: #155724; }
  .badge-w  { background: #fff3cd; color: #856404; }
  .badge-d  { background: #f8d7da; color: #721c24; }
  .badge-i  { background: #d1ecf1; color: #0c5460; }
  .badge-p  { background: #cce5ff; color: #004085; }
  .badge-sec{ background: #e2e3e5; color: #383d41; }

  /* ── INFO FILTER ──────────────────────────────────────────────── */
  .info-filter { background: #f0f4ff; border: .5pt solid #b8c9f0; border-radius: 3pt;
                 padding: 5pt 8pt; font-size: 7.5pt; color: #333; margin-bottom: 8pt; }
  .info-filter span { margin-right: 12pt; }

  /* ── FOOTER ───────────────────────────────────────────────────── */
  .footer { position: fixed; bottom: 10pt; left: 0; right: 0; text-align: center; font-size: 7pt; color: #888;
            border-top: .5pt solid #ccc; padding-top: 3pt; }
  .dicetak-oleh { font-size: 7.5pt; color: #555; margin-top: 16pt; border-top: .5pt solid #ddd; padding-top: 4pt; }

  /* ── TTD BLOK ─────────────────────────────────────────────────── */
  .ttd-row { display: table; width: 100%; margin-top: 30pt; }
  .ttd-cell { display: table-cell; width: 33%; text-align: center; font-size: 8pt; padding: 0 8pt; }
  .ttd-cell .ttd-name { border-top: .8pt solid #333; margin-top: 40pt; padding-top: 3pt; font-weight: bold; }
  .ttd-cell .ttd-nip  { font-size: 7pt; color: #555; }

  @page { size: A4 landscape; margin: 1.5cm; }
  .page-break { page-break-after: always; }
</style>
</head>
<body>

<!-- FOOTER halaman (Dompdf posisi fixed) -->
<div class="footer">
  Laporan Rekap Layanan BK &nbsp;|&nbsp;
  Dicetak oleh: <?= esc($user['fullname'] ?? $user['name'] ?? 'Sistem') ?> &nbsp;|&nbsp;
  <?= esc($printAt) ?>
  <span style="float:right; margin-right:15pt;">Hal. <span class="page"></span> / <span class="pagecount"></span></span>
</div>

<?php
// helper badge status
function badgeLayanan(string $s): string {
    return match($s) {
        'Selesai'      => 'badge-s',
        'Tindak Lanjut','Evaluasi' => 'badge-w',
        'Batal'        => 'badge-d',
        'Pelaksanaan'  => 'badge-p',
        default        => 'badge-sec',
    };
}

$semLabel = match((string)($filter['semester'] ?? '')) {
    '1' => 'Semester 1 (Ganjil)', '2' => 'Semester 2 (Genap)', default => 'Semua Semester',
};

$byType  = $rekap['byType'];
$byField = $rekap['byField'];
$byClass = $rekap['byClass'];
$total   = count($rows);
?>

<!-- ══ HALAMAN 1 — KOP + RINGKASAN ══════════════════════════════ -->

<!-- Kop Sekolah -->
<div class="kop">
  <div class="kop-logo">
    <?php if (!empty($logoB64)): ?>
      <img src="<?= $logoB64 ?>" alt="Logo">
    <?php endif; ?>
  </div>
  <div class="kop-text">
    <div class="sekolah"><?= esc($school['name'] ?? 'NAMA SEKOLAH') ?></div>
    <div class="alamat"><?= esc($school['address'] ?? '') ?><?= !empty($school['city_regency']) ? ', ' . esc($school['city_regency']) : '' ?></div>
    <div class="npsn">Telp: <?= esc($school['phone'] ?? '-') ?> &nbsp;|&nbsp; Email: <?= esc($school['email'] ?? '-') ?></div>
  </div>
</div>

<!-- Judul -->
<div class="judul-blok">
  <h1>Laporan Rekapitulasi Layanan Bimbingan dan Konseling</h1>
  <h2><?= esc($school['name'] ?? '') ?></h2>
  <div class="periode">
    <?= esc($semLabel) ?> &nbsp;|&nbsp; Tahun Ajaran <?= esc($year['year'] ?? '—') ?>
    <?php if (!empty($filter['date_from'])): ?>
      &nbsp;|&nbsp; Periode: <?= esc($filter['date_from']) ?> s.d. <?= esc($filter['date_to'] ?? '—') ?>
    <?php endif; ?>
  </div>
</div>
<div class="garis-judul"></div>

<!-- Filter aktif -->
<div class="info-filter">
  <span><b>Tahun Ajaran:</b> <?= esc($year['year'] ?? '—') ?></span>
  <span><b>Semester:</b> <?= esc($semLabel) ?></span>
  <?php if (!empty($filter['service_type'])): ?><span><b>Jenis:</b> <?= esc($filter['service_type']) ?></span><?php endif; ?>
  <?php if (!empty($filter['class_id'])): ?>
    <?php $cl = array_filter($classes ?? [], fn($c) => $c['id'] == $filter['class_id']); $cl = reset($cl); ?>
    <?php if ($cl): ?><span><b>Kelas:</b> <?= esc($cl['name']) ?></span><?php endif; ?>
  <?php endif; ?>
</div>

<!-- A. Ringkasan -->
<div class="sec-label">A. Ringkasan Layanan</div>

<div class="stat-row">
  <div class="stat-cell">
    <div class="num"><?= $total ?></div>
    <div class="lbl">Total Layanan</div>
  </div>
  <?php $statTypes = ['Bimbingan Klasikal','Bimbingan Kelompok','Konseling Individual','Konseling Kelompok','Konsultasi','Rujukan']; ?>
  <?php foreach ($statTypes as $st): ?>
  <div class="stat-cell">
    <div class="num"><?= $byType[$st] ?? 0 ?></div>
    <div class="lbl"><?= $st ?></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- B. Rekap per jenis + bidang -->
<table width="100%"><tr valign="top">
  <td width="48%" style="padding-right:6pt;">
    <div class="sec-label">B. Rekap per Jenis Layanan</div>
    <table class="rekap">
      <thead><tr><th>Jenis Layanan</th><th style="width:60pt;" class="num-col">Jumlah</th><th style="width:50pt;" class="num-col">%</th></tr></thead>
      <tbody>
      <?php foreach ($byType as $t => $j): ?>
      <tr>
        <td><?= esc($t) ?></td>
        <td class="num-col"><?= $j ?></td>
        <td class="num-col"><?= $total > 0 ? round($j/$total*100, 1) : 0 ?>%</td>
      </tr>
      <?php endforeach; ?>
      </tbody>
      <tfoot><tr><td>Total</td><td class="num-col"><?= $total ?></td><td class="num-col">100%</td></tr></tfoot>
    </table>
  </td>
  <td width="26%" style="padding-right:6pt;">
    <div class="sec-label">C. Rekap per Bidang</div>
    <table class="rekap">
      <thead><tr><th>Bidang</th><th style="width:50pt;" class="num-col">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($byField as $f => $j): ?>
      <tr><td><?= esc($f) ?></td><td class="num-col"><?= $j ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </td>
  <td width="26%">
    <div class="sec-label">D. Rekap per Kelas</div>
    <table class="rekap">
      <thead><tr><th>Kelas</th><th style="width:50pt;" class="num-col">Jumlah</th></tr></thead>
      <tbody>
      <?php foreach ($byClass as $k => $j): ?>
      <tr><td><?= esc($k) ?></td><td class="num-col"><?= $j ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </td>
</tr></table>

<!-- ══ HALAMAN 2 — TABEL DETAIL ════════════════════════════════ -->
<div class="page-break"></div>

<!-- Kop ulang halaman 2 -->
<div style="text-align:center; font-size:9pt; font-weight:bold; margin-bottom:6pt; border-bottom:.5pt solid #003580; padding-bottom:4pt;">
  LAPORAN REKAPITULASI LAYANAN BK &nbsp;|&nbsp; <?= esc($year['year'] ?? '') ?> &nbsp;|&nbsp; <?= esc($semLabel) ?>
</div>

<div class="sec-label">E. Rekapitulasi Layanan (<?= $total ?> Layanan)</div>
<p style="font-size:7pt; color:#777; margin-bottom:4pt;">
  * Catatan konseling rahasia (isi sesi, kebutuhan, perjanjian) tidak ditampilkan dalam laporan ini.
</p>

<table class="detail">
  <thead>
    <tr>
      <th style="width:20pt;">No</th>
      <th style="width:48pt;">Tanggal</th>
      <th style="width:80pt;">Jenis Layanan</th>
      <th style="width:45pt;">Bidang</th>
      <th>Topik / Materi</th>
      <th style="width:55pt;">Kelas</th>
      <th style="width:30pt; text-align:center;">Peserta</th>
      <th style="width:70pt;">Konselor</th>
      <th style="width:55pt;">Status</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($rows as $i => $r): ?>
  <tr>
    <td style="text-align:center; color:#888;"><?= $i + 1 ?></td>
    <td><?= date('d/m/Y', strtotime($r['service_date'])) ?></td>
    <td><?= esc($r['service_type']) ?></td>
    <td><?= esc($r['field']) ?></td>
    <td><?= esc($r['topic']) ?><?php if (!empty($r['material'])): ?><br><span style="font-size:6.5pt; color:#666;"><?= esc(mb_substr($r['material'], 0, 60)) ?><?= mb_strlen($r['material']) > 60 ? '…' : '' ?></span><?php endif; ?></td>
    <td><?= esc($r['class_name'] ?: '(Individu)') ?></td>
    <td style="text-align:center; font-weight:bold;"><?= (int)$r['jumlah_peserta'] ?></td>
    <td><?= esc($r['counselor_name'] ?: '—') ?></td>
    <td><span class="badge <?= badgeLayanan($r['status']) ?>"><?= esc($r['status']) ?></span></td>
  </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- TTD -->
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
  Dicetak oleh: <?= esc($user['fullname'] ?? $user['name'] ?? 'Sistem') ?> &nbsp;|&nbsp; <?= esc($printAt) ?>
</div>

</body>
</html>
