<?php
/**
 * @var array<string, mixed> $class
 * @var array<string, mixed> $school
 * @var string $semester
 * @var array<string, mixed> $activeYear
 * @var array<int, array<string, mixed>> $reportData
 * @var string $fase
 * @var string $titimangsaDate
 * @var string $titimangsaCity
 * @var string $ttdLayout
 * @var bool $showSignature
 * @var bool $showWatermark
 * @var string $logoBase64
 * @var string $principalSigBase64
 * @var bool $isSingle
 */
$isSingle           = !empty($isSingle);
$class              = (array) ($class ?? []);
$school             = (array) ($school ?? []);
$activeYear         = (array) ($activeYear ?? []);
$reportData         = (array) ($reportData ?? []);
$semester           = (string) ($semester ?? '1');
$fase               = (string) ($fase ?? 'A');
$titimangsaDate     = $titimangsaDate ?? date('d F Y');
$titimangsaCity     = $titimangsaCity ?? 'Jakarta';
$showSignature      = !empty($showSignature);
$showWatermark      = !empty($showWatermark);
$logoBase64         = $logoBase64 ?? '';
$principalSigBase64 = $principalSigBase64 ?? '';

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

$indoTitimangsaDate = !empty($titimangsaDate) ? $formatIndoDate($titimangsaDate) : $formatIndoDate(date('Y-m-d'));

$renderHeader = function ($stu, $class, $school, $fase, $semester, $activeYear) {
    ?>
    <div class="identitas-outer">
      <div class="identitas-grid">
        <div class="identitas-col">
          <div class="id-row"><span class="id-label">Nama Murid</span><span class="id-sep">:</span><span class="id-val"><?= esc(strtoupper($stu['name'])) ?></span></div>
          <div class="id-row"><span class="id-label">NIS/NISN</span><span class="id-sep">:</span><span class="id-val"><?= esc(($stu['nis'] ?: '-') . ' / ' . ($stu['nisn'] ?: '-')) ?></span></div>
          <div class="id-row"><span class="id-label">Sekolah</span><span class="id-sep">:</span><span class="id-val"><?= esc($school['name'] ?? '-') ?></span></div>
          <div class="id-row"><span class="id-label">Alamat</span><span class="id-sep">:</span><span class="id-val"><?= esc($school['address'] ?? '-') ?></span></div>
        </div>
        <div class="identitas-col">
          <div class="id-row"><span class="id-label">Kelas</span><span class="id-sep">:</span><span class="id-val"><?= esc($class['name'] ?? '-') ?></span></div>
          <div class="id-row"><span class="id-label">Fase</span><span class="id-sep">:</span><span class="id-val"><?= esc($fase ?? 'A') ?></span></div>
          <div class="id-row"><span class="id-label">Semester</span><span class="id-sep">:</span><span class="id-val"><?= esc($semester) ?></span></div>
          <div class="id-row"><span class="id-label">Tahun Ajaran</span><span class="id-sep">:</span><span class="id-val"><?= esc($activeYear['year'] ?? '-') ?></span></div>
        </div>
      </div>
    </div>
    <div class="header-divider"></div>
    <?php
};

