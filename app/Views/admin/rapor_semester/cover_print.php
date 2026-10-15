<?php
/**
 * @var array<string, mixed> $class
 * @var array<string, mixed> $school
 * @var array<string, mixed> $activeYear
 * @var array<int, array<string, mixed>> $students
 * @var string $titimangsaDate
 * @var string $titimangsaCity
 * @var bool $showSignature
 * @var bool $showWatermark
 * @var string $logoBase64
 * @var string $regionalLogoBase64
 * @var string $jenjangText
 * @var string $jenjangCode
 * @var string $principalSigBase64
 * @var bool $isSingle
 */
$isSingle           = $isSingle ?? false;
$class              = (array) ($class ?? []);
$school             = (array) ($school ?? []);
$activeYear         = (array) ($activeYear ?? []);
$students           = (array) ($students ?? []);
$titimangsaDate     = $titimangsaDate ?? date('d F Y');
$titimangsaCity     = $titimangsaCity ?? 'Jakarta';
$showSignature      = !empty($showSignature);
$showWatermark      = !empty($showWatermark);
$logoBase64         = $logoBase64 ?? '';
$regionalLogoBase64 = $regionalLogoBase64 ?? '';
$principalSigBase64 = $principalSigBase64 ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Kelengkapan Rapor – <?= esc($class['name'] ?? '') ?></title>
  <style>
    @page { 
      size: A4 portrait; 
      margin: 1.5cm 2cm; 
    }
    * { box-sizing: border-box; }
    body {
      font-family: 'Times New Roman', Times, serif;
      font-size: 11pt;
      color: #000;
      line-height: 1.4;
      margin: 0;
      background-color: #fff;
    }

    .doc-page {
      page-break-after: always;
      page-break-inside: avoid;
      position: relative;
      min-height: 25cm;
    }
    .doc-page:last-child {
      page-break-after: auto;
    }

    /* ── Watermark ── */
    .watermark-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0.08;
      width: 380px;
      pointer-events: none;
      z-index: 0;
      text-align: center;
    }
    .watermark-container img {
      width: 100%;
      height: auto;
      display: block;
    }

    .content-relative {
      position: relative;
      z-index: 1;
    }

    /* ── No Print Toolbar ── */
    .no-print {
      background: #f8f9fa;
      border-bottom: 1px solid #ddd;
      padding: 10px 15px;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }
    .btn-print { background: #0d6efd; color: #fff; padding: 6px 16px; border: 1px solid #0d6efd; border-radius: 4px; font-weight: bold; cursor: pointer; }
    .btn-close { background: #6c757d; color: #fff; padding: 6px 16px; border: 1px solid #6c757d; border-radius: 4px; font-weight: bold; cursor: pointer; }

    /* ── COVER DEPAN RAPOR SESUAI FORMAT GAMBAR RESMI (A4) ── */
    .cover-page {
      font-family: Arial, Helvetica, sans-serif !important;
      text-align: center;
      min-height: 25.5cm;
      padding-top: 5mm;
      padding-bottom: 5mm;
      box-sizing: border-box;
    }
    .cover-top-logo {
      margin-bottom: 18px;
    }
    .cover-top-logo img {
      width: 100px;
      height: auto;
      max-height: 115px;
      display: inline-block;
    }
    .cover-title-group {
      margin-bottom: 35px;
    }
    .cover-title-main {
      font-size: 19pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    .cover-title-sub {
      font-size: 16pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 4px;
      text-transform: uppercase;
    }
    .cover-title-code {
      font-size: 16pt;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
    }
    .cover-school-logo {
      margin-bottom: 40px;
    }
    .cover-school-logo img {
      width: 125px;
      height: auto;
      max-height: 135px;
      display: inline-block;
    }
    .cover-student-group {
      margin-bottom: 45px;
    }
    .cover-student-label {
      font-size: 12.5pt;
      font-weight: bold;
      margin-bottom: 6px;
      color: #000;
    }
    .cover-student-input {
      border: 1.5px solid #000;
      width: 68%;
      max-width: 440px;
      margin: 0 auto;
      padding: 7px 15px;
      font-size: 13.5pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      text-transform: uppercase;
      background: #fff;
      box-sizing: border-box;
    }
    .cover-footer-dept {
      margin-top: 50px;
      font-size: 14pt;
      font-weight: bold;
      line-height: 1.45;
      letter-spacing: 0.5px;
      text-transform: uppercase;
    }

    /* ── Identitas & Petunjuk Styling ── */
    .page-title {
      text-align: center;
      font-size: 13pt;
      font-weight: bold;
      text-transform: uppercase;
      margin-bottom: 25px;
      letter-spacing: 0.5px;
    }
    .data-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }
    .data-table td {
      padding: 5px 6px;
      vertical-align: top;
      font-size: 10.5pt;
    }
    .label-col { width: 35%; }
    .sep-col   { width: 3%; }
    .val-col   { width: 62%; font-weight: 500; }

    .foto-box {
      width: 3cm;
      height: 4cm;
      border: 1px solid #000;
      text-align: center;
      line-height: 4cm;
      font-size: 9pt;
      color: #666;
      margin: 20px 0;
      display: inline-block;
    }

    /* ── HALAMAN 3: IDENTITAS SATUAN PENDIDIKAN SESUAI FORMAT GAMBAR ── */
    .school-ident-page {
      font-family: Arial, Helvetica, sans-serif !important;
      padding-top: 15mm;
      box-sizing: border-box;
    }
    .school-ident-title-group {
      text-align: center;
      margin-bottom: 50px;
    }
    .school-ident-title-main {
      font-size: 15pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
      text-transform: uppercase;
      color: #000;
    }
    .school-ident-title-sub {
      font-size: 14pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-bottom: 5px;
      text-transform: uppercase;
      color: #000;
    }
    .school-ident-title-code {
      font-size: 14pt;
      font-weight: bold;
      letter-spacing: 1px;
      text-transform: uppercase;
      color: #000;
    }
    .school-ident-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 11pt;
      margin-top: 10px;
    }
    .school-ident-table td {
      padding: 9px 4px;
      vertical-align: top;
      color: #000;
    }
    .school-ident-label {
      width: 28%;
      white-space: nowrap;
    }
    .school-ident-sep {
      width: 3%;
      text-align: center;
    }
    .school-ident-val {
      width: 69%;
      word-break: break-word;
    }

    /* ── HALAMAN 4: IDENTITAS PESERTA DIDIK (SESUAI GAMBAR RESMI) ── */
    .student-ident-page {
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 10pt;
      line-height: 1.35;
      color: #000;
      page-break-after: always;
      page-break-inside: avoid;
      box-sizing: border-box;
      padding-top: 5mm;
    }
    .student-ident-title {
      text-align: center;
      font-size: 13.5pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-top: 5px;
      margin-bottom: 22px;
      text-transform: uppercase;
      color: #000;
    }
    .student-ident-table {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 10pt;
    }
    .student-ident-table td {
      padding: 3px 2px;
      vertical-align: top;
      color: #000;
    }
    .student-num-col {
      width: 28px;
      vertical-align: top;
      white-space: nowrap;
    }
    .student-label-col {
      width: 250px;
      vertical-align: top;
    }
    .student-sub-label {
      padding-left: 20px !important;
    }
    .student-sep-col {
      width: 20px;
      text-align: center;
      vertical-align: top;
    }
    .student-val-col {
      vertical-align: top;
      word-break: break-word;
    }
    .student-footer-table {
      width: 100%;
      margin-top: 25px;
      border-collapse: collapse;
      border: none !important;
    }
    .student-footer-table td {
      border: none !important;
      padding: 0 !important;
    }
    .student-photo-box {
      width: 3cm;
      height: 4cm;
      border: 1.5px solid #000;
      box-sizing: border-box;
      background-color: #fff;
      display: inline-block;
      overflow: hidden;
    }
    .student-ttd-container {
      margin-top: 25px;
      width: 100%;
      overflow: hidden;
    }
    .student-ttd-box {
      float: right;
      width: 270px;
      text-align: left;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 10pt;
      line-height: 1.35;
    }
    .student-ttd-date {
      margin-bottom: 2px;
    }
    .student-ttd-role {
      margin-bottom: 0;
    }
    .student-ttd-space {
      height: 55px;
      display: flex;
      align-items: center;
    }
    .student-ttd-img {
      max-height: 50px;
      display: block;
    }
    .student-ttd-name {
      font-weight: bold;
      text-decoration: underline;
    }
    .student-ttd-nip {
      font-weight: bold;
      margin-top: 2px;
    }

    /* ── HALAMAN 5 & 6: KELENGKAPAN RAPOR - LEMBAR MUTASI (KELUAR & MASUK) ── */
    .mutasi-page {
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 10pt;
      line-height: 1.35;
      color: #000;
      page-break-after: always;
      page-break-inside: avoid;
      box-sizing: border-box;
      padding-top: 5mm;
    }
    .mutasi-header-title {
      text-align: center;
      font-size: 13pt;
      font-weight: bold;
      letter-spacing: 0.5px;
      margin-top: 5px;
      margin-bottom: 22px;
      text-transform: uppercase;
      color: #000;
    }
    .mutasi-student-bar {
      margin-bottom: 15px;
      font-size: 10.5pt;
      color: #000;
      font-family: Arial, Helvetica, sans-serif !important;
    }
    .mutasi-student-name-val {
      font-weight: bold;
      letter-spacing: 0.5px;
    }

    /* Tabel Mutasi Keluar */
    .table-mutasi-keluar {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 9.5pt;
      color: #000;
    }
    .table-mutasi-keluar th,
    .table-mutasi-keluar td {
      border: 1px solid #000;
      box-sizing: border-box;
    }
    .th-keluar-main {
      font-size: 11pt;
      padding: 7px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      text-align: center;
      font-weight: bold;
    }
    .table-mutasi-keluar thead th {
      text-align: center;
      font-weight: bold;
      vertical-align: middle;
      padding: 8px 4px;
    }
    .td-keluar-empty {
      height: 195px;
      vertical-align: top;
    }
    .td-keluar-ttd {
      padding: 8px 10px;
      font-size: 9pt;
      line-height: 1.3;
      vertical-align: top;
    }
    .ttd-date-dots {
      margin-bottom: 2px;
    }
    .ttd-label {
      margin-bottom: 0;
    }
    .ttd-space-kepsek {
      height: 48px;
    }
    .ttd-space-ortu {
      height: 44px;
    }
    .ttd-line-solid {
      border-bottom: 1px solid #000;
      width: 100%;
    }
    .ttd-nip {
      margin-top: 2px;
      font-weight: bold;
    }
    .ttd-label-ortu {
      margin-top: 8px;
    }

    /* Tabel Mutasi Masuk */
    .table-mutasi-masuk {
      width: 100%;
      border-collapse: collapse;
      font-family: Arial, Helvetica, sans-serif !important;
      font-size: 9.5pt;
      color: #000;
    }
    .table-mutasi-masuk th,
    .table-mutasi-masuk td {
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      box-sizing: border-box;
    }
    .th-masuk-no {
      width: 6%;
      text-align: center;
      font-weight: bold;
      padding: 6px 2px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .th-masuk-main {
      font-size: 11pt;
      text-align: center;
      font-weight: bold;
      padding: 6px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .td-masuk-no {
      width: 6%;
      text-align: center;
      vertical-align: middle;
      padding: 4px 2px;
      border-top: none;
      border-bottom: none;
    }
    .td-masuk-lbl {
      width: 30%;
      vertical-align: middle;
      padding: 4px 6px;
      border-top: none;
      border-bottom: none;
    }
    .td-masuk-lbl.sub-indent {
      padding-left: 20px;
    }
    .td-masuk-val {
      width: 29%;
      vertical-align: middle;
      padding: 4px 6px;
      border-top: none;
      border-bottom: none;
    }
    .line-dashed-masuk {
      border-bottom: 1px dashed #666;
      width: 95%;
      height: 10px;
    }
    .td-masuk-ttd {
      width: 35%;
      padding: 10px 10px;
      font-size: 9pt;
      line-height: 1.3;
      vertical-align: top;
      border-bottom: 1px solid #000;
    }
    .ttd-space-kepsek-masuk {
      height: 105px;
    }
    .ttd-line-dotted {
      border-bottom: 1px dotted #000;
      width: 100%;
    }
    .border-top-thick {
      border-top: 1px solid #000 !important;
    }
    .border-bottom-thick {
      border-bottom: 1px solid #000 !important;
    }

    @media print {
      .no-print { display: none !important; }
      body { margin: 0; }
    }
  </style>
</head>
<body onload="<?= $isSingle ? 'window.print()' : '' ?>">

  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Kelengkapan Rapor</button>
    <button onclick="window.close()" class="btn-close">❌ Tutup</button>
    <span style="color:#555;font-size:9.5pt;margin-left:10px;">
      Kelengkapan Rapor (Cover, Petunjuk, Identitas Sekolah, Identitas Peserta Didik & Lembar Mutasi) – Kelas <?= esc($class['name'] ?? '-') ?>
    </span>
  </div>

<?php
  // Deteksi jenjang dinamis jika variabel belum dioper
  if (empty($jenjangText)) {
      $lvl = isset($school['level']) ? (int) $school['level'] : 0;
      $cLvl = isset($class['level']) ? (int) $class['level'] : 0;
      if ($lvl === 1 || ($lvl === 0 && $cLvl >= 1 && $cLvl <= 6) || ($lvl === 0 && stripos($school['name'] ?? '', 'SD') !== false)) {
          $jenjangText = 'SEKOLAH DASAR';
          $jenjangCode = '( SD )';
      } elseif ($lvl === 3 || ($lvl === 0 && $cLvl >= 10 && $cLvl <= 12) || ($lvl === 0 && stripos($school['name'] ?? '', 'SMA') !== false)) {
          $jenjangText = 'SEKOLAH MENENGAH ATAS';
          $jenjangCode = '( SMA )';
      } elseif ($lvl === 4 || ($lvl === 0 && stripos($school['name'] ?? '', 'SMK') !== false)) {
          $jenjangText = 'SEKOLAH MENENGAH KEJURUAN';
          $jenjangCode = '( SMK )';
      } else {
          $jenjangText = 'SEKOLAH MENENGAH PERTAMA';
          $jenjangCode = '( SMP )';
      }
  }

  $formatIndoDate = function ($dateStr) {
      if (empty($dateStr) || $dateStr === '0000-00-00') return '-';
      $ts = strtotime($dateStr);
      if (!$ts) return '-';
      $months = [
          1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
          5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
          9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
      ];
      $m = (int) date('n', $ts);
      return date('j', $ts) . ' ' . ($months[$m] ?? date('F', $ts)) . ' ' . date('Y', $ts);
  };

  $indoTitimangsaDate = !empty($titimangsaDate) 
      ? $formatIndoDate($titimangsaDate) 
      : $formatIndoDate(date('Y-m-d'));
?>

<?php foreach ($students as $stu): ?>
  <!-- ══════════════════════════════════════════════
       HALAMAN 1: COVER DEPAN RAPOR
       ══════════════════════════════════════════════ -->
  <div class="doc-page cover-page">
    <div class="content-relative">
      <!-- 1. Logo Daerah (Atas) -->
      <div class="cover-top-logo">
        <?php if (!empty($regionalLogoBase64)): ?>
          <img src="<?= $regionalLogoBase64 ?>" alt="Logo Daerah">
        <?php endif; ?>
      </div>

      <!-- 2. Judul Rapor -->
      <div class="cover-title-group">
        <div class="cover-title-main">RAPOR</div>
        <div class="cover-title-sub"><?= esc($jenjangText ?? 'SEKOLAH MENENGAH PERTAMA') ?></div>
        <div class="cover-title-code"><?= esc($jenjangCode ?? '( SMP )') ?></div>
      </div>

      <!-- 3. Logo Sekolah (Tengah) -->
      <div class="cover-school-logo">
        <?php if (!empty($logoBase64)): ?>
          <img src="<?= $logoBase64 ?>" alt="Logo Sekolah">
        <?php else: ?>
          <div style="font-size:45pt;color:#333;">🏫</div>
        <?php endif; ?>
      </div>

      <!-- 4. Identitas Siswa -->
      <div class="cover-student-group">
        <div class="cover-student-label">Nama Peserta Didik</div>
        <div class="cover-student-input">
          <?= esc(strtoupper($stu['name'])) ?>
        </div>

        <div class="cover-student-label" style="margin-top:22px;">NISN / NIS</div>
        <div class="cover-student-input">
          <?= esc(($stu['nisn'] ?: '-') . ' / ' . ($stu['nis'] ?: '-')) ?>
        </div>
      </div>

      <!-- 5. Footer Kementerian Pendidikan Dasar dan Menengah -->
       <br><br>
      <div class="cover-footer-dept">
        <div>KEMENTERIAN PENDIDIKAN DASAR</div>
        <div>DAN MENENGAH</div>
        <div>REPUBLIK INDONESIA</div>
      </div>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 2: PETUNJUK PENGISIAN RAPOR
       ══════════════════════════════════════════════ -->
  <div class="doc-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="page-title">PETUNJUK PENGISIAN RAPOR HASIL BELAJAR</div>

      <ol style="line-height:1.6;font-size:10.5pt;text-align:justify;padding-left:20px;">
        <li>Rapor Hasil Belajar Peserta Didik dipergunakan selama peserta didik mengikuti seluruh program pembelajaran di satuan pendidikan tersebut.</li>
        <li>Identitas Satuan Pendidikan dan Identitas Peserta Didik diisi sesuai dengan data resmi pada Data Pokok Pendidikan (DAPODIK) dan Buku Induk Sekolah.</li>
        <li>Laporan Hasil Belajar memuat 3 (tiga) pilar struktur Kurikulum Merdeka yang meliputi:
          <ul>
            <li><strong>Intrakurikuler:</strong> Memuat nilai akhir hasil pengolahan asesmen sumatif lingkup materi serta capaian kompetensi tertinggi yang dikuasai dan kompetensi yang masih memerlukan bimbingan.</li>
            <li><strong>Kokurikuler:</strong> Memuat laporan ketercapaian dimensi profil kelulusan peserta didik melalui kegiatan kokurikuler / pembelajaran berbasis projek.</li>
            <li><strong>Ekstrakurikuler:</strong> Memuat keaktifan dan perkembangan karakter peserta didik dalam kegiatan ekstrakurikuler yang diikuti.</li>
          </ul>
        </li>
        <li>Ketidakhadiran mencatat jumlah hari peserta didik tidak hadir di sekolah karena Sakit (S), Izin (I), atau Tanpa Keterangan/Alpa (A) selama satu semester berjalan.</li>
        <li>Catatan Wali Kelas memberikan refleksi perkembangan personal, apresiasi kemajuan belajar, serta arahan motivasi yang membangun.</li>
        <li>Pada semester 2 (Genap), tercantum Keputusan Kenaikan Kelas atau Kelulusan peserta didik berdasarkan kriteria ketuntasan tujuan pembelajaran yang ditetapkan satuan pendidikan.</li>
        <li>Tanggapan Orang Tua/Wali Murid disediakan sebagai sarana komunikasi dua arah dan refleksi bersama antara pihak sekolah dan orang tua.</li>
      </ol>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 3: IDENTITAS SATUAN PENDIDIKAN
       ══════════════════════════════════════════════ -->
  <div class="doc-page school-ident-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="school-ident-title-group">
        <div class="school-ident-title-main">RAPOR</div>
        <div class="school-ident-title-sub"><?= esc($jenjangText ?? 'SEKOLAH MENENGAH PERTAMA') ?></div>
        <div class="school-ident-title-code"><?= esc($jenjangCode ?? '( SMP )') ?></div>
      </div>

      <table class="school-ident-table">
        <tr>
          <td class="school-ident-label">Nama Sekolah</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc($school['name'] ?? '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">NPSN</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['npsn']) ? $school['npsn'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">NIS/NSS/NDS</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['nis_nss_nds']) ? $school['nis_nss_nds'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Alamat Sekolah</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc($school['address'] ?? '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Kelurahan / Desa</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['village']) ? $school['village'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Kecamatan</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['district']) ? $school['district'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Kota/Kabupaten</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc($school['city_regency'] ?? '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Provinsi</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['province']) ? $school['province'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">Website</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc(!empty($school['website']) ? $school['website'] : '-') ?></td>
        </tr>
        <tr>
          <td class="school-ident-label">E-mail</td>
          <td class="school-ident-sep">:</td>
          <td class="school-ident-val"><?= esc($school['email'] ?? '-') ?></td>
        </tr>
      </table>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 4: IDENTITAS PESERTA DIDIK
       ══════════════════════════════════════════════ -->
  <div class="doc-page student-ident-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="student-ident-title">IDENTITAS PESERTA DIDIK</div>

      <table class="student-ident-table">
        <!-- 1. Nama Lengkap Peserta Didik -->
        <tr>
          <td class="student-num-col">1.</td>
          <td class="student-label-col">Nama Lengkap Peserta Didik</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(strtoupper($stu['name'])) ?></td>
        </tr>

        <!-- 2. Nomor Induk/NISN -->
        <tr>
          <td class="student-num-col">2.</td>
          <td class="student-label-col">Nomor Induk/NISN</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['nis'] ?: '-') ?> / <?= esc($stu['nisn'] ?: '-') ?></td>
        </tr>

        <!-- 3. Tempat ,Tanggal Lahir -->
        <tr>
          <td class="student-num-col">3.</td>
          <td class="student-label-col">Tempat ,Tanggal Lahir</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col">
            <?= esc($stu['birth_place'] ?: '-') ?>, <?= !empty($stu['birth_date']) ? $formatIndoDate($stu['birth_date']) : '-' ?>
          </td>
        </tr>

        <!-- 4. Jenis Kelamin -->
        <tr>
          <td class="student-num-col">4.</td>
          <td class="student-label-col">Jenis Kelamin</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= ($stu['gender'] === 'L' || $stu['gender'] === 'laki-laki') ? 'Laki-laki' : 'Perempuan' ?></td>
        </tr>

        <!-- 5. Agama -->
        <tr>
          <td class="student-num-col">5.</td>
          <td class="student-label-col">Agama</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['religion'] ?? '-') ?></td>
        </tr>

        <!-- 6. Status dalam Keluarga -->
        <tr>
          <td class="student-num-col">6.</td>
          <td class="student-label-col">Status dalam Keluarga</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['family_status']) ? $stu['family_status'] : 'Anak Kandung') ?></td>
        </tr>

        <!-- 7. Anak ke -->
        <tr>
          <td class="student-num-col">7.</td>
          <td class="student-label-col">Anak ke</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['child_order'] ?? '1') ?></td>
        </tr>

        <!-- 8. Alamat Peserta Didik -->
        <tr>
          <td class="student-num-col">8.</td>
          <td class="student-label-col">Alamat Peserta Didik</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['address'] ?? '-') ?></td>
        </tr>

        <!-- 9. Nomor Telepon Rumah -->
        <tr>
          <td class="student-num-col">9.</td>
          <td class="student-label-col">Nomor Telepon Rumah</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['phone'] ?? '') ?></td>
        </tr>

        <!-- 10. Sekolah Asal (SD/MI) -->
        <tr>
          <td class="student-num-col">10.</td>
          <td class="student-label-col">Sekolah Asal (SD/MI)</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['school_origin'] ?? '') ?></td>
        </tr>

        <!-- 11. Diterima di sekolah ini -->
        <tr>
          <td class="student-num-col">11.</td>
          <td class="student-label-col">Diterima di sekolah ini</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">Di kelas</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['admission_class']) ? $stu['admission_class'] : (!empty($class['level']) ? $class['level'] : ($class['name'] ?? '-'))) ?></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">Pada tanggal</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= !empty($stu['admission_date']) ? $formatIndoDate($stu['admission_date']) : (!empty($activeYear['start_date']) ? $formatIndoDate($activeYear['start_date']) : $indoTitimangsaDate) ?></td>
        </tr>

        <!-- 12. Nama Orang Tua -->
        <tr>
          <td class="student-num-col">12.</td>
          <td class="student-label-col">Nama Orang Tua</td>
          <td class="student-sep-col"></td>
          <td class="student-val-col"></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">a. Ayah</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['father_name']) ? strtoupper($stu['father_name']) : '') ?></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">b. Ibu</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['mother_name']) ? strtoupper($stu['mother_name']) : '') ?></td>
        </tr>

        <!-- 13. Alamat Orang Tua -->
        <tr>
          <td class="student-num-col">13.</td>
          <td class="student-label-col">Alamat Orang Tua</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['parent_address']) ? $stu['parent_address'] : ($stu['address'] ?? '-')) ?></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">Nomor Telepon Orang Tua</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['parent_phone'] ?? '') ?></td>
        </tr>

        <!-- 14. Pekerjaan Orang Tua -->
        <tr>
          <td class="student-num-col">14.</td>
          <td class="student-label-col">Pekerjaan Orang Tua</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">a. Ayah</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['father_job'] ?? '') ?></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">b. Ibu</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['mother_job'] ?? '') ?></td>
        </tr>

        <!-- 15. Nama Wali Siswa -->
        <tr>
          <td class="student-num-col">15.</td>
          <td class="student-label-col">Nama Wali Siswa</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc(!empty($stu['guardian_name']) ? strtoupper($stu['guardian_name']) : '') ?></td>
        </tr>

        <!-- 16. Alamat Wali Peserta Didik -->
        <tr>
          <td class="student-num-col">16.</td>
          <td class="student-label-col">Alamat Wali Peserta Didik</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['guardian_address'] ?? '') ?></td>
        </tr>
        <tr>
          <td></td>
          <td class="student-label-col student-sub-label">Nomor Telepon Rumah</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['guardian_phone'] ?? '') ?></td>
        </tr>

        <!-- 17. Pekerjaan Wali Peserta Didik -->
        <tr>
          <td class="student-num-col">17.</td>
          <td class="student-label-col">Pekerjaan Wali Peserta Didik</td>
          <td class="student-sep-col">:</td>
          <td class="student-val-col"><?= esc($stu['guardian_job'] ?? '') ?></td>
        </tr>
      </table>

      <!-- TTD Pengesahan Buku Induk Siswa & Kotak Foto 3x4 (Sejajar TTD) -->
      <table class="student-footer-table">
        <tr>
          <td style="width: 16%; vertical-align: bottom;"></td>
          <td style="width: 34%; vertical-align: bottom;">
            <div class="student-photo-box">
              <?php if (!empty($stu['photo']) && file_exists(FCPATH . 'uploads/students/' . $stu['photo'])): ?>
                <img src="<?= base_url('uploads/students/' . $stu['photo']) ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;display:block;">
              <?php endif; ?>
            </div>
          </td>
          <td style="width: 50%; vertical-align: bottom; text-align: right;">
            <div class="student-ttd-box">
              <div class="student-ttd-date"><?= esc($titimangsaCity) ?>, <?= esc($indoTitimangsaDate) ?></div>
              <div class="student-ttd-role">Kepala Sekolah</div>
              <div class="student-ttd-space">
                <?php if ($showSignature && !empty($principalSigBase64)): ?>
                  <img src="<?= $principalSigBase64 ?>" alt="TTD Kepala Sekolah" class="student-ttd-img">
                <?php endif; ?>
              </div>
              <div class="student-ttd-name"><?= esc($school['headmaster'] ?? '-') ?></div>
              <div class="student-ttd-nip">NIP. <?= esc(!empty($school['principal_nip']) ? $school['principal_nip'] : '0') ?></div>
            </div>
          </td>
        </tr>
      </table>

    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 5: KETERANGAN PINDAH SEKOLAH (KELUAR)
       ══════════════════════════════════════════════ -->
  <div class="doc-page mutasi-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="mutasi-header-title">KETERANGAN PINDAH SEKOLAH</div>

      <div class="mutasi-student-bar">
        <span>Nama Peserta Didik &nbsp;:&nbsp; </span>
        <span class="mutasi-student-name-val">
          <?php if (!empty($stu['name'])): ?>
            <?= esc(strtoupper($stu['name'])) ?>
          <?php else: ?>
            ........................................................
          <?php endif; ?>
        </span>
      </div>

      <table class="table-mutasi-keluar">
        <thead>
          <tr>
            <th colspan="4" class="th-keluar-main">KELUAR</th>
          </tr>
          <tr>
            <th style="width: 13%;">Tanggal</th>
            <th style="width: 16%;">Kelas yang<br>ditinggalkan</th>
            <th style="width: 35%;">Sebab-sebab Keluar atau<br>Atas Permintaan<br>(Tertulis)</th>
            <th style="width: 36%;">Tanda Tangan Kepala Sekolah,<br>Stempel Sekolah, dan Tanda<br>Tangan Orang Tua/Wali</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($k = 1; $k <= 3; $k++): ?>
            <tr>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-empty"></td>
              <td class="td-keluar-ttd">
                <div class="ttd-date-dots">..................................................</div>
                <div class="ttd-label">Kepala Sekolah,</div>
                <div class="ttd-space-kepsek"></div>
                <div class="ttd-line-solid"></div>
                <div class="ttd-nip">NIP.</div>
                <div class="ttd-label ttd-label-ortu">Orang Tua/Wali,</div>
                <div class="ttd-space-ortu"></div>
                <div class="ttd-line-solid"></div>
              </td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- ══════════════════════════════════════════════
       HALAMAN 6: KETERANGAN PINDAH SEKOLAH (MASUK)
       ══════════════════════════════════════════════ -->
  <div class="doc-page mutasi-page">
    <?php if ($showWatermark && !empty($logoBase64)): ?>
      <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Logo"></div>
    <?php endif; ?>

    <div class="content-relative">
      <div class="mutasi-header-title">KETERANGAN PINDAH SEKOLAH</div>

      <div class="mutasi-student-bar">
        <span>Nama Peserta Didik &nbsp;:&nbsp; </span>
        <span class="mutasi-student-name-val">
          <?php if (!empty($stu['name'])): ?>
            <?= esc(strtoupper($stu['name'])) ?>
          <?php else: ?>
            ........................................................
          <?php endif; ?>
        </span>
      </div>

      <table class="table-mutasi-masuk">
        <thead>
          <tr>
            <th class="th-masuk-no">NO</th>
            <th colspan="3" class="th-masuk-main">MASUK</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($b = 1; $b <= 3; $b++): ?>
            <!-- 1. Nama Siswa -->
            <tr>
              <td class="td-masuk-no <?= $b > 1 ? 'border-top-thick' : '' ?>">1.</td>
              <td class="td-masuk-lbl <?= $b > 1 ? 'border-top-thick' : '' ?>">Nama Siswa</td>
              <td class="td-masuk-val <?= $b > 1 ? 'border-top-thick' : '' ?>"><div class="line-dashed-masuk"></div></td>
              <td rowspan="7" class="td-masuk-ttd <?= $b > 1 ? 'border-top-thick' : '' ?>">
                <div class="ttd-date-dots">..................................................</div>
                <div class="ttd-label">Kepala Sekolah,</div>
                <div class="ttd-space-kepsek-masuk"></div>
                <div class="ttd-line-dotted"></div>
                <div class="ttd-nip">NIP.</div>
              </td>
            </tr>
            <!-- 2. Nomor Induk -->
            <tr>
              <td class="td-masuk-no">2.</td>
              <td class="td-masuk-lbl">Nomor Induk</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 3. Nama Sekolah -->
            <tr>
              <td class="td-masuk-no">3.</td>
              <td class="td-masuk-lbl">Nama Sekolah</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 4. Masuk di Sekolah ini: -->
            <tr>
              <td class="td-masuk-no">4.</td>
              <td class="td-masuk-lbl">Masuk di Sekolah ini:</td>
              <td class="td-masuk-val"></td>
            </tr>
            <!-- a. Tanggal -->
            <tr>
              <td class="td-masuk-no"></td>
              <td class="td-masuk-lbl sub-indent">a. Tanggal</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- b. Di Kelas -->
            <tr>
              <td class="td-masuk-no"></td>
              <td class="td-masuk-lbl sub-indent">b. Di Kelas</td>
              <td class="td-masuk-val"><div class="line-dashed-masuk"></div></td>
            </tr>
            <!-- 5. Tahun Pelajaran -->
            <tr>
              <td class="td-masuk-no border-bottom-thick">5.</td>
              <td class="td-masuk-lbl border-bottom-thick">Tahun Pelajaran</td>
              <td class="td-masuk-val border-bottom-thick"><div class="line-dashed-masuk"></div></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endforeach; ?>

</body>
</html>