$renderFooter = function ($stu, $class, $pageNum) {
    ?>
    <div class="rapor-footer">
      <div class="footer-left">
        <i><b><?= esc($class['name'] ?? '-') ?> &nbsp;|&nbsp; <?= esc(strtoupper($stu['name'])) ?> &nbsp;|&nbsp; <?= esc($stu['nis'] ?: $stu['nisn'] ?: '-') ?></b></i>
      </div>
      <div class="footer-right">
        <i>Halaman &nbsp;: &nbsp;<?= $pageNum ?></i>
      </div>
    </div>
    <?php
};
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Rapor Semester – <?= esc($class['name'] ?? '') ?> – Semester <?= esc($semester) ?></title>
  <style>
    @page { 
      size: A4 portrait; 
      margin: 1cm 1.5cm; 
    }
    * { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9.5pt;
      color: #000;
      line-height: 1.35;
      margin: 0;
      background-color: #fff;
    }

    /* ── Lembar Rapor per Halaman ── */
    .rapor-sheet, .rapor-sheet-last {
      page-break-after: always;
      page-break-inside: avoid;
      break-inside: avoid;
      position: relative;
      box-sizing: border-box;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .student-wrapper:last-child .rapor-sheet-last {
      page-break-after: auto;
    }

    .sheet-body {
      flex: 1 0 auto;
      width: 100%;
      position: relative;
      z-index: 1;
    }

    /* ── Watermark Background Logo ── */
    .watermark-container {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      opacity: 0.07;
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

    /* ── Toolbar Cetak (No Print) ── */
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
    .btn-close-window { background: #6c757d; color: #fff; padding: 6px 16px; border: 1px solid #6c757d; border-radius: 4px; font-weight: bold; cursor: pointer; }

    /* ── Identitas Siswa Header ── */
    .identitas-outer {
      margin-top: 2px;
      margin-bottom: 2px;
    }
    .identitas-grid {
      display: table;
      width: 100%;
      border-collapse: collapse;
    }
    .identitas-col {
      display: table-cell;
      width: 50%;
      vertical-align: top;
    }
    .identitas-col:first-child { padding-right: 20px; }
    .identitas-col:last-child  { padding-left: 10px; }

    .id-row {
      display: table;
      width: 100%;
      margin-bottom: 1.5px;
    }
    .id-label { display: table-cell; width: 100px; font-size: 9.5pt; vertical-align: top; }
    .id-sep   { display: table-cell; width: 14px; font-size: 9.5pt; vertical-align: top; }
    .id-val   { display: table-cell; font-size: 9.5pt; vertical-align: top; }

    .header-divider {
      border-top: 1.5px solid #000;
      margin: 5px 0 10px;
    }

    /* ── Judul Rapor ── */
    .judul-laporan {
      text-align: center;
      font-size: 12.5pt;
      font-weight: bold;
      text-transform: uppercase;
      margin: 4px 0 12px;
      letter-spacing: 0.5px;
    }

    /* ── Tabel Standar Rapor ── */
    .rapor-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 9px;
      font-size: 9pt;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .rapor-table th, .rapor-table td {
      border: 1px solid #000;
      padding: 4.5px 6px;
      vertical-align: top;
    }
    .rapor-table th {
      background-color: #f2f2f2;
      font-weight: bold;
      text-align: center;
    }
    .nomor { width: 38px; text-align: center; }
    .angka { width: 80px; text-align: center; }
    .desc  { text-align: justify; font-size: 8.8pt; line-height: 1.35; }

    /* ── Layout Presensi & Catatan Wali Kelas Sejajar ── */
    .side-by-side-grid {
      display: table;
      width: 100%;
      border-collapse: collapse;
      margin-top: 2px;
      margin-bottom: 8px;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .side-by-side-grid .col-left {
      display: table-cell;
      width: 32%;
      vertical-align: top;
      padding-right: 12px;
    }
    .side-by-side-grid .col-right {
      display: table-cell;
      width: 68%;
      vertical-align: top;
    }

    /* ── Kotak Kenaikan Kelas ── */
    .kenaikan-box {
      border: 1px solid #000;
      text-align: center;
      padding: 6px 10px;
      margin-top: 8px;
      margin-bottom: 8px;
      font-size: 9.5pt;
      font-weight: bold;
      page-break-inside: avoid;
      break-inside: avoid;
    }

    /* ── Tanda Tangan ── */
    .ttd-area {
      margin-top: 14px;
      width: 100%;
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .ttd-row {
      display: table;
      width: 100%;
    }
    .ttd-left {
      display: table-cell;
      width: 50%;
      text-align: left;
      vertical-align: top;
      font-size: 9.5pt;
    }
    .ttd-right {
      display: table-cell;
      width: 50%;
      text-align: center;
      vertical-align: top;
      font-size: 9.5pt;
      padding-left: 100px;
    }
    .ttd-center {
      margin-top: 6px;
      text-align: center;
      font-size: 9.5pt;
    }
    .ttd-space {
      height: 52px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .ttd-img {
      max-height: 50px;
      max-width: 140px;
      display: block;
      margin: 0 auto;
    }
    .ttd-name {
      font-weight: bold;
      text-decoration: underline;
    }
    .ttd-nip {
      font-size: 9pt;
      margin-top: 1px;
    }
    .ttd-dots {
      letter-spacing: 1px;
    }

    /* ── Footer Dokumen Rapor (Catatan Kaki Selalu di Bawah Halaman) ── */
    .rapor-footer {
      flex-shrink: 0;
      border-top: 1px solid #000;
      padding-top: 4px;
      margin-top: auto;
      display: table;
      width: 100%;
      font-size: 8.5pt;
      position: relative;
      z-index: 1;
    }
    .footer-left {
      display: table-cell;
      text-align: left;
    }
    .footer-right {
      display: table-cell;
      text-align: right;
    }

    /* ── Mode Layar Browser (Screen Preview) ── */
    @media screen {
      body {
        background-color: #d1d5db;
        padding: 20px 0;
      }
      .rapor-sheet, .rapor-sheet-last {
        background: #fff;
        width: 210mm;
        min-height: 297mm;
        padding: 10mm 15mm;
        margin: 0 auto 25px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      }
    }

    /* ── Mode Cetak (Print / PDF) ── */
    @media print {
      .no-print { display: none !important; }
      body {
        margin: 0;
        background: #fff;
      }
      .rapor-sheet, .rapor-sheet-last {
        width: 100%;
        height: 272mm;
        min-height: 272mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
    }
  </style>
</head>
<body onload="<?= $isSingle ? 'window.print()' : '' ?>">

  <!-- Toolbar untuk browser sebelum print -->
  <div class="no-print">
    <button onclick="window.print()" class="btn-print">🖨️ Cetak Dokumen Nilai Rapor</button>
    <button onclick="window.close()" class="btn-close-window">❌ Tutup</button>
    <span style="color:#555;font-size:9.5pt;margin-left:10px;">
      Rapor Semester – Kelas <?= esc($class['name'] ?? '-') ?> –
      Semester <?= esc($semester) ?> –
      Tahun Ajaran <?= esc($activeYear['year'] ?? '-') ?> | <?= count($reportData) ?> Siswa
    </span>
  </div>

<?php foreach ($reportData as $entry):
    $stu       = $entry['student'];
    $rows      = $entry['rows'];
    $koku      = $entry['kokurikuler'];
    $ekskul    = $entry['ekskul'];
    $att       = $entry['attendance'];
    $waliNote  = $entry['wali_note'];
    $promStat  = $entry['prom_status'];
    $promClass = $entry['prom_class'];
    $waliSig   = $entry['wali_signature_base64'] ?? '';

    // ── Perhitungan Tinggi Dinamis untuk Penempatan Kokurikuler & Ekstrakurikuler ──
    $mapelHeight = 35; // header tabel
    foreach ($rows as $r) {
        $descLen = mb_strlen($r['deskripsi'] ?? '');
        $lines = max(1, ceil($descLen / 50));
        $mapelHeight += 30 + ($lines * 16);
    }

    $kokuText = '';
    if (!empty($koku)) {
        foreach ($koku as $k) {
            $kokuText .= ($k['deskripsi'] ?? '') . ' ';
        }
    } else {
        $kokuText = 'Pada semester ini, ananda menunjukkan capaian yang baik dalam penguatan profil lulusan...';
    }
    $kokuLines = max(2, ceil(mb_strlen($kokuText) / 70));
    $kokuHeight = 32 + ($kokuLines * 16) + 18;

    $ekskulCount = max(1, count($ekskul));
    $ekskulHeight = 32 + ($ekskulCount * 28);

    // Batas aman tinggi seluruh tabel di Halaman 1 agar menyisakan ruang lega sebelum footer
    $maxTableHeightPage1 = 680;

    $kokuOnPage1 = false;
    $ekskulOnPage1 = false;

    if (($mapelHeight + $kokuHeight) <= $maxTableHeightPage1) {
        $kokuOnPage1 = true;
        if (($mapelHeight + $kokuHeight + $ekskulHeight) <= $maxTableHeightPage1) {
            $ekskulOnPage1 = true;
        }
    }

    // Helper render isi Kokurikuler
    $renderKokuBlock = function ($kokuList) {
        ?>
        <table class="rapor-table" style="margin-top: 10px;">
          <thead>
            <tr>
              <th style="text-align: center; font-weight: bold; padding: 4px;">Kokurikuler</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="padding: 7px 10px; font-size: 9pt; text-align: justify; line-height: 1.35;">
                <?php if (empty($kokuList)): ?>
                  <p style="margin:0;">Pada semester ini, ananda menunjukkan capaian yang baik dalam penguatan profil pelajar dan profil kelulusan, yang ditunjukkan melalui keikutsertaan aktif dalam seluruh rangkaian kegiatan projek kokurikuler dan pembiasaan karakter di satuan pendidikan.</p>
                <?php else: ?>
                  <?php foreach ($kokuList as $kItem): ?>
                    <?php if (!empty($kItem['deskripsi'])): ?>
                      <p style="margin:0 0 6px 0; text-align:justify;"><?= nl2br(esc($kItem['deskripsi'])) ?></p>
                    <?php else: ?>
                      <p style="margin:0 0 6px 0; text-align:justify;">Pada semester ini, ananda menunjukkan capaian yang baik dalam penguatan profil pelajar melalui kegiatan projek kokurikuler <strong><?= esc($kItem['kegiatan'] ?? $kItem['tema']) ?></strong>.</p>
                    <?php endif; ?>
                  <?php endforeach; ?>
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>
        <?php
    };

    // Helper render isi Ekstrakurikuler
    $renderEkskulBlock = function ($ekskulList) {
        ?>
        <table class="rapor-table" style="margin-top: 10px;">
          <thead>
            <tr>
              <th class="nomor" style="text-align: center;">No</th>
              <th style="width: 240px; text-align: center;">Ekstrakurikuler</th>
              <th style="text-align: center;">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($ekskulList)): ?>
              <tr>
                <td colspan="3" style="text-align: center; color: #666; font-style: italic; padding: 5px;">
                  Tidak mengikuti kegiatan ekstrakurikuler pada semester ini.
                </td>
              </tr>
            <?php else: ?>
              <?php $ne = 1; foreach ($ekskulList as $ek): ?>
                <tr>
                  <td class="nomor" style="text-align: center;"><?= $ne++ ?></td>
                  <td><?= esc($ek['ekskul_name']) ?></td>
                  <td style="font-size: 9pt; text-align: justify;"><?= esc(!empty($ek['description']) ? $ek['description'] : ('Aktif dalam mengikuti kegiatan Ekstrakurikuler ' . $ek['ekskul_name'])) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        <?php
    };
?>
  <div class="student-wrapper">

    <!-- ══════════════════════════════════════════════════════
         HALAMAN 1: IDENTITAS SISWA & MATA PELAJARAN
         (+ KOKURIKULER & EKSTRAKURIKULER JIKA SPACE CUKUP)
         ══════════════════════════════════════════════════════ -->
    <div class="rapor-sheet">
      <?php if ($showWatermark && !empty($logoBase64)): ?>
        <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Watermark"></div>
      <?php endif; ?>

      <div class="sheet-body">
        <!-- Identitas Siswa -->
        <?= $renderHeader($stu, $class, $school, $fase, $semester, $activeYear) ?>

        <!-- Judul Rapor -->
        <div class="judul-laporan">LAPORAN HASIL BELAJAR</div>

        <!-- Tabel Mata Pelajaran -->
        <table class="rapor-table">
          <thead>
            <tr>
              <th class="nomor">No</th>
              <th style="width: 195px; text-align: center;">Mata Pelajaran</th>
              <th class="angka">Nilai Akhir</th>
              <th style="text-align: center;">Capaian Kompetensi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($rows as $row): ?>
              <tr>
                <td class="nomor"><?= $no++ ?></td>
                <td><?= esc($row['subject_name']) ?></td>
                <td class="angka"><?= $row['nilai'] !== null ? number_format($row['nilai'], 0) : '-' ?></td>
                <td class="desc"><?= nl2br(esc($row['deskripsi'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>

        <!-- Kokurikuler (Naik ke Hal 1 jika space cukup) -->
        <?php if ($kokuOnPage1): ?>
          <?= $renderKokuBlock($koku) ?>
        <?php endif; ?>

        <!-- Ekstrakurikuler (Naik ke Hal 1 jika space cukup) -->
        <?php if ($ekskulOnPage1): ?>
          <?= $renderEkskulBlock($ekskul) ?>
        <?php endif; ?>
      </div>

      <!-- Footer Halaman 1 (Selalu di Posisi Bawah Halaman) -->
      <?= $renderFooter($stu, $class, 1) ?>
    </div><!-- /.rapor-sheet (Halaman 1) -->


    <!-- ══════════════════════════════════════════════════════
         HALAMAN 2: SISA KOKURIKULER/EKSTRAKURIKULER,
         KETIDAKHADIRAN, CATATAN WALI KELAS, KENAIKAN KELAS,
         TANGGAPAN ORANG TUA, & TANDA TANGAN
         ══════════════════════════════════════════════════════ -->
    <div class="rapor-sheet-last">
      <?php if ($showWatermark && !empty($logoBase64)): ?>
        <div class="watermark-container"><img src="<?= $logoBase64 ?>" alt="Watermark"></div>
      <?php endif; ?>

      <div class="sheet-body">
        <!-- Identitas Siswa di Halaman 2 -->
        <?= $renderHeader($stu, $class, $school, $fase, $semester, $activeYear) ?>

        <!-- Kokurikuler (Jika tidak muat di Halaman 1) -->
        <?php if (!$kokuOnPage1): ?>
          <?= $renderKokuBlock($koku) ?>
        <?php endif; ?>

        <!-- Ekstrakurikuler (Jika tidak muat di Halaman 1) -->
        <?php if (!$ekskulOnPage1): ?>
          <?= $renderEkskulBlock($ekskul) ?>
        <?php endif; ?>

        <!-- Ketidakhadiran & Catatan Wali Kelas Sejajar -->
        <div class="side-by-side-grid" style="margin-top: 8px;">
          <!-- Kolom Kiri: Ketidakhadiran -->
          <div class="col-left">
            <table class="rapor-table" style="margin-bottom: 0;">
              <thead>
                <tr>
                  <th colspan="2" style="text-align: center; font-weight: bold; padding: 4px;">Ketidakhadiran</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="width: 58%; padding: 4px 6px;">Sakit</td>
                  <td style="width: 42%; padding: 4px 6px; text-align: left;">: &nbsp;<?= (int) ($att['sakit'] ?? 0) ?> hari</td>
                </tr>
                <tr>
                  <td style="padding: 4px 6px;">Izin</td>
                  <td style="padding: 4px 6px; text-align: left;">: &nbsp;<?= (int) ($att['izin'] ?? 0) ?> hari</td>
                </tr>
                <tr>
                  <td style="padding: 4px 6px;">Tanpa Keterangan</td>
                  <td style="padding: 4px 6px; text-align: left;">: &nbsp;<?= (int) ($att['alpa'] ?? 0) ?> hari</td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Kolom Kanan: Catatan Wali Kelas -->
          <div class="col-right">
            <table class="rapor-table" style="margin-bottom: 0;">
              <thead>
                <tr>
                  <th style="text-align: center; font-weight: bold; padding: 4px;">Catatan Wali Kelas</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td style="padding: 6px 8px; font-size: 8.8pt; line-height: 1.35; text-align: justify; min-height: 75px; vertical-align: top;">
                    <?= !empty($waliNote) ? nl2br(esc($waliNote)) : '<span style="color:#888;font-style:italic;">Belum ada catatan wali kelas.</span>' ?>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Keterangan Kenaikan Kelas (Semester 2) -->
        <?php if ($semester === '2'): ?>
          <div class="kenaikan-box">
            Keterangan Kenaikan Kelas &nbsp;: &nbsp;
            <?php if ($promStat === 'naik'): ?>
              Naik ke kelas <?= esc($promClass ?: 'berikutnya') ?>
            <?php elseif ($promStat === 'tinggal'): ?>
              Tinggal di kelas <?= esc($class['name']) ?>
            <?php elseif ($promStat === 'lulus'): ?>
              Lulus dari Satuan Pendidikan
            <?php else: ?>
              Naik ke kelas <?= esc($promClass ?: 'berikutnya') ?>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Tanggapan Orang Tua / Wali Murid -->
        <table class="rapor-table" style="margin-top: 8px;">
          <thead>
            <tr>
              <th style="text-align: center; font-weight: bold; padding: 4px;">Tanggapan Orang Tua/Wali Murid</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td style="height: 52px; vertical-align: top; padding: 6px;">
                &nbsp;
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Tanda Tangan Resmi Rapor (Format Sesuai Gambar) -->
        <div class="ttd-area">
          <div class="ttd-row">
            <div class="ttd-left">
              Orang Tua Murid
              <div class="ttd-space"></div>
              <div class="ttd-dots">....................................................</div>
            </div>
            <div class="ttd-right">
              <?= esc($titimangsaCity) ?>, <?= esc($indoTitimangsaDate) ?><br>
              Wali Kelas
              <div class="ttd-space">
                <?php if ($showSignature && !empty($waliSig)): ?>
                  <img src="<?= $waliSig ?>" alt="TTD Wali Kelas" class="ttd-img">
                <?php endif; ?>
              </div>
              <div class="ttd-name"><?= esc($class['wali_name'] ?? 'Wali Kelas') ?></div>
              <div class="ttd-nip">NIP. <?= esc(!empty($class['wali_nip']) ? $class['wali_nip'] : '-') ?></div>
            </div>
          </div>

          <div class="ttd-center">
            Kepala Sekolah
            <div class="ttd-space">
              <?php if ($showSignature && !empty($principalSigBase64)): ?>
                <img src="<?= $principalSigBase64 ?>" alt="TTD Kepala Sekolah" class="ttd-img">
              <?php endif; ?>
            </div>
            <div class="ttd-name"><?= esc($school['headmaster'] ?? 'Kepala Sekolah') ?></div>
            <div class="ttd-nip">NIP. <?= esc(!empty($school['principal_nip']) ? $school['principal_nip'] : '-') ?></div>
          </div>
        </div>
      </div>

      <!-- Footer Halaman 2 (Selalu di Posisi Bawah Halaman) -->
      <?= $renderFooter($stu, $class, 2) ?>
    </div><!-- /.rapor-sheet-last (Halaman 2) -->

  </div><!-- /.student-wrapper -->
<?php endforeach; ?>

</body>
</html>
